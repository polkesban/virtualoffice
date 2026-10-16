<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Services\AI\RagService;
use App\Support\AiProviderModelResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ConciergeController extends Controller
{
    public function __construct(private readonly RagService $rag) {}

    /**
     * POST /api/concierge/chat
     *
     * Rate-limited to 20 requests / minute (set in routes).
     */
    public function chat(Request $request): JsonResponse
    {
        $supportedLanguages = array_keys((array) config('ai.concierge.supported_languages', ['id' => 'Bahasa Indonesia', 'en' => 'English']));

        $validated = $request->validate([
            'message'    => 'required|string|min:2|max:1000',
            'session_id' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'language'   => ['nullable', 'string', Rule::in($supportedLanguages)],
            'page_path'  => 'nullable|string|max:255',
            'page_title' => 'nullable|string|max:255',
        ]);

        $question    = strip_tags(trim($validated['message']));
        $language    = $validated['language'] ?? config('ai.concierge.default_language', 'id');
        $sessionId   = $validated['session_id']
            ?? sha1(($request->ip() ?? 'guest') . '|' . ($request->userAgent() ?? 'browser'));
        $historyKey  = 'concierge_history:' . $sessionId . ':' . $language;
        $history     = Cache::get($historyKey, []);
        $maxMessages = max(2, config('ai.concierge.max_history', 6) * 2);
        $ttlMinutes  = max(30, (int) config('session.lifetime', 120));
        $pageContext = array_filter([
            'path' => isset($validated['page_path']) ? trim((string) $validated['page_path']) : null,
            'title' => isset($validated['page_title']) ? trim((string) $validated['page_title']) : null,
        ], fn ($value) => is_string($value) && $value !== '');

        try {
            $result = $this->rag->query($question, $history, $language, $pageContext);

            $history[] = ['role' => 'user', 'content' => $question];
            $history[] = ['role' => 'assistant', 'content' => $result['answer']];

            Cache::put(
                $historyKey,
                array_slice($history, -$maxMessages),
                now()->addMinutes($ttlMinutes)
            );

            AiInteraction::log([
                'interaction_type' => 'chatbot',
                'prompt_preview'   => mb_substr($question, 0, 200),
                'detected_language'=> $language,
                'model_used'       => $result['model'],
                'was_fallback'     => empty($result['sources']),
            ]);

            return response()->json([
                'answer'  => $result['answer'],
                'sources' => $result['sources'],
                'language'=> $language,
            ]);

        } catch (\Throwable $e) {
            try {
                Log::error('[Concierge] query failed', [
                    'error'      => $e->getMessage(),
                    'provider'   => AiProviderModelResolver::activeProvider(),
                    'session_id' => $sessionId,
                ]);
            } catch (\Throwable) {
                // Never let a logging failure turn a handled API error into a 500.
            }

            return response()->json([
                'answer'  => $language === 'en'
                    ? 'Sorry, a technical error occurred. Please try again shortly, or contact us at info@polkesban.ac.id.'
                    : 'Maaf, terjadi kesalahan teknis. Silakan coba lagi sebentar, atau hubungi kami di info@polkesban.ac.id.',
                'sources' => [],
                'error'   => true,
                'language'=> $language,
            ]);
        }
    }
}

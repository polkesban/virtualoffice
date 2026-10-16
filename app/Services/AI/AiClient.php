<?php

namespace App\Services\AI;

use App\Models\AiApiKey;
use App\Support\AiProviderModelResolver;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Provider-agnostic AI client.
 * Supports OpenAI, OpenRouter, and Google Gemini, switchable via AI_PROVIDER env.
 *
 * For Gemini, multiple API keys can be stored in the `ai_api_keys` table.
 * On quota exhaustion (HTTP 429 or RESOURCE_EXHAUSTED), the key is marked
 * exhausted and the next available key is tried automatically.
 */
class AiClient
{
    private const OPENROUTER_FALLBACK_MODELS = [
        'openrouter/free',
        'meta-llama/llama-3.3-70b-instruct:free',
        'google/gemma-4-31b-it:free',
        'meta-llama/llama-3.2-3b-instruct:free',
        'qwen/qwen3-next-80b-a3b-instruct:free',
    ];

    private readonly string $provider;

    public function __construct()
    {
        $this->provider = AiProviderModelResolver::activeProvider();
    }

    // ── Gemini multi-key pool ──────────────────────────────────────────────────

    /**
     * Returns an ordered list of Gemini API keys to try.
     * DB keys (priority-ordered, non-exhausted) come first.
     * Falls back to the .env GEMINI_API_KEY as a last resort.
     *
     * @return list<array{id: int|null, key: string}>
     */
    private function geminiKeyPool(): array
    {
        $pool = [];

        try {
            $dbKeys = AiApiKey::available('gemini')->get();
            foreach ($dbKeys as $row) {
                $pool[] = ['id' => $row->id, 'key' => $row->api_key];
            }
        } catch (\Throwable) {
            // Table may not exist yet during first migration
        }

        // Always add the .env key as final fallback (id = null = not tracked)
        $envKey = config('ai.gemini.api_key');
        if ($envKey) {
            $pool[] = ['id' => null, 'key' => $envKey];
        }

        return $pool;
    }

    /**
     * Returns an ordered list of OpenRouter API keys to try.
     * DB keys come first, then the .env OPENROUTER_API_KEY fallback.
     *
     * @return list<array{id: int|null, key: string}>
     */
    private function openrouterKeyPool(): array
    {
        $pool = [];
        try {
            $dbKeys = AiApiKey::available('openrouter')->get();
            foreach ($dbKeys as $row) {
                $pool[] = ['id' => $row->id, 'key' => $row->api_key];
            }
        } catch (\Throwable) {
            // Table may not exist yet during first migration
        }

        $envKey = config('ai.openrouter.api_key');
        if ($envKey) {
            $pool[] = ['id' => null, 'key' => $envKey];
        }

        return $pool;
    }

    /**
     * Returns true when the response indicates quota exhaustion.
     * Handles both Gemini REST API error formats.
     */
    private function isQuotaError(Response $response): bool
    {
        $status  = $response->json('error.status') ?? '';
        $message = strtolower($response->json('error.message') ?? $response->body());

        return in_array($status, ['RESOURCE_EXHAUSTED', 'QUOTA_EXCEEDED'], true)
            || str_contains($message, 'resource_exhausted')
            || str_contains($message, 'quota')
            || $response->status() === 429;
    }

    /**
     * Mark a DB key as exhausted so it won't be tried again until reset.
     */
    private function markExhausted(?int $keyId): void
    {
        if ($keyId === null) return;

        try {
            AiApiKey::where('id', $keyId)->update([
                'is_exhausted' => true,
                'exhausted_at' => now(),
                'fail_count'   => \DB::raw('fail_count + 1'),
            ]);
        } catch (\Throwable) {}
    }

    /**
     * Update last_used_at on success.
     */
    private function markUsed(?int $keyId): void
    {
        if ($keyId === null) return;

        try {
            AiApiKey::where('id', $keyId)->update([
                'last_used_at' => now(),
                'fail_count'   => 0,
            ]);
        } catch (\Throwable) {}
    }

    /**
     * Increment failure counters without removing the key from circulation.
     */
    private function markFailed(?int $keyId): void
    {
        if ($keyId === null) return;

        try {
            AiApiKey::where('id', $keyId)->update([
                'last_used_at' => now(),
                'fail_count'   => \DB::raw('fail_count + 1'),
            ]);
        } catch (\Throwable) {}
    }

    private function isCredentialError(Response $response): bool
    {
        if (! in_array($response->status(), [400, 401, 403], true)) {
            return false;
        }

        $status = strtoupper((string) ($response->json('error.status') ?? ''));
        $message = strtolower((string) ($response->json('error.message') ?? $response->json('error') ?? $response->body()));

        return in_array($status, ['UNAUTHENTICATED', 'PERMISSION_DENIED'], true)
            || str_contains($message, 'api key not valid')
            || str_contains($message, 'invalid api key')
            || str_contains($message, 'api_key_invalid')
            || str_contains($message, 'permission denied')
            || str_contains($message, 'authentication');
    }

    private function shouldTryNextGeminiKey(Response $response): bool
    {
        return $this->isQuotaError($response) || $this->isCredentialError($response);
    }

    private function isOpenAiCompatibleQuotaError(Response $response): bool
    {
        if (! in_array($response->status(), [429, 503], true)) {
            return false;
        }

        $status = strtoupper((string) ($response->json('error.type') ?? $response->json('error.code') ?? ''));
        $message = strtolower((string) ($response->json('error.message') ?? $response->json('error') ?? $response->body()));

        return in_array($status, ['INSUFFICIENT_QUOTA', 'RATE_LIMIT_EXCEEDED'], true)
            || str_contains($message, 'quota')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'resource exhausted');
    }

    private function isOpenAiCompatibleCredentialError(Response $response): bool
    {
        if (! in_array($response->status(), [401, 403], true)) {
            return false;
        }

        $message = strtolower((string) ($response->json('error.message') ?? $response->json('error') ?? $response->body()));

        return str_contains($message, 'invalid api key')
            || str_contains($message, 'incorrect api key')
            || str_contains($message, 'authentication')
            || str_contains($message, 'unauthorized')
            || str_contains($message, 'forbidden');
    }

    private function shouldTryNextOpenRouterKey(Response $response): bool
    {
        return $this->isOpenAiCompatibleQuotaError($response)
            || $this->isOpenAiCompatibleCredentialError($response);
    }

    private function handleOpenRouterKeyFailure(Response $response, ?int $keyId, string $context): void
    {
        if ($this->isOpenAiCompatibleQuotaError($response)) {
            $this->markFailed($keyId);

            return;
        }

        if ($this->isOpenAiCompatibleCredentialError($response)) {
            $this->markFailed($keyId);

            Log::warning('Skipping unusable OpenRouter API key.', [
                'context' => $context,
                'key_id' => $keyId,
                'status' => $response->status(),
                'error' => $response->json('error.message') ?? $response->json('error') ?? mb_substr($response->body(), 0, 200),
            ]);
        }
    }

    private function handleGeminiKeyFailure(Response $response, ?int $keyId, string $context): void
    {
        if ($this->isQuotaError($response)) {
            $this->markExhausted($keyId);

            return;
        }

        if ($this->isCredentialError($response)) {
            if ($keyId !== null) {
                try {
                    \App\Models\AiApiKey::where('id', $keyId)->update([
                        'is_active' => false,
                        'is_exhausted' => true,
                        'exhausted_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
            }

            Log::warning('Deactivating unusable Gemini API key due to credential error.', [
                'context' => $context,
                'key_id' => $keyId,
                'status' => $response->status(),
                'error' => $response->json('error.message') ?? $response->json('error') ?? mb_substr($response->body(), 0, 200),
            ]);
        }
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Text chat completion.
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function chat(array $messages, float $temperature = 0.5, int $maxTokens = 1024, bool $jsonMode = false): string
    {
        $primary = $this->provider;
        $order = [$primary];
        foreach (['gemini', 'openrouter', 'openai'] as $p) {
            if ($p !== $primary) {
                $order[] = $p;
            }
        }

        $lastException = null;

        foreach ($order as $prov) {
            // Check configuration to avoid useless API calls
            if ($prov === 'gemini') {
                $hasKeys = false;
                foreach ($this->geminiKeyPool() as $k) {
                    if ($k['key'] && $k['key'] !== 'GANTI_DENGAN_API_KEY_GEMINI') {
                        $hasKeys = true;
                        break;
                    }
                }
                if (!$hasKeys) continue;
            }
            if ($prov === 'openrouter') {
                if (count($this->openrouterKeyPool()) === 0) continue;
            }
            if ($prov === 'openai') {
                $key = config('ai.openai.api_key');
                if (!$key || str_contains($key, 'GANTI_DENGAN_API_KEY')) continue;
            }

            try {
                $ans = match ($prov) {
                    'gemini' => $this->geminiChat($messages, $temperature, $maxTokens, $jsonMode),
                    'openrouter' => $this->openrouterChat($messages, $temperature, $maxTokens, $jsonMode),
                    default  => $this->openaiChat($messages, $temperature, $maxTokens, $jsonMode),
                };
                if (trim($ans) === '') {
                    throw new RuntimeException("Empty response received from provider {$prov}");
                }
                return $ans;
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("[AiClient] Provider '{$prov}' chat failed. Trying next fallback. Error: " . $e->getMessage());
            }
        }

        throw new RuntimeException('[AiClient] All chat providers failed. Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown'));
    }

    /**
     * Vision: describe / extract information from a local image file.
     */
    public function vision(string $imagePath, string $prompt, float $temperature = 0.5, int $maxTokens = 2048): string
    {
        $primary = $this->provider;
        $order = [$primary];
        foreach (['gemini', 'openrouter', 'openai'] as $p) {
            if ($p !== $primary) {
                $order[] = $p;
            }
        }

        $lastException = null;
        foreach ($order as $prov) {
            if ($prov === 'gemini') {
                $hasKeys = false;
                foreach ($this->geminiKeyPool() as $k) {
                    if ($k['key'] && $k['key'] !== 'GANTI_DENGAN_API_KEY_GEMINI') {
                        $hasKeys = true;
                        break;
                    }
                }
                if (!$hasKeys) continue;
            }
            if ($prov === 'openrouter') {
                if (count($this->openrouterKeyPool()) === 0) continue;
            }
            if ($prov === 'openai') {
                $key = config('ai.openai.api_key');
                if (!$key || str_contains($key, 'GANTI_DENGAN_API_KEY')) continue;
            }

            try {
                return match ($prov) {
                    'gemini' => $this->geminiVision($imagePath, $prompt, $temperature, $maxTokens),
                    'openrouter' => $this->openrouterVision($imagePath, $prompt, $temperature, $maxTokens),
                    default  => $this->openaiVision($imagePath, $prompt, $temperature, $maxTokens),
                };
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("[AiClient] Provider '{$prov}' vision failed. Trying next fallback. Error: " . $e->getMessage());
            }
        }

        throw new RuntimeException('[AiClient] All vision providers failed. Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown'));
    }

    /**
     * Generate a text embedding vector (float[]).
     */
    public function embed(string $text): array
    {
        $primary = $this->provider;
        $order = [$primary];
        foreach (['gemini', 'openrouter', 'openai'] as $p) {
            if ($p !== $primary) {
                $order[] = $p;
            }
        }

        $lastException = null;
        foreach ($order as $prov) {
            if ($prov === 'gemini') {
                $hasKeys = false;
                foreach ($this->geminiKeyPool() as $k) {
                    if ($k['key'] && $k['key'] !== 'GANTI_DENGAN_API_KEY_GEMINI') {
                        $hasKeys = true;
                        break;
                    }
                }
                if (!$hasKeys) continue;
            }
            if ($prov === 'openrouter') {
                if (count($this->openrouterKeyPool()) === 0) continue;
            }
            if ($prov === 'openai') {
                $key = config('ai.openai.api_key');
                if (!$key || str_contains($key, 'GANTI_DENGAN_API_KEY')) continue;
            }

            try {
                return match ($prov) {
                    'gemini' => $this->geminiEmbed($text),
                    'openrouter' => $this->openrouterEmbed($text),
                    default  => $this->openaiEmbed($text),
                };
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("[AiClient] Provider '{$prov}' embed failed. Trying next fallback. Error: " . $e->getMessage());
            }
        }

        throw new RuntimeException('[AiClient] All embed providers failed. Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown'));
    }

    /**
     * Transcribe an audio recording into plain text.
     */
    public function transcribeAudio(string $audioPath, string $language = 'id'): string
    {
        $primary = $this->provider;
        $order = [$primary];
        foreach (['gemini', 'openrouter', 'openai'] as $p) {
            if ($p !== $primary) {
                $order[] = $p;
            }
        }

        $lastException = null;
        foreach ($order as $prov) {
            if ($prov === 'gemini') {
                $hasKeys = false;
                foreach ($this->geminiKeyPool() as $k) {
                    if ($k['key'] && $k['key'] !== 'GANTI_DENGAN_API_KEY_GEMINI') {
                        $hasKeys = true;
                        break;
                    }
                }
                if (!$hasKeys) continue;
            }
            if ($prov === 'openrouter') {
                if (count($this->openrouterKeyPool()) === 0) continue;
            }
            if ($prov === 'openai') {
                $key = config('ai.openai.api_key');
                if (!$key || str_contains($key, 'GANTI_DENGAN_API_KEY')) continue;
            }

            try {
                return match ($prov) {
                    'gemini' => $this->geminiTranscribeAudio($audioPath, $language),
                    'openrouter' => $this->openrouterTranscribeAudio($audioPath, $language),
                    default  => $this->openaiTranscribeAudio($audioPath, $language),
                };
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("[AiClient] Provider '{$prov}' transcribeAudio failed. Trying next fallback. Error: " . $e->getMessage());
            }
        }

        throw new RuntimeException('[AiClient] All transcribeAudio providers failed. Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown'));
    }

    // ── OpenAI ─────────────────────────────────────────────────────────────────

    private function openaiChat(array $messages, float $temperature, int $maxTokens, bool $jsonMode = false): string
    {
        $cfg = config('ai.openai');
        $model = AiProviderModelResolver::chatModel('openai');

        $body = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];
        if ($jsonMode) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->openaiHttp()->post('chat/completions', $body);

        $this->assertOk($response, 'OpenAI chat');

        return trim($response->json('choices.0.message.content') ?? '');
    }

    private function openaiVision(string $imagePath, string $prompt, float $temperature, int $maxTokens): string
    {
        $cfg     = config('ai.openai');
        $mime    = mime_content_type($imagePath) ?: 'image/jpeg';
        $b64     = base64_encode(file_get_contents($imagePath));
        $dataUrl = "data:{$mime};base64,{$b64}";

        $response = $this->openaiHttp()
            ->post('chat/completions', [
                'model'       => $cfg['vision_model'],
                'temperature' => $temperature,
                'max_tokens'  => $maxTokens,
                'messages'    => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text',      'text'      => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl, 'detail' => 'auto']],
                    ],
                ]],
            ]);

        $this->assertOk($response, 'OpenAI vision');

        return trim($response->json('choices.0.message.content') ?? '');
    }

    private function openaiEmbed(string $text): array
    {
        $cfg = config('ai.openai');

        $response = $this->openaiHttp()
            ->post('embeddings', [
                'model'      => $cfg['embed_model'],
                'input'      => mb_substr($text, 0, 8192),
                'dimensions' => $cfg['embed_dims'],
            ]);

        $this->assertOk($response, 'OpenAI embeddings');

        return $response->json('data.0.embedding') ?? [];
    }

    private function openaiTranscribeAudio(string $audioPath, string $language): string
    {
        $cfg = config('ai.openai');
        $stream = fopen($audioPath, 'r');

        if (! $stream) {
            throw new RuntimeException('[OpenAI transcription] Audio file could not be opened.');
        }

        try {
            $response = $this->openaiTranscriptionHttp($cfg)
                ->attach('file', $stream, basename($audioPath))
                ->post('audio/transcriptions', [
                    'model' => $cfg['transcribe_model'],
                    'language' => $language,
                    'response_format' => 'json',
                    'prompt' => 'Transkripsikan rekaman ini dalam Bahasa Indonesia secara akurat. Kembalikan hanya teks transkrip tanpa ringkasan.',
                ]);
        } finally {
            fclose($stream);
        }

        $this->assertOk($response, 'OpenAI transcription');

        return $this->normalizePlainText($response->json('text') ?? '');
    }

    private function openaiHttp(): PendingRequest
    {
        $cfg     = config('ai.openai');
        $headers = ['Authorization' => "Bearer {$cfg['api_key']}"];

        if ($cfg['org_id'] ?? null) {
            $headers['OpenAI-Organization'] = $cfg['org_id'];
        }

        return Http::baseUrl('https://api.openai.com/v1/')
            ->withHeaders($headers)
            ->timeout($cfg['timeout'])
            ->retry(2, 600, throw: false);
    }

    private function openaiTranscriptionHttp(array $cfg): PendingRequest
    {
        $headers = ['Authorization' => "Bearer {$cfg['api_key']}"];

        if ($cfg['org_id'] ?? null) {
            $headers['OpenAI-Organization'] = $cfg['org_id'];
        }

        return Http::baseUrl('https://api.openai.com/v1/')
            ->withHeaders($headers)
            ->timeout($cfg['transcribe_timeout'] ?? $cfg['timeout'])
            ->retry(1, 800, throw: false);
    }

    // ── OpenRouter ────────────────────────────────────────────────────────────

    private function openrouterChat(array $messages, float $temperature, int $maxTokens, bool $jsonMode = false): string
    {
        $cfg = config('ai.openrouter');
        $primaryModel = AiProviderModelResolver::chatModel('openrouter');

        $models = [$primaryModel];
        foreach (self::OPENROUTER_FALLBACK_MODELS as $m) {
            if ($m !== $primaryModel) {
                $models[] = $m;
            }
        }

        $lastException = null;

        foreach ($models as $currentModel) {
            foreach ($this->openrouterKeyPool() as ['id' => $keyId, 'key' => $key]) {
                try {
                    $body = [
                        'model' => $currentModel,
                        'messages' => $messages,
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                    ];
                    if ($jsonMode) {
                        $body['response_format'] = ['type' => 'json_object'];
                    }

                    $response = $this->openrouterHttp($cfg, $key)->post('chat/completions', $body);

                    if ($this->isOpenAiCompatibleCredentialError($response)) {
                        $this->handleOpenRouterKeyFailure($response, $keyId, 'OpenRouter chat');
                        continue;
                    }

                    if ($this->isOpenAiCompatibleQuotaError($response) || $response->status() === 429 || $response->status() === 503) {
                        \Illuminate\Support\Facades\Log::warning("[OpenRouter chat] Model {$currentModel} rate limited/exhausted on key ID {$keyId}. Trying next option.");
                        continue;
                    }

                    $this->assertOk($response, 'OpenRouter chat');
                    $ans = trim((string) ($response->json('choices.0.message.content') ?? ''));
                    if ($ans === '') {
                        throw new RuntimeException("Empty text response from model {$currentModel}");
                    }
                    $this->markUsed($keyId);
                    return $ans;
                } catch (\Throwable $e) {
                    $lastException = $e;
                    \Illuminate\Support\Facades\Log::warning("[OpenRouter chat] Exception with model {$currentModel} on key ID {$keyId}: " . $e->getMessage());
                }
            }
        }

        throw new RuntimeException('[OpenRouter chat] All models and keys in the fallback pool are exhausted. Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown'));
    }

    private function openrouterVision(string $imagePath, string $prompt, float $temperature, int $maxTokens): string
    {
        $cfg     = config('ai.openrouter');
        $mime    = mime_content_type($imagePath) ?: 'image/jpeg';
        $b64     = base64_encode(file_get_contents($imagePath));
        $dataUrl = "data:{$mime};base64,{$b64}";

        foreach ($this->openrouterKeyPool() as ['id' => $keyId, 'key' => $key]) {
            $response = $this->openrouterHttp($cfg, $key)
                ->post('chat/completions', [
                    'model' => $cfg['vision_model'],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                        ],
                    ]],
                ]);

            if ($this->shouldTryNextOpenRouterKey($response)) {
                $this->handleOpenRouterKeyFailure($response, $keyId, 'OpenRouter vision');
                continue;
            }

            $this->assertOk($response, 'OpenRouter vision');
            $this->markUsed($keyId);

            return trim((string) ($response->json('choices.0.message.content') ?? ''));
        }

        throw new RuntimeException('[OpenRouter vision] All API keys are exhausted or unavailable.');
    }

    private function openrouterEmbed(string $text): array
    {
        $cfg = config('ai.openrouter');
        $payload = [
            'model' => $cfg['embed_model'],
            'input' => mb_substr($text, 0, 8192),
        ];

        if (($cfg['embed_dims'] ?? 0) > 0) {
            $payload['dimensions'] = $cfg['embed_dims'];
        }

        foreach ($this->openrouterKeyPool() as ['id' => $keyId, 'key' => $key]) {
            $response = $this->openrouterHttp($cfg, $key)
                ->post('embeddings', $payload);

            if ($this->shouldTryNextOpenRouterKey($response)) {
                $this->handleOpenRouterKeyFailure($response, $keyId, 'OpenRouter embed');
                continue;
            }

            $this->assertOk($response, 'OpenRouter embed');
            $this->markUsed($keyId);

            return $response->json('data.0.embedding') ?? [];
        }

        throw new RuntimeException('[OpenRouter embed] All API keys are exhausted or unavailable.');
    }

    private function openrouterTranscribeAudio(string $audioPath, string $language): string
    {
        throw new RuntimeException('[OpenRouter transcription] Audio transcription is not supported by this integration yet. Use OpenAI or Gemini for audio transcription.');
    }

    private function openrouterHttp(array $cfg, string $key): PendingRequest
    {
        $headers = [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ];

        if (! empty($cfg['site_url'])) {
            $headers['HTTP-Referer'] = $cfg['site_url'];
        }

        if (! empty($cfg['app_name'])) {
            $headers['X-Title'] = $cfg['app_name'];
        }

        return Http::baseUrl(rtrim((string) $cfg['base_url'], '/') . '/')
            ->withHeaders($headers)
            ->timeout((int) ($cfg['timeout'] ?? 45))
            ->retry(2, 600, throw: false);
    }

    // ── Gemini ─────────────────────────────────────────────────────────────────

    private function geminiChat(array $messages, float $temperature, int $maxTokens, bool $jsonMode = false): string
    {
        $cfg      = config('ai.gemini');
        $model    = AiProviderModelResolver::chatModel('gemini');
        $contents = [];
        $sysParts = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $sysParts[] = ['text' => $msg['content']];
            } else {
                $contents[] = [
                    'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]],
                ];
            }
        }

        $generationConfig = [
            'temperature'     => $temperature,
            'maxOutputTokens' => $maxTokens,
        ];

        if ($jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
            // Hindari thinking budget menghabiskan kuota token pada model 2.5
            if (str_contains(strtolower($model), '2.5')) {
                $generationConfig['thinkingConfig'] = [
                    'thinkingBudget' => 0,
                ];
            }
        }

        $payload = [
            'contents'         => $contents,
            'generationConfig' => $generationConfig,
        ];

        if ($sysParts) {
            $payload['systemInstruction'] = ['parts' => $sysParts];
        }

        foreach ($this->geminiKeyPool() as ['id' => $keyId, 'key' => $key]) {
            $response = $this->geminiHttp($cfg)
                ->post("models/{$model}:generateContent?key={$key}", $payload);

            if ($this->shouldTryNextGeminiKey($response)) {
                $this->handleGeminiKeyFailure($response, $keyId, 'Gemini chat');
                continue;
            }

            $this->assertOk($response, 'Gemini chat');
            $this->markUsed($keyId);

            return trim($response->json('candidates.0.content.parts.0.text') ?? '');
        }

        throw new RuntimeException('[Gemini chat] All API keys are exhausted or unavailable.');
    }

    private function geminiVision(string $imagePath, string $prompt, float $temperature, int $maxTokens): string
    {
        $cfg   = config('ai.gemini');
        $model = $cfg['vision_model'];
        $mime  = mime_content_type($imagePath) ?: 'image/jpeg';
        $b64   = base64_encode(file_get_contents($imagePath));

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $b64]],
                ],
            ]],
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        foreach ($this->geminiKeyPool() as ['id' => $keyId, 'key' => $key]) {
            $response = $this->geminiHttp($cfg)
                ->post("models/{$model}:generateContent?key={$key}", $payload);

            if ($this->shouldTryNextGeminiKey($response)) {
                $this->handleGeminiKeyFailure($response, $keyId, 'Gemini vision');
                continue;
            }

            $this->assertOk($response, 'Gemini vision');
            $this->markUsed($keyId);

            return trim($response->json('candidates.0.content.parts.0.text') ?? '');
        }

        throw new RuntimeException('[Gemini vision] All API keys are exhausted or unavailable.');
    }

    private function geminiEmbed(string $text): array
    {
        $cfg   = config('ai.gemini');
        $model = $cfg['embed_model'];

        $payload = [
            'model'                => "models/{$model}",
            'content'              => ['parts' => [['text' => mb_substr($text, 0, 8192)]]],
            'outputDimensionality' => (int) ($cfg['embed_dims'] ?? 0),
        ];

        foreach ($this->geminiKeyPool() as ['id' => $keyId, 'key' => $key]) {
            $response = $this->geminiHttp($cfg)
                ->post("models/{$model}:embedContent?key={$key}", $payload);

            if ($this->shouldTryNextGeminiKey($response)) {
                $this->handleGeminiKeyFailure($response, $keyId, 'Gemini embed');
                continue;
            }

            $this->assertOk($response, 'Gemini embed');
            $this->markUsed($keyId);

            return $response->json('embedding.values') ?? [];
        }

        throw new RuntimeException('[Gemini embed] All API keys are exhausted or unavailable.');
    }

    private function geminiTranscribeAudio(string $audioPath, string $language): string
    {
        $cfg = config('ai.gemini');
        $model = $cfg['transcribe_model'] ?: $cfg['chat_model'];
        $mime = mime_content_type($audioPath) ?: 'audio/mpeg';
        $b64 = base64_encode(file_get_contents($audioPath));

        $prompt = $language === 'id'
            ? 'Transkripsikan audio rapat atau kegiatan ini ke dalam Bahasa Indonesia secara akurat. Kembalikan hanya teks transkrip mentah, tanpa ringkasan, tanpa markdown, tanpa penjelasan tambahan. Jika pembicara berganti, pisahkan seperlunya dengan baris baru.'
            : 'Transcribe this audio accurately. Return only the plain transcript without summaries, markdown, or extra explanation.';

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $b64]],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 8192,
            ],
        ];

        foreach ($this->geminiKeyPool() as ['id' => $keyId, 'key' => $key]) {
            $response = $this->geminiTranscriptionHttp($cfg)
                ->post("models/{$model}:generateContent?key={$key}", $payload);

            if ($this->shouldTryNextGeminiKey($response)) {
                $this->handleGeminiKeyFailure($response, $keyId, 'Gemini transcription');
                continue;
            }

            $this->assertOk($response, 'Gemini transcription');
            $this->markUsed($keyId);

            return $this->normalizePlainText($response->json('candidates.0.content.parts.0.text') ?? '');
        }

        throw new RuntimeException('[Gemini transcription] All API keys are exhausted or unavailable.');
    }

    private function geminiHttp(array $cfg): PendingRequest
    {
        return Http::baseUrl($cfg['base_url'] . '/')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout($cfg['timeout'])
            ->retry(2, 600, throw: false);
    }

    private function geminiTranscriptionHttp(array $cfg): PendingRequest
    {
        return Http::baseUrl($cfg['base_url'] . '/')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout($cfg['transcribe_timeout'] ?? $cfg['timeout'])
            ->retry(1, 800, throw: false);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function assertOk(Response $response, string $context): void
    {
        if ($response->failed()) {
            $error = $response->json('error.message')
                  ?? $response->json('error')
                  ?? mb_substr($response->body(), 0, 300);

            throw new RuntimeException("[{$context}] API error {$response->status()}: {$error}");
        }
    }

    private function normalizePlainText(string $text): string
    {
        $text = trim($text);

        if (preg_match('/^```(?:text)?\s*(.*?)\s*```$/s', $text, $matches)) {
            $text = trim((string) ($matches[1] ?? ''));
        }

        return trim($text);
    }
}

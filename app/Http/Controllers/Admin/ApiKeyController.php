<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiApiKey;
use App\Models\AiProviderConfig;
use App\Support\AiProviderModelResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use RuntimeException;

class ApiKeyController extends Controller
{
    private function providers(): array
    {
        return [
            'gemini' => 'Google Gemini',
            'openai' => 'OpenAI',
            'openrouter' => 'OpenRouter',
            'tinymce' => 'TinyMCE Cloud',
        ];
    }

    public function index()
    {
        $keys = AiApiKey::orderBy('provider')->orderBy('priority')->orderBy('id')->get();

        return view('admin.api-keys.index', [
            'keys' => $keys,
            'providers' => $this->providers(),
            'activeProvider' => AiProviderModelResolver::activeProvider(),
            'defaultProvider' => AiProviderModelResolver::defaultProvider(),
            'activeProviderOverride' => AiProviderModelResolver::activeProviderOverride(),
            'chatModels' => AiProviderModelResolver::chatModels(),
            'chatModelDefaults' => AiProviderModelResolver::defaultChatModels(),
            'chatModelOverrides' => AiProviderModelResolver::chatModelOverrides(),
            'chatModelSuggestions' => AiProviderModelResolver::chatModelSuggestions(),
        ]);
    }

    public function updateActiveProvider(Request $request)
    {
        $provider = $request->validate([
            'active_provider' => ['required', Rule::in(AiProviderModelResolver::supportedProviders())],
        ])['active_provider'];

        AiProviderModelResolver::setActiveProvider($provider);

        return back()->with('success', 'Provider AI runtime aktif berhasil diperbarui.');
    }

    public function updateChatModels(Request $request)
    {
        $rules = [];

        foreach (AiProviderModelResolver::supportedProviders() as $provider) {
            $rules["chat_models.{$provider}"] = ['nullable', 'string', 'max:120'];
        }

        $data = $request->validate($rules);
        $chatModels = $data['chat_models'] ?? [];

        foreach (AiProviderModelResolver::supportedProviders() as $provider) {
            $chatModel = trim((string) ($chatModels[$provider] ?? ''));

            if ($chatModel === '') {
                AiProviderConfig::query()->where('provider', $provider)->delete();
                continue;
            }

            AiProviderConfig::query()->updateOrCreate(
                ['provider' => $provider],
                ['chat_model' => $chatModel]
            );
        }

        return back()->with('success', 'Model chat AI runtime berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'provider' => ['required', Rule::in(array_keys($this->providers()))],
            'api_key' => ['required', 'string', 'min:10', 'max:500'],
            'priority' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        AiApiKey::create($data + ['is_active' => true, 'is_exhausted' => false]);

        return back()->with('success', "API Key \"{$data['label']}\" berhasil ditambahkan.");
    }

    public function update(Request $request, AiApiKey $key)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'priority' => ['required', 'integer', 'min:0', 'max:99'],
            'is_active' => ['boolean'],
        ]);

        $key->update([
            'label' => $data['label'],
            'priority' => $data['priority'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', "API Key \"{$key->label}\" diperbarui.");
    }

    public function resetExhausted(AiApiKey $key)
    {
        $key->resetExhausted();

        return back()->with('success', "Status exhausted pada \"{$key->label}\" berhasil direset.");
    }

    public function resetAll(Request $request)
    {
        $provider = $request->validate([
            'provider' => ['required', Rule::in(array_keys($this->providers()))],
        ])['provider'];

        $count = AiApiKey::where('provider', $provider)
            ->where('is_exhausted', true)
            ->count();

        AiApiKey::where('provider', $provider)
            ->where('is_exhausted', true)
            ->update(['is_exhausted' => false, 'exhausted_at' => null, 'fail_count' => 0]);

        return back()->with('success', "Reset {$count} key yang exhausted.");
    }

    public function tinymceRuntime()
    {
        $keys = AiApiKey::available('tinymce')
            ->get(['id', 'label', 'api_key', 'priority'])
            ->map(fn (AiApiKey $key) => [
                'id' => $key->id,
                'label' => $key->label,
                'key' => $key->api_key,
                'priority' => $key->priority,
                'source' => 'database',
            ])
            ->values();

        $envKey = trim((string) config('editor.tinymce.api_key'));
        if ($envKey !== '') {
            $keys->push([
                'id' => null,
                'label' => 'TINYMCE_API_KEY (.env)',
                'key' => $envKey,
                'priority' => 999,
                'source' => 'env',
            ]);
        }

        return response()->json([
            'keys' => $keys->values(),
            'cdnBase' => rtrim((string) config('editor.tinymce.cdn_base', 'https://cdn.tiny.cloud/1'), '/'),
            'version' => (string) config('editor.tinymce.version', '7'),
            'cloudEnabled' => (bool) config('editor.tinymce.use_cloud', true),
            'localFallback' => true,
        ]);
    }

    public function tinymceReportUsed(AiApiKey $key)
    {
        abort_unless($key->provider === 'tinymce', 404);

        $key->markUsed();

        return response()->json(['ok' => true]);
    }

    public function tinymceReportExhausted(AiApiKey $key)
    {
        abort_unless($key->provider === 'tinymce', 404);

        $key->markExhausted();

        return response()->json(['ok' => true]);
    }

    public function test(AiApiKey $key)
    {
        if (! $key->is_active) {
            return response()->json([
                'ok' => false,
                'provider' => $key->provider,
                'label' => $key->label,
                'message' => 'API key nonaktif. Aktifkan dulu sebelum dites.',
            ], 422);
        }

        $startedAt = microtime(true);

        try {
            $result = match ($key->provider) {
                'openai' => $this->testOpenAiKey($key),
                'openrouter' => $this->testOpenRouterKey($key),
                'gemini' => $this->testGeminiKey($key),
                'tinymce' => $this->testTinyMceKey($key),
                default => throw new RuntimeException('Provider API key belum didukung untuk fitur test.'),
            };

            return response()->json([
                'ok' => true,
                'provider' => $key->provider,
                'label' => $key->label,
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $result['message'],
                'preview' => $result['preview'] ?? null,
                'meta' => $result['meta'] ?? [],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'ok' => false,
                'provider' => $key->provider,
                'label' => $key->label,
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function testOpenAiKey(AiApiKey $key): array
    {
        $cfg = config('ai.openai');
        $model = AiProviderModelResolver::chatModel('openai');
        $headers = [
            'Authorization' => 'Bearer ' . $key->api_key,
        ];

        if ($cfg['org_id'] ?? null) {
            $headers['OpenAI-Organization'] = $cfg['org_id'];
        }

        $response = Http::baseUrl('https://api.openai.com/v1/')
            ->withHeaders($headers)
            ->timeout((int) ($cfg['timeout'] ?? 45))
            ->retry(1, 400, throw: false)
            ->post('chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Balas singkat dengan: OK Poltekkes Bandung'],
                ],
                'temperature' => 0,
                'max_tokens' => 16,
            ]);

        $this->assertProviderTestOk($response, 'OpenAI', $key);

        $content = trim((string) ($response->json('choices.0.message.content') ?? ''));
        $key->markUsed();

        return [
            'message' => 'OpenAI merespons normal menggunakan key ini.',
            'preview' => $content !== '' ? $content : 'Respons kosong tetapi koneksi berhasil.',
            'meta' => [
                'model' => $model,
                'status' => $response->status(),
            ],
        ];
    }

    private function testGeminiKey(AiApiKey $key): array
    {
        $cfg = config('ai.gemini');
        $model = AiProviderModelResolver::chatModel('gemini');

        $response = Http::baseUrl(rtrim((string) $cfg['base_url'], '/') . '/')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout((int) ($cfg['timeout'] ?? 45))
            ->retry(1, 400, throw: false)
            ->post("models/{$model}:generateContent?key={$key->api_key}", [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Balas singkat dengan: OK Poltekkes Bandung'],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => 0,
                    'maxOutputTokens' => 16,
                ],
            ]);

        $this->assertProviderTestOk($response, 'Google Gemini', $key);

        $content = trim((string) ($response->json('candidates.0.content.parts.0.text') ?? ''));
        $key->markUsed();

        return [
            'message' => 'Google Gemini merespons normal menggunakan key ini.',
            'preview' => $content !== '' ? $content : 'Respons kosong tetapi koneksi berhasil.',
            'meta' => [
                'model' => $model,
                'status' => $response->status(),
            ],
        ];
    }

    private function testOpenRouterKey(AiApiKey $key): array
    {
        $cfg = config('ai.openrouter');
        $model = AiProviderModelResolver::chatModel('openrouter');
        $headers = [
            'Authorization' => 'Bearer ' . $key->api_key,
            'Content-Type' => 'application/json',
        ];

        if (! empty($cfg['site_url'])) {
            $headers['HTTP-Referer'] = $cfg['site_url'];
        }

        if (! empty($cfg['app_name'])) {
            $headers['X-Title'] = $cfg['app_name'];
        }

        $response = Http::baseUrl(rtrim((string) $cfg['base_url'], '/') . '/')
            ->withHeaders($headers)
            ->timeout((int) ($cfg['timeout'] ?? 45))
            ->retry(1, 400, throw: false)
            ->post('chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Balas singkat dengan: OK Poltekkes Bandung'],
                ],
                'temperature' => 0,
                'max_tokens' => 16,
            ]);

        $this->assertProviderTestOk($response, 'OpenRouter', $key);

        $content = trim((string) ($response->json('choices.0.message.content') ?? ''));
        $key->markUsed();

        return [
            'message' => 'OpenRouter merespons normal menggunakan key ini.',
            'preview' => $content !== '' ? $content : 'Respons kosong tetapi koneksi berhasil.',
            'meta' => [
                'model' => $model,
                'status' => $response->status(),
            ],
        ];
    }

    private function testTinyMceKey(AiApiKey $key): array
    {
        $version = (string) config('editor.tinymce.version', '7');
        $url = $this->buildTinyMceCloudUrl($key->api_key, $version);

        $response = Http::timeout(20)
            ->retry(1, 400, throw: false)
            ->get($url);

        $this->assertProviderTestOk($response, 'TinyMCE Cloud', $key);

        $body = strtolower($response->body());
        if (! str_contains($body, 'tinymce')) {
            throw new RuntimeException('TinyMCE Cloud merespons, tetapi payload script tidak valid.');
        }

        $key->markUsed();

        return [
            'message' => 'Script TinyMCE Cloud dapat dimuat menggunakan key ini.',
            'preview' => 'CDN TinyMCE memberikan file editor yang valid. Gunakan preview di bawah untuk memastikan editor aktif di browser.',
            'meta' => [
                'version' => $version,
                'status' => $response->status(),
            ],
        ];
    }

    private function assertProviderTestOk(Response $response, string $providerName, AiApiKey $key): void
    {
        if ($response->failed()) {
            if ($this->isQuotaFailure($response)) {
                $key->markExhausted();
            }

            throw new RuntimeException("{$providerName}: " . $this->providerErrorMessage($response));
        }
    }

    private function providerErrorMessage(Response $response): string
    {
        return (string) (
            $response->json('error.message')
            ?? $response->json('error.error.message')
            ?? $response->json('error')
            ?? mb_substr(trim($response->body()), 0, 220)
            ?? 'Request gagal tanpa pesan error tambahan.'
        );
    }

    private function isQuotaFailure(Response $response): bool
    {
        $message = strtolower((string) (
            $response->json('error.message')
            ?? $response->json('error.error.message')
            ?? $response->body()
        ));
        $status = strtoupper((string) ($response->json('error.status') ?? ''));

        return in_array($response->status(), [429, 503], true)
            || in_array($status, ['RESOURCE_EXHAUSTED', 'QUOTA_EXCEEDED'], true)
            || str_contains($message, 'quota')
            || str_contains($message, 'resource_exhausted')
            || str_contains($message, 'rate limit');
    }

    private function buildTinyMceCloudUrl(string $apiKey, string $version): string
    {
        $cdnBase = rtrim((string) config('editor.tinymce.cdn_base', 'https://cdn.tiny.cloud/1'), '/');

        return $cdnBase . '/' . rawurlencode($apiKey) . '/tinymce/' . rawurlencode($version) . '/tinymce.min.js';
    }

    public function destroy(AiApiKey $key)
    {
        $label = $key->label;
        $key->delete();

        return back()->with('success', "API Key \"{$label}\" dihapus.");
    }
}

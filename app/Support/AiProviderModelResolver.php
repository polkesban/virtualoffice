<?php

namespace App\Support;

use App\Models\AiProviderConfig;

class AiProviderModelResolver
{
    private const ACTIVE_PROVIDER_ROW = 'runtime-active-provider';

    private const CHAT_MODEL_CONFIG_KEYS = [
        'openai' => 'ai.openai.chat_model',
        'openrouter' => 'ai.openrouter.chat_model',
        'gemini' => 'ai.gemini.chat_model',
    ];

    public static function supportedProviders(): array
    {
        return array_keys(self::CHAT_MODEL_CONFIG_KEYS);
    }

    public static function chatModel(string $provider): string
    {
        $provider = strtolower(trim($provider));
        $default = self::defaultChatModel($provider);

        if (! array_key_exists($provider, self::CHAT_MODEL_CONFIG_KEYS)) {
            return $default;
        }

        try {
            $override = AiProviderConfig::query()
                ->where('provider', $provider)
                ->value('chat_model');
        } catch (\Throwable) {
            return $default;
        }

        return is_string($override) && trim($override) !== ''
            ? trim($override)
            : $default;
    }

    public static function defaultChatModel(string $provider): string
    {
        $provider = strtolower(trim($provider));
        $configKey = self::CHAT_MODEL_CONFIG_KEYS[$provider] ?? self::CHAT_MODEL_CONFIG_KEYS['openai'];

        return (string) config($configKey, '');
    }

    public static function chatModels(): array
    {
        $models = [];

        foreach (self::supportedProviders() as $provider) {
            $models[$provider] = self::chatModel($provider);
        }

        return $models;
    }

    public static function defaultChatModels(): array
    {
        $models = [];

        foreach (self::supportedProviders() as $provider) {
            $models[$provider] = self::defaultChatModel($provider);
        }

        return $models;
    }

    public static function chatModelOverrides(): array
    {
        $overrides = array_fill_keys(self::supportedProviders(), null);

        try {
            AiProviderConfig::query()
                ->whereIn('provider', self::supportedProviders())
                ->get(['provider', 'chat_model'])
                ->each(function (AiProviderConfig $config) use (&$overrides): void {
                    $value = trim((string) $config->chat_model);
                    $overrides[$config->provider] = $value !== '' ? $value : null;
                });
        } catch (\Throwable) {
            // Table may not exist yet during first deployment.
        }

        return $overrides;
    }

    public static function chatModelSuggestions(): array
    {
        return [
            'openai' => [
                'gpt-4o-mini',
                'gpt-4.1-mini',
                'gpt-4.1',
            ],
            'openrouter' => [
                'openrouter/free',
                'meta-llama/llama-3.3-70b-instruct:free',
                'google/gemma-4-31b-it:free',
                'meta-llama/llama-3.2-3b-instruct:free',
                'qwen/qwen3-next-80b-a3b-instruct:free',
                'openai/gpt-4o-mini',
                'anthropic/claude-3.5-sonnet',
            ],
            'gemini' => [
                'gemini-2.5-flash',
                'gemini-2.5-pro',
                'gemini-1.5-flash',
            ],
        ];
    }

    public static function activeProvider(): string
    {
        $default = self::defaultProvider();

        try {
            $override = AiProviderConfig::query()
                ->where('provider', self::ACTIVE_PROVIDER_ROW)
                ->value('chat_model');
        } catch (\Throwable) {
            return $default;
        }

        $override = is_string($override) ? strtolower(trim($override)) : '';

        return in_array($override, self::supportedProviders(), true)
            ? $override
            : $default;
    }

    public static function defaultProvider(): string
    {
        $provider = strtolower(trim((string) config('ai.provider', 'openai')));

        return in_array($provider, self::supportedProviders(), true)
            ? $provider
            : 'openai';
    }

    public static function activeProviderOverride(): ?string
    {
        try {
            $override = AiProviderConfig::query()
                ->where('provider', self::ACTIVE_PROVIDER_ROW)
                ->value('chat_model');
        } catch (\Throwable) {
            return null;
        }

        $override = is_string($override) ? strtolower(trim($override)) : '';

        return in_array($override, self::supportedProviders(), true)
            ? $override
            : null;
    }

    public static function setActiveProvider(?string $provider): void
    {
        $provider = is_string($provider) ? strtolower(trim($provider)) : '';

        if ($provider === '' || $provider === self::defaultProvider()) {
            AiProviderConfig::query()->where('provider', self::ACTIVE_PROVIDER_ROW)->delete();
            return;
        }

        AiProviderConfig::query()->updateOrCreate(
            ['provider' => self::ACTIVE_PROVIDER_ROW],
            ['chat_model' => $provider]
        );
    }
}
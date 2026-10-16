<?php

namespace Tests\Feature;

use App\Support\AiProviderModelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProviderRuntimeConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_provider_uses_configured_default_without_override(): void
    {
        config(['ai.provider' => 'openrouter']);

        $this->assertSame('openrouter', AiProviderModelResolver::defaultProvider());
        $this->assertSame('openrouter', AiProviderModelResolver::activeProvider());
        $this->assertNull(AiProviderModelResolver::activeProviderOverride());
    }

    public function test_active_provider_uses_database_override_and_clears_when_reset_to_default(): void
    {
        config(['ai.provider' => 'openrouter']);

        AiProviderModelResolver::setActiveProvider('openai');

        $this->assertSame('openai', AiProviderModelResolver::activeProvider());
        $this->assertSame('openai', AiProviderModelResolver::activeProviderOverride());
        $this->assertDatabaseHas('ai_provider_configs', [
            'provider' => 'runtime-active-provider',
            'chat_model' => 'openai',
        ]);

        AiProviderModelResolver::setActiveProvider('openrouter');

        $this->assertSame('openrouter', AiProviderModelResolver::activeProvider());
        $this->assertNull(AiProviderModelResolver::activeProviderOverride());
        $this->assertDatabaseMissing('ai_provider_configs', [
            'provider' => 'runtime-active-provider',
        ]);
    }
}
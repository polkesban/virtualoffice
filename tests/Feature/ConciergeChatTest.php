<?php

namespace Tests\Feature;

use App\Services\AI\RagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ConciergeChatTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_concierge_chat_accepts_explicit_english_language(): void
    {
        $rag = Mockery::mock(RagService::class);
        $rag->shouldReceive('query')
            ->once()
            ->withArgs(function (string $question, array $history, string $language, array $pageContext = []): bool {
                return $question === 'Hello POLBI'
                    && $language === 'en'
                    && is_array($history)
                    && $pageContext === [];
            })
            ->andReturn([
                'answer' => 'Hello from POLBI',
                'sources' => ['Snapshot data aplikasi Poltekkes Bandung'],
                'model' => 'website-data',
            ]);

        $this->app->instance(RagService::class, $rag);

        $response = $this->postJson('/api/concierge/chat', [
            'message' => 'Hello POLBI',
            'session_id' => 'session-en-123',
            'language' => 'en',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'answer' => 'Hello from POLBI',
                'sources' => ['Snapshot data aplikasi Poltekkes Bandung'],
                'language' => 'en',
            ]);

        $this->assertDatabaseHas('ai_interactions', [
            'interaction_type' => 'chatbot',
            'detected_language' => 'en',
            'model_used' => 'website-data',
        ]);
    }

    public function test_concierge_chat_rejects_unsupported_language(): void
    {
        $response = $this->postJson('/api/concierge/chat', [
            'message' => 'Halo POLBI',
            'session_id' => 'session-invalid-language',
            'language' => 'de',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['language']);
    }

    public function test_concierge_chat_forwards_page_context(): void
    {
        $rag = Mockery::mock(RagService::class);
        $rag->shouldReceive('query')
            ->once()
            ->withArgs(function (string $question, array $history, string $language, array $pageContext): bool {
                return $question === 'Jelaskan tentang LAKIP pada halaman ini'
                    && $language === 'id'
                    && is_array($history)
                    && ($pageContext['path'] ?? null) === '/unit/bidang-administrasi-umum/lakip'
                    && ($pageContext['title'] ?? null) === 'LAKIP 2025';
            })
            ->andReturn([
                'answer' => 'Halaman ini memuat ringkasan LAKIP 2025.',
                'sources' => ['Halaman LAKIP 2025 Poltekkes Bandung'],
                'model' => 'website-data',
            ]);

        $this->app->instance(RagService::class, $rag);

        $response = $this->postJson('/api/concierge/chat', [
            'message' => 'Jelaskan tentang LAKIP pada halaman ini',
            'session_id' => 'session-lakip-context',
            'language' => 'id',
            'page_path' => '/unit/bidang-administrasi-umum/lakip',
            'page_title' => 'LAKIP 2025',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'answer' => 'Halaman ini memuat ringkasan LAKIP 2025.',
                'sources' => ['Halaman LAKIP 2025 Poltekkes Bandung'],
                'language' => 'id',
            ]);
    }
}
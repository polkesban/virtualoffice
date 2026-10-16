<?php

namespace Tests\Unit;

use App\Services\AI\AiClient;
use App\Services\Localization\PublicWebsiteTranslator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class PublicWebsiteTranslatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();

        parent::tearDown();
    }

    public function test_translate_html_translates_visible_text_and_attributes_without_touching_scripts(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->once()
            ->andReturnUsing(function (array $messages): string {
                $payload = json_decode($messages[1]['content'] ?? '[]', true);
                $dictionary = [
                    'Selamat datang' => 'Welcome',
                    'Hubungi kami' => 'Contact us',
                    'Kirim pesan' => 'Send message',
                    'Alamat email' => 'Email address',
                ];

                return json_encode(array_map(
                    static fn (string $item): string => $dictionary[$item] ?? $item,
                    is_array($payload) ? $payload : []
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            });

        $translator = new PublicWebsiteTranslator($aiClient);

        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Selamat datang</title>
    <meta name="description" content="Hubungi kami">
</head>
<body>
    <button aria-label="Alamat email">Kirim pesan</button>
    <script>const buttonLabel = 'Kirim pesan';</script>
</body>
</html>
HTML;

        $translated = $translator->translateHtml($html, 'en');

        $this->assertStringContainsString('<title>Welcome</title>', $translated);
        $this->assertStringContainsString('content="Contact us"', $translated);
        $this->assertStringContainsString('aria-label="Email address"', $translated);
        $this->assertStringContainsString('>Send message</button>', $translated);
        $this->assertStringContainsString("const buttonLabel = 'Kirim pesan';", $translated);
    }

    public function test_translate_data_keeps_structural_values_like_slug_and_links(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->once()
            ->andReturnUsing(function (array $messages): string {
                $payload = json_decode($messages[1]['content'] ?? '[]', true);
                $dictionary = [
                    'Hubungi Kami' => 'Contact Us',
                    'Program Studi' => 'Study Programs',
                ];

                return json_encode(array_map(
                    static fn (string $item): string => $dictionary[$item] ?? $item,
                    is_array($payload) ? $payload : []
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            });

        $translator = new PublicWebsiteTranslator($aiClient);

        $translated = $translator->translateData([
            'title' => 'Hubungi Kami',
            'slug' => 'hubungi-kami',
            'href' => '/kontak',
            'items' => [
                [
                    'label' => 'Program Studi',
                    'slug' => 'program-studi',
                ],
            ],
        ], 'en');

        $this->assertSame('Contact Us', $translated['title']);
        $this->assertSame('hubungi-kami', $translated['slug']);
        $this->assertSame('/kontak', $translated['href']);
        $this->assertSame('Study Programs', $translated['items'][0]['label']);
        $this->assertSame('program-studi', $translated['items'][0]['slug']);
    }

    public function test_translate_data_splits_batches_when_ai_returns_wrong_item_count(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->times(3)
            ->andReturnUsing(function (array $messages): string {
                $content = $messages[1]['content'] ?? '';
                $payload = json_decode($content, true);

                if (($payload ?? []) === ['Hubungi Kami', 'Kirim Pesan', 'Informasi Kontak']) {
                    return json_encode(['Broken response'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                if ($content === 'Hubungi Kami') {
                    return 'Contact Us';
                }

                $dictionary = [
                    'Kirim Pesan' => 'Send Message',
                    'Informasi Kontak' => 'Contact Information',
                ];

                return json_encode(array_map(
                    static fn (string $item): string => $dictionary[$item] ?? $item,
                    is_array($payload) ? $payload : []
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            });

        $translator = new PublicWebsiteTranslator($aiClient);

        $translated = $translator->translateData([
            'Hubungi Kami',
            'Kirim Pesan',
            'Informasi Kontak',
        ], 'en');

        $this->assertSame([
            'Contact Us',
            'Send Message',
            'Contact Information',
        ], $translated);
    }

    public function test_translate_data_splits_batches_when_ai_returns_unchanged_array(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->times(3)
            ->andReturnUsing(function (array $messages): string {
                $content = $messages[1]['content'] ?? '';
                $payload = json_decode($content, true);

                if (($payload ?? []) === ['Hubungi Kami', 'Kirim Pesan', 'Informasi Kontak']) {
                    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                if ($content === 'Hubungi Kami') {
                    return 'Contact Us';
                }

                $dictionary = [
                    'Kirim Pesan' => 'Send Message',
                    'Informasi Kontak' => 'Contact Information',
                ];

                return json_encode(array_map(
                    static fn (string $item): string => $dictionary[$item] ?? $item,
                    is_array($payload) ? $payload : []
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            });

        $translator = new PublicWebsiteTranslator($aiClient);

        $translated = $translator->translateData([
            'Hubungi Kami',
            'Kirim Pesan',
            'Informasi Kontak',
        ], 'en');

        $this->assertSame([
            'Contact Us',
            'Send Message',
            'Contact Information',
        ], $translated);
    }

    public function test_translate_data_retries_unchanged_indonesian_labels_individually(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->times(2)
            ->andReturnUsing(function (array $messages): string {
                $content = $messages[1]['content'] ?? '';
                $payload = json_decode($content, true);

                if (($payload ?? []) === ['Hubungi Kami', 'Alamat']) {
                    return json_encode(['Hubungi Kami', 'Address'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                if ($content === 'Hubungi Kami') {
                    return 'Contact Us';
                }

                return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            });

        $translator = new PublicWebsiteTranslator($aiClient);

        $translated = $translator->translateData([
            'Hubungi Kami',
            'Alamat',
        ], 'en');

        $this->assertSame([
            'Contact Us',
            'Address',
        ], $translated);
    }

    public function test_translate_data_retries_multiple_unchanged_labels_as_short_label_batch(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->times(2)
            ->andReturnUsing(function (array $messages): string {
                $content = $messages[1]['content'] ?? '';
                $payload = json_decode($content, true);

                if (($payload ?? []) === ['Hubungi Kami', 'Informasi Kontak', 'Alamat']) {
                    return json_encode(['Hubungi Kami', 'Informasi Kontak', 'Address'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                if (($payload ?? []) === ['Hubungi Kami', 'Informasi Kontak']) {
                    return json_encode(['Contact Us', 'Contact Information'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            });

        $translator = new PublicWebsiteTranslator($aiClient);

        $translated = $translator->translateData([
            'Hubungi Kami',
            'Informasi Kontak',
            'Alamat',
        ], 'en');

        $this->assertSame([
            'Contact Us',
            'Contact Information',
            'Address',
        ], $translated);
    }

    public function test_translate_text_uses_single_label_fallback_when_json_result_stays_unchanged(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->once()
            ->andReturnUsing(function (array $messages): string {
                if (($messages[1]['content'] ?? null) === 'Alamat'
                    && str_contains($messages[0]['content'] ?? '', 'You translate one website UI text string.')) {
                    return 'Alamat';
                }

                return 'Alamat';
            });

        $translator = new PublicWebsiteTranslator($aiClient);

        $this->assertSame('Address', $translator->translateText('Alamat', 'en'));
    }

    public function test_translate_text_does_not_fail_when_translation_cache_write_throws(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', true);

        Cache::shouldReceive('get')
            ->once()
            ->andReturn(null);
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \RuntimeException('cache unavailable'));
        Cache::shouldReceive('flush')
            ->andReturnTrue();

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('chat')
            ->once()
            ->andReturn('Alamat');

        $translator = new PublicWebsiteTranslator($aiClient);

        $this->assertSame('Address', $translator->translateText('Alamat', 'en'));
    }
}
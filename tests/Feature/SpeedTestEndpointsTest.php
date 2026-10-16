<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpeedTestEndpointsTest extends TestCase
{
    public function test_ping_endpoint_returns_json(): void
    {
        $response = $this->get('/speedtest/ping');
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function test_download_endpoint_returns_binary_stream_with_length_header(): void
    {
        $response = $this->get('/speedtest/download?bytes=1000000');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', '1000000');
    }

    public function test_upload_endpoint_accepts_small_binary_payload(): void
    {
        $this->withoutMiddleware();

        $response = $this->call(
            'POST',
            '/speedtest/upload',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/octet-stream',
                'HTTP_ACCEPT' => 'application/json',
            ],
            str_repeat('a', 4096),
        );

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('bytes_received', 4096);
    }
}
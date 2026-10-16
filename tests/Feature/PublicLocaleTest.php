<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_request_can_switch_to_english_and_sets_cookie(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', false);

        $response = $this->get('/kontak?lang=en');

        $response
            ->assertOk()
            ->assertCookie('polkesban_locale', 'en')
            ->assertHeader('Content-Language', 'en')
            ->assertSee('lang="en"', false);
    }

    public function test_public_request_defaults_to_indonesian_without_explicit_locale(): void
    {
        config()->set('app.locale', 'id');
        config()->set('ai.website_translation.enabled', false);

        $response = $this->get('/kontak');

        $response
            ->assertOk()
            ->assertHeader('Content-Language', 'id')
            ->assertSee('lang="id"', false);
    }
}
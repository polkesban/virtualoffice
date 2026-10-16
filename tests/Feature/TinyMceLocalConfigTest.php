<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TinyMceLocalConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_tinymce_runtime_defaults_to_local_mode_without_cloud(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.api-keys.tinymce.runtime'));
        $response->assertOk();
        $response->assertJson([
            'cloudEnabled' => false,
            'localFallback' => true,
        ]);
    }

    public function test_tinymce_runtime_returns_available_keys_if_cloud_explicitly_enabled(): void
    {
        config(['editor.tinymce.use_cloud' => true]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.api-keys.tinymce.runtime'));
        $response->assertOk();
        $response->assertJson([
            'cloudEnabled' => true,
            'localFallback' => true,
        ]);
    }
}

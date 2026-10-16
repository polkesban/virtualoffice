<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_page_renders_view_switcher(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk();
        $response->assertSee('Tabel');
        $response->assertSee('Kartu');
    }

    public function test_admin_program_studi_page_renders_view_switcher(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.program-studi.index'));
        $response->assertOk();
        $response->assertSee('Tabel');
        $response->assertSee('Kartu');
    }

    public function test_admin_berita_page_renders_view_switcher(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.berita.index'));
        $response->assertOk();
        $response->assertSee('Tabel');
        $response->assertSee('Kartu');
    }

    public function test_admin_pengaduan_page_renders_view_switcher(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pengaduan.index'));
        $response->assertOk();
        $response->assertSee('Tabel');
        $response->assertSee('Kartu');
    }
}

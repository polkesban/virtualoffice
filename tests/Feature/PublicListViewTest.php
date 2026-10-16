<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\ProgramStudi;
use App\Models\UnitPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicListViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_layanan_unit_page_renders_view_switcher_and_categories(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        UnitPage::create([
            'role' => 'wadir_1',
            'slug' => 'bidang-akademik',
            'judul' => 'Bidang Akademik',
            'aktif' => true,
        ]);

        UnitPage::create([
            'role' => 'pusat_mutu',
            'slug' => 'penjaminan-mutu',
            'judul' => 'Pusat Penjaminan Mutu',
            'aktif' => true,
        ]);

        UnitPage::create([
            'role' => 'unit_ti',
            'slug' => 'teknologi-informasi',
            'judul' => 'Unit Teknologi Informasi',
            'aktif' => true,
        ]);

        $response = $this->withSession(['tamu_user' => ['name' => 'Pengunjung Tamu']])->get(route('unit.index'));
        $response->assertOk();
        $response->assertSee('Tampilan Grid');
        $response->assertSee('Tampilan List (Per Kelompok)');
        $response->assertSee('Bidang & Direktorat', false);
        $response->assertSee('Pusat Pengelola & Penjaminan', false);
        $response->assertSee('Unit Pelayanan & Operasional', false);
        $response->assertSee('Jurusan & Program Studi', false);
    }

    public function test_program_studi_index_renders_view_switcher_and_jurusan_grouping(): void
    {
        $jurusan = Jurusan::create([
            'nama' => 'Jurusan Keperawatan',
            'slug' => 'jurusan-keperawatan',
            'jenjang' => 'D4',
            'aktif' => true,
        ]);

        ProgramStudi::create([
            'jurusan_id' => $jurusan->id,
            'nama' => 'D3 Keperawatan Bandung',
            'singkatan' => 'D3 Kep',
            'slug' => 'd3-keperawatan-bandung',
            'jenjang' => 'D3',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $response = $this->get(route('program-studi'));
        $response->assertOk();
        $response->assertSee('Tampilan Grid');
        $response->assertSee('Tampilan List (Per Jurusan)');
        $response->assertSee('Jurusan Keperawatan');
        $response->assertSee('D3 Keperawatan Bandung');
    }

    public function test_berita_page_renders_view_switcher(): void
    {
        $response = $this->get(route('berita'));
        $response->assertOk();
        $response->assertSee('Tampilan Grid');
        $response->assertSee('Tampilan List');
    }

    public function test_pengumuman_page_renders_view_switcher(): void
    {
        $response = $this->get(route('pengumuman'));
        $response->assertOk();
        $response->assertSee('Grid');
        $response->assertSee('List');
    }

    public function test_portal_mahasiswa_page_renders_view_switcher(): void
    {
        $response = $this->get(route('portal-mahasiswa'));
        $response->assertOk();
        $response->assertSee('Grid');
        $response->assertSee('List');
    }
}

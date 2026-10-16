<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminJurusanAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('page_sections')) {
            Schema::create('page_sections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('unit_page_id')->nullable();
                $table->unsignedBigInteger('halaman_statis_id')->nullable();
                $table->string('tipe', 50);
                $table->string('judul', 200)->nullable();
                $table->json('konfigurasi')->nullable();
                $table->integer('urutan')->default(0);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }
    }

    public function test_admin_jurusan_can_access_all_prodi_in_their_department(): void
    {
        $jurusanKeperawatan = Jurusan::create([
            'nama' => 'Jurusan Keperawatan',
            'slug' => 'jurusan-keperawatan',
            'jenjang' => 'D4',
        ]);

        $jurusanKebidanan = Jurusan::create([
            'nama' => 'Jurusan Kebidanan',
            'slug' => 'jurusan-kebidanan',
            'jenjang' => 'D3',
        ]);

        $prodiD3Kep = ProgramStudi::create([
            'jurusan_id' => $jurusanKeperawatan->id,
            'nama' => 'D3 Keperawatan Bandung',
            'singkatan' => 'D3 Kep',
            'slug' => 'd3-keperawatan-bandung',
            'jenjang' => 'D3',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $prodiD4Kep = ProgramStudi::create([
            'jurusan_id' => $jurusanKeperawatan->id,
            'nama' => 'D4 Keperawatan Bandung',
            'singkatan' => 'D4 Kep',
            'slug' => 'd4-keperawatan-bandung',
            'jenjang' => 'D4',
            'aktif' => true,
            'urutan' => 2,
        ]);

        $prodiKebidanan = ProgramStudi::create([
            'jurusan_id' => $jurusanKebidanan->id,
            'nama' => 'D3 Kebidanan Bandung',
            'singkatan' => 'D3 Bid',
            'slug' => 'd3-kebidanan-bandung',
            'jenjang' => 'D3',
            'aktif' => true,
            'urutan' => 3,
        ]);

        $adminJurusan = User::factory()->create([
            'role' => User::ROLE_ADMIN_JURUSAN,
            'jurusan_id' => $jurusanKeperawatan->id,
            'is_admin' => true,
        ]);

        // Assert role checks
        $this->assertTrue($adminJurusan->isAdminJurusan());
        $this->assertTrue($adminJurusan->isJurusanAdmin());

        // Index program studi should filter prodis by jurusan
        $response = $this->actingAs($adminJurusan)->get(route('admin.program-studi.index'));
        $response->assertOk();
        $response->assertSee('D3 Keperawatan Bandung');
        $response->assertSee('D4 Keperawatan Bandung');
        $response->assertDontSee('D3 Kebidanan Bandung');

        // Can access content block manager for prodi in same department
        $contentResponse = $this->actingAs($adminJurusan)->get(route('admin.program-studi.content', $prodiD3Kep));
        $prodiD3Kep->refresh();
        $contentResponse->assertRedirect(route('admin.halaman-unit.manage', $prodiD3Kep->unitPage));

        $this->actingAs($adminJurusan)
            ->get(route('admin.halaman-unit.manage', $prodiD3Kep->unitPage))
            ->assertOk();

        // Cannot access prodi content in different department
        $forbiddenResponse = $this->actingAs($adminJurusan)->get(route('admin.program-studi.edit', $prodiKebidanan));
        $forbiddenResponse->assertStatus(403);
    }
}

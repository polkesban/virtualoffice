<?php

namespace Tests\Feature;

use App\Models\ProgramStudi;
use App\Models\UnitPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHalamanUnitTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_halaman_unit_displays_four_distinct_tabs(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('page_sections')) {
            \Illuminate\Support\Facades\Schema::create('page_sections', function ($table) {
                $table->id();
                $table->foreignId('unit_page_id')->nullable();
                $table->string('judul')->nullable();
                $table->string('tipe')->nullable();
                $table->integer('urutan')->default(0);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        // 1. Direktorat / Unit Kerja Page
        UnitPage::updateOrCreate(
            ['role' => 'unit_ti'],
            ['slug' => 'teknologi-informasi', 'judul' => 'Unit Teknologi Informasi', 'aktif' => true]
        );

        // 2. Jurusan / Prodi Page
        UnitPage::updateOrCreate(
            ['role' => 'jurusan_kep'],
            ['slug' => 'jurusan-keperawatan', 'judul' => 'Jurusan Keperawatan', 'aktif' => true]
        );

        // 3. Ormawa Page
        UnitPage::updateOrCreate(
            ['role' => 'org_bem_km'],
            ['slug' => 'bem-km', 'judul' => 'BEM KM Poltekkes Bandung', 'aktif' => true]
        );

        // 4. Pegawai / Dosen Individual Page
        UnitPage::updateOrCreate(
            ['role' => 'user_417'],
            ['slug' => 'pegawai-laila', 'judul' => 'apt. LAILA MUSYARROFAH, M.Farm.Klin', 'aktif' => true]
        );

        $response = $this->actingAs($admin)->get(route('admin.halaman-unit.index'));

        $response->assertStatus(200);
        $response->assertSee('Direktorat & Unit Kerja', false);
        $response->assertSee('Jurusan & Prodi', false);
        $response->assertSee('Ormawa (BEM & HIMA)', false);
        $response->assertSee('Profil Dosen & Pegawai', false);

        // Check helper methods on models
        $tiPage = UnitPage::where('role', 'unit_ti')->first();
        $this->assertTrue($tiPage->isDirektoratOrUnitKerjaPage());
        $this->assertFalse($tiPage->isPegawaiOrDosenPage());

        $userPage = UnitPage::where('role', 'user_417')->first();
        $this->assertTrue($userPage->isPegawaiOrDosenPage());
        $this->assertFalse($userPage->isDirektoratOrUnitKerjaPage());
    }
}

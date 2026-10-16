<?php

namespace Tests\Feature;

use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgramStudiContentBlocksTest extends TestCase
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

    public function test_wadir1_can_open_program_studi_content_block_manager(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_WADIR_1,
            'is_admin' => true,
        ]);

        $programStudi = ProgramStudi::query()->create([
            'nama' => 'D4 Teknologi Laboratorium Medis',
            'singkatan' => 'D4 TLM',
            'slug' => 'd4-teknologi-laboratorium-medis',
            'jenjang' => 'D4',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('admin.program-studi.content', $programStudi));

        $programStudi->refresh();

        $response->assertRedirect(route('admin.halaman-unit.manage', $programStudi->unitPage));
        $this->assertNotNull($programStudi->unitPage);
        $this->assertSame('kaprodi_' . $programStudi->id, $programStudi->unitPage->role);

        $this->actingAs($user)
            ->get(route('admin.halaman-unit.manage', $programStudi->unitPage))
            ->assertOk();
    }
}
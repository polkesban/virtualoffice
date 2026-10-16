<?php

namespace Tests\Feature;

use App\Models\UnitEvent;
use App\Models\UnitPage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DosenPegawaiAccessTest extends TestCase
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

        if (Schema::hasTable('roles')) {
            DB::table('roles')->updateOrInsert(['key' => 'dosen'], ['label' => 'Dosen', 'menus' => json_encode(['halaman-unit', 'wfh'])]);
            DB::table('roles')->updateOrInsert(['key' => 'employee'], ['label' => 'Pegawai Poltekkes Bandung', 'menus' => json_encode(['halaman-unit', 'wfh'])]);
        }
        User::refreshRoleConfig();
    }

    public function test_dosen_user_can_access_admin_halaman_unit_and_creates_personal_page(): void
    {
        $dosen = User::factory()->create([
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.dosen@polkesban.ac.id',
            'role' => User::ROLE_DOSEN,
            'is_admin' => false,
        ]);

        $response = $this->actingAs($dosen)->get(route('admin.halaman-unit.index'));

        $response->assertStatus(200);
        $response->assertSee('Dr. Jane Doe');

        $dosen->refresh();
        $this->assertNotNull($dosen->managed_unit_page_id);

        $page = UnitPage::find($dosen->managed_unit_page_id);
        $this->assertNotNull($page);
        $this->assertEquals('user_' . $dosen->id, $page->role);
        $this->assertStringContainsString('dosen-dr-jane-doe', $page->slug);
    }

    public function test_employee_user_can_access_admin_halaman_unit_and_creates_personal_page(): void
    {
        $employee = User::factory()->create([
            'name' => 'John Pegawai',
            'email' => 'john.pegawai@polkesban.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'is_admin' => false,
        ]);

        $response = $this->actingAs($employee)->get(route('admin.halaman-unit.index'));

        $response->assertStatus(200);
        $response->assertSee('John Pegawai');

        $employee->refresh();
        $this->assertNotNull($employee->managed_unit_page_id);

        $page = UnitPage::find($employee->managed_unit_page_id);
        $this->assertNotNull($page);
        $this->assertEquals('user_' . $employee->id, $page->role);
        $this->assertStringContainsString('pegawai-john-pegawai', $page->slug);
    }

    public function test_dosen_user_can_create_unit_event_on_their_personal_page(): void
    {
        $dosen = User::factory()->create([
            'name' => 'Dr. Ahmad Dosen',
            'email' => 'ahmad.dosen@polkesban.ac.id',
            'role' => User::ROLE_DOSEN,
            'is_admin' => false,
        ]);

        // First access halaman-unit to generate personal page
        $this->actingAs($dosen)->get(route('admin.halaman-unit.index'));
        $dosen->refresh();
        $page = $dosen->managedUnitPage;

        $eventData = [
            'judul' => 'Webinar Hasil Penelitian Dosen 2026',
            'slug' => 'webinar-hasil-penelitian-2026',
            'jenis_kegiatan' => 'webinar',
            'metode' => 'online',
            'status' => 'published',
            'mulai_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'selesai_at' => now()->addDays(2)->addHours(3)->format('Y-m-d H:i'),
        ];

        $response = $this->actingAs($dosen)->post(route('admin.unit-events.store', $page), $eventData);

        $response->assertRedirect();
        $this->assertDatabaseHas('unit_events', [
            'unit_page_id' => $page->id,
            'judul' => 'Webinar Hasil Penelitian Dosen 2026',
            'created_by' => $dosen->id,
        ]);
    }
}

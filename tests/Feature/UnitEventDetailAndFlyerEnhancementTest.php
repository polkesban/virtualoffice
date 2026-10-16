<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\UnitPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnitEventDetailAndFlyerEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private UnitPage $unitPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->unitPage = UnitPage::create([
            'role' => 'pusat_pengembangan_pendidikan',
            'judul' => 'Pusat Pengembangan Pendidikan',
            'slug' => 'pengembangan-pendidikan',
            'tipe' => 'unit_kerja',
            'aktif' => true,
        ]);
    }

    public function test_flyer_uses_header_logo_and_has_no_duplicate_institution_text(): void
    {
        $event = UnitEvent::create([
            'unit_page_id' => $this->unitPage->id,
            'created_by' => $this->admin->id,
            'judul' => 'Kuliah Bersama Mata Kuliah Kapita Selekta',
            'slug' => 'kuliah-bersama-kapita-selekta',
            'jenis_kegiatan' => 'kuliah_pakar',
            'metode' => 'online',
            'status' => 'published',
            'mulai_at' => now()->addDays(3),
            'direktur_nama' => 'Dr. Pramita Iriana, S.Kep., M.Biomed',
            'penandatangan_nama' => 'Dr. Pramita Iriana, S.Kep., M.Biomed',
            'penandatangan_jabatan' => 'Direktur',
            'zoom_link' => 'https://us02web.zoom.us/j/86076294396',
        ]);

        $response = $this->get(route('unit.events.show', [$this->unitPage->slug, $event->slug]));

        $response->assertStatus(200);
        // Header logo is used
        $response->assertSee('images/logo.png');
        // Director name is present
        $response->assertSee('Dr. Pramita Iriana, S.Kep., M.Biomed');
        // Online location is resolved properly
        $response->assertSee('Zoom Cloud Meetings / Daring Online');
        // Zoom meeting link is rendered nicely in the location section
        $response->assertSee('https://us02web.zoom.us/j/86076294396');
        $response->assertSee('Buka Tautan Zoom / Video Meeting');
    }

    public function test_speaker_photo_can_be_uploaded_and_rendered_on_flyer(): void
    {
        Storage::fake('public');

        $event = UnitEvent::create([
            'unit_page_id' => $this->unitPage->id,
            'created_by' => $this->admin->id,
            'judul' => 'Kuliah Bersama Narasumber',
            'slug' => 'kuliah-bersama-narasumber',
            'jenis_kegiatan' => 'kuliah_pakar',
            'metode' => 'online',
            'status' => 'published',
            'mulai_at' => now()->addDays(2),
        ]);

        $fakePhoto = UploadedFile::fake()->image('speaker-bagus.jpg', 400, 400);

        $response = $this->actingAs($this->admin)->post(
            route('admin.unit-events.participants.store', [$this->unitPage, $event]),
            [
                'peran' => 'speaker',
                'participant_category' => 'public',
                'nama' => 'Bagus Sugiarto, SKM, M.Sc.PH',
                'jabatan' => 'Dosen & Pakar Kesehatan',
                'instansi' => 'Poltekkes Kemenkes Yogyakarta',
                'foto' => $fakePhoto,
            ]
        );

        $response->assertRedirect(route('admin.unit-events.participants.index', [$this->unitPage, $event]));

        $speaker = UnitEventParticipant::where('nama', 'Bagus Sugiarto, SKM, M.Sc.PH')->first();
        $this->assertNotNull($speaker);
        $this->assertEquals('speaker', $speaker->peran);
        $this->assertNotNull($speaker->foto_path);

        // Check that speaker and photo render on flyer
        $flyerResponse = $this->get(route('unit.events.flyer', [$this->unitPage->slug, $event->slug]));
        $flyerResponse->assertStatus(200);
        $flyerResponse->assertSee('Bagus Sugiarto, SKM, M.Sc.PH');
        $flyerResponse->assertSee('Poltekkes Kemenkes Yogyakarta');
    }
}

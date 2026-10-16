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

class UnitEventFlyerTest extends TestCase
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
            'role' => 'jurusan_keperawatan',
            'judul' => 'Jurusan Keperawatan',
            'slug' => 'jurusan-keperawatan',
            'tipe' => 'jurusan',
            'aktif' => true,
        ]);
    }

    public function test_public_event_page_displays_flyer_button_and_template(): void
    {
        $event = UnitEvent::create([
            'unit_page_id' => $this->unitPage->id,
            'created_by' => $this->admin->id,
            'judul' => 'Seminar Nasional Keperawatan Kritis 2026',
            'slug' => 'seminar-nasional-keperawatan-kritis-2026',
            'ringkasan' => 'Transformasi Pelayanan Keperawatan Kritis di Era Digital',
            'jenis_kegiatan' => 'seminar',
            'metode' => 'hybrid',
            'status' => 'published',
            'mulai_at' => now()->addDays(5),
            'selesai_at' => now()->addDays(5)->addHours(4),
            'flyer_theme' => 'modern_teal',
            'flyer_tagline' => 'SEMINAR NASIONAL KESEHATAN 2026',
            'direktur_nama' => 'Dr. Pujiono, SKM, M.Kes',
        ]);

        $response = $this->get(route('unit.events.show', [$this->unitPage->slug, $event->slug]));

        $response->assertStatus(200);
        $response->assertSee('Flyer Resmi');
        $response->assertSee('images/logo.png');
        $response->assertSee('Dr. Pujiono, SKM, M.Kes');
        $response->assertSee('Seminar Nasional Keperawatan Kritis 2026');
    }

    public function test_public_flyer_page_renders_with_polkesban_branding_and_speakers(): void
    {
        $event = UnitEvent::create([
            'unit_page_id' => $this->unitPage->id,
            'created_by' => $this->admin->id,
            'judul' => 'Webinar Inovasi Teknologi Farmasi Klinik',
            'slug' => 'webinar-inovasi-farmasi',
            'ringkasan' => 'Pengembangan Formulasi Berbasis Nanoteknologi',
            'jenis_kegiatan' => 'webinar',
            'metode' => 'online',
            'status' => 'published',
            'mulai_at' => now()->addDays(2),
            'selesai_at' => now()->addDays(2)->addHours(3),
            'flyer_theme' => 'emerald_health',
            'flyer_tagline' => 'KULIAH PAKAR & WEBINAR NASIONAL',
            'flyer_benefits' => "E-Sertifikat SKP Kemenkes RI\nMateri Lengkap\nAkses Portal Rekaman",
        ]);

        UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'peran' => 'speaker',
            'nama' => 'Prof. Dr. apt. Ahmad Hidayat, M.Si.',
            'jabatan' => 'Guru Besar Farmasi Klinik',
            'instansi' => 'Universitas Indonesia',
            'catatan' => 'Membawakan materi: Formulasi Terkini Obat Berbasis Nanopartikel',
            'kode_registrasi' => 'SPK-001',
            'access_token' => 'token-spk-001',
        ]);

        $response = $this->get(route('unit.events.flyer', [$this->unitPage->slug, $event->slug]));

        $response->assertStatus(200);
        $response->assertSee('alt="Kemenkes Poltekkes Bandung"', false);
        $response->assertSee('Webinar Inovasi Teknologi Farmasi Klinik');
        $response->assertSee('Dr. Pujiono, SKM, M.Kes');
        $response->assertSee('Prof. Dr. apt. Ahmad Hidayat, M.Si.');
        $response->assertSee('Guru Besar Farmasi Klinik');
        $response->assertSee('E-Sertifikat SKP Kemenkes RI');
        $response->assertSee('Unduh PDF');
        $response->assertSee('Unduh Gambar (PNG)');
    }

    public function test_admin_can_preview_flyer(): void
    {
        $event = UnitEvent::create([
            'unit_page_id' => $this->unitPage->id,
            'created_by' => $this->admin->id,
            'judul' => 'Workshop Akreditasi LAM-PTKes',
            'slug' => 'workshop-akreditasi-lamptkes',
            'jenis_kegiatan' => 'workshop',
            'metode' => 'offline',
            'status' => 'draft',
            'mulai_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.unit-events.flyer', [$this->unitPage, $event]));

        $response->assertStatus(200);
        $response->assertSee('Workshop Akreditasi LAM-PTKes');
        $response->assertSee('alt="Kemenkes Poltekkes Bandung"', false);
    }

    public function test_admin_can_update_event_flyer_customization(): void
    {
        Storage::fake('public');

        $event = UnitEvent::create([
            'unit_page_id' => $this->unitPage->id,
            'created_by' => $this->admin->id,
            'judul' => 'Rapat Kerja Tahunan 2026',
            'slug' => 'rapat-kerja-tahunan-2026',
            'jenis_kegiatan' => 'rapat',
            'metode' => 'offline',
            'status' => 'draft',
            'mulai_at' => now()->addDays(3),
        ]);

        $directorPhoto = UploadedFile::fake()->image('direktur.jpg', 400, 400);

        $response = $this->actingAs($this->admin)->put(route('admin.unit-events.update', [$this->unitPage, $event]), [
            'judul' => 'Rapat Kerja Tahunan Polkesban 2026',
            'jenis_kegiatan' => 'rapat',
            'metode' => 'offline',
            'status' => 'published',
            'mulai_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'flyer_theme' => 'navy_gold',
            'flyer_tagline' => 'RAPAT KERJA & STRATEGIS 2026',
            'direktur_nama' => 'Dr. Pujiono, SKM, M.Kes',
            'direktur_foto' => $directorPhoto,
            'flyer_benefits' => "Sertifikat Partisipasi\nKonsumsi & Materi Lengkap",
        ]);

        $response->assertRedirect(route('admin.unit-events.edit', [$this->unitPage, $event]));

        $event->refresh();
        $this->assertEquals('Rapat Kerja Tahunan Polkesban 2026', $event->judul);
        $this->assertEquals('navy_gold', $event->flyer_theme);
        $this->assertEquals('RAPAT KERJA & STRATEGIS 2026', $event->flyer_tagline);
        $this->assertEquals('Dr. Pujiono, SKM, M.Kes', $event->direktur_nama);
        $this->assertNotNull($event->direktur_foto_path);
        $this->assertCount(2, $event->resolvedBenefitsList());
    }

    public function test_admin_can_add_speaker_with_photo(): void
    {
        Storage::fake('public');

        $event = UnitEvent::create([
            'unit_page_id' => $this->unitPage->id,
            'created_by' => $this->admin->id,
            'judul' => 'Kuliah Umum Kebidanan Komunitas',
            'slug' => 'kuliah-umum-kebidanan',
            'jenis_kegiatan' => 'seminar',
            'metode' => 'online',
            'status' => 'published',
            'mulai_at' => now()->addDays(4),
        ]);

        $speakerPhoto = UploadedFile::fake()->image('speaker.png', 300, 300);

        $response = $this->actingAs($this->admin)->post(route('admin.unit-events.participants.store', [$this->unitPage, $event]), [
            'peran' => 'speaker',
            'participant_category' => 'public',
            'nama' => 'Dr. dr. Siti Nurhaliza, Sp.OG',
            'email' => 'siti.nurhaliza@kemenkes.go.id',
            'instansi' => 'RSUP dr. Hasan Sadikin Bandung',
            'jabatan' => 'Konsultan Fertilitas & Endokrinologi Reproduksi',
            'foto' => $speakerPhoto,
        ]);

        $response->assertRedirect(route('admin.unit-events.participants.index', [$this->unitPage, $event]));

        $participant = UnitEventParticipant::where('nama', 'Dr. dr. Siti Nurhaliza, Sp.OG')->first();
        $this->assertNotNull($participant);
        $this->assertEquals('speaker', $participant->peran);
        $this->assertNotNull($participant->foto_path);
        $this->assertNotNull($participant->foto_url);
    }
}

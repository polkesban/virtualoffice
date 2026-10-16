<?php

namespace Tests\Feature;

use App\Models\EvaluationRubric;
use App\Models\EvaluationRubricItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationRubricTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_evaluation_rubric_with_criteria(): void
    {
        $dosen = User::factory()->create([
            'name' => 'Dr. Budi Dosen',
            'email' => 'budi.dosen@polkesban.ac.id',
            'role' => User::ROLE_DOSEN,
            'is_admin' => false,
        ]);

        $payload = [
            'judul' => 'Rubrik Evaluasi Presentasi Mahasiswa',
            'kategori' => 'seminar',
            'jenis_rubrik' => 'analytic',
            'rumus_penilaian' => 'weighted_percentage',
            'skala_max' => 4,
            'deskripsi' => 'Petunjuk penilaian presentasi tugas akhir.',
            'items' => [
                [
                    'nama_kriteria' => 'Penguasaan Materi',
                    'deskripsi' => 'Menguasai konsep dasar dan kedalaman analisis.',
                    'bobot' => 50,
                ],
                [
                    'nama_kriteria' => 'Kerapihan Presentation Slide',
                    'deskripsi' => 'Desain visual slide dan alur penyampaian.',
                    'bobot' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($dosen)->post(route('admin.rubrik.store'), $payload);

        $rubrik = EvaluationRubric::where('judul', 'Rubrik Evaluasi Presentasi Mahasiswa')->first();
        $this->assertNotNull($rubrik);
        $response->assertRedirect(route('admin.rubrik.show', $rubrik));

        $this->assertEquals(2, $rubrik->items()->count());
        $this->assertEquals(100.0, $rubrik->total_weight);
    }

    public function test_user_can_view_rubric_and_simulate_scoring(): void
    {
        $dosen = User::factory()->create([
            'role' => User::ROLE_DOSEN,
        ]);

        $rubrik = EvaluationRubric::create([
            'judul' => 'Rubrik Evaluasi Praktikum Lab',
            'slug' => 'rubrik-evaluasi-praktikum-lab',
            'kategori' => 'praktikum',
            'jenis_rubrik' => 'analytic',
            'rumus_penilaian' => 'weighted_percentage',
            'skala_max' => 4,
            'created_by' => $dosen->id,
        ]);

        $item1 = $rubrik->items()->create([
            'nama_kriteria' => 'Keterampilan Alat',
            'bobot' => 60,
            'urutan' => 1,
        ]);

        $item2 = $rubrik->items()->create([
            'nama_kriteria' => 'Laporan Hasil',
            'bobot' => 40,
            'urutan' => 2,
        ]);

        $response = $this->actingAs($dosen)->get(route('admin.rubrik.show', $rubrik));
        $response->assertStatus(200);
        $response->assertSee('Rubrik Evaluasi Praktikum Lab');

        // Test calculateFinalScore helper logic
        $calculation = $rubrik->calculateFinalScore([
            $item1->id => 4, // 100% of 60 = 60
            $item2->id => 3, // 75% of 40 = 30
        ]);

        $this->assertEquals(90.0, $calculation['weighted_total']);
        $this->assertEquals('A', $calculation['grade_letter']);
    }

    public function test_preset_template_endpoint_returns_preset_data(): void
    {
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN]);

        $response = $this->actingAs($dosen)->get(route('admin.rubrik.preset', 'cbd'));
        $response->assertStatus(200);
        $response->assertJsonPath('jenis_rubrik', 'clinical');
        $response->assertJsonPath('skala_max', 4.5);
    }

    public function test_score_times_weight_calculation_for_dops_and_rdk(): void
    {
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN]);

        $rubrik = EvaluationRubric::create([
            'judul' => 'DOPS Test Rubric',
            'slug' => 'dops-test-rubric',
            'kategori' => 'klinik',
            'jenis_rubrik' => 'checklist',
            'rumus_penilaian' => 'score_times_weight',
            'skala_max' => 4,
            'created_by' => $dosen->id,
        ]);

        $item1 = $rubrik->items()->create(['nama_kriteria' => 'Langkah 1', 'bobot' => 3]);
        $item2 = $rubrik->items()->create(['nama_kriteria' => 'Langkah 2', 'bobot' => 2]);

        // Max raw = (4 * 3) + (4 * 2) = 12 + 8 = 20
        // Score = (4 * 3) + (3 * 2) = 12 + 6 = 18 -> (18 / 20) * 100 = 90.0%
        $calculation = $rubrik->calculateFinalScore([
            $item1->id => 4,
            $item2->id => 3,
        ]);

        $this->assertEquals(90.0, $calculation['final_percentage']);
        $this->assertEquals('A', $calculation['grade_letter']);
    }

    public function test_user_can_create_rubric_with_sub_criteria_and_calculate_scores(): void
    {
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN]);

        $payload = [
            'judul' => 'Rubrik Sikap dengan Sub Kriteria',
            'kategori' => 'klinik',
            'jenis_rubrik' => 'group_matrix',
            'rumus_penilaian' => 'raw_sum',
            'skala_max' => 4,
            'items' => [
                [
                    'nama_kriteria' => 'A. Sikap Jujur',
                    'bobot' => 16,
                    'sub_items' => [
                        ['nama_kriteria' => '1. Tidak berkata luar norma', 'bobot' => 4],
                        ['nama_kriteria' => '2. Jujur mandiri dalam ujian', 'bobot' => 4],
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($dosen)->post(route('admin.rubrik.store'), $payload);

        $rubrik = EvaluationRubric::where('judul', 'Rubrik Sikap dengan Sub Kriteria')->first();
        $this->assertNotNull($rubrik);
        $response->assertRedirect(route('admin.rubrik.show', $rubrik));

        $rootItems = $rubrik->rootItems;
        $this->assertCount(1, $rootItems);
        $this->assertCount(2, $rootItems->first()->subItems);

        $sub1 = $rootItems->first()->subItems->first();
        $sub2 = $rootItems->first()->subItems->last();

        // Calculate score with sub items
        $calculation = $rubrik->calculateFinalScore([
            $sub1->id => 4,
            $sub2->id => 4,
        ]);

        $this->assertEquals(100.0, $calculation['final_percentage']);
        $this->assertEquals('A', $calculation['grade_letter']);
    }

    public function test_user_can_access_print_view(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $rubrik = EvaluationRubric::create([
            'judul' => 'Rubrik Ujian Lisan',
            'slug' => 'rubrik-ujian-lisan',
            'kategori' => 'pbm',
            'jenis_rubrik' => 'analytic',
            'rumus_penilaian' => 'weighted_percentage',
            'skala_max' => 5,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.rubrik.print', $rubrik));
        $response->assertStatus(200);
        $response->assertSee('POLTEKKES KEMENKES BANDUNG');
        $response->assertSee('Rubrik Ujian Lisan');
    }

    public function test_preseptor_and_participant_can_access_rubric_view_via_portal_token(): void
    {
        $page = \App\Models\UnitPage::create([
            'judul' => 'Prodi Kebidanan Bandung',
            'slug' => 'prodi-kebidanan-bandung',
            'tipe' => 'prodi',
        ]);

        $event = \App\Models\UnitEvent::create([
            'unit_page_id' => $page->id,
            'judul' => 'Magang Asuhan Kebidanan Esensial',
            'slug' => 'magang-asuhan-kebidanan-esensial',
            'rubrik_aktif' => true,
            'jenis' => 'pbm',
            'mulai_at' => now(),
            'selesai_at' => now()->addDays(7),
        ]);

        $participant = \App\Models\UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'nama' => 'Bdn. Dede Supriatin, S.Tr. Keb',
            'peran' => 'preseptor',
            'access_token' => 'token-preseptor-123',
            'kode_registrasi' => 'PRESEP-001',
        ]);

        $rubrik = EvaluationRubric::create([
            'unit_event_id' => $event->id,
            'judul' => 'Penilaian Mini-CEX Kebidanan',
            'slug' => 'penilaian-mini-cex-kebidanan',
            'kategori' => 'klinik',
            'jenis_rubrik' => 'clinical',
            'rumus_penilaian' => 'score_times_weight',
            'skala_max' => 3,
            'created_by' => User::factory()->create()->id,
        ]);

        $response = $this->get(route('unit.events.rubrik.show', [
            'unitSlug' => $page->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Rubrik Penilaian Kegiatan');
        $response->assertSee('Penilaian Mini-CEX Kebidanan');
    }
}

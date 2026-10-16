<?php

namespace Tests\Feature;

use App\Models\PageSection;
use App\Models\PageSectionSubmission;
use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\UnitPage;
use App\Models\User;
use App\Support\PageFormBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageFormBlockTest extends TestCase
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

        if (! Schema::hasTable('page_section_submissions')) {
            Schema::create('page_section_submissions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('section_id');
                $table->json('data');
                $table->string('ip', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_form_block_submission_stores_structured_survey_answers(): void
    {
        $section = $this->makeFormSection();

        $response = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            'fields' => [
                'nama_inisial' => 'DML',
                'jenis_kelamin' => 'laki_laki',
                'sumber_informasi' => ['website', 'media_sosial'],
                'visi' => '4',
                'pemahaman_vmts' => [
                    'makna_visi' => '4',
                    'strategi_relevan' => '3',
                ],
                'saran' => 'Sosialisasi rutin setiap awal semester.',
            ],
        ]);

        $response->assertRedirect('/unit/' . $section->page->slug);
        $response->assertSessionHas('blok_sukses_' . $section->id, true);

        $submission = PageSectionSubmission::query()->where('section_id', $section->id)->firstOrFail();

        $this->assertSame('DML', data_get($submission->data, 'nama_inisial'));
        $this->assertSame('laki_laki', data_get($submission->data, 'jenis_kelamin'));
        $this->assertSame(['website', 'media_sosial'], data_get($submission->data, 'sumber_informasi'));
        $this->assertSame('4', data_get($submission->data, 'visi'));
        $this->assertSame('4', data_get($submission->data, 'pemahaman_vmts.makna_visi'));
        $this->assertSame('3', data_get($submission->data, 'pemahaman_vmts.strategi_relevan'));
        $this->assertSame('Sosialisasi rutin setiap awal semester.', data_get($submission->data, 'saran'));
    }

    public function test_admin_summary_endpoint_returns_live_tabulation_payload(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $section = $this->makeFormSection();

        PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'nama_inisial' => 'DML',
                'jenis_kelamin' => 'laki_laki',
                'sumber_informasi' => ['website', 'media_sosial'],
                'visi' => '4',
                'pemahaman_vmts' => [
                    'makna_visi' => '4',
                    'strategi_relevan' => '3',
                ],
                'saran' => 'Tambahkan sosialisasi via website.',
            ],
        ]);

        PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'nama_inisial' => 'RZ',
                'jenis_kelamin' => 'perempuan',
                'sumber_informasi' => ['website'],
                'visi' => '3',
                'pemahaman_vmts' => [
                    'makna_visi' => '4',
                    'strategi_relevan' => '4',
                ],
                'saran' => 'Perlu infografik singkat untuk mahasiswa baru.',
            ],
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.page-sections.responses.summary', $section));

        $response->assertOk();
        $response->assertJsonPath('total_responses', 2);
        $response->assertJsonPath('field_count', 6);

        $payload = $response->json();

        $genderField = collect($payload['fields'])->firstWhere('key', 'jenis_kelamin');
        $this->assertSame('bar', $genderField['visualization']);
        $this->assertSame(2, $genderField['response_count']);
        $this->assertSame(1, collect($genderField['items'])->firstWhere('value', 'laki_laki')['count']);
        $this->assertSame(1, collect($genderField['items'])->firstWhere('value', 'perempuan')['count']);

        $infoField = collect($payload['fields'])->firstWhere('key', 'sumber_informasi');
        $this->assertSame(2, $infoField['response_count']);
        $this->assertSame(2, collect($infoField['items'])->firstWhere('value', 'website')['count']);
        $this->assertSame(1, collect($infoField['items'])->firstWhere('value', 'media_sosial')['count']);

        $scaleField = collect($payload['fields'])->firstWhere('key', 'visi');
        $this->assertSame(3.5, $scaleField['average']);

        $matrixField = collect($payload['fields'])->firstWhere('key', 'pemahaman_vmts');
        $maknaVisiRow = collect($matrixField['rows'])->firstWhere('key', 'makna_visi');
        $this->assertSame(2, $maknaVisiRow['response_count']);
        $this->assertEquals(4.0, $maknaVisiRow['average']);

        $this->assertCount(2, $payload['responses']);
    }

    public function test_form_block_session_limit_rejects_second_submission_in_same_session(): void
    {
        $section = $this->makeFormSection();
        $config = $section->konfigurasi;
        $config['response_limit'] = 'session';
        $config['limit_message'] = 'Formulir ini hanya boleh diisi sekali per sesi.';
        $section->update(['konfigurasi' => $config]);

        $payload = [
            'fields' => [
                'nama_inisial' => 'DML',
                'jenis_kelamin' => 'laki_laki',
                'sumber_informasi' => ['website'],
                'visi' => '4',
                'pemahaman_vmts' => [
                    'makna_visi' => '4',
                    'strategi_relevan' => '4',
                ],
                'saran' => 'Pengisian pertama.',
            ],
        ];

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), $payload)
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHas('blok_sukses_' . $section->id, true);

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), $payload)
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHasErrors(['form']);

        $this->assertSame(1, PageSectionSubmission::query()->where('section_id', $section->id)->count());
    }

    public function test_form_block_email_limit_rejects_duplicate_email_address(): void
    {
        $section = $this->makeEmailLimitedFormSection();

        $firstPayload = [
            'fields' => [
                'nama' => 'Dina',
                'email' => 'dina@example.com',
                'pesan' => 'Respons pertama.',
            ],
        ];

        $secondPayload = [
            'fields' => [
                'nama' => 'Dina Kedua',
                'email' => 'dina@example.com',
                'pesan' => 'Respons kedua.',
            ],
        ];

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), $firstPayload)
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHas('blok_sukses_' . $section->id, true);

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), $secondPayload)
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHasErrors(['fields.email']);

        $this->assertSame(1, PageSectionSubmission::query()->where('section_id', $section->id)->count());
    }

    public function test_admin_summary_and_export_can_be_filtered_by_date(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $section = $this->makeFormSection();

        $olderSubmission = PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'nama_inisial' => 'OLD',
                'jenis_kelamin' => 'laki_laki',
                'sumber_informasi' => ['website'],
                'visi' => '2',
                'pemahaman_vmts' => [
                    'makna_visi' => '2',
                    'strategi_relevan' => '2',
                ],
                'saran' => 'Respons lama.',
            ],
        ]);
        $olderSubmission->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->save();

        PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'nama_inisial' => 'NEW',
                'jenis_kelamin' => 'perempuan',
                'sumber_informasi' => ['media_sosial'],
                'visi' => '4',
                'pemahaman_vmts' => [
                    'makna_visi' => '4',
                    'strategi_relevan' => '4',
                ],
                'saran' => 'Respons baru.',
            ],
        ]);

        $from = now()->subDay()->toDateString();

        $summaryResponse = $this->actingAs($admin)->getJson(route('admin.page-sections.responses.summary', [
            'section' => $section,
            'from' => $from,
        ]));

        $summaryResponse->assertOk();
        $summaryResponse->assertJsonPath('total_responses', 1);
        $summaryResponse->assertJsonPath('filters.from', $from);
        $summaryResponse->assertJsonMissing(['value' => 'OLD']);

        $exportResponse = $this->actingAs($admin)->get(route('admin.page-sections.responses.export', [
            'section' => $section,
            'from' => $from,
        ]));

        $exportResponse->assertOk();
        $exportResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('NEW', $exportResponse->getContent());
        $this->assertStringNotContainsString('OLD', $exportResponse->getContent());
    }

    public function test_form_block_supports_file_uploads_and_structural_elements(): void
    {
        config()->set('media.local_disk', 'public');
        $testDiskRoot = storage_path('framework/testing/public-uploads');
        config()->set('filesystems.disks.public.root', $testDiskRoot);
        config()->set('filesystems.disks.public.url', '/storage/testing-public');
        if (! is_dir($testDiskRoot)) {
            mkdir($testDiskRoot, 0775, true);
        }
        Storage::forgetDisk('public');
        Storage::disk('public')->deleteDirectory('page-form-uploads');

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $section = $this->makeAdvancedFormSection();

        $response = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            'fields' => [
                'nama' => 'Nadia',
                'lampiran' => [
                    UploadedFile::fake()->create('surat-pernyataan.pdf', 240, 'application/pdf'),
                    UploadedFile::fake()->image('foto-bukti.png'),
                ],
                'tanda_tangan' => $this->sampleSignatureDataUrl(),
                'tautan_portofolio' => 'https://example.com/portofolio',
            ],
        ]);

        $response->assertRedirect('/unit/' . $section->page->slug);
        $response->assertSessionHas('blok_sukses_' . $section->id, true);

        $submission = PageSectionSubmission::query()->where('section_id', $section->id)->firstOrFail();
        $this->assertSame('Nadia', data_get($submission->data, 'nama'));
        $this->assertSame('https://example.com/portofolio', data_get($submission->data, 'tautan_portofolio'));

        $files = data_get($submission->data, 'lampiran');
        $this->assertIsArray($files);
        $this->assertCount(2, $files);
        $this->assertSame('surat-pernyataan.pdf', $files[0]['name']);
        $this->assertSame('foto-bukti.png', $files[1]['name']);
        Storage::disk('public')->assertExists($files[0]['path']);
        Storage::disk('public')->assertExists($files[1]['path']);

        $signature = data_get($submission->data, 'tanda_tangan');
        $this->assertIsArray($signature);
        $this->assertSame('image/png', $signature['mime_type']);
        Storage::disk('public')->assertExists($signature['path']);

        $summaryResponse = $this->actingAs($admin)->getJson(route('admin.page-sections.responses.summary', $section));
        $summaryResponse->assertOk();
        $summaryResponse->assertJsonPath('field_count', 4);

        $payload = $summaryResponse->json();
        $this->assertNull(collect($payload['fields'])->firstWhere('type', 'section'));
        $this->assertNull(collect($payload['fields'])->firstWhere('type', 'divider'));

        $fileField = collect($payload['fields'])->firstWhere('key', 'lampiran');
        $this->assertSame('files', $fileField['visualization']);
        $this->assertSame(1, $fileField['response_count']);
        $this->assertSame(2, $fileField['file_count']);
        $this->assertCount(2, $fileField['files']);

        $signatureField = collect($payload['fields'])->firstWhere('key', 'tanda_tangan');
        $this->assertSame('files', $signatureField['visualization']);
        $this->assertSame(1, $signatureField['response_count']);
        $this->assertSame(1, $signatureField['file_count']);
        $this->assertTrue((bool) data_get($signatureField, 'files.0.is_image'));

        $recentFileAnswer = collect($payload['responses'][0]['answers'])->firstWhere('label', 'Lampiran Bukti');
        $this->assertSame('file', $recentFileAnswer['type']);
        $this->assertCount(2, $recentFileAnswer['files']);

        $recentSignatureAnswer = collect($payload['responses'][0]['answers'])->firstWhere('label', 'Tanda Tangan Persetujuan');
        $this->assertSame('signature', $recentSignatureAnswer['type']);
        $this->assertCount(1, $recentSignatureAnswer['files']);

        $exportResponse = $this->actingAs($admin)->get(route('admin.page-sections.responses.export', $section));
        $exportResponse->assertOk();
        $this->assertStringContainsString('surat-pernyataan.pdf', $exportResponse->getContent());
        $this->assertStringContainsString('foto-bukti.png', $exportResponse->getContent());
        $this->assertStringContainsString('signature-', $exportResponse->getContent());
    }

    public function test_form_block_renders_header_intro_help_and_option_images(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $section = $this->makeMediaRichFormSection();

        $response = $this->get('/unit/' . $section->page->slug);

        $response->assertOk();
        $response->assertSee('Formulir Media Lengkap');
        $response->assertSee('/storage/form-media/logo-formulir.png');
        $response->assertSee('/storage/form-media/kop-formulir.jpg');
        $response->assertSee('/storage/form-media/pengantar-formulir.jpg');
        $response->assertSee('/storage/form-media/petunjuk-identitas.jpg');
        $response->assertSee('/storage/form-media/opsi-website.jpg');
        $response->assertSee('/storage/form-media/opsi-whatsapp.jpg');

        $summaryResponse = $this->actingAs($admin)->getJson(route('admin.page-sections.responses.summary', $section));
        $summaryResponse->assertOk();
        $summaryResponse->assertJsonPath('fields.1.items.0.media_url', '/storage/form-media/opsi-website.jpg');
    }

    public function test_quiz_mode_scores_submission_and_returns_item_analysis(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $section = $this->makeQuizFormSection();

        $publicResponse = $this->get('/unit/' . $section->page->slug);
        $publicResponse->assertOk();
        $publicResponse->assertSee('Batas lulus 70%');
        $publicResponse->assertSee('data-quiz-duration-minutes="15"', false);

        $submitResponse = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            '_quiz_started_at' => now()->subMinutes(4)->toIso8601String(),
            'fields' => [
                'jalur_info' => 'website',
                'kode_unit' => 'TI',
                'perlu_surat' => 'tidak',
            ],
        ]);

        $submitResponse->assertRedirect('/unit/' . $section->page->slug);
        $submitResponse->assertSessionHas('blok_sukses_' . $section->id, true);

        $submission = PageSectionSubmission::query()->where('section_id', $section->id)->latest('id')->firstOrFail();
        $quizPayload = data_get($submission->data, '__quiz');
        $this->assertIsArray($quizPayload);
        $this->assertSame(4.5, (float) data_get($quizPayload, 'score'));
        $this->assertSame(4.5, (float) data_get($quizPayload, 'max_score'));
        $this->assertSame(100.0, (float) data_get($quizPayload, 'percent'));
        $this->assertTrue((bool) data_get($quizPayload, 'passed'));

        PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'jalur_info' => 'whatsapp',
                'kode_unit' => 'TI',
                'perlu_surat' => 'tidak',
            ],
        ]);

        PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'jalur_info' => 'website',
                'kode_unit' => 'SI',
                'perlu_surat' => 'tidak',
            ],
        ]);

        PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'jalur_info' => 'whatsapp',
                'kode_unit' => 'SI',
                'perlu_surat' => 'tidak',
            ],
        ]);

        $summaryResponse = $this->actingAs($admin)->getJson(route('admin.page-sections.responses.summary', $section));
        $summaryResponse->assertOk();
        $summaryResponse->assertJsonPath('quiz.enabled', true);
        $summaryResponse->assertJsonPath('quiz.attempt_count', 4);
        $summaryResponse->assertJsonPath('quiz.pass_count', 2);

        $payload = $summaryResponse->json();
        $quizItem = collect($payload['quiz']['items'])->firstWhere('key', 'jalur_info');
        $this->assertSame(50.0, (float) $quizItem['difficulty_index']);
        $this->assertSame(1.0, (float) $quizItem['discrimination_index']);
        $this->assertSame(2, (int) collect($quizItem['option_analysis'])->firstWhere('value', 'website')['selection_count']);

        $exportResponse = $this->actingAs($admin)->get(route('admin.page-sections.responses.export', $section));
        $exportResponse->assertOk();
        $this->assertStringContainsString('Skor Quiz', $exportResponse->getContent());
        $this->assertStringContainsString('Lulus', $exportResponse->getContent());
    }

    public function test_quiz_submission_includes_rationale_feedback_in_payload(): void
    {
        $section = $this->makeQuizFormSection();
        $config = $section->konfigurasi;
        $config['show_rationales_after_submit'] = true;
        $config['fields'][0]['opsi'][0]['rationale'] = 'Website resmi memuat informasi paling lengkap dan terverifikasi.';
        $config['fields'][0]['opsi'][1]['rationale'] = 'WhatsApp hanya bersifat pendukung sehingga tidak menjadi kanal utama.';
        $config['fields'][1]['correct_rationale'] = 'Kode unit Teknologi Informasi memang ditulis TI.';
        $config['fields'][1]['incorrect_rationale'] = 'Periksa kembali kode unit resmi yang digunakan pada halaman unit.';
        $section->update(['konfigurasi' => $config]);

        $response = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            '_quiz_started_at' => now()->subMinutes(3)->toIso8601String(),
            'fields' => [
                'jalur_info' => 'website',
                'kode_unit' => 'SI',
                'perlu_surat' => 'tidak',
            ],
        ]);

        $response->assertRedirect('/unit/' . $section->page->slug);
        $response->assertSessionHas('blok_quiz_result_' . $section->id, function (array $quiz): bool {
            return (bool) ($quiz['show_rationales_after_submit'] ?? false)
                && data_get($quiz, 'items.jalur_info.rationale') === 'Website resmi memuat informasi paling lengkap dan terverifikasi.'
                && data_get($quiz, 'items.kode_unit.rationale') === 'Periksa kembali kode unit resmi yang digunakan pada halaman unit.';
        });

        $submission = PageSectionSubmission::query()->where('section_id', $section->id)->latest('id')->firstOrFail();
        $this->assertSame('Website resmi memuat informasi paling lengkap dan terverifikasi.', data_get($submission->data, '__quiz.items.jalur_info.rationale'));
        $this->assertSame('Periksa kembali kode unit resmi yang digunakan pada halaman unit.', data_get($submission->data, '__quiz.items.kode_unit.rationale'));
    }

    public function test_required_paragraph_field_rejects_blank_rich_text_markup(): void
    {
        $section = $this->makeFormSection();
        $config = $section->konfigurasi;
        $config['fields'][5]['wajib'] = true;
        $section->update(['konfigurasi' => $config]);

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), [
                'fields' => [
                    'nama_inisial' => 'DML',
                    'jenis_kelamin' => 'laki_laki',
                    'sumber_informasi' => ['website'],
                    'visi' => '4',
                    'pemahaman_vmts' => [
                        'makna_visi' => '4',
                        'strategi_relevan' => '4',
                    ],
                    'saran' => '<p><br></p>',
                ],
            ])
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHasErrors(['fields.saran']);

        $this->assertSame(0, PageSectionSubmission::query()->where('section_id', $section->id)->count());
    }

    public function test_quiz_textarea_answer_is_scored_from_rich_text_plain_text(): void
    {
        $section = $this->makeQuizFormSection();
        $config = $section->konfigurasi;
        $config['fields'][1]['tipe'] = 'textarea';
        $config['fields'][1]['answer_key'] = 'TI';
        $section->update(['konfigurasi' => $config]);

        $response = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            '_quiz_started_at' => now()->subMinutes(2)->toIso8601String(),
            'fields' => [
                'jalur_info' => 'website',
                'kode_unit' => '<p>TI</p>',
                'perlu_surat' => 'tidak',
            ],
        ]);

        $response->assertRedirect('/unit/' . $section->page->slug);
        $response->assertSessionHas('blok_quiz_result_' . $section->id, function (array $quiz): bool {
            return data_get($quiz, 'items.kode_unit.correct') === true;
        });
    }

    public function test_admin_summary_strips_markup_from_paragraph_answers(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $section = $this->makeFormSection();

        PageSectionSubmission::query()->create([
            'section_id' => $section->id,
            'data' => [
                'nama_inisial' => 'DML',
                'jenis_kelamin' => 'laki_laki',
                'sumber_informasi' => ['website'],
                'visi' => '4',
                'pemahaman_vmts' => [
                    'makna_visi' => '4',
                    'strategi_relevan' => '4',
                ],
                'saran' => '<p>Baris pertama</p><ul><li>Poin penting</li></ul>',
            ],
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.page-sections.responses.summary', $section));
        $response->assertOk();

        $saranField = collect($response->json('fields'))->firstWhere('key', 'saran');
        $this->assertIsArray($saranField);
        $this->assertStringNotContainsString('<p>', (string) ($saranField['items'][0] ?? ''));
        $this->assertStringContainsString('Baris pertama', (string) ($saranField['items'][0] ?? ''));
        $this->assertStringContainsString('Poin penting', (string) ($saranField['items'][0] ?? ''));
    }

    public function test_employee_only_form_blocks_guest_and_allows_authenticated_employee(): void
    {
        $section = $this->makeAccessControlledFormSection(['access_scope' => 'employee_only']);

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), [
                'fields' => [
                    'nama_lengkap' => 'Guest User',
                ],
            ])
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHasErrors(['form']);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'is_admin' => false,
        ]);

        $this->actingAs($employee)
            ->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), [
                'fields' => [
                    'nama_lengkap' => 'Pegawai Unit',
                ],
            ])
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHas('blok_sukses_' . $section->id, true);

        $submission = PageSectionSubmission::query()->where('section_id', $section->id)->latest('id')->firstOrFail();
        $this->assertSame('employee_only', data_get($submission->data, '__access.scope'));
        $this->assertSame('pegawai', data_get($submission->data, '__access.user_type'));
    }

    public function test_event_participant_form_can_be_authorized_with_registration_code(): void
    {
        $section = $this->makeAccessControlledFormSection(['access_scope' => 'event_participants']);
        $event = UnitEvent::query()->create([
            'unit_page_id' => $section->page->id,
            'judul' => 'Webinar Akses Formulir',
            'slug' => 'webinar-akses-formulir',
            'status' => 'published',
            'mulai_at' => now()->addDay(),
            'selesai_at' => now()->addDays(2),
        ]);

        $participant = UnitEventParticipant::query()->create([
            'unit_event_id' => $event->id,
            'peran' => 'participant',
            'participant_category' => 'public',
            'sumber_registrasi' => 'admin',
            'nama' => 'Peserta Event',
            'email' => 'peserta@example.com',
            'kode_registrasi' => 'EVT-001',
            'access_token' => Str::random(40),
            'registered_at' => now(),
        ]);

        $config = $section->konfigurasi;
        $config['access_event_id'] = $event->id;
        $section->update(['konfigurasi' => $config]);

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.authorize', $section), [
                '_access_registration_code' => 'EVT-001',
            ])
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHas('page_form_access.' . $section->id);

        $this->withSession([
            'page_form_access.' . $section->id => [
                'participant_id' => $participant->id,
                'event_id' => $event->id,
                'granted_at' => now()->toIso8601String(),
            ],
        ])->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), [
                'fields' => [
                    'nama_lengkap' => 'Peserta Event',
                ],
            ])
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHas('blok_sukses_' . $section->id, true);

        $submission = PageSectionSubmission::query()->where('section_id', $section->id)->latest('id')->firstOrFail();
        $this->assertSame('event_participants', data_get($submission->data, '__access.scope'));
        $this->assertSame($participant->id, data_get($submission->data, '__access.participant_id'));
        $this->assertSame($event->id, data_get($submission->data, '__access.event_id'));
    }

    public function test_quiz_submission_stores_and_summarizes_proctoring_payload(): void
    {
        config()->set('media.local_disk', 'public');
        $testDiskRoot = storage_path('framework/testing/public-uploads');
        config()->set('filesystems.disks.public.root', $testDiskRoot);
        config()->set('filesystems.disks.public.url', '/storage/testing-public');
        if (! is_dir($testDiskRoot)) {
            mkdir($testDiskRoot, 0775, true);
        }
        Storage::forgetDisk('public');
        Storage::disk('public')->deleteDirectory('page-form-uploads');

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $section = $this->makeQuizFormSection();
        $config = $section->konfigurasi;
        $config['proctoring_enabled'] = true;
        $config['proctoring_capture_selfie'] = true;
        $config['proctoring_require_fullscreen'] = true;
        $section->update(['konfigurasi' => $config]);

        $this->from('/unit/' . $section->page->slug)
            ->post(route('block.submit', $section), [
                '_quiz_started_at' => now()->subMinutes(2)->toIso8601String(),
                '_proctoring' => [
                    'started_at' => now()->subMinutes(2)->toIso8601String(),
                    'device_json' => json_encode(['screen' => ['width' => 1440, 'height' => 900], 'timezone' => 'Asia/Jakarta'], JSON_THROW_ON_ERROR),
                    'events_json' => json_encode([
                        ['type' => 'fullscreen_exit', 'at' => now()->subMinute()->toIso8601String()],
                        ['type' => 'visibility_hidden', 'at' => now()->subSeconds(40)->toIso8601String()],
                        ['type' => 'copy_attempt', 'at' => now()->subSeconds(20)->toIso8601String()],
                    ], JSON_THROW_ON_ERROR),
                    'suspicious_count' => 3,
                    'selfie' => $this->sampleSignatureDataUrl(),
                ],
                'fields' => [
                    'jalur_info' => 'website',
                    'kode_unit' => 'TI',
                    'perlu_surat' => 'tidak',
                ],
            ])
            ->assertRedirect('/unit/' . $section->page->slug)
            ->assertSessionHas('blok_sukses_' . $section->id, true);

        $submission = PageSectionSubmission::query()->where('section_id', $section->id)->latest('id')->firstOrFail();
        $this->assertSame(3, data_get($submission->data, '__proctoring.suspicious_count'));
        $this->assertSame(1, data_get($submission->data, '__proctoring.fullscreen_exit_count'));
        $this->assertSame(1, data_get($submission->data, '__proctoring.tab_switch_count'));
        $this->assertSame(1, data_get($submission->data, '__proctoring.clipboard_violation_count'));
        $this->assertIsArray(data_get($submission->data, '__proctoring.selfie'));

        $summaryResponse = $this->actingAs($admin)->getJson(route('admin.page-sections.responses.summary', $section));
        $summaryResponse->assertOk();
        $summaryResponse->assertJsonPath('quiz.proctoring.enabled', true);
        $summaryResponse->assertJsonPath('quiz.proctoring.total_suspicious_count', 3);
        $summaryResponse->assertJsonPath('responses.0.proctoring.suspicious_count', 3);
    }

    public function test_conditional_required_field_only_validates_when_visible(): void
    {
        $section = $this->makeConditionalFormSection();

        $firstResponse = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            'fields' => [
                'butuh_nomor' => 'tidak',
            ],
        ]);

        $firstResponse->assertRedirect('/unit/' . $section->page->slug);
        $firstResponse->assertSessionHas('blok_sukses_' . $section->id, true);
        $this->assertNull(data_get(PageSectionSubmission::query()->latest('id')->firstOrFail()->data, 'nomor_surat'));

        $secondResponse = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            'fields' => [
                'butuh_nomor' => 'ya',
            ],
        ]);

        $secondResponse->assertRedirect('/unit/' . $section->page->slug);
        $secondResponse->assertSessionHasErrors(['fields.nomor_surat']);
    }

    public function test_schedule_blocks_submission_outside_open_window(): void
    {
        $section = $this->makeScheduledFormSection();

        $pageResponse = $this->get('/unit/' . $section->page->slug);
        $pageResponse->assertOk();
        $pageResponse->assertSee('Formulir ini belum dibuka.');

        $submitResponse = $this->from('/unit/' . $section->page->slug)->post(route('block.submit', $section), [
            'fields' => [
                'nama_lengkap' => 'Dina',
            ],
        ]);

        $submitResponse->assertRedirect('/unit/' . $section->page->slug);
        $submitResponse->assertSessionHasErrors(['form']);
    }

    private function makeFormSection(): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'teknologi-informasi',
            'judul' => 'Unit Teknologi Informasi',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Survey VMTS',
            'konfigurasi' => [
                'pengantar' => 'Bantu kami menilai pemahaman VMTS Poltekkes Bandung.',
                'pesan_sukses' => 'Terima kasih atas partisipasi Anda.',
                'judul_submit' => 'Kirim Jawaban',
                'fields' => [
                    [
                        'key' => 'nama_inisial',
                        'nama' => 'Nama (Inisial)',
                        'tipe' => 'text',
                        'wajib' => true,
                    ],
                    [
                        'key' => 'jenis_kelamin',
                        'nama' => 'Jenis Kelamin',
                        'tipe' => 'radio',
                        'wajib' => true,
                        'opsi' => [
                            ['value' => 'laki_laki', 'label' => 'Laki-laki'],
                            ['value' => 'perempuan', 'label' => 'Perempuan'],
                        ],
                    ],
                    [
                        'key' => 'sumber_informasi',
                        'nama' => 'Dari mana Anda mengetahui VMTS?',
                        'tipe' => 'checkbox',
                        'wajib' => true,
                        'opsi' => [
                            ['value' => 'website', 'label' => 'Website'],
                            ['value' => 'media_sosial', 'label' => 'Media Sosial'],
                            ['value' => 'seminar', 'label' => 'Seminar'],
                        ],
                    ],
                    [
                        'key' => 'visi',
                        'nama' => 'Bagaimana pernyataan visi menurut Anda?',
                        'tipe' => 'scale',
                        'wajib' => true,
                        'min' => 1,
                        'max' => 4,
                        'min_label' => 'Tidak Setuju',
                        'max_label' => 'Sangat Setuju',
                    ],
                    [
                        'key' => 'pemahaman_vmts',
                        'nama' => 'Pemahaman VMTS',
                        'tipe' => 'matrix',
                        'wajib' => true,
                        'min' => 1,
                        'max' => 4,
                        'min_label' => 'Sangat Tidak Paham',
                        'max_label' => 'Sangat Paham',
                        'pernyataan' => [
                            ['key' => 'makna_visi', 'label' => 'Saya memahami makna visi Poltekkes Bandung.'],
                            ['key' => 'strategi_relevan', 'label' => 'Strategi yang ditetapkan relevan dengan kebutuhan saat ini.'],
                        ],
                    ],
                    [
                        'key' => 'saran',
                        'nama' => 'Saran Perbaikan Sosialisasi VMTS',
                        'tipe' => 'textarea',
                        'wajib' => false,
                    ],
                ],
            ],
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    private function makeAdvancedFormSection(): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'form-advanced-unit',
            'judul' => 'Form Advanced Unit',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Formulir Lampiran',
            'konfigurasi' => [
                'pengantar' => 'Silakan lengkapi data dan unggah lampiran pendukung.',
                'pesan_sukses' => 'Respons beserta lampiran berhasil dikirim.',
                'judul_submit' => 'Kirim Data',
                'fields' => [
                    [
                        'key' => 'bagian_identitas',
                        'nama' => 'Identitas Responden',
                        'tipe' => 'section',
                        'deskripsi' => 'Lengkapi identitas dasar sebelum mengirim lampiran.',
                    ],
                    [
                        'key' => 'nama',
                        'nama' => 'Nama',
                        'tipe' => 'text',
                        'wajib' => true,
                    ],
                    [
                        'key' => 'lampiran',
                        'nama' => 'Lampiran Bukti',
                        'tipe' => 'file',
                        'wajib' => true,
                        'multiple' => true,
                        'max_files' => 2,
                        'max_size_mb' => 5,
                        'accept' => '.pdf,.jpg,.jpeg,.png',
                    ],
                    [
                        'key' => 'pemisah_portofolio',
                        'nama' => 'Tautan Pendukung',
                        'tipe' => 'divider',
                    ],
                    [
                        'key' => 'tanda_tangan',
                        'nama' => 'Tanda Tangan Persetujuan',
                        'tipe' => 'signature',
                        'wajib' => true,
                        'canvas_height' => 180,
                    ],
                    [
                        'key' => 'tautan_portofolio',
                        'nama' => 'Tautan Portofolio',
                        'tipe' => 'url',
                        'wajib' => false,
                    ],
                ],
            ],
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    private function makeMediaRichFormSection(): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'form-media-rich-unit',
            'judul' => 'Form Media Rich Unit',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Formulir Media Lengkap',
            'konfigurasi' => [
                'pengantar' => 'Formulir ini memakai identitas visual, gambar pengantar, ilustrasi petunjuk, dan opsi jawaban bergambar.',
                'header_logo_url' => '/storage/form-media/logo-formulir.png',
                'header_logo_alt' => 'Logo resmi formulir',
                'header_image_url' => '/storage/form-media/kop-formulir.jpg',
                'header_image_alt' => 'Kop formulir digital',
                'pengantar_image_url' => '/storage/form-media/pengantar-formulir.jpg',
                'pengantar_image_alt' => 'Gambar pengantar formulir',
                'judul_submit' => 'Kirim',
                'fields' => [
                    [
                        'key' => 'nama_lengkap',
                        'nama' => 'Nama Lengkap',
                        'tipe' => 'text',
                        'wajib' => true,
                        'deskripsi' => 'Pastikan nama sesuai dokumen resmi.',
                        'deskripsi_media_url' => '/storage/form-media/petunjuk-identitas.jpg',
                        'deskripsi_media_alt' => 'Petunjuk unggah berkas identitas',
                    ],
                    [
                        'key' => 'kanal_informasi',
                        'nama' => 'Kanal Informasi Favorit',
                        'tipe' => 'radio',
                        'wajib' => true,
                        'opsi' => [
                            [
                                'value' => 'website',
                                'label' => 'Website Resmi',
                                'media_url' => '/storage/form-media/opsi-website.jpg',
                                'media_alt' => 'Ilustrasi website resmi',
                            ],
                            [
                                'value' => 'whatsapp',
                                'label' => 'WhatsApp Broadcast',
                                'media_url' => '/storage/form-media/opsi-whatsapp.jpg',
                                'media_alt' => 'Ilustrasi WhatsApp broadcast',
                            ],
                        ],
                    ],
                ],
            ],
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    private function makeQuizFormSection(): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'form-quiz-unit',
            'judul' => 'Form Quiz Unit',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Quiz PMB',
            'konfigurasi' => [
                'mode' => 'quiz',
                'passing_score' => 70,
                'duration_minutes' => 15,
                'opens_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'closes_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'fields' => [
                    [
                        'key' => 'jalur_info',
                        'nama' => 'Kanal Informasi Paling Tepat',
                        'tipe' => 'radio',
                        'wajib' => true,
                        'question_weight' => 2,
                        'opsi' => [
                            ['value' => 'website', 'label' => 'Website', 'score' => 1.5, 'is_correct' => true],
                            ['value' => 'whatsapp', 'label' => 'WhatsApp', 'score' => 0, 'is_correct' => false],
                        ],
                    ],
                    [
                        'key' => 'kode_unit',
                        'nama' => 'Kode Unit',
                        'tipe' => 'text',
                        'wajib' => true,
                        'question_weight' => 1,
                        'answer_key' => 'TI',
                    ],
                    [
                        'key' => 'perlu_surat',
                        'nama' => 'Perlu Nomor Surat?',
                        'tipe' => 'radio',
                        'wajib' => true,
                        'question_weight' => 0,
                        'opsi' => [
                            ['value' => 'ya', 'label' => 'Ya'],
                            ['value' => 'tidak', 'label' => 'Tidak'],
                        ],
                    ],
                    [
                        'key' => 'nomor_surat',
                        'nama' => 'Nomor Surat',
                        'tipe' => 'text',
                        'wajib' => true,
                        'visibility_mode' => 'conditional',
                        'visibility_match' => 'all',
                        'visibility_rules' => [
                            ['source_key' => 'perlu_surat', 'operator' => 'equals', 'value' => 'ya'],
                        ],
                    ],
                ],
                'judul_submit' => 'Kirim Jawaban',
                'pesan_sukses' => 'Quiz berhasil dikirim.',
            ],
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    private function makeConditionalFormSection(): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'form-conditional-unit',
            'judul' => 'Form Conditional Unit',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Form Conditional',
            'konfigurasi' => [
                'fields' => [
                    [
                        'key' => 'butuh_nomor',
                        'nama' => 'Perlu nomor surat?',
                        'tipe' => 'radio',
                        'wajib' => true,
                        'opsi' => [
                            ['value' => 'ya', 'label' => 'Ya'],
                            ['value' => 'tidak', 'label' => 'Tidak'],
                        ],
                    ],
                    [
                        'key' => 'nomor_surat',
                        'nama' => 'Nomor Surat',
                        'tipe' => 'text',
                        'wajib' => true,
                        'visibility_mode' => 'conditional',
                        'visibility_rules' => [
                            ['source_key' => 'butuh_nomor', 'operator' => 'equals', 'value' => 'ya'],
                        ],
                    ],
                ],
                'pesan_sukses' => 'Form conditional terkirim.',
            ],
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    private function makeScheduledFormSection(): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'form-scheduled-unit',
            'judul' => 'Form Scheduled Unit',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Form Scheduled',
            'konfigurasi' => [
                'opens_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'scheduled_message' => 'Formulir ini belum dibuka.',
                'fields' => [
                    [
                        'key' => 'nama_lengkap',
                        'nama' => 'Nama Lengkap',
                        'tipe' => 'text',
                        'wajib' => true,
                    ],
                ],
            ],
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    private function makeAccessControlledFormSection(array $configOverrides = []): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'form-access-unit-' . Str::lower(Str::random(6)),
            'judul' => 'Form Access Unit',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Form Akses Terkontrol',
            'konfigurasi' => array_replace_recursive([
                'fields' => [
                    [
                        'key' => 'nama_lengkap',
                        'nama' => 'Nama Lengkap',
                        'tipe' => 'text',
                        'wajib' => true,
                    ],
                ],
                'pesan_sukses' => 'Akses berhasil diverifikasi.',
                'access_scope' => 'public',
            ], $configOverrides),
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    private function sampleSignatureDataUrl(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnSUs8AAAAASUVORK5CYII=';
    }

    private function makeEmailLimitedFormSection(): PageSection
    {
        $page = UnitPage::query()->create([
            'role' => 'unit_ti',
            'slug' => 'survey-email-unit',
            'judul' => 'Survey Email Unit',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        return PageSection::query()->create([
            'unit_page_id' => $page->id,
            'tipe' => 'formulir',
            'judul' => 'Form Kontak Terbatas',
            'konfigurasi' => [
                'pesan_sukses' => 'Terima kasih.',
                'judul_submit' => 'Kirim',
                'response_limit' => 'email',
                'limit_message' => 'Email ini sudah pernah digunakan untuk mengirim respons.',
                'fields' => [
                    [
                        'key' => 'nama',
                        'nama' => 'Nama',
                        'tipe' => 'text',
                        'wajib' => true,
                    ],
                    [
                        'key' => 'email',
                        'nama' => 'Email',
                        'tipe' => 'email',
                        'wajib' => true,
                    ],
                    [
                        'key' => 'pesan',
                        'nama' => 'Pesan',
                        'tipe' => 'textarea',
                        'wajib' => false,
                    ],
                ],
            ],
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    public function test_ai_text_block_storage_persists_uploaded_draft_image(): void
    {
        config()->set('media.disk', 'local');
        config()->set('media.local_disk', 'public');
        Storage::fake('public');

        $normalized = PageFormBlock::attachAiTextUploadedMedia([
            'konten' => '<p>Konten blok AI.</p>',
            '_prompt_acara' => 'Wisuda Poltekkes Bandung 2026',
            'gambar_url' => '',
            'gambar_alt' => '',
        ], UploadedFile::fake()->image('wisuda.jpg', 1200, 800), 'Foto wisuda');

        $this->assertStringStartsWith('/storage/page-builder-media/ai-teks/', $normalized['gambar_url']);
        $this->assertSame('Foto wisuda', $normalized['gambar_alt']);

        $storedPath = ltrim(str_replace('/storage/', '', $normalized['gambar_url']), '/');
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_ai_text_block_view_renders_saved_draft_image_on_public_page(): void
    {
        $section = new PageSection([
            'judul' => 'Galeri Wisuda',
            'konfigurasi' => [
                'gambar_url' => '/storage/page-builder-media/ai-teks/wisuda.jpg',
                'gambar_alt' => 'Foto wisuda',
                'konten' => '<p>Konten blok AI.</p>',
            ],
        ]);

        $html = view('unit.blocks.ai_teks', compact('section'))->render();

        $this->assertStringContainsString('src="/storage/page-builder-media/ai-teks/wisuda.jpg"', $html);
        $this->assertStringContainsString('alt="Foto wisuda"', $html);
        $this->assertStringContainsString('<p>Konten blok AI.</p>', $html);
    }
}
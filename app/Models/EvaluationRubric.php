<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationRubric extends Model
{
    protected $table = 'evaluation_rubrics';

    protected $fillable = [
        'unit_page_id',
        'unit_event_id',
        'created_by',
        'judul',
        'slug',
        'deskripsi',
        'kategori',
        'jenis_rubrik',
        'rumus_penilaian',
        'skala_max',
        'is_template',
    ];

    protected $casts = [
        'skala_max'   => 'float',
        'is_template' => 'boolean',
    ];

    public static array $kategoriLabels = [
        'umum'      => 'Umum',
        'pbm'       => 'Pembelajaran / PBM',
        'seminar'   => 'Seminar & Presentasi',
        'praktikum' => 'Praktikum & Ujian Praktek',
        'kinerja'   => 'Evaluasi Kinerja',
        'klinik'    => 'Praktik Klinik Kebidanan',
    ];

    public static array $jenisRubrikLabels = [
        'analytic'     => 'Rubrik Analitik Gradasi (Analytic Rubric)',
        'checklist'    => 'Daftar Tilik & Observasi Prosedural (Checklist / DOPS / RDK)',
        'clinical'     => 'Evaluasi Klinis & Penalaran (Mini-CEX / CBD)',
        'group_matrix' => 'Penilaian Kelompok / Multi-Mahasiswa (Group Matrix)',
    ];

    public static array $rumusPenilaianLabels = [
        'weighted_percentage' => 'Persentase Terbobot (Total Bobot 100%)',
        'score_times_weight'  => 'Perkalian Skor × Bobot Item (Point System)',
        'raw_sum'             => 'Penjumlahan Skor Mentah (Raw Score Sum)',
    ];

    public function page()
    {
        return $this->belongsTo(UnitPage::class, 'unit_page_id');
    }

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(EvaluationRubricItem::class, 'evaluation_rubric_id')->orderBy('urutan')->orderBy('id');
    }

    public function rootItems()
    {
        return $this->hasMany(EvaluationRubricItem::class, 'evaluation_rubric_id')
            ->whereNull('parent_id')
            ->with('subItems')
            ->orderBy('urutan')
            ->orderBy('id');
    }

    public function getTotalWeightAttribute(): float
    {
        // Total weight calculated from root items (or all items without parent)
        return (float) $this->items()->whereNull('parent_id')->sum('bobot');
    }

    public function getKategoriLabelAttribute(): string
    {
        return self::$kategoriLabels[$this->kategori] ?? ucfirst($this->kategori);
    }

    public function getJenisRubrikLabelAttribute(): string
    {
        return self::$jenisRubrikLabels[$this->jenis_rubrik] ?? ucfirst($this->jenis_rubrik);
    }

    public function getRumusPenilaianLabelAttribute(): string
    {
        return self::$rumusPenilaianLabels[$this->rumus_penilaian] ?? ucfirst($this->rumus_penilaian);
    }

    /**
     * Calculate score based on scores array keyed by item ID
     * $itemScores = [item_id => given_score_value]
     */
    public function calculateFinalScore(array $itemScores): array
    {
        $totalWeight = $this->total_weight;
        $rumus = $this->rumus_penilaian ?? 'weighted_percentage';

        $weightedTotal = 0;
        $rawTotal = 0;
        $maxPossibleRaw = 0;

        // Determine active items for scoring (prefer leaf items, fallback to all items)
        $allItems = $this->items;
        $parentIds = $allItems->pluck('parent_id')->filter()->unique()->toArray();
        $evalItems = $allItems->reject(fn ($i) => in_array($i->id, $parentIds, true));
        if ($evalItems->isEmpty()) {
            $evalItems = $allItems;
        }

        foreach ($evalItems as $item) {
            $givenScore = (float) ($itemScores[$item->id] ?? 0);
            $rawTotal += $givenScore;

            if ($rumus === 'score_times_weight') {
                // Score * Weight per item formula (used in DOPS, RDK, etc.)
                $itemMaxPossible = ($this->skala_max ?: 1) * (float) $item->bobot;
                $maxPossibleRaw += $itemMaxPossible;
                $weightedTotal += ($givenScore * (float) $item->bobot);
            } else {
                // Standard max scale per item
                $maxPossibleRaw += (float) $this->skala_max;
                if ($totalWeight > 0) {
                    $weightedTotal += ($givenScore / ($this->skala_max ?: 1)) * (float) $item->bobot;
                }
            }
        }

        if ($rumus === 'score_times_weight') {
            $finalPercentage = $maxPossibleRaw > 0 ? ($weightedTotal / $maxPossibleRaw) * 100 : 0;
        } elseif ($rumus === 'raw_sum') {
            $finalPercentage = $maxPossibleRaw > 0 ? ($rawTotal / $maxPossibleRaw) * 100 : 0;
        } else {
            $finalPercentage = $totalWeight > 0 ? $weightedTotal : ($maxPossibleRaw > 0 ? ($rawTotal / $maxPossibleRaw) * 100 : 0);
        }

        return [
            'raw_total'       => round($rawTotal, 2),
            'max_raw'         => round($maxPossibleRaw, 2),
            'weighted_total'  => round($weightedTotal, 2),
            'final_percentage'=> round($finalPercentage, 2),
            'grade_letter'    => self::convertToGradeLetter($finalPercentage),
        ];
    }

    public static function convertToGradeLetter(float $score): string
    {
        if ($score >= 85) return 'A';
        if ($score >= 75) return 'B';
        if ($score >= 65) return 'C';
        if ($score >= 50) return 'D';
        return 'E';
    }

    /**
     * Get pre-configured standard rubric templates (Poltekkes Kemenkes Bandung Kebidanan)
     */
    public static function getPresetTemplates(): array
    {
        return [
            'reflective_writing' => [
                'judul'           => 'Rubrik Penilaian Reflective Writing (Stase Klinik)',
                'kategori'        => 'klinik',
                'jenis_rubrik'    => 'analytic',
                'rumus_penilaian' => 'score_times_weight',
                'skala_max'       => 3,
                'deskripsi'       => 'Instrumen penilaian refleksi kritis mahasiswa berdasarkan pengalaman praktik klinik (Form Reflective Writing). Total Bobot x Skor = 30.',
                'items' => [
                    [
                        'nama_kriteria' => 'Deskripsi pengalaman praktik / asuhan pada kasus',
                        'bobot'         => 2,
                        'deskripsi'     => 'Menguraikan pengalaman praktik/asuhan pada kasus dengan jelas, sistematis, dan mengeksplorasi isu/konflik.',
                        'levels'        => [
                            ['skor' => 3, 'label' => 'Sangat Baik (3)', 'deskripsi' => 'Dapat melakukan 3-4 aspek: menguraikan jelas, mengeksplorasi isu/konflik, mengemukakan cara pandang kompleks, dan sistematis.'],
                            ['skor' => 2, 'label' => 'Baik (2)', 'deskripsi' => 'Dapat melakukan 2 aspek dari kriteria deskripsi pengalaman.'],
                            ['skor' => 1, 'label' => 'Cukup (1)', 'deskripsi' => 'Dapat melakukan 1 aspek dari kriteria deskripsi pengalaman.'],
                            ['skor' => 0, 'label' => 'Kurang (0)', 'deskripsi' => 'Tidak dapat melakukan aspek-aspek deskripsi pengalaman.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Deskripsi perasaan terhadap pengalaman praktik/situasi',
                        'bobot'         => 1,
                        'deskripsi'     => 'Refleksi terhadap gambaran pikiran dan perasaan saat menghadapi kasus.',
                        'levels'        => [
                            ['skor' => 3, 'label' => 'Sangat Baik (3)', 'deskripsi' => 'Menuliskan pengakuan/gambaran perasaan yang dieksplorasi secara mendalam dan terinternalisasi.'],
                            ['skor' => 2, 'label' => 'Baik (2)', 'deskripsi' => 'Menuliskan gambaran perasaan yang dieksplorasi dan diperhatikan.'],
                            ['skor' => 1, 'label' => 'Cukup (1)', 'deskripsi' => 'Menuliskan gambaran perasaan tetapi tidak dieksplorasi.'],
                            ['skor' => 0, 'label' => 'Kurang (0)', 'deskripsi' => 'Tidak menggambarkan perasaan terhadap pengalaman.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Pemaparan evaluasi pengalaman praktik (baik & perlu perbaikan)',
                        'bobot'         => 2,
                        'deskripsi'     => 'Evaluasi hal-hal yang sudah baik dan masih perlu perbaikan pada situasi kasus.',
                        'levels'        => [
                            ['skor' => 3, 'label' => 'Sangat Baik (3)', 'deskripsi' => 'Evaluasi ditulis lengkap, sesuai deskripsi kasus, dan mengeksplorasi lebih dalam aspek yang baik dan perlu perbaikan.'],
                            ['skor' => 2, 'label' => 'Baik (2)', 'deskripsi' => 'Evaluasi ditulis lengkap dan sesuai deskripsi kasus tanpa eksplorasi mendalam.'],
                            ['skor' => 1, 'label' => 'Cukup (1)', 'deskripsi' => 'Evaluasi ditulis kurang lengkap atau tidak sesuai deskripsi kasus.'],
                            ['skor' => 0, 'label' => 'Kurang (0)', 'deskripsi' => 'Tidak menuliskan evaluasi pengalaman.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Kemampuan analisis dan pemaknaan dari pengalaman',
                        'bobot'         => 2,
                        'deskripsi'     => 'Apa arti dari situasi yang terjadi pada kasus dan kaitannya dengan konsep kebidanan.',
                        'levels'        => [
                            ['skor' => 3, 'label' => 'Sangat Baik (3)', 'deskripsi' => 'Analisis dan pemaknaan dalam secara komprehensif dari pengalaman praktik.'],
                            ['skor' => 2, 'label' => 'Baik (2)', 'deskripsi' => 'Analisis dan pemaknaan yang dalam pada beberapa pengalaman.'],
                            ['skor' => 1, 'label' => 'Cukup (1)', 'deskripsi' => 'Analisis dan pemaknaan dangkal.'],
                            ['skor' => 0, 'label' => 'Kurang (0)', 'deskripsi' => 'Tidak menuliskan analisis dan pemaknaan.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Kemampuan membuat kesimpulan / hal lain yang dapat dilakukan',
                        'bobot'         => 1,
                        'deskripsi'     => 'Kesimpulan dari pengalaman dan analisis yang telah dilakukan.',
                        'levels'        => [
                            ['skor' => 3, 'label' => 'Sangat Baik (3)', 'deskripsi' => 'Menguraikan kesimpulan sesuai deskripsi pengalaman dengan lengkap.'],
                            ['skor' => 2, 'label' => 'Baik (2)', 'deskripsi' => 'Menguraikan beberapa kesimpulan sesuai deskripsi kasus.'],
                            ['skor' => 1, 'label' => 'Cukup (1)', 'deskripsi' => 'Kesimpulan kurang sesuai deskripsi kasus.'],
                            ['skor' => 0, 'label' => 'Kurang (0)', 'deskripsi' => 'Tidak menguraikan kesimpulan.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Kemampuan membuat rencana tindak lanjut (RTL)',
                        'bobot'         => 2,
                        'deskripsi'     => 'Rencana tindakan jika hal tersebut terjadi lagi di masa mendatang.',
                        'levels'        => [
                            ['skor' => 3, 'label' => 'Sangat Baik (3)', 'deskripsi' => 'Rencana tindak lanjut terurai jelas, realistis, dan sesuai deskripsi kasus.'],
                            ['skor' => 2, 'label' => 'Baik (2)', 'deskripsi' => 'Menguraikan beberapa rencana tindak lanjut sesuai deskripsi.'],
                            ['skor' => 1, 'label' => 'Cukup (1)', 'deskripsi' => 'Rencana tindak lanjut tidak sesuai deskripsi.'],
                            ['skor' => 0, 'label' => 'Kurang (0)', 'deskripsi' => 'Tidak menguraikan rencana tindak lanjut.'],
                        ]
                    ],
                ],
            ],

            'dops' => [
                'judul'           => 'Penilaian DOPS (Direct Observation of Procedural Skills)',
                'kategori'        => 'klinik',
                'jenis_rubrik'    => 'checklist',
                'rumus_penilaian' => 'score_times_weight',
                'skala_max'       => 4,
                'deskripsi'       => 'Observasi langsung keterampilan prosedural klinik mahasiswa kebidanan (Skala 1-4, Skor x Bobot max = 63).',
                'items' => [
                    ['nama_kriteria' => 'Mendemonstrasikan pemahaman indikasi, anatomi relevan & prosedur teknik', 'bobot' => 3, 'deskripsi' => 'Penguasaan konsep dan teknik klinis'],
                    ['nama_kriteria' => 'Mendapatkan informed consent', 'bobot' => 1, 'deskripsi' => 'Persetujuan tindakan dari pasien/keluarga'],
                    ['nama_kriteria' => 'Mendemonstrasikan persiapan pre-prosedur yang sesuai', 'bobot' => 2, 'deskripsi' => 'Persiapan alat dan lingkungan'],
                    ['nama_kriteria' => 'Kemampuan teknik prosedural', 'bobot' => 3, 'deskripsi' => 'Ketrampilan motorik & ketepatan langkah'],
                    ['nama_kriteria' => 'Teknik aseptik & pencegahan infeksi', 'bobot' => 2, 'deskripsi' => 'Penerapan sterilitas dan APD'],
                    ['nama_kriteria' => 'Mencari bantuan saat diperlukan', 'bobot' => 1, 'deskripsi' => 'Kesadaran akan keterbatasan diri'],
                    ['nama_kriteria' => 'Manajemen sesudah prosedur', 'bobot' => 2, 'deskripsi' => 'Dekontaminasi alat dan kenyamanan pasien'],
                    ['nama_kriteria' => 'Kemampuan komunikasi efektif', 'bobot' => 2, 'deskripsi' => 'Komunikasi selama tindakan'],
                    ['nama_kriteria' => 'Mempertimbangkan kondisi pasien / profesionalisme', 'bobot' => 2, 'deskripsi' => 'Empati dan privasi pasien'],
                    ['nama_kriteria' => 'Kemampuan secara umum dalam menjalankan prosedur', 'bobot' => 3, 'deskripsi' => 'Evaluasi kelancaran dan kepercayaan diri'],
                ],
            ],

            'mini_cex' => [
                'judul'           => 'PENILAIAN MINI -CEX (MINI CLINICAL EVALUATION EXERCISE)',
                'kategori'        => 'klinik',
                'jenis_rubrik'    => 'clinical',
                'rumus_penilaian' => 'score_times_weight',
                'skala_max'       => 3,
                'deskripsi'       => 'Evaluasi interaksi klinis langsung dosen/preseptor dengan mahasiswa pada pelayanan kebidanan nyata (Rating Scale 1-3, Max Score x Bobot = 73).',
                'items' => [
                    [
                        'nama_kriteria' => '1. Keterampilan komunikasi terapeutik',
                        'bobot'         => 5,
                        'deskripsi'     => 'Komunikasi terapeutik, anamnesis, dan empati.',
                        'sub_items'     => [
                            [
                                'nama_kriteria' => 'a. Riwayat kesehatan',
                                'bobot'         => 2,
                                'deskripsi'     => 'Anamnesa riwayat kesehatan ibu dan keluarga',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Tidak menggali riwayat kesehatan'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Menggali riwayat kesehatan, namun tidak lengkap'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Memfasilitasi dengan baik dan lengkap: Riwayat kesehatan ibu & Riwayat kesehatan'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'b. Kemampuan menyampaikan pertanyaan',
                                'bobot'         => 2,
                                'deskripsi'     => 'Kejelasan dan efektivitas pertanyaan',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Pertanyaan tidak jelas, tidak ada informasi apapun'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Pertanyaan jelas, tapi tidak efektif, informasi penting tidak dapat didapatkan'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Pertanyaan yang diberikan jelas, efektif dan memberikan informasi yang akurat'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'c. Respon terhadap klien',
                                'bobot'         => 1,
                                'deskripsi'     => 'Kepekaan terhadap reaksi klien',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta tidak merespon terhadap reaksi klien'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta memberikan respon terhadap reaksi verbal klien'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta memberikan respon terhadap reaksi verbal dan non verbal'],
                                ]
                            ],
                        ]
                    ],
                    [
                        'nama_kriteria' => '2. Keterampilan pemeriksaan fisik',
                        'bobot'         => 6,
                        'deskripsi'     => 'Pemeriksaan fisik kebidanan',
                        'sub_items'     => [
                            [
                                'nama_kriteria' => 'a. Efisiensi dan kelogisan',
                                'bobot'         => 3,
                                'deskripsi'     => 'Sistematis, lengkap sesuai daftar tilik, sesuai waktu disepakati',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta melaksanakan pemeriksaan fisik 1 dari 3 aspek (sistematis/lengkap/tepat waktu)'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta melaksanakan pemeriksaan fisik 2 dari 3 aspek'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta melaksanakan pemeriksaan fisik secara lengkap (3 dari 3)'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'b. Kesesuaian pemeriksaan dengan masalah kesehatan yang ada',
                                'bobot'         => 1,
                                'deskripsi'     => 'Kesesuaian data fokus',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta tidak melaksanakan pemeriksaan yang sesuai'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta melaksanakan pemeriksaan fisik secara rutinitas'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta melaksanakan sesuai data fokus asuhan'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'c. Kemampuan memberikan penjelasan kepada pasien',
                                'bobot'         => 2,
                                'deskripsi'     => 'Penjelasan tujuan/manfaat dan kenyamanan',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Saat proses pemeriksaan, peserta tidak menjelaskan tujuan/manfaat pemeriksaan'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Saat proses pemeriksaan, peserta menjelaskan tujuan/manfaat pemeriksaan yang dilakukan dengan sopan'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Saat proses pemeriksaan, peserta menjelaskan tujuan/manfaat pemeriksaan yang dilakukan dengan sopan dan memperhatikan privacy klien'],
                                ]
                            ],
                        ]
                    ],
                    [
                        'nama_kriteria' => '3. Profesionalisme / kualitas kemanusiaan',
                        'bobot'         => 2,
                        'deskripsi'     => 'Melakukan tindakan profesionalisme (hormat, empati, kerahasiaan, etika)',
                        'levels'        => [
                            ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta menunjukkan minimal 3 dari 8 sikap profesionalisme'],
                            ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta menunjukkan minimal 5 dari 8 sikap profesionalisme'],
                            ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta menunjukkan minimal 7 dari 8 sikap profesionalisme'],
                        ]
                    ],
                    [
                        'nama_kriteria' => '4. Putusan klinis / analisis',
                        'bobot'         => 3,
                        'deskripsi'     => 'Penegakan diagnosis dan perencanaan asuhan',
                        'sub_items'     => [
                            [
                                'nama_kriteria' => 'a. Diagnosis',
                                'bobot'         => 2,
                                'deskripsi'     => 'Ketepatan penegakan diagnosis kebidanan',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta mampu membuat diagnosis tidak tepat'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta mampu membuat diagnosis namun kurang lengkap'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta mampu membuat diagnosis secara tepat dan lengkap'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'b. Perencanaan, rujukan, pertimbangan resiko',
                                'bobot'         => 1,
                                'deskripsi'     => 'Penyusunan rencana tindakan kebidanan',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta tidak melakukan perencanaan asuhan'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta tidak melakukan rencana yang baik'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta mampu menyusun rencana sesuai kebutuhan pasien, melakukan rujukan (bila perlu), dan mempertimbangkan keuntungan dan resiko'],
                                ]
                            ],
                        ]
                    ],
                    [
                        'nama_kriteria' => '5. Kemampuan memberikan konsultasi',
                        'bobot'         => 2,
                        'deskripsi'     => 'Memfasilitasi pasien menceritakan kesehatan',
                        'levels'        => [
                            ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta memberikan konsultasi dengan (1 dari 3 atau tidak memberikan konsultasi)'],
                            ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta memberikan konsultasi (2 dari 3)'],
                            ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta memberikan konsultasi dengan lengkap'],
                        ]
                    ],
                    [
                        'nama_kriteria' => '6. Organisasi / efisiensi',
                        'bobot'         => 4,
                        'deskripsi'     => 'Struktur, keruntutan, keefektifan waktu, alat, dan tempat',
                        'sub_items'     => [
                            [
                                'nama_kriteria' => 'a. Struktur dan keruntutan tindakan',
                                'bobot'         => 1,
                                'deskripsi'     => 'Sistematis dan terstruktur',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta melakukan seluruh asuhan secara tidak sistematis dan terstruktur'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta melakukan seluruh asuhan secara tidak sistematis dan terstruktur, namun disertai argumen yang tepat'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta melakukan seluruh asuhan secara sistematis dan terstruktur'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'b. Keefektifan waktu / tindakan',
                                'bobot'         => 1,
                                'deskripsi'     => 'Penggunaan waktu asuhan',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta memberikan asuhan terlambat/tidak efektif'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta memberikan asuhan tanpa memperhatikan keefektifan waktu (terlalu lambat)'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta melakukan asuhan sesuai dengan kontrak waktu'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'c. Keefektifan alat',
                                'bobot'         => 1,
                                'deskripsi'     => 'Penataan dan penggunaan alat',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Alat tidak disiapkan dan tidak diletakkan efisien'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Alat di set dan diletakkan secara tidak efisien'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Alat di set dan diletakkan secara efisien'],
                                ]
                            ],
                            [
                                'nama_kriteria' => 'd. Keefektifan tempat',
                                'bobot'         => 1,
                                'deskripsi'     => 'Kesiapan tempat dan privasi pasien',
                                'levels'        => [
                                    ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Tempat tidak disiapkan untuk privacy klien dan tidak efisien'],
                                    ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Tempat disiapkan dengan memperhatikan privacy klien atau efisien (salah satu)'],
                                    ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Tempat disiapkan dengan memperhatikan privacy klien dan efisien'],
                                ]
                            ],
                        ]
                    ],
                    [
                        'nama_kriteria' => '7. Kompetensi asuhan kebidanan secara umum',
                        'bobot'         => 1,
                        'deskripsi'     => 'Kemampuan mendemonstrasikan putusan klinis yang memuaskan',
                        'levels'        => [
                            ['skor' => 1, 'label' => 'Rating 1', 'deskripsi' => 'Peserta mendemonstrasikan < 4 dari 7 indikator putusan klinis'],
                            ['skor' => 2, 'label' => 'Rating 2', 'deskripsi' => 'Peserta mendemonstrasikan 4-5 dari 7 indikator putusan klinis'],
                            ['skor' => 3, 'label' => 'Rating 3', 'deskripsi' => 'Peserta mendemonstrasikan minimal 6 dari 7 indikator putusan klinis'],
                        ]
                    ],
                ]
            ],

            'cbd' => [
                'judul'           => 'Penilaian Case Based Discussion (CBD)',
                'kategori'        => 'klinik',
                'jenis_rubrik'    => 'clinical',
                'rumus_penilaian' => 'weighted_percentage',
                'skala_max'       => 4.5,
                'deskripsi'       => 'Diskusi kasus klinis mendalam untuk menguji penalaran klinis (clinical reasoning). Skala: 2.5 (Di Bawah Harapan), 3.0 (Borderline), 3.5 (Sesuai Harapan), 4.0 (Di Atas Harapan), 4.5 (Superior). Max = 31.5.',
                'items' => [
                    [
                        'nama_kriteria' => 'Penulisan SOAP notes & kronologis kasus',
                        'bobot'         => 14.28,
                        'deskripsi'     => 'Data fokus, kronologis kasus gamblang & runut, interaksi dan evaluasi terukur.',
                        'levels'        => [
                            ['skor' => 4.5, 'label' => 'Superior (4.5)', 'deskripsi' => 'Tulisan sangat jelas, data fokus ditulis lengkap & detail, rapi, kronologis sangat gamblang dan runut.'],
                            ['skor' => 4.0, 'label' => 'Di Atas Harapan (4.0)', 'deskripsi' => 'Tulisan sangat jelas, data fokus lengkap, kronologis kasus gamblang, interaksi terukur.'],
                            ['skor' => 3.5, 'label' => 'Sesuai Harapan (3.5)', 'deskripsi' => 'Tulisan jelas, data fokus ditulis lengkap, interaksi dan evaluasi ditulis lengkap.'],
                            ['skor' => 3.0, 'label' => 'Borderline (3.0)', 'deskripsi' => 'Tulisan kurang jelas, data fokus tidak lengkap, interaksi ditulis kurang lengkap.'],
                            ['skor' => 2.5, 'label' => 'Di Bawah Harapan (2.5)', 'deskripsi' => 'Tulisan tidak jelas, data fokus tidak ditulis, interaksi tidak lengkap.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Penegakan diagnosis & analisis masalah potensial',
                        'bobot'         => 14.28,
                        'deskripsi'     => 'Diagnosis jelas & lengkap, analisis diagnosis potensial/masalah aktual secara tepat.',
                        'levels'        => [
                            ['skor' => 4.5, 'label' => 'Superior (4.5)', 'deskripsi' => 'Diagnosis jelas & lengkap, menganalisis diagnosis potensial tepat dan detail beserta pathway.'],
                            ['skor' => 4.0, 'label' => 'Di Atas Harapan (4.0)', 'deskripsi' => 'Diagnosis jelas & lengkap, dapat menganalisis diagnosis potensial secara cepat dan tepat.'],
                            ['skor' => 3.5, 'label' => 'Sesuai Harapan (3.5)', 'deskripsi' => 'Diagnosis jelas & lengkap, menganalisis diagnosis potensial tanpa bantuan.'],
                            ['skor' => 3.0, 'label' => 'Borderline (3.0)', 'deskripsi' => 'Diagnosis cukup jelas, kurang lengkap, butuh bantuan menganalisis.'],
                            ['skor' => 2.5, 'label' => 'Di Bawah Harapan (2.5)', 'deskripsi' => 'Diagnosis kurang jelas, tidak dapat menganalisis diagnosis potensial.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Pemeriksaan penunjang',
                        'bobot'         => 14.28,
                        'deskripsi'     => 'Mengidentifikasi kebutuhan pemeriksaan laboratorium / uji diagnostik.',
                        'levels'        => [
                            ['skor' => 4.5, 'label' => 'Superior (4.5)', 'deskripsi' => 'Mengidentifikasi kebutuhan pemeriksaan penunjang dan menjelaskan rasionalisasinya secara detail.'],
                            ['skor' => 4.0, 'label' => 'Di Atas Harapan (4.0)', 'deskripsi' => 'Mengidentifikasi kebutuhan pemeriksaan penunjang dan menjelaskan rasionalisasinya.'],
                            ['skor' => 3.5, 'label' => 'Sesuai Harapan (3.5)', 'deskripsi' => 'Mengidentifikasi kebutuhan pemeriksaan penunjang sesuai kondisi.'],
                            ['skor' => 3.0, 'label' => 'Borderline (3.0)', 'deskripsi' => 'Mengidentifikasi kebutuhan penunjang dengan bantuan.'],
                            ['skor' => 2.5, 'label' => 'Di Bawah Harapan (2.5)', 'deskripsi' => 'Tidak dapat mengidentifikasi kebutuhan pemeriksaan penunjang.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Penatalaksanaan asuhan kebidanan & rasionalisasi',
                        'bobot'         => 14.28,
                        'deskripsi'     => 'Asuhan menyeluruh, tindakan kolaboratif/segera, dan penjelasan rasionalisasi basic/clinical science.',
                        'levels'        => [
                            ['skor' => 4.5, 'label' => 'Superior (4.5)', 'deskripsi' => 'Asuhan sesuai kebutuhan & menyeluruh, rasionalisasi detail berbasis basic/clinical science.'],
                            ['skor' => 4.0, 'label' => 'Di Atas Harapan (4.0)', 'deskripsi' => 'Asuhan sesuai kebutuhan & menyeluruh, menjelaskan rasionalisasi berbasis science.'],
                            ['skor' => 3.5, 'label' => 'Sesuai Harapan (3.5)', 'deskripsi' => 'Asuhan sesuai kebutuhan & menyeluruh, rasionalisasi sebagian.'],
                            ['skor' => 3.0, 'label' => 'Borderline (3.0)', 'deskripsi' => 'Asuhan sesuai kebutuhan namun tidak menyeluruh.'],
                            ['skor' => 2.5, 'label' => 'Di Bawah Harapan (2.5)', 'deskripsi' => 'Asuhan tidak sesuai kebutuhan dan tidak menyeluruh.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Follow up / rencana tindak lanjut',
                        'bobot'         => 14.28,
                        'deskripsi'     => 'Rencana tindakan/evaluasi lanjutan sesuai kondisi pasien.',
                        'levels'        => [
                            ['skor' => 4.5, 'label' => 'Superior (4.5)', 'deskripsi' => 'Ada rencana tindakan/evaluasi lanjutan lengkap dengan rasionalisasi detail.'],
                            ['skor' => 4.0, 'label' => 'Di Atas Harapan (4.0)', 'deskripsi' => 'Ada rencana tindakan/evaluasi lanjutan dengan rasionalisasi semua.'],
                            ['skor' => 3.5, 'label' => 'Sesuai Harapan (3.5)', 'deskripsi' => 'Ada rencana lanjutan dengan rasionalisasi sebagian.'],
                            ['skor' => 3.0, 'label' => 'Borderline (3.0)', 'deskripsi' => 'Ada rencana lanjutan tapi kurang menyeluruh.'],
                            ['skor' => 2.5, 'label' => 'Di Bawah Harapan (2.5)', 'deskripsi' => 'Tidak ada rencana tindakan / evaluasi lanjutan.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Profesionalisme & antusiasme diskusi',
                        'bobot'         => 14.28,
                        'deskripsi'     => 'Ketelitian laporan, keaktifan diskusi, dan efektivitas waktu.',
                        'levels'        => [
                            ['skor' => 4.5, 'label' => 'Superior (4.5)', 'deskripsi' => 'Sangat teliti, sangat cermat/kritis, keaktifan tinggi, efektivitas waktu sangat baik.'],
                            ['skor' => 4.0, 'label' => 'Di Atas Harapan (4.0)', 'deskripsi' => 'Teliti, cermat/kritis, aktif dalam diskusi kasus.'],
                            ['skor' => 3.5, 'label' => 'Sesuai Harapan (3.5)', 'deskripsi' => 'Cukup teliti, cukup cermat, ada keaktifan dalam diskusi.'],
                            ['skor' => 3.0, 'label' => 'Borderline (3.0)', 'deskripsi' => 'Kurang teliti, kurang cermat dalam analisis.'],
                            ['skor' => 2.5, 'label' => 'Di Bawah Harapan (2.5)', 'deskripsi' => 'Sangat kurang teliti, tidak menunjukkan keaktifan.'],
                        ]
                    ],
                    [
                        'nama_kriteria' => 'Kemampuan klinis secara keseluruhan (overall clinical reasoning)',
                        'bobot'         => 14.32,
                        'deskripsi'     => 'Sintesis SOAP, penalaran klinis, dan pemahaman basic/clinical science mendasari kasus.',
                        'levels'        => [
                            ['skor' => 4.5, 'label' => 'Superior (4.5)', 'deskripsi' => 'Penulisan SOAP & penalaran klinis sangat baik, sangat menguasai ilmu dasar mendasari kasus.'],
                            ['skor' => 4.0, 'label' => 'Di Atas Harapan (4.0)', 'deskripsi' => 'Penulisan SOAP & penalaran klinis baik, pemahaman ilmu dasar baik.'],
                            ['skor' => 3.5, 'label' => 'Sesuai Harapan (3.5)', 'deskripsi' => 'Penulisan SOAP & penalaran klinis cukup.'],
                            ['skor' => 3.0, 'label' => 'Borderline (3.0)', 'deskripsi' => 'Penulisan SOAP kurang, penalaran klinis mulai terlihat.'],
                            ['skor' => 2.5, 'label' => 'Di Bawah Harapan (2.5)', 'deskripsi' => 'Penulisan SOAP kurang, penalaran klinis sangat kurang.'],
                        ]
                    ],
                ],
            ],

            'penilaian_sikap' => [
                'judul'           => 'Penilaian Sikap Mahasiswa Praktik Klinik (Group Scoring)',
                'kategori'        => 'klinik',
                'jenis_rubrik'    => 'group_matrix',
                'rumus_penilaian' => 'raw_sum',
                'skala_max'       => 4,
                'deskripsi'       => 'Penilaian kelompok/rombongan mahasiswa (1-7 mahasiswa) mencakup 5 domain sikap: Jujur, Menghargai, Bertanggung Jawab, Taat Aturan, dan Profesional. Max Skor = 108.',
                'items' => [
                    ['nama_kriteria' => 'A. Sikap Jujur (4 Indikator - Max 16)', 'bobot' => 16, 'deskripsi' => 'Jujur, mandiri dalam tugas, melaporkan data sesuai fakta, menghindari plagiasi.'],
                    ['nama_kriteria' => 'B. Sikap Menghargai Klien (5 Indikator - Max 20)', 'bobot' => 20, 'deskripsi' => 'Pelayanan tanpa membedakan suku/ras, menjaga kerahasiaan & kenyamanan pasien.'],
                    ['nama_kriteria' => 'C. Sikap Bertanggung Jawab (5 Indikator - Max 20)', 'bobot' => 20, 'deskripsi' => 'Melaksanakan tugas orientasi mutu, tepat waktu, cepat tanggap, kerja sama tim.'],
                    ['nama_kriteria' => 'D. Sikap Taat Aturan/Hukum (6 Indikator - Max 24)', 'bobot' => 24, 'deskripsi' => 'Mematuhi tata tertib, mengenakan seragam/atribut, datang-pulang tepat waktu.'],
                    ['nama_kriteria' => 'E. Sikap Profesional (7 Indikator - Max 28)', 'bobot' => 28, 'deskripsi' => 'Bersikap 3S, bahasa santun, komunikasi efektif, informed consent, melibatkan keluarga.'],
                ],
            ],

            'rdk' => [
                'judul'           => 'Penilaian Refleksi Diskusi Kasus (RDK)',
                'kategori'        => 'klinik',
                'jenis_rubrik'    => 'checklist',
                'rumus_penilaian' => 'score_times_weight',
                'skala_max'       => 1,
                'deskripsi'       => 'Checklist observasi pelaksanaan RDK (Fasilitator, Penyaji, Peserta, Laporan). Ya (1) / Tidak (0) × Bobot item. Total Max = 50.',
                'items' => [
                    ['nama_kriteria' => 'Fasilitator: Membuka pertemuan & menjelaskan tujuan RDK', 'bobot' => 3, 'deskripsi' => 'Membuka dan mengarahkan diskusi RDK'],
                    ['nama_kriteria' => 'Fasilitator: Mengatur lalu lintas pertanyaan & kesimpulan', 'bobot' => 4, 'deskripsi' => 'Memimpin jalannya tanya jawab dan penutupan'],
                    ['nama_kriteria' => 'Penyaji: Menyiapkan materi & menjelaskan asuhan kebidanan', 'bobot' => 3, 'deskripsi' => 'Persiapan kasus dan penjelasan ilmiah'],
                    ['nama_kriteria' => 'Penyaji: Menganalisis pemaknaan & merumuskan rencana perbaikan', 'bobot' => 9, 'deskripsi' => 'Analisis kritis kasus RDK'],
                    ['nama_kriteria' => 'Peserta: Mengikuti aktif, bertanya & tidak mendominasi', 'bobot' => 9, 'deskripsi' => 'Keaktifan dan partisipasi peserta RDK'],
                    ['nama_kriteria' => 'Laporan RDK: Sistematika & ketepatan waktu penyerahan', 'bobot' => 5, 'deskripsi' => 'Kerapihan dokumentasi laporan RDK'],
                ],
            ],
        ];
    }
}


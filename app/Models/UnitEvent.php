<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UnitEvent extends Model
{
    protected $fillable = [
        'unit_page_id',
        'created_by',
        'judul',
        'slug',
        'ringkasan',
        'deskripsi',
        'jenis_kegiatan',
        'sub_jenis_kegiatan',
        'shifts',
        'metode',
        'status',
        'flyer_path',
        'surat_tugas_event_path',
        'surat_tugas_event_uploaded_at',
        'lokasi',
        'lokasi_praktik',
        'alamat_detail',
        'maps_embed',
        'zoom_link',
        'whatsapp_group_link',
        'narahubung_nama',
        'narahubung_whatsapp',
        'mulai_at',
        'selesai_at',
        'tahun_akademik',
        'tingkat',
        'semester',
        'attendance_print_start_time',
        'attendance_print_end_time',
        'kapasitas',
        'is_featured',
        'registrasi_aktif',
        'registrasi_mulai_at',
        'registrasi_selesai_at',
        'daftar_hadir_aktif',
        'allow_direct_attendance',
        'berita_acara_catatan',
        'berita_acara_judul',
        'berita_acara_ringkasan',
        'berita_acara_form_aktif',
        'penandatangan_jabatan',
        'penandatangan_nama',
        'penandatangan_nip',
        'penandatangan_signature_path',
        'koordinator_nama',
        'koordinator_nip',
        'direktur_nama',
        'direktur_foto_path',
        'flyer_theme',
        'flyer_tagline',
        'flyer_benefits',
        'pre_test_aktif',
        'post_test_aktif',
        'evaluasi_aktif',
        'rubrik_aktif',
        'sertifikat_peserta_aktif',
        'sertifikat_narasumber_aktif',
        'sertifikat_panitia_aktif',
        'published_at',
    ];

    public static array $flyerThemeLabels = [
        'modern_teal' => 'Signature Polkesban Teal',
        'navy_gold' => 'Royal Navy & Gold Prestige',
        'emerald_health' => 'Emerald Health & Clinical',
        'crimson_maroon' => 'Crimson Maroon & Rose',
        'dark_luxury' => 'Dark Luxury & Cyan Glow',
    ];

    public static array $jenisLabels = [
        'pbm' => 'PBM',
        'rapat' => 'Rapat',
        'webinar' => 'Webinar',
        'seminar' => 'Seminar',
        'workshop' => 'Workshop',
        'pelatihan' => 'Pelatihan',
        'sosialisasi' => 'Sosialisasi',
        'kerja_lembur' => 'Kerja Lembur',
        'wisuda' => 'Wisuda',
        'lainnya' => 'Lainnya',
    ];

    public static array $metodeLabels = [
        'online' => 'Daring / Online',
        'offline' => 'Luring / Offline',
        'hybrid' => 'Hybrid',
    ];

    public static array $statusLabels = [
        'draft' => 'Draft',
        'published' => 'Dipublikasikan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public static array $faseLabels = [
        'pre_test' => 'Pre Test',
        'post_test' => 'Post Test',
        'evaluation' => 'Evaluasi',
    ];

    protected function casts(): array
    {
        return [
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
            'registrasi_mulai_at' => 'datetime',
            'registrasi_selesai_at' => 'datetime',
            'published_at' => 'datetime',
            'surat_tugas_event_uploaded_at' => 'datetime',
            'is_featured' => 'boolean',
            'registrasi_aktif' => 'boolean',
            'daftar_hadir_aktif' => 'boolean',
            'allow_direct_attendance' => 'boolean',
            'berita_acara_form_aktif' => 'boolean',
            'pre_test_aktif' => 'boolean',
            'post_test_aktif' => 'boolean',
            'evaluasi_aktif' => 'boolean',
            'rubrik_aktif' => 'boolean',
            'sertifikat_peserta_aktif' => 'boolean',
            'sertifikat_narasumber_aktif' => 'boolean',
            'sertifikat_panitia_aktif' => 'boolean',
            'shifts' => 'array',
            'lokasi_praktik' => 'array',
        ];
    }

    public function page()
    {
        return $this->belongsTo(UnitPage::class, 'unit_page_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function materials()
    {
        return $this->hasMany(UnitEventMaterial::class)->orderBy('urutan')->orderBy('id');
    }

    public function questions()
    {
        return $this->hasMany(UnitEventQuestion::class)->orderBy('fase')->orderBy('urutan')->orderBy('id');
    }

    public function participants()
    {
        return $this->hasMany(UnitEventParticipant::class)->orderBy('peran')->orderBy('nama');
    }

    public function attendances()
    {
        return $this->hasMany(UnitEventAttendance::class)->orderBy('attendance_date')->orderBy('signed_at');
    }

    public function documentation()
    {
        return $this->hasOne(UnitEventDocumentation::class, 'unit_event_id');
    }

    public function photos()
    {
        return $this->hasMany(UnitEventPhoto::class)
            ->orderByDesc('is_cover')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function submissions()
    {
        return $this->hasMany(UnitEventSubmission::class);
    }

    public function aiDocuments()
    {
        return $this->hasMany(UnitEventAiDocument::class, 'unit_event_id')->orderBy('id', 'desc');
    }

    public function evaluationRubrics()
    {
        return $this->hasMany(EvaluationRubric::class, 'unit_event_id')->orderBy('id', 'desc');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', ['published', 'completed']);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function publicPath(): string
    {
        return $this->page->publicPath() . '/event/' . trim((string) $this->slug, '/');
    }

    public function publicUrl(): string
    {
        return url($this->publicPath());
    }

    public function isRegistrationOpen(): bool
    {
        if (! $this->registrasi_aktif || ! in_array($this->status, ['published', 'completed'], true)) {
            return false;
        }

        if ($this->registrasi_mulai_at && now()->lt($this->registrasi_mulai_at)) {
            return false;
        }

        if ($this->registrasi_selesai_at && now()->gt($this->registrasi_selesai_at)) {
            return false;
        }

        if ($this->kapasitas && $this->participants()->where('peran', 'participant')->count() >= $this->kapasitas) {
            return false;
        }

        return true;
    }

    public function hasManagedParticipants(): bool
    {
        return $this->participants()
            ->where('peran', 'participant')
            ->whereIn('sumber_registrasi', ['admin', 'admin_import'])
            ->exists();
    }

    public function shouldBypassPublicRegistration(): bool
    {
        return $this->hasManagedParticipants();
    }

    public function allowsPublicRegistration(): bool
    {
        return ! $this->shouldBypassPublicRegistration() && $this->isRegistrationOpen();
    }

    public function hasQuestionsFor(string $fase): bool
    {
        return $this->questions()->where('fase', $fase)->exists();
    }

    public function questionsFor(string $fase)
    {
        return $this->questions()->where('fase', $fase)->orderBy('urutan')->orderBy('id');
    }

    public function attendanceDates(): Collection
    {
        if (! $this->mulai_at) {
            return collect();
        }

        $start = $this->mulai_at->copy()->startOfDay();
        $end = ($this->selesai_at ? $this->selesai_at->copy() : $this->mulai_at->copy())->startOfDay();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $dates = collect();

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $dates->push($cursor->copy());
        }

        return $dates;
    }

    public function attendanceDateStrings(): array
    {
        return $this->attendanceDates()
            ->map(fn (Carbon $date) => $date->toDateString())
            ->all();
    }

    public function attendanceDayCount(): int
    {
        return max(1, $this->attendanceDates()->count());
    }

    public function speakers()
    {
        return $this->participants()->where('peran', 'speaker')->orderBy('nama');
    }

    public function resolvedDirectorName(): string
    {
        return filled($this->direktur_nama)
            ? (string) $this->direktur_nama
            : (filled($this->penandatangan_nama) ? (string) $this->penandatangan_nama : 'Dr. Pujiono, SKM, M.Kes');
    }

    public function resolvedDirectorTitle(): string
    {
        return filled($this->penandatangan_jabatan)
            ? (string) $this->penandatangan_jabatan
            : 'Direktur Poltekkes Kemenkes Bandung';
    }

    public function resolvedLocationName(): string
    {
        if (filled($this->lokasi)) {
            return (string) $this->lokasi;
        }

        if ($this->metode === 'online') {
            return 'Zoom Cloud Meetings / Daring Online';
        }

        if ($this->metode === 'hybrid') {
            return 'Hybrid (Kampus Poltekkes Bandung & Online)';
        }

        if (filled($this->alamat_detail)) {
            return (string) $this->alamat_detail;
        }

        return 'Kampus Poltekkes Kemenkes Bandung';
    }

    public function resolvedDirectorPhotoUrl(): ?string
    {
        if (filled($this->direktur_foto_path)) {
            return \App\Support\MediaStorage::url($this->direktur_foto_path);
        }

        if (file_exists(public_path('images/direktur.png'))) {
            return asset('images/direktur.png');
        }

        if (file_exists(public_path('images/direktur-default.png'))) {
            return asset('images/direktur-default.png');
        }

        return null;
    }

    public function resolvedFlyerThemeConfig(): array
    {
        $theme = $this->flyer_theme ?: 'modern_teal';

        $themes = [
            'modern_teal' => [
                'name' => 'Signature Polkesban Teal',
                'bg_gradient' => 'from-slate-950 via-teal-950 to-slate-900',
                'header_badge' => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
                'accent_color' => '#0d9488',
                'accent_text' => 'text-teal-400',
                'accent_bg' => 'bg-teal-600',
                'accent_border' => 'border-teal-500/30',
                'card_bg' => 'bg-white/10 border-white/15',
                'pill_bg' => 'bg-teal-500/20 text-teal-200 border-teal-500/30',
                'gold_accent' => 'text-amber-400',
                'director_border' => 'border-amber-400/80',
                'glow_color' => 'rgba(13, 148, 136, 0.25)',
            ],
            'navy_gold' => [
                'name' => 'Royal Navy & Gold Prestige',
                'bg_gradient' => 'from-slate-950 via-blue-950 to-indigo-950',
                'header_badge' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                'accent_color' => '#2563eb',
                'accent_text' => 'text-amber-400',
                'accent_bg' => 'bg-blue-600',
                'accent_border' => 'border-amber-500/30',
                'card_bg' => 'bg-white/10 border-white/15',
                'pill_bg' => 'bg-blue-500/20 text-blue-200 border-blue-500/30',
                'gold_accent' => 'text-amber-300',
                'director_border' => 'border-amber-400',
                'glow_color' => 'rgba(37, 99, 235, 0.25)',
            ],
            'emerald_health' => [
                'name' => 'Emerald Health & Clinical',
                'bg_gradient' => 'from-slate-950 via-emerald-950 to-slate-900',
                'header_badge' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                'accent_color' => '#059669',
                'accent_text' => 'text-emerald-400',
                'accent_bg' => 'bg-emerald-600',
                'accent_border' => 'border-emerald-500/30',
                'card_bg' => 'bg-white/10 border-white/15',
                'pill_bg' => 'bg-emerald-500/20 text-emerald-200 border-emerald-500/30',
                'gold_accent' => 'text-emerald-300',
                'director_border' => 'border-emerald-400',
                'glow_color' => 'rgba(5, 150, 105, 0.25)',
            ],
            'crimson_maroon' => [
                'name' => 'Crimson Maroon & Rose',
                'bg_gradient' => 'from-slate-950 via-rose-950 to-slate-900',
                'header_badge' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                'accent_color' => '#e11d48',
                'accent_text' => 'text-rose-400',
                'accent_bg' => 'bg-rose-600',
                'accent_border' => 'border-rose-500/30',
                'card_bg' => 'bg-white/10 border-white/15',
                'pill_bg' => 'bg-rose-500/20 text-rose-200 border-rose-500/30',
                'gold_accent' => 'text-amber-300',
                'director_border' => 'border-rose-400',
                'glow_color' => 'rgba(225, 29, 72, 0.25)',
            ],
            'dark_luxury' => [
                'name' => 'Dark Luxury & Cyan Glow',
                'bg_gradient' => 'from-black via-slate-900 to-black',
                'header_badge' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
                'accent_color' => '#06b6d4',
                'accent_text' => 'text-cyan-400',
                'accent_bg' => 'bg-cyan-600',
                'accent_border' => 'border-cyan-500/30',
                'card_bg' => 'bg-white/10 border-white/15',
                'pill_bg' => 'bg-cyan-500/20 text-cyan-200 border-cyan-500/30',
                'gold_accent' => 'text-cyan-300',
                'director_border' => 'border-cyan-400',
                'glow_color' => 'rgba(6, 182, 212, 0.25)',
            ],
        ];

        return $themes[$theme] ?? $themes['modern_teal'];
    }

    public function resolvedBenefitsList(): array
    {
        if (blank($this->flyer_benefits)) {
            return [
                'E-Sertifikat Resmi Ber-SKP Kemenkes RI',
                'Materi Lengkap Narasumber & Pembicara',
                'Wawasan & Diskusi Interaktif Bersama Pakar',
                'E-Kredensial & Akses Portal Peserta',
            ];
        }

        if (is_array($this->flyer_benefits)) {
            return array_values(array_filter(array_map('trim', $this->flyer_benefits)));
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $this->flyer_benefits);
        $items = array_values(array_filter(array_map('trim', $lines)));

        return !empty($items) ? $items : [
            'E-Sertifikat Resmi Ber-SKP Kemenkes RI',
            'Materi Lengkap Narasumber & Pembicara',
            'Wawasan & Diskusi Interaktif Bersama Pakar',
            'E-Kredensial & Akses Portal Peserta',
        ];
    }

    public function resolvedTargetPesertaList(): array
    {
        $deskripsi = (string) $this->deskripsi;
        $items = [];

        if (preg_match('/Peserta\s*(?:Luring|Daring|Kegiatan)?\s*[:\-](.+?)(?=(?:Keikutsertaan|Demikian|Waktu|Hari|\n\n\n|$))/is', $deskripsi, $matches)) {
            $raw = trim($matches[1]);
            $lines = preg_split('/\r\n|\r|\n/', $raw);
            $currentItem = null;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || preg_match('/^\d+\s*Mahasiswa/i', $trimmed)) {
                    continue;
                }

                if (preg_match('/^[-•*]\s*(.+)$/', $trimmed, $m)) {
                    if ($currentItem !== null) {
                        $items[] = $currentItem;
                    }
                    $currentItem = trim($m[1]);
                } elseif ($currentItem !== null) {
                    $currentItem .= ' ' . $trimmed;
                }
            }

            if ($currentItem !== null) {
                $items[] = $currentItem;
            }
        }

        return $items;
    }

    public function resolvedFlyerImageUrl(): string
    {
        if ($this->page && $this->slug) {
            return route('unit.events.flyer.og-image', [$this->page->slug, $this->slug]);
        }

        if (filled($this->flyer_path)) {
            $url = \App\Support\MediaStorage::publicUrl($this->flyer_path);
            if ($url) {
                return str_starts_with($url, 'http') ? $url : url($url);
            }
        }

        return asset('images/logo.png');
    }
}
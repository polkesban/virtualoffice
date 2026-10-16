<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    public const HIDDEN_BUILDER_TYPES = [
        'agenda',
        'pelatihan',
        'publikasi',
        'dokumen',
        'koleksi',
        'fasilitas',
        'layanan',
    ];

    protected $table = 'page_sections';

    protected $fillable = [
        'unit_page_id', 'halaman_statis_id', 'tipe', 'judul', 'konfigurasi', 'urutan', 'aktif',
    ];

    protected $casts = [
        'konfigurasi' => 'array',
        'aktif'       => 'boolean',
        'urutan'      => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(UnitPage::class, 'unit_page_id');
    }

    public function halamanStatis()
    {
        return $this->belongsTo(HalamanStatis::class, 'halaman_statis_id');
    }

    public function submissions()
    {
        return $this->hasMany(PageSectionSubmission::class, 'section_id')->latest();
    }

    public function votes()
    {
        return $this->hasMany(PageSectionVote::class, 'section_id')->latest();
    }

    /** Returns the scope condition for siblings (same parent) */
    public function siblingQuery()
    {
        if ($this->unit_page_id) {
            return static::where('unit_page_id', $this->unit_page_id);
        }
        return static::where('halaman_statis_id', $this->halaman_statis_id);
    }

    // All available block types with labels and icons
    public static array $types = [
        'hero'          => ['ikon' => '🖼️',  'label' => 'Hero / Sampul Halaman'],
        'teks'          => ['ikon' => '📝',  'label' => 'Teks / Konten HTML'],
        'ai_teks'       => ['ikon' => '🤖',  'label' => 'AI Content Engine'],
        'statistik'     => ['ikon' => '📊',  'label' => 'Statistik & Angka'],
        'tabel'         => ['ikon' => '📋',  'label' => 'Tabel Data'],
        'galeri_foto'   => ['ikon' => '🖼',  'label' => 'Galeri Foto'],
        'produk'        => ['ikon' => '🛒',  'label' => 'Produk & Varian'],
        'kuis'          => ['ikon' => '🧩',  'label' => 'Kuis / Self-Assessment'],
        'polling'       => ['ikon' => '📊',  'label' => 'Polling / Survei'],
        'formulir'      => ['ikon' => '📬',  'label' => 'Formulir / Kontak'],
        'event_unit'    => ['ikon' => '🎫',  'label' => 'Event Unit & Registrasi'],
        'agenda'        => ['ikon' => '📅',  'label' => 'Agenda & Kalender'],
        'pelatihan'     => ['ikon' => '🎓',  'label' => 'Program Pelatihan'],
        'publikasi'     => ['ikon' => '📄',  'label' => 'Publikasi & Penelitian'],
        'dokumen'       => ['ikon' => '📋',  'label' => 'Dokumen Mutu'],
        'koleksi'       => ['ikon' => '📚',  'label' => 'Koleksi Perpustakaan'],
        'fasilitas'     => ['ikon' => '🛏️', 'label' => 'Fasilitas Asrama'],
        'layanan'       => ['ikon' => '⚙️', 'label' => 'Katalog Layanan'],
        'unduhan'       => ['ikon' => '⬇️', 'label' => 'File & Unduhan'],
        'google_drive'  => ['ikon' => '🗂️', 'label' => 'Google Drive Viewer'],
        'kontak'        => ['ikon' => '📍',  'label' => 'Informasi Kontak'],
        'embed'         => ['ikon' => '🔗',  'label' => 'Embed (YouTube / Maps)'],
        'tim'           => ['ikon' => '👥',  'label' => 'Tim & Personil'],
    ];

    // Default config values per type
    public static function defaultConfig(string $tipe): array
    {
        return match ($tipe) {
            'hero'          => ['heading' => '', 'sub' => '', 'bg_color' => '#1d4ed8', 'bg_image' => '', 'overlay_opacity' => 60],
            'teks'          => ['konten' => ''],
            'ai_teks'       => ['konten' => '', 'gambar_url' => '', 'gambar_alt' => '', '_prompt_tempat' => '', '_prompt_acara' => '', '_prompt_tanggal' => '', '_prompt_catatan' => ''],
            'statistik'     => ['items' => [['nilai' => '0', 'label' => 'Label', 'ikon' => '📊', 'warna' => 'blue']]],
            'tabel'         => ['header' => ['Kolom 1', 'Kolom 2', 'Kolom 3'], 'baris' => [['', '', '']], 'bergaris' => true, 'dengan_header' => true],
            'galeri_foto'   => ['items' => [['url' => '', 'judul' => '', 'caption' => '']], 'kolom' => 3],
            'produk'        => ['items' => [[
                'nama' => '',
                'kategori' => 'produk',
                'deskripsi' => '',
                'harga' => '',
                'satuan_harga' => '',
                'gambar' => '',
                'link_pesan' => '',
                'opsi_varian' => [],
            ]]],
            'kuis'          => ['pertanyaan' => [['soal' => '', 'opsi' => ['Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D'], 'benar' => 0, 'penjelasan' => '']]],
            'polling'       => ['pertanyaan' => 'Pertanyaan polling?', 'opsi' => ['Opsi 1', 'Opsi 2', 'Opsi 3'], 'tampil_hasil' => true],
            'formulir'      => [
                'mode' => 'form',
                'pengantar' => '',
                'header_logo_url' => '',
                'header_logo_alt' => 'Logo formulir',
                'header_image_url' => '',
                'header_image_alt' => 'Header formulir',
                'pengantar_image_url' => '',
                'pengantar_image_alt' => 'Gambar pengantar formulir',
                'passing_score' => 70,
                'duration_minutes' => 0,
                'opens_at' => null,
                'closes_at' => null,
                'scheduled_message' => 'Formulir ini belum dibuka.',
                'closed_message' => 'Akses formulir saat ini ditutup.',
                'time_limit_message' => 'Waktu pengerjaan untuk formulir ini sudah habis.',
                'show_rationales_after_submit' => true,
                'access_scope' => 'public',
                'access_event_id' => null,
                'access_message' => '',
                'access_registration_code_label' => 'Kode registrasi event',
                'proctoring_enabled' => false,
                'proctoring_require_fullscreen' => false,
                'proctoring_block_copy_paste' => true,
                'proctoring_block_context_menu' => true,
                'proctoring_capture_selfie' => false,
                'proctoring_auto_submit' => false,
                'proctoring_max_violations' => 0,
                'proctoring_notice' => 'Mode proctoring aktif. Aktivitas seperti pindah tab, keluar fullscreen, dan percobaan copy atau paste akan dicatat.',
                'proctoring_selfie_label' => 'Ambil foto verifikasi peserta',
                'fields' => [
                    ['nama' => 'Nama Lengkap', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'Email', 'tipe' => 'email', 'wajib' => true],
                    ['nama' => 'Pesan', 'tipe' => 'textarea', 'wajib' => true],
                ],
                'email_tujuan' => '',
                'pesan_sukses' => 'Terima kasih, pesan Anda telah diterima.',
                'judul_submit' => 'Kirim',
                'response_limit' => 'none',
                'limit_message' => 'Formulir ini hanya dapat dikirim satu kali.',
            ],
            'event_unit'    => ['limit' => 3, 'tampilkan_lampau' => false, 'featured_only' => false],
            'agenda'        => ['limit' => 5, 'tampil_lampau' => false],
            'pelatihan'     => ['limit' => 6, 'jenis' => ''],
            'publikasi'     => ['limit' => 5, 'jenis' => ''],
            'dokumen'       => ['limit' => 8, 'jenis' => ''],
            'koleksi'       => ['limit' => 6, 'kategori' => ''],
            'fasilitas'     => ['jenis' => ''],
            'layanan'       => ['sumber' => 'ti', 'limit' => 10],
            'unduhan'       => ['items' => [['nama' => '', 'deskripsi' => '', 'url' => '', 'tipe' => 'PDF', 'ukuran' => '']]],
            'google_drive'  => [
                'display_mode' => 'embed',
                'url' => '',
                'mode' => 'list',
                'tinggi' => 560,
                'judul' => '',
                'deskripsi' => '',
                'items' => [],
            ],
            'kontak'        => ['alamat' => '', 'telepon' => '', 'email' => '', 'jam_kerja' => '', 'maps_embed' => ''],
            'embed'         => ['url' => '', 'tinggi' => 450, 'judul' => ''],
            'tim'           => ['items' => [['nama' => '', 'jabatan' => '', 'foto' => '', 'bio' => '']]],
            default     => [],
        };
    }

    public static function builderTypes(?string $currentType = null): array
    {
        $types = collect(self::$types)
            ->except(self::HIDDEN_BUILDER_TYPES)
            ->all();

        if ($currentType && isset(self::$types[$currentType]) && ! isset($types[$currentType])) {
            $types[$currentType] = self::$types[$currentType];
        }

        return $types;
    }

    public static function creatableTypeKeys(): array
    {
        return array_keys(self::builderTypes());
    }
}

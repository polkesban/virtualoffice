<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\DokumenMutu;
use App\Models\FasilitasAsrama;
use App\Models\Galeri;
use App\Models\KoleksiPerpus;
use App\Models\LayananBisnis;
use App\Models\LayananLab;
use App\Models\LayananTI;
use App\Models\MahasiswaBerprestasi;
use App\Models\Pelatihan;
use App\Models\Publikasi;
use App\Models\UnitPage;
use App\Support\MediaStorage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class PublicSectionController extends Controller
{
    public function galeri(): View
    {
        $items = Galeri::query()
            ->where('tampil', true)
            ->orderBy('urutan')
            ->orderByDesc('id')
            ->paginate(18)
            ->through(fn (Galeri $item) => [
                'title' => $item->judul,
                'subtitle' => Galeri::$kategoriLabels[$item->kategori] ?? Str::headline($item->kategori),
                'description' => $this->excerpt($item->alt) ?: 'Dokumentasi kegiatan, akademik, dan kehidupan kampus Poltekkes Bandung.',
                'badges' => [Galeri::$kategoriLabels[$item->kategori] ?? Str::headline($item->kategori)],
                'meta' => [],
                'image' => $item->url,
                'image_alt' => $item->alt ?: $item->judul,
                'actions' => [
                    ['label' => 'Buka Gambar', 'href' => $item->url, 'target' => '_blank'],
                ],
            ]);

        return $this->renderListing(
            pageTitle: 'Galeri',
            pageDescription: 'Dokumentasi visual kegiatan, akademik, wisuda, dan atmosfer kampus Poltekkes Bandung yang sudah ditayangkan ke publik.',
            pageEyebrow: 'Dokumentasi Publik',
            emptyMessage: 'Belum ada foto yang ditayangkan ke publik.',
            items: $items,
        );
    }

    public function agenda(): View
    {
        $items = Agenda::query()
            ->publik()
            ->orderBy('tanggal_mulai')
            ->orderBy('waktu_mulai')
            ->paginate(12)
            ->through(fn (Agenda $item) => [
                'title' => $item->judul,
                'subtitle' => $this->agendaDateLabel($item),
                'description' => $this->excerpt($item->deskripsi) ?: 'Agenda kegiatan yang dibuka untuk publik.',
                'badges' => [Agenda::$kategoriLabels[$item->kategori] ?? Str::headline($item->kategori)],
                'meta' => array_values(array_filter([
                    $this->agendaTimeLabel($item),
                    $item->lokasi,
                ])),
                'image' => null,
                'image_alt' => null,
                'actions' => [],
            ]);

        return $this->renderListing(
            pageTitle: 'Agenda & Kalender',
            pageDescription: 'Jadwal agenda publik Poltekkes Bandung untuk kegiatan akademik, kemahasiswaan, dan agenda institusi lainnya.',
            pageEyebrow: 'Agenda Publik',
            emptyMessage: 'Belum ada agenda publik yang dijadwalkan.',
            items: $items,
        );
    }

    public function berprestasi(): View
    {
        $items = MahasiswaBerprestasi::query()
            ->with(['mahasiswa.programStudi'])
            ->aktif()
            ->orderBy('periode')
            ->orderBy('urutan')
            ->paginate(12)
            ->through(fn (MahasiswaBerprestasi $item) => [
                'title' => data_get($item, 'mahasiswa.name', 'Mahasiswa Berprestasi'),
                'subtitle' => data_get($item, 'mahasiswa.programStudi.nama')
                    ?: data_get($item, 'mahasiswa.programStudi.judul')
                    ?: 'Mahasiswa Poltekkes Bandung',
                'description' => $this->excerpt($item->catatan_admin) ?: 'Mahasiswa yang ditampilkan karena capaian akademik atau non-akademik yang menonjol.',
                'badges' => array_values(array_filter([
                    $item->periode_label,
                    MahasiswaBerprestasi::$periodeLabels[$item->periode] ?? null,
                ])),
                'meta' => array_values(array_filter([
                    data_get($item, 'mahasiswa.nim') ? 'NIM ' . data_get($item, 'mahasiswa.nim') : null,
                ])),
                'image' => data_get($item, 'mahasiswa.foto_url'),
                'image_alt' => data_get($item, 'mahasiswa.name', 'Foto mahasiswa'),
                'actions' => [],
            ]);

        return $this->renderListing(
            pageTitle: 'Mahasiswa Berprestasi',
            pageDescription: 'Etalase capaian mahasiswa pilihan Poltekkes Bandung yang sedang ditayangkan untuk publik.',
            pageEyebrow: 'Apresiasi Mahasiswa',
            emptyMessage: 'Belum ada mahasiswa berprestasi yang ditayangkan saat ini.',
            items: $items,
        );
    }

    public function layananBisnis(): View
    {
        $items = LayananBisnis::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(12)
            ->through(fn (LayananBisnis $item) => [
                'title' => $item->nama,
                'subtitle' => LayananBisnis::$kategoriLabels[$item->kategori] ?? Str::headline($item->kategori),
                'description' => $this->excerpt($item->deskripsi) ?: 'Layanan dan produk yang dapat dipesan melalui Unit Bisnis Poltekkes Bandung.',
                'badges' => [$item->harga_format],
                'meta' => array_values(array_filter([
                    LayananBisnis::$kategoriLabels[$item->kategori] ?? null,
                    $item->satuan_harga ? 'Satuan ' . $item->satuan_harga : null,
                ])),
                'image' => $item->url_gambar,
                'image_alt' => $item->nama,
                'actions' => [
                    ['label' => 'Pesan Sekarang', 'href' => route('layanan-bisnis.order', ['layanan' => $item->slug]), 'target' => '_self'],
                ],
            ]);

        return $this->renderListing(
            pageTitle: 'Layanan & Produk Bisnis',
            pageDescription: 'Daftar layanan, produk, catering, pelatihan, dan kebutuhan bisnis lain yang dapat dipesan secara publik.',
            pageEyebrow: 'Unit Bisnis',
            emptyMessage: 'Belum ada layanan atau produk bisnis yang ditayangkan.',
            items: $items,
        );
    }

    public function layananLab(): View
    {
        $items = LayananLab::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(12)
            ->through(fn (LayananLab $item) => [
                'title' => $item->nama,
                'subtitle' => LayananLab::$kategoriLabels[$item->kategori] ?? Str::headline($item->kategori),
                'description' => $this->excerpt($item->deskripsi) ?: $this->excerpt($item->syarat) ?: 'Layanan laboratorium yang tersedia untuk kebutuhan akademik maupun layanan umum.',
                'badges' => [$item->tarif_format],
                'meta' => array_values(array_filter([
                    $item->lama_pengerjaan ? 'Estimasi ' . $item->lama_pengerjaan : null,
                    $item->satuan ? 'Satuan ' . $item->satuan : null,
                ])),
                'image' => null,
                'image_alt' => null,
                'actions' => [],
            ]);

        return $this->renderListing(
            pageTitle: 'Layanan & Tarif Lab',
            pageDescription: 'Daftar layanan laboratorium yang aktif lengkap dengan kategori dan informasi tarif publik.',
            pageEyebrow: 'Laboratorium',
            emptyMessage: 'Belum ada layanan laboratorium yang ditayangkan.',
            items: $items,
        );
    }

    public function fasilitasAsrama(): View
    {
        $items = FasilitasAsrama::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(12)
            ->through(fn (FasilitasAsrama $item) => [
                'title' => $item->nama,
                'subtitle' => FasilitasAsrama::$jenisLabels[$item->jenis] ?? Str::headline($item->jenis),
                'description' => $this->excerpt($item->keterangan) ?: ($item->fasilitas_array !== [] ? implode(', ', array_slice($item->fasilitas_array, 0, 5)) : 'Informasi fasilitas asrama untuk calon penghuni dan civitas kampus.'),
                'badges' => array_values(array_filter([
                    $item->harga_bulan_format,
                    $item->harga_semester_format !== 'Hubungi kami' ? $item->harga_semester_format . ' / semester' : null,
                ])),
                'meta' => array_values(array_filter([
                    'Kapasitas ' . $item->kapasitas,
                    'Terisi ' . $item->terisi,
                    'Sisa ' . $item->sisa_kamar,
                    $item->lantai ? 'Lantai ' . $item->lantai : null,
                ])),
                'image' => $this->storedAsset($item->foto),
                'image_alt' => $item->nama,
                'actions' => [],
            ]);

        return $this->renderListing(
            pageTitle: 'Fasilitas Asrama',
            pageDescription: 'Ketersediaan kamar dan fasilitas asrama aktif yang ditampilkan untuk publik.',
            pageEyebrow: 'Asrama',
            emptyMessage: 'Belum ada fasilitas asrama yang ditayangkan.',
            items: $items,
        );
    }

    public function koleksiPerpus(): View
    {
        $items = KoleksiPerpus::query()
            ->where('aktif', true)
            ->orderBy('judul')
            ->paginate(12)
            ->through(fn (KoleksiPerpus $item) => [
                'title' => $item->judul,
                'subtitle' => $item->penulis,
                'description' => $this->excerpt($item->deskripsi) ?: $this->excerpt(collect([$item->penerbit, $item->tahun_terbit])->filter()->join(' • ')) ?: 'Koleksi perpustakaan yang tersedia untuk penelusuran publik.',
                'badges' => array_values(array_filter([
                    KoleksiPerpus::$kategoriLabels[$item->kategori] ?? Str::headline($item->kategori),
                    $item->status_stok,
                ])),
                'meta' => array_values(array_filter([
                    $item->isbn ? 'ISBN ' . $item->isbn : null,
                    $item->lokasi_rak ? 'Rak ' . $item->lokasi_rak : null,
                    'Stok tersedia ' . $item->stok_tersedia,
                ])),
                'image' => $this->storedAsset($item->cover),
                'image_alt' => $item->judul,
                'actions' => array_values(array_filter([
                    $item->link_digital ? ['label' => 'Buka Versi Digital', 'href' => $item->link_digital, 'target' => '_blank'] : null,
                ])),
            ]);

        return $this->renderListing(
            pageTitle: 'Koleksi Perpustakaan',
            pageDescription: 'Daftar koleksi perpustakaan aktif yang dapat ditelusuri publik lengkap dengan status stok dan tautan digital.',
            pageEyebrow: 'Perpustakaan',
            emptyMessage: 'Belum ada koleksi perpustakaan yang ditayangkan.',
            items: $items,
        );
    }

    public function publikasi(): View
    {
        $items = Publikasi::query()
            ->where('aktif', true)
            ->orderByDesc('tahun')
            ->orderBy('judul')
            ->paginate(12)
            ->through(fn (Publikasi $item) => [
                'title' => $item->judul,
                'subtitle' => $item->penulis,
                'description' => $this->excerpt($item->abstrak) ?: $this->excerpt($item->institusi) ?: 'Publikasi dan keluaran penelitian yang ditayangkan ke publik.',
                'badges' => array_values(array_filter([
                    $item->jenis_label,
                    $item->tahun ? (string) $item->tahun : null,
                ])),
                'meta' => array_values(array_filter([
                    $item->institusi,
                    $item->doi ? 'DOI ' . $item->doi : null,
                    $this->volumeLabel($item),
                ])),
                'image' => null,
                'image_alt' => null,
                'actions' => array_values(array_filter([
                    $item->file_url ? ['label' => 'Buka File', 'href' => $item->file_url, 'target' => '_blank'] : null,
                    $item->link_url ? ['label' => 'Kunjungi Tautan', 'href' => $item->link_url, 'target' => '_blank'] : null,
                ])),
            ]);

        return $this->renderListing(
            pageTitle: 'Publikasi & Penelitian',
            pageDescription: 'Kumpulan artikel, laporan, buku, dan keluaran penelitian lain yang dibuka untuk publik.',
            pageEyebrow: 'Publikasi Akademik',
            emptyMessage: 'Belum ada publikasi yang ditayangkan.',
            items: $items,
        );
    }

    public function dokumenMutu(): View
    {
        $items = DokumenMutu::query()
            ->where('aktif', true)
            ->orderByDesc('tanggal_terbit')
            ->orderBy('judul')
            ->paginate(12)
            ->through(fn (DokumenMutu $item) => [
                'title' => $item->judul,
                'subtitle' => $item->jenis_label,
                'description' => $this->excerpt($item->keterangan) ?: $item->masa_berlaku,
                'badges' => array_values(array_filter([
                    $item->jenis_label,
                    $item->masa_berlaku,
                ])),
                'meta' => array_values(array_filter([
                    $item->nomor_dok ? 'Nomor ' . $item->nomor_dok : null,
                    $item->versi ? 'Versi ' . $item->versi : null,
                    $item->tanggal_terbit?->format('d M Y'),
                ])),
                'image' => null,
                'image_alt' => null,
                'actions' => array_values(array_filter([
                    $item->file_url ? ['label' => 'Buka Dokumen', 'href' => $item->file_url, 'target' => '_blank'] : null,
                ])),
            ]);

        return $this->renderListing(
            pageTitle: 'Dokumen Mutu',
            pageDescription: 'Dokumen mutu aktif yang dipublikasikan untuk kebutuhan referensi institusi dan publik.',
            pageEyebrow: 'Mutu & Kepatuhan',
            emptyMessage: 'Belum ada dokumen mutu yang ditayangkan.',
            items: $items,
        );
    }

    public function layananTI(): View
    {
        $items = LayananTI::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(12)
            ->through(fn (LayananTI $item) => [
                'title' => $item->nama,
                'subtitle' => $item->kategori_label,
                'description' => $this->excerpt($item->deskripsi) ?: $this->excerpt($item->prosedur) ?: 'Layanan dukungan TI yang dibuka untuk kebutuhan sivitas dan publik.',
                'badges' => [$item->kategori_label],
                'meta' => array_values(array_filter([
                    $item->estimasi_waktu ? 'Estimasi ' . $item->estimasi_waktu : null,
                    $item->kontak ? 'Kontak ' . $item->kontak : null,
                ])),
                'image' => null,
                'image_alt' => null,
                'actions' => [],
            ]);

        return $this->renderListing(
            pageTitle: 'Layanan IT',
            pageDescription: 'Direktori layanan TI aktif yang dapat diakses publik untuk dukungan akun, jaringan, perangkat, dan aplikasi.',
            pageEyebrow: 'Teknologi Informasi',
            emptyMessage: 'Belum ada layanan TI yang ditayangkan.',
            items: $items,
        );
    }

    public function pelatihan(): View
    {
        $items = Pelatihan::query()
            ->where('aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderBy('judul')
            ->paginate(12)
            ->through(fn (Pelatihan $item) => [
                'title' => $item->judul,
                'subtitle' => $item->jenis_label,
                'description' => $this->excerpt($item->deskripsi) ?: $this->excerpt($item->hasil) ?: 'Program pelatihan dan workshop yang sedang ditampilkan untuk publik.',
                'badges' => [$item->jenis_label],
                'meta' => array_values(array_filter([
                    $item->durasi,
                    $item->lokasi,
                    $item->penyelenggara,
                    $item->peserta_target,
                    $item->jumlah_peserta ? 'Peserta ' . $item->jumlah_peserta : null,
                ])),
                'image' => $this->storedAsset($item->foto),
                'image_alt' => $item->judul,
                'actions' => [],
            ]);

        return $this->renderListing(
            pageTitle: 'Program Pelatihan',
            pageDescription: 'Daftar pelatihan, workshop, seminar, dan program pengembangan yang ditayangkan ke publik.',
            pageEyebrow: 'Pusat P3',
            emptyMessage: 'Belum ada program pelatihan yang ditayangkan.',
            items: $items,
        );
    }

    public function unitIndex()
    {
        if (! $this->hasVirtualOfficeAccess()) {
            return redirect()->route('login.virtual-office');
        }

        $prodiToJurusanMap = [
            'kaprodi_1'  => 'jurusan_kep',
            'kaprodi_2'  => 'jurusan_keb',
            'kaprodi_3'  => 'jurusan_gizi',
            'kaprodi_5'  => 'jurusan_kesling',
            'kaprodi_6'  => 'jurusan_tlm',
            'kaprodi_7'  => 'jurusan_farm',
            'kaprodi_9'  => 'jurusan_promkes',
            'kaprodi_10' => 'jurusan_tlm',
            'kaprodi_11' => 'jurusan_kesgigi',
            'kaprodi_12' => 'jurusan_kep',
            'kaprodi_13' => 'jurusan_kep',
            'kaprodi_14' => 'jurusan_keb',
            'kaprodi_15' => 'jurusan_keb',
            'kaprodi_16' => 'jurusan_keb',
            'kaprodi_17' => 'jurusan_kesgigi',
            'kaprodi_18' => 'jurusan_kesling',
            'kaprodi_19' => 'jurusan_kep',
            'kaprodi_20' => 'jurusan_keb',
            'kaprodi_21' => 'jurusan_gizi',
            'kaprodi_22' => 'jurusan_gizi',
        ];

        $items = UnitPage::query()
            ->where('aktif', true)
            ->orderBy('judul')
            ->get()
            ->map(function (UnitPage $item) use ($prodiToJurusanMap) {
                $role = (string) $item->role;

                if (str_starts_with($role, 'jurusan_')) {
                    $category = 'jurusan';
                } elseif (str_starts_with($role, 'kaprodi_') || $role === 'kaprodi') {
                    $category = 'prodi';
                } elseif (in_array($role, ['direktur', 'wadir_1', 'wadir_2', 'wadir_3', 'kabag_adum', 'bidang_osdm', 'spi'], true)) {
                    $category = 'bidang';
                } elseif (str_starts_with($role, 'user_') || in_array($role, ['dosen', 'employee', 'pj_kurikulum'], true)) {
                    $category = 'pegawai';
                } elseif (str_starts_with($role, 'org_')) {
                    $category = 'mahasiswa';
                } else {
                    $category = 'unit';
                }

                $subtitle = $item->sub_judul;
                if (! $subtitle) {
                    if (str_starts_with($role, 'wadir_')) {
                        $subtitle = $role === 'wadir_2' ? $item->sub_judul : null;
                    } else {
                        $subtitle = Str::headline($role);
                    }
                }
                if ($subtitle) {
                    $subtitle = str_replace('Halaman resmi', 'Halaman pelayanan', $subtitle);
                }

                return [
                    'title' => $item->judul,
                    'subtitle' => $subtitle,
                    'description' => 'Halaman pelayanan ' . strtolower($item->judul) . ' yang aktif dan dibuka untuk publik.',
                    'badges' => array_values(array_filter([
                        Str::headline($category),
                    ])),
                    'meta' => array_values(array_filter([
                        $item->slug ? 'Slug ' . $item->slug : null,
                    ])),
                    'image' => $this->storedAsset($item->cover_foto),
                    'image_alt' => $item->judul,
                    'role' => $role,
                    'warna_tema' => $item->warna_tema,
                    'is_jurusan' => $category === 'jurusan',
                    'is_kaprodi' => $category === 'prodi',
                    'is_prodi' => $category === 'prodi',
                    'category' => $category,
                    'jurusan_role' => $category === 'prodi' ? ($prodiToJurusanMap[$role] ?? null) : null,
                    'slug' => $item->slug,
                    'actions' => [
                        ['label' => 'Buka Halaman', 'href' => route('unit.show', $item->slug), 'target' => '_self'],
                    ],
                ];
            });

        return $this->renderListing(
            pageTitle: 'Layanan',
            pageDescription: 'Daftar direktori & halaman pelayanan bidang, unit, jurusan, prodi, pegawai, dan organisasi kemahasiswaan Poltekkes Kemenkes Bandung.',
            pageEyebrow: 'Direktori & Layanan',
            emptyMessage: 'Belum ada halaman pelayanan yang ditayangkan.',
            items: $items,
        );
    }

    private function renderListing(
        string $pageTitle,
        string $pageDescription,
        string $pageEyebrow,
        string $emptyMessage,
        $items,
    ): View {
        return view('public-sections.index', compact('pageTitle', 'pageDescription', 'pageEyebrow', 'emptyMessage', 'items'));
    }

    private function excerpt(?string $value, int $limit = 180): ?string
    {
        $text = Str::of((string) $value)->stripTags()->squish()->value();

        if ($text === '') {
            return null;
        }

        return Str::limit($text, $limit);
    }

    private function storedAsset(?string $path): ?string
    {
        return MediaStorage::url($path);
    }

    private function agendaDateLabel(Agenda $item): ?string
    {
        if (! $item->tanggal_mulai) {
            return null;
        }

        if (! $item->tanggal_selesai || $item->tanggal_mulai->equalTo($item->tanggal_selesai)) {
            return $item->tanggal_mulai->format('d M Y');
        }

        return $item->tanggal_mulai->format('d M Y') . ' - ' . $item->tanggal_selesai->format('d M Y');
    }

    private function agendaTimeLabel(Agenda $item): ?string
    {
        if (! $item->waktu_mulai && ! $item->waktu_selesai) {
            return null;
        }

        return trim(($item->waktu_mulai ?: '') . ($item->waktu_selesai ? ' - ' . $item->waktu_selesai : ''));
    }

    private function volumeLabel(Publikasi $item): ?string
    {
        $segments = array_values(array_filter([
            $item->volume ? 'Vol. ' . $item->volume : null,
            $item->nomor ? 'No. ' . $item->nomor : null,
            $item->halaman ? 'Hal. ' . $item->halaman : null,
        ]));

        return $segments !== [] ? implode(' • ', $segments) : null;
    }

    public static function hasVirtualOfficeAccess(): bool
    {
        return \Illuminate\Support\Facades\Auth::check()
            || \Illuminate\Support\Facades\Auth::guard('mahasiswa')->check()
            || session()->has('tamu_user');
    }

    public function showVirtualOfficeLogin()
    {
        if ($this->hasVirtualOfficeAccess()) {
            return redirect()->route('unit.index');
        }
        return view('auth.virtual-office-login');
    }

    public function redirectToGoogle()
    {
        $client = new \Google\Client();
        $client->setClientId(config('media.google_drive.client_id', env('GOOGLE_DRIVE_CLIENT_ID')));
        $client->setClientSecret(config('media.google_drive.client_secret', env('GOOGLE_DRIVE_CLIENT_SECRET')));
        $client->setRedirectUri(route('auth.google.callback'));
        $client->addScope('email');
        $client->addScope('profile');

        return redirect()->away($client->createAuthUrl());
    }

    public function handleGoogleCallback(\Illuminate\Http\Request $request)
    {
        $client = new \Google\Client();
        $client->setClientId(config('media.google_drive.client_id', env('GOOGLE_DRIVE_CLIENT_ID')));
        $client->setClientSecret(config('media.google_drive.client_secret', env('GOOGLE_DRIVE_CLIENT_SECRET')));
        $client->setRedirectUri(route('auth.google.callback'));

        if ($request->has('code')) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));
                $client->setAccessToken($token);

                $oauth2 = new \Google\Service\Oauth2($client);
                $userInfo = $oauth2->userinfo->get();
                $email = $userInfo->email;

                // Check if Google ID is already linked to User
                $linkedUser = \App\Models\User::where('google_id', $userInfo->id)->first();
                if ($linkedUser) {
                    \Illuminate\Support\Facades\Auth::login($linkedUser);
                    session(['google_user' => [
                        'id'    => $userInfo->id,
                        'name'  => $userInfo->name,
                        'email' => $userInfo->email,
                        'picture' => $userInfo->picture,
                    ]]);
                    return redirect()->route('unit.index')->with('success', 'Selamat datang kembali, ' . $linkedUser->name . '!');
                }

                // Check if Google ID is already linked to Mahasiswa
                $linkedMahasiswa = \App\Models\Mahasiswa::where('google_id', $userInfo->id)->first();
                if ($linkedMahasiswa) {
                    \Illuminate\Support\Facades\Auth::guard('mahasiswa')->login($linkedMahasiswa);
                    session(['google_user' => [
                        'id'    => $userInfo->id,
                        'name'  => $userInfo->name,
                        'email' => $userInfo->email,
                        'picture' => $userInfo->picture,
                    ]]);
                    return redirect()->route('unit.index')->with('success', 'Selamat datang kembali, ' . $linkedMahasiswa->name . '!');
                }

                // Store Google user info in session
                $googleUserSession = [
                    'id'    => $userInfo->id,
                    'name'  => $userInfo->name,
                    'email' => $userInfo->email,
                    'picture' => $userInfo->picture,
                ];
                session(['google_user' => $googleUserSession]);

                // Check Admin/Pegawai users
                $existingUser = \App\Models\User::where('email', $email)->first();
                if ($existingUser) {
                    \Illuminate\Support\Facades\Auth::login($existingUser);
                    session(['link_google_account' => [
                        'type' => 'user',
                        'id' => $existingUser->id,
                        'google_id' => $userInfo->id,
                        'name' => $existingUser->name,
                    ]]);
                    return redirect()->route('unit.index');
                }

                // Check Mahasiswa users
                $existingMahasiswa = \App\Models\Mahasiswa::where('email', $email)->first();
                if ($existingMahasiswa) {
                    \Illuminate\Support\Facades\Auth::guard('mahasiswa')->login($existingMahasiswa);
                    session(['link_google_account' => [
                        'type' => 'mahasiswa',
                        'id' => $existingMahasiswa->id,
                        'google_id' => $userInfo->id,
                        'name' => $existingMahasiswa->name,
                    ]]);
                    return redirect()->route('unit.index');
                }

                session(['tamu_user' => $googleUserSession]);

                return redirect()->route('unit.index')->with('success', 'Selamat datang, ' . $userInfo->name . '! Anda berhasil masuk menggunakan Google.');
            } catch (\Exception $e) {
                return redirect()->route('login.virtual-office')->withErrors(['error' => 'Gagal login dengan Google: ' . $e->getMessage()]);
            }
        }

        return redirect()->route('login.virtual-office')->withErrors(['error' => 'Akses ditolak atau kode otentikasi tidak valid.']);
    }

    public function linkGoogleAccount()
    {
        $linkData = session('link_google_account');
        if (! $linkData) {
            return redirect()->route('unit.index');
        }

        if ($linkData['type'] === 'user') {
            $user = \App\Models\User::find($linkData['id']);
            if ($user) {
                $user->update(['google_id' => $linkData['google_id']]);
            }
        } elseif ($linkData['type'] === 'mahasiswa') {
            $mahasiswa = \App\Models\Mahasiswa::find($linkData['id']);
            if ($mahasiswa) {
                $mahasiswa->update(['google_id' => $linkData['google_id']]);
            }
        }

        session()->forget('link_google_account');

        return redirect()->route('unit.index')->with('success', 'Akun Google Anda berhasil ditautkan!');
    }

    public function dismissLinkGoogleAccount()
    {
        session()->forget('link_google_account');
        return redirect()->route('unit.index');
    }

    public function logoutVirtualOffice()
    {
        session()->forget('tamu_user');
        session()->forget('google_user');
        session()->forget('link_google_account');
        \Illuminate\Support\Facades\Auth::logout();
        \Illuminate\Support\Facades\Auth::guard('mahasiswa')->logout();
        return redirect()->route('login.virtual-office')->with('success', 'Anda telah berhasil keluar dari Virtual Office.');
    }
}
@extends('admin.layouts.admin')
@section('title', $unitEvent->exists ? 'Edit Event Unit' : 'Tambah Event Unit')
@section('page-title', $unitEvent->exists ? 'Edit Event Unit' : 'Tambah Event Unit')
@section('page-subtitle', 'Lengkapi data acara, registrasi, akses daring, materi, tes, evaluasi, notulen, laporan, surat tugas, dan sertifikat')

@section('content')
<div class="p-6 max-w-6xl mx-auto">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent->exists ? $unitEvent : null])

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-200">
        Surat tugas kegiatan kolektif bisa diunggah langsung dari form event ini. Surat tugas / SK individual per peserta tetap dikelola dari menu <strong>Peserta, Surat Tugas & Sertifikat</strong>.
    </div>

    @if($unitEvent->exists)
        <div class="mb-4 grid grid-cols-1 gap-3 xl:grid-cols-5">
            <a href="{{ route('admin.unit-events.materials.index', [$unitPage, $unitEvent]) }}" class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Materi</p>
                <p class="mt-2 text-base font-extrabold text-gray-900 dark:text-white">Kelola Materi</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload file, link, dan embed materi event.</p>
            </a>
            <a href="{{ route('admin.unit-events.questions.index', [$unitPage, $unitEvent]) }}" class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Tes & Evaluasi</p>
                <p class="mt-2 text-base font-extrabold text-gray-900 dark:text-white">Kelola Pertanyaan</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pre test, post test, dan form evaluasi.</p>
            </a>
            <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $unitEvent]) }}" class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Peserta & Dokumen</p>
                <p class="mt-2 text-base font-extrabold text-gray-900 dark:text-white">Kelola Peserta dan Surat Tugas</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Peserta, narasumber, panitia, surat tugas / SK personal, dan sertifikat.</p>
            </a>
            <a href="{{ route('admin.unit-events.documentation.index', [$unitPage, $unitEvent]) }}" class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Notulen & Laporan</p>
                <p class="mt-2 text-base font-extrabold text-gray-900 dark:text-white">Kelola Dokumentasi Event</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Voice to text, resume AI, foto kegiatan, dan draft laporan.</p>
            </a>
            <a href="{{ route('unit.events.show', [$unitPage->slug, $unitEvent->slug]) }}" target="_blank" class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Publik</p>
                <p class="mt-2 text-base font-extrabold text-gray-900 dark:text-white">Buka Halaman Event</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lihat tampilan publik dan form registrasi.</p>
            </a>
        </div>
    @endif

    <form method="POST"
          action="{{ $unitEvent->exists ? route('admin.unit-events.update', [$unitPage, $unitEvent]) : route('admin.unit-events.store', $unitPage) }}"
          enctype="multipart/form-data"
          data-turbo="false"
          class="space-y-6">
        @csrf
        @if($unitEvent->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.6fr,1fr]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Informasi Utama Event</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Judul Event <span class="text-red-500">*</span></label>
                            <input type="text" name="judul" value="{{ old('judul', $unitEvent->judul) }}" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Slug Publik</label>
                            <input type="text" name="slug" value="{{ old('slug', $unitEvent->slug) }}" placeholder="otomatis-dari-judul" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="mt-1 text-xs text-gray-400">Gunakan huruf kecil, angka, dan tanda hubung. Kosongkan jika ingin dibuat otomatis.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Ringkasan</label>
                            <textarea name="ringkasan" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('ringkasan', $unitEvent->ringkasan) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Deskripsi Lengkap</label>
                            <textarea name="deskripsi" rows="8" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('deskripsi', $unitEvent->deskripsi) }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Jadwal & Format Pelaksanaan</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Jenis Kegiatan <span class="text-red-500">*</span></label>
                            <select name="jenis_kegiatan" id="jenis_kegiatan" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                @foreach(\App\Models\UnitEvent::$jenisLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('jenis_kegiatan', $unitEvent->jenis_kegiatan ?: 'webinar') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="sub-jenis-container" class="hidden">
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Sub Jenis Kegiatan <span class="text-red-500">*</span></label>
                            <select name="sub_jenis_kegiatan" id="sub_jenis_kegiatan" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                <option value="kuliah" @selected(old('sub_jenis_kegiatan', $unitEvent->sub_jenis_kegiatan) === 'kuliah')>Kuliah</option>
                                <option value="praktik" @selected(old('sub_jenis_kegiatan', $unitEvent->sub_jenis_kegiatan) === 'praktik')>Praktik</option>
                                <option value="gabungan" @selected(old('sub_jenis_kegiatan', $unitEvent->sub_jenis_kegiatan) === 'gabungan')>Matakuliah Gabungan</option>
                            </select>
                        </div>
                        @if($unitPage->programStudi)
                            <div class="md:col-span-2 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800 dark:bg-brand-900/20 dark:text-brand-200 font-bold">
                                🏫 Program Studi: {{ $unitPage->programStudi->nama }} ({{ $unitPage->programStudi->jenjang }})
                            </div>
                        @endif
                        <div id="shifts-container" class="md:col-span-2 hidden rounded-2xl border border-gray-100 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-gray-950 mt-2">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">Pengaturan Shift Praktik</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tentukan jam dinas/shift praktik. Siswa akan melakukan presensi sesuai shift ini.</p>
                                </div>
                                <button type="button" id="add-shift-btn" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-brand-500">+ Tambah Shift</button>
                            </div>
                            <input type="hidden" name="shifts" id="shifts-input" value="{{ json_encode(old('shifts', $unitEvent->shifts ?? [['name' => 'Pagi', 'start' => '07:00', 'end' => '14:00'], ['name' => 'Siang', 'start' => '14:00', 'end' => '21:00'], ['name' => 'Malam', 'start' => '21:00', 'end' => '07:00'], ['name' => 'Libur', 'start' => '00:00', 'end' => '00:00']])) }}">
                            <div id="shifts-list" class="space-y-3">
                                <!-- Dynamically rendered shifts -->
                            </div>
                        </div>
                        <div id="locations-container" class="md:col-span-2 hidden rounded-2xl border border-gray-100 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-gray-950 mt-2">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">Pengaturan Tempat Praktik / Lokasi</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tentukan tempat-tempat praktik mahasiswa untuk dihubungkan ke kelompok/daftar hadir.</p>
                                </div>
                                <button type="button" id="add-location-btn" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-brand-500">+ Tambah Tempat</button>
                            </div>
                            <input type="hidden" name="lokasi_praktik" id="locations-input" value="{{ json_encode(old('lokasi_praktik', $unitEvent->lokasi_praktik ?? [])) }}">
                            <div id="locations-list" class="space-y-3">
                                <!-- Dynamically rendered locations -->
                            </div>
                        </div>
                        <div id="academic-fields-container" class="md:col-span-2 hidden grid grid-cols-1 md:grid-cols-3 gap-4 border border-gray-100 bg-gray-50/20 p-5 rounded-2xl dark:border-gray-800 mt-2">
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tahun Akademik</label>
                                <select name="tahun_akademik" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                    <option value="">-- Pilih Tahun Akademik --</option>
                                    @foreach(['2023/2024', '2024/2025', '2025/2026', '2026/2027', '2027/2028'] as $ta)
                                        <option value="{{ $ta }}" @selected(old('tahun_akademik', $unitEvent->tahun_akademik ?: '2025/2026') === $ta)>{{ $ta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tingkat</label>
                                <select name="tingkat" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                    <option value="">-- Pilih Tingkat --</option>
                                    @foreach(['I', 'II', 'III', 'IV'] as $tingkat)
                                        <option value="{{ $tingkat }}" @selected(old('tingkat', $unitEvent->tingkat) === $tingkat)>{{ $tingkat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Semester</label>
                                <select name="semester" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                    <option value="">-- Pilih Semester --</option>
                                    @foreach(['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'] as $sem)
                                        <option value="{{ $sem }}" @selected(old('semester', $unitEvent->semester) === $sem)>{{ $sem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Metode <span class="text-red-500">*</span></label>
                            <select name="metode" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                @foreach(\App\Models\UnitEvent::$metodeLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('metode', $unitEvent->metode ?: 'online') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Status <span class="text-red-500">*</span></label>
                            <select name="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                @foreach(\App\Models\UnitEvent::$statusLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $unitEvent->status ?: 'draft') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Kapasitas Peserta</label>
                            <input type="number" name="kapasitas" min="1" max="100000" value="{{ old('kapasitas', $unitEvent->kapasitas) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Mulai <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="mulai_at" value="{{ old('mulai_at', $unitEvent->mulai_at?->format('Y-m-d\TH:i')) }}" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Selesai</label>
                            <input type="datetime-local" name="selesai_at" value="{{ old('selesai_at', $unitEvent->selesai_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
                            Override jam cetak daftar hadir dipakai untuk mengisi kolom <strong>Jam Mulai</strong> dan <strong>Jam Selesai</strong> sesuai jadwal kegiatan, misalnya 08.00 - 15.00. Kosongkan jika ingin mengikuti jam mulai dan selesai event.
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Jam Mulai Cetak Daftar Hadir</label>
                            <input type="time" name="attendance_print_start_time" step="60" value="{{ old('attendance_print_start_time', $unitEvent->attendance_print_start_time) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="mt-1 text-xs text-gray-400">Contoh: 08:00. Dipakai di metadata waktu dan kolom jam mulai pada lembar cetak.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Jam Selesai Cetak Daftar Hadir</label>
                            <input type="time" name="attendance_print_end_time" step="60" value="{{ old('attendance_print_end_time', $unitEvent->attendance_print_end_time) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="mt-1 text-xs text-gray-400">Contoh: 15:00. Dipakai di metadata waktu dan kolom jam selesai pada lembar cetak.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Lokasi, Link Akses, dan Narahubung</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Lokasi / Tempat</label>
                            <input type="text" name="lokasi" value="{{ old('lokasi', $unitEvent->lokasi) }}" placeholder="Aula / Gedung / Zoom Meeting" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Link Zoom / Meeting</label>
                            <input type="url" name="zoom_link" value="{{ old('zoom_link', $unitEvent->zoom_link) }}" placeholder="https://..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Link Grup WhatsApp</label>
                            <input type="url" name="whatsapp_group_link" value="{{ old('whatsapp_group_link', $unitEvent->whatsapp_group_link) }}" placeholder="https://chat.whatsapp.com/..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Narahubung</label>
                            <input type="text" name="narahubung_nama" value="{{ old('narahubung_nama', $unitEvent->narahubung_nama) }}" placeholder="Nama PIC" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">WhatsApp Narahubung</label>
                            <input type="text" name="narahubung_whatsapp" value="{{ old('narahubung_whatsapp', $unitEvent->narahubung_whatsapp) }}" placeholder="62812..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Alamat Detail</label>
                        <textarea name="alamat_detail" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('alamat_detail', $unitEvent->alamat_detail) }}</textarea>
                    </div>

                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Embed Peta / Maps</label>
                        <textarea name="maps_embed" rows="4" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Tempel iframe embed atau URL map">{{ old('maps_embed', $unitEvent->maps_embed) }}</textarea>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Penandatangan Dokumen (Mengetahui)</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Atur pejabat penandatangan di bagian bawah "Mengetahui" pada cetakan daftar hadir, daftar hadir lembur, dan berita acara.</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Jabatan Pejabat</label>
                            <input type="text" name="penandatangan_jabatan" value="{{ old('penandatangan_jabatan', $unitEvent->penandatangan_jabatan) }}" placeholder="Contoh: Direktur / Panitia / Penanggung Jawab" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama Pejabat</label>
                            <input type="text" name="penandatangan_nama" value="{{ old('penandatangan_nama', $unitEvent->penandatangan_nama) }}" placeholder="Contoh: Dr. Pramita Iriana, S.Kep., M.Biomed" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">NIP Pejabat</label>
                            <input type="text" name="penandatangan_nip" value="{{ old('penandatangan_nip', $unitEvent->penandatangan_nip) }}" placeholder="Contoh: 197009211996032001" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Tanda Tangan Digital Penanggung Jawab</label>
                            @if($unitEvent->exists)
                                <a href="{{ route('admin.unit-events.signature', [$unitPage, $unitEvent]) }}" class="text-xs font-bold text-brand-600 hover:underline flex items-center gap-1">
                                    <span>✍️ Buka Signature Pad Layar Penuh</span>
                                </a>
                            @endif
                        </div>

                        @if($unitEvent->penandatangan_signature_path)
                            <div class="mb-3 p-3 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ \App\Support\MediaStorage::url($unitEvent->penandatangan_signature_path) }}" alt="Tanda Tangan" class="h-16 max-w-xs object-contain bg-white rounded-lg p-1 border border-gray-200">
                                    <div>
                                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">✓ Tanda tangan aktif</span>
                                        <p class="text-[11px] text-gray-500">Tercetak otomatis di Daftar Hadir, Berita Acara, dan Laporan.</p>
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 text-xs font-bold text-red-600 cursor-pointer hover:underline">
                                    <input type="checkbox" name="hapus_penandatangan_signature" value="1" class="rounded">
                                    Hapus Tanda Tangan
                                </label>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ $unitEvent->penandatangan_signature_path ? 'Ganti dengan File Gambar Baru (Opsional)' : 'Unggah File Gambar Tanda Tangan (PNG / JPG / WEBP)' }}</label>
                            <input type="file" name="penandatangan_signature_file" accept="image/png, image/jpeg, image/jpg, image/webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 dark:file:text-brand-300">
                            <p class="mt-1 text-[11px] text-gray-400">Atau bubuhkan langsung menggunakan jari di HP / mouse melalui tombol <strong>"Buka Signature Pad"</strong>.</p>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama Koordinator Matakuliah</label>
                            <input type="text" name="koordinator_nama" value="{{ old('koordinator_nama', $unitEvent->koordinator_nama) }}" placeholder="Khusus cetak rekap absen praktik" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">NIP Koordinator Matakuliah</label>
                            <input type="text" name="koordinator_nip" value="{{ old('koordinator_nip', $unitEvent->koordinator_nip) }}" placeholder="Contoh: 197707112005012000" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                    </div>
                </section>

                @if($unitEvent->exists && $unitEvent->id)
                    @php
                        $speakersList = $unitEvent->speakers()->get();
                    @endphp
                    <section class="rounded-2xl border border-teal-200 bg-white p-6 dark:border-teal-900/50 dark:bg-gray-900 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                            <div>
                                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">🎙️ Narasumber & Pembicara Event</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tambahkan narasumber/pembicara beserta foto profilnya untuk tampil di template flyer resmi dan landing page event.</p>
                            </div>
                            <a href="{{ route('admin.unit-events.participants.create', [$unitPage, $unitEvent, 'peran' => 'speaker']) }}"
                               class="rounded-xl bg-teal-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-teal-500 transition-colors flex items-center gap-1.5 shadow-sm shrink-0 self-start sm:self-auto">
                                <span>+</span> Tambah Narasumber & Foto
                            </a>
                        </div>

                        @if($speakersList->isEmpty())
                            <div class="rounded-xl bg-teal-50/60 dark:bg-teal-950/30 p-4 border border-teal-200 dark:border-teal-900/40 text-center">
                                <p class="text-xs font-bold text-teal-900 dark:text-teal-300">Belum ada data narasumber / pembicara yang diunggah.</p>
                                <p class="text-[11px] text-teal-700 dark:text-teal-400 mt-1">Klik tombol <strong>"+ Tambah Narasumber & Foto"</strong> di atas untuk menambahkan nama pemateri dan mengunggah foto profilnya.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($speakersList as $spk)
                                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-teal-500/40 bg-teal-950/80 p-0.5 shadow">
                                                <img src="{{ $spk->foto_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($spk->nama) . '&background=0284c7&color=fff&size=256' }}" alt="{{ $spk->nama }}" class="h-full w-full rounded-lg object-cover">
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $spk->nama }}</p>
                                                <p class="text-[11px] text-teal-600 dark:text-teal-400 truncate">{{ $spk->jabatan ?: ($spk->instansi ?: 'Narasumber') }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('admin.unit-events.participants.edit', [$unitPage, $unitEvent, $spk]) }}" class="shrink-0 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-teal-50 hover:text-teal-700 transition-colors">
                                            ✏️ Edit & Foto
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            <div class="space-y-6">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">🖼️ Template Flyer & Desain Visual</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Atur elemen visual flyer otomatis (Logo Polkesban, Direktur, Narasumber, Tema Warna, dan Benefit).</p>
                        </div>
                        @if($unitEvent->exists && $unitEvent->id)
                            <a href="{{ route('admin.unit-events.flyer', [$unitPage, $unitEvent]) }}" target="_blank" class="rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 flex items-center gap-1">
                                <span>↗</span> Buka Flyer
                            </a>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tema Warna Flyer</label>
                            <select name="flyer_theme" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 font-semibold">
                                @foreach(\App\Models\UnitEvent::$flyerThemeLabels as $themeKey => $themeLabel)
                                    <option value="{{ $themeKey }}" @selected(old('flyer_theme', $unitEvent->flyer_theme ?: 'modern_teal') === $themeKey)>{{ $themeLabel }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400">Tema visual warna gradien, aksen badge, dan tata letak poster.</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tagline / Sub-tema Flyer</label>
                            <input type="text" name="flyer_tagline" value="{{ old('flyer_tagline', $unitEvent->flyer_tagline) }}" placeholder="Contoh: SEMINAR NASIONAL KESEHATAN 2026 / KULIAH PAKAR" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Benefit & Fasilitas Kegiatan</label>
                            <textarea name="flyer_benefits" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" placeholder="Tulis 1 baris per benefit, contoh:
E-Sertifikat Resmi Ber-SKP Kemenkes RI
Materi Lengkap Narasumber
Wawasan & Diskusi Bersama Pakar">{{ old('flyer_benefits', is_array($unitEvent->flyer_benefits) ? implode("\n", $unitEvent->flyer_benefits) : $unitEvent->flyer_benefits) }}</textarea>
                            <p class="mt-1 text-xs text-gray-400">Tulis satu poin benefit per baris untuk ditampilkan sebagai chips pada flyer.</p>
                        </div>

                        {{-- Direktur Customization for Flyer --}}
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 space-y-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-300">Sesi Direktur Poltekkes Bandung (Opening Speech / Keynote)</p>
                            
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700 dark:text-gray-300">Nama Direktur</label>
                                <input type="text" name="direktur_nama" value="{{ old('direktur_nama', $unitEvent->direktur_nama) }}" placeholder="Dr. Pujiono, SKM, M.Kes (Kosongkan untuk default)" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700 dark:text-gray-300">Upload Foto Direktur (Opsional)</label>
                                @if($unitEvent->direktur_foto_path)
                                    <div class="flex items-center gap-3 mb-2">
                                        <img src="{{ \App\Support\MediaStorage::url($unitEvent->direktur_foto_path) }}" alt="Foto Direktur" class="h-14 w-14 rounded-full object-cover border border-amber-400 ring-2 ring-amber-400/20">
                                        <label class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400 cursor-pointer">
                                            <input type="checkbox" name="hapus_direktur_foto" value="1" class="rounded">
                                            Hapus foto direktur saat ini
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="direktur_foto" accept="image/*" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800">
                                <p class="mt-1 text-[11px] text-gray-400">Format gambar persegi (JPG/PNG), maks 4 MB.</p>
                            </div>
                        </div>

                        {{-- Manual Custom Flyer Upload --}}
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Upload Flyer Gambar Custom (Opsional)</label>
                            @if($unitEvent->flyer_path)
                                <div class="mb-3">
                                    <img src="{{ \App\Support\MediaStorage::url($unitEvent->flyer_path) }}" alt="{{ $unitEvent->judul }}" class="w-full rounded-xl border border-gray-100 object-cover dark:border-gray-800 max-h-56">
                                </div>
                            @endif
                            <input type="file" name="flyer" accept="image/*" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="mt-1 text-xs text-gray-400">Jika diisi, poster custom ini akan ditampilkan di hero event. Jika dikosongkan, sistem otomatis membuatkan template flyer berlogo Polkesban & Direktur.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Surat Tugas Kegiatan</h3>
                    <div class="mt-4 space-y-4">
                        @if($unitEvent->surat_tugas_event_path && $unitEvent->slug)
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <a href="{{ route('unit.events.event-assignment-letter.show', ['unitSlug' => $unitPage->slug, 'eventSlug' => $unitEvent->slug]) }}" target="_blank" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">Lihat surat tugas kegiatan saat ini</a>
                                <p class="mt-1 text-xs text-gray-400">Diunggah {{ $unitEvent->surat_tugas_event_uploaded_at?->translatedFormat('d M Y H:i') ?: '-' }}</p>
                            </div>
                        @endif
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Upload Surat Tugas Kegiatan</label>
                            <input type="file" name="surat_tugas_event_file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="mt-1 text-xs text-gray-400">Satu file kolektif untuk event. Dokumen ini akan ditampilkan di halaman event dan portal peserta.</p>
                        </div>
                        @if($unitEvent->surat_tugas_event_path)
                            <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="hapus_surat_tugas_event" value="1" class="rounded">
                                Hapus surat tugas kegiatan yang sudah ada
                            </label>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Pengaturan Registrasi</h3>
                    <div class="mt-4 space-y-4">
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="registrasi_aktif" value="1" @checked(old('registrasi_aktif', $unitEvent->registrasi_aktif ?? true)) class="rounded">
                            Registrasi peserta dibuka
                        </label>
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $unitEvent->is_featured)) class="rounded">
                            Tampilkan sebagai event unggulan
                        </label>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Registrasi Mulai</label>
                            <input type="datetime-local" name="registrasi_mulai_at" value="{{ old('registrasi_mulai_at', $unitEvent->registrasi_mulai_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Registrasi Selesai</label>
                            <input type="datetime-local" name="registrasi_selesai_at" value="{{ old('registrasi_selesai_at', $unitEvent->registrasi_selesai_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Fitur Peserta</h3>
                    <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-700 dark:text-gray-300">
                        <label class="flex items-center gap-3"><input type="checkbox" name="daftar_hadir_aktif" value="1" @checked(old('daftar_hadir_aktif', $unitEvent->daftar_hadir_aktif ?? true)) class="rounded"> Daftar hadir online</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="allow_direct_attendance" value="1" @checked(old('allow_direct_attendance', $unitEvent->allow_direct_attendance)) class="rounded"> Izinkan daftar hadir langsung (tanpa registrasi)</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="pre_test_aktif" value="1" @checked(old('pre_test_aktif', $unitEvent->pre_test_aktif)) class="rounded"> Pre test</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="post_test_aktif" value="1" @checked(old('post_test_aktif', $unitEvent->post_test_aktif)) class="rounded"> Post test</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="evaluasi_aktif" value="1" @checked(old('evaluasi_aktif', $unitEvent->evaluasi_aktif)) class="rounded"> Form evaluasi</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="rubrik_aktif" value="1" @checked(old('rubrik_aktif', $unitEvent->rubrik_aktif)) class="rounded"> Rubrik penilaian</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="sertifikat_peserta_aktif" value="1" @checked(old('sertifikat_peserta_aktif', $unitEvent->sertifikat_peserta_aktif)) class="rounded"> Sertifikat peserta</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="sertifikat_narasumber_aktif" value="1" @checked(old('sertifikat_narasumber_aktif', $unitEvent->sertifikat_narasumber_aktif)) class="rounded"> Sertifikat narasumber</label>
                        <label class="flex items-center gap-3"><input type="checkbox" name="sertifikat_panitia_aktif" value="1" @checked(old('sertifikat_panitia_aktif', $unitEvent->sertifikat_panitia_aktif)) class="rounded"> Sertifikat panitia</label>
                        <label class="flex items-center gap-3"><input type="checkbox" id="berita_acara_form_aktif" name="berita_acara_form_aktif" value="1" @checked(old('berita_acara_form_aktif', $unitEvent->berita_acara_form_aktif ?? false)) class="rounded"> Aktifkan formulir penandatanganan Berita Acara</label>
                    </div>

                    {{-- Dynamic Konten Berita Acara section --}}
                    <div id="admin_berita_acara_konten_section" class="mt-4 p-4 border border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50 dark:bg-gray-800/50" style="display: none;">
                        <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-3">📄 Konten Berita Acara</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700 dark:text-gray-300">Judul Berita Acara</label>
                                <input type="text" name="berita_acara_judul" value="{{ old('berita_acara_judul', $unitEvent->berita_acara_judul) }}" placeholder="Contoh: Berita Acara Sosialisasi Panduan Keselamatan Pasien" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700 dark:text-gray-300">Ringkasan / Isi Berita Acara</label>
                                <textarea name="berita_acara_ringkasan" rows="4" placeholder="Tuliskan ringkasan / isi berita acara yang akan ditandatangani..." class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ old('berita_acara_ringkasan', $unitEvent->berita_acara_ringkasan) }}</textarea>
                            </div>
                        </div>
                    </div>
                </section>


            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('admin.unit-events.index', $unitPage) }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Batal</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                {{ $unitEvent->exists ? 'Simpan Perubahan' : 'Buat Event Unit' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function initEventForm() {
        const checkbox = document.getElementById('berita_acara_form_aktif');
        const section = document.getElementById('admin_berita_acara_konten_section');
        
        function toggleSection() {
            if (checkbox && checkbox.checked) {
                section.style.display = 'block';
            } else if (section) {
                section.style.display = 'none';
            }
        }
        
        if (checkbox) {
            checkbox.addEventListener('change', toggleSection);
            toggleSection();
        }

        // PBM & Shifts Logic
        const jenisKegiatanSelect = document.getElementById('jenis_kegiatan');
        const subJenisSelect = document.getElementById('sub_jenis_kegiatan');
        const subJenisContainer = document.getElementById('sub-jenis-container');
        const shiftsContainer = document.getElementById('shifts-container');
        const shiftsInput = document.getElementById('shifts-input');
        const shiftsList = document.getElementById('shifts-list');
        const addShiftBtn = document.getElementById('add-shift-btn');

        // PBM Locations & Academic Fields
        const locationsContainer = document.getElementById('locations-container');
        const locationsInput = document.getElementById('locations-input');
        const locationsList = document.getElementById('locations-list');
        const addLocationBtn = document.getElementById('add-location-btn');
        const academicFieldsContainer = document.getElementById('academic-fields-container');

        let shifts = [];
        try {
            shifts = JSON.parse((shiftsInput && shiftsInput.value) || '[]');
        } catch (e) {
            shifts = [];
        }

        let locations = [];
        try {
            locations = JSON.parse((locationsInput && locationsInput.value) || '[]');
        } catch (e) {
            locations = [];
        }

        function togglePbmFields() {
            if (!jenisKegiatanSelect) return;

            const isPbm = jenisKegiatanSelect.value === 'pbm';
            const subVal = subJenisSelect ? subJenisSelect.value : 'kuliah';
            const isPraktikOrGabungan = subVal === 'praktik' || subVal === 'gabungan';

            if (subJenisContainer) {
                if (isPbm) {
                    subJenisContainer.classList.remove('hidden');
                    subJenisContainer.style.display = 'block';
                } else {
                    subJenisContainer.classList.add('hidden');
                    subJenisContainer.style.display = 'none';
                }
            }

            if (academicFieldsContainer) {
                if (isPbm) {
                    academicFieldsContainer.classList.remove('hidden');
                    academicFieldsContainer.style.display = 'grid';
                } else {
                    academicFieldsContainer.classList.add('hidden');
                    academicFieldsContainer.style.display = 'none';
                }
            }

            if (shiftsContainer) {
                if (isPbm && isPraktikOrGabungan) {
                    shiftsContainer.classList.remove('hidden');
                    shiftsContainer.style.display = 'block';
                } else {
                    shiftsContainer.classList.add('hidden');
                    shiftsContainer.style.display = 'none';
                }
            }

            if (locationsContainer) {
                if (isPbm && isPraktikOrGabungan) {
                    locationsContainer.classList.remove('hidden');
                    locationsContainer.style.display = 'block';
                } else {
                    locationsContainer.classList.add('hidden');
                    locationsContainer.style.display = 'none';
                }
            }
        }

        function renderShifts() {
            if (!shiftsList) return;
            shiftsList.innerHTML = '';
            shifts.forEach((shift, index) => {
                const row = document.createElement('div');
                row.className = 'flex flex-wrap items-center gap-3 bg-white p-3 rounded-xl border border-gray-100 dark:bg-gray-900 dark:border-gray-800';
                row.innerHTML = `
                    <div class="flex-1 min-w-[120px]">
                        <input type="text" value="${shift.name || ''}" placeholder="Nama Shift (e.g. Pagi)" class="shift-name-input w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-850" data-index="${index}">
                    </div>
                    <div class="w-28">
                        <input type="time" value="${shift.start || ''}" class="shift-start-input w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-850" data-index="${index}">
                    </div>
                    <span class="text-gray-400 text-xs">s.d</span>
                    <div class="w-28">
                        <input type="time" value="${shift.end || ''}" class="shift-end-input w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-850" data-index="${index}">
                    </div>
                    <button type="button" class="remove-shift-btn text-red-500 hover:text-red-700 p-1.5 text-xs font-bold" data-index="${index}">Hapus</button>
                `;
                shiftsList.appendChild(row);
            });

            // Add Event Listeners
            shiftsList.querySelectorAll('.shift-name-input').forEach(input => {
                input.addEventListener('input', function() {
                    const idx = this.dataset.index;
                    shifts[idx].name = this.value;
                    updateShiftsInput();
                });
            });

            shiftsList.querySelectorAll('.shift-start-input').forEach(input => {
                input.addEventListener('change', function() {
                    const idx = this.dataset.index;
                    shifts[idx].start = this.value;
                    updateShiftsInput();
                });
            });

            shiftsList.querySelectorAll('.shift-end-input').forEach(input => {
                input.addEventListener('change', function() {
                    const idx = this.dataset.index;
                    shifts[idx].end = this.value;
                    updateShiftsInput();
                });
            });

            shiftsList.querySelectorAll('.remove-shift-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = this.dataset.index;
                    shifts.splice(idx, 1);
                    renderShifts();
                    updateShiftsInput();
                });
            });
        }

        function updateShiftsInput() {
            if (shiftsInput) {
                shiftsInput.value = JSON.stringify(shifts);
            }
        }

        function renderLocations() {
            if (!locationsList) return;
            locationsList.innerHTML = '';
            locations.forEach((loc, index) => {
                const row = document.createElement('div');
                row.className = 'flex flex-wrap items-center gap-3 bg-white p-3 rounded-xl border border-gray-100 dark:bg-gray-900 dark:border-gray-800';
                row.innerHTML = `
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" value="${loc || ''}" placeholder="Nama Tempat Praktik (e.g. RSUD Welas Asih)" class="location-name-input w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-850" data-index="${index}">
                    </div>
                    <button type="button" class="remove-location-btn text-red-500 hover:text-red-700 p-1.5 text-xs font-bold" data-index="${index}">Hapus</button>
                `;
                locationsList.appendChild(row);
            });

            locationsList.querySelectorAll('.location-name-input').forEach(input => {
                input.addEventListener('input', function() {
                    const idx = this.dataset.index;
                    locations[idx] = this.value;
                    updateLocationsInput();
                });
            });

            locationsList.querySelectorAll('.remove-location-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = this.dataset.index;
                    locations.splice(idx, 1);
                    renderLocations();
                    updateLocationsInput();
                });
            });
        }

        function updateLocationsInput() {
            if (locationsInput) {
                locationsInput.value = JSON.stringify(locations);
            }
        }

        if (jenisKegiatanSelect) {
            jenisKegiatanSelect.addEventListener('change', togglePbmFields);
            jenisKegiatanSelect.addEventListener('input', togglePbmFields);
        }
        if (subJenisSelect) {
            subJenisSelect.addEventListener('change', togglePbmFields);
            subJenisSelect.addEventListener('input', togglePbmFields);
        }
        togglePbmFields();

        if (addShiftBtn) {
            addShiftBtn.addEventListener('click', function() {
                shifts.push({ name: '', start: '', end: '' });
                renderShifts();
                updateShiftsInput();
            });
            renderShifts();
        }

        if (addLocationBtn) {
            addLocationBtn.addEventListener('click', function() {
                locations.push('');
                renderLocations();
                updateLocationsInput();
            });
            renderLocations();
        }
    }

    // Guard: prevent double initialization if both events fire
    let _eventFormInitialized = false;
    function safeInitEventForm() {
        if (_eventFormInitialized) return;
        _eventFormInitialized = true;
        initEventForm();
    }

    // turbo:load fires on every Turbo navigation (including the initial visit)
    // DOMContentLoaded fires on full page load (fallback when Turbo isn't active)
    document.addEventListener('DOMContentLoaded', safeInitEventForm);
    document.addEventListener('turbo:load', function() {
        _eventFormInitialized = false; // reset guard on each Turbo navigation
        safeInitEventForm();
    });
    document.addEventListener('turbo:render', function() {
        _eventFormInitialized = false;
        safeInitEventForm();
    });
</script>
@endpush
@endsection
@extends('layouts.app')
@section('title', 'Akses Peserta - ' . $event->judul)
@section('description', $event->jenis_kegiatan === 'wisuda' 
    ? 'Portal akses undangan wisuda resmi untuk melihat undangan digital dan scan kehadiran acara ' . $event->judul
    : 'Portal akses peserta untuk daftar hadir, tes, evaluasi, materi, surat tugas, dan sertifikat acara ' . $event->judul)

@section('content')
@php
    $materials = $event->materials()->get();
    $preTestSubmission = $submissions->get('pre_test');
    $postTestSubmission = $submissions->get('post_test');
    $evaluationSubmission = $submissions->get('evaluation');
    $attendanceDates = $event->attendanceDates();
    $attendanceDayCount = max(1, $attendanceDates->count());
    $attendanceCount = $participant->attendanceCount();
    $scheduleLabel = $event->mulai_at?->translatedFormat('d M Y H:i');

    if ($event->mulai_at && $event->selesai_at) {
        $scheduleLabel = $event->mulai_at->isSameDay($event->selesai_at)
            ? $event->mulai_at->translatedFormat('d M Y H:i') . ' - ' . $event->selesai_at->translatedFormat('H:i')
            : $event->mulai_at->translatedFormat('d M Y H:i') . ' s.d. ' . $event->selesai_at->translatedFormat('d M Y H:i');
    }

    $eventAssignmentLetterUrl = $event->surat_tugas_event_path
        ? route('unit.events.event-assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug])
        : null;
    $eventAssignmentLetterDownloadUrl = $event->surat_tugas_event_path
        ? route('unit.events.event-assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug, 'download' => 1])
        : null;

    $suratTugasExtension = strtolower(pathinfo((string) $participant->surat_tugas_path, PATHINFO_EXTENSION));
    $suratTugasIsPdf = $suratTugasExtension === 'pdf';
    $suratTugasIsImage = in_array($suratTugasExtension, ['jpg', 'jpeg', 'png', 'webp'], true);
@endphp

<section class="bg-slate-950 py-12 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-5 flex flex-wrap gap-2 text-sm text-white/70">
            <a href="{{ route('home') }}" class="hover:text-white">Beranda</a>
            <span>/</span>
            <a href="{{ route('unit.show', $page->slug) }}" class="hover:text-white">{{ $page->judul }}</a>
            <span>/</span>
            <a href="{{ route('unit.events.show', [$page->slug, $event->slug]) }}" class="hover:text-white">{{ $event->judul }}</a>
            <span>/</span>
            <span class="text-white">Akses Peserta</span>
        </nav>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1.2fr,0.8fr]">
            <div>
                <div class="flex flex-wrap gap-2 text-[11px] font-bold uppercase tracking-wider text-white/80">
                    <span class="rounded-full bg-white/10 px-3 py-1">{{ $participant->peran_label }}</span>
                    <span class="rounded-full bg-white/10 px-3 py-1">{{ $participant->kode_registrasi }}</span>
                </div>
                <h1 class="mt-4 text-3xl font-black sm:text-4xl">Halo, {{ $participant->nama }}</h1>
                @if($event->jenis_kegiatan === 'wisuda')
                    <p class="mt-3 max-w-3xl text-base leading-7 text-white/75">Gunakan halaman ini untuk melihat undangan wisuda digital Anda, mengakses link penting, dan melakukan konfirmasi kehadiran.</p>
                @else
                    <p class="mt-3 max-w-3xl text-base leading-7 text-white/75">Gunakan halaman ini untuk mengakses kehadiran, pre test, post test, evaluasi, materi, Zoom, grup WhatsApp, surat tugas / SK, dan sertifikat event.</p>
                @endif
            </div>

            @if($event->jenis_kegiatan === 'wisuda')
                @php
                    $qrCodeService = new \App\Support\QrCodeDataUri();
                    $scanUrl = route('admin.unit-events.participants.scan-direct', [
                        'unitPage' => $page->slug,
                        'unitEvent' => $event->slug,
                        'token' => $participant->access_token,
                    ]);
                    $qrCodeUri = $qrCodeService->make($scanUrl);
                @endphp
                <div class="rounded-3xl border-2 border-amber-500 bg-white shadow-2xl overflow-hidden">
                    <!-- Pure Invitation Header Image -->
                    <img src="{{ asset('images/bg-undangan.jpg') }}?v={{ filemtime(public_path('images/bg-undangan.jpg')) }}" alt="Desain Undangan" class="w-full h-auto object-cover border-b border-amber-500/20">

                    <!-- Personal Digital Ticket Details -->
                    <div class="p-6 bg-gradient-to-b from-white to-amber-50/20">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <!-- Left: Guest Information -->
                            <div class="space-y-4 flex-1">
                                <div class="inline-flex items-center gap-2 rounded-full px-3.5 py-1 text-xs border" style="background-color: #0f766e !important; color: #ffffff !important; border-color: #0d9488 !important; font-weight: 800 !important; display: inline-flex !important; align-items: center;">
                                    <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                                    Undangan Digital Resmi
                                </div>
                                
                                <div>
                                    <h3 class="text-xs text-slate-500 uppercase tracking-wider font-extrabold">Tamu Undangan</h3>
                                    <p class="text-2xl font-black text-slate-900 mt-1 leading-tight">{{ $participant->nama }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2 pt-2">
                                    <span class="text-xs font-mono bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 font-bold">ID: {{ $participant->kode_registrasi }}</span>
                                    @if(!empty($participant->meta['nomor_kursi']))
                                        <span class="text-xs font-sans bg-amber-100 px-3 py-1.5 rounded-lg border border-amber-200 text-amber-800 font-extrabold shadow-sm">Nomor Kursi: {{ $participant->meta['nomor_kursi'] }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Large QR Code Panel -->
                            <div class="flex flex-col items-center justify-center p-6 bg-white border border-amber-200/60 rounded-3xl shadow-sm w-full md:w-auto md:min-w-[260px]">
                                <div class="p-2 bg-white">
                                    <img src="{{ $qrCodeUri }}" alt="QR Code" class="w-56 h-56 md:w-60 md:h-60 mx-auto">
                                </div>
                                <span class="text-sm text-slate-950 uppercase tracking-wider font-black mt-3 bg-amber-500 px-6 py-1.5 rounded-full shadow-sm" style="background-color: #f59e0b !important; color: #0f172a !important;">Scan Kehadiran</span>
                            </div>
                        </div>

                        <!-- Ticket Footer Instructions -->
                        <div class="mt-6 border-t border-dashed border-slate-200 pt-4 text-xs text-slate-500 leading-relaxed">
                            <p class="font-semibold text-slate-700">📌 Petunjuk Masuk Area Wisuda:</p>
                            <ul class="list-disc pl-5 mt-1 space-y-1">
                                <li class="font-bold text-amber-800">Satu undangan ini hanya berlaku untuk 1 (satu) orang.</li>
                                <li>Tunjukkan QR Code di atas kepada panitia penerima tamu di pintu masuk.</li>
                                <li>Panitia akan memindai QR Code untuk mengonfirmasi kehadiran Anda.</li>
                                <li>Pastikan kecerahan layar HP Anda cukup tinggi saat proses pemindaian.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Daftar Link Undangan Langsung -->
                <div class="mt-8">
                    <h2 class="text-xl font-extrabold text-white mb-4">Daftar Link Undangan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Cetak Undangan Fisik -->
                        <a href="{{ route('unit.events.invitation.print-single', [$page->slug, $event->slug, $participant->access_token]) }}" target="_blank" class="flex items-center justify-between rounded-2xl bg-white/10 p-4 border border-white/10 hover:bg-white/15 transition-all text-white">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🖨️</span>
                                <div class="text-left">
                                    <p class="font-bold text-sm">Cetak / Unduh Undangan Fisik</p>
                                    <p class="text-xs text-white/60">Simpan kartu undangan sebagai PDF atau cetak</p>
                                </div>
                            </div>
                            <span class="text-lg font-bold text-amber-400">↗</span>
                        </a>

                        <!-- Tayangan Slide Wisudawan -->
                        <a href="{{ route('unit.events.slides', [$page->slug, $event->slug, 'nim' => $participant->identity_number]) }}" target="_blank" class="flex items-center justify-between rounded-2xl bg-gradient-to-r from-emerald-600/25 to-teal-600/25 p-4 border border-emerald-500/40 hover:bg-emerald-600/35 transition-all text-white shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🎓</span>
                                <div class="text-left">
                                    <p class="font-bold text-sm">Tayangan Slide Wisudawan</p>
                                    <p class="text-xs text-white/60">Buka slide resmi saat pemanggilan di panggung</p>
                                </div>
                            </div>
                            <span class="text-lg font-bold text-emerald-400">↗</span>
                        </a>

                        <!-- Lokasi Google Maps -->
                        @php
                            $mapsQuery = $event->link_lokasi_maps ?: ($event->lokasi ? $event->lokasi . ', Bandung' : 'Gedung Sasana Budaya Ganesha SABUGA Bandung');
                            $mapsUrl = str_starts_with($mapsQuery, 'http://') || str_starts_with($mapsQuery, 'https://') 
                                ? $mapsQuery 
                                : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($mapsQuery);
                        @endphp
                        <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-2xl bg-white/10 p-4 border border-white/10 hover:bg-white/15 transition-all text-white">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📍</span>
                                <div class="text-left">
                                    <p class="font-bold text-sm">Lokasi Acara (Google Maps)</p>
                                    <p class="text-xs text-white/60">Buka navigasi peta ke lokasi {{ $event->lokasi ?: 'Gedung SABUGA Bandung' }}</p>
                                </div>
                            </div>
                            <span class="text-lg font-bold text-amber-400">↗</span>
                        </a>

                        @if($event->zoom_link)
                            <!-- Zoom Link -->
                            <a href="{{ $event->zoom_link }}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-2xl bg-white/10 p-4 border border-white/10 hover:bg-white/15 transition-all text-white">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">🎥</span>
                                    <div class="text-left">
                                        <p class="font-bold text-sm">Gabung Zoom Meeting</p>
                                        <p class="text-xs text-white/60">Ikuti siaran langsung wisuda secara online</p>
                                    </div>
                                </div>
                                <span class="text-lg font-bold text-amber-400">↗</span>
                            </a>
                        @endif

                        @if($event->whatsapp_group_link)
                            <!-- WhatsApp Group -->
                            <a href="{{ $event->whatsapp_group_link }}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-2xl bg-white/10 p-4 border border-white/10 hover:bg-white/15 transition-all text-white">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">💬</span>
                                    <div class="text-left">
                                        <p class="font-bold text-sm">Gabung Grup WhatsApp</p>
                                        <p class="text-xs text-white/60">Grup koordinasi resmi panitia dan undangan</p>
                                    </div>
                                </div>
                                <span class="text-lg font-bold text-amber-400">↗</span>
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="rounded-3xl border border-white/10 bg-white/10 p-6 backdrop-blur">
                    <h2 class="text-lg font-extrabold">Ringkasan Akses</h2>
                    <dl class="mt-4 space-y-3 text-sm text-white/80">
                        <div>
                            <p class="font-semibold text-white">Event</p>
                            <p>{{ $event->judul }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-white">Jadwal</p>
                            <p>{{ $scheduleLabel ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-white">Kehadiran</p>
                            <p>{{ $attendanceCount > 0 ? $attendanceCount . '/' . $attendanceDayCount . ' hari tercatat' : 'Belum tercatat' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-white">Sertifikat</p>
                            <p>{{ $participant->sertifikat_path ? 'Sudah tersedia' : 'Belum tersedia' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-white">Surat Tugas Kegiatan</p>
                            <p>{{ $event->surat_tugas_event_path ? 'Sudah tersedia' : 'Belum tersedia' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-white">Surat Tugas / SK</p>
                            <p>{{ $participant->surat_tugas_path ? 'Sudah tersedia' : 'Belum tersedia' }}</p>
                        </div>
                    </dl>
                </div>
            @endif
        </div>
    </div>
</section>

<section class="bg-gray-50 py-12 dark:bg-gray-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.1fr,0.9fr]">
            <div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @if($event->daftar_hadir_aktif && $event->jenis_kegiatan !== 'wisuda')
                        <a href="{{ route('unit.events.attendance.show', [$page->slug, $event->slug, $participant->access_token]) }}" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-3xl">📝</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Daftar Hadir</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $attendanceCount > 0 ? $attendanceCount . '/' . $attendanceDayCount . ' hari sudah ditandatangani' : 'Isi kehadiran Anda per hari' }}</p>
                        </a>
                    @endif

                    @if($event->pre_test_aktif && $event->hasQuestionsFor('pre_test'))
                        <a href="{{ route('unit.events.pre-test.show', [$page->slug, $event->slug, $participant->access_token]) }}" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-3xl">📘</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Pre Test</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $preTestSubmission ? 'Sudah dikirim' : 'Belum dikerjakan' }}</p>
                        </a>
                    @endif

                    @if($event->post_test_aktif && $event->hasQuestionsFor('post_test'))
                        <a href="{{ route('unit.events.post-test.show', [$page->slug, $event->slug, $participant->access_token]) }}" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-3xl">📗</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Post Test</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $postTestSubmission ? 'Sudah dikirim' : 'Belum dikerjakan' }}</p>
                        </a>
                    @endif

                    @if($event->evaluasi_aktif && $event->hasQuestionsFor('evaluation'))
                        <a href="{{ route('unit.events.evaluation.show', [$page->slug, $event->slug, $participant->access_token]) }}" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-3xl">⭐</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Evaluasi</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $evaluationSubmission ? 'Sudah dikirim' : 'Belum diisi' }}</p>
                        </a>
                    @endif

                    @if($event->rubrik_aktif)
                        <a href="{{ route('unit.events.rubrik.show', [$page->slug, $event->slug, $participant->access_token]) }}" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900 block">
                            <p class="text-3xl">📊</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Rubrik Penilaian</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lihat instrumen penilaian, sub-kriteria, & simulasi skor.</p>
                        </a>
                    @endif

                    @if($participant->sertifikat_path)
                        <a href="{{ route('unit.events.certificate.download', [$page->slug, $event->slug, $participant->access_token]) }}" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-3xl">🏅</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Unduh Sertifikat</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">File sertifikat Anda sudah tersedia.</p>
                        </a>
                    @endif

                    @if($eventAssignmentLetterUrl)
                        <a href="{{ $eventAssignmentLetterUrl }}" target="_blank" rel="noopener" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-3xl">📑</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Surat Tugas Kegiatan</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dokumen kolektif kegiatan dari pimpinan atau direktur tersedia untuk Anda.</p>
                        </a>
                    @endif

                    @if($participant->surat_tugas_path)
                        <a href="{{ route('unit.events.assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug, 'token' => $participant->access_token]) }}" target="_blank" rel="noopener" class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-3xl">📄</p>
                            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Lihat Surat Tugas / SK</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Panitia sudah mengunggah surat tugas atau SK untuk peserta ini.</p>
                        </a>
                    @endif
                </div>

                @include('unit.events._materials', ['materials' => $materials])

                @if($eventAssignmentLetterUrl)
                    <section class="mt-8 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Surat Tugas Kegiatan</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Surat tugas kolektif event tersedia dan bisa Anda buka atau unduh dari portal peserta.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ $eventAssignmentLetterUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">Buka File</a>
                                <a href="{{ $eventAssignmentLetterDownloadUrl }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Unduh</a>
                            </div>
                        </div>
                    </section>
                @endif

                @if($participant->surat_tugas_path)
                    <section class="mt-8 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Surat Tugas / SK</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dokumen ini disiapkan panitia untuk peserta event dan bisa dibuka penuh atau diunduh kapan saja.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('unit.events.assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug, 'token' => $participant->access_token]) }}" target="_blank" rel="noopener" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">Buka File</a>
                                <a href="{{ route('unit.events.assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug, 'token' => $participant->access_token, 'download' => 1]) }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Unduh</a>
                            </div>
                        </div>

                        @if($suratTugasIsPdf)
                            <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
                                <iframe src="{{ route('unit.events.assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug, 'token' => $participant->access_token]) }}" class="h-[720px] w-full bg-gray-50 dark:bg-gray-950" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                            </div>
                        @elseif($suratTugasIsImage)
                            <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                                <img src="{{ route('unit.events.assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug, 'token' => $participant->access_token]) }}" alt="Surat tugas atau SK untuk {{ $participant->nama }}" class="h-auto max-h-[900px] w-full object-contain">
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-300">
                                Preview inline belum tersedia untuk format file ini. Gunakan tombol buka file atau unduh di atas.
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            <div class="space-y-6">
                <aside class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Info Peserta</h2>
                    <dl class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Nama</dt>
                            <dd>{{ $participant->nama }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Email</dt>
                            <dd>{{ $participant->email ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Peran</dt>
                            <dd>{{ $participant->peran_label }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Progress Kehadiran</dt>
                            <dd class="mt-2 flex flex-wrap gap-2">
                                @foreach($attendanceDates as $attendanceDate)
                                    @php $dayRecord = $participant->attendanceRecordFor($attendanceDate); @endphp
                                    <span class="rounded-full px-3 py-1 text-[11px] font-bold {{ $dayRecord ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $attendanceDate->translatedFormat('d M') }} • {{ $dayRecord ? 'Hadir' : 'Belum' }}</span>
                                @endforeach
                            </dd>
                        </div>
                        @if($event->jenis_kegiatan !== 'wisuda')
                            <div>
                                <dt class="font-semibold text-gray-900 dark:text-white">Nilai Pre Test</dt>
                                <dd>{{ $participant->pre_test_nilai !== null ? number_format($participant->pre_test_nilai, 2) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-gray-900 dark:text-white">Nilai Post Test</dt>
                                <dd>{{ $participant->post_test_nilai !== null ? number_format($participant->post_test_nilai, 2) : '—' }}</dd>
                            </div>
                        @endif
                    </dl>
                </aside>

                @if($event->zoom_link || $event->whatsapp_group_link || $event->narahubung_nama)
                    <aside class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Akses Pelaksanaan</h2>
                        <div class="mt-4 space-y-3">
                            @if($event->zoom_link)
                                <a href="{{ $event->zoom_link }}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-2xl bg-brand-50 px-4 py-3 text-sm font-bold text-brand-700 transition-colors hover:bg-brand-100 dark:bg-brand-900/20 dark:text-brand-300">
                                    <span>Gabung Zoom / Meeting</span>
                                    <span>↗</span>
                                </a>
                            @endif
                            @if($event->whatsapp_group_link)
                                <a href="{{ $event->whatsapp_group_link }}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 transition-colors hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300">
                                    <span>Gabung Grup WhatsApp</span>
                                    <span>↗</span>
                                </a>
                            @endif
                            @if($event->narahubung_nama || $event->narahubung_whatsapp)
                                <div class="rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                                    <p class="font-semibold text-gray-900 dark:text-white">Narahubung</p>
                                    <p class="mt-1">{{ $event->narahubung_nama ?: 'Panitia Event' }}</p>
                                    @if($event->narahubung_whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $event->narahubung_whatsapp) }}" target="_blank" class="mt-2 inline-flex text-brand-600 hover:underline dark:text-brand-300">{{ $event->narahubung_whatsapp }}</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </aside>
                @endif

                @if($event->maps_embed)
                    <aside class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mb-4">Lokasi Acara</h2>
                        <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 aspect-video w-full">
                            <style>
                                .map-responsive iframe {
                                    width: 100% !important;
                                    height: 100% !important;
                                    border: 0 !important;
                                }
                            </style>
                            <div class="map-responsive w-full h-full">
                                {!! $event->maps_embed !!}
                            </div>
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
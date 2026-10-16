@extends('layouts.app')
@section('title', $event->judul)
@section('og_title', $event->judul . ' - ' . $page->judul)
@section('description', $event->ringkasan ?: ($event->judul . ' diselenggarakan oleh ' . $page->judul . ' Poltekkes Kemenkes Bandung.'))
@section('og_image', $event->resolvedFlyerImageUrl())

@section('content')
@php
    $participantCount = $event->participants()->where('peran', 'participant')->count();
    $speakers = $event->participants()->where('peran', 'speaker')->orderBy('nama')->get();
    $materials = $materials ?? collect();
    $mapsEmbed = trim((string) $event->maps_embed);
    $hasIframe = str_contains(strtolower($mapsEmbed), '<iframe');
    $mapsUrl = filter_var($mapsEmbed, FILTER_VALIDATE_URL) ? $mapsEmbed : null;
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $authenticatedParticipant = $authenticatedParticipant ?? null;
    $loginUrl = route('unit.events.login.show', [$page->slug, $event->slug]);
    $registrationBypassed = $event->shouldBypassPublicRegistration();
    $directAttendanceUrl = $authenticatedParticipant
        ? route('unit.events.attendance.direct', [
            $page->slug,
            $event->slug,
            'kode_registrasi' => $authenticatedParticipant->participant_login_username,
            'portal' => $authenticatedParticipant->linked_access_portal,
        ])
        : null;
    $mahasiswaAttendanceLoginUrl = route('unit.events.attendance.direct', [$page->slug, $event->slug, 'portal' => 'mahasiswa']);
    $appAttendanceLoginUrl = route('unit.events.attendance.direct', [$page->slug, $event->slug, 'portal' => 'pegawai']);
    $eventAssignmentLetterUrl = $event->surat_tugas_event_path
        ? route('unit.events.event-assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug])
        : null;
    $eventAssignmentLetterDownloadUrl = $event->surat_tugas_event_path
        ? route('unit.events.event-assignment-letter.show', ['unitSlug' => $page->slug, 'eventSlug' => $event->slug, 'download' => 1])
        : null;
    $eventAssignmentLetterExtension = strtolower(pathinfo((string) $event->surat_tugas_event_path, PATHINFO_EXTENSION));
    $eventAssignmentLetterIsPdf = $eventAssignmentLetterExtension === 'pdf';
    $eventAssignmentLetterIsImage = in_array($eventAssignmentLetterExtension, ['jpg', 'jpeg', 'png', 'webp'], true);
@endphp

@if($event->status === 'draft')
    <div class="bg-amber-500 text-slate-950 px-4 py-2.5 text-center text-xs font-bold shadow-md relative z-20 flex items-center justify-center gap-2">
        <span>⚠️</span>
        <span>Mode Pratinjau Admin: Event ini berstatus <strong>DRAFT</strong> (belum tayang untuk pengunjung umum). Ubah status menjadi <strong>Dipublikasikan</strong> di panel admin untuk menayangkan ke publik.</span>
    </div>
@endif

<section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-gradient-to-br from-brand-900/40 via-slate-900 to-emerald-950/60"></div>
    <div class="relative mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        
        {{-- Breadcrumbs & Top Action Bar --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 border-b border-white/10 pb-4">
            <nav class="flex flex-wrap items-center gap-2 text-xs sm:text-sm text-white/70">
                <a href="{{ route('home') }}" class="hover:text-white transition">Beranda</a>
                <span>/</span>
                <a href="{{ route('unit.show', $page->slug) }}" class="hover:text-white transition">{{ $page->judul }}</a>
                <span>/</span>
                <span class="text-teal-300 font-semibold truncate max-w-xs sm:max-w-md">{{ $event->judul }}</span>
            </nav>

            {{-- Quick Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                @if($event->daftar_hadir_aktif && $event->allow_direct_attendance)
                    <a href="{{ route('unit.events.attendance.direct.show', [$page->slug, $event->slug]) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white shadow-md hover:bg-emerald-500 transition">
                        <span>✍️</span> Isi Presensi Langsung
                    </a>
                @endif
                @if($authenticatedParticipant && $event->daftar_hadir_aktif)
                    <a href="{{ $directAttendanceUrl }}" class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-black text-white shadow-md hover:bg-teal-500 transition">
                        <span>✍️</span> Presensi Peserta
                    </a>
                @endif
                <a href="{{ route('unit.events.flyer', [$page->slug, $event->slug]) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-400/50 bg-amber-500/20 px-3.5 py-2 text-xs font-bold text-amber-300 shadow hover:bg-amber-500/30 transition">
                    <span>🖼️</span> Flyer Resmi & Unduh PDF
                </a>
                <a href="{{ route('unit.events.slides', [$page->slug, $event->slug]) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-400/50 bg-gradient-to-r from-emerald-600/30 to-teal-600/30 px-3.5 py-2 text-xs font-bold text-emerald-300 shadow hover:from-emerald-600/40 hover:to-teal-600/40 transition">
                    <span>🎓</span> Tayangan Slide Wisudawan
                </a>
                @php
                    $eventPublicUrl = route('unit.events.show', [$page->slug, $event->slug]);
                    $waText = $eventPublicUrl . "\n\n" . (filled($event->deskripsi) ? trim((string) $event->deskripsi) : "Halo! Ikuti kegiatan resmi " . $event->judul . " oleh " . $page->judul) . "\n\n🔗 Akses Informasi & Presensi: " . $eventPublicUrl;
                    $waUrl = 'https://api.whatsapp.com/send?text=' . rawurlencode($waText);
                @endphp
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-[#25D366] px-3.5 py-2 text-xs font-bold text-slate-950 shadow hover:bg-[#20bd5a] transition">
                    <span>💬</span> Bagikan WA
                </a>
                @if(!$authenticatedParticipant)
                    <a href="{{ $loginUrl }}" class="inline-flex items-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-bold text-white shadow hover:bg-white/15 transition">
                        <span>🔐</span> Masuk
                    </a>
                @endif
            </div>
        </div>

        {{-- Center Focal Presentation: Identical Finished Event Flyer Poster --}}
        <div class="mx-auto max-w-[780px] w-full">
            @if($event->flyer_path)
                <div class="overflow-hidden rounded-[2rem] border border-white/15 bg-white/10 shadow-2xl backdrop-blur">
                    <img src="{{ \App\Support\MediaStorage::url($event->flyer_path) }}" alt="{{ $event->judul }}" class="h-full w-full object-cover">
                    <div class="p-4 bg-slate-900/90 border-t border-white/10 flex items-center justify-between">
                        <span class="text-xs font-semibold text-white/70">Flyer Resmi Kegiatan</span>
                        <a href="{{ route('unit.events.flyer', [$page->slug, $event->slug]) }}" target="_blank" class="text-xs font-bold text-amber-300 hover:underline flex items-center gap-1">
                            <span>✦</span> Mode Cetak & Unduh PDF →
                        </a>
                    </div>
                </div>
            @else
                <div class="relative">
                    <x-event-flyer 
                        :event="$event" 
                        :speakers="$speakers" 
                        :theme="$theme" 
                        :benefits="$benefits" 
                        :qrCodeDataUri="$qrCodeDataUri"
                        flyerId="hero-flyer-preview"
                    />
                </div>
            @endif
        </div>

    </div>
</section>

<section class="bg-gray-50 py-12 dark:bg-gray-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.4fr,0.8fr]">
            <div>
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Tentang Event</h2>
                    @if($event->deskripsi)
                        <div class="prose mt-4 max-w-none text-gray-600 dark:prose-invert dark:text-gray-300">{!! nl2br(e($event->deskripsi)) !!}</div>
                    @else
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Deskripsi lengkap acara belum ditambahkan.</p>
                    @endif
                </div>

                @if($eventAssignmentLetterUrl)
                    <section class="mt-8 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Surat Tugas Kegiatan</h2>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Dokumen ini merupakan surat tugas kolektif untuk kegiatan yang ditetapkan pimpinan atau direktur Poltekkes Bandung.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ $eventAssignmentLetterUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">Buka File</a>
                                <a href="{{ $eventAssignmentLetterDownloadUrl }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Unduh</a>
                            </div>
                        </div>

                        @if($eventAssignmentLetterIsPdf)
                            <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
                                <iframe src="{{ $eventAssignmentLetterUrl }}" class="h-[720px] w-full bg-gray-50 dark:bg-gray-950" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                            </div>
                        @elseif($eventAssignmentLetterIsImage)
                            <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                                <img src="{{ $eventAssignmentLetterUrl }}" alt="Surat tugas kegiatan {{ $event->judul }}" class="h-auto max-h-[900px] w-full object-contain">
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-300">
                                Preview inline belum tersedia untuk format file ini. Gunakan tombol buka file atau unduh di atas.
                            </div>
                        @endif
                    </section>
                @endif

                @if($speakers->isNotEmpty())
                    <div class="mt-8 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Narasumber</h2>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            @foreach($speakers as $speaker)
                                <article class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800/60">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $speaker->nama }}</p>
                                    @if($speaker->jabatan || $speaker->instansi)
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $speaker->jabatan }}{{ $speaker->jabatan && $speaker->instansi ? ' ' : '' }}{{ $speaker->instansi }}</p>
                                    @endif
                                    @if($speaker->catatan)
                                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $speaker->catatan }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                @include('unit.events._materials', ['materials' => $materials])

                {{-- Lokasi / Akses Virtual Event --}}
                @php
                    $isOnline = $event->metode === 'online';
                    $isHybrid = $event->metode === 'hybrid';
                    $hasLocation = filled($event->lokasi) || filled($event->alamat_detail) || filled($mapsEmbed) || filled($event->zoom_link);

                    // Check if mapsEmbed is a direct iframe embed
                    $hasDirectIframe = str_contains(strtolower($mapsEmbed), '<iframe');
                    
                    // If it's a URL or share link
                    $mapsExternalUrl = filter_var($mapsEmbed, FILTER_VALIDATE_URL) ? $mapsEmbed : null;
                    $isGoogleEmbedUrl = $mapsExternalUrl && (str_contains($mapsExternalUrl, 'google.com/maps/embed') || str_contains($mapsExternalUrl, 'output=embed'));
                    
                    // Construct fallback interactive embed if location text or address is provided
                    $mapsEmbedUrl = null;
                    if ($isGoogleEmbedUrl) {
                        $mapsEmbedUrl = $mapsExternalUrl;
                    } elseif (!$hasDirectIframe && !$isOnline && (filled($event->alamat_detail) || filled($event->lokasi))) {
                        $query = urlencode($event->alamat_detail ?: $event->lokasi . ' Bandung');
                        $mapsEmbedUrl = "https://maps.google.com/maps?q={$query}&t=&z=15&ie=UTF8&iwloc=&output=embed";
                    }
                @endphp

                @if($hasLocation)
                    <section class="mt-10 overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                                        {{ $isOnline ? '🌐 Akses & Lokasi Pelaksanaan Event' : '📍 Lokasi Event' }}
                                    </h2>
                                    <p class="mt-1 text-sm font-semibold text-teal-600 dark:text-teal-400">
                                        {{ $event->resolvedLocationName() }}
                                    </p>
                                </div>
                                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                                    {{ \App\Models\UnitEvent::$metodeLabels[$event->metode] ?? $event->metode }}
                                </span>
                            </div>

                            @if($event->alamat_detail)
                                <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    {{ $event->alamat_detail }}
                                </p>
                            @endif

                            {{-- Online Meeting Details for Daring / Hybrid --}}
                            @if(($isOnline || $isHybrid) && ($event->zoom_link || $event->whatsapp_group_link || $event->narahubung_nama))
                                <div class="mt-5 rounded-2xl bg-gradient-to-r from-teal-50 to-blue-50 p-4 dark:from-teal-950/40 dark:to-blue-950/40 border border-teal-100 dark:border-teal-900/50 space-y-3">
                                    <p class="text-xs font-black uppercase tracking-wider text-teal-800 dark:text-teal-300">
                                        💻 Akses Platform Daring / Video Conference:
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        @if($event->zoom_link)
                                            <a href="{{ $event->zoom_link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-blue-700 transition-colors">
                                                <span>🎥</span> Buka Tautan Zoom / Video Meeting ↗
                                            </a>
                                        @endif
                                        @if($event->whatsapp_group_link)
                                            <a href="{{ $event->whatsapp_group_link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-emerald-700 transition-colors">
                                                <span>💬</span> Grup WhatsApp Kegiatan ↗
                                            </a>
                                        @endif
                                    </div>
                                    @if($event->narahubung_nama)
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            Narahubung / PIC: <strong>{{ $event->narahubung_nama }}</strong>
                                            @if($event->narahubung_whatsapp)
                                                (WA: <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $event->narahubung_whatsapp) }}" target="_blank" class="text-teal-600 dark:text-teal-400 hover:underline font-semibold">{{ $event->narahubung_whatsapp }}</a>)
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            @endif

                            {{-- External Map Link Button --}}
                            @if($mapsExternalUrl)
                                <div class="mt-4">
                                    <a href="{{ $mapsExternalUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 shadow-sm hover:bg-gray-50 hover:text-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        <span>🗺️</span> Buka Peta di Google Maps ↗
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Render Map Embed only when valid embed is available --}}
                        @if($hasDirectIframe)
                            <div class="border-t border-gray-100 dark:border-gray-800">
                                {!! $mapsEmbed !!}
                            </div>
                        @elseif($mapsEmbedUrl)
                            <div class="border-t border-gray-100 dark:border-gray-800">
                                <iframe src="{{ $mapsEmbedUrl }}" class="h-[360px] w-full" style="border:0;" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            <div class="space-y-6">
                <aside id="{{ $registrationBypassed ? 'akses-peserta' : 'registrasi' }}" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">{{ $registrationBypassed ? 'Akses Peserta & Daftar Hadir' : 'Registrasi Peserta' }}</h2>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                @if($registrationBypassed)
                                    Peserta event ini sudah ditentukan panitia. Registrasi publik otomatis dibypass agar peserta bisa langsung masuk dan mengisi daftar hadir.
                                @else
                                    Isi formulir di bawah ini untuk mendapatkan tautan akses peserta, daftar hadir, tes, evaluasi, materi, dan sertifikat.
                                @endif
                            </p>
                        </div>
                        <a href="{{ $loginUrl }}" class="inline-flex rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-xs font-bold text-brand-700 transition-colors hover:bg-brand-100 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300 dark:hover:bg-brand-500/20">Masuk Peserta</a>
                    </div>

                    <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                        <p class="font-semibold text-gray-900 dark:text-white">Sudah terdaftar atau menerima kredensial dari panitia?</p>
                        <p class="mt-2">Gunakan menu <a href="{{ $loginUrl }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">Masuk Peserta</a> atau scan QR dari panitia untuk membuka portal peserta dan menandatangani daftar hadir.</p>
                    </div>

                    @if($authenticatedParticipant)
                        <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-4 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">
                            <p class="font-semibold">Sesi login Anda cocok dengan peserta {{ $authenticatedParticipant->nama }}.</p>
                            <p class="mt-2">Anda bisa langsung membuka portal peserta{{ $event->daftar_hadir_aktif ? ' atau mengisi daftar hadir event ini' : '' }} tanpa login ulang.</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if($event->daftar_hadir_aktif)
                                    <a href="{{ $directAttendanceUrl }}" class="inline-flex rounded-xl bg-green-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-green-500">Isi Daftar Hadir</a>
                                @endif
                                <a href="{{ route('unit.events.access', [$page->slug, $event->slug, $authenticatedParticipant->access_token]) }}" class="inline-flex rounded-xl border border-green-300 bg-white px-4 py-2 text-xs font-bold text-green-700 transition-colors hover:bg-green-100 dark:border-green-700 dark:bg-transparent dark:text-green-200 dark:hover:bg-green-900/20">Buka Portal Peserta</a>
                            </div>
                        </div>
                    @endif

                    @if($registrationBypassed)
                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200">
                            <p class="font-semibold">Form registrasi publik dimatikan otomatis karena daftar peserta sudah diimpor.</p>
                            <p class="mt-2">Jika Anda memakai akun mahasiswa atau pegawai yang sudah ada, login dulu lewat portal yang sesuai. Jika menerima username dan password sementara dari panitia, gunakan tombol Masuk Peserta.</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ $loginUrl }}" class="inline-flex rounded-xl bg-brand-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-brand-500">Masuk Peserta</a>
                                <a href="{{ $mahasiswaAttendanceLoginUrl }}" class="inline-flex rounded-xl border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-amber-800 transition-colors hover:bg-amber-100 dark:border-amber-700 dark:bg-transparent dark:text-amber-200 dark:hover:bg-amber-900/20">Login Mahasiswa & Isi Hadir</a>
                                <a href="{{ $appAttendanceLoginUrl }}" class="inline-flex rounded-xl border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-amber-800 transition-colors hover:bg-amber-100 dark:border-amber-700 dark:bg-transparent dark:text-amber-200 dark:hover:bg-amber-900/20">Login Pegawai</a>
                            </div>
                        </div>
                    @elseif($event->allowsPublicRegistration())
                        @if($viewErrors->any())
                            <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach($viewErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('unit.events.register', [$page->slug, $event->slug]) }}" class="mt-5 space-y-4">
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                                <input type="text" name="nama" value="{{ old('nama') }}" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">No. HP</label>
                                    <input type="text" name="no_hp" value="{{ old('no_hp') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Instansi</label>
                                    <input type="text" name="instansi" value="{{ old('instansi') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Jabatan</label>
                                <input type="text" name="jabatan" value="{{ old('jabatan') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Catatan</label>
                                <textarea name="catatan" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('catatan') }}</textarea>
                            </div>
                            <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">Daftar Sekarang</button>
                        </form>
                    @else
                        <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-500 dark:bg-gray-800/60 dark:text-gray-300">
                            Registrasi tidak sedang dibuka. Jika Anda sudah pernah mendaftar atau menerima kredensial dari panitia, gunakan <a href="{{ $loginUrl }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">Masuk Peserta</a> untuk membuka portal peserta Anda.
                        </div>
                    @endif
                </aside>

                <aside class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Informasi Penting</h2>
                    <dl class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Jadwal</dt>
                            <dd>{{ $event->mulai_at?->translatedFormat('l, d F Y H:i') }}</dd>
                            @if($event->selesai_at)
                                <dd>Selesai {{ $event->selesai_at->translatedFormat('l, d F Y H:i') }}</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Metode</dt>
                            <dd>{{ \App\Models\UnitEvent::$metodeLabels[$event->metode] ?? $event->metode }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-white">Tempat</dt>
                            <dd>{{ $event->lokasi ?: 'Akan diinformasikan' }}</dd>
                            @if($event->alamat_detail)
                                <dd>{{ $event->alamat_detail }}</dd>
                            @endif
                        </div>
                        @if($eventAssignmentLetterUrl)
                            <div>
                                <dt class="font-semibold text-gray-900 dark:text-white">Surat Tugas Kegiatan</dt>
                                <dd>Tersedia untuk peserta kegiatan ini.</dd>
                                <dd><a href="{{ $eventAssignmentLetterUrl }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline dark:text-brand-300">Buka dokumen</a></dd>
                            </div>
                        @endif
                        @if($event->narahubung_nama || $event->narahubung_whatsapp)
                            <div>
                                <dt class="font-semibold text-gray-900 dark:text-white">Narahubung</dt>
                                <dd>{{ $event->narahubung_nama ?: 'Panitia Event' }}</dd>
                                @if($event->narahubung_whatsapp)
                                    <dd><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $event->narahubung_whatsapp) }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-300">{{ $event->narahubung_whatsapp }}</a></dd>
                                @endif
                            </div>
                        @endif
                    </dl>
                </aside>

                {{-- Daftar Hadir & Berita Acara --}}
                <aside class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Daftar Hadir & Berita Acara</h2>
                    <div class="mt-4 space-y-3">
                        @if($event->allow_direct_attendance)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Peserta publik dapat mengisi daftar hadir langsung tanpa registrasi terlebih dahulu.</p>
                                <a href="{{ route('unit.events.attendance.direct.show', [$page->slug, $event->slug]) }}" class="mt-2 inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                    📝 Isi Daftar Hadir Langsung
                                </a>
                            </div>
                        @endif
                        @if($event->berita_acara_form_aktif)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Formulir penandatanganan berita acara tersedia untuk peserta langsung.</p>
                                <a href="{{ route('unit.events.attendance.direct.show', [$page->slug, $event->slug, 'form_type' => 'berita_acara']) }}" class="mt-2 inline-flex items-center rounded-xl border border-brand-300 bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 transition-colors hover:bg-brand-100 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300 dark:hover:bg-brand-500/20">
                                    ✍️ Tanda Tangan Berita Acara
                                </a>
                            </div>
                        @endif
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Generate berita acara otomatis dari data daftar hadir yang sudah tercatat.</p>
                            <a href="{{ route('unit.events.berita-acara', [$page->slug, $event->slug]) }}" target="_blank" class="mt-2 inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                📄 Lihat Berita Acara
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection
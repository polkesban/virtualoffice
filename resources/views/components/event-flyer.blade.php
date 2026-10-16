@props([
    'event',
    'speakers' => null,
    'theme' => null,
    'benefits' => null,
    'qrCodeDataUri' => null,
    'compact' => false,
    'printable' => false,
    'flyerId' => 'event-flyer-card',
])

@php
    $speakers = $speakers ?? $event->speakers()->get();
    $theme = $theme ?? $event->resolvedFlyerThemeConfig();
    $benefits = $benefits ?? $event->resolvedBenefitsList();
    $directorName = $event->resolvedDirectorName();
    $directorTitle = $event->resolvedDirectorTitle();
    $directorPhoto = $event->resolvedDirectorPhotoUrl();
    $page = $event->page;
    $unitName = $page?->judul ?? 'Politeknik Kesehatan Kemenkes Bandung';

    $logoPath = public_path('images/logo.png');
    $logoDataUri = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('images/logo.png');

    if (!$qrCodeDataUri) {
        $qrService = app(\App\Support\QrCodeDataUri::class);
        $qrCodeDataUri = $qrService->make(route('unit.events.show', [$page?->slug ?? 'umum', $event->slug]), 5);
    }
@endphp

@if($compact)
    {{-- Mini Thumbnail Card Mode for Grid Listings --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $theme['bg_gradient'] }} p-4 text-white shadow-md border border-white/10 flex flex-col justify-between aspect-[4/5]">
        {{-- Ambient background decorations --}}
        <div class="pointer-events-none absolute -top-10 -right-10 h-32 w-32 rounded-full bg-teal-500/20 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-amber-500/15 blur-2xl"></div>

        {{-- Mini Header --}}
        <div class="relative z-10 flex items-center justify-between gap-2 border-b border-white/10 pb-2.5">
            <div class="flex items-center">
                <img src="{{ $logoDataUri }}" alt="Logo Poltekkes Bandung" class="h-6 w-auto object-contain drop-shadow">
            </div>
            <span class="rounded-full px-2 py-0.5 text-[8px] font-extrabold uppercase tracking-wider {{ $theme['header_badge'] }} border">
                {{ \App\Models\UnitEvent::$jenisLabels[$event->jenis_kegiatan] ?? 'EVENT' }}
            </span>
        </div>

        {{-- Mini Title --}}
        <div class="relative z-10 my-auto py-2">
            <p class="text-[9px] font-bold uppercase tracking-widest text-amber-400">
                {{ $event->flyer_tagline ?: 'KEGIATAN RESMI' }}
            </p>
            <h4 class="mt-1 text-sm font-black leading-snug line-clamp-2 text-white">
                {{ $event->judul }}
            </h4>
            @if($event->ringkasan)
                <p class="mt-1 text-[10px] text-white/70 line-clamp-2 leading-relaxed">
                    {{ $event->ringkasan }}
                </p>
            @endif
        </div>

        {{-- Mini Keynote & Speakers Strip --}}
        <div class="relative z-10 border-t border-white/10 pt-2 space-y-1.5">
            {{-- Direktur Chip --}}
            <div class="flex items-center gap-2 rounded-xl bg-white/10 px-2.5 py-1.5 backdrop-blur border border-white/10">
                <div class="h-6 w-6 shrink-0 overflow-hidden rounded-full border border-amber-400 bg-teal-800/80 flex items-center justify-center text-[10px]">
                    @if($directorPhoto)
                        <img src="{{ $directorPhoto }}" alt="{{ $directorName }}" class="h-full w-full object-cover">
                    @else
                        <span>🏛️</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[7.5px] font-extrabold uppercase tracking-wider text-amber-300">Sambutan / Keynote</p>
                    <p class="text-[9px] font-bold truncate text-white">{{ $directorName }}</p>
                </div>
            </div>

            {{-- Date & Info --}}
            <div class="flex items-center justify-between text-[8.5px] text-white/80 pt-0.5">
                <span class="flex items-center gap-1">
                    <span>📅</span> {{ $event->mulai_at?->translatedFormat('d M Y') }}
                </span>
                <span class="rounded bg-teal-500/20 px-1.5 py-0.5 font-semibold text-teal-300 border border-teal-500/30">
                    {{ \App\Models\UnitEvent::$metodeLabels[$event->metode] ?? 'DARING' }}
                </span>
            </div>
        </div>
    </div>

@else

    {{-- Full High-Resolution Official Event Flyer --}}
    <div id="{{ $flyerId }}" class="relative mx-auto w-full max-w-[760px] overflow-hidden rounded-[2.5rem] bg-gradient-to-br {{ $theme['bg_gradient'] }} p-6 sm:p-10 text-white shadow-2xl border-2 border-white/15" style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 40px {{ $theme['glow_color'] }}; font-family: 'Inter', system-ui, -apple-system, sans-serif;">
        
        {{-- Decorative Modern Mesh Grid & Lighting --}}
        <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-teal-500/15 blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/2 -left-24 h-96 w-96 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 right-1/4 h-80 w-80 rounded-full bg-amber-500/15 blur-3xl"></div>
        
        {{-- Subtle Tech / Academic Background Pattern --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.035]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"></div>

        {{-- Top Official Institutional Bar --}}
        <header class="relative z-10 flex flex-col gap-4 border-b border-white/15 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center">
                <img src="{{ $logoDataUri }}" alt="Kemenkes Poltekkes Bandung" class="h-9 sm:h-12 w-auto object-contain drop-shadow">
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-[10px] font-extrabold uppercase tracking-wider {{ $theme['header_badge'] }} border shadow-sm backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                    {{ \App\Models\UnitEvent::$jenisLabels[$event->jenis_kegiatan] ?? $event->jenis_kegiatan }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-white/90 border border-white/15 backdrop-blur">
                    {{ \App\Models\UnitEvent::$metodeLabels[$event->metode] ?? $event->metode }}
                </span>
            </div>
        </header>

        {{-- Headline & Main Event Title --}}
        <section class="relative z-10 mt-6 text-center sm:mt-8">
            <h1 class="text-xl font-black tracking-tight text-white sm:text-2xl lg:text-[32px] lg:leading-[1.25] max-w-2xl mx-auto drop-shadow-md">
                @php
                    $formattedTitle = e($event->judul);
                    if (str_contains($formattedTitle, "\n")) {
                        $formattedTitle = nl2br($formattedTitle);
                    } elseif (str_contains($formattedTitle, 'Kuliah Bersama Mata Kuliah')) {
                        $formattedTitle = str_replace('Kuliah Bersama Mata Kuliah', 'Kuliah Bersama<br class="hidden sm:inline">Mata Kuliah', $formattedTitle);
                    } elseif (mb_strlen($formattedTitle) > 35 && str_contains($formattedTitle, ' - ')) {
                        $formattedTitle = str_replace(' - ', '<br class="hidden sm:inline">', $formattedTitle);
                    }
                @endphp
                {!! $formattedTitle !!}
            </h1>

            @if($event->ringkasan)
                <p class="mx-auto mt-3.5 max-w-2xl text-xs sm:text-sm leading-relaxed text-white/80 font-normal">
                    {{ $event->ringkasan }}
                </p>
            @endif
        </section>

        {{-- Keynote & Speakers Showcase --}}
        <section class="relative z-10 mt-8 space-y-4">
            {{-- Direktur Section (Opening Speech / Keynote) --}}
            <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-teal-900/50 via-slate-900/60 to-teal-900/50 p-4 sm:p-5 border border-amber-400/40 shadow-lg backdrop-blur relative">
                <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-400/10 blur-xl"></div>
                <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-5 text-center sm:text-left">
                    {{-- Director Photo / Avatar --}}
                    <div class="relative shrink-0">
                        <div class="h-20 w-20 sm:h-24 sm:w-24 overflow-hidden rounded-full border-2 {{ $theme['director_border'] }} bg-gradient-to-br from-teal-800 to-slate-950 p-1 shadow-md ring-4 ring-amber-400/20">
                            @php
                                $directorAvatarFallback = 'https://ui-avatars.com/api/?name=' . urlencode($directorName) . '&background=0d9488&color=ffffff&size=256&bold=true';
                            @endphp
                            <img src="{{ $directorPhoto ?: $directorAvatarFallback }}" alt="{{ $directorName }}" class="h-full w-full rounded-full object-cover" onerror="this.onerror=null; this.src='{{ $directorAvatarFallback }}';">
                        </div>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 rounded-full bg-amber-500 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-950 shadow">
                            Keynote
                        </span>
                    </div>

                    {{-- Director Info --}}
                    <div class="min-w-0 flex-1">
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-amber-400/15 px-2.5 py-0.5 text-[9.5px] font-black uppercase tracking-widest text-amber-300 border border-amber-400/30">
                            <span>🏛️</span> Sambutan / Opening Speech
                        </div>
                        <h4 class="mt-1.5 text-base sm:text-lg font-black text-white leading-tight">
                            {{ $directorName }}
                        </h4>
                        <p class="mt-1 text-xs sm:text-sm font-semibold text-teal-300 leading-snug">
                            {{ $directorTitle ?: 'Direktur' }} Politeknik Kesehatan Kementerian Kesehatan Bandung
                        </p>
                    </div>
                </div>
            </div>

            {{-- Narasumber (Speakers) Grid --}}
            <div class="rounded-2xl bg-white/[0.07] p-4 sm:p-5 border border-white/15 backdrop-blur">
                <div class="flex items-center justify-end gap-2 border-b border-white/10 pb-3 text-right">
                    <h4 class="text-xs sm:text-sm font-black uppercase tracking-wider text-white">
                        Narasumber & Pembicara Pakar
                    </h4>
                    <span class="text-amber-400 text-sm">🎙️</span>
                </div>

                @if($speakers->isNotEmpty())
                    <div class="mt-4 grid grid-cols-1 gap-3.5 {{ $speakers->count() >= 2 ? 'sm:grid-cols-2' : '' }} {{ $speakers->count() >= 3 ? 'lg:grid-cols-3' : '' }}">
                        @foreach($speakers as $speaker)
                            @php
                                $speakerAvatarFallback = 'https://ui-avatars.com/api/?name=' . urlencode($speaker->nama) . '&background=0284c7&color=ffffff&size=256&bold=true';
                            @endphp
                            <div class="flex items-center justify-between gap-4 rounded-xl bg-white/[0.08] p-4 border border-white/15 transition-all hover:bg-white/[0.12] hover:border-teal-400/40">
                                <div class="min-w-0 flex-1 text-right">
                                    <p class="font-black text-sm sm:text-base text-white leading-snug">
                                        {{ $speaker->nama }}
                                    </p>
                                    @if($speaker->jabatan || $speaker->instansi)
                                        <p class="mt-1 text-xs sm:text-sm text-teal-300 font-semibold leading-relaxed">
                                            {{ $speaker->jabatan }}{{ $speaker->jabatan && $speaker->instansi ? ' ' : '' }}{{ $speaker->instansi }}
                                        </p>
                                    @endif
                                    @if($speaker->catatan)
                                        <div class="mt-2 inline-block rounded-lg bg-amber-400/15 px-2.5 py-1.5 border border-amber-400/30 text-right">
                                            <p class="text-[11px] sm:text-xs text-amber-200 font-semibold leading-relaxed">
                                                {!! nl2br(e($speaker->catatan)) !!}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                                {{-- Speaker Avatar/Photo on Right --}}
                                <div class="h-20 w-20 sm:h-24 sm:w-24 shrink-0 overflow-hidden rounded-2xl border-2 border-teal-400/70 bg-teal-950 p-1 shadow-lg ring-2 ring-teal-400/20">
                                    <img src="{{ $speaker->foto_url ?: $speakerAvatarFallback }}" alt="{{ $speaker->nama }}" class="h-full w-full rounded-xl object-cover" onerror="this.onerror=null; this.src='{{ $speakerAvatarFallback }}';">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Default Speaker Highlight when no speaker records yet --}}
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-white/[0.05] p-3.5 border border-white/10">
                            <div class="min-w-0 flex-1 text-right">
                                <p class="text-xs font-bold text-white">Dosen & Praktisi Klinis</p>
                                <p class="text-[10px] text-teal-300">Pakar Bidang Kesehatan Polkesban</p>
                            </div>
                            <div class="h-11 w-11 shrink-0 rounded-xl bg-teal-800/60 border border-teal-400/40 flex items-center justify-center text-lg">
                                👩‍⚕️
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-white/[0.05] p-3.5 border border-white/10">
                            <div class="min-w-0 flex-1 text-right">
                                <p class="text-xs font-bold text-white">Narasumber Tamu Ahli</p>
                                <p class="text-[10px] text-blue-300">Kementerian Kesehatan & Mitra RS</p>
                            </div>
                            <div class="h-11 w-11 shrink-0 rounded-xl bg-blue-800/60 border border-blue-400/40 flex items-center justify-center text-lg">
                                👨‍🏫
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- Schedule, Venue & Time Matrix --}}
        <section class="relative z-10 mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            {{-- Date --}}
            <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 border border-white/15 backdrop-blur min-h-[84px]">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-500/20 text-teal-300 text-lg border border-teal-500/30">
                    🗓️
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[9px] font-extrabold uppercase tracking-wider text-teal-300">HARI & TANGGAL</p>
                    <p class="mt-0.5 text-xs font-black text-white leading-snug break-words">
                        {{ $event->mulai_at?->translatedFormat('l, d F Y') ?: 'Sesuai Jadwal' }}
                    </p>
                </div>
            </div>

            {{-- Time --}}
            <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 border border-white/15 backdrop-blur min-h-[84px]">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-300 text-lg border border-amber-500/30">
                    ⏰
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[9px] font-extrabold uppercase tracking-wider text-amber-300">WAKTU PELAKSANAAN</p>
                    <p class="mt-0.5 text-xs font-black text-white leading-snug break-words">
                        {{ $event->mulai_at?->format('H:i') }} WIB
                        @if($event->selesai_at)
                            s.d. {{ $event->selesai_at->format('H:i') }} WIB
                        @else
                            - Selesai
                        @endif
                    </p>
                </div>
            </div>

            {{-- Venue --}}
            <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 border border-white/15 backdrop-blur min-h-[84px]">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/20 text-blue-300 text-lg border border-blue-500/30">
                    📍
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[9px] font-extrabold uppercase tracking-wider text-blue-300">TEMPAT / MEDIA</p>
                    <p class="mt-0.5 text-xs font-black text-white leading-snug break-words">
                        {{ $event->resolvedLocationName() }}
                    </p>
                    @if($event->zoom_link)
                        <a href="{{ $event->zoom_link }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-teal-300 hover:text-teal-200 underline decoration-teal-400/60">
                            <span>📹</span> Klik Link Zoom ↗
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Direct Clickable Zoom Platform Card if Available --}}
        @if($event->zoom_link)
            <div class="relative z-10 mt-3.5 overflow-hidden rounded-2xl bg-gradient-to-r from-teal-950/90 via-slate-900/90 to-blue-950/90 p-3.5 sm:p-4 border border-teal-400/40 shadow-lg backdrop-blur flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-500/20 text-teal-300 text-base border border-teal-400/40">
                        📹
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[9px] font-black uppercase tracking-wider text-teal-300">Akses Daring / Zoom Cloud Meetings</p>
                        <a href="{{ $event->zoom_link }}" target="_blank" rel="noopener" class="mt-0.5 text-xs font-bold text-white hover:text-teal-200 underline decoration-teal-400/60 truncate block" title="{{ $event->zoom_link }}">
                            {{ $event->zoom_link }}
                        </a>
                    </div>
                </div>
                <a href="{{ $event->zoom_link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-3.5 py-2 text-xs font-black text-white shadow-md hover:bg-teal-500 transition shrink-0">
                    <span>🚀</span> Gabung Zoom ↗
                </a>
            </div>
        @endif

        {{-- Target & Sasaran Peserta --}}
        @php
            $targetPesertaList = $event->resolvedTargetPesertaList();
        @endphp
        @if(!empty($targetPesertaList))
            <section class="relative z-10 mt-4 rounded-2xl bg-gradient-to-r from-teal-950/80 via-slate-900/80 to-teal-950/80 p-4 border border-teal-400/30 shadow-md backdrop-blur">
                <div class="flex items-center gap-2 mb-2.5">
                    <span class="text-teal-300 text-sm">👥</span>
                    <h4 class="text-xs font-black uppercase tracking-wider text-teal-300">
                        Peserta Luring:
                    </h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($targetPesertaList as $targetItem)
                        <div class="flex items-center gap-2.5 rounded-xl bg-white/[0.06] px-3.5 py-2 border border-white/10 text-xs font-semibold text-white/90">
                            <span class="h-1.5 w-1.5 rounded-full bg-teal-400 shrink-0"></span>
                            <span class="leading-snug">{{ $targetItem }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Benefits & Facilities Chips --}}
        @if(!empty($benefits))
            <section class="relative z-10 mt-6 rounded-2xl bg-gradient-to-r from-white/[0.08] via-white/[0.04] to-white/[0.08] p-4 border border-white/15 backdrop-blur">
                <p class="text-[10px] font-black uppercase tracking-widest text-teal-300 text-center sm:text-left mb-2.5">
                    🎁 BENEFIT & FASILITAS KEGIATAN:
                </p>
                <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                    @foreach($benefits as $benefit)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-500/15 px-3 py-1 text-[10.5px] font-bold text-teal-200 border border-teal-500/30 shadow-sm">
                            <span class="text-amber-400">✓</span> {{ $benefit }}
                        </span>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Registration & Interactive QR Code Box --}}
        <section class="relative z-10 mt-6 overflow-hidden rounded-2xl bg-gradient-to-br from-teal-900/80 via-slate-900/90 to-slate-950 p-5 sm:p-6 border-2 border-teal-400/40 shadow-xl">
            <div class="grid grid-cols-1 items-center gap-5 sm:grid-cols-[1fr,auto]">
                <div>
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-0.5 text-[9.5px] font-extrabold uppercase tracking-wider text-emerald-300 border border-emerald-500/30">
                        <span>📱</span> PENDAFTARAN & PRESENSI DIGITAL
                    </div>
                    <h3 class="mt-2 text-lg sm:text-xl font-black text-white">
                        Scan QR Code untuk Mengakses Acara
                    </h3>
                    <p class="mt-1 text-xs text-white/80 leading-relaxed max-w-md">
                        Arahkan kamera smartphone Anda ke QR Code untuk pendaftaran instan, tanda tangan daftar hadir digital, materi, dan sertifikat resmi.
                    </p>

                    <div class="mt-3.5 flex flex-wrap items-center gap-3 text-[11px] text-white/70">
                        @if($event->kapasitas)
                            <span class="rounded bg-white/10 px-2 py-1 font-semibold border border-white/10">
                                👥 Kuota: {{ $event->kapasitas }} Peserta
                            </span>
                        @endif
                        @if($event->narahubung_nama || $event->narahubung_whatsapp)
                            <span class="flex items-center gap-1 font-semibold text-teal-300">
                                <span>📞</span> PIC: {{ $event->narahubung_nama ?: 'Panitia' }} ({{ $event->narahubung_whatsapp ?: '-' }})
                            </span>
                        @endif
                    </div>
                </div>

                {{-- QR Code Display --}}
                <div class="flex flex-col items-center justify-center">
                    <div class="rounded-2xl bg-white p-2.5 shadow-2xl ring-4 ring-teal-400/30">
                        <img src="{{ $qrCodeDataUri }}" alt="QR Pendaftaran {{ $event->judul }}" class="h-28 w-28 sm:h-32 sm:w-32 object-contain">
                    </div>
                    <p class="mt-1.5 text-[9px] font-black uppercase tracking-widest text-teal-300 text-center">
                        SCAN UNTUK AKSES
                    </p>
                </div>
            </div>
        </section>

        {{-- Footer & Branding Tagline --}}
        <footer class="relative z-10 mt-6 pt-4 border-t border-white/15 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left text-[10px] text-white/60">
            <div class="flex items-center gap-2">
                <span class="font-bold text-white/80">Politeknik Kesehatan Kemenkes Bandung</span>
                <span>•</span>
                <span>Jl. Pajajaran No. 56 Bandung</span>
            </div>
            <div class="flex items-center gap-3 font-semibold text-teal-300">
                <span>🌐 poltekkesbandung.ac.id</span>
                <span>•</span>
                <span>@poltekkesbandung</span>
            </div>
        </footer>

    </div>
@endif

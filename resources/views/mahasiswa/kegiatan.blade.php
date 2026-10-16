@extends('mahasiswa.layouts.portal')

@section('title', 'Jadwal PBM & Absensi')
@section('page-title', 'Jadwal PBM & Absensi')
@section('page-subtitle', 'Lihat jadwal praktik, magang, PBM, dan akses absensi langsung.')

@section('content')
<div class="space-y-6">
    {{-- Info Card --}}
    <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5 dark:border-brand-900/30 dark:bg-brand-950/10">
        <h2 class="text-sm font-bold text-brand-800 dark:text-brand-400">👋 Hallo, {{ $mahasiswa->name }}</h2>
        <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
            Halaman ini menampilkan seluruh kegiatan PBM, praktik klinik, atau magang dari Program Studi Anda di mana Anda terdaftar sebagai peserta. 
            Anda dapat membuka <strong>Kartu Akses Peserta</strong> untuk mengisi absensi mandiri, mengunduh sertifikat, atau melihat surat tugas Anda.
        </p>
    </div>

    {{-- Kegiatan List --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Kegiatan Saya</h3>
            <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-semibold px-2.5 py-1 rounded-full">
                {{ $participants->count() }} Kegiatan terdaftar
            </span>
        </div>

        @if($participants->isEmpty())
            <div class="p-12 text-center">
                <span class="text-4xl" role="img" aria-label="empty">📅</span>
                <h4 class="mt-4 text-sm font-bold text-gray-900 dark:text-white">Belum Ada Kegiatan Terdaftar</h4>
                <p class="mt-2 text-xs text-gray-500 max-w-sm mx-auto leading-relaxed">
                    Anda belum terdaftar di kegiatan PBM atau magang apa pun saat ini. Hubungi koordinator mata kuliah atau admin unit jika terdapat kekeliruan data.
                </p>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($participants as $participant)
                    @php
                        $event = $participant->event;
                        $page = $event?->page;
                        $isWithinDateRange = (! $event?->mulai_at || now()->gte($event->mulai_at->copy()->startOfDay()))
                            && (! $event?->selesai_at || now()->lte($event->selesai_at->copy()->endOfDay()));
                        $isActive = $event?->daftar_hadir_aktif && $isWithinDateRange;
                    @endphp
                    @if($event && $page)
                        <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors">
                            <div class="space-y-2 flex-1 min-w-0">
                                <div class="flex items-center flex-wrap gap-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-brand-100 dark:bg-brand-900/40 text-brand-700 dark:text-brand-400">
                                        {{ $page?->judul ?: 'Poltekkes' }}
                                    </span>
                                    @if($isActive)
                                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-green-100 dark:bg-green-950/60 text-green-700 dark:text-green-400 animate-pulse">
                                            Absensi Aktif
                                        </span>
                                    @else
                                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500">
                                            {{ (! $isWithinDateRange && $event->selesai_at && now()->gt($event->selesai_at->copy()->endOfDay())) ? 'Selesai' : 'Belum Aktif' }}
                                        </span>
                                    @endif
                                </div>

                                <h4 class="text-base font-bold text-gray-900 dark:text-white leading-tight truncate">
                                    {{ $event->judul }}
                                </h4>

                                <div class="flex flex-wrap gap-y-1.5 gap-x-4 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-1">
                                        <span>📅</span>
                                        <span>{{ $event->mulai_at?->translatedFormat('d M Y') ?: '—' }} s.d {{ $event->selesai_at?->translatedFormat('d M Y') ?: '—' }}</span>
                                    </div>
                                    @if($participant->tempat_praktik)
                                        <div class="flex items-center gap-1 font-semibold text-brand-600 dark:text-brand-400">
                                            <span>📍</span>
                                            <span>LOKASI: {{ $participant->tempat_praktik }}</span>
                                        </div>
                                    @endif
                                    @if($participant->nama_pembimbing)
                                        <div class="flex items-center gap-1 text-slate-600 dark:text-slate-300">
                                            <span>👤</span>
                                            <span>PEMBIMBING: {{ $participant->nama_pembimbing }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 flex-shrink-0">
                                @if($event->daftar_hadir_aktif)
                                    <a href="{{ route('unit.events.attendance.show', [$page->slug, $event->slug, $participant->access_token]) }}" 
                                       target="_blank"
                                       class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                        ✍️ Isi Presensi
                                    </a>
                                @endif
                                <a href="{{ route('unit.events.access', [$page->slug, $event->slug, $participant->access_token]) }}" 
                                   target="_blank"
                                   class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-brand-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                    🔑 Kartu Akses
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

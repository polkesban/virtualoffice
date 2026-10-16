{{-- Block: Event Unit & Registrasi --}}
@php
    use App\Models\UnitEvent;

    $limit = (int) ($cfg['limit'] ?? 3);
    $showPast = (bool) ($cfg['tampilkan_lampau'] ?? false);
    $featuredOnly = (bool) ($cfg['featured_only'] ?? false);

    $items = $page->events()
        ->published()
        ->when($featuredOnly, fn ($query) => $query->featured())
        ->when(! $showPast, function ($query) {
            $now = now();
            $query->where(function ($inner) use ($now) {
                $inner->where('mulai_at', '>=', $now)
                    ->orWhere(function ($running) use ($now) {
                        $running->whereNotNull('selesai_at')
                            ->where('selesai_at', '>=', $now);
                    });
            });
        })
        ->orderByDesc('is_featured')
        ->orderBy('mulai_at')
        ->limit(max($limit, 1))
        ->get();

    $gridClass = $items->count() > 1 ? 'lg:grid-cols-2' : 'lg:grid-cols-1';
@endphp

<section class="bg-gray-50 py-14 dark:bg-gray-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">Event Unit</p>
                <h2 class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $section->judul ?: 'Rapat, Webinar, dan Event Unit' }}</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-500 dark:text-gray-400">Unit dapat membuka registrasi, membagikan materi, pre test, post test, evaluasi, kehadiran, serta sertifikat melalui halaman event masing-masing.</p>
            </div>
        </div>

        @if($items->isEmpty())
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="text-4xl">🎫</p>
                <p class="mt-3 font-bold text-gray-700 dark:text-gray-300">Belum ada event unit yang ditayangkan.</p>
                <p class="mt-2 text-sm text-gray-400">Tambahkan event dari panel admin halaman unit untuk menampilkan registrasi dan detail acara di sini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 {{ $gridClass }}">
                @foreach($items as $item)
                    <article class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm transition-transform hover:-translate-y-1 dark:border-gray-800 dark:bg-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-[240px,1fr]">
                            <div class="relative min-h-[220px]">
                                @if($item->flyer_path)
                                    <div class="relative h-full w-full overflow-hidden bg-slate-900">
                                        <img src="{{ \App\Support\MediaStorage::url($item->flyer_path) }}" alt="{{ $item->judul }}" class="h-full w-full object-cover">
                                        <div class="absolute inset-0 bg-slate-950/25"></div>
                                        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5 text-[10px] font-bold text-white">
                                            <span class="rounded-full bg-white/20 px-2 py-0.5 backdrop-blur">{{ UnitEvent::$jenisLabels[$item->jenis_kegiatan] ?? $item->jenis_kegiatan }}</span>
                                            <span class="rounded-full bg-white/20 px-2 py-0.5 backdrop-blur">{{ UnitEvent::$metodeLabels[$item->metode] ?? $item->metode }}</span>
                                        </div>
                                    </div>
                                @else
                                    <x-event-flyer :event="$item" compact="true" />
                                @endif
                            </div>

                            <div class="p-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ $item->judul }}</h3>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $item->mulai_at?->translatedFormat('d M Y, H:i') }}@if($item->selesai_at) • {{ $item->selesai_at->translatedFormat('d M Y, H:i') }}@endif</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $item->isRegistrationOpen() ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $item->isRegistrationOpen() ? 'Registrasi Buka' : 'Lihat Detail' }}</span>
                                </div>

                                <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $item->ringkasan ?: \Illuminate\Support\Str::limit(strip_tags((string) $item->deskripsi), 180) }}</p>

                                <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Tempat</p>
                                        <p class="mt-1 font-bold">{{ $item->lokasi ?: 'Akan diumumkan' }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Registrasi</p>
                                        <p class="mt-1 font-bold">{{ $item->registrasi_aktif ? 'Aktif' : 'Nonaktif' }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Materi</p>
                                        <p class="mt-1 font-bold">{{ $item->materials()->where('publik', true)->count() }} item</p>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-wrap items-center gap-2">
                                    <a href="{{ route('unit.events.show', [$page->slug, $item->slug]) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">Lihat Detail Event</a>
                                    <a href="{{ route('unit.events.flyer', [$page->slug, $item->slug]) }}" target="_blank" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-800 transition-colors hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-300 flex items-center gap-1.5">
                                        <span>🖼️</span> Flyer Resmi
                                    </a>
                                    @if($item->isRegistrationOpen())
                                        <a href="{{ route('unit.events.show', [$page->slug, $item->slug]) }}#registrasi" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Daftar Peserta</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
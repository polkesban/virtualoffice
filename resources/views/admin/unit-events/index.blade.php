@extends('admin.layouts.admin')
@section('title', 'Kelola Event Unit')
@section('page-title', 'Kelola Event Unit')
@section('page-subtitle', 'Rapat, webinar, workshop, registrasi peserta, materi, tes, evaluasi, notulen, foto kegiatan, laporan, surat tugas, dan sertifikat')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => null])

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Event</p>
                <p class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $events->count() }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Dipublikasikan</p>
                <p class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $events->whereIn('status', ['published', 'completed'])->count() }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Registrasi Aktif</p>
                <p class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $events->filter(fn ($event) => $event->isRegistrationOpen())->count() }}</p>
            </div>
        </div>

        <a href="{{ route('admin.unit-events.create', $unitPage) }}"
           class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
            + Tambah Event Unit
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($events->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900">
            <p class="text-4xl">🎫</p>
            <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">Belum ada event unit</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Mulai dari satu event lalu lengkapi materi, daftar peserta, surat tugas peserta, pre test, post test, evaluasi, dan sertifikatnya.</p>
            <a href="{{ route('admin.unit-events.create', $unitPage) }}"
               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                + Buat Event Pertama
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            @foreach($events as $event)
                <article class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-[220px,1fr]">
                        <div class="relative min-h-[180px] bg-gradient-to-br from-brand-700 via-emerald-700 to-slate-900">
                            @if($event->flyer_path)
                                <img src="{{ \App\Support\MediaStorage::url($event->flyer_path) }}" alt="{{ $event->judul }}" class="h-full w-full object-cover">
                                <div class="absolute inset-0 bg-slate-950/30"></div>
                            @endif
                            <div class="absolute left-4 top-4 flex flex-wrap gap-2 text-[11px] font-bold text-white">
                                <span class="rounded-full bg-white/20 px-2.5 py-1 backdrop-blur">{{ \App\Models\UnitEvent::$jenisLabels[$event->jenis_kegiatan] ?? $event->jenis_kegiatan }}</span>
                                <span class="rounded-full bg-white/20 px-2.5 py-1 backdrop-blur">{{ \App\Models\UnitEvent::$metodeLabels[$event->metode] ?? $event->metode }}</span>
                                @if($event->is_featured)
                                    <span class="rounded-full bg-amber-400/90 px-2.5 py-1 text-slate-900">Unggulan</span>
                                @endif
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $event->judul }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $event->mulai_at?->translatedFormat('d M Y, H:i') }}
                                        @if($event->selesai_at)
                                            • {{ $event->selesai_at->translatedFormat('d M Y, H:i') }}
                                        @endif
                                    </p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ in_array($event->status, ['published', 'completed'], true) ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : ($event->status === 'cancelled' ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300') }}">
                                    {{ \App\Models\UnitEvent::$statusLabels[$event->status] ?? $event->status }}
                                </span>
                            </div>

                            @if($event->ringkasan)
                                <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $event->ringkasan }}</p>
                            @endif

                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-4">
                                <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Peserta</p>
                                    <p class="mt-1 font-bold">{{ $event->participants_count }}</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Materi</p>
                                    <p class="mt-1 font-bold">{{ $event->materials_count }}</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Pertanyaan</p>
                                    <p class="mt-1 font-bold">{{ $event->questions_count }}</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Registrasi</p>
                                    <p class="mt-1 font-bold">{{ $event->isRegistrationOpen() ? 'Buka' : 'Tutup' }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('admin.unit-events.edit', [$unitPage, $event]) }}" class="rounded-xl bg-brand-600 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-brand-500">Edit Event</a>
                                <a href="{{ route('admin.unit-events.flyer', [$unitPage, $event]) }}" target="_blank" class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 transition-colors hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300">🖼️ Flyer</a>
                                <a href="{{ route('admin.unit-events.materials.index', [$unitPage, $event]) }}" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Materi</a>
                                <a href="{{ route('admin.unit-events.questions.index', [$unitPage, $event]) }}" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Pertanyaan</a>
                                <a href="{{ route('admin.unit-events.documentation.index', [$unitPage, $event]) }}" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Notulen / Laporan</a>
                                <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $event]) }}" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Peserta / Surat Tugas</a>
                                <a href="{{ route('unit.events.show', [$unitPage->slug, $event->slug]) }}" target="_blank" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Lihat Publik</a>
                                <form method="POST" action="{{ route('admin.unit-events.destroy', [$unitPage, $event]) }}" onsubmit="return confirm('Hapus event ini? Semua materi, peserta, dan hasil tes ikut terhapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
@extends('admin.layouts.admin')
@section('title', 'Detail Rekap Rapat')
@section('page-title', 'Detail Rekap Rapat')
@section('page-subtitle', 'Detail lintas unit untuk pimpinan: ringkasan kegiatan, checklist dokumen, notulen, resume, laporan, dan dokumentasi foto')

@section('content')
<div class="mx-auto max-w-7xl p-6">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('admin.meeting-recaps.index') }}" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-950/40 dark:hover:text-brand-200">
                ← Kembali ke Rekap
            </a>
            <h1 class="mt-4 text-3xl font-black text-gray-900 dark:text-white">{{ $unitEvent->judul }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $unitEvent->page?->judul ?? 'Tanpa unit' }} • {{ \App\Models\UnitEvent::$statusLabels[$unitEvent->status] ?? $unitEvent->status }} • {{ $unitEvent->mulai_at?->translatedFormat('d M Y, H:i') ?? '-' }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
            @if($recap['document_links']['public_event'])
                <a href="{{ $recap['document_links']['public_event'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-brand-800 dark:hover:bg-brand-950/40 dark:hover:text-brand-200">
                    Lihat Halaman Publik
                </a>
            @endif
            @if($recap['document_links']['surat_tugas'])
                <a href="{{ $recap['document_links']['surat_tugas'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-brand-800 dark:hover:bg-brand-950/40 dark:hover:text-brand-200">
                    Buka Surat Tugas
                </a>
            @endif
            @if(auth()->user()?->isSuperAdmin())
                <a href="{{ route('admin.unit-events.edit', [$unitEvent->page, $unitEvent]) }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                    Edit Event
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-gray-100 bg-white px-5 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Kelengkapan</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $recap['completion_percent'] }}%</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $recap['ready_count'] }}/{{ $recap['total_count'] }} dokumen inti tersedia.</p>
        </div>
        <div class="rounded-3xl border border-gray-100 bg-white px-5 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Peserta</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $recap['participants_count'] }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Termasuk panitia, narasumber, dan peserta lain yang tercatat.</p>
        </div>
        <div class="rounded-3xl border border-gray-100 bg-white px-5 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Catatan Hadir</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $recap['attendance_records_count'] }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Untuk {{ $unitEvent->attendanceDayCount() }} hari pelaksanaan.</p>
        </div>
        <div class="rounded-3xl border border-gray-100 bg-white px-5 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Lampiran Tambahan</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $recap['materials_count'] }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Materi/lampiran event dan {{ $recap['participant_assignment_letter_count'] }} surat tugas peserta.</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr,0.8fr]">
        <div class="space-y-6">
            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-gray-900 dark:text-white">Ringkasan Kegiatan</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Metadata utama rapat yang dipakai untuk rekap pimpinan.</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-bold {{ $recap['is_complete'] ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300' }}">
                        {{ $recap['is_complete'] ? 'Dokumen Lengkap' : 'Perlu Tindak Lanjut' }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Unit</p>
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $unitEvent->page?->judul ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Metode</p>
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ \App\Models\UnitEvent::$metodeLabels[$unitEvent->metode] ?? $unitEvent->metode }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Jadwal Mulai</p>
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $unitEvent->mulai_at?->translatedFormat('l, d F Y H:i') ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Jadwal Selesai</p>
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $unitEvent->selesai_at?->translatedFormat('l, d F Y H:i') ?? 'Belum diisi' }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/70 md:col-span-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Lokasi</p>
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $unitEvent->lokasi ?: 'Belum diisi' }}</p>
                        @if($unitEvent->alamat_detail)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $unitEvent->alamat_detail }}</p>
                        @endif
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/70 md:col-span-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Ringkasan</p>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $unitEvent->ringkasan ?: 'Ringkasan kegiatan belum diisi.' }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/70 md:col-span-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Penyusun Terakhir</p>
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $unitEvent->creator?->name ?? 'Tidak diketahui' }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dokumentasi terakhir diperbarui {{ $unitEvent->documentation?->updated_at?->diffForHumans() ?? 'belum pernah diperbarui' }}.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-black text-gray-900 dark:text-white">Kelengkapan Dokumen Rapat</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Checklist ini dibuat agar pimpinan bisa melihat item mana yang sudah lengkap dan mana yang masih perlu ditagihkan ke unit pelaksana.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($recap['checklist'] as $item)
                        <article class="rounded-2xl border px-4 py-4 {{ $item['ready'] ? 'border-green-100 bg-green-50 dark:border-green-900/40 dark:bg-green-900/15' : 'border-amber-100 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-900/15' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-black {{ $item['ready'] ? 'text-green-800 dark:text-green-100' : 'text-amber-800 dark:text-amber-100' }}">{{ $item['label'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 {{ $item['ready'] ? 'text-green-700/85 dark:text-green-200/85' : 'text-amber-700/85 dark:text-amber-200/85' }}">{{ $item['detail'] }}</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-[11px] font-bold {{ $item['ready'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200' }}">
                                    {{ $item['ready'] ? 'Siap' : 'Belum' }}
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="space-y-6">
                @foreach($documentSections as $section)
                    <article class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="text-lg font-black text-gray-900 dark:text-white">{{ $section['title'] }}</h2>
                        <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800/70">
                            @if(blank($section['content']) || ($section['is_html'] && trim(strip_tags((string) $section['content'])) === ''))
                                <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $section['empty'] }}</p>
                            @elseif($section['is_html'])
                                <div class="prose max-w-none prose-sm text-gray-700 prose-headings:text-gray-900 prose-strong:text-gray-900 dark:prose-invert dark:text-gray-200">
                                    {!! $section['content'] !!}
                                </div>
                            @else
                                <div class="whitespace-pre-line text-sm leading-7 text-gray-700 dark:text-gray-200">{{ $section['content'] }}</div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-black text-gray-900 dark:text-white">Tindak Lanjut</h2>
                @if($recap['missing_labels'] === [])
                    <p class="mt-3 rounded-2xl bg-green-50 px-4 py-3 text-sm leading-6 text-green-700 dark:bg-green-900/20 dark:text-green-300">Semua dokumen inti rapat sudah tersedia. Halaman ini siap digunakan sebagai eviden monitoring pimpinan.</p>
                @else
                    <div class="mt-3 rounded-2xl bg-amber-50 px-4 py-4 dark:bg-amber-900/20">
                        <p class="text-sm font-bold text-amber-800 dark:text-amber-100">Masih perlu dilengkapi:</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($recap['missing_labels'] as $label)
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-700 shadow-sm dark:bg-amber-950/40 dark:text-amber-200">{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-black text-gray-900 dark:text-white">Lampiran Visual</h2>
                @if($unitEvent->photos->isEmpty())
                    <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">Belum ada foto kegiatan yang tersimpan untuk rapat ini.</p>
                @else
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-1">
                        @foreach($unitEvent->photos as $photo)
                            <figure class="overflow-hidden rounded-2xl border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/60">
                                <img src="{{ \App\Support\MediaStorage::url($photo->image_path) }}" alt="{{ $photo->title ?: $unitEvent->judul }}" class="h-48 w-full object-cover">
                                <figcaption class="space-y-2 px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ $photo->title ?: 'Foto kegiatan' }}</p>
                                            @if($photo->caption)
                                                <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $photo->caption }}</p>
                                            @endif
                                        </div>
                                        @if($photo->is_cover)
                                            <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold text-brand-700 dark:bg-brand-900/20 dark:text-brand-200">Cover</span>
                                        @endif
                                    </div>
                                    @if($photo->taken_at)
                                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Diambil {{ $photo->taken_at->translatedFormat('d M Y H:i') }}</p>
                                    @endif
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </section>

            @if($recap['document_links']['flyer'])
                <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-black text-gray-900 dark:text-white">Flyer Event</h2>
                    <a href="{{ $recap['document_links']['flyer'] }}" target="_blank" rel="noopener" class="mt-4 block overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800">
                        <img src="{{ $recap['document_links']['flyer'] }}" alt="{{ $unitEvent->judul }}" class="w-full object-cover">
                    </a>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
@extends('admin.layouts.admin')
@section('title', 'Rekap Rapat Lintas Unit')
@section('page-title', 'Rekap Rapat Lintas Unit')
@section('page-subtitle', 'Pemantauan rapat dari seluruh unit beserta kelengkapan dokumen rapat, notulen, resume, laporan, dan dokumentasi')

@section('content')
<div class="mx-auto max-w-7xl p-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-gray-100 bg-white px-5 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Total Rapat</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $stats['meetings'] }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Data mengikuti filter yang sedang aktif.</p>
        </div>
        <div class="rounded-3xl border border-gray-100 bg-white px-5 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Unit Terlibat</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $stats['units'] }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Unit yang memiliki rapat pada hasil saat ini.</p>
        </div>
        <div class="rounded-3xl border border-green-100 bg-green-50 px-5 py-4 shadow-sm dark:border-green-900/40 dark:bg-green-900/15">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-green-700 dark:text-green-300">Dokumen Lengkap</p>
            <p class="mt-2 text-3xl font-black text-green-800 dark:text-green-100">{{ $stats['complete'] }}</p>
            <p class="mt-1 text-sm text-green-700/80 dark:text-green-300/80">Rapat dengan seluruh dokumen inti sudah tersedia.</p>
        </div>
        <div class="rounded-3xl border border-amber-100 bg-amber-50 px-5 py-4 shadow-sm dark:border-amber-900/40 dark:bg-amber-900/15">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Perlu Tindak Lanjut</p>
            <p class="mt-2 text-3xl font-black text-amber-800 dark:text-amber-100">{{ $stats['follow_up'] }}</p>
            <p class="mt-1 text-sm text-amber-700/80 dark:text-amber-300/80">Masih ada dokumen atau eviden rapat yang belum lengkap.</p>
        </div>
    </div>

    <div class="mt-6 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <form method="GET" class="grid grid-cols-1 gap-4 lg:grid-cols-5">
            <label class="space-y-2 text-sm font-semibold text-gray-700 dark:text-gray-200 lg:col-span-2">
                <span>Kata Kunci</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Judul rapat, unit, lokasi, ringkasan"
                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none ring-0 transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
            </label>

            <label class="space-y-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span>Unit</span>
                <select name="unit_page_id"
                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none ring-0 transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                    <option value="">Semua unit</option>
                    @foreach($unitPages as $unitPage)
                        <option value="{{ $unitPage->id }}" @selected((string) ($filters['unit_page_id'] ?? '') === (string) $unitPage->id)>{{ $unitPage->judul }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span>Status</span>
                <select name="status"
                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none ring-0 transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                    <option value="">Semua status</option>
                    @foreach(\App\Models\UnitEvent::$statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span>Tahun</span>
                <select name="year"
                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none ring-0 transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                    <option value="">Semua tahun</option>
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span>Kelengkapan</span>
                <select name="completeness"
                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none ring-0 transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                    <option value="">Semua kondisi</option>
                    <option value="complete" @selected(($filters['completeness'] ?? '') === 'complete')>Lengkap</option>
                    <option value="incomplete" @selected(($filters['completeness'] ?? '') === 'incomplete')>Belum lengkap</option>
                </select>
            </label>

            <div class="flex flex-wrap items-end gap-3 lg:col-span-5">
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                    Terapkan Filter
                </button>
                <a href="{{ route('admin.meeting-recaps.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-brand-800 dark:hover:bg-brand-950/40 dark:hover:text-brand-200">
                    Reset
                </a>
            </div>
        </form>
    </div>

    @if($recaps->isEmpty())
        <div class="mt-6 rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-14 text-center dark:border-gray-700 dark:bg-gray-900">
            <p class="text-4xl">🗃️</p>
            <h2 class="mt-4 text-xl font-black text-gray-900 dark:text-white">Belum ada rapat yang cocok dengan filter</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ubah filter atau pastikan event dengan jenis rapat sudah dibuat oleh unit terkait.</p>
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50/80 dark:bg-gray-950/70">
                        <tr class="text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                            <th class="px-5 py-4">Unit</th>
                            <th class="px-5 py-4">Rapat</th>
                            <th class="px-5 py-4">Jadwal</th>
                            <th class="px-5 py-4">Peserta</th>
                            <th class="px-5 py-4">Kelengkapan</th>
                            <th class="px-5 py-4">Dokumen Kunci</th>
                            <th class="px-5 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recaps as $recap)
                            @php
                                $event = $recap['event'];
                                $statusClasses = match ($event->status) {
                                    'published', 'completed' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300',
                                    'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                };
                                $missingPreview = collect($recap['missing_labels'])->take(3)->implode(', ');
                            @endphp
                            <tr class="align-top">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $event->page?->judul ?? 'Tanpa unit' }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->page?->slug ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-start gap-2">
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ $event->judul }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ \App\Models\UnitEvent::$metodeLabels[$event->metode] ?? $event->metode }} • {{ $event->lokasi ?: 'Lokasi belum diisi' }}</p>
                                        </div>
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClasses }}">{{ \App\Models\UnitEvent::$statusLabels[$event->status] ?? $event->status }}</span>
                                    </div>
                                    @if($event->ringkasan)
                                        <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($event->ringkasan, 140) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $event->mulai_at?->translatedFormat('d M Y, H:i') ?? '-' }}</p>
                                    <p class="mt-1">@if($event->selesai_at){{ $event->selesai_at->translatedFormat('d M Y, H:i') }}@else Belum ada waktu selesai @endif</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Peserta</p>
                                            <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $recap['participants_count'] }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Absensi</p>
                                            <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $recap['attendance_records_count'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="w-full max-w-[220px]">
                                        <div class="flex items-center justify-between text-xs font-semibold text-gray-500 dark:text-gray-400">
                                            <span>{{ $recap['ready_count'] }}/{{ $recap['total_count'] }} dokumen</span>
                                            <span>{{ $recap['completion_percent'] }}%</span>
                                        </div>
                                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                            <div class="h-full rounded-full {{ $recap['is_complete'] ? 'bg-green-500' : 'bg-amber-500' }}" style="width: {{ $recap['completion_percent'] }}%"></div>
                                        </div>
                                        @if($recap['is_complete'])
                                            <p class="mt-2 text-xs font-semibold text-green-700 dark:text-green-300">Semua dokumen inti sudah tersedia.</p>
                                        @else
                                            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">Kurang: {{ $missingPreview }}{{ count($recap['missing_labels']) > 3 ? ' dan lainnya' : '' }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex max-w-xs flex-wrap gap-2">
                                        @foreach(['surat_tugas', 'minutes', 'summary', 'report', 'photos'] as $key)
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $recap['checklist'][$key]['ready'] ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                                {{ $recap['checklist'][$key]['label'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.meeting-recaps.show', $event) }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-brand-500">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
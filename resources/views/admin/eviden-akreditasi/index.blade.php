@extends('admin.layouts.admin')

@section('title', 'Ruang Eviden Akreditasi')
@section('page-title', 'Ruang Eviden Akreditasi')
@section('page-subtitle', 'Repositori bukti per kriteria, status verifikasi, PIC, dan masa berlaku dokumen.')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-brand-700 dark:border-brand-800 dark:bg-brand-950 dark:text-brand-300">
                <span aria-hidden="true">🗄️</span>
                Admin-Only
            </div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white">Ruang Eviden Akreditasi</h1>
            <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                Modul ini menyimpan bukti akreditasi secara terstruktur tanpa mengubah alur publik website. Eviden dapat ditautkan ke Dokumen Mutu, URL eksternal, atau file privat yang hanya dapat diunduh dari panel admin.
            </p>
        </div>
        <a href="{{ route('admin.eviden-akreditasi.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">
            <span aria-hidden="true">＋</span>
            Tambah Eviden
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Total Eviden</p>
            <p class="mt-3 text-3xl font-black text-gray-900 dark:text-white">{{ $summary['total'] }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Semua entri yang tersimpan dalam ruang eviden.</p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-900 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">Terverifikasi</p>
            <p class="mt-3 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $summary['verified'] }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Eviden yang sudah dinyatakan siap dipakai untuk asesmen.</p>
        </div>
        <div class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Perlu Ditinjau</p>
            <p class="mt-3 text-3xl font-black text-amber-700 dark:text-amber-300">{{ $summary['due_soon'] }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Review atau masa berlaku jatuh tempo dalam 30 hari.</p>
        </div>
        <div class="rounded-3xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-900 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-700 dark:text-rose-300">Kadaluarsa</p>
            <p class="mt-3 text-3xl font-black text-rose-700 dark:text-rose-300">{{ $summary['expired'] }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Dokumen yang sudah melewati masa berlakunya.</p>
        </div>
    </div>

    @if ($annotationsModuleReady)
    <section class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900 dark:bg-gray-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Workflow Reviewer</p>
                <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">Tindak Lanjut Catatan Reviewer</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">Ringkasan ini membantu Pusat Mutu melihat anotasi yang masih aktif tanpa membuka satu per satu eviden. Modul tetap dormant sampai migration anotasi dijalankan.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                {{ $summary['evidences_with_pending_annotations'] }} eviden punya catatan aktif
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900 dark:bg-rose-950/30">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-700 dark:text-rose-300">Belum Ditindaklanjuti</p>
                <p class="mt-2 text-3xl font-black text-rose-700 dark:text-rose-300">{{ $summary['annotations_open'] }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Catatan reviewer yang belum mulai ditangani.</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Sedang Diproses</p>
                <p class="mt-2 text-3xl font-black text-amber-700 dark:text-amber-300">{{ $summary['annotations_in_progress'] }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Catatan yang sudah masuk antrean tindak lanjut.</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">Selesai</p>
                <p class="mt-2 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $summary['annotations_resolved'] }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Catatan yang sudah ditutup oleh tim reviewer.</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Total Anotasi</p>
                <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $summary['annotations_total'] }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Semua bookmark dan anotasi reviewer yang tersimpan.</p>
            </div>
        </div>
    </section>
    @endif

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_repeat(2,minmax(0,1fr))]">
        <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Dashboard Kelengkapan</p>
                    <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">Cakupan Per Kriteria</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Ringkasan ini dihitung dari seluruh ruang eviden, terlepas dari filter tabel, agar Pusat Mutu bisa melihat folder prioritas secara cepat.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ $criteriaSummary['covered'] }} / {{ $criteriaSummary['total'] }} kriteria sudah terisi
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <p class="font-bold text-gray-900 dark:text-white">Kriteria sudah punya eviden</p>
                        <p class="font-black text-brand-700 dark:text-brand-300">{{ $criteriaSummary['coverage_percent'] }}%</p>
                    </div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $criteriaSummary['coverage_percent'] }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <p class="font-bold text-gray-900 dark:text-white">Kriteria minimal satu eviden siap review</p>
                        <p class="font-black text-emerald-700 dark:text-emerald-300">{{ $criteriaSummary['readiness_percent'] }}%</p>
                    </div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-emerald-600 transition-all" style="width: {{ $criteriaSummary['readiness_percent'] }}%"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-900 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">Kriteria Siap</p>
            <p class="mt-3 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $criteriaSummary['ready'] }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Folder yang sudah memiliki minimal satu eviden berstatus siap review atau terverifikasi.</p>
        </section>

        <section class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Prioritas Tindak Lanjut</p>
            <p class="mt-3 text-3xl font-black text-amber-700 dark:text-amber-300">{{ $criteriaSummary['attention'] }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Folder yang mengandung eviden tanpa sumber, review atau masa berlaku yang perlu perhatian, atau catatan reviewer yang masih aktif.</p>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Kriteria kosong: {{ $criteriaSummary['empty'] }}</p>
        </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="space-y-4 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Kriteria Default</p>
                <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">Folder Eviden</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Delapan folder ini mengikuti struktur utama panduan asesor LAM-PTKes dan sekarang menampilkan indikator cepat untuk kelengkapan, kesiapan, dan tindak lanjut.</p>
            </div>
            <div class="space-y-3">
                @foreach ($folders as $folder)
                @php
                    $isActiveFolder = (string) request('folder') === (string) $folder->id;
                    $readinessPercent = $folder->evidences_count > 0
                        ? min(100, (int) round(($folder->ready_evidences_count / $folder->evidences_count) * 100))
                        : 0;

                    if ($folder->evidences_count === 0) {
                        $folderStatusLabel = 'Belum terisi';
                        $folderStatusClass = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                    } elseif ($folder->missing_source_evidences_count > 0 || $folder->attention_evidences_count > 0 || (($folder->pending_annotation_evidences_count ?? 0) > 0)) {
                        $folderStatusLabel = 'Perlu tindak lanjut';
                        $folderStatusClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
                    } elseif ($folder->ready_evidences_count === 0) {
                        $folderStatusLabel = 'Butuh verifikasi';
                        $folderStatusClass = 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300';
                    } elseif ($folder->ready_evidences_count < $folder->evidences_count) {
                        $folderStatusLabel = 'Sebagian siap';
                        $folderStatusClass = 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300';
                    } else {
                        $folderStatusLabel = 'Siap';
                        $folderStatusClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
                    }
                @endphp
                <a href="{{ route('admin.eviden-akreditasi.index', array_merge(request()->except('page'), ['folder' => $folder->id])) }}"
                   class="block rounded-2xl border px-4 py-3 transition {{ $isActiveFolder ? 'border-brand-300 bg-brand-50 dark:border-brand-800 dark:bg-brand-950/40' : 'border-gray-200 bg-gray-50 hover:border-brand-200 hover:bg-white dark:border-gray-800 dark:bg-gray-950 dark:hover:border-brand-900 dark:hover:bg-gray-900' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $folder->display_label }}</p>
                            @if ($folder->description)
                            <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $folder->description }}</p>
                            @endif
                        </div>
                        <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-brand-100 px-2 py-1 text-xs font-black text-brand-700 dark:bg-brand-950 dark:text-brand-300">{{ $folder->evidences_count }}</span>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $readinessPercent }}%"></div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                        <span class="rounded-full bg-white px-2 py-1 dark:bg-gray-900">Siap {{ $folder->ready_evidences_count }}</span>
                        @if ($folder->verified_evidences_count > 0)
                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Terverifikasi {{ $folder->verified_evidences_count }}</span>
                        @endif
                        @if ($folder->attention_evidences_count > 0)
                        <span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950 dark:text-amber-300">Review {{ $folder->attention_evidences_count }}</span>
                        @endif
                        @if (($folder->pending_annotation_evidences_count ?? 0) > 0)
                        <span class="rounded-full bg-rose-50 px-2 py-1 text-rose-700 dark:bg-rose-950 dark:text-rose-300">Catatan aktif {{ $folder->pending_annotation_evidences_count }}</span>
                        @endif
                        @if ($folder->missing_source_evidences_count > 0)
                        <span class="rounded-full bg-rose-50 px-2 py-1 text-rose-700 dark:bg-rose-950 dark:text-rose-300">Tanpa sumber {{ $folder->missing_source_evidences_count }}</span>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-3 text-[11px] font-semibold uppercase tracking-[0.14em]">
                        <span class="inline-flex rounded-full px-2 py-1 {{ $folderStatusClass }}">{{ $folderStatusLabel }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $isActiveFolder ? 'Filter aktif' : 'Buka folder' }}</span>
                    </div>
                </a>
                @endforeach
            </div>
        </aside>

        <section class="space-y-4">
            <form method="GET" class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-8">
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Cari</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Judul, butir, PIC, catatan"
                               class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">PIC</label>
                        <input type="text" name="pic" value="{{ request('pic') }}" placeholder="Contoh: Pusat Mutu"
                               class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Kriteria</label>
                        <select name="folder" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                            <option value="">Semua folder</option>
                            @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" @selected((string) request('folder') === (string) $folder->id)>{{ $folder->display_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Status</label>
                        <select name="status" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                            <option value="">Semua status</option>
                            @foreach ($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Perhatian</label>
                        <select name="review" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                            <option value="">Semua</option>
                            <option value="review_overdue" @selected(request('review') === 'review_overdue')>Review lewat jatuh tempo</option>
                            <option value="review_30" @selected(request('review') === 'review_30')>Review ≤ 30 hari</option>
                            <option value="expiry_attention" @selected(request('review') === 'expiry_attention')>Berlaku habis/kadaluarsa</option>
                            <option value="expires_30" @selected(request('review') === 'expires_30')>Berlaku habis ≤ 30 hari</option>
                            <option value="expired" @selected(request('review') === 'expired')>Sudah kadaluarsa</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Sumber Eviden</label>
                        <select name="source_state" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                            <option value="">Semua sumber</option>
                            @foreach ($sourceFilterOptions as $key => $label)
                            <option value="{{ $key }}" @selected(request('source_state') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($annotationsModuleReady)
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Catatan Reviewer</label>
                        <select name="annotation_status" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                            <option value="">Semua catatan</option>
                            @foreach ($annotationFilterOptions as $key => $label)
                            <option value="{{ $key }}" @selected(request('annotation_status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Ekspor selalu mengikuti filter aktif, termasuk PIC, sumber eviden, dan perhatian review.</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.eviden-akreditasi.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex items-center justify-center rounded-2xl bg-brand-50 px-4 py-3 text-sm font-bold text-brand-700 transition hover:bg-brand-100 dark:bg-brand-950 dark:text-brand-300 dark:hover:bg-brand-900/70">Ekspor Eviden CSV</a>
                        <a href="{{ route('admin.eviden-akreditasi.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300 dark:hover:bg-emerald-900/70">Ekspor Eviden XLSX</a>
                        @if ($annotationsModuleReady)
                        <a href="{{ route('admin.eviden-akreditasi.annotations.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300 dark:hover:bg-emerald-900/70">Ekspor Catatan CSV</a>
                        <a href="{{ route('admin.eviden-akreditasi.annotations.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center justify-center rounded-2xl bg-sky-50 px-4 py-3 text-sm font-bold text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950 dark:text-sky-300 dark:hover:bg-sky-900/70">Ekspor Catatan XLSX</a>
                        @endif
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gray-100 px-4 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Filter</button>
                        <a href="{{ route('admin.eviden-akreditasi.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Reset</a>
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em] text-gray-500">Kriteria</th>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em] text-gray-500">Eviden</th>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em] text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em] text-gray-500">Sumber</th>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em] text-gray-500">PIC</th>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em] text-gray-500">Review & Berlaku</th>
                                <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-[0.18em] text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($items as $item)
                            <tr class="align-top transition hover:bg-gray-50 dark:hover:bg-gray-950/60">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $item->folder?->display_label ?? '—' }}</p>
                                    @if ($item->subcriterion_code)
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-700 dark:text-brand-300">Butir {{ $item->subcriterion_code }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $item->title }}</p>
                                    @if ($item->summary)
                                    <p class="mt-2 max-w-xl text-xs leading-5 text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($item->summary, 160) }}</p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                        @if ($item->issued_at)
                                        <span class="rounded-full bg-gray-100 px-2 py-1 dark:bg-gray-800">Terbit {{ $item->issued_at->format('d M Y') }}</span>
                                        @endif
                                        @if ($item->verified_at)
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Diverifikasi {{ $item->verified_at->format('d M Y H:i') }}</span>
                                        @endif
                                        @if ($annotationsModuleReady && (($item->annotations_count ?? 0) > 0))
                                            @if (($item->open_annotations_count ?? 0) > 0)
                                            <span class="rounded-full bg-rose-50 px-2 py-1 text-rose-700 dark:bg-rose-950 dark:text-rose-300">Belum {{ $item->open_annotations_count }}</span>
                                            @endif
                                            @if (($item->in_progress_annotations_count ?? 0) > 0)
                                            <span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950 dark:text-amber-300">Proses {{ $item->in_progress_annotations_count }}</span>
                                            @endif
                                            @if (($item->resolved_annotations_count ?? 0) > 0)
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Selesai {{ $item->resolved_annotations_count }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    @php
                                        $statusColor = match($item->verification_status) {
                                            'verified' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                                            'ready' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                                            'archived' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $statusColor }}">{{ $item->verification_status_label }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="space-y-2 text-xs">
                                        @if ($item->hasDokumenMutuLink())
                                        <a href="{{ $item->dokumenMutu->file_url }}" target="_blank" rel="noopener" class="block font-semibold text-brand-700 hover:underline dark:text-brand-300">Dokumen Mutu: {{ $item->dokumenMutu->judul }}</a>
                                        @endif
                                        @if ($item->hasUploadedFile())
                                        <a href="{{ route('admin.eviden-akreditasi.show', ['accreditationEvidence' => $item, 'source' => 'private_file']) }}" class="block font-semibold text-gray-700 hover:underline dark:text-gray-200">Pratinjau file privat: {{ $item->file_name }}@if($item->human_file_size) · {{ $item->human_file_size }}@endif</a>
                                        @endif
                                        @if ($item->hasExternalUrl())
                                        <a href="{{ $item->external_url }}" target="_blank" rel="noopener" class="block font-semibold text-sky-700 hover:underline dark:text-sky-300">URL eksternal</a>
                                        @endif
                                        @if (! $item->hasDokumenMutuLink() && ! $item->hasUploadedFile() && ! $item->hasExternalUrl())
                                        <span class="inline-flex rounded-full bg-rose-50 px-2 py-1 font-semibold text-rose-700 dark:bg-rose-950 dark:text-rose-300">Belum ada sumber</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-xs text-gray-600 dark:text-gray-300">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $item->pic_name ?: 'Belum ditentukan' }}</p>
                                    @if ($item->editor)
                                    <p class="mt-1 text-gray-500 dark:text-gray-400">Update terakhir: {{ $item->editor->name }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-xs text-gray-600 dark:text-gray-300">
                                    <div class="space-y-2">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">Review</p>
                                            <p>
                                                @if ($item->review_due_at)
                                                    {{ $item->review_due_at->format('d M Y') }}
                                                @else
                                                    Belum dijadwalkan
                                                @endif
                                            </p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">Masa Berlaku</p>
                                            <p>
                                                @if ($item->expires_at)
                                                    {{ $item->expires_at->format('d M Y') }}
                                                @else
                                                    Tidak dibatasi
                                                @endif
                                            </p>
                                        </div>
                                        @if ($item->review_overdue)
                                        <span class="inline-flex rounded-full bg-rose-50 px-2 py-1 font-semibold text-rose-700 dark:bg-rose-950 dark:text-rose-300">Review lewat</span>
                                        @elseif ($item->is_expired)
                                        <span class="inline-flex rounded-full bg-rose-50 px-2 py-1 font-semibold text-rose-700 dark:bg-rose-950 dark:text-rose-300">Kadaluarsa</span>
                                        @elseif ($item->expires_soon)
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-1 font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300">Segera habis</span>
                                        @elseif ($item->review_due_soon)
                                        <span class="inline-flex rounded-full bg-sky-50 px-2 py-1 font-semibold text-sky-700 dark:bg-sky-950 dark:text-sky-300">Review segera</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                                     <a href="{{ route('admin.eviden-akreditasi.show', $item) }}"
                                                         class="inline-flex items-center justify-center rounded-xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700 transition hover:bg-brand-100 dark:bg-brand-950 dark:text-brand-300 dark:hover:bg-brand-900/70">Lihat</a>
                                        <a href="{{ route('admin.eviden-akreditasi.edit', $item) }}"
                                           class="inline-flex items-center justify-center rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Edit</a>
                                        <form method="POST" action="{{ route('admin.eviden-akreditasi.destroy', $item) }}" onsubmit="return confirm('Hapus eviden ini? File privat yang terunggah juga akan ikut dihapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-300 dark:hover:bg-rose-900/70">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <p class="text-base font-bold text-gray-900 dark:text-white">Belum ada eviden yang cocok dengan filter saat ini.</p>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Mulai dari satu folder kriteria lalu tambahkan dokumen, URL, atau unggahan privat.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
@extends('admin.layouts.admin')

@section('title', 'Rekap Formulir')

@section('content')
<div class="mx-auto max-w-6xl p-6" x-data="formResponsesDashboard(@js($summaryPayload), @js($summaryUrl))" x-init="init()">
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ $backUrl }}" class="text-sm font-medium text-brand-600 hover:underline">← {{ $backLabel }}</a>
            <h1 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">Rekap Formulir</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $contextTitle }} · {{ $section->judul ?: 'Formulir tanpa judul' }}</p>
            @if(($summaryPayload['response_limit'] ?? 'none') !== 'none')
            <p class="mt-2 text-xs font-semibold text-brand-700 dark:text-brand-300">Batas respons aktif: {{ ($summaryPayload['response_limit'] ?? 'none') === 'session' ? '1 kali per sesi browser' : '1 kali per email' }}</p>
            @endif
            @if(($summaryPayload['access_scope'] ?? 'public') !== 'public')
            <p class="mt-2 text-xs font-semibold text-indigo-700 dark:text-indigo-300">Akses formulir: {{ match($summaryPayload['access_scope'] ?? 'public') { 'employee_only' => 'Pegawai saja', 'student_only' => 'Mahasiswa saja', 'employee_or_student' => 'Pegawai atau mahasiswa', 'event_participants' => 'Peserta event terdaftar', default => 'Publik' } }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm dark:border-brand-900 dark:bg-brand-950/30">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-300">Total Respons</p>
                <p class="mt-1 text-2xl font-extrabold text-gray-900 dark:text-white" x-text="summary.total_responses ?? 0"></p>
            </div>
            <template x-if="summary.quiz?.enabled">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-900 dark:bg-amber-950/30">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Rata-rata Quiz</p>
                    <p class="mt-1 text-2xl font-extrabold text-gray-900 dark:text-white"><span x-text="summary.quiz?.average_percent ?? 0"></span>%</p>
                </div>
            </template>
            <template x-if="summary.quiz?.enabled">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Pass Rate</p>
                    <p class="mt-1 text-2xl font-extrabold text-gray-900 dark:text-white"><span x-text="summary.quiz?.pass_rate ?? 0"></span>%</p>
                </div>
            </template>
            <template x-if="summary.quiz?.enabled && summary.quiz?.duration_minutes">
                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm dark:border-cyan-900 dark:bg-cyan-950/30">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">Durasi Quiz</p>
                    <p class="mt-1 text-2xl font-extrabold text-gray-900 dark:text-white"><span x-text="summary.quiz?.duration_minutes ?? 0"></span> mnt</p>
                </div>
            </template>
            <template x-if="summary.quiz?.proctoring?.enabled">
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm dark:border-rose-900 dark:bg-rose-950/30">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-rose-700 dark:text-rose-300">Pelanggaran Proctoring</p>
                    <p class="mt-1 text-2xl font-extrabold text-gray-900 dark:text-white"><span x-text="summary.quiz?.proctoring?.total_suspicious_count ?? 0"></span></p>
                </div>
            </template>
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-500">Refresh</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="summary.updated_at ? ('Update terakhir: ' + formatTimestamp(summary.updated_at)) : 'Belum ada respons'"></p>
            </div>
            <button type="button" @click="refresh()" class="rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">Refresh Sekarang</button>
            <a href="{{ $exportUrl }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">Export CSV</a>
            <a href="{{ $editUrl }}" class="rounded-2xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-700 transition-colors hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:text-gray-200">Edit Blok</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.page-sections.responses.show', $section) }}" class="mb-6 grid gap-4 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 md:grid-cols-[1fr_1fr_auto_auto] md:items-end">
        <div>
            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.18em] text-gray-400">Dari Tanggal</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.18em] text-gray-400">Sampai Tanggal</label>
            <input type="date" name="until" value="{{ $filters['until'] ?? '' }}" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
        </div>
        <button type="submit" class="rounded-2xl bg-gray-900 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">Terapkan Filter</button>
        <a href="{{ route('admin.page-sections.responses.show', $section) }}" class="rounded-2xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-600 transition-colors hover:border-gray-300 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300">Reset</a>
    </form>

    <div x-show="summary.quiz?.enabled" class="mb-6 grid gap-4 rounded-3xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm dark:border-amber-900 dark:bg-amber-950/20 md:grid-cols-5">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Nilai Batas Lulus</p>
            <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white"><span x-text="summary.quiz?.passing_score ?? 0"></span>%</p>
        </div>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Peserta Lulus</p>
            <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white"><span x-text="summary.quiz?.pass_count ?? 0"></span> / <span x-text="summary.quiz?.attempt_count ?? 0"></span></p>
        </div>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Waktu Habis</p>
            <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white"><span x-text="summary.quiz?.timed_out_count ?? 0"></span></p>
        </div>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Jadwal Akses</p>
            <p class="mt-1 text-xs leading-6 text-gray-600 dark:text-gray-300">
                <span x-text="summary.quiz?.opens_at ? formatTimestamp(summary.quiz.opens_at) : 'Buka kapan saja'"></span>
                <span class="mx-1">→</span>
                <span x-text="summary.quiz?.closes_at ? formatTimestamp(summary.quiz.closes_at) : 'Tanpa batas tutup'"></span>
            </p>
        </div>
        <div x-show="summary.quiz?.proctoring?.enabled">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Proctoring</p>
            <p class="mt-1 text-xs leading-6 text-gray-600 dark:text-gray-300">
                Tab <span class="font-bold text-gray-900 dark:text-white" x-text="summary.quiz?.proctoring?.tab_switch_count ?? 0"></span>
                · Fullscreen <span class="font-bold text-gray-900 dark:text-white" x-text="summary.quiz?.proctoring?.fullscreen_exit_count ?? 0"></span>
                · Clipboard <span class="font-bold text-gray-900 dark:text-white" x-text="summary.quiz?.proctoring?.clipboard_violation_count ?? 0"></span>
            </p>
        </div>
    </div>

    <template x-if="(summary.total_responses ?? 0) === 0">
        <div class="rounded-3xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
            <p class="text-4xl">📝</p>
            <h2 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Belum ada respons</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Dashboard ini akan otomatis menampilkan tabulasi dan grafik saat formulir mulai diisi.</p>
        </div>
    </template>

    <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]" x-show="(summary.total_responses ?? 0) > 0">
        <div class="space-y-5">
            <template x-for="field in summary.fields ?? []" :key="field.key">
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-base font-extrabold text-gray-900 dark:text-white" x-text="field.label"></h2>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="field.description" x-text="field.description"></p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <span x-text="(field.response_count ?? 0) + ' respons'"></span>
                        </span>
                    </div>

                    <template x-if="field.visualization === 'bar'">
                        <div class="space-y-3">
                            <template x-if="field.average">
                                <p class="text-sm font-semibold text-brand-700 dark:text-brand-300">Rata-rata: <span x-text="field.average"></span></p>
                            </template>
                            <template x-for="item in field.items ?? []" :key="field.key + '-' + item.value">
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                        <span class="flex items-center gap-3 font-medium text-gray-700 dark:text-gray-200">
                                            <template x-if="item.media_url">
                                                <img :src="item.media_url" :alt="item.media_alt || item.label" class="h-10 w-10 rounded-xl border border-gray-200 object-cover dark:border-gray-700">
                                            </template>
                                            <span class="flex flex-wrap items-center gap-2">
                                                <span x-text="item.label"></span>
                                                <template x-if="summary.quiz?.enabled && item.is_correct">
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Benar</span>
                                                </template>
                                                <template x-if="summary.quiz?.enabled && Number(item.score || 0) > 0">
                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">+<span x-text="item.score"></span></span>
                                                </template>
                                            </span>
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><span x-text="item.count"></span> · <span x-text="formatPercent(item.percent)"></span></span>
                                    </div>
                                    <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div class="h-3 rounded-full bg-brand-500 transition-all" :style="'width:' + Math.max(item.percent || 0, item.count > 0 ? 4 : 0) + '%' "></div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="field.scale">
                                <div class="flex items-center justify-between gap-4 pt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    <span x-text="field.scale.min_label"></span>
                                    <span class="text-right" x-text="field.scale.max_label"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="field.visualization === 'matrix'">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-4 text-[11px] text-gray-500 dark:text-gray-400">
                                <span x-text="field.scale?.min_label"></span>
                                <span class="text-right" x-text="field.scale?.max_label"></span>
                            </div>
                            <template x-for="row in field.rows ?? []" :key="field.key + '-' + row.key">
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <p class="font-semibold text-gray-800 dark:text-gray-100" x-text="row.label"></p>
                                        <span class="text-xs font-semibold text-brand-700 dark:text-brand-300" x-show="row.average">Rata-rata <span x-text="row.average"></span></span>
                                    </div>
                                    <div class="space-y-2">
                                        <template x-for="item in row.items ?? []" :key="row.key + '-' + item.value">
                                            <div>
                                                <div class="mb-1 flex items-center justify-between gap-3 text-xs">
                                                    <span class="font-medium text-gray-600 dark:text-gray-300" x-text="item.label"></span>
                                                    <span class="text-gray-500 dark:text-gray-400"><span x-text="item.count"></span> · <span x-text="formatPercent(item.percent)"></span></span>
                                                </div>
                                                <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800">
                                                    <div class="h-2.5 rounded-full bg-cyan-500 transition-all" :style="'width:' + Math.max(item.percent || 0, item.count > 0 ? 4 : 0) + '%' "></div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="field.visualization === 'list'">
                        <div class="space-y-2">
                            <template x-if="(field.items ?? []).length === 0">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada jawaban teks.</p>
                            </template>
                            <template x-for="(item, index) in field.items ?? []" :key="field.key + '-text-' + index">
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-200" x-text="item"></div>
                            </template>
                        </div>
                    </template>

                    <template x-if="field.visualization === 'files'">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold dark:bg-gray-800"><span x-text="field.file_count ?? 0"></span> <span x-text="field.type === 'signature' ? 'signature' : 'file'"></span></span>
                                <template x-if="field.accept">
                                    <span class="rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-800" x-text="'Format: ' + field.accept"></span>
                                </template>
                            </div>
                            <template x-if="(field.items ?? []).length > 0">
                                <div class="space-y-2">
                                    <template x-for="item in field.items ?? []" :key="field.key + '-file-type-' + item.value">
                                        <div>
                                            <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                                <span class="font-medium text-gray-700 dark:text-gray-200" x-text="item.label"></span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400"><span x-text="item.count"></span> · <span x-text="formatPercent(item.percent)"></span></span>
                                            </div>
                                            <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-800">
                                                <div class="h-3 rounded-full bg-emerald-500 transition-all" :style="'width:' + Math.max(item.percent || 0, item.count > 0 ? 4 : 0) + '%' "></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div class="grid gap-3 md:grid-cols-2">
                                <template x-for="(file, fileIndex) in field.files ?? []" :key="field.key + '-file-' + fileIndex">
                                    <a :href="file.browser_url || file.url" target="_blank" rel="noreferrer"
                                       class="rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-3 transition hover:border-brand-300 hover:bg-white dark:border-gray-800 dark:bg-gray-950/40 dark:hover:border-brand-700 dark:hover:bg-gray-900">
                                        <template x-if="file.is_image">
                                            <img :src="file.browser_url || file.url" alt="Preview signature" class="mb-3 h-32 w-full rounded-2xl border border-gray-200 object-contain bg-white p-2 dark:border-gray-700 dark:bg-gray-900">
                                        </template>
                                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100" x-text="file.name"></p>
                                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400"><span x-text="file.size_label || 'Ukuran tidak tersedia'"></span> · <span x-text="file.mime_type || 'file'"></span></p>
                                        <p class="mt-2 text-[11px] font-semibold text-brand-600 dark:text-brand-300" x-text="file.submitted_at || ''"></p>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </section>
            </template>
        </div>

        <aside class="space-y-5">
            <section x-show="summary.quiz?.enabled" class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Analisis Butir</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kesukaran, daya beda, kelengkapan jawaban, dan efektivitas opsi per soal.</p>

                <div class="mt-4 space-y-3">
                    <template x-for="item in summary.quiz?.items ?? []" :key="'quiz-item-' + item.key">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white" x-text="item.label"></p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400" x-text="item.type"></p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300" x-text="(item.max_score ?? 0) + ' poin'"></span>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Kesukaran</p>
                                    <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white"><span x-text="item.difficulty_index ?? 0"></span>%</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="item.difficulty_label"></p>
                                </div>
                                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Daya Beda</p>
                                    <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white" x-text="item.discrimination_index ?? 'n/a'"></p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Perbedaan rata-rata performa grup atas dan bawah.</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Jawaban Benar</p>
                                    <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white"><span x-text="item.correct_count ?? 0"></span> / <span x-text="item.response_count ?? 0"></span></p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><span x-text="item.correct_rate ?? 0"></span>% benar</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Omission</p>
                                    <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white"><span x-text="item.omission_rate ?? 0"></span>%</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Soal yang dilewati atau tidak terisi.</p>
                                </div>
                            </div>
                            <div class="mt-3 space-y-2" x-show="(item.option_analysis ?? []).length > 0">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Analisis Opsi</p>
                                <template x-for="option in item.option_analysis ?? []" :key="item.key + '-option-analysis-' + option.value">
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-white px-3 py-2 text-xs dark:border-gray-800 dark:bg-gray-900">
                                        <span class="flex flex-wrap items-center gap-2 font-semibold text-gray-700 dark:text-gray-200">
                                            <span x-text="option.label"></span>
                                            <template x-if="option.is_correct">
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Benar</span>
                                            </template>
                                            <template x-if="option.effective_distractor">
                                                <span class="rounded-full bg-cyan-100 px-2 py-0.5 text-[10px] font-bold text-cyan-700 dark:bg-cyan-950/30 dark:text-cyan-300">Distraktor aktif</span>
                                            </template>
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400"><span x-text="option.selection_count ?? 0"></span> dipilih · <span x-text="formatPercent(option.selection_rate)"></span></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Respons Terbaru</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Data mentah terbaru untuk audit cepat tanpa export.</p>

                <div class="mt-4 space-y-3">
                    <template x-if="(summary.responses ?? []).length === 0">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada respons terbaru.</p>
                    </template>

                    <template x-for="response in summary.responses ?? []" :key="response.id">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-100">Respons #<span x-text="response.id"></span></p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400" x-text="response.submitted_at"></p>
                            </div>
                            <div x-show="response.quiz" class="mb-3 grid gap-2 rounded-2xl border border-amber-200 bg-amber-50/70 p-3 text-xs dark:border-amber-900 dark:bg-amber-950/20 sm:grid-cols-4">
                                <div>
                                    <p class="font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Skor</p>
                                    <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white"><span x-text="response.quiz?.score ?? 0"></span> / <span x-text="response.quiz?.max_score ?? 0"></span></p>
                                </div>
                                <div>
                                    <p class="font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Persen</p>
                                    <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white"><span x-text="response.quiz?.percent ?? 0"></span>%</p>
                                </div>
                                <div>
                                    <p class="font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Status</p>
                                    <p class="mt-1 text-sm font-extrabold" :class="response.quiz?.passed ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'" x-text="response.quiz?.passed ? 'Lulus' : 'Belum Lulus'"></p>
                                </div>
                                <div>
                                    <p class="font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Durasi</p>
                                    <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white"><span x-text="response.quiz?.duration_seconds ?? 0"></span> detik</p>
                                </div>
                            </div>
                            <div x-show="response.proctoring" class="mb-3 rounded-2xl border border-rose-200 bg-rose-50/70 p-3 text-xs dark:border-rose-900 dark:bg-rose-950/20">
                                <div class="grid gap-2 sm:grid-cols-4">
                                    <div>
                                        <p class="font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">Pelanggaran</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white" x-text="response.proctoring?.suspicious_count ?? 0"></p>
                                    </div>
                                    <div>
                                        <p class="font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">Tab / Blur</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white" x-text="response.proctoring?.tab_switch_count ?? 0"></p>
                                    </div>
                                    <div>
                                        <p class="font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">Keluar Fullscreen</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white" x-text="response.proctoring?.fullscreen_exit_count ?? 0"></p>
                                    </div>
                                    <div>
                                        <p class="font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">Copy / Paste</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white" x-text="response.proctoring?.clipboard_violation_count ?? 0"></p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <template x-if="response.proctoring?.selfie?.browser_url || response.proctoring?.selfie?.url">
                                        <a :href="response.proctoring?.selfie?.browser_url || response.proctoring?.selfie?.url" target="_blank" rel="noreferrer" class="block rounded-2xl border border-rose-200 bg-white p-2 dark:border-rose-900 dark:bg-gray-900">
                                            <img :src="response.proctoring?.selfie?.browser_url || response.proctoring?.selfie?.url" alt="Selfie proctoring" class="h-24 w-24 rounded-xl object-cover">
                                        </a>
                                    </template>
                                    <div class="flex-1 text-[11px] leading-6 text-gray-500 dark:text-gray-400">
                                        <p><span class="font-bold text-gray-700 dark:text-gray-200">IP:</span> <span x-text="response.proctoring?.ip || '-' "></span></p>
                                        <p><span class="font-bold text-gray-700 dark:text-gray-200">User agent:</span> <span class="break-all" x-text="response.proctoring?.user_agent || '-' "></span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(answer, answerIndex) in response.answers ?? []" :key="response.id + '-' + answerIndex">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400" x-text="answer.label"></p>
                                        <template x-if="!['file', 'signature'].includes(answer.type)">
                                            <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-200" x-text="answer.value"></p>
                                        </template>
                                        <template x-if="['file', 'signature'].includes(answer.type)">
                                            <div class="mt-2 space-y-2">
                                                <template x-for="(file, fileIndex) in answer.files ?? []" :key="response.id + '-' + answerIndex + '-' + fileIndex">
                                                    <a :href="file.browser_url || file.url" target="_blank" rel="noreferrer"
                                                       class="block rounded-xl border border-gray-100 bg-white px-3 py-2 text-sm text-gray-700 transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                                        <template x-if="file.is_image">
                                                            <img :src="file.browser_url || file.url" alt="Preview signature" class="mb-2 h-28 w-full rounded-xl border border-gray-200 object-contain bg-white p-2 dark:border-gray-700 dark:bg-gray-950">
                                                        </template>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="font-medium" x-text="file.name"></span>
                                                            <span class="text-[11px] text-gray-500 dark:text-gray-400" x-text="file.size_label || 'file'"></span>
                                                        </div>
                                                    </a>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </aside>
    </div>
</div>

<script>
function formResponsesDashboard(initialSummary, summaryUrl) {
    return {
        summary: initialSummary,
        summaryUrl,
        timer: null,
        init() {
            this.timer = window.setInterval(() => this.refresh(), 15000);
        },
        async refresh() {
            try {
                const response = await fetch(this.summaryUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    return;
                }

                this.summary = await response.json();
            } catch (error) {
                console.error('Failed to refresh form responses', error);
            }
        },
        formatPercent(value) {
            return `${Number(value || 0).toFixed(1)}%`;
        },
        formatTimestamp(value) {
            if (!value) {
                return 'Belum ada data';
            }

            const parsed = new Date(value);
            if (Number.isNaN(parsed.getTime())) {
                return value;
            }

            return parsed.toLocaleString('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
            });
        },
    };
}
</script>
@endsection
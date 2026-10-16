@extends('admin.layouts.admin')

@section('title', 'Detail Eviden Akreditasi')
@section('page-title', $evidence->title)
@section('page-subtitle', 'Detail eviden, metadata, dan pratinjau isi dokumen tanpa keluar dari panel admin.')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a href="{{ route('admin.eviden-akreditasi.index') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">← Kembali ke Ruang Eviden</a>
                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $evidence->verification_status_label }}</span>
                @if ($evidence->folder)
                <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-brand-700 dark:bg-brand-950 dark:text-brand-300">{{ $evidence->folder->display_label }}</span>
                @endif
                @if ($evidence->subcriterion_code)
                <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-sky-700 dark:bg-sky-950 dark:text-sky-300">Butir {{ $evidence->subcriterion_code }}</span>
                @endif
            </div>

            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">{{ $evidence->title }}</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    {{ $evidence->summary ?: 'Belum ada ringkasan. Gunakan halaman edit jika perlu menambahkan konteks singkat untuk asesor atau tim mutu.' }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            @if ($selectedPreview)
            <a href="{{ $selectedPreview['open_url'] }}" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                Buka Sumber di Tab Baru
            </a>
            @endif
            <a href="{{ route('admin.eviden-akreditasi.edit', $evidence) }}"
               class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-brand-500">
                Edit Eviden
            </a>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">PIC</p>
            <p class="mt-3 text-lg font-black text-gray-900 dark:text-white">{{ $evidence->pic_name ?: 'Belum ditentukan' }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Gunakan PIC agar tindak lanjut eviden lebih jelas saat audit internal.</p>
        </div>
        <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Tanggal Dokumen</p>
            <p class="mt-3 text-lg font-black text-gray-900 dark:text-white">{{ $evidence->issued_at ? $evidence->issued_at->format('d M Y') : 'Belum diisi' }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tanggal terbit membantu memisahkan eviden aktif dan eviden lama.</p>
        </div>
        <div class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Review Berikutnya</p>
            <p class="mt-3 text-lg font-black text-amber-700 dark:text-amber-300">{{ $evidence->review_due_at ? $evidence->review_due_at->format('d M Y') : 'Belum dijadwalkan' }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $evidence->review_overdue ? 'Jadwal review sudah lewat dan perlu diprioritaskan.' : ($evidence->review_due_soon ? 'Masuk prioritas review dalam 30 hari.' : 'Belum ada sinyal jatuh tempo dekat.') }}</p>
        </div>
        <div class="rounded-3xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-900 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-700 dark:text-rose-300">Masa Berlaku</p>
            <p class="mt-3 text-lg font-black text-rose-700 dark:text-rose-300">{{ $evidence->expires_at ? $evidence->expires_at->format('d M Y') : 'Tidak dibatasi' }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $evidence->is_expired ? 'Dokumen ini sudah kadaluarsa.' : ($evidence->expires_soon ? 'Masa berlaku akan habis dalam 30 hari.' : 'Belum ada indikasi kadaluarsa dekat.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_24rem]">
        <section class="space-y-6">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Pratinjau</p>
                        <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">Pratinjau Isi Eviden</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            @if ($selectedPreview)
                                Sedang menampilkan sumber <span class="font-bold text-gray-900 dark:text-white">{{ $selectedPreview['title'] }}</span>. Jika panel kosong, sumber tersebut mungkin menolak embed dan perlu dibuka di tab baru.
                            @else
                                Eviden ini belum memiliki sumber yang bisa ditampilkan.
                            @endif
                        </p>
                    </div>

                    @if ($selectedPreview)
                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ str_replace('_', ' ', $selectedPreview['key']) }}</span>
                    @endif
                </div>

                @if ($selectedPreview && $selectedPreview['is_remote'])
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    Sumber eksternal atau Dokumen Mutu publik bisa diblokir oleh kebijakan browser pihak ketiga. Jika pratinjau tidak muncul, gunakan tombol buka di tab baru.
                </div>
                @endif

                <div class="mt-5">
                    @if (! $selectedPreview)
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                        Belum ada sumber eviden yang bisa ditampilkan. Tambahkan file privat, tautan Dokumen Mutu, atau URL eksternal dari halaman edit.
                    </div>
                    @elseif (! $selectedPreview['can_embed'])
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                        Jenis file ini belum ideal untuk preview inline di browser. Buka sumber di tab baru atau gunakan unduhan jika perlu.
                    </div>
                    @elseif ($selectedPreview['kind'] === 'image')
                    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                        <img src="{{ $selectedPreview['preview_url'] }}" alt="Pratinjau eviden {{ $evidence->title }}" class="max-h-[80vh] w-full rounded-2xl object-contain">
                    </div>
                    @elseif ($selectedPreview['kind'] === 'audio')
                    <div class="rounded-3xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-950">
                        <audio controls class="w-full" preload="metadata">
                            <source src="{{ $selectedPreview['preview_url'] }}">
                        </audio>
                    </div>
                    @elseif ($selectedPreview['kind'] === 'video')
                    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-black dark:border-gray-800">
                        <video controls class="max-h-[80vh] w-full" preload="metadata">
                            <source src="{{ $selectedPreview['preview_url'] }}">
                        </video>
                    </div>
                    @else
                    <iframe src="{{ $selectedPreview['preview_url'] }}"
                            class="h-[75vh] w-full rounded-3xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950"
                            loading="lazy"></iframe>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Catatan Internal</p>
                <div class="mt-3 rounded-2xl bg-gray-50 px-4 py-4 text-sm leading-7 text-gray-700 dark:bg-gray-950 dark:text-gray-300">
                    {{ $evidence->notes ?: 'Belum ada catatan internal untuk eviden ini.' }}
                </div>
            </div>

            @if ($textPreview)
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Teks Terbaca</p>
                        <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">{{ $textPreview['title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            @if ($textPreview['status'] === 'ready')
                                {{ $textPreview['message'] ?: 'Ekstraksi ini membantu tim mutu membaca isi file lebih cepat tanpa harus bergantung penuh pada preview PDF atau aplikasi eksternal.' }}
                            @else
                                {{ $textPreview['message'] }}
                            @endif
                        </p>
                    </div>

                    @if ($textPreview['status'] === 'ready')
                    <div class="flex flex-wrap items-center gap-2">
                        @if (! empty($textPreview['method_label']))
                        <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                            {{ $textPreview['method_label'] }}
                        </span>
                        @endif
                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ number_format($textPreview['character_count'], 0, ',', '.') }} karakter
                        </span>
                    </div>
                    @endif
                </div>

                @if ($textPreview['status'] === 'ready')
                <form method="GET" action="{{ route('admin.eviden-akreditasi.show', $evidence) }}" class="mt-5 rounded-3xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                    @if ($selectedPreview)
                    <input type="hidden" name="source" value="{{ $selectedPreview['key'] }}">
                    @endif
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <div class="min-w-0 flex-1">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Cari di Teks Terbaca</label>
                            <input type="text" name="text_q" value="{{ $textSearch['query'] ?? '' }}" placeholder="Contoh: kurikulum, audit, tracer"
                                   class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-brand-500">
                                Cari
                            </button>
                            @if (($textSearch['query'] ?? '') !== '')
                            <a href="{{ route('admin.eviden-akreditasi.show', ['accreditationEvidence' => $evidence, 'source' => $selectedPreview['key'] ?? null]) }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-600 transition hover:border-gray-300 hover:bg-white dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">
                                Reset
                            </a>
                            @endif
                        </div>
                    </div>
                </form>
                                        {{ $textPreview['message'] ?: 'Ekstraksi ini membantu tim mutu membaca isi file lebih cepat tanpa harus bergantung penuh pada preview PDF atau aplikasi eksternal.' }}
                @if (($textSearch['query'] ?? '') !== '')
                <div class="mt-4 rounded-3xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        @if (($textSearch['occurrence_count'] ?? 0) > 0)
                            {{ number_format($textSearch['occurrence_count'], 0, ',', '.') }} kecocokan ditemukan untuk &quot;{{ $textSearch['query'] }}&quot;.
                        @else
                            Tidak ada kecocokan untuk &quot;{{ $textSearch['query'] }}&quot; di teks yang sedang ditampilkan.
                        @endif
                    </p>

                    @if (($textSearch['occurrence_count'] ?? 0) > 0)

                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 transition hover:border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            Hasil Sebelumnya
                        </button>
                        <button type="button" id="evidence-text-next"
                                class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-brand-500">
                            Hasil Berikutnya
                        </button>
                        <span id="evidence-text-nav-status" class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                            Hasil 1 dari {{ number_format($textSearch['occurrence_count'], 0, ',', '.') }}
                        </span>
                    </div>
                    @endif

                    @if (! empty($textSearch['snippets'] ?? []))
                    <div class="mt-3 space-y-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Potongan Relevan</p>
                        @foreach ($textSearch['snippets'] as $snippet)
                        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Posisi karakter {{ number_format($snippet['position'], 0, ',', '.') }}</p>
                            <span class="sr-only">{{ strip_tags($snippet['html']) }}</span>
                            <p aria-hidden="true">{!! $snippet['html'] !!}</p>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif

                <div class="mt-5 overflow-hidden rounded-3xl border border-gray-200 bg-gray-950 dark:border-gray-800">
                    <pre id="evidence-text-search-target" class="max-h-[32rem] overflow-auto whitespace-pre-wrap px-5 py-5 text-sm leading-7 text-gray-100">{!! $textSearch['highlighted_html'] ?? e($textPreview['content']) !!}</pre>
                </div>

                @if ($textPreview['is_truncated'])
                <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                    Teks dibatasi pada 18.000 karakter pertama agar halaman admin tetap ringan. Jika perlu, buka file penuh dari panel preview atau tab baru.
                </p>
                @endif
                @else
                <div class="mt-5 rounded-3xl border border-dashed border-gray-300 bg-gray-50 px-6 py-8 text-sm leading-6 text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                    {{ $textPreview['message'] }}
                </div>
                @endif
            </div>
            @endif

            @if ($annotationsModuleReady)
            @php
                $annotationOpenCount = $evidence->annotations->where('status', 'open')->count();
                $annotationInProgressCount = $evidence->annotations->where('status', 'in_progress')->count();
                $annotationResolvedCount = $evidence->annotations->where('status', 'resolved')->count();
            @endphp
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Reviewer Notes</p>
                        <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">Bookmark & Anotasi Reviewer</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Simpan penanda bagian penting, kutipan yang perlu ditunjukkan saat visitasi, atau catatan tindak lanjut per eviden.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-800">
                            {{ $evidence->annotations->count() }} anotasi
                        </span>
                        <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                            {{ $annotationOpenCount }} belum
                        </span>
                        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                            {{ $annotationInProgressCount }} proses
                        </span>
                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                            {{ $annotationResolvedCount }} selesai
                        </span>
                        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                            {{ $evidence->annotations->where('is_pinned', true)->count() }} pinned
                        </span>
                    </div>
                </div>

                @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
                    <p class="font-semibold">Beberapa isian anotasi perlu diperbaiki:</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.eviden-akreditasi.annotations.store', $evidence) }}" class="mt-5 space-y-4 rounded-3xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-950">
                    @csrf
                    @if ($selectedPreview)
                    <input type="hidden" name="source" value="{{ $selectedPreview['key'] }}">
                    @endif
                    @if (($textSearch['query'] ?? '') !== '')
                    <input type="hidden" name="text_q" value="{{ $textSearch['query'] }}">
                    @endif
                    <input type="hidden" name="annotation_form" value="create">

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_15rem_auto]">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Judul Bookmark / Anotasi</label>
                            <input type="text" name="title" value="{{ old('annotation_form') === 'create' ? old('title') : '' }}" placeholder="Contoh: Bukti kuat untuk butir 2.1.2"
                                   class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Status Tindak Lanjut</label>
                            <select name="status" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                                @foreach ($annotationStatusOptions as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected((old('annotation_form') === 'create' ? old('status', 'open') : 'open') === $statusKey)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="is_pinned" value="1" @checked(old('annotation_form') === 'create' && old('is_pinned')) class="rounded border-gray-300 text-brand-600">
                            Pin anotasi ini
                        </label>
                    </div>

                    <div>
                        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Kutipan / Bookmark Teks</label>
                            @if (($textPreview['status'] ?? null) === 'ready')
                            <button type="button" data-annotation-fill-target="annotation-excerpt-create"
                                    class="annotation-selection-button inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 transition hover:border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                Ambil Teks Terpilih
                            </button>
                            @endif
                        </div>
                        <textarea id="annotation-excerpt-create" name="excerpt" rows="3" placeholder="Tempel kutipan penting atau gunakan tombol Ambil Teks Terpilih jika Anda sedang membaca panel Teks Terbaca."
                                  class="w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">{{ old('annotation_form') === 'create' ? old('excerpt') : '' }}</textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Catatan Reviewer</label>
                        <textarea name="note" rows="4" placeholder="Contoh: Tunjukkan bagian ini saat asesor menanyakan evaluasi kurikulum dan tindak lanjut pasca review."
                                  class="w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">{{ old('annotation_form') === 'create' ? old('note') : '' }}</textarea>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-brand-500">
                            Simpan Anotasi
                        </button>
                        @if (($textPreview['status'] ?? null) === 'ready')
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Pilih teks di panel Teks Terbaca lalu klik Ambil Teks Terpilih untuk membuat bookmark lebih cepat.
                        </p>
                        @endif
                    </div>
                </form>

                <div class="mt-6 space-y-4">
                    @forelse ($evidence->annotations as $annotation)
                    @php
                        $editingCurrent = old('annotation_form') === 'edit' && (string) old('annotation_id') === (string) $annotation->id;
                    @endphp
                    <article id="annotation-{{ $annotation->id }}" class="rounded-3xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-950">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-black text-gray-900 dark:text-white">{{ $annotation->title }}</h3>
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] {{ $annotation->status_badge_class }}">{{ $annotation->status_label }}</span>
                                    @if ($annotation->is_pinned)
                                    <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-amber-700 dark:bg-amber-950 dark:text-amber-300">Pinned</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                                    Dibuat oleh {{ $annotation->creator?->name ?: 'Admin' }} · {{ optional($annotation->created_at)->format('d M Y H:i') ?: 'Waktu tidak tercatat' }}
                                    @if ($annotation->updated_at && $annotation->updated_at->ne($annotation->created_at))
                                        · Update terakhir {{ $annotation->updated_at->format('d M Y H:i') }}
                                    @endif
                                </p>
                            </div>

                            <form method="POST" action="{{ route('admin.eviden-akreditasi.annotations.destroy', ['accreditationEvidence' => $evidence, 'annotation' => $annotation]) }}" onsubmit="return confirm('Hapus anotasi reviewer ini?')" class="flex shrink-0">
                                @csrf
                                @method('DELETE')
                                @if ($selectedPreview)
                                <input type="hidden" name="source" value="{{ $selectedPreview['key'] }}">
                                @endif
                                @if (($textSearch['query'] ?? '') !== '')
                                <input type="hidden" name="text_q" value="{{ $textSearch['query'] }}">
                                @endif
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-300 dark:hover:bg-rose-900/70">
                                    Hapus
                                </button>
                            </form>
                        </div>

                        @if ($annotation->excerpt)
                        <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm leading-7 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Bookmark Teks</p>
                            <p class="whitespace-pre-wrap">{{ $annotation->excerpt }}</p>
                        </div>
                        @endif

                        @if ($annotation->note)
                        <div class="mt-4 rounded-2xl bg-white px-4 py-4 text-sm leading-7 text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Catatan Reviewer</p>
                            <p class="whitespace-pre-wrap">{{ $annotation->note }}</p>
                        </div>
                        @endif

                        <details class="mt-4 rounded-2xl border border-gray-200 bg-white px-4 py-4 dark:border-gray-700 dark:bg-gray-900" @if($editingCurrent) open @endif>
                            <summary class="cursor-pointer text-sm font-bold text-gray-700 dark:text-gray-200">Edit anotasi</summary>

                            <form method="POST" action="{{ route('admin.eviden-akreditasi.annotations.update', ['accreditationEvidence' => $evidence, 'annotation' => $annotation]) }}" class="mt-4 space-y-4">
                                @csrf
                                @method('PUT')
                                @if ($selectedPreview)
                                <input type="hidden" name="source" value="{{ $selectedPreview['key'] }}">
                                @endif
                                @if (($textSearch['query'] ?? '') !== '')
                                <input type="hidden" name="text_q" value="{{ $textSearch['query'] }}">
                                @endif
                                <input type="hidden" name="annotation_form" value="edit">
                                <input type="hidden" name="annotation_id" value="{{ $annotation->id }}">

                                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_15rem_auto]">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Judul Bookmark / Anotasi</label>
                                        <input type="text" name="title" value="{{ $editingCurrent ? old('title') : $annotation->title }}"
                                               class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Status Tindak Lanjut</label>
                                        <select name="status" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                                            @foreach ($annotationStatusOptions as $statusKey => $statusLabel)
                                            <option value="{{ $statusKey }}" @selected(($editingCurrent ? old('status', $annotation->status) : $annotation->status) === $statusKey)>{{ $statusLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <label class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" name="is_pinned" value="1" @checked($editingCurrent ? old('is_pinned') : $annotation->is_pinned) class="rounded border-gray-300 text-brand-600">
                                        Pin anotasi ini
                                    </label>
                                </div>

                                <div>
                                    <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                                        <label class="block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Kutipan / Bookmark Teks</label>
                                        @if (($textPreview['status'] ?? null) === 'ready')
                                        <button type="button" data-annotation-fill-target="annotation-excerpt-{{ $annotation->id }}"
                                                class="annotation-selection-button inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 transition hover:border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Ambil Teks Terpilih
                                        </button>
                                        @endif
                                    </div>
                                    <textarea id="annotation-excerpt-{{ $annotation->id }}" name="excerpt" rows="3"
                                              class="w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">{{ $editingCurrent ? old('excerpt') : $annotation->excerpt }}</textarea>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Catatan Reviewer</label>
                                    <textarea name="note" rows="4"
                                              class="w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">{{ $editingCurrent ? old('note') : $annotation->note }}</textarea>
                                </div>

                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gray-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                                    Simpan Perubahan Anotasi
                                </button>
                            </form>
                        </details>
                    </article>
                    @empty
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                        Belum ada bookmark atau anotasi reviewer untuk eviden ini.
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </section>

        <aside class="space-y-4">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Sumber Eviden</p>
                <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">Pilih Sumber yang Dilihat</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($sources as $source)
                    <div class="rounded-2xl border px-4 py-4 {{ ($selectedPreview['key'] ?? null) === $source['key'] ? 'border-brand-300 bg-brand-50 dark:border-brand-800 dark:bg-brand-950/40' : 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $source['title'] }}</p>
                                <p class="mt-1 break-words text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $source['summary'] }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-white px-2 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-gray-600 dark:bg-gray-900 dark:text-gray-300">{{ $source['kind'] }}</span>
                        </div>

                        <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $source['description'] }}</p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.eviden-akreditasi.show', ['accreditationEvidence' => $evidence, 'source' => $source['key']]) }}"
                               class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-brand-500">
                                Lihat di Panel
                            </a>
                            <a href="{{ $source['open_url'] }}" target="_blank" rel="noopener"
                               class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-bold text-gray-600 transition hover:border-gray-300 hover:bg-white dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">
                                Buka di Tab Baru
                            </a>
                            @if ($source['download_url'])
                            <a href="{{ $source['download_url'] }}"
                               class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-bold text-gray-600 transition hover:border-gray-300 hover:bg-white dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">
                                Unduh
                            </a>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                        Belum ada sumber eviden yang tersimpan.
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-gray-500">Audit Trail Ringkas</p>
                <div class="mt-4 space-y-4 text-sm text-gray-600 dark:text-gray-300">
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">Dibuat oleh</p>
                        <p>{{ $evidence->creator?->name ?: 'Sistem / belum tercatat' }}</p>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">Diperbarui oleh</p>
                        <p>{{ $evidence->editor?->name ?: 'Belum ada pembaruan' }}</p>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">Verifikasi</p>
                        <p>{{ $evidence->verified_at ? $evidence->verified_at->format('d M Y H:i') : 'Belum diverifikasi' }}</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
@if (($textSearch['occurrence_count'] ?? 0) > 0)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const target = document.getElementById('evidence-text-search-target');
    const previousButton = document.getElementById('evidence-text-prev');
    const nextButton = document.getElementById('evidence-text-next');
    const status = document.getElementById('evidence-text-nav-status');

    if (!target || !previousButton || !nextButton || !status) {
        return;
    }

    const matches = Array.from(target.querySelectorAll('mark.evidence-text-match'));

    if (matches.length === 0) {
        return;
    }

    let currentIndex = 0;

    const setActiveMatch = function (index) {
        matches.forEach(function (match, matchIndex) {
            match.classList.toggle('bg-orange-400', matchIndex === index);
            match.classList.toggle('ring-2', matchIndex === index);
            match.classList.toggle('ring-white', matchIndex === index);
            match.classList.toggle('ring-offset-1', matchIndex === index);
            match.classList.toggle('ring-offset-gray-950', matchIndex === index);
            match.classList.toggle('bg-amber-300', matchIndex !== index);
        });

        currentIndex = index;
        status.textContent = 'Hasil ' + (currentIndex + 1) + ' dari ' + matches.length;

        matches[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
    };

    previousButton.addEventListener('click', function () {
        setActiveMatch((currentIndex - 1 + matches.length) % matches.length);
    });

    nextButton.addEventListener('click', function () {
        setActiveMatch((currentIndex + 1) % matches.length);
    });

    setActiveMatch(0);
});
</script>
@endif
@endpush

@push('scripts')
@if ($annotationsModuleReady && ($textPreview['status'] ?? null) === 'ready')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const target = document.getElementById('evidence-text-search-target');
    const buttons = document.querySelectorAll('.annotation-selection-button');

    if (!target || buttons.length === 0) {
        return;
    }

    const selectionLivesInsideTarget = function (selection) {
        if (!selection || selection.rangeCount === 0) {
            return false;
        }

        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;

        return target.contains(container.nodeType === Node.TEXT_NODE ? container.parentNode : container);
    };

    buttons.forEach(function (button) {
        const originalLabel = button.textContent;

        button.addEventListener('click', function () {
            const selection = window.getSelection();
            const selectedText = selection ? selection.toString().trim() : '';
            const targetId = button.getAttribute('data-annotation-fill-target');
            const field = targetId ? document.getElementById(targetId) : null;

            if (!field) {
                return;
            }

            if (selectedText === '' || !selectionLivesInsideTarget(selection)) {
                button.textContent = 'Pilih teks di panel dulu';
                window.setTimeout(function () {
                    button.textContent = originalLabel;
                }, 1800);
                return;
            }

            field.value = selectedText;
            field.focus();
            button.textContent = 'Teks dipindahkan';

            window.setTimeout(function () {
                button.textContent = originalLabel;
            }, 1400);
        });
    });
});
</script>
@endif
@endpush
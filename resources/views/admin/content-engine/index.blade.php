@extends('admin.layouts.admin')

@section('page-title', 'AI Content Engine')
@section('page-subtitle', 'Upload foto · AI generate artikel & caption Instagram')

@section('content')

{{-- ── Upload Card ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">

    <div class="lg:col-span-5 bg-white dark:bg-gray-900
                rounded-2xl border border-gray-100 dark:border-gray-800 p-6"
         x-data="{
             uploading:  false,
             progress:   0,
             pollJob:    null,
             error:      '',
             success:    '',

             pickFile(e) {
                 const file = e.target.files[0];
                 if (!file) return;
                 this.$refs.fileLabel.textContent = file.name;
             },

             async submit(form) {
                 if (this.uploading) return;
                 this.uploading = true;
                 this.error = '';
                 this.success = '';

                 const fd = new FormData(form);
                 try {
                     const res = await fetch(form.action, {
                         method: 'POST',
                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: fd,
                     });
                     const data = await res.json();
                     if (!res.ok) {
                         this.error = data.message ?? JSON.stringify(data.errors ?? data);
                     } else {
                         this.success = data.message;
                         form.reset();
                         this.$refs.fileLabel.textContent = 'Pilih foto atau video…';
                         // Start polling for result
                         this.startPolling(data.job_id, data.poll_url);
                     }
                 } catch (e) {
                     this.error = 'Gagal mengunggah. Periksa koneksi.';
                 } finally {
                     this.uploading = false;
                 }
             },

             startPolling(jobId, pollUrl) {
                 if (this.pollJob) clearInterval(this.pollJob);
                 let attempts = 0;
                 this.pollJob = setInterval(async () => {
                     attempts++;
                     if (attempts > 60) { clearInterval(this.pollJob); return; }
                     try {
                         const res  = await fetch(pollUrl);
                         const data = await res.json();
                         if (data.status === 'done' && data.show_url) {
                             clearInterval(this.pollJob);
                             window.location.href = data.show_url;
                         } else if (data.status === 'error') {
                             clearInterval(this.pollJob);
                             this.error = 'AI gagal memproses. Lihat tabel di bawah.';
                         }
                     } catch {}
                 }, 4000);
             }
         }">

        <div class="flex items-center gap-2 mb-5">
            <span class="text-xl" aria-hidden="true">📤</span>
            <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Upload Media Baru</h2>
        </div>

        <div x-show="error"   x-text="error"
             class="mb-4 rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800
                    px-4 py-2.5 text-xs text-red-700 dark:text-red-300" role="alert"></div>
        <div x-show="success" x-text="success"
             class="mb-4 rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800
                    px-4 py-2.5 text-xs text-green-700 dark:text-green-300" role="status"></div>

        <form action="{{ route('admin.content-engine.upload') }}" method="POST"
              enctype="multipart/form-data" @submit.prevent="submit($el)" class="space-y-3">
            @csrf

            {{-- File drop zone --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                    Foto / Video *
                </label>
                <label class="flex flex-col items-center justify-center gap-2
                              border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl
                              px-4 py-6 cursor-pointer text-center
                              hover:border-brand-400 hover:bg-brand-50 dark:hover:bg-brand-950/30
                              transition-colors group">
                    <span class="text-3xl group-hover:scale-110 transition-transform" aria-hidden="true">🖼️</span>
                    <span x-ref="fileLabel" class="text-xs text-gray-400 dark:text-gray-500">
                        Pilih foto atau video…
                    </span>
                    <span class="text-[10px] text-gray-300 dark:text-gray-600">
                        JPG, PNG, WebP, MP4 · maks 50 MB
                    </span>
                    <input type="file" name="media" class="sr-only" required
                           accept=".jpg,.jpeg,.png,.webp,.mp4,.mov"
                           @change="pickFile($event)">
                </label>
            </div>

            {{-- Metadata fields --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="ce-place" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">
                        Tempat *
                    </label>
                    <input id="ce-place" name="place" type="text" required placeholder="Aula Gedung A"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs
                                  text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                </div>
                <div>
                    <label for="ce-date" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">
                        Tanggal *
                    </label>
                    <input id="ce-date" name="date" type="date" required
                           value="{{ date('Y-m-d') }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs
                                  text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                </div>
            </div>

            <div>
                <label for="ce-subject" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">
                    Nama Acara / Subjek *
                </label>
                <input id="ce-subject" name="subject" type="text" required
                       placeholder="Wisuda Semester Genap 2026"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs
                              text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="ce-kategori" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">
                        Kategori *
                    </label>
                    <select id="ce-kategori" name="kategori" required
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                   bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs
                                   text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                        <option value="berita">Berita</option>
                        <option value="kegiatan">Kegiatan</option>
                        <option value="pengumuman">Pengumuman</option>
                        <option value="prestasi">Prestasi</option>
                    </select>
                </div>
                <div>
                    <label for="ce-title" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">
                        Saran Judul
                    </label>
                    <input id="ce-title" name="title" type="text"
                           placeholder="(opsional)"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs
                                  text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                </div>
            </div>

            <div>
                <label for="ce-notes" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">
                    Catatan Tambahan
                </label>
                <textarea id="ce-notes" name="notes" rows="2"
                          placeholder="Konteks khusus, nama tokoh penting, dsb…"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs resize-none
                                 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300"></textarea>
            </div>

            <div>
                <label for="ce-tags" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1">
                    Tags Tambahan (untuk caption IG)
                </label>
                <input id="ce-tags" name="tags" type="text"
                       placeholder="wisuda, mahasiswa, kesehatan"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs
                              text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>

            <button type="submit" :disabled="uploading"
                    class="w-full flex items-center justify-center gap-2
                           rounded-xl bg-brand-600 hover:bg-brand-500 disabled:bg-gray-300 dark:disabled:bg-gray-700
                           px-5 py-3 text-sm font-bold text-white transition-colors">
                <svg x-show="uploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span x-text="uploading ? 'Mengupload & memproses…' : '🚀 Generate dengan AI'"></span>
            </button>
        </form>
    </div>

    {{-- Info panel --}}
    <div class="lg:col-span-7 space-y-4">
        <div class="bg-gradient-to-br from-brand-600 to-accent-600 rounded-2xl p-6 text-white">
            <h3 class="text-base font-extrabold mb-3">Cara Kerja Content Engine</h3>
            <ol class="space-y-2.5 text-sm">
                @foreach([
                    ['🖼️', 'Upload foto/video acara kampus + isi metadata'],
                    ['👁️', 'Vision AI mendeskripsikan konten visual secara detail'],
                    ['📝', 'GPT-4o-mini menulis artikel berita lengkap dalam format jurnalistik'],
                    ['📸', 'AI membuat caption Instagram yang viral + 30 hashtag'],
                    ['✅', 'Review & edit hasil, lalu publish 1 klik ke halaman Berita'],
                ] as [$icon, $text])
                <li class="flex items-start gap-3">
                    <span class="text-base flex-shrink-0 mt-0.5" aria-hidden="true">{{ $icon }}</span>
                    <span class="text-white/85">{{ $text }}</span>
                </li>
                @endforeach
            </ol>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-base" aria-hidden="true">📊</span>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Statistik</h3>
            </div>
            @php
                $stats = [
                    'Total Jobs'    => \App\Models\ContentJob::count(),
                    'Selesai'       => \App\Models\ContentJob::where('status', 'done')->count(),
                    'Dipublikasi'   => \App\Models\ContentJob::where('status', 'published')->count(),
                    'Error'         => \App\Models\ContentJob::where('status', 'error')->count(),
                ];
            @endphp
            <div class="grid grid-cols-4 gap-2">
                @foreach($stats as $label => $val)
                <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                    <div class="text-xl font-black text-gray-900 dark:text-white">{{ $val }}</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>

        @if($accreditationFollowUp)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-amber-200 dark:border-amber-900 p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-base" aria-hidden="true">🗂️</span>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Tindak Lanjut Akreditasi</h3>
                    </div>
                    <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Ringkasan ini hanya muncul untuk admin yang memiliki akses ke ruang eviden akreditasi dan membantu melihat catatan reviewer aktif tanpa membuka modul satu per satu.
                    </p>
                </div>
                <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                    {{ $accreditationFollowUp['pending_evidences'] }} eviden aktif
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-rose-50 dark:bg-rose-950/30 p-3">
                    <div class="text-xl font-black text-rose-700 dark:text-rose-300">{{ $accreditationFollowUp['open'] }}</div>
                    <div class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-700 dark:text-rose-300">Belum Ditindaklanjuti</div>
                </div>
                <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 p-3">
                    <div class="text-xl font-black text-amber-700 dark:text-amber-300">{{ $accreditationFollowUp['in_progress'] }}</div>
                    <div class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">Sedang Diproses</div>
                </div>
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/30 p-3">
                    <div class="text-xl font-black text-emerald-700 dark:text-emerald-300">{{ $accreditationFollowUp['resolved'] }}</div>
                    <div class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">Selesai</div>
                </div>
                <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3">
                    <div class="text-xl font-black text-gray-900 dark:text-white">{{ $accreditationFollowUp['review_due_soon'] }}</div>
                    <div class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Review ≤ 30 Hari</div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 dark:border-gray-800 px-4 py-3">
                <div class="flex items-center justify-between gap-3 text-xs">
                    <span class="font-semibold text-gray-500 dark:text-gray-400">Total anotasi reviewer</span>
                    <span class="font-black text-gray-900 dark:text-white">{{ $accreditationFollowUp['total'] }}</span>
                </div>
            </div>

            <div class="mt-4">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Filter Cepat</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ $accreditationFollowUp['active_annotations_url'] }}" class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-rose-700 transition hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/60">
                        <span>Catatan Aktif</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-rose-700 dark:bg-rose-900/70 dark:text-rose-200">{{ $accreditationFollowUp['active_annotations'] }}</span>
                    </a>
                    <a href="{{ $accreditationFollowUp['missing_sources_url'] }}" class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-amber-700 transition hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-300 dark:hover:bg-amber-900/60">
                        <span>Tanpa Sumber</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-amber-700 dark:bg-amber-900/70 dark:text-amber-200">{{ $accreditationFollowUp['missing_sources'] }}</span>
                    </a>
                    <a href="{{ $accreditationFollowUp['review_overdue_url'] }}" class="inline-flex items-center gap-2 rounded-full bg-fuchsia-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-fuchsia-700 transition hover:bg-fuchsia-100 dark:bg-fuchsia-950/50 dark:text-fuchsia-300 dark:hover:bg-fuchsia-900/60">
                        <span>Review Lewat</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-fuchsia-700 dark:bg-fuchsia-900/70 dark:text-fuchsia-200">{{ $accreditationFollowUp['review_overdue'] }}</span>
                    </a>
                    <a href="{{ $accreditationFollowUp['review_due_soon_url'] }}" class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950/50 dark:text-sky-300 dark:hover:bg-sky-900/60">
                        <span>Review ≤ 30 Hari</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-sky-700 dark:bg-sky-900/70 dark:text-sky-200">{{ $accreditationFollowUp['review_due_soon'] }}</span>
                    </a>
                    <a href="{{ $accreditationFollowUp['expires_soon_url'] }}" class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-orange-700 transition hover:bg-orange-100 dark:bg-orange-950/50 dark:text-orange-300 dark:hover:bg-orange-900/60">
                        <span>Berlaku Habis ≤ 30 Hari</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-orange-700 dark:bg-orange-900/70 dark:text-orange-200">{{ $accreditationFollowUp['expires_soon'] }}</span>
                    </a>
                    <a href="{{ $accreditationFollowUp['expired_url'] }}" class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-red-700 transition hover:bg-red-100 dark:bg-red-950/50 dark:text-red-300 dark:hover:bg-red-900/60">
                        <span>Kadaluarsa</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-red-700 dark:bg-red-900/70 dark:text-red-200">{{ $accreditationFollowUp['expired'] }}</span>
                    </a>
                </div>

                @if ($accreditationFollowUp['review_overdue'] > 0)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-fuchsia-100 bg-fuchsia-50/70 px-4 py-3 dark:border-fuchsia-900/60 dark:bg-fuchsia-950/20">
                    <p class="text-xs leading-5 text-fuchsia-900 dark:text-fuchsia-100">Backlog review lewat bisa langsung diekspor untuk dibagikan ke PIC tanpa membuka ruang eviden.</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ $accreditationFollowUp['review_overdue_csv_export_url'] }}" class="inline-flex items-center justify-center rounded-full bg-white px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-fuchsia-700 transition hover:bg-fuchsia-100 dark:bg-fuchsia-900/60 dark:text-fuchsia-100 dark:hover:bg-fuchsia-900">Ekspor CSV</a>
                        <a href="{{ $accreditationFollowUp['review_overdue_xlsx_export_url'] }}" class="inline-flex items-center justify-center rounded-full bg-fuchsia-700 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-white transition hover:bg-fuchsia-800 dark:bg-fuchsia-800 dark:hover:bg-fuchsia-700">Ekspor XLSX</a>
                    </div>
                </div>
                @endif
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Kriteria Prioritas</p>
                    <span class="text-[11px] font-semibold text-gray-400">Top {{ count($accreditationFollowUp['priority_folders']) }}</span>
                </div>

                @if(!empty($accreditationFollowUp['priority_folders']))
                <div class="mt-3 space-y-2">
                    @foreach($accreditationFollowUp['priority_folders'] as $priorityFolder)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 px-4 py-3 transition hover:border-brand-200 hover:bg-brand-50/60 dark:hover:border-brand-900 dark:hover:bg-brand-950/20">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $priorityFolder['label'] }}</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                    @if($priorityFolder['pending_annotations'] > 0)
                                    <span class="rounded-full bg-rose-50 px-2 py-1 text-rose-700 dark:bg-rose-950 dark:text-rose-300">Catatan aktif {{ $priorityFolder['pending_annotations'] }}</span>
                                    @endif
                                    @if($priorityFolder['missing_sources'] > 0)
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950 dark:text-amber-300">Tanpa sumber {{ $priorityFolder['missing_sources'] }}</span>
                                    @endif
                                    @if($priorityFolder['review_overdue'] > 0)
                                    <span class="rounded-full bg-fuchsia-50 px-2 py-1 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300">Review lewat {{ $priorityFolder['review_overdue'] }}</span>
                                    @endif
                                    @if($priorityFolder['review_due_soon'] > 0)
                                    <span class="rounded-full bg-sky-50 px-2 py-1 text-sky-700 dark:bg-sky-950 dark:text-sky-300">Review ≤ 30 hari {{ $priorityFolder['review_due_soon'] }}</span>
                                    @endif
                                    @if($priorityFolder['expiry_attention'] > 0)
                                    <span class="rounded-full bg-orange-50 px-2 py-1 text-orange-700 dark:bg-orange-950 dark:text-orange-300">Berlaku habis/kadaluarsa {{ $priorityFolder['expiry_attention'] }}</span>
                                    @endif
                                    @if($priorityFolder['is_empty'])
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Belum ada eviden</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ $priorityFolder['url'] }}" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 transition hover:text-brand-600 dark:hover:text-brand-300">Buka</a>
                        </div>

                        @if($priorityFolder['review_overdue'] > 0 || $priorityFolder['review_due_soon'] > 0 || $priorityFolder['expiry_attention'] > 0)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2 dark:border-slate-800 dark:bg-slate-950/20">
                            <p class="text-[11px] leading-5 text-slate-700 dark:text-slate-200">Buka antrean spesifik per kriteria tanpa memilah tabel secara manual.</p>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($priorityFolder['review_overdue'] > 0)
                                <a href="{{ $priorityFolder['review_overdue_url'] }}" class="inline-flex items-center justify-center rounded-full bg-fuchsia-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-fuchsia-700 transition hover:bg-fuchsia-100 dark:bg-fuchsia-950/60 dark:text-fuchsia-200 dark:hover:bg-fuchsia-900">Review Lewat</a>
                                @endif
                                @if($priorityFolder['review_due_soon'] > 0)
                                <a href="{{ $priorityFolder['review_due_soon_url'] }}" class="inline-flex items-center justify-center rounded-full bg-sky-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950/60 dark:text-sky-200 dark:hover:bg-sky-900">Review ≤ 30 Hari</a>
                                @endif
                                @if($priorityFolder['expiry_attention'] > 0)
                                <a href="{{ $priorityFolder['expiry_attention_url'] }}" class="inline-flex items-center justify-center rounded-full bg-orange-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-orange-700 transition hover:bg-orange-100 dark:bg-orange-950/60 dark:text-orange-200 dark:hover:bg-orange-900">Berlaku</a>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">Belum ada kriteria yang masuk prioritas tindak lanjut.</p>
                @endif
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">PIC Prioritas</p>
                    <span class="text-[11px] font-semibold text-gray-400">Top {{ count($accreditationFollowUp['priority_pics']) }}</span>
                </div>

                @if(!empty($accreditationFollowUp['priority_pics']))
                <div class="mt-3 space-y-2">
                    @foreach($accreditationFollowUp['priority_pics'] as $priorityPic)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 px-4 py-3 transition hover:border-brand-200 hover:bg-brand-50/60 dark:hover:border-brand-900 dark:hover:bg-brand-950/20">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $priorityPic['name'] }}</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                    @if($priorityPic['pending_evidences'] > 0)
                                    <span class="rounded-full bg-rose-50 px-2 py-1 text-rose-700 dark:bg-rose-950 dark:text-rose-300">Catatan aktif {{ $priorityPic['pending_evidences'] }}</span>
                                    @endif
                                    @if($priorityPic['missing_sources'] > 0)
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950 dark:text-amber-300">Tanpa sumber {{ $priorityPic['missing_sources'] }}</span>
                                    @endif
                                    @if($priorityPic['review_overdue'] > 0)
                                    <span class="rounded-full bg-fuchsia-50 px-2 py-1 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300">Review lewat {{ $priorityPic['review_overdue'] }}</span>
                                    @endif
                                    @if($priorityPic['review_due_soon'] > 0)
                                    <span class="rounded-full bg-sky-50 px-2 py-1 text-sky-700 dark:bg-sky-950 dark:text-sky-300">Review ≤ 30 hari {{ $priorityPic['review_due_soon'] }}</span>
                                    @endif
                                    @if($priorityPic['expiry_attention'] > 0)
                                    <span class="rounded-full bg-orange-50 px-2 py-1 text-orange-700 dark:bg-orange-950 dark:text-orange-300">Berlaku habis/kadaluarsa {{ $priorityPic['expiry_attention'] }}</span>
                                    @endif
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Total eviden {{ $priorityPic['total_evidences'] }}</span>
                                </div>
                            </div>
                            <a href="{{ $priorityPic['url'] }}" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 transition hover:text-brand-600 dark:hover:text-brand-300">Buka</a>
                        </div>

                        @if($priorityPic['review_overdue'] > 0)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-fuchsia-100 bg-fuchsia-50/70 px-3 py-2 dark:border-fuchsia-900/60 dark:bg-fuchsia-950/20">
                            <p class="text-[11px] leading-5 text-fuchsia-900 dark:text-fuchsia-100">Ekspor antrean review lewat untuk PIC ini dengan filter yang sudah terpasang.</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ $priorityPic['review_overdue_url'] }}" class="inline-flex items-center justify-center rounded-full bg-white px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-fuchsia-700 transition hover:bg-fuchsia-100 dark:bg-fuchsia-900/60 dark:text-fuchsia-100 dark:hover:bg-fuchsia-900">Lihat</a>
                                <a href="{{ $priorityPic['review_overdue_csv_export_url'] }}" class="inline-flex items-center justify-center rounded-full bg-white px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-fuchsia-700 transition hover:bg-fuchsia-100 dark:bg-fuchsia-900/60 dark:text-fuchsia-100 dark:hover:bg-fuchsia-900">CSV</a>
                                <a href="{{ $priorityPic['review_overdue_xlsx_export_url'] }}" class="inline-flex items-center justify-center rounded-full bg-fuchsia-700 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-white transition hover:bg-fuchsia-800 dark:bg-fuchsia-800 dark:hover:bg-fuchsia-700">XLSX</a>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">Belum ada PIC yang masuk prioritas tindak lanjut.</p>
                @endif
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ $accreditationFollowUp['index_url'] }}" class="inline-flex items-center justify-center rounded-xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700 transition hover:bg-brand-100 dark:bg-brand-950 dark:text-brand-300 dark:hover:bg-brand-900/70">
                    Lihat Tindak Lanjut
                </a>
                <a href="{{ $accreditationFollowUp['xlsx_export_url'] }}" class="inline-flex items-center justify-center rounded-xl bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950 dark:text-sky-300 dark:hover:bg-sky-900/70">
                    Ekspor XLSX
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── Jobs Table ───────────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Riwayat Jobs</h2>
        <span class="text-xs text-gray-400">{{ $jobs->count() }} total</span>
    </div>

    @if($jobs->isEmpty())
        <div class="flex flex-col items-center py-14 text-gray-400 dark:text-gray-600">
            <div class="text-5xl mb-3" aria-hidden="true">🤖</div>
            <p class="text-sm font-medium">Belum ada job. Upload media pertama kamu!</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm" aria-label="Daftar content jobs">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Media</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Judul / Acara</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Dibuat</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @foreach($jobs as $job)
                        @php $badge = $job->statusBadge(); $meta = $job->metadata; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                                    <span aria-hidden="true">{{ $job->isImage() ? '🖼️' : '🎬' }}</span>
                                    {{ strtoupper($job->media_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white text-sm truncate max-w-48">
                                    {{ $job->generated_title ?? $job->title ?? $meta['subject'] ?? '—' }}
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5 truncate max-w-48">
                                    {{ $meta['place'] ?? '' }} · {{ $meta['date'] ?? '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $badge['class'] }}"
                                      @if(in_array($job->status, ['pending','processing']))
                                          x-data
                                          x-init="setInterval(async () => {
                                              const r = await fetch('{{ route('admin.content-engine.status', $job->id) }}');
                                              const d = await r.json();
                                              $el.textContent = d.badge_label;
                                              $el.className = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold ' + d.badge_class;
                                              if (d.show_url) window.location.href = d.show_url;
                                          }, 5000)"
                                      @endif>
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-400">
                                {{ $job->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($job->status === 'done')
                                        <a href="{{ route('admin.content-engine.show', $job->id) }}"
                                           class="rounded-lg bg-brand-50 dark:bg-brand-950 hover:bg-brand-100
                                                  px-3 py-1.5 text-xs font-bold text-brand-700 dark:text-brand-300
                                                  transition-colors">
                                            Review
                                        </a>
                                    @endif
                                    @if(in_array($job->status, ['done', 'error']))
                                        <form action="{{ route('admin.content-engine.regenerate', $job->id) }}" method="POST" class="inline">
                                            @csrf @method('POST')
                                            <button class="rounded-lg border border-gray-200 dark:border-gray-700
                                                           px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400
                                                           hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                                ↺ Ulang
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.content-engine.destroy', $job->id) }}" method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Hapus job ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-100 dark:border-red-900
                                                       px-3 py-1.5 text-xs font-medium text-red-400
                                                       hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

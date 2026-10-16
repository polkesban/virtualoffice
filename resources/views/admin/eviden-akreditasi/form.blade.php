@extends('admin.layouts.admin')

@section('title', isset($evidence->id) ? 'Edit Eviden Akreditasi' : 'Tambah Eviden Akreditasi')
@section('page-title', isset($evidence->id) ? 'Edit Eviden Akreditasi' : 'Tambah Eviden Akreditasi')
@section('page-subtitle', 'Simpan bukti per kriteria dalam bentuk tautan Dokumen Mutu, URL eksternal, atau file privat.')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.eviden-akreditasi.index') }}" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">← Kembali ke Ruang Eviden</a>
        @if (isset($evidence->id))
        <a href="{{ route('admin.eviden-akreditasi.show', $evidence) }}" class="inline-flex items-center justify-center rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700 transition hover:border-brand-300 hover:bg-brand-100 dark:border-brand-900 dark:bg-brand-950 dark:text-brand-300 dark:hover:bg-brand-900/70">Lihat Detail Eviden</a>
        @endif
    </div>

    <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 text-sm leading-6 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200">
        <p class="font-bold">Panduan singkat</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Pilih folder kriteria sesuai kebutuhan asesor.</li>
            <li>Set minimal satu sumber eviden: Dokumen Mutu, URL eksternal, atau file privat.</li>
            <li>Gunakan status <strong>Siap Review</strong> atau <strong>Terverifikasi</strong> saat bukti sudah siap dipakai.</li>
            <li>Isi PIC, tanggal review, dan masa berlaku agar dashboard cepat menunjukkan dokumen yang perlu ditindaklanjuti.</li>
        </ul>
    </div>

    <form method="POST"
          action="{{ isset($evidence->id) ? route('admin.eviden-akreditasi.update', $evidence) : route('admin.eviden-akreditasi.store') }}"
          enctype="multipart/form-data"
          class="space-y-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if (isset($evidence->id))
            @method('PUT')
        @endif

        @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
            <p class="font-semibold">Beberapa isian perlu diperbaiki:</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Folder Kriteria <span class="text-rose-600">*</span></label>
                <select name="folder_id" required class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                    <option value="">Pilih folder</option>
                    @foreach ($folders as $folder)
                    <option value="{{ $folder->id }}" @selected((string) old('folder_id', $evidence->folder_id) === (string) $folder->id)>{{ $folder->display_label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Status Verifikasi <span class="text-rose-600">*</span></label>
                <select name="verification_status" required class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                    @foreach ($statusOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('verification_status', $evidence->verification_status ?: 'draft') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Judul Eviden <span class="text-rose-600">*</span></label>
                <input type="text" name="title" value="{{ old('title', $evidence->title) }}" required
                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40"
                       placeholder="Contoh: Notulen review kurikulum bersama stakeholder eksternal">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Kode Butir / Subkriteria</label>
                <input type="text" name="subcriterion_code" value="{{ old('subcriterion_code', $evidence->subcriterion_code) }}"
                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40"
                       placeholder="Mis. 1.1.2 atau 4.2.6">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">PIC / Penanggung Jawab</label>
                <input type="text" name="pic_name" value="{{ old('pic_name', $evidence->pic_name) }}"
                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40"
                       placeholder="Contoh: Pusat Penjaminan Mutu">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Ringkasan Eviden</label>
            <textarea name="summary" rows="4" class="w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40" placeholder="Tuliskan konteks singkat dokumen, kegiatan, atau hasil yang dibuktikan oleh eviden ini.">{{ old('summary', $evidence->summary) }}</textarea>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Tanggal Dokumen</label>
                <input type="date" name="issued_at" value="{{ old('issued_at', optional($evidence->issued_at)->format('Y-m-d')) }}" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Review Berikutnya</label>
                <input type="date" name="review_due_at" value="{{ old('review_due_at', optional($evidence->review_due_at)->format('Y-m-d')) }}" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Masa Berlaku Sampai</label>
                <input type="date" name="expires_at" value="{{ old('expires_at', optional($evidence->expires_at)->format('Y-m-d')) }}" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
            </div>
        </div>

        <div class="rounded-3xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h2 class="text-base font-black text-gray-900 dark:text-white">Sumber Eviden</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Isi minimal satu sumber. Anda boleh memakai lebih dari satu sumber untuk eviden yang sama.</p>
            </div>
            <div class="space-y-5">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Tautkan ke Dokumen Mutu</label>
                    <select name="dokumen_mutu_id" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40">
                        <option value="">Tidak ditautkan</option>
                        @foreach ($dokumenMutuList as $dokumen)
                        <option value="{{ $dokumen->id }}" @selected((string) old('dokumen_mutu_id', $evidence->dokumen_mutu_id) === (string) $dokumen->id)>
                            {{ $dokumen->judul }}@if($dokumen->nomor_dok) · {{ $dokumen->nomor_dok }}@endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">URL Eksternal</label>
                    <input type="url" name="external_url" value="{{ old('external_url', $evidence->external_url) }}"
                           class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40"
                           placeholder="https://drive.google.com/... atau URL sistem eksternal">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">File Privat</label>
                    <input type="file" name="evidence_file"
                           class="block w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 file:mr-4 file:rounded-xl file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Gunakan untuk file privat yang tidak boleh dibuka publik. Maksimal 15 MB.</p>

                    @if ($evidence->hasUploadedFile())
                    <div class="mt-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900">
                        <p class="font-semibold text-gray-900 dark:text-white">File saat ini: {{ $evidence->file_name }}@if($evidence->human_file_size) · {{ $evidence->human_file_size }}@endif</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <a href="{{ route('admin.eviden-akreditasi.show', ['accreditationEvidence' => $evidence, 'source' => 'private_file']) }}" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">Lihat isi file privat</a>
                            <a href="{{ route('admin.eviden-akreditasi.download', $evidence) }}" class="text-sm font-semibold text-gray-600 hover:underline dark:text-gray-300">Unduh file privat</a>
                            <label class="inline-flex items-center gap-2 text-sm text-rose-700 dark:text-rose-300">
                                <input type="checkbox" name="remove_file" value="1" class="rounded border-gray-300 text-rose-600">
                                Hapus file saat ini
                            </label>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-gray-500">Catatan Internal</label>
            <textarea name="notes" rows="5" class="w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-brand-900/40" placeholder="Catatan verifikasi, tindak lanjut, atau konteks administratif lain yang tidak perlu ditampilkan ke publik.">{{ old('notes', $evidence->notes) }}</textarea>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                {{ isset($evidence->id) ? 'Simpan Perubahan' : 'Tambahkan Eviden' }}
            </button>
            <a href="{{ route('admin.eviden-akreditasi.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-200 px-5 py-3 text-sm font-bold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
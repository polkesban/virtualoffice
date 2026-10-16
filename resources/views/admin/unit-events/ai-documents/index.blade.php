@extends('admin.layouts.admin')
@section('title', 'Dokumen AI Event')
@section('page-title', 'Dokumen AI Event')
@section('page-subtitle', 'Buat dokumen pendukung event (KAK, Surat Keluar, Proposal, dll.) secara otomatis menggunakan AI')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[280px,1fr]">
        <!-- Sidebar: List of Documents -->
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100 dark:border-gray-800">
                    <h4 class="font-extrabold text-gray-900 dark:text-white">Daftar Dokumen</h4>
                    <a href="{{ route('admin.unit-events.ai-documents.index', [$unitPage, $unitEvent, 'new' => 1]) }}" 
                       class="text-xs font-bold text-brand-600 hover:underline">
                        + Baru
                    </a>
                </div>
                @if($documents->isEmpty())
                    <p class="text-xs text-gray-500 dark:text-gray-400 py-4 text-center">Belum ada dokumen AI.</p>
                @else
                    <div class="space-y-1">
                        @foreach($documents as $doc)
                            <a href="{{ route('admin.unit-events.ai-documents.index', [$unitPage, $unitEvent, 'document_id' => $doc->id]) }}"
                               class="block rounded-xl px-3 py-2.5 text-xs font-medium transition-colors {{ $activeDocument && $activeDocument->id === $doc->id ? 'bg-brand-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-750' }}">
                                <p class="font-bold truncate">{{ $doc->title }}</p>
                                <p class="mt-1 text-[10px] {{ $activeDocument && $activeDocument->id === $doc->id ? 'text-brand-100' : 'text-gray-400' }}">
                                    {{ $doc->created_at->diffForHumans() }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="space-y-6">
            @if(!$activeDocument || request()->has('new'))
                <!-- Form: Generate New Document -->
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Buat Dokumen Baru dengan AI</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tulis instruksi pembuatan dokumen. Anda dapat mengunggah template referensi (KAK, Proposal, atau format surat) sebagai acuan gaya penulisan.</p>

                    <form method="POST" action="{{ route('admin.unit-events.ai-documents.generate', [$unitPage, $unitEvent]) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama/Judul Dokumen <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required value="{{ old('title') }}" placeholder="Contoh: Kerangka Acuan Kerja (KAK) Webinar Kebidanan" 
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Petunjuk Pembuatan Dokumen (Prompt) <span class="text-red-500">*</span></label>
                            <textarea name="ai_prompt" rows="5" required placeholder="Jelaskan detail isi dokumen yang ingin Anda buat, misalnya: buatkan surat tugas pembicara Dr. Budi, atau detail anggaran dan susunan acara webinar..." 
                                      class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('ai_prompt') }}</textarea>
                        </div>

                        <div class="rounded-2xl border border-brand-200 bg-brand-50/50 p-4 dark:border-brand-900/30 dark:bg-brand-950/20">
                            <label class="mb-1 block text-sm font-bold text-brand-900 dark:text-brand-300">File Referensi / Template (Opsional)</label>
                            <input type="file" name="reference_file" accept=".txt,.pdf,.docx" 
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-750 hover:file:bg-brand-100 dark:file:bg-brand-900/40 dark:file:text-brand-300">
                            <p class="mt-1 text-xs text-brand-700/80 dark:text-brand-400/80">Mendukung file .txt, .pdf, atau .docx. AI akan membaca isi file ini untuk meniru format dan gaya bahasa dokumen acuan Anda.</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                ✨ Generate dengan AI
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <!-- Workspace: Edit & Export Document -->
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <form method="POST" action="{{ route('admin.unit-events.ai-documents.save', [$unitPage, $unitEvent]) }}">
                        @csrf
                        <input type="hidden" name="document_id" value="{{ $activeDocument->id }}">

                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
                            <div class="flex-1">
                                <label class="mb-1 block text-xs font-bold text-gray-400 uppercase tracking-wider">Judul Dokumen</label>
                                <input type="text" name="title" value="{{ old('title', $activeDocument->title) }}" required 
                                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-bold dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                            <div class="flex flex-wrap gap-2 pt-2 md:pt-0">
                                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                    Simpan Perubahan
                                </button>
                                <a href="{{ route('admin.unit-events.ai-documents.export', [$unitPage, $unitEvent, $activeDocument, 'format' => 'pdf']) }}" target="_blank"
                                   class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    🖨️ Cetak PDF
                                </a>
                                <a href="{{ route('admin.unit-events.ai-documents.export', [$unitPage, $unitEvent, $activeDocument, 'format' => 'docx']) }}"
                                   class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    📥 Unduh DOCX
                                </a>
                            </div>
                        </div>

                        <!-- Editor -->
                        <div class="mt-6">
                            <label class="mb-2 block text-sm font-bold text-gray-700 dark:text-gray-300">Editor Dokumen (TinyMCE Lokal)</label>
                            <textarea id="content_html" name="content_html" rows="20" data-rich-editor data-rich-editor-profile="full" data-rich-editor-height="500" 
                                      class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('content_html', $activeDocument->content_html) }}</textarea>
                        </div>
                    </form>
                </section>

                <!-- Prompt Context / Reference Summary -->
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Riwayat / Konteks Pembuatan Dokumen</h4>
                    <div class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                        <p><strong>Instruksi AI:</strong> {{ $activeDocument->ai_prompt }}</p>
                        @if($activeDocument->reference_filename)
                            <p><strong>File Acuan:</strong> {{ $activeDocument->reference_filename }} (telah diparse oleh AI)</p>
                        @endif
                    </div>

                    <!-- Generate another version -->
                    <div class="mt-6 border-t border-gray-100 pt-6 dark:border-gray-800">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Buat Versi Baru / Ulang dengan AI</h4>
                        <form method="POST" action="{{ route('admin.unit-events.ai-documents.generate', [$unitPage, $unitEvent]) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="title" value="{{ $activeDocument->title }}">
                            <div>
                                <textarea name="ai_prompt" rows="3" required placeholder="Tulis instruksi tambahan untuk memperbaiki/membuat ulang dokumen..." 
                                          class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                            </div>
                            <div class="flex items-center justify-between">
                                <input type="file" name="reference_file" accept=".txt,.pdf,.docx" 
                                       class="text-xs text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-gray-850 dark:file:text-gray-300">
                                <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-gray-800 dark:bg-gray-850 dark:hover:bg-gray-700">
                                    ✨ Generate Ulang
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('admin.layouts.admin')

@section('page-title', 'Review Konten AI')
@section('page-subtitle', $job->metadata['subject'] ?? 'Content Job #' . $job->id)

@section('content')

@php $meta = $job->metadata; @endphp

<div class="max-w-5xl space-y-6">

    {{-- ── Top action bar ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.content-engine.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400
                  hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
        <div class="flex items-center gap-2">
            @php $badge = $job->statusBadge(); @endphp
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            <span class="text-xs text-gray-400">Model: {{ $job->model_used ?? '—' }}</span>
        </div>
    </div>

    @if($job->status === 'error')
        <div class="rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 px-5 py-4">
            <p class="text-sm font-bold text-red-700 dark:text-red-300 mb-1">AI Error</p>
            <p class="text-xs text-red-600 dark:text-red-400 font-mono">{{ $job->error_message }}</p>
            <form action="{{ route('admin.content-engine.regenerate', $job->id) }}" method="POST" class="mt-3">
                @csrf
                <button class="rounded-lg bg-red-600 hover:bg-red-500 px-4 py-1.5 text-xs font-bold text-white transition-colors">
                    ↺ Coba Lagi
                </button>
            </form>
        </div>
    @endif

    @if($job->status === 'done')
    {{-- ── Main content form ─────────────────────────────────────────────── --}}
    <form action="{{ route('admin.content-engine.publish', $job->id) }}" method="POST"
          x-data="{ copied: false, copiedCaption: false }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- LEFT: Article --}}
            <div class="space-y-5">

                {{-- Image preview + AI description --}}
                @if($job->isImage())
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <img src="{{ route('admin.content-engine.media', $job->id) }}"
                         alt="Uploaded media"
                         class="w-full h-48 object-cover">
                    @if($job->ai_image_description)
                    <div class="p-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                            Deskripsi AI (Vision)
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                            {{ $job->ai_image_description }}
                        </p>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Generated Title (editable) --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                    <label for="gen-title" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                        Judul Artikel
                    </label>
                    <input id="gen-title" name="generated_title"
                           value="{{ $job->generated_title }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800 px-4 py-2.5
                                  text-sm font-bold text-gray-900 dark:text-white
                                  focus:outline-none focus:ring-2 focus:ring-brand-300">
                </div>

                {{-- Generated Article (editable textarea) --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                    <div class="flex items-center justify-between mb-2">
                        <label for="gen-article" class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Artikel (Markdown)
                        </label>
                        <button type="button"
                                @click="navigator.clipboard.writeText($refs.articleArea.value); copied=true; setTimeout(()=>copied=false,2000)"
                                class="text-xs text-gray-400 hover:text-brand-600 transition-colors">
                            <span x-text="copied ? '✅ Disalin!' : '📋 Salin'"></span>
                        </button>
                    </div>
                    <textarea id="gen-article" name="generated_article" rows="16"
                              x-ref="articleArea"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                     bg-gray-50 dark:bg-gray-800 px-4 py-3
                                     text-xs font-mono text-gray-800 dark:text-gray-200
                                     focus:outline-none focus:ring-2 focus:ring-brand-300 resize-y"
                              >{{ $job->generated_article }}</textarea>
                    <p class="mt-1.5 text-[10px] text-gray-400">
                        Edit langsung — perubahan disimpan saat publish.
                    </p>
                </div>
            </div>

            {{-- RIGHT: Caption + Publish --}}
            <div class="space-y-5">

                {{-- Metadata summary --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Metadata Acara</p>
                    <dl class="space-y-1">
                        @foreach(['subject' => 'Acara', 'place' => 'Tempat', 'date' => 'Tanggal', 'kategori' => 'Kategori'] as $key => $label)
                        <div class="flex items-start gap-2 text-xs">
                            <dt class="font-bold text-gray-500 w-16 flex-shrink-0">{{ $label }}</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $meta[$key] ?? '—' }}</dd>
                        </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Instagram Caption (editable) --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-base" aria-hidden="true">📸</span>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Caption Instagram</p>
                        </div>
                        <button type="button"
                                @click="navigator.clipboard.writeText($refs.captionArea.value); copiedCaption=true; setTimeout(()=>copiedCaption=false,2000)"
                                class="text-xs text-gray-400 hover:text-brand-600 transition-colors">
                            <span x-text="copiedCaption ? '✅ Disalin!' : '📋 Salin'"></span>
                        </button>
                    </div>
                    <textarea name="generated_caption" rows="10"
                              x-ref="captionArea"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                     bg-gray-50 dark:bg-gray-800 px-4 py-3
                                     text-xs text-gray-800 dark:text-gray-200 leading-relaxed
                                     focus:outline-none focus:ring-2 focus:ring-brand-300 resize-y"
                              >{{ $job->generated_caption }}</textarea>
                </div>

                {{-- Hashtags --}}
                @if($job->generated_hashtags)
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Hashtags</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($job->generated_hashtags as $tag)
                        <span class="rounded-full bg-brand-50 dark:bg-brand-950 border border-brand-200 dark:border-brand-800
                                     px-2.5 py-0.5 text-xs font-medium text-brand-700 dark:text-brand-300">
                            {{ $tag }}
                        </span>
                        @endforeach
                    </div>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ implode(' ', $job->generated_hashtags) }}')"
                            class="mt-3 text-xs text-gray-400 hover:text-brand-600">
                        📋 Salin semua hashtag
                    </button>
                </div>
                @endif

                {{-- Publish button --}}
                <div class="bg-gradient-to-br from-brand-600 to-accent-700 rounded-2xl p-5">
                    <h3 class="text-sm font-extrabold text-white mb-1">Siap Dipublikasi?</h3>
                    <p class="text-xs text-white/70 mb-4">
                        Artkel akan disimpan ke halaman Berita dan bisa langsung dilihat pengunjung.
                    </p>
                    <button type="submit"
                            onclick="return confirm('Publikasikan artikel ini ke halaman Berita?')"
                            class="w-full flex items-center justify-center gap-2
                                   rounded-xl bg-white hover:bg-brand-50
                                   px-5 py-3 text-sm font-bold text-brand-700
                                   transition-colors shadow-lg">
                        🚀 Publish ke Berita
                    </button>
                </div>

                {{-- Regenerate --}}
                <form action="{{ route('admin.content-engine.regenerate', $job->id) }}" method="POST">
                    @csrf
                    <button type="button" onclick="this.form.submit()"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                   px-5 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400
                                   hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        ↺ Generate Ulang dengan AI
                    </button>
                </form>
            </div>
        </div>
    </form>
    @endif

    @if(in_array($job->status, ['pending', 'processing']))
    <div class="flex flex-col items-center py-16 text-center"
         x-data
         x-init="setInterval(async () => {
             const r = await fetch('{{ route('admin.content-engine.status', $job->id) }}');
             const d = await r.json();
             if (d.status === 'done') window.location.reload();
         }, 5000)">
        <div class="text-5xl mb-4 animate-bounce" aria-hidden="true">🤖</div>
        <p class="text-lg font-bold text-gray-900 dark:text-white">AI sedang bekerja…</p>
        <p class="text-sm text-gray-400 mt-2">
            Mendeskripsikan gambar, membuat artikel, dan caption Instagram.
        </p>
        <p class="text-xs text-gray-300 dark:text-gray-600 mt-1">
            Halaman otomatis refresh saat selesai.
        </p>
    </div>
    @endif

    @if($job->status === 'published')
    <div class="flex flex-col items-center py-12 text-center">
        <div class="text-5xl mb-3" aria-hidden="true">🎉</div>
        <p class="text-lg font-bold text-gray-900 dark:text-white">Sudah Dipublikasi!</p>
        <a href="{{ route('berita') }}" target="_blank"
           class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500
                  px-5 py-2.5 text-sm font-bold text-white transition-colors">
            Lihat di Halaman Berita →
        </a>
    </div>
    @endif

</div>

@endsection

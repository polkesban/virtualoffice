@extends('admin.layouts.admin')

@section('page-title', 'Kelola Podcast')
@section('page-subtitle', 'Unggah dan kelola file audio podcast NotebookLM Anda untuk dimainkan langsung di Poltekkes Bandung Radio')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- ── Form Unggah Podcast Card ─────────────────────────────────────────────────── --}}
    <div class="lg:col-span-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 flex flex-col justify-between self-start">
        <div>
            <div class="flex items-center gap-2 mb-5">
                <span class="text-xl" aria-hidden="true">🎙️</span>
                <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Unggah Episode Baru</h2>
            </div>

            @if(session('error'))
                <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 px-4 py-2.5 text-xs text-red-700 dark:text-red-300" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 px-4 py-2.5 text-xs text-green-700 dark:text-green-300" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 px-4 py-2.5 text-xs text-red-700 dark:text-red-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.podcasts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="podcast-title" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                        Judul Episode *
                    </label>
                    <input id="podcast-title" name="title" type="text" required placeholder="Contoh: Pengenalan Visi Misi Poltekkes Bandung" value="{{ old('title') }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 py-2.5 px-3.5 text-xs focus:border-brand-500 focus:outline-none dark:text-white transition-shadow focus:ring-2 focus:ring-brand-500/20">
                </div>

                <div>
                    <label for="podcast-desc" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                        Deskripsi / Catatan Episode
                    </label>
                    <textarea id="podcast-desc" name="description" rows="3" placeholder="Tulis ringkasan isi obrolan podcast..."
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 py-2.5 px-3.5 text-xs focus:border-brand-500 focus:outline-none dark:text-white transition-shadow focus:ring-2 focus:ring-brand-500/20">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label for="podcast-audio" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                        File Audio Podcast * (MP3, M4A, WAV, OGG)
                    </label>
                    <input id="podcast-audio" name="audio" type="file" required accept="audio/*"
                           class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-950 dark:file:text-brand-300">
                    <p class="mt-1 text-[10px] text-gray-400">Ukuran file maksimal: 100MB.</p>
                </div>

                <div>
                    <label for="podcast-cover" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                        Gambar Sampul / Cover Art (Opsional)
                    </label>
                    <input id="podcast-cover" name="cover" type="file" accept="image/*"
                           class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-950 dark:file:text-brand-300">
                    <p class="mt-1 text-[10px] text-gray-400">Dimensi persegi disarankan. Maksimal: 10MB.</p>
                </div>

                <button type="submit"
                        class="w-full inline-flex h-10 items-center justify-center rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold shadow-md hover:shadow-lg transition-all focus:outline-none">
                    🎙️ Unggah & Simpan ke GDrive
                </button>
            </form>
        </div>
    </div>

    {{-- ── Daftar Podcast Episode Card ─────────────────────────────────────────────────── --}}
    <div class="lg:col-span-7 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-2 mb-5">
                <span class="text-xl" aria-hidden="true">🎧</span>
                <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Daftar Episode Aktif</h2>
            </div>

            @if(empty($episodes))
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <span class="text-4xl mb-3">🎙️</span>
                    <p class="text-xs font-bold text-gray-400">Belum ada episode podcast yang diunggah.</p>
                    <p class="text-[10px] text-gray-400">Silakan isi formulir di panel kiri untuk mengunggah.</p>
                </div>
            @else
                <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                    @foreach($episodes as $index => $episode)
                        <div class="flex items-start gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/20">
                            {{-- Cover Preview --}}
                            <div class="h-16 w-16 rounded-xl overflow-hidden bg-slate-800 shrink-0 border border-gray-200 dark:border-gray-800 flex items-center justify-center">
                                @if(!empty($episode['image_url']))
                                    <img src="{{ $episode['image_url'] }}" alt="Cover" class="h-full w-full object-cover">
                                @else
                                    <span class="text-2xl">🎙️</span>
                                @endif
                            </div>

                            {{-- Metadata --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs font-extrabold text-gray-900 dark:text-white truncate">{{ $episode['title'] }}</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ $episode['description'] ?: 'Tidak ada deskripsi.' }}
                                </p>
                                <div class="flex items-center gap-3 mt-2 text-[10px] text-gray-400">
                                    <span>📅 {{ \Carbon\Carbon::parse($episode['created_at'])->format('d M Y H:i') }}</span>
                                    <span class="truncate max-w-[200px]">🔗 <a href="{{ $episode['audio_url'] }}" target="_blank" class="text-brand-600 hover:underline">Link GDrive</a></span>
                                </div>
                            </div>

                            {{-- Delete action --}}
                            <div class="shrink-0">
                                <form action="{{ route('admin.podcasts.destroy') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus episode podcast ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="id" value="{{ $episode['id'] }}">
                                    <button type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-950/60 text-red-600 dark:text-red-400 transition-colors"
                                            title="Hapus Podcast">
                                        ❌
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 text-[10px] text-gray-400 flex items-center justify-between">
            <span>Total: {{ count($episodes) }} episode</span>
            <span>Semua file disimpan di Google Drive Anda secara terstruktur.</span>
        </div>
    </div>

</div>

@endsection

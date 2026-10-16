@extends('admin.layouts.admin')
@section('title', 'Kelola Galeri')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🖼️ Galeri</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola foto & galeri kampus</p>
        </div>
        <a href="{{ route('admin.galeri.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Upload Foto
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        @forelse($galeri as $g)
        <div class="group bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="relative aspect-[4/3] bg-gray-100 dark:bg-gray-800">
                <img src="{{ \App\Support\MediaStorage::url($g->file, 'galeri') }}" alt="{{ $g->alt }}"
                     class="w-full h-full object-cover"
                     loading="lazy"
                     onerror="this.src='https://placehold.co/400x300?text=Error'">
                @unless($g->tampil)
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <span class="text-white text-xs font-bold bg-black/50 px-2 py-1 rounded-lg">Disembunyikan</span>
                </div>
                @endunless
            </div>
            <div class="p-3">
                <p class="text-xs font-bold text-gray-900 dark:text-white line-clamp-1">{{ $g->judul }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ \App\Models\Galeri::$kategoriLabels[$g->kategori] ?? $g->kategori }}</p>
                <div class="flex gap-1.5 mt-2">
                    <a href="{{ route('admin.galeri.edit', $g) }}"
                       class="flex-1 text-center py-1 rounded-lg text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('admin.galeri.toggle', $g) }}">
                        @csrf @method('PATCH')
                        <button type="submit" title="{{ $g->tampil ? 'Sembunyikan' : 'Tampilkan' }}"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-500 hover:text-brand-600 transition-colors">
                            {{ $g->tampil ? '👁️' : '🚫' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.galeri.destroy', $g) }}"
                          onsubmit="return confirm('Hapus foto ini?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 transition-colors">
                            🗑️
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 text-center text-gray-400">
            Belum ada foto. <a href="{{ route('admin.galeri.create') }}" class="text-brand-600 hover:underline">Upload sekarang</a>
        </div>
        @endforelse
    </div>
</div>
@endsection

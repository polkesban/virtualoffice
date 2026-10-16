@extends('admin.layouts.admin')
@section('title', 'Kelola Berita')

@section('content')
<div x-data="{
    viewMode: localStorage.getItem('admin_berita_view_mode') || 'table',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('admin_berita_view_mode', mode);
    }
}" class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">📰 Berita</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola artikel berita kampus</p>
        </div>
        <a href="{{ route('admin.berita.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Berita
        </a>
    </div>

    {{-- Filter + View Switcher --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
        <form method="GET" class="flex flex-wrap gap-3">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari judul..."
                   class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm w-56">
            <select name="kategori" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Semua Kategori</option>
                @foreach(['prestasi'=>'Prestasi','pengumuman'=>'Pengumuman','kegiatan'=>'Kegiatan','akademik'=>'Akademik','umum'=>'Umum'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('kategori')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="published" @selected(request('status')==='published')>Published</option>
                <option value="draft"     @selected(request('status')==='draft')>Draft</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Cari</button>
            @if(request()->hasAny(['cari','kategori','status']))
            <a href="{{ route('admin.berita.index') }}" class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm">Reset</a>
            @endif
        </form>

        <div class="flex items-center gap-1.5 shrink-0 bg-white dark:bg-gray-900 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm self-end sm:self-auto">
            <button type="button" 
                    @click="switchViewMode('table')"
                    :class="viewMode === 'table' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-semibold'"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                <span>📋</span> Tabel
            </button>
            <button type="button" 
                    @click="switchViewMode('grid')"
                    :class="viewMode === 'grid' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-semibold'"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                <span>🧱</span> Kartu
            </button>
        </div>
    </div>

    <!-- TABLE VIEW MODE -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left w-8">#</th>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Status</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Penulis</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($berita as $b)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white line-clamp-1">{{ $b->judul }}</p>
                        @if($b->ringkasan)
                        <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">{{ $b->ringkasan }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400">
                            {{ ucfirst($b->kategori) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $b->status === 'published' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                            {{ ucfirst($b->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400 hidden lg:table-cell text-xs">
                        {{ $b->published_at?->format('d M Y') ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-gray-400 hidden lg:table-cell text-xs">
                        {{ $b->penulis?->name ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if(auth()->user()->isSuperAdmin() || auth()->id() === $b->user_id)
                            <a href="{{ route('admin.berita.edit', $b) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.berita.destroy', $b) }}"
                                  onsubmit="return confirm('Hapus berita ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors">
                                    Hapus
                                </button>
                            </form>
                            @else
                            <span class="px-3 py-1 text-xs text-gray-400">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                        Belum ada berita. <a href="{{ route('admin.berita.create') }}" class="text-brand-600 hover:underline">Tambah sekarang</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- GRID VIEW MODE -->
    <div x-show="viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($berita as $b)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col justify-between hover:border-brand-200 transition-colors">
            <div>
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400">
                        {{ ucfirst($b->kategori) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $b->status === 'published' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-50 text-yellow-700' }}">
                        {{ ucfirst($b->status) }}
                    </span>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-2">{{ $b->judul }}</h3>
                @if($b->ringkasan)
                <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $b->ringkasan }}</p>
                @endif
                <div class="mt-3 text-[11px] text-gray-400">
                    <span>📅 {{ $b->published_at?->format('d M Y') ?? 'Draft' }}</span>
                    <span>· Penulis: {{ $b->penulis?->name ?? '-' }}</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
                @if(auth()->user()->isSuperAdmin() || auth()->id() === $b->user_id)
                <a href="{{ route('admin.berita.edit', $b) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                    Edit
                </a>
                <form method="POST" action="{{ route('admin.berita.destroy', $b) }}"
                      onsubmit="return confirm('Hapus berita ini?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors">
                        Hapus
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800 p-12 text-center text-gray-400">
            Belum ada berita.
        </div>
        @endforelse
    </div>
</div>
@endsection

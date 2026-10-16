@extends('admin.layouts.admin')
@section('title', 'Kelola Program Studi')

@section('content')
<div x-data="{
    viewMode: localStorage.getItem('admin_prodi_view_mode') || 'table',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('admin_prodi_view_mode', mode);
    }
}" class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🎓 Program Studi</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola data program studi dan blok konten halaman publiknya</p>
        </div>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isWadir1())
        <a href="{{ route('admin.program-studi.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Prodi
        </a>
        @endif
    </div>

    <!-- Filter + View Mode Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
        <form method="GET" class="flex flex-wrap gap-3">
            <select name="jenjang" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Semua Jenjang</option>
                @foreach(['D3','D4','Profesi','S1','S2'] as $j)
                <option value="{{ $j }}" @selected(request('jenjang')===$j)>{{ $j }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter</button>
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
                    <th class="px-4 py-3 text-left">Nama & Singkatan</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Jenjang</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Akreditasi</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($prodiList as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @if($p->icon_emoji)<span class="text-xl">{{ $p->icon_emoji }}</span>@endif
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $p->nama }}</p>
                                @if($p->singkatan)<p class="text-xs text-gray-400">{{ $p->singkatan }}</p>@endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400">
                            {{ $p->jenjang }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm hidden md:table-cell">
                        {{ $p->akreditasi ?? '—' }}
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $p->aktif ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500' }}">
                            {{ $p->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.program-studi.content', $p) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 dark:bg-brand-950/30 text-brand-700 dark:text-brand-300 hover:bg-brand-100 dark:hover:bg-brand-950/50 transition-colors">
                                Blok Konten
                            </a>
                            <a href="{{ route('admin.program-studi.edit', $p) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                                Edit
                            </a>
                            @if(auth()->user()->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.program-studi.destroy', $p) }}"
                                  onsubmit="return confirm('Hapus program studi ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 transition-colors">
                                    Hapus
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">Belum ada data program studi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- GRID VIEW MODE -->
    <div x-show="viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($prodiList as $p)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col justify-between hover:border-brand-200 transition-colors">
            <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl shrink-0">{{ $p->icon_emoji ?? '🎓' }}</span>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $p->nama }}</h3>
                            @if($p->singkatan)<p class="text-xs text-gray-400">{{ $p->singkatan }}</p>@endif
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 {{ $p->aktif ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500' }}">
                        {{ $p->aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs">
                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400">
                        {{ $p->jenjang }}
                    </span>
                    <span class="text-gray-400">Akreditasi: <strong class="text-gray-700 dark:text-gray-300">{{ $p->akreditasi ?? '—' }}</strong></span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
                <a href="{{ route('admin.program-studi.content', $p) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 dark:bg-brand-950/30 text-brand-700 dark:text-brand-300 hover:bg-brand-100 dark:hover:bg-brand-950/50 transition-colors">
                    Blok Konten
                </a>
                <a href="{{ route('admin.program-studi.edit', $p) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                    Edit
                </a>
                @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('admin.program-studi.destroy', $p) }}"
                      onsubmit="return confirm('Hapus program studi ini?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 transition-colors">
                        Hapus
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800 p-12 text-center text-gray-400">
            Belum ada data program studi.
        </div>
        @endforelse
    </div>
</div>
@endsection

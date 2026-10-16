@extends('admin.layouts.admin')
@section('title', ($page ? 'Pengaduan — ' . $page->judul : 'Rekap Seluruh Pengaduan'))

@section('content')
<div x-data="{
    viewMode: localStorage.getItem('admin_pengaduan_view_mode') || 'table',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('admin_pengaduan_view_mode', mode);
    }
}" class="p-6 w-full">

    {{-- Header --}}
    <div class="mb-6 rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
        <div class="px-6 py-5 flex items-center justify-between gap-4"
             style="background: {{ $page ? $page->warna_tema : '#1e40af' }}">
            <div>
                <p class="text-xs font-bold text-white/70 uppercase tracking-widest mb-1">Dashboard Pengaduan</p>
                <h1 class="text-xl font-extrabold text-white">
                    {{ $page ? $page->judul : 'Seluruh Pengaduan' }}
                </h1>
                <p class="text-sm text-white/75 mt-0.5">
                    Rekap pengaduan, kritik, saran, dan keluhan pelanggan
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($page)
                <a href="{{ route('admin.halaman-unit.manage', $page) }}"
                   class="px-3 py-1.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition-colors">
                    ← Kelola Halaman
                </a>
                @endif
                <a href="{{ route('pengaduan.create') }}" target="_blank"
                   class="px-3 py-1.5 rounded-xl bg-white text-gray-900 text-xs font-bold transition-colors hover:bg-brand-50">
                    ↗ Form Publik
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        @foreach([
            ['label'=>'Total',    'count'=>$stats['total'],    'color'=>'gray',   'icon'=>'📋'],
            ['label'=>'Masuk',    'count'=>$stats['masuk'],    'color'=>'yellow', 'icon'=>'🔔'],
            ['label'=>'Diproses', 'count'=>$stats['diproses'], 'color'=>'blue',   'icon'=>'⚙️'],
            ['label'=>'Selesai',  'count'=>$stats['selesai'],  'color'=>'green',  'icon'=>'✅'],
            ['label'=>'Ditolak',  'count'=>$stats['ditolak'],  'color'=>'red',    'icon'=>'❌'],
        ] as $stat)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 text-center">
            <p class="text-2xl mb-1">{{ $stat['icon'] }}</p>
            <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $stat['count'] }}</p>
            <p class="text-xs text-gray-500">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filter bar + View Switcher --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-end gap-3 flex-1">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">Cari</label>
                <input type="search" name="search" value="{{ request('search') }}"
                       placeholder="Nomor tiket, subjek, nama..."
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-white px-3 py-2 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Status</label>
                <select name="status"
                        class="rounded-xl border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-white px-3 py-2 text-sm outline-none focus:border-brand-400">
                    <option value="">Semua Status</option>
                    <option value="masuk"    {{ request('status') === 'masuk'    ? 'selected' : '' }}>Masuk</option>
                    <option value="diproses" {{ request('status') === 'diproses' ? 'selected' : '' }}>Diproses</option>
                    <option value="selesai"  {{ request('status') === 'selesai'  ? 'selected' : '' }}>Selesai</option>
                    <option value="ditolak"  {{ request('status') === 'ditolak'  ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Jenis</label>
                <select name="jenis"
                        class="rounded-xl border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-white px-3 py-2 text-sm outline-none focus:border-brand-400">
                    <option value="">Semua Jenis</option>
                    <option value="pengaduan" {{ request('jenis') === 'pengaduan' ? 'selected' : '' }}>🚨 Pengaduan</option>
                    <option value="kritik"    {{ request('jenis') === 'kritik'    ? 'selected' : '' }}>💬 Kritik</option>
                    <option value="saran"     {{ request('jenis') === 'saran'     ? 'selected' : '' }}>💡 Saran</option>
                    <option value="keluhan"   {{ request('jenis') === 'keluhan'   ? 'selected' : '' }}>⚠️ Keluhan</option>
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search','status','jenis']))
            <a href="{{ $page ? route('admin.pengaduan.unit.index', $page) : route('admin.pengaduan.index') }}"
               class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 text-sm font-bold transition-colors">
                Reset
            </a>
            @endif
        </form>

        <div class="flex items-center gap-1.5 shrink-0 bg-gray-100 dark:bg-gray-800 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-700 self-end md:self-auto">
            <button type="button" 
                    @click="switchViewMode('table')"
                    :class="viewMode === 'table' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 font-semibold'"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                <span>📋</span> Tabel
            </button>
            <button type="button" 
                    @click="switchViewMode('grid')"
                    :class="viewMode === 'grid' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 font-semibold'"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                <span>🧱</span> Kartu
            </button>
        </div>
    </div>

    {{-- Content --}}
    @if($pengaduanList->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-12 text-center">
        <p class="text-4xl mb-3">📭</p>
        <p class="font-bold text-gray-700 dark:text-gray-300">Belum ada pengaduan/saran masuk</p>
        <p class="text-sm text-gray-400 mt-1">Data akan muncul saat ada yang mengisi formulir pengaduan</p>
    </div>
    @else
    <!-- TABLE VIEW MODE -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Tiket</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Jenis</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Subjek</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Tujuan</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach($pengaduanList as $item)
                @php
                    $statusClasses = [
                        'masuk'    => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                        'diproses' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'selesai'  => 'bg-green-50 text-green-700 border-green-200',
                        'ditolak'  => 'bg-red-50 text-red-600 border-red-200',
                    ][$item->status] ?? 'bg-gray-50 text-gray-600';
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors {{ !$item->dibaca_at ? 'font-semibold' : '' }}">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs text-brand-700 bg-brand-50 rounded-lg px-2 py-1">
                            {{ $item->nomor_tiket }}
                        </span>
                        @if(!$item->dibaca_at)
                        <span class="ml-1 inline-block w-2 h-2 rounded-full bg-red-500" title="Belum dibaca"></span>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell">
                        <span class="text-base">{{ $item->jenisIcon() }}</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $item->jenisLabel() }}</span>
                    </td>
                    <td class="px-4 py-3 max-w-sm sm:max-w-md">
                        <p class="truncate text-gray-900 dark:text-white" title="{{ $item->subjek }}">{{ $item->subjek }}</p>
                        <p class="text-xs text-gray-400">{{ $item->anonim ? 'Anonim' : $item->nama }}</p>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <p class="text-xs text-gray-500 max-w-[140px] truncate">{{ $item->tujuan_label }}</p>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <p class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }}</p>
                        <p class="text-xs text-gray-300">{{ $item->created_at->format('d M Y') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-bold {{ $statusClasses }}">
                            {{ $item->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($page)
                        <a href="{{ route('admin.pengaduan.unit.show', [$page, $item]) }}"
                           class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">
                            Detail →
                        </a>
                        @else
                        <a href="{{ route('admin.pengaduan.show', $item) }}"
                           class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">
                            Detail →
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- GRID VIEW MODE -->
    <div x-show="viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($pengaduanList as $item)
        @php
            $statusClasses = [
                'masuk'    => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                'diproses' => 'bg-blue-50 text-blue-700 border-blue-200',
                'selesai'  => 'bg-green-50 text-green-700 border-green-200',
                'ditolak'  => 'bg-red-50 text-red-600 border-red-200',
            ][$item->status] ?? 'bg-gray-50 text-gray-600';
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col justify-between hover:border-brand-200 transition-colors">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="font-mono text-xs text-brand-700 bg-brand-50 dark:bg-brand-950/40 rounded-lg px-2 py-1 font-bold">
                        {{ $item->nomor_tiket }}
                    </span>
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-bold {{ $statusClasses }}">
                        {{ $item->statusLabel() }}
                    </span>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-500 mb-2">
                    <span class="text-sm">{{ $item->jenisIcon() }}</span>
                    <span class="font-semibold">{{ $item->jenisLabel() }}</span>
                    <span>· {{ $item->created_at->diffForHumans() }}</span>
                </div>

                <h3 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-2">{{ $item->subjek }}</h3>
                <p class="text-xs text-gray-400 mt-1">Pengirim: <strong class="text-gray-700 dark:text-gray-300">{{ $item->anonim ? 'Anonim' : $item->nama }}</strong></p>
                <p class="text-xs text-gray-400 mt-0.5 truncate">Tujuan: {{ $item->tujuan_label }}</p>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <span class="text-[11px] text-gray-400">{{ $item->created_at->format('d M Y H:i') }}</span>
                @if($page)
                <a href="{{ route('admin.pengaduan.unit.show', [$page, $item]) }}"
                   class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-brand-600 hover:bg-brand-500 text-white transition-colors">
                    Detail →
                </a>
                @else
                <a href="{{ route('admin.pengaduan.show', $item) }}"
                   class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-brand-600 hover:bg-brand-500 text-white transition-colors">
                    Detail →
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($pengaduanList->hasPages())
    <div class="mt-6">
        {{ $pengaduanList->links() }}
    </div>
    @endif
    @endif

</div>
@endsection

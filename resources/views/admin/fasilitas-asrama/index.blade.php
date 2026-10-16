@extends('admin.layouts.admin')
@section('title', 'Fasilitas & Kamar Asrama')
@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🏠 Fasilitas & Kamar Asrama</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola data kamar, fasilitas, harga, dan ketersediaan asrama</p>
        </div>
        <a href="{{ route('admin.fasilitas-asrama.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Kamar / Fasilitas
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="jenis" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Jenis</option>
            @foreach(\App\Models\FasilitasAsrama::$jenisLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('jenis')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter</button>
    </form>

    {{-- Summary cards --}}
    @php
        $totalKapasitas = $items->sum('kapasitas');
        $totalTerisi    = $items->sum('terisi');
        $sisa           = max(0, $totalKapasitas - $totalTerisi);
    @endphp
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 text-center">
            <div class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalKapasitas }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Kapasitas</div>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 text-center">
            <div class="text-2xl font-extrabold text-brand-600">{{ $totalTerisi }}</div>
            <div class="text-xs text-gray-500 mt-1">Terisi</div>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 text-center">
            <div class="text-2xl font-extrabold text-green-600">{{ $sisa }}</div>
            <div class="text-xs text-gray-500 mt-1">Tersedia</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Nama / Tipe</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Jenis</th>
                    <th class="px-4 py-3 text-left">Kapasitas</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Harga/Bulan</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Fasilitas</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item->aktif ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $item->nama }}</p>
                        @if($item->lantai)<p class="text-xs text-gray-400">Lantai {{ $item->lantai }}</p>@endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $item->jenis === 'putra' ? 'bg-blue-50 text-blue-700' : ($item->jenis === 'putri' ? 'bg-pink-50 text-pink-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ \App\Models\FasilitasAsrama::$jenisLabels[$item->jenis] }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-bold {{ $item->sisa_kamar > 0 ? 'text-green-600' : 'text-red-500' }}">{{ $item->terisi }}/{{ $item->kapasitas }}</span>
                        <span class="text-xs text-gray-400 ml-1">({{ $item->sisa_kamar }} sisa)</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-brand-600 font-bold hidden lg:table-cell">{{ $item->harga_bulan_format }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell max-w-xs truncate">{{ $item->fasilitas ?? '—' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $item->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.fasilitas-asrama.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.fasilitas-asrama.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus {{ $item->nama }}?')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Belum ada data kamar/fasilitas. Tambahkan sekarang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('admin.layouts.admin')
@section('title', 'Layanan IT')
@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">💻 Layanan IT</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Katalog layanan dan panduan pengajuan IT ke Unit Teknologi Informasi</p>
        </div>
        <a href="{{ route('admin.layanan-ti.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Layanan
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="kategori" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Kategori</option>
            @foreach(\App\Models\LayananTI::$kategoriLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('kategori')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter</button>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left w-8">#</th>
                    <th class="px-4 py-3 text-left">Nama Layanan</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Kontak</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Est. Waktu</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($items as $i => $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item->aktif ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $item->nama }}</p>
                        @if($item->deskripsi)
                        <p class="text-xs text-gray-400 line-clamp-1">{{ $item->deskripsi }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        @php
                            $catColor = match($item->kategori) {
                                'jaringan' => 'bg-blue-50 text-blue-700',
                                'hardware' => 'bg-orange-50 text-orange-700',
                                'software' => 'bg-violet-50 text-violet-700',
                                'akun','email' => 'bg-green-50 text-green-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $catColor }}">
                            {{ $item->kategori_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell">{{ $item->kontak ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell">{{ $item->estimasi_waktu ?? '—' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $item->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.layanan-ti.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.layanan-ti.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus layanan ini?')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Belum ada layanan IT. Tambahkan sekarang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

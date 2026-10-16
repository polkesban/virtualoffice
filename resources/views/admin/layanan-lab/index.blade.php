@extends('admin.layouts.admin')
@section('title', 'Daftar Layanan & Tarif Laboratorium')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🧪 Layanan & Tarif Laboratorium</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola jenis layanan jasa lab beserta tarif dan ketentuan</p>
        </div>
        <a href="{{ route('admin.layanan-lab.create') }}"
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
            @foreach(\App\Models\LayananLab::$kategoriLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('kategori')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter</button>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Nama Layanan</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Tarif</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Waktu Pengerjaan</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($layanan as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item->aktif ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $item->nama }}</p>
                        @if($item->deskripsi)<p class="text-xs text-gray-400 line-clamp-1">{{ $item->deskripsi }}</p>@endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            {{ \App\Models\LayananLab::$kategoriLabels[$item->kategori] ?? $item->kategori }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-bold text-brand-600 dark:text-brand-400 text-xs">{{ $item->tarif_format }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell">{{ $item->lama_pengerjaan ?? '—' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $item->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.layanan-lab.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.layanan-lab.toggle', $item) }}">
                                @csrf @method('PATCH')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-yellow-50 text-yellow-700 hover:bg-yellow-100">
                                    {{ $item->aktif ? 'Off' : 'On' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.layanan-lab.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus layanan ini?')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Belum ada layanan lab. Tambahkan jenis layanan dan tarif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('admin.layouts.admin')
@section('title', 'Koleksi Perpustakaan')
@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">📚 Koleksi Perpustakaan</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Katalog buku, jurnal, dan referensi perpustakaan</p>
        </div>
        <a href="{{ route('admin.koleksi-perpus.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Koleksi
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul / penulis / ISBN…"
               class="flex-1 min-w-48 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        <select name="kategori" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Kategori</option>
            @foreach(\App\Models\KoleksiPerpus::$kategoriLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('kategori')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Cari</button>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Judul & Penulis</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Stok</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Rak</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Digital</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item->aktif ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white line-clamp-1">{{ $item->judul }}</p>
                        <p class="text-xs text-gray-400">{{ $item->penulis }} @if($item->tahun_terbit)· {{ $item->tahun_terbit }}@endif</p>
                        @if($item->isbn)<p class="text-xs text-gray-400 font-mono">ISBN: {{ $item->isbn }}</p>@endif
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700">
                            {{ \App\Models\KoleksiPerpus::$kategoriLabels[$item->kategori] ?? $item->kategori }}
                        </span>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="font-bold text-xs {{ $item->stok_tersedia > 0 ? 'text-green-600' : 'text-red-500' }}">
                            {{ $item->stok_tersedia }}/{{ $item->stok_total }}
                        </span>
                        <span class="text-xs text-gray-400 ml-1">{{ $item->status_stok }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell">{{ $item->lokasi_rak ?? '—' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        @if($item->link_digital)
                        <a href="{{ $item->link_digital }}" target="_blank" class="text-xs text-brand-600 hover:underline">🔗 Link</a>
                        @else<span class="text-xs text-gray-400">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.koleksi-perpus.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.koleksi-perpus.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus koleksi ini?')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Belum ada koleksi. Tambahkan entri pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

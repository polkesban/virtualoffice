@extends('admin.layouts.admin')
@section('title', 'Program Pelatihan & Pengembangan')
@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🎓 Program Pelatihan & Pengembangan</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pelatihan, workshop, seminar, bimtek, dan studi banding</p>
        </div>
        <a href="{{ route('admin.pelatihan.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Program
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="jenis" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Jenis</option>
            @foreach(\App\Models\Pelatihan::$jenisLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('jenis')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="tahun" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Tahun</option>
            @for($y = date('Y'); $y >= 2018; $y--)
            <option value="{{ $y }}" @selected(request('tahun')==$y)>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter</button>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Judul & Penyelenggara</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Jenis</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Lokasi</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Peserta</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item->aktif ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white line-clamp-2">{{ $item->judul }}</p>
                        @if($item->penyelenggara)<p class="text-xs text-gray-400">{{ $item->penyelenggara }}</p>@endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700">
                            {{ $item->jenis_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 hidden lg:table-cell">{{ $item->durasi }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell">{{ $item->lokasi ?? '—' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        @if($item->jumlah_peserta)<span class="font-bold text-brand-600">{{ $item->jumlah_peserta }}</span>
                        <span class="text-xs text-gray-400"> orang</span>@else<span class="text-xs text-gray-400">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.pelatihan.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.pelatihan.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus program ini?')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Belum ada program pelatihan. Tambahkan sekarang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

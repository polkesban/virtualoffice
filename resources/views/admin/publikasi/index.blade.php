@extends('admin.layouts.admin')
@section('title', 'Publikasi & Penelitian')
@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">📄 Publikasi & Penelitian</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jurnal, prosiding, skripsi, laporan penelitian, dan karya ilmiah</p>
        </div>
        <a href="{{ route('admin.publikasi.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Publikasi
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul / penulis…"
               class="flex-1 min-w-48 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        <select name="jenis" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Jenis</option>
            @foreach(\App\Models\Publikasi::$jenisLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('jenis')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="tahun" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Tahun</option>
            @for($y = date('Y'); $y >= 2010; $y--)
            <option value="{{ $y }}" @selected(request('tahun')==$y)>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Cari</button>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Judul & Penulis</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Jenis</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Tahun</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">DOI / Link</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item->aktif ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white line-clamp-2">{{ $item->judul }}</p>
                        <p class="text-xs text-gray-400">{{ $item->penulis }}</p>
                        @if($item->institusi)<p class="text-xs text-gray-400 italic">{{ $item->institusi }}</p>@endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700">
                            {{ $item->jenis_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hidden lg:table-cell">{{ $item->tahun ?? '—' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell text-xs space-y-1">
                        @if($item->doi)<p class="font-mono text-gray-500">{{ $item->doi }}</p>@endif
                        @if($item->link_url)<a href="{{ $item->link_url }}" target="_blank" class="text-brand-600 hover:underline">🔗 Link</a>@endif
                        @if($item->file_url)<a href="{{ $item->file_url }}" target="_blank" class="text-green-600 hover:underline block">⬇ File</a>@endif
                        @if(!$item->doi && !$item->link_url && !$item->file_url)<span class="text-gray-400">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.publikasi.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.publikasi.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus publikasi ini?')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Belum ada data publikasi. Tambahkan entri pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

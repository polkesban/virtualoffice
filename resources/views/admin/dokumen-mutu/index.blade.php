@extends('admin.layouts.admin')
@section('title', 'Dokumen Sistem Penjaminan Mutu')
@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">📋 Dokumen Mutu</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kebijakan, standar, prosedur, formulir, dan dokumen akreditasi</p>
        </div>
        <a href="{{ route('admin.dokumen-mutu.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Dokumen
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="jenis" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Jenis</option>
            @foreach(\App\Models\DokumenMutu::$jenisLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('jenis')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter</button>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Jenis</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">No. Dokumen</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Tanggal / Versi</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Masa Berlaku</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item->aktif ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $item->judul }}</p>
                        @if($item->file_url)
                        <a href="{{ $item->file_url }}" target="_blank" class="text-xs text-brand-600 hover:underline">⬇ Unduh dokumen</a>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        @php
                            $jenisColor = match($item->jenis) {
                                'kebijakan','standar' => 'bg-blue-50 text-blue-700',
                                'prosedur' => 'bg-green-50 text-green-700',
                                'akreditasi' => 'bg-purple-50 text-purple-700',
                                'lap_audit' => 'bg-amber-50 text-amber-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $jenisColor }}">
                            {{ $item->jenis_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs font-mono text-gray-500 hidden lg:table-cell">{{ $item->nomor_dok ?? '—' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell text-xs text-gray-600">
                        @if($item->tanggal_terbit){{ $item->tanggal_terbit->format('d/m/Y') }}@else—@endif
                        @if($item->versi)<br><span class="text-gray-400">v{{ $item->versi }}</span>@endif
                    </td>
                    <td class="px-4 py-3 text-xs hidden lg:table-cell">{{ $item->masa_berlaku }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.dokumen-mutu.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.dokumen-mutu.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus dokumen ini?')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Belum ada dokumen mutu. Tambahkan entri pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

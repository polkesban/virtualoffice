@extends('admin.layouts.admin')
@section('title', 'Kelola Agenda & Kalender Kegiatan')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">📅 Agenda & Kalender Kegiatan</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jadwal kegiatan akademik, kemahasiswaan, dan penelitian</p>
        </div>
        <a href="{{ route('admin.agenda.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Agenda
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="kategori" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Kategori</option>
            @foreach(\App\Models\Agenda::$kategoriLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('kategori')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <input type="month" name="bulan" value="{{ request('bulan') }}"
               class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter</button>
        @if(request('kategori') || request('bulan'))
        <a href="{{ route('admin.agenda.index') }}" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Reset</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Judul / Lokasi</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($agenda as $item)
                @php
                    $warnaMap = ['blue'=>'bg-blue-500','green'=>'bg-green-500','red'=>'bg-red-500','yellow'=>'bg-yellow-400','purple'=>'bg-purple-500','orange'=>'bg-orange-400','gray'=>'bg-gray-400'];
                    $warnaCls = $warnaMap[$item->warna] ?? 'bg-gray-400';
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-2.5">
                            <span class="mt-1 w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $warnaCls }}"></span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $item->judul }}</p>
                                @if($item->lokasi)<p class="text-xs text-gray-400">📍 {{ $item->lokasi }}</p>@endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs hidden md:table-cell">
                        <p>{{ $item->tanggal_mulai->translatedFormat('d M Y') }}</p>
                        @if($item->tanggal_selesai && $item->tanggal_selesai->ne($item->tanggal_mulai))
                        <p class="text-gray-400">s/d {{ $item->tanggal_selesai->translatedFormat('d M Y') }}</p>
                        @endif
                        @if($item->waktu_mulai)<p class="text-gray-400">{{ $item->waktu_mulai }}{{ $item->waktu_selesai ? ' – ' . $item->waktu_selesai : '' }}</p>@endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ \App\Models\Agenda::$kategoriLabels[$item->kategori] ?? $item->kategori }}
                        </span>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $item->publik ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $item->publik ? 'Publik' : 'Internal' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.agenda.edit', $item) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.agenda.destroy', $item) }}"
                                  onsubmit="return confirm('Hapus agenda ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Belum ada agenda. Tambahkan jadwal kegiatan pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

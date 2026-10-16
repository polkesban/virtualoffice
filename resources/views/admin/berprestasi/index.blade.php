@extends('admin.layouts.admin')

@section('title', 'Mahasiswa Berprestasi')
@section('page-title', 'Mahasiswa Berprestasi')
@section('page-subtitle', 'Student of the Month / Semester / Year yang ditampilkan di beranda')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div></div>
    <a href="{{ route('admin.berprestasi.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700
              text-white text-sm font-medium transition-colors">
        + Tambah
    </a>
</div>

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 dark:border-gray-800">
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mahasiswa</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Urutan</th>
                <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <img src="{{ $item->mahasiswa->foto_url }}" alt="{{ $item->mahasiswa->name }}"
                                 class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $item->mahasiswa->name }}</p>
                                <p class="text-xs text-gray-400">{{ $item->mahasiswa->nim }} · {{ $item->mahasiswa->programStudi?->singkatan ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-gray-600 dark:text-gray-400">
                        {{ \App\Models\MahasiswaBerprestasi::$periodeLabels[$item->periode] ?? $item->periode }}
                    </td>
                    <td class="px-5 py-3.5 text-gray-600 dark:text-gray-400">{{ $item->periode_label }}</td>
                    <td class="px-5 py-3.5 text-center text-gray-500">{{ $item->urutan }}</td>
                    <td class="px-5 py-3.5 text-center">
                        <form action="{{ route('admin.berprestasi.toggle', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition-colors
                                           {{ $item->aktif
                                               ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'
                                               : 'bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.berprestasi.edit', $item) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500
                                      hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors">
                                Edit
                            </a>
                            <form action="{{ route('admin.berprestasi.destroy', $item) }}" method="POST"
                                  onsubmit="return confirm('Hapus data ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500
                                               hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-400 text-sm">
                        Belum ada data mahasiswa berprestasi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

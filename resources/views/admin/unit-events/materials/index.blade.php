@extends('admin.layouts.admin')
@section('title', 'Materi Event Unit')
@section('page-title', 'Materi Event Unit')
@section('page-subtitle', 'Upload file, link, dan materi embed yang bisa ditonton atau diunduh')

@section('content')
<div class="p-6 max-w-6xl mx-auto">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    <div class="mb-5 flex items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Materi untuk {{ $unitEvent->judul }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Materi publik tampil di halaman event. Materi non-publik hanya terlihat melalui akses peserta.</p>
        </div>
        <a href="{{ route('admin.unit-events.materials.create', [$unitPage, $unitEvent]) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">+ Tambah Materi</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left">Materi</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Tipe</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Urutan</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($materials as $material)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $material->judul }}</p>
                            @if($material->deskripsi)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $material->deskripsi }}</p>
                            @endif
                            @if($material->tipe === 'file' && $material->file_path)
                                <a href="{{ $material->downloadable ? \App\Support\MediaStorage::downloadUrl($material->file_path) : \App\Support\MediaStorage::url($material->file_path) }}" target="_blank" class="mt-1 inline-block text-xs font-semibold text-brand-600 hover:underline">Buka file</a>
                            @elseif($material->tipe === 'link' && $material->link_url)
                                <a href="{{ $material->link_url }}" target="_blank" class="mt-1 inline-block text-xs font-semibold text-brand-600 hover:underline">Buka tautan</a>
                            @elseif($material->tipe === 'embed' && $material->embed_url)
                                <a href="{{ $material->embed_url }}" target="_blank" class="mt-1 inline-block text-xs font-semibold text-brand-600 hover:underline">Buka embed source</a>
                            @endif
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell text-gray-500">{{ \App\Models\UnitEventMaterial::$tipeLabels[$material->tipe] ?? $material->tipe }}</td>
                        <td class="hidden px-4 py-3 lg:table-cell">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $material->publik ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $material->publik ? 'Publik' : 'Akses Peserta' }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $material->downloadable ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $material->downloadable ? 'Bisa diunduh' : 'Lihat saja' }}</span>
                            </div>
                        </td>
                        <td class="hidden px-4 py-3 lg:table-cell text-gray-500">{{ $material->urutan }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.unit-events.materials.edit', [$unitPage, $unitEvent, $material]) }}" class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Edit</a>
                                <form method="POST" action="{{ route('admin.unit-events.materials.destroy', [$unitPage, $unitEvent, $material]) }}" onsubmit="return confirm('Hapus materi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-red-50 px-3 py-1 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">Belum ada materi untuk event ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
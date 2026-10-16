@extends('mahasiswa.layouts.portal')

@section('page-title', 'Portofolio Saya')
@section('page-subtitle', 'Semua pencapaian & aktivitas yang Anda catat')

@section('content')

@php
    $badgeColor = [
        'prestasi'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'organisasi'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'penelitian'   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        'magang'       => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'karya'        => 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
        'sertifikasi'  => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        'lainnya'      => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    ];
    $tingkatColor = [
        'kampus'        => 'bg-gray-100 text-gray-600',
        'kota'          => 'bg-cyan-100 text-cyan-700',
        'provinsi'      => 'bg-blue-100 text-blue-700',
        'nasional'      => 'bg-orange-100 text-orange-700',
        'internasional' => 'bg-red-100 text-red-700',
    ];
@endphp

{{-- Header row --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm text-gray-500">
            {{ $portofolio->count() }} item
            @if($portofolio->where('tampilkan', true)->count() > 0)
                · {{ $portofolio->where('tampilkan', true)->count() }} ditampilkan publik
            @endif
        </p>
    </div>
    <a href="{{ route('mahasiswa.portofolio.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700
              text-white text-sm font-medium transition-colors">
        <span aria-hidden="true">+</span> Tambah Pencapaian
    </a>
</div>

@if($portofolio->isEmpty())
    <div class="text-center py-20 text-gray-400">
        <p class="text-5xl mb-4" aria-hidden="true">📂</p>
        <p class="font-medium text-gray-600 dark:text-gray-300">Portofolio masih kosong</p>
        <p class="text-sm mt-1">Catat prestasi, sertifikasi, atau kegiatan Anda di sini.</p>
        <a href="{{ route('mahasiswa.portofolio.create') }}"
           class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-medium hover:bg-brand-700 transition-colors">
            Tambah Sekarang
        </a>
    </div>
@else
    <div class="space-y-3">
        @foreach($portofolio as $item)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5
                        flex items-start gap-4 group">

                {{-- Ikon kategori --}}
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl flex-shrink-0
                            {{ $badgeColor[$item->kategori] ?? 'bg-gray-100' }}">
                    @php $icons = ['prestasi'=>'🏆','organisasi'=>'👥','penelitian'=>'🔬','magang'=>'💼','karya'=>'🎨','sertifikasi'=>'📜','lainnya'=>'📌']; @endphp
                    {{ $icons[$item->kategori] ?? '📌' }}
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-2 flex-wrap">
                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm truncate flex-1">
                            {{ $item->judul }}
                        </h3>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                         {{ $tingkatColor[$item->tingkat] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ \App\Models\PortofolioMahasiswa::$tingkatLabels[$item->tingkat] ?? $item->tingkat }}
                            </span>
                            @if(! $item->tampilkan)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-800">
                                    Tersembunyi
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                        <span class="px-1.5 py-0.5 rounded-full {{ $badgeColor[$item->kategori] ?? '' }} text-xs">
                            {{ \App\Models\PortofolioMahasiswa::$kategoriLabels[$item->kategori] ?? $item->kategori }}
                        </span>
                        @if($item->institusi)
                            <span>{{ $item->institusi }}</span>
                        @endif
                        <span>{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('M Y') }}</span>
                    </div>

                    @if($item->deskripsi)
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ $item->deskripsi }}
                        </p>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    @if($item->lampiran_url)
                        <a href="{{ $item->lampiran_url }}" target="_blank" rel="noopener"
                           class="p-2 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors"
                           title="Lihat lampiran">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </a>
                    @endif
                    <a href="{{ route('mahasiswa.portofolio.edit', $item) }}"
                       class="p-2 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors"
                       title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <form action="{{ route('mahasiswa.portofolio.destroy', $item) }}" method="POST"
                          onsubmit="return confirm('Hapus item ini?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                title="Hapus">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection

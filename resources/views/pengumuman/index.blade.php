@extends('layouts.app')

@section('title', 'Pengumuman')
@section('description', 'Pengumuman resmi dan kegiatan Politeknik Kesehatan Kemenkes Bandung.')

@section('content')
<div class="bg-white dark:bg-gray-950 min-h-screen">

<div x-data="{
    viewMode: localStorage.getItem('pengumuman_view_mode') || 'list',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('pengumuman_view_mode', mode);
    }
}">
    {{-- Hero --}}
    <div class="bg-gradient-to-br from-brand-800 to-brand-950 py-14">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-[11px] font-black uppercase tracking-widest text-brand-300 mb-2">Info Kampus</p>
            <h1 class="text-3xl sm:text-4xl font-black text-white">Pengumuman & Kegiatan</h1>
            <nav class="mt-4 flex justify-center gap-1 text-sm text-brand-300" aria-label="Breadcrumb">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">Beranda</a>
                <span class="text-brand-600 mx-1">/</span>
                <span class="text-white">Pengumuman</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10">

        {{-- Filter tabs + View Mode Switcher --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1">
                @php $filters = [
                    ['val' => '',        'label' => 'Semua'],
                    ['val' => 'urgent',  'label' => '🔴 Urgent'],
                    ['val' => 'penting', 'label' => '🟡 Penting'],
                    ['val' => 'normal',  'label' => '🟢 Normal'],
                ];
                $currentFilter = request('prioritas', '');
                @endphp
                @foreach($filters as $f)
                <a href="{{ url('/pengumuman') }}{{ $f['val'] ? '?prioritas=' . $f['val'] : '' }}"
                   class="whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-semibold transition-colors
                          {{ $currentFilter === $f['val']
                             ? 'bg-brand-600 text-white'
                             : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-700' }}">
                    {{ $f['label'] }}
                </a>
                @endforeach
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-1.5 shrink-0 bg-gray-100 dark:bg-gray-800 p-1 rounded-2xl border border-gray-200 dark:border-gray-700">
                <button type="button" 
                        @click="switchViewMode('grid')"
                        :class="viewMode === 'grid' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 font-semibold'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                    <span>🧱</span> Grid
                </button>
                <button type="button" 
                        @click="switchViewMode('list')"
                        :class="viewMode === 'list' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 font-semibold'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                    <span>📋</span> List
                </button>
            </div>
        </div>

        {{-- Content --}}
        @if($pengumuman->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="text-6xl mb-4" aria-hidden="true">📋</div>
                <p class="text-lg font-bold text-gray-700 dark:text-gray-300">Belum ada pengumuman</p>
                <p class="text-sm text-gray-400 mt-1">Pengumuman terbaru akan muncul di sini.</p>
            </div>
        @else
            <!-- LIST VIEW MODE -->
            <div x-show="viewMode === 'list'" class="space-y-4">
                @foreach($pengumuman as $item)
                @php
                    $badgeClass = match($item->prioritas) {
                        'urgent'  => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
                        'penting' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
                        default   => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400',
                    };
                    $icon = match($item->prioritas) {
                        'urgent'  => '🔴',
                        'penting' => '🟡',
                        default   => '🟢',
                    };
                @endphp
                <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 hover:border-brand-200 dark:hover:border-brand-800 transition-colors">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center text-2xl" aria-hidden="true">
                            {{ $icon }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-[10px] font-black uppercase tracking-wide px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                    {{ ucfirst($item->prioritas) }}
                                </span>
                                <time datetime="{{ $item->tanggal_mulai }}" class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ \Carbon\Carbon::parse($item->tanggal_mulai)->translatedFormat('d F Y') }}
                                    @if($item->tanggal_selesai && $item->tanggal_selesai !== $item->tanggal_mulai)
                                        &ndash; {{ \Carbon\Carbon::parse($item->tanggal_selesai)->translatedFormat('d F Y') }}
                                    @endif
                                </time>
                            </div>
                            <h2 class="text-base font-extrabold text-gray-900 dark:text-white leading-snug">
                                {{ $item->judul }}
                            </h2>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ $item->isi }}
                            </p>
                            @if($item->file_lampiran)
                            <a href="{{ \App\Support\MediaStorage::url($item->file_lampiran) }}"
                               target="_blank" rel="noopener"
                               class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                Unduh Lampiran
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- GRID VIEW MODE -->
            <div x-show="viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2">
                @foreach($pengumuman as $item)
                @php
                    $badgeClass = match($item->prioritas) {
                        'urgent'  => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
                        'penting' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
                        default   => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400',
                    };
                @endphp
                <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 flex flex-col justify-between hover:border-brand-200 transition-colors">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full {{ $badgeClass }}">
                                {{ ucfirst($item->prioritas) }}
                            </span>
                            <time datetime="{{ $item->tanggal_mulai }}" class="text-[11px] text-gray-400">
                                {{ \Carbon\Carbon::parse($item->tanggal_mulai)->translatedFormat('d M Y') }}
                            </time>
                        </div>
                        <h2 class="text-sm font-extrabold text-gray-900 dark:text-white line-clamp-2">
                            {{ $item->judul }}
                        </h2>
                        <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 line-clamp-3">
                            {{ $item->isi }}
                        </p>
                    </div>
                    @if($item->file_lampiran)
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ \App\Support\MediaStorage::url($item->file_lampiran) }}" target="_blank" class="text-xs font-bold text-brand-600 hover:underline inline-flex items-center gap-1">
                            📎 Lampiran
                        </a>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $pengumuman->links() }}
            </div>
        @endif
    </div>
</div>
</div>
@endsection

@extends('admin.layouts.admin')
@section('title', 'Kelola Halaman — ' . $page->judul)
@section('content')
<div class="p-6 max-w-4xl mx-auto">

    {{-- Page header --}}
    <div class="rounded-2xl overflow-hidden mb-6 border border-gray-100 dark:border-gray-800">
        <div class="px-6 py-5 flex items-center justify-between gap-4" style="background-color: {{ $page->warna_tema }}">
            <div>
                <p class="text-xs font-semibold text-white/70 uppercase tracking-widest mb-1">Halaman Unit</p>
                <h1 class="text-2xl font-extrabold text-white">{{ $page->judul }}</h1>
                @if($page->sub_judul)<p class="text-sm text-white/80 mt-1">{{ $page->sub_judul }}</p>@endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($page->slug === 'unit-bisnis' || $sections->contains('tipe', 'produk'))
                <a href="{{ route('admin.layanan-bisnis.index') }}#orders"
                   class="px-3 py-1.5 rounded-xl bg-white text-gray-900 text-xs font-bold transition-colors hover:bg-emerald-50 hover:text-emerald-700">
                    🛒 Pesanan Masuk
                    @php
                        $pendingOrders = \App\Models\LayananBisnisOrder::where('status_pembayaran', 'belum_lunas')->count();
                    @endphp
                    @if($pendingOrders > 0)
                    <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-bold">{{ $pendingOrders }}</span>
                    @endif
                </a>
                @endif
                <a href="{{ route('admin.halaman-unit.section.create', $page) }}"
                   class="px-3 py-1.5 rounded-xl bg-white text-gray-900 text-xs font-bold transition-colors hover:bg-brand-50 hover:text-brand-700">
                    ＋ Tambah Blok Konten
                </a>
                <a href="{{ route('admin.unit-events.index', $page) }}"
                   class="px-3 py-1.5 rounded-xl bg-white text-gray-900 text-xs font-bold transition-colors hover:bg-brand-50 hover:text-brand-700">
                    🎫 Kelola Event Unit
                </a>
                <a href="{{ route('admin.rubrik.index', ['unit_page_id' => $page->id]) }}"
                   class="px-3 py-1.5 rounded-xl bg-white text-gray-900 text-xs font-bold transition-colors hover:bg-brand-50 hover:text-brand-700">
                    📊 Rubrik Penilaian
                </a>
                <a href="{{ route('admin.pengaduan.unit.index', $page) }}"
                   class="px-3 py-1.5 rounded-xl bg-white text-gray-900 text-xs font-bold transition-colors hover:bg-amber-50 hover:text-amber-700">
                    📋 Pengaduan
                    @php
                        $unreadCount = \App\Models\Pengaduan::whereIn('tujuan_unit', \App\Models\Pengaduan::unitAccessKeys($page->role))
                            ->whereNull('dibaca_at')->count();
                    @endphp
                    @if($unreadCount > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/unit/'.$page->slug) }}" target="_blank"
                   class="px-3 py-1.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition-colors">
                    ↗ Lihat Publik
                </a>
                <a href="{{ route('admin.halaman-unit.edit', $page) }}"
                   class="px-3 py-1.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition-colors">
                    ⚙ Pengaturan
                </a>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-900 px-6 py-3 flex items-center gap-4 text-xs text-gray-500">
            <span>{{ $sections->count() }} blok</span>
            <span>·</span>
            <span>{{ $page->events()->count() }} event unit</span>
            <span>·</span>
            <span class="font-mono">/unit/{{ $page->slug }}</span>
            <span>·</span>
            <span class="{{ $page->aktif ? 'text-green-600' : 'text-red-500' }} font-medium">
                {{ $page->aktif ? '● Live' : '● Draft' }}
            </span>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- Section list --}}
    @if($sections->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-12 text-center mb-4">
        <p class="text-4xl mb-3">🧩</p>
        <p class="font-bold text-gray-700 dark:text-gray-300">Halaman ini masih kosong</p>
        <p class="text-sm text-gray-400 mt-1">Tambahkan blok konten pertama untuk membangun halaman unit Anda</p>
    </div>
    @else
    <div class="space-y-3 mb-4">
        @foreach($sections as $i => $section)
        @php $meta = \App\Models\PageSection::$types[$section->tipe] ?? ['ikon' => '📦', 'label' => $section->tipe]; @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 flex items-center gap-4 {{ $section->aktif ? '' : 'opacity-60' }}">
            {{-- Drag handle / order --}}
            <div class="flex flex-col gap-1 shrink-0">
                <form method="POST" action="{{ route('admin.halaman-unit.section.move', $section) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="direction" value="up">
                    <button {{ $i === 0 ? 'disabled' : '' }} class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 disabled:opacity-30 text-xs transition-colors">▲</button>
                </form>
                <span class="text-xs text-gray-400 text-center">{{ $i+1 }}</span>
                <form method="POST" action="{{ route('admin.halaman-unit.section.move', $section) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="direction" value="down">
                    <button {{ $i === $sections->count()-1 ? 'disabled' : '' }} class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 disabled:opacity-30 text-xs transition-colors">▼</button>
                </form>
            </div>

            {{-- Block icon + info --}}
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="text-2xl shrink-0">{{ $meta['ikon'] }}</span>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm truncate">
                        {{ $section->judul ?: $meta['label'] }}
                    </p>
                    <p class="text-xs text-gray-400">{{ $meta['label'] }}</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 shrink-0">
                <form method="POST" action="{{ route('admin.halaman-unit.section.toggle', $section) }}">
                    @csrf @method('PATCH')
                    <button class="px-3 py-1 rounded-lg text-xs font-bold {{ $section->aktif ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }} transition-colors">
                        {{ $section->aktif ? 'Tampil' : 'Disembunyikan' }}
                    </button>
                </form>
                <a href="{{ route('admin.halaman-unit.section.edit', $section) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                     @if($section->tipe === 'formulir')
                     <a href="{{ route('admin.page-sections.responses.show', $section) }}"
                         class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">Rekap</a>
                     @endif
                     @if($section->tipe === 'produk')
                     <a href="{{ route('admin.layanan-bisnis.index') }}#orders"
                         class="px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">🛒 Pesanan</a>
                     @endif
                <form method="POST" action="{{ route('admin.halaman-unit.section.destroy', $section) }}"
                      onsubmit="return confirm('Hapus blok ini?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Hapus</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Add section button --}}
    <div class="flex justify-center">
        <a href="{{ route('admin.halaman-unit.section.create', $page) }}"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm transition-colors shadow-sm">
            <span class="text-lg">＋</span> Tambah Blok Konten
        </a>
    </div>

    @if(auth()->user()->role === 'super_admin')
    <div class="mt-6 text-center">
        <a href="{{ route('admin.halaman-unit.index') }}" class="text-sm text-gray-400 hover:text-brand-600">← Semua Halaman Unit</a>
    </div>
    @endif
</div>
@endsection

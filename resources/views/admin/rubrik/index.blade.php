@extends('admin.layouts.admin')
@section('title', 'Rubrik Penilaian')
@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">📊</span>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">Rubrik Penilaian</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1">Kelola matriks kriteria, bobot, dan skala penilaian terstruktur untuk pembelajaran, seminar, dan event.</p>
        </div>
        <div>
            <a href="{{ route('admin.rubrik.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm transition-colors shadow-sm">
                <span class="text-lg">＋</span> Buat Rubrik Baru
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 font-medium">
        {{ session('success') }}
    </div>
    @endif

    {{-- Filter & Search --}}
    <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.rubrik.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul rubrik..."
                   class="px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none w-64">

            <select name="kategori" onchange="this.form.submit()"
                    class="px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach(\App\Models\EvaluationRubric::$kategoriLabels as $key => $label)
                <option value="{{ $key }}" {{ request('kategori') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-sm font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                Filter
            </button>
            @if(request('q') || request('kategori'))
            <a href="{{ route('admin.rubrik.index') }}" class="text-xs font-semibold text-red-500 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    {{-- Rubric Grid --}}
    @if($rubrics->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-12 text-center">
        <p class="text-4xl mb-3">📊</p>
        <h3 class="font-bold text-gray-800 dark:text-gray-200 text-lg">Belum Ada Rubrik Penilaian</h3>
        <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">Buat rubrik baru dengan kriteria dan persentase bobot untuk mempermudah evaluasi tugas, seminar, maupun ujian praktikum.</p>
        <a href="{{ route('admin.rubrik.create') }}" class="mt-4 inline-block px-5 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-sm hover:bg-brand-500 transition-colors">
            ＋ Buat Rubrik Sekarang
        </a>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($rubrics as $r)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700">
                        {{ $r->kategori_label }}
                    </span>
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-700">
                        {{ $r->jenis_rubrik_label }}
                    </span>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white text-base line-clamp-2 hover:text-brand-600 transition-colors">
                    <a href="{{ route('admin.rubrik.show', $r) }}">{{ $r->judul }}</a>
                </h3>
                @if($r->deskripsi)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 line-clamp-2">{{ $r->deskripsi }}</p>
                @endif

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500">
                    <span>🧩 {{ $r->items_count }} Kriteria</span>
                    <span>Skala 1–{{ $r->skala_max }}</span>
                </div>

                @if($r->page || $r->event)
                <div class="mt-2 text-[11px] text-gray-400">
                    @if($r->page) 📌 Halaman: {{ $r->page->judul }} @endif
                    @if($r->event) 🎫 Event: {{ $r->event->judul }} @endif
                </div>
                @endif
            </div>

            <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                <a href="{{ route('admin.rubrik.show', $r) }}"
                   class="px-3 py-1.5 rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 text-xs font-bold transition-colors">
                    🔍 Lihat & Simulasi
                </a>
                <div class="flex items-center gap-1">
                    <a href="{{ route('admin.rubrik.print', $r) }}" target="_blank" title="Cetak Rubrik"
                       class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 text-xs">🖨️</a>
                    <a href="{{ route('admin.rubrik.edit', $r) }}" title="Edit Rubrik"
                       class="p-1.5 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-gray-100 text-xs">✏️</a>
                    <form method="POST" action="{{ route('admin.rubrik.destroy', $r) }}" onsubmit="return confirm('Hapus rubrik ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" title="Hapus Rubrik" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-gray-100 text-xs">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $rubrics->links() }}
    </div>
    @endif
</div>
@endsection

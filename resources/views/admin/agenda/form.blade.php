@extends('admin.layouts.admin')
@section('title', $agenda->exists ? 'Edit Agenda' : 'Tambah Agenda')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.agenda.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
            {{ $agenda->exists ? 'Edit Agenda' : 'Tambah Agenda' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ $agenda->exists ? route('admin.agenda.update', $agenda) : route('admin.agenda.store') }}"
          class="space-y-5">
        @csrf
        @if($agenda->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Kegiatan <span class="text-red-500">*</span></label>
                <input type="text" name="judul" value="{{ old('judul', $agenda->judul) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $agenda->tanggal_mulai?->format('Y-m-d')) }}" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $agenda->tanggal_selesai?->format('Y-m-d')) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Waktu Mulai</label>
                    <input type="time" name="waktu_mulai" value="{{ old('waktu_mulai', $agenda->waktu_mulai) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Waktu Selesai</label>
                    <input type="time" name="waktu_selesai" value="{{ old('waktu_selesai', $agenda->waktu_selesai) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Lokasi / Tempat</label>
                <input type="text" name="lokasi" value="{{ old('lokasi', $agenda->lokasi) }}"
                       placeholder="Aula Utama / Online via Zoom"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        @foreach(\App\Models\Agenda::$kategoriLabels as $k => $v)
                        <option value="{{ $k }}" @selected(old('kategori',$agenda->kategori)===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Warna Label</label>
                    <select name="warna" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        @foreach(\App\Models\Agenda::$warnaOptions as $k => $v)
                        <option value="{{ $k }}" @selected(old('warna',$agenda->warna??'blue')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                <textarea name="deskripsi" rows="4"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">{{ old('deskripsi', $agenda->deskripsi) }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="publik" id="publik" value="1"
                       {{ old('publik', $agenda->publik ?? true) ? 'checked' : '' }}
                       class="w-4 h-4 rounded text-brand-600">
                <label for="publik" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tampilkan di halaman publik
                </label>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.agenda.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium">Batal</a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ $agenda->exists ? 'Simpan Perubahan' : 'Tambah Agenda' }}
            </button>
        </div>
    </form>
</div>
@endsection

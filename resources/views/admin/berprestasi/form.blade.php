@extends('admin.layouts.admin')

@section('title', isset($berprestasi) ? 'Edit Mahasiswa Berprestasi' : 'Tambah Mahasiswa Berprestasi')
@section('page-title', isset($berprestasi) ? 'Edit Mahasiswa Berprestasi' : 'Tambah Mahasiswa Berprestasi')

@section('content')
<div class="max-w-lg">
    <form
        action="{{ isset($berprestasi) ? route('admin.berprestasi.update', $berprestasi) : route('admin.berprestasi.store') }}"
        method="POST" class="space-y-5">
        @csrf
        @if(isset($berprestasi)) @method('PUT') @endif

        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20
                        border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400 space-y-1">
                @foreach($errors->all() as $err)<p>• {{ $err }}</p>@endforeach
            </div>
        @endif

        {{-- Mahasiswa --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mahasiswa *</label>
            <select name="mahasiswa_id" required
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                           focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                <option value="">— Pilih Mahasiswa —</option>
                @foreach($mahasiswaList as $mhs)
                    <option value="{{ $mhs->id }}"
                        {{ old('mahasiswa_id', $berprestasi->mahasiswa_id ?? '') == $mhs->id ? 'selected' : '' }}>
                        {{ $mhs->name }} — {{ $mhs->nim }}
                        {{ $mhs->programStudi ? '(' . $mhs->programStudi->singkatan . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Periode --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipe Periode *</label>
                <select name="periode" required
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                               focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                    @foreach($periodeLabels as $val => $label)
                        <option value="{{ $val }}"
                            {{ old('periode', $berprestasi->periode ?? '') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Urutan Tampil</label>
                <input name="urutan" type="number" min="0" max="255"
                       value="{{ old('urutan', $berprestasi->urutan ?? 0) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                              focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
            </div>
        </div>

        {{-- Label periode --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Label Periode *
                <span class="text-xs font-normal text-gray-400">(Contoh: "November 2025" atau "Semester Ganjil 2025/2026")</span>
            </label>
            <input name="periode_label" type="text" maxlength="60"
                   value="{{ old('periode_label', $berprestasi->periode_label ?? '') }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                          bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                          focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors"
                   placeholder="Contoh: November 2025">
        </div>

        {{-- Catatan admin --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Catatan / Alasan Pemilihan
            </label>
            <textarea name="catatan_admin" rows="3" maxlength="1000"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                             bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                             focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors resize-none"
                      placeholder="Alasan atau highlight pencapaian mahasiswa ini…">{{ old('catatan_admin', $berprestasi->catatan_admin ?? '') }}</textarea>
        </div>

        {{-- Aktif --}}
        <div class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" value="1" id="aktif"
                   {{ old('aktif', $berprestasi->aktif ?? true) ? 'checked' : '' }}
                   class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
            <label for="aktif" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                Tampilkan di beranda sekarang
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700
                           text-white font-semibold text-sm transition-colors">
                {{ isset($berprestasi) ? 'Simpan Perubahan' : 'Tambahkan' }}
            </button>
            <a href="{{ route('admin.berprestasi.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                      text-sm font-medium text-gray-600 dark:text-gray-400
                      hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection

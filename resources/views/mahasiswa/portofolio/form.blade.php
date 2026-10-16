@extends('mahasiswa.layouts.portal')

@section('page-title', $item ? 'Edit Pencapaian' : 'Tambah Pencapaian')
@section('page-subtitle', $item ? 'Perbarui detail item portofolio Anda' : 'Catat prestasi, sertifikasi, magang, dan aktivitas lainnya')

@section('content')

<div class="max-w-2xl">
    <form
        action="{{ $item ? route('mahasiswa.portofolio.update', $item) : route('mahasiswa.portofolio.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-5"
    >
        @csrf
        @if($item) @method('PUT') @endif

        {{-- Judul --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Judul / Nama Pencapaian *
            </label>
            <input name="judul" type="text"
                   value="{{ old('judul', $item?->judul) }}"
                   required maxlength="200"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                          bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                          focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors"
                   placeholder="Contoh: Juara 1 LKS Keperawatan Tingkat Nasional">
            @error('judul') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Kategori + Tingkat --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kategori *</label>
                <select name="kategori" required
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                               focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                    @foreach($kategoriLabels as $val => $label)
                        <option value="{{ $val }}" {{ old('kategori', $item?->kategori) === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('kategori') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tingkat *</label>
                <select name="tingkat" required
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                               focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                    @foreach($tingkatLabels as $val => $label)
                        <option value="{{ $val }}" {{ old('tingkat', $item?->tingkat) === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('tingkat') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Tanggal + Institusi --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal *</label>
                <input name="tanggal" type="date"
                       value="{{ old('tanggal', $item?->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('Y-m-d') : '') }}"
                       required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                              focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                @error('tanggal') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Institusi / Penyelenggara</label>
                <input name="institusi" type="text"
                       value="{{ old('institusi', $item?->institusi) }}"
                       maxlength="200"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                              focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors"
                       placeholder="Contoh: Kemenkes RI">
            </div>
        </div>

        {{-- Deskripsi --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Deskripsi Singkat
            </label>
            <textarea name="deskripsi" rows="3" maxlength="2000"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                             bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                             focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors resize-none"
                      placeholder="Ceritakan secara singkat apa yang Anda raih atau lakukan…">{{ old('deskripsi', $item?->deskripsi) }}</textarea>
        </div>

        {{-- URL Eksternal --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Link / URL (opsional)
            </label>
            <input name="url_eksternal" type="url"
                   value="{{ old('url_eksternal', $item?->url_eksternal) }}"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                          bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                          focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors"
                   placeholder="https://contoh.com/sertifikat">
            @error('url_eksternal') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Lampiran --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Lampiran (PDF / Gambar, maks 5 MB)
            </label>
            @if($item?->lampiran)
                <div class="mb-2 flex items-center gap-2 text-sm text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <a href="{{ $item->lampiran_url }}" target="_blank" rel="noopener"
                       class="text-brand-600 hover:underline">File terlampir (ganti dengan upload baru)</a>
                </div>
            @endif
            <input type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png,.webp"
                   class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4
                          file:rounded-xl file:border-0 file:text-sm file:font-medium
                          file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100
                          transition-colors">
        </div>

        {{-- Tampilkan publik toggle --}}
        <div class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
            <input type="hidden" name="tampilkan" value="0">
            <input type="checkbox" name="tampilkan" value="1" id="tampilkan"
                   {{ old('tampilkan', $item ? $item->tampilkan : true) ? 'checked' : '' }}
                   class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
            <div>
                <label for="tampilkan" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                    Tampilkan di profil publik
                </label>
                <p class="text-xs text-gray-500 mt-0.5">
                    Jika dicentang, item ini bisa terlihat oleh admin dan ditampilkan di beranda.
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700
                           text-white font-semibold text-sm transition-colors">
                {{ $item ? 'Simpan Perubahan' : 'Tambah ke Portofolio' }}
            </button>
            <a href="{{ route('mahasiswa.portofolio.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                      text-sm font-medium text-gray-600 dark:text-gray-400
                      hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                Batal
            </a>
        </div>
    </form>
</div>

@endsection

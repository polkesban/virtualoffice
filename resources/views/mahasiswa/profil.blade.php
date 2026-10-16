@extends('mahasiswa.layouts.portal')

@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Kelola informasi akun dan foto profil Anda')

@section('content')
<div class="max-w-lg">
    <form action="{{ route('mahasiswa.profil.update') }}" method="POST"
          enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PUT')

        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20
                        border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400 space-y-1">
                @foreach($errors->all() as $err)
                    <p>• {{ $err }}</p>
                @endforeach
            </div>
        @endif

        {{-- Foto --}}
        <div class="flex items-center gap-5">
            <img src="{{ $mahasiswa->foto_url }}" alt="{{ $mahasiswa->name }}"
                 id="foto-preview"
                 class="w-20 h-20 rounded-2xl object-cover border border-gray-100 dark:border-gray-700">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ganti Foto Profil</label>
                <input type="file" name="foto" accept="image/*" id="foto-input"
                       onchange="document.getElementById('foto-preview').src = URL.createObjectURL(this.files[0])"
                       class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3
                              file:rounded-lg file:border-0 file:text-xs file:font-medium
                              file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                <p class="text-xs text-gray-400 mt-1">JPG/PNG, maks 2 MB</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">NIM</label>
            <input type="text" value="{{ $mahasiswa->nim }}" disabled
                   class="w-full rounded-xl border border-gray-100 dark:border-gray-700
                          bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-400 cursor-not-allowed">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap *</label>
            <input name="name" type="text" value="{{ old('name', $mahasiswa->name) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                          bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                          focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email *</label>
            <input name="email" type="email" value="{{ old('email', $mahasiswa->email) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                          bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                          focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. HP</label>
            <input name="no_hp" type="tel" value="{{ old('no_hp', $mahasiswa->no_hp) }}"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                          bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                          focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Program Studi *</label>
                <select name="program_studi_id" required
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                               focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}"
                            {{ old('program_studi_id', $mahasiswa->program_studi_id) == $prodi->id ? 'selected' : '' }}>
                            {{ $prodi->jenjang }} {{ $prodi->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Angkatan</label>
                <input name="angkatan" type="number" min="2015" max="{{ date('Y') + 1 }}"
                       value="{{ old('angkatan', $mahasiswa->angkatan) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-white dark:bg-gray-800 px-4 py-2.5 text-sm outline-none
                              focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
            </div>
        </div>

        {{-- Ganti password (opsional) --}}
        <details class="group">
            <summary class="text-sm font-medium text-gray-600 dark:text-gray-400 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors">
                Ganti Password (kosongkan jika tidak ingin mengubah)
            </summary>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Password Baru</label>
                      <div class="relative" data-password-toggle>
                       <input name="password" type="password" minlength="8" autocomplete="new-password" data-password-toggle-input
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-800 px-3 py-2 pr-24 text-sm outline-none
                               focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors"
                           placeholder="Min. 8 karakter">
                       <button type="button" data-password-toggle-button
                            class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 rounded-lg">
                        Lihat
                       </button>
                      </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Konfirmasi</label>
                      <div class="relative" data-password-toggle>
                       <input name="password_confirmation" type="password" autocomplete="new-password" data-password-toggle-input
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-800 px-3 py-2 pr-24 text-sm outline-none
                               focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors"
                           placeholder="Ulangi password">
                       <button type="button" data-password-toggle-button
                            class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 rounded-lg">
                        Lihat
                       </button>
                      </div>
                </div>
            </div>
        </details>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700
                           text-white font-semibold text-sm transition-colors">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection

@extends('admin.layouts.admin')
@section('title', $prodi->exists ? 'Edit Program Studi' : 'Tambah Program Studi')

@section('content')
<div class="p-6 max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.program-studi.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
                {{ $prodi->exists ? 'Edit: ' . $prodi->nama : 'Tambah Program Studi' }}
            </h1>
        </div>
        @if($prodi->exists)
        <a href="{{ route('admin.program-studi.content', $prodi) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 transition-colors hover:bg-brand-100 dark:border-brand-900 dark:bg-brand-950/30 dark:text-brand-300">
            Kelola Blok Konten
        </a>
        @endif
    </div>

    <form method="POST"
          action="{{ $prodi->exists ? route('admin.program-studi.update', $prodi) : route('admin.program-studi.store') }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        @if($prodi->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-4 text-sm text-red-700 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Program Studi <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $prodi->nama) }}" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Singkatan</label>
                    <input type="text" name="singkatan" value="{{ old('singkatan', $prodi->singkatan) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Jenjang <span class="text-red-500">*</span></label>
                    <select name="jenjang" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        @foreach(['D3','D4','Profesi','S1','S2'] as $j)
                        <option value="{{ $j }}" @selected(old('jenjang',$prodi->jenjang)===$j)>{{ $j }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Icon Emoji</label>
                    <input type="text" name="icon_emoji" value="{{ old('icon_emoji', $prodi->icon_emoji) }}"
                           placeholder="🏥" maxlength="10"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Gelar</label>
                    <input type="text" name="gelar" value="{{ old('gelar', $prodi->gelar) }}"
                           placeholder="S.Tr.Kep."
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Akreditasi</label>
                    <input type="text" name="akreditasi" value="{{ old('akreditasi', $prodi->akreditasi) }}"
                           placeholder="Unggul / A / B"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Durasi (Semester)</label>
                    <input type="number" name="durasi_semester" value="{{ old('durasi_semester', $prodi->durasi_semester) }}"
                           min="2" max="12"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Kuota</label>
                    <input type="number" name="kuota" value="{{ old('kuota', $prodi->kuota) }}" min="0"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Jurusan</label>
                    <select name="jurusan_id" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        <option value="">— Pilih Jurusan —</option>
                        @foreach($jurusan as $j)
                        <option value="{{ $j->id }}" @selected(old('jurusan_id',$prodi->jurusan_id)===$j->id)>{{ $j->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Urutan Tampil</label>
                    <input type="number" name="urutan" value="{{ old('urutan', $prodi->urutan ?? 0) }}" min="0"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Singkat</label>
                <textarea name="deskripsi_singkat" rows="2" maxlength="500"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('deskripsi_singkat', $prodi->deskripsi_singkat) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Lengkap</label>
                <textarea id="deskripsi" name="deskripsi" rows="6" data-rich-editor data-rich-editor-profile="basic"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">{{ old('deskripsi', $prodi->deskripsi) }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Editor bisa diperbesar atau dipertinggi dengan drag pada sudut kanan bawah.</p>
                @if($prodi->exists)
                <p class="text-xs text-gray-400 mt-1">Blok konten tambahan program studi akan tampil di bawah deskripsi utama pada halaman publik.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Gambar Prodi</label>
                @if($prodi->gambar)
                <img src="{{ \App\Support\MediaStorage::url($prodi->gambar) }}" class="h-20 w-auto rounded-xl object-cover mb-2">
                @endif
                <input type="file" name="gambar" accept="image/*" class="text-sm text-gray-500 dark:text-gray-400">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="aktif" id="aktif" value="1"
                       {{ old('aktif', $prodi->aktif ?? true) ? 'checked' : '' }}
                       class="w-4 h-4 rounded text-brand-600">
                <label for="aktif" class="text-sm font-medium text-gray-700 dark:text-gray-300">Aktif / Tampilkan</label>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.program-studi.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ $prodi->exists ? 'Simpan Perubahan' : 'Tambah Program Studi' }}
            </button>
        </div>
    </form>
</div>
@endsection

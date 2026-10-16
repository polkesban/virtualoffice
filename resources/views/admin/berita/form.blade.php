@extends('admin.layouts.admin')
@section('title', $berita->exists ? 'Edit Berita' : 'Tambah Berita')

@section('content')
<div class="p-6 max-w-4xl mx-auto" x-data="beritaAiDraft()">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.berita.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
            {{ $berita->exists ? 'Edit Berita' : 'Tambah Berita' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ $berita->exists ? route('admin.berita.update', $berita) : route('admin.berita.store') }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        @if($berita->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul <span class="text-red-500">*</span></label>
                <input type="text" name="judul" value="{{ old('judul', $berita->judul) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                    <select name="kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        @foreach(['prestasi'=>'Prestasi','pengumuman'=>'Pengumuman','kegiatan'=>'Kegiatan','akademik'=>'Akademik','umum'=>'Umum'] as $k=>$v)
                        <option value="{{ $k }}" @selected(old('kategori',$berita->kategori)===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        <option value="draft"     @selected(old('status',$berita->status)==='draft')>Draft</option>
                        <option value="published" @selected(old('status',$berita->status)==='published')>Published</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Publikasi</label>
                <input type="datetime-local" name="published_at"
                       value="{{ old('published_at', $berita->published_at?->format('Y-m-d\TH:i')) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                <p class="text-xs text-gray-400 mt-1">Kosongkan untuk menggunakan waktu saat ini (jika status Published)</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Ringkasan</label>
                <textarea name="ringkasan" rows="2"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('ringkasan', $berita->ringkasan) }}</textarea>
            </div>

            <div class="rounded-2xl border border-brand-100 dark:border-brand-900 bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-gray-900 p-5 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-brand-700 dark:text-brand-300">AI Generate Content</p>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white mt-1">Buat draft berita langsung dari form ini</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi topik dan arahan singkat, lalu AI akan mengisi editor isi berita di bawah ini.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-white/80 dark:bg-brand-950 px-3 py-1 text-[11px] font-bold text-brand-700 dark:text-brand-300">
                        Builder AI
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Topik / Subjek <span class="text-red-500">*</span></label>
                        <input type="text" x-model="acara"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                               placeholder="mis: Survey Pemahaman Visi Misi Poltekkes Bandung">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tempat / Unit (opsional)</label>
                        <input type="text" x-model="tempat"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                               placeholder="mis: Poltekkes Kemenkes Bandung">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tanggal / Periode (opsional)</label>
                        <input type="text" x-model="tanggal"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                               placeholder="mis: April 2026">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Arahan Tambahan</label>
                        <input type="text" x-model="catatan"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                               placeholder="fokus pada tujuan, responden, manfaat, ajakan isi survey, dll.">
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <label class="flex flex-col items-center justify-center w-36 h-24 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-brand-400 dark:hover:border-brand-600 cursor-pointer bg-white/70 dark:bg-gray-800/50 transition-colors overflow-hidden relative"
                           :class="mediaPreview ? 'border-solid border-brand-300 dark:border-brand-700 p-0' : ''">
                        <template x-if="mediaPreview">
                            <img :src="mediaPreview" class="w-full h-full object-cover rounded-xl">
                        </template>
                        <template x-if="!mediaPreview">
                            <div class="flex flex-col items-center text-gray-400 dark:text-gray-500 text-xs text-center px-2">
                                <span class="text-2xl mb-1">🖼️</span>
                                <span>Foto opsional</span>
                                <span class="mt-0.5 text-[10px] text-gray-300">JPG, PNG, WebP, GIF</span>
                            </div>
                        </template>
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" x-ref="mediaInput"
                               @change="pickMedia($event)">
                    </label>

                    <div class="flex-1 space-y-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Upload foto bila ingin AI menyesuaikan narasi berita dengan konteks visual yang Anda punya.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" :disabled="generating || !acara"
                                    @click="generate()"
                                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed px-5 py-2 text-sm font-semibold text-white transition-colors">
                                <span x-show="!generating">🚀 Generate Draft Berita</span>
                                <span x-show="generating">⏳ Menyusun draft...</span>
                            </button>

                            <button type="button" x-show="mediaPreview"
                                    @click="clearMedia()"
                                    class="text-xs font-medium text-red-500 hover:text-red-600 hover:underline">
                                Hapus foto
                            </button>
                        </div>
                        <p x-show="mediaFile" x-text="mediaFile ? '📎 ' + mediaFile.name : ''" class="text-xs text-brand-600 dark:text-brand-300"></p>
                        <p x-show="error" x-text="error" class="text-xs text-red-500"></p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Isi Berita <span class="text-red-500">*</span></label>
                <textarea id="isi" name="isi" rows="12" x-ref="isiField" data-rich-editor data-rich-editor-profile="full"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm font-mono text-xs">{{ old('isi', $berita->isi) }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Mendukung HTML/rich text. Seret sudut kanan bawah editor untuk ubah ukuran, atau pakai fullscreen dari toolbar.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Gambar Sampul</label>
                @if($berita->gambar_sampul)
                <div class="mb-2">
                    <img src="{{ \App\Support\MediaStorage::url($berita->gambar_sampul) }}" class="h-24 w-auto rounded-xl object-cover">
                </div>
                @endif
                <input type="file" name="gambar_sampul" accept="image/*"
                       class="text-sm text-gray-500 dark:text-gray-400">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Alt Text Gambar</label>
                <input type="text" name="gambar_sampul_alt" value="{{ old('gambar_sampul_alt', $berita->gambar_sampul_alt) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tags (pisahkan dengan koma)</label>
                <input type="text" name="tags"
                       value="{{ old('tags', is_array($berita->tags) ? implode(', ', $berita->tags) : $berita->tags) }}"
                       placeholder="akreditasi, kampus, award"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Meta Description (SEO)</label>
                <input type="text" name="meta_description" value="{{ old('meta_description', $berita->meta_description) }}"
                       maxlength="255"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.berita.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ $berita->exists ? 'Simpan Perubahan' : 'Tambah Berita' }}
            </button>
        </div>
    </form>
</div>

<script>
function beritaAiDraft() {
    return {
        acara: '',
        tempat: '',
        tanggal: '',
        catatan: '',
        mediaFile: null,
        mediaPreview: '',
        generating: false,
        error: '',
        pickMedia(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            this.mediaFile = file;
            const reader = new FileReader();
            reader.onload = e => {
                this.mediaPreview = e.target?.result ?? '';
            };
            reader.readAsDataURL(file);
        },
        clearMedia() {
            this.mediaFile = null;
            this.mediaPreview = '';
            if (this.$refs.mediaInput) {
                this.$refs.mediaInput.value = '';
            }
        },
        async generate() {
            if (this.generating || !this.acara) {
                return;
            }

            this.generating = true;
            this.error = '';

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('type', 'richtext');
            formData.append('acara', this.acara || '');
            formData.append('tempat', this.tempat || '');
            formData.append('tanggal', this.tanggal || '');
            formData.append('catatan', this.catatan || '');
            formData.append('context_title', document.querySelector('input[name="judul"]')?.value || '{{ old('judul', $berita->judul) }}');

            if (this.mediaFile) {
                formData.append('media', this.mediaFile);
            }

            try {
                const response = await fetch('{{ route('admin.content-engine.generate-block') }}', {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Gagal generate konten.');
                }

                const payload = data.payload || data;
                if (!payload.konten) {
                    throw new Error('Response AI tidak lengkap.');
                }

                if (!window.PolkesbanRichText?.setContent('isi', payload.konten)) {
                    this.$refs.isiField.value = payload.konten;
                }
            } catch (error) {
                this.error = error.message || 'Koneksi error.';
            } finally {
                this.generating = false;
            }
        },
    };
}
</script>
@endsection

@extends('admin.layouts.admin')
@section('title', 'Edit Halaman: ' . $halaman->judul)

@section('content')
<div class="p-6 max-w-4xl mx-auto" x-data="staticPageAiDraft(@js($halaman->judul))">
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
                ✏️ Edit: {{ $halaman->judul }}
            </h1>
            @if($halaman->updated_at)
            <span class="text-xs text-gray-400">
                Terakhir diubah: {{ $halaman->updated_at->diffForHumans() }}
                {{ $halaman->editor ? 'oleh ' . $halaman->editor->name : '' }}
            </span>
            @endif
        </div>
        <a href="{{ route('admin.halaman-statis.section.create', $halaman->kunci) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            ➕ Tambah Blok Konten
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-700 dark:text-green-400">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-4 text-sm text-red-700 dark:text-red-400">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.halaman-statis.update', $halaman->kunci) }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Halaman</label>
                <input type="text" name="judul" value="{{ old('judul', $halaman->judul) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div class="rounded-2xl border border-brand-100 dark:border-brand-900 bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-gray-900 p-5 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-brand-700 dark:text-brand-300">AI Generate Content</p>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white mt-1">Buat draft isi halaman secara otomatis</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi topik dan arahan singkat, lalu AI akan mengisi editor HTML di bawah ini.</p>
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
                               placeholder="mis: Profil pusat karier mahasiswa">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tempat / Unit (opsional)</label>
                        <input type="text" x-model="tempat"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                               placeholder="mis: Poltekkes Bandung">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tanggal / Periode (opsional)</label>
                        <input type="text" x-model="tanggal"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                               placeholder="mis: Tahun Akademik 2026/2027">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Arahan Tambahan</label>
                        <input type="text" x-model="catatan"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                               placeholder="fokus pada layanan, prestasi, fasilitas, dll.">
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
                        <p class="text-xs text-gray-500 dark:text-gray-400">Upload foto bila ingin AI menyesuaikan isi halaman dengan konteks visual yang Anda punya.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" :disabled="generating || !acara"
                                    @click="generate()"
                                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed px-5 py-2 text-sm font-semibold text-white transition-colors">
                                <span x-show="!generating">🚀 Generate Draft Halaman</span>
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
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Konten (HTML/Rich Text)</label>
                <textarea name="konten" id="konten" rows="20" x-ref="kontenField" data-rich-editor data-rich-editor-profile="full"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm font-mono text-xs leading-relaxed">{{ old('konten', $halaman->konten) }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Mendukung HTML. Gunakan tag &lt;p&gt;, &lt;h2&gt;, &lt;ul&gt;, &lt;strong&gt;, dll. Seret sudut kanan bawah editor untuk mengatur lebar/tinggi, atau gunakan mode fullscreen dari toolbar.</p>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                Simpan Perubahan
            </button>
        </div>
    </form>

    {{-- Block Content Sections --}}
    <div class="mt-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">🧩 Blok Konten Tambahan</h2>
            <a href="{{ route('admin.halaman-statis.section.create', $halaman->kunci) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                ➕ Tambah Blok Konten
            </a>
        </div>

        @if(isset($sections) && $sections->count() > 0)
        <div class="space-y-2">
            @foreach($sections as $sec)
            <div class="flex items-center gap-3 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl px-4 py-3">
                <div class="text-gray-400 cursor-move select-none">☰</div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm text-gray-800 dark:text-gray-200 truncate">
                        {{ $sec->judul ?: '(tanpa judul)' }}
                        <span class="ml-2 text-xs font-normal text-gray-400">{{ $sec->tipe }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Move up --}}
                    <form action="{{ route('admin.halaman-statis.section.move', $sec) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="direction" value="up">
                        <button type="submit" class="text-gray-400 hover:text-gray-600 text-sm px-1" title="Naik">▲</button>
                    </form>
                    {{-- Move down --}}
                    <form action="{{ route('admin.halaman-statis.section.move', $sec) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="direction" value="down">
                        <button type="submit" class="text-gray-400 hover:text-gray-600 text-sm px-1" title="Turun">▼</button>
                    </form>
                    {{-- Toggle --}}
                    <form action="{{ route('admin.halaman-statis.section.toggle', $sec) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" title="{{ $sec->aktif ? 'Nonaktifkan' : 'Aktifkan' }}"
                                class="text-xs px-2 py-1 rounded-lg {{ $sec->aktif ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $sec->aktif ? '✅ Aktif' : '⬜ Nonaktif' }}
                        </button>
                    </form>
                    {{-- Edit --}}
                    <a href="{{ route('admin.halaman-statis.section.edit', $sec) }}"
                       class="text-xs px-3 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-medium hover:bg-blue-100 transition-colors">
                        ✏️ Edit
                    </a>
                    @if($sec->tipe === 'formulir')
                    <a href="{{ route('admin.page-sections.responses.show', $sec) }}"
                       class="text-xs px-3 py-1 rounded-lg bg-brand-50 text-brand-700 font-medium hover:bg-brand-100 transition-colors">
                        📊 Rekap
                    </a>
                    @endif
                    {{-- Delete --}}
                    <form action="{{ route('admin.halaman-statis.section.destroy', $sec) }}" method="POST" class="inline"
                          onsubmit="return confirm('Hapus blok ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs px-3 py-1 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 transition-colors">🗑️</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="py-10 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
            <p class="text-gray-400 text-sm">Belum ada blok konten.
                <a href="{{ route('admin.halaman-statis.section.create', $halaman->kunci) }}" class="text-brand-500 hover:underline">Tambah sekarang</a>.
            </p>
        </div>
        @endif
    </div>
</div>

<script>
function staticPageAiDraft(pageTitle) {
    return {
        pageTitle,
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
            formData.append('context_title', this.pageTitle || '');

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

                if (!window.PolkesbanRichText?.setContent('konten', payload.konten)) {
                    this.$refs.kontenField.value = payload.konten;
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

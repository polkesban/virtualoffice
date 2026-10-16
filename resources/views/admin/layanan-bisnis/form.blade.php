@extends('admin.layouts.admin')
@section('title', $layanan->exists ? 'Edit Layanan' : 'Tambah Layanan Bisnis')

@section('content')
@php
    $initialVariantOptions = old('opsi_varian', $layanan->aktif_varian_opsi ?? []);
@endphp
<div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto w-full">
    {{-- Page Header with Quick Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.layanan-bisnis.index') }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:text-gray-800 dark:hover:text-white shadow-xs transition" title="Kembali ke Katalog">
                ←
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
                        {{ $layanan->exists ? 'Edit: ' . $layanan->nama : 'Tambah Layanan / Produk' }}
                    </h1>
                    @if($layanan->exists)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $layanan->status_stok_badge_class }}">
                        ● {{ $layanan->status_stok_label }}
                    </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola informasi produk, harga dasar, stok, ketersediaan, dan varian.</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.layanan-bisnis.index') }}"
               class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Batal
            </a>
            <button type="submit" form="product-form"
                    class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                💾 {{ $layanan->exists ? 'Simpan Perubahan' : 'Tambah Layanan' }}
            </button>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 dark:bg-red-950/30 dark:border-red-900/50 dark:text-red-300">
        <div class="flex items-center gap-2 font-bold mb-1">
            <span>⚠️</span> Ada kesalahan pada formulir:
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form id="product-form"
          method="POST"
          action="{{ $layanan->exists ? route('admin.layanan-bisnis.update', $layanan) : route('admin.layanan-bisnis.store') }}"
          enctype="multipart/form-data"
          x-data="{
              options: (@js(array_values($initialVariantOptions)) || []).map(opt => ({
                  label: opt.label || '',
                  harga: (opt.harga !== null && opt.harga !== undefined && opt.harga !== '') ? opt.harga : '',
                  satuan_harga: opt.satuan_harga || '',
                  gambar: opt.gambar || '',
                  stok: (opt.stok !== null && opt.stok !== undefined && opt.stok !== '') ? opt.stok : '',
                  tersedia: opt.tersedia !== undefined ? Boolean(opt.tersedia) : true,
              })),
              mainImagePreview: '{{ $layanan->gambar ? \App\Support\MediaStorage::url($layanan->gambar, 'bisnis') : '' }}',
              mainImageBase64: '',
              hapusGambarUtama: false,
              isDraggingMain: false,

              resolveImg(src) {
                  if (!src) return '';
                  if (src.startsWith('data:image/') || src.startsWith('http://') || src.startsWith('https://') || src.startsWith('/')) {
                      return src;
                  }
                  return '/storage/' + src;
              },

              addOption() {
                  this.options.push({ label: '', harga: '', satuan_harga: '', gambar: '', stok: '', tersedia: true });
              },
              removeOption(index) {
                  this.options.splice(index, 1);
              },
              uploadOptionImage(event, option) {
                  const file = event.target.files?.[0];
                  if (!file) return;
                  this.readFileToOption(file, option);
              },
              handleOptionPaste(event, option) {
                  const items = event.clipboardData?.items;
                  if (!items) return;
                  for (let i = 0; i < items.length; i++) {
                      if (items[i].type && items[i].type.startsWith('image/')) {
                          const file = items[i].getAsFile();
                          if (file) {
                              event.preventDefault();
                              this.readFileToOption(file, option);
                              return;
                          }
                      }
                  }
              },
              handleOptionDrop(event, option) {
                  const file = event.dataTransfer?.files?.[0];
                  if (file && file.type && file.type.startsWith('image/')) {
                      event.preventDefault();
                      this.readFileToOption(file, option);
                  }
              },
              async pasteOptionClipboard(option) {
                  try {
                      if (navigator.clipboard && navigator.clipboard.read) {
                          const items = await navigator.clipboard.read();
                          for (const item of items) {
                              const imgType = item.types.find(t => t.startsWith('image/'));
                              if (imgType) {
                                  const blob = await item.getType(imgType);
                                  this.readFileToOption(blob, option);
                                  return;
                              }
                          }
                      }
                      alert('Arahkan kursor atau klik pada kolom gambar varian, lalu tekan tombol keyboard Ctrl + V.');
                  } catch (e) {
                      alert('Arahkan kursor atau klik pada kolom gambar varian, lalu tekan tombol keyboard Ctrl + V.');
                  }
              },
              readFileToOption(file, option) {
                  const reader = new FileReader();
                  reader.onload = (e) => {
                      option.gambar = String(e.target?.result || '').trim();
                  };
                  reader.readAsDataURL(file);
              },

              // Main Image Logic
              handleMainFileChange(event) {
                  const file = event.target.files?.[0];
                  if (!file) return;
                  this.applyMainImage(file);
              },
              handleMainPaste(event) {
                  const items = event.clipboardData?.items;
                  if (!items) return;
                  for (let i = 0; i < items.length; i++) {
                      if (items[i].type && items[i].type.startsWith('image/')) {
                          const file = items[i].getAsFile();
                          if (file) {
                              event.preventDefault();
                              this.applyMainImage(file);
                              return;
                          }
                      }
                  }
              },
              handleMainDrop(event) {
                  this.isDraggingMain = false;
                  const file = event.dataTransfer?.files?.[0];
                  if (file && file.type && file.type.startsWith('image/')) {
                      event.preventDefault();
                      this.applyMainImage(file);
                  }
              },
              async pasteMainClipboard() {
                  try {
                      if (navigator.clipboard && navigator.clipboard.read) {
                          const items = await navigator.clipboard.read();
                          for (const item of items) {
                              const imgType = item.types.find(t => t.startsWith('image/'));
                              if (imgType) {
                                  const blob = await item.getType(imgType);
                                  this.applyMainImage(blob);
                                  return;
                              }
                          }
                      }
                      alert('Klik pada kotak Foto Utama, lalu tekan tombol keyboard Ctrl + V.');
                  } catch (e) {
                      alert('Klik pada kotak Foto Utama, lalu tekan tombol keyboard Ctrl + V.');
                  }
              },
              applyMainImage(file) {
                  this.hapusGambarUtama = false;
                  const reader = new FileReader();
                  reader.onload = (e) => {
                      const dataUrl = String(e.target?.result || '').trim();
                      this.mainImagePreview = dataUrl;
                      this.mainImageBase64 = dataUrl;
                  };
                  reader.readAsDataURL(file);

                  try {
                      const dt = new DataTransfer();
                      const fileObj = (file instanceof File) ? file : new File([file], 'pasted-screenshot.png', { type: file.type || 'image/png' });
                      dt.items.add(fileObj);
                      const el = document.getElementById('main-gambar-input');
                      if (el) el.files = dt.files;
                  } catch (err) {}
              },
              removeMainImage() {
                  this.mainImagePreview = '';
                  this.mainImageBase64 = '';
                  this.hapusGambarUtama = true;
                  const el = document.getElementById('main-gambar-input');
                  if (el) el.value = '';
              }
          }">
        @csrf
        @if($layanan->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- Left / Main Column (span 8 on desktop) --}}
            <div class="lg:col-span-8 space-y-6">
                {{-- Card 1: Informasi Dasar Produk --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-lg">📝</span>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Informasi Produk / Layanan</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-7">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                Nama Layanan / Produk <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nama" value="{{ old('nama', $layanan->nama) }}" required
                                   placeholder="Contoh: Kantin KPHS / Tes TOEFL Institusi"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                        </div>

                        <div class="sm:col-span-5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select name="kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                                @foreach(\App\Models\LayananBisnis::$kategoriLabels as $k => $v)
                                <option value="{{ $k }}" @selected(old('kategori',$layanan->kategori??'layanan')===$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                            Deskripsi Produk
                        </label>
                        <textarea name="deskripsi" rows="3"
                                  placeholder="Jelaskan detail menu, layanan, paket, atau fasilitas yang didapatkan pelanggan..."
                                  class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">{{ old('deskripsi', $layanan->deskripsi) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                Harga Dasar (Rp)
                            </label>
                            <input type="number" name="harga" value="{{ old('harga', $layanan->harga) }}"
                                   min="0" step="1000" placeholder="Contoh: 15000 (Kosongkan jika fleksibel)"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                Satuan Harga
                            </label>
                            <input type="text" name="satuan_harga" value="{{ old('satuan_harga', $layanan->satuan_harga) }}"
                                   placeholder="Contoh: per paket / per orang / per porsi"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                        </div>
                    </div>
                </div>

                {{-- Card 2: Jenis / Tipe / Varian Produk --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🍱</span>
                                <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Jenis / Varian Produk</h2>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">Tambahkan pilihan varian menu, ukuran, paket, atau tipe layanan lengkap dengan harga dan stok.</p>
                        </div>
                        <button type="button" @click="addOption()"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-50 px-4 py-2 text-xs font-bold text-brand-700 hover:bg-brand-100 dark:bg-brand-900/30 dark:text-brand-300 transition shadow-xs">
                            <span>＋</span>
                            <span>Tambah Opsi Varian</span>
                        </button>
                    </div>

                    <template x-if="!options.length">
                        <div class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20 px-6 py-8 text-center">
                            <span class="text-3xl block mb-2">📋</span>
                            <p class="font-bold text-sm text-gray-700 dark:text-gray-300">Belum ada varian produk</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Kosongkan jika produk hanya memiliki satu harga standar, atau klik tombol Tambah Opsi di atas untuk membuat menu/pilihan varian.</p>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <template x-for="(option, index) in options" :key="index">
                            <div class="rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 p-4 sm:p-5 border border-gray-200/80 dark:border-gray-700/80 space-y-3.5 transition-all">
                                {{-- Mobile Header: Varian number + Delete --}}
                                <div class="flex items-center justify-between sm:hidden pb-2 border-b border-gray-200/60 dark:border-gray-700/60">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide" x-text="`Varian #${index + 1}`"></span>
                                    <button type="button" @click="removeOption(index)"
                                            class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                                        <span>🗑️</span> Hapus
                                    </button>
                                </div>

                                {{-- Responsive Fields Row --}}
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                    <div class="sm:col-span-4">
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Label Varian / Menu <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="option.label" :name="`opsi_varian[${index}][label]`"
                                               placeholder="Contoh: Paket Nasi Ayam / Susu Hangat"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm text-gray-900 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>

                                    <div class="col-span-6 sm:col-span-2">
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Harga (Rp)
                                        </label>
                                        <input type="number" min="0" step="1000" x-model="option.harga" :name="`opsi_varian[${index}][harga]`"
                                               placeholder="10000"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>

                                    <div class="col-span-6 sm:col-span-2">
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Satuan
                                        </label>
                                        <input type="text" x-model="option.satuan_harga" :name="`opsi_varian[${index}][satuan_harga]`"
                                               placeholder="per porsi"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>

                                    <div class="col-span-6 sm:col-span-2">
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400" title="Kosongkan jika stok unlimited">
                                            Stok
                                        </label>
                                        <input type="number" min="0" x-model="option.stok" :name="`opsi_varian[${index}][stok]`"
                                               placeholder="∞ Ready"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>

                                    <div class="col-span-6 sm:col-span-2 flex items-center gap-1.5">
                                        <input type="hidden" :name="`opsi_varian[${index}][tersedia]`" :value="option.tersedia ? '1' : '0'">
                                        <button type="button" @click="option.tersedia = !option.tersedia"
                                                :class="option.tersedia ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300'"
                                                class="flex-1 py-2 px-2.5 rounded-xl border text-xs font-bold transition text-center select-none shadow-xs"
                                                :title="option.tersedia ? 'Klik untuk tandai Habis' : 'Klik untuk tandai Ready'">
                                            <span x-text="option.tersedia ? '✓ Ready' : '✕ Habis'"></span>
                                        </button>
                                        <button type="button" @click="removeOption(index)"
                                                class="hidden sm:inline-flex p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/30 dark:text-red-300 transition-colors shadow-xs" title="Hapus varian">
                                            🗑️
                                        </button>
                                    </div>
                                </div>

                                {{-- Foto Varian --}}
                                <div class="pt-2.5 border-t border-gray-200/60 dark:border-gray-700/60"
                                     @paste.stop="handleOptionPaste($event, option)"
                                     @dragover.prevent
                                     @drop.prevent="handleOptionDrop($event, option)">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1.5">
                                        <label class="text-xs font-bold text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                            <span>📷</span>
                                            <span>Foto / Gambar Varian (Opsional)</span>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                                Bisa Ctrl + V Screenshot
                                            </span>
                                        </label>
                                        <span class="text-[10px] text-gray-400">Tempel URL, klik Upload, atau tekan <strong>Ctrl + V</strong></span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="relative flex-1 min-w-[200px]">
                                            <input type="text" x-model="option.gambar" :name="`opsi_varian[${index}][gambar]`"
                                                   @paste.stop="handleOptionPaste($event, option)"
                                                   placeholder="Tempel link URL atau tekan Ctrl + V screenshot..."
                                                   class="w-full rounded-xl border border-gray-200 bg-white pl-3.5 pr-8 py-2 text-xs text-gray-900 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            <template x-if="option.gambar">
                                                <button type="button" @click="option.gambar = ''" title="Hapus foto varian"
                                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-xs">
                                                    ✕
                                                </button>
                                            </template>
                                        </div>

                                        <button type="button" @click="pasteOptionClipboard(option)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 dark:bg-purple-950/30 dark:text-purple-300 text-xs font-bold shrink-0 transition shadow-xs"
                                                title="Paste screenshot dari clipboard">
                                            <span>📋</span>
                                            <span>Paste Screenshot</span>
                                        </button>

                                        <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-xs font-bold text-gray-700 dark:text-gray-200 shrink-0 transition shadow-xs" title="Pilih foto varian dari komputer">
                                            <span>📁</span>
                                            <span>Upload</span>
                                            <input type="file" accept="image/*" class="hidden" @change="uploadOptionImage($event, option)">
                                        </label>
                                    </div>

                                    {{-- Preview Foto Varian --}}
                                    <template x-if="option.gambar">
                                        <div class="mt-2.5 flex items-center gap-3 p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xs">
                                            <img :src="resolveImg(option.gambar)" class="w-14 h-14 object-cover rounded-lg bg-gray-100 dark:bg-gray-800 shrink-0 border border-gray-200 dark:border-gray-700" alt="Preview Varian">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate" x-text="option.label || 'Foto varian'"></p>
                                                    <span x-show="option.gambar.startsWith('data:image/')" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                                                        📋 Screenshot Siap Disimpan
                                                    </span>
                                                </div>
                                                <p class="text-[10px] text-gray-400 truncate mt-0.5" x-text="option.gambar.startsWith('data:image/') ? 'Format gambar screenshot (base64) - otomatis disimpan saat form disimpan' : option.gambar"></p>
                                            </div>
                                            <button type="button" @click="option.gambar = ''" class="px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition shrink-0">
                                                Hapus
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Card 3: Tautan Pemesanan & QRIS --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-lg">💳</span>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Tautan Pemesanan & Pembayaran QRIS</h2>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                            Link Pemesanan (WhatsApp / Google Form)
                        </label>
                        <input type="url" name="link_pesan" value="{{ old('link_pesan', $layanan->link_pesan) }}"
                               placeholder="https://wa.me/628xx... atau https://forms.gle/..."
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                        <p class="text-xs text-gray-400 mt-1">Gunakan link ini jika produk diarahkan ke kontak WhatsApp pemesanan langsung.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                Link Gambar QRIS
                            </label>
                            <input type="url" name="qris_link" value="{{ old('qris_link', $layanan->qris_link) }}"
                                   placeholder="https://... atau link file QRIS"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                            <p class="text-xs text-gray-400 mt-1">Digunakan pada popup QRIS di form pemesanan publik.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                                Label QRIS
                            </label>
                            <input type="text" name="qris_label" value="{{ old('qris_label', $layanan->qris_label) }}"
                                   placeholder="Contoh: QRIS Koperasi Pegawai Husada Sejahtera"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right / Sidebar Column (span 4 on desktop) --}}
            <div class="lg:col-span-4 space-y-6">
                {{-- Card: Stok & Ketersediaan --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-lg">📦</span>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Ketersediaan & Stok</h2>
                    </div>

                    {{-- Switch Ready / Habis --}}
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/80 dark:border-gray-700/80">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                            Status Ketersediaan
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="is_tersedia" value="1"
                                   {{ old('is_tersedia', $layanan->is_tersedia ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-3 text-sm font-bold text-gray-700 dark:text-gray-200 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400">
                                Ready / Tersedia
                            </span>
                        </label>
                        <p class="text-[11px] text-gray-400 mt-2">Matikan switch ini jika produk sedang habis stok untuk menutup pemesanan seketika.</p>
                    </div>

                    {{-- Input Stok --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                            Jumlah Stok Produk
                        </label>
                        <input type="number" name="stok" value="{{ old('stok', $layanan->stok) }}"
                               min="0" placeholder="Kosongkan jika unlimited"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                        <p class="text-[11px] text-gray-400 mt-1">Kosongkan jika stok tidak dibatasi. Jika diisi 0, sistem otomatis menampilkan "Stok Habis".</p>
                    </div>
                </div>

                {{-- Card: Foto Utama Produk --}}
                {{-- Card: Foto Utama Produk --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🖼️</span>
                            <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Foto Utama Produk</h2>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                            Mendukung Ctrl+V
                        </span>
                    </div>

                    {{-- Hidden Inputs for Form Submission --}}
                    <input type="file" id="main-gambar-input" name="gambar" accept="image/*" class="hidden" @change="handleMainFileChange($event)">
                    <input type="hidden" name="gambar_base64" x-model="mainImageBase64">
                    <input type="hidden" name="hapus_gambar" :value="hapusGambarUtama ? '1' : '0'">

                    {{-- Image Preview If Available --}}
                    <template x-if="mainImagePreview && !hapusGambarUtama">
                        <div class="space-y-3">
                            <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 aspect-[16/10] relative group shadow-xs">
                                <img :src="resolveImg(mainImagePreview)" alt="Foto Utama" class="h-full w-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-wrap items-center justify-center gap-2 p-2">
                                    <button type="button" @click="pasteMainClipboard()"
                                            class="px-3 py-1.5 rounded-xl bg-purple-600 text-white text-xs font-bold shadow-md hover:bg-purple-500 transition">
                                        📋 Paste Screenshot
                                    </button>
                                    <label for="main-gambar-input" class="cursor-pointer px-3 py-1.5 rounded-xl bg-white text-gray-800 text-xs font-bold shadow-md hover:bg-gray-100 transition">
                                        📁 Ganti File
                                    </label>
                                    <button type="button" @click="removeMainImage()"
                                            class="px-3 py-1.5 rounded-xl bg-red-600 text-white text-xs font-bold shadow-md hover:bg-red-500 transition">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <span x-show="mainImageBase64" class="text-purple-600 dark:text-purple-400 font-bold flex items-center gap-1 text-[11px]">
                                    <span>📋</span> Screenshot baru siap disimpan
                                </span>
                                <span x-show="!mainImageBase64" class="text-gray-400 text-[11px]">
                                    Foto tersimpan saat ini
                                </span>
                                <button type="button" @click="removeMainImage()" class="text-red-500 hover:text-red-600 font-bold text-xs">
                                    Hapus Foto
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Interactive Paste & Drop Zone --}}
                    <div tabindex="0"
                         @paste.stop="handleMainPaste($event)"
                         @dragover.prevent="isDraggingMain = true"
                         @dragleave.prevent="isDraggingMain = false"
                         @drop.prevent="handleMainDrop($event)"
                         :class="isDraggingMain ? 'border-brand-500 bg-brand-50/50 dark:bg-brand-950/20 ring-2 ring-brand-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/30'"
                         class="rounded-2xl border-2 border-dashed p-4 text-center cursor-pointer transition focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20">

                        <div class="flex flex-col items-center justify-center gap-2">
                            <div class="w-10 h-10 rounded-2xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 flex items-center justify-center text-xl shadow-xs">
                                📋
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                    Klik di sini lalu tekan <kbd class="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 font-mono text-[11px] font-bold">Ctrl + V</kbd>
                                </p>
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    Untuk langsung menempel gambar screenshot dari clipboard
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-center gap-2 mt-1">
                                <button type="button" @click.stop="pasteMainClipboard()"
                                        class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                                    <span>📋</span>
                                    <span>Paste Screenshot</span>
                                </button>
                                <span class="text-xs text-gray-400">atau</span>
                                <label for="main-gambar-input" @click.stop
                                       class="cursor-pointer px-3 py-1.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-xs flex items-center gap-1.5">
                                    <span>📁</span>
                                    <span>Pilih File</span>
                                </label>
                            </div>
                            <p class="text-[10px] text-gray-400">Mendukung file JPG, PNG, WebP (maks. 4MB) atau drag & drop</p>
                        </div>
                    </div>
                </div>

                {{-- Card: Status & Publikasi --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-lg">⚙️</span>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Status & Publikasi</h2>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">
                            Urutan Tampil
                        </label>
                        <input type="number" name="urutan" value="{{ old('urutan', $layanan->urutan ?? 0) }}"
                               min="0" max="127"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                        <p class="text-[11px] text-gray-400 mt-1">Angka lebih kecil tampil lebih awal pada katalog.</p>
                    </div>

                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" name="aktif" id="aktif" value="1"
                                   {{ old('aktif', $layanan->aktif ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-brand-600 border-gray-300 focus:ring-brand-500">
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Aktif / Tampilkan di Publik</span>
                        </label>
                        <p class="text-[11px] text-gray-400 ml-6 mt-0.5">Jika nonaktif, produk disembunyikan sepenuhnya dari publik.</p>
                    </div>

                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        <button type="submit" form="product-form"
                                class="w-full py-3 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-extrabold shadow-sm transition">
                            💾 {{ $layanan->exists ? 'Simpan Perubahan' : 'Tambah Layanan' }}
                        </button>
                        <a href="{{ route('admin.layanan-bisnis.index') }}"
                           class="block w-full py-2.5 rounded-2xl border border-gray-200 dark:border-gray-700 text-center text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

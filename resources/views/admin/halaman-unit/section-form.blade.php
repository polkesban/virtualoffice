@extends('admin.layouts.admin')
@section('title', isset($section->id) ? 'Edit Blok' : 'Tambah Blok')
@section('content')
@php
    $contextType = $contextType ?? 'unit'; // 'unit' | 'statis'
    $editing     = isset($section->id);
    $builderTypes = \App\Models\PageSection::builderTypes($editing ? ($section->tipe ?? null) : null);
    $initTipe    = old('tipe', $section->tipe ?? 'teks');
    $rawConfig   = old('konfigurasi');
    $initConfig  = $rawConfig
        ? (is_array($rawConfig) ? $rawConfig : json_decode($rawConfig, true))
        : ($section->konfigurasi ?? \App\Models\PageSection::defaultConfig($initTipe));

    // Build form action & back link based on context
    if ($contextType === 'statis') {
        $backUrl     = route('admin.halaman-statis.edit', $page->kunci);
        $storeAction = route('admin.halaman-statis.section.store', $page->kunci);
        $updateAction = $editing ? route('admin.halaman-statis.section.update', $section) : '';
        $backLabel   = $page->judul;
    } else {
        $backUrl     = route('admin.halaman-unit.manage', $page);
        $storeAction = route('admin.halaman-unit.section.store', $page);
        $updateAction = $editing ? route('admin.halaman-unit.section.update', $section) : '';
        $backLabel   = $page->judul;
    }

    $formAccessEvents = method_exists($page, 'events')
        ? $page->events()->orderByDesc('mulai_at')->get(['id', 'judul', 'mulai_at'])
        : collect();
@endphp
<div class="w-full max-w-7xl mx-auto space-y-6 pb-12"
    x-data="blockBuilder('{{ $initTipe }}', JSON.parse($el.dataset.cfg), @js($formAccessEvents))"
    x-init="init()"
     data-cfg="{{ json_encode($initConfig) }}">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200 dark:border-gray-800">
        <div>
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:underline mb-1">
                <span>←</span> Kembali ke {{ $backLabel }}
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                    {{ $editing ? 'Edit Blok' : 'Tambah Blok Konten' }}
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300 border border-brand-200 dark:border-brand-800 uppercase tracking-wider">
                    <span x-text="tipe"></span>
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Tampilan editor responsif layar lebar (full-width) untuk kenyamanan editing konten seksi/blok.
            </p>
        </div>
        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ $backUrl }}"
               class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                Batal
            </a>
            <button type="button" @click="$refs.formSection.requestSubmit()"
                    class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-sm shadow-brand-500/20 hover:shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                <span>💾</span>
                <span>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Blok' }}</span>
            </button>
        </div>
    </div>

        <form x-ref="formSection"
            method="POST"
            action="{{ $editing ? $updateAction : $storeAction }}"
            enctype="multipart/form-data"
            class="space-y-6">
        @csrf
        @if($editing) @method('PUT') @endif

        {{-- Hidden serialized JSON fields --}}
        <input type="hidden" name="tipe"         :value="tipe">
        <input type="hidden" name="konfigurasi"  :value="JSON.stringify(cfg)">

        @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        {{-- ① Block type picker --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">① Pilih Jenis Blok</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-2.5">
                @foreach($builderTypes as $key => $meta)
                <button type="button" @click="setTipe('{{ $key }}')"
                        :class="tipe === '{{ $key }}'
                            ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300 shadow-sm ring-2 ring-brand-500/20'
                            : 'border-gray-200 dark:border-gray-700 hover:border-brand-300 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400'"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border-2 text-center transition-all cursor-pointer text-xs font-medium hover:scale-[1.02] active:scale-[0.98]">
                    <span class="text-2xl">{{ $meta['ikon'] }}</span>
                    <span class="leading-tight">{{ $meta['label'] }}</span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- ② Section heading (optional) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">② Judul Seksi (Opsional)</p>
            <input type="text" name="judul" value="{{ old('judul', $section->judul) }}" x-ref="sectionTitle"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                   placeholder="Judul yang muncul di atas blok ini, e.g. Program Unggulan Kami">
        </div>

        <div x-show="supportsAiDraft()" x-cloak
             class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">③ Draft AI</p>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white mt-1" x-text="aiDraftTitle()"></h2>
                    <p class="text-xs text-gray-400 mt-1" x-text="aiDraftDescription()"></p>
                </div>
                <span class="inline-flex items-center rounded-full bg-brand-50 dark:bg-brand-950 px-3 py-1 text-[11px] font-bold text-brand-700 dark:text-brand-300">
                    AI Builder
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Topik / Subjek <span class="text-red-500">*</span></label>
                    <input type="text" x-model="aiDraft.acara"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                           :placeholder="aiDraftSubjectPlaceholder()">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Tempat / Unit (opsional)</label>
                    <input type="text" x-model="aiDraft.tempat"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                           placeholder="mis: Poltekkes Bandung">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal / Periode (opsional)</label>
                    <input type="text" x-model="aiDraft.tanggal"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                           placeholder="mis: Tahun Akademik 2026/2027">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Arahan Tambahan</label>
                    <input type="text" x-model="aiDraft.catatan"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                           placeholder="fokuskan pada layanan unggulan, bahasa formal, dll.">
                </div>
            </div>

            <div class="flex items-start gap-4">
                <label class="flex flex-col items-center justify-center w-36 h-24 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-brand-400 dark:hover:border-brand-600 cursor-pointer bg-gray-50 dark:bg-gray-800/50 transition-colors overflow-hidden relative"
                       :class="aiDraft.mediaPreview ? 'border-solid border-brand-300 dark:border-brand-700 p-0' : ''">
                    <template x-if="aiDraft.mediaPreview">
                        <img :src="aiDraft.mediaPreview" class="w-full h-full object-cover rounded-xl">
                    </template>
                    <template x-if="!aiDraft.mediaPreview">
                        <div class="flex flex-col items-center text-gray-400 dark:text-gray-500 text-xs text-center px-2">
                            <span class="text-2xl mb-1">🖼️</span>
                            <span>Foto opsional</span>
                            <span class="mt-0.5 text-[10px] text-gray-300">JPG, PNG, WebP, GIF</span>
                        </div>
                    </template>
                          <input type="file" name="ai_draft_media" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" x-ref="aiMediaInput"
                           @change="pickAiMedia($event)">
                </label>

                <div class="flex-1 space-y-3">
                    <p class="text-xs text-gray-500">Foto dipakai sebagai konteks visual saat generate draft dan ikut ditampilkan di blok publik setelah perubahan disimpan.</p>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button"
                                :disabled="aiDraft.generating || !aiDraft.acara"
                                @click="generateAiDraft(@js($page->judul))"
                                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold px-5 py-2 transition-colors">
                            <span x-show="!aiDraft.generating" x-text="aiDraftButtonLabel()"></span>
                            <span x-show="aiDraft.generating">⏳ Menyusun draft...</span>
                        </button>

                        <button type="button" x-show="aiDraft.mediaPreview"
                                @click="clearAiMedia()"
                                class="text-xs font-medium text-red-500 hover:text-red-600 hover:underline">
                            Hapus foto
                        </button>
                    </div>

                    <p x-show="aiDraft.mediaFile" x-text="aiDraft.mediaFile ? '📎 ' + aiDraft.mediaFile.name : ''"
                       class="text-xs text-brand-600 dark:text-brand-300"></p>
                    <p x-show="aiDraft.error" x-text="aiDraft.error" class="text-xs text-red-500"></p>
                </div>
            </div>
        </div>

        {{-- ③ Type-specific configuration --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">
                <span x-text="supportsAiDraft() ? '④ Konfigurasi' : '③ Konfigurasi'"></span>
            </p>

            {{-- HERO --}}
            <div x-show="tipe === 'hero'" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Heading / Judul Besar</label>
                    <input type="text" x-model="cfg.heading" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Selamat Datang di Pusat Pengembangan Pendidikan">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Sub-teks / Deskripsi</label>
                    <textarea x-model="cfg.sub" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Pilih Gradasi Latar</label>
                        <select x-model="cfg.bg_gradient" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="none">Gunakan Warna Solid (Tanpa Gradasi)</option>
                            <option value="blue-cyan">Biru ke Cyan</option>
                            <option value="purple-pink">Ungu ke Pink</option>
                            <option value="indigo-blue">Indigo ke Biru</option>
                            <option value="teal-emerald">Teal ke Emerald</option>
                            <option value="orange-red">Oranye ke Merah</option>
                            <option value="gray-dark">Abu-abu Gelap</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Warna Latar Solid</label>
                        <input type="color" :value="cfg.bg_color || '#1d4ed8'" @change="cfg.bg_color = $event.target.value" class="h-9 w-full rounded-xl border border-gray-200 cursor-pointer" :disabled="cfg.bg_gradient && cfg.bg_gradient !== 'none'" :class="{'opacity-50': cfg.bg_gradient && cfg.bg_gradient !== 'none'}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">URL Gambar Latar (opsional)</label>
                        <input type="url" x-model="cfg.bg_image" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Transparansi Overlay (%)</label>
                        <input type="range" x-model="cfg.overlay_opacity" min="0" max="100" class="w-full">
                        <p class="text-xs text-gray-400 mt-1" x-text="(cfg.overlay_opacity || 60) + '%'"></p>
                    </div>
                </div>
            </div>

            {{-- TEKS --}}
            <div x-show="tipe === 'teks'" class="space-y-3">
                <label class="block text-xs font-bold text-gray-600 mb-1">Konten (HTML didukung)</label>
                <textarea id="section-teks-konten" x-model="cfg.konten" rows="8" data-rich-editor data-rich-editor-profile="full" data-rich-editor-height="360" data-rich-editor-init-hidden="true" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="<p>Konten Anda di sini...</p>&#10;&#10;Anda juga bisa menulis teks biasa tanpa HTML."></textarea>
                <p class="text-xs text-gray-400">Tag HTML yang diizinkan: &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;, &lt;h2&gt;, &lt;h3&gt;, &lt;br&gt;, &lt;table&gt;</p>
            </div>

            {{-- STATISTIK --}}
            <div x-show="tipe === 'statistik'" class="space-y-3">
                <p class="text-xs text-gray-500 mb-2">Tambahkan angka/fakta penting unit Anda</p>
                <template x-for="(item, idx) in cfg.items" :key="idx">
                    <div class="flex gap-2 items-center border border-gray-100 dark:border-gray-700 rounded-xl p-3">
                        <input type="text" x-model="item.ikon" class="w-12 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-center text-lg" placeholder="📊">
                        <input type="text" x-model="item.nilai" class="w-20 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm font-bold" placeholder="150">
                        <input type="text" x-model="item.label" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm" placeholder="Peserta Pelatihan">
                        <select x-model="item.warna" class="w-24 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs">
                            <option value="blue">Biru</option>
                            <option value="green">Hijau</option>
                            <option value="purple">Ungu</option>
                            <option value="red">Merah</option>
                            <option value="orange">Oranye</option>
                            <option value="teal">Teal</option>
                        </select>
                        <button type="button" @click="cfg.items.splice(idx,1)" class="text-red-400 hover:text-red-600 text-xs px-2 py-1 rounded-lg hover:bg-red-50">✕</button>
                    </div>
                </template>
                <button type="button" @click="cfg.items.push({nilai:'0',label:'Label',ikon:'📊',warna:'blue'})"
                        class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">
                    + Tambah Angka
                </button>
            </div>

            {{-- AGENDA --}}
            <div x-show="tipe === 'event_unit'" class="space-y-4">
                <div class="flex items-start justify-between gap-4 rounded-2xl border border-brand-100 bg-brand-50/70 px-4 py-3 text-sm text-brand-900 dark:border-brand-900/40 dark:bg-brand-900/10 dark:text-brand-100">
                    <div>
                        <p class="font-semibold">Blok ini mengambil data dari modul Event Unit.</p>
                        <p class="mt-1 text-xs text-brand-700 dark:text-brand-200/80">Buat dan kelola event, registrasi, materi, tes, evaluasi, serta sertifikat dari modul event unit untuk halaman ini.</p>
                    </div>
                    <a href="{{ route('admin.unit-events.index', $page) }}"
                       class="shrink-0 rounded-xl bg-brand-600 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-brand-500">
                        Kelola Event Unit
                    </a>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Maks. Tampil</label>
                        <input type="number" x-model="cfg.limit" min="1" max="24" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="cfg.tampilkan_lampau" class="rounded">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Tampilkan event lampau</span>
                        </label>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="cfg.featured_only" class="rounded">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Hanya event unggulan</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- AGENDA --}}
            <div x-show="tipe === 'agenda'" class="space-y-4">
                <p class="text-xs text-gray-500">Menampilkan data dari modul Agenda & Kalender</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Maks. Tampil</label>
                        <input type="number" x-model="cfg.limit" min="1" max="20" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="cfg.tampil_lampau" class="rounded">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Tampilkan agenda lampau</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- PELATIHAN --}}
            <div x-show="tipe === 'pelatihan'" class="space-y-4">
                <p class="text-xs text-gray-500">Menampilkan data dari modul Program Pelatihan</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Maks. Tampil</label>
                        <input type="number" x-model="cfg.limit" min="1" max="20" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Filter Jenis (kosong = semua)</label>
                        <select x-model="cfg.jenis" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="">Semua Jenis</option>
                            @foreach(\App\Models\Pelatihan::$jenisLabels as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- PUBLIKASI --}}
            <div x-show="tipe === 'publikasi'" class="space-y-4">
                <p class="text-xs text-gray-500">Menampilkan data dari modul Publikasi & Penelitian</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Maks. Tampil</label>
                        <input type="number" x-model="cfg.limit" min="1" max="20" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Filter Jenis (kosong = semua)</label>
                        <select x-model="cfg.jenis" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="">Semua Jenis</option>
                            @foreach(\App\Models\Publikasi::$jenisLabels as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- DOKUMEN MUTU --}}
            <div x-show="tipe === 'dokumen'" class="space-y-4">
                <p class="text-xs text-gray-500">Menampilkan data dari modul Dokumen Mutu</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Maks. Tampil</label>
                        <input type="number" x-model="cfg.limit" min="1" max="30" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Filter Jenis</label>
                        <select x-model="cfg.jenis" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="">Semua Jenis</option>
                            @foreach(\App\Models\DokumenMutu::$jenisLabels as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- KOLEKSI PERPUS --}}
            <div x-show="tipe === 'koleksi'" class="space-y-4">
                <p class="text-xs text-gray-500">Menampilkan data dari modul Koleksi Perpustakaan</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Maks. Tampil</label>
                        <input type="number" x-model="cfg.limit" min="1" max="20" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Filter Kategori</label>
                        <select x-model="cfg.kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="">Semua Kategori</option>
                            @foreach(\App\Models\KoleksiPerpus::$kategoriLabels as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- FASILITAS ASRAMA --}}
            <div x-show="tipe === 'fasilitas'" class="space-y-4">
                <p class="text-xs text-gray-500">Menampilkan data dari modul Fasilitas Asrama</p>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Filter Jenis</label>
                    <select x-model="cfg.jenis" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <option value="">Semua (Putra, Putri, Campuran)</option>
                        @foreach(\App\Models\FasilitasAsrama::$jenisLabels as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- LAYANAN (TI / Lab / Bisnis) --}}
            <div x-show="tipe === 'layanan'" class="space-y-4">
                <p class="text-xs text-gray-500">Menampilkan data dari modul layanan yang dipilih</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Sumber Layanan</label>
                        <select x-model="cfg.sumber" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="ti">Layanan TI</option>
                            <option value="lab">Layanan Lab</option>
                            <option value="bisnis">Layanan Bisnis</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Maks. Tampil</label>
                        <input type="number" x-model="cfg.limit" min="1" max="30" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            {{-- UNDUHAN --}}
            <div x-show="tipe === 'unduhan'" class="space-y-3">
                <p class="text-xs text-gray-500 mb-2">Daftar file/dokumen yang bisa diunduh pengunjung</p>
                <template x-for="(item, idx) in cfg.items" :key="idx">
                    <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-3 space-y-2">
                        <div class="flex gap-2">
                            <input type="text" x-model="item.nama" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm" placeholder="Nama dokumen">
                            <input type="text" x-model="item.tipe" class="w-16 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs text-center" placeholder="PDF">
                            <input type="text" x-model="item.ukuran" class="w-20 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs" placeholder="1.2 MB">
                            <button type="button" @click="cfg.items.splice(idx,1)" class="text-red-400 hover:text-red-600 text-xs px-2 rounded-lg hover:bg-red-50">✕</button>
                        </div>
                        <input type="url" x-model="item.url" class="w-full rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs font-mono" placeholder="https://drive.google.com/...">
                        <input type="text" x-model="item.deskripsi" class="w-full rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs text-gray-500" placeholder="Deskripsi singkat (opsional)">
                    </div>
                </template>
                <button type="button" @click="cfg.items.push({nama:'',deskripsi:'',url:'',tipe:'PDF',ukuran:''})"
                        class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">
                    + Tambah File
                </button>
            </div>

            {{-- GOOGLE DRIVE --}}
            <div x-show="tipe === 'google_drive'" class="space-y-4">
                <div class="rounded-2xl border border-brand-100 bg-brand-50/70 px-4 py-3 text-sm text-brand-900 dark:border-brand-900/40 dark:bg-brand-900/10 dark:text-brand-100">
                    <p class="font-semibold">Blok ini menampilkan isi folder atau preview file Google Drive.</p>
                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-200/80">Mode embed cocok untuk preview folder/file langsung. Mode kartu cocok untuk daftar file Google Drive yang ingin diatur manual agar judul, urutan, dan tampilannya stabil.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">URL Google Drive</label>
                    <input type="url" x-model="cfg.url" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="https://drive.google.com/drive/folders/... atau https://drive.google.com/file/d/.../view">
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Mode Tampilan</label>
                        <select x-model="cfg.display_mode" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="embed">Embed Langsung</option>
                            <option value="cards">Kartu File</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Mode Folder</label>
                        <select x-model="cfg.mode" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" :disabled="cfg.display_mode === 'cards'">
                            <option value="list">List</option>
                            <option value="grid">Grid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Tinggi Preview (px)</label>
                        <input type="number" x-model="cfg.tinggi" min="320" max="1200" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" :disabled="cfg.display_mode === 'cards'">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Judul Tambahan (opsional)</label>
                        <input type="text" x-model="cfg.judul" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Mis: Dokumen Akreditasi 2026">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Deskripsi (opsional)</label>
                    <textarea x-model="cfg.deskripsi" rows="3" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Penjelasan singkat isi folder atau petunjuk untuk pengunjung."></textarea>
                </div>

                <div x-show="cfg.display_mode === 'cards'" class="space-y-3">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-100">
                        <p class="font-semibold">Mode kartu memakai daftar item manual.</p>
                        <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-100/80">Google Drive tidak menyediakan daftar isi folder yang bisa diparse stabil dari server ini tanpa API atau kredensial. Karena itu, item kartu diisi manual tetapi tetap memakai link Google Drive per file/folder.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 space-y-3">
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Import Massal Link Drive</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tempel satu link Google Drive per baris. Format opsional: <span class="font-mono">URL | Nama | Deskripsi | Tipe | Ukuran</span></p>
                            </div>
                            <button type="button"
                                    @click="importDriveCardItems()"
                                    class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-brand-500">
                                Import ke Kartu
                            </button>
                        </div>
                        <textarea x-model="driveImport.text" rows="5" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="https://drive.google.com/file/d/.../view&#10;https://drive.google.com/drive/folders/... | Folder SOP Akreditasi | Kumpulan file SOP | Folder | 12 file"></textarea>
                        <p x-show="driveImport.message"
                           x-text="driveImport.message"
                           :class="driveImport.error ? 'text-red-500 dark:text-red-300' : 'text-emerald-600 dark:text-emerald-300'"
                           class="text-xs font-medium"></p>
                    </div>

                    <template x-for="(item, idx) in cfg.items" :key="'drive-item-'+idx">
                        <div class="rounded-2xl border border-gray-100 dark:border-gray-700 p-4 space-y-3 bg-gray-50 dark:bg-gray-800/40">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200" x-text="item.nama || ('Item Drive ' + (idx + 1))"></p>
                                <button type="button" @click="cfg.items.splice(idx,1)" class="rounded-lg px-2 py-1 text-xs font-bold text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30">Hapus</button>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                <div class="md:col-span-5">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Nama Item</label>
                                    <input type="text" x-model="item.nama" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Mis: Jadwal Seleksi Tahap 2">
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tipe / Badge</label>
                                    <input type="text" x-model="item.tipe" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="PDF, Spreadsheet, Folder, dll.">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Ukuran / Meta</label>
                                    <input type="text" x-model="item.ukuran" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="1.2 MB / 12 file">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">URL Google Drive Item</label>
                                <input type="url" x-model="item.url" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="https://drive.google.com/file/d/... atau https://drive.google.com/drive/folders/...">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Deskripsi Singkat</label>
                                <textarea x-model="item.deskripsi" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Keterangan singkat untuk item ini."></textarea>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="cfg.items.push({ nama: '', deskripsi: '', url: '', tipe: '', ukuran: '' })"
                            class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">
                        + Tambah Item Kartu
                    </button>
                </div>

                <div class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Preview Blok Google Drive</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="drivePreviewSummary()"></p>
                        </div>
                        <a x-show="drivePreviewOpenUrl()" :href="drivePreviewOpenUrl()" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-3 py-2 text-xs font-bold text-white hover:bg-brand-500 transition-colors">
                            Buka Link Drive
                        </a>
                    </div>

                    <div class="p-4">
                        <div x-show="cfg.display_mode === 'embed'" class="space-y-3">
                            <iframe x-show="drivePreviewEmbedUrl()"
                                    :src="drivePreviewEmbedUrl()"
                                    :title="cfg.judul || 'Preview Google Drive'"
                                    :style="`height:${Math.max(320, Math.min(1200, Number(cfg.tinggi || 560)))}px;border:0;`"
                                    class="w-full rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950"
                                    loading="lazy"></iframe>
                            <div x-show="!drivePreviewEmbedUrl()" class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tempel URL file atau folder Google Drive yang valid untuk melihat preview embed sebelum disimpan.
                            </div>
                        </div>

                        <div x-show="cfg.display_mode === 'cards'" class="space-y-3">
                            <div x-show="driveCardPreviewItems().length === 0" class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tambahkan item kartu untuk melihat preview daftar file Google Drive.
                            </div>
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                <template x-for="(item, idx) in driveCardPreviewItems()" :key="'drive-card-preview-'+idx">
                                    <a :href="driveItemOpenUrl(item) || '#'
                                        " target="_blank" rel="noopener noreferrer"
                                       class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 p-4 hover:border-brand-300 hover:shadow-sm transition-all">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-50 text-xl dark:bg-brand-900/30" x-text="driveItemIcon(item)"></div>
                                            <span class="rounded-full bg-white dark:bg-gray-800 px-2 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-gray-500 dark:text-gray-300" x-text="driveItemBadge(item)"></span>
                                        </div>
                                        <p class="mt-3 text-sm font-extrabold text-gray-900 dark:text-white line-clamp-2" x-text="item.nama || 'Item Google Drive'"></p>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-3" x-text="item.deskripsi || 'Tidak ada deskripsi.'"></p>
                                        <div class="mt-3 flex items-center justify-between gap-3 text-xs text-gray-400 dark:text-gray-500">
                                            <span x-text="item.ukuran || 'Google Drive'"></span>
                                            <span class="font-bold text-brand-600 dark:text-brand-400">Buka →</span>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KONTAK --}}
            <div x-show="tipe === 'kontak'" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Alamat</label>
                    <textarea x-model="cfg.alamat" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Gedung A Lantai 2, Poltekkes Bandung"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Telepon / WhatsApp</label>
                        <input type="text" x-model="cfg.telepon" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="022-1234567">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Email</label>
                        <input type="email" x-model="cfg.email" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="unit@polkesban.ac.id">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Jam Operasional</label>
                        <input type="text" x-model="cfg.jam_kerja" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Senin–Jumat, 08.00–16.00 WIB">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Embed Google Maps (src URL)</label>
                        <input type="url" x-model="cfg.maps_embed" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="https://maps.google.com/maps?...&output=embed">
                    </div>
                </div>
            </div>

            {{-- EMBED --}}
            <div x-show="tipe === 'embed'" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">URL (YouTube / Google Forms / Maps embed)</label>
                    <input type="url" x-model="cfg.url" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="https://www.youtube.com/embed/...">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Tinggi (px)</label>
                    <input type="number" x-model="cfg.tinggi" min="200" max="900" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Caption (opsional)</label>
                    <input type="text" x-model="cfg.judul" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                </div>
            </div>

            {{-- TIM --}}
            <div x-show="tipe === 'tim'" class="space-y-3">
                <p class="text-xs text-gray-500 mb-2">Daftar personil / anggota tim unit</p>
                <template x-for="(item, idx) in cfg.items" :key="idx">
                    <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-3 space-y-2">
                        <div class="flex gap-2">
                            <input type="text" x-model="item.nama" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm font-medium" placeholder="Nama lengkap">
                            <input type="text" x-model="item.jabatan" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm" placeholder="Jabatan">
                            <button type="button" @click="cfg.items.splice(idx,1)" class="text-red-400 hover:text-red-600 text-xs px-2 rounded-lg hover:bg-red-50">✕</button>
                        </div>
                        <input type="url" x-model="item.foto" class="w-full rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs font-mono" placeholder="URL foto (opsional)">
                        <textarea x-model="item.bio" rows="2" class="w-full rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs text-gray-500" placeholder="Bio singkat (opsional)"></textarea>
                    </div>
                </template>
                <button type="button" @click="cfg.items.push({nama:'',jabatan:'',foto:'',bio:''})"
                        class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">
                    + Tambah Personil
                </button>
            </div>

            {{-- AI TEKS --}}
            <div x-show="tipe === 'ai_teks'" class="space-y-4">
                <div class="rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 p-4 text-sm text-indigo-700 dark:text-indigo-300 space-y-1">
                    <p class="font-bold">🤖 AI Content Engine — Blok Teks</p>
                    <p class="text-xs">Gunakan panel Draft AI di atas untuk menyusun konten awal, lalu edit hasilnya di bawah sebelum disimpan.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Konten yang Dihasilkan (dapat diedit)</label>
                    <textarea id="section-ai-teks-konten" x-model="cfg.konten" rows="10" data-rich-editor data-rich-editor-profile="full" data-rich-editor-height="380" data-rich-editor-init-hidden="true" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono text-xs leading-relaxed" placeholder="Klik Generate Draft untuk mengisi otomatis..."></textarea>
                </div>
            </div>

            {{-- TABEL --}}
            <div x-show="tipe === 'tabel'" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-2">Header Kolom</label>
                    <div class="flex gap-2 flex-wrap">
                        <template x-for="(h, idx) in cfg.header" :key="'h'+idx">
                            <div class="flex gap-1 items-center">
                                <input type="text" x-model="cfg.header[idx]" class="w-28 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs font-bold" :placeholder="'Kolom '+(idx+1)">
                                <button type="button" @click="cfg.header.splice(idx,1); cfg.baris.forEach(r=>r.splice(idx,1))" class="text-red-400 text-xs hover:text-red-600">✕</button>
                            </div>
                        </template>
                        <button type="button" @click="cfg.header.push('Kolom '+(cfg.header.length+1)); cfg.baris.forEach(r=>r.push(''))"
                                class="px-3 py-1.5 rounded-lg border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500">+ Kolom</button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-2">Baris Data</label>
                    <template x-for="(baris, bidx) in cfg.baris" :key="'b'+bidx">
                        <div class="flex gap-2 items-center mb-1">
                            <template x-for="(col, cidx) in baris" :key="'c'+cidx">
                                <input type="text" x-model="cfg.baris[bidx][cidx]" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs" :placeholder="'Sel '+(bidx+1)+'-'+(cidx+1)">
                            </template>
                            <button type="button" @click="cfg.baris.splice(bidx,1)" class="text-red-400 text-xs hover:text-red-600 px-2">✕</button>
                        </div>
                    </template>
                    <button type="button" @click="cfg.baris.push(cfg.header.map(()=>''))"
                            class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors mt-1">+ Tambah Baris</button>
                </div>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" x-model="cfg.dengan_header" class="rounded"> Baris pertama sebagai header
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" x-model="cfg.bergaris" class="rounded"> Bergaris (striped)
                    </label>
                </div>
            </div>

            {{-- GALERI FOTO --}}
            <div x-show="tipe === 'galeri_foto'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Jumlah Kolom</label>
                        <select x-model="cfg.kolom" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="2">2 Kolom</option>
                            <option value="3">3 Kolom</option>
                            <option value="4">4 Kolom</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-2">
                    <template x-for="(item, idx) in cfg.items" :key="'g'+idx">
                        <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-3 space-y-2">
                            <div class="flex gap-2 items-center">
                                <input type="url" x-model="item.url" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs font-mono" placeholder="URL gambar (https://... atau /storage/...)">
                                <button type="button" @click="cfg.items.splice(idx,1)" class="text-red-400 text-xs hover:text-red-600 px-2">✕</button>
                            </div>
                            <div class="flex gap-2">
                                <input type="text" x-model="item.judul" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs" placeholder="Judul foto (opsional)">
                                <input type="text" x-model="item.caption" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs" placeholder="Caption (opsional)">
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="cfg.items.push({url:'',judul:'',caption:''})"
                            class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">+ Tambah Foto</button>
                </div>
            </div>

            {{-- PRODUK --}}
            <div x-show="tipe === 'produk'" class="space-y-4">
                <p class="text-xs text-gray-500">Tambahkan daftar produk atau layanan berharga dengan kategori, harga, link pesan, dan varian seperti di Unit Bisnis.</p>
                <template x-for="(item, idx) in cfg.items" :key="'produk-'+idx">
                    <div class="border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-2xl p-5 space-y-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300 text-xs font-bold" x-text="idx + 1"></span>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200" x-text="item.nama || ('Produk ' + (idx + 1))"></p>
                            </div>
                            <button type="button" @click="cfg.items.splice(idx,1)" class="text-red-500 hover:text-red-700 text-xs px-2.5 py-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors font-medium">Hapus Produk</button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nama Produk / Layanan</label>
                                <input type="text" x-model="item.nama" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Contoh: Paket Tes TOEFL Institusi">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Kategori</label>
                                <select x-model="item.kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                    @foreach(\App\Models\LayananBisnis::$kategoriLabels as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Deskripsi</label>
                            <textarea x-model="item.deskripsi" rows="3" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Jelaskan manfaat, target peserta, atau detail singkat produk."></textarea>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Harga Dasar</label>
                                <input type="number" x-model="item.harga" min="0" step="1000" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Contoh: 250000">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Satuan Harga</label>
                                <input type="text" x-model="item.satuan_harga" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="per paket / per orang / per item">
                            </div>
                        </div>

                        {{-- Stok & Ketersediaan Produk --}}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 p-3.5 rounded-xl bg-brand-50/50 dark:bg-brand-950/20 border border-brand-100 dark:border-brand-900/40">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">📦 Jumlah Stok Tersedia</label>
                                <input type="number" min="0" x-model="item.stok" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Kosongkan jika unlimited / ready">
                                <p class="text-[10px] text-gray-400 mt-1">Kosongkan jika stok tidak dibatasi. Masukkan angka jika kuota terbatas.</p>
                            </div>
                            <div class="flex flex-col justify-center">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">🔘 Status Ketersediaan</label>
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" x-model="item.is_tersedia" class="sr-only peer" :checked="item.is_tersedia !== false">
                                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                    <span class="ml-2.5 text-xs font-bold" :class="item.is_tersedia !== false ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="item.is_tersedia !== false ? 'Produk Ready / Tersedia' : 'Stok Habis'"></span>
                                </label>
                                <p class="text-[10px] text-gray-400 mt-1">Matikan jika stok produk ini sedang habis.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Foto / URL Gambar (opsional)</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" x-model="item.gambar" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Link Google Drive, file URL, atau upload foto">
                                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 shrink-0 transition-colors" title="Pilih foto dari komputer">
                                        <span>📁</span>
                                        <span>Upload</span>
                                        <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, item, 'gambar')">
                                    </label>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Bisa link Google Drive (pastikan akses: "Siapa saja yang memiliki link") atau upload foto langsung.</p>

                                <template x-if="item.gambar">
                                    <div class="mt-2 flex items-center gap-3 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700">
                                        <img :src="resolveImagePreview(item.gambar)" class="w-14 h-11 object-cover rounded-lg bg-gray-200 dark:bg-gray-700 shrink-0" alt="Preview" x-on:error="$el.classList.add('opacity-40')">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Foto Terpasang</p>
                                            <p class="text-[10px] text-gray-400 truncate" x-text="item.gambar"></p>
                                        </div>
                                        <button type="button" @click="item.gambar = ''" class="text-xs font-semibold text-red-500 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-950/30">Hapus</button>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Link Pemesanan / WhatsApp / Form</label>
                                <input type="text" x-model="item.link_pesan" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="https://wa.me/... atau /layanan-bisnis/pesan">
                                <p class="text-[11px] text-gray-400 mt-1">Bisa link WhatsApp pesanan, form internal (/layanan-bisnis/pesan), atau form eksternal.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Gambar QRIS Pembayaran (opsional)</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" x-model="item.qris_image" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="URL QRIS atau upload gambar QRIS">
                                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 shrink-0 transition-colors" title="Pilih gambar QRIS">
                                        <span>📷</span>
                                        <span>Upload</span>
                                        <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, item, 'qris_image')">
                                    </label>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Menampilkan tombol "Bayar via QRIS" popup pada kartu produk.</p>

                                <template x-if="item.qris_image">
                                    <div class="mt-2 flex items-center gap-3 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700">
                                        <img :src="resolveImagePreview(item.qris_image)" class="w-12 h-12 object-contain rounded-lg bg-white p-1 shrink-0" alt="QRIS Preview" x-on:error="$el.classList.add('opacity-40')">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">QRIS Terpasang</p>
                                            <p class="text-[10px] text-gray-400 truncate" x-text="item.qris_image"></p>
                                        </div>
                                        <button type="button" @click="item.qris_image = ''" class="text-xs font-semibold text-red-500 hover:text-red-700 px-2 py-1 rounded">Hapus</button>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Label QRIS / Keterangan Pembayaran</label>
                                <input type="text" x-model="item.qris_label" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="QRIS Koperasi Pegawai Husada Sejahtera (NMID: ID1026497443527)">
                            </div>
                        </div>

                        <div class="space-y-3 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-600">Jenis / Varian Produk</label>
                                    <p class="mt-1 text-xs text-gray-400">Gunakan untuk ukuran, paket, tipe layanan, kelas, atau opsi harga lainnya.</p>
                                </div>
                                <button type="button"
                                        @click="item.opsi_varian = item.opsi_varian || []; item.opsi_varian.push({ label: '', harga: '', satuan_harga: '', gambar: '', stok: '', tersedia: true })"
                                        class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 hover:bg-brand-100 dark:bg-brand-900/30 dark:text-brand-300">
                                    + Tambah Opsi
                                </button>
                            </div>

                            <template x-if="!item.opsi_varian || item.opsi_varian.length === 0">
                                <div class="rounded-xl bg-gray-50 px-4 py-3 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    Belum ada varian. Kosongkan jika produk hanya memiliki satu opsi harga.
                                </div>
                            </template>

                            <div class="space-y-3">
                                <template x-for="(option, oidx) in (item.opsi_varian || [])" :key="'opsi-'+idx+'-'+oidx">
                                    <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/60 p-4 border border-gray-200/80 dark:border-gray-700/80 space-y-3">
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                            <div class="md:col-span-4">
                                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Label Varian / Menu</label>
                                                <input type="text" x-model="option.label"
                                                       placeholder="Contoh: Paket Reguler / Susu Hangat"
                                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Harga</label>
                                                <input type="number" min="0" step="1000" x-model="option.harga"
                                                       placeholder="15000"
                                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Satuan</label>
                                                <input type="text" x-model="option.satuan_harga"
                                                       placeholder="per paket"
                                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500" title="Kosongkan jika stok unlimited">Stok</label>
                                                <input type="number" min="0" x-model="option.stok"
                                                       placeholder="∞ Ready"
                                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            </div>
                                            <div class="md:col-span-2 flex items-end gap-1.5">
                                                <button type="button" @click="option.tersedia = (option.tersedia === false ? true : false)"
                                                        :class="option.tersedia !== false ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300'"
                                                        class="flex-1 py-2 px-1.5 rounded-xl border text-xs font-bold transition text-center select-none"
                                                        :title="option.tersedia !== false ? 'Klik untuk tandai Habis' : 'Klik untuk tandai Ready'">
                                                    <span x-text="option.tersedia !== false ? '✓ Ready' : '✕ Habis'"></span>
                                                </button>
                                                <button type="button" @click="item.opsi_varian.splice(oidx, 1)"
                                                        class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/30 dark:text-red-300 transition-colors" title="Hapus varian ini">
                                                    🗑️
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Foto Varian --}}
                                        <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1.5">
                                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-300 flex items-center gap-1">
                                                    <span>📷</span>
                                                    <span>Foto / Gambar Varian (Opsional)</span>
                                                </label>
                                                <span class="text-[10px] text-gray-400">Upload foto varian atau masukkan URL gambar</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="text" x-model="option.gambar"
                                                       placeholder="URL foto varian atau klik Upload"
                                                       class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                                <label class="cursor-pointer inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-gray-200/70 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 shrink-0 transition-colors" title="Upload foto varian">
                                                    <span>📁</span>
                                                    <span>Upload</span>
                                                    <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, option, 'gambar')">
                                                </label>
                                                <template x-if="option.gambar">
                                                    <button type="button" @click="option.gambar = ''" class="px-2 py-1 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded">
                                                        Hapus
                                                    </button>
                                                </template>
                                            </div>
                                            <template x-if="option.gambar">
                                                <div class="mt-2 flex items-center gap-2.5 p-1.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 max-w-sm">
                                                    <img :src="resolveImagePreview(option.gambar)" class="w-10 h-10 object-cover rounded-lg bg-gray-100 dark:bg-gray-800 shrink-0" alt="Preview Varian">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-[11px] font-semibold text-gray-700 dark:text-gray-300 truncate" x-text="option.label || 'Foto varian'"></p>
                                                        <p class="text-[9px] text-gray-400 truncate" x-text="option.gambar"></p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button"
                        @click="cfg.items.push({ nama: '', kategori: 'produk', deskripsi: '', harga: '', satuan_harga: '', gambar: '', link_pesan: '', opsi_varian: [] })"
                        class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">
                    + Tambah Produk
                </button>
            </div>

            {{-- KUIS --}}
            <div x-show="tipe === 'kuis'" class="space-y-4">
                <p class="text-xs text-gray-500">Buat soal kuis untuk self-assessment pengunjung</p>
                <template x-for="(soal, sidx) in cfg.pertanyaan" :key="'s'+sidx">
                    <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-4 space-y-3">
                        <div class="flex gap-2 items-start">
                            <span class="text-xs font-bold text-gray-400 mt-2" x-text="'Soal '+(sidx+1)"></span>
                            <textarea x-model="soal.soal" rows="2" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm" placeholder="Tulis pertanyaan..."></textarea>
                            <button type="button" @click="cfg.pertanyaan.splice(sidx,1)" class="text-red-400 text-xs hover:text-red-600 mt-2">✕</button>
                        </div>
                        <div class="space-y-1.5 pl-14">
                            <template x-for="(opsi, oidx) in soal.opsi" :key="'o'+oidx">
                                <div class="flex gap-2 items-center">
                                    <input type="radio" :name="'benar_'+sidx" :value="oidx" :checked="soal.benar===oidx" @change="soal.benar=oidx"
                                           class="text-green-500" title="Tandai sebagai jawaban benar">
                                    <input type="text" x-model="soal.opsi[oidx]" class="flex-1 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs"
                                           :placeholder="'Pilihan '+'ABCD'.charAt(oidx)">
                                    <button type="button" @click="soal.opsi.splice(oidx,1)" class="text-red-400 text-xs hover:text-red-600">✕</button>
                                </div>
                            </template>
                            <button type="button" @click="soal.opsi.push('')"
                                    class="text-xs text-brand-500 hover:underline">+ Tambah Pilihan</button>
                        </div>
                        <div class="pl-14">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Penjelasan Jawaban (opsional)</label>
                            <input type="text" x-model="soal.penjelasan" class="w-full rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs" placeholder="Penjelasan singkat untuk jawaban benar">
                        </div>
                    </div>
                </template>
                <button type="button"
                        @click="cfg.pertanyaan.push({soal:'',opsi:['Pilihan A','Pilihan B','Pilihan C','Pilihan D'],benar:0,penjelasan:''})"
                        class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">
                    + Tambah Soal
                </button>
            </div>

            {{-- POLLING --}}
            <div x-show="tipe === 'polling'" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Pertanyaan Polling</label>
                    <input type="text" x-model="cfg.pertanyaan" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Apa pendapat Anda tentang...?">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-2">Pilihan Jawaban</label>
                    <div class="space-y-2">
                        <template x-for="(opsi, idx) in cfg.opsi" :key="'p'+idx">
                            <div class="flex gap-2 items-center">
                                <input type="text" x-model="cfg.opsi[idx]" class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" :placeholder="'Pilihan '+(idx+1)">
                                <button type="button" @click="cfg.opsi.splice(idx,1)" class="text-red-400 hover:text-red-600 text-xs px-2">✕</button>
                            </div>
                        </template>
                        <button type="button" @click="cfg.opsi.push('')"
                                class="text-xs text-brand-500 hover:underline">+ Tambah Pilihan</button>
                    </div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" x-model="cfg.tampil_hasil" class="rounded"> Tampilkan jumlah suara kepada pengunjung
                </label>
            </div>

            {{-- FORMULIR --}}
            <div x-show="tipe === 'formulir'" class="space-y-4">
                <div class="rounded-2xl border border-dashed border-gray-200 p-4 dark:border-gray-700 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Kop / Logo Header Formulir</label>
                        <div class="grid grid-cols-1 md:grid-cols-[1.2fr_0.8fr] gap-3">
                            <input type="text" x-model="cfg.header_logo_url" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="/storage/logo/formulir.png atau https://...">
                            <input type="text" x-model="cfg.header_logo_alt" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Alt logo">
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <span>Upload Logo</span>
                                <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, cfg, 'header_logo_url', 'header_logo_alt', 'Logo formulir')">
                            </label>
                            <button type="button" x-show="cfg.header_logo_url" @click="clearFormMedia(cfg, 'header_logo_url')" class="font-semibold text-red-500 hover:text-red-600">Hapus logo</button>
                            <span class="text-gray-400">File akan diunggah saat blok disimpan.</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Gambar / Kop Besar Header</label>
                        <div class="grid grid-cols-1 md:grid-cols-[1.2fr_0.8fr] gap-3">
                            <input type="text" x-model="cfg.header_image_url" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="/storage/kop/form-banner.jpg atau https://...">
                            <input type="text" x-model="cfg.header_image_alt" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Alt gambar header">
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <span>Upload Header</span>
                                <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, cfg, 'header_image_url', 'header_image_alt', 'Header formulir')">
                            </label>
                            <button type="button" x-show="cfg.header_image_url" @click="clearFormMedia(cfg, 'header_image_url')" class="font-semibold text-red-500 hover:text-red-600">Hapus gambar</button>
                            <span class="text-gray-400">Cocok untuk kop, banner, atau identitas visual unit.</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Gambar Pengantar Formulir</label>
                        <div class="grid grid-cols-1 md:grid-cols-[1.2fr_0.8fr] gap-3">
                            <input type="text" x-model="cfg.pengantar_image_url" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="/storage/pengantar/form-info.jpg atau https://...">
                            <input type="text" x-model="cfg.pengantar_image_alt" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Alt gambar pengantar">
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <span>Upload Pengantar</span>
                                <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, cfg, 'pengantar_image_url', 'pengantar_image_alt', 'Gambar pengantar formulir')">
                            </label>
                            <button type="button" x-show="cfg.pengantar_image_url" @click="clearFormMedia(cfg, 'pengantar_image_url')" class="font-semibold text-red-500 hover:text-red-600">Hapus gambar</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3" x-show="cfg.header_logo_url || cfg.header_image_url || cfg.pengantar_image_url">
                        <template x-if="cfg.header_logo_url">
                            <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-gray-400">Preview Logo</p>
                                <img :src="cfg.header_logo_url" :alt="cfg.header_logo_alt || 'Logo formulir'" class="h-20 w-full object-contain rounded-xl bg-gray-50 dark:bg-gray-900">
                            </div>
                        </template>
                        <template x-if="cfg.header_image_url">
                            <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-gray-400">Preview Header</p>
                                <img :src="cfg.header_image_url" :alt="cfg.header_image_alt || 'Header formulir'" class="h-20 w-full object-cover rounded-xl bg-gray-50 dark:bg-gray-900">
                            </div>
                        </template>
                        <template x-if="cfg.pengantar_image_url">
                            <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-gray-400">Preview Pengantar</p>
                                <img :src="cfg.pengantar_image_url" :alt="cfg.pengantar_image_alt || 'Gambar pengantar formulir'" class="h-20 w-full object-cover rounded-xl bg-gray-50 dark:bg-gray-900">
                            </div>
                        </template>
                    </div>
                </div>
                <div class="rounded-2xl border border-dashed border-brand-200 bg-brand-50/50 p-4 dark:border-brand-900/40 dark:bg-brand-950/10 space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">Mode Formulir</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ubah blok ini menjadi kuis dengan skor, batas lulus, durasi, dan jadwal akses.</p>
                        </div>
                        <select x-model="cfg.mode" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                            <option value="form">Formulir Biasa</option>
                            <option value="quiz">Kuis / Tes Dinilai</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-show="cfg.mode === 'quiz'">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Nilai Batas Lulus (%)</label>
                            <input type="number" min="0" max="100" step="0.1" x-model.number="cfg.passing_score" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Timer / Durasi (menit)</label>
                            <input type="number" min="0" max="480" step="1" x-model.number="cfg.duration_minutes" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="0 = tanpa timer">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Akses Formulir</label>
                            <select x-model="cfg.access_scope" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                <option value="public">Publik</option>
                                <option value="employee_only">Pegawai Poltekkes Bandung saja</option>
                                <option value="student_only">Mahasiswa saja</option>
                                <option value="employee_or_student">Pegawai atau mahasiswa</option>
                                <option value="event_participants">Peserta event terdaftar</option>
                            </select>
                        </div>
                        <div x-show="cfg.access_scope === 'event_participants'">
                            <label class="block text-xs font-bold text-gray-600 mb-1">Event Akses</label>
                            <select x-model="cfg.access_event_id" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                <option value="">Pilih event</option>
                                <template x-for="event in pageEvents" :key="'access-event-' + event.id">
                                    <option :value="event.id" x-text="event.judul"></option>
                                </template>
                            </select>
                            <p x-show="pageEvents.length === 0" class="mt-2 text-[11px] text-amber-600 dark:text-amber-300">Belum ada event pada halaman ini. Buat event terlebih dahulu jika ingin membatasi peserta terdaftar.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Pesan Saat Akses Ditolak</label>
                            <input type="text" x-model="cfg.access_message" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Kosongkan untuk pesan bawaan sesuai scope.">
                        </div>
                        <div x-show="cfg.access_scope === 'event_participants'">
                            <label class="block text-xs font-bold text-gray-600 mb-1">Label Kode Registrasi</label>
                            <input type="text" x-model="cfg.access_registration_code_label" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Kode registrasi event">
                        </div>
                    </div>

                    <div x-show="cfg.mode === 'quiz'" class="space-y-3 rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/10">
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" x-model="cfg.show_rationales_after_submit" class="rounded"> Tampilkan rasional jawaban setelah submit
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" x-model="cfg.proctoring_enabled" class="rounded"> Aktifkan proctoring
                            </label>
                        </div>

                        <div x-show="cfg.proctoring_enabled" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" x-model="cfg.proctoring_require_fullscreen" class="rounded"> Wajib fullscreen
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" x-model="cfg.proctoring_capture_selfie" class="rounded"> Minta selfie verifikasi
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" x-model="cfg.proctoring_block_copy_paste" class="rounded"> Blokir copy / paste
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" x-model="cfg.proctoring_block_context_menu" class="rounded"> Blokir klik kanan
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" x-model="cfg.proctoring_auto_submit" class="rounded"> Auto-submit saat pelanggaran melewati batas
                            </label>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Batas Pelanggaran</label>
                                <input type="number" min="0" step="1" x-model.number="cfg.proctoring_max_violations" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="0 = hanya catat tanpa batas otomatis">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 mb-1">Pesan Proctoring</label>
                                <input type="text" x-model="cfg.proctoring_notice" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Pesan yang ditampilkan sebelum kuis dimulai.">
                            </div>
                            <div x-show="cfg.proctoring_capture_selfie" class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 mb-1">Label Selfie</label>
                                <input type="text" x-model="cfg.proctoring_selfie_label" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Ambil foto verifikasi peserta">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Jadwal Buka Akses</label>
                            <input type="datetime-local" x-model="cfg.opens_at" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Jadwal Tutup Akses</label>
                            <input type="datetime-local" x-model="cfg.closes_at" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Pesan Sebelum Dibuka</label>
                            <input type="text" x-model="cfg.scheduled_message" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Formulir ini belum dibuka.">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Pesan Setelah Ditutup</label>
                            <input type="text" x-model="cfg.closed_message" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Akses formulir saat ini ditutup.">
                        </div>
                        <div x-show="cfg.mode === 'quiz'">
                            <label class="block text-xs font-bold text-gray-600 mb-1">Pesan Saat Waktu Habis</label>
                            <input type="text" x-model="cfg.time_limit_message" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Waktu pengerjaan untuk formulir ini sudah habis.">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Pengantar Formulir</label>
                    <textarea x-model="cfg.pengantar" rows="3" data-rich-editor data-rich-editor-profile="basic" data-rich-editor-height="220" data-rich-editor-init-hidden="true" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Teks pembuka singkat seperti penjelasan survey, target responden, atau petunjuk pengisian."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Email Tujuan (penerima kiriman formulir)</label>
                    <input type="email" x-model="cfg.email_tujuan" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="unit@polkesban.ac.id">
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label class="block text-xs font-bold text-gray-600">Elemen Formulir</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="addFormField('text')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Teks</button>
                            <button type="button" @click="addFormField('textarea')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Paragraf</button>
                            <button type="button" @click="addFormField('radio')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Pilihan Tunggal</button>
                            <button type="button" @click="addFormField('checkbox')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Multi Pilih</button>
                            <button type="button" @click="addFormField('select')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Dropdown</button>
                            <button type="button" @click="addFormField('scale')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Skala</button>
                            <button type="button" @click="addFormField('matrix')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Matrix</button>
                            <button type="button" @click="addFormField('file')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ File</button>
                            <button type="button" @click="addFormField('signature')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Signature</button>
                            <button type="button" @click="addFormField('url')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ URL</button>
                            <button type="button" @click="addFormField('date')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Tanggal</button>
                            <button type="button" @click="addFormField('time')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Waktu</button>
                            <button type="button" @click="addFormField('section')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Bagian</button>
                            <button type="button" @click="addFormField('divider')" class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600">+ Pemisah</button>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(field, idx) in cfg.fields" :key="'f'+idx">
                            <div class="rounded-2xl border border-gray-100 dark:border-gray-700 p-4 space-y-4 bg-gray-50/70 dark:bg-gray-950/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400"><span x-text="formFieldTypeLabel(field.tipe)"></span> <span x-text="idx + 1"></span></p>
                                        <p class="text-[11px] text-gray-500">Key stabil: <span class="font-mono" x-text="field.key || 'otomatis saat simpan'"></span></p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" @click="moveFormField(idx, -1)" :disabled="idx === 0" class="rounded-lg border border-gray-200 px-2 py-1 text-[11px] font-semibold text-gray-500 disabled:cursor-not-allowed disabled:opacity-40 hover:border-brand-300 hover:text-brand-600">↑</button>
                                        <button type="button" @click="moveFormField(idx, 1)" :disabled="idx === cfg.fields.length - 1" class="rounded-lg border border-gray-200 px-2 py-1 text-[11px] font-semibold text-gray-500 disabled:cursor-not-allowed disabled:opacity-40 hover:border-brand-300 hover:text-brand-600">↓</button>
                                        <button type="button" @click="duplicateFormField(idx)" class="rounded-lg border border-gray-200 px-2 py-1 text-[11px] font-semibold text-gray-500 hover:border-brand-300 hover:text-brand-600">Duplikat</button>
                                        <button type="button" @click="cfg.fields.splice(idx,1)" class="text-red-400 text-xs hover:text-red-600 px-2">✕ Hapus</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-[1.2fr_0.7fr] gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Judul Elemen</label>
                                        <input type="text" x-model="field.nama" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="mis: Dari mana Anda mengetahui layanan ini?">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Tipe Jawaban</label>
                                        <select x-model="field.tipe" @change="cfg.fields[idx] = normalizeFormField(field, idx)" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                            <option value="text">Teks Pendek</option>
                                            <option value="email">Email</option>
                                            <option value="url">URL / Tautan</option>
                                            <option value="date">Tanggal</option>
                                            <option value="time">Waktu</option>
                                            <option value="tel">Telepon</option>
                                            <option value="number">Angka</option>
                                            <option value="textarea">Paragraf</option>
                                            <option value="radio">Pilihan Tunggal</option>
                                            <option value="checkbox">Kotak Centang</option>
                                            <option value="select">Dropdown</option>
                                            <option value="scale">Skala Linear</option>
                                            <option value="matrix">Matrix / Likert</option>
                                            <option value="file">Upload File</option>
                                            <option value="signature">Signature Pad</option>
                                            <option value="section">Section / Bagian</option>
                                            <option value="divider">Pemisah Visual</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 md:items-end">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Deskripsi / Petunjuk</label>
                                        <input type="text" x-model="field.deskripsi" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="mis: Pilih semua jawaban yang relevan.">
                                    </div>
                                    <label x-show="!['section', 'divider'].includes(field.tipe)" class="flex items-center gap-2 text-sm text-gray-600">
                                        <input type="checkbox" x-model="field.wajib" class="rounded"> Jawaban wajib
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-[1.2fr_0.8fr] gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Gambar Deskripsi / Petunjuk</label>
                                        <input type="text" x-model="field.deskripsi_media_url" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder="/storage/form/help-image.jpg atau https://...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Alt Gambar Deskripsi</label>
                                        <input type="text" x-model="field.deskripsi_media_alt" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Alt gambar petunjuk">
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <span>Upload Gambar Petunjuk</span>
                                        <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, field, 'deskripsi_media_url', 'deskripsi_media_alt', field.nama || 'Gambar petunjuk')">
                                    </label>
                                    <button type="button" x-show="field.deskripsi_media_url" @click="clearFormMedia(field, 'deskripsi_media_url')" class="font-semibold text-red-500 hover:text-red-600">Hapus gambar</button>
                                </div>
                                <div x-show="field.deskripsi_media_url" class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                    <img :src="field.deskripsi_media_url" :alt="field.deskripsi_media_alt || field.nama || 'Gambar petunjuk'" class="h-28 w-full rounded-xl object-cover bg-gray-50 dark:bg-gray-900">
                                </div>

                                <div class="space-y-3 rounded-2xl border border-dashed border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600">Logika Tampil Bersyarat</label>
                                            <p class="mt-1 text-[11px] leading-5 text-gray-500">Field, section, atau divider bisa ditampilkan hanya jika jawaban tertentu terpenuhi.</p>
                                        </div>
                                        <select x-model="field.visibility_mode" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                            <option value="always">Selalu tampil</option>
                                            <option value="conditional">Tampil bersyarat</option>
                                        </select>
                                    </div>
                                    <div class="space-y-3" x-show="field.visibility_mode === 'conditional'">
                                        <div class="grid grid-cols-1 md:grid-cols-[180px_auto] gap-3 items-center">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-600 mb-1">Cocokkan Rule</label>
                                                <select x-model="field.visibility_match" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                                    <option value="all">Semua kondisi benar</option>
                                                    <option value="any">Salah satu kondisi benar</option>
                                                </select>
                                            </div>
                                            <button type="button" @click="addVisibilityRule(field)" class="justify-self-start rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700 hover:border-brand-300 hover:bg-brand-100 dark:border-brand-900 dark:bg-brand-950/30 dark:text-brand-300">+ Tambah Rule</button>
                                        </div>
                                        <template x-for="(rule, ruleIdx) in field.visibility_rules" :key="field.key + '-rule-' + ruleIdx">
                                            <div class="grid grid-cols-1 gap-2 rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 md:grid-cols-[1.2fr_0.9fr_1fr_auto] md:items-end">
                                                <div>
                                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1">Field Sumber</label>
                                                    <select x-model="rule.source_key" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                                        <option value="">Pilih pertanyaan</option>
                                                        <template x-for="candidate in cfg.fields.filter(candidate => candidate.key !== field.key && !['section', 'divider'].includes(candidate.tipe))" :key="field.key + '-source-' + candidate.key">
                                                            <option :value="candidate.key" x-text="candidate.nama || candidate.key"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1">Operator</label>
                                                    <select x-model="rule.operator" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                                        <option value="equals">Sama dengan</option>
                                                        <option value="not_equals">Tidak sama dengan</option>
                                                        <option value="contains">Mengandung / memilih</option>
                                                        <option value="not_contains">Tidak mengandung</option>
                                                        <option value="filled">Terisi</option>
                                                        <option value="empty">Kosong</option>
                                                        <option value="gt">Lebih besar dari</option>
                                                        <option value="gte">Lebih besar/sama</option>
                                                        <option value="lt">Lebih kecil dari</option>
                                                        <option value="lte">Lebih kecil/sama</option>
                                                    </select>
                                                </div>
                                                <div x-show="!['filled', 'empty'].includes(rule.operator)">
                                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1">Nilai Acuan</label>
                                                    <input type="text" x-model="rule.value" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="mis: ya, website, 4">
                                                </div>
                                                <button type="button" @click="field.visibility_rules.splice(ruleIdx, 1)" class="rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-500 hover:border-red-300 hover:text-red-600">Hapus</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div x-show="cfg.mode === 'quiz' && isScorableFormField(field)" class="space-y-3 rounded-2xl border border-dashed border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/40 dark:bg-amber-950/10">
                                    <div>
                                        <label class="block text-xs font-bold text-amber-800 dark:text-amber-200">Pengaturan Penilaian Kuis</label>
                                        <p class="mt-1 text-[11px] leading-5 text-amber-700/80 dark:text-amber-200/70">Tentukan bobot soal, kunci jawaban, dan nilai opsi. Nilai total peserta akan dihitung otomatis.</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Bobot Soal</label>
                                            <input type="number" min="0" step="0.25" x-model.number="field.question_weight" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                        </div>
                                        <div x-show="['text', 'textarea', 'email', 'url', 'date', 'time', 'number', 'tel'].includes(field.tipe)">
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Kunci Jawaban</label>
                                            <input type="text" x-model="field.answer_key" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Jawaban yang dianggap benar">
                                        </div>
                                        <div x-show="field.tipe === 'scale'">
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Kunci Nilai Skala</label>
                                            <select x-model="field.answer_key" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                                <option value="">Belum ditentukan</option>
                                                <template x-for="scaleValue in scaleRange(field)" :key="field.key + '-answer-' + scaleValue">
                                                    <option :value="scaleValue" x-text="scaleValue"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <label x-show="['text', 'textarea', 'email', 'url', 'date', 'time', 'number', 'tel'].includes(field.tipe)" class="flex items-center gap-2 text-sm text-gray-600 md:col-span-2">
                                            <input type="checkbox" x-model="field.answer_key_case_sensitive" class="rounded"> Cocokkan huruf besar/kecil secara sensitif
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Rasional Saat Jawaban Benar</label>
                                            <textarea x-model="field.correct_rationale" rows="3" data-rich-editor data-rich-editor-profile="basic" data-rich-editor-height="180" data-rich-editor-init-hidden="true" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Penjelasan yang ditampilkan ketika peserta menjawab benar."></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Rasional Saat Jawaban Salah</label>
                                            <textarea x-model="field.incorrect_rationale" rows="3" data-rich-editor data-rich-editor-profile="basic" data-rich-editor-height="180" data-rich-editor-init-hidden="true" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Penjelasan atau umpan balik untuk jawaban salah."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="['text', 'email', 'tel', 'number', 'textarea', 'url'].includes(field.tipe)">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Placeholder</label>
                                    <input type="text" x-model="field.placeholder" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Teks petunjuk input">
                                </div>

                                <div x-show="field.tipe === 'file'" class="space-y-3 rounded-2xl border border-dashed border-gray-200 p-4 dark:border-gray-700">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Format File yang Diizinkan</label>
                                            <input type="text" x-model="field.accept" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono" placeholder=".pdf,.docx,.jpg,.png atau image/*">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Ukuran Maksimum per File (MB)</label>
                                            <input type="number" min="1" max="100" x-model.number="field.max_size_mb" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-[auto_180px] gap-3 md:items-end">
                                        <label class="flex items-center gap-2 text-sm text-gray-600">
                                            <input type="checkbox" x-model="field.multiple" class="rounded"> Izinkan banyak file dalam satu jawaban
                                        </label>
                                        <div x-show="field.multiple">
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Maksimal Jumlah File</label>
                                            <input type="number" min="1" max="20" x-model.number="field.max_files" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                        </div>
                                    </div>
                                    <p class="text-[11px] leading-6 text-gray-500">Field ini cocok untuk CV, surat permohonan, bukti pembayaran, dokumen akreditasi, atau lampiran survei. Anda dapat menggabungkan dokumen dan gambar dalam satu elemen.</p>
                                </div>

                                <div x-show="field.tipe === 'signature'" class="space-y-3 rounded-2xl border border-dashed border-gray-200 p-4 dark:border-gray-700">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Tinggi Canvas (px)</label>
                                            <input type="number" min="120" max="420" x-model.number="field.canvas_height" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Warna Pena</label>
                                            <input type="color" x-model="field.stroke_color" class="h-11 w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-2 py-2">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Warna Latar</label>
                                            <input type="color" x-model="field.background_color" class="h-11 w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-2 py-2">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Batas Ukuran Gambar Signature (MB)</label>
                                        <input type="number" min="1" max="8" x-model.number="field.max_size_mb" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                    </div>
                                    <p class="text-[11px] leading-6 text-gray-500">Tipe ini menghasilkan signature pad langsung di form publik. Respons disimpan sebagai gambar PNG sehingga bisa direkap dan diunduh kembali dari dashboard admin.</p>
                                </div>

                                <div x-show="['radio', 'checkbox', 'select'].includes(field.tipe)" class="space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="block text-xs font-bold text-gray-600">Pilihan Jawaban</label>
                                        <button type="button" @click="addFormOption(field)" class="text-xs font-semibold text-brand-600 hover:underline">+ Tambah Pilihan</button>
                                    </div>
                                    <template x-for="(option, optionIdx) in field.opsi" :key="field.key + '-option-' + optionIdx">
                                        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 space-y-3">
                                            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-center">
                                                <input type="text" x-model="option.label" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" :placeholder="'Pilihan ' + (optionIdx + 1)">
                                                <button type="button" @click="field.opsi.splice(optionIdx, 1)" class="text-red-400 text-xs hover:text-red-600 px-2">✕</button>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-[1.2fr_0.8fr] gap-2 items-center">
                                                <input type="text" x-model="option.media_url" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm font-mono" placeholder="URL gambar opsi (opsional)">
                                                <input type="text" x-model="option.media_alt" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" placeholder="Alt gambar opsi">
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-2 items-center">
                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                                    <span>Upload Gambar Opsi</span>
                                                    <input type="file" accept="image/*" class="hidden" @change="attachFormMedia($event, option, 'media_url', 'media_alt', option.label || ('Pilihan ' + (optionIdx + 1)))">
                                                </label>
                                                <input x-show="cfg.mode === 'quiz'" type="number" min="0" step="0.25" x-model.number="option.score" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" placeholder="Nilai opsi">
                                                <label x-show="cfg.mode === 'quiz'" class="inline-flex items-center gap-2 text-sm text-gray-600">
                                                    <input type="checkbox" x-model="option.is_correct" class="rounded"> Opsi benar
                                                </label>
                                            </div>
                                            <div x-show="cfg.mode === 'quiz'">
                                                <label class="block text-xs font-bold text-gray-600 mb-1">Rasional Opsi</label>
                                                <textarea x-model="option.rationale" rows="2" data-rich-editor data-rich-editor-profile="basic" data-rich-editor-height="160" data-rich-editor-init-hidden="true" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" placeholder="Penjelasan khusus untuk opsi ini, misalnya alasan jawaban benar atau miskonsepsi umum."></textarea>
                                            </div>
                                            <div x-show="option.media_url" class="rounded-xl border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900">
                                                <img :src="option.media_url" :alt="option.media_alt || option.label || 'Gambar opsi'" class="h-24 w-full rounded-lg object-cover bg-white dark:bg-gray-950">
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="field.tipe === 'scale'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Nilai Minimum</label>
                                        <input type="number" min="1" x-model.number="field.min" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Nilai Maksimum</label>
                                        <input type="number" :min="(field.min || 1) + 1" x-model.number="field.max" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Label Minimum</label>
                                        <input type="text" x-model="field.min_label" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Sangat Tidak Setuju">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Label Maksimum</label>
                                        <input type="text" x-model="field.max_label" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Sangat Setuju">
                                    </div>
                                </div>

                                <div x-show="field.tipe === 'matrix'" class="space-y-2">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Nilai Minimum</label>
                                            <input type="number" min="1" x-model.number="field.min" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Nilai Maksimum</label>
                                            <input type="number" :min="(field.min || 1) + 1" x-model.number="field.max" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Label Minimum</label>
                                            <input type="text" x-model="field.min_label" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Sangat Tidak Setuju">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Label Maksimum</label>
                                            <input type="text" x-model="field.max_label" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Sangat Setuju">
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="block text-xs font-bold text-gray-600">Daftar Pernyataan</label>
                                        <button type="button" @click="addFormStatement(field)" class="text-xs font-semibold text-brand-600 hover:underline">+ Tambah Pernyataan</button>
                                    </div>
                                    <template x-for="(statement, statementIdx) in field.pernyataan" :key="field.key + '-statement-' + statementIdx">
                                        <div class="grid grid-cols-1 gap-2 rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 md:grid-cols-[1fr_150px_150px_auto] md:items-center">
                                            <input type="text" x-model="statement.label" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" :placeholder="'Pernyataan ' + (statementIdx + 1)">
                                            <input x-show="cfg.mode === 'quiz'" type="number" min="0" step="0.25" x-model.number="statement.score" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" placeholder="Bobot">
                                            <select x-show="cfg.mode === 'quiz'" x-model="statement.correct_value" class="w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                                <option value="">Kunci row</option>
                                                <template x-for="scaleValue in scaleRange(field)" :key="field.key + '-statement-answer-' + statementIdx + '-' + scaleValue">
                                                    <option :value="scaleValue" x-text="scaleValue"></option>
                                                </template>
                                            </select>
                                            <button type="button" @click="field.pernyataan.splice(statementIdx, 1)" class="text-red-400 text-xs hover:text-red-600 px-2">✕</button>
                                            <textarea x-show="cfg.mode === 'quiz'" x-model="statement.correct_rationale" rows="2" data-rich-editor data-rich-editor-profile="basic" data-rich-editor-height="160" data-rich-editor-init-hidden="true" class="md:col-span-2 w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" placeholder="Rasional saat row dijawab benar"></textarea>
                                            <textarea x-show="cfg.mode === 'quiz'" x-model="statement.incorrect_rationale" rows="2" data-rich-editor data-rich-editor-profile="basic" data-rich-editor-height="160" data-rich-editor-init-hidden="true" class="md:col-span-2 w-full rounded-xl border border-gray-200 bg-white dark:bg-gray-900 px-3 py-2 text-sm" placeholder="Rasional saat row dijawab salah"></textarea>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="['section', 'divider'].includes(field.tipe)" class="rounded-2xl border border-brand-100 bg-brand-50/60 px-4 py-3 text-xs leading-6 text-brand-900 dark:border-brand-900/40 dark:bg-brand-900/10 dark:text-brand-100">
                                    Elemen ini hanya mengatur alur dan tampilan formulir. Ia tidak dihitung sebagai pertanyaan dan tidak menyimpan jawaban responden.
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="addFormField('text')"
                                class="w-full py-2 rounded-xl border-2 border-dashed border-gray-200 text-xs text-gray-400 hover:border-brand-300 hover:text-brand-500 transition-colors">+ Tambah Elemen</button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Pesan Sukses (setelah kirim)</label>
                    <input type="text" x-model="cfg.pesan_sukses" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Terima kasih, pesan Anda telah diterima.">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Label Tombol Submit</label>
                    <input type="text" x-model="cfg.judul_submit" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Kirim">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Batas Pengiriman Respons</label>
                        <select x-model="cfg.response_limit" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="none">Tidak dibatasi</option>
                            <option value="session">1 kali per sesi browser</option>
                            <option value="email">1 kali per email</option>
                        </select>
                        <p class="mt-1 text-[11px] text-gray-500">Mode email akan memeriksa field bertipe email pertama pada formulir ini.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Pesan Saat Respons Ditolak</label>
                        <input type="text" x-model="cfg.limit_message" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Formulir ini hanya dapat dikirim satu kali.">
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold shadow-md shadow-brand-500/20 hover:shadow-lg transition-all cursor-pointer flex items-center gap-2">
                    <span>💾</span>
                    <span>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Blok' }}</span>
                </button>
                <a href="{{ $backUrl }}"
                   class="px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-semibold text-gray-700 dark:text-gray-300 transition-colors">Batal</a>
            </div>
            <a href="#admin-content" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hidden sm:inline-block">
                ↑ Kembali ke atas
            </a>
        </div>
    </form>
</div>

<script>
function blockBuilder(initTipe, initConfig, pageEvents = []) {
    const defaults = {
        hero:        { heading: '', sub: '', bg_color: '#1d4ed8', bg_image: '', overlay_opacity: 60 },
        teks:        { konten: '' },
        ai_teks:     { konten: '', gambar_url: '', gambar_alt: '', _prompt_acara: '', _prompt_tempat: '', _prompt_tanggal: '', _prompt_catatan: '' },
        statistik:   { items: [{ nilai: '0', label: 'Label', ikon: '📊', warna: 'blue' }] },
        tabel:       { header: ['Kolom 1', 'Kolom 2', 'Kolom 3'], baris: [['', '', '']], bergaris: true, dengan_header: true },
        galeri_foto: { items: [{ url: '', judul: '', caption: '' }], kolom: 3 },
        produk:      { items: [{ nama: '', kategori: 'produk', deskripsi: '', harga: '', satuan_harga: '', gambar: '', link_pesan: '', opsi_varian: [] }] },
        kuis:        { pertanyaan: [{ soal: '', opsi: ['Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D'], benar: 0, penjelasan: '' }] },
        polling:     { pertanyaan: 'Pertanyaan polling?', opsi: ['Opsi 1', 'Opsi 2', 'Opsi 3'], tampil_hasil: true },
        formulir:    { mode: 'form', pengantar: '', header_logo_url: '', header_logo_alt: 'Logo formulir', header_image_url: '', header_image_alt: 'Header formulir', pengantar_image_url: '', pengantar_image_alt: 'Gambar pengantar formulir', passing_score: 70, duration_minutes: 0, opens_at: '', closes_at: '', scheduled_message: 'Formulir ini belum dibuka.', closed_message: 'Akses formulir saat ini ditutup.', time_limit_message: 'Waktu pengerjaan untuk formulir ini sudah habis.', show_rationales_after_submit: true, access_scope: 'public', access_event_id: '', access_message: '', access_registration_code_label: 'Kode registrasi event', proctoring_enabled: false, proctoring_require_fullscreen: false, proctoring_block_copy_paste: true, proctoring_block_context_menu: true, proctoring_capture_selfie: false, proctoring_auto_submit: false, proctoring_max_violations: 0, proctoring_notice: 'Mode proctoring aktif. Aktivitas seperti pindah tab, keluar fullscreen, dan percobaan copy atau paste akan dicatat.', proctoring_selfie_label: 'Ambil foto verifikasi peserta', fields: [{ key: 'nama_lengkap', nama: 'Nama Lengkap', tipe: 'text', wajib: true, deskripsi: '', deskripsi_media_url: '', deskripsi_media_alt: '', visibility_mode: 'always', visibility_match: 'all', visibility_rules: [], question_weight: 1, answer_key: '', answer_key_case_sensitive: false, correct_rationale: '', incorrect_rationale: '', placeholder: '', opsi: [], min: 1, max: 4, min_label: 'Sangat Tidak Setuju', max_label: 'Sangat Setuju', pernyataan: [] }, { key: 'email', nama: 'Email', tipe: 'email', wajib: true, deskripsi: '', deskripsi_media_url: '', deskripsi_media_alt: '', visibility_mode: 'always', visibility_match: 'all', visibility_rules: [], question_weight: 1, answer_key: '', answer_key_case_sensitive: false, correct_rationale: '', incorrect_rationale: '', placeholder: '', opsi: [], min: 1, max: 4, min_label: 'Sangat Tidak Setuju', max_label: 'Sangat Setuju', pernyataan: [] }, { key: 'pesan', nama: 'Pesan', tipe: 'textarea', wajib: true, deskripsi: '', deskripsi_media_url: '', deskripsi_media_alt: '', visibility_mode: 'always', visibility_match: 'all', visibility_rules: [], question_weight: 1, answer_key: '', answer_key_case_sensitive: false, correct_rationale: '', incorrect_rationale: '', placeholder: '', opsi: [], min: 1, max: 4, min_label: 'Sangat Tidak Setuju', max_label: 'Sangat Setuju', pernyataan: [] }], email_tujuan: '', pesan_sukses: 'Terima kasih!', judul_submit: 'Kirim', response_limit: 'none', limit_message: 'Formulir ini hanya dapat dikirim satu kali.' },
        event_unit:  { limit: 3, tampilkan_lampau: false, featured_only: false },
        agenda:      { limit: 5, tampil_lampau: false },
        pelatihan:   { limit: 6, jenis: '' },
        publikasi:   { limit: 5, jenis: '' },
        dokumen:     { limit: 8, jenis: '' },
        koleksi:     { limit: 6, kategori: '' },
        fasilitas:   { jenis: '' },
        layanan:     { sumber: 'ti', limit: 10 },
        unduhan:     { items: [{ nama: '', deskripsi: '', url: '', tipe: 'PDF', ukuran: '' }] },
        google_drive:{ display_mode: 'embed', url: '', mode: 'list', tinggi: 560, judul: '', deskripsi: '', items: [] },
        kontak:      { alamat: '', telepon: '', email: '', jam_kerja: '', maps_embed: '' },
        embed:       { url: '', tinggi: 450, judul: '' },
        tim:         { items: [{ nama: '', jabatan: '', foto: '', bio: '' }] },
    };

    // Build per-type configs. If editing and tipe matches, merge saved config.
    const configs = {};
    for (const [key, def] of Object.entries(defaults)) {
        configs[key] = (initTipe === key && initConfig)
            ? Object.assign({}, def, initConfig)
            : Object.assign({}, def);
    }

    const initialAiDraft = {
        acara: initTipe === 'ai_teks' ? (initConfig?._prompt_acara ?? '') : '',
        tempat: initTipe === 'ai_teks' ? (initConfig?._prompt_tempat ?? '') : '',
        tanggal: initTipe === 'ai_teks' ? (initConfig?._prompt_tanggal ?? '') : '',
        catatan: initTipe === 'ai_teks' ? (initConfig?._prompt_catatan ?? '') : '',
        gambarUrl: initTipe === 'ai_teks' ? (initConfig?.gambar_url ?? '') : '',
    };

    return {
        tipe: initTipe,
        configs,
        pageEvents: Array.isArray(pageEvents) ? pageEvents : [],
        aiDraft: {
            ...initialAiDraft,
            mediaFile: null,
            mediaPreview: initialAiDraft.gambarUrl,
            generating: false,
            error: '',
        },
        driveImport: {
            text: '',
            message: '',
            error: false,
        },
        get cfg() { return this.configs[this.tipe] ?? {}; },
        async init() {
            this.normalizeFormConfig();
            this.syncAiDraftToConfig();
            await this.ensureRichEditors();
            this.$watch('tipe', async (value) => {
                if (value === 'formulir') {
                    this.normalizeFormConfig();
                }
                if (['teks', 'ai_teks', 'formulir'].includes(value)) {
                    await this.ensureRichEditors();
                }
            });
        },
        async ensureRichEditors() {
            if (!['teks', 'ai_teks', 'formulir'].includes(this.tipe) || !window.PolkesbanRichText?.init) {
                return;
            }

            await new Promise((resolve) => this.$nextTick(resolve));
            await new Promise((resolve) => requestAnimationFrame(() => resolve()));
            await window.PolkesbanRichText.init(this.$root);
        },
        supportsAiDraft() {
            return ['hero', 'teks', 'ai_teks', 'statistik', 'tabel', 'tim', 'formulir'].includes(this.tipe);
        },
        aiDraftTitle() {
            return {
                hero: 'Generate copy hero sesuai tema halaman',
                teks: 'Generate draft HTML untuk blok teks',
                ai_teks: 'Generate draft HTML untuk blok AI teks',
                statistik: 'Generate indikator statistik yang siap pakai',
                tabel: 'Generate struktur tabel informatif',
                tim: 'Generate susunan tim atau personil',
                formulir: 'Generate field formulir yang relevan',
            }[this.tipe] || 'Generate draft awal sesuai tema halaman';
        },
        aiDraftDescription() {
            return {
                hero: 'AI akan mengisi heading dan subjudul hero yang tetap bisa Anda ubah manual.',
                teks: 'AI akan menghasilkan draft HTML rapi untuk blok teks biasa.',
                ai_teks: 'AI akan menghasilkan draft HTML rapi untuk blok AI teks.',
                statistik: 'AI akan menyusun 3-6 indikator singkat dengan angka, label, ikon, dan warna.',
                tabel: 'AI akan menyusun header dan baris tabel yang mudah dirapikan ulang.',
                tim: 'AI akan menyiapkan susunan personil berbasis peran yang bisa Anda ganti dengan nama asli.',
                formulir: 'AI akan menyusun field formulir, pesan sukses, dan struktur isian awal.',
            }[this.tipe] || 'AI akan menghasilkan draft yang masih bisa Anda edit lagi.';
        },
        aiDraftSubjectPlaceholder() {
            return {
                hero: 'mis: Pusat karier mahasiswa Poltekkes Bandung',
                teks: 'mis: Profil layanan kemahasiswaan 2026',
                ai_teks: 'mis: Wisuda Semester Genap 2026',
                statistik: 'mis: Ringkasan capaian pusat pelatihan',
                tabel: 'mis: Jadwal layanan laboratorium',
                tim: 'mis: Struktur tim pusat karier',
                formulir: 'mis: Form pendaftaran konsultasi mahasiswa',
            }[this.tipe] || 'mis: Profil layanan kemahasiswaan 2026';
        },
        aiDraftButtonLabel() {
            return {
                statistik: '🚀 Generate Statistik',
                tabel: '🚀 Generate Tabel',
                tim: '🚀 Generate Susunan Tim',
                formulir: '🚀 Generate Formulir',
            }[this.tipe] || '🚀 Generate Draft';
        },
        syncAiDraftToConfig() {
            if (this.tipe !== 'ai_teks') {
                return;
            }

            this.cfg._prompt_acara = this.aiDraft.acara || '';
            this.cfg._prompt_tempat = this.aiDraft.tempat || '';
            this.cfg._prompt_tanggal = this.aiDraft.tanggal || '';
            this.cfg._prompt_catatan = this.aiDraft.catatan || '';
        },
        pickAiMedia(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            this.aiDraft.mediaFile = file;
            if (!String(this.cfg.gambar_alt || '').trim()) {
                this.cfg.gambar_alt = this.aiDraft.acara || this.$refs.sectionTitle?.value?.trim() || file.name;
            }
            const reader = new FileReader();
            reader.onload = e => {
                this.aiDraft.mediaPreview = e.target?.result ?? '';
            };
            reader.readAsDataURL(file);
        },
        clearAiMedia() {
            const hadUnsavedFile = !!this.aiDraft.mediaFile;
            this.aiDraft.mediaFile = null;
            if (hadUnsavedFile && this.cfg.gambar_url) {
                this.aiDraft.mediaPreview = this.cfg.gambar_url;
            } else {
                this.aiDraft.mediaPreview = '';
                this.cfg.gambar_url = '';
                this.cfg.gambar_alt = '';
            }
            if (this.$refs.aiMediaInput) {
                this.$refs.aiMediaInput.value = '';
            }
        },
        attachFormMedia(event, target, urlKey, altKey = null, defaultAlt = '') {
            const file = event.target.files?.[0];
            if (!file || !target) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                target[urlKey] = String(loadEvent.target?.result || '').trim();
                if (altKey && !String(target[altKey] || '').trim()) {
                    target[altKey] = defaultAlt || file.name;
                }
            };
            reader.readAsDataURL(file);
        },
        clearFormMedia(target, urlKey) {
            if (!target) {
                return;
            }

            target[urlKey] = '';
        },
        resolveImagePreview(url) {
            if (!url) return '';
            url = String(url).trim();
            if (url.startsWith('data:image/')) return url;
            if (url.includes('drive.google.com')) {
                const m = url.match(/(?:file\/d\/|open\?id=|uc\?id=)([a-zA-Z0-9_-]+)/);
                if (m && m[1]) {
                    return 'https://lh3.googleusercontent.com/d/' + m[1] + '=w200';
                }
            }
            return url;
        },
        formFieldDefaults(type = 'text') {
            const base = {
                key: '',
                nama: '',
                tipe: type,
                wajib: false,
                deskripsi: '',
                deskripsi_media_url: '',
                deskripsi_media_alt: '',
                visibility_mode: 'always',
                visibility_match: 'all',
                visibility_rules: [],
                question_weight: 1,
                answer_key: '',
                answer_key_case_sensitive: false,
                correct_rationale: '',
                incorrect_rationale: '',
                placeholder: '',
                opsi: ['radio', 'checkbox', 'select'].includes(type) ? [{ value: '', label: 'Pilihan 1', media_url: '', media_alt: 'Pilihan 1', score: 0, is_correct: false, rationale: '' }, { value: '', label: 'Pilihan 2', media_url: '', media_alt: 'Pilihan 2', score: 0, is_correct: false, rationale: '' }] : [],
                min: 1,
                max: 4,
                min_label: 'Sangat Tidak Setuju',
                max_label: 'Sangat Setuju',
                pernyataan: type === 'matrix' ? [{ key: '', label: 'Pernyataan 1', score: 1, correct_value: '', correct_rationale: '', incorrect_rationale: '' }, { key: '', label: 'Pernyataan 2', score: 1, correct_value: '', correct_rationale: '', incorrect_rationale: '' }] : [],
                multiple: false,
                max_files: 1,
                max_size_mb: 10,
                accept: '',
                canvas_height: 220,
                stroke_color: '#111827',
                background_color: '#ffffff',
            };

            const presets = {
                url: { nama: 'Tautan', placeholder: 'https://contoh.id/halaman' },
                date: { nama: 'Tanggal' },
                time: { nama: 'Waktu' },
                file: { nama: 'Upload Berkas', deskripsi: 'Unggah dokumen atau lampiran yang diperlukan.', accept: '.pdf,.doc,.docx,.jpg,.jpeg,.png', max_size_mb: 10 },
                signature: { nama: 'Tanda Tangan', deskripsi: 'Responden akan menggambar tanda tangan langsung di layar.', canvas_height: 220, stroke_color: '#111827', background_color: '#ffffff', max_size_mb: 2 },
                section: { nama: 'Bagian Baru', deskripsi: 'Gunakan untuk memecah formulir menjadi beberapa kelompok pertanyaan.' },
                divider: { nama: 'Pemisah', deskripsi: 'Gunakan untuk memberi jarak visual antar kelompok elemen.' },
            };

            return {
                ...base,
                ...(presets[type] || {}),
            };
        },
        formFieldTypeLabel(type) {
            return {
                text: 'Teks',
                email: 'Email',
                url: 'URL',
                date: 'Tanggal',
                time: 'Waktu',
                tel: 'Telepon',
                number: 'Angka',
                textarea: 'Paragraf',
                radio: 'Pilihan Tunggal',
                checkbox: 'Multi Pilih',
                select: 'Dropdown',
                scale: 'Skala',
                matrix: 'Matrix',
                file: 'Upload File',
                signature: 'Signature',
                section: 'Bagian',
                divider: 'Pemisah',
            }[type] || 'Elemen';
        },
        normalizeFormOption(option, idx) {
            if (typeof option === 'string') {
                return { value: '', label: option, media_url: '', media_alt: option || `Pilihan ${idx + 1}`, score: 0, is_correct: false, rationale: '' };
            }

            return {
                value: option?.value || option?.key || '',
                label: option?.label || option?.nama || `Pilihan ${idx + 1}`,
                media_url: option?.media_url || option?.image_url || option?.icon_url || '',
                media_alt: option?.media_alt || option?.label || option?.nama || `Pilihan ${idx + 1}`,
                score: Number.isFinite(Number(option?.score)) ? Number(option.score) : 0,
                is_correct: !!option?.is_correct || !!option?.benar,
                rationale: (option?.rationale || option?.penjelasan || '').toString(),
            };
        },
        normalizeFormStatement(statement, idx) {
            if (typeof statement === 'string') {
                return { key: '', label: statement, score: 1, correct_value: '', correct_rationale: '', incorrect_rationale: '' };
            }

            return {
                key: statement?.key || '',
                label: statement?.label || statement?.nama || `Pernyataan ${idx + 1}`,
                score: Number.isFinite(Number(statement?.score)) ? Number(statement.score) : 1,
                correct_value: statement?.correct_value || statement?.answer_key || '',
                correct_rationale: (statement?.correct_rationale || '').toString(),
                incorrect_rationale: (statement?.incorrect_rationale || '').toString(),
            };
        },
        normalizeVisibilityRule(rule = {}) {
            return {
                source_key: (rule?.source_key || rule?.field_key || '').toString().trim(),
                operator: ['equals', 'not_equals', 'contains', 'not_contains', 'filled', 'empty', 'gt', 'gte', 'lt', 'lte'].includes(rule?.operator) ? rule.operator : 'equals',
                value: Array.isArray(rule?.value) ? rule.value.join(', ') : (rule?.value || '').toString(),
            };
        },
        normalizeFormField(field, idx) {
            const type = field?.tipe || 'text';
            const merged = {
                ...this.formFieldDefaults(type),
                ...(field || {}),
                tipe: type,
            };

            merged.deskripsi_media_url = (merged.deskripsi_media_url || '').toString().trim();
            merged.deskripsi_media_alt = (merged.deskripsi_media_alt || '').toString().trim();
            merged.visibility_mode = ['always', 'conditional'].includes(merged.visibility_mode) ? merged.visibility_mode : 'always';
            merged.visibility_match = ['all', 'any'].includes(merged.visibility_match) ? merged.visibility_match : 'all';
            merged.visibility_rules = Array.isArray(merged.visibility_rules)
                ? merged.visibility_rules.map((rule) => this.normalizeVisibilityRule(rule)).filter((rule) => rule.source_key || rule.value || ['filled', 'empty'].includes(rule.operator))
                : [];
            merged.question_weight = Number.isFinite(Number(merged.question_weight)) ? Math.max(0, Number(merged.question_weight)) : 1;
            merged.answer_key = Array.isArray(merged.answer_key) ? merged.answer_key : (merged.answer_key || '').toString();
            merged.answer_key_case_sensitive = !!merged.answer_key_case_sensitive;

            merged.opsi = ['radio', 'checkbox', 'select'].includes(type)
                ? (Array.isArray(merged.opsi) ? merged.opsi : []).map((option, optionIdx) => this.normalizeFormOption(option, optionIdx))
                : [];

            if (['radio', 'checkbox', 'select'].includes(type) && merged.opsi.length === 0) {
                merged.opsi = this.formFieldDefaults(type).opsi;
            }

            merged.pernyataan = type === 'matrix'
                ? (Array.isArray(merged.pernyataan) ? merged.pernyataan : []).map((statement, statementIdx) => this.normalizeFormStatement(statement, statementIdx))
                : [];

            if (type === 'matrix' && merged.pernyataan.length === 0) {
                merged.pernyataan = this.formFieldDefaults(type).pernyataan;
            }

            merged.min = Number.isFinite(Number(merged.min)) ? Math.max(1, Number(merged.min)) : 1;
            merged.max = Number.isFinite(Number(merged.max)) ? Math.max(merged.min + 1, Number(merged.max)) : Math.max(merged.min + 1, 4);
            merged.wajib = ['section', 'divider'].includes(type) ? false : !!merged.wajib;
            merged.multiple = !!merged.multiple;
            merged.max_files = Number.isFinite(Number(merged.max_files)) ? Math.max(1, Number(merged.max_files)) : 1;
            merged.max_size_mb = Number.isFinite(Number(merged.max_size_mb)) ? Math.max(1, Number(merged.max_size_mb)) : 10;
            merged.accept = (merged.accept || '').toString().trim();
            merged.canvas_height = Number.isFinite(Number(merged.canvas_height)) ? Math.max(120, Number(merged.canvas_height)) : 220;
            merged.stroke_color = /^#[0-9a-fA-F]{6}$/.test((merged.stroke_color || '').toString()) ? merged.stroke_color : '#111827';
            merged.background_color = /^#[0-9a-fA-F]{6}$/.test((merged.background_color || '').toString()) ? merged.background_color : '#ffffff';

            if (!['file', 'signature'].includes(type)) {
                merged.multiple = false;
                merged.max_files = 1;
                merged.max_size_mb = 10;
                merged.accept = '';
            }

            if (type === 'signature') {
                merged.multiple = false;
                merged.max_files = 1;
                merged.accept = '';
            }

            if (type !== 'signature') {
                merged.canvas_height = 220;
                merged.stroke_color = '#111827';
                merged.background_color = '#ffffff';
            }

            if (!merged.multiple) {
                merged.max_files = 1;
            }

            if (!['text', 'email', 'tel', 'number', 'textarea', 'url'].includes(type)) {
                merged.placeholder = '';
            }

            return merged;
        },
        normalizeFormConfig() {
            if (this.tipe !== 'formulir') {
                return;
            }

            this.cfg.pengantar = this.cfg.pengantar || '';
            this.cfg.mode = ['form', 'quiz'].includes(this.cfg.mode) ? this.cfg.mode : 'form';
            this.cfg.header_logo_url = this.cfg.header_logo_url || '';
            this.cfg.header_logo_alt = this.cfg.header_logo_alt || 'Logo formulir';
            this.cfg.header_image_url = this.cfg.header_image_url || '';
            this.cfg.header_image_alt = this.cfg.header_image_alt || 'Header formulir';
            this.cfg.pengantar_image_url = this.cfg.pengantar_image_url || '';
            this.cfg.pengantar_image_alt = this.cfg.pengantar_image_alt || 'Gambar pengantar formulir';
            this.cfg.passing_score = Number.isFinite(Number(this.cfg.passing_score)) ? Math.min(100, Math.max(0, Number(this.cfg.passing_score))) : 70;
            this.cfg.duration_minutes = Number.isFinite(Number(this.cfg.duration_minutes)) ? Math.max(0, Number(this.cfg.duration_minutes)) : 0;
            this.cfg.opens_at = (this.cfg.opens_at || '').toString();
            this.cfg.closes_at = (this.cfg.closes_at || '').toString();
            this.cfg.scheduled_message = this.cfg.scheduled_message || 'Formulir ini belum dibuka.';
            this.cfg.closed_message = this.cfg.closed_message || 'Akses formulir saat ini ditutup.';
            this.cfg.time_limit_message = this.cfg.time_limit_message || 'Waktu pengerjaan untuk formulir ini sudah habis.';
            this.cfg.show_rationales_after_submit = this.cfg.show_rationales_after_submit !== false;
            this.cfg.access_scope = ['public', 'employee_only', 'student_only', 'employee_or_student', 'event_participants'].includes(this.cfg.access_scope) ? this.cfg.access_scope : 'public';
            this.cfg.access_event_id = this.cfg.access_event_id || '';
            this.cfg.access_message = (this.cfg.access_message || '').toString();
            this.cfg.access_registration_code_label = (this.cfg.access_registration_code_label || 'Kode registrasi event').toString();
            this.cfg.proctoring_enabled = !!this.cfg.proctoring_enabled;
            this.cfg.proctoring_require_fullscreen = !!this.cfg.proctoring_require_fullscreen;
            this.cfg.proctoring_block_copy_paste = this.cfg.proctoring_block_copy_paste !== false;
            this.cfg.proctoring_block_context_menu = this.cfg.proctoring_block_context_menu !== false;
            this.cfg.proctoring_capture_selfie = !!this.cfg.proctoring_capture_selfie;
            this.cfg.proctoring_auto_submit = !!this.cfg.proctoring_auto_submit;
            this.cfg.proctoring_max_violations = Number.isFinite(Number(this.cfg.proctoring_max_violations)) ? Math.max(0, Number(this.cfg.proctoring_max_violations)) : 0;
            this.cfg.proctoring_notice = (this.cfg.proctoring_notice || 'Mode proctoring aktif. Aktivitas seperti pindah tab, keluar fullscreen, dan percobaan copy atau paste akan dicatat.').toString();
            this.cfg.proctoring_selfie_label = (this.cfg.proctoring_selfie_label || 'Ambil foto verifikasi peserta').toString();
            this.cfg.email_tujuan = this.cfg.email_tujuan || '';
            this.cfg.pesan_sukses = this.cfg.pesan_sukses || 'Terima kasih!';
            this.cfg.judul_submit = this.cfg.judul_submit || 'Kirim';
            this.cfg.response_limit = ['none', 'session', 'email'].includes(this.cfg.response_limit) ? this.cfg.response_limit : 'none';
            this.cfg.limit_message = this.cfg.limit_message || 'Formulir ini hanya dapat dikirim satu kali.';
            this.cfg.fields = Array.isArray(this.cfg.fields)
                ? this.cfg.fields.map((field, idx) => this.normalizeFormField(field, idx))
                : [];

            if (this.cfg.fields.length === 0) {
                this.cfg.fields = [this.formFieldDefaults('text')];
            }
        },
        addFormField(type = 'text') {
            this.normalizeFormConfig();
            this.cfg.fields.push(this.formFieldDefaults(type));
        },
        moveFormField(idx, direction) {
            const target = idx + direction;
            if (target < 0 || target >= this.cfg.fields.length) {
                return;
            }

            const [field] = this.cfg.fields.splice(idx, 1);
            this.cfg.fields.splice(target, 0, field);
        },
        duplicateFormField(idx) {
            const source = this.cfg.fields[idx];
            if (!source) {
                return;
            }

            const clone = JSON.parse(JSON.stringify(source));
            clone.key = '';

            if (Array.isArray(clone.opsi)) {
                clone.opsi = clone.opsi.map(option => ({ ...option, value: '' }));
            }

            if (Array.isArray(clone.pernyataan)) {
                clone.pernyataan = clone.pernyataan.map(statement => ({ ...statement, key: '' }));
            }

            this.cfg.fields.splice(idx + 1, 0, this.normalizeFormField(clone, idx + 1));
        },
        addVisibilityRule(field) {
            if (!Array.isArray(field.visibility_rules)) {
                field.visibility_rules = [];
            }

            field.visibility_mode = 'conditional';
            field.visibility_rules.push(this.normalizeVisibilityRule({ source_key: '', operator: 'equals', value: '' }));
        },
        isScorableFormField(field) {
            return !['section', 'divider', 'file', 'signature'].includes(field?.tipe || 'text');
        },
        scaleRange(field) {
            const min = Number.isFinite(Number(field?.min)) ? Math.max(1, Number(field.min)) : 1;
            const max = Number.isFinite(Number(field?.max)) ? Math.max(min + 1, Number(field.max)) : min + 3;
            return Array.from({ length: Math.max(0, max - min + 1) }, (_, index) => String(min + index));
        },
        addFormOption(field) {
            if (!Array.isArray(field.opsi)) {
                field.opsi = [];
            }

            field.opsi.push({ value: '', label: `Pilihan ${field.opsi.length + 1}`, score: 0, is_correct: false, rationale: '' });
            field.opsi[field.opsi.length - 1].media_url = '';
            field.opsi[field.opsi.length - 1].media_alt = `Pilihan ${field.opsi.length}`;
        },
        addFormStatement(field) {
            if (!Array.isArray(field.pernyataan)) {
                field.pernyataan = [];
            }

            field.pernyataan.push({ key: '', label: `Pernyataan ${field.pernyataan.length + 1}`, score: 1, correct_value: '', correct_rationale: '', incorrect_rationale: '' });
        },
        plainText(html) {
            const container = document.createElement('div');
            container.innerHTML = html || '';
            return (container.textContent || container.innerText || '').replace(/\s+/g, ' ').trim();
        },
        normalizeDriveDisplayMode(value) {
            return ['embed', 'cards'].includes(value) ? value : 'embed';
        },
        normalizeDriveFolderMode(value) {
            return ['grid', 'list'].includes(value) ? value : 'list';
        },
        driveFolderId(url) {
            const value = (url || '').trim();
            const match = value.match(/drive(?:\/u\/\d+)?\/folders\/([a-zA-Z0-9_-]+)/);
            if (match) {
                return match[1];
            }

            try {
                const parsed = new URL(value);
                if (value.includes('embeddedfolderview')) {
                    return parsed.searchParams.get('id') || '';
                }
            } catch (error) {
                return '';
            }

            return '';
        },
        driveFileId(url) {
            const value = (url || '').trim();
            const match = value.match(/(?:file|document|spreadsheets|presentation|forms)\/(?:d|u\/\d+\/d)\/([a-zA-Z0-9_-]+)/);
            if (match) {
                return match[1];
            }

            try {
                const parsed = new URL(value);
                return parsed.searchParams.get('id') || '';
            } catch (error) {
                return '';
            }
        },
        drivePreviewData(rawUrl = null) {
            const inputUrl = (rawUrl ?? this.cfg.url ?? '').trim();
            const folderId = this.driveFolderId(inputUrl);
            if (folderId) {
                return {
                    kind: 'folder',
                    embedUrl: `https://drive.google.com/embeddedfolderview?id=${folderId}#${this.normalizeDriveFolderMode(this.cfg.mode)}`,
                    openUrl: `https://drive.google.com/drive/folders/${folderId}`,
                };
            }

            const fileId = this.driveFileId(inputUrl);
            if (fileId) {
                return {
                    kind: 'file',
                    embedUrl: `https://drive.google.com/file/d/${fileId}/preview`,
                    openUrl: inputUrl || `https://drive.google.com/file/d/${fileId}/view`,
                };
            }

            return {
                kind: inputUrl ? 'unknown' : 'empty',
                embedUrl: '',
                openUrl: inputUrl,
            };
        },
        drivePreviewEmbedUrl() {
            if (this.normalizeDriveDisplayMode(this.cfg.display_mode) !== 'embed') {
                return '';
            }

            return this.drivePreviewData().embedUrl;
        },
        drivePreviewOpenUrl() {
            const displayMode = this.normalizeDriveDisplayMode(this.cfg.display_mode);
            if (displayMode === 'cards' && !this.cfg.url) {
                return '';
            }

            return this.drivePreviewData().openUrl;
        },
        driveCardPreviewItems() {
            if (!Array.isArray(this.cfg.items)) {
                return [];
            }

            return this.cfg.items.filter((item) => {
                const name = String(item?.nama || '').trim();
                const url = String(item?.url || '').trim();
                return name !== '' || url !== '';
            });
        },
        driveItemKind(item) {
            return this.drivePreviewData(item?.url || '').kind;
        },
        driveItemIcon(item) {
            const badge = String(item?.tipe || '').toLowerCase();
            if (badge.includes('sheet') || badge.includes('excel')) {
                return '📊';
            }
            if (badge.includes('pdf')) {
                return '📄';
            }
            if (badge.includes('form')) {
                return '📝';
            }
            if (badge.includes('slide') || badge.includes('ppt')) {
                return '🖥️';
            }
            if (badge.includes('folder') || this.driveItemKind(item) === 'folder') {
                return '🗂️';
            }
            return '📎';
        },
        driveItemBadge(item) {
            if (String(item?.tipe || '').trim() !== '') {
                return item.tipe;
            }

            return this.driveItemKind(item) === 'folder' ? 'Folder Drive' : 'File Drive';
        },
        driveItemOpenUrl(item) {
            const preview = this.drivePreviewData(item?.url || '');
            return preview.openUrl || String(item?.url || '').trim();
        },
        drivePreviewSummary() {
            const displayMode = this.normalizeDriveDisplayMode(this.cfg.display_mode);
            if (displayMode === 'cards') {
                const total = this.driveCardPreviewItems().length;
                return total > 0
                    ? `${total} item kartu akan ditampilkan pada blok Google Drive.`
                    : 'Mode kartu aktif. Tambahkan item kartu untuk melihat hasil daftar file.';
            }

            const preview = this.drivePreviewData();
            if (preview.kind === 'folder') {
                return 'Preview folder Google Drive aktif. Tampilan folder mengikuti mode list atau grid.';
            }
            if (preview.kind === 'file') {
                return 'Preview file Google Drive aktif dan akan tampil sebagai iframe publik.';
            }
            if (preview.kind === 'unknown') {
                return 'URL terisi, tetapi pola Google Drive belum dikenali untuk preview embed otomatis.';
            }

            return 'Tempel URL Google Drive untuk melihat preview blok sebelum disimpan.';
        },
        guessDriveItemType(url, kind = null) {
            const value = String(url || '').toLowerCase();

            if (kind === 'folder') {
                return 'Folder';
            }

            if (value.includes('/spreadsheets/')) {
                return 'Spreadsheet';
            }

            if (value.includes('/document/')) {
                return 'Dokumen';
            }

            if (value.includes('/presentation/')) {
                return 'Slide';
            }

            if (value.includes('/forms/')) {
                return 'Form';
            }

            if (value.includes('.pdf')) {
                return 'PDF';
            }

            return kind === 'file' ? 'File Drive' : 'Google Drive';
        },
        guessDriveItemName(url, kind, index) {
            const type = this.guessDriveItemType(url, kind);

            if (kind === 'folder') {
                return `Folder Drive ${index}`;
            }

            if (['Spreadsheet', 'Dokumen', 'Slide', 'Form', 'PDF'].includes(type)) {
                return `${type} ${index}`;
            }

            return `File Drive ${index}`;
        },
        importDriveCardItems() {
            const lines = String(this.driveImport.text || '')
                .split(/\r?\n/)
                .map((line) => line.trim())
                .filter(Boolean);

            if (lines.length === 0) {
                this.driveImport.message = 'Belum ada link yang ditempel untuk diimpor.';
                this.driveImport.error = true;
                return;
            }

            if (!Array.isArray(this.cfg.items)) {
                this.cfg.items = [];
            }

            const knownUrls = new Set(
                this.cfg.items
                    .map((item) => String(item?.url || '').trim())
                    .filter(Boolean)
            );

            const imported = [];
            let skipped = 0;

            lines.forEach((line) => {
                const parts = line.split('|').map((part) => part.trim());
                const [url, nama = '', deskripsi = '', tipe = '', ukuran = ''] = parts;

                if (!/^https?:\/\//i.test(url || '')) {
                    skipped += 1;
                    return;
                }

                if (knownUrls.has(url)) {
                    skipped += 1;
                    return;
                }

                const preview = this.drivePreviewData(url);
                const itemIndex = this.cfg.items.length + imported.length + 1;

                imported.push({
                    nama: nama || this.guessDriveItemName(url, preview.kind, itemIndex),
                    deskripsi,
                    url,
                    tipe: tipe || this.guessDriveItemType(url, preview.kind),
                    ukuran,
                });

                knownUrls.add(url);
            });

            if (imported.length === 0) {
                this.driveImport.message = skipped > 0
                    ? 'Semua baris dilewati karena link tidak valid atau sudah ada di daftar kartu.'
                    : 'Tidak ada item baru yang berhasil diimpor.';
                this.driveImport.error = true;
                return;
            }

            this.cfg.items = [...this.cfg.items, ...imported];
            this.driveImport.message = `${imported.length} item berhasil diimpor${skipped > 0 ? `, ${skipped} baris dilewati.` : '.'}`;
            this.driveImport.error = false;
            this.driveImport.text = '';
        },
        async generateAiDraft(pageTitle = '') {
            if (this.aiDraft.generating || !this.aiDraft.acara) {
                return;
            }

            this.aiDraft.generating = true;
            this.aiDraft.error = '';

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('type', this.tipe);
            formData.append('acara', this.aiDraft.acara || '');
            formData.append('tempat', this.aiDraft.tempat || '');
            formData.append('tanggal', this.aiDraft.tanggal || '');
            formData.append('catatan', this.aiDraft.catatan || '');

            const sectionTitle = this.$refs.sectionTitle?.value?.trim() || '';
            const contextTitle = (sectionTitle || pageTitle || '').substring(0, 200);
            if (contextTitle) {
                formData.append('context_title', contextTitle);
            }

            if (this.aiDraft.mediaFile) {
                formData.append('media', this.aiDraft.mediaFile);
            }

            try {
                const response = await fetch('{{ route('admin.content-engine.generate-block') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData,
                });

                const data = await response.json();
                if (!response.ok) {
                    let errMsg = data.error || data.message || 'Gagal generate konten.';
                    if (data.errors) {
                        const firstErrorKey = Object.keys(data.errors)[0];
                        errMsg = data.errors[firstErrorKey][0];
                    }
                    throw new Error(errMsg);
                }

                const payload = data.payload || data;
                const applied = this.applyAiDraftPayload(payload);
                if (!applied) {
                    throw new Error('Response AI tidak lengkap.');
                }

                this.syncAiDraftToConfig();
            } catch (error) {
                this.aiDraft.error = error.message || 'Koneksi error.';
            } finally {
                this.aiDraft.generating = false;
            }
        },
        applyAiDraftPayload(payload) {
            if (this.tipe === 'hero') {
                if (payload.heading) {
                    this.cfg.heading = payload.heading;
                }
                if (payload.sub) {
                    this.cfg.sub = payload.sub;
                } else if (payload.konten) {
                    this.cfg.sub = this.plainText(payload.konten);
                }

                return Boolean(this.cfg.heading || this.cfg.sub);
            }

            if (['teks', 'ai_teks'].includes(this.tipe)) {
                if (!payload.konten) {
                    return false;
                }

                this.cfg.konten = payload.konten;
                queueMicrotask(() => {
                    const editorId = this.tipe === 'ai_teks' ? 'section-ai-teks-konten' : 'section-teks-konten';
                    window.PolkesbanRichText?.setContent(editorId, payload.konten);
                });
                return true;
            }

            if (this.tipe === 'statistik') {
                if (!Array.isArray(payload.items) || payload.items.length === 0) {
                    return false;
                }

                this.cfg.items = payload.items;
                return true;
            }

            if (this.tipe === 'tabel') {
                if (!Array.isArray(payload.header) || !Array.isArray(payload.baris)) {
                    return false;
                }

                this.cfg.header = payload.header;
                this.cfg.baris = payload.baris;
                this.cfg.dengan_header = Boolean(payload.dengan_header ?? true);
                this.cfg.bergaris = Boolean(payload.bergaris ?? true);
                return true;
            }

            if (this.tipe === 'tim') {
                if (!Array.isArray(payload.items) || payload.items.length === 0) {
                    return false;
                }

                this.cfg.items = payload.items;
                return true;
            }

            if (this.tipe === 'formulir') {
                if (!Array.isArray(payload.fields) || payload.fields.length === 0) {
                    return false;
                }

                this.cfg.fields = payload.fields.map((field, idx) => this.normalizeFormField(field, idx));
                this.cfg.pengantar = payload.pengantar ?? this.cfg.pengantar;
                this.cfg.email_tujuan = payload.email_tujuan ?? '';
                this.cfg.pesan_sukses = payload.pesan_sukses ?? this.cfg.pesan_sukses;
                this.cfg.judul_submit = payload.judul_submit ?? this.cfg.judul_submit;
                return true;
            }

            return false;
        },
        setTipe(t) {
            this.tipe = t;
            if (t === 'formulir') {
                this.normalizeFormConfig();
            }
            this.syncAiDraftToConfig();
            if (['teks', 'ai_teks'].includes(t)) {
                this.ensureRichEditors();
            }
        },
    };
}
</script>
@endsection

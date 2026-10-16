@extends('layouts.app')

@section('title', 'Formulir Pengaduan / Kritik / Saran / Keluhan — Poltekkes Bandung')
@section('description', 'Sampaikan pengaduan, kritik, saran, atau keluhan Anda kepada unit-unit di Politeknik Kesehatan Bandung.')

@section('content')
<div class="min-h-screen bg-teal-50/20 dark:bg-gray-950/50 py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6">

        {{-- Header --}}
        <div class="mb-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-600 text-white text-2xl shadow-lg mb-4">
                📋
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                Formulir Pengaduan / Kritik / Saran
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                Sampaikan pengaduan, kritik, saran, atau keluhan Anda kepada unit/bagian terkait di Poltekkes Bandung.
                Setiap masukan akan ditindaklanjuti sesuai prosedur.
            </p>
        </div>

        @if($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 p-4">
            <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">Terdapat kesalahan pada formulir:</p>
            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-300 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

<style>
    .glassmorphic-card {
        background-color: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
    }
    .dark .glassmorphic-card {
        background-color: rgba(17, 24, 39, 0.8) !important;
    }
</style>

        <div class="glassmorphic-card rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden">

            {{-- Form top band --}}
            <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-400 to-sky-400"></div>

            <form action="{{ route('pengaduan.store') }}" method="POST" class="p-6 sm:p-8 space-y-6">
                @csrf

                {{-- Jenis --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-2">
                        Jenis Pesan <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach([
                            ['val'=>'pengaduan','icon'=>'🚨','label'=>'Pengaduan','color'=>'red'],
                            ['val'=>'kritik',   'icon'=>'💬','label'=>'Kritik',   'color'=>'orange'],
                            ['val'=>'saran',    'icon'=>'💡','label'=>'Saran',    'color'=>'green'],
                            ['val'=>'keluhan',  'icon'=>'⚠️','label'=>'Keluhan',  'color'=>'yellow'],
                        ] as $jenis)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="jenis" value="{{ $jenis['val'] }}"
                                   class="peer sr-only"
                                   {{ old('jenis', 'saran') === $jenis['val'] ? 'checked' : '' }}>
                            <div class="flex flex-col items-center gap-1 rounded-2xl border-2 border-gray-200 bg-gray-50 py-3 px-2 text-center transition-all
                                        peer-checked:border-brand-500 peer-checked:bg-brand-50 dark:peer-checked:bg-brand-950/20 peer-checked:shadow-sm
                                        hover:border-brand-300 dark:border-gray-700 dark:bg-gray-800">
                                <span class="text-xl">{{ $jenis['icon'] }}</span>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-brand-700 dark:peer-checked:text-brand-400">{{ $jenis['label'] }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('jenis')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Tujuan --}}
                <div>
                    <label for="tujuan_unit" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                        Ditujukan kepada <span class="text-red-500">*</span>
                    </label>
                    <select id="tujuan_unit" name="tujuan_unit" required
                            class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition">
                        <option value="">— Pilih Unit/Bagian/Prodi Tujuan —</option>
                        @php
                            $grouped = collect($tujuanOptions)->groupBy('group');
                        @endphp
                        @foreach($grouped as $groupName => $items)
                        <optgroup label="{{ $groupName }}">
                            @foreach($items as $item)
                            <option value="{{ $item['key'] }}"
                                    {{ old('tujuan_unit') === $item['key'] ? 'selected' : '' }}>
                                {{ $item['label'] }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @error('tujuan_unit')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Subjek --}}
                <div>
                    <label for="subjek" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                        Subjek / Judul <span class="text-red-500">*</span>
                    </label>
                    <input id="subjek" type="text" name="subjek" required
                           value="{{ old('subjek') }}"
                           placeholder="Contoh: Keluhan layanan perpustakaan..."
                           maxlength="200"
                           class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition">
                    @error('subjek')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Isi --}}
                <div>
                    <label for="isi" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                        Isi Pesan <span class="text-red-500">*</span>
                    </label>
                    <textarea id="isi" name="isi" rows="5" required
                              placeholder="Tuliskan pengaduan/kritik/saran/keluhan Anda secara jelas dan detail..."
                              maxlength="5000"
                              class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition resize-none">{{ old('isi') }}</textarea>
                    @error('isi')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Anonim toggle --}}
                <div x-data="{ anonim: {{ old('anonim') ? 'true' : 'false' }} }">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input type="checkbox" name="anonim" value="1"
                                   x-model="anonim"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer
                                        peer-checked:bg-brand-600 transition-colors
                                        after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                        after:bg-white after:rounded-full after:w-5 after:h-5
                                        after:transition-transform peer-checked:after:translate-x-5"></div>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-200">Kirim sebagai Anonim</p>
                            <p class="text-xs text-gray-400">Identitas Anda tidak akan disimpan atau ditampilkan</p>
                        </div>
                    </label>

                    {{-- Identity fields --}}
                    <div x-show="!anonim" x-transition class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="nama" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input id="nama" type="text" name="nama" :required="!anonim"
                                   value="{{ old('nama') }}"
                                   placeholder="Nama Anda"
                                   class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition">
                            @error('nama')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                Email <span class="text-xs font-normal text-gray-400">(opsional)</span>
                            </label>
                            <input id="email" type="email" name="email"
                                   value="{{ old('email') }}"
                                   placeholder="email@contoh.com"
                                   class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="telepon" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                Nomor Telepon <span class="text-xs font-normal text-gray-400">(opsional)</span>
                            </label>
                            <input id="telepon" type="tel" name="telepon"
                                   value="{{ old('telepon') }}"
                                   placeholder="08xx-xxxx-xxxx"
                                   class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition">
                        </div>
                    </div>

                    <div x-show="anonim" x-transition class="mt-3">
                        <input type="hidden" name="nama" value="Anonim">
                    </div>
                </div>

                {{-- Notice --}}
                <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-800 leading-relaxed">
                    <p class="font-bold mb-1">📌 Informasi Penting</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Pesan yang masuk akan ditinjau oleh unit terkait dalam 3 hari kerja.</li>
                        <li>Simpan <strong>Nomor Tiket</strong> yang diberikan setelah pengiriman untuk memantau status.</li>
                        <li>Mohon sampaikan pesan dengan bahasa yang sopan dan konstruktif.</li>
                    </ul>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full rounded-2xl bg-brand-600 hover:bg-brand-500 active:bg-brand-700 text-white font-bold py-3.5 px-6 text-sm transition-all shadow-lg shadow-brand-900/20 focus:ring-2 focus:ring-brand-300 focus:outline-none">
                    Kirim Pengaduan / Saran
                    <span class="ml-1">→</span>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">
            Politeknik Kesehatan Bandung · Formulir ini terenkripsi dan aman
        </p>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Hubungi Kami')
@section('description', 'Hubungi Politeknik Kesehatan Kemenkes Bandung — kami siap membantu pertanyaan Anda.')

@php
    $contactFormLabels = \App\Support\PublicLocale::translateData([
        'send' => 'Kirim Pesan',
        'sending' => 'Mengirim...',
    ]);
@endphp

@section('content')

<section class="bg-brand-700 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-3 flex text-sm text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-white" aria-current="page">Kontak</li>
            </ol>
        </nav>
        <h1 class="text-3xl font-bold text-white">Hubungi Kami</h1>
        <p class="mt-2 text-brand-200">Kami siap menjawab pertanyaan Anda</p>
    </div>
</section>

<section class="bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-3 lg:gap-12">

            {{-- Info Kontak --}}
            <div class="space-y-6">
                <h2 class="text-xl font-bold text-gray-900">Informasi Kontak</h2>

                <dl class="space-y-5">
                    @php $kontakInfos = [
                        ['icon' => '📍', 'label' => 'Alamat', 'nilai' => 'Jl. Pajajaran No. 56, Bandung 40171, Jawa Barat'],
                        ['icon' => '📞', 'label' => 'Telepon', 'nilai' => '(022) 4231627'],
                        ['icon' => '📧', 'label' => 'Email', 'nilai' => 'info@polkesban.ac.id'],
                        ['icon' => '🕐', 'label' => 'Jam Operasional', 'nilai' => "Senin–Jumat: 08.00–16.00 WIB\nSabtu: 08.00–12.00 WIB"],
                    ]; @endphp
                    @foreach ($kontakInfos as $info)
                    <div class="flex gap-3">
                        <span class="text-xl" aria-hidden="true">{{ $info['icon'] }}</span>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $info['label'] }}</dt>
                            <dd class="mt-0.5 text-sm text-gray-700 whitespace-pre-line">{{ $info['nilai'] }}</dd>
                        </div>
                    </div>
                    @endforeach
                </dl>

                {{-- Peta (embed tanpa cookies / Google Maps JS SDK) --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 aspect-video mt-6">
                    <iframe
                        src="https://maps.google.com/maps?q=Jl.+Pajajaran+No.+56+Bandung+Politeknik+Kesehatan+Kemenkes+Bandung&output=embed&z=16"
                        loading="lazy"
                        class="h-full w-full border-0"
                        title="Lokasi kampus Poltekkes Bandung di Google Maps"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
                <a href="https://maps.google.com/maps?q=Jl.+Pajajaran+No.+56+Bandung+Politeknik+Kesehatan+Kemenkes+Bandung"
                   target="_blank" rel="noopener noreferrer"
                   class="mt-2 inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 hover:underline transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Buka di Google Maps
                </a>

                {{-- Tautan Layanan --}}
                <div class="mt-6 rounded-xl bg-brand-50 border border-brand-100 p-4">
                    <p class="text-xs font-semibold text-brand-700 uppercase tracking-wide mb-3">Layanan Online</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['label'=>'Helpdesk',     'href'=>'https://helpdesk.poltekkesbandung.ac.id/'],
                            ['label'=>'SPMB',         'href'=>'https://spmb.poltekkesbandung.ac.id/'],
                            ['label'=>'SIAKAD',       'href'=>'https://siak.poltekkesbandung.ac.id/'],
                            ['label'=>'Perpustakaan', 'href'=>'https://library.poltekkesbandung.ac.id'],
                        ] as $srv)
                        <a href="{{ $srv['href'] }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-1.5 rounded-lg bg-white border border-brand-100 px-3 py-2 text-xs font-medium text-brand-700 hover:bg-brand-100 transition-colors">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            {{ $srv['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Form Kontak --}}
            <div class="mt-12 lg:col-span-2 lg:mt-0">
                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Kirim Pesan</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Isi form di bawah ini. Kami akan merespons dalam 1-2 hari kerja.
                    </p>

                    <form action="{{ route('kontak.store') }}" method="POST" class="mt-6 space-y-5"
                                                    x-data="{ loading: false, labels: @js($contactFormLabels) }" @submit="loading = true">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Nama --}}
                            <div>
                                <label for="nama" class="block text-sm font-medium text-gray-700">
                                    Nama Lengkap <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="nama" name="nama"
                                       value="{{ old('nama') }}" required maxlength="100"
                                       autocomplete="name"
                                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm
                                              focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none
                                              @error('nama') border-red-400 @enderror">
                                @error('nama')
                                <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input type="email" id="email" name="email"
                                       value="{{ old('email') }}" required maxlength="255"
                                       autocomplete="email"
                                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm
                                              focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none
                                              @error('email') border-red-400 @enderror">
                                @error('email')
                                <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Telepon --}}
                            <div>
                                <label for="telepon" class="block text-sm font-medium text-gray-700">Telepon</label>
                                <input type="tel" id="telepon" name="telepon"
                                       value="{{ old('telepon') }}" maxlength="20"
                                       autocomplete="tel"
                                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm
                                              focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none
                                              @error('telepon') border-red-400 @enderror">
                                @error('telepon')
                                <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Topik --}}
                            <div>
                                <label for="topik" class="block text-sm font-medium text-gray-700">
                                    Topik <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <select id="topik" name="topik" required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm
                                               focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none
                                               @error('topik') border-red-400 @enderror">
                                    <option value="">-- Pilih Topik --</option>
                                    @foreach (['umum' => 'Pertanyaan Umum', 'pendaftaran' => 'Pendaftaran Mahasiswa', 'akademik' => 'Akademik', 'kerjasama' => 'Kerjasama Institusi', 'lainnya' => 'Lainnya'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('topik') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('topik')
                                <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Subjek --}}
                        <div>
                            <label for="subjek" class="block text-sm font-medium text-gray-700">
                                Subjek <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="subjek" name="subjek"
                                   value="{{ old('subjek') }}" required maxlength="200"
                                   class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm
                                          focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none
                                          @error('subjek') border-red-400 @enderror">
                            @error('subjek')
                            <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Pesan --}}
                        <div>
                            <label for="pesan" class="block text-sm font-medium text-gray-700">
                                Pesan <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <textarea id="pesan" name="pesan" rows="5" required
                                      minlength="10" maxlength="2000"
                                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm
                                             focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none
                                             resize-y @error('pesan') border-red-400 @enderror">{{ old('pesan') }}</textarea>
                            @error('pesan')
                            <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-400">
                                <span class="text-red-500">*</span> Wajib diisi
                            </p>
                            <button type="submit"
                                    :disabled="loading"
                                    class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white
                                           hover:bg-brand-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors">
                                <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span x-text="loading ? labels.sending : labels.send">Kirim Pesan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

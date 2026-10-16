@extends('layouts.app')

@section('title', 'Akreditasi')
@section('description', 'Status akreditasi seluruh program studi Politeknik Kesehatan Kemenkes Bandung dari LAM-PTKes dan BAN-PT.')

@section('content')
<div class="bg-white dark:bg-gray-950 min-h-screen">

    {{-- Hero Banner --}}
    <div class="bg-gradient-to-br from-brand-800 to-brand-950 py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-[11px] font-black uppercase tracking-widest text-brand-300 mb-2">Tentang Kami</p>
            <h1 class="text-3xl sm:text-4xl font-black text-white">Akreditasi</h1>
            <nav class="mt-4 flex justify-center gap-1 text-sm text-brand-300" aria-label="Breadcrumb">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">Beranda</a>
                <span class="text-brand-600 mx-1">/</span>
                <span class="text-white">Akreditasi</span>
            </nav>
        </div>
    </div>

    {{-- Sub-Navigation --}}
    <div class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <nav class="flex gap-0 overflow-x-auto scrollbar-none" aria-label="Navigasi profil">
                @php $tabs = [
                    ['href' => '/profil/sejarah',    'label' => 'Sejarah'],
                    ['href' => '/profil/visi-misi',  'label' => 'Visi & Misi'],
                    ['href' => '/profil/struktur',   'label' => 'Struktur Organisasi'],
                    ['href' => '/profil/akreditasi', 'label' => 'Akreditasi'],
                ]; @endphp
                @foreach($tabs as $tab)
                <a href="{{ url($tab['href']) }}"
                   class="whitespace-nowrap px-4 py-3 text-sm font-semibold border-b-2 transition-colors
                          {{ request()->is(ltrim($tab['href'], '/'))
                             ? 'border-brand-600 text-brand-700 dark:text-brand-400'
                             : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">
                    {{ $tab['label'] }}
                </a>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Content --}}
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">

        {{-- Akreditasi Institusi --}}
        <div class="mb-10 rounded-3xl bg-gradient-to-r from-brand-600 to-brand-800 p-8 text-center text-white shadow-xl shadow-brand-900/30">
            <div class="text-5xl mb-4" aria-hidden="true">🏆</div>
            <h2 class="text-2xl font-black">Akreditasi Institusi</h2>
            <p class="mt-2 text-brand-200 text-sm">Politeknik Kesehatan Kemenkes Bandung</p>
            <div class="mt-5 inline-block rounded-full bg-white/20 backdrop-blur-sm border border-white/30 px-6 py-2">
                <span class="text-xl font-black">UNGGUL</span>
            </div>
            <p class="mt-3 text-brand-200 text-xs">LAM-PTKes / BAN-PT</p>
        </div>

        {{-- Program Studi Akreditasi --}}
        @php
            $prodiAkred = \App\Models\ProgramStudi::where('aktif', true)
                ->orderBy('urutan')
                ->get();
            $totalProdi = $prodiAkred->count();
        @endphp

        <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2">Akreditasi Program Studi</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ $totalProdi }} program studi</p>

        <div class="rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-gray-500 dark:text-gray-400">Program Studi</th>
                        <th class="px-5 py-3 text-center text-xs font-black uppercase tracking-wide text-gray-500 dark:text-gray-400 w-28">Jenjang</th>
                        <th class="px-5 py-3 text-center text-xs font-black uppercase tracking-wide text-gray-500 dark:text-gray-400 w-28">Akreditasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($prodiAkred as $p)
                    <tr class="bg-white dark:bg-gray-950 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                        <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            <span aria-hidden="true">{{ $p->icon_emoji }}</span>
                            <a href="{{ route('program-studi.show', $p->slug) }}" class="hover:text-brand-700 dark:hover:text-brand-400 transition-colors">
                                {{ $p->nama }}
                            </a>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @php $jLabel = match($p->jenjang){ 'D3'=>'D-III','D4'=>'D-IV', default=>$p->jenjang }; @endphp
                            <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                {{ $jLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-block text-xs font-black px-3 py-1 rounded-full
                                {{ $p->akreditasi === 'Unggul'
                                   ? 'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                                   : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400' }}">
                                {{ $p->akreditasi }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-6 text-xs text-gray-400 text-center">
            Data akreditasi berdasarkan keputusan LAM-PTKes dan BAN-PT. Untuk informasi terkini kunjungi
            <a href="https://www.lamptkes.org" target="_blank" rel="noopener" class="text-brand-600 hover:underline">lamptkes.org</a> atau
            <a href="https://ban-pt.kemdikbud.go.id" target="_blank" rel="noopener" class="text-brand-600 hover:underline">ban-pt.kemdikbud.go.id</a>.
        </p>

    </div>
</div>
@endsection

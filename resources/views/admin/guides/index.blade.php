@extends('admin.layouts.admin')

@section('title', 'Pusat Panduan')
@section('page-title', 'Pusat Panduan Super Admin')
@section('page-subtitle', 'Ikhtisar seluruh fitur, modul, dan panduan operasional aplikasi Poltekkes Bandung')

@section('content')
<div class="mx-auto max-w-7xl space-y-8">
    <section class="rounded-[2rem] border border-brand-200/80 dark:border-brand-900/60 bg-gradient-to-br from-brand-50 via-white to-emerald-50 dark:from-brand-950/30 dark:via-gray-900 dark:to-emerald-950/20 p-6 shadow-sm">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-brand-600 dark:text-brand-400">Super Admin Control</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Pusat Panduan Aplikasi dan Website</h1>
                <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-300">
                    Halaman ini merangkum seluruh area kerja website publik, portal mahasiswa, panel admin, dan kontrol super admin.
                    Gunakan sebagai panduan onboarding, audit fitur, atau referensi operasional harian.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:min-w-[420px]">
                <div class="rounded-2xl bg-white/90 dark:bg-gray-950/60 border border-gray-200 dark:border-gray-800 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Modul Panduan</p>
                    <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $guideStats['guide_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/90 dark:bg-gray-950/60 border border-gray-200 dark:border-gray-800 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Kelompok</p>
                    <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $guideStats['group_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/90 dark:bg-gray-950/60 border border-gray-200 dark:border-gray-800 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Fitur Tercatat</p>
                    <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $guideStats['feature_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/90 dark:bg-gray-950/60 border border-gray-200 dark:border-gray-800 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Route Cakupan</p>
                    <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $guideStats['pattern_count'] }}</p>
                </div>
            </div>
        </div>
    </section>

    @foreach($guideGroups as $group)
    <section class="space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-bold {{ $group['badge_class'] }}">
                    <span aria-hidden="true">{{ $group['group_icon'] }}</span>
                    {{ $group['group_label'] }}
                </p>
                <h2 class="mt-3 text-xl font-extrabold text-gray-900 dark:text-white">{{ $group['group_description'] }}</h2>
            </div>
            <div class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">
                {{ count($group['guides']) }} modul panduan
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach($group['guides'] as $guide)
            <article class="rounded-[1.75rem] border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white">{{ $guide['title'] }}</h3>
                    <span class="inline-flex items-center rounded-full bg-brand-100 dark:bg-brand-950 px-2.5 py-1 text-[11px] font-bold text-brand-700 dark:text-brand-300">
                        {{ count($guide['features']) }} fitur
                    </span>
                </div>
                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $guide['summary'] }}</p>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Langkah Cepat</p>
                        <ol class="mt-2 space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                            @foreach($guide['steps'] as $index => $step)
                            <li class="flex gap-2">
                                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-950 text-[10px] font-black text-brand-700 dark:text-brand-300">{{ $index + 1 }}</span>
                                <span>{{ $step }}</span>
                            </li>
                            @endforeach
                        </ol>
                    </div>

                    <div>
                        <p class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Fitur Utama</p>
                        <ul class="mt-2 space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                            @foreach($guide['features'] as $feature)
                            <li class="flex gap-2">
                                <span class="mt-1 text-emerald-500">•</span>
                                <span>{{ $feature }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                @if($guide['tips'])
                <div class="mt-5 rounded-2xl bg-gray-50 dark:bg-gray-950/60 border border-gray-200 dark:border-gray-800 p-4">
                    <p class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Tips dan Catatan</p>
                    <ul class="mt-2 space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                        @foreach($guide['tips'] as $tip)
                        <li class="flex gap-2">
                            <span class="mt-1 text-amber-500">•</span>
                            <span>{{ $tip }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if($guide['patterns'])
                <div class="mt-5">
                    <p class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Route Terkait</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($guide['patterns'] as $pattern)
                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-[11px] font-mono text-gray-600 dark:text-gray-300">{{ $pattern }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </article>
            @endforeach
        </div>
    </section>
    @endforeach
</div>
@endsection
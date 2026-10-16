@php
    $guide = $guide ?? null;
    $position = match ($guide['group'] ?? 'public') {
        'public' => [
            'button' => 'top-20 right-4 sm:top-24 sm:right-5',
            'panel' => 'inset-x-3 top-20 bottom-auto sm:inset-auto sm:top-24 sm:right-5 sm:w-[28rem] lg:w-[30rem]',
        ],
        default => [
            'button' => 'top-4 right-4 sm:top-5 sm:right-5',
            'panel' => 'inset-x-3 top-20 bottom-auto sm:inset-auto sm:top-5 sm:right-5 sm:w-[28rem] lg:w-[30rem]',
        ],
    };
    $theme = match ($guide['group'] ?? 'public') {
        'admin' => [
            'button' => 'bg-brand-600 hover:bg-brand-500 text-white shadow-brand-950/35',
            'header' => 'from-brand-900 via-brand-800 to-emerald-800',
            'surface' => 'border-brand-200 dark:border-brand-900/60',
            'muted' => 'bg-brand-50 dark:bg-brand-950/30 text-brand-800 dark:text-brand-100',
        ],
        'super_admin' => [
            'button' => 'bg-brand-600 hover:bg-brand-500 text-white shadow-brand-950/35',
            'header' => 'from-fuchsia-900 via-purple-800 to-brand-900',
            'surface' => 'border-fuchsia-200 dark:border-fuchsia-900/60',
            'muted' => 'bg-fuchsia-50 dark:bg-fuchsia-950/30 text-fuchsia-900 dark:text-fuchsia-100',
        ],
        'portal' => [
            'button' => 'bg-brand-600 hover:bg-brand-500 text-white shadow-brand-950/35',
            'header' => 'from-emerald-900 via-emerald-800 to-teal-800',
            'surface' => 'border-emerald-200 dark:border-emerald-900/60',
            'muted' => 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-900 dark:text-emerald-100',
        ],
        default => [
            'button' => 'bg-brand-600 hover:bg-brand-500 text-white shadow-brand-950/35',
            'header' => 'from-sky-900 via-brand-800 to-brand-700',
            'surface' => 'border-sky-200 dark:border-sky-900/60',
            'muted' => 'bg-sky-50 dark:bg-sky-950/30 text-sky-900 dark:text-sky-100',
        ],
    };
@endphp

@if($guide)
<section x-data="{ open: false }"
         @keydown.escape.window="open = false">

    <template x-teleport="body">
        <div class="fixed z-[260] {{ $position['button'] }}">
            <button type="button"
                    x-show="!open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2 scale-90"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-90"
                    @click="open = true"
                    class="group relative inline-flex h-14 w-14 cursor-pointer items-center justify-center rounded-full border-[3px] border-white dark:border-gray-950 shadow-2xl transition-all duration-200 hover:scale-110 active:scale-95 {{ $theme['button'] }}"
                    aria-label="Buka panduan halaman">
                <span class="absolute inset-0 rounded-full bg-white/18 opacity-0 transition-opacity duration-200 group-hover:opacity-100"></span>
                <span class="absolute -inset-1 rounded-full border border-brand-300/40"></span>
                <span class="relative -mt-[1px] text-[2.2rem] font-black leading-none text-white drop-shadow-sm">?</span>
            </button>
        </div>

        <div x-show="open"
             x-cloak
             x-transition.opacity
             class="fixed inset-0 z-[261]">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]" @click="open = false"></div>

            <div class="absolute {{ $position['panel'] }}">
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-[0.98]"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-3 scale-[0.98]"
                     @click.stop
                     class="overflow-hidden rounded-[2rem] border bg-white shadow-2xl dark:bg-gray-900 {{ $theme['surface'] }} max-h-[calc(100vh-1.5rem)] sm:max-h-[min(46rem,calc(100vh-2.5rem))]">

                    <div class="bg-gradient-to-br {{ $theme['header'] }} px-5 py-5 text-white sm:px-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white/95">
                                        <span aria-hidden="true">{{ $guide['group_icon'] ?? 'ℹ️' }}</span>
                                        {{ $guide['group_label'] ?? 'Panduan' }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-black/20 px-3 py-1 text-[11px] font-semibold text-white/95">
                                        Help halaman ini
                                    </span>
                                </div>
                                <h2 class="mt-3 text-lg font-extrabold leading-tight text-white">{{ $guide['title'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-white/95">{{ $guide['summary'] }}</p>
                            </div>

                            <button type="button"
                                    @click="open = false"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-lg text-white hover:bg-white/25 transition-colors"
                                    aria-label="Tutup panduan">
                                ×
                            </button>
                        </div>
                    </div>

                    <div class="max-h-[calc(100vh-14rem)] overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                        @if(!empty($guide['steps']))
                        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 p-4">
                            <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">Langkah Cepat</h3>
                            <ol class="mt-3 space-y-3">
                                @foreach($guide['steps'] as $index => $step)
                                <li class="flex gap-3 rounded-2xl {{ $theme['muted'] }} px-4 py-3 text-sm leading-6">
                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/85 text-[11px] font-black text-gray-900">{{ $index + 1 }}</span>
                                    <span>{{ $step }}</span>
                                </li>
                                @endforeach
                            </ol>
                        </div>
                        @endif

                        <div class="mt-4 grid gap-4">
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/60 p-4">
                                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">Fitur Utama</h3>
                                <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-800 dark:text-gray-100">
                                    @foreach($guide['features'] as $feature)
                                    <li class="flex gap-2">
                                        <span class="mt-1 text-brand-600 dark:text-brand-300">•</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>

                            @if(!empty($guide['tips']))
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-amber-50/90 dark:bg-amber-950/20 p-4">
                                <h3 class="text-sm font-extrabold text-amber-950 dark:text-amber-100">Tips dan Catatan</h3>
                                <ul class="mt-3 space-y-2 text-sm leading-6 text-amber-950/90 dark:text-amber-50">
                                    @foreach($guide['tips'] as $tip)
                                    <li class="flex gap-2">
                                        <span class="mt-1 text-amber-600 dark:text-amber-300">•</span>
                                        <span>{{ $tip }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</section>
@endif
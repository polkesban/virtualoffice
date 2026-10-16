@php
    $materials = $materials ?? collect();
@endphp

@if($materials->isNotEmpty())
    <section class="mt-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Materi Event</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Materi dapat dibuka langsung, disematkan, atau diunduh sesuai pengaturan admin.</p>
            </div>
        </div>

        <div class="mt-5 space-y-4">
            @foreach($materials as $material)
                <article class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="p-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ \App\Models\UnitEventMaterial::$tipeLabels[$material->tipe] ?? $material->tipe }}</span>
                                    @if($material->downloadable)
                                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300">Bisa diunduh</span>
                                    @endif
                                </div>
                                <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">{{ $material->judul }}</h3>
                                @if($material->deskripsi)
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $material->deskripsi }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if($material->tipe === 'file' && $material->file_path)
                                    <a href="{{ $material->downloadable ? \App\Support\MediaStorage::downloadUrl($material->file_path) : \App\Support\MediaStorage::url($material->file_path) }}" target="_blank" @if($material->downloadable) download @endif class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                        {{ $material->downloadable ? 'Unduh Materi' : 'Buka File' }}
                                    </a>
                                @elseif($material->tipe === 'link' && $material->link_url)
                                    <a href="{{ $material->link_url }}" target="_blank" rel="noopener" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                        Buka Tautan
                                    </a>
                                @elseif($material->tipe === 'embed' && $material->embed_url)
                                    <a href="{{ $material->embed_url }}" target="_blank" rel="noopener" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                        Buka Sumber
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($material->tipe === 'embed' && $material->embed_url)
                            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800">
                                <iframe src="{{ $material->embed_url }}" class="h-[360px] w-full" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif@php
    $materials = $materials ?? collect();
@endphp

@if($materials->isNotEmpty())
    <section class="mt-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Materi Event</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Materi dapat dibuka langsung, disematkan, atau diunduh sesuai pengaturan admin.</p>
            </div>
        </div>

        <div class="mt-5 space-y-4">
            @foreach($materials as $material)
                <article class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="p-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ \App\Models\UnitEventMaterial::$tipeLabels[$material->tipe] ?? $material->tipe }}</span>
                                    @if($material->downloadable)
                                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300">Bisa diunduh</span>
                                    @endif
                                </div>
                                <h3 class="mt-3 text-lg font-extrabold text-gray-900 dark:text-white">{{ $material->judul }}</h3>
                                @if($material->deskripsi)
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $material->deskripsi }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if($material->tipe === 'file' && $material->file_path)
                                    <a href="{{ $material->downloadable ? \App\Support\MediaStorage::downloadUrl($material->file_path) : \App\Support\MediaStorage::url($material->file_path) }}" target="_blank" @if($material->downloadable) download @endif class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                        {{ $material->downloadable ? 'Unduh Materi' : 'Buka File' }}
                                    </a>
                                @elseif($material->tipe === 'link' && $material->link_url)
                                    <a href="{{ $material->link_url }}" target="_blank" rel="noopener" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                        Buka Tautan
                                    </a>
                                @elseif($material->tipe === 'embed' && $material->embed_url)
                                    <a href="{{ $material->embed_url }}" target="_blank" rel="noopener" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                                        Buka Sumber
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($material->tipe === 'embed' && $material->embed_url)
                            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800">
                                <iframe src="{{ $material->embed_url }}" class="h-[360px] w-full" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
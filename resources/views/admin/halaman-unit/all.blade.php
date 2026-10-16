@extends('admin.layouts.admin')
@section('title', 'Semua Halaman Unit')
@section('content')
<div class="p-6 max-w-5xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🗂️ Halaman Unit</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola halaman publik masing-masing unit kerja</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.halaman-unit.create-page') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            ➕ Buat Halaman Baru
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @php
        $pegawaiDosenPages = $pages->filter(fn($page) => $page->isPegawaiOrDosenPage());

        $jurusanProdiPages = $pages->filter(fn($page) => $page->isJurusanOrProdiPage());

        $ormawaPages = $pages->filter(fn($page) => $page->isPortalMahasiswaPage());

        $direktoratPages = $pages->filter(fn($page) => $page->isDirektoratOrUnitKerjaPage());

        // Separate jurusan pages and prodi pages for nested view
        $jurusanPages = $jurusanProdiPages->filter(function($page) {
            return str_starts_with($page->role, 'jurusan_');
        });

        $prodiPages = $jurusanProdiPages->filter(function($page) {
            return !str_starts_with($page->role, 'jurusan_');
        });

        // Separate sub-pages for P3
        $subP3Pages = $pages->filter(function($page) {
            return str_starts_with($page->role, 'sub_p3_');
        });

        $jurusanMap = [
            1 => 'jurusan_kep',
            2 => 'jurusan_keb',
            3 => 'jurusan_gizi',
            4 => 'jurusan_kesling',
            5 => 'jurusan_kesgigi',
            6 => 'jurusan_tlm',
            7 => 'jurusan_farm',
            10 => 'jurusan_promkes',
        ];
    @endphp

    <div x-data="{ activeTab: 'direktorat' }" class="mt-6">
        <!-- Tabs Header -->
        <div class="flex border-b border-gray-200 dark:border-gray-800 mb-6 overflow-x-auto pb-px gap-1">
            <button @click="activeTab = 'direktorat'"
                    :class="activeTab === 'direktorat' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="py-3 px-5 border-b-2 font-medium text-sm transition-colors focus:outline-none flex items-center gap-2 shrink-0">
                🏢 Direktorat & Unit Kerja
                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-semibold">{{ $direktoratPages->count() }}</span>
            </button>
            <button @click="activeTab = 'jurusan_prodi'"
                    :class="activeTab === 'jurusan_prodi' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="py-3 px-5 border-b-2 font-medium text-sm transition-colors focus:outline-none flex items-center gap-2 shrink-0">
                🎓 Jurusan & Prodi
                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-semibold">{{ $jurusanProdiPages->count() }}</span>
            </button>
            <button @click="activeTab = 'ormawa'"
                    :class="activeTab === 'ormawa' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="py-3 px-5 border-b-2 font-medium text-sm transition-colors focus:outline-none flex items-center gap-2 shrink-0">
                👥 Ormawa (BEM & HIMA)
                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-semibold">{{ $ormawaPages->count() }}</span>
            </button>
            <button @click="activeTab = 'pegawai_dosen'"
                    :class="activeTab === 'pegawai_dosen' ? 'border-brand-600 text-brand-600 dark:text-brand-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="py-3 px-5 border-b-2 font-medium text-sm transition-colors focus:outline-none flex items-center gap-2 shrink-0">
                👨‍💼 Profil Dosen & Pegawai
                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-semibold">{{ $pegawaiDosenPages->count() }}</span>
            </button>
        </div>

        <!-- Tab Content: Direktorat -->
        <div x-show="activeTab === 'direktorat'">
            @if($direktoratPages->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">Tidak ada halaman direktorat/unit kerja.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($direktoratPages as $page)
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="h-2" style="background-color: {{ $page->warna_tema }}"></div>
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $page->judul }}</h3>
                                    @if($page->sub_judul)<p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $page->sub_judul }}</p>@endif
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold {{ $page->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $page->aktif ? 'Live' : 'Draft' }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-400">{{ $page->sections_count }} blok</span>
                                <div class="flex gap-2 items-center">
                                    <a href="{{ $page->publicUrl() }}" target="_blank"
                                       class="text-xs text-gray-400 hover:text-brand-600 transition-colors">↗ Lihat</a>
                                    <a href="{{ route('admin.halaman-unit.manage', $page) }}"
                                       class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-600 hover:bg-brand-100 transition-colors">Kelola</a>
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.halaman-unit.destroy-page', $page) }}" method="POST"
                                          onsubmit="return confirm('Hapus halaman {{ addslashes($page->judul) }} beserta semua bloknya?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2 py-1 rounded-lg text-xs bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Hapus halaman">🗑️</button>
                                    </form>
                                    @endif
                                </div>
                            </div>

                            {{-- Nest P3 sub-pages inside P3 center page --}}
                            @if($page->role === 'pusat_p3' && $subP3Pages->isNotEmpty())
                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2 text-left">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Sub-Layanan:</span>
                                <div class="space-y-1.5">
                                    @foreach($subP3Pages as $subPage)
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-950 text-xs border border-brand-300/60 dark:border-brand-800/60">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $subPage->judul }}</span>
                                        <div class="flex gap-2 items-center">
                                            <a href="{{ $subPage->publicUrl() }}" target="_blank" class="text-[10px] text-gray-400 hover:text-brand-600 transition-colors">↗ Lihat</a>
                                            <a href="{{ route('admin.halaman-unit.manage', $subPage) }}" class="px-2 py-0.5 rounded text-[10px] font-bold bg-brand-50 text-brand-600 hover:bg-brand-100 transition-colors">Kelola</a>
                                            @if(auth()->user()->isSuperAdmin())
                                            <form action="{{ route('admin.halaman-unit.destroy-page', $subPage) }}" method="POST"
                                                  onsubmit="return confirm('Hapus sub-halaman {{ addslashes($subPage->judul) }} beserta semua bloknya?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600" title="Hapus">🗑️</button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Tab Content: Jurusan / Prodi -->
        <div x-show="activeTab === 'jurusan_prodi'" x-cloak>
            @if($jurusanProdiPages->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">Tidak ada halaman jurusan/prodi.</p>
            @else
                <div class="space-y-6">
                    @foreach($jurusanPages as $jurusanPage)
                        @php
                            $childProdis = $prodiPages->filter(function($prodiPage) use ($jurusanPage, $jurusanMap) {
                                $jId = $prodiPage->programStudi?->jurusan_id;
                                return ($jurusanMap[$jId] ?? null) === $jurusanPage->role;
                            });
                        @endphp
                        <div class="bg-gray-50 dark:bg-gray-950 p-5 rounded-2xl border border-gray-150 dark:border-gray-850">
                            <!-- Jurusan Page Header Card -->
                            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3 mb-4">
                                <div>
                                    <h2 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                        📁 {{ $jurusanPage->judul }}
                                    </h2>
                                    @if($jurusanPage->sub_judul)<p class="text-xs text-gray-400 mt-0.5">{{ $jurusanPage->sub_judul }}</p>@endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold {{ $jurusanPage->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }} mr-2">
                                        {{ $jurusanPage->aktif ? 'Live' : 'Draft' }}
                                    </span>
                                    <a href="{{ $jurusanPage->publicUrl() }}" target="_blank" class="text-xs text-gray-400 hover:text-brand-600 transition-colors">↗ Lihat</a>
                                    <a href="{{ route('admin.halaman-unit.manage', $jurusanPage) }}" class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-600 hover:bg-brand-100 transition-colors">Kelola Jurusan</a>
                                </div>
                            </div>

                            <!-- Child Prodi Grid -->
                            @if($childProdis->isEmpty())
                                <p class="text-xs text-gray-400 italic">Belum ada halaman prodi di bawah jurusan ini.</p>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($childProdis as $page)
                                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-brand-300/60 dark:border-brand-800/60 p-4 hover:shadow-sm transition-shadow">
                                            <div class="flex items-start justify-between gap-2">
                                                <div>
                                                    <h3 class="font-bold text-gray-900 dark:text-white text-xs">{{ $page->judul }}</h3>
                                                    @if($page->sub_judul)<p class="text-[10px] text-gray-400 mt-0.5 line-clamp-1">{{ $page->sub_judul }}</p>@endif
                                                </div>
                                                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $page->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                    {{ $page->aktif ? 'Live' : 'Draft' }}
                                                </span>
                                            </div>
                                            <div class="mt-3 flex items-center justify-between">
                                                <span class="text-[10px] text-gray-400">{{ $page->sections_count }} blok</span>
                                                <div class="flex gap-2 items-center">
                                                    <a href="{{ $page->publicUrl() }}" target="_blank" class="text-[10px] text-gray-400 hover:text-brand-600 transition-colors">↗ Lihat</a>
                                                    <a href="{{ route('admin.halaman-unit.manage', $page) }}" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-brand-50 text-brand-600 hover:bg-brand-100 transition-colors">Kelola</a>
                                                    @if(auth()->user()->isSuperAdmin())
                                                    <form action="{{ route('admin.halaman-unit.destroy-page', $page) }}" method="POST" onsubmit="return confirm('Hapus halaman {{ addslashes($page->judul) }} beserta semua bloknya?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="px-1.5 py-0.5 rounded text-[10px] bg-red-50 text-red-500 hover:bg-red-100 transition-colors">🗑️</button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Unmapped/Orphan Prodis -->
                    @php
                        $orphanProdis = $prodiPages->filter(function($prodiPage) use ($jurusanMap) {
                            $jId = $prodiPage->programStudi?->jurusan_id;
                            return !isset($jurusanMap[$jId]);
                        });
                    @endphp
                    @if($orphanProdis->isNotEmpty())
                        <div class="bg-gray-50 dark:bg-gray-950 p-5 rounded-2xl border border-gray-150 dark:border-gray-850">
                            <div class="border-b border-gray-200 dark:border-gray-800 pb-3 mb-4">
                                <h2 class="text-base font-extrabold text-gray-900 dark:text-white">❓ Lainnya / Tanpa Jurusan</h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($orphanProdis as $page)
                                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-brand-300/60 dark:border-brand-800/60 p-4 hover:shadow-sm transition-shadow">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h3 class="font-bold text-gray-900 dark:text-white text-xs">{{ $page->judul }}</h3>
                                                @if($page->sub_judul)<p class="text-[10px] text-gray-400 mt-0.5 line-clamp-1">{{ $page->sub_judul }}</p>@endif
                                            </div>
                                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $page->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $page->aktif ? 'Live' : 'Draft' }}
                                            </span>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between">
                                            <span class="text-[10px] text-gray-400">{{ $page->sections_count }} blok</span>
                                            <div class="flex gap-2 items-center">
                                                <a href="{{ $page->publicUrl() }}" target="_blank" class="text-[10px] text-gray-400 hover:text-brand-600 transition-colors">↗ Lihat</a>
                                                <a href="{{ route('admin.halaman-unit.manage', $page) }}" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-brand-50 text-brand-600 hover:bg-brand-100 transition-colors">Kelola</a>
                                                @if(auth()->user()->isSuperAdmin())
                                                <form action="{{ route('admin.halaman-unit.destroy-page', $page) }}" method="POST" onsubmit="return confirm('Hapus halaman {{ addslashes($page->judul) }} beserta semua bloknya?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="px-1.5 py-0.5 rounded text-[10px] bg-red-50 text-red-500 hover:bg-red-100 transition-colors">🗑️</button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Tab Content: Ormawa -->
        <div x-show="activeTab === 'ormawa'" x-cloak>
            @if($ormawaPages->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">Tidak ada halaman Ormawa (BEM/HIMA).</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($ormawaPages as $page)
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="h-2" style="background-color: {{ $page->warna_tema }}"></div>
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $page->judul }}</h3>
                                    @if($page->sub_judul)<p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $page->sub_judul }}</p>@endif
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold {{ $page->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $page->aktif ? 'Live' : 'Draft' }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-400">{{ $page->sections_count }} blok</span>
                                <div class="flex gap-2 items-center">
                                    <a href="{{ $page->publicUrl() }}" target="_blank"
                                       class="text-xs text-gray-400 hover:text-brand-600 transition-colors">↗ Lihat</a>
                                    <a href="{{ route('admin.halaman-unit.manage', $page) }}"
                                       class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-600 hover:bg-brand-100 transition-colors">Kelola</a>
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.halaman-unit.destroy-page', $page) }}" method="POST"
                                          onsubmit="return confirm('Hapus halaman {{ addslashes($page->judul) }} beserta semua bloknya?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2 py-1 rounded-lg text-xs bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Hapus halaman">🗑️</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Tab Content: Profil Dosen & Pegawai -->
        <div x-show="activeTab === 'pegawai_dosen'" x-data="{ searchQuery: '' }" x-cloak>
            <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50 dark:bg-gray-800/60 p-3.5 rounded-2xl border border-gray-100 dark:border-gray-800">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Halaman profil portofolio individu dosen dan tenaga kependidikan Polkesban.
                </div>
                <div class="w-full sm:w-72">
                    <input type="text" x-model="searchQuery" placeholder="🔍 Cari nama dosen / pegawai..." 
                           class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-sm">
                </div>
            </div>

            @if($pegawaiDosenPages->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">Tidak ada halaman profil dosen / pegawai.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($pegawaiDosenPages as $page)
                    <div x-show="searchQuery === '' || '{{ strtolower(addslashes($page->judul . ' ' . $page->sub_judul)) }}'.includes(searchQuery.toLowerCase())"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="h-2" style="background-color: {{ $page->warna_tema }}"></div>
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $page->judul }}</h3>
                                    @if($page->sub_judul)<p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $page->sub_judul }}</p>@endif
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold {{ $page->aktif ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $page->aktif ? 'Live' : 'Draft' }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-400">{{ $page->sections_count }} blok</span>
                                <div class="flex gap-2 items-center">
                                    <a href="{{ $page->publicUrl() }}" target="_blank"
                                       class="text-xs text-gray-400 hover:text-brand-600 transition-colors">↗ Lihat</a>
                                    <a href="{{ route('admin.halaman-unit.manage', $page) }}"
                                       class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-600 hover:bg-brand-100 transition-colors">Kelola</a>
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.halaman-unit.destroy-page', $page) }}" method="POST"
                                          onsubmit="return confirm('Hapus halaman profil {{ addslashes($page->judul) }} beserta semua bloknya?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2 py-1 rounded-lg text-xs bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Hapus halaman">🗑️</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

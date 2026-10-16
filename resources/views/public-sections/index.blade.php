@extends('layouts.app')

@section('title', $pageTitle)
@section('description', $pageDescription)

@section('content')
<style>
    .glassmorphic-article {
        background-color: rgba(255, 255, 255, 0.76) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
    }
    .dark .glassmorphic-article {
        background-color: rgba(30, 41, 59, 0.85) !important;
    }
    .glassmorphic-header {
        background-color: rgba(15, 118, 110, 0.3) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-bottom: 1px solid rgba(15, 118, 110, 0.15);
    }
</style>

<div x-data="{
    showModal: false,
    selectedRole: '',
    selectedTitle: '',
    selectedService: '',
    userSubject: '',
    searchQuery: '',
    activeCategory: 'all',
    itemsData: {{ json_encode(method_exists($items, 'items') ? $items->items() : $items) }},
    viewMode: localStorage.getItem('unit_view_mode') || 'grid',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('unit_view_mode', mode);
    },
    setCategory(cat) {
        this.activeCategory = cat;
    },
    get filteredItems() {
        let list = this.itemsData;
        if (this.activeCategory !== 'all') {
            list = list.filter(item => item.category === this.activeCategory);
        }
        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            list = list.filter(item => 
                (item.title && item.title.toLowerCase().includes(query)) || 
                (item.subtitle && item.subtitle.toLowerCase().includes(query)) ||
                (item.category && item.category.toLowerCase().includes(query))
            );
        }
        return list;
    },
    get filteredBidang() {
        return this.filteredItems.filter(item => item.category === 'bidang');
    },
    get filteredUnit() {
        return this.filteredItems.filter(item => item.category === 'unit');
    },
    get filteredJurusans() {
        return this.filteredItems.filter(item => item.category === 'jurusan');
    },
    get filteredProdis() {
        return this.filteredItems.filter(item => item.category === 'prodi');
    },
    get filteredPegawai() {
        return this.filteredItems.filter(item => item.category === 'pegawai');
    },
    get filteredMahasiswa() {
        return this.filteredItems.filter(item => item.category === 'mahasiswa');
    },
    prodiToJurusanMap: {
        'kaprodi_1': 'jurusan_kep',
        'kaprodi_2': 'jurusan_keb',
        'kaprodi_3': 'jurusan_gizi',
        'kaprodi_5': 'jurusan_kesling',
        'kaprodi_6': 'jurusan_tlm',
        'kaprodi_7': 'jurusan_farm',
        'kaprodi_9': 'jurusan_promkes',
        'kaprodi_10': 'jurusan_tlm',
        'kaprodi_11': 'jurusan_kesgigi',
        'kaprodi_12': 'jurusan_kep',
        'kaprodi_13': 'jurusan_kep',
        'kaprodi_14': 'jurusan_keb',
        'kaprodi_15': 'jurusan_keb',
        'kaprodi_16': 'jurusan_keb',
        'kaprodi_17': 'jurusan_kesgigi',
        'kaprodi_18': 'jurusan_kesling',
        'kaprodi_19': 'jurusan_kep',
        'kaprodi_20': 'jurusan_keb',
        'kaprodi_21': 'jurusan_gizi',
        'kaprodi_22': 'jurusan_gizi'
    },
    getFilteredProdisForJurusan(jurusanRole) {
        return this.itemsData.filter(item => 
            item.category === 'prodi' && (item.jurusan_role === jurusanRole || this.prodiToJurusanMap[item.role] === jurusanRole)
        );
    },
    get countAll() { return this.itemsData.length; },
    get countBidang() { return this.itemsData.filter(i => i.category === 'bidang').length; },
    get countUnit() { return this.itemsData.filter(i => i.category === 'unit').length; },
    get countJurusan() { return this.itemsData.filter(i => i.category === 'jurusan').length; },
    get countProdi() { return this.itemsData.filter(i => i.category === 'prodi').length; },
    get countPegawai() { return this.itemsData.filter(i => i.category === 'pegawai').length; },
    get countMahasiswa() { return this.itemsData.filter(i => i.category === 'mahasiswa').length; },
    services: {{ json_encode(App\Models\UnitPage::tupoksiServices()) }},
    get currentServices() {
        let role = this.selectedRole;
        if (role.startsWith('kaprodi')) {
            role = 'kaprodi';
        }
        return this.services[role] || ['Lainnya'];
    },
    openTupoksiModal(role, title) {
        this.selectedRole = role;
        this.selectedTitle = title;
        let roleKey = role.startsWith('kaprodi') ? 'kaprodi' : role;
        this.selectedService = this.services[roleKey] ? this.services[roleKey][0] : 'Lainnya';
        this.userSubject = '';
        this.showModal = true;
    },
    prodiRoleMap: {
        'kaprodi_1': 'prodi_keperawatan',
        'kaprodi_2': 'prodi_kebidanan',
        'kaprodi_3': 'prodi_gizi',
        'kaprodi_5': 'prodi_kesling',
        'kaprodi_6': 'prodi_tlm',
        'kaprodi_7': 'prodi_farmasi',
        'kaprodi_9': 'prodi_promkes',
        'kaprodi_10': 'prodi_tlm',
        'kaprodi_11': 'prodi_kesgi',
        'kaprodi_12': 'prodi_keperawatan',
        'kaprodi_13': 'prodi_keperawatan',
        'kaprodi_14': 'prodi_kebidanan',
        'kaprodi_15': 'prodi_kebidanan',
        'kaprodi_16': 'prodi_kebidanan',
        'kaprodi_17': 'prodi_kesgi',
        'kaprodi_18': 'prodi_kesling',
        'kaprodi_19': 'prodi_keperawatan',
        'kaprodi_20': 'prodi_kebidanan',
        'kaprodi_21': 'prodi_gizi',
        'kaprodi_22': 'prodi_gizi',
        'kaprodi': 'prodi_kebidanan'
    },
    mapRoleToTujuan(role) {
        return this.prodiRoleMap[role] || role;
    }
}" @keydown.escape.window="showModal = false" class="relative">

@php
    $totalItems = method_exists($items, 'total') ? $items->total() : $items->count();
    $loggedInName = '';
    $loggedInEmail = '';
    $loggedInPhone = '';
    
    if (auth()->guard('mahasiswa')->check()) {
        $loggedInName = auth()->guard('mahasiswa')->user()->name;
        $loggedInEmail = auth()->guard('mahasiswa')->user()->email;
        $loggedInPhone = auth()->guard('mahasiswa')->user()->no_hp;
    } elseif (auth()->check()) {
        $loggedInName = auth()->user()->name;
        $loggedInEmail = auth()->user()->email;
    }
@endphp

<!-- Header Section -->
<section class="glassmorphic-header relative z-10 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-3 flex text-sm text-teal-800 dark:text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-teal-950 dark:hover:text-white">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-teal-950 dark:text-white" aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </nav>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-teal-800 dark:text-brand-200">{{ $pageEyebrow }}</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-teal-950 dark:text-white sm:text-4xl">{{ $pageTitle }}</h1>
                <p class="mt-3 text-sm leading-6 text-teal-850 dark:text-brand-100 sm:text-base">{{ $pageDescription }}</p>
            </div>
            <div class="inline-flex w-fit items-center gap-3 rounded-2xl border border-teal-600/20 dark:border-white/15 bg-teal-600/10 dark:bg-white/10 px-5 py-4 text-teal-950 dark:text-white backdrop-blur-sm">
                <span class="text-2xl font-black" x-text="filteredItems.length"></span>
                <span class="text-sm font-semibold text-teal-800 dark:text-brand-100">item ditampilkan</span>
            </div>
        </div>
    </div>
</section>

<!-- Main Body Section -->
<section class="bg-teal-50/20 dark:bg-gray-950/50 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($pageTitle === 'Layanan')
            <!-- Category Tabs (Filter Chips) -->
            <div class="mb-8 overflow-x-auto pb-2 scrollbar-thin">
                <div class="flex items-center gap-2 min-w-max">
                    <button type="button" 
                            @click="setCategory('all')"
                            :class="activeCategory === 'all' ? 'bg-brand-600 text-white font-extrabold shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold transition-all cursor-pointer">
                        <span>🌟 Semua</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeCategory === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" x-text="countAll"></span>
                    </button>

                    <button type="button" 
                            @click="setCategory('bidang')"
                            :class="activeCategory === 'bidang' ? 'bg-blue-600 text-white font-extrabold shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold transition-all cursor-pointer">
                        <span>🏢 Bidang & Direktorat</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeCategory === 'bidang' ? 'bg-white/20 text-white' : 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300'" x-text="countBidang"></span>
                    </button>

                    <button type="button" 
                            @click="setCategory('unit')"
                            :class="activeCategory === 'unit' ? 'bg-teal-600 text-white font-extrabold shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold transition-all cursor-pointer">
                        <span>⚙️ Unit & Pusat</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeCategory === 'unit' ? 'bg-white/20 text-white' : 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300'" x-text="countUnit"></span>
                    </button>

                    <button type="button" 
                            @click="setCategory('jurusan')"
                            :class="activeCategory === 'jurusan' ? 'bg-purple-600 text-white font-extrabold shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold transition-all cursor-pointer">
                        <span>🏛️ Jurusan</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeCategory === 'jurusan' ? 'bg-white/20 text-white' : 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300'" x-text="countJurusan"></span>
                    </button>

                    <button type="button" 
                            @click="setCategory('prodi')"
                            :class="activeCategory === 'prodi' ? 'bg-amber-600 text-white font-extrabold shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold transition-all cursor-pointer">
                        <span>🎓 Program Studi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeCategory === 'prodi' ? 'bg-white/20 text-white' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300'" x-text="countProdi"></span>
                    </button>

                    <button type="button" 
                            @click="setCategory('pegawai')"
                            :class="activeCategory === 'pegawai' ? 'bg-indigo-600 text-white font-extrabold shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold transition-all cursor-pointer">
                        <span>👤 Pegawai & Dosen</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeCategory === 'pegawai' ? 'bg-white/20 text-white' : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300'" x-text="countPegawai"></span>
                    </button>

                    <button type="button" 
                            @click="setCategory('mahasiswa')"
                            :class="activeCategory === 'mahasiswa' ? 'bg-rose-600 text-white font-extrabold shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold transition-all cursor-pointer">
                        <span>👥 Mahasiswa & ORMAWA</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeCategory === 'mahasiswa' ? 'bg-white/20 text-white' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300'" x-text="countMahasiswa"></span>
                    </button>
                </div>
            </div>

            <!-- Toolbar: Search Bar + View Mode Switcher -->
            <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Search bar -->
                <div class="w-full md:max-w-md">
                    <div class="relative">
                        <input type="text" x-model="searchQuery" placeholder="Cari nama bidang, unit, jurusan, prodi, pegawai, atau ORMAWA..."
                               class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-3.5 pl-12 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-sm transition-all placeholder-gray-400">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-gray-400">
                            🔍
                        </div>
                        <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute inset-y-0 right-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-white text-xs font-bold cursor-pointer">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- View Mode Switcher -->
                <div class="flex items-center gap-1.5 self-start md:self-auto bg-white dark:bg-gray-800 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <button type="button" 
                            @click="switchViewMode('grid')"
                            :class="viewMode === 'grid' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60 font-semibold'"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs transition-all cursor-pointer">
                        <span>🧱</span> Tampilan Grid
                    </button>
                    <button type="button" 
                            @click="switchViewMode('list')"
                            :class="viewMode === 'list' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60 font-semibold'"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs transition-all cursor-pointer">
                        <span>📋</span> Tampilan List
                    </button>
                </div>
            </div>

            <!-- ===================== GRID VIEW MODE ===================== -->
            <div x-show="viewMode === 'grid'" class="space-y-16">
                
                <!-- 1. Bidang Section -->
                <div class="mb-14" x-show="filteredBidang.length > 0">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 border-gray-200 dark:border-gray-800 font-sans">
                        <span>🏢</span> Bidang & Direktorat
                    </h2>
                    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        <template x-for="item in filteredBidang" :key="item.title">
                            <article class="glassmorphic-article flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200/20 dark:border-gray-800/30 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg">
                                <div x-show="item.image" class="aspect-[16/10] overflow-hidden bg-gray-100 dark:bg-gray-800">
                                    <img :src="item.image" :alt="item.image_alt || item.title" class="h-full w-full object-cover">
                                </div>
                                <div class="flex h-full flex-col p-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-950/40 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-blue-700 dark:text-blue-300">BIDANG</span>
                                    </div>
                                    <h3 class="mt-4 text-xl font-black leading-tight text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    <p x-show="item.subtitle" class="mt-2 text-sm font-semibold text-blue-700 dark:text-blue-400 font-sans" x-text="item.subtitle"></p>

                                    <div class="mt-auto pt-6 flex flex-wrap gap-3 border-t border-gray-100 dark:border-gray-800/40">
                                        <a :href="'/unit/' + item.slug"
                                           class="inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 font-sans">
                                            Buka Halaman
                                        </a>
                                        <button type="button"
                                                @click="openTupoksiModal(item.role, item.title)"
                                                class="inline-flex items-center justify-center rounded-full bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500 shadow-sm cursor-pointer font-sans">
                                            Ajukan Layanan
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>

                <!-- 2. Unit & Pusat Section -->
                <div class="mb-14" x-show="filteredUnit.length > 0">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 border-gray-200 dark:border-gray-800 font-sans">
                        <span>⚙️</span> Unit Pelayanan & Pusat Pengelola
                    </h2>
                    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        <template x-for="item in filteredUnit" :key="item.title">
                            <article class="glassmorphic-article flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200/20 dark:border-gray-800/30 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg">
                                <div x-show="item.image" class="aspect-[16/10] overflow-hidden bg-gray-100 dark:bg-gray-800">
                                    <img :src="item.image" :alt="item.image_alt || item.title" class="h-full w-full object-cover">
                                </div>
                                <div class="flex h-full flex-col p-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full bg-teal-50 dark:bg-teal-950/40 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-teal-700 dark:text-teal-300">UNIT & PUSAT</span>
                                    </div>
                                    <h3 class="mt-4 text-xl font-black leading-tight text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    <p x-show="item.subtitle" class="mt-2 text-sm font-semibold text-teal-700 dark:text-teal-400 font-sans" x-text="item.subtitle"></p>

                                    <div class="mt-auto pt-6 flex flex-wrap gap-3 border-t border-gray-100 dark:border-gray-800/40">
                                        <a :href="'/unit/' + item.slug"
                                           class="inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 font-sans">
                                            Buka Halaman
                                        </a>
                                        <button type="button"
                                                @click="openTupoksiModal(item.role, item.title)"
                                                class="inline-flex items-center justify-center rounded-full bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500 shadow-sm cursor-pointer font-sans">
                                            Ajukan Layanan
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>

                <!-- 3. Jurusan Section -->
                <div class="mb-14" x-show="filteredJurusans.length > 0">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 border-gray-200 dark:border-gray-800 font-sans">
                        <span>🏛️</span> Jurusan
                    </h2>
                    <div class="grid gap-8 md:grid-cols-2">
                        <template x-for="jurusan in filteredJurusans" :key="jurusan.title">
                            <div class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-200 dark:border-gray-800 shadow-sm p-6 flex flex-col gap-6"
                                 :style="jurusan.warna_tema ? ($store.theme.dark ? 'background-color: color-mix(in srgb, ' + jurusan.warna_tema + ' 12%, #1e293b); border-color: color-mix(in srgb, ' + jurusan.warna_tema + ' 30%, #334155)' : 'background-color: ' + jurusan.warna_tema + '0d; border-color: ' + jurusan.warna_tema + '26') : ''">
                                <!-- Jurusan Header -->
                                <div class="flex items-start gap-4">
                                    <div x-show="jurusan.image" class="h-16 w-16 rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-800 shrink-0 border border-gray-200/60 dark:border-gray-800/40">
                                        <img :src="jurusan.image" :alt="jurusan.image_alt || jurusan.title" class="h-full w-full object-cover">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-lg font-black text-gray-900 dark:text-white font-sans" x-text="jurusan.title"></h3>
                                        <p x-show="jurusan.subtitle" class="text-xs font-semibold text-brand-700 dark:text-brand-400 mt-1 font-sans" x-text="jurusan.subtitle"></p>
                                        <div class="mt-3 flex gap-2">
                                            <a :href="'/unit/' + jurusan.slug" class="inline-flex h-8 items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-3.5 text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 font-sans">
                                                Buka Jurusan
                                            </a>
                                            <button type="button" 
                                                    @click="openTupoksiModal(jurusan.role, jurusan.title)" 
                                                    class="inline-flex h-8 items-center justify-center rounded-full bg-brand-600 px-3.5 py-1.5 text-xs font-bold text-white transition-colors hover:bg-brand-500 shadow-sm cursor-pointer font-sans">
                                                Layanan
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Program Studi Lists inside this Jurusan -->
                                <div class="border-t border-gray-100 dark:border-gray-800/60 pt-5" x-show="getFilteredProdisForJurusan(jurusan.role).length > 0">
                                    <h4 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3.5">Daftar Program Studi</h4>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <template x-for="prodi in getFilteredProdisForJurusan(jurusan.role)" :key="prodi.title">
                                            <div class="p-4 rounded-2xl bg-gray-50/50 dark:bg-gray-700/60 border border-gray-100 dark:border-gray-800 flex flex-col justify-between gap-4">
                                                <div class="min-w-0">
                                                    <h5 class="text-sm font-extrabold text-gray-950 dark:text-white font-sans" x-text="prodi.title"></h5>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-sans" x-text="prodi.subtitle"></p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <a :href="'/unit/' + prodi.slug" class="inline-flex h-9 items-center justify-center rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750 font-sans">
                                                        Buka
                                                    </a>
                                                    <button type="button" 
                                                            @click="openTupoksiModal(prodi.role, prodi.title)" 
                                                            class="inline-flex h-9 items-center justify-center rounded-xl bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:hover:bg-brand-950/60 px-4 text-xs font-bold text-brand-700 dark:text-brand-400 font-sans">
                                                        Layanan
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 4. Program Studi Section (Standalone Tab / Grid) -->
                <div class="mb-14" x-show="filteredProdis.length > 0 && activeCategory === 'prodi'">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 border-gray-200 dark:border-gray-800 font-sans">
                        <span>🎓</span> Program Studi (Semua)
                    </h2>
                    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        <template x-for="item in filteredProdis" :key="item.title">
                            <article class="glassmorphic-article flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200/20 dark:border-gray-800/30 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex h-full flex-col p-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full bg-amber-50 dark:bg-amber-950/40 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-amber-700 dark:text-amber-300">PRODI</span>
                                    </div>
                                    <h3 class="mt-4 text-xl font-black leading-tight text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    <p x-show="item.subtitle" class="mt-2 text-sm font-semibold text-amber-700 dark:text-amber-400 font-sans" x-text="item.subtitle"></p>

                                    <div class="mt-auto pt-6 flex flex-wrap gap-3 border-t border-gray-100 dark:border-gray-800/40">
                                        <a :href="'/unit/' + item.slug"
                                           class="inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 font-sans">
                                            Buka Halaman
                                        </a>
                                        <button type="button"
                                                @click="openTupoksiModal(item.role, item.title)"
                                                class="inline-flex items-center justify-center rounded-full bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500 shadow-sm cursor-pointer font-sans">
                                            Ajukan Layanan
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>

                <!-- 5. Pegawai & Dosen Section -->
                <div class="mb-14" x-show="filteredPegawai.length > 0">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 border-gray-200 dark:border-gray-800 font-sans">
                        <span>👤</span> Pegawai & Dosen
                    </h2>
                    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        <template x-for="item in filteredPegawai" :key="item.title">
                            <article class="glassmorphic-article flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200/20 dark:border-gray-800/30 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex h-full flex-col p-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full bg-indigo-50 dark:bg-indigo-950/40 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-indigo-700 dark:text-indigo-300">PEGAWAI & DOSEN</span>
                                    </div>
                                    <h3 class="mt-4 text-lg font-black leading-tight text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    <p x-show="item.subtitle" class="mt-2 text-xs font-semibold text-indigo-700 dark:text-indigo-400 font-sans" x-text="item.subtitle"></p>

                                    <div class="mt-auto pt-6 flex flex-wrap gap-3 border-t border-gray-100 dark:border-gray-800/40">
                                        <a :href="'/unit/' + item.slug"
                                           class="inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 font-sans">
                                            Buka Halaman
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>

                <!-- 6. Mahasiswa & ORMAWA Section -->
                <div class="mb-14" x-show="filteredMahasiswa.length > 0">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 border-gray-200 dark:border-gray-800 font-sans">
                        <span>👥</span> Mahasiswa & Organisasi Kemahasiswaan (ORMAWA)
                    </h2>
                    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        <template x-for="item in filteredMahasiswa" :key="item.title">
                            <article class="glassmorphic-article flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200/20 dark:border-gray-800/30 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex h-full flex-col p-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full bg-rose-50 dark:bg-rose-950/40 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-rose-700 dark:text-rose-300">MAHASISWA & ORMAWA</span>
                                    </div>
                                    <h3 class="mt-4 text-lg font-black leading-tight text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    <p x-show="item.subtitle" class="mt-2 text-xs font-semibold text-rose-700 dark:text-rose-400 font-sans" x-text="item.subtitle"></p>

                                    <div class="mt-auto pt-6 flex flex-wrap gap-3 border-t border-gray-100 dark:border-gray-800/40">
                                        <a :href="'/unit/' + item.slug"
                                           class="inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 font-sans">
                                            Buka Halaman
                                        </a>
                                        <button type="button"
                                                @click="openTupoksiModal(item.role, item.title)"
                                                class="inline-flex items-center justify-center rounded-full bg-brand-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-brand-500 shadow-sm cursor-pointer font-sans">
                                            Layanan
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>

            </div>

            <!-- ===================== LIST VIEW MODE (PER KELOMPOK) ===================== -->
            <div x-show="viewMode === 'list'" class="space-y-12">
                <!-- Group 1: Bidang -->
                <div x-show="filteredBidang.length > 0" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 text-lg">🏢</span>
                        <div>
                            <h2 class="text-xl font-black text-gray-900 dark:text-white font-sans">Bidang & Direktorat</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">Direktur, Wadir, Bagian Adum, OSDM, SPI, dan Pengarah Strategis</p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        <template x-for="item in filteredBidang" :key="item.title">
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 px-3 rounded-2xl transition-colors">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-950/40 px-2.5 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-300">BIDANG</span>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    </div>
                                    <p x-show="item.subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-sans" x-text="item.subtitle"></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="'/unit/' + item.slug" class="inline-flex h-9 items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-sans">
                                        Buka Halaman
                                    </a>
                                    <button type="button" 
                                            @click="openTupoksiModal(item.role, item.title)"
                                            class="inline-flex h-9 items-center justify-center rounded-xl bg-brand-600 px-4 text-xs font-bold text-white hover:bg-brand-500 shadow-sm transition-colors cursor-pointer font-sans">
                                        Ajukan Layanan
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Group 2: Unit & Pusat -->
                <div x-show="filteredUnit.length > 0" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 text-lg">⚙️</span>
                        <div>
                            <h2 class="text-xl font-black text-gray-900 dark:text-white font-sans">Unit Pelayanan & Pusat Pengelola</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">Perpustakaan, IT, Lab, Bisnis, Humas, Bahasa, UPK, Pusat Mutu, P3, & Litmas</p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        <template x-for="item in filteredUnit" :key="item.title">
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 px-3 rounded-2xl transition-colors">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-full bg-teal-50 dark:bg-teal-950/40 px-2.5 py-0.5 text-[10px] font-bold text-teal-700 dark:text-teal-300">UNIT & PUSAT</span>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    </div>
                                    <p x-show="item.subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-sans" x-text="item.subtitle"></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="'/unit/' + item.slug" class="inline-flex h-9 items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-sans">
                                        Buka Halaman
                                    </a>
                                    <button type="button" 
                                            @click="openTupoksiModal(item.role, item.title)"
                                            class="inline-flex h-9 items-center justify-center rounded-xl bg-brand-600 px-4 text-xs font-bold text-white hover:bg-brand-500 shadow-sm transition-colors cursor-pointer font-sans">
                                        Ajukan Layanan
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Group 3: Jurusan -->
                <div x-show="filteredJurusans.length > 0" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 text-lg">🏛️</span>
                        <div>
                            <h2 class="text-xl font-black text-gray-900 dark:text-white font-sans">Jurusan & Program Studi</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">8 Jurusan beserta seluruh Program Studi terintegrasi</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <template x-for="jurusan in filteredJurusans" :key="jurusan.title">
                            <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-800/50 p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200/60 dark:border-gray-700/60 pb-3.5">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex rounded-full bg-purple-50 dark:bg-purple-950/40 px-2.5 py-0.5 text-[10px] font-bold text-purple-700 dark:text-purple-300">JURUSAN</span>
                                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white font-sans" x-text="jurusan.title"></h3>
                                        </div>
                                        <p x-show="jurusan.subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-sans" x-text="jurusan.subtitle"></p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <a :href="'/unit/' + jurusan.slug" class="inline-flex h-8 items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 px-3 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 font-sans">
                                            Buka Jurusan
                                        </a>
                                        <button type="button" 
                                                @click="openTupoksiModal(jurusan.role, jurusan.title)"
                                                class="inline-flex h-8 items-center justify-center rounded-lg bg-brand-600 px-3 text-xs font-bold text-white hover:bg-brand-500 font-sans">
                                            Layanan
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3.5 pl-2 sm:pl-4 space-y-2" x-show="getFilteredProdisForJurusan(jurusan.role).length > 0">
                                    <template x-for="prodi in getFilteredProdisForJurusan(jurusan.role)" :key="prodi.title">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 py-2 px-3 rounded-xl hover:bg-white dark:hover:bg-gray-750 transition-colors">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="text-xs text-gray-400">└─</span>
                                                <span class="inline-flex rounded-md bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-[10px] font-bold text-gray-700 dark:text-gray-300">PRODI</span>
                                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate font-sans" x-text="prodi.title"></span>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0 pl-6 sm:pl-0">
                                                <a :href="'/unit/' + prodi.slug" class="inline-flex h-7 items-center justify-center rounded-md border border-gray-200 dark:border-gray-700 px-2.5 text-[11px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 font-sans">
                                                    Buka
                                                </a>
                                                <button type="button" 
                                                        @click="openTupoksiModal(prodi.role, prodi.title)"
                                                        class="inline-flex h-7 items-center justify-center rounded-md bg-brand-50 dark:bg-brand-950/50 px-2.5 text-[11px] font-bold text-brand-700 dark:text-brand-300 hover:bg-brand-100 font-sans">
                                                    Layanan
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Group 4: Pegawai & Dosen -->
                <div x-show="filteredPegawai.length > 0" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 text-lg">👤</span>
                        <div>
                            <h2 class="text-xl font-black text-gray-900 dark:text-white font-sans">Pegawai & Dosen</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">Direktori halaman personal Dosen & Pegawai Poltekkes Bandung</p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        <template x-for="item in filteredPegawai" :key="item.title">
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 px-3 rounded-2xl transition-colors">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-full bg-indigo-50 dark:bg-indigo-950/40 px-2.5 py-0.5 text-[10px] font-bold text-indigo-700 dark:text-indigo-300">PEGAWAI & DOSEN</span>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    </div>
                                    <p x-show="item.subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-sans" x-text="item.subtitle"></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="'/unit/' + item.slug" class="inline-flex h-9 items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-sans">
                                        Buka Halaman
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Group 5: Mahasiswa & ORMAWA -->
                <div x-show="filteredMahasiswa.length > 0" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 text-lg">👥</span>
                        <div>
                            <h2 class="text-xl font-black text-gray-900 dark:text-white font-sans">Mahasiswa & Organisasi Kemahasiswaan (ORMAWA)</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">BEM KM Poltekkes Bandung dan seluruh Himpunan Mahasiswa (HIMA)</p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        <template x-for="item in filteredMahasiswa" :key="item.title">
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 px-3 rounded-2xl transition-colors">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-full bg-rose-50 dark:bg-rose-950/40 px-2.5 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-300">MAHASISWA & ORMAWA</span>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white font-sans" x-text="item.title"></h3>
                                    </div>
                                    <p x-show="item.subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-sans" x-text="item.subtitle"></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="'/unit/' + item.slug" class="inline-flex h-9 items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-sans">
                                        Buka Halaman
                                    </a>
                                    <button type="button" 
                                            @click="openTupoksiModal(item.role, item.title)"
                                            class="inline-flex h-9 items-center justify-center rounded-xl bg-brand-600 px-4 text-xs font-bold text-white hover:bg-brand-500 shadow-sm transition-colors cursor-pointer font-sans">
                                        Layanan
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Empty State for Search -->
            <div x-show="filteredItems.length === 0" class="rounded-3xl border border-dashed border-gray-300 dark:border-gray-800 bg-white dark:bg-gray-900 px-8 py-16 text-center shadow-sm">
                <h2 class="text-xl font-black text-gray-900 dark:text-white font-sans">Tidak Ditemukan</h2>
                <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400 font-sans">Tidak ada item yang cocok dengan kriteria pencarian atau kategori yang dipilih.</p>
            </div>
        @else
            @if ($items->count() === 0)
            <div class="rounded-3xl border border-dashed border-gray-300 dark:border-gray-800 bg-white dark:bg-gray-900 px-8 py-16 text-center shadow-sm">
                <h2 class="text-xl font-black text-gray-900 dark:text-white">Belum Ada Konten</h2>
                <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $emptyMessage }}</p>
            </div>
            @endif
        @endif
    </div>
</section>

<!-- Modal Backdrop -->
<div x-show="showModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
     <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
         <!-- Background overlay -->
         <div x-show="showModal"
              x-transition:enter="ease-out duration-300"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="ease-in duration-200"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity"
              @click="showModal = false"></div>

         <!-- Modal Content -->
         <div x-show="showModal"
              x-transition:enter="ease-out duration-300"
              x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
              x-transition:leave="ease-in duration-200"
              x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
              x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-gray-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 dark:border-gray-800">
             
             <!-- Header -->
             <div class="bg-brand-700 px-6 py-4 flex items-center justify-between text-white">
                 <div>
                     <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-200 font-sans">Formulir Layanan</span>
                     <h3 class="text-lg font-black leading-tight mt-0.5 font-sans" x-text="selectedTitle"></h3>
                 </div>
                 <button type="button" @click="showModal = false" class="text-white hover:text-brand-200 transition-colors text-xl font-bold cursor-pointer">&times;</button>
             </div>

             <!-- Form -->
             <form action="{{ route('pengaduan.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 m-0">
                 @csrf
                 <input type="hidden" name="tujuan_unit" :value="mapRoleToTujuan(selectedRole)">

                 <!-- Nama -->
                 <div>
                     <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Nama Lengkap</label>
                     <input type="text" name="nama" required placeholder="Masukkan nama Anda"
                            value="{{ $loggedInName }}"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700 transition-colors font-sans">
                 </div>

                 <div class="grid grid-cols-2 gap-4">
                     <!-- Email -->
                     <div>
                         <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Email (Opsional)</label>
                         <input type="email" name="email" placeholder="nama@email.com"
                                value="{{ $loggedInEmail }}"
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700 transition-colors font-sans">
                     </div>

                     <!-- Telepon -->
                     <div>
                         <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Telepon (Opsional)</label>
                         <input type="text" name="telepon" placeholder="08123456789"
                                value="{{ $loggedInPhone }}"
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700 transition-colors font-sans">
                     </div>
                 </div>

                 <!-- Kategori Pengajuan -->
                 <div>
                     <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Tipe Pengajuan</label>
                     <select name="jenis" required
                             class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700 transition-colors font-sans">
                         <option value="pengaduan" selected>Pengaduan / Pertanyaan / Layanan</option>
                         <option value="saran">Saran</option>
                         <option value="kritik">Kritik</option>
                         <option value="keluhan">Keluhan</option>
                     </select>
                 </div>

                 <!-- Jenis Layanan Sesuai Tupoksi -->
                 <div>
                     <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Jenis Layanan / Bidang Urusan</label>
                     <select name="tupoksi_service" x-model="selectedService" required
                             class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700 transition-colors font-sans">
                         <template x-for="svc in currentServices" :key="svc">
                             <option :value="svc" x-text="svc"></option>
                         </template>
                     </select>
                 </div>

                 <!-- Subjek Ringkas -->
                 <div>
                     <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Subjek / Masalah Ringkas</label>
                     <input type="text" name="subjek" required placeholder="Contoh: Pengajuan KTA Perpustakaan Baru"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700 transition-colors font-sans">
                 </div>

                 <!-- Detail Isi Pengajuan -->
                 <div>
                     <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Detail Pertanyaan / Laporan</label>
                     <textarea name="isi" required rows="4" minlength="10" placeholder="Jelaskan secara detail layanan yang Anda perlukan..."
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700 transition-colors resize-none font-sans"></textarea>
                 </div>

                 <!-- File Lampiran -->
                 <div>
                     <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 font-sans">Lampiran Berkas (Opsional)</label>
                     <input type="file" name="file_lampiran"
                            class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 dark:file:bg-brand-950/40 dark:file:text-brand-400 hover:file:bg-brand-100 dark:hover:file:bg-brand-900/30 transition-all font-sans">
                     <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400 font-sans">Mendukung file PDF, Gambar/Foto, Word, Excel, ZIP (Maksimal 5MB)</p>
                 </div>

                 <!-- Action buttons -->
                 <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                     <button type="button" @click="showModal = false"
                             class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer font-sans">Batal</button>
                     <button type="submit"
                             class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors shadow-lg shadow-brand-900/10 cursor-pointer font-sans">Kirim Pengajuan</button>
                 </div>
             </form>
         </div>
     </div>
</div>

</div>
@endsection
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@hotwired/turbo@8.0.4/dist/turbo.es2017-umd.js" defer></script>
    <style>
        /* Widen main admin panels/lists to full width on large screens */
        #admin-content > div[class*="max-w-4xl"],
        #admin-content > div[class*="max-w-5xl"],
        #admin-content > div[class*="max-w-6xl"],
        #admin-content > div[class*="max-w-7xl"] {
            max-width: 100% !important;
        }
    </style>
</head>
<body x-data="{ sidebarOpen: window.innerWidth > 768 ? (localStorage.getItem('sidebarOpen') !== 'false') : false }"
      @toggle-sidebar.window="sidebarOpen = !sidebarOpen; if (window.innerWidth > 768) localStorage.setItem('sidebarOpen', sidebarOpen)"
      :class="{ 'dark': $store.theme.dark }"
      class="min-h-screen bg-gray-50 dark:bg-gray-950 font-sans text-gray-900 dark:text-white antialiased flex flex-col h-screen overflow-hidden">

    <x-navbar />

    <div class="flex flex-1 overflow-hidden">

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        {{-- Mobile backdrop --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm md:hidden"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <aside x-show="sidebarOpen"
               x-transition:enter="transition ease-out duration-300 md:transition-none"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200 md:transition-none"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 z-50 md:static flex md:w-72 flex-col relative overflow-hidden bg-gradient-to-b from-brand-950 via-brand-900 to-slate-950
                      border-r border-brand-900/50 shadow-2xl shadow-brand-950/20 flex-shrink-0"
               aria-label="Admin menu">

            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-20 right-[-36px] h-56 w-56 rounded-full bg-brand-400/10 blur-3xl"></div>
                <div class="absolute top-1/3 left-[-42px] h-44 w-44 rounded-full bg-brand-300/10 blur-3xl"></div>
                <div class="absolute bottom-10 right-8 h-32 w-32 rounded-full bg-accent-500/10 blur-3xl"></div>
            </div>

            {{-- Logo --}}
            <div class="relative h-16 flex items-center gap-3 px-6 border-b border-white/10 flex-shrink-0">
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-brand-400 via-brand-500 to-accent-500 flex items-center justify-center
                            text-white font-black text-sm">P</div>
                <div>
                    <p class="text-sm font-bold text-white leading-none">Poltekkes Bandung</p>
                    <p class="text-[10px] text-white/55 leading-none mt-0.5">Admin Panel</p>
                </div>
            </div>

            {{-- Nav --}}
            <nav class="relative flex-1 px-3 py-4 space-y-1.5 overflow-y-auto" aria-label="Sidebar navigation">
                @php
                    $user = auth()->user();
                    $sidebarTones = [
                        [
                            'active' => 'bg-brand-400/15 text-white ring-1 ring-brand-300/20 shadow-lg shadow-brand-950/20',
                            'icon'   => 'bg-brand-300/20 text-brand-50 ring-1 ring-brand-200/20',
                            'dot'    => 'bg-brand-300',
                        ],
                        [
                            'active' => 'bg-emerald-400/15 text-white ring-1 ring-emerald-300/20 shadow-lg shadow-emerald-950/20',
                            'icon'   => 'bg-emerald-300/20 text-emerald-50 ring-1 ring-emerald-200/20',
                            'dot'    => 'bg-emerald-300',
                        ],
                        [
                            'active' => 'bg-teal-400/15 text-white ring-1 ring-teal-300/20 shadow-lg shadow-teal-950/20',
                            'icon'   => 'bg-teal-300/20 text-teal-50 ring-1 ring-teal-200/20',
                            'dot'    => 'bg-teal-300',
                        ],
                        [
                            'active' => 'bg-sky-400/15 text-white ring-1 ring-sky-300/20 shadow-lg shadow-sky-950/20',
                            'icon'   => 'bg-sky-300/20 text-sky-50 ring-1 ring-sky-200/20',
                            'dot'    => 'bg-sky-300',
                        ],
                        [
                            'active' => 'bg-lime-400/15 text-white ring-1 ring-lime-300/20 shadow-lg shadow-lime-950/20',
                            'icon'   => 'bg-lime-300/20 text-lime-50 ring-1 ring-lime-200/20',
                            'dot'    => 'bg-lime-300',
                        ],
                    ];

                    // Build static page nav entries dynamically from DB
                    $staticPageNav = \App\Models\HalamanStatis::orderBy('kunci')->get()
                        ->map(fn($h) => [
                            'route'  => 'admin.halaman-statis.edit',
                            'params' => ['kunci' => $h->kunci],
                            'icon'   => $h->menu_icon ?: '📄',
                            'label'  => $h->menu_label ?: $h->judul,
                            'menu'   => 'halaman-statis:' . $h->kunci,
                        ])->toArray();

                    $allNav = array_merge([
                        // Super admin only
                        ['route' => 'admin.users.index',            'icon' => '👥', 'label' => 'Pengelola / Users',       'menu' => 'users',               'order' => 10],
                        ['route' => 'admin.roles.index',            'icon' => '🪪', 'label' => 'Kelola Role',             'menu' => 'roles',               'order' => 15],
                        ['route' => 'admin.users.credentials',      'icon' => '🔐', 'label' => 'Tabel Kredensial',         'menu' => 'users',               'order' => 18],
                        ['route' => 'admin.halaman-statis.index',   'icon' => '📑', 'label' => 'Kelola Halaman Statis',    'menu' => 'halaman-statis-mgr',  'order' => 25],
                        ['route' => 'admin.nav-menus.index',        'icon' => '🧭', 'label' => 'Kelola Navigasi Publik',  'menu' => 'nav-menus',           'order' => 26],
                        ['route' => 'admin.nav-admin.index',        'icon' => '⚙️', 'label' => 'Konfigurasi Sidebar',     'menu' => 'nav-admin',           'order' => 27],
                        ['route' => 'admin.guides.index',           'icon' => '🗺️', 'label' => 'Pusat Panduan',          'menu' => 'guides',              'order' => 28],
                        ['route' => 'admin.api-keys.index',         'icon' => '🔑', 'label' => 'API Keys',                'menu' => 'api-keys',            'order' => 20],
                        ['route' => 'admin.content-engine.index',   'icon' => '🤖', 'label' => 'Content Engine',          'menu' => 'content-engine',      'order' => 30],
                        ['route' => 'admin.knowledge.index',        'icon' => '📚', 'label' => 'Knowledge Base',          'menu' => 'content-engine',      'order' => 31],
                        ['route' => 'admin.suno-playlists.index',   'icon' => '📻', 'label' => 'Suno Playlists',          'menu' => 'suno-playlists',      'order' => 32],
                        ['route' => 'admin.podcasts.index',         'icon' => '🎙️', 'label' => 'Kelola Podcast',          'menu' => 'podcasts',            'order' => 33],
                        ['route' => 'admin.videos.index',           'icon' => '📹', 'label' => 'Kelola Video',            'menu' => 'videos',              'order' => 34],
                        // Konten editorial
                        ['route' => 'admin.berita.index',           'icon' => '📰', 'label' => 'Berita',                  'menu' => 'berita',              'order' => 40],
                        ['route' => 'admin.pengumuman.index',       'icon' => '📢', 'label' => 'Pengumuman',              'menu' => 'pengumuman',          'order' => 50],
                        ['route' => 'admin.galeri.index',           'icon' => '🖼️', 'label' => 'Galeri',                 'menu' => 'galeri',              'order' => 60],
                        // Kalender / Agenda
                        ['route' => 'admin.agenda.index',           'icon' => '📅', 'label' => 'Agenda & Kalender',       'menu' => 'agenda',              'order' => 70],
                        // Akademik
                        ['route' => 'admin.program-studi.index',    'icon' => '🎓', 'label' => 'Program Studi',           'menu' => 'program-studi',       'order' => 80],
                        ['route' => 'pbm.classes.index',            'icon' => '🏥', 'label' => 'Praktik Klinik (PBM)',    'menu' => 'pbm',                 'order' => 85],
                        // Kemahasiswaan
                        ['route' => 'admin.berprestasi.index',      'icon' => '🏆', 'label' => 'Mahasiswa Berprestasi',   'menu' => 'berprestasi',         'order' => 90],
                        ['route' => 'portal-mahasiswa',             'icon' => '🎓', 'label' => 'Portal Mahasiswa',        'menu' => '*',                  'order' => 91],
                        // Unit Bisnis & Lab
                        ['route' => 'admin.layanan-bisnis.index',   'icon' => '🏪', 'label' => 'Layanan & Produk Bisnis', 'menu' => 'layanan-bisnis',      'order' => 100],
                        ['route' => 'admin.layanan-lab.index',      'icon' => '🧪', 'label' => 'Layanan & Tarif Lab',     'menu' => 'layanan-lab',         'order' => 110],
                        // Unit spesialisasi
                        ['route' => 'admin.fasilitas-asrama.index', 'icon' => '🛏️', 'label' => 'Fasilitas Asrama',       'menu' => 'fasilitas-asrama',    'order' => 120],
                        ['route' => 'admin.koleksi-perpus.index',   'icon' => '📚', 'label' => 'Koleksi Perpustakaan',   'menu' => 'koleksi-perpus',      'order' => 130],
                        ['route' => 'admin.publikasi.index',        'icon' => '📄', 'label' => 'Publikasi & Penelitian', 'menu' => 'publikasi',           'order' => 140],
                        ['route' => 'admin.dokumen-mutu.index',     'icon' => '📋', 'label' => 'Dokumen Mutu',           'menu' => 'dokumen-mutu',        'order' => 150],
                        ['route' => 'admin.layanan-ti.index',       'icon' => '💻', 'label' => 'Layanan IT',             'menu' => 'layanan-ti',          'order' => 160],
                        ['route' => 'admin.pelatihan.index',        'icon' => '🎓', 'label' => 'Program Pelatihan',      'menu' => 'pelatihan',           'order' => 170],
                        // Halaman Unit (Page Builder)
                        ['route' => 'admin.halaman-unit.index',     'icon' => '🗂️', 'label' => 'Halaman Unit Saya',      'menu' => 'halaman-unit',        'order' => 180],
                        ['route' => 'admin.rubrik.index',           'icon' => '📊', 'label' => 'Rubrik Penilaian',        'menu' => 'halaman-unit',        'order' => 182],
                        ['route' => 'admin.wfh.index',              'icon' => '🏠', 'label' => 'Laporan Kegiatan WFH',    'menu' => 'wfh',                 'order' => 183],
                        ['route' => 'admin.pengaduan.index',        'icon' => '📋', 'label' => 'Pengaduan & Keluhan',     'menu' => 'pengaduan',           'order' => 185],
                        // Link eksternal
                        ['route' => 'https://beta.poltekkesbandung.ac.id/', 'icon' => '🌐', 'label' => 'Lihat Website Utama', 'external' => true, 'menu' => '*', 'order' => 999],
                    ], $staticPageNav);

                    // Apply AdminNavConfig: override labels/icons, sort by urutan, hide globally_hidden
                    $navConfig = \App\Models\AdminNavConfig::getAllKeyed();
                    foreach ($allNav as &$item) {
                        $key = $item['menu'] ?? '';
                        if (isset($navConfig[$key])) {
                            $cfg = $navConfig[$key];
                            if (!empty($cfg['custom_label'])) $item['label'] = $cfg['custom_label'];
                            if (!empty($cfg['custom_icon']))  $item['icon']  = $cfg['custom_icon'];
                            $item['_urutan']          = $cfg['urutan'] ?? 999;
                            $item['_globally_hidden'] = !empty($cfg['globally_hidden']);
                        } else {
                            $item['_urutan']          = $item['order'] ?? 999;
                            $item['_globally_hidden'] = false;
                        }
                    }
                    unset($item);
                    usort($allNav, fn($a, $b) => $a['_urutan'] <=> $b['_urutan']);

                    $resolveGroup = function (array $item): array {
                        $route = $item['route'] ?? '';
                        $menu = $item['menu'] ?? '';

                        if (
                            in_array($menu, ['users', 'roles', 'api-keys', 'content-engine', 'halaman-statis-mgr', 'nav-menus', 'nav-admin', 'guides'], true)
                            || in_array($route, ['admin.users.credentials', 'admin.knowledge.index', 'admin.suno-playlists.index', 'admin.podcasts.index', 'admin.videos.index'], true)
                        ) {
                            return ['Super Admin', 10];
                        }

                        if (in_array($menu, ['berita', 'pengumuman', 'galeri', 'agenda'], true)) {
                            return ['Konten', 20];
                        }

                        if (in_array($menu, ['program-studi', 'berprestasi', 'pbm'], true) || ($item['route'] ?? '') === 'portal-mahasiswa') {
                            return ['Akademik & Kemahasiswaan', 30];
                        }

                        if (in_array($menu, ['layanan-bisnis', 'layanan-lab', 'fasilitas-asrama', 'koleksi-perpus', 'publikasi', 'dokumen-mutu', 'layanan-ti', 'pelatihan'], true)) {
                            return ['Unit & Layanan', 40];
                        }

                        if ($menu === 'halaman-unit' || $menu === 'pengaduan' || $menu === 'wfh') {
                            return ['Halaman Unit', 50];
                        }

                        if (str_starts_with($menu, 'halaman-statis:')) {
                            return ['Halaman Statis', 60];
                        }

                        if ($menu === '*') {
                            return ['Website', 90];
                        }

                        return ['Lainnya', 80];
                    };

                    // Filter berdasarkan role
                    $navItems = array_values(array_filter($allNav, function($item) use ($user) {
                        if ($user->isSuperAdmin()) return true;
                        // Skip globally hidden items for non-super-admin
                        if (!empty($item['_globally_hidden'])) return false;
                        $menu = $item['menu'] ?? '';
                        if ($menu === '*') return true;
                        if ($menu === 'wfh') return true;
                        if ($menu === 'pbm') return in_array($user->role, ['admin', 'dosen', 'ci']);
                        if (in_array($menu, ['users', 'roles', 'api-keys', 'content-engine', 'halaman-statis-mgr', 'nav-menus', 'nav-admin', 'guides'])) return false;
                        return $user->hasRoleAccess($menu);
                    }));

                    foreach ($navItems as &$item) {
                        [$item['_group_label'], $item['_group_order']] = $resolveGroup($item);
                    }
                    unset($item);

                    usort($navItems, function ($a, $b) {
                        if ($a['_group_order'] !== $b['_group_order']) {
                            return $a['_group_order'] <=> $b['_group_order'];
                        }

                        return $a['_urutan'] <=> $b['_urutan'];
                    });

                    $groupedNavItems = [];
                    foreach ($navItems as $item) {
                        $groupedNavItems[$item['_group_label']][] = $item;
                    }
                @endphp

                @foreach($groupedNavItems as $groupLabel => $groupItems)
                    <div class="{{ $loop->first ? '' : 'pt-3' }}">
                        <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/40">
                            {{ $groupLabel }}
                        </p>

                        <div class="space-y-1.5">
                            @foreach($groupItems as $item)
                                @php
                                    $href = str_starts_with($item['route'], 'http')
                                        ? $item['route']
                                        : (isset($item['params'])
                                            ? route($item['route'], $item['params'])
                                            : route($item['route']));
                                    $isActive = isset($item['external']) ? false : request()->is(ltrim(parse_url($href, PHP_URL_PATH), '/') . '*');
                                    $tone = $sidebarTones[abs(crc32($item['menu'] ?? $item['label'])) % count($sidebarTones)];

                                    // Display unread complaints count badge if this is the pengaduan menu
                                    $unreadCountBadge = null;
                                    if (($item['menu'] ?? '') === 'pengaduan') {
                                        $user = auth()->user();
                                        if ($user) {
                                            if ($user->isSuperAdmin()) {
                                                $unreadCountBadge = \App\Models\Pengaduan::whereNull('dibaca_at')->count();
                                            } else {
                                                $pageId = null;
                                                if ($user->managed_unit_page_id) {
                                                    $pageId = $user->managed_unit_page_id;
                                                } elseif ($user->isKaprodi() && $user->programStudi) {
                                                    $pageId = $user->programStudi->unit_page_id;
                                                }
                                                
                                                $page = $pageId ? \App\Models\UnitPage::find($pageId) : \App\Models\UnitPage::where('role', $user->role)->first();
                                                if ($page) {
                                                    $unreadCountBadge = \App\Models\Pengaduan::whereIn('tujuan_unit', \App\Models\Pengaduan::unitAccessKeys($page->role))
                                                        ->whereNull('dibaca_at')->count();
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <a href="{{ $href }}"
                                   class="group flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium
                                          transition-all duration-200
                                          {{ $isActive
                                               ? $tone['active']
                                               : 'text-white/72 hover:text-white hover:bg-white/6' }}"
                                   {{ isset($item['external']) ? 'target="_blank" rel="noopener"' : '' }}>
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base transition-transform duration-200 group-hover:scale-[1.03] {{ $tone['icon'] }}"
                                          aria-hidden="true">{{ $item['icon'] }}</span>
                                    <span class="truncate">{{ $item['label'] }}</span>
                                    @if(isset($unreadCountBadge) && $unreadCountBadge > 0)
                                        <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold">{{ $unreadCountBadge > 99 ? '99+' : $unreadCountBadge }}</span>
                                    @elseif($isActive)
                                        <span class="ml-auto w-2 h-2 rounded-full {{ $tone['dot'] }} shadow-[0_0_0_4px_rgba(255,255,255,0.06)]" aria-hidden="true"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            {{-- User + logout --}}
            <div class="relative p-4 border-t border-white/10 flex-shrink-0 bg-white/5 backdrop-blur-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center
                                text-white text-xs font-bold flex-shrink-0">
                        {{ mb_substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? '—' }}</p>
                        <p class="text-xs text-white/55 truncate">Admin</p>
                    </div>
                </div>
                <a href="{{ url('/') }}"
                   class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium
                          text-white/70 hover:text-white hover:bg-white/8 transition-colors mb-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Beranda Utama
                </a>
                <a href="{{ route('admin.ubah-password') }}"
                   class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium
                          text-white/70 hover:text-white hover:bg-white/8 transition-colors mb-1.5">
                    🔑 Ubah Password
                </a>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium
                                   text-white/70 hover:text-white hover:bg-white/8 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        {{-- ── Main ─────────────────────────────────────────────────────────── --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top bar --}}
            <header class="h-16 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800
                           flex items-center justify-between px-6 flex-shrink-0">
                <div class="flex items-center gap-3">
                    {{-- Burger Button --}}
                    <button @click="sidebarOpen = !sidebarOpen; if (window.innerWidth > 768) localStorage.setItem('sidebarOpen', sidebarOpen)"
                            class="w-9 h-9 rounded-xl flex items-center justify-center
                                   bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700
                                   text-gray-500 dark:text-gray-400 transition-colors"
                            aria-label="Toggle Sidebar">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-base font-bold text-gray-900 dark:text-white">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-xs text-gray-400 dark:text-gray-500">@yield('page-subtitle', '')</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Dark mode toggle --}}
                    <button @click="$store.theme.toggle()"
                            class="w-9 h-9 rounded-xl flex items-center justify-center
                                   bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700
                                   text-gray-500 dark:text-gray-400 transition-colors"
                            :aria-label="$store.theme.dark ? 'Mode terang' : 'Mode gelap'">
                        <span x-text="$store.theme.dark ? '☀️' : '🌙'" class="text-base"></span>
                    </button>
                    {{-- AI provider badge --}}
                    <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full
                                 bg-brand-50 dark:bg-brand-950 border border-brand-200 dark:border-brand-800
                                 px-3 py-1 text-xs font-bold text-brand-700 dark:text-brand-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                        AI: {{ strtoupper(config('ai.provider')) }}
                    </span>
                </div>
            </header>

            {{-- Flash messages --}}
            @if(session('login_as_original_admin_id'))
                <div class="mx-6 mt-4 flex items-center justify-between gap-3 rounded-xl bg-amber-50 dark:bg-amber-900/30
                            border border-amber-300 dark:border-amber-700 px-4 py-3 text-sm
                            text-amber-800 dark:text-amber-200 flex-shrink-0">
                    <span>⚠️ Anda sedang <strong>Login As</strong> — {{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                    <form method="POST" action="{{ route('admin.users.return-to-admin') }}">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1 rounded-lg text-xs font-bold bg-amber-200 dark:bg-amber-700 text-amber-900 dark:text-amber-100 hover:bg-amber-300 transition-colors">
                            ↩ Kembali ke Super Admin
                        </button>
                    </form>
                </div>
            @endif

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                     class="mx-6 mt-4 flex items-center gap-3 rounded-xl bg-green-50 dark:bg-green-950
                            border border-green-200 dark:border-green-800 px-4 py-3 text-sm
                            text-green-800 dark:text-green-200 flex-shrink-0"
                     role="alert">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
                     class="mx-6 mt-4 flex items-center gap-3 rounded-xl bg-red-50 dark:bg-red-950
                            border border-red-200 dark:border-red-800 px-4 py-3 text-sm
                            text-red-800 dark:text-red-200 flex-shrink-0"
                     role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Scrollable content area --}}
            <main class="flex-1 overflow-y-auto p-6" id="admin-content" tabindex="-1">
                @include('partials.context-guide', [
                    'guide' => $contextualGuide ?? null,
                    'containerClass' => 'mb-6',
                ])
                @yield('content')
            </main>

            {{-- Back to Top Button --}}
            <div x-data="{ showTopBtn: false }"
                 x-init="document.getElementById('admin-content').addEventListener('scroll', (e) => { showTopBtn = e.target.scrollTop > 300 })"
                 class="fixed bottom-6 right-6 z-50">
                <button x-show="showTopBtn"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                        @click="document.getElementById('admin-content').scrollTo({ top: 0, behavior: 'smooth' })"
                        class="w-11 h-11 rounded-full flex items-center justify-center
                               bg-brand-600 hover:bg-brand-500 text-white shadow-xl shadow-brand-950/20 hover:scale-105 active:scale-95 transition-all
                               border border-white/10"
                        title="Kembali ke atas">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <x-concierge-widget />

    @stack('scripts')
</body>
</html>

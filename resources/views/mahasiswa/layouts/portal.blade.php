<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ mobileSidebarOpen: false }"
      :class="{ 'dark': $store.theme.dark }"
      class="scroll-smooth h-full overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Portal Mahasiswa') — {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script>
        if (localStorage.getItem('polkesban-theme') === 'dark' || (!localStorage.getItem('polkesban-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 font-sans text-gray-900 dark:text-white antialiased flex flex-col h-screen overflow-hidden">
    {{-- Header Navbar Website Poltekkes Bandung --}}
    <x-navbar />

    @php
        $me = Auth::guard('mahasiswa')->user();
        $navItems = [
            ['route' => 'mahasiswa.portofolio.index',  'icon' => '📁', 'label' => 'Portofolio Saya'],
            ['route' => 'mahasiswa.obe.index',         'icon' => '📘', 'label' => 'Buku Portofolio OBE'],
            ['route' => 'mahasiswa.portofolio.create', 'icon' => '➕', 'label' => 'Tambah Pencapaian'],
            ['route' => 'mahasiswa.kegiatan.index',    'icon' => '📅', 'label' => 'Jadwal PBM / Absensi'],
            ['route' => 'mahasiswa.profil.edit',       'icon' => '👤', 'label' => 'Profil Saya'],
            ['route' => 'home',                        'icon' => '🌐', 'label' => 'Website Poltekkes Bandung'],
        ];
    @endphp

    <div class="flex flex-1 overflow-hidden">

        <!-- Mobile Sidebar Drawer -->
        <div x-show="mobileSidebarOpen" 
             class="fixed inset-0 z-50 md:hidden" 
             style="display: none;" 
             role="dialog" 
             aria-modal="true">
            <!-- Backdrop -->
            <div x-show="mobileSidebarOpen"
                 x-transition:enter="transition-opacity ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileSidebarOpen = false"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

            <!-- Drawer Content -->
            <div x-show="mobileSidebarOpen"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="relative flex w-full max-w-xs flex-1 flex-col bg-white dark:bg-gray-900 h-full p-4 shadow-xl">
                 
                 <!-- Close Button -->
                 <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                     <a href="{{ route('home') }}" class="flex items-center gap-3">
                         <div class="w-8 h-8 rounded-xl bg-brand-600 flex items-center justify-center text-white font-black text-sm">P</div>
                         <div>
                             <p class="text-sm font-bold text-gray-900 dark:text-white leading-none">Poltekkes Bandung</p>
                             <p class="text-[10px] text-gray-400 leading-none mt-0.5">Portal Mahasiswa</p>
                         </div>
                     </a>
                     <button @click="mobileSidebarOpen = false" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                         </svg>
                     </button>
                 </div>

                 <!-- Nav Links -->
                 <nav class="flex-1 py-4 space-y-1 overflow-y-auto">
                     @foreach($navItems as $item)
                         @php $isActive = request()->routeIs($item['route'].'*'); @endphp
                         <a href="{{ route($item['route']) }}"
                            @click="mobileSidebarOpen = false"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150
                                   {{ $isActive
                                        ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                             <span class="text-base" aria-hidden="true">{{ $item['icon'] }}</span>
                             {{ $item['label'] }}
                         </a>
                     @endforeach
                 </nav>

                 <!-- User profile & Logout -->
                 <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                     <div class="flex items-center gap-3 mb-3">
                         <img src="{{ $me->foto_url }}" alt="{{ $me->name }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                         <div class="flex-1 min-w-0">
                             <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $me->name }}</p>
                             <p class="text-xs text-gray-400 truncate">{{ $me->nim }}</p>
                         </div>
                     </div>
                     <form action="{{ route('mahasiswa.logout') }}" method="POST">
                         @csrf
                         <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                             <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                             </svg>
                             Keluar
                         </button>
                     </form>
                 </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="hidden md:flex md:w-64 flex-col bg-white dark:bg-gray-900
                      border-r border-gray-100 dark:border-gray-800 flex-shrink-0">

            {{-- Logo --}}
            <div class="h-16 flex items-center gap-3 px-6 border-b border-gray-100 dark:border-gray-800 flex-shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-brand-600 flex items-center justify-center
                                text-white font-black text-sm">P</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white leading-none">Poltekkes Bandung</p>
                        <p class="text-[10px] text-gray-400 leading-none mt-0.5">Portal Mahasiswa</p>
                    </div>
                </a>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @foreach($navItems as $item)
                    @php $isActive = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium
                              transition-colors duration-150
                              {{ $isActive
                                   ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400 font-bold'
                                   : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                        <span class="text-base" aria-hidden="true">{{ $item['icon'] }}</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- User + logout --}}
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex-shrink-0">
                <div class="flex items-center gap-3 mb-3">
                    <img src="{{ $me->foto_url }}" alt="{{ $me->name }}"
                         class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $me->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $me->nim }}</p>
                    </div>
                </div>
                <form action="{{ route('mahasiswa.logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl px-3 py-2
                                   text-xs font-medium text-gray-500 hover:text-red-600
                                   hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Sub Topbar Portal Mahasiswa --}}
            <header class="h-16 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800
                           flex items-center justify-between px-6 flex-shrink-0 gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    {{-- Mobile Hamburger Menu --}}
                    <button @click="mobileSidebarOpen = true" 
                            class="p-2 -ml-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 md:hidden focus:outline-none focus:ring-2 focus:ring-brand-500 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-base font-bold text-gray-900 dark:text-white truncate">
                            @yield('page-title', 'Portal Mahasiswa')
                        </h1>
                        @hasSection('page-subtitle')
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate hidden sm:block">@yield('page-subtitle')</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    {{-- Mobile: user avatar --}}
                    <img src="{{ Auth::guard('mahasiswa')->user()->foto_url }}"
                         alt="{{ Auth::guard('mahasiswa')->user()->name }}"
                         class="w-8 h-8 rounded-full object-cover md:hidden cursor-pointer"
                         @click="mobileSidebarOpen = true">
                </div>
            </header>

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mx-6 mt-4 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20
                            border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('info'))
                <div class="mx-6 mt-4 px-4 py-3 rounded-xl bg-blue-50 dark:bg-blue-900/20
                            border border-blue-200 dark:border-blue-800 text-sm text-blue-700 dark:text-blue-400">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @include('partials.context-guide', [
                    'guide' => $contextualGuide ?? null,
                    'containerClass' => 'mb-6',
                ])
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

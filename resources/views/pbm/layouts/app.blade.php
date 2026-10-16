<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>PBM (Edlink Style) - Virtual Office Poltekkes Bandung</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        html {
            overflow-x: auto;
            overflow-y: scroll;
            -webkit-text-size-adjust: 100%;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* Slate 50 */
            min-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y pinch-zoom;
        }
        /* PBM Theme Color (Blue like Edlink) */
        .pbm-primary { background-color: #0ea5e9; color: white; }
        .pbm-primary-text { color: #0ea5e9; }
        .pbm-hover:hover { background-color: #e0f2fe; color: #0ea5e9; }

        /* Smooth sleek custom scrollbars so users can see and drag easily */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.7);
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Swipeable horizontal containers */
        .swipe-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            touch-action: pan-x;
        }

        /* Prevent body jumping when modal opens */
        body.modal-open {
            overflow: hidden;
        }
    </style>
    @stack('head')
</head>
<body class="antialiased text-gray-800 flex flex-col min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white/95 backdrop-blur-md shadow-xs sticky top-0 z-40 h-16 flex items-center px-4 sm:px-6 justify-between border-b border-gray-200">
        <div class="flex items-center gap-3">
            <!-- Mobile Drawer Toggle Button -->
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition cursor-pointer" aria-label="Menu Navigasi">
                <i data-feather="menu" class="w-5 h-5"></i>
            </button>

            <a href="{{ route('pbm.classes.index') }}" class="font-bold text-lg sm:text-xl flex items-center gap-2">
                <span class="pbm-primary w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white shadow-xs">P</span>
                <span class="truncate">PBM Virtual</span>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-900 transition flex items-center gap-1.5 text-xs sm:text-sm font-medium px-2.5 py-1.5 rounded-lg hover:bg-gray-50">
                <i data-feather="monitor" class="w-4 h-4"></i> <span class="hidden sm:inline">Back to VO</span>
            </a>
            <div class="flex items-center gap-2 pl-2 border-l border-gray-200">
                <div class="w-8 h-8 bg-sky-100 rounded-full flex items-center justify-center text-sky-700 font-bold border border-sky-200 text-sm shadow-xs">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="hidden md:block text-left">
                    <div class="text-xs font-semibold text-gray-800 truncate max-w-[130px]">{{ Auth::user()->name ?? 'User' }}</div>
                    <div class="text-[10px] text-gray-400 capitalize">{{ Auth::user()->role ?? 'Member' }}</div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Slide-Over Navigation Drawer Overlay -->
    <div id="mobileSidebarDrawer" class="fixed inset-0 z-50 lg:hidden hidden transition-opacity duration-300">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onclick="toggleMobileSidebar()"></div>

        <!-- Drawer Content -->
        <div class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-white shadow-2xl z-10 flex flex-col justify-between overflow-y-auto transform transition-transform duration-300 ease-in-out">
            <div>
                <!-- Drawer Header -->
                <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-sky-50 to-indigo-50">
                    <div class="flex items-center gap-2.5">
                        <span class="pbm-primary w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white shadow-xs">P</span>
                        <div class="font-bold text-gray-900">PBM Virtual</div>
                    </div>
                    <button type="button" onclick="toggleMobileSidebar()" class="p-1.5 rounded-lg hover:bg-white text-gray-400 hover:text-gray-700 transition">
                        <i data-feather="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- User Info -->
                <div class="p-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50/50">
                    <div class="w-10 h-10 bg-sky-100 rounded-full flex items-center justify-center text-sky-700 font-bold border border-sky-200">
                        {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-sm text-gray-900 truncate">{{ Auth::user()->name ?? 'User' }}</div>
                        <div class="text-xs text-gray-500 capitalize">{{ Auth::user()->role ?? 'Member' }}</div>
                    </div>
                </div>

                <!-- Nav Links -->
                <div class="py-3 px-2 space-y-1">
                    <a href="{{ route('pbm.classes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('pbm.classes.index') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i data-feather="home" class="w-4 h-4"></i> Beranda
                    </a>
                    <a href="{{ route('pbm.classes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('pbm.classes.show') || request()->routeIs('pbm.classes.edit') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i data-feather="book" class="w-4 h-4"></i> Kelas Saya
                    </a>
                    <a href="{{ route('pbm.schedule') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('pbm.schedule') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i data-feather="calendar" class="w-4 h-4"></i> Jadwal Kuliah & Praktik
                    </a>
                    @if(Auth::user() && in_array(Auth::user()->role, ['ci', 'super_admin', 'dosen', 'kaprodi']))
                    <a href="{{ route('pbm.ci.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('pbm.ci.dashboard') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i data-feather="check-square" class="w-4 h-4"></i> Dashboard CI
                    </a>
                    @endif
                </div>
            </div>

            <!-- Drawer Footer -->
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                <a href="{{ route('home') }}" class="flex items-center justify-center gap-2 w-full py-2 px-3 bg-white border border-gray-200 rounded-lg text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-100 transition">
                    <i data-feather="monitor" class="w-4 h-4"></i> Kembali ke Virtual Office
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Quick Navigation Bar -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-gray-200 px-3 py-2 flex justify-around items-center shadow-lg">
        <a href="{{ route('pbm.classes.index') }}" class="flex flex-col items-center gap-0.5 text-[11px] font-medium {{ request()->routeIs('pbm.classes.index') ? 'text-sky-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
            <i data-feather="home" class="w-4 h-4"></i>
            <span>Beranda</span>
        </a>
        <a href="{{ route('pbm.classes.index') }}" class="flex flex-col items-center gap-0.5 text-[11px] font-medium {{ request()->routeIs('pbm.classes.show') || request()->routeIs('pbm.classes.edit') ? 'text-sky-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
            <i data-feather="book" class="w-4 h-4"></i>
            <span>Kelas</span>
        </a>
        <a href="{{ route('pbm.schedule') }}" class="flex flex-col items-center gap-0.5 text-[11px] font-medium {{ request()->routeIs('pbm.schedule') ? 'text-sky-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
            <i data-feather="calendar" class="w-4 h-4"></i>
            <span>Jadwal</span>
        </a>
        <button type="button" onclick="toggleMobileSidebar()" class="flex flex-col items-center gap-0.5 text-[11px] font-medium text-gray-500 hover:text-gray-900 cursor-pointer">
            <i data-feather="menu" class="w-4 h-4"></i>
            <span>Menu</span>
        </button>
    </div>

    <!-- Layout Container -->
    @hasSection('fullwidth')
        <div class="w-full max-w-7xl mx-auto px-3 sm:px-4 md:px-8 py-4 sm:py-6 pb-24 lg:pb-8 flex-1 min-w-0">
            <main class="w-full min-w-0">
                @yield('content')
            </main>
        </div>
    @else
    <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row gap-6 px-3 sm:px-4 md:px-6 py-4 sm:py-6 pb-24 lg:pb-8 flex-1 min-w-0">
        
        <!-- Sidebar for Desktop (visible on lg+) -->
        <aside class="w-64 hidden lg:block shrink-0">
            <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden sticky top-24">
                <div class="p-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50/50">
                    <div class="w-10 h-10 bg-sky-100 rounded-full flex items-center justify-center text-sky-700 font-bold border border-sky-200">
                        {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-sm truncate text-gray-900">{{ Auth::user()->name ?? 'User' }}</div>
                        <div class="text-xs text-gray-500 capitalize">{{ Auth::user()->role ?? 'Member' }}</div>
                    </div>
                </div>
                <div class="py-2">
                    <a href="{{ route('pbm.classes.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('pbm.classes.index') ? 'border-l-4 border-sky-500 text-sky-600 bg-sky-50' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                        <i data-feather="home" class="w-4 h-4"></i> Beranda
                    </a>
                    <a href="{{ route('pbm.classes.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('pbm.classes.show') || request()->routeIs('pbm.classes.edit') ? 'border-l-4 border-sky-500 text-sky-600 bg-sky-50' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                        <i data-feather="book" class="w-4 h-4"></i> Kelas Saya
                    </a>
                    <a href="{{ route('pbm.schedule') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('pbm.schedule') ? 'border-l-4 border-sky-500 text-sky-600 bg-sky-50' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                        <i data-feather="calendar" class="w-4 h-4"></i> Jadwal
                    </a>
                    @if(Auth::user() && in_array(Auth::user()->role, ['ci', 'super_admin', 'dosen', 'kaprodi']))
                    <a href="{{ route('pbm.ci.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('pbm.ci.dashboard') ? 'border-l-4 border-sky-500 text-sky-600 bg-sky-50' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                        <i data-feather="check-square" class="w-4 h-4"></i> Dashboard CI
                    </a>
                    @endif
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 w-full min-w-0">
            @yield('content')
        </main>
        
    </div>
    @endif

    <script>
        function toggleMobileSidebar() {
            const drawer = document.getElementById('mobileSidebarDrawer');
            if (drawer) {
                drawer.classList.toggle('hidden');
                document.body.classList.toggle('modal-open', !drawer.classList.contains('hidden'));
            }
        }

        document.addEventListener("turbo:load", function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
        
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    </script>
    @stack('scripts')
</body>
</html>

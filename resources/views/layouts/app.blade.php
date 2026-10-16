<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      :class="{ 'dark': $store.theme.dark }"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Meta --}}
    <title>
        @hasSection('title')
            @yield('title') — {{ config('app.name') }}
        @else
            {{ config('app.name') }} — Politeknik Kesehatan Kemenkes Bandung
        @endif
    </title>
    <meta name="description" content="@yield('description', 'Politeknik Kesehatan Kemenkes Bandung — Kampus kesehatan terbaik di Jawa Barat.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('description', 'Politeknik Kesehatan Kemenkes Bandung.')">
    <meta property="og:image" content="@yield('og_image', asset('favicon.png'))">
    <meta property="og:image:alt" content="{{ config('app.name') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="@yield('og_image', asset('favicon.png'))">

    {{-- Favicon --}}
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- DNS Prefetch --}}
    <link rel="dns-prefetch" href="//fonts.bunny.net">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@hotwired/turbo@8.0.4/dist/turbo.es2017-umd.js" defer></script>

    {{-- Head extras --}}
    @stack('head')
    <style>
        .simpel-bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
            pointer-events: none;
            filter: blur(1.2px); /* Soft focus for bubbles/sparkles */
        }
        /* Light Mode: Floating waterdrops */
        .simpel-particle {
            position: absolute;
            bottom: -120px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, rgba(0, 162, 154, 0.12) 50%, rgba(255, 255, 255, 0) 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatUp 15s linear infinite;
        }
        /* Dark Mode: Sparkling stars */
        .dark .simpel-particle {
            bottom: auto;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 8px 1.5px rgba(255, 255, 255, 0.85), 0 0 16px 3px rgba(56, 189, 248, 0.45);
            animation: float-and-sparkle 8s ease-in-out infinite alternate;
        }
        @keyframes floatUp {
            0% {
                transform: translateY(0) translateX(0) scale(0.8);
                opacity: 0;
            }
            10% {
                opacity: 0.75;
            }
            90% {
                opacity: 0.75;
            }
            100% {
                transform: translateY(-125vh) translateX(80px) scale(1.3);
                opacity: 0;
            }
        }
        @keyframes float-and-sparkle {
            0% {
                opacity: 0.15;
                transform: translateY(0) scale(0.7);
            }
            50% {
                opacity: 0.85;
                transform: translateY(-15px) scale(1.2);
            }
            100% {
                opacity: 0.15;
                transform: translateY(-30px) scale(0.7);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-teal-50 dark:bg-gray-950 font-sans text-gray-900 dark:text-white antialiased">
    {{-- Floating waterdrops background --}}
    <div class="simpel-bg-particles" id="simpelBgParticles"></div>

    @if(session('login_as_original_admin_id'))
        <div class="bg-amber-500 text-white text-xs font-bold px-4 py-2.5 flex items-center justify-between gap-3 sticky top-0 z-50 shadow-md">
            <span class="flex items-center gap-1.5">⚠️ Anda sedang <strong>Login As</strong> — {{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('admin.users.return-to-admin') }}" class="m-0">
                @csrf
                <button type="submit"
                        class="px-3 py-1 rounded-lg text-xs font-black bg-white text-amber-900 hover:bg-amber-100 transition-colors cursor-pointer">
                    ↩ Kembali ke Super Admin
                </button>
            </form>
        </div>
    @endif

    {{-- Skip to main content --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-brand-600 focus:text-white focus:rounded">
        Lompat ke konten utama
    </a>

    {{-- Navbar --}}
    <x-navbar :contextual-guide="$contextualGuide ?? null" />

    {{-- Sticky Google Account Linking Prompt --}}
    @if (session('link_google_account'))
        <div class="bg-brand-900 text-white border-b border-brand-850 sticky top-0 z-40 shadow-lg px-4 py-3">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🔗</span>
                    <span class="text-sm">
                        Akun Google Anda adalah milik <strong>{{ session('link_google_account.name') }}</strong>. Apakah Anda ingin menautkan akun Google ini ke akun Virtual Office tersebut?
                    </span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form method="POST" action="{{ route('auth.google.link') }}" class="m-0">
                        @csrf
                        <button type="submit"
                                class="px-4 py-1.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black transition-colors cursor-pointer !text-slate-950">
                            Ya, Tautkan
                        </button>
                    </form>
                    <form method="POST" action="{{ route('auth.google.dismiss') }}" class="m-0">
                        @csrf
                        <button type="submit"
                                class="px-4 py-1.5 rounded-xl bg-brand-800 hover:bg-brand-700 text-white text-xs font-bold transition-colors cursor-pointer">
                            Tidak
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
             class="fixed top-20 right-4 z-50 max-w-sm rounded-lg bg-brand-600 px-4 py-3 text-white shadow-lg"
             role="alert">
            {{ session('success') }}
        </div>
    @endif

    {{-- Main Content --}}
    <main id="main-content" tabindex="-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <x-footer />

    {{-- Virtual Concierge Widget --}}
    <x-concierge-widget />

    {{-- Script extras --}}
    @stack('scripts')

    <script>
        (function(){
            var currentMode = null;
            function initParticles() {
                var container = document.getElementById('simpelBgParticles');
                if (!container) return;
                var isDark = document.documentElement.classList.contains('dark');
                var modeKey = isDark ? 'dark' : 'light';
                if (currentMode === modeKey) return; // Prevent redundant generation
                currentMode = modeKey;
                
                container.innerHTML = '';
                var particleCount = isDark ? 40 : 25;
                for (var i = 0; i < particleCount; i++) {
                    var particle = document.createElement('div');
                    particle.className = 'simpel-particle';
                    if (isDark) {
                        // Dark mode: Sparkling stars
                        var size = Math.random() * 3 + 2.5;
                        particle.style.width = size + 'px';
                        particle.style.height = size + 'px';
                        particle.style.left = (Math.random() * 100) + '%';
                        particle.style.top = (Math.random() * 100) + '%';
                        var duration = Math.random() * 6 + 5;
                        var delay = Math.random() * -10;
                        particle.style.animationName = 'float-and-sparkle';
                        particle.style.animationDuration = duration + 's';
                        particle.style.animationDelay = delay + 's';
                    } else {
                        // Light mode: Floating waterdrops
                        var size = Math.random() * 60 + 25;
                        particle.style.width = size + 'px';
                        particle.style.height = size + 'px';
                        particle.style.left = (Math.random() * 100) + '%';
                        var duration = Math.random() * 25 + 20;
                        var delay = Math.random() * -45;
                        particle.style.animationName = 'floatUp';
                        particle.style.animationDuration = duration + 's';
                        particle.style.animationDelay = delay + 's';
                    }
                    container.appendChild(particle);
                }
            }
            
            initParticles();
            document.addEventListener('turbo:load', function() {
                currentMode = null; // Reset to force re-render
                initParticles();
            });
            
            // Watch for HTML class changes to regenerate particles when theme toggles
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        initParticles();
                    }
                });
            });
            observer.observe(document.documentElement, { attributes: true });
        })();
    </script>
</body>
</html>

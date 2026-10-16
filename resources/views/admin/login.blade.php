<!DOCTYPE html>
<html lang="id"
      x-data
      :class="{ 'dark': $store.theme.dark }"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .simpel-bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 5;
            pointer-events: none;
        }
        .simpel-particle {
            position: absolute;
            bottom: -120px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.3) 0%, rgba(0, 162, 154, 0.14) 50%, rgba(255, 255, 255, 0) 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatUp 15s linear infinite;
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
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#004d47] via-[#006e65] to-[#008f87] font-sans antialiased
             flex items-center justify-center p-4 relative overflow-hidden">
    {{-- Floating waterdrops background --}}
    <div class="simpel-bg-particles" id="simpelBgParticles"></div>

    {{-- Ambient light effects / Futuristic Tech Mesh --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -left-40 h-[600px] w-[600px] rounded-full bg-teal-500/10 dark:bg-teal-500/20 blur-[130px] animate-pulse" style="animation-duration: 8s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[800px] w-[800px] rounded-full bg-cyan-500/5 dark:bg-cyan-500/10 blur-[160px]"></div>
        <div class="absolute -bottom-40 -right-40 h-[600px] w-[600px] rounded-full bg-yellow-500/5 dark:bg-yellow-500/15 blur-[130px] animate-pulse" style="animation-duration: 10s;"></div>
        
        <!-- Grid overlay -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#00a29a05_1px,transparent_1px),linear-gradient(to_bottom,#00a29a05_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#ffffff03_1px,transparent_1px),linear-gradient(to_bottom,#ffffff03_1px,transparent_1px)] bg-[size:3.5rem_3.5rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)]"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo-polkesban.png') }}" alt="Poltekkes Bandung" class="mx-auto mb-4 h-24 object-contain drop-shadow-[0_0_15px_rgba(0,162,154,0.15)]">
            <h1 class="text-3xl font-black text-white tracking-tight font-sans">Admin Panel</h1>
            <p class="text-xs font-semibold text-teal-100 tracking-wider uppercase mt-1">Politeknik Kesehatan Kemenkes Bandung</p>
        </div>

        @include('partials.context-guide', [
            'guide' => $contextualGuide ?? null,
            'containerClass' => 'mb-6',
            'defaultOpen' => true,
        ])

        {{-- Card --}}
        <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-[2.5rem] border border-gray-200/80 dark:border-gray-800/80 p-8 shadow-2xl shadow-teal-500/5 dark:shadow-teal-950/30">

            @if($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800
                            px-4 py-3 text-sm text-red-700 dark:text-red-300" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success') || session('status'))
                <div class="mb-5 rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800
                            px-4 py-3 text-sm text-green-700 dark:text-green-300" role="status">
                    {{ session('success') ?? session('status') }}
                </div>
            @endif

            <form action="{{ route('admin.login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        Email / NIP
                    </label>
                    <input id="email" name="email" type="text"
                           value="{{ old('email') }}"
                           autocomplete="username" required autofocus
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800
                                  px-4 py-2.5 text-sm text-gray-900 dark:text-white
                                  placeholder-gray-400 dark:placeholder-gray-500
                                  focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-700
                                  transition-colors">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        Password
                    </label>
                    <div class="relative" data-password-toggle>
                        <input id="password" name="password" type="password"
                               autocomplete="current-password" required data-password-toggle-input
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                      bg-gray-50 dark:bg-gray-800
                                      px-4 py-2.5 pr-24 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-700
                                      transition-colors">
                        <button type="button" data-password-toggle-button
                                class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-bold text-teal-600 transition-colors hover:text-teal-500 dark:text-teal-400 dark:hover:text-teal-300 focus:outline-none focus:ring-2 focus:ring-teal-500 rounded-lg">
                            Lihat
                        </button>
                    </div>
                </div>

                <div>
                    <label for="captcha" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        Captcha
                    </label>
                    <div class="mb-2 rounded-xl border border-dashed border-gray-300 dark:border-gray-700
                                bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Berapa hasil dari {{ $captchaQuestion }}?
                    </div>
                    <input id="captcha" name="captcha" type="text"
                           value="{{ old('captcha') }}"
                           inputmode="numeric" autocomplete="off" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800
                                  px-4 py-2.5 text-sm text-gray-900 dark:text-white
                                  placeholder-gray-400 dark:placeholder-gray-500
                                  focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-700
                                  transition-colors"
                           placeholder="Masukkan hasil perhitungan">
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2">
                        <input id="remember" name="remember" type="checkbox"
                               class="w-4 h-4 rounded text-teal-600 border-gray-300 dark:border-gray-600">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Ingat saya</span>
                    </label>
                    <a href="{{ route('admin.password.request') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-500 dark:text-teal-400 dark:hover:text-teal-300">
                        Lupa password?
                    </a>
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-teal-600 hover:bg-teal-500
                               px-5 py-3 text-sm font-bold text-white
                               transition-colors duration-200 shadow-lg shadow-teal-950/20
                               focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                    Masuk ke Admin Panel
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-200 dark:text-gray-500 mt-6">
            <a href="{{ url('/') }}" class="hover:underline">← Kembali ke website</a>
        </p>
    </div>

    <script>
        (function(){
            var container = document.getElementById('simpelBgParticles');
            if (!container) return;
            var particleCount = 25;
            for (var i = 0; i < particleCount; i++) {
                var particle = document.createElement('div');
                particle.className = 'simpel-particle';
                var size = Math.random() * 80 + 20;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = (Math.random() * 100) + '%';
                var duration = Math.random() * 15 + 10;
                var delay = Math.random() * -25;
                particle.style.animationDuration = duration + 's';
                particle.style.animationDelay = delay + 's';
                container.appendChild(particle);
            }
        })();

        // Toggle password visibility
        (function(){
            var toggleContainers = document.querySelectorAll('[data-password-toggle]');
            toggleContainers.forEach(function(container) {
                var input = container.querySelector('[data-password-toggle-input]');
                var button = container.querySelector('[data-password-toggle-button]');
                if (input && button) {
                    button.addEventListener('click', function() {
                        if (input.type === 'password') {
                            input.type = 'text';
                            button.textContent = 'Sembunyikan';
                        } else {
                            input.type = 'password';
                            button.textContent = 'Lihat';
                        }
                    });
                }
            });
        })();
    </script>
</body>
</html>

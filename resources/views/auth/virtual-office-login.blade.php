<!DOCTYPE html>
<html lang="id"
      x-data
      :class="{ 'dark': $store.theme.dark }"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk Virtual Office — {{ config('app.name') }}</title>
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
        {{-- Logo & Header --}}
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo-polkesban.png') }}" alt="Poltekkes Bandung" class="mx-auto mb-4 h-24 object-contain drop-shadow-[0_0_15px_rgba(0,162,154,0.15)]">
            <h1 class="text-3xl font-black text-white tracking-tight font-sans">Virtual Office</h1>
            <p class="text-xs font-semibold text-teal-100 tracking-wider uppercase mt-1">Politeknik Kesehatan Kemenkes Bandung</p>
        </div>

        {{-- Error or Success Alert --}}
        @if($errors->any())
            <div class="mb-5 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 px-4 py-3 text-sm text-red-750 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        {{-- Login Options Card --}}
        <div class="bg-white/45 dark:bg-gray-950/50 backdrop-blur-xl rounded-[2.5rem] border border-gray-250/60 dark:border-gray-800/60 p-8 shadow-2xl shadow-teal-500/5 dark:shadow-teal-950/35">
            <h2 class="text-xl font-black text-gray-900 dark:text-white mb-1.5 text-center font-sans">Pilih Akses Masuk</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-6">Silakan pilih jenis akun Anda untuk melanjutkan ke layanan internal Poltekkes Bandung.</p>

            <div class="space-y-4">
                {{-- Pegawai --}}
                <a href="{{ route('pegawai.login') }}"
                   class="flex items-center justify-between p-4 rounded-2xl border border-gray-100 dark:border-gray-800/80 bg-gray-50/50 hover:bg-white dark:bg-gray-800/40 dark:hover:bg-gray-800/80 text-gray-900 dark:text-white transition-all duration-300 group hover:border-teal-500 hover:shadow-lg hover:shadow-teal-500/10">
                    <div class="flex items-center gap-3.5">
                        <span class="text-2xl drop-shadow-[0_0_8px_rgba(0,162,154,0.2)]">👨‍💼</span>
                        <div class="text-left">
                            <p class="text-sm font-bold leading-tight group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">Dosen & Pegawai</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Masuk menggunakan akun SIM Kepegawaian</p>
                        </div>
                    </div>
                    <span class="text-teal-600 dark:text-teal-400 group-hover:translate-x-1.5 transition-transform duration-300">➔</span>
                </a>

                {{-- Mahasiswa --}}
                <a href="{{ route('mahasiswa.login') }}"
                   class="flex items-center justify-between p-4 rounded-2xl border border-gray-100 dark:border-gray-800/80 bg-gray-50/50 hover:bg-white dark:bg-gray-800/40 dark:hover:bg-gray-800/80 text-gray-900 dark:text-white transition-all duration-300 group hover:border-cyan-500 hover:shadow-lg hover:shadow-cyan-500/10">
                    <div class="flex items-center gap-3.5">
                        <span class="text-2xl drop-shadow-[0_0_8px_rgba(14,165,233,0.2)]">🎓</span>
                        <div class="text-left">
                            <p class="text-sm font-bold leading-tight group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">Mahasiswa Aktif</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Masuk menggunakan akun SIAKAD</p>
                        </div>
                    </div>
                    <span class="text-cyan-600 dark:text-cyan-400 group-hover:translate-x-1.5 transition-transform duration-300">➔</span>
                </a>

                {{-- Admin --}}
                <a href="{{ route('admin.login') }}"
                   class="flex items-center justify-between p-4 rounded-2xl border border-gray-100 dark:border-gray-800/80 bg-gray-50/50 hover:bg-white dark:bg-gray-800/40 dark:hover:bg-gray-800/80 text-gray-900 dark:text-white transition-all duration-300 group hover:border-yellow-500 hover:shadow-lg hover:shadow-yellow-500/10">
                    <div class="flex items-center gap-3.5">
                        <span class="text-2xl drop-shadow-[0_0_8px_rgba(234,179,8,0.2)]">⚙️</span>
                        <div class="text-left">
                            <p class="text-sm font-bold leading-tight group-hover:text-yellow-600 dark:group-hover:text-yellow-500 transition-colors">Pengelola Halaman</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Masuk ke panel admin unit kerja / prodi</p>
                        </div>
                    </div>
                    <span class="text-yellow-600 dark:text-yellow-500 group-hover:translate-x-1.5 transition-transform duration-300">➔</span>
                </a>

                {{-- Separator --}}
                <div class="relative py-4">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-white px-4 text-xs font-bold text-gray-400 dark:bg-gray-900 dark:text-gray-500 font-sans">atau</span>
                    </div>
                </div>

                {{-- Google Login for Guests --}}
                <a href="{{ route('auth.google') }}"
                   class="flex items-center justify-center gap-3 w-full py-3.5 px-4 rounded-2xl bg-white hover:bg-gray-50 text-gray-900 font-bold text-sm transition-all duration-300 border border-gray-250 hover:border-teal-500 hover:shadow-lg hover:shadow-teal-500/10 dark:bg-gray-800 dark:text-white dark:border-gray-800 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                        <path fill="#EA4335" d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.13-5.136 4.13A5.87 5.87 0 0 1 8 12.662a5.87 5.87 0 0 1 5.99-5.87 5.75 5.75 0 0 1 3.996 1.6l3.1-3.1A10.15 10.15 0 0 0 13.99 2 10.18 10.18 0 0 0 3.8 12.18 10.18 10.18 0 0 0 13.99 22.36a9.93 9.93 0 0 0 9.77-7.975c.14-.65.24-1.375.24-2.1H12.24z"/>
                    </svg>
                    <span>Masuk dengan Google</span>
                </a>
            </div>
        </div>

        <p class="text-center text-[10px] tracking-wide text-gray-400 dark:text-gray-600 mt-8 font-sans">
            &copy; 2026 Politeknik Kesehatan Kemenkes Bandung. All rights reserved.
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
    </script>
</body>
</html>

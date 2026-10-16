@extends('layouts.app')

@section('title', 'Login Mahasiswa')
@section('description', 'Masuk ke Portal Mahasiswa Poltekkes Bandung untuk mengelola portofolio, pencapaian, dan profil akademik.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="border-t border-slate-200 dark:border-gray-800 bg-gradient-to-b from-slate-50 via-white to-slate-100 dark:from-gray-950 dark:via-gray-950 dark:to-gray-950">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_26rem] lg:items-start">
            <div class="rounded-[2rem] border border-slate-200 bg-slate-950 p-7 text-white shadow-xl shadow-slate-300/40 sm:p-8 lg:p-10 relative z-10"
                 style="background-image: linear-gradient(135deg, #0f172a 0%, #0b1220 52%, #134e4a 100%);">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-[11px] font-black uppercase tracking-[0.22em] text-white/90">
                    Portal Mahasiswa Poltekkes Bandung
                </span>
                <h1 class="mt-5 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                    Masuk ke Portal Mahasiswa.
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-200">
                    Gunakan NIM dan password Anda untuk mengakses portofolio, profil, dan layanan mahasiswa.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-brand-300">Yang bisa dilakukan</p>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-100">
                            <li>Kelola portofolio dan pencapaian akademik.</li>
                            <li>Perbarui data profil dalam satu panel.</li>
                            <li>Akses tetap konsisten dengan identitas Poltekkes Bandung.</li>
                        </ul>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-sky-300">Belum punya akun?</p>
                        <p class="mt-3 text-sm leading-6 text-slate-100">Lanjutkan ke registrasi mahasiswa dan isi data sesuai KTM atau data akademik resmi.</p>
                        <a href="{{ route('mahasiswa.register') }}"
                           class="mt-4 inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-brand-100">
                            Daftar Akun Mahasiswa
                        </a>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-md lg:justify-self-end">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/80 dark:bg-gray-900 dark:border-gray-800 relative z-10">
                <div class="mb-6 text-center">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('images/logo-polkesban.png') }}" alt="Poltekkes Bandung"
                             class="mx-auto mb-3 h-16 object-contain">
                    </a>
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Portal Mahasiswa</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masuk untuk mengelola portofolio Anda</p>
                </div>

                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                        {{ session('info') }}
                    </div>
                @endif

                @if(session('success') || session('status'))
                    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                        {{ session('success') ?? session('status') }}
                    </div>
                @endif

                <form action="{{ route('mahasiswa.login.post') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="nim" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">NIM</label>
                        <input id="nim" name="nim" type="text"
                               value="{{ old('nim') }}"
                               autocomplete="username"
                               class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                               placeholder="Masukkan NIM Anda" required>
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <div class="relative" data-password-toggle>
                            <input id="password" name="password" type="password"
                                   autocomplete="current-password" data-password-toggle-input
                                   class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 pr-24 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                                   placeholder="••••••••" required>
                            <button type="button" data-password-toggle-button
                                    class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 rounded-lg">
                                Lihat
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="captcha" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Captcha</label>
                        <div class="mb-2 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            Berapa hasil dari {{ $captchaQuestion }}?
                        </div>
                        <input id="captcha" name="captcha" type="text"
                               value="{{ old('captcha') }}"
                               inputmode="numeric" autocomplete="off"
                               class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                               placeholder="Masukkan hasil perhitungan" required>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex cursor-pointer items-center gap-2 text-gray-600 dark:text-gray-400">
                            <input type="checkbox" name="remember" class="rounded border-gray-300">
                            Ingat saya
                        </label>
                        <a href="{{ route('mahasiswa.password.request') }}" class="font-medium text-brand-600 hover:underline">
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-brand-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                        Masuk
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum punya akun?
                    <a href="{{ route('mahasiswa.register') }}" class="font-medium text-brand-600 hover:underline">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

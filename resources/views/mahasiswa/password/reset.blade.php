@extends('layouts.app')

@section('title', 'Reset Password Mahasiswa')
@section('description', 'Atur ulang password Portal Mahasiswa Poltekkes Bandung menggunakan tautan email reset.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="border-t border-slate-200 bg-gradient-to-b from-slate-50 via-white to-slate-100">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_26rem] lg:items-start">
            <div class="rounded-[2rem] border border-slate-200 bg-slate-950 p-7 text-white shadow-xl shadow-slate-300/40 sm:p-8 lg:p-10"
                 style="background-image: linear-gradient(135deg, #0f172a 0%, #172554 55%, #115e59 100%);">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-[11px] font-black uppercase tracking-[0.22em] text-white/90">
                    Password Baru
                </span>
                <h1 class="mt-5 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                    Atur Ulang Password Portal.
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-200">
                    Buat password baru untuk akun mahasiswa Anda, lalu gunakan password tersebut saat login berikutnya.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-emerald-300">Password yang baik</p>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-100">
                            <li>Minimal 8 karakter.</li>
                            <li>Gabungkan huruf, angka, dan simbol jika memungkinkan.</li>
                            <li>Jangan gunakan password lama yang mudah ditebak.</li>
                        </ul>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-sky-300">Perlu login lagi</p>
                        <p class="mt-3 text-sm leading-6 text-slate-100">Setelah password diubah, gunakan password baru tersebut saat masuk ke Portal Mahasiswa.</p>
                        <a href="{{ route('mahasiswa.login') }}"
                           class="mt-4 inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-sky-100">
                            Ke Login Mahasiswa
                        </a>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-md lg:justify-self-end">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/80 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-6 text-center">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset('images/logo-polkesban.png') }}" alt="Poltekkes Bandung" class="mx-auto mb-3 h-16 object-contain">
                        </a>
                        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Reset Password</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Simpan password baru untuk akun mahasiswa Anda</p>
                    </div>

                    @if($errors->any())
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('mahasiswa.password.update') }}" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email Mahasiswa</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required
                                   class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                                   placeholder="nim@mhs.polkesban.ac.id">
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Password Baru</label>
                            <div class="relative" data-password-toggle>
                                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" data-password-toggle-input
                                       class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 pr-24 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                                       placeholder="Minimal 8 karakter">
                                <button type="button" data-password-toggle-button
                                        class="absolute inset-y-0 right-3 inline-flex items-center rounded-lg text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-300 dark:text-brand-400 dark:hover:text-brand-300">
                                    Lihat
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password Baru</label>
                            <div class="relative" data-password-toggle>
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required data-password-toggle-input
                                       class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 pr-24 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                                       placeholder="Ulangi password baru">
                                <button type="button" data-password-toggle-button
                                        class="absolute inset-y-0 right-3 inline-flex items-center rounded-lg text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-300 dark:text-brand-400 dark:hover:text-brand-300">
                                    Lihat
                                </button>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full rounded-xl bg-brand-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                            Simpan Password Baru
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
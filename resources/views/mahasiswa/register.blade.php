@extends('layouts.app')

@section('title', 'Daftar Akun Mahasiswa')
@section('description', 'Daftar akun Portal Mahasiswa Poltekkes Bandung untuk mulai mengelola profil dan portofolio mahasiswa.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="border-t border-slate-200 bg-gradient-to-b from-slate-50 via-white to-slate-100">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.08fr)_28rem] lg:items-start">
            <div class="rounded-[2rem] border border-slate-200 bg-slate-950 p-7 text-white shadow-xl shadow-slate-300/40 sm:p-8 lg:p-10"
                 style="background-image: linear-gradient(135deg, #0f172a 0%, #172554 55%, #0f766e 100%);">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-[11px] font-black uppercase tracking-[0.22em] text-white/90">
                    Registrasi Mahasiswa
                </span>
                <h1 class="mt-5 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                    Buat Akun Mahasiswa.
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-200">
                    Lengkapi data sesuai KTM atau data akademik agar akun Anda bisa langsung digunakan.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-emerald-300">Siapkan data</p>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-100">
                            <li>NIM aktif dan nama sesuai data akademik.</li>
                            <li>Pilih program studi yang benar.</li>
                            <li>Gunakan email yang rutin diakses.</li>
                        </ul>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-sky-300">Sudah punya akun?</p>
                        <p class="mt-3 text-sm leading-6 text-slate-100">Jika akun sudah tersedia, langsung masuk ke portal tanpa perlu mengulangi proses pendaftaran.</p>
                        <a href="{{ route('mahasiswa.login') }}"
                           class="mt-4 inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-sky-100">
                            Masuk ke Portal
                        </a>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-md lg:max-w-none lg:justify-self-end">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/80 dark:bg-gray-900 dark:border-gray-800">
                <div class="mb-6 text-center">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('images/logo-polkesban.png') }}" alt="Poltekkes Bandung"
                             class="mx-auto mb-3 h-14 object-contain">
                    </a>
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Buat Akun Mahasiswa</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Isi data diri sesuai KTM atau data akademik</p>
                </div>

                @if($errors->any())
                    <div class="mb-4 space-y-1 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                        @foreach($errors->all() as $err)
                            <p>• {{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('mahasiswa.register.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">NIM *</label>
                            <input name="nim" type="text" value="{{ old('nim') }}" required
                                   class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                                   placeholder="Contoh: P17320121001">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Angkatan *</label>
                            <input name="angkatan" type="number" value="{{ old('angkatan', date('Y') - 1) }}" required
                                   min="2015" max="{{ date('Y') + 1 }}"
                                   class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Lengkap *</label>
                        <input name="name" type="text" value="{{ old('name') }}" required
                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                               placeholder="Sesuai KTM">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Program Studi *</label>
                        <select name="program_studi_id" required
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                            <option value="">— Pilih Prodi —</option>
                            @foreach($prodis as $prodi)
                                <option value="{{ $prodi->id }}" {{ old('program_studi_id') == $prodi->id ? 'selected' : '' }}>
                                    {{ $prodi->jenjang }} {{ $prodi->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Email *</label>
                        <input name="email" type="email" value="{{ old('email') }}" required
                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                               placeholder="nim@mhs.polkesban.ac.id">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">No. HP</label>
                        <input name="no_hp" type="tel" value="{{ old('no_hp') }}"
                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                               placeholder="08xx-xxxx-xxxx">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Password *</label>
                            <div class="relative" data-password-toggle>
                                <input name="password" type="password" required minlength="8"
                                       autocomplete="new-password" data-password-toggle-input
                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 pr-24 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                                       placeholder="Min. 8 karakter">
                                <button type="button" data-password-toggle-button
                                        class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 rounded-lg">
                                    Lihat
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Konfirmasi *</label>
                            <div class="relative" data-password-toggle>
                                <input name="password_confirmation" type="password" required
                                       autocomplete="new-password" data-password-toggle-input
                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 pr-24 text-sm outline-none transition-colors focus:border-transparent focus:ring-2 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                                       placeholder="Ulangi password">
                                <button type="button" data-password-toggle-button
                                        class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 rounded-lg">
                                    Lihat
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="mt-2 w-full rounded-xl bg-brand-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                        Daftar Sekarang
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-gray-500 dark:text-gray-400">
                    Sudah punya akun?
                    <a href="{{ route('mahasiswa.login') }}" class="font-medium text-brand-600 hover:underline">Masuk</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

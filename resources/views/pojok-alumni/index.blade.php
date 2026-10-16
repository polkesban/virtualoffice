@extends('layouts.app')

@section('title', 'Pojok Alumni')
@section('description', 'Ruang publik alumni Poltekkes Bandung untuk membagikan jejak karier, testimoni, dan pembaruan data yang mendukung tracer study.')

@section('content')
<style>
    .glassmorphic-card {
        background-color: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
    }
    .dark .glassmorphic-card {
        background-color: rgba(17, 24, 39, 0.8) !important;
    }
</style>

<section class="bg-gradient-to-br from-brand-800 via-brand-900 to-slate-950 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-3 flex text-sm text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-white">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-white" aria-current="page">Pojok Alumni</li>
            </ol>
        </nav>
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(20rem,0.9fr)] lg:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-brand-200">Tracer Study</p>
                <h1 class="mt-3 max-w-3xl text-3xl font-black tracking-tight text-white sm:text-5xl">Pojok Alumni untuk Testimoni, Jejak Karier, dan Cerita Dampak</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-brand-100 sm:text-base">Halaman ini menjadi pintu masuk alumni untuk mengisi testimoni, memperbarui jejak karier, dan membantu Poltekkes Bandung membangun tracer study yang lebih akurat.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('mahasiswa.login') }}" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">Portal Mahasiswa</a>
                    <a href="#form-testimoni" class="inline-flex items-center justify-center rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Isi Testimoni Alumni</a>
                </div>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/10 p-6 text-white backdrop-blur-sm">
                <h2 class="text-lg font-black">Data yang paling membantu tracer study</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-brand-100" role="list">
                    <li>Program studi dan tahun lulus</li>
                    <li>Pekerjaan atau instansi saat ini</li>
                    <li>Tautan portofolio atau LinkedIn bila ada</li>
                    <li>Testimoni singkat tentang dampak pendidikan Poltekkes Bandung</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="bg-teal-50/20 dark:bg-gray-950/50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 xl:grid-cols-[minmax(0,1.15fr)_minmax(22rem,0.85fr)]">
            <div id="form-testimoni" class="glassmorphic-card rounded-3xl border border-slate-250/20 dark:border-gray-800/35 p-6 shadow-sm sm:p-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Form Alumni</p>
                    <h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white">Kirim testimoni dan pembaruan karier</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-gray-300">Isian ini terbuka untuk alumni. Data akan disimpan sebagai bahan tracer study, dan testimoni publik hanya akan ditayangkan setelah dikurasi tim kampus.</p>
                </div>

                @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 dark:bg-rose-950/30 px-4 py-3 text-sm text-rose-700 dark:text-rose-450">
                    <p class="font-semibold">Beberapa isian masih perlu diperbaiki.</p>
                    <ul class="mt-2 list-disc pl-5" role="list">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('pojok-alumni.store') }}" method="POST" class="mt-8 space-y-5">
                    @csrf
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="nama" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Nama lengkap</label>
                            <input id="nama" name="nama" type="text" value="{{ old('nama') }}" required class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Email aktif</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                        <div>
                            <label for="telepon" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Nomor WhatsApp</label>
                            <input id="telepon" name="telepon" type="text" value="{{ old('telepon') }}" class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                        <div>
                            <label for="tahun_lulus" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Tahun lulus</label>
                            <input id="tahun_lulus" name="tahun_lulus" type="number" min="1970" max="{{ now()->year }}" value="{{ old('tahun_lulus') }}" required class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                    </div>

                    <div>
                        <label for="program_studi" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Program studi</label>
                        <select id="program_studi" name="program_studi" required class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                            <option value="" class="dark:bg-slate-950">Pilih program studi</option>
                            @foreach ($programStudi as $prodi)
                            <option value="{{ $prodi->nama }}" @selected(old('program_studi') === $prodi->nama) class="dark:bg-slate-950">{{ $prodi->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="instansi" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Instansi / tempat berkarya</label>
                            <input id="instansi" name="instansi" type="text" value="{{ old('instansi') }}" class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                        <div>
                            <label for="jabatan" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Peran atau jabatan</label>
                            <input id="jabatan" name="jabatan" type="text" value="{{ old('jabatan') }}" class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                        <div>
                            <label for="kota_domisili" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Kota domisili</label>
                            <input id="kota_domisili" name="kota_domisili" type="text" value="{{ old('kota_domisili') }}" class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                        <div>
                            <label for="linkedin_url" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Tautan LinkedIn</label>
                            <input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url') }}" placeholder="https://www.linkedin.com/in/..." class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </div>
                    </div>

                    <div>
                        <label for="portofolio_url" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Tautan portofolio</label>
                        <input id="portofolio_url" name="portofolio_url" type="url" value="{{ old('portofolio_url') }}" placeholder="https://portfolioanda.com" class="mt-2 w-full rounded-2xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                    </div>

                    <div>
                        <label for="testimoni" class="block text-sm font-semibold text-slate-800 dark:text-gray-200">Testimoni alumni</label>
                        <textarea id="testimoni" name="testimoni" rows="6" required class="mt-2 w-full rounded-3xl border border-slate-250/30 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/40 px-4 py-3 text-sm leading-6 text-slate-900 dark:text-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100">{{ old('testimoni') }}</textarea>
                        <p class="mt-2 text-xs text-slate-500 dark:text-gray-400">Ceritakan dampak pengalaman belajar di Poltekkes Bandung terhadap karier, pelayanan, atau perjalanan profesional Anda.</p>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200/20 dark:border-gray-850/30 bg-white/45 dark:bg-gray-900/45 px-4 py-4 text-sm text-slate-600 dark:text-gray-300">
                        <input type="checkbox" name="bersedia_dihubungi" value="1" @checked(old('bersedia_dihubungi')) class="mt-1 h-4 w-4 rounded border-slate-350 dark:border-gray-800 text-brand-600 focus:ring-brand-500">
                        <span>Saya bersedia dihubungi kembali oleh tim Poltekkes Bandung untuk keperluan tracer study atau verifikasi data.</span>
                    </label>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-brand-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">Kirim Testimoni</button>
                        <a href="{{ route('mahasiswa.login') }}" class="inline-flex items-center justify-center rounded-full border border-brand-200 px-6 py-3 text-sm font-semibold text-brand-700 dark:text-brand-400 transition hover:bg-brand-50 dark:hover:bg-brand-900/30">Masuk Portal Mahasiswa</a>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="glassmorphic-card rounded-3xl border border-slate-250/20 dark:border-gray-800/35 p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Akses Cepat</p>
                    <div class="mt-4 space-y-3">
                        <a href="{{ route('mahasiswa.login') }}" class="flex items-start justify-between rounded-2xl border border-slate-200/20 dark:border-gray-850/30 bg-white/45 dark:bg-slate-900/40 px-4 py-4 transition hover:border-brand-200 dark:hover:border-brand-800 hover:bg-brand-50 dark:hover:bg-brand-900/30">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Portal Mahasiswa</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-gray-300">Masuk untuk memperbarui portofolio, profil, dan jejak capaian.</p>
                            </div>
                            <span class="text-brand-700 dark:text-brand-400">→</span>
                        </a>
                        <a href="{{ route('mahasiswa.register') }}" class="flex items-start justify-between rounded-2xl border border-slate-200/20 dark:border-gray-850/30 bg-white/45 dark:bg-slate-900/40 px-4 py-4 transition hover:border-brand-200 dark:hover:border-brand-800 hover:bg-brand-50 dark:hover:bg-brand-900/30">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Registrasi Akun Mahasiswa</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-gray-300">Bila belum punya akun portal, daftar dari sini sebelum melengkapi portofolio.</p>
                            </div>
                            <span class="text-brand-700 dark:text-brand-400">→</span>
                        </a>
                    </div>
                </div>

                <div class="glassmorphic-card rounded-3xl border border-slate-250/20 dark:border-gray-800/35 p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Yang Sudah Ditayangkan</p>
                    <h2 class="mt-3 text-xl font-black text-slate-950 dark:text-white">Testimoni alumni terkurasi</h2>
                    @if ($testimoni->isEmpty())
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-gray-300">Belum ada testimoni alumni yang ditayangkan. Kirim cerita Anda melalui formulir agar dapat menjadi bagian dari etalase dampak lulusan Poltekkes Bandung.</p>
                    @else
                    <div class="mt-5 space-y-4">
                        @foreach ($testimoni as $item)
                        <article class="rounded-2xl border border-slate-200/20 dark:border-gray-850/30 bg-white/45 dark:bg-slate-900/40 px-4 py-4">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $item->nama }}</h3>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-700 dark:text-brand-450">{{ $item->program_studi }} • {{ $item->tahun_lulus }}</p>
                            @if ($item->jabatan || $item->instansi)
                            <p class="mt-2 text-sm text-slate-600 dark:text-gray-300">{{ collect([$item->jabatan, $item->instansi])->filter()->join(' • ') }}</p>
                            @endif
                            <p class="mt-3 text-sm leading-6 text-slate-700 dark:text-gray-350">{{ $item->testimoni }}</p>
                        </article>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
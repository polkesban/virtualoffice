@extends('layouts.app')
@section('title', 'Login Peserta - ' . $event->judul)
@section('description', 'Masuk sebagai peserta event untuk mengisi daftar hadir, tes, evaluasi, materi, dan sertifikat acara ' . $event->judul)

@section('content')
<section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-gradient-to-br from-brand-900 via-slate-900 to-emerald-950"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <nav class="mb-5 flex flex-wrap gap-2 text-sm text-white/70">
            <a href="{{ route('home') }}" class="hover:text-white">Beranda</a>
            <span>/</span>
            <a href="{{ route('unit.show', $page->slug) }}" class="hover:text-white">{{ $page->judul }}</a>
            <span>/</span>
            <a href="{{ route('unit.events.show', [$page->slug, $event->slug]) }}" class="hover:text-white">{{ $event->judul }}</a>
            <span>/</span>
            <span class="text-white">Login Peserta</span>
        </nav>

        <div class="max-w-3xl">
            <div class="flex flex-wrap gap-2 text-[11px] font-bold uppercase tracking-wider text-white/80">
                <span class="rounded-full bg-white/10 px-3 py-1">Portal Peserta</span>
                <span class="rounded-full bg-white/10 px-3 py-1">{{ $event->shouldBypassPublicRegistration() ? 'Daftar Hadir Langsung' : 'Login dengan Kredensial' }}</span>
            </div>
            <h1 class="mt-5 text-3xl font-black sm:text-5xl">Login Peserta Event</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-white/80">Gunakan username berupa kode registrasi dan password sementara yang diberikan panitia untuk membuka portal peserta, mengisi daftar hadir, mengerjakan tes, serta mengunduh sertifikat jika tersedia. Jika Anda sudah punya akun mahasiswa atau akun pegawai Poltekkes Bandung, Anda juga bisa login lewat portal yang sesuai untuk langsung diarahkan ke daftar hadir.</p>
        </div>
    </div>
</section>

<section class="bg-gray-50 py-12 dark:bg-gray-950">
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 sm:px-6 lg:grid-cols-[0.9fr,1.1fr] lg:px-8">
        <aside class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Informasi Event</h2>
            <dl class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Nama Event</dt>
                    <dd>{{ $event->judul }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Jadwal</dt>
                    <dd>{{ $event->mulai_at?->translatedFormat('l, d F Y H:i') ?: '—' }}</dd>
                    @if($event->selesai_at)
                        <dd>Selesai {{ $event->selesai_at->translatedFormat('l, d F Y H:i') }}</dd>
                    @endif
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Tempat</dt>
                    <dd>{{ $event->lokasi ?: 'Akan diinformasikan' }}</dd>
                </div>
                @if($event->narahubung_nama || $event->narahubung_whatsapp)
                    <div>
                        <dt class="font-semibold text-gray-900 dark:text-white">Narahubung</dt>
                        <dd>{{ $event->narahubung_nama ?: 'Panitia Event' }}</dd>
                        @if($event->narahubung_whatsapp)
                            <dd>{{ $event->narahubung_whatsapp }}</dd>
                        @endif
                    </div>
                @endif
            </dl>

            <div class="mt-6 rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                <p class="font-semibold text-gray-900 dark:text-white">Butuh bantuan?</p>
                <p class="mt-2">Jika Anda belum menerima username atau password sementara, hubungi panitia event. Peserta yang memakai akun mahasiswa atau pegawai bisa login dari portal yang sesuai dan sistem akan mencoba membuka daftar hadir event ini secara otomatis.</p>
            </div>

            @if($event->shouldBypassPublicRegistration())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200">
                    <p class="font-semibold">Registrasi publik dibypass untuk event ini.</p>
                    <p class="mt-2">Jika Anda sudah punya akun mahasiswa atau pegawai Poltekkes Bandung, gunakan salah satu tombol berikut agar setelah login Anda langsung diarahkan ke daftar hadir event.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('unit.events.attendance.direct', [$page->slug, $event->slug, 'portal' => 'mahasiswa']) }}" class="inline-flex rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-amber-400">Login Mahasiswa</a>
                        <a href="{{ route('unit.events.attendance.direct', [$page->slug, $event->slug, 'portal' => 'pegawai']) }}" class="inline-flex rounded-xl border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-amber-800 transition-colors hover:bg-amber-100 dark:border-amber-700 dark:bg-transparent dark:text-amber-200 dark:hover:bg-amber-900/20">Login Pegawai</a>
                    </div>
                </div>
            @endif
        </aside>

        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-6">
                <a href="{{ route('unit.events.show', [$page->slug, $event->slug]) }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">← Kembali ke halaman event</a>
                <h2 class="mt-3 text-2xl font-extrabold text-gray-900 dark:text-white">Masuk dengan Kredensial Peserta</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Username peserta menggunakan kode registrasi yang tertera pada lembar kredensial dari panitia. @if(request('redirect') === 'attendance') Setelah berhasil login Anda akan diarahkan langsung ke halaman daftar hadir. @endif</p>
            </div>

            @if(session('info'))
                <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-900/50 dark:bg-sky-900/20 dark:text-sky-300">{{ session('info') }}</div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('unit.events.login.submit', [$page->slug, $event->slug]) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', request('redirect')) }}">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Username Peserta</label>
                    <input type="text" name="kode_registrasi" value="{{ old('kode_registrasi', request('kode_registrasi')) }}" required placeholder="Contoh: EVT-AB12CD34" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm uppercase dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Password Sementara</label>
                    <input type="password" name="password" required placeholder="Masukkan password sementara" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">Masuk ke Portal Peserta</button>
            </form>
        </div>
    </div>
</section>
@endsection
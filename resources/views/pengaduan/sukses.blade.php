@extends('layouts.app')

@section('title', 'Pengaduan Berhasil Dikirim — Poltekkes Bandung')
@section('content')
<div class="min-h-screen bg-teal-50/20 dark:bg-gray-950/50 flex items-center justify-center py-16">
    <div class="mx-auto max-w-lg px-4 text-center">

        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-950/40 text-green-600 dark:text-green-400 text-4xl mb-6 shadow-lg">
            ✅
        </div>

        <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
            Terima Kasih!
        </h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed mb-6">
            Pengaduan / saran Anda telah kami terima dan akan segera ditindaklanjuti oleh unit terkait.
        </p>

        @if($tiket)
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

        <div class="glassmorphic-card rounded-3xl border border-gray-200 dark:border-gray-700 p-6 shadow-xl mb-6">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Nomor Tiket Anda</p>
            <div class="font-mono text-2xl font-extrabold text-brand-700 dark:text-brand-400 bg-brand-50 dark:bg-brand-950/20 rounded-2xl py-3 px-6 inline-block">
                {{ $tiket }}
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">
                Simpan nomor tiket ini untuk memantau status pengaduan Anda.
            </p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm transition-colors shadow-sm">
                ← Kembali ke Beranda
            </a>
            <a href="{{ route('pengaduan.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm transition-colors">
                Kirim Pengaduan Lain
            </a>
        </div>

        <div class="mt-8 rounded-2xl bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 p-4 text-xs text-blue-800 dark:text-blue-300 leading-relaxed text-left">
            <p class="font-bold mb-1">💬 Selanjutnya apa?</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Unit terkait akan meninjau pengaduan Anda dalam 3 hari kerja.</li>
                <li>Anda dapat menggunakan nomor tiket di atas untuk menanyakan status langsung ke unit terkait.</li>
                <li>Mohon simpan nomor tiket ini untuk referensi.</li>
            </ul>
        </div>
    </div>
</div>
@endsection

@extends('admin.layouts.admin')
@section('title', 'Hasil Scan QR - ' . $participant->nama)

@section('content')
<div class="p-6 max-w-lg mx-auto text-center mt-12">
    <div class="rounded-3xl border border-gray-100 bg-white p-8 shadow-md dark:border-gray-800 dark:bg-gray-900">
        @if(!$alreadyAttended)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-950/40 dark:text-green-300">
                <span class="text-3xl">✅</span>
            </div>
            <h2 class="mt-5 text-2xl font-black text-gray-900 dark:text-white">Kehadiran Dicatat</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Selamat! Tamu undangan telah berhasil dikonfirmasi kehadirannya.</p>
        @else
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-950/40 dark:text-yellow-300">
                <span class="text-3xl">⚠️</span>
            </div>
            <h2 class="mt-5 text-2xl font-black text-gray-900 dark:text-white">Sudah Hadir</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tamu undangan ini sudah terdaftar hadir hari ini sebelumnya.</p>
        @endif

        <div class="mt-6 rounded-2xl bg-gray-50 p-5 text-left text-sm dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="font-bold text-gray-500 dark:text-gray-400">Nama Tamu</dt>
                    <dd class="font-extrabold text-gray-900 dark:text-white text-right">{{ $participant->nama }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-bold text-gray-500 dark:text-gray-400">Kode Registrasi</dt>
                    <dd class="font-mono font-bold text-gray-900 dark:text-white">{{ $participant->kode_registrasi }}</dd>
                </div>
                @if(!empty($participant->meta['nama_wisudawan']))
                    <div class="flex justify-between border-t border-dashed border-gray-200 dark:border-gray-700 pt-3">
                        <dt class="font-bold text-gray-500 dark:text-gray-400">Wisudawan/wati</dt>
                        <dd class="font-extrabold text-purple-600 dark:text-purple-300 text-right">{{ $participant->meta['nama_wisudawan'] }}</dd>
                    </div>
                @endif
                @if(!empty($participant->meta['nomor_kursi']))
                    <div class="flex justify-between">
                        <dt class="font-bold text-gray-500 dark:text-gray-400">Nomor Kursi</dt>
                        <dd class="font-extrabold text-purple-600 dark:text-purple-300">{{ $participant->meta['nomor_kursi'] }}</dd>
                    </div>
                @endif
                <div class="flex justify-between border-t border-dashed border-gray-200 dark:border-gray-700 pt-3">
                    <dt class="font-bold text-gray-500 dark:text-gray-400">Waktu Scan</dt>
                    <dd class="text-gray-950 dark:text-gray-100 font-semibold">{{ now()->translatedFormat('H:i:s') }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-8 flex flex-col gap-3">
            <a href="{{ route('admin.unit-events.participants.scanner', [$unitPage, $unitEvent]) }}" class="rounded-xl bg-purple-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-purple-500">Buka Kamera Scanner</a>
            <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $unitEvent]) }}" class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-bold text-gray-700 transition-colors hover:border-purple-200 hover:text-purple-700 dark:border-gray-700 dark:text-gray-200">Daftar Peserta</a>
        </div>
    </div>
</div>
@endsection

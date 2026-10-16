@extends('admin.layouts.admin')
@section('title', 'Ubah Password')
@section('page-title', 'Ubah Password')
@section('page-subtitle', 'Silakan ubah password Anda demi keamanan akun.')

@section('content')
<div class="mx-auto max-w-xl p-6">
    <div class="mb-6">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
            ⬅️ Kembali ke Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <form method="POST" action="{{ route('admin.ubah-password.update') }}" class="space-y-4">
            @csrf

            <!-- Password Saat Ini -->
            <label class="block space-y-1.5">
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Password Saat Ini <span class="text-red-500">*</span></span>
                <input type="password" name="current_password" required autofocus
                    class="w-full rounded-xl border border-gray-250 bg-white px-4 py-2.5 text-sm font-medium text-gray-950 dark:bg-gray-950 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
            </label>

            <!-- Password Baru -->
            <label class="block space-y-1.5">
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Password Baru (min. 8 karakter) <span class="text-red-500">*</span></span>
                <input type="password" name="password" required minlength="8"
                    class="w-full rounded-xl border border-gray-250 bg-white px-4 py-2.5 text-sm font-medium text-gray-950 dark:bg-gray-950 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
            </label>

            <!-- Konfirmasi Password Baru -->
            <label class="block space-y-1.5">
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Konfirmasi Password Baru <span class="text-red-500">*</span></span>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="w-full rounded-xl border border-gray-250 bg-white px-4 py-2.5 text-sm font-medium text-gray-950 dark:bg-gray-950 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
            </label>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-750 hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-750 dark:text-gray-200">
                    Batal
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-500 transition shadow-sm">
                    Perbarui Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

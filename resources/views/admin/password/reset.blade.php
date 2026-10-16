<!DOCTYPE html>
<html lang="id"
      x-data
      :class="{ 'dark': $store.theme.dark }"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password Admin — {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 font-sans antialiased flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600 text-2xl font-black text-white shadow-xl shadow-brand-900/30">P</div>
            <h1 class="mt-4 text-xl font-extrabold text-gray-900 dark:text-white">Atur Ulang Password Admin</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masukkan password baru untuk akun admin Anda.</p>
        </div>

        <div class="rounded-3xl border border-gray-100 bg-white p-8 shadow-xl shadow-gray-200/50 dark:border-gray-800 dark:bg-gray-900 dark:shadow-gray-950/50">
            @if($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('admin.password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">Email Admin</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 dark:focus:ring-brand-700"
                           placeholder="admin@polkesban.ac.id">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">Password Baru</label>
                    <div class="relative" data-password-toggle>
                        <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" data-password-toggle-input
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-24 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 dark:focus:ring-brand-700"
                               placeholder="Minimal 8 karakter">
                        <button type="button" data-password-toggle-button
                                class="absolute inset-y-0 right-3 inline-flex items-center rounded-lg text-xs font-bold text-brand-600 transition-colors hover:text-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-300 dark:text-brand-400 dark:hover:text-brand-300">
                            Lihat
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">Konfirmasi Password Baru</label>
                    <div class="relative" data-password-toggle>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required data-password-toggle-input
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-24 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 dark:focus:ring-brand-700"
                               placeholder="Ulangi password baru">
                        <button type="button" data-password-toggle-button
                                class="absolute inset-y-0 right-3 inline-flex items-center rounded-lg text-xs font-bold text-brand-600 transition-colors hover:text-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-300 dark:text-brand-400 dark:hover:text-brand-300">
                            Lihat
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2">
                    Simpan Password Baru
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-600">
            <a href="{{ route('admin.login') }}" class="hover:underline">← Kembali ke login admin</a>
        </p>
    </div>
</body>
</html>
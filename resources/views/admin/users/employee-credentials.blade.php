@extends('admin.layouts.admin')
@section('title', 'Kredensial Pegawai')

@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🧾 Kredensial Pegawai Poltekkes Bandung</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Halaman ini membaca file kredensial pegawai terbaru hasil impor DUK. Login utama pegawai menggunakan NIP Baru melalui portal pegawai.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($employeeCredentialDownloadUrl)
            <a href="{{ $employeeCredentialDownloadUrl }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:bg-brand-900/20 dark:hover:text-brand-300">
                Unduh XLSX
            </a>
            @endif
            <a href="{{ route('admin.users.employee-credentials.print', ['file' => $selectedFilename]) }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                🖨️ Cetak / Simpan PDF
            </a>
        </div>
    </div>

    @if($employeeCredentialRows->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200">
            <p class="font-semibold">File kredensial pegawai belum ditemukan.</p>
            <p class="mt-2">Jalankan lagi command impor pegawai dengan opsi reset password agar file kredensial baru terbentuk dan bisa dicetak dari panel admin.</p>
            <p class="mt-2 font-mono text-xs">php artisan users:import-duk "DUK November 2025.xlsx" --reset-passwords</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Akun</p>
                <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $employeeCredentialSummary['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">File Kredensial</p>
                <p class="mt-2 text-sm font-bold text-gray-900 dark:text-white">{{ $employeeCredentialFileName }}</p>
                @if($employeeCredentialGeneratedAt)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employeeCredentialGeneratedAt->translatedFormat('d F Y H:i') }}</p>
                @endif
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sumber DUK</p>
                <p class="mt-2 text-sm font-bold text-gray-900 dark:text-white">{{ $employeeCredentialSourceLabel ?: 'Tidak tercatat' }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Portal login: {{ $pegawaiLoginUrl }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sumber Email</p>
                <div class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                    @foreach($employeeCredentialSummary['email_sources'] as $source => $count)
                        <p><span class="font-semibold uppercase">{{ $source }}</span>: {{ $count }}</p>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200">
            <p class="font-semibold">Dokumen rahasia internal.</p>
            <p class="mt-2">Bagikan hanya kepada pegawai yang bersangkutan. Username utama adalah NIP Baru pada kolom NIP / Username. Password pada tabel ini adalah password sementara hasil impor terakhir.</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 text-left">No</th>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">NIP / Username</th>
                            <th class="px-4 py-3 text-left">Email Akun</th>
                            <th class="px-4 py-3 text-left">Password Sementara</th>
                            <th class="px-4 py-3 text-left">Jabatan DUK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($employeeCredentialRows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $row['no'] }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</p>
                                @if($row['phone'])
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['phone'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <p class="font-mono font-semibold text-gray-900 dark:text-white">{{ $row['identity_number'] }}</p>
                                <p class="mt-1 text-gray-500 dark:text-gray-400">Login: {{ $row['login_identifier'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <p class="text-brand-600 dark:text-brand-300">{{ $row['email'] }}</p>
                                @if($row['email_source'])
                                    <p class="mt-1 text-gray-500 dark:text-gray-400 uppercase">{{ $row['email_source'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3"><code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs dark:bg-gray-800">{{ $row['password'] }}</code></td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $row['position'] ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
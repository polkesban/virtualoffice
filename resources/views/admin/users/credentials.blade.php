@extends('admin.layouts.admin')
@section('title', 'Tabel Kredensial Admin & Pegawai')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🔐 Tabel Kredensial Admin & Pegawai</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Halaman ini hanya dapat diakses oleh Super Admin. Tabel admin dan pegawai ditampilkan dalam satu halaman agar lebih mudah dicari dan dicetak.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.employee-credentials') }}"
               class="no-print px-4 py-2 rounded-xl border border-gray-200 text-gray-700 text-sm font-bold dark:border-gray-700 dark:text-gray-300">
                Halaman Pegawai Saja
            </a>
            <a href="{{ route('admin.users.credentials.print') }}" target="_blank"
               class="no-print px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-bold">
                🖨️ Cetak / Simpan PDF
            </a>
        </div>
    </div>

    <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 text-sm text-amber-800 dark:text-amber-300 mb-6">
        <strong>⚠️ RAHASIA — Dokumen internal.</strong>
        Segera ganti password setelah login pertama. Jangan bagikan ke pihak tidak berwenang.
        URL Login Admin: <strong>https://polkesban.ac.id/admin/login</strong>
        <span class="block mt-1">URL Login Pegawai: <strong>{{ $pegawaiLoginUrl }}</strong></span>
        <span class="block mt-1 text-xs">Label <strong>Manual / tidak tersimpan</strong> berarti akun dibuat manual dan password awalnya tidak bisa ditampilkan ulang oleh sistem. Untuk pegawai, login utama menggunakan NIP Baru.</span>
    </div>

    {{-- Struktural & Unit --}}
    <h2 class="text-sm font-extrabold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3">Struktural, Pusat & Unit Kerja</h2>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-x-auto mb-8">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Jabatan / Unit</th>
                    <th class="px-4 py-3 text-left">Email Login</th>
                    <th class="px-4 py-3 text-left">Password Sementara</th>
                    <th class="px-4 py-3 text-left">Menu Yang Dapat Dikelola</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($struktural as $credential)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $credential['no'] }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white text-xs">{{ $credential['jabatan'] }}</td>
                    <td class="px-4 py-3 text-xs text-brand-600 dark:text-brand-400 font-mono">{{ $credential['email'] }}</td>
                    <td class="px-4 py-3">
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded font-mono">{{ $credential['password'] }}</code>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $credential['menu'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Kaprodi --}}
    <h2 class="text-sm font-extrabold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3">Kepala Program Studi (Kaprodi)</h2>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Program Studi</th>
                    <th class="px-4 py-3 text-left">Email Login</th>
                    <th class="px-4 py-3 text-left">Password Sementara</th>
                    <th class="px-4 py-3 text-left">Menu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($kaprodi as $credential)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $credential['no'] }}</td>
                    <td class="px-4 py-3 text-xs">
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">Kaprodi</span>
                        <span class="ml-1 text-gray-900 dark:text-white">{{ $credential['prodi'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-brand-600 dark:text-brand-400 font-mono">{{ $credential['email'] }}</td>
                    <td class="px-4 py-3">
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded font-mono">{{ $credential['password'] }}</code>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">Profil Prodi, Berita, Pengumuman</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="text-sm font-extrabold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 mt-8">Admin Halaman Publik BEM & HIMA</h2>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Halaman Publik</th>
                    <th class="px-4 py-3 text-left">Email Login</th>
                    <th class="px-4 py-3 text-left">Password Sementara</th>
                    <th class="px-4 py-3 text-left">Akses</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($pageAdmins as $credential)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $credential['no'] }}</td>
                    <td class="px-4 py-3 text-xs">
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">Page Admin</span>
                        <span class="ml-1 text-gray-900 dark:text-white">{{ $credential['halaman'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-brand-600 dark:text-brand-400 font-mono">{{ $credential['email'] }}</td>
                    <td class="px-4 py-3">
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded font-mono">{{ $credential['password'] }}</code>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $credential['menu'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="text-sm font-extrabold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 mt-8">Pegawai Poltekkes Bandung</h2>
    @if($employeeCredentialRows->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200">
            <p class="font-semibold">Tabel pegawai belum tersedia.</p>
            <p class="mt-2">File kredensial pegawai terbaru belum ditemukan. Jalankan ulang impor pegawai bila perlu.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4 mb-4">
            <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pegawai</p>
                <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $employeeCredentialSummary['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">File Sumber</p>
                <p class="mt-2 text-sm font-bold text-gray-900 dark:text-white">{{ $employeeCredentialFileName ?: '—' }}</p>
                @if($employeeCredentialGeneratedAt)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employeeCredentialGeneratedAt->translatedFormat('d F Y H:i') }}</p>
                @endif
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sumber DUK</p>
                <p class="mt-2 text-sm font-bold text-gray-900 dark:text-white">{{ $employeeCredentialSourceLabel ?: 'Tidak tercatat' }}</p>
                @if($employeeCredentialDownloadUrl)
                    <a href="{{ $employeeCredentialDownloadUrl }}" class="mt-2 inline-flex text-xs font-semibold text-brand-600 hover:underline dark:text-brand-300">Unduh XLSX</a>
                @endif
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

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200 mb-4">
            <p class="font-semibold">Kredensial pegawai terakhir hasil impor DUK.</p>
            <p class="mt-2">Bagikan hanya kepada pegawai terkait. Username utama adalah NIP Baru, dan password di bawah ini adalah password sementara hasil impor terakhir.</p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
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
                        <td class="px-4 py-3"><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded font-mono">{{ $row['password'] }}</code></td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $row['position'] ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="text-xs text-gray-400 mt-6 text-center">
        Halaman ini hanya dapat diakses oleh Super Admin. URL Admin: <strong>https://polkesban.ac.id/admin/login</strong> • URL Pegawai: <strong>{{ $pegawaiLoginUrl }}</strong>
    </p>
</div>
@endsection

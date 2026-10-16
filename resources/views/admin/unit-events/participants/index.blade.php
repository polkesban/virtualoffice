@extends('admin.layouts.admin')
@section('title', 'Peserta Event Unit')
@section('page-title', 'Peserta Event Unit')
@section('page-subtitle', 'Daftar peserta, narasumber, panitia, kehadiran, nilai, surat tugas, dan sertifikat')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    @php
        $attendanceDayCount = max(1, $attendanceDates->count());
        $participantsBaseUrl = url('admin/halaman-unit/' . $unitPage->getKey() . '/events/' . $unitEvent->getKey() . '/participants');
        $importTemplateUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.participants.import-template')
            ? route('admin.unit-events.participants.import-template', [$unitPage, $unitEvent])
            : $participantsBaseUrl . '/import-template';
        $credentialsPrintUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.participants.credentials-print')
            ? route('admin.unit-events.participants.credentials-print', [$unitPage, $unitEvent])
            : $participantsBaseUrl . '/credentials-print';
        $credentialsCardsPrintUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.participants.credentials-cards-print')
            ? route('admin.unit-events.participants.credentials-cards-print', [$unitPage, $unitEvent])
            : $participantsBaseUrl . '/credentials-cards-print';
        $attendancePrintUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.participants.attendance-print')
            ? route('admin.unit-events.participants.attendance-print', [$unitPage, $unitEvent])
            : $participantsBaseUrl . '/attendance-print';
        $attendancePrintLemburUrl = $attendancePrintUrl . (str_contains($attendancePrintUrl, '?') ? '&' : '?') . 'format=lembur';
        $participantsCreateUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.participants.create')
            ? route('admin.unit-events.participants.create', [$unitPage, $unitEvent])
            : $participantsBaseUrl . '/create';
        $participantsImportUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.participants.import')
            ? route('admin.unit-events.participants.import', [$unitPage, $unitEvent])
            : $participantsBaseUrl . '/import';
        $participantsAttachEmployeesUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.participants.attach-employees')
            ? route('admin.unit-events.participants.attach-employees', [$unitPage, $unitEvent])
            : $participantsBaseUrl . '/attach-employees';
        $unitEventEditUrl = \Illuminate\Support\Facades\Route::has('admin.unit-events.edit')
            ? route('admin.unit-events.edit', [$unitPage, $unitEvent])
            : url('admin/halaman-unit/' . $unitPage->getKey() . '/events/' . $unitEvent->getKey() . '/edit');
        $employeeSearchUrl = request()->url();
        $linkedEmployeeIds = $participants->pluck('linked_user_id')->filter()->map(fn ($id) => (int) $id)->all();
    @endphp
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach(\App\Models\UnitEventParticipant::$peranLabels as $key => $label)
                @php
                    $displayLabel = ($unitEvent->jenis_kegiatan === 'wisuda' && $key === 'participant') ? 'Undangan' : $label;
                @endphp
                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $displayLabel }}</p>
                    <p class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $participants->where('peran', $key)->count() }}</p>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ $importTemplateUrl }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Unduh Template Excel</a>
            @if($unitEvent->jenis_kegiatan === 'wisuda')
                <a href="{{ route('unit.events.slides', [$unitPage->slug, $unitEvent->slug]) }}" target="_blank" class="rounded-xl px-4 py-2 text-sm font-bold transition-colors flex items-center gap-1.5 shadow-sm text-white" style="background-color: #0f766e !important;">
                    <span>🖥️</span> Tayangan Slide Wisudawan
                </a>
                <a href="{{ route('admin.unit-events.participants.scanner', [$unitPage, $unitEvent]) }}" class="rounded-xl px-4 py-2 text-sm font-bold transition-colors flex items-center gap-1.5 shadow-sm" style="background-color: #6d28d9 !important; color: #ffffff !important; display: inline-flex !important; align-items: center;">
                    <span>📷</span> Scanner QR Hadir
                </a>
                <button type="button" onclick="openWisudaPrintModal()" class="rounded-xl border px-4 py-2 text-sm font-bold transition-colors shadow-sm flex items-center gap-2 cursor-pointer hover:opacity-90" style="background-color: #faf5ff !important; color: #6b21a8 !important; border-color: #d8b4fe !important; display: inline-flex !important; align-items: center;">
                    <span>🎓</span>
                    <span>Cetak Undangan Wisuda (Per Prodi / Jurusan)</span>
                    <span class="rounded-full bg-purple-200 px-2 py-0.5 text-xs text-purple-900 font-extrabold">Ringan</span>
                </button>
            @endif
            <a href="{{ $credentialsPrintUrl }}" target="_blank" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Cetak Kredensial Peserta</a>
            <a href="{{ $credentialsCardsPrintUrl }}" target="_blank" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Cetak Kartu Kredensial</a>
            <a href="{{ $attendancePrintUrl }}" target="_blank" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Cetak Daftar Hadir</a>
            <a href="{{ $attendancePrintLemburUrl }}" target="_blank" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Cetak Daftar Hadir Lembur</a>
            <a href="{{ $participantsCreateUrl }}?peran=speaker" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-teal-500 shadow-sm flex items-center gap-1.5">
                <span>🎙️</span> + Tambah Narasumber
            </a>
            <a href="{{ $participantsCreateUrl }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">+ Tambah Peserta</a>
        </div>
    </div>

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

    <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Tarik Pegawai Poltekkes Bandung</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Gunakan master akun pegawai yang sudah diimpor dari DUK untuk menambahkan peserta event tanpa Excel. Admin bisa mencari manual berdasarkan nama, NIP, atau email, lalu menambahkan pegawai terpilih ke event ini.</p>
                <p class="mt-2 text-xs text-gray-400">Jika peserta pegawai sudah punya akun, sistem akan langsung menghubungkan ke portal pegawai sehingga QR dan daftar hadir tetap memakai akun aplikasi yang sama.</p>
            </div>

            <form method="GET" action="{{ $employeeSearchUrl }}" class="w-full rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60 lg:max-w-xl">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr,auto] md:items-end">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Cari Nama / NIP / Email Pegawai</label>
                        <input type="text" name="pegawai_q" value="{{ $employeeQuery }}" placeholder="Contoh: 1987..., siti, atau @polkesban" class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">Cari Pegawai</button>
                </div>
            </form>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-[1.1fr,0.9fr]">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">Hasil Pencarian Pegawai</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Cari pegawai lalu centang yang ingin ditambahkan ke event.</p>
                    </div>
                    @if($employeeQuery !== '')
                        <span class="rounded-full bg-white px-3 py-1 text-[11px] font-bold text-gray-600 dark:bg-gray-900 dark:text-gray-300">{{ $employeeSearchResults->count() }} hasil</span>
                    @endif
                </div>

                @if($employeeQuery === '')
                    <p class="mt-4 rounded-xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-400 dark:border-gray-700">Masukkan kata kunci pencarian untuk menampilkan kandidat pegawai.</p>
                @elseif($employeeSearchResults->isEmpty())
                    <p class="mt-4 rounded-xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-400 dark:border-gray-700">Tidak ada pegawai yang cocok dengan kata kunci tersebut.</p>
                @else
                    <form method="POST" action="{{ $participantsAttachEmployeesUrl }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="source" value="manual_search">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                                <span class="mb-1 block">Tambahkan sebagai</span>
                                <select name="peran" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                    @foreach(\App\Models\UnitEventParticipant::$peranLabels as $roleKey => $roleLabel)
                                        <option value="{{ $roleKey }}">{{ ($unitEvent->jenis_kegiatan === 'wisuda' && $roleKey === 'participant') ? 'Undangan' : $roleLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">Tambah / Perbarui Pegawai Terpilih</button>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead class="bg-white text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">Pilih</th>
                                        <th class="px-4 py-3 text-left">Pegawai</th>
                                        <th class="px-4 py-3 text-left hidden md:table-cell">Akun</th>
                                        <th class="px-4 py-3 text-left hidden xl:table-cell">Status Event</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($employeeSearchResults as $employee)
                                        @php $alreadyLinked = in_array((int) $employee->id, $linkedEmployeeIds, true); @endphp
                                        <tr class="bg-white dark:bg-gray-900">
                                            <td class="px-4 py-3 align-top">
                                                <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">NIP: {{ $employee->identity_number ?: '—' }}</p>
                                            </td>
                                            <td class="hidden px-4 py-3 align-top md:table-cell text-xs text-gray-500 dark:text-gray-400">
                                                <p>{{ $employee->email ?: '—' }}</p>
                                                <p class="mt-1">Portal Pegawai</p>
                                            </td>
                                            <td class="hidden px-4 py-3 align-top xl:table-cell text-xs">
                                                <span class="rounded-full px-2.5 py-1 font-bold {{ $alreadyLinked ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $alreadyLinked ? 'Sudah ada di event, bisa diperbarui' : 'Siap ditambahkan' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">Baca Surat Tugas Event</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ambil kandidat pegawai dari surat tugas event yang sudah diupload, lalu konfirmasi sebelum ditambahkan.</p>
                    </div>
                    <form method="GET" action="{{ $employeeSearchUrl }}">
                        @if($employeeQuery !== '')
                            <input type="hidden" name="pegawai_q" value="{{ $employeeQuery }}">
                        @endif
                        <input type="hidden" name="read_assignment_letter" value="1">
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">Baca Ulang Surat Tugas</button>
                    </form>
                </div>

                <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white/70 p-4 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                    @if($unitEvent->surat_tugas_event_path)
                        <p class="font-semibold text-gray-700 dark:text-gray-200">File aktif: {{ basename((string) $unitEvent->surat_tugas_event_path) }}</p>
                        <p class="mt-1">Parser otomatis saat ini aman untuk PDF digital berbasis teks. Jika surat tugas berupa scan gambar, hasilnya bisa kosong karena OCR belum aktif di server.</p>
                    @else
                        <p>Event ini belum memiliki surat tugas. Upload dulu dari halaman edit event untuk mengaktifkan preview otomatis.</p>
                        <a href="{{ $unitEventEditUrl }}" class="mt-3 inline-flex rounded-lg bg-white px-3 py-1.5 font-bold text-brand-700 transition-colors hover:bg-brand-50 dark:bg-gray-900 dark:text-brand-300">Buka Form Event</a>
                    @endif
                </div>

                @if($assignmentLetterPreview)
                    <div class="mt-4 rounded-xl border px-4 py-3 text-sm {{ $assignmentLetterPreview['status'] === 'matched' ? 'border-brand-200 bg-brand-50 text-brand-700 dark:border-brand-900/50 dark:bg-brand-900/20 dark:text-brand-300' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300' }}">
                        <p class="font-semibold">{{ $assignmentLetterPreview['message'] }}</p>
                        @if($assignmentLetterPreview['file_name'])
                            <p class="mt-1 text-xs opacity-80">Sumber: {{ $assignmentLetterPreview['file_name'] }}</p>
                        @endif
                        @if(($assignmentLetterPreview['matched_identity_count'] ?? 0) > 0 || ($assignmentLetterPreview['matched_name_count'] ?? 0) > 0)
                            <p class="mt-2 text-xs opacity-80">Match NIP: {{ $assignmentLetterPreview['matched_identity_count'] }} · Match nama: {{ $assignmentLetterPreview['matched_name_count'] }}</p>
                        @endif
                        @if(!empty($assignmentLetterPreview['unmatched_identity_numbers']))
                            <p class="mt-2 text-xs opacity-80">NIP belum ditemukan di master: {{ implode(', ', array_slice($assignmentLetterPreview['unmatched_identity_numbers'], 0, 5)) }}{{ count($assignmentLetterPreview['unmatched_identity_numbers']) > 5 ? ' ...' : '' }}</p>
                        @endif
                    </div>

                    @if(($assignmentLetterPreview['matches'] ?? collect())->isNotEmpty())
                        <form method="POST" action="{{ $participantsAttachEmployeesUrl }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="source" value="assignment_letter">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <span class="mb-1 block">Tambahkan sebagai</span>
                                    <select name="peran" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                        @foreach(\App\Models\UnitEventParticipant::$peranLabels as $roleKey => $roleLabel)
                                            <option value="{{ $roleKey }}">{{ ($unitEvent->jenis_kegiatan === 'wisuda' && $roleKey === 'participant') ? 'Undangan' : $roleLabel }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">Tarik Peserta dari Surat Tugas</button>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                                <table class="w-full text-sm">
                                    <thead class="bg-white text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left w-12">Pilih</th>
                                            <th class="px-4 py-3 text-left">Pegawai</th>
                                            <th class="px-4 py-3 text-left hidden lg:table-cell">Hasil Baca</th>
                                            <th class="px-4 py-3 text-left hidden xl:table-cell">Status Event</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach($assignmentLetterPreview['matches'] as $match)
                                            @php
                                                $employee = $match['user'];
                                                $alreadyLinked = in_array((int) $employee->id, $linkedEmployeeIds, true);
                                            @endphp
                                            <tr class="bg-white dark:bg-gray-900">
                                                <td class="px-4 py-3 align-top">
                                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" checked>
                                                </td>
                                                <td class="px-4 py-3 align-top">
                                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</p>
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">NIP: {{ $employee->identity_number ?: '—' }}</p>
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employee->email ?: '—' }}</p>
                                                </td>
                                                <td class="hidden px-4 py-3 align-top lg:table-cell text-xs text-gray-500 dark:text-gray-400">
                                                    <p class="font-semibold text-gray-700 dark:text-gray-200">{{ $match['match_label'] }}</p>
                                                    <p class="mt-1">Tingkat keyakinan: {{ $match['confidence_label'] }}</p>
                                                    <p class="mt-1">Nilai cocok: {{ $match['match_value'] }}</p>
                                                </td>
                                                <td class="hidden px-4 py-3 align-top xl:table-cell text-xs">
                                                    <span class="rounded-full px-2.5 py-1 font-bold {{ $alreadyLinked ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $alreadyLinked ? 'Sudah ada di event, bisa diperbarui' : 'Kandidat dari surat tugas' }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Impor Peserta dari Excel</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Silakan <a href="{{ $importTemplateUrl }}" class="font-extrabold text-brand-600 hover:text-brand-500 underline dark:text-brand-400 dark:hover:text-brand-300">Unduh Template Excel di sini</a>, isi data peserta sesuai kolom tabel, lalu unggah kembali file tersebut di sebelah kanan. Template ini menyediakan kolom <strong>Kategori Peserta</strong> serta <strong>NIP / NIS / NIK</strong> agar klasifikasi peserta dan pengaitan akun lama bisa langsung diproses saat impor.</p>
                <p class="mt-2 text-xs text-gray-400">Baris identik dengan data peserta yang sudah ada akan dilewati otomatis agar impor ulang tidak menghasilkan duplikasi penuh. Jika event sudah memiliki roster peserta hasil impor/admin, formulir registrasi publik akan otomatis dibypass sehingga peserta cukup login atau scan QR untuk mengisi daftar hadir.</p>
            </div>

            <form id="import-participants-form" method="POST" action="{{ $participantsImportUrl }}" enctype="multipart/form-data" class="w-full rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60 lg:max-w-xl">
                @csrf
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr,auto] md:items-end">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">File Excel / PDF Peserta</label>
                        <input type="file" id="import-file-input" name="participants_file" accept=".xlsx,.xls,.csv,.pdf" required class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900">
                        <p class="mt-1.5 text-xs text-gray-400">Format didukung: Excel (.xlsx, .xls), CSV (.csv), atau Daftar Calon Wisudawan (.pdf) via OCR otomatis.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">Impor Peserta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Search Filter & Prodi Filter for Participants / Invitation Links -->
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3 p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
        <div class="flex flex-wrap items-center gap-3 flex-1">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">🔍</span>
                <input type="text" id="participants-search" placeholder="Cari Nama Undangan, Wisudawan, ID..." class="block w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:border-brand-500 focus:bg-white dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
            </div>

            @if($unitEvent->jenis_kegiatan === 'wisuda')
                <form method="GET" action="{{ request()->url() }}" class="flex items-center">
                    <select name="program_studi_id" onchange="this.form.submit()" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-brand-500 focus:bg-white dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="">— Semua Program Studi —</option>
                        @foreach($prodiList as $prodi)
                            <option value="{{ $prodi->id }}" @selected(request('program_studi_id') == $prodi->id)>
                                {{ $prodi->nama }} ({{ $prodi->jenjang }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" form="bulk-delete-form" id="bulk-delete-btn" class="hidden rounded-xl px-4 py-2 text-sm font-bold text-white transition-colors bg-red-600 hover:bg-red-700 items-center gap-1">
                🗑️ Hapus Terpilih (<span id="selected-count">0</span>)
            </button>
            @if($unitEvent->jenis_kegiatan === 'wisuda')
                <a href="{{ route('admin.unit-events.participants.export-excel', [$unitPage, $unitEvent, 'program_studi_id' => request('program_studi_id')]) }}" class="rounded-xl px-4 py-2 text-sm font-bold transition-colors flex items-center gap-1.5" style="background-color: #16a34a !important; color: #ffffff !important;">
                    <span>📥</span> Export Excel Undangan
                </a>
            @endif
            @if($unitEvent->jenis_kegiatan === 'pbm' && ($unitEvent->sub_jenis_kegiatan === 'praktik' || $unitEvent->sub_jenis_kegiatan === 'gabungan' || stripos($unitEvent->judul, 'praktik') !== false || stripos($unitEvent->judul, 'magang') !== false))
                <a href="{{ route('admin.unit-events.participants.export-excel', [$unitPage, $unitEvent]) }}" class="rounded-xl px-4 py-2 text-sm font-bold transition-colors flex items-center gap-1.5" style="background-color: #0f766e !important; color: #ffffff !important;">
                    <span>📥</span> Unduh Rekap Kehadiran (Excel)
                </a>
            @endif
            <div class="text-xs text-gray-500 dark:text-gray-400 font-bold" id="participants-count-indicator">
                Menampilkan {{ $participants->count() }} data
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left w-12"><input type="checkbox" id="select-all-participants" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"></th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kontak</th>
                        <th class="px-4 py-3 text-left hidden xl:table-cell">Status</th>
                        <th class="px-4 py-3 text-left hidden 2xl:table-cell">Nilai</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($participants as $participant)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 participant-row">
                            <td class="px-4 py-3 align-middle">
                                <input type="checkbox" name="ids[]" value="{{ $participant->id }}" class="participant-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-3">
                                    @if($participant->foto_path)
                                        <img src="{{ \App\Support\MediaStorage::url($participant->foto_path) }}" alt="{{ $participant->nama }}" class="h-10 w-10 shrink-0 rounded-xl object-cover border border-teal-500/40 shadow-sm">
                                    @elseif($participant->peran === 'speaker')
                                        <div class="h-10 w-10 shrink-0 rounded-xl bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-xs border border-teal-200 dark:border-teal-800">🎙️</div>
                                    @endif
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white">{{ $participant->nama }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                                            <span class="rounded-full px-2.5 py-0.5 {{ $participant->peran === 'speaker' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300' : ($participant->peran === 'committee' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300') }}">{{ $participant->peran_label }}</span>
                                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $participant->kode_registrasi }}</span>
                                        </div>
                                        @if($participant->instansi || $participant->jabatan)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $participant->jabatan }}{{ $participant->jabatan && $participant->instansi ? ' • ' : '' }}{{ $participant->instansi }}</p>
                                        @endif
                                    </div>
                                </div>
                            @if($participant->formatted_identity_number)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Identitas: {{ $participant->formatted_identity_number }}</p>
                            @endif
                            @if(!empty($participant->meta['nama_wisudawan']))
                                <p class="mt-1 text-xs text-purple-600 dark:text-purple-300 font-semibold">Orang Tua Dari: {{ $participant->meta['nama_wisudawan'] }}</p>
                            @endif
                            @if(!empty($participant->meta['nomor_kursi']))
                                <p class="mt-0.5 text-xs text-purple-600 dark:text-purple-300 font-semibold">Kursi: {{ $participant->meta['nomor_kursi'] }}</p>
                            @endif
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell text-xs text-gray-500 dark:text-gray-400">
                            <p class="font-semibold text-gray-700 dark:text-gray-200">{{ $participant->participant_category_label }}</p>
                            <p class="mt-1 {{ $participant->usesLinkedApplicationAccess() ? 'text-emerald-600 dark:text-emerald-300' : 'text-sky-600 dark:text-sky-300' }}">{{ $participant->usesLinkedApplicationAccess() ? ($participant->linked_access_portal_label ?: 'Akun aplikasi') : 'Kredensial peserta' }}</p>
                        </td>
                        <td class="hidden px-4 py-3 lg:table-cell text-xs text-gray-500 dark:text-gray-400">
                            <p>{{ $participant->email ?: '—' }}</p>
                            <p>{{ $participant->no_hp ?: '—' }}</p>
                            <p class="mt-1">Akses: {{ $participant->usesLinkedApplicationAccess() ? ($participant->linked_access_identifier ?: $participant->linked_access_portal_label) : 'Kode registrasi + password sementara' }}</p>
                            <p class="mt-1">Daftar: {{ $participant->registered_at?->translatedFormat('d M Y H:i') ?: '—' }}</p>
                        </td>
                        <td class="hidden px-4 py-3 xl:table-cell text-xs">
                            @php $attendanceCount = $participant->attendanceCount(); @endphp
                            <p class="font-semibold {{ $attendanceCount > 0 ? 'text-green-600 dark:text-green-300' : 'text-gray-500 dark:text-gray-400' }}">{{ $attendanceCount > 0 ? $attendanceCount . '/' . $attendanceDayCount . ' hari hadir' : 'Belum hadir' }}</p>
                            @if($attendanceDates->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($attendanceDates as $attendanceDate)
                                        @php $attendanceRecord = $participant->attendanceRecordFor($attendanceDate); @endphp
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $attendanceRecord ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $attendanceDate->translatedFormat('d M') }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if($unitEvent->jenis_kegiatan !== 'wisuda')
                                <p class="mt-1 {{ $participant->evaluasi_dikirim_at ? 'text-brand-600 dark:text-brand-300' : 'text-gray-500 dark:text-gray-400' }}">{{ $participant->evaluasi_dikirim_at ? 'Evaluasi terkirim' : 'Belum evaluasi' }}</p>
                                <p class="mt-1 {{ $participant->surat_tugas_path ? 'text-sky-600 dark:text-sky-300' : 'text-gray-500 dark:text-gray-400' }}">{{ $participant->surat_tugas_path ? 'Surat tugas / SK tersedia' : 'Belum ada surat tugas / SK' }}</p>
                                <p class="mt-1 {{ $participant->sertifikat_path ? 'text-emerald-600 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400' }}">{{ $participant->sertifikat_path ? 'Sertifikat tersedia' : 'Belum ada sertifikat' }}</p>
                            @endif
                        </td>
                        <td class="hidden px-4 py-3 2xl:table-cell text-xs text-gray-500 dark:text-gray-400">
                            <p>Pre test: {{ $participant->pre_test_nilai !== null ? number_format($participant->pre_test_nilai, 2) : '—' }}</p>
                            <p class="mt-1">Post test: {{ $participant->post_test_nilai !== null ? number_format($participant->post_test_nilai, 2) : '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button onclick="copyInvitationLink('{{ route('unit.events.access', [$unitPage->slug, $unitEvent->slug, $participant->access_token]) }}', this)" class="rounded-lg bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 flex items-center gap-1">
                                    <span>🔗</span> Salin Link
                                </button>
                                <a href="{{ route('unit.events.access', [$unitPage->slug, $unitEvent->slug, $participant->access_token]) }}" target="_blank" class="rounded-lg bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 transition-colors hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-300">Akses</a>
                                <a href="{{ route('admin.unit-events.participants.edit', [$unitPage, $unitEvent, $participant]) }}" class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Edit</a>
                                <form method="POST" action="{{ route('admin.unit-events.participants.destroy', [$unitPage, $unitEvent, $participant]) }}" onsubmit="return confirm('Hapus data peserta ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-red-50 px-3 py-1 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">Belum ada peserta, narasumber, atau panitia untuk event ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<form id="bulk-delete-form" method="POST" action="{{ route('admin.unit-events.participants.destroy-bulk', [$unitPage, $unitEvent]) }}" onsubmit="return confirm('Hapus semua peserta terpilih?')">
    @csrf
    <div id="bulk-delete-ids-container"></div>
</form>

<!-- Progress overlay for AJAX PDF Import -->
<div id="pdf-progress-overlay" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/80 backdrop-blur-md">
    <div class="w-full max-w-md rounded-3xl border border-gray-800 bg-slate-900 p-8 text-center text-white shadow-2xl animate-[fadeIn_0.3s_ease-out]">
        <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-purple-900/30 text-purple-400 border border-purple-500/25">
            <span class="text-3xl animate-bounce">📄</span>
        </div>
        <h3 class="mt-5 text-lg font-extrabold text-white" id="progress-title">Mengonversi File PDF...</h3>
        <p class="mt-2 text-sm text-slate-400" id="progress-desc">Memecah halaman dokumen menjadi gambar</p>
        
        <div class="mt-6 h-2 w-full overflow-hidden rounded-full bg-slate-800">
            <div id="progress-bar-fill" class="h-full w-0 bg-gradient-to-r from-purple-500 to-indigo-500 transition-all duration-300"></div>
        </div>
        <span class="mt-2 block text-xs text-slate-500 font-bold" id="progress-pct">0%</span>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const importForm = document.getElementById("import-participants-form");
    const fileInput = document.getElementById("import-file-input");
    const progressOverlay = document.getElementById("pdf-progress-overlay");
    const progressTitle = document.getElementById("progress-title");
    const progressDesc = document.getElementById("progress-desc");
    const progressBarFill = document.getElementById("progress-bar-fill");
    const progressPct = document.getElementById("progress-pct");

    if (!importForm) return;

    importForm.addEventListener("submit", function (e) {
        const file = fileInput.files[0];
        if (!file) return;

        const extension = file.name.split('.').pop().toLowerCase();
        if (extension !== 'pdf') {
            return;
        }

        e.preventDefault();

        progressOverlay.classList.remove("hidden");
        progressOverlay.classList.add("flex");
        updateProgress(10, "Mengonversi File PDF...", "Memecah halaman dokumen menjadi gambar...");

        const formData = new FormData();
        formData.append("participants_file", file);
        formData.append("_token", "{{ csrf_token() }}");

        fetch("{{ $participantsImportUrl }}", {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json"
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(async (data) => {
            if (data.success && data.type === 'pdf') {
                const pages = data.pages;
                const totalPages = pages.length;
                let processedPages = 0;
                let totalImported = 0;

                updateProgress(30, "Memulai OCR AI...", "Menghubungi modul kecerdasan buatan...");

                const failedPages = [];
                for (let i = 0; i < totalPages; i++) {
                    const pagePath = pages[i];
                    const pageNumber = i + 1;
                    const percent = Math.round(30 + ((pageNumber / totalPages) * 65));

                    updateProgress(percent, `Membaca Halaman ${pageNumber} dari ${totalPages}...`, `AI sedang mengekstrak tabel wisudawan halaman ${pageNumber}...`);

                    try {
                        const pageRes = await fetch("{{ route('admin.unit-events.participants.import-pdf-page', [$unitPage, $unitEvent]) }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Accept": "application/json"
                            },
                            body: JSON.stringify({ image_path: pagePath })
                        });
                        
                        if (!pageRes.ok) {
                            const errData = await pageRes.json().catch(() => ({}));
                            throw new Error(errData.message || `Status: ${pageRes.status}`);
                        }

                        const pageData = await pageRes.json();
                        if (pageData.success) {
                            totalImported += pageData.count;
                        } else {
                            throw new Error(pageData.message || "Gagal membaca halaman.");
                        }
                    } catch (err) {
                        console.error(`Error processing page ${pageNumber}:`, err);
                        failedPages.push({ number: pageNumber, error: err.message });
                    }
                }

                if (failedPages.length > 0) {
                    const failedDetails = failedPages.map(p => `Halaman ${p.number} (Error: ${p.error})`).join("\n");
                    alert(`Impor selesai sebagian.\n\nBerhasil mengimpor ${totalImported} data.\nGagal membaca halaman berikut karena kendala AI (overload/High Demand):\n${failedDetails}`);
                } else {
                    updateProgress(100, "Selesai!", `Berhasil mengimpor ${totalImported} undangan tamu.`);
                }
                
                setTimeout(() => {
                    window.location.reload();
                }, failedPages.length > 0 ? 5000 : 1500);
            } else {
                throw new Error(data.message || "Gagal mengolah file PDF.");
            }
        })
        .catch(err => {
            console.error("Import error: ", err);
            alert("Error: " + (err.message || "Gagal mengimpor file PDF."));
            progressOverlay.classList.remove("flex");
            progressOverlay.classList.add("hidden");
        });
    });

    function updateProgress(percent, title, desc) {
        progressBarFill.style.width = `${percent}%`;
        progressPct.textContent = `${percent}%`;
        if (title) progressTitle.textContent = title;
        if (desc) progressDesc.textContent = desc;
    }
});

// Search & Copy script helpers
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("participants-search");
    const countIndicator = document.getElementById("participants-count-indicator");
    const tableRows = document.querySelectorAll("tbody tr.participant-row");

    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            });

            if (countIndicator) {
                countIndicator.textContent = `Menampilkan ${visibleCount} dari ${tableRows.length} data`;
            }
        });
    }
});

function copyInvitationLink(url, button) {
    navigator.clipboard.writeText(url).then(() => {
        const originalHTML = button.innerHTML;
        button.innerHTML = "<span>✓</span> Tersalin!";
        button.style.backgroundColor = "#dcfce7";
        button.style.color = "#166534";
        button.style.borderColor = "#bbf7d0";
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.backgroundColor = "";
            button.style.color = "";
            button.style.borderColor = "";
        }, 2000);
    }).catch(err => {
        console.error("Gagal menyalin link: ", err);
    });
}

// Bulk delete checkbox handling
document.addEventListener("DOMContentLoaded", function() {
    const selectAllCheckbox = document.getElementById("select-all-participants");
    const participantCheckboxes = document.querySelectorAll(".participant-checkbox");
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const selectedCountSpan = document.getElementById("selected-count");
    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    const idsContainer = document.getElementById("bulk-delete-ids-container");

    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll(".participant-checkbox:checked").length;
        if (checkedCount > 0) {
            bulkDeleteBtn.classList.remove("hidden");
            bulkDeleteBtn.classList.add("inline-flex");
            selectedCountSpan.textContent = checkedCount;
        } else {
            bulkDeleteBtn.classList.add("hidden");
            bulkDeleteBtn.classList.remove("inline-flex");
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener("change", function() {
            const visibleRows = document.querySelectorAll("tbody tr.participant-row");
            visibleRows.forEach(row => {
                if (row.style.display !== "none") {
                    const cb = row.querySelector(".participant-checkbox");
                    if (cb) {
                        cb.checked = selectAllCheckbox.checked;
                    }
                }
            });
            updateBulkDeleteButton();
        });
    }

    participantCheckboxes.forEach(cb => {
        cb.addEventListener("change", function() {
            updateBulkDeleteButton();
            if (!this.checked && selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
        });
    });

    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener("submit", function(e) {
            // Clear previous inputs
            idsContainer.innerHTML = "";
            
            const checkedBoxes = document.querySelectorAll(".participant-checkbox:checked");
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert("Tidak ada peserta yang terpilih.");
                return;
            }

            // Inject hidden input for each checked checkbox
            checkedBoxes.forEach(cb => {
                const hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                hiddenInput.name = "ids[]";
                hiddenInput.value = cb.value;
                idsContainer.appendChild(hiddenInput);
            });
        });
    }

    const searchInput = document.getElementById("modal-prodi-search");
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const query = this.value.toLowerCase().trim();
            const items = document.querySelectorAll("#modal-prodi-grid .prodi-item");
            items.forEach(item => {
                const title = item.querySelector(".prodi-title")?.textContent.toLowerCase() || "";
                if (title.includes(query)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        });
    }
});

function openWisudaPrintModal() {
    const modal = document.getElementById("modal-wisuda-print");
    if (modal) {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        const searchInput = document.getElementById("modal-prodi-search");
        if (searchInput) {
            searchInput.value = "";
            searchInput.focus();
        }
    }
}

function closeWisudaPrintModal() {
    const modal = document.getElementById("modal-wisuda-print");
    if (modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
}
</script>

@if($unitEvent->jenis_kegiatan === 'wisuda')
<!-- Modal Cetak Undangan Wisuda Per Prodi / Jurusan -->
<div id="modal-wisuda-print" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm transition-all" onclick="if(event.target === this) closeWisudaPrintModal()">
    <div class="relative w-full max-w-4xl rounded-3xl border border-gray-100 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900 max-h-[90vh] flex flex-col animate-[fadeIn_0.2s_ease-out]">
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-100 text-2xl text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">🎓</span>
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Cetak Undangan Wisuda Digital</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pilih Program Studi atau Jurusan untuk mengunduh / mencetak kartu undangan agar proses cetak jauh lebih ringan dan cepat.</p>
                </div>
            </div>
            <button type="button" onclick="closeWisudaPrintModal()" class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-white transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Filter Search inside Modal -->
        <div class="mt-4 mb-2">
            <input type="text" id="modal-prodi-search" placeholder="🔍 Cari Program Studi (contoh: Farmasi, Keperawatan, Gizi, dll)..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-purple-500 focus:bg-white focus:outline-none shadow-sm">
        </div>

        <!-- Body with Scroll -->
        <div class="overflow-y-auto flex-1 pr-1 space-y-6 py-2">
            <!-- Section 1: Per Program Studi (Paling Ringan) -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                        <span>⚡</span> Rekomendasi: Cetak per Program Studi (Paling Ringan ~30 - 110 Undangan per File)
                    </h4>
                    <span class="text-[11px] font-bold text-gray-400">{{ count($wisudaProdis ?? []) }} Prodi Tersedia</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5" id="modal-prodi-grid">
                    @foreach($wisudaProdis as $pName => $pTotal)
                        <div class="prodi-item flex items-center justify-between p-3 rounded-2xl border border-gray-100 bg-gray-50/80 hover:border-purple-300 hover:bg-purple-50/40 dark:border-gray-800 dark:bg-gray-800/60 dark:hover:border-purple-800 transition-all">
                            <div class="min-w-0 pr-2">
                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate prodi-title">{{ $pName }}</p>
                                <span class="inline-block mt-0.5 text-[11px] font-bold text-purple-600 dark:text-purple-400">{{ $pTotal }} Tamu Undangan</span>
                            </div>
                            <a href="{{ route('admin.unit-events.participants.invitation-print', [$unitPage, $unitEvent, 'prodi' => $pName]) }}" target="_blank" class="shrink-0 px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 active:scale-95">
                                <span>🖨️</span> Buka & Cetak
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 2: Per Jurusan -->
            <div class="border-t border-gray-100 dark:border-gray-800 pt-5">
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                    <span>🏢</span> Cetak per Jurusan (Gabungan Seluruh Jenjang dalam Satu Jurusan)
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                    @foreach($jurusanDefinitions as $jKey => $jDef)
                        @php $jCount = $wisudaJurusanCounts[$jKey] ?? 0; @endphp
                        <div class="flex items-center justify-between p-3 rounded-2xl border border-gray-100 bg-gray-50/80 hover:border-blue-300 hover:bg-blue-50/40 dark:border-gray-800 dark:bg-gray-800/60 dark:hover:border-blue-800 transition-all">
                            <div class="min-w-0 pr-2">
                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $jDef['label'] }}</p>
                                <span class="inline-block mt-0.5 text-[11px] font-bold text-blue-600 dark:text-blue-400">{{ $jCount }} Undangan</span>
                            </div>
                            <a href="{{ route('admin.unit-events.participants.invitation-print', [$unitPage, $unitEvent, 'jurusan' => $jKey]) }}" target="_blank" class="shrink-0 px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1 active:scale-95">
                                <span>🖨️</span> Cetak
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 3: Semua Sekaligus -->
            <div class="border-t border-gray-100 dark:border-gray-800 pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 bg-amber-50/70 dark:bg-amber-950/20 p-4 rounded-2xl border border-amber-200/70 dark:border-amber-900/40">
                <div>
                    <p class="text-xs font-extrabold text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                        <span>⚠️</span> Cetak Seluruh Undangan Sekaligus ({{ array_sum($wisudaProdis ?? []) }} Undangan)
                    </p>
                    <p class="text-[11px] text-amber-700 dark:text-amber-300/80 mt-0.5">Memuat total 678 lembar A4 sekaligus. Membutuhkan waktu beberapa detik untuk merender seluruh QR code.</p>
                </div>
                <a href="{{ route('admin.unit-events.participants.invitation-print', [$unitPage, $unitEvent]) }}" target="_blank" class="shrink-0 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 active:scale-95">
                    <span>📑</span> Buka Semua Sekaligus
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-100 pt-3 dark:border-gray-800 flex justify-end">
            <button type="button" onclick="closeWisudaPrintModal()" class="px-5 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>
@endif

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection
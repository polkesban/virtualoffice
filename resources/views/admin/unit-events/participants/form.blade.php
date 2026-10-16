@extends('admin.layouts.admin')
@section('title', $participant->exists ? 'Edit Peserta Event' : 'Tambah Peserta Event')
@section('page-title', $participant->exists ? 'Edit Peserta Event' : 'Tambah Peserta Event')
@section('page-subtitle', 'Peserta, narasumber, panitia, kehadiran, surat tugas, dan sertifikat')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $participant->exists ? route('admin.unit-events.participants.update', [$unitPage, $unitEvent, $participant]) : route('admin.unit-events.participants.store', [$unitPage, $unitEvent]) }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        @if($participant->exists)
            @method('PUT')
        @endif

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 space-y-4">
            @if($participant->exists)
                <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <p><span class="font-semibold">Kode Registrasi:</span> {{ $participant->kode_registrasi }}</p>
                    <p class="mt-1"><span class="font-semibold">Kategori Peserta:</span> {{ $participant->participant_category_label }}</p>
                    <p class="mt-1 break-all"><span class="font-semibold">Link Akses:</span> <a href="{{ route('unit.events.access', [$unitPage->slug, $unitEvent->slug, $participant->access_token]) }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-300">{{ route('unit.events.access', [$unitPage->slug, $unitEvent->slug, $participant->access_token]) }}</a></p>
                    @if($participant->surat_tugas_path)
                        <p class="mt-1"><span class="font-semibold">Surat Tugas / SK:</span> <a href="{{ route('unit.events.assignment-letter.show', ['unitSlug' => $unitPage->slug, 'eventSlug' => $unitEvent->slug, 'token' => $participant->access_token]) }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-300">Tersedia untuk peserta</a></p>
                    @endif
                    @if($participant->usesLinkedApplicationAccess())
                        <p class="mt-1"><span class="font-semibold">Akun Terhubung:</span> {{ $participant->linked_access_portal_label ?: 'Akun aplikasi' }}</p>
                        <p class="mt-1"><span class="font-semibold">Identitas Akun:</span> {{ $participant->linked_access_identifier ?: 'Terdeteksi dari email peserta' }}</p>
                        <p class="mt-1 break-all"><span class="font-semibold">Akses Langsung Daftar Hadir:</span> <a href="{{ route('unit.events.attendance.direct', [$unitPage->slug, $unitEvent->slug, 'kode_registrasi' => $participant->participant_login_username, 'portal' => $participant->linked_access_portal]) }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-300">{{ route('unit.events.attendance.direct', [$unitPage->slug, $unitEvent->slug, 'kode_registrasi' => $participant->participant_login_username, 'portal' => $participant->linked_access_portal]) }}</a></p>
                    @endif
                    @if($participant->hasAccessCredentials())
                        <p class="mt-1"><span class="font-semibold">Username Peserta:</span> {{ $participant->participant_login_username }}</p>
                        <p class="mt-1"><span class="font-semibold">Password Sementara:</span> {{ $participant->temporary_access_password ?: 'Manual / tidak tersimpan' }}</p>
                        <p class="mt-1 break-all"><span class="font-semibold">URL Login Peserta:</span> <a href="{{ route('unit.events.login.show', [$unitPage->slug, $unitEvent->slug]) }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-300">{{ route('unit.events.login.show', [$unitPage->slug, $unitEvent->slug]) }}</a></p>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Peran <span class="text-red-500">*</span></label>
                    <select name="peran" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        @foreach(\App\Models\UnitEventParticipant::$peranLabels as $key => $label)
                            <option value="{{ $key }}" @selected(old('peran', $participant->peran ?: request('peran', 'participant')) === $key)>{{ ($unitEvent->jenis_kegiatan === 'wisuda' && $key === 'participant') ? 'Undangan' : $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Kategori Peserta <span class="text-red-500">*</span></label>
                    <select name="participant_category" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        @foreach(\App\Models\UnitEventParticipant::$participantCategoryLabels as $key => $label)
                            <option value="{{ $key }}" @selected(old('participant_category', $participant->participant_category ?: 'public') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Mahasiswa akan terdeteksi otomatis saat NIM cocok dengan akun mahasiswa. Kategori tetap bisa Anda sesuaikan manual bila diperlukan.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $participant->nama) }}" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" value="{{ old('email', $participant->email) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">NIP / NIS / NIK / NIM</label>
                    <input type="text" name="identity_number" value="{{ old('identity_number', $participant->formatted_identity_number) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm uppercase dark:border-gray-700 dark:bg-gray-800">
                    <p class="mt-1 text-xs text-gray-400">Isi untuk menghubungkan peserta dengan akun aplikasi yang sudah ada, termasuk akun pegawai berdasarkan NIP dan akun mahasiswa berdasarkan NIM.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" value="{{ old('no_hp', $participant->no_hp) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Instansi</label>
                    <input type="text" name="instansi" value="{{ old('instansi', $participant->instansi) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Jabatan</label>
                    <input type="text" name="jabatan" value="{{ old('jabatan', $participant->jabatan) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Waktu Registrasi</label>
                    <input type="datetime-local" name="registered_at" value="{{ old('registered_at', $participant->registered_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="attended" value="1" @checked(old('attended', (bool) $participant->attended_at)) class="rounded">
                        Tandai sudah hadir
                    </label>
                </div>
            </div>

            @if($unitEvent->jenis_kegiatan === 'pbm' && ($unitEvent->sub_jenis_kegiatan === 'praktik' || $unitEvent->sub_jenis_kegiatan === 'gabungan' || stripos($unitEvent->judul, 'praktik') !== false || stripos($unitEvent->judul, 'magang') !== false))
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 border-t border-gray-100 dark:border-gray-800 pt-4">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tempat Praktik</label>
                        @if(!empty($unitEvent->lokasi_praktik))
                            <select name="tempat_praktik" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                <option value="">-- Pilih Tempat Praktik --</option>
                                @foreach($unitEvent->lokasi_praktik as $loc)
                                    <option value="{{ $loc }}" @selected(old('tempat_praktik', $participant->tempat_praktik) === $loc)>{{ $loc }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="tempat_praktik" value="{{ old('tempat_praktik', $participant->tempat_praktik) }}" placeholder="Contoh: RSUD Welas Asih" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama Pembimbing</label>
                        <select name="nama_pembimbing" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            <option value="">-- Pilih Pembimbing --</option>
                            @foreach($employees ?? [] as $emp)
                                <option value="{{ $emp['name'] }}" @selected(old('nama_pembimbing', $participant->nama_pembimbing) === $emp['name'])>{{ $emp['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if($unitEvent->jenis_kegiatan === 'wisuda')
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 border-t border-gray-100 dark:border-gray-800 pt-4">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama Wisudawan / Wisudawati</label>
                        <input type="text" name="nama_wisudawan" value="{{ old('nama_wisudawan', $participant->meta['nama_wisudawan'] ?? '') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Contoh: Budi Santoso">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nomor Kursi / Baris Undangan</label>
                        <input type="text" name="nomor_kursi" value="{{ old('nomor_kursi', $participant->meta['nomor_kursi'] ?? '') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Contoh: A-12">
                    </div>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Catatan</label>
                <textarea name="catatan" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('catatan', $participant->catatan) }}</textarea>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Catatan Kehadiran</label>
                <textarea name="attendance_notes" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('attendance_notes', $participant->attendance_notes) }}</textarea>
                @if($unitEvent->attendanceDayCount() > 1)
                    <p class="mt-2 text-xs text-gray-400">Event ini berlangsung {{ $unitEvent->attendanceDayCount() }} hari. Tanda tangan harian peserta dikelola dari portal peserta dan dapat dicetak dari halaman daftar peserta.</p>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">🎙️ Foto Profil / Narasumber</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Foto narasumber akan ditampilkan pada template flyer resmi berlogo Polkesban dan rincian acara.</p>
                </div>
            </div>
            @if($participant->foto_path)
                <div class="flex items-center gap-4">
                    <img src="{{ \App\Support\MediaStorage::url($participant->foto_path) }}" alt="{{ $participant->nama }}" class="h-16 w-16 rounded-2xl object-cover border border-teal-500/40 shadow-sm ring-2 ring-teal-500/20">
                    <label class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400 cursor-pointer">
                        <input type="checkbox" name="hapus_foto" value="1" class="rounded">
                        Hapus foto profil saat ini
                    </label>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Upload Foto Profil / Narasumber</label>
                <input type="file" name="foto" accept="image/*" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="mt-1 text-xs text-gray-400">Format gambar (JPG, PNG, WEBP), rasio persegi dianjurkan, maksimal 4 MB.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 space-y-4">
            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Surat Tugas / SK</h3>
            @if($participant->surat_tugas_path)
                <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <a href="{{ route('unit.events.assignment-letter.show', ['unitSlug' => $unitPage->slug, 'eventSlug' => $unitEvent->slug, 'token' => $participant->access_token]) }}" target="_blank" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">Lihat file surat tugas / SK saat ini</a>
                    <p class="mt-1 text-xs text-gray-400">Diunggah {{ $participant->surat_tugas_uploaded_at?->translatedFormat('d M Y H:i') ?: '-' }}</p>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Upload Surat Tugas / SK</label>
                <input type="file" name="surat_tugas_file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="mt-1 text-xs text-gray-400">Format yang didukung: PDF, JPG, PNG, atau WEBP. File ini akan tampil di portal peserta event.</p>
            </div>
            @if($participant->surat_tugas_path)
                <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="hapus_surat_tugas" value="1" class="rounded">
                    Hapus surat tugas / SK yang sudah ada
                </label>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 space-y-4">
            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Sertifikat</h3>
            @if($participant->sertifikat_path)
                <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <a href="{{ \App\Support\MediaStorage::url($participant->sertifikat_path) }}" target="_blank" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">Lihat file sertifikat saat ini</a>
                    <p class="mt-1 text-xs text-gray-400">Diunggah {{ $participant->sertifikat_uploaded_at?->translatedFormat('d M Y H:i') ?: '-' }}</p>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Upload File Sertifikat</label>
                <input type="file" name="sertifikat_file" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>
            @if($participant->sertifikat_path)
                <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="hapus_sertifikat" value="1" class="rounded">
                    Hapus sertifikat yang sudah ada
                </label>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $unitEvent]) }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Batal</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                {{ $participant->exists ? 'Simpan Perubahan' : 'Tambah Peserta' }}
            </button>
        </div>
    </form>
</div>
@endsection
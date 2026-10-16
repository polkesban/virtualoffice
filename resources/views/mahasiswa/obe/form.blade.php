@extends('mahasiswa.layouts.portal')

@section('title', ($obe ? 'Edit' : 'Buat') . ' Buku Portofolio OBE')

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'identitas' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider mb-1">
                <a href="{{ route('mahasiswa.obe.index') }}" class="hover:underline">Buku Portofolio OBE</a> &bull; {{ $obe ? 'Edit Portofolio' : 'Buat Baru' }}
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $obe ? 'Edit Buku Portofolio Pembelajaran OBE' : 'Formulir Buku Portofolio Pembelajaran OBE' }}
            </h1>
        </div>
        <div class="flex items-center gap-2">
            @if($obe)
                <a href="{{ route('mahasiswa.obe.print', $obe->id) }}" target="_blank" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition flex items-center gap-2 shadow-sm">
                    🖨️ Cetak / Preview PDF
                </a>
            @endif
            <a href="{{ route('mahasiswa.obe.index') }}" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition shadow-sm">
                Kembali
            </a>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-3 shadow-sm">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm shadow-sm">
            <p class="font-bold mb-1">Terjadi kesalahan pada input formulir:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form & Tabs Nav -->
    <form action="{{ $obe ? route('mahasiswa.obe.update', $obe->id) : route('mahasiswa.obe.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($obe)
            @method('PUT')
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
            <!-- Tabs Bar (High contrast for dark mode) -->
            <div class="flex border-b border-gray-100 dark:border-gray-800 overflow-x-auto bg-gray-50/50 dark:bg-gray-800/40">
                <button type="button" @click="activeTab = 'identitas'" 
                        :class="activeTab === 'identitas' ? 'border-brand-600 text-brand-600 dark:text-brand-400 bg-white dark:bg-gray-900 font-bold' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white font-medium'"
                        class="px-6 py-4 border-b-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                    👤 <span>1. Identitas & Praktik</span>
                </button>
                <button type="button" @click="activeTab = 'profil_mk'" 
                        :class="activeTab === 'profil_mk' ? 'border-brand-600 text-brand-600 dark:text-brand-400 bg-white dark:bg-gray-900 font-bold' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white font-medium'"
                        class="px-6 py-4 border-b-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                    📚 <span>2. Profil MK & CPL/CPMK</span>
                </button>
                <button type="button" @click="activeTab = 'evidence'" 
                        :class="activeTab === 'evidence' ? 'border-brand-600 text-brand-600 dark:text-brand-400 bg-white dark:bg-gray-900 font-bold' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white font-medium'"
                        class="px-6 py-4 border-b-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                    📁 <span>3. Evidence / Bukti Dokumen</span>
                </button>
                <button type="button" @click="activeTab = 'nilai'" 
                        :class="activeTab === 'nilai' ? 'border-brand-600 text-brand-600 dark:text-brand-400 bg-white dark:bg-gray-900 font-bold' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white font-medium'"
                        class="px-6 py-4 border-b-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                    📝 <span>4. Nilai & Umpan Balik</span>
                </button>
            </div>

            <div class="p-6">
                <!-- TAB 1: IDENTITAS & PRAKTIK -->
                <div x-show="activeTab === 'identitas'" class="space-y-6">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Identitas Mahasiswa & Informasi Praktik (Halaman 1)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Informasi identitas mahasiswa ditarik dari sistem/SIAKAD. Lengkapi data lahan praktik & pembimbing.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Mahasiswa</label>
                            <input type="text" value="{{ $mahasiswa->name }}" readonly class="w-full px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200 text-sm font-medium border border-gray-200 dark:border-gray-700 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NIM (Nomor Induk Mahasiswa)</label>
                            <input type="text" value="{{ $mahasiswa->nim }}" readonly class="w-full px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200 text-sm font-medium border border-gray-200 dark:border-gray-700 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Program Studi</label>
                            <input type="text" value="{{ $mahasiswa->programStudi?->nama_prodi ?? 'Poltekkes Kemenkes Bandung' }}" readonly class="w-full px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200 text-sm font-medium border border-gray-200 dark:border-gray-700 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kelas</label>
                            <input type="text" name="kelas" value="{{ old('kelas', $obe->kelas ?? 'Reguler A') }}" placeholder="Contoh: Reguler 3A" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Semester <span class="text-red-500">*</span></label>
                            <select name="semester" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white" required>
                                @for($i=1; $i<=8; $i++)
                                    <option value="{{ $i }}" {{ old('semester', $obe->semester ?? 1) == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tahun Akademik <span class="text-red-500">*</span></label>
                            <input type="text" name="tahun_akademik" value="{{ old('tahun_akademik', $obe->tahun_akademik ?? '2025/2026') }}" placeholder="Contoh: 2025/2026" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dosen Pembimbing Akademik (PA)</label>
                            <input type="text" name="dosen_pa" value="{{ old('dosen_pa', $obe->dosen_pa ?? '') }}" placeholder="Nama Dosen PA beserta Gelar" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Lahan Praktik</label>
                            <input type="text" name="lahan_praktik" value="{{ old('lahan_praktik', $obe->lahan_praktik ?? '') }}" placeholder="Contoh: RSUD Kota Bandung / Puskesmas X" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pembimbing Lahan / CI (Clinical Instructor)</label>
                            <input type="text" name="pembimbing_lahan" value="{{ old('pembimbing_lahan', $obe->pembimbing_lahan ?? '') }}" placeholder="Nama Pembimbing Lahan Praktik" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Lembar Pengesahan -->
                    <div class="pt-6 border-t border-gray-100 dark:border-gray-800 space-y-4">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Informasi Lembar Pengesahan (Halaman 2)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ketua Jurusan</label>
                                <input type="text" name="ketua_jurusan" value="{{ old('ketua_jurusan', $obe->ketua_jurusan ?? 'Dr. H. Yudi Triono, S.Kp., M.Kes') }}" placeholder="Nama Ketua Jurusan" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">NIP Ketua Jurusan</label>
                                <input type="text" name="nip_ketua_jurusan" value="{{ old('nip_ketua_jurusan', $obe->nip_ketua_jurusan ?? '197401011998031001') }}" placeholder="NIP Ketua Jurusan" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ketua Program Studi</label>
                                <input type="text" name="ketua_prodi" value="{{ old('ketua_prodi', $obe->ketua_prodi ?? 'Hj. Nining Suhartini, M.Pd') }}" placeholder="Nama Ketua Prodi" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">NIP Ketua Program Studi</label>
                                <input type="text" name="nip_ketua_prodi" value="{{ old('nip_ketua_prodi', $obe->nip_ketua_prodi ?? '197905122003122002') }}" placeholder="NIP Ketua Program Studi" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PROFIL MK & CPL/CPMK -->
                <div x-show="activeTab === 'profil_mk'" class="space-y-6">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Profil Mata Kuliah & Kurikulum OBE (Halaman 4 - 5)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Data CPL, CPMK, Sub-CPMK, & Bobot Penilaian (Dapat disesuaikan atau diisi dari SIAKAD SEVIMA).</p>
                    </div>

                    <!-- Informasi Dasar MK -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-brand-50/50 dark:bg-brand-950/30 p-4 rounded-xl border border-brand-100 dark:border-brand-900/50">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1">Kode Mata Kuliah <span class="text-red-500">*</span></label>
                            <input type="text" name="kode_mk" value="{{ old('kode_mk', $obe->kode_mk ?? 'GZ-301') }}" placeholder="Contoh: GZ-301" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-mono text-gray-900 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1">Nama Mata Kuliah <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_mk" value="{{ old('nama_mk', $obe->nama_mk ?? 'Asuhan Gizi Terstandar') }}" placeholder="Contoh: Asuhan Gizi Terstandar" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-bold text-gray-900 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1">Bobot SKS <span class="text-red-500">*</span></label>
                            <input type="number" name="sks" value="{{ old('sks', $obe->sks ?? 3) }}" min="1" max="10" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white" required>
                        </div>
                    </div>

                    <!-- CPL List -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">1. Capaian Pembelajaran Lulusan (CPL)</h4>
                        <div class="space-y-2">
                            @foreach($defaultCpl as $index => $cpl)
                                <div class="flex gap-2 items-center bg-gray-50 dark:bg-gray-800/60 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700/50">
                                    <input type="text" name="cpl_list[{{$index}}][kode]" value="{{ $cpl['kode'] }}" class="w-24 px-2 py-1 text-xs font-mono font-bold rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <input type="text" name="cpl_list[{{$index}}][deskripsi]" value="{{ $cpl['deskripsi'] }}" class="flex-1 px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- CPMK List -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">2. Capaian Pembelajaran Mata Kuliah (CPMK)</h4>
                        <div class="space-y-2">
                            @foreach($defaultCpmk as $index => $cpmk)
                                <div class="flex gap-2 items-center bg-gray-50 dark:bg-gray-800/60 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700/50">
                                    <input type="text" name="cpmk_list[{{$index}}][kode]" value="{{ $cpmk['kode'] }}" class="w-24 px-2 py-1 text-xs font-mono font-bold rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <input type="number" name="cpmk_list[{{$index}}][bobot]" value="{{ $cpmk['bobot'] }}" placeholder="Bobot %" class="w-20 px-2 py-1 text-xs text-center rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">%</span>
                                    <input type="text" name="cpmk_list[{{$index}}][deskripsi]" value="{{ $cpmk['deskripsi'] }}" class="flex-1 px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Sub CPMK List -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">3. Sub-CPMK</h4>
                        <div class="space-y-2">
                            @foreach($defaultSubCpmk as $index => $sub)
                                <div class="flex gap-2 items-center">
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400 w-6">{{ $index + 1 }}.</span>
                                    <input type="text" name="sub_cpmk_list[]" value="{{ $sub }}" class="flex-1 px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- TAB 3: EVIDENCE BUKTI DOKUMEN -->
                <div x-show="activeTab === 'evidence'" class="space-y-6">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Daftar Evidence & Upload Dokumen Pembelajaran (Halaman 6)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload bukti fisik portofolio seperti Laporan Studi Kasus, Log Book, Leaflet, dan dokumen pendukung lainnya.</p>
                    </div>

                    @if(!$obe)
                        <div class="p-6 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs">
                            💡 <strong>Catatan:</strong> Pengunggahan berkas file evidence dapat dilakukan setelah Anda menyimpan formulir portofolio OBE ini pertama kali.
                        </div>
                    @else
                        <!-- List Existing Evidences -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Checklist Dokumen Evidence</h4>
                            <div class="divide-y divide-gray-100 dark:divide-gray-800 border border-gray-100 dark:border-gray-800 rounded-xl overflow-hidden">
                                @foreach($obe->evidences as $index => $ev)
                                    <div class="p-4 bg-white dark:bg-gray-900 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" name="evidences_status[{{ $ev->id }}]" value="1" {{ $ev->is_ada ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ev->nama_dokumen }}</p>
                                                @if($ev->file_path)
                                                    <a href="{{ $ev->file_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 dark:text-brand-400 hover:underline mt-0.5">
                                                        📎 Lihat File Lampiran ({{ strtoupper(pathinfo($ev->file_path, PATHINFO_EXTENSION)) }})
                                                    </a>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">Belum ada file diupload</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($ev->file_path)
                                            <div class="flex items-center gap-2">
                                                <button type="submit" form="del-ev-{{ $ev->id }}" class="text-xs text-red-500 hover:underline">Hapus File</button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- TAB 4: NILAI & UMPAN BALIK -->
                <div x-show="activeTab === 'nilai'" class="space-y-6">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Nilai Mata Kuliah & Umpan Balik Pembimbing (Halaman 6)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Isi hasil nilai mata kuliah dan evaluasi umpan balik dari Dosen/Pembimbing Lahan.</p>
                    </div>

                    <!-- Nilai Akhir -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-800/60 p-4 rounded-xl border border-gray-100 dark:border-gray-700/50">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nilai Angka (0-100)</label>
                            <input type="number" step="0.01" name="nilai_angka" value="{{ old('nilai_angka', $obe->nilai_angka ?? '') }}" placeholder="Contoh: 85.50" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nilai Mutu (0.00 - 4.00)</label>
                            <input type="number" step="0.01" name="nilai_mutu" value="{{ old('nilai_mutu', $obe->nilai_mutu ?? '') }}" placeholder="Contoh: 3.75" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Lambang / Huruf Mutu</label>
                            <input type="text" name="nilai_lambang" value="{{ old('nilai_lambang', $obe->nilai_lambang ?? '') }}" placeholder="Contoh: A / A-" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs uppercase font-bold text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Umpan Balik Pembimbing -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Umpan Balik Pembimbing</h4>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kelebihan Mahasiswa</label>
                            <textarea name="kelebihan_mahasiswa" rows="3" placeholder="Catatan keunggulan/kelebihan mahasiswa..." class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">{{ old('kelebihan_mahasiswa', $obe->kelebihan_mahasiswa ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Aspek Yang Perlu Ditingkatkan</label>
                            <textarea name="aspek_perlu_ditingkatkan" rows="3" placeholder="Catatan aspek perbaikan..." class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">{{ old('aspek_perlu_ditingkatkan', $obe->aspek_perlu_ditingkatkan ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Rekomendasi</label>
                            <textarea name="rekomendasi_pembimbing" rows="3" placeholder="Rekomendasi pembimbing..." class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white">{{ old('rekomendasi_pembimbing', $obe->rekomendasi_pembimbing ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="p-6 bg-gray-50/50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <a href="{{ route('mahasiswa.obe.index') }}" class="px-4 py-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm font-medium transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-medium text-sm shadow-sm transition">
                    💾 Simpan Portofolio OBE
                </button>
            </div>
        </div>
    </form>

    <!-- Modal Form Upload Evidence Terpisah jika sudah mode Edit -->
    @if($obe)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 space-y-4">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Upload File Lampiran Evidence Baru</h3>
            <form action="{{ route('mahasiswa.obe.evidence.upload', $obe->id) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Dokumen Evidence</label>
                    <input type="text" name="nama_dokumen" placeholder="Contoh: Laporan Asuhan Gizi Kasus 1" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">File Dokumen (PDF / Image / Office)</label>
                    <input type="file" name="file" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 dark:file:bg-brand-900/50 file:text-brand-700 dark:file:text-brand-300 hover:file:bg-brand-100">
                </div>
                <div>
                    <button type="submit" class="w-full px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-sm transition">
                        📤 Upload Dokumen
                    </button>
                </div>
            </form>
        </div>

        @foreach($obe->evidences as $ev)
            @if($ev->file_path)
                <form id="del-ev-{{ $ev->id }}" action="{{ route('mahasiswa.obe.evidence.destroy', [$obe->id, $ev->id]) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endforeach
    @endif
</div>
@endsection

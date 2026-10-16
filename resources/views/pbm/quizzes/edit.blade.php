@extends('pbm.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto pb-12">
    <!-- Breadcrumb -->
    <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="inline-flex items-center text-sm font-medium text-sky-600 hover:text-sky-700 mb-6 transition">
        <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i> Kembali ke Detail Kuis
    </a>

    <!-- Header Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                <i data-feather="settings" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pengaturan Kuis & Pengawasan</h1>
                <p class="text-sm text-gray-500">Konfigurasikan jadwal, durasi, proctoring/pengawasan kamera, dan Safe Exam Browser.</p>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form action="{{ route('pbm.quizzes.update', $quiz->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Bagian 1: Detail Dasar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2 pb-3 border-b border-gray-100">
                <i data-feather="file-text" class="w-4 h-4 text-purple-600"></i> Informasi Umum Kuis
            </h2>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Kuis <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                <textarea name="description" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">{{ old('description', $quiz->description) }}</textarea>
            </div>
        </div>

        <!-- Bagian 2: Waktu & Pelaksanaan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2 pb-3 border-b border-gray-100">
                <i data-feather="calendar" class="w-4 h-4 text-purple-600"></i> Jadwal & Durasi Pengerjaan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai</label>
                    <input type="datetime-local" name="start_time" 
                           value="{{ old('start_time', $quiz->start_time ? $quiz->start_time->format('Y-m-d\TH:i') : '') }}" 
                           class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                    <span class="text-xs text-gray-400 mt-1 block">Kosongkan jika kuis dapat diakses kapan saja.</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
                    <input type="datetime-local" name="end_time" 
                           value="{{ old('end_time', $quiz->end_time ? $quiz->end_time->format('Y-m-d\TH:i') : '') }}" 
                           class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                    <span class="text-xs text-gray-400 mt-1 block">Batas akhir penyerahan pengerjaan kuis.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durasi Pengerjaan (Menit)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $quiz->duration_minutes) }}" min="1" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                    <span class="text-xs text-gray-400 mt-1 block">Kosongkan jika durasi tidak dibatasi.</span>
                </div>

                <div class="pt-2 sm:pt-6 space-y-3">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions', $quiz->shuffle_questions) ? 'checked' : '' }} class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200">
                        <span class="ml-2.5 text-sm font-medium text-gray-700">Acak Urutan Butir Soal</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="show_results" value="1" {{ old('show_results', $quiz->show_results) ? 'checked' : '' }} class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200">
                        <span class="ml-2.5 text-sm font-medium text-gray-700">Tampilkan Nilai & Pembahasan Setelah Selesai</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Bagian 3: Pengawasan (Proctoring) -->
        <div class="bg-white rounded-2xl shadow-sm border border-purple-200 p-6 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i data-feather="camera" class="w-4 h-4 text-purple-600"></i> Pengawasan Online (Online Proctoring)
                </h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                    Fitur Keamanan CBT
                </span>
            </div>

            <!-- Master Toggle Proctoring -->
            <div class="bg-purple-50/70 p-4 rounded-xl border border-purple-100">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="enable_proctoring" id="edit_enable_proctoring" value="1" 
                           {{ old('enable_proctoring', $quiz->enable_proctoring) ? 'checked' : '' }}
                           onchange="toggleProctoringSub(this)"
                           class="mt-1 w-5 h-5 rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-300">
                    <div>
                        <span class="text-base font-bold text-gray-900 block">Aktifkan Pengawasan (Proctoring)</span>
                        <span class="text-xs text-gray-600 block mt-0.5">
                            Mengaktifkan sistem pengawas otomatis untuk mendeteksi kecurangan, mencatat pelanggaran integritas, dan memberikan log foto berkala.
                        </span>
                    </div>
                </label>
            </div>

            <!-- Sub-options for Proctoring -->
            <div id="proctoring_sub_options" class="{{ old('enable_proctoring', $quiz->enable_proctoring) ? '' : 'hidden' }} space-y-4 pl-2">
                <!-- Webcam Option -->
                <div class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-purple-300 transition bg-white">
                    <input type="checkbox" name="require_webcam" id="require_webcam" value="1" 
                           {{ old('require_webcam', $quiz->require_webcam) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-300">
                    <label for="require_webcam" class="cursor-pointer">
                        <span class="text-sm font-bold text-gray-900 block flex items-center gap-1.5">
                            <i data-feather="video" class="w-4 h-4 text-sky-600"></i> Wajibkan Kamera / Webcam Peserta
                        </span>
                        <span class="text-xs text-gray-500 block mt-0.5">
                            Peserta harus memberikan izin kamera sebelum dapat memulai ujian. Sistem akan menampilkan thumbnail kamera langsung dan mengambil snapshot otomatis setiap menit & saat ada pelanggaran.
                        </span>
                    </label>
                </div>

                <!-- Microphone Option -->
                <div class="p-3.5 rounded-xl border border-gray-200 hover:border-purple-300 transition bg-white space-y-3">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="require_microphone" id="require_microphone" value="1" 
                               {{ old('require_microphone', $quiz->require_microphone) ? 'checked' : '' }}
                               onchange="document.getElementById('mic_sensitivity_container').classList.toggle('hidden', !this.checked)"
                               class="mt-1 rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-300">
                        <label for="require_microphone" class="cursor-pointer">
                            <span class="text-sm font-bold text-gray-900 block flex items-center gap-1.5">
                                <i data-feather="mic" class="w-4 h-4 text-pink-600"></i> Wajibkan Mikrofon / On Mic (Deteksi Percakapan)
                            </span>
                            <span class="text-xs text-gray-500 block mt-0.5">
                                Peserta wajib mengaktifkan mikrofon sepanjang ujian. Sistem akan mendeteksi apabila peserta berbicara, berbisik, atau mengobrol/bertanya dengan orang lain, dan mencatatnya sebagai pelanggaran serta mengambil bukti foto snapshot seketika.
                            </span>
                        </label>
                    </div>

                    <!-- Sensitivity Sub-panel -->
                    <div id="mic_sensitivity_container" class="{{ old('require_microphone', $quiz->require_microphone) ? '' : 'hidden' }} ml-7 pt-3 border-t border-gray-100 space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Tingkat Sensitivitas Mikrofon (Deteksi Suara):
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            @php $currSens = old('mic_sensitivity', $quiz->mic_sensitivity ?? 'medium'); @endphp
                            <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition text-xs {{ $currSens === 'low' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-400' : 'border-gray-200 hover:bg-gray-50' }}">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <input type="radio" name="mic_sensitivity" value="low" {{ $currSens === 'low' ? 'checked' : '' }} class="text-purple-600 focus:ring-purple-300">
                                        Rendah (Toleran)
                                    </span>
                                </div>
                                <span class="text-[11px] text-gray-500">Untuk ruangan agak bising. Hanya mendeteksi jika peserta bersuara jelas / lantang.</span>
                            </label>

                            <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition text-xs {{ $currSens === 'medium' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-400' : 'border-gray-200 hover:bg-gray-50' }}">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <input type="radio" name="mic_sensitivity" value="medium" {{ $currSens === 'medium' ? 'checked' : '' }} class="text-purple-600 focus:ring-purple-300">
                                        Sedang (Standar)
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Rekomendasi</span>
                                </div>
                                <span class="text-[11px] text-gray-500">Seimbang mendeteksi obrolan/pertanyaan wajar dengan toleransi suara ketukan keyboard.</span>
                            </label>

                            <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition text-xs {{ $currSens === 'high' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-400' : 'border-gray-200 hover:bg-gray-50' }}">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <input type="radio" name="mic_sensitivity" value="high" {{ $currSens === 'high' ? 'checked' : '' }} class="text-purple-600 focus:ring-purple-300">
                                        Tinggi (Ketat)
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Peka Bisikan</span>
                                </div>
                                <span class="text-[11px] text-gray-500">Sangat peka hingga suara bisikan pelan. Cocok hanya jika peserta di ruangan hening/kedap.</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Browser Lockdown Option -->
                <div class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-purple-300 transition bg-white">
                    <input type="checkbox" name="enable_browser_lockdown" id="enable_browser_lockdown" value="1" 
                           {{ old('enable_browser_lockdown', $quiz->enable_browser_lockdown) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-300">
                    <label for="enable_browser_lockdown" class="cursor-pointer">
                        <span class="text-sm font-bold text-gray-900 block flex items-center gap-1.5">
                            <i data-feather="lock" class="w-4 h-4 text-amber-600"></i> Kunci Layar Browser (Browser Lockdown)
                        </span>
                        <span class="text-xs text-gray-500 block mt-0.5">
                            Mewajibkan mode Fullscreen, mendeteksi perpindahan tab atau minimize jendela, memblokir klik kanan, copy, paste, dan tombol pintas DevTools (F12, Inspect Element).
                        </span>
                    </label>
                </div>

                <!-- Max Violations Threshold -->
                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                    <label class="block text-sm font-bold text-gray-900 mb-1">
                        Batas Toleransi Pelanggaran (Auto-Lock Threshold)
                    </label>
                    <p class="text-xs text-gray-500 mb-3">
                        Jumlah maksimal pelanggaran (keluar fullscreen, pindah tab, dll.) yang ditoleransi sebelum sesi ujian peserta otomatis terkunci dan tidak dapat dilanjutkan tanpa persetujuan dosen/pengawas.
                    </p>
                    <div class="flex items-center gap-3">
                        <input type="number" name="max_violations" 
                               value="{{ old('max_violations', $quiz->max_violations ?? 3) }}" 
                               min="1" max="50" 
                               class="w-28 rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm font-bold">
                        <span class="text-sm font-medium text-gray-600">Kali pelanggaran</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian 4: Safe Exam Browser (SEB) -->
        <div class="bg-white rounded-2xl shadow-sm border border-blue-200 p-6 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i data-feather="shield" class="w-4 h-4 text-blue-600"></i> Integrasi Safe Exam Browser (SEB)
                </h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    Lingkungan Lockdown Terkunci
                </span>
            </div>

            <!-- Master Toggle SEB -->
            <div class="bg-blue-50/70 p-4 rounded-xl border border-blue-100">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="require_seb" id="edit_require_seb" value="1" 
                           {{ old('require_seb', $quiz->require_seb) ? 'checked' : '' }}
                           onchange="toggleSebSub(this)"
                           class="mt-1 w-5 h-5 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-300">
                    <div>
                        <span class="text-base font-bold text-gray-900 block">Wajibkan Safe Exam Browser (SEB)</span>
                        <span class="text-xs text-gray-600 block mt-0.5">
                            Jika diaktifkan, peserta ujian hanya diizinkan membuka dan mengerjakan soal melalui aplikasi desktop Safe Exam Browser. Browser biasa seperti Chrome, Edge, atau Firefox akan ditolak.
                        </span>
                    </div>
                </label>
            </div>

            <!-- SEB Configuration Sub-options -->
            <div id="seb_sub_options" class="{{ old('require_seb', $quiz->require_seb) ? '' : 'hidden' }} space-y-4 pl-2">
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-1">
                        Password Keluar Aplikasi (Quit Password - Opsional)
                    </label>
                    <input type="text" name="seb_quit_password" 
                           value="{{ old('seb_quit_password', $quiz->seb_quit_password) }}" 
                           placeholder="Contoh: UjianPolkesban2026"
                           class="w-full max-w-md rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm">
                    <span class="text-xs text-gray-500 mt-1 block">
                        Peserta harus memasukkan password ini jika ingin menutup Safe Exam Browser sebelum kuis selesai. Kosongkan jika ingin mengizinkan keluar bebas.
                    </span>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-sm font-bold text-gray-900 block">Unduh File Konfigurasi SEB</span>
                        <span class="text-xs text-gray-500 block mt-0.5">
                            File konfigurasi (.seb) dapat dibagikan kepada peserta atau digunakan oleh proktor di lab komputer.
                        </span>
                    </div>
                    <a href="{{ route('pbm.quizzes.seb-config', $quiz->id) }}" 
                       class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-semibold rounded-xl text-xs flex items-center gap-1.5 shrink-0 shadow-sm transition">
                        <i data-feather="download" class="w-4 h-4 text-blue-600"></i> Unduh File (.seb)
                    </a>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold text-sm hover:bg-gray-100 transition">
                Batal
            </a>
            <button type="submit" class="pbm-primary px-7 py-2.5 rounded-xl font-bold text-sm shadow-md hover:opacity-90 transition flex items-center gap-2">
                <i data-feather="check" class="w-4 h-4"></i> Simpan Perubahan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function toggleProctoringSub(el) {
        const sub = document.getElementById('proctoring_sub_options');
        if (sub) {
            if (el.checked) {
                sub.classList.remove('hidden');
            } else {
                sub.classList.add('hidden');
            }
        }
    }

    function toggleSebSub(el) {
        const sub = document.getElementById('seb_sub_options');
        if (sub) {
            if (el.checked) {
                sub.classList.remove('hidden');
            } else {
                sub.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
@endpush

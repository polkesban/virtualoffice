@extends('pbm.layouts.app')

@section('fullwidth', true)

@section('content')
<style>
    .btn-ai-generate {
        background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4) !important;
        border: none !important;
    }
    .btn-ai-generate:hover {
        background: linear-gradient(135deg, #6d28d9 0%, #4338ca 100%) !important;
        box-shadow: 0 6px 20px rgba(124, 58, 237, 0.5) !important;
    }
    .btn-ai-generate * {
        color: #ffffff !important;
        stroke: #ffffff !important;
    }
    .btn-ai-save {
        background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(5, 150, 105, 0.35) !important;
        border: none !important;
    }
    .btn-ai-save:hover {
        background: linear-gradient(135deg, #047857 0%, #065f46 100%) !important;
    }
    .btn-ai-save * {
        color: #ffffff !important;
        stroke: #ffffff !important;
    }
    .ai-gradient-bg {
        background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important;
        color: #ffffff !important;
    }
    .ai-banner-bg {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%) !important;
        color: #ffffff !important;
    }
</style>
<div x-data="aiQuestionGenerator()" class="space-y-6">

    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="hover:text-sky-600 transition flex items-center gap-1 font-medium">
                    <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali ke Kuis
                </a>
                <span>/</span>
                <span class="text-gray-400 truncate max-w-xs">{{ $quiz->title }}</span>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Generate Soal AI</span>
            </nav>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl ai-gradient-bg flex items-center justify-center text-white shadow-md"
                     style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important; color: #ffffff !important;">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke: #ffffff !important;">
                        <rect x="4" y="4" width="16" height="16" rx="2" stroke-width="2"></rect>
                        <rect x="9" y="9" width="6" height="6" stroke-width="2"></rect>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 flex items-center gap-2">
                        <span>Generate Soal CBT Berbasis AI</span>
                    </h1>
                    <p class="text-gray-500 text-xs md:text-sm mt-0.5">
                        Kuis: <strong class="text-gray-700">{{ $quiz->title }}</strong> 
                        @if($class) | Kelas: <span class="text-gray-700">{{ $class->name }}</span> @endif
                        @if($session) | Sesi: <span class="text-gray-700">{{ $session->title }}</span> @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-1.5">
                <i data-feather="x" class="w-4 h-4"></i> Batal
            </a>
            <template x-if="step === 'preview'">
                <button type="button" @click="saveSelectedQuestions()" :disabled="selectedCount === 0 || isSaving" class="pbm-primary px-5 py-2 rounded-xl text-sm font-bold hover:opacity-90 transition shadow-md flex items-center gap-2 disabled:opacity-50">
                    <template x-if="!isSaving">
                        <span class="flex items-center gap-2">
                            <i data-feather="check" class="w-4 h-4"></i>
                            <span x-text="'Simpan ' + selectedCount + ' Soal ke Kuis'"></span>
                        </span>
                    </template>
                    <template x-if="isSaving">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Menyimpan...</span>
                        </span>
                    </template>
                </button>
            </template>
        </div>
    </div>

    <!-- Error Alert (if any) -->
    <div x-show="errorMessage" x-cloak class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm flex items-start gap-3">
        <i data-feather="alert-circle" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
        <div class="flex-1 text-sm text-red-700 font-medium" x-text="errorMessage"></div>
        <button type="button" @click="errorMessage = ''" class="text-red-400 hover:text-red-700">
            <i data-feather="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- ================================================================= -->
    <!-- TAHAP 1: KONFIGURASI SUMBER & PARAMETER                           -->
    <!-- ================================================================= -->
    <div x-show="step === 'config'" class="space-y-6">

        <!-- Card: Sumber Materi Acuan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">1</span>
                        <span>Pilih Sumber Materi / RPS Acuan</span>
                        <span class="text-red-500">*</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pilih bahan ajar yang sudah disematkan di kelas/sesi, unggah berkas baru, atau tuliskan pokok bahasan materi.
                    </p>
                </div>

                <!-- Tab Navigasi Sumber -->
                <div class="flex overflow-x-auto swipe-scroll max-w-full bg-gray-200/70 p-1 rounded-xl text-xs font-semibold gap-1 shrink-0 self-stretch sm:self-auto">
                    <button type="button" @click="sourceTab = 'materials'" 
                            :class="sourceTab === 'materials' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 shrink-0 whitespace-nowrap">
                        <i data-feather="folder" class="w-3.5 h-3.5"></i>
                        <span>Bahan Ajar & RPS Kelas</span>
                    </button>
                    <button type="button" @click="sourceTab = 'upload'" 
                            :class="sourceTab === 'upload' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 shrink-0 whitespace-nowrap">
                        <i data-feather="upload-cloud" class="w-3.5 h-3.5"></i>
                        <span>Upload Berkas Baru</span>
                    </button>
                    <button type="button" @click="sourceTab = 'custom'" 
                            :class="sourceTab === 'custom' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 shrink-0 whitespace-nowrap">
                        <i data-feather="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Tulis Pokok Bahasan</span>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- TAB 1: Bahan Ajar Kelas/Sesi -->
                <div x-show="sourceTab === 'materials'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-600">
                            Centang materi atau dokumen RPS yang ingin dianalisis oleh AI untuk menyusun soal:
                        </p>
                        @if($sessionMaterials->count() > 0)
                        <button type="button" @click="toggleSelectAllSessionMaterials()" class="text-xs text-sky-600 hover:text-sky-800 font-semibold">
                            <span x-text="isAllSessionSelected ? 'Batalkan Pilih Semua' : 'Pilih Semua Materi Sesi Ini'"></span>
                        </button>
                        @endif
                    </div>

                    <!-- Materi Sesi Ini -->
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i data-feather="layers" class="w-3.5 h-3.5 text-sky-500"></i>
                            <span>Bahan Ajar Pada Sesi Ini ({{ $sessionMaterials->count() }})</span>
                        </h3>
                        @if($sessionMaterials->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($sessionMaterials as $mat)
                                <label class="p-3.5 rounded-xl border transition-all cursor-pointer flex items-start gap-3 select-none"
                                       :class="selectedMaterialIds.includes({{ $mat->id }}) ? 'border-purple-400 bg-purple-50/30 ring-1 ring-purple-200' : 'border-gray-200 hover:border-gray-300 bg-white'">
                                    <input type="checkbox" value="{{ $mat->id }}" x-model="selectedMaterialIds" class="mt-1 w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm text-gray-800 truncate">{{ $mat->title }}</span>
                                            <span class="text-[10px] px-2 py-0.5 rounded bg-sky-100 text-sky-700 font-semibold uppercase shrink-0">Sesi Ini</span>
                                        </div>
                                        @if($mat->description)
                                        <p class="text-xs text-gray-500 line-clamp-1 mt-0.5">{{ $mat->description }}</p>
                                        @endif
                                        <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1">
                                            <i data-feather="file" class="w-3 h-3"></i> Tipe: {{ strtoupper($mat->type ?? 'Dokumen') }}
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-center text-xs text-gray-500">
                                Belum ada bahan ajar yang disematkan langsung di sesi ini. Anda dapat memilih materi dari sesi lain di bawah atau mengunggah berkas baru.
                            </div>
                        @endif
                    </div>

                    <!-- Materi Sesi Lain dalam Kelas (termasuk RPS) -->
                    @if($otherMaterials->count() > 0)
                    <div class="pt-3 border-t border-gray-100">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i data-feather="book-open" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span>Bahan Ajar / RPS Sesi Lain di Kelas Ini ({{ $otherMaterials->count() }})</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                            @foreach($otherMaterials as $oMat)
                            <label class="p-3 rounded-xl border transition-all cursor-pointer flex items-start gap-3 select-none"
                                   :class="selectedMaterialIds.includes({{ $oMat->id }}) ? 'border-purple-400 bg-purple-50/30 ring-1 ring-purple-200' : 'border-gray-200 hover:border-gray-300 bg-white'">
                                <input type="checkbox" value="{{ $oMat->id }}" x-model="selectedMaterialIds" class="mt-1 w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-gray-800 truncate">{{ $oMat->title }}</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-medium truncate shrink-0">
                                            {{ $oMat->clinicalSession->title ?? 'Sesi Lain' }}
                                        </span>
                                    </div>
                                    @if($oMat->description)
                                    <p class="text-xs text-gray-500 line-clamp-1 mt-0.5">{{ $oMat->description }}</p>
                                    @endif
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <!-- TAB 2: Upload Berkas Baru -->
                <div x-show="sourceTab === 'upload'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-1">
                            Unggah Berkas RPS / Materi / Modul (PDF, Word DOCX, PPTX, atau TXT)
                        </label>
                        <p class="text-xs text-gray-500 mb-3">
                            Unggah berkas referensi khusus untuk kuis ini. Sistem akan mengekstrak teks isi dokumen secara otomatis.
                        </p>
                        
                        <div class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-purple-400 hover:bg-purple-50/20 transition cursor-pointer relative"
                             @click="$refs.fileInput.click()">
                            <input type="file" x-ref="fileInput" @change="handleFileSelected($event)" accept=".pdf,.docx,.pptx,.txt" class="hidden">
                            
                            <template x-if="!uploadedFileName">
                                <div>
                                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center">
                                        <i data-feather="upload-cloud" class="w-6 h-6"></i>
                                    </div>
                                    <p class="font-bold text-sm text-gray-800">Klik untuk memilih berkas atau seret berkas ke sini</p>
                                    <p class="text-xs text-gray-400 mt-1">Mendukung format PDF, Word (.docx), PowerPoint (.pptx), atau Teks (.txt) — Maksimal 50 MB</p>
                                </div>
                            </template>

                            <template x-if="uploadedFileName">
                                <div class="flex items-center justify-center gap-3 bg-white p-3 rounded-xl border border-purple-200 shadow-xs max-w-md mx-auto">
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                        <i data-feather="file-text" class="w-5 h-5"></i>
                                    </div>
                                    <div class="text-left min-w-0 flex-1">
                                        <div class="font-bold text-sm text-gray-800 truncate" x-text="uploadedFileName"></div>
                                        <div class="text-xs text-gray-400" x-text="uploadedFileSize"></div>
                                    </div>
                                    <button type="button" @click.stop="removeUploadedFile()" class="p-1 text-gray-400 hover:text-red-500 rounded-lg">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Tulis Pokok Bahasan Manual -->
                <div x-show="sourceTab === 'custom'" class="space-y-3">
                    <label class="block text-sm font-semibold text-gray-800">
                        Pokok Bahasan / Teks Capaian Pembelajaran dari RPS
                    </label>
                    <p class="text-xs text-gray-500">
                        Anda dapat menempelkan silabus, materi kuliah, kasus ringkas, atau uraian kompetensi yang ingin diujikan.
                    </p>
                    <textarea x-model="customText" rows="6" 
                              class="w-full rounded-xl border-gray-300 shadow-sm text-sm focus:border-purple-500 focus:ring focus:ring-purple-200"
                              placeholder="Tempelkan ringkasan materi, diagnosis keperawatan/kebidanan, penatalaksanaan klinis, atau topik bahasan di sini..."></textarea>
                </div>
            </div>
        </div>

        <!-- Card: Parameter Pengaturan Soal -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">2</span>
                    <span>Parameter & Format Soal CBT</span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Sesuaikan standar soal agar sesuai dengan tingkat kompetensi dan tujuan evaluasi pembelajaran.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Jumlah Soal -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Jumlah Soal <span class="text-red-500">*</span>
                    </label>
                    <select x-model.number="questionCount" class="w-full rounded-xl border-gray-300 shadow-sm text-sm font-medium focus:border-purple-500 focus:ring focus:ring-purple-200 py-2.5">
                        <option value="1">1 Butir Soal</option>
                        <option value="3">3 Butir Soal</option>
                        <option value="5" selected>5 Butir Soal (Standar)</option>
                        <option value="8">8 Butir Soal</option>
                        <option value="10">10 Butir Soal</option>
                        <option value="15">15 Butir Soal</option>
                    </select>
                </div>

                <!-- Tingkat Kesulitan / Taksonomi Bloom -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Tingkat Taksonomi Bloom <span class="text-red-500">*</span>
                    </label>
                    <select x-model="difficulty" class="w-full rounded-xl border-gray-300 shadow-sm text-sm font-medium focus:border-purple-500 focus:ring focus:ring-purple-200 py-2.5">
                        <option value="c3_c4" selected>C3 - C4 (Aplikasi & Analisis Klinis - UKOM)</option>
                        <option value="c1_c2">C1 - C2 (Mengingat & Pemahaman Teori)</option>
                        <option value="c5_c6">C5 - C6 (Evaluasi & Penalaran Kritis Lanjut)</option>
                    </select>
                </div>

                <!-- Tipe Format Soal -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Format Soal <span class="text-red-500">*</span>
                    </label>
                    <select x-model="formatType" class="w-full rounded-xl border-gray-300 shadow-sm text-sm font-medium focus:border-purple-500 focus:ring focus:ring-purple-200 py-2.5">
                        <option value="vignette_case" selected>Kasus / Vignette Klinis Pasien (CBT)</option>
                        <option value="conceptual">Pilihan Ganda Konseptual / Teori</option>
                    </select>
                </div>

                <!-- Jumlah Opsi Jawaban -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Jumlah Pilihan Opsi <span class="text-red-500">*</span>
                    </label>
                    <select x-model.number="optionCount" class="w-full rounded-xl border-gray-300 shadow-sm text-sm font-medium focus:border-purple-500 focus:ring focus:ring-purple-200 py-2.5">
                        <option value="5" selected>5 Opsi (A, B, C, D, E) - Standar CBT</option>
                        <option value="4">4 Opsi (A, B, C, D)</option>
                    </select>
                </div>
            </div>

            <!-- Instruksi Tambahan (Opsional) -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                    Instruksi Khusus Tambahan <span class="text-xs text-gray-400 font-normal">(Opsional)</span>
                </label>
                <input type="text" x-model="additionalInstructions" 
                       placeholder="Contoh: Fokuskan pada kegawatdaruratan maternal, sertakan nilai TTV abnormal, atau fokus tindakan pencegahan..." 
                       class="w-full rounded-xl border-gray-300 shadow-sm text-sm focus:border-purple-500 focus:ring focus:ring-purple-200">
            </div>
        </div>

        <!-- Tombol Aksi Generate -->
        <div class="flex items-center justify-between bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <i data-feather="info" class="w-4 h-4 text-purple-500 shrink-0"></i>
                <span>AI akan menyusun soal lengkap dengan skenario kasus, kunci jawaban terbaik, dan pembahasan rasional per opsi.</span>
            </div>
            <button type="button" @click="startGeneration()" 
                    class="btn-ai-generate font-bold px-8 py-3.5 rounded-xl text-sm transition flex items-center gap-2 cursor-pointer select-none"
                    style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important; color: #ffffff !important; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.45) !important;">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke: #ffffff !important;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="text-white font-bold text-sm tracking-wide" style="color: #ffffff !important; font-weight: 700;">Mulai Generate Soal dengan AI</span>
            </button>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAHAP 2: INDIKATOR LOADING ANIMASI                               -->
    <!-- ================================================================= -->
    <div x-show="step === 'loading'" x-cloak class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center space-y-6">
        <div class="relative w-20 h-20 mx-auto">
            <div class="absolute inset-0 rounded-full border-4 border-purple-100 animate-ping"></div>
            <div class="w-20 h-20 rounded-full flex items-center justify-center text-white shadow-xl"
                 style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important; color: #ffffff !important;">
                <svg class="w-8 h-8 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke: #ffffff !important;">
                    <rect x="4" y="4" width="16" height="16" rx="2" stroke-width="2"></rect>
                    <rect x="9" y="9" width="6" height="6" stroke-width="2"></rect>
                </svg>
            </div>
        </div>

        <div>
            <h3 class="text-xl font-extrabold text-gray-900 mb-2">Sedang Menyusun Soal CBT dengan AI...</h3>
            <p class="text-sm font-medium text-purple-700 min-h-[24px]" x-text="loadingMessages[loadingMessageIndex]"></p>
            <p class="text-xs text-gray-400 mt-2">
                Model AI sedang membaca materi dan merumuskan soal vignette, opsi jawaban, dan rasional ilmiah.
            </p>
        </div>

        <div class="max-w-md mx-auto bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full w-2/3 animate-pulse rounded-full"
                 style="background: linear-gradient(90deg, #7c3aed 0%, #4f46e5 100%) !important;"></div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAHAP 3: LAYAR PRATINJAU & TELAAH SOAL (INTERACTIVE REVIEW)       -->
    <!-- ================================================================= -->
    <div x-show="step === 'preview'" x-cloak class="space-y-6">
        <!-- Review Banner -->
        <div class="rounded-2xl p-6 text-white shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
             style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%) !important; color: #ffffff !important;">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-white/20 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider backdrop-blur-sm" style="color: #ffffff !important;">
                        Telaah & Konfirmasi Soal
                    </span>
                    <span class="text-xs font-medium" style="color: #e9d5ff !important;" x-text="generatedQuestions.length + ' Soal Dihasilkan'"></span>
                </div>
                <h2 class="text-xl font-bold" style="color: #ffffff !important;">Hasil Pembuatan Soal CBT oleh AI</h2>
                <p class="text-xs mt-1" style="color: #e9d5ff !important;">
                    Periksa kasus/vignette, pertanyaan, dan rasional tiap opsi di bawah. Anda dapat mengedit teks secara langsung atau menghapus soal yang tidak diinginkan sebelum disimpan.
                </p>
            </div>

            <div class="flex items-center gap-3 self-stretch md:self-auto justify-between md:justify-end">
                <button type="button" @click="step = 'config'" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-semibold border border-white/20 transition flex items-center gap-1.5">
                    <i data-feather="refresh-cw" class="w-3.5 h-3.5"></i> Buat Ulang
                </button>
                <button type="button" @click="saveSelectedQuestions()" :disabled="selectedCount === 0 || isSaving" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/30 transition flex items-center gap-2 disabled:opacity-50">
                    <i data-feather="check-circle" class="w-4 h-4"></i>
                    <span x-text="'Simpan ' + selectedCount + ' Soal ke Kuis'"></span>
                </button>
            </div>
        </div>

        <!-- Quick Toolbar: Pilih Semua & Counter -->
        <div class="flex items-center justify-between bg-white px-5 py-3.5 rounded-xl border border-gray-200 shadow-xs">
            <label class="flex items-center gap-2.5 cursor-pointer select-none text-sm font-bold text-gray-800">
                <input type="checkbox" @change="toggleSelectAllGenerated($event.target.checked)" :checked="selectedCount === generatedQuestions.length && generatedQuestions.length > 0" class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                <span>Pilih Semua Soal</span>
            </label>
            <div class="text-xs font-semibold text-gray-600">
                <span class="text-purple-700 font-bold" x-text="selectedCount"></span> dari <span x-text="generatedQuestions.length"></span> soal dipilih
            </div>
        </div>

        <!-- Daftar Soal Hasil Generate -->
        <div class="space-y-6">
            <template x-for="(q, qIndex) in generatedQuestions" :key="'gen_q_' + qIndex">
                <div class="bg-white rounded-2xl border-2 transition-all duration-200 overflow-hidden shadow-sm"
                     :class="q.isSelected ? 'border-purple-300 ring-2 ring-purple-100/50' : 'border-gray-200 opacity-60 bg-gray-50/50'">
                    
                    <!-- Header Soal -->
                    <div class="p-4 bg-gray-50/80 border-b border-gray-200/80 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" x-model="q.isSelected" class="w-5 h-5 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span class="w-7 h-7 rounded-lg bg-purple-600 text-white font-extrabold text-xs flex items-center justify-center shadow-xs"
                                      x-text="qIndex + 1"></span>
                                <span class="font-bold text-sm text-gray-900" x-text="'Soal #' + (qIndex + 1)"></span>
                            </label>
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 border border-purple-200"
                                  x-text="q.points + ' Poin'"></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="removeGeneratedQuestion(qIndex)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Hapus Soal Ini dari Hasil">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Isi Soal -->
                    <div class="p-6 space-y-5">

                        <!-- Kasus / Vignette (jika ada) -->
                        <template x-if="q.vignette">
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <span>Kasus / Vignette Klinis</span>
                                    </label>
                                    <span class="text-[11px] text-blue-500 font-medium">(Dapat diedit langsung)</span>
                                </div>
                                <textarea x-model="q.vignette" rows="4" 
                                          class="w-full rounded-lg border-blue-200 shadow-xs text-sm text-gray-800 focus:border-blue-500 focus:ring focus:ring-blue-200 bg-white leading-relaxed"></textarea>
                            </div>
                        </template>

                        <!-- Pertanyaan / Lead-in -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Pertanyaan / Lead-in <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-gray-400 font-medium">(Dapat diedit langsung)</span>
                            </div>
                            <textarea x-model="q.question_text" rows="2" 
                                      class="w-full rounded-xl border-gray-300 shadow-sm text-sm font-semibold text-gray-900 focus:border-purple-500 focus:ring focus:ring-purple-200 bg-white leading-relaxed"></textarea>
                        </div>

                        <!-- Opsi Jawaban & Rasional -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                Pilihan Opsi & Rasional Pembahasan:
                            </label>
                            
                            <div class="space-y-3">
                                <template x-for="(opt, optIndex) in q.options" :key="'opt_' + qIndex + '_' + optIndex">
                                    <div class="p-4 rounded-xl border transition-all"
                                         :class="opt.is_correct ? 'border-emerald-400 bg-emerald-50/20 ring-1 ring-emerald-200' : 'border-gray-200 bg-gray-50/30'">
                                        
                                        <!-- Opsi Radio & Teks -->
                                        <div class="flex items-start gap-3 mb-2.5">
                                            <label class="flex items-center gap-2 cursor-pointer select-none pt-1 shrink-0">
                                                <input type="radio" :name="'correct_opt_' + qIndex" :value="optIndex" 
                                                       :checked="opt.is_correct"
                                                       @change="setCorrectOption(qIndex, optIndex)"
                                                       class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 cursor-pointer">
                                                <span class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs"
                                                      :class="opt.is_correct ? 'bg-emerald-600 text-white shadow-xs' : 'bg-gray-200 text-gray-700'"
                                                      x-text="String.fromCharCode(65 + optIndex)"></span>
                                            </label>
                                            
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-[11px] font-bold uppercase tracking-wider"
                                                          :class="opt.is_correct ? 'text-emerald-700 font-extrabold' : 'text-gray-500'"
                                                          x-text="'Opsi ' + String.fromCharCode(65 + optIndex) + (opt.is_correct ? ' (Kunci Jawaban Benar)' : '')"></span>
                                                    <span x-show="opt.is_correct" class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        ✓ Jawaban Benar
                                                    </span>
                                                </div>
                                                <input type="text" x-model="opt.text" 
                                                       class="w-full rounded-lg border-gray-300 shadow-xs text-sm focus:border-purple-500 focus:ring focus:ring-purple-200 bg-white font-medium"
                                                       placeholder="Teks opsi jawaban...">
                                            </div>
                                        </div>

                                        <!-- Rasional Pembahasan -->
                                        <div class="ml-9">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[11px] font-semibold text-gray-500">Rasional / Pembahasan:</span>
                                            </div>
                                            <textarea x-model="opt.rationale" rows="1" 
                                                      class="w-full rounded-lg border-gray-200 shadow-xs text-xs text-gray-700 focus:border-purple-500 focus:ring focus:ring-purple-200 bg-white"
                                                      placeholder="Penjelasan mengapa opsi ini benar atau salah..."></textarea>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </template>
        </div>

        <!-- Sticky Bottom Bar Review -->
        <div class="sticky bottom-4 z-30 bg-white/95 backdrop-blur-md border border-gray-200 rounded-2xl p-4 shadow-xl flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                <span class="w-3 h-3 rounded-full bg-purple-600"></span>
                <span>Siap disimpan: <strong class="text-purple-700 text-sm" x-text="selectedCount"></strong> dari <span x-text="generatedQuestions.length"></span> butir soal</span>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="step = 'config'" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-sm transition">
                    Kembali ke Pengaturan
                </button>
                <button type="button" @click="saveSelectedQuestions()" :disabled="selectedCount === 0 || isSaving" 
                        class="btn-ai-save font-bold px-7 py-2.5 rounded-xl text-sm transition shadow-md flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                        style="background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; color: #ffffff !important; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.35) !important;">
                    <template x-if="!isSaving">
                        <span class="flex items-center gap-2 text-white" style="color: #ffffff !important;">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke: #ffffff !important;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span style="color: #ffffff !important; font-weight: 700;" x-text="'Simpan ' + selectedCount + ' Soal ke Kuis'"></span>
                        </span>
                    </template>
                    <template x-if="isSaving">
                        <span class="flex items-center gap-2 text-white" style="color: #ffffff !important;">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="color: #ffffff !important;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span style="color: #ffffff !important;">Menyimpan ke Database...</span>
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden Form untuk Submit ke Controller store() -->
    <form x-ref="submitForm" action="{{ route('pbm.quizzes.questions.ai.store', $quiz->id) }}" method="POST" class="hidden">
        @csrf
        <div x-ref="hiddenInputsContainer"></div>
    </form>

</div>
@endsection

@push('scripts')
<script>
window.aiQuestionGenerator = function() {
    return {
        step: 'config', // 'config', 'loading', 'preview'
        sourceTab: 'materials', // 'materials', 'upload', 'custom'
        
        // Sumber Data
        selectedMaterialIds: [],
        uploadedFile: null,
        uploadedFileName: '',
        uploadedFileSize: '',
        customText: '',
        
        // Parameter Soal
        questionCount: 5,
        difficulty: 'c3_c4',
        formatType: 'vignette_case',
        optionCount: 5,
        additionalInstructions: '',

        // Hasil Generate & Preview
        generatedQuestions: [],
        errorMessage: '',
        isSaving: false,

        // Loading Messages Rotator
        loadingMessageIndex: 0,
        loadingMessages: [
            'Membaca dan mengekstrak dokumen materi serta RPS perkuliahan...',
            'Menelaah kompetensi klinis dan menyusun skenario kasus vignette...',
            'Merumuskan kalimat pertanyaan lead-in yang terfokus...',
            'Menyusun pilihan jawaban homogen dan menetapkan kunci jawaban benar...',
            'Menuliskan rasional dan pembahasan ilmiah untuk setiap opsi jawaban...'
        ],
        loadingInterval: null,

        get isAllSessionSelected() {
            const sessionIds = [{{ $sessionMaterials->pluck('id')->implode(',') }}];
            return sessionIds.length > 0 && sessionIds.every(id => this.selectedMaterialIds.includes(id));
        },

        get selectedCount() {
            return this.generatedQuestions.filter(q => q.isSelected).length;
        },

        toggleSelectAllSessionMaterials() {
            const sessionIds = [{{ $sessionMaterials->pluck('id')->implode(',') }}];
            if (this.isAllSessionSelected) {
                this.selectedMaterialIds = this.selectedMaterialIds.filter(id => !sessionIds.includes(id));
            } else {
                sessionIds.forEach(id => {
                    if (!this.selectedMaterialIds.includes(id)) {
                        this.selectedMaterialIds.push(id);
                    }
                });
            }
        },

        handleFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.uploadedFile = file;
            this.uploadedFileName = file.name;
            this.uploadedFileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            this.$nextTick(() => { if (typeof feather !== 'undefined') feather.replace(); });
        },

        removeUploadedFile() {
            this.uploadedFile = null;
            this.uploadedFileName = '';
            this.uploadedFileSize = '';
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        toggleSelectAllGenerated(checked) {
            this.generatedQuestions.forEach(q => q.isSelected = checked);
        },

        setCorrectOption(qIndex, targetOptIndex) {
            this.generatedQuestions[qIndex].options.forEach((opt, idx) => {
                opt.is_correct = (idx === targetOptIndex);
            });
        },

        removeGeneratedQuestion(index) {
            this.generatedQuestions.splice(index, 1);
            if (this.generatedQuestions.length === 0) {
                this.step = 'config';
            }
        },

        async startGeneration() {
            this.errorMessage = '';

            // Cek kelengkapan sumber materi
            const hasMaterials = this.selectedMaterialIds.length > 0;
            const hasUpload = !!this.uploadedFile;
            const hasCustomText = this.customText.trim().length >= 10;

            if (!hasMaterials && !hasUpload && !hasCustomText) {
                this.errorMessage = 'Harap pilih minimal satu bahan ajar kelas, unggah berkas baru, atau tuliskan pokok bahasan materi sebelum memulai.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            this.step = 'loading';
            this.loadingMessageIndex = 0;
            this.loadingInterval = setInterval(() => {
                this.loadingMessageIndex = (this.loadingMessageIndex + 1) % this.loadingMessages.length;
            }, 3500);

            // Siapkan FormData
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            this.selectedMaterialIds.forEach(id => formData.append('material_ids[]', id));
            if (this.uploadedFile) {
                formData.append('uploaded_file', this.uploadedFile);
            }
            if (this.customText.trim()) {
                formData.append('custom_text', this.customText.trim());
            }
            formData.append('question_count', this.questionCount);
            formData.append('difficulty', this.difficulty);
            formData.append('format_type', this.formatType);
            formData.append('option_count', this.optionCount);
            if (this.additionalInstructions.trim()) {
                formData.append('additional_instructions', this.additionalInstructions.trim());
            }

            try {
                const response = await fetch('{{ route('pbm.quizzes.questions.ai.generate', $quiz->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                clearInterval(this.loadingInterval);

                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch(parseErr) {
                    const cleanSnippet = responseText ? responseText.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').substring(0, 180).trim() : '';
                    throw new Error(`Server error (${response.status}): ` + (cleanSnippet || 'Terjadi gangguan koneksi atau memori server.'));
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Terjadi kesalahan pada server saat memproses soal AI.');
                }

                if (!Array.isArray(data.questions) || data.questions.length === 0) {
                    throw new Error('AI tidak menghasilkan butir soal yang valid.');
                }

                // Tandai semua soal default terpilih
                this.generatedQuestions = data.questions.map(q => ({
                    ...q,
                    isSelected: true
                }));

                this.step = 'preview';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                this.$nextTick(() => { if (typeof feather !== 'undefined') feather.replace(); });

            } catch (err) {
                clearInterval(this.loadingInterval);
                this.step = 'config';
                this.errorMessage = err.message || 'Gagal menyusun soal dengan AI.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                this.$nextTick(() => { if (typeof feather !== 'undefined') feather.replace(); });
            }
        },

        saveSelectedQuestions() {
            const selected = this.generatedQuestions.filter(q => q.isSelected);
            if (selected.length === 0) {
                alert('Pilih minimal satu butir soal untuk disimpan ke kuis.');
                return;
            }

            this.isSaving = true;

            // Masukkan data soal ke form tersembunyi
            const container = this.$refs.hiddenInputsContainer;
            container.innerHTML = '';

            selected.forEach((q, qIdx) => {
                const addInput = (name, val) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = val ?? '';
                    container.appendChild(input);
                };

                addInput(`questions[${qIdx}][vignette]`, q.vignette);
                addInput(`questions[${qIdx}][question_text]`, q.question_text);
                addInput(`questions[${qIdx}][points]`, q.points || 1);

                q.options.forEach((opt, optIdx) => {
                    addInput(`questions[${qIdx}][options][${optIdx}][text]`, opt.text);
                    addInput(`questions[${qIdx}][options][${optIdx}][is_correct]`, opt.is_correct ? '1' : '0');
                    addInput(`questions[${qIdx}][options][${optIdx}][rationale]`, opt.rationale);
                });
            });

            this.$refs.submitForm.submit();
        }
    };
};
</script>
@endpush

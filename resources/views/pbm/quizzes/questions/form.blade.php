@extends('pbm.layouts.app')

@section('fullwidth', true)

@section('content')
@php
    $isEdit = $isEdit ?? false;
    
    // Prepare initial options for Alpine.js
    if (old('options')) {
        $rawOptions = array_values(old('options'));
        $initOptions = array_map(function($opt, $i) {
            return [
                'uid' => 'opt_' . uniqid(),
                'id' => $opt['id'] ?? null,
                'text' => $opt['text'] ?? '',
                'rationale' => $opt['rationale'] ?? '',
                'is_correct' => false
            ];
        }, $rawOptions, array_keys($rawOptions));
        $initCorrectIndex = (int) old('correct_option_index', 0);
    } elseif ($isEdit && isset($question)) {
        $rawOptions = $question->options->values();
        $initCorrectIndex = 0;
        $initOptions = $rawOptions->map(function($opt, $idx) use (&$initCorrectIndex) {
            if ($opt->is_correct) {
                $initCorrectIndex = $idx;
            }
            return [
                'uid' => 'opt_' . $opt->id . '_' . uniqid(),
                'id' => $opt->id,
                'text' => $opt->option_text ?? '',
                'rationale' => $opt->rationale ?? '',
                'is_correct' => (bool)$opt->is_correct
            ];
        })->toArray();
    } else {
        $initCorrectIndex = 0;
        $initOptions = [
            ['uid' => 'opt_' . uniqid(), 'id' => null, 'text' => '', 'rationale' => '', 'is_correct' => true],
            ['uid' => 'opt_' . uniqid(), 'id' => null, 'text' => '', 'rationale' => '', 'is_correct' => false],
            ['uid' => 'opt_' . uniqid(), 'id' => null, 'text' => '', 'rationale' => '', 'is_correct' => false],
            ['uid' => 'opt_' . uniqid(), 'id' => null, 'text' => '', 'rationale' => '', 'is_correct' => false],
        ];
    }
@endphp

<!-- Notification / Errors -->
@if (isset($errors) && $errors->any())
<div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl mb-6 shadow-sm">
    <div class="flex items-center gap-2 text-red-800 font-semibold mb-1">
        <i data-feather="alert-triangle" class="w-5 h-5 text-red-600"></i>
        <span>Terdapat kesalahan pada formulir:</span>
    </div>
    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Breadcrumb & Top Bar -->
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="hover:text-sky-600 transition flex items-center gap-1 font-medium">
                <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali ke Kuis
            </a>
            <span>/</span>
            <span class="text-gray-400 truncate max-w-xs">{{ $quiz->title }}</span>
            <span>/</span>
            <span class="text-gray-800 font-semibold">{{ $isEdit ? 'Edit Soal' : 'Tambah Soal Baru' }}</span>
        </nav>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900">
                {{ $isEdit ? 'Edit Soal CBT' : 'Tambah Soal CBT Baru' }}
            </h1>
            <span class="bg-sky-100 text-sky-800 text-xs font-semibold px-3 py-1 rounded-full border border-sky-200">
                Halaman Editor Penuh
            </span>
        </div>
        <p class="text-gray-500 text-sm mt-1">
            Kuis: <span class="font-medium text-gray-700">{{ $quiz->title }}</span>
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
            <i data-feather="x" class="w-4 h-4"></i> Batal
        </a>
        <button type="submit" form="questionForm" class="pbm-primary px-5 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-md flex items-center gap-2">
            <i data-feather="check" class="w-4 h-4"></i> Simpan Soal
        </button>
    </div>
</div>

@if(!$isEdit)
<div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200/70 rounded-2xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0 shadow-sm">
            <i data-feather="cpu" class="w-5 h-5"></i>
        </div>
        <div>
            <h4 class="font-bold text-gray-900 text-sm">Ingin membuat soal lebih cepat dan otomatis?</h4>
            <p class="text-xs text-gray-600">Gunakan fitur AI Generator untuk menyusun soal CBT berdasarkan materi atau RPS kelas.</p>
        </div>
    </div>
    <a href="{{ route('pbm.quizzes.questions.ai.create', $quiz->id) }}" class="px-4 py-2 text-white text-xs font-semibold rounded-xl shadow-sm hover:opacity-95 transition flex items-center gap-1.5 shrink-0 self-start sm:self-auto" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important;">
        <i data-feather="zap" class="w-3.5 h-3.5"></i> Gunakan AI Generator
    </a>
</div>
@endif

<form id="questionForm" 
      action="{{ $isEdit ? route('pbm.questions.update', $question->id) : route('pbm.quizzes.questions.store', $quiz->id) }}" 
      method="POST" 
      class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <!-- Card 1: Kasus / Vignette (Opsional) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 transition-all hover:shadow-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-3 border-b border-gray-100">
            <div>
                <label class="block text-base font-bold text-gray-800">
                    Kasus / Vignette <span class="text-xs text-gray-400 font-normal">(Opsional)</span>
                </label>
                <p class="text-xs text-gray-500 mt-0.5">
                    Gunakan bagian ini untuk skenario kasus, narasi klinis pasien, atau konteks data sebelum pertanyaan inti.
                </p>
            </div>
            <button type="button" onclick="toggleEditorHeight('vignette_editor')" class="self-start sm:self-auto text-xs text-sky-700 hover:text-sky-900 font-medium bg-sky-50 px-3 py-1.5 rounded-lg border border-sky-200 hover:bg-sky-100 transition flex items-center gap-1.5 shadow-xs">
                <i data-feather="maximize-2" class="w-3.5 h-3.5"></i> Perbesar / Perkecil Editor
            </button>
        </div>
        <div>
            <textarea id="vignette_editor" name="vignette" rows="6" class="w-full rounded-xl border-gray-300 shadow-sm">{!! old('vignette', $question->vignette ?? '') !!}</textarea>
        </div>
    </div>

    <!-- Card 2: Pertanyaan / Lead-in (Wajib) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 transition-all hover:shadow-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-3 border-b border-gray-100">
            <div>
                <label class="block text-base font-bold text-gray-800">
                    Pertanyaan / Lead-in <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mt-0.5">
                    Tuliskan kalimat instruksi atau pertanyaan inti yang wajib dijawab oleh peserta ujian.
                </p>
            </div>
            <button type="button" onclick="toggleEditorHeight('question_editor')" class="self-start sm:self-auto text-xs text-sky-700 hover:text-sky-900 font-medium bg-sky-50 px-3 py-1.5 rounded-lg border border-sky-200 hover:bg-sky-100 transition flex items-center gap-1.5 shadow-xs">
                <i data-feather="maximize-2" class="w-3.5 h-3.5"></i> Perbesar / Perkecil Editor
            </button>
        </div>
        <div>
            <textarea id="question_editor" name="question_text" rows="5" class="w-full rounded-xl border-gray-300 shadow-sm">{!! old('question_text', $question->question_text ?? '') !!}</textarea>
        </div>
    </div>

    <!-- Card 3: Poin / Bobot Soal -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1">
                    Bobot Poin Soal <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500">Nilai yang didapatkan peserta jika menjawab soal ini dengan benar.</p>
            </div>
            <div class="self-start sm:self-auto sm:ml-auto">
                <input type="number" name="points" min="1" required 
                       value="{{ old('points', $question->points ?? 1) }}" 
                       class="w-28 rounded-xl border-gray-300 text-center font-bold text-base shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200">
            </div>
        </div>
    </div>

    <!-- Card 4: Opsi Jawaban & Rasional / Pembahasan (Alpine.js) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6"
         x-data="questionOptionsHandler({{ json_encode($initOptions) }}, {{ $initCorrectIndex }})">
        
        <div class="mb-5 pb-3 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <span>Pilihan Opsi Jawaban & Rasional</span>
                        <span class="text-red-500">*</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pilih satu opsi sebagai kunci jawaban benar (lingkaran radio). Tuliskan teks opsi dan sertakan penjelasan rasional untuk setiap pilihan.
                    </p>
                </div>
                <div class="text-xs font-semibold px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-200 self-start sm:self-auto flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Kunci aktif: Opsi <strong x-text="String.fromCharCode(65 + correctOptionIndex)"></strong></span>
                </div>
            </div>
        </div>

        <!-- Options Container -->
        <div class="space-y-5">
            <template x-for="(option, index) in options" :key="option.uid">
                <div class="p-5 rounded-2xl border-2 transition-all duration-200"
                     :class="correctOptionIndex === index 
                        ? 'border-emerald-500 bg-emerald-50/20 shadow-sm ring-2 ring-emerald-100' 
                        : 'border-gray-200 bg-gray-50/40 hover:border-gray-300'">
                    
                    <!-- Option Header -->
                    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-200/70">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="radio" 
                                   name="correct_option_index" 
                                   :value="index"
                                   :checked="correctOptionIndex === index" 
                                   @change="correctOptionIndex = index"
                                   class="w-5 h-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 cursor-pointer">
                            <span class="w-8 h-8 rounded-xl flex items-center justify-center font-extrabold text-sm transition shadow-xs"
                                  :class="correctOptionIndex === index ? 'bg-emerald-600 text-white shadow-emerald-200' : 'bg-gray-200 text-gray-700'"
                                  x-text="String.fromCharCode(65 + index)"></span>
                            <span class="font-bold text-gray-800 text-base" x-text="'Opsi ' + String.fromCharCode(65 + index)"></span>
                            <span x-show="correctOptionIndex === index" 
                                  class="text-xs font-bold px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full border border-emerald-300 shadow-xs flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                <span>Kunci Jawaban Benar</span>
                            </span>
                        </label>
                        
                        <button type="button" 
                                @click="removeOption(index)" 
                                x-show="options.length > 2" 
                                class="text-gray-400 hover:text-red-600 p-2 rounded-xl hover:bg-red-50 transition" 
                                title="Hapus Opsi ini">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>

                    <!-- Hidden ID for Existing Option -->
                    <template x-if="option.id">
                        <input type="hidden" :name="'options['+index+'][id]'" :value="option.id">
                    </template>

                    <!-- Option Text (TinyMCE Rich Text) -->
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Teks Jawaban Opsi <span class="text-red-500">*</span>
                        </label>
                        <textarea :id="'opt_editor_' + option.uid" 
                                  :name="'options['+index+'][text]'" 
                                  class="w-full rounded-xl border-gray-300 shadow-sm"></textarea>
                    </div>

                    <!-- Option Rationale / Pembahasan -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Rasional / Pembahasan Opsi
                            </label>
                            <span class="text-xs text-gray-400">Digunakan untuk pembahasan & analisis butir soal</span>
                        </div>
                        <textarea :name="'options['+index+'][rationale]'" 
                                  x-model="option.rationale" 
                                  rows="2" 
                                  class="w-full rounded-xl border-gray-300 shadow-sm text-sm focus:border-sky-500 focus:ring focus:ring-sky-200 bg-white" 
                                  placeholder="Tuliskan alasan / analisis mengapa opsi ini benar atau salah..."></textarea>
                    </div>
                </div>
            </template>
        </div>

        <!-- Add Option Button -->
        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
            <button type="button" 
                    @click="addOption()" 
                    class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-sm transition flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                <span>Tambah Opsi Jawaban</span>
            </button>
            <span class="text-xs text-gray-400" x-text="'Total opsi: ' + options.length"></span>
        </div>
    </div>

    <!-- Bottom Sticky Action Bar -->
    <div class="sticky bottom-4 z-30 bg-white/95 backdrop-blur-md border border-gray-200/80 rounded-2xl p-4 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-gray-600">
            <i data-feather="info" class="w-4 h-4 text-sky-500 shrink-0"></i>
            <span>Pastikan salah satu opsi telah dipilih sebagai <strong>Kunci Jawaban Benar</strong> sebelum menyimpan.</span>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">
                Batal
            </a>
            <button type="submit" class="pbm-primary px-7 py-2.5 rounded-xl text-sm font-bold hover:opacity-90 transition shadow-md flex items-center gap-2">
                <i data-feather="check-circle" class="w-4 h-4"></i>
                <span>Simpan Soal</span>
            </button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
window.toggleEditorHeight = function(editorId) {
    const ed = tinymce.get(editorId);
    if (!ed) return;
    const container = ed.getContainer();
    if (!container) return;
    const currentHeight = container.offsetHeight;
    const targetHeight = currentHeight < 380 ? 540 : 260;
    container.style.height = targetHeight + 'px';
    const iframe = container.querySelector('iframe');
    const header = container.querySelector('.tox-editor-header');
    const statusbar = container.querySelector('.tox-statusbar');
    const headerHeight = header ? header.offsetHeight : 0;
    const statusbarHeight = statusbar ? statusbar.offsetHeight : 0;
    if (iframe) {
        iframe.style.height = (targetHeight - headerHeight - statusbarHeight) + 'px';
    }
};

window.questionOptionsHandler = function(initialOptions, initialCorrectIndex) {
    return {
        options: initialOptions || [],
        correctOptionIndex: typeof initialCorrectIndex === 'number' ? initialCorrectIndex : 0,

        init() {
            this.$nextTick(() => {
                this.options.forEach(opt => {
                    this.initOptionEditor(opt.uid, opt.text);
                });
            });
        },

        initOptionEditor(uid, initialText) {
            const selector = '#opt_editor_' + uid;
            if (tinymce.get('opt_editor_' + uid)) {
                tinymce.get('opt_editor_' + uid).destroy();
            }
            tinymce.init({
                selector: selector,
                height: 120,
                min_height: 90,
                resize: 'both',
                menubar: false,
                statusbar: false,
                plugins: ['lists', 'charmap'],
                toolbar: 'bold italic underline | superscript subscript | forecolor | bullist numlist | removeformat',
                content_style: 'body { font-family:Inter,Helvetica,Arial,sans-serif; font-size:13px; margin: 8px; }',
                setup: function(editor) {
                    editor.on('init', function() {
                        if (initialText) {
                            editor.setContent(initialText);
                        }
                    });
                    editor.on('change input keyup paste', function() {
                        editor.save();
                    });
                }
            });
        },

        addOption() {
            const newUid = 'opt_' + Math.random().toString(36).substr(2, 9);
            this.options.push({
                uid: newUid,
                id: null,
                text: '',
                rationale: '',
                is_correct: false
            });
            this.$nextTick(() => {
                this.initOptionEditor(newUid, '');
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        removeOption(index) {
            if (this.options.length <= 2) {
                alert('Soal minimal harus memiliki 2 pilihan opsi jawaban.');
                return;
            }
            const opt = this.options[index];
            const ed = tinymce.get('opt_editor_' + opt.uid);
            if (ed) {
                ed.destroy();
            }
            if (this.correctOptionIndex === index) {
                this.correctOptionIndex = 0;
            } else if (this.correctOptionIndex > index) {
                this.correctOptionIndex--;
            }
            this.options.splice(index, 1);
        }
    };
};

document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi TinyMCE untuk Kasus & Pertanyaan
    tinymce.init({
        selector: '#vignette_editor, #question_editor',
        height: 260,
        min_height: 180,
        resize: 'both',
        statusbar: true,
        elementpath: false,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'image media table | fullscreen | removeformat | help',
        content_style: 'body { font-family:Inter,Helvetica,Arial,sans-serif; font-size:14px; line-height: 1.6; }',
        setup: function(editor) {
            editor.on('change input keyup paste', function() {
                editor.save();
            });
        }
    });

    // Form submit validation & sync
    const form = document.getElementById('questionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (window.tinymce) {
                tinymce.triggerSave();
            }
            const qEditor = tinymce.get('question_editor');
            const qContent = qEditor ? qEditor.getContent({format: 'text'}).trim() : '';
            if (!qContent && !document.getElementById('question_editor').value.trim()) {
                e.preventDefault();
                alert('Mohon isi Pertanyaan / Lead-in terlebih dahulu sebelum menyimpan.');
                return false;
            }
        });
    }

    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush

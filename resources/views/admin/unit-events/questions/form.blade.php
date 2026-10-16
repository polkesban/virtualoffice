@extends('admin.layouts.admin')
@section('title', $question->exists ? 'Edit Pertanyaan Event' : 'Tambah Pertanyaan Event')
@section('page-title', $question->exists ? 'Edit Pertanyaan Event' : 'Tambah Pertanyaan Event')
@section('page-subtitle', 'Atur pre test, post test, atau evaluasi peserta')

@section('content')
<div class="p-6 max-w-4xl mx-auto" x-data="{ tipe: '{{ old('tipe_pertanyaan', $question->tipe_pertanyaan ?: 'multiple_choice') }}' }">
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
          action="{{ $question->exists ? route('admin.unit-events.questions.update', [$unitPage, $unitEvent, $question]) : route('admin.unit-events.questions.store', [$unitPage, $unitEvent]) }}"
          class="space-y-5">
        @csrf
        @if($question->exists)
            @method('PUT')
        @endif

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Fase <span class="text-red-500">*</span></label>
                    <select name="fase" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        @foreach(\App\Models\UnitEventQuestion::$faseLabels as $key => $label)
                            <option value="{{ $key }}" @selected(old('fase', $question->fase ?: 'pre_test') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tipe Pertanyaan <span class="text-red-500">*</span></label>
                    <select name="tipe_pertanyaan" x-model="tipe" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        @foreach(\App\Models\UnitEventQuestion::$tipeLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Urutan</label>
                    <input type="number" name="urutan" min="0" max="9999" value="{{ old('urutan', $question->urutan ?? 0) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Pertanyaan <span class="text-red-500">*</span></label>
                <textarea name="pertanyaan" rows="4" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('pertanyaan', $question->pertanyaan) }}</textarea>
            </div>

            <div x-show="tipe === 'multiple_choice'" class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Opsi Jawaban</label>
                <textarea name="opsi_teks" rows="5" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Satu opsi per baris">{{ old('opsi_teks', $question->opsi ? implode("\n", $question->opsi) : '') }}</textarea>
                <p class="text-xs text-gray-400">Minimal dua opsi. Untuk pre/post test, jawaban benar harus sama persis dengan salah satu opsi.</p>
            </div>

            <div x-show="tipe === 'multiple_choice'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Jawaban Benar</label>
                    <input type="text" name="jawaban_benar" value="{{ old('jawaban_benar', $question->jawaban_benar) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Bobot Nilai</label>
                    <input type="number" name="bobot" min="0" max="100" value="{{ old('bobot', $question->bobot ?? 1) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="wajib" value="1" @checked(old('wajib', $question->wajib ?? true)) class="rounded">
                    Pertanyaan wajib diisi
                </label>
                <p class="text-xs text-gray-400 md:text-right">Pertanyaan rating dan teks cocok untuk evaluasi narasumber, pelaksanaan, dan kepuasan peserta.</p>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.unit-events.questions.index', [$unitPage, $unitEvent]) }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Batal</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                {{ $question->exists ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}
            </button>
        </div>
    </form>
</div>
@endsection
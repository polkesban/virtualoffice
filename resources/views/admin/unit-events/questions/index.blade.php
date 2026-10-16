@extends('admin.layouts.admin')
@section('title', 'Pertanyaan Event Unit')
@section('page-title', 'Pertanyaan Event Unit')
@section('page-subtitle', 'Kelola pre test, post test, dan evaluasi acara')

@section('content')
<div class="p-6 max-w-6xl mx-auto">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    <div class="mb-5 flex items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Pertanyaan untuk {{ $unitEvent->judul }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Gunakan pilihan ganda untuk tes dan rating/teks untuk evaluasi.</p>
        </div>
        <a href="{{ route('admin.unit-events.questions.create', [$unitPage, $unitEvent]) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">+ Tambah Pertanyaan</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</div>
    @endif

    <div class="space-y-5">
        @foreach(\App\Models\UnitEventQuestion::$faseLabels as $faseKey => $faseLabel)
            @php $items = $questions->get($faseKey, collect()); @endphp
            <section class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-base font-extrabold text-gray-900 dark:text-white">{{ $faseLabel }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $items->count() }} pertanyaan</p>
                    </div>
                </div>

                @if($items->isEmpty())
                    <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-400 dark:bg-gray-800/60">Belum ada pertanyaan pada fase ini.</p>
                @else
                    <div class="space-y-3">
                        @foreach($items as $question)
                            <article class="rounded-2xl border border-gray-100 px-4 py-4 dark:border-gray-800">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $question->urutan }}. {{ $question->pertanyaan }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-bold">
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ \App\Models\UnitEventQuestion::$tipeLabels[$question->tipe_pertanyaan] ?? $question->tipe_pertanyaan }}</span>
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Bobot: {{ $question->bobot }}</span>
                                            <span class="rounded-full px-2.5 py-1 {{ $question->wajib ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $question->wajib ? 'Wajib' : 'Opsional' }}</span>
                                        </div>
                                        @if($question->opsi)
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Opsi: {{ implode(' • ', $question->opsi) }}</p>
                                        @endif
                                        @if($question->jawaban_benar)
                                            <p class="mt-1 text-xs text-brand-600 dark:text-brand-300">Jawaban benar: {{ $question->jawaban_benar }}</p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.unit-events.questions.edit', [$unitPage, $unitEvent, $question]) }}" class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200">Edit</a>
                                        <form method="POST" action="{{ route('admin.unit-events.questions.destroy', [$unitPage, $unitEvent, $question]) }}" onsubmit="return confirm('Hapus pertanyaan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-red-50 px-3 py-1 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
@endsection
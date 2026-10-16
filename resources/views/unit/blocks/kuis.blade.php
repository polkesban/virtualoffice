@php
    $cfg          = $section->konfigurasi ?? [];
    $pertanyaan   = $cfg['pertanyaan'] ?? [];
    $total        = count($pertanyaan);
    $pertanyaan   = \App\Support\PublicLocale::translateData($pertanyaan);
    $quizLabels   = \App\Support\PublicLocale::translateData([
        'questions' => 'soal',
        'nextQuestion' => 'Soal Berikutnya →',
        'seeResult' => 'Lihat Hasil',
        'excellent' => 'Luar biasa! Pemahaman Anda sangat baik.',
        'good' => 'Cukup baik! Terus tingkatkan.',
        'retry' => 'Pelajari lagi materinya ya!',
    ]);
@endphp
@if($total > 0)
<div
    x-data="{
        soal: @js($pertanyaan),
        labels: @js($quizLabels),
        current: 0,
        jawaban: [],
        selesai: false,
        pilih(oidx) {
            if (this.jawaban[this.current] !== undefined) return;
            this.jawaban[this.current] = oidx;
        },
        next() {
            if (this.current < this.soal.length - 1) this.current++;
            else this.selesai = true;
        },
        skor() { return this.jawaban.filter((j,i) => j === this.soal[i].benar).length; },
        ulang() { this.jawaban = []; this.current = 0; this.selesai = false; }
    }"
    class="space-y-5"
>
    {{-- Progress --}}
    <div class="flex items-center gap-3">
        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
            <div class="bg-brand-500 h-2 rounded-full transition-all duration-300"
                 :style="'width:' + Math.round(((selesai ? soal.length : current) / soal.length) * 100) + '%'"></div>
        </div>
        <span class="text-xs text-gray-500" x-text="(selesai ? soal.length : current) + ' / ' + soal.length + ' ' + labels.questions"></span>
    </div>

    {{-- Quiz card --}}
    <template x-if="!selesai">
        <div class="border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
            <p class="font-semibold text-gray-800 dark:text-gray-100" x-text="(current+1) + '. ' + soal[current].soal"></p>

            <div class="space-y-2">
                <template x-for="(opsi, oidx) in soal[current].opsi" :key="oidx">
                    <button type="button"
                            @click="pilih(oidx)"
                            :disabled="jawaban[current] !== undefined"
                            :class="{
                                'border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300': jawaban[current] === undefined,
                                'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700': jawaban[current] !== undefined && oidx === soal[current].benar,
                                'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600': jawaban[current] !== undefined && jawaban[current] === oidx && oidx !== soal[current].benar,
                                'border-gray-200 dark:border-gray-700 opacity-60': jawaban[current] !== undefined && jawaban[current] !== oidx && oidx !== soal[current].benar
                            }"
                            class="w-full text-left px-4 py-2.5 rounded-xl border text-sm transition-all">
                        <span class="font-bold mr-2" x-text="'ABCD'.charAt(oidx) + '.'"></span>
                        <span x-text="opsi"></span>
                    </button>
                </template>
            </div>

            <template x-if="jawaban[current] !== undefined && soal[current].penjelasan">
                <div class="text-sm text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl px-4 py-2.5">
                    💡 <span x-text="soal[current].penjelasan"></span>
                </div>
            </template>

            <template x-if="jawaban[current] !== undefined">
                <div class="flex justify-end">
                    <button @click="next()" type="button"
                            class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl transition-colors">
                        <span x-text="current < soal.length - 1 ? labels.nextQuestion : labels.seeResult"></span>
                    </button>
                </div>
            </template>
        </div>
    </template>

    {{-- Hasil --}}
    <template x-if="selesai">
        <div class="text-center py-8 space-y-3">
            <div class="text-5xl" x-text="skor() >= soal.length * 0.8 ? '🎉' : skor() >= soal.length * 0.5 ? '👍' : '📚'"></div>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Skor Anda: <span x-text="skor()"></span> / {{ $total }}
            </p>
            <p class="text-sm text-gray-500" x-text="skor() >= soal.length * 0.8 ? labels.excellent : skor() >= soal.length * 0.5 ? labels.good : labels.retry"></p>
            <button @click="ulang()" type="button"
                    class="mt-2 px-6 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl transition-colors">
                🔄 Ulangi Kuis
            </button>
        </div>
    </template>
</div>
@endif

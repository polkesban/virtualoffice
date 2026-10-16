@php
    $cfg          = $section->konfigurasi ?? [];
    $pertanyaan   = $cfg['pertanyaan'] ?? '';
    $opsi         = $cfg['opsi'] ?? [];
    $tampilHasil  = $cfg['tampil_hasil'] ?? true;
    $sectionId    = $section->id;
    $pollLabels   = \App\Support\PublicLocale::translateData([
        'votes' => 'suara',
    ]);
@endphp
@if(!empty($pertanyaan) && count($opsi) > 0)
<div
    x-data="{
        labels: @js($pollLabels),
        voted: false,
        pilihan: null,
        votes: [],
        total: 0,
        loading: false,
        init() {
            const key = 'poll_voted_{{ $sectionId }}';
            const stored = localStorage.getItem(key);
            if (stored !== null) {
                this.voted = true;
                this.pilihan = parseInt(stored);
            }
            @if($tampilHasil)
            this.fetchVotes();
            @endif
        },
        vote(idx) {
            if (this.voted || this.loading) return;
            this.loading = true;
            fetch('{{ route('block.vote', $section) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ pilihan: idx })
            }).then(r => r.json()).then(d => {
                this.voted = true; this.pilihan = idx; this.loading = false;
                localStorage.setItem('poll_voted_{{ $sectionId }}', idx);
                if (d.votes) { this.votes = d.votes; this.total = d.total; }
            }).catch(() => { this.loading = false; });
        },
        fetchVotes() {
            fetch('{{ route('block.vote', $section) }}?results=1')
                .then(r => r.json()).then(d => {
                    if (d.votes) { this.votes = d.votes; this.total = d.total; }
                });
        },
        persen(idx) {
            if (!this.total) return 0;
            return Math.round((this.votes[idx] ?? 0) / this.total * 100);
        }
    }"
    class="space-y-4 max-w-lg"
>
    <p class="font-semibold text-gray-800 dark:text-gray-100 text-base">{{ $pertanyaan }}</p>

    <div class="space-y-2">
        @foreach($opsi as $idx => $o)
        <button type="button"
                @click="vote({{ $idx }})"
                :disabled="voted || loading"
                class="relative w-full text-left px-4 py-3 rounded-xl border text-sm font-medium overflow-hidden transition-all"
                :class="voted && pilihan === {{ $idx }} ? 'border-brand-500 text-brand-700 dark:text-brand-300 bg-brand-50 dark:bg-brand-900/20' :
                        (voted ? 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 cursor-default' : 'border-gray-200 dark:border-gray-700 hover:border-brand-400 hover:bg-gray-50 dark:hover:bg-gray-800')">
            @if($tampilHasil)
            <div class="absolute inset-0 bg-brand-100 dark:bg-brand-900/30 rounded-xl transition-all"
                 :style="voted ? 'width:' + persen({{ $idx }}) + '%' : 'width:0'"
                 style="z-index:0"></div>
            @endif
            <span class="relative z-10 flex justify-between items-center">
                <span>{{ $o }}</span>
                @if($tampilHasil)
                <span x-show="voted" class="text-xs text-gray-500 ml-3" x-text="persen({{ $idx }}) + '%'"></span>
                @endif
            </span>
        </button>
        @endforeach
    </div>

    <p class="text-xs text-gray-400" x-show="voted">
        Terima kasih sudah berpartisipasi!
        @if($tampilHasil)<span x-text="' (' + total + ' ' + labels.votes + ')' "></span>@endif
    </p>
    <p class="text-xs text-gray-400" x-show="!voted && !loading">Pilih salah satu opsi di atas.</p>
    <p class="text-xs text-gray-400" x-show="loading">Mengirim suara...</p>
</div>
@endif

{{-- Block: Agenda & Kalender --}}
@php
    use App\Models\Agenda;
    $limit        = $cfg['limit'] ?? 5;
    $tampil_lampau= $cfg['tampil_lampau'] ?? false;
    $items = Agenda::where('publik', 1)
        ->when(!$tampil_lampau, fn($q) => $q->where('tanggal_mulai', '>=', now()->startOfDay()))
        ->orderBy('tanggal_mulai')
        ->limit($limit)
        ->get();
@endphp
<section class="py-14 bg-white dark:bg-gray-950">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                {{ $section->judul ?: 'Agenda & Kalender' }}
            </h2>
        </div>
        @if($items->isEmpty())
        <p class="text-gray-400 text-sm">Belum ada agenda yang dijadwalkan.</p>
        @else
        <div class="space-y-3">
            @foreach($items as $item)
            @php
              $warna = $item->warna ?: '#1d4ed8';
            @endphp
            <div class="flex gap-4 items-start p-4 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 hover:shadow-sm transition-shadow">
                <div class="shrink-0 w-14 text-center rounded-xl p-2 text-white" style="background-color:{{ $warna }}">
                    <p class="text-xs font-semibold uppercase">{{ $item->tanggal_mulai->translatedFormat('M') }}</p>
                    <p class="text-xl font-extrabold leading-none">{{ $item->tanggal_mulai->format('d') }}</p>
                </div>
                <div class="min-w-0">
                    <p class="font-bold text-gray-900 dark:text-white">{{ $item->judul }}</p>
                    @if($item->lokasi)<p class="text-xs text-gray-500 mt-0.5">📍 {{ $item->lokasi }}</p>@endif
                    @if($item->waktu_mulai)<p class="text-xs text-gray-500">🕐 {{ substr($item->waktu_mulai, 0, 5) }}{{ $item->waktu_selesai ? '–'.substr($item->waktu_selesai,0,5) : '' }} WIB</p>@endif
                    @if($item->deskripsi)<p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $item->deskripsi }}</p>@endif
                </div>
                @if($item->kategori)
                <span class="shrink-0 ml-auto px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $item->kategori }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

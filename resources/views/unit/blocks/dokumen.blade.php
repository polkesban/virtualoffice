{{-- Block: Dokumen Mutu --}}
@php
    use App\Models\DokumenMutu;
    $limit = $cfg['limit'] ?? 8;
    $jenis = $cfg['jenis'] ?? '';
    $items = DokumenMutu::where('aktif', 1)
        ->when($jenis, fn($q) => $q->where('jenis', $jenis))
        ->orderByDesc('tanggal_terbit')
        ->limit($limit)
        ->get();
    $colorMap = [
        'kebijakan'=>'bg-blue-50 text-blue-700','standar'=>'bg-indigo-50 text-indigo-700',
        'prosedur'=>'bg-green-50 text-green-700','formulir'=>'bg-gray-100 text-gray-700',
        'akreditasi'=>'bg-purple-50 text-purple-700','lap_audit'=>'bg-amber-50 text-amber-700',
        'lainnya'=>'bg-gray-100 text-gray-600',
    ];
@endphp
<section class="py-14 bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: 'Dokumen Mutu' }}
        </h2>
        @if($items->isEmpty())
        <p class="text-gray-400 text-sm">Belum ada dokumen mutu yang dipublikasikan.</p>
        @else
        <div class="space-y-2">
            @foreach($items as $item)
            <div class="flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-gray-950 border border-gray-100 dark:border-gray-800 hover:shadow-sm transition-shadow">
                <span class="text-2xl shrink-0">📄</span>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $item->judul }}</p>
                    <p class="text-xs text-gray-400">
                        @if($item->nomor_dok){{ $item->nomor_dok }} · @endif
                        @if($item->tanggal_terbit){{ $item->tanggal_terbit->format('d/m/Y') }} · @endif
                        @if($item->versi)v{{ $item->versi }} · @endif
                        {{ $item->masa_berlaku }}
                    </p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold {{ $colorMap[$item->jenis] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $item->jenis_label }}
                </span>
                @if($item->file_url)
                <a href="{{ $item->file_url }}" target="_blank"
                   class="shrink-0 px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">
                    ⬇ Unduh
                </a>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

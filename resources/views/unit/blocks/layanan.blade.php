{{-- Block: Katalog Layanan (TI / Lab / Bisnis) --}}
@php
    $sumber = $cfg['sumber'] ?? 'ti';
    $limit  = $cfg['limit'] ?? 10;
    $items  = match($sumber) {
        'lab'    => \App\Models\LayananLab::where('aktif', 1)->orderBy('urutan')->limit($limit)->get(),
        'bisnis' => \App\Models\LayananBisnis::where('aktif', 1)->orderBy('urutan')->limit($limit)->get(),
        default  => \App\Models\LayananTI::where('aktif', 1)->orderBy('urutan')->limit($limit)->get(),
    };
    $sourceLabels = ['ti' => 'Layanan IT', 'lab' => 'Layanan Lab', 'bisnis' => 'Layanan Bisnis'];
@endphp
<section class="py-14 bg-white dark:bg-gray-950">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: ($sourceLabels[$sumber] ?? 'Katalog Layanan') }}
        </h2>
        @if($items->isEmpty())
        <p class="text-gray-400 text-sm">Belum ada layanan yang dipublikasikan.</p>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($items as $item)
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-5 hover:shadow-md transition-shadow">
                <h3 class="font-bold text-gray-900 dark:text-white">{{ $item->nama }}</h3>
                {{-- Deskripsi (field nama berbeda per model) --}}
                @if(isset($item->deskripsi))
                <p class="text-sm text-gray-500 mt-2 line-clamp-3">{{ $item->deskripsi }}</p>
                @endif
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    {{-- LayananTI --}}
                    @if($sumber === 'ti')
                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-bold">{{ $item->kategori_label ?? $item->kategori }}</span>
                        @if($item->estimasi_waktu)<span class="text-gray-400">⏱ {{ $item->estimasi_waktu }}</span>@endif
                        @if($item->kontak)<span class="text-gray-400">📞 {{ $item->kontak }}</span>@endif
                    {{-- LayananLab --}}
                    @elseif($sumber === 'lab')
                        @if($item->tarif)<span class="font-bold text-brand-600">Rp {{ number_format($item->tarif,0,',','.') }}/{{ $item->satuan }}</span>@endif
                        @if($item->lama_pengerjaan)<span class="text-gray-400">⏱ {{ $item->lama_pengerjaan }}</span>@endif
                    {{-- LayananBisnis --}}
                    @elseif($sumber === 'bisnis')
                        @if($item->harga)<span class="font-bold text-brand-600">Rp {{ number_format($item->harga,0,',','.') }}</span>@endif
                        <a href="{{ route('layanan-bisnis.order', ['layanan' => $item->slug]) }}" class="ml-auto px-3 py-1 rounded-lg bg-brand-600 text-white hover:bg-brand-500 transition-colors">Pesan</a>
                        @if($item->link_pesan)
                        <a href="{{ $item->link_pesan }}" target="_blank" rel="noopener noreferrer" class="px-3 py-1 rounded-lg border border-brand-200 text-brand-700 hover:bg-brand-50 transition-colors">Info</a>
                        @endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

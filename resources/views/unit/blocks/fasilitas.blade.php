{{-- Block: Fasilitas Asrama --}}
@php
    use App\Models\FasilitasAsrama;
    $jenis = $cfg['jenis'] ?? '';
    $items = FasilitasAsrama::where('aktif', 1)
        ->when($jenis, fn($q) => $q->where('jenis', $jenis))
        ->orderBy('urutan')
        ->get();
    $jenisColor = ['putra'=>'bg-blue-50 text-blue-700','putri'=>'bg-pink-50 text-pink-700','mix'=>'bg-gray-100 text-gray-700'];
@endphp
<section class="py-14 bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: 'Fasilitas & Kamar Asrama' }}
        </h2>
        @if($items->isEmpty())
        <p class="text-gray-400 text-sm">Data kamar/fasilitas asrama belum tersedia.</p>
        @else
        {{-- Summary strip --}}
        @php
            $totalKap = $items->sum('kapasitas');
            $totalTerisi = $items->sum('terisi');
        @endphp
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-950 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 text-center">
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalKap }}</div>
                <div class="text-xs text-gray-400 mt-1">Total Kapasitas</div>
            </div>
            <div class="bg-white dark:bg-gray-950 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 text-center">
                <div class="text-2xl font-extrabold text-brand-600">{{ $totalTerisi }}</div>
                <div class="text-xs text-gray-400 mt-1">Terisi</div>
            </div>
            <div class="bg-white dark:bg-gray-950 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 text-center">
                <div class="text-2xl font-extrabold text-green-600">{{ max(0,$totalKap-$totalTerisi) }}</div>
                <div class="text-xs text-gray-400 mt-1">Tersedia</div>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($items as $item)
            <div class="bg-white dark:bg-gray-950 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-md transition-shadow">
                @if($item->foto)
                <img src="{{ \App\Support\MediaStorage::url($item->foto) }}" class="w-full h-36 object-cover" alt="{{ $item->nama }}">
                @endif
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $item->nama }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $jenisColor[$item->jenis] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ FasilitasAsrama::$jenisLabels[$item->jenis] ?? $item->jenis }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
                        <span>👥 {{ $item->terisi }}/{{ $item->kapasitas }} orang</span>
                        @if($item->lantai)<span>🏢 Lantai {{ $item->lantai }}</span>@endif
                        <span class="font-bold {{ $item->sisa_kamar > 0 ? 'text-green-600' : 'text-red-500' }}">
                            {{ $item->sisa_kamar > 0 ? $item->sisa_kamar.' tersedia' : 'Penuh' }}
                        </span>
                    </div>
                    @if($item->fasilitas)
                    <div class="flex flex-wrap gap-1 mb-2">
                        @foreach($item->fasilitas_array as $f)
                        <span class="px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-xs text-gray-600">{{ $f }}</span>
                        @endforeach
                    </div>
                    @endif
                    <p class="text-xs font-bold text-brand-600">{{ $item->harga_bulan_format }}<span class="font-normal text-gray-400">/bulan</span></p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

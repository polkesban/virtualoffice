{{-- Block: Statistik & Angka --}}
@php
  $items = $cfg['items'] ?? [];
  $colorMap = [
    'blue'   => ['bg'=>'bg-blue-50',   'text'=>'text-blue-700',   'ring'=>'ring-blue-200'],
    'green'  => ['bg'=>'bg-green-50',  'text'=>'text-green-700',  'ring'=>'ring-green-200'],
    'purple' => ['bg'=>'bg-purple-50', 'text'=>'text-purple-700', 'ring'=>'ring-purple-200'],
    'red'    => ['bg'=>'bg-red-50',    'text'=>'text-red-700',    'ring'=>'ring-red-200'],
    'orange' => ['bg'=>'bg-orange-50', 'text'=>'text-orange-700', 'ring'=>'ring-orange-200'],
    'teal'   => ['bg'=>'bg-teal-50',   'text'=>'text-teal-700',   'ring'=>'ring-teal-200'],
  ];
@endphp
<section class="py-14 bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if($section->judul)
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white text-center mb-8">{{ $section->judul }}</h2>
        @endif
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ min(count($items), 5) }} gap-6">
            @foreach($items as $item)
            @php $colors = $colorMap[$item['warna'] ?? 'blue'] ?? $colorMap['blue']; @endphp
            <div class="rounded-2xl {{ $colors['bg'] }} ring-1 {{ $colors['ring'] }} p-6 text-center">
                <div class="text-3xl mb-2">{{ $item['ikon'] ?? '📊' }}</div>
                <div class="text-3xl font-extrabold {{ $colors['text'] }}">{{ $item['nilai'] ?? '0' }}</div>
                <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">{{ $item['label'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

@php
    $cfg        = $section->konfigurasi ?? [];
    $header     = $cfg['header'] ?? [];
    $baris      = $cfg['baris'] ?? [];
    $bergaris   = $cfg['bergaris'] ?? true;
    $denganHeader = $cfg['dengan_header'] ?? true;
@endphp
@if(!empty($header) || !empty($baris))
<div class="overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        @if($denganHeader && !empty($header))
        <thead class="bg-brand-600 text-white">
            <tr>
                @foreach($header as $h)
                <th class="px-4 py-3 font-semibold">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        @endif
        <tbody>
            @foreach($baris as $ri => $row)
            <tr class="{{ $bergaris && $ri % 2 === 1 ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-900' }} border-t border-gray-100 dark:border-gray-800">
                @foreach($row as $cell)
                <td class="px-4 py-2.5">{{ $cell }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

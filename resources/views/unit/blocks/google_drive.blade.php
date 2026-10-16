@php
    $drive = \App\Support\GoogleDriveEmbed::fromUrl($cfg['url'] ?? null, $cfg['mode'] ?? 'list');
    $displayMode = in_array($cfg['display_mode'] ?? 'embed', ['embed', 'cards'], true) ? $cfg['display_mode'] : 'embed';
    $height = max(320, min(1200, (int) ($cfg['tinggi'] ?? 560)));
    $title = $section->judul ?: ($cfg['judul'] ?? 'Google Drive');
    $description = trim((string) ($cfg['deskripsi'] ?? ''));
    $items = collect($cfg['items'] ?? [])
        ->filter(fn ($item) => is_array($item) && (trim((string) ($item['nama'] ?? '')) !== '' || trim((string) ($item['url'] ?? '')) !== ''))
        ->values();
    $kindLabel = match ($drive['kind']) {
        'folder' => 'Folder Google Drive',
        'file' => 'File Google Drive',
        default => 'Tautan Google Drive',
    };
@endphp


<section class="py-14 bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400">{{ $kindLabel }}</p>
                <h2 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $title }}</h2>
                @if($description !== '')
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $description }}</p>
                @endif
            </div>
            @if($drive['open_url'])
            <a href="{{ $drive['open_url'] }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                Buka di Google Drive
            </a>
            @endif
        </div>

        @if($displayMode === 'cards' || ($items->isNotEmpty() && empty($drive['embed_url'])))
        @if($items->isEmpty())
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-100">
            Mode kartu aktif, tetapi belum ada item file/folder Google Drive yang diisi pada builder.
        </div>
        @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($items as $item)
            @php
                $itemDrive = \App\Support\GoogleDriveEmbed::fromUrl($item['url'] ?? null, 'list');
                $itemBadge = trim((string) ($item['tipe'] ?? ''));
                $itemKind = $itemDrive['kind'] === 'folder' ? 'Folder Drive' : 'File Drive';
                $itemIcon = match (true) {
                    str_contains(strtolower($itemBadge), 'sheet'), str_contains(strtolower($itemBadge), 'excel') => '📊',
                    str_contains(strtolower($itemBadge), 'pdf') => '📄',
                    str_contains(strtolower($itemBadge), 'slide'), str_contains(strtolower($itemBadge), 'ppt') => '🖥️',
                    str_contains(strtolower($itemBadge), 'form') => '📝',
                    $itemDrive['kind'] === 'folder' => '🗂️',
                    default => '📎',
                };
            @endphp
            <a href="{{ $itemDrive['open_url'] ?? ($item['url'] ?? '#') }}" target="_blank" rel="noopener noreferrer"
               class="group rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-950">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-2xl dark:bg-brand-900/30">{{ $itemIcon }}</div>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-gray-500 dark:bg-gray-800 dark:text-gray-300">{{ $itemBadge !== '' ? $itemBadge : $itemKind }}</span>
                </div>
                <h3 class="mt-4 text-base font-extrabold text-gray-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ $item['nama'] ?? 'Item Google Drive' }}</h3>
                @if(!empty($item['deskripsi']))
                <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $item['deskripsi'] }}</p>
                @endif
                <div class="mt-4 flex items-center justify-between gap-3 text-xs text-gray-400 dark:text-gray-500">
                    <span>{{ $item['ukuran'] ?? 'Google Drive' }}</span>
                    <span class="font-bold text-brand-600 dark:text-brand-400">Buka →</span>
                </div>
            </a>
            @endforeach
        </div>
        @endif
        @elseif($drive['embed_url'])
        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <iframe src="{{ $drive['embed_url'] }}"
                    title="{{ $title }}"
                    width="100%"
                    height="{{ $height }}"
                    class="w-full bg-white dark:bg-gray-950"
                    style="border:0;"
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin">
            </iframe>
        </div>
        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">Jika isi tidak muncul, pastikan file atau folder dibagikan untuk siapa saja yang memiliki tautan.</p>
        @else
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-100">
            URL Google Drive belum dapat dipreview otomatis. Anda masih bisa membukanya langsung lewat tombol di atas.
        </div>
        @endif
    </div>
</section>
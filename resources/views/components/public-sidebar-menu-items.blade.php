@foreach ($items as $item)
@php
    $hasChildren = $item->childrenRecursive->isNotEmpty();
    $itemActive = $hasActiveDescendant($item);
    $forceExpanded = false;
    $defaultOpen = $itemActive || $forceExpanded;
    $collectSearchText = function ($node) use (&$collectSearchText) {
        $segments = array_filter([$node->label, $node->href]);

        foreach ($node->childrenRecursive as $child) {
            $segments[] = $collectSearchText($child);
        }

        return trim(implode(' ', $segments));
    };
    $searchText = \App\Support\PublicLocale::translateData($collectSearchText($item));
    $depthClass = match ($depth ?? 0) {
        0 => 'text-sm',
        1 => 'text-sm',
        default => 'text-[13px]',
    };
    $paddingClass = match ($depth ?? 0) {
        0 => 'px-4 py-3',
        1 => 'px-3 py-3',
        default => 'px-3 py-2.5',
    };
@endphp
@if (! $hasChildren)
<a href="{{ $resolveHref($item->href) }}"
   target="{{ $item->target }}"
   @click="sidebarOpen = false"
   x-show="matchesSearch(@js($searchText))"
   class="flex items-center justify-between rounded-2xl border {{ $paddingClass }} {{ $depthClass }} font-semibold transition-colors {{ $itemActive ? 'border-brand-500 bg-brand-50/30 dark:bg-brand-950/40 text-brand-700 dark:text-brand-300' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-brand-300 dark:hover:border-brand-500 hover:text-brand-700 dark:hover:text-brand-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
    <span>{{ $item->icon ? $item->icon . ' ' : '' }}{{ $item->label }}</span>
    <svg class="h-4 w-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
    </svg>
</a>
@else
<section x-data="{ open: @js($defaultOpen) }"
         x-show="matchesSearch(@js($searchText))"
         class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 p-2">
    <div class="flex items-center gap-2">
        @if($item->href)
        <a href="{{ $resolveHref($item->href) }}"
           target="{{ $item->target }}"
           @click="sidebarOpen = false"
           class="flex flex-1 items-center justify-between rounded-2xl {{ ($depth ?? 0) === 0 ? 'px-3 py-3' : 'px-3 py-2.5' }} {{ $depthClass }} font-bold transition-colors {{ $itemActive ? 'text-brand-700 dark:text-brand-300' : 'text-slate-800 dark:text-slate-100 hover:text-brand-700 dark:hover:text-brand-300' }}">
            <span>{{ $item->icon ? $item->icon . ' ' : '' }}{{ $item->label }}</span>
        </a>
        @else
        <button type="button"
                @click="open = !open"
                class="flex flex-1 items-center justify-between rounded-2xl {{ ($depth ?? 0) === 0 ? 'px-3 py-3' : 'px-3 py-2.5' }} {{ $depthClass }} font-bold text-left transition-colors {{ $itemActive ? 'text-brand-700 dark:text-brand-300' : 'text-slate-800 dark:text-slate-100 hover:text-brand-700 dark:hover:text-brand-300' }}">
            <span>{{ $item->icon ? $item->icon . ' ' : '' }}{{ $item->label }}</span>
        </button>
        @endif
        @unless($forceExpanded)
        <button type="button"
                @click="open = !open"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-300 transition hover:border-brand-300 dark:hover:border-brand-500 hover:text-brand-700 dark:hover:text-brand-300"
                :aria-expanded="(open || searchQuery.trim() !== '').toString()"
                aria-label="Buka atau tutup submenu {{ $item->label }}">
            <svg class="h-4 w-4 transition-transform duration-150"
                 :class="{ 'rotate-90': open || searchQuery.trim() !== '' }"
                 fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
            </svg>
        </button>
        @endunless
    </div>
    @if($forceExpanded)
    <div class="space-y-1 pb-1 {{ ($depth ?? 0) > 0 ? 'pl-2' : '' }}">
        @include('components.public-sidebar-menu-items', [
            'items' => $item->childrenRecursive,
            'depth' => ($depth ?? 0) + 1,
            'resolveHref' => $resolveHref,
            'isActiveHref' => $isActiveHref,
            'hasActiveDescendant' => $hasActiveDescendant,
        ])
    </div>
    @else
    <div x-show="open || searchQuery.trim() !== ''" x-transition.opacity.duration.150ms class="space-y-1 pb-1 {{ ($depth ?? 0) > 0 ? 'pl-2' : '' }}">
        @include('components.public-sidebar-menu-items', [
            'items' => $item->childrenRecursive,
            'depth' => ($depth ?? 0) + 1,
            'resolveHref' => $resolveHref,
            'isActiveHref' => $isActiveHref,
            'hasActiveDescendant' => $hasActiveDescendant,
        ])
    </div>
    @endif
</section>
@endif
@endforeach

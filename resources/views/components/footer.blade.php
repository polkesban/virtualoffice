@php
    $footerAppearance = \App\Support\SiteAppearance::footer();
    $footerResolveHref = function (?string $href): string {
        if (! $href) {
            return '#';
        }

        return str_starts_with($href, 'http://') || str_starts_with($href, 'https://')
            ? $href
            : url($href);
    };
    $footerBottomText = str_replace('{year}', (string) now()->year, $footerAppearance['bottom_text']);
@endphp

<footer class="bg-brand-900 text-gray-300" role="contentinfo">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Kolom 1: Peta Lokasi --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-200">{{ $footerAppearance['map_title'] }}</h3>
                <div class="mt-4 overflow-hidden rounded-2xl border border-brand-800/80 bg-brand-950/50 aspect-video shadow-lg shadow-black/20">
                    <iframe
                        src="{{ $footerAppearance['map_embed_url'] }}"
                        loading="lazy"
                        class="h-full w-full border-0"
                        title="Lokasi kampus Poltekkes Bandung di Google Maps"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-gray-400">
                    {{ $footerAppearance['address_text'] }}
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-400">
                    <a href="{{ $footerAppearance['phone_href'] }}" class="hover:text-white transition-colors">{{ $footerAppearance['phone_text'] }}</a>
                    <span class="text-brand-700">•</span>
                    <a href="{{ $footerAppearance['email_href'] }}" class="hover:text-white transition-colors">{{ $footerAppearance['email_text'] }}</a>
                </div>
                <a href="{{ $footerAppearance['map_link_url'] }}"
                   target="_blank" rel="noopener noreferrer"
                   class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-200 hover:text-white transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    {{ $footerAppearance['map_link_label'] }}
                </a>
            </div>

            {{-- Kolom 2: Tautan Cepat --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-200">{{ $footerAppearance['quick_links_title'] }}</h3>
                <ul class="mt-4 space-y-2 text-sm" role="list">
                    @foreach ($footerAppearance['quick_links'] as $link)
                    <li>
                        <a href="{{ $footerResolveHref($link['href'] ?? '') }}"
                           @if(!empty($link['external'])) target="_blank" rel="noopener noreferrer" @endif
                           class="hover:text-white transition-colors flex items-center gap-1">
                            {{ $link['label'] }}
                            @if(!empty($link['external']))
                            <svg class="w-3 h-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            @endif
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Kolom 3: Tentang --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-200">{{ $footerAppearance['about_links_title'] }}</h3>
                <ul class="mt-4 space-y-2 text-sm" role="list">
                    @foreach ($footerAppearance['about_links'] as $link)
                    <li>
                        <a href="{{ $footerResolveHref($link['href'] ?? '') }}"
                           @if(!empty($link['external'])) target="_blank" rel="noopener noreferrer" @endif
                           class="hover:text-white transition-colors">
                            {{ $link['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Kolom 4: Newsletter --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-200">{{ $footerAppearance['newsletter_title'] }}</h3>
                <p class="mt-4 text-sm leading-relaxed text-gray-400">
                    {{ $footerAppearance['newsletter_body'] }}
                </p>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="mt-4 flex gap-2">
                    @csrf
                    <label for="newsletter-email" class="sr-only">Alamat email</label>
                    <input id="newsletter-email" type="email" name="email" required
                           placeholder="{{ $footerAppearance['newsletter_placeholder'] }}"
                           class="w-full rounded-lg border-0 bg-brand-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-400 focus:outline-none">
                    <button type="submit"
                            class="shrink-0 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-500 transition-colors">
                        {{ $footerAppearance['newsletter_button_label'] }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Complaint / Feedback CTA --}}
        <div class="mt-10 rounded-2xl border border-brand-700/60 bg-brand-800/50 px-5 py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="text-3xl shrink-0" aria-hidden="true">📋</span>
                <div>
                    <p class="font-bold text-white text-sm">Pengaduan / Kritik / Saran / Keluhan</p>
                    <p class="text-xs text-gray-400 mt-0.5 leading-relaxed max-w-md">
                        Sampaikan masukan Anda kepada unit atau program studi terkait. Setiap laporan akan ditindaklanjuti.
                    </p>
                </div>
            </div>
            <a href="{{ route('pengaduan.create') }}"
               class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 px-5 py-2.5 text-sm font-bold text-white transition-colors shadow-lg shadow-brand-900/30">
                Kirim Sekarang
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-6 border-t border-brand-800 pt-6 flex flex-col items-center justify-between gap-3 text-xs text-gray-500 sm:flex-row">
            <p>{{ $footerBottomText }}</p>
            <nav aria-label="Link kebijakan">
                <ul class="flex gap-4" role="list">
                    @foreach($footerAppearance['policy_links'] as $link)
                    <li>
                        <a href="{{ $footerResolveHref($link['href'] ?? '') }}"
                           @if(!empty($link['external'])) target="_blank" rel="noopener noreferrer" @endif
                           class="hover:text-white transition-colors">{{ $link['label'] }}</a>
                    </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>
</footer>

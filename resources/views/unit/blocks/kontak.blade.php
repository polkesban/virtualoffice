{{-- Block: Informasi Kontak --}}
@php
    $alamat  = $cfg['alamat'] ?? '';
    $telepon = $cfg['telepon'] ?? '';
    $email   = $cfg['email'] ?? '';
    $jam     = $cfg['jam_kerja'] ?? '';
    $maps    = $cfg['maps_embed'] ?? '';
@endphp
<section class="py-14 bg-white dark:bg-gray-950">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: 'Hubungi Kami' }}
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-4">
                @if($alamat)
                <div class="flex gap-3 items-start">
                    <span class="text-xl shrink-0 mt-0.5">📍</span>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-0.5">Alamat</p>
                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ $alamat }}</p>
                    </div>
                </div>
                @endif
                @if($telepon)
                <div class="flex gap-3 items-start">
                    <span class="text-xl shrink-0 mt-0.5">📞</span>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-0.5">Telepon / WhatsApp</p>
                        <a href="tel:{{ preg_replace('/\D/','',$telepon) }}" class="text-brand-600 hover:underline text-sm">{{ $telepon }}</a>
                    </div>
                </div>
                @endif
                @if($email)
                <div class="flex gap-3 items-start">
                    <span class="text-xl shrink-0 mt-0.5">✉️</span>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-0.5">Email</p>
                        <a href="mailto:{{ $email }}" class="text-brand-600 hover:underline text-sm">{{ $email }}</a>
                    </div>
                </div>
                @endif
                @if($jam)
                <div class="flex gap-3 items-start">
                    <span class="text-xl shrink-0 mt-0.5">🕐</span>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-0.5">Jam Operasional</p>
                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ $jam }}</p>
                    </div>
                </div>
                @endif
            </div>
            @if($maps)
            <div class="rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
                <iframe src="{{ $maps }}" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
            @endif
        </div>
    </div>
</section>

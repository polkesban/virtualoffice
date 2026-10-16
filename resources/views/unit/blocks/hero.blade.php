{{-- Block: Hero / Banner --}}
@php
  $bg   = $cfg['bg_color'] ?? $themeColor ?? '#1d4ed8';
  $bgImg = $cfg['bg_image'] ?? '';
  $opacity = ($cfg['overlay_opacity'] ?? 60) / 100;
  $aiImg = $cfg['gambar_url'] ?? '';

  $gradient = $cfg['bg_gradient'] ?? 'none';
  $gradientStyle = '';
  
  if ($gradient !== 'none') {
      $gradientStyle = match($gradient) {
          'blue-cyan' => 'linear-gradient(to right, #1e40af, #06b6d4)',
          'purple-pink' => 'linear-gradient(to right, #6b21a8, #ec4899)',
          'indigo-blue' => 'linear-gradient(to right, #312e81, #1d4ed8)',
          'gray-dark' => 'linear-gradient(to right, #111827, #374151)',
          'teal-emerald' => 'linear-gradient(to right, #115e59, #10b981)',
          'orange-red' => 'linear-gradient(to right, #c2410c, #dc2626)',
          default => '',
      };
  }

  $sectionStyle = '';
  if ($bgImg) {
      $sectionStyle = 'background-image:url('.htmlspecialchars($bgImg, ENT_QUOTES).');background-size:cover;background-position:center;';
  } else {
      $sectionStyle = $gradientStyle ? "background: {$gradientStyle};" : "background-color: {$bg};";
  }

  $overlayStyle = '';
  if ($bgImg) {
      $overlayStyle = $gradientStyle ? "background: {$gradientStyle}; opacity: {$opacity};" : "background-color: {$bg}; opacity: {$opacity};";
  }
@endphp
<section class="relative overflow-hidden" style="{{ $sectionStyle }}">
    @if($bgImg)
    <div class="absolute inset-0" style="{{ $overlayStyle }}"></div>
    @endif
    
    @if(isset($page))
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" style="padding-top: 1.5rem; padding-bottom: 0.5rem;">
        <nav class="flex text-sm text-white/70">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors">Beranda</a>
            <span class="mx-2">/</span>
            @if(isset($parentPage) && $parentPage)
            <a href="{{ $parentPage->publicUrl() }}" class="hover:text-white transition-colors">{{ $parentPage->judul }}</a>
            <span class="mx-2">/</span>
            @endif
            <span class="text-white">{{ $page->judul }}</span>
        </nav>
    </div>
    @endif

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" style="{{ isset($page) ? 'padding-top: 1rem;' : 'padding-top: 4rem;' }} padding-bottom: 5rem;">
        <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-10 lg:gap-16">
            
            {{-- Image --}}
            @if($aiImg)
            <div class="w-full">
                <figure class="overflow-hidden rounded-2xl border border-white/20 shadow-2xl" style="background-color: rgba(255,255,255,0.05);">
                    <img src="{{ Str::startsWith($aiImg, ['http://', 'https://']) ? $aiImg : \App\Support\MediaStorage::url($aiImg) }}" 
                         alt="{{ $cfg['gambar_alt'] ?? 'Ilustrasi hero' }}" 
                         class="h-auto w-full object-cover">
                </figure>
            </div>
            @endif

            {{-- Text Content --}}
            <div class="{{ $aiImg ? 'w-full text-left' : 'w-full text-center md:col-span-2 max-w-4xl mx-auto' }}">
                @if($section->judul)
                <p class="text-sm font-bold tracking-widest uppercase text-white/70 mb-3">{{ $section->judul }}</p>
                @endif
                @if(!empty($cfg['heading']))
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">{{ $cfg['heading'] }}</h2>
                @endif
                @if(!empty($cfg['sub']))
                <p class="mt-5 text-lg text-white/80 {{ $aiImg ? 'max-w-2xl' : 'max-w-2xl mx-auto' }}">{{ $cfg['sub'] }}</p>
                @endif
            </div>

        </div>
    </div>
</section>

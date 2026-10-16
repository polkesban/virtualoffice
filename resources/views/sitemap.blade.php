<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    {{-- Halaman Statis --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ url('/berita') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ url('/program-studi') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ url('/kontak') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.5</priority>
    </url>
    @foreach ($managedSectionUrls as $sectionUrl)
    <url>
        <loc>{{ url($sectionUrl) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    {{-- Program Studi --}}
    @foreach ($programStudiList as $prodi)
    <url>
        <loc>{{ url('/program-studi/' . $prodi->slug) }}</loc>
        <lastmod>{{ $prodi->updated_at->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    {{-- Berita --}}
    @foreach ($beritaList as $berita)
    <url>
        <loc>{{ url('/berita/' . $berita->slug) }}</loc>
        <lastmod>{{ $berita->updated_at->toAtomString() }}</lastmod>
        <changefreq>never</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach

    {{-- Halaman Unit --}}
    @foreach ($unitPageList as $unitPage)
    <url>
        <loc>{{ url($unitPage->publicPath()) }}</loc>
        <lastmod>{{ $unitPage->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

</urlset>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flyer Resmi - {{ $event->judul }} - Poltekkes Kemenkes Bandung</title>
    <meta name="description" content="Flyer dan informasi resmi kegiatan {{ $event->judul }} diselenggarakan oleh {{ $page->judul }} Poltekkes Kemenkes Bandung.">

    @php
        $ogImageUrl = $event->resolvedFlyerImageUrl();
        $ogTitle = 'Flyer: ' . $event->judul . ' - ' . $page->judul;
        $ogDescription = $event->ringkasan ?: ('Flyer dan informasi resmi kegiatan ' . $event->judul . ' diselenggarakan oleh ' . $page->judul . ' Poltekkes Kemenkes Bandung.');
    @endphp
    <!-- Open Graph / WhatsApp / Facebook / Telegram Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Poltekkes Kemenkes Bandung">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:secure_url" content="{{ $ogImageUrl }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $event->judul }}">
    <link rel="image_src" href="{{ $ogImageUrl }}">
    <meta itemprop="image" content="{{ $ogImageUrl }}">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('build/assets/app.css') }}" onerror="this.onerror=null;">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        }
                    }
                }
            }
        }
    </script>
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('js/jspdf.umd.min.js') }}"></script>

    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #090d16;
            color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        @media print {
            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            html, body {
                background: #090d16 !important;
                color: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            #event-flyer-printable {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 auto !important;
                border-radius: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: portrait;
                margin: 0mm;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col">

    {{-- Sticky Action Toolbar --}}
    <header class="no-print sticky top-0 z-50 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 px-4 py-3 sm:px-8 shadow-lg">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('unit.events.show', [$page->slug, $event->slug]) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-200 hover:bg-slate-700 transition">
                    ← Kembali ke Event
                </a>
                <div class="hidden md:block">
                    <p class="text-xs font-bold text-white truncate max-w-[280px]">{{ $event->judul }}</p>
                    <p class="text-[10px] text-teal-400 font-semibold">{{ $page->judul }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Direct Color PDF Download Button --}}
                <button id="btn-download-pdf" onclick="downloadFlyerPdf()" class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-teal-500 transition">
                    📄 Unduh PDF (Warna Asli)
                </button>

                {{-- Download PNG Button --}}
                <button id="btn-download-flyer" onclick="downloadFlyerImage()" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-emerald-500 transition">
                    ⬇️ Unduh Gambar (PNG)
                </button>

                {{-- Print / Browser PDF Button --}}
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-200 border border-slate-700 shadow hover:bg-slate-700 transition">
                    🖨️ Cetak / Print
                </button>

                {{-- WhatsApp Share --}}
                @php
                    $eventPublicUrl = route('unit.events.show', [$page->slug, $event->slug]);
                    if (filled($event->deskripsi)) {
                        $waText = $eventPublicUrl . "\n\n" . trim((string) $event->deskripsi) . "\n\n🔗 Akses Informasi & Presensi: " . $eventPublicUrl;
                    } else {
                        $waText = $eventPublicUrl . "\n\n"
                            . "Halo! Ikuti kegiatan resmi " . $event->judul . " oleh " . $page->judul . " Poltekkes Kemenkes Bandung.\n\n"
                            . "📅 Jadwal: " . ($event->mulai_at?->translatedFormat('l, d F Y') ?? '-') . "\n"
                            . "📍 Lokasi: " . $event->resolvedLocationName() . "\n"
                            . "🔗 Informasi & Presensi: " . $eventPublicUrl;
                    }
                    $waUrl = 'https://api.whatsapp.com/send?text=' . rawurlencode($waText);
                @endphp
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-[#25D366] px-3.5 py-2 text-xs font-bold text-slate-950 shadow-md hover:bg-[#20bd5a] transition">
                    💬 Bagikan WA
                </a>

                {{-- Copy Link --}}
                <button onclick="copyFlyerLink(this)" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-300 hover:bg-slate-700 transition">
                    📋 Salin Link
                </button>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 py-8 px-3 sm:px-6 flex flex-col items-center justify-center">
        <div class="w-full max-w-[780px]">
            <x-event-flyer 
                :event="$event" 
                :speakers="$speakers" 
                :theme="$theme" 
                :benefits="$benefits" 
                :qrCodeDataUri="$qrCodeDataUri"
                flyerId="event-flyer-printable"
            />
        </div>
    </main>

    {{-- Footer Bar --}}
    <footer class="no-print py-4 text-center text-xs text-slate-500 border-t border-slate-900">
        <p>© {{ date('Y') }} Politeknik Kesehatan Kementerian Kesehatan Bandung. Seluruh hak cipta dilindungi.</p>
    </footer>

    {{-- Client Scripts for Download & Copy --}}
    <script>
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = () => resolve(true);
                s.onerror = () => reject(new Error('Failed to load ' + src));
                document.head.appendChild(s);
            });
        }

        async function ensureHtml2Canvas() {
            if (typeof html2canvas !== 'undefined') return window.html2canvas;
            try {
                await loadScript("{{ asset('js/html2canvas.min.js') }}");
                if (typeof html2canvas !== 'undefined') return window.html2canvas;
            } catch (e) {
                console.warn('Local html2canvas load failed, trying cdn fallback', e);
                try {
                    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
                } catch (err) {
                    console.error('All html2canvas sources failed', err);
                }
            }
            return typeof html2canvas !== 'undefined' ? window.html2canvas : null;
        }

        async function ensureJsPdf() {
            if (window.jspdf && window.jspdf.jsPDF) return window.jspdf.jsPDF;
            try {
                await loadScript("{{ asset('js/jspdf.umd.min.js') }}");
                if (window.jspdf && window.jspdf.jsPDF) return window.jspdf.jsPDF;
            } catch (e) {
                console.warn('Local jspdf load failed, trying cdn fallback', e);
                try {
                    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
                } catch (err) {
                    console.error('All jspdf sources failed', err);
                }
            }
            return (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : null;
        }

        async function downloadFlyerPdf() {
            const btn = document.getElementById('btn-download-pdf');
            const target = document.getElementById('event-flyer-printable');
            if (!target) return;

            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Menyiapkan PDF...';
            btn.disabled = true;

            try {
                const h2c = await ensureHtml2Canvas();
                if (!h2c) {
                    window.print();
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }

                const canvas = await h2c(target, {
                    scale: 2.5,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#090d16',
                    logging: false,
                });

                const imgData = canvas.toDataURL('image/jpeg', 0.95);
                const jsPDFConstructor = await ensureJsPdf();

                if (jsPDFConstructor) {
                    const pdf = new jsPDFConstructor({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'a4',
                    });

                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = pdf.internal.pageSize.getHeight();
                    const imgWidth = canvas.width;
                    const imgHeight = canvas.height;
                    const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
                    const imgX = (pdfWidth - imgWidth * ratio) / 2;
                    const imgY = 0;

                    pdf.addImage(imgData, 'JPEG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);
                    const slug = "{{ \Illuminate\Support\Str::slug($event->judul) ?: 'event-polkesban' }}";
                    pdf.save(`flyer-${slug}.pdf`);

                    btn.innerHTML = '✓ PDF Diunduh!';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }, 2500);
                } else {
                    window.print();
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (err) {
                console.error('Error generating PDF:', err);
                window.print();
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function downloadFlyerImage() {
            const btn = document.getElementById('btn-download-flyer');
            const target = document.getElementById('event-flyer-printable');
            if (!target) return;

            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Menyiapkan Gambar...';
            btn.disabled = true;

            try {
                const h2c = await ensureHtml2Canvas();
                if (!h2c) {
                    alert('Library gambar sedang tidak dapat dimuat di browser. Anda dapat menggunakan tombol Cetak / PDF sebagai alternatif.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }

                const canvas = await h2c(target, {
                    scale: 2.5, // High-resolution output
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#090d16',
                    logging: false,
                });

                const link = document.createElement('a');
                const slug = "{{ \Illuminate\Support\Str::slug($event->judul) ?: 'event-polkesban' }}";
                link.download = `flyer-${slug}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();

                btn.innerHTML = '✓ Berhasil Diunduh';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2500);
            } catch (err) {
                console.error('Error generating flyer image:', err);
                alert('Gagal membuat file gambar flyer. Anda dapat menggunakan tombol Unduh PDF sebagai alternatif.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function copyFlyerLink(button) {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                const original = button.innerHTML;
                button.innerHTML = '✓ Link Disalin!';
                button.classList.add('bg-teal-600', 'text-white');
                setTimeout(() => {
                    button.innerHTML = original;
                    button.classList.remove('bg-teal-600', 'text-white');
                }, 2000);
            }).catch(() => {
                prompt('Salin tautan flyer berikut:', url);
            });
        }
    </script>
</body>
</html>

<?php

namespace App\Services\Pbm;

class ClassCoverDiagramService
{
    /**
     * Generate an authentic, razor-sharp technical vector diagram (SVG)
     * tailored to health science subjects at Poltekkes Kemenkes Bandung.
     *
     * @return array{svg: string, label: string, key: string}
     */
    public static function generate(string $className, string $courseName = '', string $description = '', string $prodi = ''): array
    {
        $domain = self::detectDomain($className . ' ' . $courseName . ' ' . $description . ' ' . $prodi);
        $displayTitle = !empty($courseName) ? "{$courseName} : {$className}" : $className;

        return self::buildDiagram($domain, $displayTitle);
    }

    /**
     * Build diagram by explicit preset key.
     */
    public static function buildByKey(string $key, string $title): array
    {
        return self::buildDiagram($key, $title);
    }

    /**
     * Available presets list for the banner gallery.
     */
    public static function getPresets(): array
    {
        return [
            [
                'key' => 'epidemiology',
                'category' => 'Epidemiologi & Kesmas',
                'title' => 'Kurva Epidemiologi & Transmisi',
                'desc' => 'Histogram insidensi, kurva puncak wabah, angka reproduksi R0, dan klaster transmisi penyakit.',
                'badge' => 'Surveilans & Data',
            ],
            [
                'key' => 'nursing',
                'category' => 'Keperawatan',
                'title' => 'Monitor EKG & Tanda Vital Pasien',
                'desc' => 'Rhythm strip EKG Lead II, monitor HR, saturasi SpO2, tekanan darah NIBP, dan laju respirasi.',
                'badge' => 'Asuhan Klinis',
            ],
            [
                'key' => 'pharmacy',
                'category' => 'Farmasi',
                'title' => 'Kurva Farmakokinetik & Molekul Obat',
                'desc' => 'Grafik konsentrasi obat vs waktu, jendela terapi (MEC & MSC), Cmax, Tmax, dan struktur molekul.',
                'badge' => 'Farmakologi',
            ],
            [
                'key' => 'laboratory',
                'category' => 'Lab Medis / TLM',
                'title' => 'Elektroforesis Gel & DNA Helix',
                'desc' => 'Pita elektroforesis agarosa, visualisasi heliks ganda DNA, dan spektrofotometri absorbansi.',
                'badge' => 'Analisis Lab',
            ],
            [
                'key' => 'dental',
                'category' => 'Kesehatan Gigi',
                'title' => 'Lengkung Gigi & Anatomi Odontogram',
                'desc' => 'Diagram lengkung gigi rahang atas & bawah, sistem notasi kuadran, dan indeks kesehatan oral.',
                'badge' => 'Terapi Gigi',
            ],
            [
                'key' => 'nutrition',
                'category' => 'Gizi & Dietetika',
                'title' => 'Distribusi Makronutrisi & Energi',
                'desc' => 'Donut chart kebutuhan kalori harian, rasio karbohidrat, protein, lemak, dan profil mikronutrien.',
                'badge' => 'Dietetika',
            ],
            [
                'key' => 'maternal',
                'category' => 'Kebidanan',
                'title' => 'Partograf & Pemantauan Denyut Janin',
                'desc' => 'Kurva dilatasi serviks (Action & Alert line), telemetri DJJ, dan interval his kontraksi.',
                'badge' => 'Maternal Neonatal',
            ],
            [
                'key' => 'sanitation',
                'category' => 'Sanitasi Lingkungan',
                'title' => 'Baku Mutu Air & Siklus Filtrasi',
                'desc' => 'Indeks parameter kualitas air (pH, TDS, DO, BOD) dan skema tahapan penjernihan ekologis.',
                'badge' => 'Kesehatan Lingkungan',
            ],
            [
                'key' => 'general',
                'category' => 'Medis Umum',
                'title' => 'Sistem Fisiologi & Bio-Sinyal Klinis',
                'desc' => 'Gelombang denyut hemodinamik, alur triase klinis, dan metrik diagnosis kesehatan.',
                'badge' => 'Sains Medis',
            ],
        ];
    }

    private static function detectDomain(string $text): string
    {
        $t = strtolower($text);

        if (str_contains($t, 'epidemiolog') || str_contains($t, 'surveilans') || str_contains($t, 'wabah') || str_contains($t, 'kesmas')) {
            return 'epidemiology';
        }
        if (str_contains($t, 'perawat') || str_contains($t, 'keperawatan') || str_contains($t, 'ners') || str_contains($t, 'gawat darurat') || str_contains($t, 'kardio')) {
            return 'nursing';
        }
        if (str_contains($t, 'farmas') || str_contains($t, 'obat') || str_contains($t, 'farmako') || str_contains($t, 'resep') || str_contains($t, 'kimia')) {
            return 'pharmacy';
        }
        if (str_contains($t, 'tlm') || str_contains($t, 'analis') || str_contains($t, 'laborator') || str_contains($t, 'hematolog') || str_contains($t, 'patologi') || str_contains($t, 'mikrobio')) {
            return 'laboratory';
        }
        if (str_contains($t, 'gigi') || str_contains($t, 'dental') || str_contains($t, 'mulut') || str_contains($t, 'periodon')) {
            return 'dental';
        }
        if (str_contains($t, 'gizi') || str_contains($t, 'nutrisi') || str_contains($t, 'diet') || str_contains($t, 'pangan')) {
            return 'nutrition';
        }
        if (str_contains($t, 'bidan') || str_contains($t, 'kebidanan') || str_contains($t, 'maternal') || str_contains($t, 'neonatus') || str_contains($t, 'persalinan') || str_contains($t, 'nifas')) {
            return 'maternal';
        }
        if (str_contains($t, 'sanitasi') || str_contains($t, 'lingkungan') || str_contains($t, 'limbah') || str_contains($t, 'vektor') || str_contains($t, 'air')) {
            return 'sanitation';
        }

        return 'general';
    }

    private static function buildDiagram(string $domain, string $title): array
    {
        switch ($domain) {
            case 'epidemiology':
                return [
                    'key' => 'epidemiology',
                    'label' => 'Diagram Kurva Epidemiologi & Analisis Kasus Transmisi',
                    'svg' => self::renderEpidemiology($title),
                ];
            case 'nursing':
                return [
                    'key' => 'nursing',
                    'label' => 'Diagram Monitor Telemetri EKG & Tanda Vital Pasien',
                    'svg' => self::renderNursing($title),
                ];
            case 'pharmacy':
                return [
                    'key' => 'pharmacy',
                    'label' => 'Diagram Farmakokinetik (Cmax, Tmax, MEC) & Struktur Kimia',
                    'svg' => self::renderPharmacy($title),
                ];
            case 'laboratory':
                return [
                    'key' => 'laboratory',
                    'label' => 'Diagram Elektroforesis Gel Agarosa & Struktur DNA Helix',
                    'svg' => self::renderLaboratory($title),
                ];
            case 'dental':
                return [
                    'key' => 'dental',
                    'label' => 'Diagram Lengkung Geligi & Sistem Kuadran Odontogram',
                    'svg' => self::renderDental($title),
                ];
            case 'nutrition':
                return [
                    'key' => 'nutrition',
                    'label' => 'Diagram Distribusi Makronutrisi & Total Pengeluaran Energi',
                    'svg' => self::renderNutrition($title),
                ];
            case 'maternal':
                return [
                    'key' => 'maternal',
                    'label' => 'Diagram Partograf Dilatasi Serviks & Telemetri DJJ',
                    'svg' => self::renderMaternal($title),
                ];
            case 'sanitation':
                return [
                    'key' => 'sanitation',
                    'label' => 'Diagram Parameter Mutu Kualitas Air & Penjernihan Ekologis',
                    'svg' => self::renderSanitation($title),
                ];
            default:
                return [
                    'key' => 'general',
                    'label' => 'Diagram Fisiologi Klinis & Bio-Sinyal Kesehatan',
                    'svg' => self::renderGeneral($title),
                ];
        }
    }

    private static function escapeTitle(string $title, string $fallback): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $title));
        if (mb_strlen($clean) > 42) {
            $clean = mb_substr($clean, 0, 39) . '...';
        }
        return htmlspecialchars(!empty($clean) ? strtoupper($clean) : $fallback);
    }

    private static function renderEpidemiology(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'EPIDEMIOLOGI & SURVEILANS KESEHATAN');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgEpi" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#080d1a"/>
      <stop offset="55%" stop-color="#0e172a"/>
      <stop offset="100%" stop-color="#062d2a"/>
    </linearGradient>
    <linearGradient id="curveFillEpi" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.5"/>
      <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.0"/>
    </linearGradient>
    <linearGradient id="curveLineEpi" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#3b82f6"/>
      <stop offset="35%" stop-color="#06b6d4"/>
      <stop offset="100%" stop-color="#10b981"/>
    </linearGradient>
    <linearGradient id="barGradEpi" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.85"/>
      <stop offset="100%" stop-color="#0284c7" stop-opacity="0.2"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgEpi)"/>

  <!-- Technical Grid lines -->
  <g stroke="#334155" stroke-width="1" opacity="0.35">
    <line x1="90" y1="120" x2="1110" y2="120" stroke-dasharray="4 4"/>
    <line x1="90" y1="210" x2="1110" y2="210" stroke-dasharray="4 4"/>
    <line x1="90" y1="300" x2="1110" y2="300" stroke-dasharray="4 4"/>
    <line x1="90" y1="390" x2="1110" y2="390" stroke-dasharray="4 4"/>
    <line x1="90" y1="480" x2="1110" y2="480" stroke-dasharray="4 4"/>
    <line x1="280" y1="90" x2="280" y2="500" stroke-dasharray="4 4"/>
    <line x1="480" y1="90" x2="480" y2="500" stroke-dasharray="4 4"/>
    <line x1="680" y1="90" x2="680" y2="500" stroke-dasharray="4 4"/>
    <line x1="880" y1="90" x2="880" y2="500" stroke-dasharray="4 4"/>
  </g>

  <!-- Incidence Histogram Bars -->
  <rect x="120" y="445" width="26" height="35" rx="4" fill="url(#barGradEpi)"/>
  <rect x="158" y="415" width="26" height="65" rx="4" fill="url(#barGradEpi)"/>
  <rect x="196" y="375" width="26" height="105" rx="4" fill="url(#barGradEpi)"/>
  <rect x="234" y="320" width="26" height="160" rx="4" fill="url(#barGradEpi)"/>
  <rect x="272" y="260" width="26" height="220" rx="4" fill="url(#barGradEpi)"/>
  <rect x="310" y="210" width="26" height="270" rx="4" fill="url(#barGradEpi)"/>
  <rect x="348" y="175" width="26" height="305" rx="4" fill="url(#barGradEpi)"/>
  <rect x="386" y="155" width="26" height="325" rx="4" fill="url(#barGradEpi)"/>
  <rect x="424" y="185" width="26" height="295" rx="4" fill="url(#barGradEpi)"/>
  <rect x="462" y="235" width="26" height="245" rx="4" fill="url(#barGradEpi)"/>
  <rect x="500" y="295" width="26" height="185" rx="4" fill="url(#barGradEpi)"/>
  <rect x="538" y="360" width="26" height="120" rx="4" fill="url(#barGradEpi)"/>
  <rect x="576" y="405" width="26" height="75" rx="4" fill="url(#barGradEpi)"/>
  <rect x="614" y="435" width="26" height="45" rx="4" fill="url(#barGradEpi)"/>

  <!-- Epidemic Curve Area -->
  <path d="M 90 480 C 220 460, 300 200, 400 145 C 500 115, 580 360, 700 435 C 780 465, 920 478, 1110 480 L 1110 480 L 90 480 Z" fill="url(#curveFillEpi)"/>
  <path d="M 90 480 C 220 460, 300 200, 400 145 C 500 115, 580 360, 700 435 C 780 465, 920 478, 1110 480" stroke="url(#curveLineEpi)" stroke-width="4.5" stroke-linecap="round"/>

  <!-- Peak Indicator -->
  <circle cx="400" cy="145" r="8" fill="#06b6d4" stroke="#ffffff" stroke-width="3"/>
  <line x1="400" y1="145" x2="400" y2="480" stroke="#06b6d4" stroke-width="2" stroke-dasharray="4 4"/>

  <!-- Secondary Attack Projection -->
  <path d="M 400 145 C 480 175, 570 250, 730 315 C 860 365, 980 410, 1110 430" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="6 6" fill="none"/>
  <circle cx="730" cy="315" r="6" fill="#f43f5e" stroke="#ffffff" stroke-width="2"/>

  <!-- Network Cluster Graphic (Right Side) -->
  <g transform="translate(760, 115)">
    <line x1="120" y1="70" x2="190" y2="45" stroke="#38bdf8" stroke-width="1.5" opacity="0.6"/>
    <line x1="190" y1="45" x2="250" y2="95" stroke="#34d399" stroke-width="1.5" opacity="0.6"/>
    <line x1="190" y1="45" x2="160" y2="135" stroke="#38bdf8" stroke-width="1.5" opacity="0.6"/>
    <line x1="160" y1="135" x2="250" y2="95" stroke="#818cf8" stroke-width="1.5" opacity="0.6"/>
    <line x1="160" y1="135" x2="230" y2="185" stroke="#fbbf24" stroke-width="1.5" opacity="0.6"/>
    <line x1="160" y1="135" x2="105" y2="175" stroke="#06b6d4" stroke-width="1.5" opacity="0.6"/>
    <line x1="120" y1="70" x2="105" y2="175" stroke="#38bdf8" stroke-width="1.5" opacity="0.6"/>

    <circle cx="120" cy="70" r="6" fill="#38bdf8"/>
    <circle cx="190" cy="45" r="8" fill="#34d399" stroke="#ffffff" stroke-width="2"/>
    <circle cx="250" cy="95" r="6" fill="#818cf8"/>
    <circle cx="160" cy="135" r="9" fill="#f43f5e" stroke="#ffffff" stroke-width="2"/>
    <circle cx="230" cy="185" r="6" fill="#fbbf24"/>
    <circle cx="105" cy="175" r="7" fill="#06b6d4"/>
    <text x="180" y="22" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700" text-anchor="middle" letter-spacing="1">TRANSMISSION CLUSTERS</text>
  </g>

  <!-- Metric Badges Bottom Right -->
  <g transform="translate(730, 360)">
    <rect width="170" height="52" rx="10" fill="#1e293b" stroke="#334155" stroke-width="1.5"/>
    <text x="16" y="24" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700" letter-spacing="1">BASIC REPRODUCTION</text>
    <text x="16" y="44" fill="#10b981" font-family="system-ui, sans-serif" font-size="16" font-weight="800">R₀ = 1.84 &lt; 2.0</text>
  </g>
  <g transform="translate(915, 360)">
    <rect width="185" height="52" rx="10" fill="#1e293b" stroke="#334155" stroke-width="1.5"/>
    <text x="16" y="24" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700" letter-spacing="1">ATTACK RATE</text>
    <text x="16" y="44" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="16" font-weight="800">14.2 / 1,000 Pop</text>
  </g>

  <!-- Axis & Labels -->
  <rect x="90" y="495" width="1020" height="2" fill="#475569"/>
  <text x="90" y="525" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="12" font-weight="600">T0 : Waktu Onset Kasus Pertama</text>
  <text x="400" y="525" fill="#06b6d4" font-family="system-ui, sans-serif" font-size="13" font-weight="800" text-anchor="middle">Puncak Epidemi (Peak Incidence)</text>
  <text x="1110" y="525" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="12" font-weight="600" text-anchor="end">Tn : Periode Surveilans</text>

  <!-- Top Title Bar -->
  <g transform="translate(90, 42)">
    <rect width="520" height="40" rx="20" fill="#1e293b" stroke="#06b6d4" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#10b981"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderNursing(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'ASUHAN KEPERAWATAN & GAWAT DARURAT');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgNur" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#04121d"/>
      <stop offset="50%" stop-color="#0a2336"/>
      <stop offset="100%" stop-color="#031e24"/>
    </linearGradient>
    <linearGradient id="ekgGrad" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#10b981"/>
      <stop offset="50%" stop-color="#06b6d4"/>
      <stop offset="100%" stop-color="#34d399"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgNur)"/>

  <!-- Clinical Telemetry Grid -->
  <g stroke="#0e3a53" stroke-width="1" opacity="0.55">
    <line x1="80" y1="120" x2="1120" y2="120"/>
    <line x1="80" y1="200" x2="1120" y2="200"/>
    <line x1="80" y1="280" x2="1120" y2="280"/>
    <line x1="80" y1="360" x2="1120" y2="360"/>
    <line x1="80" y1="440" x2="1120" y2="440"/>
    <line x1="200" y1="80" x2="200" y2="460"/>
    <line x1="360" y1="80" x2="360" y2="460"/>
    <line x1="520" y1="80" x2="520" y2="460"/>
    <line x1="680" y1="80" x2="680" y2="460"/>
    <line x1="840" y1="80" x2="840" y2="460"/>
    <line x1="1000" y1="80" x2="1000" y2="460"/>
  </g>

  <!-- ECG Waveform (Lead II Rhythm Strip) -->
  <path d="M 80 280 L 160 280 Q 180 268 190 280 L 205 280 L 215 295 L 230 140 L 248 335 L 258 280 L 280 280 Q 310 240 330 280 L 440 280 Q 460 268 470 280 L 485 280 L 495 295 L 510 140 L 528 335 L 538 280 L 560 280 Q 590 240 610 280 L 720 280 Q 740 268 750 280 L 765 280 L 775 295 L 790 140 L 808 335 L 818 280 L 840 280 Q 870 240 890 280 L 1000 280 Q 1020 268 1030 280 L 1045 280 L 1055 295 L 1070 140 L 1088 335 L 1098 280 L 1120 280" stroke="url(#ekgGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>

  <!-- Waveform annotation label -->
  <text x="90" y="150" fill="#10b981" font-family="system-ui, sans-serif" font-size="12" font-weight="700" letter-spacing="1">LEAD II • ECG NORMAL SINUS RHYTHM (NSR)</text>

  <!-- Vital Signs Panels (Bottom) -->
  <g transform="translate(80, 470)">
    <rect width="240" height="95" rx="12" fill="#082236" stroke="#124a6e" stroke-width="1.5"/>
    <text x="18" y="32" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="11" font-weight="700">HEART RATE (HR)</text>
    <text x="18" y="74" fill="#10b981" font-family="system-ui, sans-serif" font-size="36" font-weight="900">76 <tspan font-size="16" font-weight="500" fill="#6ee7b7">bpm</tspan></text>
  </g>
  <g transform="translate(340, 470)">
    <rect width="240" height="95" rx="12" fill="#082236" stroke="#124a6e" stroke-width="1.5"/>
    <text x="18" y="32" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="11" font-weight="700">OXYGEN SAT (SpO2)</text>
    <text x="18" y="74" fill="#06b6d4" font-family="system-ui, sans-serif" font-size="36" font-weight="900">99 <tspan font-size="16" font-weight="500" fill="#67e8f9">%</tspan></text>
  </g>
  <g transform="translate(600, 470)">
    <rect width="240" height="95" rx="12" fill="#082236" stroke="#124a6e" stroke-width="1.5"/>
    <text x="18" y="32" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="11" font-weight="700">BLOOD PRESSURE (NIBP)</text>
    <text x="18" y="74" fill="#f59e0b" font-family="system-ui, sans-serif" font-size="36" font-weight="900">120/80 <tspan font-size="16" font-weight="500" fill="#fcd34d">mmHg</tspan></text>
  </g>
  <g transform="translate(860, 470)">
    <rect width="240" height="95" rx="12" fill="#082236" stroke="#124a6e" stroke-width="1.5"/>
    <text x="18" y="32" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="11" font-weight="700">RESPIRATORY RATE (RR)</text>
    <text x="18" y="74" fill="#a78bfa" font-family="system-ui, sans-serif" font-size="36" font-weight="900">18 <tspan font-size="16" font-weight="500" fill="#c4b5fd">x/min</tspan></text>
  </g>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#0a2336" stroke="#10b981" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#10b981"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderPharmacy(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'FARMASETIKA & FARMAKOLOGI KLINIS');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgPha" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#120826"/>
      <stop offset="50%" stop-color="#1c103b"/>
      <stop offset="100%" stop-color="#08182f"/>
    </linearGradient>
    <linearGradient id="curvePha" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#c084fc" stop-opacity="0.45"/>
      <stop offset="100%" stop-color="#c084fc" stop-opacity="0.0"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgPha)"/>

  <!-- Molecular Structure (Left side) -->
  <g stroke="#a855f7" stroke-width="2.5" opacity="0.45" fill="none" transform="translate(60, 40)">
    <polygon points="120,220 160,195 200,220 200,270 160,295 120,270"/>
    <polygon points="200,220 240,195 280,220 280,270 240,295 200,270"/>
    <polygon points="160,295 200,270 200,220 240,295 240,345 200,370"/>
    <circle cx="120" cy="220" r="5" fill="#38bdf8"/>
    <circle cx="280" cy="220" r="5" fill="#f43f5e"/>
    <circle cx="240" cy="345" r="5" fill="#34d399"/>
    <text x="140" y="405" fill="#a855f7" font-family="system-ui, sans-serif" font-size="11" font-weight="700" letter-spacing="1">BIOAKTIF MOLEKULER</text>
  </g>

  <!-- Pharmacokinetic Curve (Concentration vs Time) -->
  <path d="M 420 460 C 470 450, 510 180, 560 160 C 640 140, 740 280, 880 390 C 980 430, 1050 455, 1120 460 L 1120 460 L 420 460 Z" fill="url(#curvePha)"/>
  <path d="M 420 460 C 470 450, 510 180, 560 160 C 640 140, 740 280, 880 390 C 980 430, 1050 455, 1120 460" stroke="#c084fc" stroke-width="4.5" stroke-linecap="round"/>

  <!-- Therapeutic Window Lines -->
  <line x1="420" y1="230" x2="1120" y2="230" stroke="#34d399" stroke-width="1.5" stroke-dasharray="6 6"/>
  <text x="1110" y="222" fill="#34d399" font-family="system-ui, sans-serif" font-size="11" font-weight="700" text-anchor="end">Minimum Effective Concentration (MEC)</text>

  <line x1="420" y1="140" x2="1120" y2="140" stroke="#f43f5e" stroke-width="1.5" stroke-dasharray="6 6"/>
  <text x="1110" y="132" fill="#f43f5e" font-family="system-ui, sans-serif" font-size="11" font-weight="700" text-anchor="end">Maximum Safe Concentration (MSC / Batas Toksik)</text>

  <!-- Cmax Peak Marker -->
  <circle cx="560" cy="160" r="7" fill="#a855f7" stroke="#ffffff" stroke-width="2.5"/>
  <line x1="560" y1="160" x2="560" y2="460" stroke="#a855f7" stroke-width="1.5" stroke-dasharray="4 4"/>

  <!-- Axis & Labels -->
  <line x1="420" y1="120" x2="420" y2="470" stroke="#64748b" stroke-width="2"/>
  <line x1="420" y1="470" x2="1120" y2="470" stroke="#64748b" stroke-width="2"/>
  <text x="420" y="500" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="12" font-weight="700">T0 : Waktu Pemberian Dosis</text>
  <text x="560" y="500" fill="#c084fc" font-family="system-ui, sans-serif" font-size="12" font-weight="800" text-anchor="middle">Tmax (Puncak Cmax)</text>
  <text x="1120" y="500" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="12" font-weight="700" text-anchor="end">Waktu Eliminasi (t1/2)</text>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#1c103b" stroke="#a855f7" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#a855f7"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderLaboratory(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'TEKNOLOGI LABORATORIUM MEDIS');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgLab" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#071228"/>
      <stop offset="50%" stop-color="#0e214d"/>
      <stop offset="100%" stop-color="#05333e"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgLab)"/>

  <!-- DNA Double Helix Representation -->
  <g transform="translate(100, 150)">
    <path d="M 0 80 Q 75 10, 150 80 T 300 80 T 450 80 T 600 80 T 750 80 T 900 80 T 1000 80" stroke="#38bdf8" stroke-width="4.5" fill="none"/>
    <path d="M 0 80 Q 75 150, 150 80 T 300 80 T 450 80 T 600 80 T 750 80 T 900 80 T 1000 80" stroke="#34d399" stroke-width="4.5" fill="none"/>
    <line x1="75" y1="25" x2="75" y2="135" stroke="#818cf8" stroke-width="2.5"/>
    <line x1="225" y1="25" x2="225" y2="135" stroke="#f43f5e" stroke-width="2.5"/>
    <line x1="375" y1="25" x2="375" y2="135" stroke="#818cf8" stroke-width="2.5"/>
    <line x1="525" y1="25" x2="525" y2="135" stroke="#38bdf8" stroke-width="2.5"/>
    <line x1="675" y1="25" x2="675" y2="135" stroke="#fbbf24" stroke-width="2.5"/>
    <line x1="825" y1="25" x2="825" y2="135" stroke="#34d399" stroke-width="2.5"/>
    <line x1="975" y1="25" x2="975" y2="135" stroke="#f43f5e" stroke-width="2.5"/>
    <text x="0" y="-15" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="12" font-weight="700" letter-spacing="1">MOLECULAR DNA HELIX</text>
  </g>

  <!-- Agarose Gel Electrophoresis visualization -->
  <g transform="translate(100, 350)">
    <rect width="1000" height="150" rx="10" fill="#091b36" stroke="#1e3a8a" stroke-width="1.5"/>
    <text x="30" y="30" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="11" font-weight="700" letter-spacing="1">AGAROSE GEL ELECTROPHORESIS (LADDER &amp; SAMPLE LANES)</text>
    
    <!-- Lane 1: Ladder -->
    <rect x="50" y="50" width="40" height="8" rx="2" fill="#38bdf8"/>
    <rect x="50" y="70" width="40" height="8" rx="2" fill="#38bdf8"/>
    <rect x="50" y="95" width="40" height="8" rx="2" fill="#38bdf8"/>
    <rect x="50" y="120" width="40" height="8" rx="2" fill="#38bdf8"/>
    <text x="50" y="142" fill="#64748b" font-family="system-ui, sans-serif" font-size="9" font-weight="700">Ladder</text>

    <!-- Lane 2 -->
    <rect x="180" y="55" width="40" height="10" rx="2" fill="#34d399"/>
    <rect x="180" y="95" width="40" height="12" rx="2" fill="#34d399"/>
    <text x="180" y="142" fill="#64748b" font-family="system-ui, sans-serif" font-size="9" font-weight="700">Sample A</text>

    <!-- Lane 3 -->
    <rect x="310" y="70" width="40" height="10" rx="2" fill="#38bdf8"/>
    <rect x="310" y="120" width="40" height="8" rx="2" fill="#38bdf8"/>
    <text x="310" y="142" fill="#64748b" font-family="system-ui, sans-serif" font-size="9" font-weight="700">Sample B</text>

    <!-- Lane 4 -->
    <rect x="440" y="55" width="40" height="14" rx="2" fill="#818cf8"/>
    <rect x="440" y="95" width="40" height="9" rx="2" fill="#818cf8"/>
    <text x="440" y="142" fill="#64748b" font-family="system-ui, sans-serif" font-size="9" font-weight="700">Sample C</text>

    <!-- Spectrophotometry Metric on Right -->
    <g transform="translate(680, 45)">
      <rect width="280" height="75" rx="8" fill="#0c254b" stroke="#2563eb" stroke-width="1.5"/>
      <text x="16" y="26" fill="#93c5fd" font-family="system-ui, sans-serif" font-size="10" font-weight="700">SPECTROPHOTOMETRIC ABSORBANCE</text>
      <text x="16" y="56" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="22" font-weight="900">λ = 540nm <tspan font-size="14" font-weight="600" fill="#94a3b8">| OD: 0.642</tspan></text>
    </g>
  </g>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#0e214d" stroke="#38bdf8" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#38bdf8"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderDental(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'PELAYANAN KESEHATAN GIGI & MULUT');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgDen" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#081829"/>
      <stop offset="50%" stop-color="#0d2847"/>
      <stop offset="100%" stop-color="#05333f"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgDen)"/>

  <!-- Dental Arch / Quadrant Odontogram Chart -->
  <g transform="translate(600, 310)">
    <!-- Quadrant Division Lines -->
    <line x1="0" y1="-180" x2="0" y2="180" stroke="#334155" stroke-width="1.5" stroke-dasharray="4 4"/>
    <line x1="-350" y1="0" x2="350" y2="0" stroke="#334155" stroke-width="1.5" stroke-dasharray="4 4"/>
    <text x="280" y="-120" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="11" font-weight="800">KUADRAN 1 (UR)</text>
    <text x="-280" y="-120" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="11" font-weight="800" text-anchor="end">KUADRAN 2 (UL)</text>
    <text x="-280" y="140" fill="#06b6d4" font-family="system-ui, sans-serif" font-size="11" font-weight="800" text-anchor="end">KUADRAN 3 (LL)</text>
    <text x="280" y="140" fill="#06b6d4" font-family="system-ui, sans-serif" font-size="11" font-weight="800">KUADRAN 4 (LR)</text>

    <!-- Upper Maxillary Arch Curve -->
    <path d="M -300 -40 Q 0 -170, 300 -40" stroke="#38bdf8" stroke-width="4" fill="none"/>
    <!-- Lower Mandibular Arch Curve -->
    <path d="M -260 50 Q 0 160, 260 50" stroke="#06b6d4" stroke-width="4" fill="none"/>

    <!-- Maxillary Tooth Nodes -->
    <circle cx="-280" cy="-45" r="10" fill="#f8fafc" stroke="#38bdf8" stroke-width="3"/>
    <circle cx="-200" cy="-90" r="10" fill="#f8fafc" stroke="#38bdf8" stroke-width="3"/>
    <circle cx="-100" cy="-125" r="10" fill="#f8fafc" stroke="#38bdf8" stroke-width="3"/>
    <circle cx="0" cy="-140" r="12" fill="#38bdf8" stroke="#ffffff" stroke-width="3"/>
    <circle cx="100" cy="-125" r="10" fill="#f8fafc" stroke="#38bdf8" stroke-width="3"/>
    <circle cx="200" cy="-90" r="10" fill="#f8fafc" stroke="#38bdf8" stroke-width="3"/>
    <circle cx="280" cy="-45" r="10" fill="#f8fafc" stroke="#38bdf8" stroke-width="3"/>

    <!-- Mandibular Tooth Nodes -->
    <circle cx="-240" cy="55" r="10" fill="#f8fafc" stroke="#06b6d4" stroke-width="3"/>
    <circle cx="-160" cy="95" r="10" fill="#f8fafc" stroke="#06b6d4" stroke-width="3"/>
    <circle cx="-80" cy="120" r="10" fill="#f8fafc" stroke="#06b6d4" stroke-width="3"/>
    <circle cx="0" cy="130" r="12" fill="#06b6d4" stroke="#ffffff" stroke-width="3"/>
    <circle cx="80" cy="120" r="10" fill="#f8fafc" stroke="#06b6d4" stroke-width="3"/>
    <circle cx="160" cy="95" r="10" fill="#f8fafc" stroke="#06b6d4" stroke-width="3"/>
    <circle cx="240" cy="55" r="10" fill="#f8fafc" stroke="#06b6d4" stroke-width="3"/>
  </g>

  <!-- Left Status Panel -->
  <g transform="translate(80, 480)">
    <rect width="260" height="85" rx="10" fill="#0b2440" stroke="#164e63" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">STATUS ODONTOGRAM</text>
    <text x="18" y="58" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="18" font-weight="800">DMFT Index: &lt; 1.2</text>
    <text x="18" y="75" fill="#34d399" font-family="system-ui, sans-serif" font-size="11" font-weight="600">Kategori Risiko Rendah (Low Risk)</text>
  </g>

  <!-- Right Status Panel -->
  <g transform="translate(860, 480)">
    <rect width="260" height="85" rx="10" fill="#0b2440" stroke="#164e63" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">ORAL HYGIENE INDEX (OHI-S)</text>
    <text x="18" y="58" fill="#10b981" font-family="system-ui, sans-serif" font-size="18" font-weight="800">Skor OHI-S: 0.8</text>
    <text x="18" y="75" fill="#6ee7b7" font-family="system-ui, sans-serif" font-size="11" font-weight="600">Kebersihan Mulut Baik (Good)</text>
  </g>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#0d2847" stroke="#38bdf8" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#38bdf8"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderNutrition(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'ILMU GIZI & DIETETIKA KLINIS');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgNut" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#061810"/>
      <stop offset="50%" stop-color="#0a2a1a"/>
      <stop offset="100%" stop-color="#042017"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgNut)"/>

  <!-- Macronutrient Distribution Chart (Donut / Semi-Circle) -->
  <g transform="translate(320, 330)">
    <circle cx="0" cy="0" r="140" fill="none" stroke="#166534" stroke-width="36"/>
    <circle cx="0" cy="0" r="140" fill="none" stroke="#22c55e" stroke-width="36" stroke-dasharray="440 880" stroke-dashoffset="0"/>
    <circle cx="0" cy="0" r="140" fill="none" stroke="#eab308" stroke-width="36" stroke-dasharray="220 880" stroke-dashoffset="-440"/>
    <circle cx="0" cy="0" r="140" fill="none" stroke="#06b6d4" stroke-width="36" stroke-dasharray="220 880" stroke-dashoffset="-660"/>
    <text x="0" y="-8" fill="#f0fdf4" font-family="system-ui, sans-serif" font-size="30" font-weight="900" text-anchor="middle">2,150</text>
    <text x="0" y="22" fill="#86efac" font-family="system-ui, sans-serif" font-size="13" font-weight="700" text-anchor="middle">kcal / hari</text>
    <text x="0" y="44" fill="#a7f3d0" font-family="system-ui, sans-serif" font-size="10" font-weight="600" text-anchor="middle">Total Energy Expenditure</text>
  </g>

  <!-- Macro Legends (Right Side) -->
  <g transform="translate(620, 200)">
    <rect x="0" y="0" width="22" height="22" rx="5" fill="#22c55e"/>
    <text x="36" y="16" fill="#f0fdf4" font-family="system-ui, sans-serif" font-size="17" font-weight="700">Karbohidrat Kompleks (55%)</text>
    <text x="36" y="34" fill="#86efac" font-family="system-ui, sans-serif" font-size="12">Sumber energi utama &amp; serat larut</text>

    <rect x="0" y="70" width="22" height="22" rx="5" fill="#06b6d4"/>
    <text x="36" y="86" fill="#f0fdf4" font-family="system-ui, sans-serif" font-size="17" font-weight="700">Protein Berkualitas Tinggi (20%)</text>
    <text x="36" y="104" fill="#67e8f9" font-family="system-ui, sans-serif" font-size="12">Asam amino esensial &amp; perbaikan jaringan</text>

    <rect x="0" y="140" width="22" height="22" rx="5" fill="#eab308"/>
    <text x="36" y="156" fill="#f0fdf4" font-family="system-ui, sans-serif" font-size="17" font-weight="700">Lemak Sehat &amp; Esensial (25%)</text>
    <text x="36" y="174" fill="#fde047" font-family="system-ui, sans-serif" font-size="12">Omega-3, penyerapan vitamin A, D, E, K</text>
  </g>

  <!-- Micronutrient Status (Bottom Bar) -->
  <g transform="translate(100, 500)">
    <rect width="1000" height="50" rx="10" fill="#092d1c" stroke="#166534" stroke-width="1.5"/>
    <text x="30" y="30" fill="#86efac" font-family="system-ui, sans-serif" font-size="12" font-weight="800">STATUS GIZI SEIMBANG :</text>
    <text x="210" y="30" fill="#f0fdf4" font-family="system-ui, sans-serif" font-size="12" font-weight="600">Kalsium: 100% AKG • Zat Besi: 95% AKG • Vitamin C: 120% AKG • Serat: 28g/hari</text>
  </g>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#0a2a1a" stroke="#22c55e" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#22c55e"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderMaternal(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'KEBIDANAN & KESEHATAN MATERNAL NEONATAL');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgMat" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#1f0a1c"/>
      <stop offset="50%" stop-color="#2d0f28"/>
      <stop offset="100%" stop-color="#0e172a"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgMat)"/>

  <!-- Partograph Grid & Dilation Lines -->
  <g stroke="#4a1942" stroke-width="1" opacity="0.6">
    <line x1="80" y1="140" x2="1120" y2="140"/>
    <line x1="80" y1="220" x2="1120" y2="220"/>
    <line x1="80" y1="300" x2="1120" y2="300"/>
    <line x1="80" y1="380" x2="1120" y2="380"/>
  </g>

  <!-- Partograph Alert & Action Lines -->
  <line x1="250" y1="380" x2="700" y2="140" stroke="#eab308" stroke-width="2.5" stroke-dasharray="6 6"/>
  <text x="710" y="145" fill="#eab308" font-family="system-ui, sans-serif" font-size="11" font-weight="800">Garis Waspada (Alert Line)</text>

  <line x1="450" y1="380" x2="900" y2="140" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="6 6"/>
  <text x="910" y="145" fill="#f43f5e" font-family="system-ui, sans-serif" font-size="11" font-weight="800">Garis Bertindak (Action Line)</text>

  <!-- Cervical Dilation Progress Plot -->
  <path d="M 250 380 L 350 320 L 450 260 L 550 190 L 650 140" stroke="#ec4899" stroke-width="4.5" fill="none" stroke-linecap="round"/>
  <circle cx="250" cy="380" r="6" fill="#ec4899" stroke="#ffffff" stroke-width="2"/>
  <circle cx="350" cy="320" r="6" fill="#ec4899" stroke="#ffffff" stroke-width="2"/>
  <circle cx="450" cy="260" r="6" fill="#ec4899" stroke="#ffffff" stroke-width="2"/>
  <circle cx="550" cy="190" r="6" fill="#ec4899" stroke="#ffffff" stroke-width="2"/>
  <circle cx="650" cy="140" r="7" fill="#10b981" stroke="#ffffff" stroke-width="2.5"/>

  <!-- Telemetry Panels Bottom -->
  <g transform="translate(80, 450)">
    <rect width="320" height="95" rx="12" fill="#2d0f28" stroke="#701a75" stroke-width="1.5"/>
    <text x="18" y="30" fill="#f472b6" font-family="system-ui, sans-serif" font-size="11" font-weight="700">DENYUT JANTUNG JANIN (DJJ / FHR)</text>
    <text x="18" y="72" fill="#ec4899" font-family="system-ui, sans-serif" font-size="34" font-weight="900">142 <tspan font-size="16" font-weight="500" fill="#fbcfe8">bpm (Normal)</tspan></text>
  </g>
  <g transform="translate(430, 450)">
    <rect width="320" height="95" rx="12" fill="#2d0f28" stroke="#701a75" stroke-width="1.5"/>
    <text x="18" y="30" fill="#f472b6" font-family="system-ui, sans-serif" font-size="11" font-weight="700">PEMBUKAAN SERVIKS (DILATASI)</text>
    <text x="18" y="72" fill="#10b981" font-family="system-ui, sans-serif" font-size="34" font-weight="900">10 <tspan font-size="16" font-weight="500" fill="#a7f3d0">cm (Lengkap)</tspan></text>
  </g>
  <g transform="translate(780, 450)">
    <rect width="340" height="95" rx="12" fill="#2d0f28" stroke="#701a75" stroke-width="1.5"/>
    <text x="18" y="30" fill="#f472b6" font-family="system-ui, sans-serif" font-size="11" font-weight="700">HIS / KONTRAKSI UTERUS</text>
    <text x="18" y="72" fill="#eab308" font-family="system-ui, sans-serif" font-size="34" font-weight="900">4x / 10' <tspan font-size="16" font-weight="500" fill="#fef08a">&gt; 40 detik</tspan></text>
  </g>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#2d0f28" stroke="#ec4899" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#ec4899"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderSanitation(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'SANITASI & KESEHATAN LINGKUNGAN');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgSan" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#04161b"/>
      <stop offset="50%" stop-color="#082b33"/>
      <stop offset="100%" stop-color="#071b26"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgSan)"/>

  <!-- Water & Ecological Filtration Cycle Diagram -->
  <g transform="translate(100, 160)">
    <!-- Stage 1: Koagulasi & Flokulasi -->
    <rect x="0" y="60" width="180" height="90" rx="12" fill="#0d3b45" stroke="#06b6d4" stroke-width="2"/>
    <text x="90" y="95" fill="#e0f2fe" font-family="system-ui, sans-serif" font-size="14" font-weight="800" text-anchor="middle">Tahap 1</text>
    <text x="90" y="120" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="12" font-weight="600" text-anchor="middle">Koagulasi &amp; Flokulasi</text>

    <line x1="180" y1="105" x2="240" y2="105" stroke="#38bdf8" stroke-width="3" stroke-dasharray="4 4"/>

    <!-- Stage 2: Sedimentasi -->
    <rect x="240" y="60" width="180" height="90" rx="12" fill="#0d3b45" stroke="#06b6d4" stroke-width="2"/>
    <text x="330" y="95" fill="#e0f2fe" font-family="system-ui, sans-serif" font-size="14" font-weight="800" text-anchor="middle">Tahap 2</text>
    <text x="330" y="120" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="12" font-weight="600" text-anchor="middle">Sedimentasi Partikel</text>

    <line x1="420" y1="105" x2="480" y2="105" stroke="#38bdf8" stroke-width="3" stroke-dasharray="4 4"/>

    <!-- Stage 3: Filtrasi Bertingkat -->
    <rect x="480" y="60" width="180" height="90" rx="12" fill="#0d3b45" stroke="#06b6d4" stroke-width="2"/>
    <text x="570" y="95" fill="#e0f2fe" font-family="system-ui, sans-serif" font-size="14" font-weight="800" text-anchor="middle">Tahap 3</text>
    <text x="570" y="120" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="12" font-weight="600" text-anchor="middle">Filtrasi Membran/Pasir</text>

    <line x1="660" y1="105" x2="720" y2="105" stroke="#10b981" stroke-width="3" stroke-dasharray="4 4"/>

    <!-- Stage 4: Disinfeksi & Distribusi -->
    <rect x="720" y="60" width="220" height="90" rx="12" fill="#064e3b" stroke="#10b981" stroke-width="2"/>
    <text x="830" y="95" fill="#d1fae5" font-family="system-ui, sans-serif" font-size="14" font-weight="800" text-anchor="middle">Tahap 4</text>
    <text x="830" y="120" fill="#34d399" font-family="system-ui, sans-serif" font-size="12" font-weight="600" text-anchor="middle">Disinfeksi Klor / UV Steril</text>
  </g>

  <!-- Parameter Badges Bottom -->
  <g transform="translate(100, 390)">
    <rect width="220" height="85" rx="10" fill="#082b33" stroke="#0e7490" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">DERAJAT KEASAMAN</text>
    <text x="18" y="60" fill="#10b981" font-family="system-ui, sans-serif" font-size="28" font-weight="900">pH 7.1 <tspan font-size="13" font-weight="600" fill="#6ee7b7">Netral</tspan></text>
  </g>
  <g transform="translate(350, 390)">
    <rect width="220" height="85" rx="10" fill="#082b33" stroke="#0e7490" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">TOTAL DISSOLVED SOLIDS</text>
    <text x="18" y="60" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="28" font-weight="900">110 <tspan font-size="13" font-weight="600" fill="#7dd3fc">ppm</tspan></text>
  </g>
  <g transform="translate(600, 390)">
    <rect width="220" height="85" rx="10" fill="#082b33" stroke="#0e7490" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">DISSOLVED OXYGEN (DO)</text>
    <text x="18" y="60" fill="#10b981" font-family="system-ui, sans-serif" font-size="28" font-weight="900">6.8 <tspan font-size="13" font-weight="600" fill="#6ee7b7">mg/L</tspan></text>
  </g>
  <g transform="translate(850, 390)">
    <rect width="250" height="85" rx="10" fill="#082b33" stroke="#0e7490" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">BAKU MUTU BAKTERIOLOGIS</text>
    <text x="18" y="60" fill="#34d399" font-family="system-ui, sans-serif" font-size="28" font-weight="900">0 MPN <tspan font-size="13" font-weight="600" fill="#a7f3d0">/100ml E.coli</tspan></text>
  </g>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#082b33" stroke="#06b6d4" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#06b6d4"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }

    private static function renderGeneral(string $title): string
    {
        $titleEsc = self::escapeTitle($title, 'ILMU KESEHATAN & PRAKTIK KLINIS');
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bgGen" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#081424"/>
      <stop offset="50%" stop-color="#0c233c"/>
      <stop offset="100%" stop-color="#03252a"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgGen)"/>

  <!-- Pulse Wave & Cross Centerpiece -->
  <g transform="translate(100, 260)">
    <path d="M 0 60 L 220 60 L 250 10 L 280 120 L 310 60 L 450 60 L 470 30 L 490 90 L 510 60 L 1000 60" stroke="#06b6d4" stroke-width="4.5" stroke-linecap="round"/>
    <circle cx="250" cy="10" r="7" fill="#10b981" stroke="#ffffff" stroke-width="2"/>
    <circle cx="280" cy="120" r="7" fill="#f43f5e" stroke="#ffffff" stroke-width="2"/>
  </g>

  <!-- Metric Badges Bottom -->
  <g transform="translate(100, 440)">
    <rect width="280" height="80" rx="10" fill="#0c2540" stroke="#164e63" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">HEMODINAMIK KLINIS</text>
    <text x="18" y="58" fill="#10b981" font-family="system-ui, sans-serif" font-size="22" font-weight="800">Status Fisiologis Normal</text>
  </g>
  <g transform="translate(420, 440)">
    <rect width="280" height="80" rx="10" fill="#0c2540" stroke="#164e63" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">PROTOKOL DIAGNOSTIK</text>
    <text x="18" y="58" fill="#38bdf8" font-family="system-ui, sans-serif" font-size="22" font-weight="800">Evidence-Based Practice</text>
  </g>
  <g transform="translate(740, 440)">
    <rect width="360" height="80" rx="10" fill="#0c2540" stroke="#164e63" stroke-width="1.5"/>
    <text x="18" y="28" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="10" font-weight="700">STANDAR KOMPETENSI KESEHATAN</text>
    <text x="18" y="58" fill="#a78bfa" font-family="system-ui, sans-serif" font-size="22" font-weight="800">Kurikulum Poltekkes Kemenkes</text>
  </g>

  <!-- Top Title Bar -->
  <g transform="translate(80, 42)">
    <rect width="520" height="40" rx="20" fill="#0c233c" stroke="#06b6d4" stroke-width="1.5"/>
    <circle cx="22" cy="20" r="7" fill="#06b6d4"/>
    <text x="42" y="25" fill="#f8fafc" font-family="system-ui, sans-serif" font-size="13" font-weight="800" letter-spacing="0.5">{$titleEsc}</text>
  </g>
</svg>
SVG;
    }
}

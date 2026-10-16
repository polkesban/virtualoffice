<?php

namespace App\Support;

use App\Models\UnitEvent;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class EventFlyerOgImageGenerator
{
    private static string $fontBold = '/usr/share/fonts/truetype/lato/Lato-Bold.ttf';
    private static string $fontBlack = '/usr/share/fonts/truetype/lato/Lato-Black.ttf';
    private static string $fontRegular = '/usr/share/fonts/truetype/lato/Lato-Regular.ttf';

    public static function generate(UnitEvent $event): string
    {
        $cacheDir = storage_path('app/public/flyer-og-cache');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $filename = 'flyer-og-' . $event->id . '-' . md5($event->updated_at?->toIso8601String() ?? 'v1') . '.png';
        $filepath = $cacheDir . '/' . $filename;

        if (file_exists($filepath) && filesize($filepath) > 1000) {
            return $filepath;
        }

        $width = 1200;
        $height = 630;

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        // Palette
        $themeColors = [
            'bg_start' => [10, 25, 47],    // Deep Navy
            'bg_mid'   => [13, 60, 75],    // Dark Teal
            'bg_end'   => [5, 12, 24],     // Midnight
        ];

        // Draw gradient background
        for ($y = 0; $y < $height; $y++) {
            $t = $y / $height;
            $r = (int) ($themeColors['bg_start'][0] * (1 - $t) + $themeColors['bg_end'][0] * $t);
            $g = (int) ($themeColors['bg_mid'][1] * (1 - $t) + $themeColors['bg_end'][1] * $t);
            $b = (int) ($themeColors['bg_start'][2] * (1 - $t) + $themeColors['bg_end'][2] * $t);
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width, $y, $color);
        }

        // Draw glowing decorative accents
        $glowColor1 = imagecolorallocatealpha($img, 20, 184, 166, 115);
        imagefilledellipse($img, 120, 100, 380, 380, $glowColor1);
        $glowColor2 = imagecolorallocatealpha($img, 14, 165, 233, 118);
        imagefilledellipse($img, $width - 120, $height - 120, 420, 420, $glowColor2);

        // Colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $teal = imagecolorallocate($img, 45, 212, 191);
        $tealDark = imagecolorallocate($img, 20, 184, 166);
        $tealBg = imagecolorallocatealpha($img, 13, 148, 136, 85);
        $amber = imagecolorallocate($img, 251, 191, 36);
        $cardBg = imagecolorallocatealpha($img, 255, 255, 255, 116);
        $cardBorder = imagecolorallocatealpha($img, 255, 255, 255, 85);
        $darkBox = imagecolorallocatealpha($img, 10, 20, 35, 45);
        $boxBorder = imagecolorallocatealpha($img, 45, 212, 191, 80);

        // 1. Draw Header Logo
        $logoPath = public_path('images/logo.png');
        $logoX = 50;
        $logoY = 32;
        if (file_exists($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                $origW = imagesx($logo);
                $origH = imagesy($logo);
                $targetH = 50;
                $targetW = (int) ($origW * ($targetH / $origH));
                imagecopyresampled($img, $logo, $logoX, $logoY, 0, 0, $targetW, $targetH, $origW, $origH);
                imagedestroy($logo);
                $logoX += $targetW + 16;
            }
        }

        // Header Title / Subtitle
        $pageTitle = $event->page?->judul ?: 'Pusat Pengembangan Pendidikan';
        imagettftext($img, 12, 0, $logoX, $logoY + 20, $white, self::getFont('black'), 'KEMENTERIAN KESEHATAN RI');
        imagettftext($img, 10, 0, $logoX, $logoY + 40, $teal, self::getFont('bold'), 'POLTEKKES KEMENKES BANDUNG • ' . mb_strtoupper($pageTitle));

        // Event Type Badge on top right
        $jenisLabel = UnitEvent::$jenisLabels[$event->jenis_kegiatan] ?? 'EVENT RESMI';
        $badgeText = 'POSTER RESMI • ' . mb_strtoupper($jenisLabel);
        self::drawPill($img, $width - 290, 35, 240, 34, $tealBg, $teal, $badgeText, 10, self::getFont('bold'), $white);

        // Horizontal separator
        $sepColor = imagecolorallocatealpha($img, 255, 255, 255, 95);
        imageline($img, 50, 98, $width - 50, 98, $sepColor);

        // 2. Main Title (Left Column)
        $title = $event->judul;
        $lines = self::wrapText($title, 24, 28);
        $titleY = 145;
        foreach ($lines as $i => $line) {
            imagettftext($img, 22, 0, 50, $titleY + ($i * 38), $white, self::getFont('black'), $line);
        }

        // 3. Schedule & Location Pills Box (Left side below title)
        $dateText = $event->mulai_at ? $event->mulai_at->translatedFormat('l, d F Y') : '-';
        $timeText = $event->mulai_at ? $event->mulai_at->format('H:i') . ($event->selesai_at ? ' - ' . $event->selesai_at->format('H:i') . ' WIB' : ' WIB s.d. Selesai') : '-';
        $locText = $event->resolvedLocationName();
        if (mb_strlen($locText) > 36) {
            $locText = mb_substr($locText, 0, 33) . '...';
        }

        $matrixY = 255;
        self::drawInfoCard($img, 50, $matrixY, 680, 52, $cardBg, $cardBorder, 'HARI & TANGGAL', $dateText, $white, $teal);
        self::drawInfoCard($img, 50, $matrixY + 62, 330, 52, $cardBg, $cardBorder, 'WAKTU', $timeText, $white, $teal);
        self::drawInfoCard($img, 395, $matrixY + 62, 335, 52, $cardBg, $cardBorder, 'TEMPAT / MEDIA', $locText, $white, $teal);

        // 4. Target Peserta / Highlight
        $targetPeserta = $event->resolvedTargetPesertaList();
        $targetSummary = !empty($targetPeserta) ? implode(' • ', array_slice($targetPeserta, 0, 3)) : 'Mahasiswa, Dosen & Sivitas Akademika Polkesban';
        if (mb_strlen($targetSummary) > 65) {
            $targetSummary = mb_substr($targetSummary, 0, 62) . '...';
        }
        self::drawInfoCard($img, 50, $matrixY + 124, 680, 50, $cardBg, $cardBorder, 'PESERTA LURING', $targetSummary, $white, $amber);

        // 5. Right Column: Keynote & Speaker Box
        $speakers = $event->speakers()->get();
        $speaker = $speakers->first();

        $rightBoxX = 750;
        $rightBoxY = 125;
        $rightBoxW = 400;
        $rightBoxH = 370;

        self::drawRoundedRect($img, $rightBoxX, $rightBoxY, $rightBoxW, $rightBoxH, 16, $darkBox, $boxBorder);

        // Direktur section in top of right box
        $directorName = $event->direktur_nama ?: 'Iwan Ridwan, S.Kp., M.Kep.';
        imagettftext($img, 9, 0, $rightBoxX + 20, $rightBoxY + 28, $amber, self::getFont('bold'), 'PIMPINAN INSTITUSI:');
        imagettftext($img, 12, 0, $rightBoxX + 20, $rightBoxY + 48, $white, self::getFont('black'), $directorName);
        imagettftext($img, 9, 0, $rightBoxX + 20, $rightBoxY + 66, $teal, self::getFont('regular'), 'Direktur Poltekkes Kemenkes Bandung');

        imageline($img, $rightBoxX + 20, $rightBoxY + 80, $rightBoxX + $rightBoxW - 20, $rightBoxY + 80, $sepColor);

        // Speaker Section
        imagettftext($img, 9, 0, $rightBoxX + 20, $rightBoxY + 102, $amber, self::getFont('bold'), 'NARASUMBER & PEMBICARA:');

        if ($speaker) {
            $speakerPhotoSize = 120;
            $speakerPhotoX = $rightBoxX + $rightBoxW - 20 - $speakerPhotoSize;
            $speakerPhotoY = $rightBoxY + 115;

            $photoDrawn = false;
            if ($speaker->foto_path) {
                try {
                    $localPhotoPath = \App\Support\MediaStorage::temporaryLocalPath($speaker->foto_path);
                    if ($localPhotoPath && file_exists($localPhotoPath)) {
                        $photoData = @file_get_contents($localPhotoPath);
                        if ($photoData) {
                            $photoImg = @imagecreatefromstring($photoData);
                            if ($photoImg) {
                                $pw = imagesx($photoImg);
                                $ph = imagesy($photoImg);
                                imagecopyresampled($img, $photoImg, $speakerPhotoX, $speakerPhotoY, 0, 0, $speakerPhotoSize, $speakerPhotoSize, $pw, $ph);
                                imagedestroy($photoImg);
                                $photoDrawn = true;
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }

            if (!$photoDrawn) {
                imagefilledrectangle($img, $speakerPhotoX, $speakerPhotoY, $speakerPhotoX + $speakerPhotoSize, $speakerPhotoY + $speakerPhotoSize, $tealBg);
                imagerectangle($img, $speakerPhotoX, $speakerPhotoY, $speakerPhotoX + $speakerPhotoSize, $speakerPhotoY + $speakerPhotoSize, $teal);
                imagettftext($img, 11, 0, $speakerPhotoX + 16, $speakerPhotoY + 54, $white, self::getFont('bold'), 'NARASUMBER');
            } else {
                imagerectangle($img, $speakerPhotoX, $speakerPhotoY, $speakerPhotoX + $speakerPhotoSize, $speakerPhotoY + $speakerPhotoSize, $teal);
            }

            $speakerNameLines = self::wrapText($speaker->nama, 12, 17);
            $snY = $speakerPhotoY + 22;
            foreach ($speakerNameLines as $line) {
                imagettftext($img, 12, 0, $rightBoxX + 20, $snY, $white, self::getFont('black'), $line);
                $snY += 20;
            }

            $instansi = trim(($speaker->jabatan ? $speaker->jabatan . ' ' : '') . ($speaker->instansi ?: ''));
            $instansiLines = self::wrapText($instansi, 10, 20);
            foreach ($instansiLines as $line) {
                imagettftext($img, 9, 0, $rightBoxX + 20, $snY, $teal, self::getFont('bold'), $line);
                $snY += 16;
            }

            if ($speaker->catatan) {
                $catatanLines = self::wrapText($speaker->catatan, 8, 22);
                $snY += 4;
                foreach ($catatanLines as $line) {
                    imagettftext($img, 8, 0, $rightBoxX + 20, $snY, $amber, self::getFont('bold'), $line);
                    $snY += 14;
                }
            }
        }

        // QR Code & Footer bar
        $footerY = 515;
        self::drawRoundedRect($img, 50, $footerY, $width - 100, 80, 14, $cardBg, $cardBorder);

        // QR Code generator
        $qrSize = 62;
        $qrX = 65;
        $qrY = $footerY + 9;
        try {
            $qrUrl = route('unit.events.flyer', [$event->page?->slug, $event->slug]);
            $qrOptions = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRCode::ECC_L,
                'scale' => 4,
                'imageBase64' => false,
            ]);
            $qrRaw = (new QRCode($qrOptions))->render($qrUrl);
            $qrImg = @imagecreatefromstring($qrRaw);
            if ($qrImg) {
                imagecopyresampled($img, $qrImg, $qrX, $qrY, 0, 0, $qrSize, $qrSize, imagesx($qrImg), imagesy($qrImg));
                imagedestroy($qrImg);
            }
        } catch (\Throwable $e) {}

        imagettftext($img, 12, 0, $qrX + $qrSize + 18, $footerY + 34, $white, self::getFont('black'), 'Scan QR Code untuk Akses Flyer, Jadwal & Presensi Online');
        imagettftext($img, 10, 0, $qrX + $qrSize + 18, $footerY + 56, $teal, self::getFont('bold'), 'Portal Resmi Kegiatan: poltekkesbandung.ac.id • @poltekkesbandung');

        imagettftext($img, 11, 0, $width - 240, $footerY + 46, $amber, self::getFont('black'), 'Poltekkes Bandung ↗');

        // Save PNG
        imagepng($img, $filepath, 8);
        imagedestroy($img);

        return $filepath;
    }

    private static function getFont(string $weight = 'regular'): string
    {
        return match ($weight) {
            'black', 'heavy' => file_exists(self::$fontBlack) ? self::$fontBlack : self::$fontBold,
            'bold'           => file_exists(self::$fontBold) ? self::$fontBold : '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            default          => file_exists(self::$fontRegular) ? self::$fontRegular : '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        };
    }

    private static function drawRoundedRect($img, int $x, int $y, int $w, int $h, int $r, int $color, ?int $borderColor = null): void
    {
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $color);
        if ($borderColor !== null) {
            imagerectangle($img, $x, $y, $x + $w, $y + $h, $borderColor);
        }
    }

    private static function drawPill($img, int $x, int $y, int $w, int $h, int $bgColor, int $borderColor, string $text, int $fontSize, string $font, int $textColor): void
    {
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $bgColor);
        imagerectangle($img, $x, $y, $x + $w, $y + $h, $borderColor);
        imagettftext($img, $fontSize, 0, $x + 16, $y + $h - 11, $textColor, $font, $text);
    }

    private static function drawInfoCard($img, int $x, int $y, int $w, int $h, int $bgColor, int $borderColor, string $label, string $value, int $textColor, int $accentColor): void
    {
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $bgColor);
        imagerectangle($img, $x, $y, $x + $w, $y + $h, $borderColor);
        imagettftext($img, 8, 0, $x + 16, $y + 18, $accentColor, self::getFont('black'), $label);
        imagettftext($img, 11, 0, $x + 16, $y + 38, $textColor, self::getFont('bold'), $value);
    }

    private static function wrapText(string $text, int $fontSize, int $maxChars): array
    {
        $rawLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
        $lines = [];

        foreach ($rawLines as $rawLine) {
            $words = explode(' ', trim($rawLine));
            $currentLine = '';

            foreach ($words as $word) {
                if (mb_strlen($currentLine . ' ' . $word) > $maxChars) {
                    if ($currentLine !== '') {
                        $lines[] = trim($currentLine);
                    }
                    $currentLine = $word;
                } else {
                    $currentLine = ($currentLine === '') ? $word : $currentLine . ' ' . $word;
                }
            }

            if ($currentLine !== '') {
                $lines[] = trim($currentLine);
            }
        }

        return $lines;
    }
}

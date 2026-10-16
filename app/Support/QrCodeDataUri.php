<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeDataUri
{
    public function make(string $text, int $scale = 5): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => max(3, $scale),
            'addQuietzone' => true,
            'outputBase64' => true,
        ]);

        return (new QRCode($options))->render($text);
    }

    private function withWhiteBackground(string $svg): string
    {
        if (! str_contains($svg, '<svg') || str_contains($svg, '<rect width="100%" height="100%" fill="#fff"/>')) {
            return $svg;
        }

        return preg_replace(
            '/(<svg\b[^>]*>)/',
            '$1' . "\n<rect width=\"100%\" height=\"100%\" fill=\"#fff\"/>",
            $svg,
            1,
        ) ?? $svg;
    }
}
<?php

namespace App\Services\AI;

use App\Models\UnitEvent;
use App\Models\UnitEventDocumentation;
use App\Support\AiProviderModelResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UnitEventDocumentationAiService
{
    public function __construct(private readonly AiClient $ai) {}

    public function generateSummary(UnitEvent $unitEvent, UnitEventDocumentation $documentation, Collection $photos): array
    {
        $source = $this->buildMinutesSource($documentation);
        $context = $this->buildEventContext($unitEvent, $documentation, $photos);

        $prompt = <<<PROMPT
Buatkan resume hasil rapat atau kegiatan dalam Bahasa Indonesia formal berdasarkan konteks dan bahan berikut.

{$context}

Bahan notulen/transkrip:
{$source}

Ketentuan Penting:
- DILARANG menggunakan tag <html>, <head>, <style>, <body>, atau <!DOCTYPE>.
- DILARANG menggunakan markdown codeblock (misal ```html ... ```).
- HANYA hasilkan fragment HTML bersih yang siap dipakai langsung di rich text editor (gunakan tag <h3>, <h4>, <p>, <ul>, <li>, <strong>, <em>, <table>, <tr>, <td>).
- Wajib memuat 4 bagian utama berikut secara UTUH, PADAT, LENGKAP, dan TIDAK BOLEH TERPOTONG:
  1. Ringkasan Eksekutif (rangkuman singkat tujuan dan hasil utama rapat)
  2. Pokok Bahasan & Poin Penting (rangkum secara efektif poin-poin utama pembahasan, jangan terlalu berbelit-belit agar ruang cukup)
  3. Keputusan / Kesepakatan Rapat (poin-poin kesepakatan final)
  4. Rencana Tindak Lanjut / Action Plan (tindak lanjut teknis yang disepakati)
- Tulis secara padat, informatif, dan terstruktur rapi. PASTIKAN SELURUH DOKUMEN SAMPAI PADA BAGIAN 4 (TINDAK LANJUT) SELESAI DITULIS SECARA SEMPURNA DAN MEMILIKI TAG PENUTUP.
PROMPT;

        $rawHtml = $this->ai->chat([
            ['role' => 'system', 'content' => 'Anda adalah sekretaris rapat profesional untuk institusi pendidikan kesehatan (Poltekkes Kemenkes Bandung). Tugas Anda adalah merangkum transkrip mentah rapat secara padat, formal, akurat, dan lengkap dari Ringkasan Eksekutif hingga Tindak Lanjut tanpa ada bagian yang terpotong.'],
            ['role' => 'user', 'content' => $prompt],
        ], 0.3, 8192);

        return [
            'html' => $this->cleanHtmlOutput($rawHtml),
            'model' => $this->resolvedModel(),
        ];
    }

    public function generateReport(UnitEvent $unitEvent, UnitEventDocumentation $documentation, Collection $photos): array
    {
        $source = $this->buildMinutesSource($documentation);
        $summary = trim(strip_tags((string) $documentation->ai_summary_html));
        $context = $this->buildEventContext($unitEvent, $documentation, $photos);

        $summaryBlock = $summary !== ''
            ? "Resume yang sudah tersedia:\n{$summary}\n\n"
            : '';

        $reportNotes = trim((string) $documentation->report_notes);
        $reportNotesBlock = $reportNotes !== ''
            ? "Arahan tambahan admin untuk laporan:\n{$reportNotes}\n\n"
            : '';

        $prompt = <<<PROMPT
Susun draft laporan kegiatan atau laporan hasil rapat dalam Bahasa Indonesia formal berdasarkan data berikut.

{$context}

{$reportNotesBlock}{$summaryBlock}Bahan notulen/transkrip:
{$source}

Ketentuan Penting:
- DILARANG menggunakan tag <html>, <head>, <style>, <body>, atau <!DOCTYPE>.
- DILARANG menggunakan markdown codeblock (misal ```html ... ```).
- HANYA hasilkan fragment HTML bersih yang siap diedit admin di editor (gunakan tag <h2>, <h3>, <h4>, <p>, <ul>, <li>, <strong>, <em>, <table>, <tr>, <td>).
- Struktur minimal wajib komprehensif dari awal hingga akhir: Pendahuluan, Waktu dan Tempat, Peserta, Jalannya Kegiatan/Rapat, Hasil dan Keputusan, Tindak Lanjut, Penutup.
- Jangan mengarang nama peserta, keputusan, atau angka yang tidak tersedia. PASTIKAN SELURUH DRAFT LAPORAN SELESAI DITULIS HINGGA PENUTUP TANPA TERPOTONG.
PROMPT;

        $rawHtml = $this->ai->chat([
            ['role' => 'system', 'content' => 'Anda adalah penyusun laporan kegiatan kampus dan notulis rapat pimpinan. Hasilkan draft yang formal, jelas, komprehensif, dan siap cetak.'],
            ['role' => 'user', 'content' => $prompt],
        ], 0.35, 8192);

        return [
            'html' => $this->cleanHtmlOutput($rawHtml),
            'model' => $this->resolvedModel(),
        ];
    }

    private function buildMinutesSource(UnitEventDocumentation $documentation): string
    {
        $chunks = collect([
            trim((string) $documentation->transcript_text),
            trim(strip_tags((string) $documentation->minutes_html)),
        ])->filter();

        return Str::limit($chunks->implode("\n\n-----\n\n"), 60000, "\n\n...[bahan dipotong karena melebihi 60.000 karakter]");
    }

    private function buildEventContext(UnitEvent $unitEvent, UnitEventDocumentation $documentation, Collection $photos): string
    {
        $participantCounts = [
            'total' => $unitEvent->participants()->count(),
            'peserta' => $unitEvent->participants()->where('peran', 'participant')->count(),
            'narasumber' => $unitEvent->participants()->where('peran', 'speaker')->count(),
            'panitia' => $unitEvent->participants()->where('peran', 'committee')->count(),
        ];

        $photoLines = $photos
            ->take(8)
            ->map(function ($photo, int $index) {
                $title = trim((string) ($photo->title ?: 'Foto ' . ($index + 1)));
                $caption = trim((string) $photo->caption);

                return $caption !== ''
                    ? '- ' . $title . ': ' . $caption
                    : '- ' . $title;
            })
            ->implode("\n");

        $photoBlock = $photoLines !== ''
            ? "Dokumentasi foto yang tersedia:\n{$photoLines}\n"
            : "Dokumentasi foto: belum ada foto yang diunggah.\n";

        $notesBlock = trim((string) $documentation->report_notes);
        $notesBlock = $notesBlock !== ''
            ? "Catatan tambahan admin:\n{$notesBlock}\n"
            : '';

        $schedule = $unitEvent->mulai_at
            ? $unitEvent->mulai_at->translatedFormat('d F Y H:i') . ($unitEvent->selesai_at ? ' s.d. ' . $unitEvent->selesai_at->translatedFormat('d F Y H:i') : '')
            : '-';

        return implode("\n", array_filter([
            'Nama kegiatan/event: ' . $unitEvent->judul,
            'Jenis kegiatan: ' . (UnitEvent::$jenisLabels[$unitEvent->jenis_kegiatan] ?? $unitEvent->jenis_kegiatan),
            'Metode: ' . (UnitEvent::$metodeLabels[$unitEvent->metode] ?? $unitEvent->metode),
            'Unit penyelenggara: ' . ($unitEvent->page?->judul ?: '-'),
            'Waktu pelaksanaan: ' . $schedule,
            'Tempat/Media: ' . ($unitEvent->lokasi ?: ($unitEvent->zoom_link ? 'Daring / Online' : '-')),
            'Jumlah peserta terdaftar: ' . $participantCounts['total'],
            'Rincian peserta: peserta ' . $participantCounts['peserta'] . ', narasumber ' . $participantCounts['narasumber'] . ', panitia ' . $participantCounts['panitia'] . '.',
            $photoBlock,
            $notesBlock,
        ]));
    }

    private function cleanHtmlOutput(string $rawHtml): string
    {
        $html = trim($rawHtml);

        // Remove markdown code block wrappers (e.g. ```html ... ```)
        $html = preg_replace('/^```(?:html|xml)?\s*/i', '', $html);
        $html = preg_replace('/```$/', '', $html);
        $html = trim($html);

        // Extract inner <body> content if <body> tag exists
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = $matches[1];
        }

        // Remove <style>...</style> blocks completely
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // Remove <head>...</head>, <!DOCTYPE...>, <title>...</title>
        $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);

        // Strip structural document tags
        $html = preg_replace('/<\/?(html|head|body|title)[^>]*>/i', '', $html);
        $html = trim($html);

        // Handle potential truncation: if HTML ends abruptly without closing tag, trim to last closing tag
        if ($html !== '' && !str_ends_with($html, '>') && !str_ends_with($html, '.')) {
            $lastTagPos = max(
                (int) strrpos($html, '</p>'),
                (int) strrpos($html, '</ul>'),
                (int) strrpos($html, '</ol>'),
                (int) strrpos($html, '</li>'),
                (int) strrpos($html, '</h4>'),
                (int) strrpos($html, '</h3>')
            );
            if ($lastTagPos > 0) {
                $tagEnd = strpos($html, '>', $lastTagPos);
                if ($tagEnd !== false) {
                    $html = substr($html, 0, $tagEnd + 1);
                }
            }
        }

        // Auto-close any unclosed <ul>, <ol>, or <li> tags if needed
        $openUl = substr_count($html, '<ul') - substr_count($html, '</ul>');
        for ($i = 0; $i < $openUl; $i++) {
            $html .= '</ul>';
        }
        $openOl = substr_count($html, '<ol') - substr_count($html, '</ol>');
        for ($i = 0; $i < $openOl; $i++) {
            $html .= '</ol>';
        }

        return trim($html);
    }

    private function resolvedModel(): string
    {
        return AiProviderModelResolver::chatModel(AiProviderModelResolver::activeProvider());
    }
}

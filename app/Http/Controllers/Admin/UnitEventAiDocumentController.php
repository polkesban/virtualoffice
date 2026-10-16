<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesUnitPageAccess;
use App\Http\Controllers\Controller;
use App\Models\UnitEvent;
use App\Models\UnitEventAiDocument;
use App\Models\UnitPage;
use App\Services\AI\AiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Smalot\PdfParser\Parser as PdfParser;

class UnitEventAiDocumentController extends Controller
{
    use AuthorizesUnitPageAccess;

    public function index(UnitPage $unitPage, UnitEvent $unitEvent, Request $request): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $documents = $unitEvent->aiDocuments()->get();

        $activeDocument = null;
        if ($request->filled('document_id')) {
            $activeDocument = $unitEvent->aiDocuments()->find($request->query('document_id'));
        }

        if (!$activeDocument && $documents->isNotEmpty()) {
            $activeDocument = $documents->first();
        }

        return view('admin.unit-events.ai-documents.index', compact(
            'unitPage',
            'unitEvent',
            'documents',
            'activeDocument'
        ));
    }

    public function generate(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, AiClient $ai): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $request->validate([
            'title' => 'required|string|max:255',
            'ai_prompt' => 'required|string',
            'reference_file' => 'nullable|file|mimes:txt,pdf,docx|max:10240',
        ]);

        $referenceText = '';
        $referenceFilename = null;

        if ($request->hasFile('reference_file')) {
            $file = $request->file('reference_file');
            $referenceFilename = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());

            try {
                if ($ext === 'txt') {
                    $referenceText = file_get_contents($file->getPathname());
                } elseif ($ext === 'pdf') {
                    $parser = new PdfParser();
                    $pdf = $parser->parseFile($file->getPathname());
                    $referenceText = $pdf->getText();
                } elseif ($ext === 'docx') {
                    $zip = new \ZipArchive();
                    if ($zip->open($file->getPathname()) === true) {
                        if (($index = $zip->locateName('word/document.xml')) !== false) {
                            $xmlContent = $zip->getFromIndex($index);
                            preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/', $xmlContent, $matches);
                            $referenceText = implode(" ", $matches[1]);
                        }
                        $zip->close();
                    }
                }
            } catch (\Throwable $e) {
                return back()->withErrors(['reference_file' => 'Gagal mengekstrak teks dari file referensi: ' . $e->getMessage()]);
            }
        }

        try {
            $systemPrompt = "Anda adalah asisten AI profesional untuk pembuatan dokumen institusi pendidikan tinggi (Politeknik Kesehatan Kemenkes Bandung). Buat dokumen yang profesional, terstruktur rapi, lengkap, dan siap pakai sesuai kebutuhan pengguna. Tulis dalam Bahasa Indonesia dengan format HTML yang bersih (gunakan heading h1/h2/h3, paragraf, daftar list, tabel jika diperlukan, dan styling teks tebal/miring). Jangan gunakan wrapper markdown seperti ```html ... ```.";

            $userPrompt = "Berikut adalah informasi mengenai Event/Kegiatan:\n";
            $userPrompt .= "- Judul Event: {$unitEvent->judul}\n";
            $userPrompt .= "- Jenis Kegiatan: " . (UnitEvent::$jenisLabels[$unitEvent->jenis_kegiatan] ?? $unitEvent->jenis_kegiatan) . "\n";
            $userPrompt .= "- Metode: " . (UnitEvent::$metodeLabels[$unitEvent->metode] ?? $unitEvent->metode) . "\n";
            if ($unitEvent->ringkasan) {
                $userPrompt .= "- Ringkasan: {$unitEvent->ringkasan}\n";
            }
            if ($unitEvent->lokasi) {
                $userPrompt .= "- Lokasi: {$unitEvent->lokasi}\n";
            }

            if ($referenceText) {
                $userPrompt .= "\nGunakan contoh/template dokumen referensi berikut sebagai acuan struktur, gaya bahasa, dan format konten:\n";
                $userPrompt .= "=== AWAL TEMPLATE REFERENSI ===\n" . substr($referenceText, 0, 50000) . "\n=== AKHIR TEMPLATE REFERENSI ===\n\n";
            }

            $userPrompt .= "Buat dokumen dengan judul '{$request->title}' berdasarkan detail di atas.\n";
            $userPrompt .= "Petunjuk khusus/Kebutuhan dari pengguna:\n{$request->ai_prompt}\n";

            $generatedHtml = $ai->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ], 0.6, 2500);

            // Clean any potential markdown wrapper code blocks if AI ignored system prompt instructions
            $generatedHtml = preg_replace('/^```html\s*/i', '', $generatedHtml);
            $generatedHtml = preg_replace('/```$/', '', $generatedHtml);
            $generatedHtml = trim($generatedHtml);

            $doc = UnitEventAiDocument::create([
                'unit_event_id' => $unitEvent->id,
                'title' => $request->title,
                'content_html' => $generatedHtml,
                'ai_prompt' => $request->ai_prompt,
                'reference_filename' => $referenceFilename,
                'reference_text' => $referenceText ?: null,
            ]);

            return redirect()->route('admin.unit-events.ai-documents.index', [
                $unitPage, $unitEvent, 'document_id' => $doc->id
            ])->with('success', 'Dokumen berhasil dibuat oleh AI. Anda bisa mengedit dan mengekspornya.');

        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['ai_prompt' => 'Gagal membuat dokumen dengan AI: ' . $e->getMessage()]);
        }
    }

    public function save(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $request->validate([
            'document_id' => 'required|exists:unit_event_ai_documents,id',
            'title' => 'required|string|max:255',
            'content_html' => 'required|string',
        ]);

        $doc = $unitEvent->aiDocuments()->findOrFail($request->document_id);
        $doc->update([
            'title' => $request->title,
            'content_html' => $request->content_html,
        ]);

        return redirect()->route('admin.unit-events.ai-documents.index', [
            $unitPage, $unitEvent, 'document_id' => $doc->id
        ])->with('success', 'Dokumen berhasil disimpan.');
    }

    public function export(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventAiDocument $aiDocument, Request $request)
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $format = $request->query('format', 'pdf');

        if ($format === 'docx') {
            $filename = Str::slug($aiDocument->title) . '-' . date('YmdHis');
            $content = '
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="utf-8">
                <title>' . htmlspecialchars($aiDocument->title) . '</title>
                <style>
                    body {
                        font-family: "Arial", sans-serif;
                        font-size: 12pt;
                        line-height: 1.5;
                        margin: 1in;
                    }
                    h1 { font-size: 16pt; font-weight: bold; margin-bottom: 12pt; }
                    h2 { font-size: 14pt; font-weight: bold; margin-top: 18pt; margin-bottom: 6pt; }
                    h3 { font-size: 12pt; font-weight: bold; margin-top: 12pt; margin-bottom: 6pt; }
                    p { margin-bottom: 6pt; text-align: justify; }
                    table { border-collapse: collapse; width: 100%; margin-top: 12pt; margin-bottom: 12pt; }
                    th, td { border: 1px solid #000; padding: 6pt; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                </style>
            </head>
            <body>
                ' . $aiDocument->content_html . '
            </body>
            </html>';

            return response($content)
                ->header('Content-Type', 'application/vnd.ms-word')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.doc"')
                ->header('Cache-Control', 'private, max-age=0, must-revalidate');
        }

        return view('admin.unit-events.ai-documents.print', compact('unitPage', 'unitEvent', 'aiDocument'));
    }
}

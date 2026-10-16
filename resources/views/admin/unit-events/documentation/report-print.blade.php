<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Event - {{ $unitEvent->judul }}</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 24px;
    font-family: Arial, sans-serif;
    background: #f3f4f6;
    color: #0f172a;
  }
  .no-print {
    max-width: 980px;
    margin: 0 auto 18px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: space-between;
    align-items: center;
  }
  .no-print .actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .no-print a,
  .no-print button {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    border-radius: 999px;
    padding: 9px 16px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
  }
  .sheet {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    padding: 40px 50px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
  }
  .document-title {
    margin: 0 0 30px;
    text-align: center;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    line-height: 1.4;
  }
  .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 14px;
  }
  .meta-table td {
    padding: 4px 0;
    vertical-align: top;
    line-height: 1.5;
  }
  .meta-table .label {
    width: 120px;
  }
  .meta-table .colon {
    width: 15px;
    text-align: center;
  }
  .indent-table {
    margin-left: 20px;
    margin-bottom: 10px;
    width: calc(100% - 20px);
  }
  .indent-table .label {
    width: 180px;
  }
  .content {
    font-size: 14px;
    line-height: 1.6;
    color: #000;
  }
  .content h4 {
    font-size: 14px;
    font-weight: bold;
    margin: 20px 0 10px;
    text-transform: none;
  }
  .content p {
    margin: 0 0 10px;
  }
  .content .generated-content {
    margin: 10px 0;
    padding: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 13px;
  }
  .content .generated-content h1,
  .content .generated-content h2,
  .content .generated-content h3,
  .content .generated-content h4 {
    font-size: 14px;
    margin: 10px 0 5px;
  }
  .signature-section {
    margin-top: 50px;
    font-size: 14px;
  }
  .signature-table {
    width: 100%;
  }
  .signature-table td {
    vertical-align: top;
  }
  
  .photo-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 20px;
  }
  .photo-card {
    border: 1px solid #e2e8f0;
    overflow: hidden;
    background: #fff;
    page-break-inside: avoid;
  }
  .photo-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    background: #e2e8f0;
  }
  .photo-card .body {
    padding: 10px 12px 12px;
  }
  .photo-card .title {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 4px;
  }
  .photo-card .caption {
    font-size: 10.5px;
    color: #475569;
    line-height: 1.5;
  }
  
  @media print {
    @page {
      size: A4 portrait;
      margin: 15mm 20mm;
    }
    body {
      background: #fff;
      padding: 0;
      color: #000;
    }
    .no-print {
      display: none !important;
    }
    .sheet {
      max-width: none;
      margin: 0;
      padding: 0;
      box-shadow: none;
    }
    .content .generated-content {
      background: transparent;
      border: none;
      padding: 0;
    }
    .photo-card {
      break-inside: avoid;
    }
  }
</style>
</head>
<body>
@php
    $scheduleLabel = $unitEvent->mulai_at
        ? $unitEvent->mulai_at->translatedFormat('d F Y') . ($unitEvent->selesai_at ? ' s.d. ' . $unitEvent->selesai_at->translatedFormat('d F Y') : '')
        : '—';

    $locationLabel = $unitEvent->lokasi ?: ($unitEvent->zoom_link ? 'Daring / Online' : '—');
    
    // Check if we have generated content
    $hasGeneratedContent = filled($documentation->report_html) || filled($documentation->ai_summary_html);
@endphp

<div class="no-print">
    <div>
        <strong>Format Laporan Sesuai Template</strong>
    </div>
    <div class="actions">
        <a href="{{ route('admin.unit-events.documentation.index', [$unitPage, $unitEvent]) }}">Kembali ke dokumentasi</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
</div>

<section class="sheet">
    <div class="document-title">
        LAPORAN SINGKAT KEGIATAN DENGAN/ TANPA MENGGUNAKAN UANG NEGARA
    </div>

    <table class="meta-table">
        <tbody>
            <tr>
                <td class="label">Kepada</td>
                <td class="colon">:</td>
                <td>Direktur Poltekkes Kemenkes Bandung</td>
            </tr>
            <tr>
                <td class="label">Dari</td>
                <td class="colon">:</td>
                <td>{{ $unitEvent->penandatangan_jabatan ?? '................................' }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal</td>
                <td class="colon">:</td>
                <td>{{ now()->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Perihal</td>
                <td class="colon">:</td>
                <td>Laporan Singkat Pelaksanaan Kegiatan {{ $unitEvent->judul }}</td>
            </tr>
        </tbody>
    </table>

    <div class="content">
        <h4>I. Dasar Pelaksanaan</h4>
        <p>Surat Tugas / Nota Dinas/ Surat Edaran/ Instruksi Nomor ................................ </p>

        <h4>II. Identitas Kegiatan</h4>
        <table class="meta-table indent-table">
            <tbody>
                <tr>
                    <td class="label">Nama Kegiatan</td>
                    <td class="colon">:</td>
                    <td>{{ $unitEvent->judul }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal</td>
                    <td class="colon">:</td>
                    <td>{{ $scheduleLabel }}</td>
                </tr>
                <tr>
                    <td class="label">Tempat</td>
                    <td class="colon">:</td>
                    <td>{{ $locationLabel }}</td>
                </tr>
                <tr>
                    <td class="label">Penyelenggara</td>
                    <td class="colon">:</td>
                    <td>{{ $unitPage->judul }}</td>
                </tr>
                <tr>
                    <td class="label">Penanggung Jawab</td>
                    <td class="colon">:</td>
                    <td>{{ $unitEvent->penandatangan_nama ?? '................................' }}</td>
                </tr>
            </tbody>
        </table>

        <h4>III. Tujuan Kegiatan</h4>
        <p>Jelaskan tujuan kegiatan.</p>

        <h4>IV. Ringkasan Pelaksanaan</h4>
        <p>Waktu, peserta, agenda, hasil</p>
        
        @if($hasGeneratedContent)
            <div class="generated-content">
                @if(filled($documentation->report_html))
                    {!! $documentation->report_html !!}
                @elseif(filled($documentation->ai_summary_html))
                    {!! $documentation->ai_summary_html !!}
                @endif
            </div>
        @endif

        <h4>V. Penggunaan Anggaran</h4>
        <table class="meta-table indent-table">
            <tbody>
                <tr>
                    <td class="label" style="width: 120px;">Sumber Dana</td>
                    <td class="colon">:</td>
                    <td>................................</td>
                </tr>
                <tr>
                    <td class="label">Pagu</td>
                    <td class="colon">:</td>
                    <td>................................</td>
                </tr>
                <tr>
                    <td class="label">Realisasi</td>
                    <td class="colon">:</td>
                    <td>................................</td>
                </tr>
            </tbody>
        </table>

        <h4>VI. Hasil/Output Kegiatan</h4>
        <p>Output dan manfaat</p>

        <h4>VII. Permasalahan/Kendala</h4>
        <p>Uraikan kendala bila ada.</p>

        <h4>VIII. Tindak Lanjut</h4>
        <p>Rencana tindak lanjut.</p>

        <h4>IX. Penutup</h4>
        <p>Demikian laporan singkat ini disampaikan sebagai bentuk pertanggungjawaban pelaksanaan kegiatan.</p>
    </div>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%; padding-left: 20px;">
                    <div>
                        Mengetahui,<br>
                        {{ $unitEvent->penandatangan_jabatan ?: 'Penanggung Jawab Kegiatan' }}
                    </div>
                    @if($unitEvent->penandatangan_signature_path)
                        <div style="height: 68px; margin: 4px 0; display: flex; align-items: center;">
                            <img src="{{ \App\Support\MediaStorage::url($unitEvent->penandatangan_signature_path) }}" alt="Tanda tangan Penanggung Jawab" style="max-height: 64px; max-width: 100%; object-fit: contain;">
                        </div>
                    @else
                        <div style="margin-bottom: 68px;"></div>
                    @endif
                    <div>
                        @if($unitEvent->penandatangan_nama)
                            <strong><u>{{ $unitEvent->penandatangan_nama }}</u></strong><br>
                            NIP. {{ $unitEvent->penandatangan_nip ?: '................................' }}
                        @else
                            (......................................................)<br>
                            NIP. ........................................
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($photos->isNotEmpty())
        <div style="page-break-before: always;">
            <h4 style="font-size: 14px; margin-bottom: 15px; font-family: Arial, sans-serif;">Lampiran Dokumentasi Foto</h4>
            <div class="photo-grid">
                @foreach($photos as $photo)
                    <div class="photo-card">
                        <img src="{{ \App\Support\MediaStorage::url($photo->image_path) }}" alt="{{ $photo->title ?: 'Foto kegiatan' }}">
                        <div class="body">
                            <div class="title">{{ $photo->title ?: 'Dokumentasi Kegiatan' }}</div>
                            <div class="caption">
                                @if($photo->taken_at)
                                    <div>{{ $photo->taken_at->translatedFormat('d F Y H:i') }}</div>
                                @endif
                                <div>{{ $photo->caption ?: 'Dokumentasi kegiatan/event.' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
</body>
</html>

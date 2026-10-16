<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cetak Undangan - {{ $participant->nama }}</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; padding: 20px; background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
  
  .no-print { margin-bottom: 20px; }
  .no-print button { padding: 12px 30px; font-size: 14px; font-weight: bold; background: #6d28d9; color: white; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: background 0.2s; }
  .no-print button:hover { background: #5b21b6; }

  /* Two-level (dua tingkat) invitation card design */
  .invitation-card {
      width: 100%;
      max-width: 680px;
      background: white;
      border: 2px solid #d97706;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  }

  .invitation-header-img {
      width: 100%;
      height: auto;
      display: block;
      border-bottom: 2px solid #fbbf24;
  }

  .invitation-details {
      padding: 24px;
      background: #fafaf9;
  }

  .details-grid {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 24px;
      align-items: center;
  }

  .official-badge {
      display: inline-block;
      background: #0f766e;
      color: white;
      font-size: 10px;
      font-weight: 800;
      text-transform: uppercase;
      padding: 3px 12px;
      border-radius: 9999px;
      margin-bottom: 10px;
      letter-spacing: 0.05em;
  }

  .guest-name {
      font-size: 22px;
      font-weight: 900;
      color: #0f172a;
      margin: 0 0 6px 0;
      line-height: 1.25;
  }

  .relation-text {
      font-size: 14px;
      font-style: italic;
      color: #b45309;
      font-weight: bold;
      margin: 0 0 16px 0;
  }

  .badges-row {
      display: flex;
      gap: 8px;
  }

  .badge {
      font-size: 11px;
      font-weight: bold;
      background: #e2e8f0;
      color: #475569;
      padding: 4px 12px;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
  }

  .highlight-badge {
      background: #fef3c7;
      color: #92400e;
      border-color: #fde68a;
  }

  .qr-pane {
      display: flex;
      flex-direction: column;
      align-items: center;
  }

  .qr-wrapper {
      background: white;
      padding: 8px;
      border-radius: 14px;
      border: 2px solid #fbbf24;
      box-shadow: 0 4px 10px rgba(0,0,0,0.06);
  }

  .qr-wrapper img {
      width: 140px;
      height: 140px;
      display: block;
  }

  .qr-desc {
      font-size: 9px;
      font-weight: bold;
      color: #64748b;
      margin-top: 6px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
  }

  .print-instructions {
      margin-top: 20px;
      border-top: 1px dashed #cbd5e1;
      padding-top: 16px;
      font-size: 11px;
      color: #64748b;
      line-height: 1.6;
  }

  .print-instructions ul {
      margin: 4px 0 0 0;
      padding-left: 20px;
  }

  @media print {
      body {
          padding: 0;
          background: white;
      }
      .no-print {
          display: none !important;
      }
      .invitation-card {
          border: none;
          box-shadow: none;
          max-width: 100%;
          border-radius: 0;
      }
      .qr-wrapper img {
          width: 150px;
          height: 150px;
      }
  }

  @media (max-width: 576px) {
      .details-grid {
          grid-template-columns: 1fr;
          text-align: center;
          gap: 20px;
      }
      .badges-row {
          justify-content: center;
      }
      .qr-pane {
          margin-top: 10px;
      }
  }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">🖨️ Cetak / Simpan PDF Undangan</button>
</div>

<div class="invitation-card">
    <!-- Top Level: Graphic Banner -->
    <img src="{{ asset('images/bg-undangan.jpg') }}?v={{ filemtime(public_path('images/bg-undangan.jpg')) }}" alt="Undangan Wisuda Resmi" class="invitation-header-img">

    <!-- Bottom Level: Personal Details -->
    <div class="invitation-details">
        <div class="details-grid">
            <div class="info-pane">
                <div class="official-badge">Undangan Resmi</div>
                <h1 class="guest-name">{{ $participant->nama }}</h1>
                
                @if(!empty($participant->meta['nama_wisudawan']) && !str_contains(strtolower($participant->nama), strtolower($participant->meta['nama_wisudawan'])))
                    <p class="relation-text">Orang Tua / Wali dari {{ $participant->meta['nama_wisudawan'] }}</p>
                @endif

                <div class="badges-row">
                    <span class="badge">ID: {{ $participant->kode_registrasi }}</span>
                    @if(!empty($participant->meta['nomor_kursi']))
                        <span class="badge highlight-badge">Kursi: {{ $participant->meta['nomor_kursi'] }}</span>
                    @endif
                </div>
            </div>

            <div class="qr-pane">
                <div class="qr-wrapper">
                    <img src="{{ $qrCodeUri }}" alt="QR Code">
                </div>
                <div class="qr-desc">Scan Kehadiran Panitia</div>
            </div>
        </div>

        <div class="print-instructions">
            <p><strong>📌 Petunjuk Penggunaan Undangan Wisuda:</strong></p>
            <ul>
                <li style="font-weight: bold; color: #b45309;">Satu undangan ini hanya berlaku untuk 1 (satu) orang.</li>
                <li>Silakan cetak lembar undangan ini atau simpan sebagai PDF di smartphone Anda.</li>
                <li>Tunjukkan lembar cetak atau layar HP berisi QR Code di atas kepada panitia saat registrasi di pintu masuk.</li>
                <li>Satu kode undangan berlaku untuk nama yang tertera di atas.</li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Undangan Wisuda - {{ $filterTitle ?? 'Semua' }} - {{ $unitEvent->judul }}</title>
<style>
  * { box-sizing: border-box; }
  body { 
    margin: 0; 
    padding: 0; 
    background: #f1f5f9; 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
    color: #1e293b; 
  }
  
  /* Top Toolbar for Filter & Print Actions */
  .no-print { 
    position: sticky;
    top: 0;
    z-index: 100;
    background: #ffffff;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
  }

  .toolbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .toolbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .filter-label {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .filter-select {
    padding: 9px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    cursor: pointer;
    min-width: 280px;
    max-width: 420px;
    outline: none;
    transition: all 0.2s;
  }

  .filter-select:hover, .filter-select:focus {
    border-color: #7c3aed;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
  }

  .badge-info {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ede9fe;
    color: #6d28d9;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid #ddd6fe;
  }

  .btn-print {
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 800;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 10px rgba(109, 40, 217, 0.25);
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-print:hover {
    background: linear-gradient(135deg, #6d28d9, #5b21b6);
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(109, 40, 217, 0.35);
  }

  .btn-back {
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .btn-back:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  /* Sheet Layout for 2 Cards per Landscape A4 */
  .sheet { 
    display: grid; 
    grid-template-columns: repeat(2, minmax(0, 1fr)); 
    gap: 24px; 
    max-width: 1200px; 
    margin: 0 auto; 
    padding: 0 20px 40px 20px;
    align-items: start; 
  }
  
  /* Two-level (dua tingkat) Invitation Card Design */
  .card { 
    background: #ffffff; 
    border: 2px solid #d97706; 
    border-radius: 20px; 
    overflow: hidden; 
    display: flex; 
    flex-direction: column; 
    box-shadow: 0 4px 8px -1px rgba(0, 0, 0, 0.08); 
    page-break-inside: avoid;
    break-inside: avoid;
  }
  
  .card-banner {
    width: 100%;
    height: auto;
    display: block;
    border-bottom: 2px solid #fbbf24;
  }

  .card-body {
    padding: 20px;
    background: #fafaf9;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .grid { 
    display: grid; 
    grid-template-columns: 1fr auto; 
    gap: 16px; 
    align-items: center; 
  }

  .official-badge {
    display: inline-block;
    background: #0f766e;
    color: white;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 2px 10px;
    border-radius: 9999px;
    margin-bottom: 6px;
    letter-spacing: 0.05em;
  }

  .guest-name {
    font-size: 18px;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 4px 0;
    line-height: 1.25;
  }

  .relation-text {
    font-size: 12px;
    font-style: italic;
    color: #b45309;
    font-weight: bold;
    margin: 0 0 6px 0;
  }

  .prodi-text {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    margin: 0 0 10px 0;
  }

  .badges-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }

  .badge {
    font-size: 10px;
    font-weight: bold;
    background: #e2e8f0;
    color: #475569;
    padding: 3px 10px;
    border-radius: 5px;
    border: 1px solid #cbd5e1;
  }

  .highlight-badge {
    background: #fef3c7;
    color: #92400e;
    border-color: #fde68a;
  }
  
  .qr { 
    text-align: center; 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
  }
  .qr img { 
    width: 115px; 
    height: 115px; 
    display: block; 
    background: white; 
    padding: 6px; 
    border-radius: 10px; 
    border: 2px solid #fbbf24; 
  }
  .qr span { 
    display: block; 
    margin-top: 4px; 
    font-size: 8px; 
    font-weight: 800; 
    color: #64748b; 
    text-transform: uppercase; 
    letter-spacing: 0.04em; 
  }
  
  .foot { 
    margin-top: 14px; 
    border-top: 1px dashed #cbd5e1; 
    padding-top: 10px; 
    font-size: 10px; 
    line-height: 1.4; 
    color: #57534e; 
  }
  
  @media print {
    @page { 
      size: A4 landscape; 
      margin: 8mm; 
    }
    body { 
      background: white; 
      padding: 0; 
    }
    .no-print { 
      display: none !important; 
    }
    .sheet { 
      max-width: none; 
      gap: 6mm; 
      grid-template-columns: repeat(2, minmax(0, 1fr)); 
      padding: 0;
    }
    .card { 
      border-radius: 12px; 
      break-inside: avoid; 
      page-break-inside: avoid; 
    }
    .qr img { 
      width: 26mm; 
      height: 26mm; 
    }
  }
</style>
</head>
<body>

<!-- Filter & Print Action Header Toolbar -->
<div class="no-print">
  <div class="toolbar-left">
    <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $unitEvent]) }}" class="btn-back">
      ⬅️ Data Peserta
    </a>

    <div class="filter-label">
      <span>Filter Undangan:</span>
      <select class="filter-select" onchange="window.location.href=this.value">
        <option value="{{ route('admin.unit-events.participants.invitation-print', [$unitPage, $unitEvent]) }}" {{ empty($selectedProdi) && empty($selectedJurusan) ? 'selected' : '' }}>
          🌟 Semua Undangan ({{ $totalAllUndangan ?? count($invitationRows) }} Orang)
        </option>

        <optgroup label="🏢 PILIH PER JURUSAN (Lebih Ringkas)">
          @foreach($jurusanDefinitions as $jKey => $jDef)
            <option value="{{ route('admin.unit-events.participants.invitation-print', [$unitPage, $unitEvent, 'jurusan' => $jKey]) }}" {{ $selectedJurusan === $jKey ? 'selected' : '' }}>
              {{ $jDef['label'] }} ({{ $jurusanCounts[$jKey] ?? 0 }} Undangan)
            </option>
          @endforeach
        </optgroup>

        <optgroup label="🎓 PILIH PER PROGRAM STUDI (Paling Ringan)">
          @foreach($availableProdis as $prodiName => $pCount)
            <option value="{{ route('admin.unit-events.participants.invitation-print', [$unitPage, $unitEvent, 'prodi' => $prodiName]) }}" {{ $selectedProdi === $prodiName ? 'selected' : '' }}>
              {{ $prodiName }} ({{ $pCount }} Undangan)
            </option>
          @endforeach
        </optgroup>
      </select>
    </div>

    <div class="badge-info">
      <span>📄</span>
      <span>{{ $filterTitle }} &bull; <strong>{{ count($invitationRows) }} Kartu</strong></span>
    </div>
  </div>

  <div class="toolbar-right">
    <button type="button" onclick="window.print()" class="btn-print">
      <span>🖨️</span>
      <span>Cetak / Simpan PDF ({{ count($invitationRows) }} Undangan)</span>
    </button>
  </div>
</div>

<div class="sheet">
  @forelse($invitationRows as $row)
    @php $participant = $row['participant']; @endphp
    <section class="card">
      <!-- Top Level: Graphic Banner -->
      <img src="{{ asset('images/bg-undangan.jpg') }}?v={{ filemtime(public_path('images/bg-undangan.jpg')) }}" alt="Undangan Wisuda Resmi" class="card-banner">

      <!-- Bottom Level: Personal Details -->
      <div class="card-body">
        <div class="grid">
          <div class="info-pane">
            <div class="official-badge">Undangan Resmi</div>
            <h1 class="guest-name">{{ $participant->nama }}</h1>
            
            @if(!empty($row['nama_wisudawan']) && !str_contains(strtolower($participant->nama), strtolower($row['nama_wisudawan'])))
              <p class="relation-text">Orang Tua / Wali dari {{ $row['nama_wisudawan'] }}</p>
            @endif

            @if(!empty($participant->instansi))
              <p class="prodi-text">🎓 {{ $participant->instansi }}</p>
            @endif

            <div class="badges-row">
              <span class="badge">ID: {{ $participant->kode_registrasi }}</span>
              @if(!empty($participant->identity_number))
                <span class="badge">NIM: {{ $participant->identity_number }}</span>
              @endif
              @if(!empty($row['nomor_kursi']))
                <span class="badge highlight-badge">Kursi: {{ $row['nomor_kursi'] }}</span>
              @endif
            </div>
          </div>

          <div class="qr">
            <img src="{{ $row['qr_code_data_uri'] }}" alt="QR Code Kehadiran">
            <span>Scan Registrasi</span>
          </div>
        </div>

        <div class="foot">
          <div><strong>Petunjuk Penerimaan Tamu:</strong></div>
          <div style="font-weight: bold; color: #b45309;">* Satu undangan ini hanya berlaku untuk 1 (satu) orang.</div>
          <div>1. Perlihatkan kartu undangan ini (cetak/layar HP) saat memasuki area wisuda.</div>
          <div>2. Panitia penerima tamu akan memindai QR Code di atas untuk konfirmasi kehadiran.</div>
        </div>
      </div>
    </section>
  @empty
    <section class="card" style="grid-column: span 2; min-height: 160px; align-items: center; justify-content: center; text-align: center;">
      <div style="font-weight: bold; color: #64748b; padding: 40px;">
        <p style="font-size: 24px; margin: 0 0 8px 0;">🔍</p>
        <p style="font-size: 16px; margin: 0 0 6px 0; color: #1e293b;">Tidak ada data undangan untuk filter ini.</p>
        <p style="font-size: 13px; font-weight: normal; margin: 0;">Silakan pilih Program Studi atau Jurusan lain pada dropdown di atas.</p>
      </div>
    </section>
  @endforelse
</div>

</body>
</html>

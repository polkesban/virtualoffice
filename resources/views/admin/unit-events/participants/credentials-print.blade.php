<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kredensial Peserta - {{ $unitEvent->judul }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; padding: 24px 28px; }
  h1 { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
  .meta { font-size: 10px; color: #555; margin-bottom: 14px; line-height: 1.5; }
  .warn { border: 1px solid #f59e0b; background: #fef9ec; padding: 9px 13px; margin-bottom: 18px; font-size: 10.5px; color: #783e00; border-radius: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  th { background: #e8edf2; padding: 6px 9px; font-size: 10px; font-weight: bold; text-align: left; border: 1px solid #aaa; }
  td { padding: 6px 9px; border: 1px solid #ccc; vertical-align: top; line-height: 1.5; }
  tr:nth-child(even) td { background: #f8fafc; }
  code { background: #eef2f7; padding: 1px 5px; border-radius: 3px; font-family: 'Courier New', monospace; font-size: 10.5px; letter-spacing: 0.02em; }
  .footer { margin-top: 26px; font-size: 10px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
  .no-print { text-align: center; margin-bottom: 20px; }
  .no-print button { padding: 10px 32px; font-size: 14px; font-weight: bold; background: #0ea5e9; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .no-print button:hover { background: #0284c7; }
  .small { font-size: 10px; color: #555; }
  .qr { text-align: center; }
  .qr img { width: 72px; height: 72px; display: block; margin: 0 auto 4px; }
  @media print {
    @page { size: A4 landscape; margin: 12mm; }
    .no-print { display: none !important; }
    body { padding: 10px 14px; }
    tr:nth-child(even) td { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    th { background: #e8edf2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .warn { background: #fef9ec !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<h1>🔐 Kredensial Login Peserta Event — {{ $unitEvent->judul }}</h1>
<p class="meta">
  URL Portal Peserta: <strong>{{ $loginUrl }}</strong><br>
  Unit: {{ $unitPage->judul }} &nbsp;|&nbsp; Tanggal Cetak: {{ now()->format('d F Y H:i') }}
</p>

<div class="warn">
  ⚠️ <strong>Dokumen terbatas untuk panitia dan peserta event.</strong>
  QR pada lembar ini mengarahkan peserta ke halaman daftar hadir event. Bagikan hanya kepada peserta yang berhak mengakses portal event.
</div>

<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th style="width:180px">Nama</th>
      <th style="width:170px">Unit Kerja / Jabatan</th>
      <th style="width:110px">Peran</th>
      <th style="width:140px">Akses Manual</th>
      <th style="width:150px">Password / Status</th>
      <th style="width:104px">QR Hadir</th>
      <th>Petunjuk Akses</th>
    </tr>
  </thead>
  <tbody>
    @forelse($credentialRows as $row)
      @php $participant = $row['participant']; @endphp
      <tr>
        <td>{{ $loop->iteration }}</td>
        <td>
          <strong>{{ $participant->nama }}</strong>
          <div class="small">Kategori: {{ $row['category_label'] }}</div>
          @if($participant->formatted_identity_number)
            <div class="small">NIP/NIS/NIK: {{ $participant->formatted_identity_number }}</div>
          @endif
          @if($participant->email)
            <div class="small">{{ $participant->email }}</div>
          @endif
          @if($participant->no_hp)
            <div class="small">{{ $participant->no_hp }}</div>
          @endif
        </td>
        <td>
          <div>{{ $participant->instansi ?: '—' }}</div>
          <div class="small">{{ $participant->jabatan ?: '—' }}</div>
        </td>
        <td>{{ $participant->peran_label }}</td>
        <td>
          @if($participant->usesLinkedApplicationAccess())
            <div>{{ $row['access_label'] }}</div>
          @else
            <code>{{ $row['access_label'] }}</code>
          @endif
          <div class="small">{{ $row['access_mode_label'] }}</div>
        </td>
        <td>
          @if($participant->usesLinkedApplicationAccess())
            <div>{{ $row['secret_label'] }}</div>
          @else
            <code>{{ $row['secret_label'] }}</code>
          @endif
        </td>
        <td class="qr">
          <img src="{{ $row['qr_code_data_uri'] }}" alt="QR daftar hadir {{ $participant->nama }}">
          <div class="small">Scan untuk mengisi daftar hadir</div>
        </td>
        <td>
          @foreach($row['instruction_lines'] as $line)
            <div>{{ $line }}</div>
          @endforeach
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="8" style="text-align:center; padding: 24px 12px; color: #64748b;">Belum ada peserta dengan kredensial login pada event ini.</td>
      </tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  {{ config('app.name') }} &nbsp;|&nbsp; {{ $loginUrl }}<br>
  Lembar ini dapat dibagikan kepada peserta agar masuk ke portal event dan menandatangani daftar hadir.
</div>

</body>
</html>
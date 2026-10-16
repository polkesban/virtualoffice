<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kredensial Pegawai - Poltekkes Bandung</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; padding: 24px 28px; }
  h1 { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
  .meta { font-size: 10px; color: #555; margin-bottom: 14px; }
  .warn { border: 1px solid #f59e0b; background: #fef9ec; padding: 9px 13px; margin-bottom: 18px; font-size: 10.5px; color: #783e00; border-radius: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  th { background: #e8edf2; padding: 6px 8px; font-size: 10px; font-weight: bold; text-align: left; border: 1px solid #aaa; }
  td { padding: 5px 8px; border: 1px solid #ccc; vertical-align: top; line-height: 1.45; }
  tr:nth-child(even) td { background: #f8fafc; }
  code { background: #eef2f7; padding: 1px 5px; border-radius: 3px; font-family: 'Courier New', monospace; font-size: 10.5px; letter-spacing: 0.02em; }
  .summary { margin: 12px 0 14px; font-size: 10.5px; color: #333; }
  .summary span { margin-right: 16px; }
  .footer { margin-top: 26px; font-size: 10px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
  .no-print { text-align: center; margin-bottom: 20px; }
  .no-print button { padding: 10px 32px; font-size: 14px; font-weight: bold; background: #0ea5e9; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .no-print button:hover { background: #0284c7; }
  @page { size: landscape; }
  @media print {
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

<h1>🧾 Kredensial Pegawai Poltekkes Bandung</h1>
<p class="meta">
  Portal login: <strong>{{ $pegawaiLoginUrl }}</strong>
  @if($employeeCredentialGeneratedAt)
    &nbsp;|&nbsp; Dibuat: {{ $employeeCredentialGeneratedAt->format('d/m/Y H:i') }}
  @endif
  @if($employeeCredentialFileName)
    &nbsp;|&nbsp; File: {{ $employeeCredentialFileName }}
  @endif
</p>

@if($employeeCredentialRows->isEmpty())
  <div class="warn">
    File kredensial pegawai belum tersedia. Jalankan ulang command impor pegawai dengan opsi reset password untuk menghasilkan dokumen cetak baru.
  </div>
@else
  <div class="warn">
    ⚠️ <strong>RAHASIA — Dokumen internal.</strong>
    Bagikan hanya kepada pegawai yang bersangkutan. Username utama memakai NIP Baru. Password di bawah ini adalah password sementara hasil impor terakhir dan sebaiknya diganti setelah login pertama.
  </div>

  <div class="summary">
    <span><strong>Total akun:</strong> {{ $employeeCredentialSummary['total'] }}</span>
    @if($employeeCredentialSourceLabel)
      <span><strong>Sumber DUK:</strong> {{ $employeeCredentialSourceLabel }}</span>
    @endif
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:34px">No</th>
        <th style="width:210px">Nama</th>
        <th style="width:125px">NIP Baru</th>
        <th style="width:210px">Email Akun</th>
        <th style="width:145px">Password Sementara</th>
        <th>Jabatan DUK</th>
      </tr>
    </thead>
    <tbody>
      @foreach($employeeCredentialRows as $row)
      <tr>
        <td>{{ $row['no'] }}</td>
        <td>{{ $row['name'] }}</td>
        <td>{{ $row['identity_number'] }}</td>
        <td>{{ $row['email'] }}</td>
        <td><code>{{ $row['password'] }}</code></td>
        <td>{{ $row['position'] ?: '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
@endif

<div class="footer">
  {{ config('app.name') }} | {{ $pegawaiLoginUrl }}<br>
  Dokumen ini bersifat rahasia. Hapus atau simpan di lokasi aman setelah dibagikan.
</div>

</body>
</html>
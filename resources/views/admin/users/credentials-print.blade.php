<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kredensial Admin dan Pegawai — Poltekkes Bandung</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; padding: 24px 28px; }
  h1 { font-size: 17px; font-weight: bold; margin-bottom: 3px; }
  .meta { font-size: 10px; color: #555; margin-bottom: 14px; }
  .warn { border: 1px solid #f59e0b; background: #fef9ec; padding: 9px 13px; margin-bottom: 18px; font-size: 10.5px; color: #783e00; border-radius: 4px; }
  h2 { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; color: #444; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 20px 0 8px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  th { background: #e8edf2; padding: 6px 9px; font-size: 10px; font-weight: bold; text-align: left; border: 1px solid #aaa; }
  td { padding: 5px 9px; border: 1px solid #ccc; vertical-align: top; line-height: 1.5; }
  tr:nth-child(even) td { background: #f8fafc; }
  code { background: #eef2f7; padding: 1px 5px; border-radius: 3px; font-family: 'Courier New', monospace; font-size: 10.5px; letter-spacing: 0.02em; }
  .footer { margin-top: 26px; font-size: 10px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
  .no-print { text-align: center; margin-bottom: 20px; }
  .no-print button { padding: 10px 32px; font-size: 14px; font-weight: bold; background: #0ea5e9; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .no-print button:hover { background: #0284c7; }
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

<h1>🔐 Tabel Kredensial Admin dan Pegawai — Poltekkes Bandung</h1>
<p class="meta">URL Admin: <strong>https://polkesban.ac.id/admin/login</strong> &nbsp;|&nbsp; URL Pegawai: <strong>{{ $pegawaiLoginUrl }}</strong> &nbsp;|&nbsp; Tanggal: {{ now()->format('d F Y') }}</p>

<div class="warn">
  ⚠️ <strong>RAHASIA — Dokumen internal.</strong>
  Segera ganti password setelah login pertama. Jangan bagikan ke pihak tidak berwenang.
  <br>Label <strong>Manual / tidak tersimpan</strong> berarti akun dibuat manual dan password awalnya tidak bisa ditampilkan ulang oleh sistem. Untuk pegawai, login utama menggunakan NIP Baru.
</div>

<h2>Struktural, Pusat &amp; Unit Kerja</h2>
<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th style="width:160px">Jabatan / Unit</th>
      <th style="width:190px">Email Login</th>
      <th style="width:140px">Password Sementara</th>
      <th>Menu Yang Dapat Dikelola</th>
    </tr>
  </thead>
  <tbody>
    @foreach($struktural as $credential)
    <tr>
      <td>{{ $credential['no'] }}</td>
      <td>{{ $credential['jabatan'] }}</td>
      <td>{{ $credential['email'] }}</td>
      <td><code>{{ $credential['password'] }}</code></td>
      <td>{{ $credential['menu'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<h2>Kepala Program Studi (Kaprodi)</h2>
<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th style="width:220px">Program Studi</th>
      <th style="width:210px">Email Login</th>
      <th style="width:140px">Password Sementara</th>
      <th>Menu</th>
    </tr>
  </thead>
  <tbody>
    @foreach($kaprodi as $credential)
    <tr>
      <td>{{ $credential['no'] }}</td>
      <td>{{ $credential['prodi'] }}</td>
      <td>{{ $credential['email'] }}</td>
      <td><code>{{ $credential['password'] }}</code></td>
      <td>Profil Prodi, Berita, Pengumuman</td>
    </tr>
    @endforeach
  </tbody>
</table>

<h2>Admin Halaman Publik BEM &amp; HIMA</h2>
<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th style="width:220px">Halaman Publik</th>
      <th style="width:210px">Email Login</th>
      <th style="width:140px">Password Sementara</th>
      <th>Akses</th>
    </tr>
  </thead>
  <tbody>
    @foreach($pageAdmins as $credential)
    <tr>
      <td>{{ $credential['no'] }}</td>
      <td>{{ $credential['halaman'] }}</td>
      <td>{{ $credential['email'] }}</td>
      <td><code>{{ $credential['password'] }}</code></td>
      <td>{{ $credential['menu'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<h2>Pegawai Poltekkes Bandung</h2>
@if($employeeCredentialRows->isEmpty())
<div class="warn">
  Tabel pegawai belum tersedia karena file kredensial pegawai terbaru belum ditemukan.
</div>
@else
<p class="meta">
  Total pegawai: <strong>{{ $employeeCredentialSummary['total'] }}</strong>
  @if($employeeCredentialSourceLabel)
    &nbsp;|&nbsp; Sumber DUK: <strong>{{ $employeeCredentialSourceLabel }}</strong>
  @endif
  @if($employeeCredentialFileName)
    &nbsp;|&nbsp; File: <strong>{{ $employeeCredentialFileName }}</strong>
  @endif
</p>
<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
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
  {{ config('app.name') }} &nbsp;|&nbsp; https://polkesban.ac.id/admin/login &nbsp;|&nbsp; {{ $pegawaiLoginUrl }}<br>
  Dokumen ini bersifat rahasia. Harap semua password diganti setelah login pertama.
</div>

</body>
</html>

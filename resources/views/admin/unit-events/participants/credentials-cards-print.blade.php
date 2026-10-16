<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kartu Kredensial Peserta - {{ $unitEvent->judul }}</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; padding: 18px; background: #f1f5f9; font-family: Arial, sans-serif; color: #0f172a; }
  .no-print { text-align: center; margin-bottom: 18px; }
  .no-print button { padding: 10px 32px; font-size: 14px; font-weight: bold; background: #0ea5e9; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .sheet { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; max-width: 1180px; margin: 0 auto; align-items: start; }
  .card { position: relative; min-height: 292px; background: linear-gradient(160deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #cbd5e1; border-radius: 18px; padding: 16px; overflow: hidden; display: flex; flex-direction: column; }
  .card::before { content: ''; position: absolute; inset: 0 auto auto 0; width: 100%; height: 10px; background: linear-gradient(90deg, #0f766e 0%, #2563eb 60%, #0ea5e9 100%); }
  .head { display: flex; align-items: center; gap: 10px; margin-top: 4px; }
  .head img { width: 42px; height: 42px; object-fit: contain; }
  .head-copy { flex: 1; }
  .head-copy .org { font-size: 9px; font-weight: 700; letter-spacing: 0.08em; color: #0f766e; text-transform: uppercase; }
  .head-copy .title { margin-top: 2px; font-size: 14px; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }
  .sub { margin-top: 2px; font-size: 10px; color: #475569; }
  .badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
  .badge { display: inline-flex; border-radius: 999px; background: #dcfce7; color: #166534; padding: 4px 9px; font-size: 9px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; }
  .grid { display: grid; grid-template-columns: minmax(0, 1fr) 118px; gap: 12px; margin-top: 12px; align-items: start; }
  .label { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b; }
  .value { margin-top: 3px; font-size: 12px; font-weight: 700; line-height: 1.32; }
  .value code { display: inline-block; background: #e2e8f0; border-radius: 8px; padding: 3px 6px; font-family: 'Courier New', monospace; font-size: 12px; }
  .meta { margin-top: 10px; display: grid; gap: 8px; }
  .qr { text-align: center; }
  .qr img { width: 108px; height: 108px; display: block; margin: 0 auto; background: white; padding: 5px; border-radius: 12px; border: 1px solid #cbd5e1; }
  .qr span { display: block; margin-top: 6px; font-size: 9px; line-height: 1.25; color: #475569; }
  .foot { margin-top: auto; border-top: 1px dashed #94a3b8; padding-top: 10px; font-size: 9.5px; line-height: 1.4; color: #475569; }
  .foot strong { color: #0f172a; }
  .foot-compact { display: none; }
  @media print {
    @page { size: A4 landscape; margin: 6mm; }
    body { background: white; padding: 0; }
    .no-print { display: none !important; }
    .sheet { max-width: none; gap: 5mm; align-items: stretch; }
    .card { min-height: 93mm; padding: 8px 9px 9px; border-radius: 10px; break-inside: avoid; page-break-inside: avoid; }
    .card::before { height: 5px; }
    .head { gap: 8px; margin-top: 0; }
    .head img { width: 28px; height: 28px; }
    .head-copy .org { font-size: 6.7px; letter-spacing: 0.06em; }
    .head-copy .title { font-size: 10.2px; line-height: 1.15; }
    .sub { font-size: 7px; }
    .badges { gap: 4px; margin-top: 6px; }
    .badge { padding: 2px 6px; font-size: 6.6px; }
    .grid { grid-template-columns: minmax(0, 1fr) 25mm; gap: 8px; margin-top: 8px; }
    .label { font-size: 6.4px; }
    .value { margin-top: 2px; font-size: 8.5px; line-height: 1.2; }
    .value code { font-size: 7.6px; padding: 2px 4px; border-radius: 5px; }
    .meta { margin-top: 6px; gap: 5px; }
    .qr img { width: 21mm; height: 21mm; padding: 1.1mm; border-radius: 6px; }
    .qr span { margin-top: 3px; font-size: 6.5px; }
    .foot { margin-top: 6px; padding-top: 5px; font-size: 7px; line-height: 1.25; }
    .foot-detail { display: none; }
    .foot-compact { display: block; }
  }
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="sheet">
  @forelse($credentialRows as $row)
    @php $participant = $row['participant']; @endphp
    <section class="card">
      <div class="head">
        <img src="{{ asset('images/logo-polkesban.png') }}" alt="Logo Poltekkes Bandung">
        <div class="head-copy">
          <div class="org">Kredensial Peserta Event</div>
          <div class="title">{{ $unitEvent->judul }}</div>
          <div class="sub">{{ $unitPage->judul }}</div>
        </div>
      </div>

      <div class="badges">
        <div class="badge">{{ $participant->peran_label }}</div>
        <div class="badge" style="background:#fef3c7;color:#92400e;">{{ $row['category_label'] }}</div>
        <div class="badge" style="background:#dbeafe;color:#1d4ed8;">{{ $row['access_mode_label'] }}</div>
      </div>

      <div class="grid">
        <div>
          <div class="label">Nama Peserta</div>
          <div class="value">{{ $participant->nama }}</div>

          <div class="meta">
            @if($participant->formatted_identity_number)
              <div>
                <div class="label">NIP / NIS / NIK</div>
                <div class="value"><code>{{ $participant->formatted_identity_number }}</code></div>
              </div>
            @endif
            <div>
              <div class="label">Unit Kerja / Jabatan</div>
              <div class="value">{{ $participant->instansi ?: '—' }}<br>{{ $participant->jabatan ?: '—' }}</div>
            </div>
            <div>
              <div class="label">Akses Manual</div>
              <div class="value">
                @if($participant->usesLinkedApplicationAccess())
                  {{ $row['access_label'] }}
                @else
                  <code>{{ $row['access_label'] }}</code>
                @endif
              </div>
            </div>
            <div>
              <div class="label">Password / Status</div>
              <div class="value">
                @if($participant->usesLinkedApplicationAccess())
                  {{ $row['secret_label'] }}
                @else
                  <code>{{ $row['secret_label'] }}</code>
                @endif
              </div>
            </div>
          </div>
        </div>

        <div class="qr">
          <img src="{{ $row['qr_code_data_uri'] }}" alt="QR daftar hadir {{ $participant->nama }}">
          <span>Scan untuk mengisi daftar hadir</span>
        </div>
      </div>

      <div class="foot">
        <div class="foot-detail">
          <div><strong>URL Akses:</strong> {{ $row['login_url'] }}</div>
          @foreach($row['instruction_lines'] as $line)
            <div>{{ $line }}</div>
          @endforeach
        </div>
        <div class="foot-compact">
          Scan QR untuk mengisi daftar hadir. Jika QR bermasalah, gunakan akses manual yang tercetak di kartu ini.
        </div>
      </div>
    </section>
  @empty
    <section class="card">
      <div class="title">Belum ada peserta dengan kredensial login pada event ini.</div>
    </section>
  @endforelse
</div>

</body>
</html>
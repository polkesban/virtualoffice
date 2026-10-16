<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Hadir Kerja Lembur - {{ $unitEvent->judul }}</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 24px;
    font-family: Arial, sans-serif;
    font-size: 11px;
    color: #000;
    background: #f3f4f6;
  }
  .no-print {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
    margin: 0 auto 18px;
    max-width: 980px;
  }
  .no-print .actions,
  .no-print .filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .no-print a,
  .no-print button {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #0f172a;
    border-radius: 999px;
    padding: 9px 16px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
  }
  .no-print a.active {
    border-color: #0f766e;
    background: #ccfbf1;
    color: #115e59;
  }
  .sheet {
    max-width: 980px;
    margin: 0 auto 18px;
    background: #ffffff;
    padding: 30px 40px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
  }
  .letterhead {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 22px;
    margin-bottom: 20px;
  }
  .letterhead-brand {
    width: 260px;
  }
  .letterhead-brand img {
    display: block;
    width: 100%;
    height: auto;
  }
  .letterhead-copy {
    text-align: left;
    padding-top: 4px;
  }
  .letterhead-copy .line-ministry {
    color: #10b6bb;
    font-size: 19px;
    font-weight: 800;
    line-height: 1.1;
  }
  .letterhead-copy .line-directorate {
    color: #555;
    font-size: 15px;
    font-weight: 600;
    line-height: 1.1;
  }
  .letterhead-copy .line-campus {
    margin-top: 2px;
    color: #777;
    font-size: 15px;
    font-weight: 400;
    line-height: 1.1;
  }
  .letterhead-copy .line-contact {
    margin-top: 3px;
    color: #777;
    font-size: 10px;
    line-height: 1.35;
    display: flex;
    align-items: center;
    gap: 4px;
  }
  .letterhead-copy .line-contact svg {
    width: 10px;
    height: 10px;
    fill: currentColor;
  }
  .document-title {
    margin: 10px 0 20px;
    text-align: center;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
  }
  .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }
  .meta-table td {
    padding: 3px 0;
    vertical-align: top;
    line-height: 1.45;
  }
  .meta-table .label {
    width: 120px;
  }
  .meta-table .colon {
    width: 15px;
  }
  .attendance-table {
    width: 100%;
    border-collapse: collapse;
  }
  .attendance-table th,
  .attendance-table td {
    border: 1px solid #000;
    padding: 6px;
    vertical-align: middle;
  }
  .attendance-table th {
    font-weight: bold;
    text-align: center;
    font-size: 11px;
  }
  .attendance-table .col-no {
    width: 40px;
    text-align: center;
  }
  .attendance-table .col-name {
    width: 250px;
  }
  .attendance-table .col-nip {
    width: 180px;
    text-align: center;
  }
  .attendance-table .col-date {
    width: 160px;
    text-align: center;
  }
  .attendance-table .col-start,
  .attendance-table .col-finish {
    width: 100px;
    text-align: center;
  }
  .attendance-table .col-signature {
    width: 140px;
    text-align: center;
  }
  .signature-image {
    max-width: 100%;
    max-height: 48px;
    object-fit: contain;
  }
  .signature-line {
    height: 50px;
  }
  .footer-signature {
    display: flex;
    justify-content: flex-end;
    margin-top: 40px;
  }
  .footer-signature .box {
    width: 300px;
    text-align: center;
    line-height: 1.3;
  }
  .footer-signature .space {
    height: 80px;
  }
  @media print {
    @page {
      size: A4 portrait;
      margin: 10mm;
    }
    body {
      background: #ffffff;
      padding: 0;
    }
    .no-print {
      display: none !important;
    }
    .sheet {
      margin: 0;
      padding: 0;
      max-width: none;
      box-shadow: none;
      page-break-after: always;
    }
    .sheet:last-child {
      page-break-after: auto;
    }
    .attendance-table thead {
      display: table-header-group;
    }
    .letterhead-brand {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .letterhead-copy .line-ministry {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
  }
</style>
</head>
<body>

<div class="no-print">
    <div class="actions">
        <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $unitEvent]) }}">Kembali ke peserta</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    @if($attendanceDates->count() > 1)
        <div class="filters">
            <a href="{{ request()->fullUrlWithQuery(['tanggal' => null]) }}" class="{{ $selectedDate ? '' : 'active' }}">Semua Hari</a>
            @foreach($attendanceDates as $attendanceDate)
                <a href="{{ request()->fullUrlWithQuery(['tanggal' => $attendanceDate->toDateString()]) }}" class="{{ $selectedDate === $attendanceDate->toDateString() ? 'active' : '' }}">{{ $attendanceDate->translatedFormat('d M Y') }}</a>
            @endforeach
        </div>
    @endif
</div>

@foreach($printDates as $attendanceDate)
    <section class="sheet">
        <div class="letterhead">
          <div class="letterhead-brand">
            <img src="{{ asset('images/logo-polkesban.png') }}" alt="Kemenkes Poltekkes Bandung">
          </div>
          <div class="letterhead-copy">
            <div class="line-ministry">Kementerian Kesehatan</div>
            <div class="line-directorate">Direktorat Jenderal</div>
            <div class="line-directorate">Sumber Daya Manusia Kesehatan</div>
            <div class="line-campus">Poltekkes Kemenkes Bandung</div>
            <div class="line-contact">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"/></svg>
                Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171
            </div>
            <div class="line-contact">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/></svg>
                (022) 4231627
            </div>
            <div class="line-contact">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M352 256c0 22.2-1.2 43.6-3.3 64H163.3c-2.2-20.4-3.3-41.8-3.3-64s1.2-43.6 3.3-64H348.7c2.2 20.4 3.3 41.8 3.3 64zm28.8-64H503.9c5.3 20.5 8.1 41.9 8.1 64s-2.8 43.5-8.1 64H380.8c2.1-20.6 3.2-42 3.2-64s-1.1-43.4-3.2-64zm112.6-32H376.7c-10-63.9-29.8-117.4-55.3-151.6c78.3 20.7 142 77.5 171.9 151.6zm-149.1 0H167.7c6.1-36.4 15.5-68.6 27-94.7c10.5-23.6 22.2-40.7 33.5-51.5C239.4 3.2 248.7 0 256 0s16.6 3.2 27.8 13.8c11.3 10.8 23 27.9 33.5 51.5c11.6 26 20.9 58.2 27 94.7zm-209 0H18.6C48.6 85.9 112.2 29.1 190.6 8.4C165.1 42.6 145.3 96.1 135.3 160zM8.1 192H131.2c-2.1 20.6-3.2 42-3.2 64s1.1 43.4 3.2 64H8.1C2.8 299.5 0 278.1 0 256s2.8-43.5 8.1-64zM194.7 446.6c-11.6-26-20.9-58.2-27-94.6H344.3c-6.1 36.4-15.5 68.6-27 94.6c-10.5 23.6-22.2 40.7-33.5 51.5C272.6 508.8 263.3 512 256 512s-16.6-3.2-27.8-13.8c-11.3-10.8-23-27.9-33.5-51.5zM135.3 352c10 63.9 29.8 117.4 55.3 151.6C112.2 482.9 48.6 426.1 18.6 352H135.3zm358.1 0c-29.9 74.1-93.6 130.9-171.9 151.6c25.5-34.2 45.2-87.7 55.3-151.6H493.4z"/></svg>
                https://poltekkesbandung.ac.id
            </div>
          </div>
        </div>

        <div class="document-title">DAFTAR HADIR KERJA LEMBUR</div>

        <table class="meta-table">
            <tbody>
                <tr>
                    <td class="label">Unit Kerja</td>
                    <td class="colon">:</td>
                    <td>Poltekkes Kemenkes Bandung</td>
                </tr>
                <tr>
                    <td class="label">Bulan/Tahun</td>
                    <td class="colon">:</td>
                    <td>Februari 2026</td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-bottom: 12px; font-size: 11px;">
            Berdasarkan Surat Perintah Kerja Lembur (SPKL) Nomor : PP. 03.01/F.X/2117/2026 Tanggal 8 Maret 2026
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-name">Nama Pegawai</th>
                    <th class="col-nip">NIP</th>
                    <th class="col-date">Hari/Tanggal Lembur</th>
                    <th class="col-start">Jam Mulai<br>Lembur</th>
                    <th class="col-finish">Jam Selesai<br>Lembur</th>
                    <th class="col-signature">Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $formatPrintedTime = static function (?string $time): ?string {
                        if (! $time) {
                            return null;
                        }
                        return str_replace(':', '.', substr($time, 0, 5));
                    };
                    $scheduledStartTimeLabel = $formatPrintedTime($unitEvent->attendance_print_start_time ?: $unitEvent->mulai_at?->format('H:i')) ?? '08.00';
                    $scheduledEndTimeLabel = $formatPrintedTime($unitEvent->attendance_print_end_time ?: $unitEvent->selesai_at?->format('H:i')) ?? '15.00';
                @endphp
                @forelse($participants as $participant)
                    @php 
                        $attendance = $participant->attendanceRecordFor($attendanceDate);
                    @endphp
                    <tr>
                        <td class="col-no">{{ $loop->iteration }}</td>
                        <td>{{ $participant->nama }}</td>
                        <td style="text-align: center;">{{ $participant->identity_number }}</td>
                        <td style="text-align: center;">{{ $attendanceDate->translatedFormat('l, d F Y') }}</td>
                        <td style="text-align: center;">{{ $scheduledStartTimeLabel }}</td>
                        <td style="text-align: center;">{{ $scheduledEndTimeLabel }}</td>
                        <td class="col-signature">
                            @if($attendance?->signature_path)
                                <img src="{{ \App\Support\MediaStorage::url($attendance->signature_path) }}" alt="Tanda tangan {{ $participant->nama }}" class="signature-image">
                            @elseif($attendance)
                                <div style="color: #0f766e; font-weight: bold; font-size: 11px; text-align: center;">
                                    ✓ HADIR<br>
                                    <span style="font-size: 8.5px; color: #4b5563; font-weight: normal;">{{ $attendance->signed_at ? \Carbon\Carbon::parse($attendance->signed_at)->timezone('Asia/Jakarta')->format('H.i') : '' }} WIB</span>
                                </div>
                            @else
                                <div class="signature-line"></div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 24px 12px; color: #64748b;">Belum ada peserta pada event ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-signature">
            <div class="box">
                <div>Mengetahui,</div>
                <div>{{ $unitEvent->penandatangan_jabatan ?: 'Direktur Poltekkes Kemenkes Bandung' }}</div>
                @if($unitEvent->penandatangan_signature_path)
                    <div style="height: 72px; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ \App\Support\MediaStorage::url($unitEvent->penandatangan_signature_path) }}" alt="Tanda tangan Penanggung Jawab" style="max-height: 68px; max-width: 100%; object-fit: contain;">
                    </div>
                @else
                    <div class="space"></div>
                @endif
                <div><strong><u>{{ $unitEvent->penandatangan_nama ?: 'Dr. Pramita Iriana, S.Kep., M.Biomed' }}</u></strong></div>
                <div>NIP. {{ $unitEvent->penandatangan_nip ?: '197009211996032001' }}</div>
            </div>
        </div>
    </section>
@endforeach

</body>
</html>

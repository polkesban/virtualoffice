@extends('layouts.app')
@section('title', 'Berita Acara - ' . ($event->berita_acara_judul ?? $event->judul))
@section('description', 'Berita acara kegiatan ' . ($event->berita_acara_ringkasan ?? $event->judul))

@section('content')
@php
    $formatPrintedTime = static function (?string $time): ?string {
        if (! $time) {
            return null;
        }
        return str_replace(':', '.', substr($time, 0, 5));
    };

    $startTimeLabel = $formatPrintedTime($event->attendance_print_start_time ?: $event->mulai_at?->format('H:i'));
    $endTimeLabel = $formatPrintedTime($event->attendance_print_end_time ?: $event->selesai_at?->format('H:i'));
    $timeLabel = '—';
    if ($startTimeLabel && $endTimeLabel) {
        $timeLabel = $startTimeLabel . ' - ' . $endTimeLabel . ' WIB';
    } elseif ($startTimeLabel) {
        $timeLabel = $startTimeLabel . ' WIB';
    } elseif ($endTimeLabel) {
        $timeLabel = $endTimeLabel . ' WIB';
    }

    $locationLabel = $event->lokasi ?: ($event->zoom_link ? 'Daring / Online' : 'Poltekkes Kemenkes Bandung');
    $beritaJudul = $event->berita_acara_judul ?? $event->judul;
    $beritaRingkasan = $event->berita_acara_ringkasan ?? $event->deskripsi;
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $beritaJudul }} - Berita Acara</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 24px;
    font-family: Arial, sans-serif;
    font-size: 11px;
    color: #111827;
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
  .sheet {
    max-width: 980px;
    margin: 0 auto 18px;
    background: #ffffff;
    padding: 22px 24px 28px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
  }
  .letterhead {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 22px;
  }
  .letterhead-brand {
    width: 44%;
    min-width: 280px;
  }
  .letterhead-brand img {
    display: block;
    width: 100%;
    height: auto;
  }
  .letterhead-copy {
    flex: 1;
    padding-top: 4px;
  }
  .letterhead-copy .line-ministry {
    color: #10b6bb;
    font-size: 17px;
    font-weight: 800;
    line-height: 1.08;
  }
  .letterhead-copy .line-directorate {
    color: #303030;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.08;
  }
  .letterhead-copy .line-campus {
    margin-top: 6px;
    color: #5d6369;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.1;
  }
  .letterhead-copy .line-contact {
    margin-top: 5px;
    color: #2f2f2f;
    font-size: 9px;
    line-height: 1.35;
  }
  .letterhead-copy .line-contact span {
    display: inline-block;
    min-width: 44px;
    font-weight: 700;
    color: #475569;
  }
  .separator {
    margin-top: 8px;
    border-top: 2px solid #000000;
    border-bottom: 1px solid #000000;
    height: 4px;
  }
  .document-title {
    margin: 18px 0 6px;
    text-align: center;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }
  .document-subtitle {
    text-align: center;
    font-size: 11px;
    line-height: 1.45;
    margin-bottom: 14px;
  }
  .meta-table,
  .attendance-table {
    width: 100%;
    border-collapse: collapse;
  }
  .meta-table {
    margin-bottom: 16px;
  }
  .meta-table td {
    padding: 3px 0;
    vertical-align: top;
    line-height: 1.45;
  }
  .meta-table .label {
    width: 170px;
    font-weight: 700;
  }
  .meta-table .colon {
    width: 12px;
    font-weight: 700;
  }
  .attendance-table thead th,
  .attendance-table tbody td {
    border: 1px solid #000000;
  }
  .attendance-table thead th {
    padding: 8px 6px;
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    text-align: center;
    background: #ffffff;
  }
  .attendance-table tbody td {
    padding: 7px 6px;
    vertical-align: top;
    line-height: 1.4;
  }
  .attendance-table .col-no {
    width: 40px;
    text-align: center;
  }
  .attendance-table .col-name {
    width: 180px;
  }
  .attendance-table .col-identity {
    width: 180px;
  }
  .attendance-table .col-instansi {
    width: 180px;
  }
  .attendance-table .col-status {
    width: 80px;
    text-align: center;
  }
  .attendance-table .col-signature {
    width: 100px;
    text-align: center;
  }
  .name {
    font-size: 12px;
    font-weight: 700;
  }
  .subline {
    margin-top: 2px;
    color: #334155;
    font-size: 10px;
  }
  .note {
    margin-top: 4px;
    color: #475569;
    font-size: 9.5px;
  }
  .signature-image {
    max-width: 100%;
    max-height: 50px;
    object-fit: contain;
  }
  .signature-line {
    height: 40px;
    border-bottom: 1px solid #94a3b8;
    margin: 0 4px;
  }
  .status-badge {
    display: inline-block;
    padding: 1px 6px;
    font-size: 9px;
    font-weight: 700;
    border: 1px solid #000;
  }
  .footer-signature {
    display: flex;
    justify-content: space-between;
    margin-top: 24px;
  }
  .footer-signature .box {
    width: 260px;
    text-align: center;
    line-height: 1.5;
  }
  .footer-signature .space {
    height: 72px;
  }
  @media print {
    @page {
      size: A4 portrait;
      margin: 10mm 10mm 12mm;
    }
    body {
      background: #ffffff;
      padding: 0;
      font-size: 10.5px;
    }
    .no-print {
      display: none !important;
    }
    .sheet {
      margin: 0 0 10px;
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
  }
  @media (max-width: 760px) {
    .letterhead {
      flex-direction: column;
      gap: 14px;
    }
    .letterhead-brand {
      width: 100%;
      min-width: 0;
      max-width: 360px;
    }
  }
</style>
</head>
<body>
<div class="no-print">
    <div class="actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <a href="{{ route('unit.events.show', [$page->slug, $event->slug]) }}" style="text-decoration: none;">← Kembali ke Event</a>
        @auth
            <a href="{{ route('admin.unit-events.signature', [$page, $event]) }}" style="background-color: #059669; color: #ffffff; text-decoration: none; border-color: #059669; font-weight: 700;">
                ✍️ TTD Penanggung Jawab
            </a>
        @endauth
        <button type="button" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    </div>
</div>

@php
    $attendanceGroup = $attendances->isEmpty() ? collect(['' => collect()]) : $attendances;
@endphp
@foreach($attendanceGroup as $date => $dateAttendances)
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
                <div class="line-contact"><span>Alamat</span>Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171</div>
                <div class="line-contact"><span>Telp</span>(022) 4231627</div>
                <div class="line-contact"><span>Web</span>https://poltekkesbandung.ac.id</div>
            </div>
        </div>

        <div class="separator"></div>

        <div class="document-title">BERITA ACARA</div>
        <div class="document-subtitle" style="font-size: 13px; font-weight: bold; text-transform: uppercase;">{{ $beritaJudul }}</div>

        {{-- Konten Berita Acara --}}
        @if(!empty($event->berita_acara_ringkasan))
            <div style="margin: 20px 0; font-size: 11.5px; line-height: 1.6; text-align: justify; white-space: pre-line;" class="content">{{ $event->berita_acara_ringkasan }}</div>
        @elseif($beritaRingkasan)
            <div style="margin: 20px 0; font-size: 11.5px; line-height: 1.6; text-align: justify;" class="content">
                @if(strip_tags($beritaRingkasan) === $beritaRingkasan)
                    {!! nl2br(e($beritaRingkasan)) !!}
                @else
                    {!! $beritaRingkasan !!}
                @endif
            </div>
        @endif

        <table class="meta-table">
            <tbody>
                <tr>
                    <td class="label">Nama Kegiatan</td>
                    <td class="colon">:</td>
                    <td>{{ $event->judul }}</td>
                </tr>
                <tr>
                    <td class="label">Hari / Tanggal</td>
                    <td class="colon">:</td>
                    <td>
                        @if($attendances->isNotEmpty())
                            @if($attendances->keys()->count() === 1)
                                {{ Carbon\Carbon::parse($attendances->keys()->first())->translatedFormat('l, d F Y') }}
                            @else
                                {{ Carbon\Carbon::parse($attendances->keys()->first())->translatedFormat('d F Y') }} - {{ Carbon\Carbon::parse($attendances->keys()->last())->translatedFormat('d F Y') }}
                            @endif
                        @else
                            {{ $event->mulai_at?->translatedFormat('l, d F Y') ?: '—' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Waktu</td>
                    <td class="colon">:</td>
                    <td>{{ $timeLabel }}</td>
                </tr>
                <tr>
                    <td class="label">Tempat / Media</td>
                    <td class="colon">:</td>
                    <td>{{ $locationLabel }}</td>
                </tr>
                <tr>
                    <td class="label">Unit Penyelenggara</td>
                    <td class="colon">:</td>
                    <td>{{ $page->judul }}</td>
                </tr>
                <tr>
                    <td class="label">Jumlah Hadir</td>
                    <td class="colon">:</td>
                    <td>{{ $presentCount }} orang dari {{ $totalParticipants }} peserta terdaftar</td>
                </tr>
            </tbody>
        </table>

        @if($event->berita_acara_catatan)
            <div style="margin-bottom: 16px; padding: 8px; border: 1px solid #ccc; font-size: 10px; line-height: 1.5;" class="content">
                <strong>Catatan:</strong> 
                @if(strip_tags($event->berita_acara_catatan) === $event->berita_acara_catatan)
                    {!! nl2br(e($event->berita_acara_catatan)) !!}
                @else
                    {!! $event->berita_acara_catatan !!}
                @endif
            </div>
        @endif

        {{-- Signing action button located directly below content --}}
        @if($event->berita_acara_form_aktif)
            <div class="no-print" style="margin: 24px 0; display: flex; justify-content: center; width: 100%;">
                <a href="{{ route('unit.events.attendance.direct.show', [$page->slug, $event->slug, 'form_type' => 'berita_acara']) }}" class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-green-700 shadow-md" style="background-color: #10b981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 12px; font-weight: bold; font-size: 14px;">
                    ✍️ Tanda Tangani Berita Acara Ini
                </a>
            </div>
        @endif

        <div class="document-subtitle" style="margin-bottom: 8px; font-weight: 700;">DAFTAR HADIR PESERTA</div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-name">Nama</th>
                    <th class="col-identity">Unit Kerja / Jabatan</th>
                    <th class="col-instansi">Instansi</th>
                    <th class="col-status">Status</th>
                    <th class="col-signature">Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dateAttendances as $index => $attendance)
                    <tr>
                        <td class="col-no">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                        <td class="col-name">
                            <div class="name">{{ $attendance->nama }}</div>
                            @if($attendance->no_hp)
                                <div class="subline">{{ $attendance->no_hp }}</div>
                            @endif
                            @if($attendance->notes)
                                <div class="note">{{ $attendance->notes }}</div>
                            @endif
                        </td>
                        <td class="col-identity">
                            <div>{{ $attendance->unit_kerja ?? '—' }}</div>
                            @if($attendance->jabatan)
                                <div class="subline">{{ $attendance->jabatan }}</div>
                            @endif
                        </td>
                        <td class="col-instansi">
                            <div>{{ $attendance->instansi ?? '—' }}</div>
                        </td>
                        <td class="col-status" style="text-align: center;">
                            <span class="status-badge">{{ ucfirst($attendance->attendance_status) }}</span>
                        </td>
                        <td class="col-signature">
                            @if($attendance->signature_path)
                                <img src="{{ \App\Support\MediaStorage::url($attendance->signature_path) }}" alt="Tanda tangan {{ $attendance->nama }}" class="signature-image">
                            @else
                                <div class="signature-line"></div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: #64748b; font-style: italic; font-size: 12px;">
                            Belum ada peserta yang menandatangani Berita Acara ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-signature">
            <div class="box">
                <div>Bandung, 
                    @if($attendances->isNotEmpty())
                        @if($attendances->keys()->count() === 1)
                            {{ Carbon\Carbon::parse($attendances->keys()->first())->translatedFormat('d F Y') }}
                        @else
                            {{ Carbon\Carbon::parse($attendances->keys()->first())->translatedFormat('d F Y') }} s.d. {{ Carbon\Carbon::parse($attendances->keys()->last())->translatedFormat('d F Y') }}
                        @endif
                    @else
                        {{ $event->mulai_at?->translatedFormat('d F Y') ?: '—' }}
                    @endif
                </div>
                <div>Mengetahui,</div>
                <div>{{ $event->penandatangan_jabatan ?: 'Panitia / Penanggung Jawab' }}</div>
                @if($event->penandatangan_signature_path)
                    <div style="height: 72px; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ \App\Support\MediaStorage::url($event->penandatangan_signature_path) }}" alt="Tanda tangan Penanggung Jawab" style="max-height: 68px; max-width: 100%; object-fit: contain;">
                    </div>
                @else
                    <div class="space"></div>
                @endif
                @if($event->penandatangan_nama)
                    <div><strong><u>{{ $event->penandatangan_nama }}</u></strong></div>
                    @if($event->penandatangan_nip)
                        <div>NIP. {{ $event->penandatangan_nip }}</div>
                    @endif
                @else
                    <div>..............................................</div>
                @endif
            </div>
        </div>
    </section>
@endforeach

</body>
</html>

@push('scripts')
<script>
    function exportToPDF() {
        window.print();
    }
</script>
@endpush
@endsection

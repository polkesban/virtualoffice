<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Hadir - {{ $unitEvent->judul }}</title>
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
    padding: 22px 24px 28px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
  }
  .letterhead {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
  }
  .letterhead-brand {
    flex: 0 0 210px;
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
    width: 250px;
  }
  .attendance-table .col-identity {
    width: 230px;
  }
  .attendance-table .col-start,
  .attendance-table .col-finish {
    width: 88px;
    text-align: center;
  }
  .attendance-table .col-signature {
    width: 190px;
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
  .time-cell {
    text-align: center;
  }
  .time-value {
    min-height: 16px;
    font-weight: 700;
    font-size: 11px;
  }
  .time-placeholder {
    height: 18px;
    border-bottom: 1px solid #94a3b8;
    margin: 3px 6px 0;
  }
  .signature-image {
    max-width: 100%;
    max-height: 68px;
    object-fit: contain;
  }
  .signature-line {
    height: 50px;
    border-bottom: 1px solid #94a3b8;
    margin: 0 8px;
  }
  .footer-signature {
    display: flex;
    justify-content: flex-end;
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
      page-break-after: always !important;
      break-after: page !important;
    }
    .sheet:last-child {
      page-break-after: auto !important;
      break-after: auto !important;
    }
    .attendance-table thead {
      display: table-header-group;
    }
  }
  @media screen and (max-width: 760px) {
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

  /* Joint PBM Classes Styling */
  @page landscape-page {
    size: A4 landscape;
    margin: 8mm;
  }
  .sheet-landscape {
    page: landscape-page;
    max-width: 1080px !important;
  }
  @media print {
    .sheet-landscape {
      margin: 0;
      padding: 0;
      max-width: none !important;
      box-shadow: none;
      page-break-after: always !important;
      break-after: page !important;
    }
    .sheet-landscape:last-child {
      page-break-after: auto !important;
      break-after: auto !important;
    }
  }
  .gabungan-attendance-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 8px;
  }
  .gabungan-attendance-table th,
  .gabungan-attendance-table td {
    border: 1px solid #000000;
    padding: 3px 2px;
    height: 18px;
  }
  .gabungan-attendance-table th {
    background-color: #f8fafc;
    text-align: center;
    font-weight: bold;
  }
  .pembimbing-header-row td {
    background-color: #f1f5f9;
  }
</style>
</head>
<body>
@php
    $formatPrintedTime = static function (?string $time): ?string {
        if (! $time) {
            return null;
        }

        return str_replace(':', '.', substr($time, 0, 5));
    };

    $scheduledStartTimeLabel = $formatPrintedTime($unitEvent->attendance_print_start_time ?: $unitEvent->mulai_at?->format('H:i'));
    $scheduledEndTimeLabel = $formatPrintedTime($unitEvent->attendance_print_end_time ?: $unitEvent->selesai_at?->format('H:i'));

    $timeLabel = '—';

    if ($scheduledStartTimeLabel && $scheduledEndTimeLabel) {
        $timeLabel = $scheduledStartTimeLabel . ' - ' . $scheduledEndTimeLabel . ' WIB';
    } elseif ($scheduledStartTimeLabel) {
        $timeLabel = $scheduledStartTimeLabel . ' WIB';
    } elseif ($scheduledEndTimeLabel) {
        $timeLabel = $scheduledEndTimeLabel . ' WIB';
    }

    $locationLabel = $unitEvent->lokasi ?: ($unitEvent->zoom_link ? 'Daring / Online' : '—');
@endphp

<div class="no-print">
    <div class="actions">
        <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $unitEvent]) }}">Kembali ke peserta</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    @if($attendanceDates->count() > 1)
        <div class="filters">
            <a href="{{ route('admin.unit-events.participants.attendance-print', [$unitPage, $unitEvent]) }}" class="{{ $selectedDate ? '' : 'active' }}">Semua Hari</a>
            @foreach($attendanceDates as $attendanceDate)
                <a href="{{ route('admin.unit-events.participants.attendance-print', [$unitPage, $unitEvent, 'tanggal' => $attendanceDate->toDateString()]) }}" class="{{ $selectedDate === $attendanceDate->toDateString() ? 'active' : '' }}">{{ $attendanceDate->translatedFormat('d M Y') }}</a>
            @endforeach
        </div>
    @endif
</div>

@if($unitEvent->jenis_kegiatan === 'pbm' && ($unitEvent->sub_jenis_kegiatan === 'gabungan' || $unitEvent->sub_jenis_kegiatan === 'praktik' || stripos($unitEvent->judul, 'praktik') !== false || stripos($unitEvent->judul, 'magang') !== false))
    @php
        $studentsAll = $participants->where('peran', 'participant')->values();
        $pembimbingsAll = $participants->whereNotIn('peran', ['participant'])->values();

        $groupedStudents = $studentsAll->groupBy(fn($item) => $item->tempat_praktik ?: 'Lainnya');
        $groupedPembimbings = $pembimbingsAll->groupBy(fn($item) => $item->tempat_praktik ?: 'Lainnya');
        $locations = $groupedStudents->keys()->values();
        if ($locations->isEmpty()) {
            $locations = collect(['Lainnya']);
        }

        // Chunk attendance dates into weekly groups (7 days per sheet)
        $weeklyDateChunks = $attendanceDates->chunk(7)->values();
        $totalWeeks = $weeklyDateChunks->count();
        if ($totalWeeks === 0) {
            $weeklyDateChunks = collect([collect()]);
            $totalWeeks = 1;
        }
    @endphp

    @foreach($locations as $locName)
        @php
            $students = $groupedStudents->get($locName, collect())->values();
            $pembimbings = $groupedPembimbings->get($locName, collect())->values();
            if ($pembimbings->isEmpty() && $locName !== 'Lainnya') {
                $pembimbings = $groupedPembimbings->get('Lainnya', collect())->values();
            }

            // If still no pembimbing participants, derive from students' nama_pembimbing field
            if ($pembimbings->isEmpty()) {
                $pembimbingNames = $students->pluck('nama_pembimbing')
                    ->filter(fn($n) => filled($n))
                    ->unique()
                    ->values();
            } else {
                $pembimbingNames = collect();
            }

            $waktu = $unitEvent->mulai_at ? $unitEvent->mulai_at->translatedFormat('d F Y') : '';
            if ($unitEvent->selesai_at) {
                $waktu .= ' s.d ' . $unitEvent->selesai_at->translatedFormat('d F Y');
            }

            $kaprodi = $participants->first(fn($p) => $p->peran === 'kaprodi');
            $koordinator = $participants->first(fn($p) => $p->peran === 'koordinator_matakuliah');

            $kaprodiNama = $kaprodi ? $kaprodi->nama : ($unitEvent->penandatangan_nama ?: 'Sri Mulyati, SST., M. Keb');
            $kaprodiNip = $kaprodi ? $kaprodi->identity_number : ($unitEvent->penandatangan_nip ?: '198105092005012000');

            $koordinatorNama = $koordinator ? $koordinator->nama : ($unitEvent->koordinator_nama ?: 'Yulidar Yanti, SST., M. Keb');
            $koordinatorNip = $koordinator ? $koordinator->identity_number : ($unitEvent->koordinator_nip ?: '197707112005012000');
        @endphp

        @foreach($weeklyDateChunks as $weekIdx => $weekDates)
            @php
                $weekDates = $weekDates->values();
                $colCount = min($weekDates->count(), 7);
                if ($colCount === 0) $colCount = 7;
                $weekLabel = $totalWeeks > 1 ? ' — Minggu ' . ($weekIdx + 1) : '';
            @endphp

            <section class="sheet sheet-landscape">
                <div class="letterhead">
                    <div class="letterhead-brand">
                        <img src="{{ asset('images/logo-polkesban.png') }}" alt="Kemenkes Poltekkes Bandung">
                    </div>
                    <div class="letterhead-copy">
                        <div class="line-ministry">Kementerian Kesehatan</div>
                        <div class="line-directorate">Poltekkes Bandung</div>
                        <div class="line-campus" style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                            📍 Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171<br>
                            ☎ (022) 4231627<br>
                            🌐 https://poltekkesbandung.ac.id
                        </div>
                    </div>
                </div>

                <div class="separator"></div>

                <div class="meta-container" style="display: flex; justify-content: space-between; margin-top: 15px; font-size: 9px; font-weight: bold; text-transform: uppercase;">
                    <table style="border-collapse: collapse; border: none; width: 48%;">
                        <tr style="border: none;">
                            <td style="border: none; padding: 2px 0; width: 140px;">NAMA MATA KULIAH</td>
                            <td style="border: none; padding: 2px 0; width: 10px;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: normal;">{{ $unitEvent->judul }}</td>
                        </tr>
                        <tr style="border: none;">
                            <td style="border: none; padding: 2px 0;">Waktu Praktik</td>
                            <td style="border: none; padding: 2px 0;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: normal;">{{ $waktu }}</td>
                        </tr>
                        <tr style="border: none;">
                            <td style="border: none; padding: 2px 0;">Tempat Praktik</td>
                            <td style="border: none; padding: 2px 0;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: normal;">{{ $locName }}</td>
                        </tr>
                        <tr style="border: none;">
                            <td style="border: none; padding: 2px 0;">Semester</td>
                            <td style="border: none; padding: 2px 0;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: normal;">{{ $unitEvent->semester }}</td>
                        </tr>
                    </table>
                    <table style="border-collapse: collapse; border: none; width: 48%;">
                        <tr style="border: none;">
                            <td style="border: none; padding: 2px 0; width: 140px;">Program Studi</td>
                            <td style="border: none; padding: 2px 0; width: 10px;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: normal;">{{ $unitPage->programStudi->nama ?? '—' }}</td>
                        </tr>
                        <tr style="border: none;">
                            <td style="border: none; padding: 2px 0;">Tahun Akademik</td>
                            <td style="border: none; padding: 2px 0;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: normal;">{{ $unitEvent->tahun_akademik }}</td>
                        </tr>
                        @if($totalWeeks > 1)
                        <tr style="border: none;">
                            <td style="border: none; padding: 2px 0;">Lembar Minggu</td>
                            <td style="border: none; padding: 2px 0;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: normal;">Minggu {{ $weekIdx + 1 }} dari {{ $totalWeeks }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <table class="gabungan-attendance-table">
                    <thead>
                        <tr>
                            <th rowspan="3" style="width: 25px; vertical-align: middle;">NO</th>
                            <th rowspan="3" style="width: 140px; vertical-align: middle; text-align: left; padding-left: 5px;">NAMA</th>
                            @foreach($weekDates as $d => $attDate)
                                <th colspan="4" style="font-size: 7px; padding: 2px 1px;">{{ $attDate->translatedFormat('D, d/m') }}</th>
                            @endforeach
                            @for($d = $weekDates->count(); $d < 7; $d++)
                                <th colspan="4" style="font-size: 7px; padding: 2px 1px; color: #cbd5e1;">Hari/ Tanggal</th>
                            @endfor
                        </tr>
                        <tr>
                            @for($d = 0; $d < 7; $d++)
                                <th colspan="2" style="font-size: 7px; padding: 1px 0;">Datang</th>
                                <th colspan="2" style="font-size: 7px; padding: 1px 0;">Pulang</th>
                            @endfor
                        </tr>
                        <tr>
                            @for($d = 0; $d < 7; $d++)
                                <th style="font-size: 6.5px; padding: 1px 0; font-weight: normal; width: 22px;">Jam</th>
                                <th style="font-size: 6.5px; padding: 1px 0; font-weight: normal; width: 22px;">Paraf</th>
                                <th style="font-size: 6.5px; padding: 1px 0; font-weight: normal; width: 22px;">Jam</th>
                                <th style="font-size: 6.5px; padding: 1px 0; font-weight: normal; width: 22px;">Paraf</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $sIdx => $student)
                            <tr>
                                <td style="text-align: center; font-size: 8px;">{{ $sIdx + 1 }}</td>
                                <td style="font-size: 8px; font-weight: bold; padding-left: 5px;">{{ $student->nama }}</td>
                                @for($d = 0; $d < 7; $d++)
                                    @php
                                        $attDate = $weekDates->get($d);
                                        $attendance = null;
                                        $jamDatang = '';
                                        $jamPulang = '';
                                        if ($student && $attDate) {
                                            $dateStr = $attDate->toDateString();
                                            $attendance = $student->attendances->first(fn($a) => $a->attendance_date->toDateString() === $dateStr);
                                            if ($attendance) {
                                                $shift = collect($unitEvent->shifts ?? [])->first(fn($s) => $s['name'] === $attendance->shift_name);
                                                if ($shift) {
                                                    $jamDatang = str_replace(':', '.', substr($shift['start'], 0, 5));
                                                    $jamPulang = str_replace(':', '.', substr($shift['end'], 0, 5));
                                                } else {
                                                    $jamDatang = $scheduledStartTimeLabel ?: '';
                                                    $jamPulang = $scheduledEndTimeLabel ?: '';
                                                }
                                            }
                                        }
                                    @endphp
                                    <!-- Datang Jam -->
                                    <td style="text-align: center; font-size: 7px; padding: 1px 0;">
                                        {{ $attendance && $attendance->signed_at ? $jamDatang : '' }}
                                    </td>
                                    <!-- Datang Paraf -->
                                    <td style="text-align: center; vertical-align: middle; padding: 1px;">
                                        @if($attendance && $attendance->signature_path)
                                            <img src="{{ \App\Support\MediaStorage::url($attendance->signature_path) }}" style="max-height: 20px; max-width: 100%; object-fit: contain; display: block; margin: 0 auto;">
                                        @elseif($attendance && $attendance->signed_at)
                                            ✓
                                        @endif
                                    </td>
                                    <!-- Pulang Jam -->
                                    <td style="text-align: center; font-size: 7px; padding: 1px 0;">
                                        {{ $attendance && $attendance->check_out_at ? $jamPulang : '' }}
                                    </td>
                                    <!-- Pulang Paraf -->
                                    <td style="text-align: center; vertical-align: middle; padding: 1px;">
                                        @if($attendance && $attendance->check_out_signature_path)
                                            <img src="{{ \App\Support\MediaStorage::url($attendance->check_out_signature_path) }}" style="max-height: 20px; max-width: 100%; object-fit: contain; display: block; margin: 0 auto;">
                                        @elseif($attendance && $attendance->check_out_at)
                                            ✓
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach

                        <tr class="pembimbing-header-row">
                            <td colspan="2" style="font-size: 8px; font-weight: bold; background-color: #f1f5f9; padding-left: 5px; height: 16px;">PEMBIMBING</td>
                            @for($d = 0; $d < 7; $d++)
                                <td style="text-align: center; font-size: 6px; font-weight: bold; background-color: #f1f5f9; padding: 1px 0;">Jam</td>
                                <td style="text-align: center; font-size: 6px; font-weight: bold; background-color: #f1f5f9; padding: 1px 0;">Paraf</td>
                                <td style="text-align: center; font-size: 6px; font-weight: bold; background-color: #f1f5f9; padding: 1px 0;">Jam</td>
                                <td style="text-align: center; font-size: 6px; font-weight: bold; background-color: #f1f5f9; padding: 1px 0;">Paraf</td>
                            @endfor
                        </tr>

                        @if($pembimbings->isNotEmpty())
                            {{-- Pembimbing from participant records (have attendance data) --}}
                            @foreach($pembimbings as $pIdx => $pb)
                                <tr>
                                    <td style="text-align: center; font-size: 8px;">{{ $pIdx + 1 }}</td>
                                    <td style="font-size: 8px; font-weight: bold; padding-left: 5px;">{{ $pb->nama }}</td>
                                    @for($d = 0; $d < 7; $d++)
                                        @php
                                            $attDate = $weekDates->get($d);
                                            $attendance = null;
                                            $jamDatang = '';
                                            $jamPulang = '';
                                            if ($pb && $attDate) {
                                                $dateStr = $attDate->toDateString();
                                                $attendance = $pb->attendances->first(fn($a) => $a->attendance_date->toDateString() === $dateStr);
                                                if ($attendance) {
                                                    $shift = collect($unitEvent->shifts ?? [])->first(fn($s) => $s['name'] === $attendance->shift_name);
                                                    if ($shift) {
                                                        $jamDatang = str_replace(':', '.', substr($shift['start'], 0, 5));
                                                        $jamPulang = str_replace(':', '.', substr($shift['end'], 0, 5));
                                                    } else {
                                                        $jamDatang = $scheduledStartTimeLabel ?: '';
                                                        $jamPulang = $scheduledEndTimeLabel ?: '';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <!-- Datang Jam -->
                                        <td style="text-align: center; font-size: 7px; padding: 1px 0;">
                                            {{ $attendance && $attendance->signed_at ? $jamDatang : '' }}
                                        </td>
                                        <!-- Datang Paraf -->
                                        <td style="text-align: center; vertical-align: middle; padding: 1px;">
                                            @if($attendance && $attendance->signature_path)
                                                <img src="{{ \App\Support\MediaStorage::url($attendance->signature_path) }}" style="max-height: 20px; max-width: 100%; object-fit: contain; display: block; margin: 0 auto;">
                                            @elseif($attendance && $attendance->signed_at)
                                                ✓
                                            @endif
                                        </td>
                                        <!-- Pulang Jam -->
                                        <td style="text-align: center; font-size: 7px; padding: 1px 0;">
                                            {{ $attendance && $attendance->check_out_at ? $jamPulang : '' }}
                                        </td>
                                        <!-- Pulang Paraf -->
                                        <td style="text-align: center; vertical-align: middle; padding: 1px;">
                                            @if($attendance && $attendance->check_out_signature_path)
                                                <img src="{{ \App\Support\MediaStorage::url($attendance->check_out_signature_path) }}" style="max-height: 20px; max-width: 100%; object-fit: contain; display: block; margin: 0 auto;">
                                            @elseif($attendance && $attendance->check_out_at)
                                                ✓
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        @elseif($pembimbingNames->isNotEmpty())
                            {{-- Pembimbing names derived from student records (name only, no attendance data) --}}
                            @foreach($pembimbingNames as $pIdx => $pbName)
                                <tr>
                                    <td style="text-align: center; font-size: 8px;">{{ $pIdx + 1 }}</td>
                                    <td style="font-size: 8px; font-weight: bold; padding-left: 5px;">{{ $pbName }}</td>
                                    @for($d = 0; $d < 7; $d++)
                                        <td style="text-align: center; font-size: 7px; padding: 1px 0;"></td>
                                        <td style="text-align: center; vertical-align: middle; padding: 1px;"></td>
                                        <td style="text-align: center; font-size: 7px; padding: 1px 0;"></td>
                                        <td style="text-align: center; vertical-align: middle; padding: 1px;"></td>
                                    @endfor
                                </tr>
                            @endforeach
                        @else
                            {{-- Fallback: at least 2 empty rows for manual filling --}}
                            @for($pIdx = 0; $pIdx < 2; $pIdx++)
                                <tr>
                                    <td style="text-align: center; font-size: 8px;">{{ $pIdx + 1 }}</td>
                                    <td style="font-size: 8px; font-weight: bold; padding-left: 5px;"></td>
                                    @for($d = 0; $d < 7; $d++)
                                        <td style="text-align: center; font-size: 7px; padding: 1px 0;"></td>
                                        <td style="text-align: center; vertical-align: middle; padding: 1px;"></td>
                                        <td style="text-align: center; font-size: 7px; padding: 1px 0;"></td>
                                        <td style="text-align: center; vertical-align: middle; padding: 1px;"></td>
                                    @endfor
                                </tr>
                            @endfor
                        @endif
                    </tbody>
                </table>

                <div style="margin-top: 15px; display: flex; justify-content: space-around; font-size: 8.5px; line-height: 1.4;">
                    <div style="width: 250px; text-align: center;">
                        <div style="height: 12px;"></div>
                        <div>Ketua Program Studi</div>
                        @if($unitEvent->penandatangan_signature_path)
                            <div style="height: 48px; display: flex; align-items: center; justify-content: center;">
                                <img src="{{ \App\Support\MediaStorage::url($unitEvent->penandatangan_signature_path) }}" alt="Tanda tangan Kaprodi" style="max-height: 44px; max-width: 100%; object-fit: contain;">
                            </div>
                        @else
                            <div style="height: 48px;"></div>
                        @endif
                        <div><strong><u>{{ $kaprodiNama }}</u></strong></div>
                        <div>NIP. {{ $kaprodiNip }}</div>
                    </div>
                    <div style="width: 250px; text-align: center;">
                        <div>Bandung, {{ now()->translatedFormat('d F Y') }}</div>
                        <div>Koordinator Praktik</div>
                        <div style="height: 48px;"></div>
                        <div><strong><u>{{ $koordinatorNama }}</u></strong></div>
                        <div>NIP. {{ $koordinatorNip }}</div>
                    </div>
                </div>
            </section>
        @endforeach
    @endforeach
@else
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
            <div class="line-contact"><span>Alamat</span>Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171</div>
            <div class="line-contact"><span>Telp</span>(022) 4231627</div>
            <div class="line-contact"><span>Web</span>https://poltekkesbandung.ac.id</div>
          </div>
            </div>

            <div class="separator"></div>

            <div class="document-title">Daftar Hadir</div>
            <div class="document-subtitle">{{ $unitPage->judul }} · {{ $attendanceDate->translatedFormat('l, d F Y') }}</div>

            <table class="meta-table">
                <tbody>
                    <tr>
                        <td class="label">Nama Kegiatan</td>
                        <td class="colon">:</td>
                        <td>{{ $unitEvent->judul }}</td>
                    </tr>
                    <tr>
                        <td class="label">Hari / Tanggal</td>
                        <td class="colon">:</td>
                        <td>{{ $attendanceDate->translatedFormat('l, d F Y') }}</td>
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
                        <td>{{ $unitPage->judul }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-name">Nama</th>
                        <th class="col-identity">Unit Kerja / Jabatan</th>
                        <th class="col-start">Jam Mulai</th>
                        <th class="col-finish">Jam Selesai</th>
                        <th class="col-signature">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $participant)
                        @php $attendance = $participant->attendanceRecordFor($attendanceDate); @endphp
                        <tr>
                            <td class="col-no">{{ $loop->iteration }}</td>
                            <td class="col-name">
                                <div class="name">{{ $participant->nama }}</div>
                                @if($participant->formatted_identity_number)
                                    <div class="subline">NIP/NIS/NIK: {{ $participant->formatted_identity_number }}</div>
                                @endif
                                <div class="subline">{{ $participant->peran_label }}</div>
                                @if($attendance?->notes)
                                    <div class="note">Catatan: {{ $attendance->notes }}</div>
                                @endif
                            </td>
                            <td class="col-identity">
                                <div>{{ $participant->instansi ?: '—' }}</div>
                                <div class="subline">{{ $participant->jabatan ?: '—' }}</div>
                            </td>
                            <td class="col-start time-cell">
                              @if($scheduledStartTimeLabel)
                                <div class="time-value">{{ $scheduledStartTimeLabel }}</div>
                                @else
                                    <div class="time-placeholder"></div>
                                @endif
                            </td>
                            <td class="col-finish time-cell">
                              @if($scheduledEndTimeLabel)
                                <div class="time-value">{{ $scheduledEndTimeLabel }}</div>
                              @else
                                <div class="time-placeholder"></div>
                              @endif
                            </td>
                            <td class="col-signature">
                                @if($attendance?->signature_path)
                                    <img src="{{ \App\Support\MediaStorage::url($attendance->signature_path) }}" alt="Tanda tangan {{ $participant->nama }}" class="signature-image">
                                @elseif($attendance)
                                    <div style="color: #0f766e; font-weight: bold; font-size: 11px; text-align: center; padding-top: 10px;">
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
                            <td colspan="6" style="text-align:center; padding: 24px 12px; color: #64748b;">Belum ada peserta pada event ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 10px; font-size: 8.5px; color: #4b5563; font-style: italic; line-height: 1.4;">
                * Catatan: Tanda centang (✓ HADIR) menunjukkan bahwa kehadiran peserta/undangan telah dicatat secara digital melalui pemindaian (scan) QR Code undangan oleh panitia pada waktu yang tertera.
            </div>

            <div class="footer-signature">
                <div class="box">
                    <div>Bandung, {{ $attendanceDate->translatedFormat('d F Y') }}</div>
                    <div>Mengetahui,</div>
                    <div>{{ $unitEvent->penandatangan_jabatan ?: 'Panitia / Penanggung Jawab' }}</div>
                    @if($unitEvent->penandatangan_signature_path)
                        <div style="height: 72px; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ \App\Support\MediaStorage::url($unitEvent->penandatangan_signature_path) }}" alt="Tanda tangan Penanggung Jawab" style="max-height: 68px; max-width: 100%; object-fit: contain;">
                        </div>
                    @else
                        <div class="space"></div>
                    @endif
                    @if($unitEvent->penandatangan_nama)
                        <div><strong><u>{{ $unitEvent->penandatangan_nama }}</u></strong></div>
                        @if($unitEvent->penandatangan_nip)
                            <div>NIP. {{ $unitEvent->penandatangan_nip }}</div>
                        @endif
                    @else
                        <div>..............................................</div>
                    @endif
                </div>
            </div>
        </section>
    @endforeach
@endif

</body>
</html>

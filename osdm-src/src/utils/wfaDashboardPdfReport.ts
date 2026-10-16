import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { WfaSubmission } from '../types';
import { renderOfficialLandscapeKopSurat } from './officialLandscapeKopSurat';

// Format tanggal panjang Indonesia: "07 Oktober 2026"
const formatDateIndoLong = (dateStr: string): string => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
};

export interface GenerateDashboardPdfOptions {
  submissions: WfaSubmission[];
  periodMode: 'semua' | 'harian' | 'bulanan' | 'tahunan';
  periodLabel: string;
  selectedMonth?: number;
  selectedYear?: number;
  customFilename?: string;
  customKopSuratImage?: string | null;
  // Kustomisasi Penandatangan & TTD Digital PNG
  signerCityDate?: string;
  signerTitle?: string;
  signerName?: string;
  signerInstitution?: string;
  signerSignaturePng?: string | null;
}

const MONTH_NAMES = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

/**
 * Generate dan Download Laporan Eksekutif PDF Dashboard & Statistik Analitik WFA
 * Menghasilkan dokumen A4 Landscape yang proporsional, rapi, dan bebas tabrakan:
 * - Lembar 1: Ringkasan Eksekutif, KPI Cards, Komposisi Validasi, Distribusi Unit Kerja, Distribusi Wilayah & Kategori, serta Lembar Pengesahan.
 * - Lembar 2 (Lampiran): Analisis Tren Kronologis Harian / Bulanan dengan tabel rincian yang luas.
 */
export async function generateWfaDashboardAnalyticsPdf({
  submissions,
  periodMode,
  periodLabel,
  selectedMonth = new Date().getMonth(),
  selectedYear = new Date().getFullYear(),
  customFilename,
  customKopSuratImage,
  signerCityDate,
  signerTitle,
  signerName,
  signerInstitution,
  signerSignaturePng,
}: GenerateDashboardPdfOptions): Promise<void> {
  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a4',
  });

  const pageWidth = doc.internal.pageSize.getWidth(); // 297mm
  const pageHeight = doc.internal.pageSize.getHeight(); // 210mm
  const margin = 14;
  const contentWidth = pageWidth - margin * 2; // 269mm

  // 1. DATA AGGREGATION & METRICS
  const total = submissions.length;
  const valid = submissions.filter((s) => s.status === 'Valid').length;
  const pending = submissions.filter((s) => s.status === 'Menunggu Validasi').length;
  const rejected = submissions.filter((s) => s.status === 'Ditolak').length;
  const uniqueEmployees = new Set(submissions.map((s) => s.nip || s.employeeName)).size;

  const validPct = total > 0 ? ((valid / total) * 100).toFixed(1) : '0';
  const pendingPct = total > 0 ? ((pending / total) * 100).toFixed(1) : '0';
  const rejectedPct = total > 0 ? ((rejected / total) * 100).toFixed(1) : '0';

  // Unit Kerja Stats
  const unitKerjaMap = new Map<string, { total: number; valid: number; pending: number; rejected: number }>();
  submissions.forEach((s) => {
    const unit = (s.unitKerja || 'Unit Tidak Terdata').trim();
    const curr = unitKerjaMap.get(unit) || { total: 0, valid: 0, pending: 0, rejected: 0 };
    curr.total += 1;
    if (s.status === 'Valid') curr.valid += 1;
    else if (s.status === 'Menunggu Validasi') curr.pending += 1;
    else if (s.status === 'Ditolak') curr.rejected += 1;
    unitKerjaMap.set(unit, curr);
  });
  const unitKerjaStats = Array.from(unitKerjaMap.entries())
    .map(([name, stat]) => ({ name, ...stat }))
    .sort((a, b) => b.total - a.total);

  // Wilayah Stats
  const wilayahMap = new Map<string, { total: number; valid: number; rejected: number }>();
  submissions.forEach((s) => {
    const loc = (s.lokasiKegiatan || 'Lainnya').trim();
    const curr = wilayahMap.get(loc) || { total: 0, valid: 0, rejected: 0 };
    curr.total += 1;
    if (s.status === 'Valid') curr.valid += 1;
    else if (s.status === 'Ditolak') curr.rejected += 1;
    wilayahMap.set(loc, curr);
  });
  const wilayahStats = Array.from(wilayahMap.entries())
    .map(([name, stat]) => ({ name, ...stat }))
    .sort((a, b) => b.total - a.total);

  // Jenis WFA Stats (statusWfa)
  const jenisFull = submissions.filter((s) => !s.statusWfa || s.statusWfa.includes('Datang & WFA Pulang') || s.statusWfa.includes('Full Day') || s.statusWfa.includes('Penuh')).length;
  const jenisDatang = submissions.filter((s) => s.statusWfa === 'WFA Datang' || s.statusWfa === 'Datang').length;
  const jenisPulang = submissions.filter((s) => s.statusWfa === 'WFA Pulang' || s.statusWfa === 'Pulang').length;

  // Trend Data for Annex / Lampiran
  interface TrendRow {
    label: string;
    subLabel: string;
    total: number;
    valid: number;
    pending: number;
    rejected: number;
    pct: string;
  }
  const trendItems: TrendRow[] = [];

  if (periodMode === 'bulanan') {
    const daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
    for (let i = 1; i <= daysInMonth; i++) {
      const daySubmissions = submissions.filter((s) => {
        const parts = (s.tanggalWfa || '').split('-');
        return parts.length === 3 && parseInt(parts[2], 10) === i;
      });
      if (daySubmissions.length > 0) {
        const dObj = new Date(selectedYear, selectedMonth, i);
        const dayName = DAY_NAMES[dObj.getDay()] || '';
        const dayTotal = daySubmissions.length;
        const dayValid = daySubmissions.filter((s) => s.status === 'Valid').length;
        const dayPending = daySubmissions.filter((s) => s.status === 'Menunggu Validasi').length;
        const dayRejected = daySubmissions.filter((s) => s.status === 'Ditolak').length;
        const pct = dayTotal > 0 ? ((dayValid / dayTotal) * 100).toFixed(0) : '0';

        trendItems.push({
          label: `${i} ${MONTH_NAMES[selectedMonth]} ${selectedYear}`,
          subLabel: dayName,
          total: dayTotal,
          valid: dayValid,
          pending: dayPending,
          rejected: dayRejected,
          pct: `${pct}%`,
        });
      }
    }
  } else if (periodMode === 'tahunan' || periodMode === 'semua') {
    MONTH_NAMES.forEach((mName, mIdx) => {
      const monthSubmissions = submissions.filter((s) => {
        const parts = (s.tanggalWfa || '').split('-');
        return parts.length >= 2 && parseInt(parts[1], 10) - 1 === mIdx;
      });
      if (monthSubmissions.length > 0 || periodMode === 'tahunan') {
        const mTotal = monthSubmissions.length;
        const mValid = monthSubmissions.filter((s) => s.status === 'Valid').length;
        const mPending = monthSubmissions.filter((s) => s.status === 'Menunggu Validasi').length;
        const mRejected = monthSubmissions.filter((s) => s.status === 'Ditolak').length;
        const pct = mTotal > 0 ? ((mValid / mTotal) * 100).toFixed(0) : '0';

        trendItems.push({
          label: `${mName} ${selectedYear}`,
          subLabel: `Bulan ${mIdx + 1}`,
          total: mTotal,
          valid: mValid,
          pending: mPending,
          rejected: mRejected,
          pct: `${pct}%`,
        });
      }
    });
  }

  const hasAnnex = periodMode !== 'harian' && trendItems.length > 0;
  const totalPages = hasAnnex ? 2 : 1;

  // ==========================================
  // HALAMAN 1: RINGKASAN EKSEKUTIF & DISTRIBUSI
  // ==========================================

  // 1. KOP SURAT RESMI KEMENKES POLTEKKES BANDUNG (MODEL DUA SISI RESMI)
  const { lineY2 } = renderOfficialLandscapeKopSurat(doc, margin, pageWidth);

  // 2. JUDUL LAPORAN EKSEKUTIF & METADATA
  const titleY = lineY2 + 5.0;
  const subtitleY = titleY + 4.0;
  const metaY = subtitleY + 4.2;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42); // slate-900
  doc.text('LAPORAN EKSEKUTIF ANALISIS & STATISTIK PENGAJUAN WFA BIMBINGAN', pageWidth / 2, titleY, { align: 'center' });

  doc.setFontSize(8);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(100, 116, 139); // slate-500
  doc.text('PANGKALAN DATA MONITORING & EVALUASI PRESENSI TIM KERJA OSDM', pageWidth / 2, subtitleY, { align: 'center' });

  const todayStr = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'full',
    timeStyle: 'short',
  }).format(new Date());

  doc.setFontSize(7.5);
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(30, 41, 59);
  doc.text(`Periode Data: ${periodLabel}`, margin, metaY);

  doc.setFont('helvetica', 'normal');
  doc.setTextColor(71, 85, 105);
  doc.text(`Dicetak: ${todayStr} WIB   |   Pengelola: Tim Kerja OSDM Poltekkes Kemenkes Bandung`, pageWidth - margin, metaY, { align: 'right' });

  // 3. KPI SUMMARY CARDS (5 Kotak Metrik Proporsional)
  const cardY = metaY + 2.2;
  const cardHeight = 13.5;
  const cardGap = 3.5;
  const cardWidth = (contentWidth - cardGap * 4) / 5;

  const kpis = [
    {
      title: 'TOTAL PENGAJUAN',
      value: `${total}`,
      sub: `${uniqueEmployees} Pegawai Pemohon`,
      bg: [248, 250, 252],
      border: [203, 213, 225],
      text: [15, 23, 42],
    },
    {
      title: 'SUDAH VALID',
      value: `${valid}`,
      sub: `${validPct}% Lolos Verifikasi`,
      bg: [236, 253, 245],
      border: [167, 243, 208],
      text: [6, 95, 70],
    },
    {
      title: 'MENUNGGU VALIDASI',
      value: `${pending}`,
      sub: `${pendingPct}% Perlu Verifikasi`,
      bg: [254, 243, 199],
      border: [253, 230, 138],
      text: [146, 64, 14],
    },
    {
      title: 'DITOLAK / REVISI',
      value: `${rejected}`,
      sub: `${rejectedPct}% Dikembalikan`,
      bg: [255, 241, 242],
      border: [254, 205, 211],
      text: [159, 18, 57],
    },
    {
      title: 'PEGAWAI PEMOHON',
      value: `${uniqueEmployees}`,
      sub: 'Pegawai Aktif WFA',
      bg: [238, 242, 255],
      border: [199, 210, 254],
      text: [67, 56, 202],
    },
  ];

  kpis.forEach((kpi, idx) => {
    const x = margin + idx * (cardWidth + cardGap);
    doc.setFillColor(kpi.bg[0], kpi.bg[1], kpi.bg[2]);
    doc.setDrawColor(kpi.border[0], kpi.border[1], kpi.border[2]);
    doc.setLineWidth(0.2);
    doc.roundedRect(x, cardY, cardWidth, cardHeight, 1.2, 1.2, 'FD');

    // Title
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.2);
    doc.setTextColor(100, 116, 139);
    doc.text(kpi.title, x + 2.5, cardY + 3.8);

    // Value
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.setTextColor(kpi.text[0], kpi.text[1], kpi.text[2]);
    doc.text(kpi.value, x + 2.5, cardY + 8.8);

    // Subtext
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6);
    doc.setTextColor(100, 116, 139);
    doc.text(kpi.sub, x + 2.5, cardY + 12);
  });

  // 4. PROGRESS BAR PROPORSI VALIDASI
  const barY = cardY + cardHeight + 3.0;
  const barHeight = 3.6;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7);
  doc.setTextColor(30, 41, 59);
  doc.text(`KOMPOSISI STATUS VALIDASI BERKAS (${total} TOTAL BERKAS):`, margin, barY - 1);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(100, 116, 139);
  doc.text(`Tingkat Persetujuan Valid: ${validPct}%`, pageWidth - margin, barY - 1, { align: 'right' });

  // Background track
  doc.setFillColor(241, 245, 249);
  doc.roundedRect(margin, barY, contentWidth, barHeight, 0.8, 0.8, 'F');

  if (total > 0) {
    const validW = (valid / total) * contentWidth;
    const pendingW = (pending / total) * contentWidth;
    const rejectedW = (rejected / total) * contentWidth;

    let curX = margin;
    if (validW > 0) {
      doc.setFillColor(16, 185, 129); // emerald-500
      doc.rect(curX, barY, validW, barHeight, 'F');
      curX += validW;
    }
    if (pendingW > 0) {
      doc.setFillColor(245, 158, 11); // amber-500
      doc.rect(curX, barY, pendingW, barHeight, 'F');
      curX += pendingW;
    }
    if (rejectedW > 0) {
      doc.setFillColor(239, 68, 68); // rose-500
      doc.rect(curX, barY, rejectedW, barHeight, 'F');
    }
  }

  // Legend Bar
  const legY = barY + barHeight + 3.0;
  doc.setFontSize(6.5);

  // Dot 1: Valid
  doc.setFillColor(16, 185, 129);
  doc.circle(margin + 2, legY - 1, 1.2, 'F');
  doc.setTextColor(15, 23, 42);
  doc.text(`Valid (Disetujui): ${valid} berkas (${validPct}%)`, margin + 5, legY);

  // Dot 2: Pending
  doc.setFillColor(245, 158, 11);
  doc.circle(margin + 65, legY - 1, 1.2, 'F');
  doc.text(`Menunggu Validasi: ${pending} berkas (${pendingPct}%)`, margin + 68, legY);

  // Dot 3: Rejected
  doc.setFillColor(239, 68, 68);
  doc.circle(margin + 130, legY - 1, 1.2, 'F');
  doc.text(`Ditolak / Revisi: ${rejected} berkas (${rejectedPct}%)`, margin + 133, legY);

  // 5. DUA TABEL DISTRIBUSI BERDAMPINGAN (SIDE-BY-SIDE)
  const tablesStartY = legY + 3.2;
  const colGap = 6;
  const halfWidth = (contentWidth - colGap) / 2;

  // TABEL KIRI: DISTRIBUSI UNIT KERJA / JURUSAN (Top 8 + Lainnya + Total)
  const topUnitLimit = 8;
  const topUnits = unitKerjaStats.slice(0, topUnitLimit);
  const otherUnits = unitKerjaStats.slice(topUnitLimit);

  const unitKerjaRows: (string | number)[][] = topUnits.map((u, i) => [
    `${i + 1}`,
    u.name,
    `${u.total}`,
    `${u.valid}`,
    `${u.rejected}`,
    total > 0 ? `${((u.total / total) * 100).toFixed(1)}%` : '0%',
  ]);

  if (otherUnits.length > 0) {
    const oTotal = otherUnits.reduce((acc, curr) => acc + curr.total, 0);
    const oValid = otherUnits.reduce((acc, curr) => acc + curr.valid, 0);
    const oRejected = otherUnits.reduce((acc, curr) => acc + curr.rejected, 0);
    unitKerjaRows.push([
      '-',
      `Unit Kerja Lainnya (${otherUnits.length} Unit)`,
      `${oTotal}`,
      `${oValid}`,
      `${oRejected}`,
      total > 0 ? `${((oTotal / total) * 100).toFixed(1)}%` : '0%',
    ]);
  }

  if (unitKerjaStats.length > 0) {
    unitKerjaRows.push([
      '',
      'TOTAL KESELURUHAN',
      `${total}`,
      `${valid}`,
      `${rejected}`,
      '100%',
    ]);
  }

  autoTable(doc, {
    startY: tablesStartY,
    margin: { left: margin, right: margin + halfWidth + colGap },
    tableWidth: halfWidth,
    head: [
      [
        { content: 'DISTRIBUSI JURUSAN / UNIT KERJA', colSpan: 6, styles: { halign: 'left', fillColor: [79, 70, 229] } },
      ],
      ['No', 'Jurusan / Unit Kerja', 'Total', 'Valid', 'Tolak', '%'],
    ],
    body: unitKerjaRows,
    theme: 'grid',
    styles: {
      fontSize: 6.5,
      cellPadding: 1.4,
      font: 'helvetica',
      textColor: [30, 41, 59],
      lineColor: [226, 232, 240],
      lineWidth: 0.15,
    },
    headStyles: {
      fillColor: [30, 41, 59],
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      halign: 'center',
    },
    columnStyles: {
      0: { cellWidth: 8, halign: 'center' },
      1: { cellWidth: 'auto', fontStyle: 'bold' },
      2: { cellWidth: 12, halign: 'center', fontStyle: 'bold' },
      3: { cellWidth: 12, halign: 'center', textColor: [16, 185, 129] },
      4: { cellWidth: 12, halign: 'center', textColor: [239, 68, 68] },
      5: { cellWidth: 13, halign: 'center' },
    },
    didParseCell: (data) => {
      if (data.row.index === unitKerjaRows.length - 1 && unitKerjaRows.length > 1) {
        data.cell.styles.fontStyle = 'bold';
        data.cell.styles.fillColor = [241, 245, 249];
      }
    },
  });

  const leftTableFinalY = (doc as any).lastAutoTable?.finalY || tablesStartY + 35;

  // TABEL KANAN: DISTRIBUSI WILAYAH & KATEGORI PRESENSI
  const wilayahRows = wilayahStats.slice(0, 5).map((w, i) => [
    `${i + 1}`,
    w.name,
    `${w.total}`,
    `${w.valid}`,
    `${w.rejected}`,
    total > 0 ? `${((w.total / total) * 100).toFixed(1)}%` : '0%',
  ]);

  autoTable(doc, {
    startY: tablesStartY,
    margin: { left: margin + halfWidth + colGap, right: margin },
    tableWidth: halfWidth,
    head: [
      [
        { content: 'DISTRIBUSI WILAYAH KEGIATAN', colSpan: 6, styles: { halign: 'left', fillColor: [14, 165, 233] } },
      ],
      ['No', 'Wilayah / Kota-Kabupaten', 'Total', 'Valid', 'Tolak', '%'],
    ],
    body: wilayahRows,
    theme: 'grid',
    styles: {
      fontSize: 6.5,
      cellPadding: 1.4,
      font: 'helvetica',
      textColor: [30, 41, 59],
      lineColor: [226, 232, 240],
      lineWidth: 0.15,
    },
    headStyles: {
      fillColor: [30, 41, 59],
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      halign: 'center',
    },
    columnStyles: {
      0: { cellWidth: 8, halign: 'center' },
      1: { cellWidth: 'auto', fontStyle: 'bold' },
      2: { cellWidth: 12, halign: 'center', fontStyle: 'bold' },
      3: { cellWidth: 12, halign: 'center', textColor: [16, 185, 129] },
      4: { cellWidth: 12, halign: 'center', textColor: [239, 68, 68] },
      5: { cellWidth: 13, halign: 'center' },
    },
  });

  const rightTableWilayahFinalY = (doc as any).lastAutoTable?.finalY || tablesStartY + 25;

  // Sub-Tabel Kategori Presensi di bawah tabel Wilayah
  const kategoriRows = [
    ['A', 'WFA Seharian Penuh (Full Day)', `${jenisFull}`, total > 0 ? `${((jenisFull / total) * 100).toFixed(1)}%` : '0%'],
    ['B', 'WFA Datang (Pagi s.d Siang)', `${jenisDatang}`, total > 0 ? `${((jenisDatang / total) * 100).toFixed(1)}%` : '0%'],
    ['C', 'WFA Pulang (Siang s.d Sore)', `${jenisPulang}`, total > 0 ? `${((jenisPulang / total) * 100).toFixed(1)}%` : '0%'],
  ];

  autoTable(doc, {
    startY: rightTableWilayahFinalY + 2.5,
    margin: { left: margin + halfWidth + colGap, right: margin },
    tableWidth: halfWidth,
    head: [
      [
        { content: 'KATEGORI PRESENSI WFA', colSpan: 4, styles: { halign: 'left', fillColor: [16, 185, 129] } },
      ],
      ['Kode', 'Jenis Kehadiran', 'Jumlah Berkas', 'Proporsi'],
    ],
    body: kategoriRows,
    theme: 'grid',
    styles: {
      fontSize: 6.5,
      cellPadding: 1.4,
      font: 'helvetica',
      textColor: [30, 41, 59],
      lineColor: [226, 232, 240],
      lineWidth: 0.15,
    },
    headStyles: {
      fillColor: [30, 41, 59],
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      halign: 'center',
    },
    columnStyles: {
      0: { cellWidth: 10, halign: 'center' },
      1: { cellWidth: 'auto', fontStyle: 'bold' },
      2: { cellWidth: 20, halign: 'center', fontStyle: 'bold' },
      3: { cellWidth: 16, halign: 'center' },
    },
  });

  const rightTableFinalY = (doc as any).lastAutoTable?.finalY || tablesStartY + 45;
  const bottomSectionY = Math.max(leftTableFinalY, rightTableFinalY) + 5.0;

  // 6. BOTTOM SECTION: EVALUATION NOTE (LEFT) & SIGNATURE BLOCK (RIGHT) - NO COLLISION!
  const todayYmd = new Date().toISOString().slice(0, 10);
  const tglPengesahan = formatDateIndoLong(todayYmd);

  // Kiri: Catatan & Evaluasi Verifikasi Presensi
  const noteBoxWidth = halfWidth;
  const noteBoxY = bottomSectionY;
  const noteBoxHeight = 28;

  doc.setFillColor(248, 250, 252); // slate-50
  doc.setDrawColor(226, 232, 240); // slate-200
  doc.setLineWidth(0.2);
  doc.roundedRect(margin, noteBoxY, noteBoxWidth, noteBoxHeight, 1.5, 1.5, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7);
  doc.setTextColor(30, 41, 59);
  doc.text('CATATAN & EVALUASI VERIFIKASI PRESENSI:', margin + 3.5, noteBoxY + 4.5);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.2);
  doc.setTextColor(71, 85, 105);
  doc.text('• Berkas status "Valid" telah diverifikasi kesesuaian surat tugas dan lokasi bimbingan.', margin + 3.5, noteBoxY + 9.5);
  doc.text('• Berkas "Ditolak / Revisi" telah dikembalikan ke pemohon dengan catatan evaluasi OSDM.', margin + 3.5, noteBoxY + 14);
  doc.text('• Rekapitulasi ini merupakan data resmi pemantauan kepatuhan presensi kerja pegawai.', margin + 3.5, noteBoxY + 18.5);

  if (hasAnnex) {
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(79, 70, 229);
    doc.text('* Rincian tren aktivitas kronologis harian/bulanan terlampir pada Lembar Lampiran (Halaman 2).', margin + 3.5, noteBoxY + 23.5);
  }

  // Kanan: Lembar Tanda Tangan Pengesahan Resmi (Bebas tabrakan, ruang sangat lega!)
  const sigBoxX = margin + halfWidth + colGap;
  const sigCenter = sigBoxX + halfWidth / 2;
  const sigY = noteBoxY + 2;

  const displayCityDate = (signerCityDate && signerCityDate.trim()) || `Bandung, ${tglPengesahan}`;
  const displayTitle = (signerTitle && signerTitle.trim()) || 'Penanggung Jawab Tim Kerja OSDM,';
  const rawName = (signerName && signerName.trim()) || 'Tim Kerja OSDM';
  const displayName = rawName.startsWith('(') ? rawName : `( ${rawName} )`;
  const displayInst = (signerInstitution && signerInstitution.trim()) || 'Poltekkes Kemenkes Bandung';

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(30, 41, 59);
  doc.text(displayCityDate, sigCenter, sigY, { align: 'center' });
  doc.text(displayTitle, sigCenter, sigY + 4, { align: 'center' });

  // Tanda Tangan Digital PNG Resmi (Jika diunggah)
  if (signerSignaturePng) {
    try {
      let sigW = 30;
      let sigH = 12;
      if (typeof window !== 'undefined' && typeof Image !== 'undefined') {
        const img = new Image();
        img.src = signerSignaturePng;
        if (!img.complete) {
          await new Promise<void>((resolve) => {
            img.onload = () => resolve();
            img.onerror = () => resolve();
          });
        }
        if (img.naturalWidth && img.naturalHeight) {
          const ratio = img.naturalWidth / img.naturalHeight;
          sigH = 12;
          sigW = sigH * ratio;
          if (sigW > 45) {
            sigW = 45;
            sigH = sigW / ratio;
          }
        }
      }
      const sigImgY = sigY + 4.5 + (12 - sigH) / 2;
      doc.addImage(signerSignaturePng, 'PNG', sigCenter - sigW / 2, sigImgY, sigW, sigH);
    } catch (e) {
      console.warn('Gagal memuat tanda tangan digital PNG:', e);
    }
  }

  // Nama Pengesah & Institusi
  doc.setFont('helvetica', 'bold');
  doc.text(displayName, sigCenter, sigY + 18, { align: 'center' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7);
  doc.text(displayInst, sigCenter, sigY + 22, { align: 'center' });

  // Catatan Kaki Halaman 1
  doc.setFontSize(6);
  doc.setTextColor(148, 163, 184);
  doc.text(
    `* Dokumen ini dibuat otomatis oleh Sistem Portal OSDM Poltekkes Kemenkes Bandung pada ${todayStr} WIB. Sah tanpa tanda tangan basah.`,
    margin,
    pageHeight - 6
  );
  doc.text(`Halaman 1 dari ${totalPages}`, pageWidth - margin, pageHeight - 6, { align: 'right' });

  // ==========================================
  // HALAMAN 2: LAMPIRAN ANALISIS TREN WAKTU (JIKA ADA DATA TREN)
  // ==========================================
  if (hasAnnex) {
    doc.addPage();

    // Header Halaman 2 (Kop Mini / Title Banner)
    const p2HeaderY = 10;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.setTextColor(15, 23, 42);
    doc.text('LAMPIRAN: REKAPITULASI TREN VOLUME PENGAJUAN WFA', margin, p2HeaderY);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(100, 116, 139);
    doc.text(`Pusat Data Monitoring dan Evaluasi Presensi Pegawai — Periode: ${periodLabel}`, margin, p2HeaderY + 4.5);

    doc.setFontSize(7.5);
    doc.text(`Dicetak: ${todayStr} WIB`, pageWidth - margin, p2HeaderY + 4.5, { align: 'right' });

    // Garis Pemisah Header Halaman 2
    doc.setDrawColor(203, 213, 225);
    doc.setLineWidth(0.4);
    doc.line(margin, p2HeaderY + 7.5, pageWidth - margin, p2HeaderY + 7.5);

    // Tabel Lampiran Tren yang LEGA dan TERPERINCI
    const annexTableY = p2HeaderY + 11;
    const annexBody: (string | number)[][] = trendItems.map((item, idx) => [
      `${idx + 1}`,
      item.label,
      item.subLabel,
      `${item.total}`,
      `${item.valid}`,
      `${item.pending}`,
      `${item.rejected}`,
      item.pct,
      item.total === 0 ? '-' : item.rejected === 0 ? 'Optimal (100% Valid)' : `${item.valid} Disetujui, ${item.rejected} Ditolak`,
    ]);

    // Baris Total Akumulasi
    const sumTotal = trendItems.reduce((a, b) => a + b.total, 0);
    const sumValid = trendItems.reduce((a, b) => a + b.valid, 0);
    const sumPending = trendItems.reduce((a, b) => a + b.pending, 0);
    const sumRejected = trendItems.reduce((a, b) => a + b.rejected, 0);
    const sumPct = sumTotal > 0 ? ((sumValid / sumTotal) * 100).toFixed(1) : '0';

    annexBody.push([
      '',
      'TOTAL AKUMULASI PERIODE',
      '-',
      `${sumTotal}`,
      `${sumValid}`,
      `${sumPending}`,
      `${sumRejected}`,
      `${sumPct}%`,
      'Rekapitulasi Periode Terpilih',
    ]);

    autoTable(doc, {
      startY: annexTableY,
      margin: { left: margin, right: margin },
      head: [
        [
          {
            content: `RINCIAN TREN AKTIVITAS KRONOLOGIS (${periodMode.toUpperCase()})`,
            colSpan: 9,
            styles: { halign: 'left', fillColor: [79, 70, 229] },
          },
        ],
        ['No', 'Tanggal / Periode', 'Keterangan', 'Total Berkas', 'Disetujui (Valid)', 'Menunggu', 'Ditolak', '% Kelulusan', 'Status Dominan'],
      ],
      body: annexBody,
      theme: 'grid',
      styles: {
        fontSize: 7,
        cellPadding: 1.8,
        font: 'helvetica',
        textColor: [30, 41, 59],
        lineColor: [226, 232, 240],
        lineWidth: 0.15,
      },
      headStyles: {
        fillColor: [30, 41, 59],
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        halign: 'center',
      },
      columnStyles: {
        0: { cellWidth: 8, halign: 'center' },
        1: { cellWidth: 45, fontStyle: 'bold' },
        2: { cellWidth: 25, halign: 'center', textColor: [100, 116, 139] },
        3: { cellWidth: 24, halign: 'center', fontStyle: 'bold' },
        4: { cellWidth: 28, halign: 'center', textColor: [16, 185, 129] },
        5: { cellWidth: 22, halign: 'center', textColor: [245, 158, 11] },
        6: { cellWidth: 22, halign: 'center', textColor: [239, 68, 68] },
        7: { cellWidth: 25, halign: 'center', fontStyle: 'bold' },
        8: { cellWidth: 'auto', fontStyle: 'normal' },
      },
      didParseCell: (data) => {
        if (data.row.index === annexBody.length - 1) {
          data.cell.styles.fontStyle = 'bold';
          data.cell.styles.fillColor = [241, 245, 249];
        }
      },
    });

    const annexFinalY = (doc as any).lastAutoTable?.finalY || annexTableY + 80;



    // Footer Halaman 2
    doc.setFontSize(6);
    doc.setTextColor(148, 163, 184);
    doc.text(
      `* Dokumen ini dibuat otomatis oleh Sistem Portal OSDM Poltekkes Kemenkes Bandung. Lampiran Sah.`,
      margin,
      pageHeight - 6
    );
    doc.text(`Halaman 2 dari ${totalPages}`, pageWidth - margin, pageHeight - 6, { align: 'right' });
  }

  // Unduh dokumen PDF
  const filename = customFilename || `Laporan_Eksekutif_Dashboard_WFA_${periodMode}_${Date.now()}.pdf`;
  doc.save(filename);
}

import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { KebugaranSubmission } from '../types';
import { renderOfficialLandscapeKopSurat } from './officialLandscapeKopSurat';
import {
  calculateBmi,
  classifyBloodPressure,
  classifyBloodSugar,
  classifyCholesterol,
  formatTanggalPendek
} from './kebugaranUtils';

export interface GenerateKebugaranDashboardPdfOptions {
  submissions: KebugaranSubmission[];
  selectedTriwulan: string;
  selectedYear: string;
  selectedUnit: string;
  customFilename?: string;
  customKopSuratImage?: string | null;
  // Kustomisasi Penandatangan & TTD Digital PNG
  signerCityDate?: string;
  signerTitle?: string;
  signerName?: string;
  signerInstitution?: string;
  signerSignaturePng?: string | null;
}

function generateDonutChartBase64(
  totalPrima: number,
  totalPerhatian: number,
  total: number
): string | null {
  try {
    if (typeof document === 'undefined') return null;
    const canvas = document.createElement('canvas');
    canvas.width = 580;
    canvas.height = 175;
    const ctx = canvas.getContext('2d');
    if (!ctx) return null;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const centerX = 95;
    const centerY = 87;
    const outerRadius = 70;
    const innerRadius = 40;

    const primaRatio = total > 0 ? totalPrima / total : 0;
    const startAngle = -Math.PI / 2;
    const primaAngle = startAngle + primaRatio * 2 * Math.PI;
    const endAngle = startAngle + 2 * Math.PI;

    // Draw Prima arc (Emerald #10b981)
    if (primaRatio > 0) {
      ctx.beginPath();
      ctx.arc(centerX, centerY, outerRadius, startAngle, primaAngle);
      ctx.arc(centerX, centerY, innerRadius, primaAngle, startAngle, true);
      ctx.closePath();
      ctx.fillStyle = '#10b981';
      ctx.fill();
    }

    // Draw Perhatian arc (Amber #f59e0b)
    if (1 - primaRatio > 0) {
      ctx.beginPath();
      ctx.arc(centerX, centerY, outerRadius, primaAngle, endAngle);
      ctx.arc(centerX, centerY, innerRadius, endAngle, primaAngle, true);
      ctx.closePath();
      ctx.fillStyle = '#f59e0b';
      ctx.fill();
    }

    // Border between arcs
    ctx.lineWidth = 2.5;
    ctx.strokeStyle = '#ffffff';
    ctx.stroke();

    // Center text
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 26px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(`${total}`, centerX, centerY - 7);

    ctx.fillStyle = '#64748b';
    ctx.font = '13px sans-serif';
    ctx.fillText('Pegawai', centerX, centerY + 15);

    // Legends on right side (x: 185 to 565)
    const primaPct = total > 0 ? ((totalPrima / total) * 100).toFixed(1) : '0';
    const perhatianPct = total > 0 ? ((totalPerhatian / total) * 100).toFixed(1) : '0';

    // Box 1: Sehat Prima
    ctx.fillStyle = '#ecfdf5';
    ctx.beginPath();
    if (typeof (ctx as any).roundRect === 'function') {
      (ctx as any).roundRect(190, 10, 375, 72, 8);
    } else {
      ctx.rect(190, 10, 375, 72);
    }
    ctx.fill();
    ctx.strokeStyle = '#a7f3d0';
    ctx.lineWidth = 1.2;
    ctx.stroke();

    ctx.fillStyle = '#10b981';
    ctx.beginPath();
    ctx.arc(212, 34, 7, 0, 2 * Math.PI);
    ctx.fill();

    ctx.textAlign = 'left';
    ctx.fillStyle = '#065f46';
    ctx.font = 'bold 15px sans-serif';
    ctx.fillText('Sehat Prima (4 Normal Bebas Risiko)', 228, 39);

    ctx.fillStyle = '#047857';
    ctx.font = 'bold 22px sans-serif';
    ctx.fillText(`${totalPrima} Pegawai (${primaPct}%)`, 228, 66);

    // Box 2: Perlu Pemantauan
    ctx.fillStyle = '#fffbeb';
    ctx.beginPath();
    if (typeof (ctx as any).roundRect === 'function') {
      (ctx as any).roundRect(190, 92, 375, 72, 8);
    } else {
      ctx.rect(190, 92, 375, 72);
    }
    ctx.fill();
    ctx.strokeStyle = '#fde68a';
    ctx.lineWidth = 1.2;
    ctx.stroke();

    ctx.fillStyle = '#f59e0b';
    ctx.beginPath();
    ctx.arc(212, 116, 7, 0, 2 * Math.PI);
    ctx.fill();

    ctx.fillStyle = '#92400e';
    ctx.font = 'bold 15px sans-serif';
    ctx.fillText('Perlu Pemantauan (Min. 1 Abnormal)', 228, 121);

    ctx.fillStyle = '#b45309';
    ctx.font = 'bold 22px sans-serif';
    ctx.fillText(`${totalPerhatian} Pegawai (${perhatianPct}%)`, 228, 148);

    return canvas.toDataURL('image/png');
  } catch (err) {
    console.error('Failed to generate donut chart canvas:', err);
    return null;
  }
}

export async function generateKebugaranDashboardAnalyticsPdf({
  submissions,
  selectedTriwulan,
  selectedYear,
  selectedUnit,
  customFilename,
  signerCityDate,
  signerTitle,
  signerName,
  signerInstitution,
  signerSignaturePng,
}: GenerateKebugaranDashboardPdfOptions): Promise<void> {
  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a4',
  });

  const pageWidth = doc.internal.pageSize.getWidth(); // 297mm
  const pageHeight = doc.internal.pageSize.getHeight(); // 210mm
  const margin = 14;
  const contentWidth = pageWidth - margin * 2; // 269mm

  // 1. DATA EVALUATION
  const evaluated = submissions.map((sub) => {
    const bp = classifyBloodPressure(sub.tensiSistolik, sub.tensiDiastolik);
    const bmi = calculateBmi(sub.beratBadan, sub.tinggiBadan);
    const chol = classifyCholesterol(sub.kolesterol);
    const sugar = classifyBloodSugar(sub.gulaDarah, sub.tipeGulaDarah);

    const isBpNormal = bp.status === 'normal';
    const isBmiNormal = bmi.category === 'normal';
    const isCholNormal = chol.status === 'normal';
    const isSugarNormal = sugar.status === 'normal';

    const abnormalCount =
      (isBpNormal ? 0 : 1) +
      (isBmiNormal ? 0 : 1) +
      (isCholNormal ? 0 : 1) +
      (isSugarNormal ? 0 : 1);

    const isPrima = abnormalCount === 0;

    return {
      ...sub,
      bp,
      bmi,
      chol,
      sugar,
      isBpNormal,
      isBmiNormal,
      isCholNormal,
      isSugarNormal,
      abnormalCount,
      isPrima,
    };
  });

  const total = evaluated.length;
  const uniqueEmployees = new Set(evaluated.map((s) => s.nip || s.namaPegawai)).size;

  const tensiNormal = evaluated.filter((s) => s.isBpNormal).length;
  const tensiAbnormal = total - tensiNormal;
  const tensiNormalPct = total > 0 ? ((tensiNormal / total) * 100).toFixed(1) : '0';

  const imtNormal = evaluated.filter((s) => s.isBmiNormal).length;
  const imtAbnormal = total - imtNormal;
  const imtNormalPct = total > 0 ? ((imtNormal / total) * 100).toFixed(1) : '0';

  const cholNormal = evaluated.filter((s) => s.isCholNormal).length;
  const cholAbnormal = total - cholNormal;
  const cholNormalPct = total > 0 ? ((cholNormal / total) * 100).toFixed(1) : '0';

  const sugarNormal = evaluated.filter((s) => s.isSugarNormal).length;
  const sugarAbnormal = total - sugarNormal;
  const sugarNormalPct = total > 0 ? ((sugarNormal / total) * 100).toFixed(1) : '0';

  const totalPrima = evaluated.filter((s) => s.isPrima).length;
  const totalPerhatian = total - totalPrima;
  const totalPrimaPct = total > 0 ? ((totalPrima / total) * 100).toFixed(1) : '0';
  const totalPerhatianPct = total > 0 ? ((totalPerhatian / total) * 100).toFixed(1) : '0';

  const indeksAgregat = total > 0
    ? Math.round((parseFloat(tensiNormalPct) + parseFloat(imtNormalPct) + parseFloat(cholNormalPct) + parseFloat(sugarNormalPct)) / 4)
    : 0;

  // Unit Distribution
  const unitMap = new Map<string, { total: number; tensiNormal: number; imtNormal: number; cholNormal: number; sugarNormal: number; prima: number; perhatian: number }>();
  evaluated.forEach((s) => {
    const u = (s.unitKerja || 'Unit Lainnya').trim();
    const curr = unitMap.get(u) || { total: 0, tensiNormal: 0, imtNormal: 0, cholNormal: 0, sugarNormal: 0, prima: 0, perhatian: 0 };
    curr.total += 1;
    if (s.isBpNormal) curr.tensiNormal += 1;
    if (s.isBmiNormal) curr.imtNormal += 1;
    if (s.isCholNormal) curr.cholNormal += 1;
    if (s.isSugarNormal) curr.sugarNormal += 1;
    if (s.isPrima) curr.prima += 1;
    else curr.perhatian += 1;
    unitMap.set(u, curr);
  });
  const unitStats = Array.from(unitMap.entries())
    .map(([name, stat]) => ({ name, ...stat }))
    .sort((a, b) => b.total - a.total);

  // List of Attention Needed Employees for Annex (Lembar 2)
  const attentionList = evaluated.filter((s) => !s.isPrima);
  const totalPages = attentionList.length > 0 ? 2 : 1;

  // ==========================================
  // HALAMAN 1: RINGKASAN EKSEKUTIF & PENGESAHAN
  // ==========================================

  // 1. KOP SURAT RESMI KEMENKES POLTEKKES BANDUNG
  const { lineY2 } = renderOfficialLandscapeKopSurat(doc, margin, pageWidth);

  // 2. JUDUL LAPORAN EKSEKUTIF & METADATA
  const titleY = lineY2 + 5.0;
  const subtitleY = titleY + 4.0;
  const metaY = subtitleY + 4.2;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42); // slate-900
  doc.text('LAPORAN EKSEKUTIF ANALISIS & STATISTIK KEBUGARAN PEGAWAI', pageWidth / 2, titleY, { align: 'center' });

  doc.setFontSize(8);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(100, 116, 139); // slate-500
  doc.text('PANGKALAN DATA PEMERIKSAAN KESEHATAN BERKALA TIM KERJA OSDM & LAYANAN KESEHATAN', pageWidth / 2, subtitleY, { align: 'center' });

  const todayStr = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'full',
    timeStyle: 'short',
  }).format(new Date());

  doc.setFontSize(7.5);
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(30, 41, 59);
  doc.text(`Periode: ${selectedTriwulan}  |  Tahun: ${selectedYear}  |  Unit: ${selectedUnit}`, margin, metaY);

  doc.setFont('helvetica', 'normal');
  doc.setTextColor(71, 85, 105);
  doc.text(`Dicetak: ${todayStr} WIB   |   Pengelola: Tim Kerja OSDM Poltekkes Kemenkes Bandung`, pageWidth - margin, metaY, { align: 'right' });

  // 3. KPI SUMMARY CARDS (4 Kotak Metrik)
  const cardY = metaY + 2.2;
  const cardHeight = 13.5;
  const cardGap = 4.0;
  const cardWidth = (contentWidth - cardGap * 3) / 4;

  const kpis = [
    {
      title: 'TOTAL PEMERIKSAAN',
      value: `${total}`,
      sub: `${uniqueEmployees} Pegawai Terdata`,
      bg: [248, 250, 252],
      border: [203, 213, 225],
      text: [15, 23, 42],
    },
    {
      title: 'SEHAT PRIMA (4 NORMAL)',
      value: `${totalPrima}`,
      sub: `${totalPrimaPct}% Seluruh Indikator Ideal`,
      bg: [236, 253, 245],
      border: [167, 243, 208],
      text: [6, 95, 70],
    },
    {
      title: 'PERLU PEMANTAUAN',
      value: `${totalPerhatian}`,
      sub: `${totalPerhatianPct}% Minimal 1 Indikator Abnormal`,
      bg: [254, 243, 199],
      border: [253, 230, 138],
      text: [146, 64, 14],
    },
    {
      title: 'INDEKS KESEHATAN AGREGAT',
      value: `${indeksAgregat}%`,
      sub: 'Tingkat Kepatuhan Standar Kemenkes',
      bg: [239, 246, 255],
      border: [191, 219, 254],
      text: [30, 64, 175],
    },
  ];

  kpis.forEach((kpi, idx) => {
    const x = margin + idx * (cardWidth + cardGap);
    doc.setFillColor(kpi.bg[0], kpi.bg[1], kpi.bg[2]);
    doc.setDrawColor(kpi.border[0], kpi.border[1], kpi.border[2]);
    doc.setLineWidth(0.3);
    doc.roundedRect(x, cardY, cardWidth, cardHeight, 1.5, 1.5, 'FD');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.5);
    doc.setTextColor(kpi.text[0], kpi.text[1], kpi.text[2]);
    doc.text(kpi.title, x + 3.0, cardY + 3.8);

    doc.setFontSize(11.5);
    doc.text(kpi.value, x + 3.0, cardY + 8.8);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(5.8);
    doc.setTextColor(100, 116, 139);
    doc.text(kpi.sub, x + 3.0, cardY + 12.0);
  });

  // 4. DUA TABEL UTAMA (SIDE-BY-SIDE)
  const tableStartY = cardY + cardHeight + 2.8;
  const leftTableWidth = 142;
  const rightTableWidth = contentWidth - leftTableWidth - 4; // 123mm

  // TABEL KIRI: 4 PARAMETER UTAMA
  const paramTableData = [
    [
      'Tekanan Darah (Tensi)',
      'Sistolik <120 / Diastolik <80',
      `${tensiNormal}`,
      `${tensiAbnormal}`,
      `${tensiNormalPct}%`
    ],
    [
      'Indeks Massa Tubuh (IMT)',
      'Ideal Kemenkes: 18.5 - 22.9',
      `${imtNormal}`,
      `${imtAbnormal}`,
      `${imtNormalPct}%`
    ],
    [
      'Kolesterol Total',
      'Normal <200 mg/dL',
      `${cholNormal}`,
      `${cholAbnormal}`,
      `${cholNormalPct}%`
    ],
    [
      'Gula Darah (GDP / GDS)',
      'GDP <100 / GDS <140 mg/dL',
      `${sugarNormal}`,
      `${sugarAbnormal}`,
      `${sugarNormalPct}%`
    ],
    [
      'STATUS KESELURUHAN PRIMA',
      '4 Parameter Normal Bebas Risiko',
      `${totalPrima}`,
      `${totalPerhatian}`,
      `${totalPrimaPct}%`
    ]
  ];

  autoTable(doc, {
    startY: tableStartY,
    margin: { left: margin },
    tableWidth: leftTableWidth,
    head: [['Parameter Skrining', 'Standar Normal Kemenkes', 'Normal', 'Abnormal', '% Kepatuhan']],
    body: paramTableData,
    theme: 'grid',
    styles: {
      font: 'helvetica',
      fontSize: 6.5,
      cellPadding: { top: 1.3, bottom: 1.3, left: 1.8, right: 1.8 },
      textColor: [30, 41, 59],
      lineColor: [226, 232, 240],
      lineWidth: 0.18,
    },
    headStyles: {
      fillColor: [15, 23, 42],
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      fontSize: 6.6,
      halign: 'center',
    },
    columnStyles: {
      0: { fontStyle: 'bold', cellWidth: 42 },
      1: { cellWidth: 50, textColor: [100, 116, 139] },
      2: { halign: 'center', textColor: [6, 95, 70], fontStyle: 'bold', cellWidth: 16 },
      3: { halign: 'center', textColor: [225, 29, 72], fontStyle: 'bold', cellWidth: 16 },
      4: { halign: 'center', fontStyle: 'bold', cellWidth: 18 },
    },
  });

  const leftTableFinalY = (doc as any).lastAutoTable?.finalY || tableStartY + 24;

  // TABEL KANAN: DISTRIBUSI PER JURUSAN / UNIT KERJA
  const unitTableRows = unitStats.slice(0, 7).map((u, i) => [
    `${i + 1}`,
    u.name.length > 20 ? u.name.substring(0, 19) + '...' : u.name,
    `${u.total}`,
    `${u.tensiNormal}`,
    `${u.imtNormal}`,
    `${u.cholNormal}`,
    `${u.sugarNormal}`,
    `${u.prima}`
  ]);

  autoTable(doc, {
    startY: tableStartY,
    margin: { left: margin + leftTableWidth + 4 },
    tableWidth: rightTableWidth,
    head: [['No', 'Unit Kerja / Jurusan', 'Total', 'Tensi', 'IMT', 'Kol.', 'Gula', 'Prima']],
    body: unitTableRows,
    theme: 'grid',
    styles: {
      font: 'helvetica',
      fontSize: 6.3,
      cellPadding: { top: 1.2, bottom: 1.2, left: 1.6, right: 1.6 },
      textColor: [30, 41, 59],
      lineColor: [226, 232, 240],
      lineWidth: 0.18,
    },
    headStyles: {
      fillColor: [13, 148, 136], // teal-600
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      fontSize: 6.6,
      halign: 'center',
    },
    columnStyles: {
      0: { halign: 'center', cellWidth: 7 },
      1: { fontStyle: 'bold', cellWidth: 46 },
      2: { halign: 'center', fontStyle: 'bold', cellWidth: 12 },
      3: { halign: 'center', cellWidth: 11, textColor: [6, 95, 70] },
      4: { halign: 'center', cellWidth: 11, textColor: [6, 95, 70] },
      5: { halign: 'center', cellWidth: 11, textColor: [6, 95, 70] },
      6: { halign: 'center', cellWidth: 11, textColor: [6, 95, 70] },
      7: { halign: 'center', fontStyle: 'bold', cellWidth: 14, textColor: [13, 148, 136] },
    },
  });

  const rightTableFinalY = (doc as any).lastAutoTable?.finalY || tableStartY + 27;

  // 5. SEKSI INFOGRAFIS ANALITIK (SIDE-BY-SIDE)
  // Memanfaatkan ruang tengah-bawah dokumen agar sangat informatif & profesional bagi pimpinan
  const maxTableFinalY = Math.max(leftTableFinalY, rightTableFinalY);
  const chartsStartY = maxTableFinalY + 3.2;
  const chartsHeight = 43.0;

  // 5A. GRAFIK KIRI: KOMPARASI BATANG 4 PARAMETER SKRINING (NORMAL VS PERLU PEMANTAUAN)
  doc.setFillColor(248, 250, 252);
  doc.setDrawColor(203, 213, 225);
  doc.setLineWidth(0.2);
  doc.roundedRect(margin, chartsStartY, leftTableWidth, chartsHeight, 1.5, 1.5, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7.0);
  doc.setTextColor(15, 23, 42);
  doc.text('GRAFIK DISTRIBUSI KELAIKAN 4 PARAMETER SKRINING', margin + 3.2, chartsStartY + 4.2);

  // Legend Bar Komparasi
  const legBarX = margin + 82.0;
  doc.setFillColor(16, 185, 129);
  doc.circle(legBarX, chartsStartY + 3.2, 0.9, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(5.8);
  doc.setTextColor(6, 95, 70);
  doc.text('Normal (Ideal)', legBarX + 2.2, chartsStartY + 4.0);

  doc.setFillColor(244, 63, 94);
  doc.circle(legBarX + 26.0, chartsStartY + 3.2, 0.9, 'F');
  doc.setTextColor(159, 18, 57);
  doc.text('Abnormal / Pantau', legBarX + 28.2, chartsStartY + 4.0);

  // 4 Baris Parameter
  const barParams = [
    {
      label: 'Tekanan Darah (Tensi)',
      normal: tensiNormal,
      abnormal: tensiAbnormal,
      normalPct: tensiNormalPct,
      abnNormalPct: (100 - parseFloat(tensiNormalPct)).toFixed(1)
    },
    {
      label: 'Indeks Massa Tubuh (IMT)',
      normal: imtNormal,
      abnormal: imtAbnormal,
      normalPct: imtNormalPct,
      abnNormalPct: (100 - parseFloat(imtNormalPct)).toFixed(1)
    },
    {
      label: 'Kolesterol Total',
      normal: cholNormal,
      abnormal: cholAbnormal,
      normalPct: cholNormalPct,
      abnNormalPct: (100 - parseFloat(cholNormalPct)).toFixed(1)
    },
    {
      label: 'Gula Darah (GDP / GDS)',
      normal: sugarNormal,
      abnormal: sugarAbnormal,
      normalPct: sugarNormalPct,
      abnNormalPct: (100 - parseFloat(sugarNormalPct)).toFixed(1)
    }
  ];

  const barTrackX = margin + 44.0;
  const barTrackWidth = leftTableWidth - 47.0; // 95mm
  const barTrackHeight = 4.2;

  barParams.forEach((bp, idx) => {
    const rowY = chartsStartY + 7.2 + idx * 8.6;

    // Label Parameter
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.2);
    doc.setTextColor(30, 41, 59);
    doc.text(bp.label, margin + 3.2, rowY + 3.0);

    // Track Bar Segmented
    const normalWidth = total > 0 ? (bp.normal / total) * barTrackWidth : 0;
    const abnWidth = barTrackWidth - normalWidth;

    // Normal Segment (Emerald)
    if (normalWidth > 0) {
      doc.setFillColor(16, 185, 129);
      doc.rect(barTrackX, rowY, normalWidth, barTrackHeight, 'F');
      if (normalWidth >= 16) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(5.3);
        doc.setTextColor(255, 255, 255);
        doc.text(`${bp.normal} (${bp.normalPct}%)`, barTrackX + normalWidth / 2, rowY + 2.9, { align: 'center' });
      }
    }

    // Abnormal Segment (Rose)
    if (abnWidth > 0) {
      doc.setFillColor(244, 63, 94);
      doc.rect(barTrackX + normalWidth, rowY, abnWidth, barTrackHeight, 'F');
      if (abnWidth >= 16) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(5.3);
        doc.setTextColor(255, 255, 255);
        doc.text(`${bp.abnormal} (${bp.abnNormalPct}%)`, barTrackX + normalWidth + abnWidth / 2, rowY + 2.9, { align: 'center' });
      }
    }

    // Subtle Outline
    doc.setDrawColor(203, 213, 225);
    doc.setLineWidth(0.18);
    doc.roundedRect(barTrackX, rowY, barTrackWidth, barTrackHeight, 0.4, 0.4, 'D');
  });

  // 5B. GRAFIK KANAN: PROPORSI STATUS KESEHATAN INSTITUSI (DONUT CHART)
  const rightChartX = margin + leftTableWidth + 4;
  doc.setFillColor(248, 250, 252);
  doc.setDrawColor(203, 213, 225);
  doc.setLineWidth(0.2);
  doc.roundedRect(rightChartX, chartsStartY, rightTableWidth, chartsHeight, 1.5, 1.5, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7.0);
  doc.setTextColor(15, 23, 42);
  doc.text('GRAFIK PROPORSI KESEHATAN INSTITUSI', rightChartX + 3.2, chartsStartY + 4.2);

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(6.2);
  doc.setTextColor(30, 64, 175);
  doc.text(`Kepatuhan Agregat: ${indeksAgregat}%`, rightChartX + rightTableWidth - 3.2, chartsStartY + 4.2, { align: 'right' });

  // Generate Donut Canvas Data URL
  const donutDataUrl = generateDonutChartBase64(totalPrima, totalPerhatian, total);
  let donutDrawn = false;
  if (donutDataUrl) {
    try {
      doc.addImage(donutDataUrl, 'PNG', rightChartX + 2.0, chartsStartY + 6.5, rightTableWidth - 4.0, 34.5, undefined, 'FAST');
      donutDrawn = true;
    } catch {
      donutDrawn = false;
    }
  }

  if (!donutDrawn) {
    // Fallback Vector Cards jika canvas tidak didukung browser
    const fbBoxW = rightTableWidth - 8.0;
    doc.setFillColor(236, 253, 245);
    doc.setDrawColor(167, 243, 208);
    doc.roundedRect(rightChartX + 4.0, chartsStartY + 7.5, fbBoxW, 14.5, 1.2, 1.2, 'FD');

    doc.setFillColor(16, 185, 129);
    doc.circle(rightChartX + 8.5, chartsStartY + 12.0, 1.8, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.5);
    doc.setTextColor(6, 95, 70);
    doc.text('Sehat Prima (4 Parameter Normal Bebas Risiko)', rightChartX + 13.0, chartsStartY + 12.5);

    doc.setFontSize(9.5);
    doc.setTextColor(4, 120, 87);
    doc.text(`${totalPrima} Pegawai (${totalPrimaPct}%)`, rightChartX + 13.0, chartsStartY + 18.5);

    doc.setFillColor(254, 243, 199);
    doc.setDrawColor(253, 230, 138);
    doc.roundedRect(rightChartX + 4.0, chartsStartY + 24.5, fbBoxW, 14.5, 1.2, 1.2, 'FD');

    doc.setFillColor(245, 158, 11);
    doc.circle(rightChartX + 8.5, chartsStartY + 29.0, 1.8, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.5);
    doc.setTextColor(146, 64, 14);
    doc.text('Perlu Pemantauan (Minimal 1 Indikator Abnormal)', rightChartX + 13.0, chartsStartY + 29.5);

    doc.setFontSize(9.5);
    doc.setTextColor(180, 83, 9);
    doc.text(`${totalPerhatian} Pegawai (${totalPerhatianPct}%)`, rightChartX + 13.0, chartsStartY + 35.5);
  }

  // 6. BOTTOM SECTION: CATATAN & EVALUASI (KIRI) DAN TANDA TANGAN (KANAN)
  // Diposisikan secara rapi DI BAWAH kedua grafik visual
  const bottomSectionY = chartsStartY + chartsHeight + 3.5;
  const notesWidth = leftTableWidth;
  const notesHeight = 26.0;

  doc.setFillColor(248, 250, 252);
  doc.setDrawColor(203, 213, 225);
  doc.setLineWidth(0.2);
  doc.roundedRect(margin, bottomSectionY, notesWidth, notesHeight, 1.5, 1.5, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7.0);
  doc.setTextColor(15, 23, 42);
  doc.text('CATATAN & EVALUASI TIM KERJA OSDM & POLIKLINIK:', margin + 3.0, bottomSectionY + 4.2);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.3);
  doc.setTextColor(51, 65, 85);
  doc.text('• Pegawai berstatus "Sehat Prima" telah memenuhi seluruh rentang nilai normal standar Kemenkes.', margin + 3.0, bottomSectionY + 8.5);
  doc.text('• Pegawai pada kategori "Perlu Pemantauan" disarankan melakukan pembinaan gaya hidup & pola makan seimbang.', margin + 3.0, bottomSectionY + 12.5);
  doc.text('• Konsultasi tindak lanjut dapat dilakukan melalui Klinik Pratama / Layanan Poliklinik Poltekkes Bandung.', margin + 3.0, bottomSectionY + 16.5);

  if (attentionList.length > 0) {
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(30, 64, 175);
    doc.text(`* Rincian ${attentionList.length} pegawai yang memerlukan pemantauan terlampir pada Lembar Lampiran (Halaman 2).`, margin + 3.0, bottomSectionY + 21.5);
  }

  // 7. BLOK TANDA TANGAN RESMI (LEMBAR UTAMA HALAMAN 1 - DI BAWAH GRAFIK KANAN)
  const sigX = rightChartX + (rightTableWidth / 2);
  const sigY = bottomSectionY + 1.0;

  const now = new Date();
  const tglPengesahan = signerCityDate && signerCityDate.trim() !== ''
    ? signerCityDate
    : `Bandung, ${now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}`;

  const labelJabatan = signerTitle && signerTitle.trim() !== ''
    ? signerTitle
    : 'Penanggung Jawab Tim Kerja OSDM,';

  const namaPenandatangan = signerName && signerName.trim() !== ''
    ? signerName
    : 'Tim Kerja OSDM';

  const nipPenandatangan = signerInstitution && signerInstitution.trim() !== ''
    ? signerInstitution
    : '';

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.0);
  doc.setTextColor(30, 41, 59);
  doc.text(tglPengesahan, sigX, sigY, { align: 'center' });
  doc.text(labelJabatan, sigX, sigY + 3.8, { align: 'center' });

  // Tanda Tangan Digital PNG (jika ada)
  if (signerSignaturePng) {
    try {
      const imgWidth = 26;
      const imgHeight = 12;
      const imgX = sigX - imgWidth / 2;
      const imgY = sigY + 4.5;
      doc.addImage(signerSignaturePng, 'PNG', imgX, imgY, imgWidth, imgHeight, undefined, 'FAST');
    } catch {
      // fallback
    }
  }

  // Nama & NIP Penandatangan
  const nameY = sigY + 18.5;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7.5);
  doc.setTextColor(15, 23, 42);
  doc.text(`( ${namaPenandatangan} )`, sigX, nameY, { align: 'center' });

  if (nipPenandatangan) {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.8);
    doc.setTextColor(71, 85, 105);
    doc.text(nipPenandatangan, sigX, nameY + 3.6, { align: 'center' });
  }

  // Footer Halaman 1
  const footerY = pageHeight - 6.5;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(5.8);
  doc.setTextColor(148, 163, 184);
  doc.text(
    `* Dokumen ini dibuat otomatis oleh Sistem Portal OSDM Poltekkes Kemenkes Bandung pada ${todayStr}. Sah tanpa tanda tangan basah.`,
    margin,
    footerY
  );
  doc.text(`Halaman 1 dari ${totalPages}`, pageWidth - margin, footerY, { align: 'right' });

  // ==========================================
  // HALAMAN 2: LAMPIRAN RINCIAN PEGAWAI PERLU PEMANTAUAN (JIKA ADA)
  // ==========================================
  if (attentionList.length > 0) {
    doc.addPage('a4', 'landscape');

    // Header Lampiran
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.setTextColor(15, 23, 42);
    doc.text('LAMPIRAN: DAFTAR PEGAWAI YANG MEMERLUKAN PEMANTAUAN KESEHATAN', margin, margin + 4.0);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.3);
    doc.setTextColor(100, 116, 139);
    doc.text(
      `Pusat Data Monitoring Kebugaran Pegawai — Periode: ${selectedTriwulan}  |  Tahun: ${selectedYear}  |  Keterangan: Tanda bintang (*) menandakan parameter abnormal yang dihitung pada kolom Evaluasi`,
      margin,
      margin + 8.5
    );
    doc.text(`Dicetak: ${todayStr} WIB`, pageWidth - margin, margin + 8.5, { align: 'right' });

    // Table Data Lampiran dengan tanda bintang (*) pada parameter tidak normal
    const annexRows = attentionList.map((emp, idx) => {
      const tensiText = !emp.isBpNormal
        ? `* ${emp.tensiSistolik}/${emp.tensiDiastolik} (${emp.bp.label})`
        : `${emp.tensiSistolik}/${emp.tensiDiastolik} (${emp.bp.label})`;

      const imtText = !emp.isBmiNormal
        ? `* ${emp.bmi.bmi} (${emp.bmi.label})`
        : `${emp.bmi.bmi} (${emp.bmi.label})`;

      const cholText = !emp.isCholNormal
        ? `* ${emp.kolesterol} (${emp.chol.label})`
        : `${emp.kolesterol} (${emp.chol.label})`;

      const sugarText = !emp.isSugarNormal
        ? `* ${emp.gulaDarah} (${emp.sugar.label})`
        : `${emp.gulaDarah} (${emp.sugar.label})`;

      return [
        `${idx + 1}`,
        emp.namaPegawai,
        emp.nip,
        emp.unitKerja,
        formatTanggalPendek(emp.tanggalPeriksa),
        tensiText,
        imtText,
        cholText,
        sugarText,
        `${emp.abnormalCount} KONDISI`
      ];
    });

    autoTable(doc, {
      startY: margin + 12.0,
      margin: { left: margin, right: margin },
      head: [
        [
          'No',
          'Nama Pegawai',
          'NIP',
          'Unit Kerja',
          'Tgl Periksa',
          'Tensi Darah',
          'IMT (Kategori)',
          'Kolesterol',
          'Gula Darah',
          'Evaluasi'
        ]
      ],
      body: annexRows,
      theme: 'grid',
      styles: {
        font: 'helvetica',
        fontSize: 6.2,
        cellPadding: { top: 1.4, bottom: 1.4, left: 1.5, right: 1.5 },
        textColor: [30, 41, 59],
        lineColor: [226, 232, 240],
        lineWidth: 0.2,
      },
      headStyles: {
        fillColor: [15, 23, 42],
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        fontSize: 6.6,
        halign: 'center',
      },
      columnStyles: {
        0: { halign: 'center', cellWidth: 8 },
        1: { fontStyle: 'bold', cellWidth: 42 },
        2: { fontStyle: 'normal', cellWidth: 26 },
        3: { cellWidth: 36 },
        4: { halign: 'center', cellWidth: 20 },
        5: { cellWidth: 38 },
        6: { cellWidth: 32 },
        7: { cellWidth: 26 },
        8: { cellWidth: 26 },
        9: { halign: 'center', fontStyle: 'bold' },
      },
      didParseCell: (data) => {
        // Beri sorotan warna dan format tebal pada sel dengan tanda bintang (*)
        if (data.section === 'body' && [5, 6, 7, 8].includes(data.column.index)) {
          const val = String(data.cell.raw || '');
          if (val.startsWith('*')) {
            data.cell.styles.textColor = [190, 24, 93]; // rose-700
            data.cell.styles.fontStyle = 'bold';
            data.cell.styles.fillColor = [255, 241, 242]; // rose-50 highlight
          }
        }
        // Kolom evaluasi kondisi
        if (data.section === 'body' && data.column.index === 9) {
          data.cell.styles.fontStyle = 'bold';
          data.cell.styles.textColor = [180, 83, 9]; // amber-700
          data.cell.styles.fillColor = [254, 243, 199]; // amber-100 highlight
        }
      },
    });

    // Footer Lampiran
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(5.8);
    doc.setTextColor(148, 163, 184);
    doc.text(
      '* Dokumen ini dibuat otomatis oleh Sistem Portal OSDM Poltekkes Kemenkes Bandung. Lampiran Sah.',
      margin,
      pageHeight - 6.5
    );
    doc.text(`Halaman 2 dari ${totalPages}`, pageWidth - margin, pageHeight - 6.5, { align: 'right' });
  }

  // SAVE FILE
  const filename = customFilename || `Laporan_Eksekutif_Kebugaran_${selectedTriwulan.replace(/\s+/g, '_')}_${Date.now()}.pdf`;
  doc.save(filename);
}

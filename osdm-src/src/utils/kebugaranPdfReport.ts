import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { KebugaranSubmission } from '../types';
import { renderOfficialLandscapeKopSurat } from './officialLandscapeKopSurat';
import {
  calculateBmi,
  classifyBloodPressure,
  classifyBloodSugar,
  classifyCholesterol,
} from './kebugaranUtils';

export interface SignerConfig {
  leftTitle: string;
  leftJabatan: string;
  leftNama: string;
  leftNip: string;
  rightKotaTanggal: string;
  rightJabatan: string;
  rightNama: string;
  rightSubtext: string;
}

export interface GenerateKebugaranPdfOptions {
  submissions: KebugaranSubmission[];
  periodeFilter: string;
  unitFilter: string;
  customKopSuratImage?: string | null;
  signerSettings: SignerConfig;
  customFilename?: string;
}

/**
 * Generate dan Download Laporan Resmi PDF Rekapitulasi Pemeriksaan & Tes Kebugaran Pegawai
 * Mendukung Kop Surat Gambar Kustom (PNG/JPG) dan Pejabat Penandatangan Dinamis.
 */
export async function generateKebugaranPdfReport({
  submissions,
  periodeFilter,
  unitFilter,
  customKopSuratImage,
  signerSettings,
  customFilename,
}: GenerateKebugaranPdfOptions): Promise<void> {
  // A4 Landscape: 297mm x 210mm
  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a4',
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const margin = 12;

  // 1. KOP SURAT RESMI KEMENKES POLTEKKES BANDUNG (MODEL DUA SISI RESMI)
  const { lineY2 } = renderOfficialLandscapeKopSurat(doc, margin, pageWidth);
  const separatorY = lineY2;

  // Metadata Kode Dokumen di pojok kanan bawah kop
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.5);
  doc.setTextColor(100, 116, 139);
  doc.text('KODE: OSDM-KBG-2026', pageWidth - margin, separatorY + 3.5, { align: 'right' });

  // 2. JUDUL DOKUMEN & SUBTITLE
  const titleY = separatorY + 7;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.setTextColor(15, 23, 42);
  doc.text('REKAPITULASI HASIL PEMERIKSAAN & TES KEBUGARAN PEGAWAI', pageWidth / 2, titleY, {
    align: 'center',
  });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(71, 85, 105);
  const subtitleText = `Periode Pemeriksaan: ${periodeFilter}  •  Unit Kerja: ${unitFilter}`;
  doc.text(subtitleText, pageWidth / 2, titleY + 4.5, { align: 'center' });

  // 3. KPI / RINGKASAN STATISTIK
  const totalSubmissions = submissions.length;
  const normalBpCount = submissions.filter((s) => s.tensiSistolik < 120 && s.tensiDiastolik < 80).length;
  const normalCholCount = submissions.filter((s) => s.kolesterol < 200).length;
  const idealBmiCount = submissions.filter((s) => {
    const b = calculateBmi(s.beratBadan, s.tinggiBadan).bmi;
    return b >= 18.5 && b <= 22.9;
  }).length;

  const kpiY = titleY + 7.5;
  const kpiBoxHeight = 8.5;
  const kpiBoxWidth = (pageWidth - 2 * margin - 9) / 4;

  const kpis = [
    {
      label: 'TOTAL TERDATA',
      value: `${totalSubmissions} Pegawai`,
      sub: 'Pemeriksaan Tercatat',
      r: 15, g: 23, b: 42,
    },
    {
      label: 'TENSI NORMAL (<120/80)',
      value: `${normalBpCount} Orang`,
      sub: totalSubmissions > 0 ? `${Math.round((normalBpCount / totalSubmissions) * 100)}% dari total` : '0%',
      r: 16, g: 185, b: 129,
    },
    {
      label: 'KOLESTEROL AMAN (<200)',
      value: `${normalCholCount} Orang`,
      sub: totalSubmissions > 0 ? `${Math.round((normalCholCount / totalSubmissions) * 100)}% dari total` : '0%',
      r: 37, g: 99, b: 235,
    },
    {
      label: 'IMT IDEAL (18.5 - 22.9)',
      value: `${idealBmiCount} Orang`,
      sub: totalSubmissions > 0 ? `${Math.round((idealBmiCount / totalSubmissions) * 100)}% dari total` : '0%',
      r: 124, g: 58, b: 237,
    },
  ];

  kpis.forEach((kpi, idx) => {
    const x = margin + idx * (kpiBoxWidth + 3);
    doc.setFillColor(248, 250, 252);
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.2);
    doc.roundedRect(x, kpiY, kpiBoxWidth, kpiBoxHeight, 1, 1, 'FD');

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(5.5);
    doc.setTextColor(100, 116, 139);
    doc.text(kpi.label, x + 2.5, kpiY + 2.8);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(kpi.r, kpi.g, kpi.b);
    doc.text(kpi.value, x + 2.5, kpiY + 6.2);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(5.5);
    doc.setTextColor(100, 116, 139);
    doc.text(kpi.sub, x + kpiBoxWidth - 2.5, kpiY + 6.2, { align: 'right' });
  });

  // 4. TABEL REKAPITULASI PEMERIKSAAN
  const tableData = submissions.map((sub, idx) => {
    const bmi = calculateBmi(sub.beratBadan, sub.tinggiBadan);
    const bp = classifyBloodPressure(sub.tensiSistolik, sub.tensiDiastolik);
    return [
      (idx + 1).toString(),
      `${sub.namaPegawai}\nNIP: ${sub.nip}`,
      sub.unitKerja,
      sub.tanggalPeriksa,
      `${sub.tensiSistolik}/${sub.tensiDiastolik}\n(${bp.label})`,
      `${sub.beratBadan}k/${sub.tinggiBadan}c\nIMT: ${bmi.bmi}`,
      sub.lingkarPinggang ? `${sub.lingkarPinggang} cm` : '-',
      `${sub.gulaDarah}\n(${sub.tipeGulaDarah})`,
      `${sub.kolesterol} mg/dL`,
      sub.fasyankes,
    ];
  });

  autoTable(doc, {
    startY: kpiY + kpiBoxHeight + 3,
    margin: { left: margin, right: margin, bottom: 28 },
    head: [
      [
        'No',
        'NIP & Nama Pegawai',
        'Unit Kerja',
        'Tgl Periksa',
        'Tensi (mmHg)',
        'BB/TB (IMT)',
        'LP (cm)',
        'Gula Darah',
        'Kolesterol',
        'Fasyankes',
      ],
    ],
    body: tableData.length > 0 ? tableData : [['-', 'Tidak ada data pemeriksaan', '-', '-', '-', '-', '-', '-', '-', '-']],
    theme: 'grid',
    styles: {
      font: 'helvetica',
      fontSize: 6.5,
      cellPadding: 1.5,
      overflow: 'linebreak',
      valign: 'middle',
    },
    headStyles: {
      fillColor: [15, 23, 42], // slate-900
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      fontSize: 7,
      halign: 'center',
    },
    alternateRowStyles: {
      fillColor: [248, 250, 252],
    },
    columnStyles: {
      0: {
        halign: 'center',
        cellWidth: 11,
        cellPadding: { top: 1.8, bottom: 1.8, left: 0.5, right: 0.5 },
      },
      1: { cellWidth: 44 },
      2: { cellWidth: 36 },
      3: { halign: 'center', cellWidth: 19 },
      4: { halign: 'center', cellWidth: 26 },
      5: { halign: 'center', cellWidth: 26 },
      6: { halign: 'center', cellWidth: 16 },
      7: { halign: 'center', cellWidth: 26 },
      8: { halign: 'center', cellWidth: 24 },
      9: { cellWidth: 'auto' },
    },
    didDrawPage: () => {
      // Footer page numbering
      const totalPages = (doc as any).internal.getNumberOfPages();
      const pageNumber = (doc as any).internal.getCurrentPageInfo().pageNumber;

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(6.5);
      doc.setTextColor(148, 163, 184);

      doc.text(
        'Laporan Rekapitulasi Kebugaran Pegawai • Poltekkes Kemenkes Bandung • Dokumen Resmi Kedinasan',
        margin,
        pageHeight - 6
      );
      doc.text(
        `Halaman ${pageNumber} dari ${totalPages}`,
        pageWidth - margin,
        pageHeight - 6,
        { align: 'right' }
      );
    },
  });

  // 5. BLOK PENGESAHAN & TANDA TANGAN DINAMIS (Kiri & Kanan)
  const lastTableY = (doc as any).lastAutoTable ? (doc as any).lastAutoTable.finalY : 140;

  // Space check: need at least 32mm for signatures
  if (lastTableY > pageHeight - 34) {
    doc.addPage();
  }

  const signY = lastTableY > pageHeight - 34 ? 18 : lastTableY + 5;

  // Pihak Kiri (Mengetahui)
  const leftX = margin + 4;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(100, 116, 139);
  if (signerSettings.leftTitle) {
    doc.text(signerSettings.leftTitle, leftX, signY);
  }
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(15, 23, 42);
  doc.text(signerSettings.leftJabatan, leftX, signY + 4);

  doc.text(signerSettings.leftNama, leftX, signY + 22);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.5);
  doc.setTextColor(100, 116, 139);
  const formattedLeftNip = signerSettings.leftNip.trim().toLowerCase().startsWith('nip')
    ? signerSettings.leftNip
    : `NIP. ${signerSettings.leftNip}`;
  doc.text(formattedLeftNip, leftX, signY + 25.5);

  // Pihak Kanan (Pembuat Rekap / Pengesah)
  const rightX = pageWidth - margin - 75;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(100, 116, 139);
  doc.text(signerSettings.rightKotaTanggal, rightX, signY);

  doc.setFont('helvetica', 'bold');
  doc.setTextColor(15, 23, 42);
  doc.text(signerSettings.rightJabatan, rightX, signY + 4);

  doc.text(signerSettings.rightNama, rightX, signY + 22);
  if (signerSettings.rightSubtext) {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.setTextColor(100, 116, 139);
    doc.text(signerSettings.rightSubtext, rightX, signY + 25.5);
  }

  // 6. SIMPAN FILE PDF
  const dateIso = new Date().toISOString().slice(0, 10);
  const filename = customFilename || `Laporan_Rekap_Kebugaran_Poltekkes_${dateIso}.pdf`;
  doc.save(filename);
}

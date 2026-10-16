import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { WfaSubmission } from '../types';
import { renderOfficialLandscapeKopSurat } from './officialLandscapeKopSurat';

// Format tanggal panjang Indonesia: "01 Oktober 2026"
const formatDateIndoLong = (dateStr: string): string => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
};

export interface GeneratePdfOptions {
  submissions: WfaSubmission[];
  title?: string;
  subtitle?: string;
  filterLabel?: string;
  periodLabel?: string;
  generatedBy?: string;
  customFilename?: string;
  customKopSuratImage?: string | null;
  // Kustomisasi Penandatangan & TTD Digital PNG
  signerCityDate?: string;
  signerTitle?: string;
  signerName?: string;
  signerInstitution?: string;
  signerSignaturePng?: string | null;
}

/**
 * Generate dan Download Laporan Resmi PDF Dashboard Pengajuan WFA Bimbingan
 * Mendukung Kop Surat Bawaan atau Gambar Kop Surat Kustom (PNG/JPG) yang diunggah pengguna.
 */
export async function generateWfaPdfReport({
  submissions,
  title = 'LAPORAN REKAPITULASI PENGAJUAN WORK FROM ANYWHERE (WFA) BIMBINGAN',
  subtitle = 'PANGKALAN DATA MONITORING & VERIFIKASI PRESENSI TIM KERJA OSDM',
  filterLabel = 'Semua Pengajuan',
  periodLabel = 'Semua Periode',
  generatedBy = 'Tim Kerja OSDM Poltekkes Kemenkes Bandung',
  customFilename,
  customKopSuratImage,
  signerCityDate,
  signerTitle,
  signerName,
  signerInstitution,
  signerSignaturePng,
}: GeneratePdfOptions): Promise<void> {
  // A4 Landscape orientation: 297mm x 210mm
  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a4',
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const margin = 14;

  // KPI Metrics calculation
  const totalSubmissions = submissions.length;
  const validCount = submissions.filter((s) => s.status === 'Valid').length;
  const pendingCount = submissions.filter((s) => s.status === 'Menunggu Validasi').length;
  const rejectedCount = submissions.filter((s) => s.status === 'Ditolak').length;

  // Count by location
  const locBandungKota = submissions.filter((s) => s.lokasiKegiatan === 'Kota Bandung').length;
  const locBandungKab = submissions.filter((s) => s.lokasiKegiatan === 'Kabupaten Bandung').length;
  const locBogorKota = submissions.filter((s) => s.lokasiKegiatan === 'Kota Bogor').length;
  const locBogorKab = submissions.filter((s) => s.lokasiKegiatan === 'Kabupaten Bogor').length;
  const locKarawangKota = submissions.filter((s) => s.lokasiKegiatan === 'Kota Karawang').length;
  const locKarawangKab = submissions.filter((s) => s.lokasiKegiatan === 'Kabupaten Karawang').length;

  // 1. KOP SURAT RESMI KEMENKES POLTEKKES BANDUNG (MODEL DUA SISI RESMI)
  const { lineY2 } = renderOfficialLandscapeKopSurat(doc, margin, pageWidth);

  // 2. JUDUL LAPORAN & METADATA
  const titleY = lineY2 + 5.0;
  const subtitleY = titleY + 4.0;
  const dateY = subtitleY + 4.5;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text(title, pageWidth / 2, titleY, { align: 'center' });

  doc.setFontSize(8.5);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(100, 116, 139);
  doc.text(subtitle, pageWidth / 2, subtitleY, { align: 'center' });

  // Tanggal cetak & filter
  const todayStr = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'full',
    timeStyle: 'short',
  }).format(new Date());

  doc.setFontSize(8);
  doc.setTextColor(30, 41, 59);
  doc.text(`Periode: ${periodLabel}   |   Dicetak: ${todayStr} WIB`, margin, dateY);
  doc.text(`Kriteria: ${filterLabel}`, pageWidth - margin, dateY, { align: 'right' });

  // 3. MINI DASHBOARD KPI CARDS (4 Boxes across page)
  const cardY = dateY + 3.0;
  const cardHeight = 15;
  const cardGap = 4;
  const totalWidth = pageWidth - margin * 2;
  const cardWidth = (totalWidth - cardGap * 3) / 4;

  const kpis = [
    {
      title: 'TOTAL PENGAJUAN',
      value: `${totalSubmissions}`,
      sub: 'Berkas Terdata',
      bgR: 241, bgG: 245, bgB: 249, // slate-100
      borderR: 203, borderG: 213, borderB: 225,
      textR: 30, textG: 41, textB: 59,
    },
    {
      title: 'DISETUJUI (VALID)',
      value: `${validCount}`,
      sub: 'Jadwal Presensi Aktif',
      bgR: 236, bgG: 253, bgB: 245, // emerald-50
      borderR: 167, borderG: 243, borderB: 208,
      textR: 6, textG: 95, textB: 70, // emerald-800
    },
    {
      title: 'MENUNGGU VALIDASI',
      value: `${pendingCount}`,
      sub: 'Butuh Verifikasi OSDM',
      bgR: 254, bgG: 243, bgB: 199, // amber-100
      borderR: 253, borderG: 230, borderB: 138,
      textR: 146, textG: 64, textB: 14, // amber-800
    },
    {
      title: 'DITOLAK / REVISI',
      value: `${rejectedCount}`,
      sub: 'Berkas Tidak Sesuai',
      bgR: 255, bgG: 241, bgB: 242, // rose-50
      borderR: 254, borderG: 205, borderB: 211,
      textR: 159, textG: 18, textB: 57, // rose-800
    },
  ];

  kpis.forEach((kpi, idx) => {
    const x = margin + idx * (cardWidth + cardGap);
    // Card background
    doc.setFillColor(kpi.bgR, kpi.bgG, kpi.bgB);
    doc.setDrawColor(kpi.borderR, kpi.borderG, kpi.borderB);
    doc.setLineWidth(0.3);
    doc.roundedRect(x, cardY, cardWidth, cardHeight, 1.5, 1.5, 'FD');

    // Title
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.5);
    doc.setTextColor(100, 116, 139);
    doc.text(kpi.title, x + 3, cardY + 4.2);

    // Value
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.setTextColor(kpi.textR, kpi.textG, kpi.textB);
    doc.text(kpi.value, x + 3, cardY + 10);

    // Subtitle
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.setTextColor(100, 116, 139);
    doc.text(kpi.sub, x + 3, cardY + 13.5);
  });

  // Wilayah Breakdown Sub-line
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(71, 85, 105);
  const locSummary = `Distribusi Wilayah: Bandung (${locBandungKota} Kota / ${locBandungKab} Kab) • Bogor (${locBogorKota} Kota / ${locBogorKab} Kab) • Karawang (${locKarawangKota} Kota / ${locKarawangKab} Kab)`;
  doc.text(locSummary, margin, cardY + cardHeight + 4.5);

  // 4. TABEL DETAIL PENGAJUAN WFA (AutoTable)
  const tableData = submissions.map((sub, index) => {
    return [
      (index + 1).toString(),
      sub.nip ? sub.nip.trim() : '-',
      sub.employeeName,
      sub.unitKerja || '-',
      formatDateIndoLong(sub.tanggalWfa),
      sub.lokasiKegiatan,
      sub.lokasiLahanBimbingan || '-',
      sub.statusWfa,
      sub.status,
      sub.catatanPengelola || '-',
    ];
  });

  autoTable(doc, {
    startY: cardY + cardHeight + 7,
    margin: { left: margin, right: margin, bottom: 25 },
    head: [
      [
        'No',
        'NIP',
        'Nama Pegawai',
        'Unit Kerja',
        'Tanggal WFA',
        'Wilayah',
        'Lokasi Lahan',
        'Presensi',
        'Status',
        'Catatan OSDM',
      ],
    ],
    body: tableData,
    theme: 'grid',
    styles: {
      font: 'helvetica',
      fontSize: 7,
      cellPadding: 1.8,
      overflow: 'linebreak',
      valign: 'middle',
    },
    headStyles: {
      fillColor: [15, 23, 42], // slate-900
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      fontSize: 7.5,
      halign: 'center',
    },
    alternateRowStyles: {
      fillColor: [248, 250, 252], // slate-50
    },
    columnStyles: {
      0: {
        halign: 'center',
        cellWidth: 11,
        cellPadding: { top: 1.8, bottom: 1.8, left: 0.5, right: 0.5 },
      },
      1: {
        halign: 'center',
        cellWidth: 34,
        fontSize: 7,
        cellPadding: { top: 1.8, bottom: 1.8, left: 1, right: 1 },
      },
      2: { cellWidth: 38 },
      3: { cellWidth: 26 },
      4: { halign: 'center', cellWidth: 18 },
      5: { cellWidth: 26 },
      6: { cellWidth: 36 },
      7: { cellWidth: 25 },
      8: { halign: 'center', cellWidth: 22 },
      9: { cellWidth: 'auto' },
    },
    didParseCell: (data) => {
      // Color status column based on value
      if (data.section === 'body' && data.column.index === 8) {
        const val = String(data.cell.raw);
        if (val === 'Valid') {
          data.cell.styles.textColor = [5, 150, 105]; // emerald-600
          data.cell.styles.fontStyle = 'bold';
        } else if (val === 'Menunggu Validasi') {
          data.cell.styles.textColor = [217, 119, 6]; // amber-600
          data.cell.styles.fontStyle = 'bold';
        } else if (val === 'Ditolak') {
          data.cell.styles.textColor = [225, 29, 72]; // rose-600
          data.cell.styles.fontStyle = 'bold';
        }
      }
    },
    didDrawPage: () => {
      // Footer page numbering on each page
      const currentPages = (doc as any).internal.getNumberOfPages();
      const pageCurrent = (doc as any).internal.getCurrentPageInfo().pageNumber;

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(7);
      doc.setTextColor(148, 163, 184);

      doc.text(
        'Sistem Monitoring WFA Bimbingan • Poltekkes Kemenkes Bandung • Dokumen Sah Kedinasan',
        margin,
        pageHeight - 8
      );
      doc.text(
        `Halaman ${pageCurrent} dari ${currentPages}`,
        pageWidth - margin,
        pageHeight - 8,
        { align: 'right' }
      );
    },
  });

  // 5. BLOK PENGESAHAN / TANDA TANGAN TIM KERJA OSDM
  const lastTableY = (doc as any).lastAutoTable ? (doc as any).lastAutoTable.finalY : 140;
  
  // If not enough space on current page for signature block, add a new page
  if (lastTableY > pageHeight - 38) {
    doc.addPage();
  }

  const signY = (lastTableY > pageHeight - 38 ? 20 : lastTableY + 8);
  const signX = pageWidth - margin - 65;

  const formattedDateNow = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date());

  const displayCityDate = (signerCityDate && signerCityDate.trim()) || `Bandung, ${formattedDateNow}`;
  const displayTitle = (signerTitle && signerTitle.trim()) || 'Pengelola Kepegawaian (OSDM)';
  const rawName = (signerName && signerName.trim()) || 'Tim Kerja Organisasi & SDM';
  const displayName = rawName.startsWith('(') ? rawName : `( ${rawName} )`;
  const displayInst = (signerInstitution && signerInstitution.trim()) || 'Poltekkes Kemenkes Bandung';

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(15, 23, 42);
  doc.text(displayCityDate, signX, signY);
  doc.setFont('helvetica', 'bold');
  doc.text(displayTitle, signX, signY + 4.5);

  // Tanda Tangan Digital PNG (Jika diunggah)
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
      doc.addImage(signerSignaturePng, 'PNG', signX + 4, signY + 5.5, sigW, sigH);
    } catch (e) {
      console.warn('Gagal memuat ttd digital:', e);
    }
  }

  // TTD line & Nama
  doc.setDrawColor(203, 213, 225);
  doc.line(signX, signY + 20, signX + 55, signY + 20);

  doc.setFont('helvetica', 'bold');
  doc.text(displayName, signX, signY + 24);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(6.8);
  doc.setTextColor(100, 116, 139);
  doc.text(displayInst, signX, signY + 27.5);

  // Save the PDF
  const filename = customFilename || `Laporan_WFA_Bimbingan_Poltekkes_${new Date().toISOString().slice(0, 10)}.pdf`;
  doc.save(filename);
}

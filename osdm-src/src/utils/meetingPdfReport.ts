import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { MeetingSession, MeetingAttendanceRecord } from '../types';
import { getKopSuratDataUrl } from './kopSuratCanvas';

export interface GenerateMeetingPdfOptions {
  session: MeetingSession;
  attendees: MeetingAttendanceRecord[];
  customKopSuratImage?: string | null;
  customFilename?: string;
}

const MONTH_NAMES = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
];

const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function formatIndoDateWithDay(dateStr: string): string {
  if (!dateStr) return '-';
  try {
    const [year, month, day] = dateStr.split('-');
    if (!year || !month || !day) return dateStr;
    const d = new Date(parseInt(year, 10), parseInt(month, 10) - 1, parseInt(day, 10));
    const dayName = DAY_NAMES[d.getDay()] || '';
    const mIdx = parseInt(month, 10) - 1;
    return `${dayName}, ${parseInt(day, 10)} ${MONTH_NAMES[mIdx] || month} ${year}`;
  } catch {
    return dateStr;
  }
}

function formatIndoDateOnly(dateStr: string): string {
  if (!dateStr) return '-';
  try {
    const [year, month, day] = dateStr.split('-');
    if (!year || !month || !day) return dateStr;
    const mIdx = parseInt(month, 10) - 1;
    return `${parseInt(day, 10)} ${MONTH_NAMES[mIdx] || month} ${year}`;
  } catch {
    return dateStr;
  }
}

interface SignatureInfo {
  dataUrl: string;
  width: number;
  height: number;
  aspectRatio: number;
}

/**
 * Pre-rasterize an image data URL to a clean PNG data URL via offscreen canvas
 * while PRESERVING its exact natural dimensions and aspect ratio.
 */
async function prepareSignatureImage(dataUrl: string): Promise<SignatureInfo | null> {
  if (!dataUrl) return null;
  if (typeof window === 'undefined' || typeof Image === 'undefined') {
    return { dataUrl, width: 120, height: 60, aspectRatio: 2 };
  }

  return new Promise((resolve) => {
    try {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = () => {
        const natW = img.naturalWidth || 120;
        const natH = img.naturalHeight || 60;
        const ratio = natW / (natH || 1);

        try {
          const canvas = document.createElement('canvas');
          canvas.width = natW;
          canvas.height = natH;
          const ctx = canvas.getContext('2d');
          if (ctx) {
            ctx.clearRect(0, 0, natW, natH);
            ctx.drawImage(img, 0, 0, natW, natH);
            resolve({
              dataUrl: canvas.toDataURL('image/png'),
              width: natW,
              height: natH,
              aspectRatio: ratio > 0 ? ratio : 2,
            });
            return;
          }
        } catch {
          // fallback
        }

        resolve({
          dataUrl,
          width: natW,
          height: natH,
          aspectRatio: ratio > 0 ? ratio : 2,
        });
      };
      img.onerror = () => resolve(null);
      img.src = dataUrl;
    } catch {
      resolve(null);
    }
  });
}

/**
 * Generate dan Unduh Dokumen Rekapitulasi Daftar Hadir Rapat Resmi Poltekkes Kemenkes Bandung
 */
export async function generateMeetingPdfReport({
  session,
  attendees,
  customKopSuratImage,
  customFilename,
}: GenerateMeetingPdfOptions): Promise<void> {
  // A4 Portrait: 210mm x 297mm
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const margin = 14;
  const contentWidth = pageWidth - margin * 2;

  // 1. KOP SURAT RESMI
  let kopBannerWidth = 182;
  let kopBannerHeight = 31.6;
  let kopBannerX = (pageWidth - kopBannerWidth) / 2;
  const kopBannerY = 8;
  let lineY = kopBannerY + kopBannerHeight + 2.5;

  try {
    const rawImage = customKopSuratImage || (await getKopSuratDataUrl());
    if (rawImage) {
      if (typeof window !== 'undefined' && typeof Image !== 'undefined') {
        const img = new Image();
        img.src = rawImage;
        await new Promise<void>((resolve) => {
          if (img.complete) return resolve();
          img.onload = () => resolve();
          img.onerror = () => resolve();
        });
        if (img.naturalWidth && img.naturalHeight) {
          const ratio = img.naturalWidth / img.naturalHeight;
          kopBannerHeight = kopBannerWidth / ratio;
          if (kopBannerHeight > 40) {
            kopBannerHeight = 40;
            kopBannerWidth = Math.min(182, 40 * ratio);
            kopBannerX = (pageWidth - kopBannerWidth) / 2;
          }
        }
      }

      lineY = kopBannerY + kopBannerHeight + 2.5;
      doc.addImage(rawImage, 'PNG', kopBannerX, kopBannerY, kopBannerWidth, kopBannerHeight);

      // Garis ganda pembatas Kop Surat resmi (Standar Tata Naskah Dinas: Garis Ganda Hitam Tebal-Tipis)
      doc.setDrawColor(0, 0, 0); // Hitam pekat
      doc.setLineWidth(0.8);
      doc.line(margin, lineY, pageWidth - margin, lineY);

      doc.setDrawColor(0, 0, 0); // Hitam pekat
      doc.setLineWidth(0.25);
      doc.line(margin, lineY + 1.1, pageWidth - margin, lineY + 1.1);
    }
  } catch (err) {
    console.warn('Error loading Kop Surat for meeting PDF:', err);
  }

  // 2. JUDUL DOKUMEN (Dengan Jarak Proporsional, Elegan & Tidak Mepet ke Kop Surat)
  let currentY = lineY + 8.5; // Jarak lega & proporsional dari garis kop surat

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(13);
  doc.setTextColor(15, 23, 42);
  doc.text('DAFTAR HADIR RAPAT', pageWidth / 2, currentY, { align: 'center' });

  // Hilangkan duplikasi agenda di atas:
  // Subjudul hanya ditampilkan jika session.title memang merupakan nama kegiatan spesifik yang BERBEDA dari agenda
  const cleanTitle = (session.title || '').trim();
  const cleanAgenda = (session.agenda || '').trim();
  const isSameAsAgenda =
    !cleanTitle ||
    cleanTitle.toLowerCase() === cleanAgenda.toLowerCase() ||
    cleanTitle.toLowerCase().includes(cleanAgenda.toLowerCase()) ||
    cleanAgenda.toLowerCase().includes(cleanTitle.toLowerCase());
  const isGenericTitle = cleanTitle.toUpperCase() === 'DAFTAR HADIR RAPAT' || cleanTitle.toUpperCase() === 'DAFTAR HADIR';

  if (!isGenericTitle && !isSameAsAgenda) {
    currentY += 5;
    doc.setFontSize(10.5);
    doc.setTextColor(30, 41, 59);
    const titleLines = doc.splitTextToSize(cleanTitle.toUpperCase(), contentWidth - 10);
    doc.text(titleLines, pageWidth / 2, currentY, { align: 'center' });
    currentY += titleLines.length * 4.5 + 4;
  } else {
    currentY += 6; // Jarak 6mm ke detail box informasi rapat
  }

  // 3. BLOK DETAIL INFORMASI RAPAT
  const detailBoxX = margin;
  const detailBoxY = currentY;
  const detailBoxWidth = contentWidth;
  
  const leftColX = detailBoxX + 4;
  const leftValX = detailBoxX + 28;
  const rightColX = detailBoxX + 98;
  const rightValX = detailBoxX + 118;

  doc.setFontSize(8.5);
  const agendaText = doc.splitTextToSize(session.agenda || '-', contentWidth - (leftValX - detailBoxX) - 4);
  const extraAgendaHeight = Math.max(0, (agendaText.length - 1) * 3.8);
  const detailBoxHeight = 22 + extraAgendaHeight;

  // Background box lembut
  doc.setFillColor(248, 250, 252);
  doc.setDrawColor(203, 213, 225);
  doc.setLineWidth(0.3);
  doc.roundedRect(detailBoxX, detailBoxY, detailBoxWidth, detailBoxHeight, 2, 2, 'FD');

  // Baris 1
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(71, 85, 105);
  doc.text('Hari / Tanggal', leftColX, detailBoxY + 5.5);
  doc.text(':', leftValX - 2.5, detailBoxY + 5.5);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(15, 23, 42);
  doc.text(formatIndoDateWithDay(session.date), leftValX, detailBoxY + 5.5);

  doc.setFont('helvetica', 'bold');
  doc.setTextColor(71, 85, 105);
  doc.text('Tempat', rightColX, detailBoxY + 5.5);
  doc.text(':', rightValX - 2.5, detailBoxY + 5.5);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(15, 23, 42);
  const locLines = doc.splitTextToSize(session.location || 'Poltekkes Kemenkes Bandung', contentWidth - (rightValX - detailBoxX) - 3);
  doc.text(locLines[0] || '-', rightValX, detailBoxY + 5.5);

  // Baris 2
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(71, 85, 105);
  doc.text('Waktu / Jam', leftColX, detailBoxY + 11);
  doc.text(':', leftValX - 2.5, detailBoxY + 11);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(15, 23, 42);
  const timeStr = `${session.timeStart || '09:00'} s.d. ${session.timeEnd || 'Selesai'} WIB`;
  doc.text(timeStr, leftValX, detailBoxY + 11);

  doc.setFont('helvetica', 'bold');
  doc.setTextColor(71, 85, 105);
  doc.text('Pimpinan', rightColX, detailBoxY + 11);
  doc.text(':', rightValX - 2.5, detailBoxY + 11);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(15, 23, 42);
  doc.text(session.pimpinanRapat || '-', rightValX, detailBoxY + 11);

  // Baris 3: Agenda
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(71, 85, 105);
  doc.text('Agenda', leftColX, detailBoxY + 16.5);
  doc.text(':', leftValX - 2.5, detailBoxY + 16.5);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(15, 23, 42);
  doc.text(agendaText, leftValX, detailBoxY + 16.5);

  currentY = detailBoxY + detailBoxHeight + 5;

  // Urutkan peserta secara kronologis berdasarkan waktu mengisi daftar hadir:
  // Yang mengisi lebih awal berada di posisi paling atas, semakin terlambat semakin di bawah
  const sortedAttendees = [...attendees].sort((a, b) => {
    const timeA = a.timestamp ? new Date(a.timestamp).getTime() : 0;
    const timeB = b.timestamp ? new Date(b.timestamp).getTime() : 0;

    if (timeA && timeB && !isNaN(timeA) && !isNaN(timeB)) {
      return timeA - timeB;
    }
    if (timeA && !isNaN(timeA)) return -1;
    if (timeB && !isNaN(timeB)) return 1;

    // Fallback: urutan numerik ID (e.g. att-178972...)
    const numA = parseInt((a.id || '').replace(/\D/g, ''), 10) || 0;
    const numB = parseInt((b.id || '').replace(/\D/g, ''), 10) || 0;
    return numA - numB;
  });

  // Pre-rasterize all attendee signatures to PNG with natural aspect ratio
  const rasterizedSignatures: Record<string, SignatureInfo | null> = {};
  for (const att of sortedAttendees) {
    if (att.signatureDataUrl) {
      rasterizedSignatures[att.id] = await prepareSignatureImage(att.signatureDataUrl);
    }
  }

  // 4. TABEL PRESENSI PESERTA RAPAT
  const tableHeaders = [['No', 'Nama Lengkap & NIP', 'Unit Kerja', 'Jabatan', 'Tanda Tangan / Paraf']];

  const tableBody = sortedAttendees.map((att, idx) => {
    const hasSignature = !!att.signatureDataUrl;
    return [
      (idx + 1).toString(),
      `${att.name}\nNIP: ${att.nip || '-'}`,
      att.unitKerja || '-',
      att.jabatan || '-',
      hasSignature
        ? '' // Biarkan kosong agar paraf digital tampil bersih tanpa bertumpuk dengan teks/titik-titik
        : idx % 2 === 0
        ? `${idx + 1}. ....................`
        : `    ${idx + 1}. ....................`,
    ];
  });

  // If table is empty, display placeholder row
  if (tableBody.length === 0) {
    tableBody.push(['-', 'Belum ada peserta yang mengisi daftar hadir', '-', '-', '-']);
  }

  autoTable(doc, {
    head: tableHeaders,
    body: tableBody,
    startY: currentY,
    margin: { left: margin, right: margin, bottom: 20 },
    theme: 'grid',
    styles: {
      font: 'helvetica',
      fontSize: 8,
      textColor: [15, 23, 42],
      lineColor: [203, 213, 225],
      lineWidth: 0.2,
      cellPadding: { top: 2.5, bottom: 2.5, left: 2.5, right: 2.5 },
      minCellHeight: 14, // Tinggi sel pas dan lega untuk paraf proporsional
      valign: 'middle',
    },
    headStyles: {
      fillColor: [15, 118, 110], // Dark Teal OSDM
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      halign: 'center',
      minCellHeight: 8,
    },
    columnStyles: {
      0: { cellWidth: 10, halign: 'center' }, // No
      1: { cellWidth: 56 }, // Nama & NIP
      2: { cellWidth: 42 }, // Unit Kerja
      3: { cellWidth: 42 }, // Jabatan
      4: { cellWidth: 32, halign: 'center' }, // Paraf
    },
    didDrawCell: (data) => {
      // Draw participant digital signature image in column index 4 (Tanda Tangan / Paraf)
      if (data.column.index === 4 && data.section === 'body') {
        const attendee = sortedAttendees[data.row.index];
        if (attendee && rasterizedSignatures[attendee.id]) {
          try {
            const sig = rasterizedSignatures[attendee.id];
            if (sig && sig.dataUrl) {
              const cell = data.cell;
              const padX = 2; // Margin padding kiri-kanan
              const padY = 1.5; // Margin padding atas-bawah
              const maxW = Math.max(10, cell.width - padX * 2); // Maks 28mm
              const maxH = Math.max(8, cell.height - padY * 2); // Maks 11mm

              const sigRatio = sig.aspectRatio > 0 ? sig.aspectRatio : 2;

              // Hitung ukuran dengan mempertahankan rasio aspek asli (tidak gepeng)
              let drawW = maxW;
              let drawH = drawW / sigRatio;

              if (drawH > maxH) {
                drawH = maxH;
                drawW = drawH * sigRatio;
              }

              // Posisikan tepat di tengah sel tabel (center alignment)
              const drawX = cell.x + (cell.width - drawW) / 2;
              const drawY = cell.y + (cell.height - drawH) / 2;

              doc.addImage(sig.dataUrl, 'PNG', drawX, drawY, drawW, drawH);
            }
          } catch {
            // Ignore signature rendering failure
          }
        }
      }
    },
  });

  // 5. TANDA TANGAN PENGESAHAN PIMPINAN RAPAT
  // Check if we need a new page or there's enough space
  // @ts-ignore
  let finalY = doc.lastAutoTable?.finalY || currentY + 40;

  // Needed height for signature block ~38mm
  if (finalY + 40 > pageHeight - margin) {
    doc.addPage();
    finalY = margin + 10;
  } else {
    finalY += 8;
  }

  const sigBlockX = pageWidth - margin - 65;
  doc.setFontSize(8.5);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(15, 23, 42);

  doc.text(`Bandung, ${formatIndoDateOnly(session.date)}`, sigBlockX, finalY);
  finalY += 4.5;
  doc.text('Pimpinan Rapat / Penanggung Jawab,', sigBlockX, finalY);

  // Space for physical signature
  finalY += 20;

  doc.setFont('helvetica', 'bold');
  doc.text(session.pimpinanRapat || 'Pimpinan Rapat', sigBlockX, finalY);
  finalY += 4;
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(71, 85, 105);
  doc.text(session.jabatanPimpinan || 'Poltekkes Kemenkes Bandung', sigBlockX, finalY);

  // 6. NOMOR HALAMAN DAN FOOTER RESMI
  const totalPages = doc.internal.pages.length - 1;
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(148, 163, 184);

    // Left: Generated timestamp
    const nowStr = new Date().toLocaleString('id-ID', {
      dateStyle: 'medium',
      timeStyle: 'short',
    });
    doc.text(`Dicetak melalui Portal OSDM Poltekkes Kemenkes Bandung pada ${nowStr} WIB`, margin, pageHeight - 6);

    // Right: Page number
    doc.text(`Halaman ${i} dari ${totalPages}`, pageWidth - margin, pageHeight - 6, { align: 'right' });
  }

  // Generate filename
  const filenameSlug = (session.title || session.agenda || 'rapat')
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '_')
    .substring(0, 35);
  const filename = customFilename || `Daftar_Hadir_${filenameSlug}_${session.date}.pdf`;

  doc.save(filename);
}

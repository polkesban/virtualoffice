import jsPDF from 'jspdf';
import { KEMENKES_POLKESBAN_LOGO_DATA_URL } from './kemenkesLogoBase64';

export interface RenderLandscapeKopResult {
  lineY1: number;
  lineY2: number;
  contentStartY: number;
}

/**
 * Render Kop Surat Resmi Dua Sisi Terpisah untuk Dokumen A4 Landscape (297mm x 210mm)
 * - Sisi Paling Kiri: Logo Resmi Kemenkes Poltekkes Bandung (Aspect Ratio Asli 916x250 ~ 3.664:1, 66mm x 18mm)
 * - Sisi Paling Kanan: Teks Kementerian Kesehatan s.d Alamat Lengkap & Kontak (Tipografi Vector Asli Rata Kanan)
 * - Garis Pemisah: Garis Ganda Hitam Tebal-Tipis Kedinasan membentang penuh dari margin kiri ke kanan
 */
export function renderOfficialLandscapeKopSurat(
  doc: jsPDF,
  margin: number = 14,
  pageWidth: number = 297
): RenderLandscapeKopResult {
  try {
    // SISI PALING KIRI: Logo Resmi Kemenkes Poltekkes Bandung (Aspect Ratio Asli 916x250 ~ 3.664:1)
    // Tinggi 18mm -> Lebar proporsional 18 * 3.664 = 65.95mm (~66mm)
    doc.addImage(KEMENKES_POLKESBAN_LOGO_DATA_URL, 'PNG', margin, 6.5, 66, 18);

    // SISI PALING KANAN: Detail Kementerian Kesehatan s.d Alamat & Kontak (Tipografi Vector Asli Rata Kanan)
    const rightX = pageWidth - margin;

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.setTextColor(0, 169, 157); // #00A99D Teal Kemenkes
    doc.text('KEMENTERIAN KESEHATAN', rightX, 10.5, { align: 'right' });

    doc.setFontSize(8.5);
    doc.setTextColor(60, 60, 60);
    doc.text('Direktorat Jenderal Sumber Daya Manusia Kesehatan', rightX, 14.5, { align: 'right' });

    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100);
    doc.text('Poltekkes Kemenkes Bandung', rightX, 18.5, { align: 'right' });

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.8);
    doc.setTextColor(70, 70, 70);
    doc.text('Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171', rightX, 22.5, { align: 'right' });
    doc.text('Telp: (022) 4231627   |   https://poltekkesbandung.ac.id', rightX, 26.2, { align: 'right' });
  } catch (err) {
    console.warn('Gagal memuat kop dua sisi resmi:', err);
  }

  // Garis Pemisah Kop Surat (Double Line span across page)
  const lineY1 = 29.5;
  const lineY2 = lineY1 + 0.8;
  doc.setDrawColor(0, 0, 0);
  doc.setLineWidth(0.75);
  doc.line(margin, lineY1, pageWidth - margin, lineY1);
  doc.setDrawColor(0, 0, 0);
  doc.setLineWidth(0.25);
  doc.line(margin, lineY2, pageWidth - margin, lineY2);

  return {
    lineY1,
    lineY2,
    contentStartY: lineY2 + 5.0,
  };
}

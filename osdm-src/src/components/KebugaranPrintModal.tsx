import React, { useState, useRef, useMemo } from 'react';
import {
  Printer,
  X,
  FileText,
  Upload,
  Image as ImageIcon,
  RotateCcw,
  Trash2,
  Settings2,
  ChevronDown,
  ChevronUp,
  PenLine,
  CheckCircle2,
  AlertCircle,
  Sparkles,
  Calendar,
  UserCheck,
  FileDown,
  Loader2
} from 'lucide-react';
import { KebugaranSubmission } from '../types';
import {
  formatTanggalIndo,
  calculateBmi,
  classifyBloodPressure,
  classifyBloodSugar,
  classifyCholesterol
} from '../utils/kebugaranUtils';
import { generateKebugaranPdfReport } from '../utils/kebugaranPdfReport';

interface KebugaranPrintModalProps {
  isOpen: boolean;
  onClose: () => void;
  submissions: KebugaranSubmission[];
  periodeFilter: string;
  unitFilter: string;
}

interface SignerSettings {
  leftTitle: string;
  leftJabatan: string;
  leftNama: string;
  leftNip: string;
  rightKotaTanggal: string;
  rightJabatan: string;
  rightNama: string;
  rightSubtext: string;
}

const getDefaultSignerSettings = (dateStr: string): SignerSettings => ({
  leftTitle: 'Mengetahui,',
  leftJabatan: 'Ketua Tim Kerja Kepegawaian & OSDM',
  leftNama: 'Dr. Haris Sofyana, S.Kep., Ners., M.Kep.',
  leftNip: '197306211998031003',
  rightKotaTanggal: `Bandung, ${dateStr}`,
  rightJabatan: 'Penanggung Jawab Kebugaran Pegawai',
  rightNama: 'Tim Kesehatan & Kebugaran Terpadu',
  rightSubtext: 'Poltekkes Kemenkes Bandung',
});

export const KebugaranPrintModal: React.FC<KebugaranPrintModalProps> = ({
  isOpen,
  onClose,
  submissions,
  periodeFilter,
  unitFilter,
}) => {
  if (!isOpen) return null;

  const currentDateStr = useMemo(() => formatTanggalIndo(new Date().toISOString().slice(0, 10)), []);

  // Control state for admin customization panel
  const [isConfigOpen, setIsConfigOpen] = useState<boolean>(true);
  const [activeTab, setActiveTab] = useState<'all' | 'kop' | 'signer'>('all');
  const [isGeneratingPdf, setIsGeneratingPdf] = useState<boolean>(false);

  // Custom Kop Surat Upload State (PNG / JPG / JPEG)
  const [customKopSurat, setCustomKopSurat] = useState<string | null>(() => {
    try {
      return localStorage.getItem('kebugaran_custom_kop_surat_image');
    } catch {
      return null;
    }
  });

  const [customKopSuratName, setCustomKopSuratName] = useState<string | null>(() => {
    try {
      return localStorage.getItem('kebugaran_custom_kop_surat_filename');
    } catch {
      return null;
    }
  });

  const [isDraggingKop, setIsDraggingKop] = useState<boolean>(false);
  const [kopUploadError, setKopUploadError] = useState<string | null>(null);
  const [toastMsg, setToastMsg] = useState<string | null>(null);
  const kopFileInputRef = useRef<HTMLInputElement | null>(null);

  // Signer Settings State
  const [signerSettings, setSignerSettings] = useState<SignerSettings>(() => {
    try {
      const saved = localStorage.getItem('kebugaran_signer_settings');
      if (saved) {
        const parsed = JSON.parse(saved);
        return {
          ...getDefaultSignerSettings(currentDateStr),
          ...parsed,
        };
      }
    } catch (e) {
      console.warn('Error reading signer settings from localStorage:', e);
    }
    return getDefaultSignerSettings(currentDateStr);
  });

  // Process uploaded Kop Surat file
  const handleProcessKopFile = (file: File) => {
    setKopUploadError(null);
    const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    const hasValidExt = /\.(png|jpg|jpeg)$/i.test(file.name);
    if (!validTypes.includes(file.type) && !hasValidExt) {
      setKopUploadError('Format file tidak didukung. Harap unggah file gambar berekstensi .PNG, .JPG, atau .JPEG.');
      return;
    }

    // Max 6MB
    if (file.size > 6 * 1024 * 1024) {
      setKopUploadError('Ukuran file terlalu besar (maksimal 6 MB). Silakan gunakan gambar dengan ukuran lebih kecil.');
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      const result = event.target?.result as string;
      if (result) {
        setCustomKopSurat(result);
        setCustomKopSuratName(file.name);
        try {
          localStorage.setItem('kebugaran_custom_kop_surat_image', result);
          localStorage.setItem('kebugaran_custom_kop_surat_filename', file.name);
        } catch (e) {
          console.warn('Could not save custom kop surat to localStorage:', e);
        }
        setToastMsg(`Kop surat "${file.name}" berhasil diterapkan dan disimpan.`);
        setTimeout(() => setToastMsg(null), 3500);
      }
    };
    reader.onerror = () => {
      setKopUploadError('Gagal membaca file gambar. Silakan coba kembali.');
    };
    reader.readAsDataURL(file);
  };

  // Reset to default official Kop Surat
  const handleResetKopSurat = () => {
    setCustomKopSurat(null);
    setCustomKopSuratName(null);
    setKopUploadError(null);
    try {
      localStorage.removeItem('kebugaran_custom_kop_surat_image');
      localStorage.removeItem('kebugaran_custom_kop_surat_filename');
    } catch (e) {
      console.warn('Could not clear custom kop surat from localStorage:', e);
    }
    if (kopFileInputRef.current) {
      kopFileInputRef.current.value = '';
    }
    setToastMsg('Kop surat telah dikembalikan ke format standar Kemenkes.');
    setTimeout(() => setToastMsg(null), 3000);
  };

  // Update signer field
  const handleUpdateSigner = (key: keyof SignerSettings, value: string) => {
    setSignerSettings((prev) => {
      const updated = { ...prev, [key]: value };
      try {
        localStorage.setItem('kebugaran_signer_settings', JSON.stringify(updated));
      } catch (e) {
        console.warn('Error saving signer settings:', e);
      }
      return updated;
    });
  };

  // Reset signer fields to default
  const handleResetSigner = () => {
    const defaultVals = getDefaultSignerSettings(currentDateStr);
    setSignerSettings(defaultVals);
    try {
      localStorage.removeItem('kebugaran_signer_settings');
    } catch (e) {
      console.warn('Error resetting signer settings:', e);
    }
    setToastMsg('Data penandatangan telah dikembalikan ke pejabat awal.');
    setTimeout(() => setToastMsg(null), 3000);
  };

  // METODE 1: CETAK DOKUMEN VIA IFRAME TERISOLASI (Hanya cetak dokumen rekapan, TANPA halaman dashboard admin)
  const handleIsolatedPrint = () => {
    const printContent = document.getElementById('printable-kebugaran-area');
    if (!printContent) {
      window.print();
      return;
    }

    // Buat iframe terisolasi yang tidak terlihat
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.style.visibility = 'hidden';
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow?.document;
    if (!doc) {
      window.print();
      return;
    }

    // Ambil stylesheet dari dokumen induk agar tabel dan font ter-render sempurna
    const headElements = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
      .map((el) => el.outerHTML)
      .join('\n');

    doc.open();
    doc.write(`
      <!DOCTYPE html>
      <html lang="id">
        <head>
          <meta charset="utf-8" />
          <title>Rekapitulasi Hasil Tes Kebugaran Pegawai</title>
          ${headElements}
          <style>
            @page {
              size: A4 landscape;
              margin: 10mm 10mm 10mm 10mm;
            }
            * {
              -webkit-print-color-adjust: exact !important;
              print-color-adjust: exact !important;
            }
            body {
              background-color: #ffffff !important;
              color: #0f172a !important;
              font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
              margin: 0 !important;
              padding: 6px !important;
            }
            table {
              border-collapse: collapse !important;
              width: 100% !important;
            }
            tr {
              page-break-inside: avoid !important;
            }
            thead {
              display: table-header-group !important;
            }
          </style>
        </head>
        <body>
          <div style="width: 100%; background: #ffffff;">
            ${printContent.innerHTML}
          </div>
        </body>
      </html>
    `);
    doc.close();

    // Berikan jeda kecil agar gambar kop selesai di-render di iframe sebelum dialog cetak dipanggil
    setTimeout(() => {
      try {
        iframe.contentWindow?.focus();
        iframe.contentWindow?.print();
      } catch (err) {
        console.error('Print iframe error:', err);
      } finally {
        setTimeout(() => {
          try {
            document.body.removeChild(iframe);
          } catch {
            // ignore
          }
        }, 1500);
      }
    }, 450);
  };

  // METODE 2: DOWNLOAD FILE PDF RESMI SECARA LANGSUNG (Format .pdf murni via jsPDF)
  const handleDownloadPdf = async () => {
    setIsGeneratingPdf(true);
    try {
      await generateKebugaranPdfReport({
        submissions,
        periodeFilter,
        unitFilter,
        customKopSuratImage: customKopSurat,
        signerSettings,
        customFilename: `Laporan_Rekap_Kebugaran_Poltekkes_${periodeFilter.replace(/\s+/g, '_')}_${new Date().toISOString().slice(0, 10)}.pdf`,
      });
      setToastMsg('File PDF resmi berhasil diunduh ke komputer Anda.');
      setTimeout(() => setToastMsg(null), 3500);
    } catch (error) {
      console.error('Error generating PDF:', error);
      alert('Gagal menghasilkan file PDF. Silakan coba kembali.');
    } finally {
      setIsGeneratingPdf(false);
    }
  };

  // Summary counts for printed report
  const totalSubmissions = submissions.length;
  const normalBpCount = submissions.filter((s) => s.tensiSistolik < 120 && s.tensiDiastolik < 80).length;
  const normalCholCount = submissions.filter((s) => s.kolesterol < 200).length;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 print:p-0 print:bg-white print:static print:overflow-visible">
      {/* Container Modal */}
      <div className="bg-white w-full max-w-5xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[96vh] print:max-h-none print:shadow-none print:border-none print:w-full print:max-w-none print:rounded-none">
        
        {/* Top Action Bar (Hidden on actual print) */}
        <div className="print:hidden px-4 sm:px-6 py-3.5 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 border-b border-slate-800">
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30">
              <Printer className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-sm font-bold">Pratinjau Cetak Rekapan Tes Kebugaran</h3>
                {customKopSurat ? (
                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-semibold border border-emerald-500/40">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                    Kop Kustom
                  </span>
                ) : (
                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 text-[10px] border border-slate-700">
                    Kop Bawaan
                  </span>
                )}
              </div>
              <p className="text-[11px] text-slate-400">
                Pilih <strong>Download PDF</strong> untuk mengunduh berkas .pdf atau <strong>Cetak Dokumen</strong> untuk mencetak langsung ke printer.
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2 ml-auto flex-wrap">
            {/* Toggle Config Panel */}
            <button
              type="button"
              onClick={() => setIsConfigOpen(!isConfigOpen)}
              className={`flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all border ${
                isConfigOpen
                  ? 'bg-indigo-600/30 border-indigo-400/50 text-indigo-200'
                  : 'bg-slate-800 hover:bg-slate-700 border-slate-700 text-slate-200'
              }`}
              title="Buka/Tutup pengaturan kop surat & penandatangan"
            >
              <Settings2 className="w-3.5 h-3.5 text-indigo-400" />
              <span>{isConfigOpen ? 'Tutup Pengaturan' : 'Sesuaikan Kop & TTD'}</span>
              {isConfigOpen ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
            </button>

            {/* BUTTON 1: DOWNLOAD PDF LANGSUNG (Menggunakan jsPDF) */}
            <button
              type="button"
              onClick={handleDownloadPdf}
              disabled={isGeneratingPdf}
              className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 active:scale-95 disabled:bg-slate-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition-all cursor-pointer"
              title="Unduh langsung dokumen format PDF resmi (.pdf) ke komputer Anda"
            >
              {isGeneratingPdf ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <FileDown className="w-4 h-4" />
              )}
              <span>{isGeneratingPdf ? 'Memproses PDF...' : 'Download File PDF'}</span>
            </button>

            {/* BUTTON 2: CETAK DOKUMEN TERISOLASI (Hanya dokumen kebugaran, tanpa background admin dashboard) */}
            <button
              type="button"
              onClick={handleIsolatedPrint}
              className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition-all cursor-pointer"
              title="Cetak dokumen ini ke printer atau cetak ke PDF browser"
            >
              <Printer className="w-4 h-4" />
              <span>Cetak Dokumen</span>
            </button>

            {/* Close Button */}
            <button
              onClick={onClose}
              className="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors"
              title="Tutup"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Admin Customization Panel (Hidden on print) */}
        {isConfigOpen && (
          <div className="print:hidden bg-slate-50 border-b border-slate-200 p-4 sm:p-5 space-y-4 transition-all">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-2.5">
              <div className="flex items-center gap-2">
                <span className="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                  <Sparkles className="w-3.5 h-3.5 text-indigo-600" />
                  Kustomisasi Kop & Penandatangan Laporan:
                </span>
              </div>

              {/* Subtabs */}
              <div className="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 text-xs">
                <button
                  type="button"
                  onClick={() => setActiveTab('all')}
                  className={`px-3 py-1 rounded-lg font-bold transition-all ${
                    activeTab === 'all'
                      ? 'bg-slate-900 text-white shadow-2xs'
                      : 'text-slate-600 hover:text-slate-900'
                  }`}
                >
                  Semua Pengaturan
                </button>
                <button
                  type="button"
                  onClick={() => setActiveTab('kop')}
                  className={`px-3 py-1 rounded-lg font-bold transition-all flex items-center gap-1.5 ${
                    activeTab === 'kop'
                      ? 'bg-indigo-600 text-white shadow-2xs'
                      : 'text-slate-600 hover:text-slate-900'
                  }`}
                >
                  <ImageIcon className="w-3.5 h-3.5" />
                  <span>Kop Surat</span>
                </button>
                <button
                  type="button"
                  onClick={() => setActiveTab('signer')}
                  className={`px-3 py-1 rounded-lg font-bold transition-all flex items-center gap-1.5 ${
                    activeTab === 'signer'
                      ? 'bg-indigo-600 text-white shadow-2xs'
                      : 'text-slate-600 hover:text-slate-900'
                  }`}
                >
                  <PenLine className="w-3.5 h-3.5" />
                  <span>Penandatangan</span>
                </button>
              </div>
            </div>

            {/* Notification Toast in Modal */}
            {toastMsg && (
              <div className="p-2.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-medium flex items-center gap-2">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>{toastMsg}</span>
              </div>
            )}

            {/* Error in Modal */}
            {kopUploadError && (
              <div className="p-2.5 rounded-xl bg-rose-50 border border-rose-300 text-rose-800 text-xs font-medium flex items-center gap-2">
                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                <span>{kopUploadError}</span>
              </div>
            )}

            {/* SECTION 1: KOP SURAT */}
            {(activeTab === 'all' || activeTab === 'kop') && (
              <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <h4 className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                      <ImageIcon className="w-4 h-4 text-indigo-600" />
                      1. Gambar Kop Surat Dokumen
                    </h4>
                    <p className="text-[11px] text-slate-500">
                      Unggah file gambar kop surat resmi Anda (PNG / JPG / JPEG, maksimal 6 MB).
                    </p>
                  </div>

                  {customKopSurat ? (
                    <div className="flex items-center gap-2">
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-[10px] font-semibold">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        Kop Kustom Aktif ({customKopSuratName || 'custom_kop.png'})
                      </span>
                      <button
                        type="button"
                        onClick={handleResetKopSurat}
                        className="text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1"
                        title="Hapus kop kustom dan gunakan format teks standar"
                      >
                        <RotateCcw className="w-3 h-3" />
                        Kembalikan ke Standar
                      </button>
                    </div>
                  ) : (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-300 text-slate-600 text-[10px] font-medium">
                      Menggunakan Kop Teks Resmi Standar Poltekkes
                    </span>
                  )}
                </div>

                {/* Hidden input file */}
                <input
                  type="file"
                  ref={kopFileInputRef}
                  accept="image/png, image/jpeg, image/jpg"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleProcessKopFile(file);
                  }}
                  className="hidden"
                />

                {/* Drag and Drop Zone */}
                <div
                  onDragOver={(e) => {
                    e.preventDefault();
                    setIsDraggingKop(true);
                  }}
                  onDragLeave={() => setIsDraggingKop(false)}
                  onDrop={(e) => {
                    e.preventDefault();
                    setIsDraggingKop(false);
                    const file = e.dataTransfer.files?.[0];
                    if (file) handleProcessKopFile(file);
                  }}
                  className={`border-2 border-dashed rounded-xl p-3 sm:p-4 text-center transition-all ${
                    isDraggingKop
                      ? 'border-indigo-500 bg-indigo-50/80 ring-2 ring-indigo-500/20'
                      : 'border-slate-300 bg-slate-50/60 hover:border-indigo-400 hover:bg-slate-50'
                  }`}
                >
                  <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                        <Upload className="w-5 h-5" />
                      </div>
                      <div className="text-left">
                        <p className="text-xs font-bold text-slate-800">
                          {customKopSurat ? 'Ganti dengan gambar kop surat baru?' : 'Unggah File Gambar Kop Surat dari Komputer'}
                        </p>
                        <p className="text-[11px] text-slate-500">
                          Klik tombol di samping atau seret file gambar langsung ke kotak ini.
                        </p>
                      </div>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                      <button
                        type="button"
                        onClick={() => kopFileInputRef.current?.click()}
                        className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition-all active:scale-95"
                      >
                        <Upload className="w-3.5 h-3.5" />
                        <span>{customKopSurat ? 'Pilih Gambar Baru' : 'Upload Gambar Kop'}</span>
                      </button>

                      {customKopSurat && (
                        <button
                          type="button"
                          onClick={handleResetKopSurat}
                          title="Hapus gambar kop dan pakai format standar"
                          className="p-2 rounded-xl border border-slate-300 text-slate-600 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-300 transition-colors"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* SECTION 2: PENANDATANGAN */}
            {(activeTab === 'all' || activeTab === 'signer') && (
              <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2">
                  <div>
                    <h4 className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                      <PenLine className="w-4 h-4 text-indigo-600" />
                      2. Data Pejabat & Penandatangan Dokumen
                    </h4>
                    <p className="text-[11px] text-slate-500">
                      Sesuaikan nama pejabat, NIP, jabatan, dan tempat/tanggal cetak sesuai kebutuhan institusi.
                    </p>
                  </div>

                  <button
                    type="button"
                    onClick={handleResetSigner}
                    className="text-[11px] font-semibold text-slate-600 hover:text-indigo-600 hover:underline flex items-center gap-1"
                    title="Kembalikan nama pejabat ke data bawaan awal"
                  >
                    <RotateCcw className="w-3 h-3" />
                    Reset ke Default
                  </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {/* Kolom Kiri: Pihak Mengetahui */}
                  <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                    <div className="flex items-center gap-1.5 text-xs font-bold text-slate-800 border-b border-slate-200 pb-1.5">
                      <UserCheck className="w-3.5 h-3.5 text-indigo-600" />
                      <span>Pihak Kiri (Mengetahui / Atasan)</span>
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        Kalimat Pengantar:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.leftTitle}
                        onChange={(e) => handleUpdateSigner('leftTitle', e.target.value)}
                        placeholder="Contoh: Mengetahui,"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        Jabatan Pejabat:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.leftJabatan}
                        onChange={(e) => handleUpdateSigner('leftJabatan', e.target.value)}
                        placeholder="Contoh: Ketua Tim Kerja Kepegawaian & OSDM"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        Nama Lengkap & Gelar:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.leftNama}
                        onChange={(e) => handleUpdateSigner('leftNama', e.target.value)}
                        placeholder="Contoh: Dr. Haris Sofyana, S.Kep., Ners., M.Kep."
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500 font-semibold"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        NIP Pejabat:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.leftNip}
                        onChange={(e) => handleUpdateSigner('leftNip', e.target.value)}
                        placeholder="Contoh: 197306211998031003"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500 font-mono"
                      />
                    </div>
                  </div>

                  {/* Kolom Kanan: Pihak Penanggung Jawab / Pembuat Laporan */}
                  <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                    <div className="flex items-center gap-1.5 text-xs font-bold text-slate-800 border-b border-slate-200 pb-1.5">
                      <Calendar className="w-3.5 h-3.5 text-indigo-600" />
                      <span>Pihak Kanan (Pembuat Rekap / Pengesah)</span>
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        Kota & Tanggal Dokumen:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.rightKotaTanggal}
                        onChange={(e) => handleUpdateSigner('rightKotaTanggal', e.target.value)}
                        placeholder={`Contoh: Bandung, ${currentDateStr}`}
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        Jabatan / Peran:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.rightJabatan}
                        onChange={(e) => handleUpdateSigner('rightJabatan', e.target.value)}
                        placeholder="Contoh: Penanggung Jawab Kebugaran Pegawai"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        Nama Penandatangan / Tim:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.rightNama}
                        onChange={(e) => handleUpdateSigner('rightNama', e.target.value)}
                        placeholder="Contoh: Tim Kesehatan & Kebugaran Terpadu"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500 font-semibold"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-[10px] font-semibold text-slate-600 uppercase tracking-wider block">
                        Keterangan Unit / Instansi / NIP:
                      </label>
                      <input
                        type="text"
                        value={signerSettings.rightSubtext}
                        onChange={(e) => handleUpdateSigner('rightSubtext', e.target.value)}
                        placeholder="Contoh: Poltekkes Kemenkes Bandung"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-900 bg-white focus:outline-none focus:border-indigo-500"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Printable Area */}
        <div className="overflow-y-auto p-8 sm:p-10 bg-white text-slate-900 font-sans print:p-0 print:m-0" id="printable-kebugaran-area">
          
          {/* KOP SURAT RESMI (Custom Image or Default Typography Header) */}
          {customKopSurat ? (
            <div className="pb-4 border-b-2 border-slate-900 mb-6">
              <div className="flex flex-col items-center justify-center">
                <img
                  src={customKopSurat}
                  alt="Kop Surat Resmi Poltekkes"
                  className="w-full max-h-36 object-contain"
                />
              </div>
              <div className="flex justify-between items-center text-[10px] text-slate-500 pt-1.5">
                <span className="font-mono">KODE DOKUMEN: OSDM-KBG-2026</span>
                <span>Dicetak: {currentDateStr}</span>
              </div>
            </div>
          ) : (
            <div className="flex items-center justify-between pb-4 border-b-2 border-slate-900 mb-6">
              <div className="flex items-center gap-4">
                <img
                  src="https://poltekkesbandung.ac.id/wp-content/uploads/2026/05/cropped-logo-transparan-2.png"
                  alt="Logo Kemenkes Poltekkes"
                  className="w-20 h-20 object-contain"
                  onError={(e) => {
                    (e.target as HTMLElement).style.display = 'none';
                  }}
                />
                <div>
                  <div className="text-xs font-bold tracking-widest text-slate-700 uppercase">
                    KEMENTERIAN KESEHATAN REPUBLIK INDONESIA
                  </div>
                  <div className="text-sm font-black tracking-tight text-slate-900 uppercase">
                    DIREKTORAT JENDERAL TENAGA KESEHATAN
                  </div>
                  <div className="text-base font-extrabold text-blue-900 uppercase">
                    POLITEKNIK KESEHATAN KEMENKES BANDUNG
                  </div>
                  <div className="text-[11px] text-slate-600 leading-tight">
                    Jl. Pajajaran No. 56, Pasirkaliki, Kec. Cicendo, Kota Bandung, Jawa Barat 40171
                    <br />
                    Laman: www.poltekkesbandung.ac.id | Pos-el: informasi@poltekkesbandung.ac.id
                  </div>
                </div>
              </div>

              <div className="text-right hidden sm:block">
                <div className="text-[10px] font-mono text-slate-500">KODE DOKUMEN:</div>
                <div className="text-xs font-bold font-mono text-slate-800">OSDM-KBG-2026</div>
                <div className="text-[10px] text-slate-500 mt-1">Dicetak: {currentDateStr}</div>
              </div>
            </div>
          )}

          {/* DOKUMEN HEADER & FILTER METADATA */}
          <div className="text-center space-y-1 mb-6">
            <h1 className="text-base sm:text-lg font-black uppercase tracking-tight text-slate-900">
              REKAPITULASI HASIL PEMERIKSAAN & TES KEBUGARAN PEGAWAI
            </h1>
            <p className="text-xs text-slate-600">
              Periode Pemeriksaan: <strong className="text-slate-900">{periodeFilter}</strong> • Unit Kerja: <strong className="text-slate-900">{unitFilter}</strong>
            </p>
          </div>

          {/* TABEL REKAPAN */}
          <div className="overflow-x-auto border border-slate-300 rounded-lg mb-6">
            <table className="w-full text-left border-collapse text-[11px]">
              <thead>
                <tr className="bg-slate-100 text-slate-800 border-b border-slate-300 font-bold">
                  <th className="py-2.5 px-2 border-r border-slate-300 text-center w-8">No</th>
                  <th className="py-2.5 px-3 border-r border-slate-300">NIP & Nama Pegawai</th>
                  <th className="py-2.5 px-3 border-r border-slate-300">Unit Kerja</th>
                  <th className="py-2.5 px-2 border-r border-slate-300 text-center">Tgl Periksa</th>
                  <th className="py-2.5 px-2 border-r border-slate-300 text-center">Tensi (mmHg)</th>
                  <th className="py-2.5 px-2 border-r border-slate-300 text-center">BB/TB (IMT)</th>
                  <th className="py-2.5 px-2 border-r border-slate-300 text-center">LP (cm)</th>
                  <th className="py-2.5 px-2 border-r border-slate-300 text-center">Gula Darah</th>
                  <th className="py-2.5 px-2 border-r border-slate-300 text-center">Kolesterol</th>
                  <th className="py-2.5 px-3">Fasyankes</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200">
                {submissions.length === 0 ? (
                  <tr>
                    <td colSpan={10} className="py-8 text-center text-slate-500">
                      Tidak ada data pemeriksaan kebugaran untuk filter yang dipilih.
                    </td>
                  </tr>
                ) : (
                  submissions.map((sub, idx) => {
                    const bmi = calculateBmi(sub.beratBadan, sub.tinggiBadan);
                    const bp = classifyBloodPressure(sub.tensiSistolik, sub.tensiDiastolik);
                    return (
                      <tr key={sub.id} className={idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'}>
                        <td className="py-2 px-2 border-r border-slate-200 text-center font-medium text-slate-600">
                          {idx + 1}
                        </td>
                        <td className="py-2 px-3 border-r border-slate-200">
                          <div className="font-bold text-slate-900">{sub.namaPegawai}</div>
                          <div className="font-mono text-[10px] text-slate-500">NIP: {sub.nip}</div>
                        </td>
                        <td className="py-2 px-3 border-r border-slate-200 text-slate-700">
                          {sub.unitKerja}
                        </td>
                        <td className="py-2 px-2 border-r border-slate-200 text-center font-mono text-[10px]">
                          {sub.tanggalPeriksa}
                        </td>
                        <td className="py-2 px-2 border-r border-slate-200 text-center font-bold">
                          {sub.tensiSistolik}/{sub.tensiDiastolik}
                          <span className="block text-[9px] font-normal text-slate-500">{bp.label}</span>
                        </td>
                        <td className="py-2 px-2 border-r border-slate-200 text-center">
                          <span className="font-bold">{sub.beratBadan}k / {sub.tinggiBadan}c</span>
                          <span className="block text-[9px] text-slate-500">IMT: {bmi.bmi}</span>
                        </td>
                        <td className="py-2 px-2 border-r border-slate-200 text-center font-semibold">
                          {sub.lingkarPinggang}
                        </td>
                        <td className="py-2 px-2 border-r border-slate-200 text-center">
                          <span className="font-bold">{sub.gulaDarah}</span>
                          <span className="block text-[9px] text-slate-500 font-semibold">{sub.tipeGulaDarah}</span>
                        </td>
                        <td className="py-2 px-2 border-r border-slate-200 text-center font-bold">
                          {sub.kolesterol} <span className="text-[9px] font-normal text-slate-400">mg/dL</span>
                        </td>
                        <td className="py-2 px-3 text-slate-700 text-[10px]">
                          {sub.fasyankes}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>

          {/* STATISTIK RINGKAS */}
          <div className="grid grid-cols-3 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs mb-8">
            <div>
              <span className="text-slate-500 block text-[11px]">Total Pegawai Terdata:</span>
              <span className="font-black text-slate-900 text-sm">{totalSubmissions} Orang</span>
            </div>
            <div>
              <span className="text-slate-500 block text-[11px]">Tensi Normal:</span>
              <span className="font-bold text-emerald-700">{normalBpCount} Orang ({totalSubmissions > 0 ? Math.round((normalBpCount / totalSubmissions) * 100) : 0}%)</span>
            </div>
            <div>
              <span className="text-slate-500 block text-[11px]">Kolesterol &lt; 200 mg/dL:</span>
              <span className="font-bold text-blue-700">{normalCholCount} Orang ({totalSubmissions > 0 ? Math.round((normalCholCount / totalSubmissions) * 100) : 0}%)</span>
            </div>
          </div>

          {/* PENGESAHAN & TANDA TANGAN DINAMIS */}
          <div className="flex justify-between items-end text-xs text-slate-800 pt-4">
            {/* Pihak Kiri (Mengetahui) */}
            <div className="space-y-1 text-left min-w-[220px]">
              {signerSettings.leftTitle && (
                <p className="text-slate-500 text-[11px]">{signerSettings.leftTitle}</p>
              )}
              <p className="font-bold">{signerSettings.leftJabatan}</p>
              <div className="h-16" />
              <p className="font-bold underline text-slate-900">{signerSettings.leftNama}</p>
              {signerSettings.leftNip && (
                <p className="text-[10px] font-mono text-slate-500">
                  {signerSettings.leftNip.trim().toLowerCase().startsWith('nip')
                    ? signerSettings.leftNip
                    : `NIP. ${signerSettings.leftNip}`}
                </p>
              )}
            </div>

            {/* Pihak Kanan (Pembuat Rekap / Pengesah) */}
            <div className="text-right space-y-1 min-w-[220px]">
              <p className="text-slate-500 text-[11px]">{signerSettings.rightKotaTanggal}</p>
              <p className="font-bold">{signerSettings.rightJabatan}</p>
              <div className="h-16" />
              <p className="font-bold underline text-slate-900">{signerSettings.rightNama}</p>
              {signerSettings.rightSubtext && (
                <p className="text-[10px] text-slate-500">{signerSettings.rightSubtext}</p>
              )}
            </div>
          </div>

        </div>
      </div>
    </div>
  );
};

import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  FileDown,
  X,
  FileSignature,
  Upload,
  Trash2,
  RotateCcw,
  CheckCircle2,
  AlertCircle,
  Building,
  User,
  Calendar,
  Briefcase,
  Sparkles,
} from 'lucide-react';
import { WfaSubmission } from '../types';
import {
  WfaSignerConfig,
  getDefaultSignerConfig,
  loadSignerConfig,
  saveSignerConfig,
} from '../utils/wfaSignerHelper';
import { generateWfaDashboardAnalyticsPdf } from '../utils/wfaDashboardPdfReport';

export interface WfaDashboardExportPdfModalProps {
  isOpen: boolean;
  onClose: () => void;
  submissions: WfaSubmission[];
  periodMode: 'semua' | 'harian' | 'bulanan' | 'tahunan';
  periodLabel: string;
  selectedMonth?: number;
  selectedYear?: number;
}

export const WfaDashboardExportPdfModal: React.FC<WfaDashboardExportPdfModalProps> = ({
  isOpen,
  onClose,
  submissions,
  periodMode,
  periodLabel,
  selectedMonth,
  selectedYear,
}) => {
  const [signerConfig, setSignerConfig] = useState<WfaSignerConfig>(getDefaultSignerConfig());
  const [isExporting, setIsExporting] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const [successToast, setSuccessToast] = useState<string | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Load saved configuration from localStorage when opened
  useEffect(() => {
    if (isOpen) {
      const saved = loadSignerConfig();
      setSignerConfig(saved);
      setUploadError(null);
    }
  }, [isOpen]);

  // Handle PNG Signature Upload
  const handleProcessPngFile = (file: File) => {
    setUploadError(null);

    // Strict format validation: must be PNG
    const isPng = file.type === 'image/png' || file.name.toLowerCase().endsWith('.png');
    if (!isPng) {
      setUploadError('Hanya file gambar dengan format PNG (disarankan berlatar transparan) yang diperbolehkan.');
      return;
    }

    // Size limit 3MB
    if (file.size > 3 * 1024 * 1024) {
      setUploadError('Ukuran file tanda tangan terlalu besar (maksimal 3 MB).');
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      const result = e.target?.result as string;
      if (result) {
        const updated: WfaSignerConfig = {
          ...signerConfig,
          signaturePng: result,
          signatureFilename: file.name,
        };
        setSignerConfig(updated);
        saveSignerConfig(updated);
        setSuccessToast(`Tanda tangan digital "${file.name}" berhasil dimuat.`);
        setTimeout(() => setSuccessToast(null), 3500);
      }
    };
    reader.onerror = () => {
      setUploadError('Gagal membaca file gambar PNG. Silakan coba kembali.');
    };
    reader.readAsDataURL(file);
  };

  // Handle remove signature
  const handleRemoveSignature = () => {
    const updated: WfaSignerConfig = {
      ...signerConfig,
      signaturePng: null,
      signatureFilename: null,
    };
    setSignerConfig(updated);
    saveSignerConfig(updated);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    setSuccessToast('Tanda tangan digital dihapus. Dokumen akan menggunakan ruang tanda tangan basah.');
    setTimeout(() => setSuccessToast(null), 3000);
  };

  // Reset all signer identity to default
  const handleResetToDefault = () => {
    const def = getDefaultSignerConfig();
    setSignerConfig(def);
    saveSignerConfig(def);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    setSuccessToast('Pengaturan penandatangan dikembalikan ke format standar Tim Kerja OSDM.');
    setTimeout(() => setSuccessToast(null), 3000);
  };

  // Handle text field changes
  const handleFieldChange = (field: keyof WfaSignerConfig, value: string) => {
    const updated = { ...signerConfig, [field]: value };
    setSignerConfig(updated);
    saveSignerConfig(updated);
  };

  // Handle PDF Export
  const handleExportPdf = async () => {
    if (submissions.length === 0) {
      alert('Tidak ada data pengajuan untuk diekspor pada periode ini.');
      return;
    }

    setIsExporting(true);
    try {
      await generateWfaDashboardAnalyticsPdf({
        submissions,
        periodMode,
        periodLabel,
        selectedMonth,
        selectedYear,
        customFilename: `Laporan_Eksekutif_Dashboard_WFA_${periodMode}_${Date.now()}.pdf`,
        signerCityDate: signerConfig.cityDate,
        signerTitle: signerConfig.title,
        signerName: signerConfig.name,
        signerInstitution: signerConfig.institution,
        signerSignaturePng: signerConfig.signaturePng,
      });

      // Auto close after download
      setTimeout(() => {
        setIsExporting(false);
        onClose();
      }, 600);
    } catch (err) {
      console.error('Error exporting dashboard PDF:', err);
      alert('Terjadi kesalahan saat memproses ekspor PDF Dashboard. Silakan coba kembali.');
      setIsExporting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-5 bg-slate-950/80 backdrop-blur-md overflow-y-auto">
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 12 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 12 }}
          transition={{ duration: 0.2, ease: 'easeOut' }}
          className="relative w-full max-w-2xl bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-auto max-h-[92vh] flex flex-col text-slate-800"
        >
          {/* HEADER MODAL */}
          <div className="px-5 sm:px-6 py-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex items-center justify-between shrink-0 shadow-sm">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300 shrink-0">
                <FileSignature className="w-5 h-5" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="font-bold text-sm sm:text-base text-white tracking-tight">
                    Pengesahan &amp; Tanda Tangan Digital PDF
                  </h3>
                  <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                    Format PNG
                  </span>
                </div>
                <p className="text-[11px] sm:text-xs text-slate-300 mt-0.5">
                  Sesuaikan identitas penandatangan dan upload tanda tangan digital resmi.
                </p>
              </div>
            </div>

            <button
              type="button"
              onClick={onClose}
              className="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* TOAST ALERTS */}
          {successToast && (
            <div className="bg-emerald-50 border-b border-emerald-200 px-5 py-2 text-xs font-semibold text-emerald-800 flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
              <span>{successToast}</span>
            </div>
          )}

          {uploadError && (
            <div className="bg-rose-50 border-b border-rose-200 px-5 py-2 text-xs font-semibold text-rose-800 flex items-center gap-2">
              <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
              <span>{uploadError}</span>
            </div>
          )}

          {/* BODY CONTENT */}
          <div className="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1 bg-slate-50/50">
            {/* Periode Ringkasan */}
            <div className="p-3 rounded-2xl bg-indigo-50/60 border border-indigo-100 flex items-center justify-between text-xs">
              <span className="text-slate-600 flex items-center gap-1.5 font-medium">
                <Sparkles className="w-3.5 h-3.5 text-indigo-600" />
                Periode Data Laporan:
              </span>
              <span className="font-bold text-indigo-900 bg-white px-2.5 py-1 rounded-lg border border-indigo-200/60 shadow-2xs">
                {periodLabel} ({submissions.length} Berkas)
              </span>
            </div>

            {/* 1. FORM PENANDATANGAN */}
            <div className="p-4 bg-white border border-slate-200 rounded-2xl shadow-xs space-y-3.5">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="w-7 h-7 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <User className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-xs font-bold text-slate-800">1. Identitas Penandatangan Dokumen</h4>
                    <p className="text-[10px] text-slate-500">Dapat disesuaikan dengan pejabat berwenang atau nama tim.</p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handleResetToDefault}
                  className="text-[10px] font-semibold text-slate-500 hover:text-indigo-600 hover:underline flex items-center gap-1 cursor-pointer"
                  title="Kembalikan nama dan jabatan ke format bawaan"
                >
                  <RotateCcw className="w-3 h-3" />
                  Reset ke Bawaan
                </button>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {/* Kota & Tanggal */}
                <div className="space-y-1">
                  <label className="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                    <Calendar className="w-3 h-3 text-slate-400" />
                    Kota &amp; Tanggal Pengesahan:
                  </label>
                  <input
                    type="text"
                    value={signerConfig.cityDate}
                    onChange={(e) => handleFieldChange('cityDate', e.target.value)}
                    placeholder="Contoh: Bandung, 07 Oktober 2026"
                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white"
                  />
                </div>

                {/* Jabatan */}
                <div className="space-y-1">
                  <label className="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                    <Briefcase className="w-3 h-3 text-slate-400" />
                    Jabatan Penandatangan:
                  </label>
                  <input
                    type="text"
                    value={signerConfig.title}
                    onChange={(e) => handleFieldChange('title', e.target.value)}
                    placeholder="Contoh: Penanggung Jawab Tim Kerja OSDM,"
                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white"
                  />
                </div>

                {/* Nama Pejabat / Tim */}
                <div className="space-y-1">
                  <label className="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                    <User className="w-3 h-3 text-slate-400" />
                    Nama Lengkap / Tim Penandatangan:
                  </label>
                  <input
                    type="text"
                    value={signerConfig.name}
                    onChange={(e) => handleFieldChange('name', e.target.value)}
                    placeholder="Contoh: Tim Kerja OSDM atau dr. H. ..."
                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white font-medium"
                  />
                </div>

                {/* Institusi / NIP */}
                <div className="space-y-1">
                  <label className="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                    <Building className="w-3 h-3 text-slate-400" />
                    Institusi / NIP Pejabat:
                  </label>
                  <input
                    type="text"
                    value={signerConfig.institution}
                    onChange={(e) => handleFieldChange('institution', e.target.value)}
                    placeholder="Contoh: Poltekkes Kemenkes Bandung atau NIP..."
                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white"
                  />
                </div>
              </div>
            </div>

            {/* 2. UPLOAD TANDA TANGAN DIGITAL (PNG) */}
            <div className="p-4 bg-white border border-slate-200 rounded-2xl shadow-xs space-y-3.5">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <FileSignature className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-xs font-bold text-slate-800">
                      2. Tanda Tangan Digital Resmi (Khusus Format PNG)
                    </h4>
                    <p className="text-[10px] text-slate-500">
                      Disarankan file PNG berlatar transparan agar tanda tangan tercetak rapi di atas dokumen.
                    </p>
                  </div>
                </div>

                {signerConfig.signaturePng ? (
                  <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-[10px] font-bold">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                    TTD Digital Aktif
                  </span>
                ) : (
                  <span className="text-[10px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                    Mode TTD Basah / Manual
                  </span>
                )}
              </div>

              {/* Hidden file input */}
              <input
                type="file"
                ref={fileInputRef}
                accept="image/png"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) handleProcessPngFile(file);
                }}
                className="hidden"
              />

              {/* Upload Dropzone / Existing Preview */}
              {signerConfig.signaturePng ? (
                <div className="p-3.5 rounded-2xl border border-emerald-200 bg-emerald-50/40 flex flex-col sm:flex-row items-center gap-4">
                  {/* Checkerboard Preview Area */}
                  <div className="w-36 h-20 bg-[linear-gradient(45deg,#f1f5f9_25%,transparent_25%),linear-gradient(-45deg,#f1f5f9_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#f1f5f9_75%),linear-gradient(-45deg,transparent_75%,#f1f5f9_75%)] bg-[size:10px_10px] bg-[position:0_0,0_5px,5px_-5px,-5px_0] border border-slate-200 rounded-xl p-2 flex items-center justify-center shrink-0 shadow-2xs">
                    <img
                      src={signerConfig.signaturePng}
                      alt="Pratinjau Tanda Tangan Digital"
                      className="max-h-full max-w-full object-contain"
                    />
                  </div>

                  <div className="flex-1 text-center sm:text-left space-y-1">
                    <div className="flex items-center gap-1.5 justify-center sm:justify-start">
                      <span className="text-xs font-bold text-slate-800">
                        {signerConfig.signatureFilename || 'tanda_tangan.png'}
                      </span>
                      <span className="text-[9px] px-1.5 py-0.2 bg-emerald-200 text-emerald-900 rounded font-semibold">
                        PNG
                      </span>
                    </div>
                    <p className="text-[10px] text-slate-500">
                      Tanda tangan digital telah terpasang dan akan dicetak otomatis tepat di atas nama pengesah.
                    </p>
                  </div>

                  <div className="flex items-center gap-2 shrink-0">
                    <button
                      type="button"
                      onClick={() => fileInputRef.current?.click()}
                      className="px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                    >
                      Ganti PNG
                    </button>
                    <button
                      type="button"
                      onClick={handleRemoveSignature}
                      className="p-1.5 rounded-xl text-rose-600 hover:bg-rose-100/70 border border-rose-200 transition-colors cursor-pointer"
                      title="Hapus tanda tangan digital"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              ) : (
                <div
                  onDragOver={(e) => {
                    e.preventDefault();
                    setIsDragging(true);
                  }}
                  onDragLeave={() => setIsDragging(false)}
                  onDrop={(e) => {
                    e.preventDefault();
                    setIsDragging(false);
                    const file = e.dataTransfer.files?.[0];
                    if (file) handleProcessPngFile(file);
                  }}
                  onClick={() => fileInputRef.current?.click()}
                  className={`border-2 border-dashed rounded-2xl p-4 sm:p-5 text-center cursor-pointer transition-all ${
                    isDragging
                      ? 'border-indigo-500 bg-indigo-50/80 ring-2 ring-indigo-500/20'
                      : 'border-slate-300 bg-slate-50/50 hover:border-indigo-400 hover:bg-indigo-50/30'
                  }`}
                >
                  <div className="flex flex-col items-center justify-center gap-2">
                    <div className="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-2xs">
                      <Upload className="w-5 h-5" />
                    </div>
                    <div>
                      <p className="text-xs font-bold text-slate-800">
                        Klik untuk memilih atau seret &amp; lepas file Tanda Tangan PNG
                      </p>
                      <p className="text-[10px] text-slate-500 mt-0.5">
                        Hanya mendukung format <strong>PNG</strong> (latar belakang transparan disarankan, maks. 3 MB).
                      </p>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* 3. PRATINJAU LANGSUNG BLOK PENGESAHAN */}
            <div className="p-4 bg-white border border-slate-200 rounded-2xl shadow-xs space-y-2.5">
              <span className="text-[11px] font-bold text-slate-600 uppercase tracking-wider block">
                Pratinjau Tampilan Blok Pengesahan di PDF:
              </span>

              <div className="p-4 rounded-xl bg-slate-100/60 border border-slate-200 flex flex-col items-center justify-center text-center">
                <span className="text-[11px] text-slate-700">{signerConfig.cityDate}</span>
                <span className="text-[11px] text-slate-800 font-semibold">{signerConfig.title}</span>

                {/* Space / Signature PNG */}
                <div className="h-16 flex items-center justify-center my-1">
                  {signerConfig.signaturePng ? (
                    <img
                      src={signerConfig.signaturePng}
                      alt="TTD"
                      className="max-h-14 max-w-[140px] object-contain drop-shadow-2xs"
                    />
                  ) : (
                    <span className="text-[10px] text-slate-400 italic">
                      [ Ruang Tanda Tangan Basah / Manual ]
                    </span>
                  )}
                </div>

                <span className="text-[11px] font-bold text-slate-900">
                  {signerConfig.name.startsWith('(') ? signerConfig.name : `( ${signerConfig.name} )`}
                </span>
                <span className="text-[10px] text-slate-600 mt-0.5">{signerConfig.institution}</span>
              </div>
            </div>
          </div>

          {/* FOOTER ACTIONS */}
          <div className="px-5 sm:px-6 py-3.5 bg-white border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
            <span className="text-[11px] text-slate-500 hidden sm:inline">
              Pengaturan penandatangan tersimpan otomatis di perangkat Anda.
            </span>

            <div className="flex items-center gap-2 ml-auto">
              <button
                type="button"
                onClick={onClose}
                disabled={isExporting}
                className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200 transition-colors cursor-pointer"
              >
                Batal
              </button>

              <button
                type="button"
                onClick={handleExportPdf}
                disabled={isExporting}
                className="flex items-center gap-1.5 px-4 sm:px-5 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 active:from-indigo-800 active:to-violet-800 text-white font-bold text-xs shadow-md shadow-indigo-100 transition-all disabled:opacity-50 cursor-pointer"
              >
                <FileDown className="w-4 h-4" />
                <span>{isExporting ? 'Mengekspor Dokumen PDF...' : 'Unduh Laporan PDF Sekarang'}</span>
              </button>
            </div>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

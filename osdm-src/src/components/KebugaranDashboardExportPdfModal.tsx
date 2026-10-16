import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
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
import { KebugaranSubmission } from '../types';
import {
  WfaSignerConfig,
  getDefaultSignerConfig,
  loadSignerConfig,
  saveSignerConfig,
} from '../utils/wfaSignerHelper';
import { generateKebugaranDashboardAnalyticsPdf } from '../utils/kebugaranDashboardPdfReport';

export interface KebugaranDashboardExportPdfModalProps {
  isOpen: boolean;
  onClose: () => void;
  submissions: KebugaranSubmission[];
  selectedTriwulan: string;
  selectedYear: string;
  selectedUnit: string;
}

export const KebugaranDashboardExportPdfModal: React.FC<KebugaranDashboardExportPdfModalProps> = ({
  isOpen,
  onClose,
  submissions,
  selectedTriwulan,
  selectedYear,
  selectedUnit,
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

    const isPng = file.type === 'image/png' || file.name.toLowerCase().endsWith('.png');
    if (!isPng) {
      setUploadError('Hanya file gambar dengan format PNG (disarankan berlatar transparan) yang diperbolehkan.');
      return;
    }

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

  const handleRemoveSignature = () => {
    const updated: WfaSignerConfig = {
      ...signerConfig,
      signaturePng: null,
      signatureFilename: null,
    };
    setSignerConfig(updated);
    saveSignerConfig(updated);
    if (fileInputRef.current) fileInputRef.current.value = '';
    setSuccessToast('Tanda tangan digital telah dihapus.');
    setTimeout(() => setSuccessToast(null), 2500);
  };

  const handleResetDefaults = () => {
    const def = getDefaultSignerConfig();
    setSignerConfig(def);
    saveSignerConfig(def);
    if (fileInputRef.current) fileInputRef.current.value = '';
    setSuccessToast('Pengaturan penandatangan dikembalikan ke default.');
    setTimeout(() => setSuccessToast(null), 2500);
  };

  const handleFieldChange = (field: keyof WfaSignerConfig, value: string) => {
    const updated = {
      ...signerConfig,
      [field]: value,
    };
    setSignerConfig(updated);
    saveSignerConfig(updated);
  };

  // Download PDF Action
  const handleDownloadPdf = async () => {
    setIsExporting(true);
    try {
      await generateKebugaranDashboardAnalyticsPdf({
        submissions,
        selectedTriwulan,
        selectedYear,
        selectedUnit,
        customFilename: `Laporan_Eksekutif_Dashboard_Kebugaran_${selectedTriwulan.replace(/\s+/g, '_')}_${Date.now()}.pdf`,
        signerCityDate: signerConfig.cityDate,
        signerTitle: signerConfig.title,
        signerName: signerConfig.name,
        signerInstitution: signerConfig.institution,
        signerSignaturePng: signerConfig.signaturePng,
      });
      onClose();
    } catch (err) {
      console.error('Failed to export PDF:', err);
      alert('Terjadi kesalahan saat memproses ekspor PDF Dashboard Kebugaran.');
    } finally {
      setIsExporting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-60 flex items-center justify-center p-3 sm:p-5 bg-slate-950/80 backdrop-blur-md overflow-y-auto">
        <motion.div
          initial={{ opacity: 0, scale: 0.96, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.96, y: 15 }}
          transition={{ duration: 0.2 }}
          className="relative w-full max-w-2xl bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-auto max-h-[94vh] flex flex-col text-slate-800"
        >
          {/* Header */}
          <div className="py-4 px-6 bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 text-white flex items-center justify-between shrink-0">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-2xl bg-teal-500/20 border border-teal-400/30 flex items-center justify-center text-teal-400 shrink-0">
                <FileDown className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-bold text-base sm:text-lg text-white tracking-tight">
                  Ekspor Laporan Eksekutif PDF Kebugaran
                </h3>
                <p className="text-xs text-slate-300 mt-0.5">
                  Format A4 Landscape Resmi Kemenkes RI • Filter: {selectedTriwulan} ({selectedYear})
                </p>
              </div>
            </div>

            <button
              onClick={onClose}
              className="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Body */}
          <div className="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1 bg-slate-50/50">
            {/* Notification Toast */}
            {successToast && (
              <div className="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>{successToast}</span>
              </div>
            )}
            {uploadError && (
              <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                <span>{uploadError}</span>
              </div>
            )}

            {/* Document Info Card */}
            <div className="p-4 rounded-2xl bg-teal-50/80 border border-teal-200 text-xs text-teal-950 space-y-1.5">
              <div className="flex items-center gap-2 font-bold text-teal-900">
                <Sparkles className="w-4 h-4 text-teal-600" />
                <span>Ringkasan Dokumen PDF yang Dihasilkan:</span>
              </div>
              <p className="text-teal-800 leading-relaxed text-[11px]">
                Dokumen mencakup Kop Surat Resmi Kemenkes 2-Sisi, 4 KPI Kesehatan Utama, Proporsi Status Sehat Prima vs Perlu Pemantauan, Tabel Komparasi 4 Parameter, Tabel Distribusi per Unit Kerja, serta Lampiran rincian pegawai yang membutuhkan pemantauan khusus.
              </p>
            </div>

            {/* Signer Customization Section */}
            <div className="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center gap-2">
                  <FileSignature className="w-4 h-4 text-teal-600" />
                  <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Pengaturan Penandatangan Dokumen
                  </h4>
                </div>
                <button
                  type="button"
                  onClick={handleResetDefaults}
                  className="flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-slate-800 transition-colors"
                  title="Kembalikan ke Nilai Default"
                >
                  <RotateCcw className="w-3 h-3" />
                  <span>Reset Default</span>
                </button>
              </div>

              {/* Form Fields */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                {/* 1. Kota & Tanggal */}
                <div>
                  <label className="block text-[11px] font-bold text-slate-600 mb-1 flex items-center gap-1.5">
                    <Calendar className="w-3.5 h-3.5 text-slate-400" />
                    <span>Tempat &amp; Tanggal Pengesahan</span>
                  </label>
                  <input
                    type="text"
                    value={signerConfig.cityDate}
                    onChange={(e) => handleFieldChange('cityDate', e.target.value)}
                    placeholder="Contoh: Bandung, 07 Oktober 2026"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 text-xs text-slate-800"
                  />
                </div>

                {/* 2. Jabatan Penandatangan */}
                <div>
                  <label className="block text-[11px] font-bold text-slate-600 mb-1 flex items-center gap-1.5">
                    <Briefcase className="w-3.5 h-3.5 text-slate-400" />
                    <span>Jabatan Penandatangan</span>
                  </label>
                  <input
                    type="text"
                    value={signerConfig.title}
                    onChange={(e) => handleFieldChange('title', e.target.value)}
                    placeholder="Contoh: Penanggung Jawab Tim Kerja OSDM,"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 text-xs text-slate-800"
                  />
                </div>

                {/* 3. Nama Penandatangan */}
                <div>
                  <label className="block text-[11px] font-bold text-slate-600 mb-1 flex items-center gap-1.5">
                    <User className="w-3.5 h-3.5 text-slate-400" />
                    <span>Nama Penandatangan</span>
                  </label>
                  <input
                    type="text"
                    value={signerConfig.name}
                    onChange={(e) => handleFieldChange('name', e.target.value)}
                    placeholder="Contoh: Rian Chaidir Hidayat, A.Md"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 text-xs font-semibold text-slate-800"
                  />
                </div>

                {/* 4. NIP / Keterangan Institusi */}
                <div>
                  <label className="block text-[11px] font-bold text-slate-600 mb-1 flex items-center gap-1.5">
                    <Building className="w-3.5 h-3.5 text-slate-400" />
                    <span>NIP / Keterangan Pejabat</span>
                  </label>
                  <input
                    type="text"
                    value={signerConfig.institution}
                    onChange={(e) => handleFieldChange('institution', e.target.value)}
                    placeholder="Contoh: NIP. 199202252023211020"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 text-xs text-slate-800"
                  />
                </div>
              </div>

              {/* Upload Tanda Tangan Digital PNG */}
              <div className="pt-2 border-t border-slate-100">
                <label className="block text-[11px] font-bold text-slate-700 mb-1 flex items-center justify-between">
                  <span className="flex items-center gap-1.5">
                    <FileSignature className="w-3.5 h-3.5 text-teal-600" />
                    <span>Upload Tanda Tangan Digital (Format PNG)</span>
                  </span>
                  <span className="text-[10px] text-slate-400 font-normal">
                    Format: .PNG (Disarankan Transparan, Maks 3MB)
                  </span>
                </label>

                {signerConfig.signaturePng ? (
                  <div className="mt-2 p-3 rounded-2xl border border-teal-200 bg-teal-50/40 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                      <div className="w-20 h-12 bg-white rounded-xl border border-slate-200 p-1 flex items-center justify-center overflow-hidden shadow-2xs">
                        <img
                          src={signerConfig.signaturePng}
                          alt="Tanda Tangan Digital"
                          className="max-h-full max-w-full object-contain"
                        />
                      </div>
                      <div>
                        <div className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                          <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                          <span>Tanda Tangan Siap Diterapkan</span>
                        </div>
                        <p className="text-[10px] text-slate-500 mt-0.5">
                          {signerConfig.signatureFilename || 'signature.png'}
                        </p>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        className="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors"
                      >
                        Ganti
                      </button>
                      <button
                        type="button"
                        onClick={handleRemoveSignature}
                        className="p-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition-colors"
                        title="Hapus Tanda Tangan"
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
                      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                        handleProcessPngFile(e.dataTransfer.files[0]);
                      }
                    }}
                    onClick={() => fileInputRef.current?.click()}
                    className={`mt-2 p-5 rounded-2xl border-2 border-dashed text-center cursor-pointer transition-all ${
                      isDragging
                        ? 'border-teal-500 bg-teal-50'
                        : 'border-slate-200 hover:border-teal-400 bg-slate-50/70 hover:bg-white'
                    }`}
                  >
                    <Upload className="w-6 h-6 text-slate-400 mx-auto mb-1.5" />
                    <p className="text-xs font-semibold text-slate-700">
                      Klik untuk memilih file tanda tangan PNG atau tarik ke sini
                    </p>
                    <p className="text-[10px] text-slate-400 mt-0.5">
                      Tanda tangan akan otomatis disisipkan di atas nama penandatangan pada PDF.
                    </p>
                  </div>
                )}

                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/png"
                  onChange={(e) => {
                    if (e.target.files && e.target.files[0]) {
                      handleProcessPngFile(e.target.files[0]);
                    }
                  }}
                  className="hidden"
                />
              </div>
            </div>
          </div>

          {/* Footer Actions */}
          <div className="py-3.5 px-6 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors"
            >
              Batal
            </button>

            <button
              type="button"
              disabled={isExporting}
              onClick={handleDownloadPdf}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white text-xs font-bold transition-all shadow-sm shadow-teal-500/20 disabled:opacity-50"
            >
              <FileDown className="w-4 h-4" />
              <span>{isExporting ? 'Memproses PDF...' : 'Unduh Laporan PDF Eksekutif'}</span>
            </button>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

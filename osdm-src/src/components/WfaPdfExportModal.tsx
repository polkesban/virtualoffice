import React, { useState, useMemo, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  FileDown,
  X,
  Calendar,
  Clock,
  CheckCircle2,
  AlertCircle,
  FileText,
  Filter,
  CalendarDays,
  CalendarRange,
  Layers,
  Sparkles,
  MapPin,
  Building2,
  Upload,
  Image as ImageIcon,
  RotateCcw,
  Trash2,
  Check,
  FileSignature,
  Briefcase,
  User,
} from 'lucide-react';
import { WfaSubmission, WfaValidationStatus } from '../types';
import { generateWfaPdfReport } from '../utils/wfaPdfReport';
import {
  WfaSignerConfig,
  getDefaultSignerConfig,
  loadSignerConfig,
  saveSignerConfig,
} from '../utils/wfaSignerHelper';

export type TimeFilterPeriod = 'harian' | 'mingguan' | 'bulanan' | 'tahunan' | 'semua';

interface WfaPdfExportModalProps {
  isOpen: boolean;
  onClose: () => void;
  submissions: WfaSubmission[];
  initialStatusFilter?: 'All' | WfaValidationStatus;
  initialLokasiFilter?: string;
  initialJenisWfaFilter?: string;
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

const AVAILABLE_YEARS = [2024, 2025, 2026, 2027, 2028];

// Helper: Format Date string YYYY-MM-DD to Indonesian standard
function formatIndoDate(dateStr: string): string {
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

// Helper: Get Monday and Sunday of the current week (or given date)
function getWeekRange(d: Date = new Date()): { start: string; end: string } {
  const date = new Date(d);
  const day = date.getDay(); // 0 is Sunday
  // Distance to Monday (if Sunday (0), distance is -6; if Monday (1), distance is 0)
  const diffToMonday = date.getDate() - day + (day === 0 ? -6 : 1);
  const monday = new Date(date.setDate(diffToMonday));

  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);

  const toIsoDate = (dt: Date) => dt.toISOString().slice(0, 10);
  return {
    start: toIsoDate(monday),
    end: toIsoDate(sunday),
  };
}

export const WfaPdfExportModal: React.FC<WfaPdfExportModalProps> = ({
  isOpen,
  onClose,
  submissions,
  initialStatusFilter = 'All',
  initialLokasiFilter = 'All',
  initialJenisWfaFilter = 'All',
}) => {
  const todayIso = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const currentYear = useMemo(() => new Date().getFullYear(), []);
  const currentMonthIdx = useMemo(() => new Date().getMonth(), []);

  // Time Filter Mode
  const [periodType, setPeriodType] = useState<TimeFilterPeriod>('bulanan');

  // Daily State
  const [dailyDate, setDailyDate] = useState<string>(todayIso);

  // Weekly State
  const [weeklyPreset, setWeeklyPreset] = useState<'this_week' | 'last_7_days' | 'custom'>('this_week');
  const [weekStartDate, setWeekStartDate] = useState<string>(() => getWeekRange().start);
  const [weekEndDate, setWeekEndDate] = useState<string>(() => getWeekRange().end);

  // Monthly State
  const [selectedMonth, setSelectedMonth] = useState<number>(currentMonthIdx);
  const [selectedMonthYear, setSelectedMonthYear] = useState<number>(currentYear);

  // Yearly State
  const [selectedYear, setSelectedYear] = useState<number>(currentYear);

  // Secondary Filters
  const [statusFilter, setStatusFilter] = useState<'All' | WfaValidationStatus>(initialStatusFilter);
  const [lokasiFilter, setLokasiFilter] = useState<string>(initialLokasiFilter);
  const [jenisWfaFilter, setJenisWfaFilter] = useState<string>(initialJenisWfaFilter);

  // List of distinct Jenis WFA options
  const jenisWfaOptions = useMemo(() => {
    const defaults = ['WFA Datang', 'WFA Pulang', 'WFA Datang & WFA Pulang'];
    const dynamic = submissions
      .map((s) => s.statusWfa?.trim())
      .filter((v): v is string => Boolean(v) && !defaults.includes(v));
    return [...defaults, ...Array.from(new Set(dynamic))];
  }, [submissions]);

  // Generating state
  const [isGenerating, setIsGenerating] = useState(false);
  const [uploadSuccessToast, setUploadSuccessToast] = useState<string | null>(null);

  // Signer & PNG Signature State
  const [signerConfig, setSignerConfig] = useState<WfaSignerConfig>(getDefaultSignerConfig());
  const [sigUploadError, setSigUploadError] = useState<string | null>(null);
  const [isDraggingSig, setIsDraggingSig] = useState<boolean>(false);
  const sigFileInputRef = useRef<HTMLInputElement | null>(null);

  // Synchronize initial filters and signer config when modal opens
  React.useEffect(() => {
    if (isOpen) {
      setStatusFilter(initialStatusFilter);
      setLokasiFilter(initialLokasiFilter);
      setJenisWfaFilter(initialJenisWfaFilter);
      setSignerConfig(loadSignerConfig());
      setSigUploadError(null);
      try {
        localStorage.removeItem('wfa_custom_kop_surat_image');
        localStorage.removeItem('wfa_custom_kop_surat_filename');
      } catch (e) {
        // ignore
      }
    }
  }, [isOpen, initialStatusFilter, initialLokasiFilter, initialJenisWfaFilter]);

  // Handle process PNG Signature File
  const handleProcessSigFile = (file: File) => {
    setSigUploadError(null);
    const isPng = file.type === 'image/png' || file.name.toLowerCase().endsWith('.png');
    if (!isPng) {
      setSigUploadError('Hanya file gambar dengan format PNG (disarankan berlatar transparan) yang diperbolehkan.');
      return;
    }
    if (file.size > 3 * 1024 * 1024) {
      setSigUploadError('Ukuran file tanda tangan terlalu besar (maksimal 3 MB).');
      return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
      const result = e.target?.result as string;
      if (result) {
        const updated = {
          ...signerConfig,
          signaturePng: result,
          signatureFilename: file.name,
        };
        setSignerConfig(updated);
        saveSignerConfig(updated);
        setUploadSuccessToast(`Tanda tangan digital "${file.name}" berhasil diterapkan.`);
        setTimeout(() => setUploadSuccessToast(null), 3500);
      }
    };
    reader.onerror = () => {
      setSigUploadError('Gagal membaca file gambar PNG.');
    };
    reader.readAsDataURL(file);
  };

  const handleRemoveSignature = () => {
    const updated = {
      ...signerConfig,
      signaturePng: null,
      signatureFilename: null,
    };
    setSignerConfig(updated);
    saveSignerConfig(updated);
    if (sigFileInputRef.current) {
      sigFileInputRef.current.value = '';
    }
    setUploadSuccessToast('Tanda tangan digital telah dihapus (menggunakan ruang tanda tangan basah).');
    setTimeout(() => setUploadSuccessToast(null), 3000);
  };

  const handleResetSigner = () => {
    const def = getDefaultSignerConfig();
    setSignerConfig(def);
    saveSignerConfig(def);
    if (sigFileInputRef.current) {
      sigFileInputRef.current.value = '';
    }
    setUploadSuccessToast('Identitas penandatangan dikembalikan ke format bawaan Tim Kerja OSDM.');
    setTimeout(() => setUploadSuccessToast(null), 3000);
  };

  const handleSignerFieldChange = (field: keyof WfaSignerConfig, value: string) => {
    const updated = { ...signerConfig, [field]: value };
    setSignerConfig(updated);
    saveSignerConfig(updated);
  };

  // Handle Weekly preset click
  const handleSelectWeeklyPreset = (preset: 'this_week' | 'last_7_days' | 'custom') => {
    setWeeklyPreset(preset);
    if (preset === 'this_week') {
      const { start, end } = getWeekRange();
      setWeekStartDate(start);
      setWeekEndDate(end);
    } else if (preset === 'last_7_days') {
      const endDt = new Date();
      const startDt = new Date();
      startDt.setDate(endDt.getDate() - 6);
      setWeekStartDate(startDt.toISOString().slice(0, 10));
      setWeekEndDate(endDt.toISOString().slice(0, 10));
    }
  };

  // Compute Active Time Period Description & Filename
  const { periodLabel, filenamePrefix } = useMemo(() => {
    if (periodType === 'harian') {
      const formatted = formatIndoDate(dailyDate);
      return {
        periodLabel: `Harian - ${formatted}`,
        filenamePrefix: `Laporan_WFA_Harian_${dailyDate}`,
      };
    }

    if (periodType === 'mingguan') {
      const startFmt = formatIndoDate(weekStartDate);
      const endFmt = formatIndoDate(weekEndDate);
      return {
        periodLabel: `Mingguan - ${startFmt} s.d ${endFmt}`,
        filenamePrefix: `Laporan_WFA_Mingguan_${weekStartDate}_sd_${weekEndDate}`,
      };
    }

    if (periodType === 'bulanan') {
      const mName = MONTH_NAMES[selectedMonth] || 'Bulan';
      const mNum = String(selectedMonth + 1).padStart(2, '0');
      return {
        periodLabel: `Bulanan - ${mName} ${selectedMonthYear}`,
        filenamePrefix: `Laporan_WFA_Bulanan_${selectedMonthYear}_${mNum}`,
      };
    }

    if (periodType === 'tahunan') {
      return {
        periodLabel: `Tahunan - Tahun ${selectedYear}`,
        filenamePrefix: `Laporan_WFA_Tahunan_${selectedYear}`,
      };
    }

    // Semua
    return {
      periodLabel: 'Semua Periode Terdata',
      filenamePrefix: `Laporan_WFA_Rekap_Lengkap_${todayIso}`,
    };
  }, [
    periodType,
    dailyDate,
    weekStartDate,
    weekEndDate,
    selectedMonth,
    selectedMonthYear,
    selectedYear,
    todayIso,
  ]);

  // Filter Submissions based on active Time Period & Filters
  const matchedSubmissions = useMemo(() => {
    return submissions.filter((sub) => {
      // 1. Time Filter
      const tDate = sub.tanggalWfa; // YYYY-MM-DD
      if (!tDate) return false;

      let matchTime = false;
      if (periodType === 'harian') {
        matchTime = tDate === dailyDate;
      } else if (periodType === 'mingguan') {
        matchTime = tDate >= weekStartDate && tDate <= weekEndDate;
      } else if (periodType === 'bulanan') {
        const [yStr, mStr] = tDate.split('-');
        const y = parseInt(yStr, 10);
        const m = parseInt(mStr, 10) - 1; // 0-based
        matchTime = y === selectedMonthYear && m === selectedMonth;
      } else if (periodType === 'tahunan') {
        const [yStr] = tDate.split('-');
        const y = parseInt(yStr, 10);
        matchTime = y === selectedYear;
      } else {
        matchTime = true; // semua
      }

      if (!matchTime) return false;

      // 2. Status Filter
      if (statusFilter !== 'All' && sub.status !== statusFilter) {
        return false;
      }

      // 3. Lokasi Filter
      if (lokasiFilter !== 'All' && sub.lokasiKegiatan !== lokasiFilter) {
        return false;
      }

      // 4. Jenis WFA Filter
      if (jenisWfaFilter !== 'All' && sub.statusWfa?.trim() !== jenisWfaFilter?.trim()) {
        return false;
      }

      return true;
    });
  }, [
    submissions,
    periodType,
    dailyDate,
    weekStartDate,
    weekEndDate,
    selectedMonth,
    selectedMonthYear,
    selectedYear,
    statusFilter,
    lokasiFilter,
    jenisWfaFilter,
  ]);

  // Counts in current selection
  const validCount = matchedSubmissions.filter((s) => s.status === 'Valid').length;
  const pendingCount = matchedSubmissions.filter((s) => s.status === 'Menunggu Validasi').length;
  const rejectedCount = matchedSubmissions.filter((s) => s.status === 'Ditolak').length;

  // Handle Generate and Download PDF
  const handleExecuteDownload = async () => {
    if (matchedSubmissions.length === 0) {
      const confirmEmpty = window.confirm(
        'Tidak ditemukan berkas pengajuan WFA pada periode yang dipilih. Apakah Anda tetap ingin mengunduh laporan berformat kosong?'
      );
      if (!confirmEmpty) return;
    }

    setIsGenerating(true);
    try {
      // Criteria label
      const criteriaParts: string[] = [];
      if (statusFilter !== 'All') criteriaParts.push(`Status: ${statusFilter}`);
      if (lokasiFilter !== 'All') criteriaParts.push(`Wilayah: ${lokasiFilter}`);
      const criteriaLabel = criteriaParts.length > 0 ? criteriaParts.join(' • ') : 'Semua Status & Wilayah';

      await generateWfaPdfReport({
        submissions: matchedSubmissions,
        filterLabel: criteriaLabel,
        periodLabel: periodLabel,
        customFilename: `${filenamePrefix}.pdf`,
        signerCityDate: signerConfig.cityDate,
        signerTitle: signerConfig.title,
        signerName: signerConfig.name,
        signerInstitution: signerConfig.institution,
        signerSignaturePng: signerConfig.signaturePng,
      });

      // Close modal after download triggers
      setTimeout(() => {
        setIsGenerating(false);
        onClose();
      }, 500);
    } catch (err) {
      console.error('Error generating filtered PDF:', err);
      alert('Terjadi kesalahan saat memproses laporan PDF. Silakan coba kembali.');
      setIsGenerating(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/75 backdrop-blur-xs overflow-y-auto">
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 10 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 10 }}
        className="w-full max-w-2xl bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-6"
      >
        {/* Header Modal */}
        <div className="px-5 sm:px-6 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400">
              <FileDown className="w-5 h-5" />
            </div>
            <div>
              <h3 className="font-bold text-sm sm:text-base text-white tracking-tight flex items-center gap-2">
                <span>Konfigurasi Periode Laporan PDF</span>
                <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-medium border border-emerald-500/30">
                  Format Resmi OSDM
                </span>
              </h3>
              <p className="text-[11px] sm:text-xs text-slate-300">
                Pilih filter rentang waktu harian, mingguan, bulanan, atau tahunan sebelum mengunduh.
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content Body */}
        <div className="p-5 sm:p-6 space-y-5 max-h-[80vh] overflow-y-auto">
          {/* TAB PILIHAN PERIODE WAKTU */}
          <div>
            <label className="text-xs font-bold text-slate-800 flex items-center gap-2 mb-2">
              <Clock className="w-3.5 h-3.5 text-indigo-600" />
              <span>1. Tentukan Periode Waktu Laporan:</span>
              <span className="text-rose-500">*</span>
            </label>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button
                type="button"
                onClick={() => setPeriodType('harian')}
                className={`flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all ${
                  periodType === 'harian'
                    ? 'bg-indigo-50/80 border-indigo-500 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs'
                    : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300'
                }`}
              >
                <Calendar className={`w-4 h-4 mb-1 ${periodType === 'harian' ? 'text-indigo-600' : 'text-slate-400'}`} />
                <span className="text-xs font-bold">Harian</span>
                <span className="text-[10px] text-slate-400">1 Hari Kerja</span>
              </button>

              <button
                type="button"
                onClick={() => setPeriodType('mingguan')}
                className={`flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all ${
                  periodType === 'mingguan'
                    ? 'bg-indigo-50/80 border-indigo-500 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs'
                    : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300'
                }`}
              >
                <CalendarRange className={`w-4 h-4 mb-1 ${periodType === 'mingguan' ? 'text-indigo-600' : 'text-slate-400'}`} />
                <span className="text-xs font-bold">Mingguan</span>
                <span className="text-[10px] text-slate-400">Pekan Berjalan</span>
              </button>

              <button
                type="button"
                onClick={() => setPeriodType('bulanan')}
                className={`flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all ${
                  periodType === 'bulanan'
                    ? 'bg-indigo-50/80 border-indigo-500 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs'
                    : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300'
                }`}
              >
                <CalendarDays className={`w-4 h-4 mb-1 ${periodType === 'bulanan' ? 'text-indigo-600' : 'text-slate-400'}`} />
                <span className="text-xs font-bold">Bulanan</span>
                <span className="text-[10px] text-slate-400">1 Bulan Penuh</span>
              </button>

              <button
                type="button"
                onClick={() => setPeriodType('tahunan')}
                className={`flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all ${
                  periodType === 'tahunan'
                    ? 'bg-indigo-50/80 border-indigo-500 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs'
                    : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300'
                }`}
              >
                <Layers className={`w-4 h-4 mb-1 ${periodType === 'tahunan' ? 'text-indigo-600' : 'text-slate-400'}`} />
                <span className="text-xs font-bold">Tahunan</span>
                <span className="text-[10px] text-slate-400">Tahun Anggaran</span>
              </button>
            </div>

            <div className="mt-2 flex items-center justify-end">
              <button
                type="button"
                onClick={() => setPeriodType('semua')}
                className={`text-[11px] font-semibold transition-colors px-2 py-1 rounded-lg ${
                  periodType === 'semua'
                    ? 'bg-slate-900 text-white font-bold'
                    : 'text-slate-500 hover:text-slate-800 underline'
                }`}
              >
                Atau Cetak Semua Periode (Tanpa Batasan Waktu)
              </button>
            </div>
          </div>

          {/* DETAIL KONTROL WAKTU SESUAI PILIHAN */}
          <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
            {/* OPSI 1: HARIAN */}
            {periodType === 'harian' && (
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-700">Pilih Tanggal Pelaksanaan WFA:</span>
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => setDailyDate(todayIso)}
                      className="text-[10px] px-2 py-0.5 rounded-lg bg-indigo-100 text-indigo-700 font-bold hover:bg-indigo-200 transition-colors"
                    >
                      Hari Ini
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        const yest = new Date();
                        yest.setDate(yest.getDate() - 1);
                        setDailyDate(yest.toISOString().slice(0, 10));
                      }}
                      className="text-[10px] px-2 py-0.5 rounded-lg bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300 transition-colors"
                    >
                      Kemarin
                    </button>
                  </div>
                </div>

                <div className="relative">
                  <input
                    type="date"
                    value={dailyDate}
                    onChange={(e) => setDailyDate(e.target.value)}
                    onClick={(e) => {
                      try {
                        e.currentTarget.showPicker();
                      } catch {}
                    }}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 font-bold text-xs sm:text-sm bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                  />
                </div>
                <p className="text-[11px] text-slate-500">
                  Laporan akan merangkum seluruh dosen/tendik yang terjadwal bimbingan WFA pada tanggal{' '}
                  <strong className="text-slate-800">{formatIndoDate(dailyDate)}</strong>.
                </p>
              </div>
            )}

            {/* OPSI 2: MINGGUAN */}
            {periodType === 'mingguan' && (
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-700">Pilihan Rentang Pekan:</span>
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => handleSelectWeeklyPreset('this_week')}
                      className={`text-[10px] px-2.5 py-1 rounded-lg font-bold transition-all ${
                        weeklyPreset === 'this_week'
                          ? 'bg-indigo-600 text-white'
                          : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                      }`}
                    >
                      Minggu Ini (Senin-Minggu)
                    </button>
                    <button
                      type="button"
                      onClick={() => handleSelectWeeklyPreset('last_7_days')}
                      className={`text-[10px] px-2.5 py-1 rounded-lg font-bold transition-all ${
                        weeklyPreset === 'last_7_days'
                          ? 'bg-indigo-600 text-white'
                          : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                      }`}
                    >
                      7 Hari Terakhir
                    </button>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div>
                    <label className="text-[10px] font-bold text-slate-500 uppercase block mb-1">
                      Tanggal Mulai Pekan:
                    </label>
                    <input
                      type="date"
                      value={weekStartDate}
                      onChange={(e) => {
                        setWeekStartDate(e.target.value);
                        setWeeklyPreset('custom');
                      }}
                      onClick={(e) => {
                        try {
                          e.currentTarget.showPicker();
                        } catch {}
                      }}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 font-bold text-xs bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                    />
                  </div>
                  <div>
                    <label className="text-[10px] font-bold text-slate-500 uppercase block mb-1">
                      Tanggal Akhir Pekan:
                    </label>
                    <input
                      type="date"
                      value={weekEndDate}
                      onChange={(e) => {
                        setWeekEndDate(e.target.value);
                        setWeeklyPreset('custom');
                      }}
                      onClick={(e) => {
                        try {
                          e.currentTarget.showPicker();
                        } catch {}
                      }}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 font-bold text-xs bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                    />
                  </div>
                </div>

                <p className="text-[11px] text-slate-500">
                  Rentang terpilih: <strong className="text-slate-800">{formatIndoDate(weekStartDate)}</strong> s.d.{' '}
                  <strong className="text-slate-800">{formatIndoDate(weekEndDate)}</strong>.
                </p>
              </div>
            )}

            {/* OPSI 3: BULANAN */}
            {periodType === 'bulanan' && (
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-700">Pilih Bulan & Tahun Rekapitulasi:</span>
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedMonth(currentMonthIdx);
                      setSelectedMonthYear(currentYear);
                    }}
                    className="text-[10px] px-2 py-0.5 rounded-lg bg-indigo-100 text-indigo-700 font-bold hover:bg-indigo-200 transition-colors"
                  >
                    Bulan Berjalan ({MONTH_NAMES[currentMonthIdx]} {currentYear})
                  </button>
                </div>

                <div className="grid grid-cols-2 gap-2.5">
                  <div>
                    <label className="text-[10px] font-bold text-slate-500 uppercase block mb-1">Bulan:</label>
                    <select
                      value={selectedMonth}
                      onChange={(e) => setSelectedMonth(parseInt(e.target.value, 10))}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 font-bold text-xs bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                    >
                      {MONTH_NAMES.map((m, idx) => (
                        <option key={m} value={idx}>
                          {m}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="text-[10px] font-bold text-slate-500 uppercase block mb-1">Tahun:</label>
                    <select
                      value={selectedMonthYear}
                      onChange={(e) => setSelectedMonthYear(parseInt(e.target.value, 10))}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 font-bold text-xs bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                    >
                      {AVAILABLE_YEARS.map((y) => (
                        <option key={y} value={y}>
                          {y}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>

                <p className="text-[11px] text-slate-500">
                  Laporan akan mencakup seluruh data WFA bimbingan selama bulan{' '}
                  <strong className="text-slate-800">
                    {MONTH_NAMES[selectedMonth]} {selectedMonthYear}
                  </strong>
                  .
                </p>
              </div>
            )}

            {/* OPSI 4: TAHUNAN */}
            {periodType === 'tahunan' && (
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-700">Pilih Tahun Anggaran / Akademik:</span>
                  <button
                    type="button"
                    onClick={() => setSelectedYear(currentYear)}
                    className="text-[10px] px-2 py-0.5 rounded-lg bg-indigo-100 text-indigo-700 font-bold hover:bg-indigo-200 transition-colors"
                  >
                    Tahun Ini ({currentYear})
                  </button>
                </div>

                <div>
                  <select
                    value={selectedYear}
                    onChange={(e) => setSelectedYear(parseInt(e.target.value, 10))}
                    className="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-slate-900 font-bold text-xs sm:text-sm bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                  >
                    {AVAILABLE_YEARS.map((y) => (
                      <option key={y} value={y}>
                        Tahun Anggaran {y}
                      </option>
                    ))}
                  </select>
                </div>

                <p className="text-[11px] text-slate-500">
                  Laporan tahunan merangkum seluruh berkas penugasan dari 1 Januari {selectedYear} hingga 31 Desember{' '}
                  {selectedYear}.
                </p>
              </div>
            )}

            {/* OPSI 5: SEMUA PERIODE */}
            {periodType === 'semua' && (
              <div className="p-2.5 rounded-xl bg-indigo-100/50 border border-indigo-200 text-indigo-900 text-xs flex items-center gap-2">
                <CheckCircle2 className="w-4 h-4 text-indigo-600 shrink-0" />
                <span>Mencetak seluruh rekaman data WFA bimbingan dari awal pencatatan hingga saat ini.</span>
              </div>
            )}
          </div>

          {/* 3. FORMAT KOP SURAT RESMI KEDINASAN */}
          <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
              <label className="text-xs font-bold text-slate-800 flex items-center gap-2">
                <FileText className="w-3.5 h-3.5 text-indigo-600" />
                <span>3. Format Kop Surat Resmi Kedinasan:</span>
              </label>

              <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-[10px] font-bold self-start sm:self-auto">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                Standar Resmi: Kemenkes &amp; Poltekkes Bandung (Model Dua Sisi)
              </span>
            </div>

            {/* Pratinjau Tampilan Header Kop Surat */}
            <div className="bg-white rounded-xl border border-slate-200 p-3 sm:p-4 overflow-hidden shadow-2xs">
              <div className="w-full flex items-center justify-between gap-4">
                {/* Kiri: Logo Asli Kemenkes Poltekkes Bandung */}
                <div className="shrink-0 flex items-center">
                  <img
                    src="/osdm/logo-kemenkes-polkesban.png"
                    alt="Logo Resmi Kemenkes Poltekkes Bandung"
                    className="h-9 sm:h-11 w-auto object-contain"
                  />
                </div>

                {/* Kanan: Identitas Kementerian & Alamat Rata Kanan */}
                <div className="text-right space-y-0.5 shrink-0">
                  <p className="text-[10.5px] sm:text-xs font-bold text-[#00A99D] uppercase tracking-tight">
                    KEMENTERIAN KESEHATAN
                  </p>
                  <p className="text-[8.5px] sm:text-[9.5px] font-bold text-slate-700 leading-tight">
                    Direktorat Jenderal Sumber Daya Manusia Kesehatan
                  </p>
                  <p className="text-[8px] sm:text-[9px] font-semibold text-slate-500 leading-tight">
                    Poltekkes Kemenkes Bandung
                  </p>
                  <p className="text-[7px] sm:text-[8px] text-slate-500 leading-tight">
                    Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171
                  </p>
                  <p className="text-[7px] sm:text-[8px] text-slate-500 leading-tight">
                    Telp: (022) 4231627 | https://poltekkesbandung.ac.id
                  </p>
                </div>
              </div>

              {/* Garis Ganda Pembatas Kedinasan */}
              <div className="mt-2.5 space-y-[2px]">
                <div className="h-[2px] bg-slate-900 w-full" />
                <div className="h-[0.8px] bg-slate-900 w-full" />
              </div>
            </div>

            <p className="text-[10px] text-slate-400">
              Format kop surat resmi dua sisi terpisah ini diterapkan secara otomatis dan proporsional pada dokumen A4 Landscape tanpa distorsi gambar.
            </p>
          </div>

          {/* 3. FILTER TAMBAHAN: STATUS & WILAYAH */}
          <div className="space-y-2 pt-1">
            <label className="text-xs font-bold text-slate-800 flex items-center gap-2">
              <Filter className="w-3.5 h-3.5 text-slate-500" />
              <span>2. Filter Tambahan (Opsional):</span>
            </label>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <span className="text-[10px] font-bold text-slate-500 uppercase block mb-1">
                  Status Validasi Berkas:
                </span>
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value as any)}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                >
                  <option value="All">Semua Status (Valid, Menunggu, Ditolak)</option>
                  <option value="Valid">Hanya Valid / Disetujui</option>
                  <option value="Menunggu Validasi">Hanya Menunggu Validasi</option>
                  <option value="Ditolak">Hanya Ditolak</option>
                </select>
              </div>

              <div>
                <span className="text-[10px] font-bold text-slate-500 uppercase block mb-1">
                  Pilihan Jenis WFA:
                </span>
                <select
                  value={jenisWfaFilter}
                  onChange={(e) => setJenisWfaFilter(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                >
                  <option value="All">Semua Jenis WFA</option>
                  <option value="WFA Datang">WFA Datang</option>
                  <option value="WFA Pulang">WFA Pulang</option>
                  <option value="WFA Datang & WFA Pulang">WFA Datang & Pulang</option>
                  {jenisWfaOptions
                    .filter((opt) => !['WFA Datang', 'WFA Pulang', 'WFA Datang & WFA Pulang'].includes(opt))
                    .map((opt) => (
                      <option key={opt} value={opt}>
                        {opt}
                      </option>
                    ))}
                </select>
              </div>

              <div>
                <span className="text-[10px] font-bold text-slate-500 uppercase block mb-1">
                  Wilayah Penugasan:
                </span>
                <select
                  value={lokasiFilter}
                  onChange={(e) => setLokasiFilter(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 cursor-pointer"
                >
                  <option value="All">Semua Wilayah Penugasan</option>
                  <option value="Kota Bandung">Kota Bandung</option>
                  <option value="Kabupaten Bandung">Kabupaten Bandung</option>
                  <option value="Kota Bogor">Kota Bogor</option>
                  <option value="Kabupaten Bogor">Kabupaten Bogor</option>
                  <option value="Kota Karawang">Kota Karawang</option>
                  <option value="Kabupaten Karawang">Kabupaten Karawang</option>
                </select>
              </div>
            </div>
          </div>

          {/* 4. PENGESAHAN & TANDA TANGAN DIGITAL (PNG) */}
          <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3.5">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
              <label className="text-xs font-bold text-slate-800 flex items-center gap-2">
                <FileSignature className="w-3.5 h-3.5 text-indigo-600" />
                <span>4. Pengesahan &amp; Tanda Tangan Digital Resmi (Khusus Format PNG):</span>
              </label>

              <div className="flex items-center gap-2">
                {signerConfig.signaturePng ? (
                  <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-[10px] font-bold">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                    TTD Digital Aktif
                  </span>
                ) : (
                  <span className="text-[10px] font-medium text-slate-500 bg-slate-200/70 px-2 py-0.5 rounded-full">
                    Mode TTD Basah
                  </span>
                )}

                <button
                  type="button"
                  onClick={handleResetSigner}
                  className="text-[10px] font-semibold text-slate-500 hover:text-indigo-600 hover:underline flex items-center gap-1 cursor-pointer"
                  title="Kembalikan nama penandatangan ke bawaan"
                >
                  <RotateCcw className="w-3 h-3" />
                  Reset
                </button>
              </div>
            </div>

            {/* Form Fields Penandatangan */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <div className="space-y-1">
                <span className="text-[10px] font-bold text-slate-600 flex items-center gap-1">
                  <Calendar className="w-3 h-3 text-slate-400" />
                  Kota &amp; Tanggal Pengesahan:
                </span>
                <input
                  type="text"
                  value={signerConfig.cityDate}
                  onChange={(e) => handleSignerFieldChange('cityDate', e.target.value)}
                  placeholder="Contoh: Bandung, 07 Oktober 2026"
                  className="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:border-indigo-500"
                />
              </div>

              <div className="space-y-1">
                <span className="text-[10px] font-bold text-slate-600 flex items-center gap-1">
                  <Briefcase className="w-3 h-3 text-slate-400" />
                  Jabatan Penandatangan:
                </span>
                <input
                  type="text"
                  value={signerConfig.title}
                  onChange={(e) => handleSignerFieldChange('title', e.target.value)}
                  placeholder="Contoh: Penanggung Jawab Tim Kerja OSDM,"
                  className="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:border-indigo-500"
                />
              </div>

              <div className="space-y-1">
                <span className="text-[10px] font-bold text-slate-600 flex items-center gap-1">
                  <User className="w-3 h-3 text-slate-400" />
                  Nama Lengkap / Tim Penandatangan:
                </span>
                <input
                  type="text"
                  value={signerConfig.name}
                  onChange={(e) => handleSignerFieldChange('name', e.target.value)}
                  placeholder="Contoh: Tim Kerja OSDM atau dr. H. ..."
                  className="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:border-indigo-500 font-medium"
                />
              </div>

              <div className="space-y-1">
                <span className="text-[10px] font-bold text-slate-600 flex items-center gap-1">
                  <Building2 className="w-3 h-3 text-slate-400" />
                  Lembaga / NIP Pejabat:
                </span>
                <input
                  type="text"
                  value={signerConfig.institution}
                  onChange={(e) => handleSignerFieldChange('institution', e.target.value)}
                  placeholder="Contoh: Poltekkes Kemenkes Bandung"
                  className="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:border-indigo-500"
                />
              </div>
            </div>

            {/* Input file & Dropzone TTD Digital PNG */}
            <input
              type="file"
              ref={sigFileInputRef}
              accept="image/png"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) handleProcessSigFile(file);
              }}
              className="hidden"
            />

            {signerConfig.signaturePng ? (
              <div className="p-3 rounded-xl border border-emerald-200 bg-emerald-50/50 flex items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                  <div className="w-24 h-14 bg-[linear-gradient(45deg,#f1f5f9_25%,transparent_25%),linear-gradient(-45deg,#f1f5f9_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#f1f5f9_75%),linear-gradient(-45deg,transparent_75%,#f1f5f9_75%)] bg-[size:8px_8px] bg-[position:0_0,0_4px,4px_-4px,-4px_0] border border-slate-200 rounded-lg p-1.5 flex items-center justify-center shrink-0">
                    <img
                      src={signerConfig.signaturePng}
                      alt="Pratinjau TTD PNG"
                      className="max-h-full max-w-full object-contain"
                    />
                  </div>
                  <div>
                    <span className="text-xs font-bold text-slate-800 block">
                      {signerConfig.signatureFilename || 'tanda_tangan.png'}
                    </span>
                    <span className="text-[10px] text-slate-500">
                      Format PNG transparan valid. TTD otomatis dicetak di atas nama penandatangan.
                    </span>
                  </div>
                </div>

                <div className="flex items-center gap-1.5 shrink-0">
                  <button
                    type="button"
                    onClick={() => sigFileInputRef.current?.click()}
                    className="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                  >
                    Ganti
                  </button>
                  <button
                    type="button"
                    onClick={handleRemoveSignature}
                    className="p-1.5 rounded-lg text-rose-600 hover:bg-rose-100 border border-rose-200 transition-colors cursor-pointer"
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
                  setIsDraggingSig(true);
                }}
                onDragLeave={() => setIsDraggingSig(false)}
                onDrop={(e) => {
                  e.preventDefault();
                  setIsDraggingSig(false);
                  const file = e.dataTransfer.files?.[0];
                  if (file) handleProcessSigFile(file);
                }}
                onClick={() => sigFileInputRef.current?.click()}
                className={`border-2 border-dashed rounded-xl p-3 text-center cursor-pointer transition-all ${
                  isDraggingSig
                    ? 'border-indigo-500 bg-indigo-50'
                    : 'border-slate-300 bg-white hover:border-indigo-400 hover:bg-indigo-50/20'
                }`}
              >
                <div className="flex items-center justify-center gap-2 text-slate-700">
                  <Upload className="w-4 h-4 text-indigo-600 shrink-0" />
                  <span className="text-xs font-bold">
                    Upload Tanda Tangan Digital (Format PNG, maks. 3 MB)
                  </span>
                </div>
                <p className="text-[10px] text-slate-400 mt-0.5">
                  Disarankan gambar PNG berlatar transparan agar menyatu dengan dokumen PDF.
                </p>
              </div>
            )}

            {sigUploadError && (
              <div className="p-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-[11px] flex items-center gap-1.5">
                <AlertCircle className="w-3.5 h-3.5 shrink-0 text-rose-600" />
                <span>{sigUploadError}</span>
              </div>
            )}

            {/* Pratinjau Tampilan Blok Pengesahan di Dokumen PDF */}
            <div className="pt-1 space-y-1.5">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">
                Pratinjau Blok Tanda Tangan di PDF:
              </span>
              <div className="p-3.5 rounded-xl bg-white border border-slate-200 text-center flex flex-col items-center justify-center">
                <span className="text-[11px] text-slate-700">{signerConfig.cityDate}</span>
                <span className="text-[11px] text-slate-800 font-semibold">{signerConfig.title}</span>

                <div className="h-14 flex items-center justify-center my-1">
                  {signerConfig.signaturePng ? (
                    <img
                      src={signerConfig.signaturePng}
                      alt="TTD"
                      className="max-h-12 max-w-[130px] object-contain drop-shadow-2xs"
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
                <span className="text-[10px] text-slate-500 mt-0.5">{signerConfig.institution}</span>
              </div>
            </div>
          </div>

          {/* 5. RINGKASAN PRATINJAU HASIL FILTER */}
          <div className="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white space-y-3">
            <div className="flex items-center justify-between border-b border-slate-700/80 pb-2.5">
              <div className="flex items-center gap-2">
                <Sparkles className="w-4 h-4 text-emerald-400" />
                <span className="text-xs font-bold uppercase tracking-wider text-slate-300">
                  5. Ringkasan Pratinjau Berkas Siap Cetak
                </span>
              </div>
              <span className="text-xs font-mono font-bold text-emerald-400">
                {matchedSubmissions.length} Berkas Ditemukan
              </span>
            </div>

            <div className="grid grid-cols-3 gap-2 text-center text-xs">
              <div className="p-2 rounded-xl bg-white/5 border border-white/10">
                <p className="text-[10px] text-emerald-400 font-semibold">Disetujui (Valid)</p>
                <p className="text-lg font-black text-white mt-0.5">{validCount}</p>
              </div>
              <div className="p-2 rounded-xl bg-white/5 border border-white/10">
                <p className="text-[10px] text-amber-400 font-semibold">Menunggu</p>
                <p className="text-lg font-black text-white mt-0.5">{pendingCount}</p>
              </div>
              <div className="p-2 rounded-xl bg-white/5 border border-white/10">
                <p className="text-[10px] text-rose-400 font-semibold">Ditolak</p>
                <p className="text-lg font-black text-white mt-0.5">{rejectedCount}</p>
              </div>
            </div>

            <div className="text-[11px] text-slate-300 pt-1 space-y-1">
              <p className="flex items-center gap-1.5">
                <span className="text-slate-400">Label Laporan:</span>
                <span className="font-semibold text-white">{periodLabel}</span>
              </p>
              <p className="flex items-center gap-1.5 font-mono text-[10px] text-slate-400">
                <span>Nama File:</span>
                <span className="text-indigo-300">{filenamePrefix}.pdf</span>
              </p>
            </div>
          </div>
        </div>

        {/* Footer Actions */}
        <div className="px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
          <button
            type="button"
            onClick={onClose}
            disabled={isGenerating}
            className="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-100 transition-colors"
          >
            Batal
          </button>

          <button
            type="button"
            onClick={handleExecuteDownload}
            disabled={isGenerating}
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-all disabled:opacity-50"
          >
            <FileDown className="w-4 h-4" />
            <span>
              {isGenerating
                ? 'Sedang Memproses PDF...'
                : `Unduh Laporan PDF (${matchedSubmissions.length} Berkas)`}
            </span>
          </button>
        </div>
      </motion.div>
    </div>
  );
};

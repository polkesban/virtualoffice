import React, { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  BarChart3,
  TrendingUp,
  PieChart,
  Calendar,
  CalendarDays,
  MapPin,
  Building2,
  CheckCircle2,
  Clock,
  AlertCircle,
  X,
  FileDown,
  Layers,
  Sparkles,
  Users,
  Check,
  ChevronRight,
  Filter,
} from 'lucide-react';
import { WfaSubmission } from '../types';
import { generateWfaDashboardAnalyticsPdf } from '../utils/wfaDashboardPdfReport';
import { WfaDashboardExportPdfModal } from './WfaDashboardExportPdfModal';

export type DashboardPeriodMode = 'semua' | 'harian' | 'bulanan' | 'tahunan';

interface WfaDashboardModalProps {
  isOpen: boolean;
  onClose: () => void;
  submissions: WfaSubmission[];
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

export const WfaDashboardModal: React.FC<WfaDashboardModalProps> = ({
  isOpen,
  onClose,
  submissions,
}) => {
  const todayYmd = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const currentYear = useMemo(() => new Date().getFullYear(), []);
  const currentMonth = useMemo(() => new Date().getMonth(), []);

  // Filter States
  const [periodMode, setPeriodMode] = useState<DashboardPeriodMode>('bulanan');
  const [selectedDate, setSelectedDate] = useState(todayYmd);
  const [selectedMonth, setSelectedMonth] = useState(currentMonth);
  const [selectedYear, setSelectedYear] = useState(currentYear);
  const [isExportingPdf, setIsExportingPdf] = useState(false);
  const [isExportPdfModalOpen, setIsExportPdfModalOpen] = useState(false);

  // Filter submissions according to selected period
  const filteredSubmissions = useMemo(() => {
    return submissions.filter((sub) => {
      const dateStr = sub.tanggalWfa;
      if (!dateStr) return false;

      if (periodMode === 'harian') {
        return dateStr === selectedDate;
      }

      if (periodMode === 'bulanan') {
        const parts = dateStr.split('-');
        if (parts.length < 2) return false;
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        return y === selectedYear && m === selectedMonth;
      }

      if (periodMode === 'tahunan') {
        const parts = dateStr.split('-');
        if (parts.length < 1) return false;
        const y = parseInt(parts[0], 10);
        return y === selectedYear;
      }

      // 'semua'
      return true;
    });
  }, [submissions, periodMode, selectedDate, selectedMonth, selectedYear]);

  // Label text for current period
  const activePeriodLabel = useMemo(() => {
    if (periodMode === 'harian') {
      const parts = selectedDate.split('-');
      if (parts.length === 3) {
        const d = parseInt(parts[2], 10);
        const m = MONTH_NAMES[parseInt(parts[1], 10) - 1] || parts[1];
        return `Harian: ${d} ${m} ${parts[0]}`;
      }
      return `Harian: ${selectedDate}`;
    }
    if (periodMode === 'bulanan') {
      return `Bulan: ${MONTH_NAMES[selectedMonth]} ${selectedYear}`;
    }
    if (periodMode === 'tahunan') {
      return `Tahun Anggaran: ${selectedYear}`;
    }
    return 'Semua Periode Terdata (Akumulasi)';
  }, [periodMode, selectedDate, selectedMonth, selectedYear]);

  // Key KPI Metrics
  const metrics = useMemo(() => {
    const total = filteredSubmissions.length;
    const valid = filteredSubmissions.filter((s) => s.status === 'Valid').length;
    const pending = filteredSubmissions.filter((s) => s.status === 'Menunggu Validasi').length;
    const rejected = filteredSubmissions.filter((s) => s.status === 'Ditolak').length;

    // Unique employees
    const uniqueEmployees = new Set(filteredSubmissions.map((s) => s.nip || s.employeeName)).size;

    const validPct = total > 0 ? ((valid / total) * 100).toFixed(1) : '0';
    const pendingPct = total > 0 ? ((pending / total) * 100).toFixed(1) : '0';
    const rejectedPct = total > 0 ? ((rejected / total) * 100).toFixed(1) : '0';

    return {
      total,
      valid,
      pending,
      rejected,
      uniqueEmployees,
      validPct,
      pendingPct,
      rejectedPct,
    };
  }, [filteredSubmissions]);

  // Distribution by Unit Kerja / Jurusan
  const unitKerjaStats = useMemo(() => {
    const map = new Map<string, { total: number; valid: number; pending: number; rejected: number }>();

    filteredSubmissions.forEach((s) => {
      const unit = (s.unitKerja && s.unitKerja.trim() !== '' ? s.unitKerja.trim() : 'Direktorat / Unit Lainnya');
      const current = map.get(unit) || { total: 0, valid: 0, pending: 0, rejected: 0 };
      current.total += 1;
      if (s.status === 'Valid') current.valid += 1;
      else if (s.status === 'Menunggu Validasi') current.pending += 1;
      else if (s.status === 'Ditolak') current.rejected += 1;
      map.set(unit, current);
    });

    return Array.from(map.entries())
      .map(([name, stat]) => ({
        name,
        ...stat,
      }))
      .sort((a, b) => b.total - a.total);
  }, [filteredSubmissions]);

  // Distribution by Wilayah Kegiatan
  const wilayahStats = useMemo(() => {
    const standardWilayah = [
      'Kota Bandung',
      'Kabupaten Bandung',
      'Kota Bogor',
      'Kabupaten Bogor',
      'Kota Karawang',
      'Kabupaten Karawang',
    ];

    const map = new Map<string, { total: number; valid: number; rejected: number }>();
    standardWilayah.forEach((w) => map.set(w, { total: 0, valid: 0, rejected: 0 }));

    filteredSubmissions.forEach((s) => {
      const loc = (s.lokasiKegiatan || 'Lainnya').trim();
      const current = map.get(loc) || { total: 0, valid: 0, rejected: 0 };
      current.total += 1;
      if (s.status === 'Valid') current.valid += 1;
      else if (s.status === 'Ditolak') current.rejected += 1;
      map.set(loc, current);
    });

    return Array.from(map.entries())
      .map(([name, stat]) => ({ name, ...stat }))
      .sort((a, b) => b.total - a.total);
  }, [filteredSubmissions]);

  // Distribution by Presensi (WFA Datang, WFA Pulang, Datang & Pulang)
  const presensiStats = useMemo(() => {
    let datang = 0;
    let pulang = 0;
    let datangPulang = 0;

    filteredSubmissions.forEach((s) => {
      const status = (s.statusWfa || '').trim();
      if (status.includes('&')) {
        datangPulang += 1;
      } else if (status.includes('Pulang')) {
        pulang += 1;
      } else if (status.includes('Datang')) {
        datang += 1;
      }
    });

    const total = filteredSubmissions.length;
    return [
      {
        label: 'WFA Datang',
        count: datang,
        pct: total > 0 ? ((datang / total) * 100).toFixed(1) : '0',
        color: 'bg-emerald-500',
        textColor: 'text-emerald-700',
        badgeBg: 'bg-emerald-50 text-emerald-800 border-emerald-200',
      },
      {
        label: 'WFA Pulang',
        count: pulang,
        pct: total > 0 ? ((pulang / total) * 100).toFixed(1) : '0',
        color: 'bg-teal-500',
        textColor: 'text-teal-700',
        badgeBg: 'bg-teal-50 text-teal-800 border-teal-200',
      },
      {
        label: 'WFA Datang & Pulang (Full Day)',
        count: datangPulang,
        pct: total > 0 ? ((datangPulang / total) * 100).toFixed(1) : '0',
        color: 'bg-indigo-500',
        textColor: 'text-indigo-700',
        badgeBg: 'bg-indigo-50 text-indigo-800 border-indigo-200',
      },
    ];
  }, [filteredSubmissions]);

  // Time Trend Data (Bar Chart)
  // If 'tahunan' or 'semua': show 12 months (Jan - Dec)
  // If 'bulanan': show days in month (1..31)
  const timeTrendData = useMemo(() => {
    if (periodMode === 'bulanan') {
      const daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
      const dayCounts = Array.from({ length: daysInMonth }, (_, i) => ({
        label: `${i + 1}`,
        fullLabel: `Tgl ${i + 1} ${MONTH_NAMES[selectedMonth]}`,
        total: 0,
        valid: 0,
        rejected: 0,
      }));

      filteredSubmissions.forEach((s) => {
        const parts = (s.tanggalWfa || '').split('-');
        if (parts.length === 3) {
          const day = parseInt(parts[2], 10);
          if (day >= 1 && day <= daysInMonth) {
            dayCounts[day - 1].total += 1;
            if (s.status === 'Valid') dayCounts[day - 1].valid += 1;
            else if (s.status === 'Ditolak') dayCounts[day - 1].rejected += 1;
          }
        }
      });

      const maxVal = Math.max(...dayCounts.map((d) => d.total), 1);
      return { items: dayCounts, maxVal, type: 'daily' as const };
    }

    // Yearly or All Time: Monthly chart (Jan - Dec)
    const monthCounts = MONTH_NAMES.map((name, idx) => ({
      label: name.slice(0, 3),
      fullLabel: `${name} ${selectedYear}`,
      total: 0,
      valid: 0,
      rejected: 0,
    }));

    filteredSubmissions.forEach((s) => {
      const parts = (s.tanggalWfa || '').split('-');
      if (parts.length >= 2) {
        const m = parseInt(parts[1], 10) - 1;
        if (m >= 0 && m < 12) {
          monthCounts[m].total += 1;
          if (s.status === 'Valid') monthCounts[m].valid += 1;
          else if (s.status === 'Ditolak') monthCounts[m].rejected += 1;
        }
      }
    });

    const maxVal = Math.max(...monthCounts.map((d) => d.total), 1);
    return { items: monthCounts, maxVal, type: 'monthly' as const };
  }, [filteredSubmissions, periodMode, selectedMonth, selectedYear]);

  // Handler: Export current dashboard view to official Executive Dashboard & Analytics PDF Report
  const handleExportDashboardPdf = async () => {
    if (filteredSubmissions.length === 0) {
      alert('Tidak ada data pengajuan pada periode ini untuk diekspor.');
      return;
    }

    setIsExportingPdf(true);
    try {
      await generateWfaDashboardAnalyticsPdf({
        submissions: filteredSubmissions,
        periodMode,
        periodLabel: activePeriodLabel,
        selectedMonth,
        selectedYear,
        customFilename: `Laporan_Eksekutif_Dashboard_WFA_${periodMode}_${Date.now()}.pdf`,
      });
    } catch (err) {
      console.error('Failed to export dashboard PDF:', err);
      alert('Terjadi kesalahan saat memproses ekspor PDF Dashboard.');
    } finally {
      setIsExportingPdf(false);
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/80 backdrop-blur-md overflow-y-auto">
        <motion.div
          initial={{ opacity: 0, scale: 0.96, y: 14 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.96, y: 14 }}
          transition={{ duration: 0.22, ease: 'easeOut' }}
          className="relative w-full max-w-5xl bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-auto max-h-[94vh] flex flex-col text-slate-800"
        >
          {/* TOP HEADER BANNER */}
          <div className="py-4 px-5 sm:px-7 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shrink-0 relative overflow-hidden shadow-md">
            {/* Decorative background ambient circles */}
            <div className="absolute inset-0 pointer-events-none">
              <div className="absolute -top-10 -right-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl" />
              <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl" />
            </div>

            <div className="relative flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-400 shrink-0 shadow-xs">
                  <BarChart3 className="w-5 h-5" />
                </div>
                <div>
                  <div className="flex items-center gap-2 flex-wrap">
                    <h3 className="font-bold text-base sm:text-lg text-white tracking-tight">
                      Dashboard Analitik &amp; Statistik WFA
                    </h3>
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                      Live Data
                    </span>
                  </div>
                  <p className="text-[11px] sm:text-xs text-slate-300 mt-0.5">
                    Rekapitulasi total pengajuan harian, bulanan, tahunan, status validasi, jurusan, dan wilayah.
                  </p>
                </div>
              </div>

              {/* Action Buttons in Header: Hanya tombol Tutup (X), tombol ekspor dipusatkan di footer agar tidak redundan */}
              <div className="flex items-center gap-2 self-end sm:self-center">
                <button
                  type="button"
                  onClick={onClose}
                  className="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                  aria-label="Tutup"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>

          {/* DASHBOARD CONTENT BODY */}
          <div className="p-4 sm:p-6 space-y-5 overflow-y-auto flex-1 bg-slate-50/50">
            {/* 1. FILTER PERIODE TABS */}
            <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                <div className="flex items-center gap-2">
                  <Filter className="w-4 h-4 text-indigo-600 shrink-0" />
                  <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Pilihan Periode Waktu Dashboard:
                  </span>
                </div>
                <div className="text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full self-start sm:self-auto">
                  {activePeriodLabel} ({metrics.total} Berkas)
                </div>
              </div>

              {/* Mode Buttons */}
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                  type="button"
                  onClick={() => setPeriodMode('harian')}
                  className={`flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    periodMode === 'harian'
                      ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-500/20'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'
                  }`}
                >
                  <Calendar className="w-3.5 h-3.5" />
                  <span>Harian</span>
                </button>

                <button
                  type="button"
                  onClick={() => setPeriodMode('bulanan')}
                  className={`flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    periodMode === 'bulanan'
                      ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-500/20'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'
                  }`}
                >
                  <CalendarDays className="w-3.5 h-3.5" />
                  <span>Bulanan</span>
                </button>

                <button
                  type="button"
                  onClick={() => setPeriodMode('tahunan')}
                  className={`flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    periodMode === 'tahunan'
                      ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-500/20'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'
                  }`}
                >
                  <TrendingUp className="w-3.5 h-3.5" />
                  <span>Tahunan</span>
                </button>

                <button
                  type="button"
                  onClick={() => setPeriodMode('semua')}
                  className={`flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    periodMode === 'semua'
                      ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-500/20'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'
                  }`}
                >
                  <Layers className="w-3.5 h-3.5" />
                  <span>Semua Periode</span>
                </button>
              </div>

              {/* Dynamic Pickers based on mode */}
              {periodMode === 'harian' && (
                <div className="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2.5">
                  <span className="text-xs font-semibold text-slate-600">Pilih Tanggal:</span>
                  <input
                    type="date"
                    value={selectedDate}
                    onChange={(e) => setSelectedDate(e.target.value)}
                    className="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-bold bg-white text-slate-900 cursor-pointer focus:outline-none focus:border-indigo-500"
                  />
                  <button
                    type="button"
                    onClick={() => setSelectedDate(todayYmd)}
                    className="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs border border-indigo-200 transition-colors cursor-pointer"
                  >
                    Hari Ini
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      const d = new Date();
                      d.setDate(d.getDate() - 1);
                      setSelectedDate(d.toISOString().slice(0, 10));
                    }}
                    className="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs border border-slate-200 transition-colors cursor-pointer"
                  >
                    Kemarin
                  </button>
                </div>
              )}

              {periodMode === 'bulanan' && (
                <div className="pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-semibold text-slate-600 w-16">Bulan:</span>
                    <select
                      value={selectedMonth}
                      onChange={(e) => setSelectedMonth(parseInt(e.target.value, 10))}
                      className="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-bold bg-white text-slate-900 cursor-pointer focus:outline-none focus:border-indigo-500"
                    >
                      {MONTH_NAMES.map((m, idx) => (
                        <option key={m} value={idx}>
                          {m}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-semibold text-slate-600 w-16">Tahun:</span>
                    <select
                      value={selectedYear}
                      onChange={(e) => setSelectedYear(parseInt(e.target.value, 10))}
                      className="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-bold bg-white text-slate-900 cursor-pointer focus:outline-none focus:border-indigo-500"
                    >
                      {AVAILABLE_YEARS.map((y) => (
                        <option key={y} value={y}>
                          {y}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              )}

              {periodMode === 'tahunan' && (
                <div className="pt-2 border-t border-slate-100 flex items-center gap-2.5">
                  <span className="text-xs font-semibold text-slate-600">Pilih Tahun Anggaran:</span>
                  <select
                    value={selectedYear}
                    onChange={(e) => setSelectedYear(parseInt(e.target.value, 10))}
                    className="px-4 py-1.5 rounded-lg border border-slate-300 text-xs font-bold bg-white text-slate-900 cursor-pointer focus:outline-none focus:border-indigo-500"
                  >
                    {AVAILABLE_YEARS.map((y) => (
                      <option key={y} value={y}>
                        Tahun {y}
                      </option>
                    ))}
                  </select>
                </div>
              )}
            </div>

            {/* 2. KEY KPI METRIC CARDS */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
              {/* Card 1: Total Pengajuan */}
              <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs relative overflow-hidden">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Total Pengajuan
                  </span>
                  <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                    <BarChart3 className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl sm:text-3xl font-black text-slate-900 font-mono">
                    {metrics.total}
                  </span>
                  <span className="text-[11px] font-semibold text-slate-400">Berkas WFA</span>
                </div>
                <div className="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                  <span>Pegawai Pemohon:</span>
                  <span className="font-bold text-slate-800 font-mono">{metrics.uniqueEmployees} Orang</span>
                </div>
              </div>

              {/* Card 2: Valid (Disetujui) */}
              <div className="p-4 rounded-2xl bg-emerald-50/90 border border-emerald-200 shadow-xs relative overflow-hidden">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-emerald-800 uppercase tracking-wider">
                    Sudah Valid
                  </span>
                  <div className="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <CheckCircle2 className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl sm:text-3xl font-black text-emerald-900 font-mono">
                    {metrics.valid}
                  </span>
                  <span className="text-[11px] font-bold text-emerald-700 bg-emerald-200/80 px-2 py-0.5 rounded-full">
                    {metrics.validPct}% Lolos
                  </span>
                </div>
                <div className="mt-2 pt-2 border-t border-emerald-100 flex items-center justify-between text-[11px] text-emerald-700 font-medium">
                  <span>Terjadwal Aktif:</span>
                  <span className="font-bold font-mono">{metrics.valid} Berkas</span>
                </div>
              </div>

              {/* Card 3: Menunggu Validasi */}
              <div className="p-4 rounded-2xl bg-amber-50/90 border border-amber-200 shadow-xs relative overflow-hidden">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-amber-800 uppercase tracking-wider">
                    Menunggu
                  </span>
                  <div className="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                    <Clock className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl sm:text-3xl font-black text-amber-900 font-mono">
                    {metrics.pending}
                  </span>
                  <span className="text-[11px] font-bold text-amber-800 bg-amber-200/80 px-2 py-0.5 rounded-full">
                    {metrics.pendingPct}% Antrean
                  </span>
                </div>
                <div className="mt-2 pt-2 border-t border-amber-100 flex items-center justify-between text-[11px] text-amber-700 font-medium">
                  <span>Perlu Tindakan OSDM:</span>
                  <span className="font-bold font-mono">{metrics.pending} Berkas</span>
                </div>
              </div>

              {/* Card 4: Ditolak / Revisi */}
              <div className="p-4 rounded-2xl bg-rose-50/90 border border-rose-200 shadow-xs relative overflow-hidden">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-rose-800 uppercase tracking-wider">
                    Ditolak / Revisi
                  </span>
                  <div className="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center">
                    <AlertCircle className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl sm:text-3xl font-black text-rose-900 font-mono">
                    {metrics.rejected}
                  </span>
                  <span className="text-[11px] font-bold text-rose-800 bg-rose-200/80 px-2 py-0.5 rounded-full">
                    {metrics.rejectedPct}% Revisi
                  </span>
                </div>
                <div className="mt-2 pt-2 border-t border-rose-100 flex items-center justify-between text-[11px] text-rose-700 font-medium">
                  <span>Berkas Dikembalikan:</span>
                  <span className="font-bold font-mono">{metrics.rejected} Berkas</span>
                </div>
              </div>
            </div>

            {/* 3. VISUAL STATUS PROPORTION BAR */}
            <div className="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                  <PieChart className="w-4 h-4 text-indigo-600" />
                  <span>Komposisi Validasi Berkas:</span>
                </span>
                <span className="text-xs text-slate-500 font-mono">100% Total Proporsi</span>
              </div>

              {/* Segmented Bar */}
              <div className="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                <div
                  style={{ width: `${metrics.validPct}%` }}
                  className="bg-emerald-500 h-full transition-all duration-500"
                  title={`Valid: ${metrics.valid} (${metrics.validPct}%)`}
                />
                <div
                  style={{ width: `${metrics.pendingPct}%` }}
                  className="bg-amber-400 h-full transition-all duration-500"
                  title={`Menunggu: ${metrics.pending} (${metrics.pendingPct}%)`}
                />
                <div
                  style={{ width: `${metrics.rejectedPct}%` }}
                  className="bg-rose-500 h-full transition-all duration-500"
                  title={`Ditolak: ${metrics.rejected} (${metrics.rejectedPct}%)`}
                />
              </div>

              {/* Legend & Breakdown */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs pt-1">
                <div className="flex items-center justify-between p-2 rounded-xl bg-emerald-50/60 border border-emerald-200">
                  <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-500" />
                    <span className="font-semibold text-emerald-900">Valid (Disetujui)</span>
                  </div>
                  <span className="font-bold font-mono text-emerald-800">
                    {metrics.valid} ({metrics.validPct}%)
                  </span>
                </div>

                <div className="flex items-center justify-between p-2 rounded-xl bg-amber-50/60 border border-amber-200">
                  <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-amber-400" />
                    <span className="font-semibold text-amber-900">Menunggu Validasi</span>
                  </div>
                  <span className="font-bold font-mono text-amber-800">
                    {metrics.pending} ({metrics.pendingPct}%)
                  </span>
                </div>

                <div className="flex items-center justify-between p-2 rounded-xl bg-rose-50/60 border border-rose-200">
                  <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-rose-500" />
                    <span className="font-semibold text-rose-900">Ditolak / Revisi</span>
                  </div>
                  <span className="font-bold font-mono text-rose-800">
                    {metrics.rejected} ({metrics.rejectedPct}%)
                  </span>
                </div>
              </div>
            </div>

            {/* 4. TIME TREND CHART (BAR CHART) */}
            <div className="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
              <div className="flex items-center justify-between flex-wrap gap-2">
                <div className="flex items-center gap-2">
                  <TrendingUp className="w-4 h-4 text-indigo-600" />
                  <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Grafik Tren Volume Pengajuan ({timeTrendData.type === 'daily' ? 'Per Hari' : 'Per Bulan'})
                  </h4>
                </div>
                <span className="text-[11px] text-slate-500">
                  Maks: <strong className="font-mono text-slate-800">{timeTrendData.maxVal} Berkas</strong>
                </span>
              </div>

              {/* Bar Chart Container */}
              <div className="pt-4 pb-1 overflow-x-auto no-scrollbar">
                <div className="min-w-[500px] flex items-end gap-1.5 sm:gap-2 h-44 px-1 border-b border-slate-200">
                  {timeTrendData.items.map((item, idx) => {
                    const heightPct = Math.max((item.total / timeTrendData.maxVal) * 100, 3);
                    const isPeak = item.total > 0 && item.total === timeTrendData.maxVal;

                    return (
                      <div
                        key={idx}
                        className="flex-1 flex flex-col items-center justify-end h-full group relative"
                      >
                        {/* Tooltip on hover */}
                        <div className="absolute -top-10 hidden group-hover:flex flex-col items-center z-10 pointer-events-none">
                          <div className="bg-slate-900 text-white text-[10px] px-2 py-1 rounded shadow-md whitespace-nowrap">
                            <p className="font-bold">{item.fullLabel}</p>
                            <p className="font-mono text-emerald-400">Total: {item.total} Berkas</p>
                            <p className="text-[9px] text-slate-300">
                              Valid: {item.valid} | Ditolak: {item.rejected}
                            </p>
                          </div>
                          <div className="w-1.5 h-1.5 bg-slate-900 rotate-45 -mt-0.5" />
                        </div>

                        {/* Bar Value text above bar */}
                        {item.total > 0 && (
                          <span
                            className={`text-[9px] font-mono font-bold mb-1 ${
                              isPeak ? 'text-indigo-600' : 'text-slate-500'
                            }`}
                          >
                            {item.total}
                          </span>
                        )}

                        {/* Bar pillar */}
                        <div
                          style={{ height: `${heightPct}%` }}
                          className={`w-full rounded-t-md transition-all duration-300 ${
                            isPeak
                              ? 'bg-gradient-to-t from-indigo-600 to-indigo-400 shadow-sm'
                              : item.total > 0
                              ? 'bg-gradient-to-t from-indigo-500/80 to-indigo-400/70 hover:from-indigo-600 hover:to-indigo-500'
                              : 'bg-slate-100'
                          }`}
                        />

                        {/* Label below bar */}
                        <span className="text-[10px] text-slate-500 mt-2 font-mono group-hover:text-indigo-600 font-medium">
                          {item.label}
                        </span>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* 5. DUA KOLOM: JURUSAN / UNIT KERJA & WILAYAH / PRESENSI */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
              {/* Kolom Kiri: Peringkat Jurusan / Unit Kerja (7 cols) */}
              <div className="lg:col-span-7 p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Building2 className="w-4 h-4 text-emerald-600" />
                    <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                      Distribusi per Jurusan / Unit Kerja
                    </h4>
                  </div>
                  <span className="text-xs text-slate-500 font-mono">
                    {unitKerjaStats.length} Unit Kerja
                  </span>
                </div>

                <div className="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                  {unitKerjaStats.length === 0 ? (
                    <p className="text-xs text-slate-400 text-center py-6">
                      Tidak ada data pengajuan pada periode ini.
                    </p>
                  ) : (
                    unitKerjaStats.map((u, idx) => {
                      const maxUnit = unitKerjaStats[0]?.total || 1;
                      const barWidth = Math.max((u.total / maxUnit) * 100, 4);

                      return (
                        <div key={u.name} className="p-2.5 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-300 transition-colors">
                          <div className="flex items-center justify-between text-xs mb-1.5">
                            <span className="font-bold text-slate-800 flex items-center gap-1.5 truncate max-w-[70%]">
                              <span className="w-4 text-slate-400 font-mono text-[10px]">#{idx + 1}</span>
                              <span className="truncate" title={u.name}>{u.name}</span>
                            </span>
                            <div className="flex items-center gap-2 shrink-0 font-mono">
                              <span className="font-bold text-slate-900">{u.total} Berkas</span>
                              <span className="text-[10px] text-emerald-700 font-bold bg-emerald-100 px-1.5 py-0.2 rounded">
                                {u.valid} ✓
                              </span>
                              {u.rejected > 0 && (
                                <span className="text-[10px] text-rose-700 font-bold bg-rose-100 px-1.5 py-0.2 rounded">
                                  {u.rejected} ✗
                                </span>
                              )}
                            </div>
                          </div>

                          {/* Progress bar */}
                          <div className="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div
                              style={{ width: `${barWidth}%` }}
                              className="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-300"
                            />
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>

              {/* Kolom Kanan: Wilayah & Tipe Presensi (5 cols) */}
              <div className="lg:col-span-5 space-y-4">
                {/* Box Wilayah */}
                <div className="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
                  <div className="flex items-center gap-2">
                    <MapPin className="w-4 h-4 text-amber-600" />
                    <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                      Distribusi Wilayah Penugasan
                    </h4>
                  </div>

                  <div className="space-y-2">
                    {wilayahStats.map((w) => {
                      const maxW = Math.max(...wilayahStats.map((x) => x.total), 1);
                      const wPct = Math.max((w.total / maxW) * 100, 2);

                      return (
                        <div key={w.name} className="space-y-1">
                          <div className="flex items-center justify-between text-xs">
                            <span className="font-medium text-slate-700">{w.name}</span>
                            <span className="font-mono font-bold text-slate-900">
                              {w.total} <span className="text-[10px] text-slate-400 font-normal">berkas</span>
                            </span>
                          </div>
                          <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div
                              style={{ width: `${wPct}%` }}
                              className="h-full bg-amber-500 rounded-full transition-all duration-300"
                            />
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* Box Tipe Presensi */}
                <div className="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
                  <div className="flex items-center gap-2">
                    <Clock className="w-4 h-4 text-teal-600" />
                    <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                      Pilihan Presensi WFA
                    </h4>
                  </div>

                  <div className="space-y-2.5">
                    {presensiStats.map((p) => (
                      <div key={p.label} className="p-2 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                        <div className="flex items-center justify-between text-xs">
                          <span className="font-semibold text-slate-800">{p.label}</span>
                          <span className="font-bold font-mono text-slate-900">
                            {p.count} <span className="text-[10px] text-slate-500">({p.pct}%)</span>
                          </span>
                        </div>
                        <div className="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                          <div
                            style={{ width: `${p.pct}%` }}
                            className={`h-full ${p.color} rounded-full transition-all duration-300`}
                          />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* FOOTER ACTIONS */}
          <div className="px-5 sm:px-7 py-3.5 bg-white border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
            <div className="text-xs text-slate-500 flex items-center gap-1.5">
              <Sparkles className="w-4 h-4 text-indigo-600" />
              <span>
                Menampilkan data untuk: <strong>{activePeriodLabel}</strong>
              </span>
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setIsExportPdfModalOpen(true)}
                className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 active:from-indigo-800 active:to-violet-800 text-white font-bold text-xs shadow-md shadow-indigo-100 transition-all cursor-pointer"
                title="Buka dialog konfigurasi pengesahan, upload TTD digital PNG, dan unduh PDF Dashboard"
              >
                <FileDown className="w-4 h-4" />
                <span>Ekspor PDF Dashboard (Statistik &amp; Grafik)</span>
              </button>

              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200 transition-colors cursor-pointer"
              >
                Tutup
              </button>
            </div>
          </div>
        </motion.div>
      </div>

      {/* Modal Dialog Konfigurasi Pengesahan & TTD Digital PNG */}
      <WfaDashboardExportPdfModal
        isOpen={isExportPdfModalOpen}
        onClose={() => setIsExportPdfModalOpen(false)}
        submissions={filteredSubmissions}
        periodMode={periodMode}
        periodLabel={activePeriodLabel}
        selectedMonth={selectedMonth}
        selectedYear={selectedYear}
      />
    </AnimatePresence>
  );
};

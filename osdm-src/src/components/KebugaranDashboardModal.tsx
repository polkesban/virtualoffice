import React, { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  Activity,
  Heart,
  Scale,
  Droplets,
  AlertTriangle,
  CheckCircle2,
  X,
  Filter,
  Search,
  Calendar,
  Building,
  Users,
  FileSpreadsheet,
  Phone,
  Sparkles,
  Printer,
  HeartPulse,
  FileDown,
  PieChart,
  BarChart3,
  ShieldCheck,
  ChevronRight
} from 'lucide-react';
import { KebugaranSubmission, KebugaranPeriode, GulaDarahType } from '../types';
import {
  calculateBmi,
  classifyBloodPressure,
  classifyBloodSugar,
  classifyCholesterol,
  formatTanggalIndo,
  formatTanggalPendek
} from '../utils/kebugaranUtils';
import { getPoltekkesUnitKerjaList } from '../data/employeeDatabase';
import { KebugaranDashboardExportPdfModal } from './KebugaranDashboardExportPdfModal';

interface KebugaranDashboardModalProps {
  isOpen: boolean;
  onClose: () => void;
  submissions: KebugaranSubmission[];
}

export type HealthFilterTab =
  | 'all'
  | 'perhatian'
  | 'tensi_abnormal'
  | 'imt_abnormal'
  | 'kolesterol_abnormal'
  | 'gula_abnormal'
  | 'prima';

export const KebugaranDashboardModal: React.FC<KebugaranDashboardModalProps> = ({
  isOpen,
  onClose,
  submissions,
}) => {
  // Current date & year helper
  const currentYear = useMemo(() => new Date().getFullYear(), []);

  // Filter States
  const [selectedTriwulan, setSelectedTriwulan] = useState<string>('Semua Triwulan');
  const [selectedYear, setSelectedYear] = useState<string>('Semua Tahun');
  const [selectedUnit, setSelectedUnit] = useState<string>('Semua Unit');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [activeTab, setActiveTab] = useState<'ringkasan' | 'unit_kerja'>('ringkasan');
  const [healthFilter, setHealthFilter] = useState<HealthFilterTab>('all');
  const [isExportPdfModalOpen, setIsExportPdfModalOpen] = useState(false);

  // Helper render circular SVG Donut Chart
  const renderDonutChart = (
    segments: { label: string; value: number; color: string }[],
    size = 130,
    strokeWidth = 14
  ) => {
    const r = 38;
    const c = 2 * Math.PI * r;
    const totalVal = segments.reduce((sum, s) => sum + s.value, 0);
    if (totalVal === 0) {
      return (
        <svg width={size} height={size} viewBox="0 0 100 100">
          <circle cx="50" cy="50" r={r} fill="transparent" stroke="#f1f5f9" strokeWidth={strokeWidth} />
        </svg>
      );
    }
    let accumPct = 0;
    return (
      <svg width={size} height={size} viewBox="0 0 100 100" className="transform -rotate-90">
        <circle cx="50" cy="50" r={r} fill="transparent" stroke="#f1f5f9" strokeWidth={strokeWidth} />
        {segments.map((seg, idx) => {
          if (seg.value <= 0) return null;
          const pct = seg.value / totalVal;
          const dash = pct * c;
          const offset = -(accumPct * c);
          accumPct += pct;
          return (
            <circle
              key={idx}
              cx="50"
              cy="50"
              r={r}
              fill="transparent"
              stroke={seg.color}
              strokeWidth={strokeWidth}
              strokeDasharray={`${dash} ${c - dash}`}
              strokeDashoffset={offset}
              className="transition-all duration-700 ease-out"
            />
          );
        })}
      </svg>
    );
  };

  // List of Units
  const allUnits = useMemo(() => getPoltekkesUnitKerjaList(), []);

  // Detect Available Years dynamically from dataset
  const availableYears = useMemo(() => {
    const yearSet = new Set<number>();
    yearSet.add(currentYear);
    submissions.forEach((sub) => {
      if (sub.tanggalPeriksa) {
        const y = parseInt(sub.tanggalPeriksa.split('-')[0], 10);
        if (!isNaN(y) && y > 2000 && y < 2100) yearSet.add(y);
      } else if (sub.createdAt) {
        const y = new Date(sub.createdAt).getFullYear();
        if (!isNaN(y) && y > 2000 && y < 2100) yearSet.add(y);
      }
    });
    return Array.from(yearSet).sort((a, b) => b - a);
  }, [submissions, currentYear]);

  // Filtered Submissions based on Triwulan, Year, Unit, and Search
  const filteredSubmissions = useMemo(() => {
    return submissions.filter((sub) => {
      // 1. Filter Triwulan
      if (selectedTriwulan !== 'Semua Triwulan' && sub.periode !== selectedTriwulan) {
        return false;
      }

      // 2. Filter Tahunan
      if (selectedYear !== 'Semua Tahun') {
        const targetY = parseInt(selectedYear, 10);
        let subYear = 0;
        if (sub.tanggalPeriksa) {
          subYear = parseInt(sub.tanggalPeriksa.split('-')[0], 10);
        } else if (sub.createdAt) {
          subYear = new Date(sub.createdAt).getFullYear();
        }
        if (subYear !== targetY) {
          return false;
        }
      }

      // 3. Filter Unit Kerja
      if (selectedUnit !== 'Semua Unit' && sub.unitKerja !== selectedUnit) {
        return false;
      }

      // 4. Search Query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchName = (sub.namaPegawai || '').toLowerCase().includes(q);
        const matchNip = (sub.nip || '').toLowerCase().includes(q);
        const matchUnit = (sub.unitKerja || '').toLowerCase().includes(q);
        if (!matchName && !matchNip && !matchUnit) {
          return false;
        }
      }

      return true;
    });
  }, [submissions, selectedTriwulan, selectedYear, selectedUnit, searchQuery]);

  // Processed Health Analysis for Each Submission
  const evaluatedSubmissions = useMemo(() => {
    return filteredSubmissions.map((sub) => {
      // Tensi
      const bp = classifyBloodPressure(sub.tensiSistolik, sub.tensiDiastolik);
      const isBpNormal = bp.status === 'normal';

      // IMT
      const bmi = calculateBmi(sub.beratBadan, sub.tinggiBadan);
      const isBmiNormal = bmi.category === 'normal';

      // Kolesterol
      const chol = classifyCholesterol(sub.kolesterol);
      const isCholNormal = chol.status === 'normal';

      // Gula Darah
      const sugar = classifyBloodSugar(sub.gulaDarah, sub.tipeGulaDarah);
      const isSugarNormal = sugar.status === 'normal';

      // Overall health status:
      // Prima = 4 parameter normal
      // Perhatian = minimal 1 parameter tidak normal
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
  }, [filteredSubmissions]);

  // Aggregated Statistical Metrics
  const stats = useMemo(() => {
    const total = evaluatedSubmissions.length;

    // Tensi
    const tensiNormal = evaluatedSubmissions.filter((s) => s.isBpNormal).length;
    const tensiAbnormal = total - tensiNormal;
    const tensiPreHipertensi = evaluatedSubmissions.filter((s) => s.bp.status === 'prehipertensi').length;
    const tensiHipertensi1 = evaluatedSubmissions.filter((s) => s.bp.status === 'hipertensi1').length;
    const tensiHipertensi2 = evaluatedSubmissions.filter((s) => s.bp.status === 'hipertensi2').length;

    // IMT
    const imtNormal = evaluatedSubmissions.filter((s) => s.isBmiNormal).length;
    const imtAbnormal = total - imtNormal;
    const imtKurang = evaluatedSubmissions.filter((s) => s.bmi.category === 'kurang').length;
    const imtLebih = evaluatedSubmissions.filter((s) => s.bmi.category === 'lebih').length;
    const imtObesitas = evaluatedSubmissions.filter((s) => s.bmi.category === 'obesitas').length;

    // Kolesterol
    const kolesterolNormal = evaluatedSubmissions.filter((s) => s.isCholNormal).length;
    const kolesterolAbnormal = total - kolesterolNormal;
    const kolesterolBorderline = evaluatedSubmissions.filter((s) => s.chol.status === 'borderline').length;
    const kolesterolTinggi = evaluatedSubmissions.filter((s) => s.chol.status === 'tinggi').length;

    // Gula Darah
    const gulaNormal = evaluatedSubmissions.filter((s) => s.isSugarNormal).length;
    const gulaAbnormal = total - gulaNormal;
    const gulaPrediabetes = evaluatedSubmissions.filter((s) => s.sugar.status === 'prediabetes').length;
    const gulaDiabetes = evaluatedSubmissions.filter((s) => s.sugar.status === 'diabetes').length;

    // Overall Healthy vs Need Attention
    const totalPrima = evaluatedSubmissions.filter((s) => s.isPrima).length;
    const totalPerhatian = total - totalPrima;

    const pct = (val: number) => (total > 0 ? Math.round((val / total) * 100) : 0);

    return {
      total,
      totalPrima,
      totalPrimaPct: pct(totalPrima),
      totalPerhatian,
      totalPerhatianPct: pct(totalPerhatian),

      tensi: {
        normal: tensiNormal,
        normalPct: pct(tensiNormal),
        abnormal: tensiAbnormal,
        abnormalPct: pct(tensiAbnormal),
        preHipertensi: tensiPreHipertensi,
        hipertensi1: tensiHipertensi1,
        hipertensi2: tensiHipertensi2,
      },
      imt: {
        normal: imtNormal,
        normalPct: pct(imtNormal),
        abnormal: imtAbnormal,
        abnormalPct: pct(imtAbnormal),
        kurang: imtKurang,
        lebih: imtLebih,
        obesitas: imtObesitas,
      },
      kolesterol: {
        normal: kolesterolNormal,
        normalPct: pct(kolesterolNormal),
        abnormal: kolesterolAbnormal,
        abnormalPct: pct(kolesterolAbnormal),
        borderline: kolesterolBorderline,
        tinggi: kolesterolTinggi,
      },
      gula: {
        normal: gulaNormal,
        normalPct: pct(gulaNormal),
        abnormal: gulaAbnormal,
        abnormalPct: pct(gulaAbnormal),
        prediabetes: gulaPrediabetes,
        diabetes: gulaDiabetes,
      },
    };
  }, [evaluatedSubmissions]);

  // Filtered List for Table Drilldown
  const drilldownList = useMemo(() => {
    switch (healthFilter) {
      case 'perhatian':
        return evaluatedSubmissions.filter((s) => !s.isPrima);
      case 'tensi_abnormal':
        return evaluatedSubmissions.filter((s) => !s.isBpNormal);
      case 'imt_abnormal':
        return evaluatedSubmissions.filter((s) => !s.isBmiNormal);
      case 'kolesterol_abnormal':
        return evaluatedSubmissions.filter((s) => !s.isCholNormal);
      case 'gula_abnormal':
        return evaluatedSubmissions.filter((s) => !s.isSugarNormal);
      case 'prima':
        return evaluatedSubmissions.filter((s) => s.isPrima);
      case 'all':
      default:
        return evaluatedSubmissions;
    }
  }, [evaluatedSubmissions, healthFilter]);

  // Unit Kerja Distribution Statistics
  const unitDistribution = useMemo(() => {
    const map = new Map<
      string,
      {
        unit: string;
        total: number;
        tensiNormal: number;
        imtNormal: number;
        kolNormal: number;
        gulaNormal: number;
        prima: number;
        perhatian: number;
      }
    >();

    evaluatedSubmissions.forEach((s) => {
      const u = s.unitKerja && s.unitKerja.trim() ? s.unitKerja.trim() : 'Lainnya';
      if (!map.has(u)) {
        map.set(u, {
          unit: u,
          total: 0,
          tensiNormal: 0,
          imtNormal: 0,
          kolNormal: 0,
          gulaNormal: 0,
          prima: 0,
          perhatian: 0,
        });
      }
      const item = map.get(u)!;
      item.total += 1;
      if (s.isBpNormal) item.tensiNormal += 1;
      if (s.isBmiNormal) item.imtNormal += 1;
      if (s.isCholNormal) item.kolNormal += 1;
      if (s.isSugarNormal) item.gulaNormal += 1;
      if (s.isPrima) item.prima += 1;
      else item.perhatian += 1;
    });

    return Array.from(map.values()).sort((a, b) => b.total - a.total);
  }, [evaluatedSubmissions]);

  // Export Filtered Dashboard Data to CSV
  const handleExportDashboardCSV = () => {
    const nowStr = new Date().toISOString().slice(0, 10);
    const nowTime = new Date().toLocaleString('id-ID');

    const headers = [
      'No',
      'NIP Pegawai',
      'Nama Lengkap Pegawai',
      'Unit Kerja / Jurusan',
      'Periode',
      'Tanggal Periksa',
      'Tensi Sistolik (mmHg)',
      'Tensi Diastolik (mmHg)',
      'Status Tensi',
      'Klasifikasi Tensi',
      'Berat Badan (kg)',
      'Tinggi Badan (cm)',
      'IMT',
      'Status IMT',
      'Kategori IMT',
      'Kolesterol (mg/dL)',
      'Status Kolesterol',
      'Klasifikasi Kolesterol',
      'Tipe Gula Darah',
      'Gula Darah (mg/dL)',
      'Status Gula Darah',
      'Klasifikasi Gula Darah',
      'Status Keseluruhan (Prima / Perlu Perhatian)',
      'Jumlah Parameter Tidak Normal',
      'Nomor WhatsApp',
      'Fasyankes'
    ];

    const escapeCSV = (val: string | number | null | undefined): string => {
      if (val === null || val === undefined) return '""';
      const str = String(val).replace(/"/g, '""');
      return `"${str}"`;
    };

    const rows = evaluatedSubmissions.map((s, idx) => {
      return [
        idx + 1,
        escapeCSV(`'${s.nip}`),
        escapeCSV(s.namaPegawai),
        escapeCSV(s.unitKerja),
        escapeCSV(s.periode),
        escapeCSV(s.tanggalPeriksa || '-'),
        s.tensiSistolik,
        s.tensiDiastolik,
        escapeCSV(s.isBpNormal ? 'NORMAL' : 'TIDAK NORMAL'),
        escapeCSV(s.bp.label),
        s.beratBadan,
        s.tinggiBadan,
        s.bmi.bmi,
        escapeCSV(s.isBmiNormal ? 'NORMAL' : 'TIDAK NORMAL'),
        escapeCSV(s.bmi.label),
        s.kolesterol,
        escapeCSV(s.isCholNormal ? 'NORMAL' : 'TIDAK NORMAL'),
        escapeCSV(s.chol.label),
        escapeCSV(s.tipeGulaDarah),
        s.gulaDarah,
        escapeCSV(s.isSugarNormal ? 'NORMAL' : 'TIDAK NORMAL'),
        escapeCSV(s.sugar.label),
        escapeCSV(s.isPrima ? 'SEHAT PRIMA' : 'PERLU PERHATIAN'),
        s.abnormalCount,
        escapeCSV(s.nomorWa ? `'${s.nomorWa}` : '-'),
        escapeCSV(s.fasyankes)
      ].join(',');
    });

    let csvContent = '\uFEFF';
    csvContent += `DASHBOARD ANALISIS & STATISTIK KESEHATAN KEBUGARAN PEGAWAI\n`;
    csvContent += `Instansi,"Politeknik Kesehatan Kemenkes Bandung"\n`;
    csvContent += `Filter Periode Triwulan,"${selectedTriwulan}"\n`;
    csvContent += `Filter Tahun,"${selectedYear}"\n`;
    csvContent += `Filter Unit Kerja,"${selectedUnit}"\n`;
    csvContent += `Waktu Tarikan Data,"${nowTime}"\n`;
    csvContent += `Total Data Pegawai Terdata,${evaluatedSubmissions.length}\n`;
    csvContent += `Pegawai Sehat Prima,${stats.totalPrima} (${stats.totalPrimaPct}%)\n`;
    csvContent += `Pegawai Perlu Perhatian,${stats.totalPerhatian} (${stats.totalPerhatianPct}%)\n\n`;
    csvContent += headers.join(',') + '\r\n';
    csvContent += rows.join('\r\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute(
      'download',
      `dashboard_kebugaran_${selectedTriwulan.replace(/\s+/g, '_')}_${selectedYear.replace(/\s+/g, '_')}_${nowStr}.csv`
    );
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  // WhatsApp Counseling / Reminder trigger
  const handleOpenWhatsAppCounseling = (sub: typeof evaluatedSubmissions[0]) => {
    let phone = (sub.nomorWa || '').replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) phone = '62' + phone.substring(1);
    if (!phone) {
      alert(`Nomor WhatsApp untuk ${sub.namaPegawai} belum tersedia di data.`);
      return;
    }

    const catatanKesehatan: string[] = [];
    if (!sub.isBpNormal) catatanKesehatan.push(`- Tensi Darah: ${sub.tensiSistolik}/${sub.tensiDiastolik} mmHg (${sub.bp.label})`);
    if (!sub.isBmiNormal) catatanKesehatan.push(`- IMT / Berat: ${sub.bmi.bmi} (${sub.bmi.label})`);
    if (!sub.isCholNormal) catatanKesehatan.push(`- Kolesterol: ${sub.kolesterol} mg/dL (${sub.chol.label})`);
    if (!sub.isSugarNormal) catatanKesehatan.push(`- Gula Darah (${sub.tipeGulaDarah}): ${sub.gulaDarah} mg/dL (${sub.sugar.label})`);

    const msg = encodeURIComponent(
      `Yth. Bpk/Ibu ${sub.namaPegawai},\n\n` +
      `Salam sehat dari Tim Kerja OSDM & Layanan Kesehatan Poltekkes Kemenkes Bandung.\n\n` +
      `Berdasarkan rekap data pemeriksaan kebugaran berkala periode *${sub.periode}*, tercatat hasil evaluasi kesehatan:\n` +
      (catatanKesehatan.length > 0 ? catatanKesehatan.join('\n') + '\n\n' : '- Seluruh parameter terpantau dalam kondisi optimal & prima.\n\n') +
      `Mohon tetap menjaga pola makan seimbang, istirahat cukup, dan aktivitas fisik rutin. Apabila membutuhkan konsultasi lanjutan, silakan menghubungi Poliklinik/Klinik Pratama Poltekkes Kemenkes Bandung.\n\n` +
      `Terima kasih atas perhatian dan kerja samanya.\nSalam Sehat,\nTim Kerja OSDM Poltekkes Kemenkes Bandung`
    );

    window.open(`https://wa.me/${phone}?text=${msg}`, '_blank');
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 overflow-y-auto bg-slate-900/60 backdrop-blur-xs">
        <motion.div
          initial={{ opacity: 0, scale: 0.96, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.96, y: 15 }}
          transition={{ duration: 0.2 }}
          className="relative w-full max-w-7xl max-h-[92vh] flex flex-col bg-slate-50 border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-auto"
        >
          {/* Top Modal Header */}
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 bg-white border-b border-slate-200 shrink-0">
            <div className="flex items-center gap-3">
              <div className="w-11 h-11 rounded-2xl bg-gradient-to-tr from-teal-600 via-emerald-600 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-teal-500/20">
                <HeartPulse className="w-6 h-6 animate-pulse" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <h2 className="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                    Dashboard Analisis & Rekapitulasi Kebugaran Pegawai
                  </h2>
                  <span className="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                    Standar Kemenkes RI
                  </span>
                </div>
                <p className="text-xs text-slate-500 mt-0.5">
                  Rekapitulasi komprehensif tekanan darah, IMT, kolesterol, dan gula darah dengan filter triwulan & tahunan.
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2 self-end sm:self-center">
              {/* Tombol Ekspor PDF Eksekutif Resmi */}
              <button
                onClick={() => setIsExportPdfModalOpen(true)}
                className="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-xs"
                title="Ekspor Laporan Eksekutif PDF Resmi Kemenkes RI (Landscape A4 dengan TTD Digital)"
              >
                <FileDown className="w-4 h-4" />
                <span>Ekspor PDF</span>
              </button>

              <button
                onClick={handleExportDashboardCSV}
                className="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-xs"
                title="Ekspor Seluruh Rekap Data Dashboard ke Format CSV / Excel"
              >
                <FileSpreadsheet className="w-4 h-4" />
                <span>Ekspor Excel</span>
              </button>

              <button
                onClick={onClose}
                className="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                title="Tutup Dashboard"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
          </div>

          {/* Filter Bar (Triwulan, Tahunan, Unit Kerja, Pencarian) */}
          <div className="bg-white border-b border-slate-200 px-6 py-3.5 shadow-2xs shrink-0">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-center">
              {/* Filter Triwulan */}
              <div className="flex items-center gap-2">
                <Calendar className="w-4 h-4 text-teal-600 shrink-0" />
                <div className="w-full">
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                    Periode Triwulan
                  </label>
                  <select
                    value={selectedTriwulan}
                    onChange={(e) => setSelectedTriwulan(e.target.value)}
                    className="w-full text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  >
                    <option value="Semua Triwulan">Semua Triwulan</option>
                    <option value="Triwulan I">Triwulan I (Jan - Mar)</option>
                    <option value="Triwulan II">Triwulan II (Apr - Jun)</option>
                    <option value="Triwulan III">Triwulan III (Jul - Sep)</option>
                    <option value="Triwulan IV">Triwulan IV (Okt - Des)</option>
                  </select>
                </div>
              </div>

              {/* Filter Tahunan */}
              <div className="flex items-center gap-2">
                <Calendar className="w-4 h-4 text-cyan-600 shrink-0" />
                <div className="w-full">
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                    Filter Tahun
                  </label>
                  <select
                    value={selectedYear}
                    onChange={(e) => setSelectedYear(e.target.value)}
                    className="w-full text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                  >
                    <option value="Semua Tahun">Semua Tahun</option>
                    {availableYears.map((yr) => (
                      <option key={yr} value={yr.toString()}>
                        Tahun {yr}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Filter Unit Kerja */}
              <div className="flex items-center gap-2">
                <Building className="w-4 h-4 text-indigo-600 shrink-0" />
                <div className="w-full">
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                    Unit Kerja / Jurusan
                  </label>
                  <select
                    value={selectedUnit}
                    onChange={(e) => setSelectedUnit(e.target.value)}
                    className="w-full text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 truncate"
                  >
                    <option value="Semua Unit">Semua Unit Kerja</option>
                    {allUnits.map((u) => (
                      <option key={u} value={u}>
                        {u}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Search Box */}
              <div className="flex items-center gap-2">
                <Search className="w-4 h-4 text-slate-400 shrink-0" />
                <div className="w-full">
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                    Pencarian Pegawai
                  </label>
                  <input
                    type="text"
                    placeholder="Nama / NIP pegawai..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Modal Main Scrollable Content */}
          <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
            {/* Top Overview KPI Summary Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              {/* Card 1: Total Data Pegawai */}
              <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:shadow-sm transition-all">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-500">TOTAL PEMERIKSAAN</span>
                  <div className="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <Users className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline gap-2">
                  <span className="text-2xl sm:text-3xl font-black text-slate-900">{stats.total}</span>
                  <span className="text-xs font-medium text-slate-500">pegawai terdata</span>
                </div>
                <div className="mt-2 text-[11px] text-slate-500 flex items-center gap-1">
                  <span>Filter:</span>
                  <span className="font-semibold text-slate-700">{selectedTriwulan}</span>
                  <span>•</span>
                  <span className="font-semibold text-slate-700">{selectedYear}</span>
                </div>
              </div>

              {/* Card 2: Sehat Prima (4 Parameter Normal) */}
              <div className="bg-white rounded-2xl p-4 border border-emerald-200 bg-gradient-to-br from-white to-emerald-50/40 shadow-2xs hover:shadow-sm transition-all">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-emerald-800">SEHAT PRIMA (4 NORMAL)</span>
                  <div className="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <ShieldCheck className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline gap-2">
                  <span className="text-2xl sm:text-3xl font-black text-emerald-700">{stats.totalPrima}</span>
                  <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                    {stats.totalPrimaPct}%
                  </span>
                </div>
                <p className="mt-2 text-[11px] text-emerald-700">
                  Tensi, IMT, kolesterol, dan gula darah normal
                </p>
              </div>

              {/* Card 3: Perlu Pemantauan / Perhatian Khusus */}
              <div className="bg-white rounded-2xl p-4 border border-amber-200 bg-gradient-to-br from-white to-amber-50/40 shadow-2xs hover:shadow-sm transition-all">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-amber-800">PERLU PEMANTAUAN</span>
                  <div className="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <AlertTriangle className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline gap-2">
                  <span className="text-2xl sm:text-3xl font-black text-amber-700">{stats.totalPerhatian}</span>
                  <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                    {stats.totalPerhatianPct}%
                  </span>
                </div>
                <p className="mt-2 text-[11px] text-amber-700">
                  Minimal 1 parameter berada di luar batas normal
                </p>
              </div>

              {/* Card 4: Indeks Kebugaran Institusi */}
              <div className="bg-white rounded-2xl p-4 border border-teal-200 bg-gradient-to-br from-white to-teal-50/40 shadow-2xs hover:shadow-sm transition-all">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-teal-800">INDEKS KESEHATAN AGREGAT</span>
                  <div className="w-8 h-8 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center">
                    <Activity className="w-4 h-4" />
                  </div>
                </div>
                <div className="mt-2 flex items-baseline gap-2">
                  <span className="text-2xl sm:text-3xl font-black text-teal-700">
                    {stats.total > 0
                      ? Math.round(
                          (stats.tensi.normalPct +
                            stats.imt.normalPct +
                            stats.kolesterol.normalPct +
                            stats.gula.normalPct) /
                            4
                        )
                      : 0}
                    %
                  </span>
                  <span className="text-xs font-bold text-teal-800">Rerata 4 Parameter</span>
                </div>
                <p className="mt-2 text-[11px] text-teal-700">
                  Tingkat kepatuhan rentang normal Kemenkes
                </p>
              </div>
            </div>

            {/* SECTION INFOGRAFIS & GRAFIK VISUAL (PIE & BAR) */}
            <div className="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                    <PieChart className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="text-sm font-black text-slate-900 tracking-tight">
                      Infografis Visual &amp; Komparasi Statistik Kebugaran
                    </h3>
                    <p className="text-[11px] text-slate-500">
                      Visualisasi grafik pie/donut status kesehatan institusi dan grafik bar komparasi 4 parameter
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                    Sampel: {stats.total} Pegawai
                  </span>
                </div>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
                {/* 1. GRAFIK DONUT / PIE: STATUS KESEHATAN INSTITUSI (5 Kolom) */}
                <div className="lg:col-span-5 p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-emerald-50/20 border border-slate-200/80 flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <PieChart className="w-3.5 h-3.5 text-teal-600" />
                        <span>Grafik Pie: Status Kesehatan Institusi</span>
                      </span>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-teal-100 text-teal-800">
                        {stats.totalPrimaPct}% Prima
                      </span>
                    </div>

                    {/* Circular Donut + Center Stats */}
                    <div className="flex items-center justify-center gap-5 py-2">
                      <div className="relative w-36 h-36 flex items-center justify-center shrink-0">
                        {renderDonutChart([
                          { label: 'Sehat Prima', value: stats.totalPrima, color: '#10b981' },
                          { label: 'Perlu Pemantauan', value: stats.totalPerhatian, color: '#f59e0b' }
                        ], 140, 14)}
                        <div className="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                          <span className="text-2xl font-black text-slate-900 leading-none">
                            {stats.totalPrimaPct}%
                          </span>
                          <span className="text-[10px] font-bold text-emerald-700 mt-0.5 uppercase tracking-wide">
                            Prima
                          </span>
                          <span className="text-[9px] text-slate-400">
                            {stats.totalPrima}/{stats.total} Org
                          </span>
                        </div>
                      </div>

                      {/* Legends & Detail */}
                      <div className="space-y-2.5 flex-1 text-xs">
                        <div className="p-2.5 rounded-xl bg-white border border-emerald-200/80 shadow-2xs">
                          <div className="flex items-center justify-between">
                            <span className="flex items-center gap-2 font-bold text-emerald-900 text-xs">
                              <span className="w-2.5 h-2.5 rounded-full bg-emerald-500" />
                              Sehat Prima
                            </span>
                            <span className="text-xs font-black text-emerald-700 font-mono">
                              {stats.totalPrima} ({stats.totalPrimaPct}%)
                            </span>
                          </div>
                          <p className="text-[10px] text-slate-500 mt-1">
                            Seluruh 4 indikator normal standar Kemenkes RI
                          </p>
                        </div>

                        <div className="p-2.5 rounded-xl bg-white border border-amber-200/80 shadow-2xs">
                          <div className="flex items-center justify-between">
                            <span className="flex items-center gap-2 font-bold text-amber-900 text-xs">
                              <span className="w-2.5 h-2.5 rounded-full bg-amber-500" />
                              Perlu Pantauan
                            </span>
                            <span className="text-xs font-black text-amber-700 font-mono">
                              {stats.totalPerhatian} ({stats.totalPerhatianPct}%)
                            </span>
                          </div>
                          <p className="text-[10px] text-slate-500 mt-1">
                            Terdapat 1 atau lebih indikator di luar batas normal
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <p className="text-[10px] text-slate-400 italic text-center pt-2 border-t border-slate-200/60 mt-2">
                    * Skrining berkala membantu deteksi dini penyakit tidak menular (PTM) pada civitas akademika.
                  </p>
                </div>

                {/* 2. GRAFIK BAR: KOMPARASI 4 PARAMETER NORMAL VS TIDAK NORMAL (7 Kolom) */}
                <div className="lg:col-span-7 p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-blue-50/20 border border-slate-200/80 flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <BarChart3 className="w-3.5 h-3.5 text-blue-600" />
                        <span>Grafik Bar: Komparasi 4 Parameter Utama (Normal vs Abnormal)</span>
                      </span>
                      <div className="flex items-center gap-2 text-[10px] font-bold">
                        <span className="flex items-center gap-1 text-emerald-700">
                          <span className="w-2 h-2 rounded-full bg-emerald-500" /> Normal
                        </span>
                        <span className="flex items-center gap-1 text-rose-700">
                          <span className="w-2 h-2 rounded-full bg-rose-500" /> Abnormal
                        </span>
                      </div>
                    </div>

                    {/* 4 Bars Comparison */}
                    <div className="space-y-3 pt-1">
                      {/* Bar 1: Tensi */}
                      <div>
                        <div className="flex justify-between items-center text-xs mb-1">
                          <span className="font-bold text-slate-800 flex items-center gap-1.5">
                            <Heart className="w-3.5 h-3.5 text-rose-500" />
                            Tekanan Darah (Tensi)
                          </span>
                          <div className="flex items-center gap-3 text-[11px] font-mono">
                            <span className="text-emerald-700 font-bold">Normal: {stats.tensi.normal} ({stats.tensi.normalPct}%)</span>
                            <span className="text-rose-600 font-bold">Abnormal: {stats.tensi.abnormal} ({stats.tensi.abnormalPct}%)</span>
                          </div>
                        </div>
                        <div className="w-full h-4 rounded-full bg-slate-200 overflow-hidden flex shadow-inner">
                          <div
                            style={{ width: `${stats.tensi.normalPct}%` }}
                            className="bg-emerald-500 h-full transition-all duration-500 flex items-center justify-end pr-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.tensi.normalPct}%
                          </div>
                          <div
                            style={{ width: `${stats.tensi.abnormalPct}%` }}
                            className="bg-rose-500 h-full transition-all duration-500 flex items-center justify-start pl-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.tensi.abnormalPct}%
                          </div>
                        </div>
                      </div>

                      {/* Bar 2: IMT */}
                      <div>
                        <div className="flex justify-between items-center text-xs mb-1">
                          <span className="font-bold text-slate-800 flex items-center gap-1.5">
                            <Scale className="w-3.5 h-3.5 text-teal-600" />
                            Indeks Massa Tubuh (IMT)
                          </span>
                          <div className="flex items-center gap-3 text-[11px] font-mono">
                            <span className="text-emerald-700 font-bold">Ideal: {stats.imt.normal} ({stats.imt.normalPct}%)</span>
                            <span className="text-orange-600 font-bold">Abnormal: {stats.imt.abnormal} ({stats.imt.abnormalPct}%)</span>
                          </div>
                        </div>
                        <div className="w-full h-4 rounded-full bg-slate-200 overflow-hidden flex shadow-inner">
                          <div
                            style={{ width: `${stats.imt.normalPct}%` }}
                            className="bg-emerald-500 h-full transition-all duration-500 flex items-center justify-end pr-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.imt.normalPct}%
                          </div>
                          <div
                            style={{ width: `${stats.imt.abnormalPct}%` }}
                            className="bg-orange-500 h-full transition-all duration-500 flex items-center justify-start pl-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.imt.abnormalPct}%
                          </div>
                        </div>
                      </div>

                      {/* Bar 3: Kolesterol */}
                      <div>
                        <div className="flex justify-between items-center text-xs mb-1">
                          <span className="font-bold text-slate-800 flex items-center gap-1.5">
                            <Droplets className="w-3.5 h-3.5 text-indigo-500" />
                            Kolesterol Total (&lt;200 mg/dL)
                          </span>
                          <div className="flex items-center gap-3 text-[11px] font-mono">
                            <span className="text-emerald-700 font-bold">Normal: {stats.kolesterol.normal} ({stats.kolesterol.normalPct}%)</span>
                            <span className="text-rose-600 font-bold">Tinggi: {stats.kolesterol.abnormal} ({stats.kolesterol.abnormalPct}%)</span>
                          </div>
                        </div>
                        <div className="w-full h-4 rounded-full bg-slate-200 overflow-hidden flex shadow-inner">
                          <div
                            style={{ width: `${stats.kolesterol.normalPct}%` }}
                            className="bg-emerald-500 h-full transition-all duration-500 flex items-center justify-end pr-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.kolesterol.normalPct}%
                          </div>
                          <div
                            style={{ width: `${stats.kolesterol.abnormalPct}%` }}
                            className="bg-rose-500 h-full transition-all duration-500 flex items-center justify-start pl-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.kolesterol.abnormalPct}%
                          </div>
                        </div>
                      </div>

                      {/* Bar 4: Gula Darah */}
                      <div>
                        <div className="flex justify-between items-center text-xs mb-1">
                          <span className="font-bold text-slate-800 flex items-center gap-1.5">
                            <Activity className="w-3.5 h-3.5 text-amber-500" />
                            Gula Darah (GDP / GDS)
                          </span>
                          <div className="flex items-center gap-3 text-[11px] font-mono">
                            <span className="text-emerald-700 font-bold">Normal: {stats.gula.normal} ({stats.gula.normalPct}%)</span>
                            <span className="text-amber-600 font-bold">Tinggi: {stats.gula.abnormal} ({stats.gula.abnormalPct}%)</span>
                          </div>
                        </div>
                        <div className="w-full h-4 rounded-full bg-slate-200 overflow-hidden flex shadow-inner">
                          <div
                            style={{ width: `${stats.gula.normalPct}%` }}
                            className="bg-emerald-500 h-full transition-all duration-500 flex items-center justify-end pr-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.gula.normalPct}%
                          </div>
                          <div
                            style={{ width: `${stats.gula.abnormalPct}%` }}
                            className="bg-amber-500 h-full transition-all duration-500 flex items-center justify-start pl-1.5 text-[9px] font-bold text-white"
                          >
                            {stats.gula.abnormalPct}%
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Rerata Kepatuhan 4 Indikator:</span>
                    <span className="font-bold font-mono text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">
                      {Math.round((stats.tensi.normalPct + stats.imt.normalPct + stats.kolesterol.normalPct + stats.gula.normalPct) / 4)}% Kepatuhan Agregat
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {/* 4 MAIN HEALTH PARAMETERS REKAP (NORMAL VS TIDAK NORMAL) */}
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-2">
                  <div className="w-2 h-5 rounded-full bg-teal-600" />
                  <h3 className="text-sm font-black text-slate-900 tracking-tight uppercase">
                    Rekapitulasi 4 Parameter Kesehatan (Normal vs Tidak Normal)
                  </h3>
                </div>
                <span className="text-xs text-slate-500">
                  Berdasarkan standar skrining berkala Kemenkes RI
                </span>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {/* 1. TEKANAN DARAH (TENSI) */}
                <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-slate-300 transition-all flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                          <Heart className="w-4 h-4" />
                        </div>
                        <div>
                          <h4 className="text-xs font-black text-slate-900">Tekanan Darah (Tensi)</h4>
                          <span className="text-[10px] text-slate-400">Sistolik & Diastolik</span>
                        </div>
                      </div>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        {stats.total} Org
                      </span>
                    </div>

                    {/* Progress Bar Normal vs Tidak Normal */}
                    <div className="mt-3">
                      <div className="flex justify-between text-xs font-bold mb-1">
                        <span className="text-emerald-700 flex items-center gap-1">
                          <CheckCircle2 className="w-3.5 h-3.5" />
                          Normal: {stats.tensi.normal} ({stats.tensi.normalPct}%)
                        </span>
                        <span className="text-rose-700 flex items-center gap-1">
                          <AlertTriangle className="w-3.5 h-3.5" />
                          Abnormal: {stats.tensi.abnormal} ({stats.tensi.abnormalPct}%)
                        </span>
                      </div>
                      <div className="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex">
                        <div
                          style={{ width: `${stats.tensi.normalPct}%` }}
                          className="bg-emerald-500 h-full transition-all duration-500"
                          title={`Normal: ${stats.tensi.normal} (${stats.tensi.normalPct}%)`}
                        />
                        <div
                          style={{ width: `${stats.tensi.abnormalPct}%` }}
                          className="bg-rose-500 h-full transition-all duration-500"
                          title={`Tidak Normal: ${stats.tensi.abnormal} (${stats.tensi.abnormalPct}%)`}
                        />
                      </div>
                    </div>

                    {/* Subcategories Breakdown */}
                    <div className="mt-3.5 pt-3 border-t border-slate-100 space-y-1.5 text-[11px]">
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-emerald-500" />
                          Optimal (&lt;120 / &lt;80):
                        </span>
                        <span className="font-bold text-slate-900">{stats.tensi.normal}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-amber-500" />
                          Pre-Hipertensi:
                        </span>
                        <span className="font-bold text-slate-900">{stats.tensi.preHipertensi}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-orange-500" />
                          Hipertensi Derajat 1:
                        </span>
                        <span className="font-bold text-slate-900">{stats.tensi.hipertensi1}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-rose-600" />
                          Hipertensi Derajat 2:
                        </span>
                        <span className="font-bold text-slate-900">{stats.tensi.hipertensi2}</span>
                      </div>
                    </div>
                  </div>

                  <button
                    onClick={() => setHealthFilter('tensi_abnormal')}
                    className="mt-3 w-full py-1.5 text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors flex items-center justify-center gap-1"
                  >
                    <span>Lihat Daftar Tensi Abnormal</span>
                    <ChevronRight className="w-3 h-3" />
                  </button>
                </div>

                {/* 2. IMT (INDEKS MASSA TUBUH) */}
                <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-slate-300 transition-all flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                          <Scale className="w-4 h-4" />
                        </div>
                        <div>
                          <h4 className="text-xs font-black text-slate-900">Indeks Massa Tubuh (IMT)</h4>
                          <span className="text-[10px] text-slate-400">Berat & Tinggi Badan</span>
                        </div>
                      </div>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        {stats.total} Org
                      </span>
                    </div>

                    {/* Progress Bar Normal vs Tidak Normal */}
                    <div className="mt-3">
                      <div className="flex justify-between text-xs font-bold mb-1">
                        <span className="text-emerald-700 flex items-center gap-1">
                          <CheckCircle2 className="w-3.5 h-3.5" />
                          Ideal: {stats.imt.normal} ({stats.imt.normalPct}%)
                        </span>
                        <span className="text-orange-700 flex items-center gap-1">
                          <AlertTriangle className="w-3.5 h-3.5" />
                          Abnormal: {stats.imt.abnormal} ({stats.imt.abnormalPct}%)
                        </span>
                      </div>
                      <div className="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex">
                        <div
                          style={{ width: `${stats.imt.normalPct}%` }}
                          className="bg-emerald-500 h-full transition-all duration-500"
                          title={`Ideal: ${stats.imt.normal} (${stats.imt.normalPct}%)`}
                        />
                        <div
                          style={{ width: `${stats.imt.abnormalPct}%` }}
                          className="bg-orange-500 h-full transition-all duration-500"
                          title={`Tidak Normal: ${stats.imt.abnormal} (${stats.imt.abnormalPct}%)`}
                        />
                      </div>
                    </div>

                    {/* Subcategories Breakdown */}
                    <div className="mt-3.5 pt-3 border-t border-slate-100 space-y-1.5 text-[11px]">
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-emerald-500" />
                          Ideal (18.5 - 22.9):
                        </span>
                        <span className="font-bold text-slate-900">{stats.imt.normal}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-amber-500" />
                          Berat Kurang (&lt;18.5):
                        </span>
                        <span className="font-bold text-slate-900">{stats.imt.kurang}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-orange-500" />
                          Kelebihan BB (23.0 - 24.9):
                        </span>
                        <span className="font-bold text-slate-900">{stats.imt.lebih}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-rose-600" />
                          Obesitas (&ge;25.0):
                        </span>
                        <span className="font-bold text-slate-900">{stats.imt.obesitas}</span>
                      </div>
                    </div>
                  </div>

                  <button
                    onClick={() => setHealthFilter('imt_abnormal')}
                    className="mt-3 w-full py-1.5 text-[11px] font-bold text-orange-700 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors flex items-center justify-center gap-1"
                  >
                    <span>Lihat Daftar IMT Abnormal</span>
                    <ChevronRight className="w-3 h-3" />
                  </button>
                </div>

                {/* 3. KOLESTEROL TOTAL */}
                <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-slate-300 transition-all flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                          <Droplets className="w-4 h-4" />
                        </div>
                        <div>
                          <h4 className="text-xs font-black text-slate-900">Kolesterol Total</h4>
                          <span className="text-[10px] text-slate-400">Target &lt;200 mg/dL</span>
                        </div>
                      </div>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        {stats.total} Org
                      </span>
                    </div>

                    {/* Progress Bar Normal vs Tidak Normal */}
                    <div className="mt-3">
                      <div className="flex justify-between text-xs font-bold mb-1">
                        <span className="text-emerald-700 flex items-center gap-1">
                          <CheckCircle2 className="w-3.5 h-3.5" />
                          Normal: {stats.kolesterol.normal} ({stats.kolesterol.normalPct}%)
                        </span>
                        <span className="text-rose-700 flex items-center gap-1">
                          <AlertTriangle className="w-3.5 h-3.5" />
                          Tinggi: {stats.kolesterol.abnormal} ({stats.kolesterol.abnormalPct}%)
                        </span>
                      </div>
                      <div className="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex">
                        <div
                          style={{ width: `${stats.kolesterol.normalPct}%` }}
                          className="bg-emerald-500 h-full transition-all duration-500"
                          title={`Normal: ${stats.kolesterol.normal} (${stats.kolesterol.normalPct}%)`}
                        />
                        <div
                          style={{ width: `${stats.kolesterol.abnormalPct}%` }}
                          className="bg-rose-500 h-full transition-all duration-500"
                          title={`Tinggi: ${stats.kolesterol.abnormal} (${stats.kolesterol.abnormalPct}%)`}
                        />
                      </div>
                    </div>

                    {/* Subcategories Breakdown */}
                    <div className="mt-3.5 pt-3 border-t border-slate-100 space-y-1.5 text-[11px]">
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-emerald-500" />
                          Normal (&lt;200 mg/dL):
                        </span>
                        <span className="font-bold text-slate-900">{stats.kolesterol.normal}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-amber-500" />
                          Batas Tinggi (200-239):
                        </span>
                        <span className="font-bold text-slate-900">{stats.kolesterol.borderline}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-rose-600" />
                          Tinggi (&ge;240 mg/dL):
                        </span>
                        <span className="font-bold text-slate-900">{stats.kolesterol.tinggi}</span>
                      </div>
                    </div>
                  </div>

                  <button
                    onClick={() => setHealthFilter('kolesterol_abnormal')}
                    className="mt-3 w-full py-1.5 text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors flex items-center justify-center gap-1"
                  >
                    <span>Lihat Kolesterol Tinggi</span>
                    <ChevronRight className="w-3 h-3" />
                  </button>
                </div>

                {/* 4. GULA DARAH */}
                <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-slate-300 transition-all flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                          <Activity className="w-4 h-4" />
                        </div>
                        <div>
                          <h4 className="text-xs font-black text-slate-900">Gula Darah (GDP / GDS)</h4>
                          <span className="text-[10px] text-slate-400">Puasa / Sewaktu</span>
                        </div>
                      </div>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        {stats.total} Org
                      </span>
                    </div>

                    {/* Progress Bar Normal vs Tidak Normal */}
                    <div className="mt-3">
                      <div className="flex justify-between text-xs font-bold mb-1">
                        <span className="text-emerald-700 flex items-center gap-1">
                          <CheckCircle2 className="w-3.5 h-3.5" />
                          Normal: {stats.gula.normal} ({stats.gula.normalPct}%)
                        </span>
                        <span className="text-rose-700 flex items-center gap-1">
                          <AlertTriangle className="w-3.5 h-3.5" />
                          Abnormal: {stats.gula.abnormal} ({stats.gula.abnormalPct}%)
                        </span>
                      </div>
                      <div className="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex">
                        <div
                          style={{ width: `${stats.gula.normalPct}%` }}
                          className="bg-emerald-500 h-full transition-all duration-500"
                          title={`Normal: ${stats.gula.normal} (${stats.gula.normalPct}%)`}
                        />
                        <div
                          style={{ width: `${stats.gula.abnormalPct}%` }}
                          className="bg-rose-500 h-full transition-all duration-500"
                          title={`Tidak Normal: ${stats.gula.abnormal} (${stats.gula.abnormalPct}%)`}
                        />
                      </div>
                    </div>

                    {/* Subcategories Breakdown */}
                    <div className="mt-3.5 pt-3 border-t border-slate-100 space-y-1.5 text-[11px]">
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-emerald-500" />
                          Normal (GDP&lt;100 / GDS&lt;140):
                        </span>
                        <span className="font-bold text-slate-900">{stats.gula.normal}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-amber-500" />
                          Pre-Diabetes:
                        </span>
                        <span className="font-bold text-slate-900">{stats.gula.prediabetes}</span>
                      </div>
                      <div className="flex justify-between items-center text-slate-600">
                        <span className="flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-rose-600" />
                          Diabetes:
                        </span>
                        <span className="font-bold text-slate-900">{stats.gula.diabetes}</span>
                      </div>
                    </div>
                  </div>

                  <button
                    onClick={() => setHealthFilter('gula_abnormal')}
                    className="mt-3 w-full py-1.5 text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors flex items-center justify-center gap-1"
                  >
                    <span>Lihat Gula Darah Tinggi</span>
                    <ChevronRight className="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>

            {/* TAB SELECTOR: RINCIAN PEGAWAI VS DISTRIBUSI UNIT KERJA */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setActiveTab('ringkasan')}
                    className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all ${
                      activeTab === 'ringkasan'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                    }`}
                  >
                    Daftar & Drilldown Pegawai ({drilldownList.length})
                  </button>
                  <button
                    onClick={() => setActiveTab('unit_kerja')}
                    className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all ${
                      activeTab === 'unit_kerja'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                    }`}
                  >
                    Distribusi per Jurusan / Unit ({unitDistribution.length})
                  </button>
                </div>

                {activeTab === 'ringkasan' && (
                  <div className="flex flex-wrap items-center gap-1.5">
                    <button
                      onClick={() => setHealthFilter('all')}
                      className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                        healthFilter === 'all'
                          ? 'bg-teal-600 text-white shadow-2xs'
                          : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                      }`}
                    >
                      Semua ({evaluatedSubmissions.length})
                    </button>
                    <button
                      onClick={() => setHealthFilter('perhatian')}
                      className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                        healthFilter === 'perhatian'
                          ? 'bg-amber-600 text-white shadow-2xs'
                          : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200'
                      }`}
                    >
                      ⚠️ Perlu Pantauan ({stats.totalPerhatian})
                    </button>
                    <button
                      onClick={() => setHealthFilter('tensi_abnormal')}
                      className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                        healthFilter === 'tensi_abnormal'
                          ? 'bg-rose-600 text-white shadow-2xs'
                          : 'bg-white text-rose-700 hover:bg-rose-50 border border-rose-200'
                      }`}
                    >
                      Tensi ({stats.tensi.abnormal})
                    </button>
                    <button
                      onClick={() => setHealthFilter('imt_abnormal')}
                      className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                        healthFilter === 'imt_abnormal'
                          ? 'bg-orange-600 text-white shadow-2xs'
                          : 'bg-white text-orange-700 hover:bg-orange-50 border border-orange-200'
                      }`}
                    >
                      IMT ({stats.imt.abnormal})
                    </button>
                    <button
                      onClick={() => setHealthFilter('kolesterol_abnormal')}
                      className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                        healthFilter === 'kolesterol_abnormal'
                          ? 'bg-indigo-600 text-white shadow-2xs'
                          : 'bg-white text-indigo-700 hover:bg-indigo-50 border border-indigo-200'
                      }`}
                    >
                      Kolesterol ({stats.kolesterol.abnormal})
                    </button>
                    <button
                      onClick={() => setHealthFilter('gula_abnormal')}
                      className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                        healthFilter === 'gula_abnormal'
                          ? 'bg-amber-600 text-white shadow-2xs'
                          : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200'
                      }`}
                    >
                      Gula Darah ({stats.gula.abnormal})
                    </button>
                    <button
                      onClick={() => setHealthFilter('prima')}
                      className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all ${
                        healthFilter === 'prima'
                          ? 'bg-emerald-600 text-white shadow-2xs'
                          : 'bg-white text-emerald-700 hover:bg-emerald-50 border border-emerald-200'
                      }`}
                    >
                      ✨ Prima ({stats.totalPrima})
                    </button>
                  </div>
                )}
              </div>

              {/* TAB CONTENT 1: DAFTAR RINCIAN PEGAWAI */}
              {activeTab === 'ringkasan' && (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="bg-slate-100/75 border-b border-slate-200 text-[11px] font-black text-slate-700 uppercase tracking-wider">
                        <th className="py-3 px-3 w-10 text-center">No</th>
                        <th className="py-3 px-4">Nama Pegawai & NIP</th>
                        <th className="py-3 px-3">Unit Kerja</th>
                        <th className="py-3 px-3">Periode & Tgl</th>
                        <th className="py-3 px-3 text-center">Tensi Darah</th>
                        <th className="py-3 px-3 text-center">IMT (Berat/Tinggi)</th>
                        <th className="py-3 px-3 text-center">Kolesterol</th>
                        <th className="py-3 px-3 text-center">Gula Darah</th>
                        <th className="py-3 px-3 text-center">Status</th>
                        <th className="py-3 px-3 text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {drilldownList.length === 0 ? (
                        <tr>
                          <td colSpan={10} className="py-12 text-center text-slate-400">
                            <Users className="w-10 h-10 mx-auto text-slate-300 mb-2" />
                            <p className="font-semibold text-slate-600">Tidak ada data pegawai yang sesuai filter.</p>
                            <p className="text-xs text-slate-400 mt-0.5">Silakan sesuaikan filter triwulan, tahun, atau kata kunci pencarian.</p>
                          </td>
                        </tr>
                      ) : (
                        drilldownList.map((sub, idx) => (
                          <tr
                            key={sub.id}
                            className={`hover:bg-slate-50 transition-colors ${
                              !sub.isPrima ? 'bg-amber-50/20' : ''
                            }`}
                          >
                            <td className="py-2.5 px-3 text-center font-bold text-slate-400">{idx + 1}</td>
                            <td className="py-2.5 px-4">
                              <div className="font-bold text-slate-900 leading-snug">{sub.namaPegawai}</div>
                              <div className="text-[11px] font-mono text-slate-500">{sub.nip}</div>
                            </td>
                            <td className="py-2.5 px-3">
                              <span className="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                {sub.unitKerja || '-'}
                              </span>
                            </td>
                            <td className="py-2.5 px-3 whitespace-nowrap">
                              <div className="font-semibold text-slate-800">{sub.periode}</div>
                              <div className="text-[10px] text-slate-400">{formatTanggalPendek(sub.tanggalPeriksa)}</div>
                            </td>

                            {/* Tensi */}
                            <td className="py-2.5 px-3 text-center whitespace-nowrap">
                              <div className="font-bold text-slate-800">
                                {!sub.isBpNormal && <span className="text-rose-600 font-black mr-0.5">*</span>}
                                {sub.tensiSistolik}/{sub.tensiDiastolik}{' '}
                                <span className="text-[10px] text-slate-400 font-normal">mmHg</span>
                              </div>
                              <span
                                className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border mt-0.5 ${sub.bp.badgeBg}`}
                              >
                                {!sub.isBpNormal && '* '}
                                {sub.bp.label}
                              </span>
                            </td>

                            {/* IMT */}
                            <td className="py-2.5 px-3 text-center whitespace-nowrap">
                              <div className="font-bold text-slate-800">
                                {!sub.isBmiNormal && <span className="text-rose-600 font-black mr-0.5">*</span>}
                                {sub.bmi.bmi}{' '}
                                <span className="text-[10px] text-slate-400 font-normal">
                                  ({sub.beratBadan}kg/{sub.tinggiBadan}cm)
                                </span>
                              </div>
                              <span
                                className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border mt-0.5 ${sub.bmi.badgeBg}`}
                              >
                                {!sub.isBmiNormal && '* '}
                                {sub.bmi.label}
                              </span>
                            </td>

                            {/* Kolesterol */}
                            <td className="py-2.5 px-3 text-center whitespace-nowrap">
                              <div className="font-bold text-slate-800">
                                {!sub.isCholNormal && <span className="text-rose-600 font-black mr-0.5">*</span>}
                                {sub.kolesterol}{' '}
                                <span className="text-[10px] text-slate-400 font-normal">mg/dL</span>
                              </div>
                              <span
                                className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border mt-0.5 ${sub.chol.badgeBg}`}
                              >
                                {!sub.isCholNormal && '* '}
                                {sub.chol.label}
                              </span>
                            </td>

                            {/* Gula Darah */}
                            <td className="py-2.5 px-3 text-center whitespace-nowrap">
                              <div className="font-bold text-slate-800">
                                {!sub.isSugarNormal && <span className="text-rose-600 font-black mr-0.5">*</span>}
                                {sub.gulaDarah}{' '}
                                <span className="text-[10px] text-slate-400 font-normal">
                                  mg/dL ({sub.tipeGulaDarah})
                                </span>
                              </div>
                              <span
                                className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border mt-0.5 ${sub.sugar.badgeBg}`}
                              >
                                {!sub.isSugarNormal && '* '}
                                {sub.sugar.label}
                              </span>
                            </td>

                            {/* Overall Status */}
                            <td className="py-2.5 px-3 text-center whitespace-nowrap">
                              {sub.isPrima ? (
                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black">
                                  <CheckCircle2 className="w-3 h-3" />
                                  PRIMA
                                </span>
                              ) : (
                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-black">
                                  <AlertTriangle className="w-3 h-3" />
                                  {sub.abnormalCount} KONDISI
                                </span>
                              )}
                            </td>

                            {/* Action WA */}
                            <td className="py-2.5 px-3 text-center">
                              {sub.nomorWa ? (
                                <button
                                  onClick={() => handleOpenWhatsAppCounseling(sub)}
                                  className="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors"
                                  title={`Kirim Rekomendasi / Pengingat Kesehatan ke WhatsApp ${sub.namaPegawai}`}
                                >
                                  <Phone className="w-3.5 h-3.5" />
                                </button>
                              ) : (
                                <span className="text-slate-300 text-xs">-</span>
                              )}
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              )}

              {/* TAB CONTENT 2: DISTRIBUSI PER JURUSAN / UNIT KERJA */}
              {activeTab === 'unit_kerja' && (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="bg-slate-100/75 border-b border-slate-200 text-[11px] font-black text-slate-700 uppercase tracking-wider">
                        <th className="py-3 px-3 w-10 text-center">No</th>
                        <th className="py-3 px-4">Jurusan / Unit Kerja</th>
                        <th className="py-3 px-3 text-center">Total Terdata</th>
                        <th className="py-3 px-3 text-center">Tensi Normal</th>
                        <th className="py-3 px-3 text-center">IMT Ideal</th>
                        <th className="py-3 px-3 text-center">Kolesterol Normal</th>
                        <th className="py-3 px-3 text-center">Gula Normal</th>
                        <th className="py-3 px-3 text-center">Sehat Prima</th>
                        <th className="py-3 px-3 text-center">Perlu Pantauan</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {unitDistribution.length === 0 ? (
                        <tr>
                          <td colSpan={9} className="py-12 text-center text-slate-400">
                            Tidak ada data untuk unit kerja yang dipilih.
                          </td>
                        </tr>
                      ) : (
                        unitDistribution.map((item, idx) => {
                          const tensiPct = Math.round((item.tensiNormal / item.total) * 100);
                          const imtPct = Math.round((item.imtNormal / item.total) * 100);
                          const kolPct = Math.round((item.kolNormal / item.total) * 100);
                          const gulaPct = Math.round((item.gulaNormal / item.total) * 100);
                          const primaPct = Math.round((item.prima / item.total) * 100);

                          return (
                            <tr key={item.unit} className="hover:bg-slate-50 transition-colors">
                              <td className="py-3 px-3 text-center font-bold text-slate-400">{idx + 1}</td>
                              <td className="py-3 px-4 font-bold text-slate-900">{item.unit}</td>
                              <td className="py-3 px-3 text-center font-black text-slate-800">
                                {item.total} Org
                              </td>
                              <td className="py-3 px-3 text-center">
                                <span className="font-bold text-emerald-700">{item.tensiNormal}</span>
                                <span className="text-[10px] text-slate-400 block">({tensiPct}%)</span>
                              </td>
                              <td className="py-3 px-3 text-center">
                                <span className="font-bold text-emerald-700">{item.imtNormal}</span>
                                <span className="text-[10px] text-slate-400 block">({imtPct}%)</span>
                              </td>
                              <td className="py-3 px-3 text-center">
                                <span className="font-bold text-emerald-700">{item.kolNormal}</span>
                                <span className="text-[10px] text-slate-400 block">({kolPct}%)</span>
                              </td>
                              <td className="py-3 px-3 text-center">
                                <span className="font-bold text-emerald-700">{item.gulaNormal}</span>
                                <span className="text-[10px] text-slate-400 block">({gulaPct}%)</span>
                              </td>
                              <td className="py-3 px-3 text-center">
                                <span className="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                  {item.prima} ({primaPct}%)
                                </span>
                              </td>
                              <td className="py-3 px-3 text-center">
                                <span
                                  className={`px-2 py-0.5 rounded-full font-bold text-[10px] ${
                                    item.perhatian > 0
                                      ? 'bg-amber-100 text-amber-800'
                                      : 'bg-slate-100 text-slate-500'
                                  }`}
                                >
                                  {item.perhatian} Org
                                </span>
                              </td>
                            </tr>
                          );
                        })
                      )}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>

          {/* Modal Footer */}
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3.5 bg-white border-t border-slate-200 shrink-0 text-xs text-slate-500">
            <div className="flex items-center gap-2">
              <span className="w-2 h-2 rounded-full bg-teal-500 animate-ping" />
              <span>Data sinkron langsung dengan formulir skrining kebugaran pegawai Poltekkes Kemenkes Bandung.</span>
            </div>
            <button
              onClick={onClose}
              className="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-colors"
            >
              Tutup Dashboard
            </button>
          </div>
        </motion.div>
      </div>

      {/* Ekspor Laporan Eksekutif PDF Modal */}
      <KebugaranDashboardExportPdfModal
        isOpen={isExportPdfModalOpen}
        onClose={() => setIsExportPdfModalOpen(false)}
        submissions={filteredSubmissions}
        selectedTriwulan={selectedTriwulan}
        selectedYear={selectedYear}
        selectedUnit={selectedUnit}
      />
    </AnimatePresence>
  );
};

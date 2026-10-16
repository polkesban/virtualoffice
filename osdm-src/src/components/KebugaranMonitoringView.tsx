import React, { useState, useMemo, useEffect } from 'react';
import {
  Activity,
  Calendar,
  Search,
  Filter,
  FileSpreadsheet,
  Printer,
  Plus,
  Heart,
  Users,
  AlertTriangle,
  CheckCircle2,
  Phone,
  Building,
  Trash2,
  RefreshCw,
  ExternalLink,
  MessageSquare,
  ArrowUpDown,
  HeartPulse,
  BarChart3
} from 'lucide-react';
import { KebugaranSubmission, KebugaranPeriode, EmployeeRecord, MenuItem } from '../types';
import { getActiveEmployees, getPoltekkesUnitKerjaList, subscribeEmployeeChanges } from '../data/employeeDatabase';
import {
  exportKebugaranToExcel,
  exportBelumMengisiToExcel,
  formatTanggalIndo,
  formatTanggalPendek,
  calculateBmi,
  classifyBloodPressure,
  classifyBloodSugar,
  classifyCholesterol
} from '../utils/kebugaranUtils';
import { KebugaranPrintModal } from './KebugaranPrintModal';
import { KebugaranDashboardModal } from './KebugaranDashboardModal';

interface KebugaranMonitoringViewProps {
  submissions: KebugaranSubmission[];
  onOpenInputModal?: () => void;
  onDeleteSubmission?: (id: string) => Promise<{ success: boolean; error?: string }>;
  onRefresh?: () => void;
  kebugaranMenu?: MenuItem;
  onUpdateMenuPin?: (menuId: string, isProtected: boolean, pinCode: string) => void;
}

export const KebugaranMonitoringView: React.FC<KebugaranMonitoringViewProps> = ({
  submissions,
  onOpenInputModal,
  onDeleteSubmission,
  onRefresh,
  kebugaranMenu,
  onUpdateMenuPin,
}) => {
  // Navigation subtabs
  const [activeSubTab, setActiveSubTab] = useState<'sudah' | 'belum'>('sudah');

  // Filters
  const [periodeFilter, setPeriodeFilter] = useState<string>('Semua Periode');
  const [unitFilter, setUnitFilter] = useState<string>('Semua Unit');
  const [searchQuery, setSearchQuery] = useState<string>('');

  // Print modal state
  const [isPrintModalOpen, setIsPrintModalOpen] = useState(false);

  // Dashboard Kebugaran Modal state
  const [isDashboardModalOpen, setIsDashboardModalOpen] = useState(false);

  // Master employees list (reactive to cloud DB sync)
  const [allMasterEmployees, setAllMasterEmployees] = useState<EmployeeRecord[]>(() => getActiveEmployees());

  useEffect(() => {
    setAllMasterEmployees(getActiveEmployees());
    const unsubscribe = subscribeEmployeeChanges(() => {
      setAllMasterEmployees(getActiveEmployees());
    });
    return unsubscribe;
  }, []);

  const allUnits = useMemo(() => getPoltekkesUnitKerjaList(), []);

  // Filtered Submissions (Data Sudah Mengisi)
  const filteredSubmissions = useMemo(() => {
    return submissions.filter((sub) => {
      // Periode Filter
      if (periodeFilter !== 'Semua Periode' && sub.periode !== periodeFilter) {
        return false;
      }
      // Unit Kerja Filter
      if (unitFilter !== 'Semua Unit' && sub.unitKerja !== unitFilter) {
        return false;
      }
      // Search Query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchNip = sub.nip.toLowerCase().includes(q);
        const matchName = sub.namaPegawai.toLowerCase().includes(q);
        const matchUnit = sub.unitKerja.toLowerCase().includes(q);
        const matchFasyankes = sub.fasyankes.toLowerCase().includes(q);
        if (!matchNip && !matchName && !matchUnit && !matchFasyankes) {
          return false;
        }
      }
      return true;
    });
  }, [submissions, periodeFilter, unitFilter, searchQuery]);

  // Set of NIPs that have submitted for the selected Periode
  const submittedNipSet = useMemo(() => {
    const set = new Set<string>();
    submissions.forEach((s) => {
      if (periodeFilter === 'Semua Periode' || s.periode === periodeFilter) {
        set.add(s.nip.replace(/[\s.-]/g, '').toLowerCase());
      }
    });
    return set;
  }, [submissions, periodeFilter]);

  // List of Employees who haven't submitted (Daftar Belum Mengisi)
  // Non-active employees (Tugas Belajar, Cuti Melahirkan, Cuti Besar) are exempted
  const unsubmittedEmployees = useMemo(() => {
    return allMasterEmployees.filter((emp) => {
      // Exclude employees with status other than 'Aktif'
      if (emp.status && emp.status !== 'Aktif') {
        return false;
      }

      const cleanNip = emp.nip.replace(/[\s.-]/g, '').toLowerCase();
      const hasSubmitted = submittedNipSet.has(cleanNip);

      if (hasSubmitted) return false;

      // Unit filter
      if (unitFilter !== 'Semua Unit' && emp.unitKerja !== unitFilter) {
        return false;
      }

      // Search Query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchNip = emp.nip.toLowerCase().includes(q);
        const matchName = emp.name.toLowerCase().includes(q);
        const matchUnit = emp.unitKerja.toLowerCase().includes(q);
        if (!matchNip && !matchName && !matchUnit) {
          return false;
        }
      }

      return true;
    });
  }, [allMasterEmployees, submittedNipSet, unitFilter, searchQuery]);

  // Scope of active master employees based on Unit filter (exempt non-active employees from KPI total)
  const masterInScope = useMemo(() => {
    const activeOnly = allMasterEmployees.filter((emp) => !emp.status || emp.status === 'Aktif');
    if (unitFilter === 'Semua Unit') return activeOnly;
    return activeOnly.filter((emp) => emp.unitKerja === unitFilter);
  }, [allMasterEmployees, unitFilter]);

  // Count of exempt employees (Tugas Belajar / Cuti)
  const exemptEmployeesCount = useMemo(() => {
    return allMasterEmployees.filter((emp) => {
      const isExempt = emp.status && emp.status !== 'Aktif';
      if (!isExempt) return false;
      if (unitFilter !== 'Semua Unit' && emp.unitKerja !== unitFilter) return false;
      return true;
    }).length;
  }, [allMasterEmployees, unitFilter]);

  // Number of master employees in scope who have submitted
  const masterSubmittedCount = useMemo(() => {
    return masterInScope.filter((emp) => {
      const cleanNip = emp.nip.replace(/[\s.-]/g, '').toLowerCase();
      return submittedNipSet.has(cleanNip);
    }).length;
  }, [masterInScope, submittedNipSet]);

  // KPI Calculations (100% Synchronized with Tab 2 "Pegawai Belum Mengisi")
  const totalMaster = masterInScope.length;
  const totalFilled = masterSubmittedCount;
  const totalUnfilled = unsubmittedEmployees.length;
  const percentageFilled = totalMaster > 0 ? Math.round((totalFilled / totalMaster) * 100) : 0;

  // Health Metrics Breakdown from Filtered Submissions
  const bpNormalCount = filteredSubmissions.filter(
    (s) => s.tensiSistolik < 120 && s.tensiDiastolik < 80
  ).length;
  const cholNormalCount = filteredSubmissions.filter((s) => s.kolesterol < 200).length;
  const idealBmiCount = filteredSubmissions.filter((s) => {
    const bmi = calculateBmi(s.beratBadan, s.tinggiBadan).bmi;
    return bmi >= 18.5 && bmi <= 22.9;
  }).length;

  // Handle Export Excel Data Tes Kebugaran
  const handleExportFullExcel = () => {
    exportKebugaranToExcel(filteredSubmissions, periodeFilter);
  };

  // Handle Export Excel Pegawai Belum Mengisi
  const handleExportBelumMengisiExcel = () => {
    exportBelumMengisiToExcel(unsubmittedEmployees, periodeFilter);
  };

  // Quick WhatsApp Reminder generator
  const sendWhatsAppReminder = (emp: EmployeeRecord) => {
    const rawNumber = emp.nomorWa || '';
    let phone = rawNumber.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
      phone = '62' + phone.substring(1);
    }
    const message = encodeURIComponent(
      `Yth. Bpk/Ibu ${emp.name},\n\n` +
      `Mengingatkan kembali untuk pengisian data kebugaran berkala pegawai Poltekkes Kemenkes Bandung untuk periode: *${periodeFilter}*.\n` +
      `Silakan input formulir kebugaran melalui portal internal kepegawaian.\n\n` +
      `Terima kasih atas kerja samanya.\nSalam Sehat,\nTim Kerja Kepegawaian & OSDM Poltekkes Kemenkes Bandung`
    );
    window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
  };

  return (
    <div className="space-y-6">
      {/* Top Banner & Header */}
      <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold mb-1">
            <Activity className="w-3.5 h-3.5 text-blue-600" />
            <span>Kebugaran Pegawai • Poltekkes Kemenkes Bandung</span>
          </div>
          <h2 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Monitoring Tes Kebugaran Pegawai
          </h2>
          <p className="text-xs sm:text-sm text-slate-500 mt-0.5">
            Pencatatan kesehatan berkala, ekspor Excel rekap, cetak formulir resmi, dan pantau pegawai yang belum mengisi.
          </p>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
          {onRefresh && (
            <button
              onClick={onRefresh}
              className="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition-all shadow-xs"
              title="Muat Ulang Data"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Refresh</span>
            </button>
          )}

          {/* Tombol Dashboard Kebugaran (Tensi, IMT, Kolesterol, Gula Darah) */}
          <button
            onClick={() => setIsDashboardModalOpen(true)}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-teal-600 via-emerald-600 to-cyan-600 hover:from-teal-700 hover:via-emerald-700 hover:to-cyan-700 text-white text-xs font-bold transition-all shadow-xs shadow-teal-100"
            title="Buka Dashboard Analisis & Statistik Kebugaran Pegawai (Tensi, IMT, Kolesterol, Gula Darah - Filter Triwulan & Tahunan)"
          >
            <HeartPulse className="w-3.5 h-3.5" />
            <span>Dashboard Kebugaran</span>
          </button>

          {/* Cetak Rekapan Button */}
          <button
            onClick={() => setIsPrintModalOpen(true)}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all shadow-xs"
            title="Cetak Rekapan Tes Kebugaran dengan KOP Resmi Kemenkes"
          >
            <Printer className="w-3.5 h-3.5 text-sky-300" />
            <span>Cetak Rekapan</span>
          </button>
        </div>
      </div>


      {/* KPI Cards Section */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        {/* Card 1: Sudah Mengisi */}
        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
              Sudah Mengisi ({periodeFilter})
            </span>
            <div className="text-2xl font-black text-slate-900 mt-1">
              {totalFilled} <span className="text-xs font-normal text-slate-500">/ {totalMaster} org</span>
            </div>
            <div className="text-[11px] font-semibold text-emerald-600 mt-0.5">
              Tingkat Kepatuhan: {percentageFilled}%
            </div>
          </div>
          <div className="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
            <CheckCircle2 className="w-6 h-6" />
          </div>
        </div>

        {/* Card 2: Belum Mengisi */}
        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
              Belum Mengisi ({periodeFilter})
            </span>
            <div className="text-2xl font-black text-rose-600 mt-1">
              {totalUnfilled} <span className="text-xs font-normal text-slate-500">pegawai</span>
            </div>
            <div className="text-[11px] font-semibold text-rose-600 mt-0.5">
              Perlu pengingat & tindak lanjut
            </div>
          </div>
          <div className="w-11 h-11 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
            <AlertTriangle className="w-6 h-6" />
          </div>
        </div>

        {/* Card 3: Tekanan Darah Normal */}
        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
              Tensi Darah Optimal
            </span>
            <div className="text-2xl font-black text-slate-900 mt-1">
              {bpNormalCount}{' '}
              <span className="text-xs font-normal text-slate-500">
                ({filteredSubmissions.length > 0 ? Math.round((bpNormalCount / filteredSubmissions.length) * 100) : 0}%)
              </span>
            </div>
            <div className="text-[11px] font-semibold text-blue-600 mt-0.5">
              Sistolik &lt; 120 / Diastolik &lt; 80
            </div>
          </div>
          <div className="w-11 h-11 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
            <Heart className="w-6 h-6" />
          </div>
        </div>

        {/* Card 4: Kolesterol & IMT */}
        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
              Kolesterol Aman
            </span>
            <div className="text-2xl font-black text-slate-900 mt-1">
              {cholNormalCount}{' '}
              <span className="text-xs font-normal text-slate-500">
                ({filteredSubmissions.length > 0 ? Math.round((cholNormalCount / filteredSubmissions.length) * 100) : 0}%)
              </span>
            </div>
            <div className="text-[11px] font-semibold text-teal-600 mt-0.5">
              &lt; 200 mg/dL (Ideal Kemenkes)
            </div>
          </div>
          <div className="w-11 h-11 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center shrink-0">
            <Activity className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Sub-tab Switcher: Data Hasil Tes Kebugaran VS Daftar Belum Mengisi */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white p-2.5 rounded-2xl border border-slate-200 shadow-xs">
        <div className="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl w-full sm:w-auto">
          <button
            onClick={() => setActiveSubTab('sudah')}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all ${
              activeSubTab === 'sudah'
                ? 'bg-white text-blue-700 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
            <span>Data Hasil Tes Kebugaran</span>
            <span className="px-2 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-800 font-extrabold">
              {filteredSubmissions.length}
            </span>
          </button>

          <button
            onClick={() => setActiveSubTab('belum')}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all ${
              activeSubTab === 'belum'
                ? 'bg-white text-rose-700 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <AlertTriangle className="w-4 h-4 text-rose-500" />
            <span>Pegawai Belum Mengisi</span>
            <span className="px-2 py-0.5 rounded-full text-[10px] bg-rose-100 text-rose-800 font-extrabold">
              {unsubmittedEmployees.length}
            </span>
          </button>
        </div>

        {/* Tab Specific Excel Download CTA */}
        {activeSubTab === 'belum' ? (
          <button
            onClick={handleExportBelumMengisiExcel}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-all w-full sm:w-auto justify-center"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>Download Excel Pegawai Belum Mengisi</span>
          </button>
        ) : (
          <button
            onClick={handleExportFullExcel}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-all w-full sm:w-auto justify-center"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>Tarikan Excel Rekap ({filteredSubmissions.length})</span>
          </button>
        )}
      </div>

      {/* Filter Toolbar (Periode, Unit Kerja, Search) */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs grid grid-cols-1 sm:grid-cols-3 gap-3">
        {/* Dropdown Periode (Triwulan I s.d IV) */}
        <div>
          <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
            Filter Periode Pemeriksaan
          </label>
          <select
            value={periodeFilter}
            onChange={(e) => setPeriodeFilter(e.target.value)}
            className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 cursor-pointer"
          >
            <option value="Semua Periode">Semua Periode (Triwulan I - IV)</option>
            <option value="Triwulan I">Triwulan I (Jan - Mar)</option>
            <option value="Triwulan II">Triwulan II (Apr - Jun)</option>
            <option value="Triwulan III">Triwulan III (Jul - Sep)</option>
            <option value="Triwulan IV">Triwulan IV (Okt - Des)</option>
          </select>
        </div>

        {/* Dropdown Unit Kerja */}
        <div>
          <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
            Filter Unit Kerja / Jurusan
          </label>
          <select
            value={unitFilter}
            onChange={(e) => setUnitFilter(e.target.value)}
            className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 cursor-pointer"
          >
            <option value="Semua Unit">Semua Unit Kerja ({allUnits.length})</option>
            {allUnits.map((u) => (
              <option key={u} value={u}>
                {u}
              </option>
            ))}
          </select>
        </div>

        {/* Pencarian (NIP / Nama / Fasyankes) */}
        <div>
          <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
            Cari Pegawai (Nama / NIP)
          </label>
          <div className="relative">
            <input
              type="text"
              placeholder="Ketik nama atau NIP pegawai..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
            />
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          </div>
        </div>
      </div>

      {/* VIEW CONTENT BASED ON TAB */}
      {activeSubTab === 'sudah' ? (
        /* TAB 1: DATA HASIL TES KEBUGARAN */
        <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="font-bold text-xs text-slate-800 uppercase tracking-wider">
                Daftar Hasil Pemeriksaan Kebugaran Pegawai
              </span>
              <span className="text-xs text-slate-500 font-medium">
                ({filteredSubmissions.length} Data)
              </span>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-slate-50/80 text-slate-600 font-bold border-b border-slate-200 text-[11px]">
                  <th className="py-3 px-3.5 text-center w-10">No</th>
                  <th className="py-3 px-3.5">Pegawai (NIP & Nama)</th>
                  <th className="py-3 px-3.5">Unit Kerja</th>
                  <th className="py-3 px-3">Periode & Tgl</th>
                  <th className="py-3 px-3">Tensi Darah</th>
                  <th className="py-3 px-3">BB / TB / IMT</th>
                  <th className="py-3 px-3">LP (cm)</th>
                  <th className="py-3 px-3">Gula Darah</th>
                  <th className="py-3 px-3">Kolesterol</th>
                  <th className="py-3 px-3.5">Fasyankes</th>
                  <th className="py-3 px-3.5 text-center w-20">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredSubmissions.length === 0 ? (
                  <tr>
                    <td colSpan={11} className="py-12 text-center text-slate-500">
                      <div className="max-w-xs mx-auto space-y-2">
                        <Activity className="w-8 h-8 text-slate-300 mx-auto" />
                        <p className="font-semibold text-slate-700 text-sm">
                          Belum ada data pemeriksaan kebugaran
                        </p>
                        <p className="text-xs text-slate-400">
                          Gunakan tombol "Input Data Kebugaran" untuk menambahkan hasil tes pegawai.
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  filteredSubmissions.map((sub, idx) => {
                    const bmi = calculateBmi(sub.beratBadan, sub.tinggiBadan);
                    const bp = classifyBloodPressure(sub.tensiSistolik, sub.tensiDiastolik);
                    const bs = classifyBloodSugar(sub.gulaDarah, sub.tipeGulaDarah);
                    const chol = classifyCholesterol(sub.kolesterol);

                    return (
                      <tr key={sub.id} className="hover:bg-slate-50/60 transition-colors">
                        <td className="py-3 px-3.5 text-center font-mono text-slate-400 text-[11px]">
                          {idx + 1}
                        </td>

                        {/* Nama & NIP */}
                        <td className="py-3 px-3.5">
                          <div className="font-bold text-slate-900">{sub.namaPegawai}</div>
                          <div className="font-mono text-[11px] text-slate-500 flex items-center gap-2">
                            <span>NIP: {sub.nip}</span>
                            {sub.nomorWa && (
                              <a
                                href={`https://wa.me/${sub.nomorWa.replace(/[^0-9]/g, '')}`}
                                target="_blank"
                                rel="noreferrer"
                                className="text-emerald-600 hover:text-emerald-700 flex items-center gap-0.5"
                                title="Chat WhatsApp Pegawai"
                              >
                                <Phone className="w-3 h-3" />
                                <span>{sub.nomorWa}</span>
                              </a>
                            )}
                          </div>
                        </td>

                        {/* Unit Kerja */}
                        <td className="py-3 px-3.5 text-slate-700 font-medium">
                          {sub.unitKerja}
                        </td>

                        {/* Periode & Tanggal */}
                        <td className="py-3 px-3">
                          <span className="font-semibold text-blue-700 block">{sub.periode}</span>
                          <span className="text-[10px] text-slate-500 font-mono">
                            {formatTanggalPendek(sub.tanggalPeriksa)}
                          </span>
                        </td>

                        {/* Tensi Darah */}
                        <td className="py-3 px-3">
                          <span className="font-mono font-bold text-slate-900">
                            {sub.tensiSistolik}/{sub.tensiDiastolik}
                          </span>
                          <span className={`block text-[10px] font-semibold px-2 py-0.5 rounded-full border w-fit mt-0.5 ${bp.badgeBg}`}>
                            {bp.label}
                          </span>
                        </td>

                        {/* BB / TB / IMT */}
                        <td className="py-3 px-3">
                          <span className="font-semibold text-slate-800">
                            {sub.beratBadan} kg / {sub.tinggiBadan} cm
                          </span>
                          <span className={`block text-[10px] font-semibold px-2 py-0.5 rounded-full border w-fit mt-0.5 ${bmi.badgeBg}`}>
                            IMT: {bmi.bmi}
                          </span>
                        </td>

                        {/* Lingkar Pinggang */}
                        <td className="py-3 px-3 font-mono font-bold text-slate-800">
                          {sub.lingkarPinggang} cm
                        </td>

                        {/* Gula Darah */}
                        <td className="py-3 px-3">
                          <span className="font-mono font-bold text-slate-900">
                            {sub.gulaDarah} mg/dL
                          </span>
                          <span className={`block text-[10px] font-semibold px-2 py-0.5 rounded-full border w-fit mt-0.5 ${bs.badgeBg}`}>
                            {sub.tipeGulaDarah} • {bs.label.split(' ')[0]}
                          </span>
                        </td>

                        {/* Kolesterol */}
                        <td className="py-3 px-3">
                          <span className="font-mono font-bold text-slate-900">
                            {sub.kolesterol} mg/dL
                          </span>
                          <span className={`block text-[10px] font-semibold px-2 py-0.5 rounded-full border w-fit mt-0.5 ${chol.badgeBg}`}>
                            {chol.label.split(' ')[0]}
                          </span>
                        </td>

                        {/* Fasyankes */}
                        <td className="py-3 px-3.5 text-slate-600 text-[11px]">
                          {sub.fasyankes}
                        </td>

                        {/* Aksi Hapus */}
                        <td className="py-3 px-3.5 text-center">
                          {onDeleteSubmission && (
                            <button
                              onClick={() => {
                                if (window.confirm(`Hapus data kebugaran atas nama ${sub.namaPegawai}?`)) {
                                  onDeleteSubmission(sub.id);
                                }
                              }}
                              className="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                              title="Hapus Data"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          )}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      ) : (
        /* TAB 2: INFORMASI DAFTAR PEGAWAI YANG BELUM MENGISI DATA KEBUGARAN */
        <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="px-5 py-3.5 border-b border-slate-100 bg-rose-50/40">
            <div>
              <div className="flex items-center gap-2">
                <AlertTriangle className="w-4 h-4 text-rose-600" />
                <span className="font-bold text-xs text-rose-900 uppercase tracking-wider">
                  Daftar Pegawai Belum Mengisi Data Kebugaran
                </span>
                <span className="text-xs font-bold text-rose-600">
                  ({unsubmittedEmployees.length} Pegawai)
                </span>
              </div>
              <p className="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5 flex-wrap">
                <span>Target Periode: <strong>{periodeFilter}</strong> • Unduh rekap dalam format Excel untuk bahan koordinasi & tindak lanjut.</span>
                {exemptEmployeesCount > 0 && (
                  <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                    💡 {exemptEmployeesCount} pegawai dikecualikan (Tugas Belajar / Cuti / Pensiun)
                  </span>
                )}
              </p>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-slate-50/80 text-slate-600 font-bold border-b border-slate-200 text-[11px]">
                  <th className="py-3 px-3.5 text-center w-10">No</th>
                  <th className="py-3 px-3.5">NIP Pegawai</th>
                  <th className="py-3 px-3.5">Nama Lengkap Pegawai</th>
                  <th className="py-3 px-3.5">Unit Kerja / Jurusan</th>
                  <th className="py-3 px-3.5">Jabatan</th>
                  <th className="py-3 px-3.5">Status</th>
                  <th className="py-3 px-3.5 text-center w-36">Tindak Lanjut</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {unsubmittedEmployees.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="py-12 text-center text-slate-500">
                      <div className="max-w-xs mx-auto space-y-2">
                        <CheckCircle2 className="w-8 h-8 text-emerald-500 mx-auto" />
                        <p className="font-bold text-slate-800 text-sm">
                          Luar Biasa! Semua Pegawai Sudah Mengisi
                        </p>
                        <p className="text-xs text-slate-400">
                          Tidak ada pegawai yang tertinggal pada filter periode {periodeFilter}.
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  unsubmittedEmployees.map((emp, idx) => (
                    <tr key={emp.nip} className="hover:bg-slate-50/60 transition-colors">
                      <td className="py-3 px-3.5 text-center font-mono text-slate-400 text-[11px]">
                        {idx + 1}
                      </td>
                      <td className="py-3 px-3.5 font-mono text-slate-700 font-semibold">
                        {emp.nip}
                      </td>
                      <td className="py-3 px-3.5 font-bold text-slate-900">
                        {emp.name}
                      </td>
                      <td className="py-3 px-3.5 text-slate-700">
                        {emp.unitKerja}
                      </td>
                      <td className="py-3 px-3.5 text-slate-500 text-[11px]">
                        {emp.jabatan || 'Dosen / Tenaga Kependidikan'}
                      </td>
                      <td className="py-3 px-3.5">
                        <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200">
                          Belum Input {periodeFilter}
                        </span>
                      </td>
                      <td className="py-3 px-3.5 text-center">
                        <button
                          type="button"
                          onClick={() => sendWhatsAppReminder(emp)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold transition-colors"
                          title={`Kirim Pengingat WhatsApp ke ${emp.name}`}
                        >
                          <MessageSquare className="w-3.5 h-3.5 text-emerald-600" />
                          <span>Kirim WA</span>
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* PRINT REKAPAN MODAL */}
      <KebugaranPrintModal
        isOpen={isPrintModalOpen}
        onClose={() => setIsPrintModalOpen(false)}
        submissions={filteredSubmissions}
        periodeFilter={periodeFilter}
        unitFilter={unitFilter}
      />

      {/* Dashboard Kebugaran Modal */}
      <KebugaranDashboardModal
        isOpen={isDashboardModalOpen}
        onClose={() => setIsDashboardModalOpen(false)}
        submissions={submissions}
      />
    </div>
  );
};

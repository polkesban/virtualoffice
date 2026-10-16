import React, { useState, useMemo, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  Users,
  UserPlus,
  Search,
  Filter,
  Edit,
  Trash2,
  CheckCircle2,
  AlertCircle,
  X,
  FileSpreadsheet,
  RotateCcw,
  Building2,
  Briefcase,
  Mail,
  MessageCircle,
  CreditCard,
  ChevronLeft,
  ChevronRight,
  Sparkles,
  GraduationCap,
  FlaskConical,
  Award,
  BarChart3,
  ChevronDown,
  ChevronUp,
  Layers,
} from 'lucide-react';
import { EmployeeRecord, EmployeeStatus, EmployeeKepegawaian } from '../types';
import {
  getActiveEmployees,
  addNewEmployee,
  updateExistingEmployee,
  deleteExistingEmployee,
  resetEmployeeToDefault,
  subscribeEmployeeChanges,
  loadEmployeeDelta,
  POLTEKKES_EMPLOYEES,
} from '../data/employeeDatabase';

const UNIT_KERJA_PRESETS = [
  'Keperawatan Bandung',
  'Keperawatan Bogor',
  'Kebidanan Bandung',
  'Kebidanan Bogor',
  'Kebidanan Karawang',
  'Gizi',
  'TLM',
  'Farmasi',
  'Kesehatan Gigi',
  'Sanitasi Lingkungan',
  'Promosi Kesehatan',
  'Direktorat / OSDM',
];

export type JabatanCategoryId = 'All' | 'dosen' | 'jfu' | 'plp' | 'jft_lain' | 'lainnya';

/**
 * Helper untuk menghapus label redundan (JFT) atau (JFU) di akhir nama jabatan
 * Contoh: "Dosen Lektor (JFT)" -> "Dosen Lektor"
 */
export function cleanJabatanTitle(jabatan?: string | null): string {
  if (!jabatan) return '-';
  return (
    jabatan
      .replace(/\s*\((?:JFT|JFU)\)/gi, '')
      .replace(/\s+-\s+(?:JFT|JFU)/gi, '')
      .trim() || '-'
  );
}

export function getJabatanCategory(jabatan: string): {
  id: 'dosen' | 'jfu' | 'plp' | 'jft_lain' | 'lainnya';
  title: string;
  shortTitle: string;
  badgeClass: string;
  color: string;
  bgLight: string;
  desc: string;
} {
  const j = (jabatan || '').toLowerCase();

  // Dosen / Tenaga Pendidik
  if (
    j.includes('dosen') ||
    j.includes('lektor') ||
    j.includes('asisten ahli') ||
    j.includes('guru besar') ||
    j.includes('profesor')
  ) {
    return {
      id: 'dosen',
      title: 'Tenaga Pendidik (Dosen)',
      shortTitle: 'Dosen',
      badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
      color: '#6366f1',
      bgLight: 'bg-indigo-50/70',
      desc: 'Guru Besar, Lektor Kepala, Lektor, Asisten Ahli',
    };
  }

  // PLP / Pranata Laboratorium Pendidikan
  if (
    j.includes('pranata laboratorium') ||
    j.includes('plp') ||
    j.includes('laboran') ||
    j.includes('laboratorium')
  ) {
    return {
      id: 'plp',
      title: 'Pranata Laboratorium Pendidikan (PLP)',
      shortTitle: 'PLP / Lab',
      badgeClass: 'bg-teal-50 text-teal-700 border-teal-200',
      color: '#0d9488',
      bgLight: 'bg-teal-50/70',
      desc: 'PLP Ahli Pertama, Muda, Mahir, Terampil & Laboran',
    };
  }

  // JFU / Layanan Operasional / Pelaksana
  if (
    j.includes('(jfu)') ||
    j.includes('layanan operasional') ||
    j.includes('pengadministrasi') ||
    j.includes('pramubakti') ||
    j.includes('pelaksana')
  ) {
    return {
      id: 'jfu',
      title: 'Pelaksana Operasional (JFU)',
      shortTitle: 'JFU / Pelaksana',
      badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
      color: '#2563eb',
      bgLight: 'bg-blue-50/70',
      desc: 'Penata, Operator & Pengelola Layanan Operasional',
    };
  }

  // JFT Lainnya / Fungsional Keahlian & Keterampilan
  if (
    j.includes('(jft)') ||
    j.includes('pustakawan') ||
    j.includes('pranata komputer') ||
    j.includes('keuangan') ||
    j.includes('arsiparis') ||
    j.includes('sdm aparatur') ||
    j.includes('perencana') ||
    j.includes('hukum') ||
    j.includes('humas') ||
    j.includes('pengembang teknologi')
  ) {
    return {
      id: 'jft_lain',
      title: 'Fungsional Tertentu Lain (JFT)',
      shortTitle: 'JFT Lainnya',
      badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',
      color: '#9333ea',
      bgLight: 'bg-purple-50/70',
      desc: 'Pranata Komputer, Pustakawan, Keuangan, SDM, dll.',
    };
  }

  return {
    id: 'lainnya',
    title: 'Struktural & Lainnya',
    shortTitle: 'Lainnya',
    badgeClass: 'bg-slate-100 text-slate-700 border-slate-200',
    color: '#64748b',
    bgLight: 'bg-slate-50/70',
    desc: 'Kasubbag, Struktural, dan penugasan khusus',
  };
}

export interface SpecificJabatanSummary {
  id: string;
  name: string;
  group: 'Dosen' | 'JFU' | 'PLP' | 'JFT' | 'Lainnya';
  groupLabel: string;
  color: string;
  badgeClass: string;
  bgLight: string;
  borderClass: string;
  count: number;
  percent: string;
  description: string;
  icon: any;
  match: (j: string) => boolean;
}

export const EmployeeManagerView: React.FC = () => {
  const [employees, setEmployees] = useState<EmployeeRecord[]>(() => getActiveEmployees());
  const [searchQuery, setSearchQuery] = useState('');
  const [unitFilter, setUnitFilter] = useState('All');
  const [statusFilter, setStatusFilter] = useState('All');
  const [jabatanCategoryFilter, setJabatanCategoryFilter] = useState<JabatanCategoryId>('All');
  const [selectedJabatanId, setSelectedJabatanId] = useState<string | null>(null);
  const [jabatanTab, setJabatanTab] = useState<'All' | 'Dosen' | 'JFU' | 'PLP' | 'JFT'>('All');
  const [jabatanSearch, setJabatanSearch] = useState('');
  const [showAllJabatanCards, setShowAllJabatanCards] = useState(false);
  const [isDashboardModalOpen, setIsDashboardModalOpen] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 12;

  // Modals state
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [editingEmployee, setEditingEmployee] = useState<EmployeeRecord | null>(null);
  const [deletingEmployee, setDeletingEmployee] = useState<EmployeeRecord | null>(null);
  const [isResetConfirmOpen, setIsResetConfirmOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Form states for Add / Edit
  const [formNip, setFormNip] = useState('');
  const [formName, setFormName] = useState('');
  const [formUnit, setFormUnit] = useState('');
  const [formJenisJabatan, setFormJenisJabatan] = useState<'Fungsional' | 'Pelaksana'>('Fungsional');
  const [formJenisKepegawaian, setFormJenisKepegawaian] = useState<EmployeeKepegawaian>('ASN');
  const [formJabatan, setFormJabatan] = useState('');
  const [formJabatanPengelola, setFormJabatanPengelola] = useState('');
  const [formEmail, setFormEmail] = useState('');
  const [formNomorWa, setFormNomorWa] = useState('');
  const [formStatus, setFormStatus] = useState<EmployeeStatus>('Aktif');
  const [formError, setFormError] = useState<string | null>(null);

  // Filter states
  const [kepegawaianFilter, setKepegawaianFilter] = useState<'All' | 'ASN' | 'Non ASN'>('All');

  // Toast feedback
  const [toastMessage, setToastMessage] = useState<{
    text: string;
    type: 'success' | 'error';
    title?: string;
  } | null>(null);
  const toastTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const showToast = (text: string, type: 'success' | 'error' = 'success', title?: string) => {
    if (toastTimerRef.current) {
      clearTimeout(toastTimerRef.current);
    }
    const derivedTitle = title || (type === 'success' ? 'Operasi Berhasil' : 'Terjadi Kesalahan');
    setToastMessage({ text, type, title: derivedTitle });
    toastTimerRef.current = setTimeout(() => {
      setToastMessage(null);
    }, 2800);
  };

  useEffect(() => {
    return () => {
      if (toastTimerRef.current) {
        clearTimeout(toastTimerRef.current);
      }
    };
  }, []);

  // Sync with storage & delta changes
  const reloadData = () => {
    setEmployees([...getActiveEmployees()]);
  };

  useEffect(() => {
    reloadData();
    const unsubscribe = subscribeEmployeeChanges(() => {
      reloadData();
    });
    return unsubscribe;
  }, []);

  const delta = useMemo(() => loadEmployeeDelta(), [employees]);

  // Status statistics for active dashboard (Excludes Pensiun from dashboard view)
  const statusStats = useMemo(() => {
    let aktif = 0;
    let tugasBelajar = 0;
    let cutiMelahirkan = 0;
    let cutiBesar = 0;
    let pensiun = 0;

    employees.forEach((emp) => {
      const st = emp.status || 'Aktif';
      if (st === 'Tugas Belajar') tugasBelajar++;
      else if (st === 'Cuti Melahirkan') cutiMelahirkan++;
      else if (st === 'Cuti Besar') cutiBesar++;
      else if (st === 'Pensiun') pensiun++;
      else aktif++;
    });

    // Total active workforce excluding retired employees
    const activeTotal = aktif + tugasBelajar + cutiMelahirkan + cutiBesar;

    return {
      total: activeTotal,
      aktif,
      tugasBelajar,
      cutiMelahirkan,
      cutiBesar,
      pensiun,
    };
  }, [employees]);

  // Jabatan category statistics & specific list
  const jabatanStats = useMemo(() => {
    const total = employees.length || 1;
    const catMap = {
      dosen: {
        id: 'dosen' as const,
        count: 0,
        title: 'Tenaga Pendidik (Dosen)',
        shortTitle: 'Dosen',
        color: '#6366f1',
        badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        desc: 'Guru Besar, Lektor Kepala, Lektor, Asisten Ahli',
        icon: GraduationCap,
      },
      jfu: {
        id: 'jfu' as const,
        count: 0,
        title: 'Pelaksana Operasional (JFU)',
        shortTitle: 'JFU / Pelaksana',
        color: '#2563eb',
        badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
        desc: 'Penata, Operator & Pengelola Layanan Operasional',
        icon: Briefcase,
      },
      plp: {
        id: 'plp' as const,
        count: 0,
        title: 'Pranata Laboratorium (PLP)',
        shortTitle: 'PLP / Lab',
        color: '#0d9488',
        badgeClass: 'bg-teal-50 text-teal-700 border-teal-200',
        desc: 'PLP Ahli Pertama, Muda, Mahir, Terampil & Laboran',
        icon: FlaskConical,
      },
      jft_lain: {
        id: 'jft_lain' as const,
        count: 0,
        title: 'Fungsional Keahlian Lain (JFT)',
        shortTitle: 'JFT Lainnya',
        color: '#9333ea',
        badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',
        desc: 'Pranata Komputer, Pustakawan, Keuangan, SDM, dll.',
        icon: Award,
      },
      lainnya: {
        id: 'lainnya' as const,
        count: 0,
        title: 'Struktural & Lainnya',
        shortTitle: 'Lainnya',
        color: '#64748b',
        badgeClass: 'bg-slate-100 text-slate-700 border-slate-200',
        desc: 'Kasubbag, Struktural, dan penugasan khusus',
        icon: Building2,
      },
    };

    const specificMap: Record<string, number> = {};

    employees.forEach((emp) => {
      const cat = getJabatanCategory(emp.jabatan || '');
      catMap[cat.id].count++;

      const specific = (emp.jabatan || 'Lainnya').trim();
      specificMap[specific] = (specificMap[specific] || 0) + 1;
    });

    const specificList = Object.entries(specificMap)
      .map(([jabatan, count]) => ({
        jabatan,
        count,
        percent: ((count / total) * 100).toFixed(1),
        category: getJabatanCategory(jabatan),
      }))
      .sort((a, b) => b.count - a.count);

    return {
      catMap,
      specificList,
      total,
    };
  }, [employees]);

  // List of all specific jabatan cards (Lektor, Lektor Kepala, Asisten Ahli, Penata, Operator, PLP, dll.)
  // Excludes Pensiun from counts so it accurately reflects active serving employees
  const specificJabatanList = useMemo(() => {
    const nonPensiunEmployees = employees.filter((emp) => emp.status !== 'Pensiun');
    const total = nonPensiunEmployees.length || 1;

    const items = [
      // ─── DOSEN / TENAGA PENDIDIK ───
      {
        id: 'lektor',
        name: 'Lektor',
        group: 'Dosen' as const,
        groupLabel: 'Dosen',
        color: '#4f46e5',
        badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        bgLight: 'bg-indigo-50/40',
        borderClass: 'border-indigo-200 hover:border-indigo-400',
        description: 'Jabatan Fungsional Dosen Lektor (JFT)',
        icon: GraduationCap,
        match: (j: string) => j.toLowerCase().includes('lektor') && !j.toLowerCase().includes('lektor kepala'),
      },
      {
        id: 'lektor_kepala',
        name: 'Lektor Kepala',
        group: 'Dosen' as const,
        groupLabel: 'Dosen',
        color: '#4338ca',
        badgeClass: 'bg-indigo-100 text-indigo-800 border-indigo-300 font-bold',
        bgLight: 'bg-indigo-50/60',
        borderClass: 'border-indigo-300 hover:border-indigo-500',
        description: 'Jabatan Fungsional Dosen Lektor Kepala (JFT)',
        icon: GraduationCap,
        match: (j: string) => j.toLowerCase().includes('lektor kepala'),
      },
      {
        id: 'asisten_ahli',
        name: 'Asisten Ahli',
        group: 'Dosen' as const,
        groupLabel: 'Dosen',
        color: '#6366f1',
        badgeClass: 'bg-violet-50 text-violet-700 border-violet-200',
        bgLight: 'bg-violet-50/40',
        borderClass: 'border-violet-200 hover:border-violet-400',
        description: 'Jabatan Fungsional Dosen Asisten Ahli (JFT)',
        icon: GraduationCap,
        match: (j: string) => j.toLowerCase().includes('asisten ahli'),
      },
      {
        id: 'guru_besar',
        name: 'Guru Besar / Profesor',
        group: 'Dosen' as const,
        groupLabel: 'Dosen',
        color: '#3730a3',
        badgeClass: 'bg-purple-100 text-purple-900 border-purple-300 font-black',
        bgLight: 'bg-purple-50/60',
        borderClass: 'border-purple-300 hover:border-purple-500',
        description: 'Jenjang Akademik Tertinggi Guru Besar (JFT)',
        icon: Award,
        match: (j: string) => j.toLowerCase().includes('guru besar') || j.toLowerCase().includes('profesor'),
      },
      {
        id: 'dosen_pengembang',
        name: 'Dosen / Pengembang Prodi',
        group: 'Dosen' as const,
        groupLabel: 'Dosen',
        color: '#4f46e5',
        badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        bgLight: 'bg-indigo-50/30',
        borderClass: 'border-indigo-200 hover:border-indigo-400',
        description: 'Dosen penugasan pengembang program studi',
        icon: GraduationCap,
        match: (j: string) =>
          j.toLowerCase().includes('dosen') &&
          !j.toLowerCase().includes('lektor') &&
          !j.toLowerCase().includes('asisten ahli'),
      },

      // ─── PELAKSANA OPERASIONAL (JFU) ───
      {
        id: 'operator_layanan',
        name: 'Operator Layanan Operasional',
        group: 'JFU' as const,
        groupLabel: 'Pelaksana (JFU)',
        color: '#0284c7',
        badgeClass: 'bg-sky-50 text-sky-700 border-sky-200',
        bgLight: 'bg-sky-50/40',
        borderClass: 'border-sky-200 hover:border-sky-400',
        description: 'Operator Layanan Operasional SMU / SMP (JFU)',
        icon: Briefcase,
        match: (j: string) => j.toLowerCase().includes('operator layanan'),
      },
      {
        id: 'penata_layanan',
        name: 'Penata Layanan Operasional',
        group: 'JFU' as const,
        groupLabel: 'Pelaksana (JFU)',
        color: '#0369a1',
        badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
        bgLight: 'bg-blue-50/40',
        borderClass: 'border-blue-200 hover:border-blue-400',
        description: 'Penata Layanan Operasional (JFU)',
        icon: Briefcase,
        match: (j: string) => j.toLowerCase().includes('penata layanan'),
      },
      {
        id: 'pengelola_layanan',
        name: 'Pengelola Layanan Operasional',
        group: 'JFU' as const,
        groupLabel: 'Pelaksana (JFU)',
        color: '#075985',
        badgeClass: 'bg-cyan-50 text-cyan-700 border-cyan-200',
        bgLight: 'bg-cyan-50/40',
        borderClass: 'border-cyan-200 hover:border-cyan-400',
        description: 'Pengelola Layanan Operasional (JFU)',
        icon: Briefcase,
        match: (j: string) => j.toLowerCase().includes('pengelola layanan'),
      },
      {
        id: 'pengadministrasi_pramubakti',
        name: 'Pengadministrasi & Pramubakti',
        group: 'JFU' as const,
        groupLabel: 'Pelaksana (JFU)',
        color: '#475569',
        badgeClass: 'bg-slate-100 text-slate-700 border-slate-300',
        bgLight: 'bg-slate-50/60',
        borderClass: 'border-slate-200 hover:border-slate-400',
        description: 'Pengadministrasi Umum, Pramubakti & Pendukung',
        icon: Briefcase,
        match: (j: string) => j.toLowerCase().includes('pengadministrasi') || j.toLowerCase().includes('pramubakti'),
      },

      // ─── LABORAN / PRANATA LABORATORIUM PENDIDIKAN (PLP) ───
      {
        id: 'plp_pertama',
        name: 'PLP Ahli Pertama',
        group: 'PLP' as const,
        groupLabel: 'Laboran (PLP)',
        color: '#0d9488',
        badgeClass: 'bg-teal-50 text-teal-700 border-teal-200',
        bgLight: 'bg-teal-50/40',
        borderClass: 'border-teal-200 hover:border-teal-400',
        description: 'Pranata Laboratorium Pendidikan Ahli Pertama (JFT)',
        icon: FlaskConical,
        match: (j: string) =>
          (j.toLowerCase().includes('pranata laboratorium') || j.toLowerCase().includes('plp')) &&
          j.toLowerCase().includes('pertama'),
      },
      {
        id: 'plp_terampil',
        name: 'PLP Terampil',
        group: 'PLP' as const,
        groupLabel: 'Laboran (PLP)',
        color: '#0f766e',
        badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        bgLight: 'bg-emerald-50/40',
        borderClass: 'border-emerald-200 hover:border-emerald-400',
        description: 'Pranata Laboratorium Pendidikan Terampil (JFT)',
        icon: FlaskConical,
        match: (j: string) =>
          (j.toLowerCase().includes('pranata laboratorium') || j.toLowerCase().includes('plp')) &&
          j.toLowerCase().includes('terampil'),
      },
      {
        id: 'plp_muda',
        name: 'PLP Ahli Muda',
        group: 'PLP' as const,
        groupLabel: 'Laboran (PLP)',
        color: '#115e59',
        badgeClass: 'bg-teal-100 text-teal-800 border-teal-300 font-bold',
        bgLight: 'bg-teal-50/50',
        borderClass: 'border-teal-300 hover:border-teal-500',
        description: 'Pranata Laboratorium Pendidikan Ahli Muda (JFT)',
        icon: FlaskConical,
        match: (j: string) =>
          (j.toLowerCase().includes('pranata laboratorium') || j.toLowerCase().includes('plp')) &&
          j.toLowerCase().includes('muda'),
      },
      {
        id: 'plp_mahir',
        name: 'PLP Mahir',
        group: 'PLP' as const,
        groupLabel: 'Laboran (PLP)',
        color: '#134e4a',
        badgeClass: 'bg-teal-50 text-teal-700 border-teal-200',
        bgLight: 'bg-teal-50/40',
        borderClass: 'border-teal-200 hover:border-teal-400',
        description: 'Pranata Laboratorium Pendidikan Mahir (JFT)',
        icon: FlaskConical,
        match: (j: string) =>
          (j.toLowerCase().includes('pranata laboratorium') || j.toLowerCase().includes('plp')) &&
          j.toLowerCase().includes('mahir'),
      },
      {
        id: 'pengelola_lab',
        name: 'Pengelola Laboratorium',
        group: 'PLP' as const,
        groupLabel: 'Laboran (PLP)',
        color: '#0d9488',
        badgeClass: 'bg-teal-50 text-teal-700 border-teal-200',
        bgLight: 'bg-teal-50/30',
        borderClass: 'border-teal-200 hover:border-teal-400',
        description: 'Pengelola Laboratorium Terpadu & Jurusan',
        icon: FlaskConical,
        match: (j: string) =>
          j.toLowerCase().includes('laboratorium') &&
          !j.toLowerCase().includes('pertama') &&
          !j.toLowerCase().includes('terampil') &&
          !j.toLowerCase().includes('muda') &&
          !j.toLowerCase().includes('mahir'),
      },

      // ─── FUNGSIONAL TERTENTU (JFT) KEAHLIAN LAINNYA ───
      {
        id: 'keuangan',
        name: 'Pengelola / Pranata Keuangan APBN',
        group: 'JFT' as const,
        groupLabel: 'Fungsional (JFT)',
        color: '#059669',
        badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        bgLight: 'bg-emerald-50/40',
        borderClass: 'border-emerald-200 hover:border-emerald-400',
        description: 'Pranata & Analis Pengelolaan Keuangan APBN (JFT)',
        icon: Award,
        match: (j: string) => j.toLowerCase().includes('keuangan'),
      },
      {
        id: 'pustakawan',
        name: 'Pustakawan',
        group: 'JFT' as const,
        groupLabel: 'Fungsional (JFT)',
        color: '#d97706',
        badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
        bgLight: 'bg-amber-50/40',
        borderClass: 'border-amber-200 hover:border-amber-400',
        description: 'Pustakawan Ahli Pertama, Muda, Madya & Terampil (JFT)',
        icon: Award,
        match: (j: string) => j.toLowerCase().includes('pustakawan') || j.toLowerCase().includes('perpustakaan'),
      },
      {
        id: 'komputer',
        name: 'Pranata Komputer',
        group: 'JFT' as const,
        groupLabel: 'Fungsional (JFT)',
        color: '#2563eb',
        badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
        bgLight: 'bg-blue-50/40',
        borderClass: 'border-blue-200 hover:border-blue-400',
        description: 'Pranata Komputer Ahli Pertama, Mahir & Terampil (JFT)',
        icon: Award,
        match: (j: string) => j.toLowerCase().includes('pranata komputer'),
      },
      {
        id: 'sdm_aparatur',
        name: 'Pranata / Analis SDM Aparatur',
        group: 'JFT' as const,
        groupLabel: 'Fungsional (JFT)',
        color: '#7c3aed',
        badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',
        bgLight: 'bg-purple-50/40',
        borderClass: 'border-purple-200 hover:border-purple-400',
        description: 'Pranata & Analis SDM Aparatur (JFT)',
        icon: Award,
        match: (j: string) => j.toLowerCase().includes('sumber daya manusia') || j.toLowerCase().includes('sdm'),
      },
      {
        id: 'ptp',
        name: 'Pengembang Teknologi Pembelajaran',
        group: 'JFT' as const,
        groupLabel: 'Fungsional (JFT)',
        color: '#9333ea',
        badgeClass: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
        bgLight: 'bg-fuchsia-50/40',
        borderClass: 'border-fuchsia-200 hover:border-fuchsia-400',
        description: 'Pengembang Teknologi Pembelajaran Ahli Pertama (JFT)',
        icon: Award,
        match: (j: string) => j.toLowerCase().includes('teknologi pembelajaran'),
      },
      {
        id: 'arsiparis',
        name: 'Arsiparis',
        group: 'JFT' as const,
        groupLabel: 'Fungsional (JFT)',
        color: '#64748b',
        badgeClass: 'bg-slate-100 text-slate-700 border-slate-300',
        bgLight: 'bg-slate-50/40',
        borderClass: 'border-slate-200 hover:border-slate-400',
        description: 'Arsiparis Ahli Pertama (JFT)',
        icon: Award,
        match: (j: string) => j.toLowerCase().includes('arsiparis'),
      },
      {
        id: 'hukum_humas_perencana',
        name: 'Hukum, Humas & Perencana',
        group: 'JFT' as const,
        groupLabel: 'Fungsional (JFT)',
        color: '#0891b2',
        badgeClass: 'bg-cyan-50 text-cyan-700 border-cyan-200',
        bgLight: 'bg-cyan-50/40',
        borderClass: 'border-cyan-200 hover:border-cyan-400',
        description: 'Analis Hukum, Pranata Humas & Perencana (JFT)',
        icon: Award,
        match: (j: string) =>
          j.toLowerCase().includes('hukum') ||
          j.toLowerCase().includes('hubungan masyarakat') ||
          j.toLowerCase().includes('perencana'),
      },
      {
        id: 'struktural_kasubbag',
        name: 'Kasubbag Administrasi Akademik',
        group: 'Lainnya' as const,
        groupLabel: 'Struktural',
        color: '#475569',
        badgeClass: 'bg-slate-100 text-slate-800 border-slate-300 font-bold',
        bgLight: 'bg-slate-50/60',
        borderClass: 'border-slate-300 hover:border-slate-500',
        description: 'Struktural Kasubbag Administrasi Akademik',
        icon: Building2,
        match: (j: string) =>
          j.toLowerCase().includes('administrasi akademik') || j.toLowerCase().includes('kepala subbagian'),
      },
    ];

    return items
      .map((it) => {
        const count = nonPensiunEmployees.filter((emp) => it.match(emp.jabatan || '')).length;
        return {
          ...it,
          count,
          percent: ((count / total) * 100).toFixed(1),
        };
      })
      .sort((a, b) => b.count - a.count);
  }, [employees]);

  // Group count totals for the tabs
  const jabatanGroupTotals = useMemo(() => {
    let dosen = 0;
    let jfu = 0;
    let plp = 0;
    let jft = 0;

    specificJabatanList.forEach((it) => {
      if (it.group === 'Dosen') dosen += it.count;
      else if (it.group === 'JFU') jfu += it.count;
      else if (it.group === 'PLP') plp += it.count;
      else if (it.group === 'JFT') jft += it.count;
    });

    return { dosen, jfu, plp, jft, total: statusStats.total };
  }, [specificJabatanList, statusStats.total]);

  // Filtered specific jabatan cards based on active tab and search input
  const visibleJabatanCards = useMemo(() => {
    let list = specificJabatanList;

    if (jabatanTab !== 'All') {
      list = list.filter((it) => it.group === jabatanTab);
    }

    if (jabatanSearch.trim()) {
      const q = jabatanSearch.toLowerCase().trim();
      list = list.filter(
        (it) => it.name.toLowerCase().includes(q) || it.description.toLowerCase().includes(q)
      );
    }

    return list;
  }, [specificJabatanList, jabatanTab, jabatanSearch]);

  // Active clicked jabatan item config
  const activeSelectedJabatan = useMemo(() => {
    if (!selectedJabatanId) return null;
    return specificJabatanList.find((it) => it.id === selectedJabatanId) || null;
  }, [selectedJabatanId, specificJabatanList]);

  // Unique Unit Kerja list for filter dropdown
  const uniqueUnits = useMemo(() => {
    const set = new Set<string>();
    employees.forEach((e) => {
      if (e.unitKerja) set.add(e.unitKerja);
    });
    return Array.from(set).sort();
  }, [employees]);

  // Filtered employees
  const filteredEmployees = useMemo(() => {
    const q = searchQuery.toLowerCase().trim();
    const cleanQ = q.replace(/[\s.-]/g, '');

    return employees.filter((emp) => {
      const matchSearch =
        !q ||
        emp.nip.includes(cleanQ) ||
        emp.name.toLowerCase().includes(q) ||
        (emp.unitKerja && emp.unitKerja.toLowerCase().includes(q)) ||
        (emp.jabatan && emp.jabatan.toLowerCase().includes(q));

      const matchUnit = unitFilter === 'All' || emp.unitKerja === unitFilter;
      const matchStatus = statusFilter === 'All' || (emp.status || 'Aktif') === statusFilter;
      const matchKepegawaian = kepegawaianFilter === 'All' || (emp.jenisKepegawaian || 'ASN') === kepegawaianFilter;
      const matchJabatanCat =
        jabatanCategoryFilter === 'All' || getJabatanCategory(emp.jabatan || '').id === jabatanCategoryFilter;

      // Filter by clicked specific jabatan card
      let matchSpecificJabatan = true;
      if (activeSelectedJabatan) {
        matchSpecificJabatan = activeSelectedJabatan.match(emp.jabatan || '');
      }

      return matchSearch && matchUnit && matchStatus && matchKepegawaian && matchJabatanCat && matchSpecificJabatan;
    });
  }, [employees, searchQuery, unitFilter, statusFilter, kepegawaianFilter, jabatanCategoryFilter, activeSelectedJabatan]);

  // Pagination
  const totalPages = Math.max(1, Math.ceil(filteredEmployees.length / itemsPerPage));
  const paginatedEmployees = useMemo(() => {
    const start = (currentPage - 1) * itemsPerPage;
    return filteredEmployees.slice(start, start + itemsPerPage);
  }, [filteredEmployees, currentPage]);

  useEffect(() => {
    setCurrentPage(1);
  }, [searchQuery, unitFilter, statusFilter, kepegawaianFilter, jabatanCategoryFilter, selectedJabatanId]);

  // Open Add Modal
  const handleOpenAddModal = () => {
    setFormNip('');
    setFormName('');
    setFormUnit('');
    setFormJenisJabatan('Fungsional');
    setFormJenisKepegawaian('ASN');
    setFormJabatan('');
    setFormJabatanPengelola('');
    setFormEmail('');
    setFormNomorWa('');
    setFormStatus('Aktif');
    setFormError(null);
    setIsAddModalOpen(true);
  };

  // Submit Add
  const handleSubmitAdd = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);

    const cleanNip = formNip.replace(/[\s.-]/g, '').trim();
    if (!cleanNip || cleanNip.length < 3) {
      setFormError('Nomor NIP / NIK / ID Pegawai Non-ASN wajib diisi minimal 3 karakter!');
      return;
    }
    if (!formName.trim()) {
      setFormError('Nama lengkap pegawai wajib diisi!');
      return;
    }
    if (!formUnit.trim()) {
      setFormError('Unit kerja wajib dipilih atau diisi!');
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await addNewEmployee({
        nip: cleanNip,
        name: formName.trim(),
        unitKerja: formUnit.trim(),
        jenisJabatan: formJenisJabatan,
        jenisKepegawaian: formJenisKepegawaian,
        jabatan: cleanJabatanTitle(formJabatan) || (formJenisJabatan === 'Fungsional' ? 'Dosen' : 'Pelaksana Layanan Operasional'),
        jabatanPengelola: formJabatanPengelola.trim(),
        email: formEmail.trim(),
        nomorWa: formNomorWa.trim(),
        status: formStatus,
      });

      if (res.success) {
        reloadData();
        setIsAddModalOpen(false);
        showToast(
          `Pegawai ${formName.trim()} (NIP: ${cleanNip}) berhasil disimpan permanen ke database!`,
          'success',
          'Pegawai Baru Ditambahkan'
        );
      } else {
        setFormError(res.error || 'Gagal menambahkan pegawai.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // Open Edit Modal
  const handleOpenEditModal = (emp: EmployeeRecord) => {
    setEditingEmployee(emp);
    setFormNip(emp.nip);
    setFormName(emp.name);
    setFormUnit(emp.unitKerja);
    const cat = getJabatanCategory(emp.jabatan);
    setFormJenisJabatan(emp.jenisJabatan || (cat.id === 'jfu' ? 'Pelaksana' : 'Fungsional'));
    setFormJenisKepegawaian(emp.jenisKepegawaian || 'ASN');
    setFormJabatan(cleanJabatanTitle(emp.jabatan));
    setFormJabatanPengelola(emp.jabatanPengelola || '');
    setFormEmail(emp.email || '');
    setFormNomorWa(emp.nomorWa || '');
    setFormStatus(emp.status || 'Aktif');
    setFormError(null);
  };

  // Submit Edit
  const handleSubmitEdit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingEmployee) return;

    if (!formName.trim()) {
      setFormError('Nama pegawai tidak boleh kosong!');
      return;
    }
    if (!formUnit.trim()) {
      setFormError('Unit kerja tidak boleh kosong!');
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await updateExistingEmployee(editingEmployee.nip, {
        name: formName.trim(),
        unitKerja: formUnit.trim(),
        jenisJabatan: formJenisJabatan,
        jenisKepegawaian: formJenisKepegawaian,
        jabatan: cleanJabatanTitle(formJabatan) || (formJenisJabatan === 'Fungsional' ? 'Dosen' : 'Pelaksana Layanan Operasional'),
        jabatanPengelola: formJabatanPengelola.trim(),
        email: formEmail.trim(),
        nomorWa: formNomorWa.trim(),
        status: formStatus,
      });

      if (res.success) {
        reloadData();
        setEditingEmployee(null);
        showToast(
          `Data pegawai ${formName.trim()} berhasil diperbarui di database!`,
          'success',
          'Data Berhasil Diperbarui'
        );
      } else {
        setFormError(res.error || 'Gagal memperbarui pegawai.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // Submit Delete
  const handleConfirmDelete = async () => {
    if (!deletingEmployee) return;
    setIsSubmitting(true);
    try {
      const res = await deleteExistingEmployee(deletingEmployee.nip);
      if (res.success) {
        reloadData();
        showToast(
          `Pegawai ${deletingEmployee.name} (${deletingEmployee.nip}) berhasil dihapus permanen dari database.`,
          'success',
          'Data Berhasil Dihapus'
        );
        setDeletingEmployee(null);
      } else {
        showToast(res.error || 'Gagal menghapus pegawai', 'error', 'Gagal Menghapus');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // Reset to Factory Default
  const handleConfirmReset = async () => {
    setIsSubmitting(true);
    try {
      await resetEmployeeToDefault();
      reloadData();
      setIsResetConfirmOpen(false);
      showToast(
        'Database pegawai berhasil disegarkan dari server Poltekkes Bandung.',
        'success',
        'Sinkronisasi Berhasil'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  // Export CSV Pegawai
  const handleExportEmployeeCSV = () => {
    if (employees.length === 0) return;
    const headers = ['No', 'NIP', 'Nama Pegawai', 'Jenis Kepegawaian', 'Unit Kerja', 'Jabatan', 'Status Kepegawaian', 'Email'];
    const rows = employees.map((emp, idx) => [
      idx + 1,
      `"${emp.nip}"`,
      `"${emp.name.replace(/"/g, '""')}"`,
      `"${emp.jenisKepegawaian || 'ASN'}"`,
      `"${(emp.unitKerja || '').replace(/"/g, '""')}"`,
      `"${cleanJabatanTitle(emp.jabatan).replace(/"/g, '""')}"`,
      `"${emp.status || 'Aktif'}"`,
      `"${(emp.email || '').replace(/"/g, '""')}"`,
    ]);
    const csvContent = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `database_pegawai_poltekkes_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="space-y-6">
      {/* POPUP NOTIFIKASI FEEDBACK (TENGAH LAYAR) */}
      <AnimatePresence>
        {toastMessage && (
          <div
            className="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-xs"
            onClick={() => setToastMessage(null)}
          >
            <motion.div
              initial={{ opacity: 0, scale: 0.82, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: -10 }}
              transition={{ type: 'spring', damping: 25, stiffness: 350 }}
              className="relative w-full max-w-sm sm:max-w-md bg-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 flex flex-col items-center text-center overflow-hidden cursor-default"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Tombol Tutup Silang di Sudut Kanan Atas */}
              <button
                type="button"
                onClick={() => setToastMessage(null)}
                className="absolute top-3.5 right-3.5 p-1.5 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                title="Tutup notifikasi"
              >
                <X className="w-4 h-4" />
              </button>

              {/* Ikon Besar Berwarna Cerah & Kontras */}
              <div
                className={`w-16 h-16 rounded-2xl flex items-center justify-center mb-3.5 shadow-xs ${
                  toastMessage.type === 'success'
                    ? 'bg-emerald-50 text-emerald-600 border border-emerald-200'
                    : 'bg-rose-50 text-rose-600 border border-rose-200'
                }`}
              >
                {toastMessage.type === 'success' ? (
                  <CheckCircle2 className="w-8 h-8" />
                ) : (
                  <AlertCircle className="w-8 h-8" />
                )}
              </div>

              {/* Judul Notifikasi */}
              <h4 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                {toastMessage.title || (toastMessage.type === 'success' ? 'Operasi Berhasil!' : 'Terjadi Kesalahan!')}
              </h4>

              {/* Isi Pesan dengan Warna Jelas & Terbaca */}
              <p className="text-xs sm:text-sm text-slate-600 font-medium mt-1.5 leading-relaxed max-w-xs sm:max-w-sm">
                {toastMessage.text}
              </p>

              {/* Indikator Garis Durasi Menghilang Otomatis */}
              <motion.div
                initial={{ width: '100%' }}
                animate={{ width: '0%' }}
                transition={{ duration: 2.8, ease: 'linear' }}
                className={`absolute bottom-0 left-0 right-0 h-1.5 ${
                  toastMessage.type === 'success' ? 'bg-emerald-500' : 'bg-rose-500'
                }`}
              />
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* BANNER NOTIFIKASI FILTER AKTIF (JIKA ADA FILTER AKTIF) */}
      {(activeSelectedJabatan || statusFilter !== 'All' || kepegawaianFilter !== 'All') && (
        <div className="p-3.5 rounded-2xl bg-indigo-50 border border-indigo-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs text-indigo-900 shadow-xs">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="w-2.5 h-2.5 rounded-full bg-indigo-600 animate-pulse shrink-0" />
            <span>
              Tabel sedang menyaring:{' '}
              {activeSelectedJabatan && (
                <span>
                  Jabatan <strong>{activeSelectedJabatan.name}</strong> ({filteredEmployees.length} pegawai)
                </span>
              )}
              {activeSelectedJabatan && (statusFilter !== 'All' || kepegawaianFilter !== 'All') && ' • '}
              {kepegawaianFilter !== 'All' && (
                <span>
                  Jenis <strong>{kepegawaianFilter}</strong>
                </span>
              )}
              {kepegawaianFilter !== 'All' && statusFilter !== 'All' && ' • '}
              {statusFilter !== 'All' && (
                <span>
                  Status <strong>{statusFilter}</strong>
                </span>
              )}
            </span>
          </div>
          <div className="flex items-center gap-2 self-end sm:self-auto">
            <button
              type="button"
              onClick={() => setIsDashboardModalOpen(true)}
              className="px-2.5 py-1 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-semibold text-xs transition-all cursor-pointer flex items-center gap-1"
            >
              <BarChart3 className="w-3.5 h-3.5 text-indigo-600" />
              <span>Buka Dashboard</span>
            </button>
            <button
              type="button"
              onClick={() => {
                setSelectedJabatanId(null);
                setStatusFilter('All');
                setKepegawaianFilter('All');
              }}
              className="px-3 py-1 rounded-lg bg-white border border-indigo-300 text-indigo-700 hover:bg-indigo-50 font-bold text-xs transition-all shadow-2xs cursor-pointer flex items-center gap-1"
            >
              <X className="w-3 h-3" />
              <span>Hapus Filter</span>
            </button>
          </div>
        </div>
      )}

      {/* Action Controls & Filters */}
      <div className="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3.5">
        {/* BARIS 1: Input Pencarian (Kiri) & Tombol Aksi (Kanan) */}
        <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
          {/* Search bar */}
          <div className="relative flex-1 min-w-0">
            <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari NIP, nama lengkap, jabatan kedinasan, atau unit kerja..."
              className="w-full pl-10 pr-9 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
            />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                title="Hapus pencarian"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          {/* Action Buttons */}
          <div className="flex items-center gap-2 shrink-0 flex-wrap justify-end">
            {/* Tombol Buka Dashboard */}
            <button
              type="button"
              onClick={() => setIsDashboardModalOpen(true)}
              className={`flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all border shadow-2xs whitespace-nowrap cursor-pointer ${
                selectedJabatanId
                  ? 'bg-indigo-600 hover:bg-indigo-700 text-white border-indigo-700'
                  : 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border-indigo-200'
              }`}
              title="Buka Dashboard Formasi Jabatan & Status Pegawai"
            >
              <BarChart3 className={`w-4 h-4 ${selectedJabatanId ? 'text-white' : 'text-indigo-600'}`} />
              <span>Dashboard Formasi ({statusStats.total})</span>
              {selectedJabatanId && (
                <span className="w-2 h-2 rounded-full bg-emerald-300 animate-pulse ml-0.5" />
              )}
            </button>

            <button
              onClick={handleOpenAddModal}
              className="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white text-xs font-bold transition-all shadow-xs whitespace-nowrap"
            >
              <UserPlus className="w-4 h-4" />
              <span>+ Tambah Pegawai</span>
            </button>

            <button
              onClick={handleExportEmployeeCSV}
              className="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-all border border-slate-200 shadow-2xs whitespace-nowrap"
              title="Ekspor CSV Data Pegawai"
            >
              <FileSpreadsheet className="w-4 h-4 text-emerald-600" />
              <span>CSV Pegawai</span>
            </button>

            {(delta.added.length > 0 || delta.deleted.length > 0 || Object.keys(delta.updated).length > 0) && (
              <button
                onClick={() => setIsResetConfirmOpen(true)}
                className="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition-all border border-rose-200 shadow-2xs whitespace-nowrap"
                title="Kembalikan database pegawai ke standar awal"
              >
                <RotateCcw className="w-4 h-4" />
                <span>Reset Default</span>
              </button>
            )}
          </div>
        </div>

        {/* BARIS 2: Bilah Filter Dropdown (Kiri) & Info Paginasi (Kanan) */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
          <div className="flex items-center gap-2.5 flex-wrap w-full sm:w-auto">
            <div className="flex items-center gap-1.5 text-xs font-bold text-slate-500 shrink-0">
              <Filter className="w-3.5 h-3.5 text-slate-400" />
              <span>Filter:</span>
            </div>

            {/* Filter Unit Kerja */}
            <div className="relative min-w-[180px] sm:min-w-[210px]">
              <select
                value={unitFilter}
                onChange={(e) => setUnitFilter(e.target.value)}
                className="w-full pl-3 pr-8 py-2 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500 cursor-pointer transition-colors"
              >
                <option value="All">Semua Unit Kerja ({uniqueUnits.length})</option>
                {uniqueUnits.map((u) => (
                  <option key={u} value={u}>
                    {u}
                  </option>
                ))}
              </select>
            </div>

            {/* Filter Status Pegawai */}
            <div className="relative min-w-[160px] sm:min-w-[190px]">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full pl-3 pr-8 py-2 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500 cursor-pointer transition-colors"
              >
                <option value="All">Semua Status Pegawai</option>
                <option value="Aktif">🟢 Aktif (Wajib Kebugaran)</option>
                <option value="Tugas Belajar">🔵 Tugas Belajar</option>
                <option value="Cuti Melahirkan">🟣 Cuti Melahirkan</option>
                <option value="Cuti Besar">🟠 Cuti Besar</option>
                <option value="Pensiun">⚫ Pensiun</option>
              </select>
            </div>

            {/* Filter Jenis Kepegawaian (ASN / Non ASN) */}
            <div className="relative min-w-[150px] sm:min-w-[180px]">
              <select
                value={kepegawaianFilter}
                onChange={(e) => setKepegawaianFilter(e.target.value as any)}
                className="w-full pl-3 pr-8 py-2 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-none focus:border-indigo-500 cursor-pointer transition-colors"
              >
                <option value="All">Semua Kepegawaian</option>
                <option value="ASN">🏛️ ASN ({employees.filter(e => (e.jenisKepegawaian || 'ASN') === 'ASN').length})</option>
                <option value="Non ASN">📋 Non ASN ({employees.filter(e => e.jenisKepegawaian === 'Non ASN').length})</option>
              </select>
            </div>

            {/* Filter Kategori Jabatan */}
            <div className="relative min-w-[170px] sm:min-w-[200px]">
              <select
                value={jabatanCategoryFilter}
                onChange={(e) => setJabatanCategoryFilter(e.target.value as JabatanCategoryId)}
                className="w-full pl-3 pr-8 py-2 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500 cursor-pointer transition-colors"
              >
                <option value="All">Semua Kategori Jabatan</option>
                <option value="dosen">🎓 Tenaga Pendidik (Dosen)</option>
                <option value="jfu">💼 Pelaksana Operasional (JFU)</option>
                <option value="plp">🧪 Pranata Lab (PLP)</option>
                <option value="jft_lain">🎖️ Fungsional Keahlian (JFT Lain)</option>
                <option value="lainnya">🏛️ Struktural & Lainnya</option>
              </select>
            </div>

            {(unitFilter !== 'All' || statusFilter !== 'All' || kepegawaianFilter !== 'All' || jabatanCategoryFilter !== 'All' || searchQuery) && (
              <button
                onClick={() => {
                  setUnitFilter('All');
                  setStatusFilter('All');
                  setKepegawaianFilter('All');
                  setJabatanCategoryFilter('All');
                  setSearchQuery('');
                }}
                className="text-[11px] font-semibold text-rose-600 hover:text-rose-700 underline px-1 cursor-pointer"
              >
                Reset Filter
              </button>
            )}
          </div>

          <div className="text-[11px] text-slate-500 flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto shrink-0">
            <span>
              Menampilkan <strong>{filteredEmployees.length}</strong> dari {employees.length} pegawai
              {unitFilter !== 'All' ? ` (${unitFilter})` : ''}
              {kepegawaianFilter !== 'All' ? ` • ${kepegawaianFilter}` : ''}
              {statusFilter !== 'All' ? ` • ${statusFilter}` : ''}
              {jabatanCategoryFilter !== 'All' ? ` • ${jabatanStats.catMap[jabatanCategoryFilter]?.shortTitle}` : ''}
            </span>
            <span className="text-slate-300">•</span>
            <span className="text-slate-400">
              Halaman {currentPage} dari {totalPages}
            </span>
          </div>
        </div>
      </div>

      {/* Employees Table */}
      <div className="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs text-slate-700">
            <thead className="bg-slate-900 text-slate-200 uppercase font-bold text-[10px] tracking-wider">
              <tr>
                <th className="py-3.5 px-4 w-12 text-center">No</th>
                <th className="py-3.5 px-4">NIP</th>
                <th className="py-3.5 px-4">Nama Lengkap & Gelar</th>
                <th className="py-3.5 px-4 text-center">Kepegawaian</th>
                <th className="py-3.5 px-4">Unit Kerja</th>
                <th className="py-3.5 px-4">Jabatan</th>
                <th className="py-3.5 px-4">Jabatan Pengelola</th>
                <th className="py-3.5 px-4 text-center">Status</th>
                <th className="py-3.5 px-4 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {paginatedEmployees.length === 0 ? (
                <tr>
                  <td colSpan={9} className="py-12 text-center text-slate-400">
                    <Users className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                    <p className="font-semibold">Tidak ada data pegawai yang sesuai pencarian.</p>
                    <p className="text-[11px] text-slate-400 mt-1">
                      Coba ubah kata kunci pencarian atau tambah pegawai baru.
                    </p>
                  </td>
                </tr>
              ) : (
                paginatedEmployees.map((emp, index) => {
                  const itemNumber = (currentPage - 1) * itemsPerPage + index + 1;
                  const cleanNipKey = emp.nip.replace(/[\s.-]/g, '').trim().toLowerCase();
                  const isBaseEmployee = POLTEKKES_EMPLOYEES.some(
                    (b) => b.nip.replace(/[\s.-]/g, '').trim().toLowerCase() === cleanNipKey
                  );
                  const isCustomAdded = !isBaseEmployee;
                  const isCustomUpdated = Boolean(delta.updated[emp.nip] || delta.updated[cleanNipKey]);
                  const isBogor = emp.unitKerja?.toLowerCase().includes('bogor');
                  const isKarawang = emp.unitKerja?.toLowerCase().includes('karawang');
                  const empStatus = emp.status || 'Aktif';

                  return (
                    <tr key={emp.nip} className="hover:bg-slate-50 transition-colors">
                      <td className="py-3 px-4 text-center font-semibold text-slate-400 text-[11px]">
                        {itemNumber}
                      </td>
                      <td className="py-3 px-4">
                        <span className="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                          {emp.nip}
                        </span>
                        {isCustomAdded && (
                          <span className="ml-2 inline-block text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800">
                            Baru
                          </span>
                        )}
                        {isCustomUpdated && !isCustomAdded && (
                          <span className="ml-2 inline-block text-[9px] font-bold px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800">
                            Diedit
                          </span>
                        )}
                      </td>
                      <td className="py-3 px-4">
                        <p className="font-bold text-slate-900">{emp.name}</p>
                        <div className="flex items-center gap-2 flex-wrap mt-0.5">
                          {emp.nomorWa && (
                            <span className="inline-flex items-center gap-1 text-[11px] font-mono font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                              <MessageCircle className="w-3 h-3 text-emerald-600" />
                              <span>{emp.nomorWa}</span>
                            </span>
                          )}
                          {emp.email && (
                            <p className="text-[11px] text-slate-400 flex items-center gap-1">
                              <Mail className="w-3 h-3" />
                              <span>{emp.email}</span>
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="py-3 px-4 text-center">
                        {emp.jenisKepegawaian === 'Non ASN' ? (
                          <span
                            className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-300 shadow-2xs"
                            title="Pegawai Non-ASN"
                          >
                            <span>📋</span>
                            <span>Non ASN</span>
                          </span>
                        ) : (
                          <span
                            className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs"
                            title="Pegawai ASN"
                          >
                            <span>🏛️</span>
                            <span>ASN</span>
                          </span>
                        )}
                      </td>
                      <td className="py-3 px-4">
                        <span
                          className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border ${
                            isBogor
                              ? 'bg-amber-50 text-amber-800 border-amber-200'
                              : isKarawang
                              ? 'bg-purple-50 text-purple-800 border-purple-200'
                              : 'bg-emerald-50 text-emerald-800 border-emerald-200'
                          }`}
                        >
                          <Building2 className="w-3 h-3" />
                          <span>{emp.unitKerja || '-'}</span>
                        </span>
                      </td>
                      <td className="py-3 px-4 text-slate-800 text-xs font-medium">
                        {cleanJabatanTitle(emp.jabatan)}
                      </td>
                      <td className="py-3 px-4">
                        {emp.jabatanPengelola ? (
                          <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs">
                            <span className="text-xs">🏛️</span>
                            <span>{emp.jabatanPengelola}</span>
                          </span>
                        ) : (
                          <span className="text-[11px] text-slate-300 font-mono">-</span>
                        )}
                      </td>
                      <td className="py-3 px-4 text-center">
                        {empStatus === 'Tugas Belajar' ? (
                          <span
                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 shadow-2xs"
                            title="Tugas Belajar (Dikecualikan dari tes kebugaran & dilarang WFA)"
                          >
                            Tugas Belajar
                          </span>
                        ) : empStatus === 'Cuti Melahirkan' ? (
                          <span
                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 shadow-2xs"
                            title="Cuti Melahirkan (Dikecualikan dari tes kebugaran & dilarang WFA)"
                          >
                            Cuti Melahirkan
                          </span>
                        ) : empStatus === 'Cuti Besar' ? (
                          <span
                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs"
                            title="Cuti Besar (Dikecualikan dari tes kebugaran & dilarang WFA)"
                          >
                            Cuti Besar
                          </span>
                        ) : empStatus === 'Pensiun' ? (
                          <span
                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300 shadow-2xs"
                            title="Pensiun (Purna Tugas - Bebas Kebugaran & Dilarang WFA)"
                          >
                            Pensiun
                          </span>
                        ) : (
                          <span
                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs"
                            title="Pegawai Aktif (Wajib tes kebugaran & berhak mengajukan WFA)"
                          >
                            Aktif
                          </span>
                        )}
                      </td>
                      <td className="py-3 px-4 text-center">
                        <div className="flex items-center justify-center gap-1.5">
                          <button
                            onClick={() => handleOpenEditModal(emp)}
                            className="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-all border border-indigo-200 hover:border-indigo-400"
                            title="Edit Data Pegawai"
                          >
                            <Edit className="w-3.5 h-3.5" />
                          </button>
                          <button
                            onClick={() => setDeletingEmployee(emp)}
                            className="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition-all border border-rose-200 hover:border-rose-400"
                            title="Hapus Pegawai"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Footer */}
        {totalPages > 1 && (
          <div className="p-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
            <span className="text-slate-500">
              Halaman <strong>{currentPage}</strong> dari <strong>{totalPages}</strong> ({filteredEmployees.length} pegawai)
            </span>
            <div className="flex items-center gap-2">
              <button
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                disabled={currentPage === 1}
                className="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 flex items-center gap-1"
              >
                <ChevronLeft className="w-3.5 h-3.5" />
                <span>Sebelumnya</span>
              </button>
              <button
                onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
                className="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 flex items-center gap-1"
              >
                <span>Berikutnya</span>
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* MODAL: TAMBAH PEGAWAI BARU */}
      <AnimatePresence>
        {isAddModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs">
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="w-full max-w-2xl max-h-[90vh] flex flex-col rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden"
            >
              <div className="px-5 sm:px-6 py-3.5 bg-slate-900 text-white flex items-center justify-between shrink-0">
                <div className="flex items-center gap-2">
                  <UserPlus className="w-5 h-5 text-emerald-400" />
                  <h3 className="font-bold text-sm sm:text-base">Tambah Pegawai Baru</h3>
                </div>
                <button
                  type="button"
                  onClick={() => setIsAddModalOpen(false)}
                  className="text-slate-400 hover:text-white p-1 rounded-lg transition-colors cursor-pointer"
                  title="Tutup formulir"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <form onSubmit={handleSubmitAdd} className="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                {formError && (
                  <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <AlertCircle className="w-4 h-4 shrink-0 text-rose-500" />
                    <span>{formError}</span>
                  </div>
                )}

                {/* Baris 1: NIP & Nama Lengkap (2 Kolom) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Nomor NIP / NIK / ID Non-ASN <span className="text-rose-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      placeholder="18 digit NIP / 16 digit NIK"
                      value={formNip}
                      onChange={(e) => setFormNip(e.target.value)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 font-mono text-xs sm:text-sm focus:outline-none focus:border-emerald-500"
                    />
                    <p className="text-[10px] text-slate-400 mt-0.5">
                      ASN: 18 digit NIP. Non-ASN: 16 digit NIK atau Nomor ID.
                    </p>
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Nama Lengkap & Gelar <span className="text-rose-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      placeholder="Contoh: Dr. Nama Pegawai, M.Kes."
                      value={formName}
                      onChange={(e) => setFormName(e.target.value)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-emerald-500"
                    />
                  </div>
                </div>

                {/* Baris 2: Unit Kerja & Status Kepegawaian (2 Kolom) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Unit Kerja / Jurusan <span className="text-rose-500">*</span>
                    </label>
                    <select
                      value={UNIT_KERJA_PRESETS.includes(formUnit) ? formUnit : formUnit ? 'OTHER' : ''}
                      onChange={(e) => {
                        if (e.target.value !== 'OTHER') {
                          setFormUnit(e.target.value);
                        } else {
                          setFormUnit('');
                        }
                      }}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-emerald-500 cursor-pointer"
                    >
                      <option value="">-- Pilih Rekomendasi Unit Kerja --</option>
                      {UNIT_KERJA_PRESETS.map((unit) => (
                        <option key={unit} value={unit}>
                          {unit}
                        </option>
                      ))}
                      <option value="OTHER">Ketik Unit Kerja Lainnya...</option>
                    </select>
                    {(!UNIT_KERJA_PRESETS.includes(formUnit) || formUnit === '') && (
                      <input
                        type="text"
                        placeholder="Ketik unit kerja spesifik..."
                        value={formUnit}
                        onChange={(e) => setFormUnit(e.target.value)}
                        className="w-full mt-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500"
                      />
                    )}
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Status Kepegawaian <span className="text-rose-500">*</span>
                    </label>
                    <select
                      value={formStatus}
                      onChange={(e) => setFormStatus(e.target.value as EmployeeStatus)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-emerald-500 cursor-pointer font-medium"
                    >
                      <option value="Aktif">🟢 Aktif (Wajib Kebugaran & Berhak WFA)</option>
                      <option value="Tugas Belajar">🔵 Tugas Belajar (Bebas Kebugaran & Blokir WFA)</option>
                      <option value="Cuti Melahirkan">🟣 Cuti Melahirkan (Bebas Kebugaran & Blokir WFA)</option>
                      <option value="Cuti Besar">🟠 Cuti Besar (Bebas Kebugaran & Blokir WFA)</option>
                      <option value="Pensiun">⚫ Pensiun (Purna Tugas)</option>
                    </select>
                    <p className="text-[10px] text-slate-400 mt-0.5">
                      Pegawai non-aktif dikecualikan dari tagihan kebugaran & form WFA.
                    </p>
                  </div>
                </div>

                {/* Pilihan Jenis Kepegawaian (ASN vs Non ASN) */}
                <div className="bg-slate-50/90 p-3.5 rounded-2xl border border-slate-200 flex items-center justify-between flex-wrap gap-2">
                  <div>
                    <span className="text-xs font-bold text-slate-800 block">
                      Jenis Kepegawaian <span className="text-rose-500">*</span>
                    </span>
                    <span className="text-[10px] text-slate-400">
                      Pilih status Aparatur Sipil Negara (ASN) atau Non ASN
                    </span>
                  </div>
                  <div className="inline-flex rounded-xl border border-slate-300 p-0.5 bg-white shadow-2xs">
                    <button
                      type="button"
                      onClick={() => setFormJenisKepegawaian('ASN')}
                      className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                        formJenisKepegawaian === 'ASN'
                          ? 'bg-blue-600 text-white shadow-xs'
                          : 'text-slate-600 hover:text-slate-900'
                      }`}
                    >
                      <span>🏛️</span>
                      <span>ASN</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setFormJenisKepegawaian('Non ASN')}
                      className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                        formJenisKepegawaian === 'Non ASN'
                          ? 'bg-amber-600 text-white shadow-xs'
                          : 'text-slate-600 hover:text-slate-900'
                      }`}
                    >
                      <span>📋</span>
                      <span>Non ASN</span>
                    </button>
                  </div>
                </div>

                {/* Box Jabatan & Jabatan Pengelola */}
                <div className="bg-slate-50/90 p-3.5 rounded-2xl border border-slate-200 space-y-3">
                  <div className="flex items-center justify-between flex-wrap gap-2">
                    <span className="text-xs font-bold text-slate-800">Jenis Jabatan</span>
                    <div className="inline-flex rounded-xl border border-slate-300 p-0.5 bg-white shadow-2xs">
                      <button
                        type="button"
                        onClick={() => setFormJenisJabatan('Fungsional')}
                        className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                          formJenisJabatan === 'Fungsional'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        <span>🎖️</span>
                        <span>Fungsional</span>
                      </button>
                      <button
                        type="button"
                        onClick={() => setFormJenisJabatan('Pelaksana')}
                        className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                          formJenisJabatan === 'Pelaksana'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        <span>💼</span>
                        <span>Pelaksana</span>
                      </button>
                    </div>
                  </div>

                  {/* 2 Kolom: Jabatan & Jabatan Pengelola */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">
                        Jabatan <span className="text-rose-500">*</span>
                      </label>
                      <input
                        type="text"
                        required
                        list="add-jabatan-list"
                        placeholder={
                          formJenisJabatan === 'Fungsional'
                            ? 'Dosen Lektor / PLP / Pranata Komputer'
                            : 'Pengelola Layanan Operasional / Operator'
                        }
                        value={formJabatan}
                        onChange={(e) => setFormJabatan(e.target.value)}
                        className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-emerald-500 bg-white"
                      />
                      <datalist id="add-jabatan-list">
                        {formJenisJabatan === 'Fungsional'
                          ? [
                              'Dosen Asisten Ahli',
                              'Dosen Lektor',
                              'Dosen Lektor Kepala',
                              'Guru Besar',
                              'Pranata Laboratorium Pendidikan (PLP)',
                              'Pranata Komputer',
                              'Arsiparis',
                              'Pustakawan',
                              'Analis SDM Aparatur',
                            ].map((j) => <option key={j} value={j} />)
                          : [
                              'Pengelola Layanan Operasional',
                              'Operator Layanan Operasional',
                              'Pengadministrasi Perkantoran',
                              'Pramubakti',
                              'Pengemudi',
                              'Petugas Keamanan',
                            ].map((j) => <option key={j} value={j} />)}
                      </datalist>
                      <div className="flex items-center gap-1 flex-wrap mt-1">
                        {(formJenisJabatan === 'Fungsional'
                          ? ['Dosen Lektor', 'Dosen Lektor Kepala', 'PLP', 'Pranata Komputer']
                          : ['Pengelola Layanan Operasional', 'Operator', 'Pengadministrasi']
                        ).map((c) => (
                          <button
                            key={c}
                            type="button"
                            onClick={() => setFormJabatan(c)}
                            className="text-[10px] px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 cursor-pointer"
                          >
                            {c}
                          </button>
                        ))}
                      </div>
                    </div>

                    <div>
                      <div className="flex items-center justify-between mb-1">
                        <label className="text-xs font-bold text-slate-700">
                          Jabatan Pengelola <span className="text-[10px] font-normal text-slate-400">(Tugas Tambahan)</span>
                        </label>
                        {formJabatanPengelola && (
                          <button
                            type="button"
                            onClick={() => setFormJabatanPengelola('')}
                            className="text-[10px] text-rose-600 hover:text-rose-800 underline cursor-pointer"
                          >
                            Hapus
                          </button>
                        )}
                      </div>
                      <input
                        type="text"
                        list="add-pengelola-list"
                        placeholder="Ketua Jurusan / Kaprodi / dll. (Opsional)"
                        value={formJabatanPengelola}
                        onChange={(e) => setFormJabatanPengelola(e.target.value)}
                        className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-emerald-500 bg-white"
                      />
                      <datalist id="add-pengelola-list">
                        {[
                          'Direktur',
                          'Wakil Direktur I',
                          'Wakil Direktur II',
                          'Wakil Direktur III',
                          'Ketua Jurusan',
                          'Sekretaris Jurusan',
                          'Ketua Program Studi',
                          'Sekretaris Program Studi',
                          'Kepala Pusat',
                          'Kepala Unit',
                          'Koordinator',
                        ].map((p) => <option key={p} value={p} />)}
                      </datalist>
                      <div className="flex items-center gap-1 flex-wrap mt-1">
                        {['Ketua Jurusan', 'Sekretaris Jurusan', 'Kaprodi', 'Kepala Unit'].map((c) => (
                          <button
                            key={c}
                            type="button"
                            onClick={() => setFormJabatanPengelola(c)}
                            className="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 cursor-pointer"
                          >
                            {c}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Baris 4: Kontak Pegawai (Email & WhatsApp dalam 2 Kolom) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Email Pegawai (Opsional)
                    </label>
                    <input
                      type="email"
                      placeholder="nama.pegawai@poltekkesbandung.ac.id"
                      value={formEmail}
                      onChange={(e) => setFormEmail(e.target.value)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-emerald-500"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Nomor WhatsApp Pegawai (Aktif)
                    </label>
                    <div className="relative">
                      <div className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-mono font-bold text-emerald-600 pointer-events-none">
                        WA
                      </div>
                      <input
                        type="tel"
                        placeholder="Contoh: 081234567890"
                        value={formNomorWa}
                        onChange={(e) => setFormNomorWa(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 rounded-xl border border-slate-300 text-slate-900 font-mono text-xs sm:text-sm focus:outline-none focus:border-emerald-500"
                      />
                    </div>
                  </div>
                </div>

                {/* Sticky Footer Action Buttons */}
                <div className="sticky bottom-0 bg-white/95 backdrop-blur-xs pt-3 pb-1 border-t border-slate-200 flex items-center justify-end gap-2.5 shrink-0 -mx-6 px-6 mt-4">
                  <button
                    type="button"
                    onClick={() => setIsAddModalOpen(false)}
                    className="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-100 cursor-pointer"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs shadow-xs cursor-pointer flex items-center gap-1.5"
                  >
                    {isSubmitting ? 'Menyimpan...' : 'Simpan Pegawai Baru'}
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL: EDIT PEGAWAI */}
      <AnimatePresence>
        {editingEmployee && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs">
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="w-full max-w-2xl max-h-[90vh] flex flex-col rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden"
            >
              <div className="px-5 sm:px-6 py-3.5 bg-indigo-950 text-white flex items-center justify-between shrink-0">
                <div className="flex items-center gap-2">
                  <Edit className="w-5 h-5 text-indigo-400" />
                  <h3 className="font-bold text-sm sm:text-base">Edit Data Pegawai</h3>
                </div>
                <button
                  type="button"
                  onClick={() => setEditingEmployee(null)}
                  className="text-slate-400 hover:text-white p-1 rounded-lg transition-colors cursor-pointer"
                  title="Tutup formulir"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <form onSubmit={handleSubmitEdit} className="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                {formError && (
                  <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <AlertCircle className="w-4 h-4 shrink-0 text-rose-500" />
                    <span>{formError}</span>
                  </div>
                )}

                {/* Baris 1: NIP & Nama Lengkap (2 Kolom) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                  <div>
                    <label className="text-xs font-bold text-slate-500 block mb-1">
                      Nomor NIP / NIK / ID (Tidak Dapat Diubah)
                    </label>
                    <input
                      type="text"
                      disabled
                      value={formNip}
                      className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 font-mono text-xs sm:text-sm cursor-not-allowed"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Nama Lengkap & Gelar <span className="text-rose-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      value={formName}
                      onChange={(e) => setFormName(e.target.value)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-indigo-500"
                    />
                  </div>
                </div>

                {/* Baris 2: Unit Kerja & Status Kepegawaian (2 Kolom) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Unit Kerja / Jurusan <span className="text-rose-500">*</span>
                    </label>
                    <select
                      value={UNIT_KERJA_PRESETS.includes(formUnit) ? formUnit : 'OTHER'}
                      onChange={(e) => {
                        if (e.target.value !== 'OTHER') {
                          setFormUnit(e.target.value);
                        }
                      }}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-indigo-500 cursor-pointer"
                    >
                      {UNIT_KERJA_PRESETS.map((unit) => (
                        <option key={unit} value={unit}>
                          {unit}
                        </option>
                      ))}
                      <option value="OTHER">Lainnya...</option>
                    </select>
                    {(!UNIT_KERJA_PRESETS.includes(formUnit) || formUnit === '') && (
                      <input
                        type="text"
                        placeholder="Nama unit kerja..."
                        value={formUnit}
                        onChange={(e) => setFormUnit(e.target.value)}
                        className="w-full mt-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500"
                      />
                    )}
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Status Kepegawaian <span className="text-rose-500">*</span>
                    </label>
                    <select
                      value={formStatus}
                      onChange={(e) => setFormStatus(e.target.value as EmployeeStatus)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-indigo-500 cursor-pointer font-medium"
                    >
                      <option value="Aktif">🟢 Aktif (Wajib Kebugaran & Berhak WFA)</option>
                      <option value="Tugas Belajar">🔵 Tugas Belajar (Bebas Kebugaran & Blokir WFA)</option>
                      <option value="Cuti Melahirkan">🟣 Cuti Melahirkan (Bebas Kebugaran & Blokir WFA)</option>
                      <option value="Cuti Besar">🟠 Cuti Besar (Bebas Kebugaran & Blokir WFA)</option>
                      <option value="Pensiun">⚫ Pensiun (Purna Tugas)</option>
                    </select>
                    <p className="text-[10px] text-slate-400 mt-0.5">
                      Pegawai non-aktif dibebaskan dari tagihan kebugaran & form WFA.
                    </p>
                  </div>
                </div>

                {/* Pilihan Jenis Kepegawaian (ASN vs Non ASN) */}
                <div className="bg-slate-50/90 p-3.5 rounded-2xl border border-slate-200 flex items-center justify-between flex-wrap gap-2">
                  <div>
                    <span className="text-xs font-bold text-slate-800 block">
                      Jenis Kepegawaian <span className="text-rose-500">*</span>
                    </span>
                    <span className="text-[10px] text-slate-400">
                      Pilih status Aparatur Sipil Negara (ASN) atau Non ASN
                    </span>
                  </div>
                  <div className="inline-flex rounded-xl border border-slate-300 p-0.5 bg-white shadow-2xs">
                    <button
                      type="button"
                      onClick={() => setFormJenisKepegawaian('ASN')}
                      className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                        formJenisKepegawaian === 'ASN'
                          ? 'bg-blue-600 text-white shadow-xs'
                          : 'text-slate-600 hover:text-slate-900'
                      }`}
                    >
                      <span>🏛️</span>
                      <span>ASN</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setFormJenisKepegawaian('Non ASN')}
                      className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                        formJenisKepegawaian === 'Non ASN'
                          ? 'bg-amber-600 text-white shadow-xs'
                          : 'text-slate-600 hover:text-slate-900'
                      }`}
                    >
                      <span>📋</span>
                      <span>Non ASN</span>
                    </button>
                  </div>
                </div>

                {/* Box Jabatan & Jabatan Pengelola */}
                <div className="bg-slate-50/90 p-3.5 rounded-2xl border border-slate-200 space-y-3">
                  <div className="flex items-center justify-between flex-wrap gap-2">
                    <span className="text-xs font-bold text-slate-800">Jenis Jabatan</span>
                    <div className="inline-flex rounded-xl border border-slate-300 p-0.5 bg-white shadow-2xs">
                      <button
                        type="button"
                        onClick={() => setFormJenisJabatan('Fungsional')}
                        className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                          formJenisJabatan === 'Fungsional'
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        <span>🎖️</span>
                        <span>Fungsional</span>
                      </button>
                      <button
                        type="button"
                        onClick={() => setFormJenisJabatan('Pelaksana')}
                        className={`flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                          formJenisJabatan === 'Pelaksana'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        <span>💼</span>
                        <span>Pelaksana</span>
                      </button>
                    </div>
                  </div>

                  {/* 2 Kolom: Jabatan & Jabatan Pengelola */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">
                        Jabatan <span className="text-rose-500">*</span>
                      </label>
                      <input
                        type="text"
                        required
                        list="edit-jabatan-list"
                        placeholder={
                          formJenisJabatan === 'Fungsional'
                            ? 'Dosen Lektor / PLP / Pranata Komputer'
                            : 'Pengelola Layanan Operasional / Operator'
                        }
                        value={formJabatan}
                        onChange={(e) => setFormJabatan(e.target.value)}
                        className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-indigo-500 bg-white"
                      />
                      <datalist id="edit-jabatan-list">
                        {formJenisJabatan === 'Fungsional'
                          ? [
                              'Dosen Asisten Ahli',
                              'Dosen Lektor',
                              'Dosen Lektor Kepala',
                              'Guru Besar',
                              'Pranata Laboratorium Pendidikan (PLP)',
                              'Pranata Komputer',
                              'Arsiparis',
                              'Pustakawan',
                              'Analis SDM Aparatur',
                            ].map((j) => <option key={j} value={j} />)
                          : [
                              'Pengelola Layanan Operasional',
                              'Operator Layanan Operasional',
                              'Pengadministrasi Perkantoran',
                              'Pramubakti',
                              'Pengemudi',
                              'Petugas Keamanan',
                            ].map((j) => <option key={j} value={j} />)}
                      </datalist>
                      <div className="flex items-center gap-1 flex-wrap mt-1">
                        {(formJenisJabatan === 'Fungsional'
                          ? ['Dosen Lektor', 'Dosen Lektor Kepala', 'PLP', 'Pranata Komputer']
                          : ['Pengelola Layanan Operasional', 'Operator', 'Pengadministrasi']
                        ).map((c) => (
                          <button
                            key={c}
                            type="button"
                            onClick={() => setFormJabatan(c)}
                            className="text-[10px] px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 cursor-pointer"
                          >
                            {c}
                          </button>
                        ))}
                      </div>
                    </div>

                    <div>
                      <div className="flex items-center justify-between mb-1">
                        <label className="text-xs font-bold text-slate-700">
                          Jabatan Pengelola <span className="text-[10px] font-normal text-slate-400">(Tugas Tambahan)</span>
                        </label>
                        {formJabatanPengelola && (
                          <button
                            type="button"
                            onClick={() => setFormJabatanPengelola('')}
                            className="text-[10px] text-rose-600 hover:text-rose-800 underline cursor-pointer"
                          >
                            Hapus
                          </button>
                        )}
                      </div>
                      <input
                        type="text"
                        list="edit-pengelola-list"
                        placeholder="Ketua Jurusan / Kaprodi / dll. (Opsional)"
                        value={formJabatanPengelola}
                        onChange={(e) => setFormJabatanPengelola(e.target.value)}
                        className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-indigo-500 bg-white"
                      />
                      <datalist id="edit-pengelola-list">
                        {[
                          'Direktur',
                          'Wakil Direktur I',
                          'Wakil Direktur II',
                          'Wakil Direktur III',
                          'Ketua Jurusan',
                          'Sekretaris Jurusan',
                          'Ketua Program Studi',
                          'Sekretaris Program Studi',
                          'Kepala Pusat',
                          'Kepala Unit',
                          'Koordinator',
                        ].map((p) => <option key={p} value={p} />)}
                      </datalist>
                      <div className="flex items-center gap-1 flex-wrap mt-1">
                        {['Ketua Jurusan', 'Sekretaris Jurusan', 'Kaprodi', 'Kepala Unit'].map((c) => (
                          <button
                            key={c}
                            type="button"
                            onClick={() => setFormJabatanPengelola(c)}
                            className="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 cursor-pointer"
                          >
                            {c}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Baris 4: Kontak Pegawai (Email & WhatsApp dalam 2 Kolom) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Email Pegawai
                    </label>
                    <input
                      type="email"
                      value={formEmail}
                      onChange={(e) => setFormEmail(e.target.value)}
                      className="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm focus:outline-none focus:border-indigo-500"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Nomor WhatsApp Pegawai (Aktif)
                    </label>
                    <div className="relative">
                      <div className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-mono font-bold text-emerald-600 pointer-events-none">
                        WA
                      </div>
                      <input
                        type="tel"
                        placeholder="Contoh: 081234567890"
                        value={formNomorWa}
                        onChange={(e) => setFormNomorWa(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 rounded-xl border border-slate-300 text-slate-900 font-mono text-xs sm:text-sm focus:outline-none focus:border-indigo-500"
                      />
                    </div>
                  </div>
                </div>

                {/* Sticky Footer Action Buttons */}
                <div className="sticky bottom-0 bg-white/95 backdrop-blur-xs pt-3 pb-1 border-t border-slate-200 flex items-center justify-end gap-2.5 shrink-0 -mx-6 px-6 mt-4">
                  <button
                    type="button"
                    onClick={() => setEditingEmployee(null)}
                    className="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-100 cursor-pointer"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs shadow-xs cursor-pointer flex items-center gap-1.5"
                  >
                    {isSubmitting ? 'Memperbarui...' : 'Perbarui Data Pegawai'}
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL: KONFIRMASI HAPUS PEGAWAI */}
      <AnimatePresence>
        {deletingEmployee && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden p-6 space-y-4"
            >
              <div className="p-3 w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center">
                <Trash2 className="w-6 h-6" />
              </div>

              <div>
                <h3 className="font-bold text-base text-slate-900">Hapus Data Pegawai?</h3>
                <p className="text-xs text-slate-600 mt-1 leading-relaxed">
                  Apakah Anda yakin ingin menghapus data pegawai berikut dari sistem?
                </p>
              </div>

              <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                <p className="font-bold text-slate-900">{deletingEmployee.name}</p>
                <p className="text-slate-500 font-mono">NIP: {deletingEmployee.nip}</p>
                <p className="text-slate-500">Unit: {deletingEmployee.unitKerja}</p>
              </div>

              <div className="p-3 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800">
                ⚠️ Pegawai yang dihapus tidak akan dapat ditemukan saat mengisi form pengajuan WFA bimbingan. Anda dapat menambahkannya kembali kapan saja.
              </div>

              <div className="flex items-center justify-end gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setDeletingEmployee(null)}
                  className="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-100"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleConfirmDelete}
                  className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs"
                >
                  Ya, Hapus Pegawai
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL: KONFIRMASI RESET DATABASE */}
      <AnimatePresence>
        {isResetConfirmOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden p-6 space-y-4"
            >
              <div className="p-3 w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                <RotateCcw className="w-6 h-6" />
              </div>

              <div>
                <h3 className="font-bold text-base text-slate-900">Kembalikan ke Data Standar Awal?</h3>
                <p className="text-xs text-slate-600 mt-1 leading-relaxed">
                  Tindakan ini akan menghapus seluruh penambahan atau pengubahan data pegawai kustom, dan mengembalikan master data ke daftar pegawai awal Poltekkes Kemenkes Bandung.
                </p>
              </div>

              <div className="flex items-center justify-end gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setIsResetConfirmOpen(false)}
                  className="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-100"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleConfirmReset}
                  className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs"
                >
                  Reset ke Data Awal
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ─── TOMBOL MELAYANG DASHBOARD (FAB) ─── */}
      <div className="fixed bottom-6 right-6 z-40 flex items-center gap-2.5">
        {selectedJabatanId && (
          <button
            type="button"
            onClick={() => setSelectedJabatanId(null)}
            className="px-3.5 py-3 rounded-full bg-rose-600 hover:bg-rose-700 text-white shadow-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer animate-in fade-in"
            title="Hapus filter jabatan"
          >
            <X className="w-4 h-4" />
            <span className="hidden sm:inline">Reset Filter</span>
          </button>
        )}

        <button
          type="button"
          onClick={() => setIsDashboardModalOpen(true)}
          className={`flex items-center gap-2.5 px-4 sm:px-5 py-3 rounded-full text-white shadow-xl hover:shadow-2xl hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer border ${
            selectedJabatanId
              ? 'bg-indigo-700 border-indigo-400 ring-4 ring-indigo-500/30'
              : 'bg-slate-900 hover:bg-slate-800 border-slate-700/60'
          }`}
          title="Buka Dashboard Formasi Jabatan & Status Pegawai"
        >
          <div className={`p-1.5 rounded-full ${selectedJabatanId ? 'bg-white/20 text-white' : 'bg-indigo-500/20 text-indigo-400'}`}>
            <BarChart3 className="w-4 h-4" />
          </div>
          <div className="flex flex-col text-left">
            <span className="text-xs font-bold leading-tight flex items-center gap-1.5">
              <span>Dashboard Formasi</span>
              {selectedJabatanId ? (
                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping" />
              ) : (
                <span className="text-[10px] bg-indigo-500/30 text-indigo-200 px-1.5 py-0.2 rounded-full font-semibold">
                  {statusStats.total}
                </span>
              )}
            </span>
            <span className="text-[10px] text-slate-300 leading-tight">
              {activeSelectedJabatan ? `${activeSelectedJabatan.name} (${activeSelectedJabatan.count})` : 'Klik untuk rincian jabatan'}
            </span>
          </div>
        </button>
      </div>

      {/* ─── MODAL MELAYANG DASHBOARD FORMASI & STATUS (TANPA PENSIUN) ─── */}
      <AnimatePresence>
        {isDashboardModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
            {/* Backdrop */}
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsDashboardModalOpen(false)}
              className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            />

            {/* Modal Dialog */}
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ duration: 0.2 }}
              className="relative bg-white rounded-3xl shadow-2xl max-w-5xl w-full max-h-[90vh] flex flex-col border border-slate-100 overflow-hidden z-10"
            >
              {/* Modal Header */}
              <div className="p-4 sm:p-6 pb-4 border-b border-slate-100 flex items-start sm:items-center justify-between gap-3 bg-gradient-to-r from-slate-50 to-indigo-50/40">
                <div className="flex items-center gap-3">
                  <div className="p-2.5 rounded-2xl bg-indigo-600 text-white shadow-sm shrink-0">
                    <BarChart3 className="w-5 h-5" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2 flex-wrap">
                      <h3 className="text-sm sm:text-base font-bold text-slate-900">
                        Dashboard Formasi & Status Pegawai
                      </h3>
                      {selectedJabatanId && activeSelectedJabatan && (
                        <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-600 text-white">
                          Filter: {activeSelectedJabatan.name} ({activeSelectedJabatan.count})
                        </span>
                      )}
                    </div>
                    <p className="text-[11px] sm:text-xs text-slate-500 mt-0.5">
                      Distribusi formasi pegawai aktif Poltekkes Kemenkes Bandung • Klik kartu untuk langsung memfilter tabel
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setIsDashboardModalOpen(false)}
                  className="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-colors cursor-pointer"
                  title="Tutup (Esc)"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Modal Body (Scrollable) */}
              <div className="p-4 sm:p-6 overflow-y-auto space-y-6">
                {/* Section 1: Ringkasan Status Pegawai (5 cards: TANPA Pensiun) */}
                <div className="space-y-2.5">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Users className="w-4 h-4 text-emerald-600" />
                      <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Status Kepegawaian Aktif
                      </h4>
                    </div>
                    {statusFilter !== 'All' && (
                      <button
                        type="button"
                        onClick={() => setStatusFilter('All')}
                        className="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 underline cursor-pointer"
                      >
                        Tampilkan Semua Status
                      </button>
                    )}
                  </div>

                  <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    {/* 1. Semua Pegawai Aktif */}
                    <button
                      type="button"
                      onClick={() => setStatusFilter('All')}
                      className={`p-3 rounded-2xl border text-left transition-all cursor-pointer ${
                        statusFilter === 'All'
                          ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/30'
                          : 'bg-white border-slate-200 hover:border-slate-300'
                      }`}
                    >
                      <div className="flex items-center justify-between text-slate-500 mb-1">
                        <span className="text-[11px] font-semibold text-slate-700">Semua Pegawai</span>
                        <Users className="w-3.5 h-3.5 text-slate-400" />
                      </div>
                      <div className="text-xl font-black text-slate-900">{statusStats.total}</div>
                      <p className="text-[10px] text-slate-500 mt-0.5">Pegawai aktif bertugas</p>
                    </button>

                    {/* 2. Aktif */}
                    <button
                      type="button"
                      onClick={() => setStatusFilter(statusFilter === 'Aktif' ? 'All' : 'Aktif')}
                      className={`p-3 rounded-2xl border text-left transition-all cursor-pointer ${
                        statusFilter === 'Aktif'
                          ? 'border-emerald-600 ring-2 ring-emerald-500/20 bg-emerald-50/30'
                          : 'bg-white border-slate-200 hover:border-emerald-300'
                      }`}
                    >
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[11px] font-semibold text-emerald-700 flex items-center gap-1">
                          <span className="w-2 h-2 rounded-full bg-emerald-500" />
                          <span>Aktif</span>
                        </span>
                        <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded">
                          {((statusStats.aktif / (statusStats.total || 1)) * 100).toFixed(0)}%
                        </span>
                      </div>
                      <div className="text-xl font-black text-emerald-600">{statusStats.aktif}</div>
                      <p className="text-[10px] text-slate-500 mt-0.5">Wajib Fit & Berhak WFA</p>
                    </button>

                    {/* 3. Tugas Belajar */}
                    <button
                      type="button"
                      onClick={() => setStatusFilter(statusFilter === 'Tugas Belajar' ? 'All' : 'Tugas Belajar')}
                      className={`p-3 rounded-2xl border text-left transition-all cursor-pointer ${
                        statusFilter === 'Tugas Belajar'
                          ? 'border-sky-600 ring-2 ring-sky-500/20 bg-sky-50/30'
                          : 'bg-white border-slate-200 hover:border-sky-300'
                      }`}
                    >
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[11px] font-semibold text-sky-700 flex items-center gap-1">
                          <span className="w-2 h-2 rounded-full bg-sky-500" />
                          <span>Tugas Belajar</span>
                        </span>
                        <span className="text-[10px] font-bold text-sky-600">Bebas Fit</span>
                      </div>
                      <div className="text-xl font-black text-sky-700">{statusStats.tugasBelajar}</div>
                      <p className="text-[10px] text-slate-500 mt-0.5">Studi lanjut / Non-WFA</p>
                    </button>

                    {/* 4. Cuti Melahirkan */}
                    <button
                      type="button"
                      onClick={() => setStatusFilter(statusFilter === 'Cuti Melahirkan' ? 'All' : 'Cuti Melahirkan')}
                      className={`p-3 rounded-2xl border text-left transition-all cursor-pointer ${
                        statusFilter === 'Cuti Melahirkan'
                          ? 'border-purple-600 ring-2 ring-purple-500/20 bg-purple-50/30'
                          : 'bg-white border-slate-200 hover:border-purple-300'
                      }`}
                    >
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[11px] font-semibold text-purple-700 flex items-center gap-1">
                          <span className="w-2 h-2 rounded-full bg-purple-500" />
                          <span>Cuti Melahirkan</span>
                        </span>
                        <span className="text-[10px] font-bold text-purple-600">Bebas Fit</span>
                      </div>
                      <div className="text-xl font-black text-purple-700">{statusStats.cutiMelahirkan}</div>
                      <p className="text-[10px] text-slate-500 mt-0.5">Maternity / Non-WFA</p>
                    </button>

                    {/* 5. Cuti Besar */}
                    <button
                      type="button"
                      onClick={() => setStatusFilter(statusFilter === 'Cuti Besar' ? 'All' : 'Cuti Besar')}
                      className={`p-3 rounded-2xl border text-left transition-all cursor-pointer ${
                        statusFilter === 'Cuti Besar'
                          ? 'border-amber-600 ring-2 ring-amber-500/20 bg-amber-50/30'
                          : 'bg-white border-slate-200 hover:border-amber-300'
                      }`}
                    >
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[11px] font-semibold text-amber-700 flex items-center gap-1">
                          <span className="w-2 h-2 rounded-full bg-amber-500" />
                          <span>Cuti Besar</span>
                        </span>
                        <span className="text-[10px] font-bold text-amber-600">Bebas Fit</span>
                      </div>
                      <div className="text-xl font-black text-amber-700">{statusStats.cutiBesar}</div>
                      <p className="text-[10px] text-slate-500 mt-0.5">Cuti panjang / Non-WFA</p>
                    </button>
                  </div>
                </div>

                {/* Section 2: Formasi per Masing-Masing Jabatan */}
                <div className="space-y-3 pt-4 border-t border-slate-100">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                      <Briefcase className="w-4 h-4 text-indigo-600" />
                      <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Rincian Formasi Jabatan ({specificJabatanList.length} Jabatan)
                      </h4>
                    </div>

                    {/* Quick Search inside Modal */}
                    <div className="relative min-w-[220px] sm:min-w-[260px]">
                      <Search className="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                      <input
                        type="text"
                        value={jabatanSearch}
                        onChange={(e) => setJabatanSearch(e.target.value)}
                        placeholder="Cari jabatan (lektor, plp, dll)..."
                        className="w-full pl-8 pr-7 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 transition-all"
                      />
                      {jabatanSearch && (
                        <button
                          onClick={() => setJabatanSearch('')}
                          className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                        >
                          <X className="w-3 h-3" />
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Filter Category Tabs */}
                  <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1">
                    <button
                      type="button"
                      onClick={() => setJabatanTab('All')}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer ${
                        jabatanTab === 'All'
                          ? 'bg-slate-900 text-white shadow-xs'
                          : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                      }`}
                    >
                      Semua Jabatan ({jabatanGroupTotals.total})
                    </button>

                    <button
                      type="button"
                      onClick={() => setJabatanTab('Dosen')}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer ${
                        jabatanTab === 'Dosen'
                          ? 'bg-indigo-600 text-white shadow-xs'
                          : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'
                      }`}
                    >
                      <GraduationCap className="w-3.5 h-3.5" />
                      <span>Jenjang Dosen ({jabatanGroupTotals.dosen})</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => setJabatanTab('JFU')}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer ${
                        jabatanTab === 'JFU'
                          ? 'bg-blue-600 text-white shadow-xs'
                          : 'bg-blue-50 text-blue-700 hover:bg-blue-100'
                      }`}
                    >
                      <Briefcase className="w-3.5 h-3.5" />
                      <span>Pelaksana / JFU ({jabatanGroupTotals.jfu})</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => setJabatanTab('PLP')}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer ${
                        jabatanTab === 'PLP'
                          ? 'bg-teal-600 text-white shadow-xs'
                          : 'bg-teal-50 text-teal-700 hover:bg-teal-100'
                      }`}
                    >
                      <FlaskConical className="w-3.5 h-3.5" />
                      <span>Laboran / PLP ({jabatanGroupTotals.plp})</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => setJabatanTab('JFT')}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer ${
                        jabatanTab === 'JFT'
                          ? 'bg-purple-600 text-white shadow-xs'
                          : 'bg-purple-50 text-purple-700 hover:bg-purple-100'
                      }`}
                    >
                      <Award className="w-3.5 h-3.5" />
                      <span>Fungsional Tertentu / JFT ({jabatanGroupTotals.jft})</span>
                    </button>
                  </div>

                  {/* Grid of Specific Jabatan Cards */}
                  <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                    {visibleJabatanCards.map((item) => {
                      const isSelected = selectedJabatanId === item.id;
                      const ItemIcon = item.icon || Briefcase;

                      return (
                        <button
                          key={item.id}
                          type="button"
                          onClick={() => {
                            if (isSelected) {
                              setSelectedJabatanId(null);
                            } else {
                              setSelectedJabatanId(item.id);
                              setIsDashboardModalOpen(false); // Langsung tutup modal & terapkan filter
                            }
                          }}
                          className={`p-3.5 rounded-2xl border text-left transition-all relative overflow-hidden group cursor-pointer ${
                            isSelected
                              ? 'border-indigo-600 ring-2 ring-indigo-500/30 shadow-md bg-indigo-50/50'
                              : 'bg-white border-slate-200 hover:border-indigo-300 hover:shadow-xs'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-1.5 gap-1">
                            <span className={`inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border ${item.badgeClass}`}>
                              <ItemIcon className="w-3 h-3" />
                              <span>{item.groupLabel}</span>
                            </span>
                            <span className="text-[10px] font-black text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                              {item.percent}%
                            </span>
                          </div>

                          <div className="flex items-baseline justify-between mt-1">
                            <span className="text-2xl font-black text-slate-900 group-hover:text-indigo-600 transition-colors">
                              {item.count}
                            </span>
                            <span className="text-[11px] font-bold text-slate-500">Pegawai</span>
                          </div>

                          <p className="text-xs font-bold text-slate-900 mt-1 line-clamp-1 group-hover:text-indigo-900" title={item.name}>
                            {item.name}
                          </p>

                          <p className="text-[10px] text-slate-500 mt-0.5 line-clamp-1" title={item.description}>
                            {item.description}
                          </p>

                          {isSelected && (
                            <div className="mt-2 pt-1.5 border-t border-indigo-200 flex items-center justify-between text-[10px] font-bold text-indigo-700">
                              <span className="flex items-center gap-1">
                                <CheckCircle2 className="w-3 h-3 text-indigo-600" />
                                <span>Filter Aktif</span>
                              </span>
                              <span className="underline">Hapus</span>
                            </div>
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Modal Footer */}
              <div className="p-4 sm:p-5 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div className="text-xs text-slate-500">
                  {selectedJabatanId && activeSelectedJabatan ? (
                    <span>
                      Sedang menyaring: <strong>{activeSelectedJabatan.name}</strong> ({activeSelectedJabatan.count} pegawai).
                    </span>
                  ) : (
                    <span>Pilih salah satu kartu jabatan untuk langsung memfilter tabel pegawai.</span>
                  )}
                </div>

                <div className="flex items-center gap-2 self-end sm:self-auto">
                  {selectedJabatanId && (
                    <button
                      type="button"
                      onClick={() => setSelectedJabatanId(null)}
                      className="px-3 py-1.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition-all cursor-pointer"
                    >
                      Reset Filter Jabatan
                    </button>
                  )}
                  <button
                    type="button"
                    onClick={() => setIsDashboardModalOpen(false)}
                    className="px-4 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-all cursor-pointer shadow-xs"
                  >
                    Tutup Dashboard
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
};

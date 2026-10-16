import React, { useState, useMemo, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  CheckCircle2,
  Clock,
  AlertCircle,
  Search,
  Filter,
  ExternalLink,
  Check,
  X,
  RotateCcw,
  Trash2,
  Download,
  Calendar,
  User,
  MapPin,
  FileText,
  Building2,
  Briefcase,
  Sparkles,
  ChevronDown,
  RefreshCw,
  FileSpreadsheet,
  MessageCircle,
  PhoneCall,
  FileDown,
  BarChart3,
  Layers,
  Pencil,
} from 'lucide-react';
import { WfaSubmission, WfaValidationStatus } from '../types';
import { WfaPdfExportModal } from './WfaPdfExportModal';
import { WfaDashboardModal } from './WfaDashboardModal';

// Date filter utility helpers
const formatDateIndo = (dateStr: string): string => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Format tanggal panjang Indonesia: "01 Oktober 2026"
const formatDateIndoLong = (dateStr: string): string => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
};

// Helper avatar inisial pegawai
const getInitials = (name: string): string => {
  if (!name) return 'PG';
  const clean = name.replace(/^(dr|drg|prof|ir|apt)\.?\s+/i, '').trim();
  const parts = clean.split(/\s+/).filter((p) => !p.includes('.') && p.length > 1);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return clean.slice(0, 2).toUpperCase() || 'PG';
};


interface RejectPreset {
  icon: string;
  label: string;
  shortLabel: string;
  text: string;
}

const WFA_REJECT_PRESETS: RejectPreset[] = [
  {
    icon: '✍️',
    label: 'Surat Belum Ditandatangani Direktur',
    shortLabel: 'Belum TTD',
    text: 'Surat tugas belum ditandatangani oleh Direktur / Pimpinan.',
  },
  {
    icon: '📍',
    label: 'Lokasi Bimbingan Tidak Sesuai Kebijakan Direktur',
    shortLabel: 'Lokasi Luar Ketentuan',
    text: 'Lokasi bimbingan tidak sesuai ketentuan kebijakan Direktur untuk mendapatkan WFA.',
  },
  {
    icon: '🔒',
    label: 'Link Surat Tugas Tidak Bisa Dibuka',
    shortLabel: 'Link Terkunci',
    text: 'Tautan/link surat tugas tidak dapat dibuka (izin akses Google Drive belum dibuka / link rusak).',
  },
  {
    icon: '📅',
    label: 'Tanggal / Lokasi Tidak Sesuai',
    shortLabel: 'Tgl/Lokasi Beda',
    text: 'Tanggal pelaksanaan atau lokasi kegiatan pada surat tugas tidak sesuai dengan formulir pengajuan.',
  },
  {
    icon: '📎',
    label: 'Berkas Lampiran Kurang Lengkap',
    shortLabel: 'Berkas Kurang',
    text: 'Lampiran dokumen atau berkas pendukung surat tugas belum lengkap / salah unggah dokumen.',
  },
  {
    icon: '📋',
    label: 'Kegiatan Bimbingan Tidak Tercantum',
    shortLabel: 'Belum di ST',
    text: 'Nama kegiatan bimbingan tidak tercantum dalam surat tugas resmi yang dilampirkan.',
  },
  {
    icon: '⚠️',
    label: 'Jadwal Bentrok / Kuota WFA Penuh',
    shortLabel: 'Kuota/Bentrok',
    text: 'Jadwal pengajuan bentrok dengan agenda kedinasan lain atau telah melampaui ketentuan kuota WFA.',
  },
];

interface WfaMonitoringViewProps {
  submissions: WfaSubmission[];
  onUpdateStatus: (id: string, status: WfaValidationStatus, notes?: string) => Promise<{ success: boolean; error?: string }>;
  onUpdateLink?: (id: string, newLink: string) => Promise<{ success: boolean; linkSuratTugas?: string; error?: string }>;
  onDeleteSubmission?: (id: string) => Promise<{ success: boolean; error?: string }>;
  onRefresh?: () => void;
  osdmContactWa?: string;
  onUpdateOsdmContactWa?: (newWa: string) => void;
}

export const WfaMonitoringView: React.FC<WfaMonitoringViewProps> = ({
  submissions,
  onUpdateStatus,
  onUpdateLink,
  onDeleteSubmission,
  onRefresh,
  osdmContactWa = '08119712525',
  onUpdateOsdmContactWa,
}) => {
  const [isPdfExportModalOpen, setIsPdfExportModalOpen] = useState(false);
  const [isDashboardModalOpen, setIsDashboardModalOpen] = useState(false);

  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<'All' | WfaValidationStatus>('Menunggu Validasi');
  const [lokasiFilter, setLokasiFilter] = useState<string>('All');
  const [jenisWfaFilter, setJenisWfaFilter] = useState<string>('All');
  const [filterDate, setFilterDate] = useState<string>('');
  const [isProcessingId, setIsProcessingId] = useState<string | null>(null);
  const [actionFeedback, setActionFeedback] = useState<{ id: string; message: string; type: 'success' | 'error' } | null>(null);

  // Auto-dismiss action feedback floating toast after 3 seconds
  useEffect(() => {
    if (!actionFeedback) return;
    const timer = setTimeout(() => {
      setActionFeedback(null);
    }, 3000);
    return () => clearTimeout(timer);
  }, [actionFeedback]);


  
  // Rejection notes modal state
  const [rejectModalItem, setRejectModalItem] = useState<WfaSubmission | null>(null);
  const [rejectReason, setRejectReason] = useState('');

  // Edit Link Surat Tugas modal state
  const [editLinkItem, setEditLinkItem] = useState<WfaSubmission | null>(null);
  const [editLinkValue, setEditLinkValue] = useState('');
  const [isSavingLink, setIsSavingLink] = useState(false);
  const [editLinkError, setEditLinkError] = useState('');

  const handleOpenEditLink = (sub: WfaSubmission) => {
    setEditLinkItem(sub);
    setEditLinkValue(sub.linkSuratTugas || '');
    setEditLinkError('');
  };

  const handleSaveEditLink = async () => {
    if (!editLinkItem || !onUpdateLink) return;
    const trimmed = editLinkValue.trim();
    if (!trimmed) {
      setEditLinkError('Link surat tugas tidak boleh kosong.');
      return;
    }
    setIsSavingLink(true);
    setEditLinkError('');
    try {
      const res = await onUpdateLink(editLinkItem.id, trimmed);
      if (res.success) {
        setActionFeedback({
          id: editLinkItem.id,
          message: `Link surat tugas ${editLinkItem.employeeName} berhasil diperbarui!`,
          type: 'success',
        });
        setEditLinkItem(null);
      } else {
        setEditLinkError(res.error || 'Gagal menyimpan perubahan link.');
      }
    } catch (err: any) {
      setEditLinkError(err?.message || 'Terjadi kesalahan sistem.');
    } finally {
      setIsSavingLink(false);
    }
  };

  // Delete confirmation
  const [deleteConfirmId, setDeleteConfirmId] = useState<string | null>(null);

  // WhatsApp modal state for sending messages to employee
  const [waModalItem, setWaModalItem] = useState<WfaSubmission | null>(null);
  const [waTargetPhone, setWaTargetPhone] = useState('');
  const [waCustomMessage, setWaCustomMessage] = useState('');
  const [waPresetType, setWaPresetType] = useState<'kekurangan' | 'kesalahan' | 'konfirmasi' | 'validasi' | 'custom'>('kekurangan');
  const [waPhoneError, setWaPhoneError] = useState<string | null>(null);

  // Format WhatsApp phone number (08... -> 628...)
  const formatWaPhone = (phone: string): string => {
    let clean = phone.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
      clean = '62' + clean.substring(1);
    } else if (clean.startsWith('8')) {
      clean = '62' + clean;
    }
    return clean;
  };

  // Generate template message for admin
  const getWaTemplate = (
    type: 'kekurangan' | 'kesalahan' | 'konfirmasi' | 'validasi' | 'custom',
    sub: WfaSubmission,
    extraNotes?: string
  ): string => {
    const nama = sub.employeeName || 'Bapak/Ibu';
    const tanggal = sub.tanggalWfa;
    const kegiatan = sub.namaKegiatan;
    const lokasi = `${sub.lokasiKegiatan}${sub.lokasiLahanBimbingan ? ' (' + sub.lokasiLahanBimbingan + ')' : ''}`;

    switch (type) {
      case 'kekurangan':
        return `Yth. ${nama},\n\nSehubungan dengan pengajuan WFA Bimbingan Anda untuk tanggal ${tanggal} di ${lokasi} (Kegiatan: ${kegiatan}), Tim Kerja OSDM Poltekkes Kemenkes Bandung mendapati adanya kekurangan berkas / persyaratan:\n\n- ${extraNotes || '[Tuliskan rincian berkas atau kekurangan persyaratan di sini, misal: lembar pengesahan surat tugas belum bertanda tangan / tautan belum bisa dibuka]'}\n\nMohon segera melengkapi kekurangan persyaratan tersebut agar pengajuan jadwal WFA Anda dapat kami validasi.\n\nTerima kasih.\nTim Kerja OSDM Poltekkes Kemenkes Bandung`;

      case 'kesalahan':
        return `Yth. ${nama},\n\nMohon maaf, pengajuan WFA Bimbingan Anda untuk tanggal ${tanggal} di ${lokasi} belum dapat disetujui karena terdapat kesalahan data / tidak sesuai ketentuan:\n\n- ${extraNotes || sub.catatanPengelola || '[Tuliskan kesalahan atau alasan revisi di sini]'}\n\nSilakan lakukan perbaikan atau pengajuan ulang melalui aplikasi WFA Bimbingan. Terima kasih.\nTim Kerja OSDM Poltekkes Kemenkes Bandung`;

      case 'konfirmasi':
        return `Yth. ${nama},\n\nTim Kerja OSDM Poltekkes Kemenkes Bandung ingin mengonfirmasi terkait pengajuan WFA Bimbingan Anda untuk tanggal ${tanggal} di ${lokasi} (Kegiatan: ${kegiatan}).\n\nMohon konfirmasinya terkait kesesuaian berkas surat tugas dan jadwal kehadiran bimbingan Anda.\n\nTerima kasih.\nTim Kerja OSDM Poltekkes Kemenkes Bandung`;

      case 'validasi':
        return `Yth. ${nama},\n\nPengajuan WFA Bimbingan Anda untuk tanggal ${tanggal} di ${lokasi} (Kegiatan: ${kegiatan}, Status: ${sub.statusWfa}) telah divalidasi dan DINYATAKAN VALID / DISETUJUI oleh Tim Kerja OSDM Poltekkes Kemenkes Bandung.\n\nData telah tercatat di sistem jadwal WFA. Selamat bertugas.\n\nTerima kasih.\nTim Kerja OSDM Poltekkes Kemenkes Bandung`;

      default:
        return extraNotes || '';
    }
  };

  // Open WhatsApp Messenger Modal
  const handleOpenWaModal = (
    sub: WfaSubmission,
    defaultPreset?: 'kekurangan' | 'kesalahan' | 'konfirmasi' | 'validasi',
    extraNotes?: string
  ) => {
    let preset = defaultPreset;
    if (!preset) {
      if (sub.status === 'Ditolak') preset = 'kesalahan';
      else if (sub.status === 'Valid') preset = 'validasi';
      else preset = 'kekurangan';
    }

    setWaModalItem(sub);
    setWaTargetPhone(sub.nomorWa || '');
    setWaPresetType(preset);
    setWaPhoneError(null);
    setWaCustomMessage(getWaTemplate(preset, sub, extraNotes));
  };

  // Change active preset inside WA modal
  const handleChangeWaPreset = (preset: 'kekurangan' | 'kesalahan' | 'konfirmasi' | 'validasi') => {
    if (!waModalItem) return;
    setWaPresetType(preset);
    setWaCustomMessage(getWaTemplate(preset, waModalItem));
  };

  // Open WhatsApp Web/App
  const handleSendWa = () => {
    if (!waModalItem) return;
    const cleanPhone = formatWaPhone(waTargetPhone);
    if (cleanPhone.length < 9) {
      setWaPhoneError('Nomor WhatsApp tidak valid. Masukkan minimal 9-14 digit angka.');
      return;
    }

    const url = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(waCustomMessage)}`;
    window.open(url, '_blank');
    setActionFeedback({
      id: waModalItem.id,
      message: `Tautan WhatsApp ke ${waModalItem.employeeName} (${cleanPhone}) berhasil dibuka!`,
      type: 'success',
    });
    setWaModalItem(null);
    setTimeout(() => setActionFeedback(null), 3500);
  };

  // List of distinct Jenis WFA options
  const jenisWfaOptions = useMemo(() => {
    const defaults = ['WFA Datang', 'WFA Pulang', 'WFA Datang & WFA Pulang'];
    const dynamic = submissions
      .map((s) => s.statusWfa?.trim())
      .filter((v): v is string => Boolean(v) && !defaults.includes(v));
    return [...defaults, ...Array.from(new Set(dynamic))];
  }, [submissions]);

  // Filtered submissions
  const filteredSubmissions = useMemo(() => {
    return submissions.filter((sub) => {
      // Search matches
      const query = searchQuery.toLowerCase().trim();
      const matchSearch =
        !query ||
        sub.nip.toLowerCase().includes(query) ||
        sub.employeeName.toLowerCase().includes(query) ||
        sub.namaKegiatan.toLowerCase().includes(query) ||
        (sub.unitKerja && sub.unitKerja.toLowerCase().includes(query));

      // Status matches
      const matchStatus = statusFilter === 'All' || sub.status === statusFilter;

      // Lokasi matches
      const matchLokasi = lokasiFilter === 'All' || sub.lokasiKegiatan === lokasiFilter;

      // Jenis WFA matches
      const matchJenisWfa =
        jenisWfaFilter === 'All' ||
        sub.statusWfa?.trim() === jenisWfaFilter?.trim();

      // Date matches (per hari berdasarkan tanggal pelaksanaan WFA)
      let matchDate = true;
      if (filterDate) {
        matchDate = sub.tanggalWfa === filterDate;
      }

      return matchSearch && matchStatus && matchLokasi && matchJenisWfa && matchDate;
    });
  }, [submissions, searchQuery, statusFilter, lokasiFilter, jenisWfaFilter, filterDate]);

  const handleResetAllFilters = () => {
    setSearchQuery('');
    setStatusFilter('Menunggu Validasi');
    setLokasiFilter('All');
    setJenisWfaFilter('All');
    setFilterDate('');
  };

  const isAnyFilterActive = Boolean(
    searchQuery ||
    statusFilter !== 'Menunggu Validasi' ||
    lokasiFilter !== 'All' ||
    jenisWfaFilter !== 'All' ||
    filterDate
  );

  // KPI Counts
  const totalCount = submissions.length;
  const pendingCount = submissions.filter((s) => s.status === 'Menunggu Validasi').length;
  const validCount = submissions.filter((s) => s.status === 'Valid').length;
  const rejectedCount = submissions.filter((s) => s.status === 'Ditolak').length;

  // Handle Quick Validate
  const handleValidate = async (id: string) => {
    setIsProcessingId(id);
    setActionFeedback(null);
    try {
      const res = await onUpdateStatus(id, 'Valid');
      if (res.success) {
        setActionFeedback({
          id,
          message: 'Status berhasil divalidasi menjadi "Valid dan Sudah terjadwal WFA"!',
          type: 'success',
        });
      } else {
        setActionFeedback({
          id,
          message: res.error || 'Gagal memvalidasi pengajuan.',
          type: 'error',
        });
      }
    } finally {
      setIsProcessingId(null);
      setTimeout(() => setActionFeedback(null), 3500);
    }
  };

  // Handle Reset to Pending
  const handleResetToPending = async (id: string) => {
    setIsProcessingId(id);
    setActionFeedback(null);
    try {
      const res = await onUpdateStatus(id, 'Menunggu Validasi');
      if (res.success) {
        setActionFeedback({
          id,
          message: 'Status berhasil dikembalikan ke "Menunggu Validasi".',
          type: 'success',
        });
      }
    } finally {
      setIsProcessingId(null);
      setTimeout(() => setActionFeedback(null), 3000);
    }
  };

  // Handle Rejection Submit
  const handleConfirmReject = async () => {
    if (!rejectModalItem) return;
    setIsProcessingId(rejectModalItem.id);
    try {
      const res = await onUpdateStatus(rejectModalItem.id, 'Ditolak', rejectReason);
      if (res.success) {
        setActionFeedback({
          id: rejectModalItem.id,
          message: 'Status pengajuan telah ditandai Ditolak/Revisi.',
          type: 'success',
        });
        setRejectModalItem(null);
        setRejectReason('');
      }
    } finally {
      setIsProcessingId(null);
      setTimeout(() => setActionFeedback(null), 3000);
    }
  };

  // Handle Delete
  const handleDelete = async (id: string) => {
    if (!onDeleteSubmission) return;
    setIsProcessingId(id);
    try {
      const res = await onDeleteSubmission(id);
      setDeleteConfirmId(null);
      if (res?.success) {
        setActionFeedback({
          id,
          message: 'Data pengajuan berhasil dihapus permanen dari server & sinkron ke tampilan pegawai.',
          type: 'success',
        });
      }
    } finally {
      setIsProcessingId(null);
      setTimeout(() => setActionFeedback(null), 3500);
    }
  };

  // Export CSV
  const handleExportCSV = () => {
    if (filteredSubmissions.length === 0) {
      alert('Tidak ada data pengajuan untuk diekspor.');
      return;
    }

    const headers = [
      'ID Pengajuan',
      'NIP',
      'Nama Pegawai',
      'Nomor WhatsApp Pegawai',
      'Unit Kerja',
      'Jabatan',
      'Tanggal WFA',
      'Nama Kegiatan',
      'Lokasi Kegiatan',
      'Lokasi Lahan Bimbingan',
      'Status WFA',
      'Link Surat Tugas',
      'Status Validasi',
      'Waktu Validasi',
      'Petugas Validasi',
      'Catatan Pengelola',
      'Waktu Pengajuan'
    ];

    const rows = filteredSubmissions.map((sub) => [
      `"${sub.id}"`,
      `"${sub.nip}"`,
      `"${sub.employeeName.replace(/"/g, '""')}"`,
      `"${(sub.nomorWa || '-').replace(/"/g, '""')}"`,
      `"${(sub.unitKerja || '').replace(/"/g, '""')}"`,
      `"${(sub.jabatan || '').replace(/"/g, '""')}"`,
      `"${formatDateIndoLong(sub.tanggalWfa)}"`,
      `"${sub.namaKegiatan.replace(/"/g, '""')}"`,
      `"${sub.lokasiKegiatan}"`,
      `"${(sub.lokasiLahanBimbingan || '').replace(/"/g, '""')}"`,
      `"${sub.statusWfa}"`,
      `"${sub.linkSuratTugas}"`,
      `"${sub.status}"`,
      `"${sub.validatedAt || '-'}"`,
      `"${sub.validatedBy || '-'}"`,
      `"${(sub.catatanPengelola || '').replace(/"/g, '""')}"`,
      `"${sub.createdAt}"`
    ]);

    const csvContent = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n');
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `rekap_wfa_bimbingan_poltekkes_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="space-y-6">
      {/* Top Banner & Title */}
      <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold mb-1">
            <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
            <span>Manajemen OSDM Terpadu • Poltekkes Kemenkes Bandung</span>
          </div>
          <h2 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Monitoring & Administrasi WFA Bimbingan
          </h2>
          <p className="text-xs sm:text-sm text-slate-500 mt-0.5">
            Verifikasi pengajuan WFA dan unduh laporan resmi format PDF atau CSV.
          </p>
        </div>

        <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
          {onRefresh && (
            <button
              onClick={onRefresh}
              className="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition-all shadow-xs"
              title="Muat Ulang Data"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Muat Ulang</span>
            </button>
          )}

          <button
            onClick={() => setIsDashboardModalOpen(true)}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-bold transition-all shadow-xs shadow-indigo-100"
            title="Buka Dashboard Statistik & Grafik Pengajuan WFA"
          >
            <BarChart3 className="w-3.5 h-3.5" />
            <span>Dashboard & Grafik</span>
          </button>

          <button
            onClick={() => setIsPdfExportModalOpen(true)}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-rose-200 hover:bg-rose-50 text-rose-700 hover:border-rose-300 text-xs font-bold transition-all shadow-xs cursor-pointer"
            title="Download Laporan Resmi Dashboard WFA Bimbingan format PDF (Harian, Mingguan, Bulanan, Tahunan)"
          >
            <FileDown className="w-3.5 h-3.5 text-rose-600" />
            <span>Download Laporan PDF</span>
          </button>

          <button
            onClick={handleExportCSV}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all shadow-xs"
            title="Unduh Rekap CSV"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span className="hidden sm:inline">Rekap CSV</span>
          </button>
        </div>
      </div>

      {/* KPI Cards (Interactive Quick Filters) */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Total */}
        <button
          type="button"
          onClick={() => setStatusFilter('All')}
          className={`p-4 rounded-2xl text-left transition-all cursor-pointer relative overflow-hidden group ${
            statusFilter === 'All'
              ? 'bg-blue-50/90 border-2 border-blue-500 shadow-md ring-2 ring-blue-400/20'
              : 'bg-white border border-slate-200 hover:border-blue-300 hover:bg-blue-50/30 shadow-xs'
          }`}
          title="Klik untuk menampilkan semua pengajuan WFA"
        >
          <div className="flex items-center justify-between">
            <p className="text-xs font-bold text-slate-700 group-hover:text-blue-700 transition-colors">
              Total Pengajuan
            </p>
            <div
              className={`p-1 rounded-lg transition-colors ${
                statusFilter === 'All'
                  ? 'bg-blue-200/80 text-blue-700'
                  : 'bg-slate-100 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-600'
              }`}
            >
              <Layers className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline justify-between mt-2">
            <span className="text-2xl font-black text-slate-900">{totalCount}</span>
            <span
              className={`text-[11px] font-bold px-2 py-0.5 rounded-full transition-colors ${
                statusFilter === 'All'
                  ? 'bg-blue-600 text-white'
                  : 'text-blue-700 bg-blue-100/80 group-hover:bg-blue-200'
              }`}
            >
              Semua Berkas
            </span>
          </div>
        </button>

        {/* Menunggu Validasi (Highlighted) */}
        <button
          type="button"
          onClick={() => setStatusFilter('Menunggu Validasi')}
          className={`p-4 rounded-2xl text-left transition-all cursor-pointer relative overflow-hidden group ${
            statusFilter === 'Menunggu Validasi'
              ? 'bg-amber-50 border-2 border-amber-500 shadow-md ring-2 ring-amber-400/20'
              : 'bg-amber-50/70 border border-amber-200 hover:border-amber-400 hover:bg-amber-50/90 shadow-xs'
          }`}
          title="Klik untuk memfilter pengajuan yang Menunggu Validasi"
        >
          <div className="flex items-center justify-between">
            <p className="text-xs font-bold text-amber-800 group-hover:text-amber-900 transition-colors">
              Menunggu Validasi
            </p>
            <div
              className={`p-1 rounded-lg transition-colors ${
                statusFilter === 'Menunggu Validasi'
                  ? 'bg-amber-200 text-amber-800'
                  : 'bg-amber-100 text-amber-600 group-hover:bg-amber-200'
              }`}
            >
              <Clock className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline justify-between mt-2">
            <span className="text-2xl font-black text-amber-900">{pendingCount}</span>
            <span
              className={`text-[11px] font-bold px-2 py-0.5 rounded-full transition-colors ${
                statusFilter === 'Menunggu Validasi'
                  ? 'bg-amber-600 text-white'
                  : 'text-amber-800 bg-amber-200/90 group-hover:bg-amber-300'
              }`}
            >
              Perlu Tindakan
            </span>
          </div>
        </button>

        {/* Valid (Disetujui) */}
        <button
          type="button"
          onClick={() => setStatusFilter('Valid')}
          className={`p-4 rounded-2xl text-left transition-all cursor-pointer relative overflow-hidden group ${
            statusFilter === 'Valid'
              ? 'bg-emerald-50 border-2 border-emerald-500 shadow-md ring-2 ring-emerald-400/20'
              : 'bg-emerald-50/70 border border-emerald-200 hover:border-emerald-400 hover:bg-emerald-50/90 shadow-xs'
          }`}
          title="Klik untuk memfilter pengajuan yang Sudah Valid"
        >
          <div className="flex items-center justify-between">
            <p className="text-xs font-bold text-emerald-800 group-hover:text-emerald-900 transition-colors">
              Sudah Valid
            </p>
            <div
              className={`p-1 rounded-lg transition-colors ${
                statusFilter === 'Valid'
                  ? 'bg-emerald-200 text-emerald-800'
                  : 'bg-emerald-100 text-emerald-600 group-hover:bg-emerald-200'
              }`}
            >
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline justify-between mt-2">
            <span className="text-2xl font-black text-emerald-900">{validCount}</span>
            <span
              className={`text-[11px] font-bold px-2 py-0.5 rounded-full transition-colors ${
                statusFilter === 'Valid'
                  ? 'bg-emerald-600 text-white'
                  : 'text-emerald-800 bg-emerald-200/90 group-hover:bg-emerald-300'
              }`}
            >
              Terjadwal WFA
            </span>
          </div>
        </button>

        {/* Ditolak */}
        <button
          type="button"
          onClick={() => setStatusFilter('Ditolak')}
          className={`p-4 rounded-2xl text-left transition-all cursor-pointer relative overflow-hidden group ${
            statusFilter === 'Ditolak'
              ? 'bg-rose-50 border-2 border-rose-500 shadow-md ring-2 ring-rose-400/20'
              : 'bg-rose-50/70 border border-rose-200 hover:border-rose-400 hover:bg-rose-50/90 shadow-xs'
          }`}
          title="Klik untuk memfilter pengajuan yang Ditolak / Perlu Revisi"
        >
          <div className="flex items-center justify-between">
            <p className="text-xs font-bold text-rose-800 group-hover:text-rose-900 transition-colors">
              Ditolak / Revisi
            </p>
            <div
              className={`p-1 rounded-lg transition-colors ${
                statusFilter === 'Ditolak'
                  ? 'bg-rose-200 text-rose-800'
                  : 'bg-rose-100 text-rose-600 group-hover:bg-rose-200'
              }`}
            >
              <AlertCircle className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline justify-between mt-2">
            <span className="text-2xl font-black text-rose-900">{rejectedCount}</span>
            <span
              className={`text-[11px] font-bold px-2 py-0.5 rounded-full transition-colors ${
                statusFilter === 'Ditolak'
                  ? 'bg-rose-600 text-white'
                  : 'text-rose-800 bg-rose-200/90 group-hover:bg-rose-300'
              }`}
            >
              Dikembalikan
            </span>
          </div>
        </button>
      </div>



      {/* Floating Action Toast Notification (Top-Center) */}
      <AnimatePresence>
        {actionFeedback && (
          <motion.div
            initial={{ opacity: 0, y: -24, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -16, scale: 0.95 }}
            transition={{ duration: 0.22, ease: 'easeOut' }}
            className="fixed top-6 left-1/2 -translate-x-1/2 z-[100] max-w-lg w-[92%] sm:w-auto pointer-events-auto"
          >
            <div
              className={`px-4 py-3 rounded-2xl shadow-2xl backdrop-blur-md border flex items-center gap-3 text-xs sm:text-sm font-semibold transition-all ${
                actionFeedback.type === 'success'
                  ? 'bg-slate-950/95 border-emerald-500/50 text-white shadow-emerald-950/40 ring-1 ring-emerald-500/20'
                  : 'bg-slate-950/95 border-rose-500/50 text-white shadow-rose-950/40 ring-1 ring-rose-500/20'
              }`}
            >
              <div
                className={`w-7 h-7 rounded-xl flex items-center justify-center shrink-0 ${
                  actionFeedback.type === 'success'
                    ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                    : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                }`}
              >
                {actionFeedback.type === 'success' ? (
                  <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                ) : (
                  <AlertCircle className="w-4 h-4 text-rose-400" />
                )}
              </div>

              <span className="leading-snug pr-2 text-slate-100">{actionFeedback.message}</span>

              <button
                type="button"
                onClick={() => setActionFeedback(null)}
                className="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors ml-auto cursor-pointer shrink-0"
                title="Tutup notifikasi"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Filter and Search Bar */}
      <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5">
          {/* Search Box - dibuat lebih pendek dan rapi */}
          <div className="sm:col-span-2 lg:col-span-3 relative">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              type="text"
              placeholder="Cari NIP, nama..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition-all placeholder:text-slate-400"
              title="Cari NIP, nama pegawai, kegiatan, atau unit kerja"
            />
            {searchQuery && (
              <button
                type="button"
                onClick={() => setSearchQuery('')}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded transition-colors"
                title="Hapus pencarian"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          {/* Filter Tanggal Pelaksanaan WFA (Per Hari) */}
          <div className="sm:col-span-1 lg:col-span-2">
            <div className="flex items-center gap-1.5 px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus-within:border-indigo-500 focus-within:bg-white transition-all relative">
              <Calendar className="w-3.5 h-3.5 text-slate-400 shrink-0 pointer-events-none" />
              <input
                type="date"
                value={filterDate}
                onChange={(e) => setFilterDate(e.target.value)}
                onClick={(e) => {
                  try {
                    e.currentTarget.showPicker();
                  } catch {}
                }}
                className="w-full bg-transparent text-xs text-slate-800 font-semibold focus:outline-none cursor-pointer py-0 min-w-0 [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:w-0 [&::-webkit-calendar-picker-indicator]:p-0"
                title="Filter pengajuan berdasarkan tanggal pelaksanaan WFA per hari"
              />
              {filterDate && (
                <button
                  type="button"
                  onClick={() => setFilterDate('')}
                  className="text-slate-400 hover:text-slate-600 p-0.5 rounded hover:bg-slate-200/70 transition-colors shrink-0 cursor-pointer"
                  title="Hapus filter tanggal WFA"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          </div>

          {/* Status Filter */}
          <div className="sm:col-span-1 lg:col-span-3">
            <div className="flex items-center gap-1.5 px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus-within:border-indigo-500 focus-within:bg-white transition-all">
              <Filter className="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value as any)}
                className="w-full bg-transparent text-xs text-slate-800 font-medium focus:outline-none cursor-pointer py-0 truncate"
              >
                <option value="All">Semua Status ({submissions.length})</option>
                <option value="Menunggu Validasi">⏳ Menunggu Validasi ({pendingCount})</option>
                <option value="Valid">✓ Valid & Terjadwal ({validCount})</option>
                <option value="Ditolak">✕ Ditolak ({rejectedCount})</option>
              </select>
            </div>
          </div>

          {/* Filter Jenis WFA (Pilihan Pegawai) */}
          <div className="sm:col-span-1 lg:col-span-2">
            <div className="flex items-center gap-1.5 px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus-within:border-indigo-500 focus-within:bg-white transition-all">
              <Briefcase className="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <select
                value={jenisWfaFilter}
                onChange={(e) => setJenisWfaFilter(e.target.value)}
                className="w-full bg-transparent text-xs text-slate-800 font-medium focus:outline-none cursor-pointer py-0 truncate"
                title="Filter berdasarkan jenis WFA yang dipilih pegawai"
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
          </div>

          {/* Lokasi Filter */}
          <div className="sm:col-span-1 lg:col-span-2">
            <div className="flex items-center gap-1.5 px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus-within:border-indigo-500 focus-within:bg-white transition-all">
              <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <select
                value={lokasiFilter}
                onChange={(e) => setLokasiFilter(e.target.value)}
                className="w-full bg-transparent text-xs text-slate-800 font-medium focus:outline-none focus:border-indigo-500 cursor-pointer py-0 truncate"
                title="Filter berdasarkan lokasi kegiatan"
              >
                <option value="All">Semua Lokasi</option>
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

        {/* Filter Summary Tags */}
        <div className="flex items-center justify-between text-xs text-slate-500 pt-1 flex-wrap gap-2">
          <div className="flex items-center gap-2 flex-wrap">
            <span>
              Menampilkan <strong>{filteredSubmissions.length}</strong> dari {submissions.length} pengajuan
            </span>
            {statusFilter !== 'All' && (
              <span
                className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${
                  statusFilter === 'Menunggu Validasi'
                    ? 'bg-amber-50 text-amber-800 border-amber-200'
                    : statusFilter === 'Valid'
                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                    : 'bg-rose-50 text-rose-800 border-rose-200'
                }`}
              >
                <span>
                  Status: {statusFilter === 'Menunggu Validasi' ? '⏳ Menunggu Validasi' : statusFilter === 'Valid' ? '✓ Valid' : '✕ Ditolak'}
                </span>
                <button
                  type="button"
                  onClick={() => setStatusFilter('All')}
                  className="hover:opacity-75 p-0.5 rounded-full cursor-pointer ml-0.5"
                  title="Tampilkan Semua Status"
                >
                  <X className="w-2.5 h-2.5" />
                </button>
              </span>
            )}
            {filterDate && (
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[11px] font-semibold border border-indigo-200">
                <Calendar className="w-3 h-3" />
                <span>Tgl WFA: {formatDateIndo(filterDate)}</span>
                <button
                  type="button"
                  onClick={() => setFilterDate('')}
                  className="hover:text-indigo-900 p-0.5 rounded-full cursor-pointer"
                  title="Hapus filter tanggal WFA"
                >
                  <X className="w-2.5 h-2.5" />
                </button>
              </span>
            )}
            {jenisWfaFilter !== 'All' && (
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 text-[11px] font-semibold border border-teal-200">
                <Briefcase className="w-3 h-3" />
                <span>Jenis: {jenisWfaFilter === 'WFA Datang & WFA Pulang' ? 'Datang & Pulang' : jenisWfaFilter}</span>
                <button
                  type="button"
                  onClick={() => setJenisWfaFilter('All')}
                  className="hover:text-teal-900 p-0.5 rounded-full cursor-pointer"
                  title="Hapus filter jenis WFA"
                >
                  <X className="w-2.5 h-2.5" />
                </button>
              </span>
            )}
            {lokasiFilter !== 'All' && (
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-semibold border border-slate-200">
                <MapPin className="w-3 h-3" />
                <span>Lokasi: {lokasiFilter}</span>
                <button
                  type="button"
                  onClick={() => setLokasiFilter('All')}
                  className="hover:text-slate-900 p-0.5 rounded-full cursor-pointer"
                  title="Hapus filter lokasi"
                >
                  <X className="w-2.5 h-2.5" />
                </button>
              </span>
            )}
          </div>
          {isAnyFilterActive && (
            <button
              type="button"
              onClick={handleResetAllFilters}
              className="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline cursor-pointer"
            >
              Reset Semua Filter
            </button>
          )}
        </div>
      </div>

      {/* Submissions Table / Cards */}
      {filteredSubmissions.length === 0 ? (
        <div className="p-12 text-center rounded-3xl bg-white border border-slate-200 shadow-xs space-y-3">
          <div className="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
            <FileText className="w-7 h-7" />
          </div>
          <h3 className="text-base font-bold text-slate-800">Tidak ada data pengajuan yang cocok</h3>
          <p className="text-xs text-slate-500 max-w-sm mx-auto">
            {isAnyFilterActive
              ? 'Silakan sesuaikan kata kunci pencarian, filter status, jenis WFA, lokasi, atau filter tanggal WFA Anda.'
              : 'Belum ada pegawai yang mengajukan jadwal WFA Bimbingan.'}
          </p>
          {isAnyFilterActive && (
            <button
              type="button"
              onClick={handleResetAllFilters}
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition-all border border-indigo-200 mt-2"
            >
              <RotateCcw className="w-3.5 h-3.5" />
              <span>Reset Semua Filter</span>
            </button>
          )}
        </div>
      ) : (
        <div className="space-y-3">
          {filteredSubmissions.map((sub) => {
            const isProcessing = isProcessingId === sub.id;

            return (
              <motion.div
                key={sub.id}
                layout
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                className={`p-4 sm:p-5 rounded-2xl border transition-all duration-200 shadow-xs hover:shadow-md space-y-3.5 ${
                  sub.status === 'Valid'
                    ? 'bg-white border-slate-200 border-l-4 border-l-emerald-500'
                    : sub.status === 'Menunggu Validasi'
                    ? 'bg-gradient-to-r from-amber-50/40 via-white to-white border-slate-200 border-l-4 border-l-amber-500'
                    : 'bg-slate-50/70 border-slate-200 border-l-4 border-l-rose-500'
                }`}
              >
                {/* Header: Status, Tanggal & Opsi A: Tombol Hapus Terpisah di Pojok Kanan Atas */}
                <div className="flex items-center justify-between gap-3 pb-2.5 border-b border-slate-100">
                  <div className="flex items-center gap-2 flex-wrap">
                    {sub.status === 'Valid' && (
                      <span className="inline-flex items-center gap-1.5 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full bg-emerald-600 text-white font-bold text-[10px] sm:text-[11px] uppercase tracking-wider shadow-xs order-1">
                        <CheckCircle2 className="w-3 h-3 sm:w-3.5 sm:h-3.5 shrink-0" />
                        <span>VALID & TERJADWAL WFA</span>
                      </span>
                    )}
                    {sub.status === 'Menunggu Validasi' && (
                      <span className="inline-flex items-center gap-1.5 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full bg-amber-500 text-white font-bold text-[10px] sm:text-[11px] uppercase tracking-wider shadow-xs order-1">
                        <Clock className="w-3 h-3 sm:w-3.5 sm:h-3.5 shrink-0" />
                        <span>MENUNGGU VALIDASI</span>
                      </span>
                    )}
                    {sub.status === 'Ditolak' && (
                      <span className="inline-flex items-center gap-1.5 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full bg-rose-600 text-white font-bold text-[10px] sm:text-[11px] uppercase tracking-wider shadow-xs order-1">
                        <AlertCircle className="w-3 h-3 sm:w-3.5 sm:h-3.5 shrink-0" />
                        <span>DITOLAK / REVISI</span>
                      </span>
                    )}

                    {/* Info Tanggal Pengajuan: Di mobile tampil di samping badge status dengan teks kecil (order-2), di desktop tampil setelah tanggal WFA (sm:order-3) */}
                    {sub.createdAt && (
                      <span className="text-[9.5px] sm:text-[11px] text-slate-400 font-medium order-2 sm:order-3 whitespace-nowrap">
                        Diajukan: {new Date(sub.createdAt).toLocaleString('id-ID')}
                      </span>
                    )}

                    <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-md order-3 sm:order-2">
                      <Calendar className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                      <span>Tanggal WFA: <strong className="text-slate-900">{formatDateIndoLong(sub.tanggalWfa)}</strong></span>
                    </span>
                  </div>

                  {/* OPSI A: Tombol Hapus dipisah ke pojok kanan atas agar aman dari salah klik */}
                  {onDeleteSubmission && (
                    <button
                      onClick={() => setDeleteConfirmId(sub.id)}
                      disabled={isProcessing}
                      className="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors cursor-pointer shrink-0"
                      title="Hapus data pengajuan secara permanen"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  )}
                </div>

                {/* Body Utama: Profil Pegawai & Tombol Aksi Verifikasi */}
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                  {/* Profil Pegawai */}
                  <div className="flex items-start gap-3 min-w-0">
                    <div className={`w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center font-bold text-xs sm:text-sm shrink-0 shadow-2xs ${
                      sub.status === 'Valid'
                        ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                        : sub.status === 'Menunggu Validasi'
                        ? 'bg-amber-100 text-amber-900 border border-amber-200'
                        : 'bg-slate-100 text-slate-700 border border-slate-200'
                    }`}>
                      {getInitials(sub.employeeName)}
                    </div>

                    <div className="space-y-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <h4 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-tight">
                          {sub.employeeName}
                        </h4>
                        <span className="font-mono text-xs font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                          NIP: {sub.nip}
                        </span>
                        {sub.nomorWa ? (
                          <button
                            type="button"
                            onClick={() => handleOpenWaModal(sub)}
                            className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-mono font-bold text-xs border border-emerald-200 transition-colors shadow-2xs"
                            title="Klik untuk mengirim pesan WhatsApp ke pegawai"
                          >
                            <MessageCircle className="w-3.5 h-3.5 text-emerald-600" />
                            <span>WA: {sub.nomorWa}</span>
                          </button>
                        ) : (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-400 font-medium text-[11px] border border-slate-200">
                            <span>WA: Belum dicantumkan</span>
                          </span>
                        )}
                      </div>

                      {(sub.unitKerja || sub.jabatan) && (
                        <p className="text-xs text-slate-500 flex items-center gap-1.5 flex-wrap">
                          {sub.unitKerja && (
                            <span className="inline-flex items-center gap-1 font-medium">
                              <Building2 className="w-3 h-3 text-slate-400" />
                              {sub.unitKerja}
                            </span>
                          )}
                          {sub.jabatan && (
                            <span className="text-slate-400 font-normal">
                              • {sub.jabatan}
                            </span>
                          )}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Tombol Aksi Verifikasi: Hanya Validasi & Tolak (Fokus & Ergonomis) */}
                  <div className="flex items-center gap-2 shrink-0 self-end lg:self-center flex-wrap">
                    {sub.status !== 'Valid' ? (
                      <button
                        onClick={() => handleValidate(sub.id)}
                        disabled={isProcessing}
                        className={`flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm ${
                          isProcessing
                            ? 'bg-slate-300 text-slate-500 cursor-not-allowed'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white hover:shadow-md hover:shadow-emerald-600/25 active:scale-95 cursor-pointer'
                        }`}
                        title="Validasi pengajuan ini menjadi status Valid"
                      >
                        <Check className="w-4 h-4" />
                        <span>Validasi</span>
                      </button>
                    ) : (
                      <button
                        onClick={() => handleResetToPending(sub.id)}
                        disabled={isProcessing}
                        className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-all border border-slate-200 cursor-pointer"
                        title="Batalkan validasi dan kembalikan ke antrean"
                      >
                        <RotateCcw className="w-3.5 h-3.5" />
                        <span>Batalkan Validasi</span>
                      </button>
                    )}

                    {sub.status !== 'Ditolak' && (
                      <button
                        onClick={() => {
                          setRejectModalItem(sub);
                          setRejectReason(sub.catatanPengelola || '');
                        }}
                        disabled={isProcessing}
                        className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 active:scale-95 text-rose-700 hover:text-rose-800 border border-rose-200 text-xs font-bold transition-all shadow-2xs cursor-pointer"
                        title="Tolak pengajuan atau minta revisi berkas"
                      >
                        <X className="w-3.5 h-3.5 text-rose-600" />
                        <span>Tolak</span>
                      </button>
                    )}
                  </div>
                </div>

                {/* Blok Kegiatan & Detail Pengajuan */}
                <div className="rounded-xl bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200/70 overflow-hidden">
                  {/* Nama Kegiatan — area tersendiri */}
                  <div className="flex items-start gap-2.5 px-3.5 py-3 border-l-[3px] border-l-indigo-400">
                    <Briefcase className="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
                    <div className="min-w-0">
                      <span className="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Kegiatan</span>
                      <p className="text-xs sm:text-sm font-semibold text-slate-800 leading-relaxed mt-0.5 break-words">
                        {sub.namaKegiatan}
                      </p>
                    </div>
                  </div>

                  {/* Detail Lokasi & Status */}
                  <div className="flex items-center gap-2 text-xs flex-wrap px-3.5 py-2.5 bg-white/60 border-t border-slate-200/50">
                    <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white text-slate-800 font-semibold border border-slate-200 shadow-2xs">
                      <MapPin className="w-3.5 h-3.5 text-rose-500" />
                      <span>{sub.lokasiKegiatan}</span>
                    </span>

                    {sub.lokasiLahanBimbingan && (
                      <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white text-emerald-800 font-semibold border border-emerald-200 shadow-2xs">
                        <Building2 className="w-3.5 h-3.5 text-emerald-600" />
                        <span>Lahan: <strong>{sub.lokasiLahanBimbingan}</strong></span>
                      </span>
                    )}

                    <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white text-teal-800 font-bold border border-teal-200 shadow-2xs">
                      <span className="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                      <span>Pilihan: {sub.statusWfa}</span>
                    </span>

                    <div className="flex items-center gap-1.5 flex-wrap sm:ml-auto">
                      {sub.linkSuratTugas ? (
                        <a
                          href={sub.linkSuratTugas}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="inline-flex items-center gap-1 px-3 py-1 rounded-md bg-sky-50 hover:bg-sky-100 text-sky-700 hover:text-sky-900 font-semibold border border-sky-200 transition-colors shadow-2xs text-xs"
                          title="Buka dokumen surat tugas di tab baru"
                        >
                          <ExternalLink className="w-3.5 h-3.5" />
                          <span>Buka Surat Tugas</span>
                        </a>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-100 text-slate-400 font-medium text-xs border border-slate-200">
                          Belum ada link ST
                        </span>
                      )}

                      {onUpdateLink && (
                        <button
                          type="button"
                          onClick={() => handleOpenEditLink(sub)}
                          className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-50 hover:bg-amber-100 text-amber-800 hover:text-amber-900 font-semibold border border-amber-200 transition-colors shadow-2xs text-xs cursor-pointer"
                          title="Perbaiki / edit link surat tugas jika ada salah ketik atau typo"
                        >
                          <Pencil className="w-3.5 h-3.5 text-amber-600" />
                          <span>Edit Link</span>
                        </button>
                      )}
                    </div>
                  </div>
                </div>

                {/* Audit Info if Validated or Rejected */}
                {sub.validatedAt && sub.status === 'Valid' && (
                  <p className="text-[11px] text-emerald-700 font-medium pt-0.5">
                    ✓ Disetujui oleh: {sub.validatedBy || 'Tim OSDM Poltekkes'} ({new Date(sub.validatedAt).toLocaleString('id-ID')})
                  </p>
                )}
                {sub.validatedAt && sub.status === 'Ditolak' && (
                  <p className="text-[11px] text-rose-700 font-medium pt-0.5">
                    ✗ Ditolak oleh: {sub.validatedBy || 'Tim OSDM Poltekkes'} ({new Date(sub.validatedAt).toLocaleString('id-ID')})
                  </p>
                )}

                {/* Rejection Notes */}
                {sub.status === 'Ditolak' && sub.catatanPengelola && (
                  <div className="p-2.5 rounded-xl bg-rose-100/60 border border-rose-200 text-rose-900 text-xs">
                    <p className="font-bold">Catatan Pengelola:</p>
                    <p className="mt-0.5">{sub.catatanPengelola}</p>
                  </div>
                )}
              </motion.div>
            );
          })}
        </div>
      )}

      {/* Reject Modal */}
      {rejectModalItem && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
          <div className="w-full max-w-lg bg-white rounded-3xl p-5 shadow-2xl space-y-3 border border-slate-200 text-slate-900 animate-in fade-in zoom-in-95 duration-150">
            {/* Header */}
            <div className="flex items-center justify-between pb-2 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold shrink-0">
                  ✕
                </div>
                <div>
                  <h3 className="text-sm sm:text-base font-bold text-slate-900 leading-tight">
                    Tolak / Minta Revisi Pengajuan WFA
                  </h3>
                </div>
              </div>
              <button
                onClick={() => setRejectModalItem(null)}
                className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center text-xs transition-colors shrink-0"
              >
                ✕
              </button>
            </div>

            {/* Info Pegawai Ringkas */}
            <div className="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200/80 text-xs flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
              <div className="flex items-center gap-1.5 min-w-0">
                <span className="text-slate-500">Pegawai:</span>
                <strong className="text-slate-900 font-bold truncate">{rejectModalItem.employeeName}</strong>
              </div>
              <div className="flex items-center gap-1.5 shrink-0">
                <span className="text-slate-500">Tgl WFA:</span>
                <strong className="text-slate-900 font-bold">{rejectModalItem.tanggalWfa}</strong>
              </div>
              {rejectModalItem.namaKegiatan && (
                <div className="w-full text-slate-600 text-[11px] truncate pt-1 border-t border-slate-200/60">
                  <span className="text-slate-400 mr-1">Kegiatan:</span>
                  <span>{rejectModalItem.namaKegiatan}</span>
                </div>
              )}
            </div>

            {/* Menu Pintasan Cepat (Smart Dropdown + Tag Ringkas) */}
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-800 flex items-center gap-1">
                  <span className="text-amber-500 font-bold">⚡</span>
                  <span>Menu Pintasan Alasan Cepat:</span>
                </label>
                {rejectReason && (
                  <button
                    type="button"
                    onClick={() => setRejectReason('')}
                    className="text-[11px] text-rose-600 hover:text-rose-800 font-semibold transition-colors"
                  >
                    Kosongkan Catatan
                  </button>
                )}
              </div>

              {/* Dropdown Menu Cerdas */}
              <div className="relative">
                <select
                  value={WFA_REJECT_PRESETS.some(p => p.text === rejectReason) ? rejectReason : ''}
                  onChange={(e) => {
                    if (e.target.value) setRejectReason(e.target.value);
                  }}
                  className="w-full pl-3 pr-8 py-2 text-xs font-semibold bg-slate-50 hover:bg-slate-100/70 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white focus:ring-1 focus:ring-rose-300 transition-all cursor-pointer shadow-2xs truncate"
                >
                  <option value="">-- Pilih Menu Alasan Penolakan (7 Pilihan Cepat) --</option>
                  {WFA_REJECT_PRESETS.map((item, idx) => (
                    <option key={idx} value={item.text}>
                      {item.icon} {item.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Tombol Tag Cepat 1-Klik Ringkas */}
              <div className="flex flex-wrap gap-1 pt-0.5 max-h-20 overflow-y-auto">
                {WFA_REJECT_PRESETS.map((item, idx) => {
                  const isSelected = rejectReason === item.text;
                  return (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => setRejectReason(item.text)}
                      className={`text-[10px] px-2 py-1 rounded-lg font-semibold transition-all border shrink-0 flex items-center gap-1 ${
                        isSelected
                          ? 'bg-rose-100 text-rose-800 border-rose-400 font-bold shadow-2xs'
                          : 'bg-white hover:bg-slate-100 text-slate-600 border-slate-200'
                      }`}
                      title={item.text}
                    >
                      <span>{item.icon}</span>
                      <span>{item.shortLabel}</span>
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Textarea Catatan */}
            <div className="space-y-1">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-700">
                  Alasan Penolakan / Catatan untuk Pegawai:
                </label>
                <span className="text-[10px] text-slate-400 italic">Dapat disunting bebas</span>
              </div>
              <textarea
                rows={2}
                placeholder="Pilih dari menu di atas atau ketik manual catatan revisi..."
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:border-rose-500 focus:bg-white transition-all resize-none leading-relaxed"
              />
              <p className="text-[10px] text-slate-500 leading-tight">
                💡 Catatan otomatis masuk ke draft pesan WhatsApp jika klik <strong className="text-rose-600">Tolak &amp; Kirim WA</strong>.
              </p>
            </div>

            {/* Action Buttons */}
            <div className="flex items-center justify-end gap-2 pt-1 flex-wrap border-t border-slate-100">
              <button
                onClick={() => setRejectModalItem(null)}
                className="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors"
              >
                Batal
              </button>
              <button
                onClick={handleConfirmReject}
                className="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow-xs transition-colors"
              >
                Tandai Ditolak Saja
              </button>
              <button
                onClick={async () => {
                  const targetItem = rejectModalItem;
                  const reason = rejectReason;
                  await handleConfirmReject();
                  handleOpenWaModal(targetItem, 'kesalahan', reason);
                }}
                className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors"
              >
                <MessageCircle className="w-3.5 h-3.5" />
                <span>Tolak &amp; Kirim WA Pegawai</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: KIRIM PESAN WHATSAPP KE PEGAWAI */}
      {waModalItem && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
          <div className="w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-200 text-slate-900 overflow-hidden my-6">
            {/* Modal Header with WhatsApp theme */}
            <div className="bg-emerald-700 text-white p-5 flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <div className="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center text-white">
                  <MessageCircle className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-base font-black">
                    Kirim Pesan WhatsApp ke Pegawai
                  </h3>
                  <p className="text-xs text-emerald-100">
                    Sampaikan informasi, kesalahan data, atau kekurangan persyaratan pengajuan
                  </p>
                </div>
              </div>
              <button
                onClick={() => setWaModalItem(null)}
                className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm font-bold transition-colors"
              >
                ✕
              </button>
            </div>

            <div className="p-5 sm:p-6 space-y-4 max-h-[80vh] overflow-y-auto">
              {/* Pegawai Info Box */}
              <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1.5">
                <div className="flex justify-between items-baseline flex-wrap gap-1">
                  <span className="text-slate-500 font-medium">Nama Pegawai:</span>
                  <span className="font-bold text-slate-900 text-sm">{waModalItem.employeeName}</span>
                </div>
                <div className="flex justify-between items-baseline flex-wrap gap-1">
                  <span className="text-slate-500 font-medium">NIP Pegawai:</span>
                  <span className="font-mono text-slate-800 font-semibold">{waModalItem.nip}</span>
                </div>
                <div className="flex justify-between items-baseline flex-wrap gap-1">
                  <span className="text-slate-500 font-medium">Tanggal Pelaksanaan WFA:</span>
                  <span className="font-semibold text-slate-800">{waModalItem.tanggalWfa}</span>
                </div>
                <div className="flex justify-between items-baseline flex-wrap gap-1">
                  <span className="text-slate-500 font-medium">Lokasi / Lahan:</span>
                  <span className="text-slate-700 font-medium">{waModalItem.lokasiKegiatan} ({waModalItem.lokasiLahanBimbingan || '-'})</span>
                </div>
                <div className="flex justify-between items-center pt-1 border-t border-slate-200">
                  <span className="text-slate-500 font-medium">Status Pengajuan Saat Ini:</span>
                  <span className={`px-2 py-0.5 rounded-full font-bold text-[11px] ${
                    waModalItem.status === 'Valid'
                      ? 'bg-emerald-100 text-emerald-800'
                      : waModalItem.status === 'Menunggu Validasi'
                      ? 'bg-amber-100 text-amber-800'
                      : 'bg-rose-100 text-rose-800'
                  }`}>
                    {waModalItem.status}
                  </span>
                </div>
              </div>

              {/* Nomor WhatsApp Input Field */}
              <div className="space-y-1">
                <div className="flex items-center justify-between">
                  <label className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <span>Nomor WhatsApp Tujuan Pegawai</span>
                    <span className="text-rose-500">*</span>
                  </label>
                  {waTargetPhone && (
                    <span className="text-[11px] font-mono text-emerald-600 font-semibold">
                      Tujuan: +{formatWaPhone(waTargetPhone)}
                    </span>
                  )}
                </div>
                <div className="relative">
                  <div className="absolute left-3.5 top-1/2 -translate-y-1/2 flex items-center gap-1 text-xs font-mono font-bold text-slate-400 pointer-events-none">
                    <span className="text-emerald-600">WA</span>
                    <span className="text-slate-300">|</span>
                  </div>
                  <input
                    type="tel"
                    placeholder="Contoh: 081234567890 atau 6281234567890"
                    value={waTargetPhone}
                    onChange={(e) => {
                      setWaTargetPhone(e.target.value);
                      setWaPhoneError(null);
                    }}
                    className={`w-full pl-14 pr-3.5 py-2.5 rounded-xl border text-xs sm:text-sm font-mono text-slate-900 focus:outline-none focus:border-emerald-500 ${
                      waPhoneError ? 'border-rose-400 bg-rose-50/40' : 'border-slate-300 bg-white'
                    }`}
                  />
                </div>
                {waPhoneError ? (
                  <p className="text-[11px] text-rose-600 font-medium">{waPhoneError}</p>
                ) : (
                  <p className="text-[11px] text-slate-400">
                    Nomor WhatsApp diambil otomatis dari formulir pengajuan pegawai atau dapat Anda sesuaikan.
                  </p>
                )}
              </div>

              {/* Quick Template Selector Chips */}
              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-800 block">
                  Pilih Format Pesan Cepat:
                </label>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <button
                    type="button"
                    onClick={() => handleChangeWaPreset('kekurangan')}
                    className={`p-2 rounded-xl text-left text-xs font-bold transition-all border ${
                      waPresetType === 'kekurangan'
                        ? 'bg-amber-500 text-white border-amber-600 shadow-xs'
                        : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                    }`}
                  >
                    ⚠️ Kekurangan Berkas
                  </button>

                  <button
                    type="button"
                    onClick={() => handleChangeWaPreset('kesalahan')}
                    className={`p-2 rounded-xl text-left text-xs font-bold transition-all border ${
                      waPresetType === 'kesalahan'
                        ? 'bg-rose-600 text-white border-rose-700 shadow-xs'
                        : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                    }`}
                  >
                    ❌ Kesalahan / Revisi
                  </button>

                  <button
                    type="button"
                    onClick={() => handleChangeWaPreset('konfirmasi')}
                    className={`p-2 rounded-xl text-left text-xs font-bold transition-all border ${
                      waPresetType === 'konfirmasi'
                        ? 'bg-sky-600 text-white border-sky-700 shadow-xs'
                        : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                    }`}
                  >
                    ℹ️ Konfirmasi Jadwal
                  </button>

                  <button
                    type="button"
                    onClick={() => handleChangeWaPreset('validasi')}
                    className={`p-2 rounded-xl text-left text-xs font-bold transition-all border ${
                      waPresetType === 'validasi'
                        ? 'bg-emerald-600 text-white border-emerald-700 shadow-xs'
                        : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                    }`}
                  >
                    ✅ WFA Disetujui
                  </button>
                </div>
              </div>

              {/* Textarea for WhatsApp message content */}
              <div className="space-y-1">
                <div className="flex items-center justify-between">
                  <label className="text-xs font-bold text-slate-800">
                    Isi Pesan WhatsApp (Dapat Diedit Bebas):
                  </label>
                  <span className="text-[11px] text-slate-400">
                    {waCustomMessage.length} karakter
                  </span>
                </div>
                <textarea
                  rows={6}
                  value={waCustomMessage}
                  onChange={(e) => {
                    setWaCustomMessage(e.target.value);
                    setWaPresetType('custom');
                  }}
                  placeholder="Ketik isi pesan WhatsApp untuk pegawai..."
                  className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-900 leading-relaxed focus:outline-none focus:border-emerald-500 font-sans"
                />
                <p className="text-[11px] text-slate-500 italic">
                  💡 Tips: Anda dapat mengubah atau menambahkan rincian berkas yang kurang langsung pada kotak pesan di atas sebelum menekan tombol kirim.
                </p>
              </div>

              {/* Modal Actions */}
              <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setWaModalItem(null)}
                  className="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleSendWa}
                  className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md hover:shadow-emerald-600/30 transition-all active:scale-95"
                >
                  <MessageCircle className="w-4 h-4" />
                  <span>Buka WhatsApp &amp; Kirim Pesan</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal Edit Link Surat Tugas */}
      {editLinkItem && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto">
          <div className="w-full max-w-lg bg-white rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4 border border-slate-200 text-slate-900 animate-in fade-in zoom-in-95 duration-150">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2.5">
                <div className="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-bold shrink-0">
                  <Pencil className="w-4 h-4" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-900 leading-tight">
                    Edit Link Surat Tugas
                  </h3>
                  <p className="text-xs text-slate-500">
                    Perbaiki tautan yang salah ketik / typo tanpa perlu menolak pengajuan
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setEditLinkItem(null)}
                className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center text-xs transition-colors shrink-0 cursor-pointer"
                title="Tutup dialog"
              >
                ✕
              </button>
            </div>

            {/* Info Pegawai */}
            <div className="px-3.5 py-2.5 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1.5">
              <div className="flex justify-between items-baseline flex-wrap gap-1">
                <span className="text-slate-500">Pegawai:</span>
                <span className="font-bold text-slate-900">{editLinkItem.employeeName}</span>
              </div>
              <div className="flex justify-between items-baseline flex-wrap gap-1">
                <span className="text-slate-500">NIP:</span>
                <span className="font-mono text-slate-700">{editLinkItem.nip}</span>
              </div>
              <div className="flex justify-between items-baseline flex-wrap gap-1">
                <span className="text-slate-500">Tanggal WFA:</span>
                <span className="font-semibold text-slate-800">{editLinkItem.tanggalWfa}</span>
              </div>
              <div className="pt-1 border-t border-slate-200/70 text-slate-600 truncate">
                <span className="text-slate-400 mr-1">Kegiatan:</span>
                <span className="font-medium text-slate-700">{editLinkItem.namaKegiatan}</span>
              </div>
            </div>

            {/* Form Input URL */}
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-800">
                  URL / Tautan Surat Tugas:
                </label>
                {editLinkValue.trim() && (
                  <a
                    href={editLinkValue.trim().startsWith('http') ? editLinkValue.trim() : `https://${editLinkValue.trim()}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-[11px] text-sky-600 hover:text-sky-800 font-semibold inline-flex items-center gap-1 hover:underline"
                    title="Uji coba buka link ini di tab baru untuk memastikan link sudah benar"
                  >
                    <ExternalLink className="w-3 h-3" />
                    <span>Tes Buka Tautan</span>
                  </a>
                )}
              </div>
              <input
                type="text"
                value={editLinkValue}
                onChange={(e) => {
                  setEditLinkValue(e.target.value);
                  setEditLinkError('');
                }}
                placeholder="https://drive.google.com/... atau https://srikandi.arsip.go.id/..."
                className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-xs sm:text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-200 font-mono transition-all"
                autoFocus
              />
              {editLinkError ? (
                <p className="text-[11px] text-rose-600 font-medium">{editLinkError}</p>
              ) : (
                <p className="text-[11px] text-slate-500">
                  💡 Awalan <code className="font-mono bg-slate-100 px-1 rounded text-slate-700">https://</code> akan ditambahkan otomatis jika tidak dicantumkan.
                </p>
              )}
            </div>

            {/* Action Buttons */}
            <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setEditLinkItem(null)}
                disabled={isSavingLink}
                className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleSaveEditLink}
                disabled={isSavingLink || !editLinkValue.trim()}
                className={`inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-white text-xs font-bold shadow-xs transition-all cursor-pointer ${
                  isSavingLink || !editLinkValue.trim()
                    ? 'bg-slate-300 cursor-not-allowed text-slate-500'
                    : 'bg-amber-600 hover:bg-amber-700 active:scale-95'
                }`}
              >
                {isSavingLink ? (
                  <>
                    <RotateCcw className="w-3.5 h-3.5 animate-spin" />
                    <span>Menyimpan...</span>
                  </>
                ) : (
                  <>
                    <Check className="w-3.5 h-3.5" />
                    <span>Simpan Perubahan Link</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {deleteConfirmId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
          <div className="w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl space-y-4 border border-slate-200 text-center">
            <div className="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <Trash2 className="w-6 h-6" />
            </div>
            <div className="space-y-1">
              <h3 className="text-base font-bold text-slate-900">Hapus Data Pengajuan?</h3>
              <p className="text-xs text-slate-500">
                Data pengajuan ini akan dihapus permanen dari sistem monitoring.
              </p>
            </div>
            <div className="flex items-center justify-center gap-2 pt-2">
              <button
                onClick={() => setDeleteConfirmId(null)}
                className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold"
              >
                Batal
              </button>
              <button
                onClick={() => handleDelete(deleteConfirmId)}
                className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold"
              >
                Hapus Permanen
              </button>
            </div>
          </div>
        </div>
      )}

      {/* PDF Export Time Filter Modal */}
      <WfaPdfExportModal
        isOpen={isPdfExportModalOpen}
        onClose={() => setIsPdfExportModalOpen(false)}
        submissions={submissions}
        initialStatusFilter={statusFilter}
        initialLokasiFilter={lokasiFilter}
        initialJenisWfaFilter={jenisWfaFilter}
      />

      {/* Analytics Dashboard & Charts Modal */}
      <WfaDashboardModal
        isOpen={isDashboardModalOpen}
        onClose={() => setIsDashboardModalOpen(false)}
        submissions={submissions}
      />
    </div>
  );
};

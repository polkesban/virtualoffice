import React, { useState, useRef, useEffect, useCallback, useMemo } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  Calendar,
  Clock,
  MapPin,
  CheckCircle2,
  Search,
  RotateCcw,
  Sparkles,
  AlertCircle,
  Building2,
  Briefcase,
  User,
  PenTool,
  Layers,
  FileText,
  Lock,
  Info,
} from 'lucide-react';
import { MeetingSession, MeetingAttendanceRecord, EmployeeRecord } from '../types';
import { findEmployeeByNip, searchEmployees } from '../data/employeeDatabase';
import { compressSignatureCanvas } from '../utils/imageOptimizer';
import { getMeetingAgendas, saveAgendaPermanently } from '../utils/meetingAgendaHelper';

interface MeetingAttendanceModalProps {
  isOpen: boolean;
  onClose: () => void;
  session: MeetingSession;
  onSubmitAttendance: (attendance: Omit<MeetingAttendanceRecord, 'id' | 'timestamp'>) => Promise<boolean> | boolean;
  existingAttendees?: MeetingAttendanceRecord[];
  onSwitchSession?: () => void;
  hasMultipleSessions?: boolean;
}

export const MeetingAttendanceModal: React.FC<MeetingAttendanceModalProps> = ({
  isOpen,
  onClose,
  session,
  onSubmitAttendance,
  existingAttendees = [],
  onSwitchSession,
  hasMultipleSessions = false,
}) => {
  const [nip, setNip] = useState('');
  const [name, setName] = useState('');
  const [unitKerja, setUnitKerja] = useState('');
  const [jabatan, setJabatan] = useState('');

  // Agenda list & selection
  const availableAgendas = useMemo(() => getMeetingAgendas(session), [session]);
  const [selectedAgendas, setSelectedAgendas] = useState<string[]>([]);

  useEffect(() => {
    if (availableAgendas.length > 0) {
      setSelectedAgendas(availableAgendas);
    }
  }, [availableAgendas]);

  // Autocomplete search helper
  const [searchKeyword, setSearchKeyword] = useState('');
  const [showSearchResults, setShowSearchResults] = useState(false);
  const [matchedEmployees, setMatchedEmployees] = useState<EmployeeRecord[]>([]);

  // Signature canvas state
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const [isDrawing, setIsDrawing] = useState(false);
  const [hasSignature, setHasSignature] = useState(false);

  // Status & submission feedback
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [submittedData, setSubmittedData] = useState<MeetingAttendanceRecord | null>(null);

  // Initialize Canvas DPI & sizing
  const initCanvas = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // High DPI scaling for ultra crisp signatures
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);

    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.lineWidth = 2.5;
    ctx.strokeStyle = '#0f172a'; // slate-900
  }, []);

  useEffect(() => {
    if (isOpen && !submittedData) {
      // Delay slightly for modal layout to render dimensions
      const timer = setTimeout(() => {
        initCanvas();
      }, 150);
      return () => clearTimeout(timer);
    }
  }, [isOpen, submittedData, initCanvas]);

  // Handle auto-population on NIP change
  useEffect(() => {
    if (!nip || nip.trim().length === 0) return;
    const found = findEmployeeByNip(nip);
    if (found) {
      setName(found.name);
      setUnitKerja(found.unitKerja);
      setJabatan(found.jabatan);
      setErrorMessage(null);
    }
  }, [nip]);

  // Handle employee search input
  const handleSearchChange = (kw: string) => {
    setSearchKeyword(kw);
    if (kw.trim().length >= 2) {
      const results = searchEmployees(kw);
      setMatchedEmployees(results.slice(0, 6));
      setShowSearchResults(true);
    } else {
      setMatchedEmployees([]);
      setShowSearchResults(false);
    }
  };

  const handleSelectEmployee = (emp: EmployeeRecord) => {
    setNip(emp.nip);
    setName(emp.name);
    setUnitKerja(emp.unitKerja);
    setJabatan(emp.jabatan);
    setShowSearchResults(false);
    setSearchKeyword('');
    setErrorMessage(null);
  };

  // Canvas drawing handlers (Touch & Mouse)
  const getCoordinates = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();

    if ('touches' in e) {
      const touch = e.touches[0];
      return {
        x: touch.clientX - rect.left,
        y: touch.clientY - rect.top,
      };
    } else {
      return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top,
      };
    }
  };

  const startDrawing = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const { x, y } = getCoordinates(e);
    ctx.beginPath();
    ctx.moveTo(x, y);
    setIsDrawing(true);
  };

  const draw = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    if (!isDrawing) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const { x, y } = getCoordinates(e);
    ctx.lineTo(x, y);
    ctx.stroke();
    setHasSignature(true);
  };

  const stopDrawing = () => {
    if (!isDrawing) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.closePath();
    setIsDrawing(false);
  };

  const clearCanvas = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const dpr = window.devicePixelRatio || 1;
    ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
    setHasSignature(false);
  };

  // Submit attendance
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    if (session.isActive === false) {
      setErrorMessage('Saat ini belum ada sesi rapat aktif yang dibuka. Silakan hubungi Tim Kerja OSDM jika menurut Anda ini keliru.');
      return;
    }

    // Form validation
    if (!name.trim()) {
      setErrorMessage('Nama pegawai/peserta wajib diisi.');
      return;
    }

    if (!unitKerja.trim()) {
      setErrorMessage('Unit kerja wajib diisi.');
      return;
    }

    if (!hasSignature || !canvasRef.current) {
      setErrorMessage('Mohon bubuhkan tanda tangan / paraf pada kotak yang telah disediakan.');
      return;
    }

    // 1. Validasi Ketat Kehadiran Ganda pada Agenda yang Sama (HARD REJECTION)
    const currentSessionAgenda = (session.agenda || session.title || 'Daftar Hadir Rapat').trim();
    const targetAgendas =
      selectedAgendas.length > 0
        ? selectedAgendas
        : availableAgendas.length > 0
        ? availableAgendas
        : [currentSessionAgenda];
    const cleanNip = nip.replace(/[\s.-]/g, '').toLowerCase().trim();
    const cleanName = name.replace(/\s+/g, ' ').toLowerCase().trim();

    // Helper untuk normalisasi teks agenda (menghilangkan spasi berlebih & case-insensitive)
    const normalizeAgendaStr = (s: string) => s.toLowerCase().replace(/[\s\-_.,()]+/g, ' ').trim();

    const existingDuplicate = existingAttendees.find((att) => {
      // Jika record presensi berasal dari rapat lain (meetingId berbeda), izinkan hadir di rapat ini
      if (att.meetingId && session.id && att.meetingId !== session.id) {
        return false;
      }

      // Cocokkan NIP (jika ada) atau Nama lengkap
      const matchNip = cleanNip && att.nip && att.nip.replace(/[\s.-]/g, '').toLowerCase().trim() === cleanNip;
      const matchName = cleanName && att.name && att.name.replace(/\s+/g, ' ').toLowerCase().trim() === cleanName;
      if (!matchNip && !matchName) return false;

      // Ambil agenda yang BENAR-BENAR diikuti oleh pegawai pada record tersebut
      // JANGAN PERNAH default ke availableAgendas jika kosong, agar tidak memalsukan agenda masa lalu!
      const attendeeAgendas: string[] = Array.isArray(att.agendas) ? att.agendas : [];

      if (attendeeAgendas.length > 0) {
        // Cek irisan agenda spesifik yang sama
        const normAttendeeAgendas = attendeeAgendas.map(normalizeAgendaStr);
        return targetAgendas.some((ag) => {
          const normAg = normalizeAgendaStr(ag);
          return normAttendeeAgendas.includes(normAg);
        });
      }

      // Jika data lama tidak memiliki field agendas tersimpan atau rapat tidak ada agenda spesifik:
      if (availableAgendas.length === 0 && att.meetingId === session.id) {
        return true;
      }
      return false;
    });

    if (existingDuplicate) {
      const attendeeAgendas: string[] = Array.isArray(existingDuplicate.agendas)
        ? existingDuplicate.agendas
        : [];
      const normAttendeeAgendas = attendeeAgendas.map(normalizeAgendaStr);
      const overlapAgendas = targetAgendas.filter((ag) =>
        normAttendeeAgendas.includes(normalizeAgendaStr(ag))
      );
      const overlapStr = overlapAgendas.join(', ') || 'agenda rapat ini';
      const timeStr = new Date(existingDuplicate.timestamp).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
      });

      setErrorMessage(
        `Presensi Ditolak: Pegawai atas nama "${existingDuplicate.name}"${
          existingDuplicate.nip ? ` (NIP. ${existingDuplicate.nip})` : ''
        } sudah tercatat hadir pada "${overlapStr}" pukul ${timeStr} WIB. Anda tidak dapat mengisi kehadiran ganda untuk agenda yang sama.`
      );
      return;
    }

    try {
      setIsSubmitting(true);
      const signatureDataUrl = compressSignatureCanvas(canvasRef.current);
      if (!signatureDataUrl) {
        setErrorMessage('Mohon bubuhkan tanda tangan / paraf pada kotak yang telah disediakan.');
        setIsSubmitting(false);
        return;
      }

      const attendanceData = {
        meetingId: session.id,
        nip: nip.trim(),
        name: name.trim(),
        unitKerja: unitKerja.trim(),
        jabatan: jabatan.trim(),
        signatureDataUrl,
        agendas: targetAgendas,
      };

      // Simpan agenda ini secara permanen ke daftar riwayat agenda master
      targetAgendas.forEach((ag) => saveAgendaPermanently(ag));
      if (session.agenda) {
        saveAgendaPermanently(session.agenda);
      }

      const success = await onSubmitAttendance(attendanceData);
      if (success !== false) {
        const fullRecord: MeetingAttendanceRecord = {
          ...attendanceData,
          id: `att-${Date.now()}`,
          timestamp: new Date().toISOString(),
        };
        setSubmittedData(fullRecord);
      }
    } catch (err: any) {
      setErrorMessage(err?.message || 'Gagal menyimpan kehadiran. Silakan coba kembali.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleResetForm = () => {
    setNip('');
    setName('');
    setUnitKerja('');
    setJabatan('');
    setSelectedAgendas(availableAgendas);
    setSubmittedData(null);
    setErrorMessage(null);
    setHasSignature(false);
    setTimeout(() => {
      initCanvas();
    }, 100);
  };

  if (!isOpen) return null;

  // Jika sesi rapat sedang tidak aktif / belum dibuka, tampilkan dialog pemberitahuan bersih tanpa header formulir
  if (session.isActive === false) {
    return (
      <AnimatePresence>
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: 15 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: 15 }}
            transition={{ duration: 0.2 }}
            className="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200 p-7 sm:p-8 text-center space-y-5"
          >
            {/* Icon Info */}
            <div className="w-16 h-16 rounded-2xl bg-teal-50 text-teal-700 border border-teal-200/80 mx-auto flex items-center justify-center shadow-xs">
              <Info className="w-8 h-8 text-teal-700" />
            </div>

            {/* Title & Description */}
            <div className="space-y-2">
              <h3 className="text-xl font-black text-slate-900 tracking-tight">
                Belum Ada Sesi Rapat Aktif
              </h3>
              <p className="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-sm mx-auto">
                Saat ini belum ada sesi atau agenda rapat yang sedang aktif. Silakan hubungi <strong className="text-slate-800">Tim Kerja OSDM</strong> jika menurut Anda ini keliru.
              </p>
            </div>

            {/* Actions */}
            <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2.5">
              {hasMultipleSessions && onSwitchSession && (
                <button
                  type="button"
                  onClick={onSwitchSession}
                  className="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition-colors cursor-pointer"
                >
                  Pilih Agenda Lain
                </button>
              )}
              <button
                type="button"
                onClick={onClose}
                className="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 transition-colors shadow-sm cursor-pointer"
              >
                Kembali ke menu
              </button>
            </div>
          </motion.div>
        </div>
      </AnimatePresence>
    );
  }

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5">
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 15 }}
          transition={{ duration: 0.2 }}
          className="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[92vh]"
        >
          {/* Top Header Banner (Ultra-Compact Horizontal Layout) */}
          <div className="bg-gradient-to-r from-teal-800 via-emerald-800 to-teal-900 text-white p-3.5 sm:p-4.5 relative shrink-0 overflow-hidden">
            {/* Subtle decorative background elements */}
            <div className="absolute inset-0 pointer-events-none">
              <div className="absolute -top-8 -right-8 w-32 h-32 bg-emerald-500/8 rounded-full blur-2xl" />
              <div className="absolute -bottom-6 -left-6 w-28 h-28 bg-teal-400/8 rounded-full blur-2xl" />
              <div className="absolute top-0 right-0 w-full h-px bg-gradient-to-r from-transparent via-emerald-400/25 to-transparent" />
            </div>

            {/* Row 1: Logo di kiri, DAFTAR HADIR RAPAT di sisi kanan sejajar logo, dan tombol Tutup */}
            <div className="flex items-center justify-between gap-3 relative">
              <div className="flex items-center gap-3 sm:gap-4 min-w-0">
                {/* Logo Poltekkes Kemenkes Bandung di sisi kiri */}
                <div className="bg-white px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl shadow-md shadow-black/15 border border-white/60 shrink-0 flex items-center justify-center ring-1 ring-white/20">
                  <img
                    src="./assets/logo-poltekkes.png"
                    alt="Logo Poltekkes Kemenkes Bandung"
                    className="h-7 sm:h-8.5 w-auto max-w-[150px] sm:max-w-[200px] object-contain"
                    onError={(e) => {
                      (e.target as HTMLImageElement).src =
                        'https://poltekkesbandung.ac.id/wp-content/uploads/2026/05/cropped-logo-transparan-2.png';
                    }}
                  />
                </div>

                {/* DAFTAR HADIR RAPAT sejajar di sisi kanan logo */}
                <div className="min-w-0">
                  <h2 className="text-sm sm:text-base md:text-lg font-black text-white tracking-tight leading-tight uppercase truncate drop-shadow-sm">
                    DAFTAR HADIR RAPAT
                  </h2>
                </div>
              </div>

              {/* Close button */}
              <button
                onClick={onClose}
                className="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/12 hover:bg-white/25 text-white/70 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0 backdrop-blur-sm border border-white/10"
                title="Tutup Formulir"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Row 2: Metadata Badges (Tanggal, Waktu, Lokasi) */}
            <div className="mt-2.5 flex items-center gap-1.5 sm:gap-2 flex-wrap text-[11px] sm:text-xs relative">
              <div className="inline-flex items-center gap-1 bg-emerald-500/30 text-emerald-100 border border-emerald-400/40 px-2.5 py-1 rounded-lg font-semibold shadow-sm backdrop-blur-md">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse" />
                <span>Sesi Aktif</span>
              </div>
              <div className="inline-flex items-center gap-1 bg-white/12 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/10 text-emerald-100 font-semibold shadow-sm">
                <Calendar className="w-3 h-3 text-emerald-300 shrink-0" />
                <span>{session.date}</span>
              </div>
              <div className="inline-flex items-center gap-1 bg-white/12 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/10 text-emerald-100 font-semibold shadow-sm">
                <Clock className="w-3 h-3 text-emerald-300 shrink-0" />
                <span>{session.timeStart || '09:00'} - {session.timeEnd || 'Selesai'} WIB</span>
              </div>
              {session.location && (
                <div
                  className="inline-flex items-center gap-1 bg-white/12 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/10 text-emerald-100 font-semibold shadow-sm max-w-xs sm:max-w-md truncate"
                  title={session.location}
                >
                  <MapPin className="w-3 h-3 text-emerald-300 shrink-0" />
                  <span className="truncate">{session.location}</span>
                </div>
              )}

            </div>

            {/* Row 3: Highlight AGENDA RAPAT (Ramping & Eye-Catching) */}
            {session.agenda && (
              <div className="mt-2.5 w-full bg-gradient-to-r from-emerald-500/15 via-teal-400/20 to-emerald-500/15 border border-emerald-400/30 rounded-xl px-3 py-1.5 flex items-center gap-2.5 text-xs sm:text-sm relative">
                <span className="px-2 py-0.5 rounded-md bg-emerald-400 text-emerald-950 font-black text-[10px] tracking-wider uppercase shrink-0 shadow-sm">
                  AGENDA RAPAT
                </span>
                <span className="text-white font-bold truncate text-xs sm:text-sm drop-shadow-sm">
                  {session.agenda}
                </span>
              </div>
            )}
          </div>

          {/* Modal Body */}
          <div className="p-4 sm:p-6 overflow-y-auto flex-1 space-y-4">
            {submittedData ? (
              /* Success Confirmation Card */
              <motion.div
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                className="text-center py-6 px-4 space-y-4"
              >
                <div className="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center shadow-lg shadow-emerald-500/20">
                  <CheckCircle2 className="w-10 h-10" />
                </div>
                <div>
                  <h3 className="text-xl font-extrabold text-slate-900">Kehadiran Berhasil Dicatat!</h3>
                  <p className="text-xs sm:text-sm text-slate-600 mt-1 max-w-md mx-auto">
                    Terima kasih, tanda tangan dan data kehadiran Anda telah tersimpan ke dalam rekapan presensi rapat ini.
                  </p>
                </div>

                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 max-w-md mx-auto text-left space-y-2 text-xs sm:text-sm">
                  <div className="flex justify-between border-b border-slate-200/80 pb-1.5">
                    <span className="text-slate-500 font-medium">Nama Peserta:</span>
                    <span className="font-bold text-slate-800">{submittedData.name}</span>
                  </div>
                  {submittedData.nip && (
                    <div className="flex justify-between border-b border-slate-200/80 pb-1.5">
                      <span className="text-slate-500 font-medium">NIP:</span>
                      <span className="font-mono font-bold text-slate-800">{submittedData.nip}</span>
                    </div>
                  )}
                  <div className="flex justify-between border-b border-slate-200/80 pb-1.5">
                    <span className="text-slate-500 font-medium">Unit Kerja:</span>
                    <span className="font-medium text-slate-800">{submittedData.unitKerja}</span>
                  </div>
                  <div className="flex justify-between border-b border-slate-200/80 pb-1.5">
                    <span className="text-slate-500 font-medium">Waktu Presensi:</span>
                    <span className="font-medium text-teal-700">
                      {new Date(submittedData.timestamp).toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                      })}{' '}
                      WIB
                    </span>
                  </div>
                  <div className="pt-2 flex items-center justify-between">
                    <span className="text-slate-500 font-medium">Paraf Tersimpan:</span>
                    <div className="h-10 w-24 bg-white border border-slate-200 rounded p-1 flex items-center justify-center">
                      <img src={submittedData.signatureDataUrl} alt="Paraf" className="max-h-full object-contain" />
                    </div>
                  </div>
                </div>

                <div className="pt-3 flex items-center justify-center gap-3">
                  <button
                    type="button"
                    onClick={handleResetForm}
                    className="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors"
                  >
                    Presensi Pegawai Lain
                  </button>
                  <button
                    type="button"
                    onClick={onClose}
                    className="px-5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 shadow-md transition-colors"
                  >
                    Selesai & Tutup
                  </button>
                </div>
              </motion.div>
            ) : (
              /* Presence Form */
              <form onSubmit={handleSubmit} className="space-y-4">
                {errorMessage && (
                  <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-2">
                    <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
                    <span>{errorMessage}</span>
                  </div>
                )}

                {/* Quick Autocomplete Search Helper */}
                <div className="bg-teal-50/70 border border-teal-100 rounded-xl p-3 space-y-2">
                  <label className="text-xs font-bold text-teal-950 flex items-center gap-1.5">
                    <Search className="w-3.5 h-3.5 text-teal-700" />
                    <span>Cari nama atau NIP anda disini</span>
                  </label>
                  <div className="relative">
                    <input
                      type="text"
                      value={searchKeyword}
                      onChange={(e) => handleSearchChange(e.target.value)}
                      placeholder="Contoh: Ahmad, Femmy, 1982..."
                      className="w-full text-xs sm:text-sm px-3 py-2 bg-white rounded-lg border border-teal-200 focus:outline-none focus:ring-2 focus:ring-teal-500 text-slate-800"
                    />
                    {showSearchResults && matchedEmployees.length > 0 && (
                      <div className="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-xl border border-slate-200 z-30 max-h-52 overflow-y-auto divide-y divide-slate-100">
                        {matchedEmployees.map((emp) => (
                          <button
                            key={emp.nip}
                            type="button"
                            onClick={() => handleSelectEmployee(emp)}
                            className="w-full text-left p-2.5 hover:bg-teal-50 transition-colors flex flex-col gap-0.5"
                          >
                            <span className="font-bold text-xs sm:text-sm text-slate-900">{emp.name}</span>
                            <span className="text-[11px] text-slate-500">
                              NIP: <span className="font-mono text-teal-700 font-semibold">{emp.nip}</span> •{' '}
                              {emp.unitKerja}
                            </span>
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                </div>

                {/* Baris NIP (kompak) & Unit Kerja */}
                <div className="grid grid-cols-1 min-[480px]:grid-cols-12 gap-3 items-start">
                  {/* NIP Input (Kompak, pas untuk 18 digit) */}
                  <div className="min-[480px]:col-span-5 max-w-[260px] min-[480px]:max-w-none">
                    <div className="flex items-center justify-between mb-1">
                      <label className="text-xs font-bold text-slate-700 flex items-center gap-1">
                        <span>Nomor Induk Pegawai (NIP)</span>
                      </label>
                      {nip && (
                        <button
                          type="button"
                          onClick={() => {
                            setNip('');
                            setName('');
                            setUnitKerja('');
                            setJabatan('');
                          }}
                          className="text-[11px] text-slate-400 hover:text-slate-600 font-medium cursor-pointer"
                        >
                          Kosongkan
                        </button>
                      )}
                    </div>
                    <div className="relative">
                      <input
                        type="text"
                        value={nip}
                        onChange={(e) => setNip(e.target.value)}
                        placeholder="Contoh: 19861210..."
                        className="w-full text-xs sm:text-sm px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 font-mono text-slate-900"
                      />
                    </div>
                  </div>

                  {/* Unit Kerja / Jurusan */}
                  <div className="min-[480px]:col-span-7">
                    <label className="text-xs font-bold text-slate-700 block mb-1">
                      Unit Kerja / Jurusan <span className="text-rose-500">*</span>
                    </label>
                    <div className="relative">
                      <Building2 className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                      <input
                        type="text"
                        value={unitKerja}
                        onChange={(e) => setUnitKerja(e.target.value)}
                        placeholder="Contoh: Keperawatan Bandung / OSDM"
                        required
                        className="w-full text-xs sm:text-sm pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                      />
                    </div>
                  </div>
                </div>

                {/* Nama Pegawai */}
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">
                    Nama Lengkap & Gelar <span className="text-rose-500">*</span>
                  </label>
                  <div className="relative">
                    <User className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                    <input
                      type="text"
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      placeholder="Nama lengkap peserta rapat beserta gelar"
                      required
                      className="w-full text-xs sm:text-sm pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900 font-medium"
                    />
                  </div>
                </div>

                {/* Jabatan (Lebih Panjang / Full Width) */}
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Jabatan</label>
                  <div className="relative">
                    <Briefcase className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                    <input
                      type="text"
                      value={jabatan}
                      onChange={(e) => setJabatan(e.target.value)}
                      placeholder="Contoh: Dosen Lektor Kepala / Koordinator Program Studi / Pengelola"
                      className="w-full text-xs sm:text-sm pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                    />
                  </div>
                </div>

                {/* Seleksi Agenda Rapat yang Dihadiri (jika rapat memiliki > 1 agenda) */}
                {availableAgendas.length > 1 && (
                  <div className="space-y-2 p-3.5 rounded-xl bg-teal-50/70 border border-teal-200/90 shadow-2xs">
                    <div className="flex items-center justify-between">
                      <label className="text-xs font-bold text-teal-950 flex items-center gap-1.5">
                        <Layers className="w-3.5 h-3.5 text-teal-700" />
                        <span>
                          Agenda Rapat yang Diikuti <span className="text-rose-500">*</span>
                        </span>
                      </label>
                      <button
                        type="button"
                        onClick={() => setSelectedAgendas(availableAgendas)}
                        className="text-[11px] text-teal-700 hover:text-teal-950 font-semibold cursor-pointer"
                      >
                        Pilih Semua
                      </button>
                    </div>
                    <div className="space-y-1.5 pt-0.5">
                      {availableAgendas.map((agendaItem, idx) => {
                        const isChecked = selectedAgendas.includes(agendaItem);
                        return (
                          <label
                            key={idx}
                            className={`flex items-start gap-2.5 p-2 rounded-lg border text-xs cursor-pointer transition-all ${
                              isChecked
                                ? 'bg-white border-teal-500 text-slate-900 font-medium shadow-2xs'
                                : 'bg-white/60 border-slate-200 text-slate-500 hover:bg-white'
                            }`}
                          >
                            <input
                              type="checkbox"
                              checked={isChecked}
                              onChange={(e) => {
                                if (e.target.checked) {
                                  setSelectedAgendas([...selectedAgendas, agendaItem]);
                                } else {
                                  if (selectedAgendas.length > 1) {
                                    setSelectedAgendas(selectedAgendas.filter((a) => a !== agendaItem));
                                  }
                                }
                              }}
                              className="mt-0.5 w-4 h-4 rounded text-teal-600 focus:ring-teal-500 cursor-pointer"
                            />
                            <span className="flex-1 leading-snug">{agendaItem}</span>
                          </label>
                        );
                      })}
                    </div>
                  </div>
                )}

                {/* SIGNATURE PAD (Paraf Digital) */}
                <div className="space-y-1.5 pt-1">
                  <div className="flex items-center justify-between">
                    <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                      <PenTool className="w-3.5 h-3.5 text-teal-700" />
                      <span>
                        Bubuhkan Paraf / Tanda Tangan <span className="text-rose-500">*</span>
                      </span>
                    </label>
                    <button
                      type="button"
                      onClick={clearCanvas}
                      className="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1 transition-colors px-2 py-0.5 rounded hover:bg-rose-50 cursor-pointer"
                    >
                      <RotateCcw className="w-3 h-3" />
                      <span>Ulangi Paraf</span>
                    </button>
                  </div>

                  <div className="relative border-2 border-dashed border-slate-300 hover:border-teal-500 rounded-xl bg-slate-50 overflow-hidden transition-colors">
                    <canvas
                      ref={canvasRef}
                      onMouseDown={startDrawing}
                      onMouseMove={draw}
                      onMouseUp={stopDrawing}
                      onMouseLeave={stopDrawing}
                      onTouchStart={startDrawing}
                      onTouchMove={draw}
                      onTouchEnd={stopDrawing}
                      className="w-full h-36 sm:h-40 block cursor-crosshair touch-none bg-white"
                    />

                    {!hasSignature && (
                      <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-slate-400 select-none">
                        <PenTool className="w-6 h-6 mb-1 text-slate-300" />
                        <span className="text-xs font-medium">Goreskan tanda tangan / paraf Anda di sini</span>
                        <span className="text-[10px] text-slate-400">(Sentuh layar ponsel atau seret kursor mouse)</span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Submit Action */}
                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="w-full py-3 px-4 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-teal-700 via-teal-800 to-emerald-800 hover:from-teal-800 hover:to-emerald-900 shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                  >
                    {isSubmitting ? (
                      <>
                        <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                        <span>Menyimpan Kehadiran...</span>
                      </>
                    ) : (
                      <>
                        <CheckCircle2 className="w-4 h-4" />
                        <span>Saya Hadir</span>
                      </>
                    )}
                  </button>
                </div>
              </form>
            )}
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

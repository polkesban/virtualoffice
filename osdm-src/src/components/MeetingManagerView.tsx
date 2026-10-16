import React, { useState, useMemo, useEffect, useRef } from 'react';
import {
  FileText,
  Calendar,
  Clock,
  MapPin,
  Users,
  Download,
  Share2,
  ExternalLink,
  Plus,
  PlusCircle,
  Trash2,
  Edit3,
  CheckCircle2,
  Search,
  Filter,
  Save,
  RotateCcw,
  Sparkles,
  Building2,
  AlertCircle,
  Copy,
  Check,
  Layers,
  Eye,
  X,
  QrCode,
  Power,
  Sliders,
  User,
} from 'lucide-react';
import { MeetingSession, MeetingAttendanceRecord } from '../types';
import { generateMeetingPdfReport } from '../utils/meetingPdfReport';
import { MeetingExportPdfModal } from './MeetingExportPdfModal';
import { MeetingQRCodeModal } from './MeetingQRCodeModal';
import { MeetingCreateModal } from './MeetingCreateModal';
import { MeetingConfigModal } from './MeetingConfigModal';
import {
  getMeetingAgendas,
  filterAttendeesByAgenda,
  countAttendeesForAgenda,
  getAllPermanentAgendas,
  saveAgendaPermanently,
  removeSavedAgenda,
} from '../utils/meetingAgendaHelper';

interface MeetingManagerViewProps {
  session: MeetingSession;
  allSessions?: MeetingSession[];
  onSelectSession?: (sessionId: string) => void;
  onCreateNewSession?: (newSession: MeetingSession) => void;
  onDeleteSession?: (sessionId: string) => void;
  onToggleSessionStatus?: (sessionId: string, isActive: boolean) => void;
  onUpdateSession: (updatedSession: MeetingSession) => void;
  onResetSession: () => void;
  attendees: MeetingAttendanceRecord[];
  onDeleteAttendee: (id: string) => void;
  onDeleteAttendeesByAgenda?: (agenda: string) => void;
  onAddAttendee: (attendee: Omit<MeetingAttendanceRecord, 'id' | 'timestamp'>) => void;
  onOpenPublicForm: () => void;
}

export const MeetingManagerView: React.FC<MeetingManagerViewProps> = ({
  session,
  allSessions = [],
  onSelectSession,
  onCreateNewSession,
  onDeleteSession,
  onToggleSessionStatus,
  onUpdateSession,
  onResetSession,
  attendees,
  onDeleteAttendee,
  onDeleteAttendeesByAgenda,
  onAddAttendee,
  onOpenPublicForm,
}) => {
  // Filter attendees specifically for this active session (to prevent collision with other meetings)
  const sessionAttendees = useMemo(() => {
    return attendees.filter((att) => !att.meetingId || att.meetingId === session.id);
  }, [attendees, session.id]);

  // Modals state
  const [isQRModalOpen, setIsQRModalOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isConfigModalOpen, setIsConfigModalOpen] = useState(false);
  const [sessionToConfigure, setSessionToConfigure] = useState<MeetingSession | null>(null);

  const handleOpenConfigModal = (targetSession: MeetingSession) => {
    setSessionToConfigure(targetSession);
    setIsConfigModalOpen(true);
  };

  const handleSaveConfig = (updatedSession: MeetingSession) => {
    onUpdateSession(updatedSession);
    setSaveSuccessToast(true);
    setTimeout(() => setSaveSuccessToast(false), 3000);
  };

  // Agenda list state & refresh trigger
  const [agendaRefreshTick, setAgendaRefreshTick] = useState(0);

  useEffect(() => {
    const handleAgendasUpdated = () => {
      setAgendaRefreshTick((prev) => prev + 1);
    };
    window.addEventListener('meeting_agendas_updated', handleAgendasUpdated);
    return () => {
      window.removeEventListener('meeting_agendas_updated', handleAgendasUpdated);
    };
  }, []);

  // Agenda list & edit state (permanent history)
  const availableAgendas = useMemo(
    () => getAllPermanentAgendas(session, attendees, allSessions),
    [session, attendees, allSessions, agendaRefreshTick]
  );

  const handleDeleteAgenda = (agendaToDelete: string) => {
    if (!agendaToDelete || agendaToDelete === 'All') return;

    const count = countAttendeesForAgenda(attendees, agendaToDelete);
    const confirmMsg =
      count > 0
        ? `Apakah Anda yakin ingin menghapus agenda:\n"${agendaToDelete}"?\n\nPerhatian: Terdapat ${count} peserta tercatat hadir pada agenda ini. Menghapus agenda ini akan SEKALIGUS MENGHAPUS SEMUA ${count} PESERTA yang hadir pada agenda ini agar Anda tidak perlu menghapusnya satu per satu.`
        : `Apakah Anda yakin ingin menghapus agenda:\n"${agendaToDelete}" dari daftar pilihan?`;

    if (!window.confirm(confirmMsg)) return;

    // 1. Simpan ke blacklist deleted agendas & hapus dari saved list
    removeSavedAgenda(agendaToDelete);

    // 2. Cascade delete semua peserta di agenda ini
    if (onDeleteAttendeesByAgenda) {
      onDeleteAttendeesByAgenda(agendaToDelete);
    } else {
      const attendeesInAgenda = filterAttendeesByAgenda(attendees, agendaToDelete);
      attendeesInAgenda.forEach((att) => onDeleteAttendee(att.id));
    }

    // 3. Jika sesi rapat aktif menggunakan agenda ini, bersihkan dari session
    if (session.agenda && session.agenda.trim().toLowerCase() === agendaToDelete.trim().toLowerCase()) {
      onUpdateSession({
        ...session,
        agenda: '',
        agendas: (session.agendas || []).filter(
          (a) => a.trim().toLowerCase() !== agendaToDelete.trim().toLowerCase()
        ),
      });
    }

    setAgendaRefreshTick((prev) => prev + 1);

    // 4. Pilih agenda yang tersisa
    const remaining = availableAgendas.filter(
      (a) => a.trim().toLowerCase() !== agendaToDelete.trim().toLowerCase()
    );
    setSelectedAgendaDropdown(remaining[0] || '');
  };



  // Export PDF Modal State
  const [isExportModalOpen, setIsExportModalOpen] = useState(false);
  const [exportTargetAgenda, setExportTargetAgenda] = useState<string | undefined>(undefined);

  // Selected agenda in the smart dropdown selector
  const [selectedAgendaDropdown, setSelectedAgendaDropdown] = useState<string>(
    session.agenda ? session.agenda.trim() : (availableAgendas[0] || '')
  );

  // Keep selectedAgendaDropdown in sync if availableAgendas or session agenda changes
  React.useEffect(() => {
    if (availableAgendas.length === 0) {
      if (selectedAgendaDropdown !== '') {
        setSelectedAgendaDropdown('');
      }
    } else if (
      !selectedAgendaDropdown ||
      !availableAgendas.some((a) => a.trim().toLowerCase() === selectedAgendaDropdown.trim().toLowerCase())
    ) {
      setSelectedAgendaDropdown(availableAgendas[0] || '');
    }
  }, [availableAgendas, selectedAgendaDropdown]);

  // Floating Window (Jendela Melayang) Modal State for specific Agenda
  const [activeModalAgenda, setActiveModalAgenda] = useState<string | null>(null);
  const [modalSearchQuery, setModalSearchQuery] = useState('');
  const [modalUnitFilter, setModalUnitFilter] = useState('All');

  // Feedback states
  const [saveSuccessToast, setSaveSuccessToast] = useState(false);
  const [copiedSessionId, setCopiedSessionId] = useState<string | null>(null);

  // Preview signature modal
  const [previewSignature, setPreviewSignature] = useState<{ name: string; url: string } | null>(null);

  // Manual Add Modal State
  const [isManualAddOpen, setIsManualAddOpen] = useState(false);
  const [manualNip, setManualNip] = useState('');
  const [manualName, setManualName] = useState('');
  const [manualUnitKerja, setManualUnitKerja] = useState('');
  const [manualJabatan, setManualJabatan] = useState('');
  const [manualTargetAgenda, setManualTargetAgenda] = useState<string | null>(null);

  const handleCopyLinkForSession = (sessionId: string) => {
    try {
      const baseUrl = window.location.href.split('#')[0].split('?')[0];
      const url = `${baseUrl}?meetId=${encodeURIComponent(sessionId)}`;
      navigator.clipboard.writeText(url);
      setCopiedSessionId(sessionId);
      setTimeout(() => setCopiedSessionId(null), 2000);
    } catch {
      // fallback
    }
  };

  const handleExportPdf = (targetAgenda?: string) => {
    setExportTargetAgenda(targetAgenda);
    setIsExportModalOpen(true);
  };

  const handleOpenAgendaModal = (agendaName: string) => {
    setActiveModalAgenda(agendaName);
    setModalSearchQuery('');
    setModalUnitFilter('All');
  };

  const handleCloseAgendaModal = () => {
    setActiveModalAgenda(null);
    setModalSearchQuery('');
    setModalUnitFilter('All');
  };

  // Distinct unit list for global table filter (scoped to this session)
  const unitList = useMemo(() => {
    const set = new Set<string>();
    sessionAttendees.forEach((att) => {
      if (att.unitKerja) set.add(att.unitKerja.trim());
    });
    return Array.from(set).sort();
  }, [sessionAttendees]);

  // Filtered attendees for the active Floating Window (Modal)
  const modalAttendees = useMemo(() => {
    if (!activeModalAgenda) return [];
    const base =
      activeModalAgenda === 'ALL'
        ? sessionAttendees
        : filterAttendeesByAgenda(attendees, activeModalAgenda);

    return base
      .filter((att) => {
        const matchUnit = modalUnitFilter === 'All' || att.unitKerja === modalUnitFilter;
        const q = modalSearchQuery.toLowerCase().trim();
        const matchQuery =
          !q ||
          att.name.toLowerCase().includes(q) ||
          att.nip.toLowerCase().includes(q) ||
          att.unitKerja.toLowerCase().includes(q) ||
          (att.jabatan && att.jabatan.toLowerCase().includes(q));
        return matchUnit && matchQuery;
      })
      .sort((a, b) => {
        const timeA = a.timestamp ? new Date(a.timestamp).getTime() : 0;
        const timeB = b.timestamp ? new Date(b.timestamp).getTime() : 0;
        if (timeA && timeB && !isNaN(timeA) && !isNaN(timeB)) {
          return timeA - timeB;
        }
        if (timeA && !isNaN(timeA)) return -1;
        if (timeB && !isNaN(timeB)) return 1;
        const numA = parseInt((a.id || '').replace(/\D/g, ''), 10) || 0;
        const numB = parseInt((b.id || '').replace(/\D/g, ''), 10) || 0;
        return numA - numB;
      });
  }, [attendees, sessionAttendees, activeModalAgenda, modalSearchQuery, modalUnitFilter]);

  // Distinct unit list for the active Floating Window (Modal)
  const modalUnitList = useMemo(() => {
    if (!activeModalAgenda) return [];
    const base =
      activeModalAgenda === 'ALL'
        ? sessionAttendees
        : filterAttendeesByAgenda(attendees, activeModalAgenda);
    const set = new Set<string>();
    base.forEach((att) => {
      if (att.unitKerja) set.add(att.unitKerja.trim());
    });
    return Array.from(set).sort();
  }, [attendees, sessionAttendees, activeModalAgenda]);

  // Handle Manual Add Submit
  const handleManualAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!manualName.trim()) {
      alert('Nama peserta wajib diisi.');
      return;
    }
    // Generate simple placeholder paraf for manual entry
    const sampleSig = `data:image/svg+xml;base64,${btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="160" height="50" viewBox="0 0 160 50">
        <path d="M 20 35 Q 45 10 75 30 T 135 25" fill="none" stroke="#0f172a" stroke-width="2"/>
        <text x="95" y="42" font-family="sans-serif" font-size="10" fill="#64748b">PARAF</text>
      </svg>`
    )}`;

    const targetAgendas =
      manualTargetAgenda && manualTargetAgenda !== 'ALL'
        ? [manualTargetAgenda]
        : selectedAgendaDropdown
        ? [selectedAgendaDropdown]
        : availableAgendas;

    onAddAttendee({
      meetingId: session.id,
      nip: manualNip.trim(),
      name: manualName.trim(),
      unitKerja: manualUnitKerja.trim() || 'Poltekkes Kemenkes Bandung',
      jabatan: manualJabatan.trim() || 'Pegawai',
      signatureDataUrl: sampleSig,
      agendas: targetAgendas,
      notes: 'Ditambahkan manual oleh Admin',
    });

    setManualNip('');
    setManualName('');
    setManualUnitKerja('');
    setManualJabatan('');
    setManualTargetAgenda(null);
    setIsManualAddOpen(false);
  };

  return (
    <div className="space-y-6 pb-12">
      {/* Multi-Session Switcher Header & Tabs */}
      <div className="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-xl bg-teal-50 text-teal-700 border border-teal-100">
              <Layers className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-sm sm:text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>Agenda Rapat Aktif & Berjalan</span>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">
                  {allSessions.length || 1} Sesi
                </span>
              </h2>
              <p className="text-[11px] text-slate-500">
                Pilih tab rapat untuk mengelola detail, menampilkan QR presensi proyektor, atau mengunduh rekapan masing-masing rapat.
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={() => setIsCreateModalOpen(true)}
            className="group px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-xs hover:shadow-sm cursor-pointer shrink-0"
          >
            <PlusCircle className="w-4 h-4 text-teal-200 group-hover:text-white group-hover:scale-110 transition-all shrink-0" />
            <span>Buat Sesi Baru</span>
          </button>
        </div>

        {/* Sessions Horizontal Tabs / Cards */}
        <div className="flex items-center gap-3 overflow-x-auto pb-1.5 pt-0.5 scrollbar-thin">
          {(allSessions.length > 0 ? allSessions : [session]).map((s) => {
            const isCurrent = s.id === session.id;
            const count = attendees.filter((a) => !a.meetingId || a.meetingId === s.id).length;
            const isActive = s.isActive !== false;

            return (
              <div
                key={s.id}
                onClick={() => onSelectSession && onSelectSession(s.id)}
                className={`group flex-shrink-0 cursor-pointer rounded-2xl p-3.5 border transition-all text-left w-80 relative flex flex-col justify-between ${
                  isCurrent
                    ? 'bg-teal-50/95 border-teal-500 shadow-sm ring-2 ring-teal-600/30'
                    : 'bg-slate-50/80 border-slate-200 hover:bg-white hover:border-slate-300'
                }`}
              >
                <div>
                  <div className="flex items-center justify-between gap-2 mb-1.5">
                    <span className="inline-flex items-center gap-1.5 text-[10px] font-bold">
                      <span
                        className={`w-2 h-2 rounded-full ${
                          isActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'
                        }`}
                      />
                      <span className={isActive ? 'text-emerald-700 font-bold' : 'text-slate-500'}>
                        {isActive ? 'Sedang Berjalan' : 'Selesai / Ditutup'}
                      </span>
                    </span>
                    <span className="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-white border border-slate-200 text-teal-800 shadow-2xs">
                      {count} Peserta
                    </span>
                  </div>

                  <h4
                    className={`text-xs font-black line-clamp-1 leading-snug ${
                      isCurrent ? 'text-teal-950' : 'text-slate-800 group-hover:text-teal-700'
                    }`}
                    title={s.agenda || s.title}
                  >
                    {s.agenda || s.title || 'Daftar Hadir Rapat'}
                  </h4>

                  <div className="flex items-center gap-2 text-[10px] text-slate-500 mt-1 truncate">
                    <span>{s.timeStart} WIB</span>
                    <span>•</span>
                    <span className="truncate">{s.location || 'Poltekkes Kemenkes Bandung'}</span>
                  </div>
                </div>

                {/* Card Quick Action Buttons */}
                <div className="flex items-center justify-between pt-2.5 mt-2.5 border-t border-slate-200/60">
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleOpenConfigModal(s);
                    }}
                    className="text-[10px] font-bold text-teal-800 bg-white hover:bg-teal-100/70 border border-slate-200 hover:border-teal-300 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1 shadow-2xs cursor-pointer"
                    title="Buka jendela konfigurasi melayang untuk sesi ini"
                  >
                    <Sliders className="w-3 h-3 text-teal-700" />
                    <span>Konfigurasi</span>
                  </button>

                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleCopyLinkForSession(s.id);
                      }}
                      className="p-1 rounded-lg text-slate-400 hover:text-teal-700 hover:bg-white transition-colors cursor-pointer"
                      title={copiedSessionId === s.id ? 'Tautan disalin!' : 'Salin tautan rapat ini'}
                    >
                      {copiedSessionId === s.id ? (
                        <Check className="w-3.5 h-3.5 text-emerald-600" />
                      ) : (
                        <Copy className="w-3.5 h-3.5" />
                      )}
                    </button>
                    {allSessions.length > 1 && onDeleteSession && (
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          if (
                            window.confirm(
                              `Apakah Anda yakin ingin menghapus sesi rapat:\n"${
                                s.agenda || s.title
                              }"?`
                            )
                          ) {
                            onDeleteSession(s.id);
                          }
                        }}
                        className="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-white transition-colors cursor-pointer"
                        title="Hapus sesi ini"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>



      {saveSuccessToast && (
        <div className="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-sm animate-in fade-in slide-in-from-top-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
          <span>Judul dan konfigurasi sesi rapat berhasil diperbarui!</span>
        </div>
      )}

      {/* Sleek KPI Summary Bar (Ramping & Modern) */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex items-center gap-3.5">
          <div className="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0 border border-teal-100">
            <Users className="w-6 h-6" />
          </div>
          <div>
            <span className="text-xs font-semibold text-slate-500 block">Total Peserta Hadir</span>
            <div className="flex items-baseline gap-1.5">
              <span className="text-2xl font-black text-teal-700">{sessionAttendees.length}</span>
              <span className="text-xs text-slate-500">orang hadir</span>
            </div>
            <span className="text-[11px] text-emerald-600 font-semibold flex items-center gap-1 mt-0.5">
              <CheckCircle2 className="w-3 h-3" />
              Presensi terverifikasi
            </span>
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex items-center gap-3.5">
          <div className="w-12 h-12 rounded-xl bg-slate-50 text-slate-700 flex items-center justify-center shrink-0 border border-slate-200">
            <Building2 className="w-6 h-6 text-slate-600" />
          </div>
          <div>
            <span className="text-xs font-semibold text-slate-500 block">Unit Kerja Berpartisipasi</span>
            <div className="flex items-baseline gap-1.5">
              <span className="text-2xl font-black text-slate-800">{unitList.length}</span>
              <span className="text-xs text-slate-500">unit</span>
            </div>
            <span className="text-[11px] text-slate-400 font-medium block mt-0.5">Lembaga & Jurusan</span>
          </div>
        </div>

        <div className="bg-gradient-to-r from-teal-800 to-emerald-900 text-white rounded-2xl p-4 shadow-2xs flex items-center justify-between gap-3">
          <div className="space-y-0.5">
            <div className="flex items-center gap-1.5">
              <Sparkles className="w-4 h-4 text-emerald-300" />
              <span className="text-[11px] font-bold text-emerald-200 uppercase tracking-wider block">
                Presensi Mandiri Peserta
              </span>
            </div>
            <p className="text-xs text-teal-100 line-clamp-1">
              Buka layar presensi atau scan QR proyektor
            </p>
          </div>
          <div className="flex items-center gap-2 shrink-0">
            <button
              type="button"
              onClick={() => setIsQRModalOpen(true)}
              className="py-2 px-3 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-bold transition-colors flex items-center gap-1.5 border border-white/20 shadow-sm cursor-pointer"
              title="Tampilkan QR Code untuk diproyeksikan di layar proyektor"
            >
              <QrCode className="w-3.5 h-3.5 text-emerald-200" />
              <span>QR Layar</span>
            </button>
            <button
              onClick={onOpenPublicForm}
              className="py-2 px-3 rounded-xl bg-white text-teal-950 text-xs font-bold hover:bg-teal-50 transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
            >
              <ExternalLink className="w-3.5 h-3.5 text-teal-800" />
              <span>Buka Layar</span>
            </button>
          </div>
        </div>
      </div>

      {/* Section: Daftar Kehadiran (Menu Presensi Berdasarkan Agenda Rapat) */}
      <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
        {/* Section Header */}
        <div className="pb-3 border-b border-slate-100">
          <div className="flex items-center gap-2">
            <Layers className="w-5 h-5 text-teal-700" />
            <h2 className="text-base sm:text-lg font-bold text-slate-900">
              Menu Presensi Berdasarkan Agenda Rapat
            </h2>
          </div>
          <p className="text-xs text-slate-500 mt-0.5">
            Pilih agenda rapat melalui menu dropdown untuk membuka daftar peserta dan mengelola presensi.
          </p>
        </div>

        {/* Smart Dropdown Selector Bar */}
        <div className="bg-gradient-to-br from-slate-50 via-teal-50/15 to-emerald-50/20 rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
          <div className="flex flex-col md:flex-row items-stretch md:items-end justify-between gap-3">
            <div className="flex-1 space-y-1.5">
              <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <Layers className="w-4 h-4 text-teal-700" />
                <span>Pilih Agenda Rapat:</span>
                <span className="text-[11px] font-normal text-slate-500">
                  ({availableAgendas.length} agenda terdaftar)
                </span>
              </label>
              <div className="relative">
                <select
                  value={selectedAgendaDropdown}
                  onChange={(e) => setSelectedAgendaDropdown(e.target.value)}
                  disabled={availableAgendas.length === 0}
                  className="w-full text-xs sm:text-sm font-bold py-2.5 pl-3 pr-8 bg-white border-2 border-slate-200 hover:border-teal-400 focus:border-teal-600 rounded-xl focus:outline-none text-slate-900 shadow-sm cursor-pointer transition-colors disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                >
                  {availableAgendas.length === 0 ? (
                    <option value="">-- Belum ada agenda rapat yang terdaftar --</option>
                  ) : (
                    availableAgendas.map((ag) => {
                      const count = countAttendeesForAgenda(attendees, ag);
                      const isSessionActive =
                        session.agenda && session.agenda.trim().toLowerCase() === ag.trim().toLowerCase();
                      return (
                        <option key={ag} value={ag}>
                          {isSessionActive ? '[Sesi Aktif] ' : ''}
                          {ag} ({count} Pegawai Hadir)
                        </option>
                      );
                    })
                  )}
                </select>
              </div>
            </div>

            {/* Action Buttons for the selected agenda */}
            <div className="flex items-center gap-2 shrink-0">
              <button
                type="button"
                onClick={() => handleOpenAgendaModal(selectedAgendaDropdown)}
                disabled={!selectedAgendaDropdown || availableAgendas.length === 0}
                className="flex-1 md:flex-none px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-teal-700 to-emerald-700 hover:from-teal-800 hover:to-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 shadow-sm hover:shadow-md cursor-pointer"
                title="Lihat daftar peserta rapat pada agenda ini"
              >
                <Eye className="w-4 h-4" />
                <span>Lihat Peserta Rapat</span>
                <span className="px-2 py-0.5 rounded-full bg-white/25 text-white text-[11px] font-black">
                  {selectedAgendaDropdown ? countAttendeesForAgenda(attendees, selectedAgendaDropdown) : 0} Hadir
                </span>
              </button>

              <button
                type="button"
                onClick={() => handleExportPdf(selectedAgendaDropdown)}
                disabled={!selectedAgendaDropdown || availableAgendas.length === 0}
                className="p-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-teal-50 hover:text-teal-800 border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
                title="Unduh PDF rekap untuk agenda terpilih"
              >
                <Download className="w-4 h-4 text-teal-700" />
              </button>

              {selectedAgendaDropdown && (
                <button
                  type="button"
                  onClick={() => handleDeleteAgenda(selectedAgendaDropdown)}
                  className="p-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-rose-600 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
                  title="Hapus agenda ini beserta seluruh peserta rapatnya"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* JENDELA MELAYANG (FLOATING MODAL): DAFTAR PESERTA PER AGENDA */}
      {activeModalAgenda !== null && (
        <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 animate-in fade-in">
          <div className="bg-white rounded-2xl w-full max-w-5xl max-h-[92vh] flex flex-col border border-slate-200 shadow-2xl overflow-hidden animate-in zoom-in-95 duration-150">
            {/* Modal Header */}
            <div className="bg-gradient-to-r from-teal-800 via-emerald-800 to-teal-900 text-white p-4 sm:p-5 flex items-start justify-between gap-4 shrink-0 shadow-md relative overflow-hidden">
              {/* Subtle decorative background elements */}
              <div className="absolute inset-0 pointer-events-none">
                <div className="absolute -top-8 -right-8 w-36 h-36 bg-emerald-500/8 rounded-full blur-2xl" />
                <div className="absolute -bottom-6 -left-6 w-28 h-28 bg-teal-400/8 rounded-full blur-2xl" />
                <div className="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-emerald-400/25 to-transparent" />
              </div>

              <div className="space-y-1.5 relative">
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-500/25 text-emerald-100 border border-emerald-400/30 flex items-center gap-1 shadow-sm">
                    <Layers className="w-3 h-3 text-emerald-300" />
                    Jendela Melayang Presensi
                  </span>
                  <span className="text-xs text-emerald-200 font-semibold">
                    {filterAttendeesByAgenda(attendees, activeModalAgenda === 'ALL' ? undefined : activeModalAgenda).length} Pegawai Hadir
                  </span>
                </div>
                <h2 className="text-base sm:text-xl font-black text-white tracking-tight drop-shadow-sm">
                  {activeModalAgenda === 'ALL'
                    ? 'Daftar Hadir Keseluruhan (Semua Agenda Rapat)'
                    : activeModalAgenda}
                </h2>
                <p className="text-xs text-emerald-100/80">
                  Daftar nama pegawai yang hadir dan membubuhkan paraf digital pada agenda ini.
                </p>
              </div>

              <div className="flex items-center gap-2 shrink-0 relative">
                <button
                  type="button"
                  onClick={() => {
                    setManualTargetAgenda(activeModalAgenda === 'ALL' ? null : activeModalAgenda);
                    setIsManualAddOpen(true);
                  }}
                  className="hidden sm:flex px-3 py-1.5 rounded-xl text-xs font-bold text-teal-950 bg-emerald-100 hover:bg-white transition-colors items-center gap-1.5 shadow-sm cursor-pointer"
                  title="Tambah manual peserta ke agenda ini"
                >
                  <Plus className="w-3.5 h-3.5 text-teal-800" />
                  <span>Tambah Manual</span>
                </button>

                <button
                  type="button"
                  onClick={() => {
                    handleExportPdf(activeModalAgenda === 'ALL' ? 'ALL' : activeModalAgenda);
                  }}
                  className="hidden sm:flex px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-white/12 hover:bg-white/20 border border-white/15 transition-colors items-center gap-1.5 shadow-sm cursor-pointer backdrop-blur-sm"
                  title="Unduh laporan PDF agenda ini"
                >
                  <Download className="w-3.5 h-3.5" />
                  <span>Unduh PDF</span>
                </button>

                <button
                  type="button"
                  onClick={handleCloseAgendaModal}
                  className="w-8 h-8 rounded-xl bg-white/12 hover:bg-white/25 text-white/70 hover:text-white flex items-center justify-center transition-colors cursor-pointer border border-white/10 backdrop-blur-sm"
                  title="Tutup jendela melayang"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Modal Subtoolbar: Search & Filters */}
            <div className="p-3 sm:p-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shrink-0">
              <div className="flex items-center gap-2 flex-1">
                <div className="relative flex-1 sm:max-w-xs">
                  <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                  <input
                    type="text"
                    value={modalSearchQuery}
                    onChange={(e) => setModalSearchQuery(e.target.value)}
                    placeholder="Cari NIP, nama, unit di agenda ini..."
                    className="w-full text-xs pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-800 shadow-xs"
                  />
                </div>

                {modalUnitList.length > 1 && (
                  <select
                    value={modalUnitFilter}
                    onChange={(e) => setModalUnitFilter(e.target.value)}
                    className="text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-700 shadow-xs"
                  >
                    <option value="All">Semua Unit ({filterAttendeesByAgenda(attendees, activeModalAgenda === 'ALL' ? undefined : activeModalAgenda).length})</option>
                    {modalUnitList.map((u) => (
                      <option key={u} value={u}>
                        {u}
                      </option>
                    ))}
                  </select>
                )}
              </div>

              <div className="flex items-center justify-between sm:justify-end gap-2 text-xs text-slate-600 font-medium">
                <span>
                  Menampilkan: <strong className="text-teal-800 font-bold">{modalAttendees.length}</strong> pegawai
                </span>
                <button
                  type="button"
                  onClick={() => {
                    setManualTargetAgenda(activeModalAgenda === 'ALL' ? null : activeModalAgenda);
                    setIsManualAddOpen(true);
                  }}
                  className="sm:hidden px-2.5 py-1.5 rounded-lg text-xs font-bold text-teal-800 bg-teal-50 border border-teal-200 flex items-center gap-1"
                >
                  <Plus className="w-3 h-3" />
                  <span>Tambah</span>
                </button>
              </div>
            </div>

            {/* Modal Body: Scrollable Attendees Table */}
            <div className="overflow-y-auto flex-1 p-3 sm:p-4">
              <div className="overflow-x-auto border border-slate-200 rounded-xl">
                <table className="w-full text-left border-collapse text-xs">
                  <thead>
                    <tr className="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                      <th className="py-3 px-3 text-center w-12">No</th>
                      <th className="py-3 px-3 w-28">Waktu Hadir</th>
                      <th className="py-3 px-3">Nama Pegawai & NIP</th>
                      <th className="py-3 px-3">Unit Kerja</th>
                      <th className="py-3 px-3">Jabatan</th>
                      <th className="py-3 px-3 text-center w-28">Paraf Digital</th>
                      <th className="py-3 px-3 text-center w-16">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {modalAttendees.length === 0 ? (
                      <tr>
                        <td colSpan={7} className="py-12 text-center text-slate-400">
                          <div className="flex flex-col items-center justify-center gap-2">
                            <Users className="w-10 h-10 text-slate-300" />
                            <span className="font-bold text-sm text-slate-700">
                              Belum ada peserta pada agenda ini
                            </span>
                            <p className="text-xs text-slate-500 max-w-sm">
                              {modalSearchQuery
                                ? 'Tidak ada pegawai yang cocok dengan kata kunci pencarian.'
                                : 'Peserta yang mengisi presensi agenda ini otomatis akan tampil di jendela melayang ini.'}
                            </p>
                            <button
                              type="button"
                              onClick={() => {
                                setManualTargetAgenda(activeModalAgenda === 'ALL' ? null : activeModalAgenda);
                                setIsManualAddOpen(true);
                              }}
                              className="mt-2 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition-colors flex items-center gap-1.5 cursor-pointer"
                            >
                              <Plus className="w-3.5 h-3.5" />
                              <span>Tambah Peserta ke Agenda Ini</span>
                            </button>
                          </div>
                        </td>
                      </tr>
                    ) : (
                      modalAttendees.map((att, idx) => (
                        <tr key={att.id} className="hover:bg-teal-50/40 transition-colors">
                          <td className="py-2.5 px-3 text-center font-medium text-slate-500">{idx + 1}</td>
                          <td className="py-2.5 px-3 text-slate-600 font-mono text-[11px]">
                            {new Date(att.timestamp).toLocaleTimeString('id-ID', {
                              hour: '2-digit',
                              minute: '2-digit',
                            })}{' '}
                            WIB
                          </td>
                          <td className="py-2.5 px-3">
                            <span className="font-bold text-slate-900 block text-xs sm:text-sm">{att.name}</span>
                            {att.nip ? (
                              <span className="text-[11px] font-mono text-teal-700 font-semibold block">
                                NIP. {att.nip}
                              </span>
                            ) : (
                              <span className="text-[10px] text-slate-400 italic">Non-NIP / Peserta Tamu</span>
                            )}
                          </td>
                          <td className="py-2.5 px-3 text-slate-700 font-medium">{att.unitKerja || '-'}</td>
                          <td className="py-2.5 px-3 text-slate-600">{att.jabatan || '-'}</td>
                          <td className="py-2.5 px-3 text-center">
                            {att.signatureDataUrl ? (
                              <button
                                type="button"
                                onClick={() => setPreviewSignature({ name: att.name, url: att.signatureDataUrl })}
                                className="h-9 w-20 mx-auto bg-slate-50 hover:bg-teal-50 border border-slate-200 hover:border-teal-300 rounded p-1 flex items-center justify-center transition-colors group cursor-pointer"
                                title="Klik untuk memperbesar paraf"
                              >
                                <img
                                  src={att.signatureDataUrl}
                                  alt={`Paraf ${att.name}`}
                                  className="max-h-full max-w-full object-contain"
                                />
                              </button>
                            ) : (
                              <span className="text-slate-300 italic text-[11px]">-</span>
                            )}
                          </td>
                          <td className="py-2.5 px-3 text-center">
                            <button
                              type="button"
                              onClick={() => {
                                if (window.confirm(`Hapus entri kehadiran untuk ${att.name}?`)) {
                                  onDeleteAttendee(att.id);
                                }
                              }}
                              className="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors mx-auto cursor-pointer"
                              title="Hapus kehadiran ini"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            {/* Modal Footer */}
            <div className="p-3 sm:p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
              <div className="text-xs text-slate-500 font-medium">
                Total tercatat: <strong className="text-slate-800">{modalAttendees.length}</strong> orang hadir
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => {
                    handleExportPdf(activeModalAgenda === 'ALL' ? 'ALL' : activeModalAgenda);
                  }}
                  className="sm:hidden px-3 py-1.5 rounded-xl text-xs font-bold text-teal-800 bg-teal-50 border border-teal-200 flex items-center gap-1"
                >
                  <Download className="w-3.5 h-3.5" />
                  <span>Unduh PDF</span>
                </button>
                <button
                  type="button"
                  onClick={handleCloseAgendaModal}
                  className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 transition-colors shadow-xs cursor-pointer"
                >
                  Tutup Jendela
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Preview Paraf Besar */}
      {previewSignature && (
        <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl p-5 max-w-md w-full border border-slate-200 shadow-2xl space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
              <div>
                <span className="text-xs text-slate-500 font-medium">Pratinjau Paraf Digital</span>
                <h3 className="text-sm font-bold text-slate-900">{previewSignature.name}</h3>
              </div>
              <button
                onClick={() => setPreviewSignature(null)}
                className="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center"
              >
                ✕
              </button>
            </div>
            <div className="h-44 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center p-4">
              <img
                src={previewSignature.url}
                alt="Paraf"
                className="max-h-full max-w-full object-contain filter drop-shadow-sm"
              />
            </div>
            <div className="text-right">
              <button
                onClick={() => setPreviewSignature(null)}
                className="px-4 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Tambah Peserta Manual */}
      {isManualAddOpen && (
        <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl p-5 max-w-md w-full border border-slate-200 shadow-2xl space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
              <h3 className="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                <Plus className="w-4 h-4 text-teal-700" />
                <span>Tambah Peserta Presensi Manual</span>
              </h3>
              <button
                onClick={() => setIsManualAddOpen(false)}
                className="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleManualAddSubmit} className="space-y-3">
              {manualTargetAgenda && (
                <div className="p-2.5 bg-teal-50 border border-teal-200 rounded-xl text-xs text-teal-900 flex items-center justify-between">
                  <span className="font-semibold text-teal-700">Agenda Dituju:</span>
                  <span className="font-bold truncate max-w-[210px]" title={manualTargetAgenda}>
                    {manualTargetAgenda}
                  </span>
                </div>
              )}

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">NIP (Opsional)</label>
                <input
                  type="text"
                  value={manualNip}
                  onChange={(e) => setManualNip(e.target.value)}
                  placeholder="NIP Pegawai"
                  className="w-full text-xs p-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600"
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">
                  Nama Lengkap <span className="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  value={manualName}
                  onChange={(e) => setManualName(e.target.value)}
                  placeholder="Nama lengkap peserta"
                  required
                  className="w-full text-xs p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600"
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Unit Kerja / Asal Instansi</label>
                <input
                  type="text"
                  value={manualUnitKerja}
                  onChange={(e) => setManualUnitKerja(e.target.value)}
                  placeholder="Contoh: Keperawatan Bandung"
                  className="w-full text-xs p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600"
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Jabatan</label>
                <input
                  type="text"
                  value={manualJabatan}
                  onChange={(e) => setManualJabatan(e.target.value)}
                  placeholder="Contoh: Dosen / Pengelola"
                  className="w-full text-xs p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600"
                />
              </div>

              <div className="pt-2 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setIsManualAddOpen(false)}
                  className="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 shadow-sm"
                >
                  Simpan Peserta
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Export PDF Modal dengan Seleksi Agenda & Upload Kop Surat */}
      <MeetingExportPdfModal
        isOpen={isExportModalOpen}
        onClose={() => setIsExportModalOpen(false)}
        session={session}
        attendees={exportTargetAgenda && exportTargetAgenda !== 'ALL' ? attendees : sessionAttendees}
        initialSelectedAgenda={exportTargetAgenda}
      />

      {/* QR Code Modal Khusus Sesi Rapat Terpilih */}
      <MeetingQRCodeModal
        isOpen={isQRModalOpen}
        onClose={() => setIsQRModalOpen(false)}
        session={session}
      />

      {/* Modal Tambah Sesi Rapat Baru */}
      <MeetingCreateModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onCreate={(newSess) => {
          if (onCreateNewSession) {
            onCreateNewSession(newSess);
          }
        }}
      />

      {/* Modal Konfigurasi Sesi Rapat (Dialog Melayang) */}
      <MeetingConfigModal
        isOpen={isConfigModalOpen}
        onClose={() => {
          setIsConfigModalOpen(false);
          setSessionToConfigure(null);
        }}
        session={sessionToConfigure || session}
        onSave={handleSaveConfig}
        onDeleteSession={onDeleteSession}
        canDelete={allSessions.length > 1}
      />
    </div>
  );
};

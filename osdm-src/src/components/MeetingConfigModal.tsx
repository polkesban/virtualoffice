import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  Save,
  Calendar,
  Clock,
  MapPin,
  User,
  Briefcase,
  FileText,
  Edit3,
  Trash2,
  CheckCircle2,
  Sliders,
  Check,
} from 'lucide-react';
import { MeetingSession } from '../types';
import { saveAgendaPermanently } from '../utils/meetingAgendaHelper';

interface MeetingConfigModalProps {
  isOpen: boolean;
  onClose: () => void;
  session: MeetingSession | null;
  onSave: (updatedSession: MeetingSession) => void;
  onDeleteSession?: (sessionId: string) => void;
  canDelete?: boolean;
}

export const MeetingConfigModal: React.FC<MeetingConfigModalProps> = ({
  isOpen,
  onClose,
  session,
  onSave,
  onDeleteSession,
  canDelete = false,
}) => {
  const [agenda, setAgenda] = useState('');
  const [date, setDate] = useState('');
  const [timeStart, setTimeStart] = useState('09:00');
  const [timeEnd, setTimeEnd] = useState('12:00');
  const [location, setLocation] = useState('');
  const [pimpinanRapat, setPimpinanRapat] = useState('');
  const [jabatanPimpinan, setJabatanPimpinan] = useState('');
  const [notulis, setNotulis] = useState('');
  const [isActive, setIsActive] = useState(true);

  // Sync state when modal opens or session changes
  useEffect(() => {
    if (session && isOpen) {
      setAgenda(session.agenda || '');
      setDate(session.date || '');
      setTimeStart(session.timeStart || '09:00');
      setTimeEnd(session.timeEnd || 'Selesai');
      setLocation(session.location || '');
      setPimpinanRapat(session.pimpinanRapat || '');
      setJabatanPimpinan(session.jabatanPimpinan || '');
      setNotulis(session.notulis || '');
      setIsActive(session.isActive !== false);
    }
  }, [session, isOpen]);

  if (!isOpen || !session) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!agenda.trim()) {
      alert('Agenda / topik pembahasan rapat tidak boleh kosong.');
      return;
    }

    const cleanAgenda = agenda.trim();
    if (cleanAgenda) {
      saveAgendaPermanently(cleanAgenda);
    }

    const updated: MeetingSession = {
      ...session,
      title: 'Daftar Hadir Rapat',
      agenda: cleanAgenda,
      agendas: cleanAgenda ? [cleanAgenda] : session.agendas,
      date: date || session.date,
      timeStart: timeStart || session.timeStart,
      timeEnd: timeEnd || session.timeEnd,
      location: location.trim(),
      pimpinanRapat: pimpinanRapat.trim(),
      jabatanPimpinan: jabatanPimpinan.trim() || undefined,
      notulis: notulis.trim() || undefined,
      isActive: isActive,
    };

    onSave(updated);
    onClose();
  };

  const handleDelete = () => {
    if (!onDeleteSession || !session) return;
    if (
      window.confirm(
        `Apakah Anda yakin ingin menghapus sesi rapat:\n"${
          session.agenda || session.title
        }"?\n\nPerhatian: Sesi ini akan dihapus dari daftar rapat aktif.`
      )
    ) {
      onDeleteSession(session.id);
      onClose();
    }
  };

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs font-sans">
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 15 }}
          className="relative w-full max-w-xl bg-white border border-slate-200 rounded-3xl p-6 sm:p-7 shadow-2xl text-slate-900 overflow-hidden max-h-[92vh] flex flex-col"
        >
          {/* Top Accent Gradient */}
          <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-teal-600 via-emerald-600 to-teal-700" />

          {/* Close button */}
          <button
            onClick={onClose}
            type="button"
            className="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-700 rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Header */}
          <div className="mb-5 pr-6">
            <div className="flex items-center gap-2.5 mb-1">
              <div className="p-2 rounded-xl bg-teal-50 text-teal-700 border border-teal-100">
                <Sliders className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-lg font-black tracking-tight text-slate-900">
                  Konfigurasi Agenda & Detail Rapat
                </h3>
                <span className="text-[11px] font-mono text-slate-400">ID Sesi: {session.id}</span>
              </div>
            </div>
            <p className="text-xs text-slate-500">
              Perbarui judul kegiatan, topik agenda, jadwal, lokasi, serta pimpinan rapat untuk sesi ini.
            </p>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="overflow-y-auto pr-1 space-y-4 flex-1">
            {/* Status Sesi Radio/Cards */}
            <div className="p-3 bg-slate-50 rounded-2xl border border-slate-200/80">
              <label className="text-xs font-bold text-slate-700 block mb-2">Status Sesi Rapat</label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => setIsActive(true)}
                  className={`p-2.5 rounded-xl border text-left transition-all flex items-center gap-2 cursor-pointer ${
                    isActive
                      ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-bold shadow-2xs'
                      : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <span className={`w-2.5 h-2.5 rounded-full ${isActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'}`} />
                  <div className="text-xs">
                    <span className="block font-bold">Sedang Berjalan</span>
                    <span className="text-[10px] text-slate-500">Terbuka untuk presensi</span>
                  </div>
                </button>

                <button
                  type="button"
                  onClick={() => setIsActive(false)}
                  className={`p-2.5 rounded-xl border text-left transition-all flex items-center gap-2 cursor-pointer ${
                    !isActive
                      ? 'bg-slate-100 border-slate-400 text-slate-900 font-bold shadow-2xs'
                      : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <span className="w-2.5 h-2.5 rounded-full bg-slate-400" />
                  <div className="text-xs">
                    <span className="block font-bold">Selesai / Ditutup</span>
                    <span className="text-[10px] text-slate-500">Presensi ditutup</span>
                  </div>
                </button>
              </div>
            </div>

            {/* Agenda / Topik */}
            <div>
              <div className="flex items-center justify-between mb-1">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                  <FileText className="w-3.5 h-3.5 text-teal-700" />
                  <span>Agenda / Topik Pembahasan</span>
                  <span className="text-rose-500">*</span>
                </label>
                <span className="text-[10px] text-teal-700 font-semibold bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200/70">
                  Tersimpan Permanen
                </span>
              </div>
              <input
                type="text"
                value={agenda}
                onChange={(e) => setAgenda(e.target.value)}
                placeholder="Contoh: Evaluasi Capaian Kinerja Triwulan III (SKP)"
                required
                className="w-full text-xs sm:text-sm p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900 font-semibold"
              />
            </div>

            {/* Tanggal & Waktu */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1 mb-1">
                  <Calendar className="w-3.5 h-3.5 text-slate-500" />
                  <span>Tanggal</span>
                </label>
                <input
                  type="date"
                  value={date}
                  onChange={(e) => setDate(e.target.value)}
                  onClick={(e) => {
                    try {
                      e.currentTarget.showPicker();
                    } catch {}
                  }}
                  required
                  className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900 cursor-pointer"
                />
              </div>
              <div>
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1 mb-1">
                  <Clock className="w-3.5 h-3.5 text-slate-500" />
                  <span>Jam Mulai</span>
                </label>
                <input
                  type="time"
                  value={timeStart}
                  onChange={(e) => setTimeStart(e.target.value)}
                  required
                  className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
              <div>
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1 mb-1">
                  <Clock className="w-3.5 h-3.5 text-slate-500" />
                  <span>Jam Selesai</span>
                </label>
                <input
                  type="text"
                  value={timeEnd}
                  onChange={(e) => setTimeEnd(e.target.value)}
                  placeholder="12:00 / Selesai"
                  className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
            </div>

            {/* Lokasi / Ruangan */}
            <div>
              <label className="text-xs font-bold text-slate-700 flex items-center gap-1 mb-1">
                <MapPin className="w-3.5 h-3.5 text-slate-500" />
                <span>Lokasi / Ruangan</span>
              </label>
              <input
                type="text"
                value={location}
                onChange={(e) => setLocation(e.target.value)}
                placeholder="Ruang Rapat Gedung Direktorat Lt. 2 / Hybrid Zoom"
                className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
              />
            </div>

            {/* Pimpinan & Jabatan */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1 mb-1">
                  <User className="w-3.5 h-3.5 text-slate-500" />
                  <span>Pimpinan Rapat</span>
                </label>
                <input
                  type="text"
                  value={pimpinanRapat}
                  onChange={(e) => setPimpinanRapat(e.target.value)}
                  placeholder="Nama pimpinan rapat"
                  className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
              <div>
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1 mb-1">
                  <Briefcase className="w-3.5 h-3.5 text-slate-500" />
                  <span>Jabatan Pimpinan</span>
                </label>
                <input
                  type="text"
                  value={jabatanPimpinan}
                  onChange={(e) => setJabatanPimpinan(e.target.value)}
                  placeholder="Contoh: Direktur / Ketua Tim Kerja"
                  className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
            </div>

            {/* Notulis */}
            <div>
              <label className="text-xs font-bold text-slate-700 block mb-1">Notulis / Sekretaris</label>
              <input
                type="text"
                value={notulis}
                onChange={(e) => setNotulis(e.target.value)}
                placeholder="Nama notulis rapat (opsional)"
                className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
              />
            </div>

            {/* Action Buttons */}
            <div className="pt-4 mt-2 border-t border-slate-200 flex items-center justify-between gap-3">
              <div>
                {canDelete && onDeleteSession && (
                  <button
                    type="button"
                    onClick={handleDelete}
                    className="px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors flex items-center gap-1.5 cursor-pointer"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                    <span>Hapus Sesi</span>
                  </button>
                )}
              </div>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={onClose}
                  className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
                >
                  <Save className="w-4 h-4" />
                  <span>Simpan Perubahan</span>
                </button>
              </div>
            </div>
          </form>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  Plus,
  Calendar,
  Clock,
  MapPin,
  User,
  Briefcase,
  FileText,
  Sparkles,
} from 'lucide-react';
import { MeetingSession } from '../types';

interface MeetingCreateModalProps {
  isOpen: boolean;
  onClose: () => void;
  onCreate: (session: MeetingSession) => void;
}

export const MeetingCreateModal: React.FC<MeetingCreateModalProps> = ({
  isOpen,
  onClose,
  onCreate,
}) => {
  const today = new Date().toISOString().split('T')[0];

  const [agenda, setAgenda] = useState('');
  const [date, setDate] = useState(today);
  const [timeStart, setTimeStart] = useState('09:00');
  const [timeEnd, setTimeEnd] = useState('12:00');
  const [location, setLocation] = useState('Ruang Rapat Gedung Direktorat Lt. 2 Poltekkes Kemenkes Bandung');
  const [pimpinanRapat, setPimpinanRapat] = useState('Dr. Pramita Iriana, S.Kp., M.Biomed');
  const [jabatanPimpinan, setJabatanPimpinan] = useState('Direktur Poltekkes Kemenkes Bandung');
  const [notulis, setNotulis] = useState('');

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!agenda.trim()) return;

    const cleanAgenda = agenda.trim();
    const newSession: MeetingSession = {
      id: `meet-${Date.now()}`,
      title: 'Daftar Hadir Rapat',
      agenda: cleanAgenda,
      agendas: [cleanAgenda],
      date: date || today,
      timeStart: timeStart || '09:00',
      timeEnd: timeEnd || 'Selesai',
      location: location.trim(),
      pimpinanRapat: pimpinanRapat.trim(),
      jabatanPimpinan: jabatanPimpinan.trim(),
      notulis: notulis.trim(),
      isActive: true,
      createdAt: new Date().toISOString(),
    };

    onCreate(newSession);
    onClose();
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
            className="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-700 rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Header */}
          <div className="mb-5 pr-6">
            <div className="flex items-center gap-2.5 mb-1">
              <div className="p-2 rounded-xl bg-teal-50 text-teal-700 border border-teal-100">
                <Plus className="w-5 h-5" />
              </div>
              <h3 className="text-lg font-black tracking-tight text-slate-900">
                Buat Sesi Agenda Rapat Baru
              </h3>
            </div>
            <p className="text-xs text-slate-500">
              Buat agenda rapat tambahan yang berjalan bersamaan di ruangan/waktu yang sama tanpa menimpa data rapat lain.
            </p>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-4 overflow-y-auto pr-1 flex-1 py-1">
            <div>
              <label className="text-xs font-bold text-slate-700 block mb-1">
                Agenda / Topik Pembahasan Utama <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                required
                value={agenda}
                onChange={(e) => setAgenda(e.target.value)}
                placeholder="Contoh: Rapat Koordinasi dan Evaluasi Sasaran Kinerja Triwulan III"
                className="w-full text-xs sm:text-sm p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900 font-medium"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Tanggal</label>
                <input
                  type="date"
                  value={date}
                  onChange={(e) => setDate(e.target.value)}
                  onClick={(e) => {
                    try {
                      e.currentTarget.showPicker();
                    } catch {}
                  }}
                  className="w-full text-xs p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900 cursor-pointer"
                />
              </div>
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Jam Mulai</label>
                <input
                  type="time"
                  value={timeStart}
                  onChange={(e) => setTimeStart(e.target.value)}
                  className="w-full text-xs p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Jam Selesai</label>
                <input
                  type="text"
                  value={timeEnd}
                  onChange={(e) => setTimeEnd(e.target.value)}
                  placeholder="12:00 / Selesai"
                  className="w-full text-xs p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
            </div>

            <div>
              <label className="text-xs font-bold text-slate-700 block mb-1">Lokasi / Ruangan</label>
              <input
                type="text"
                value={location}
                onChange={(e) => setLocation(e.target.value)}
                placeholder="Contoh: Auditorium Gedung Direktorat / Daring via Zoom"
                className="w-full text-xs sm:text-sm p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Pimpinan Rapat</label>
                <input
                  type="text"
                  value={pimpinanRapat}
                  onChange={(e) => setPimpinanRapat(e.target.value)}
                  placeholder="Nama pimpinan rapat"
                  className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Jabatan Pimpinan</label>
                <input
                  type="text"
                  value={jabatanPimpinan}
                  onChange={(e) => setJabatanPimpinan(e.target.value)}
                  placeholder="Contoh: Direktur / Ketua Jurusan"
                  className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
                />
              </div>
            </div>

            <div>
              <label className="text-xs font-bold text-slate-700 block mb-1">Notulis Rapat (Opsional)</label>
              <input
                type="text"
                value={notulis}
                onChange={(e) => setNotulis(e.target.value)}
                placeholder="Nama notulis rapat"
                className="w-full text-xs sm:text-sm p-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600 text-slate-900"
              />
            </div>

            <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
              >
                Batal
              </button>
              <button
                type="submit"
                className="px-5 py-2 rounded-xl text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 transition-colors shadow-sm flex items-center gap-1.5"
              >
                <Plus className="w-4 h-4" />
                <span>Buat Sesi Rapat</span>
              </button>
            </div>
          </form>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

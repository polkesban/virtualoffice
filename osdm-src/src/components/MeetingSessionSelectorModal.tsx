import React from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  Calendar,
  Clock,
  MapPin,
  ChevronRight,
} from 'lucide-react';
import { MeetingSession } from '../types';

interface MeetingSessionSelectorModalProps {
  isOpen: boolean;
  onClose: () => void;
  sessions: MeetingSession[];
  onSelectSession: (session: MeetingSession) => void;
}

export const MeetingSessionSelectorModal: React.FC<MeetingSessionSelectorModalProps> = ({
  isOpen,
  onClose,
  sessions,
  onSelectSession,
}) => {
  if (!isOpen) return null;

  // Filter only active sessions or show all if all inactive
  const activeSessions = sessions.filter((s) => s.isActive !== false);
  const displaySessions = activeSessions.length > 0 ? activeSessions : sessions;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs font-sans">
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 15 }}
          className="relative w-full max-w-lg bg-white border border-slate-200 rounded-3xl p-5 sm:p-6 shadow-2xl text-slate-900 overflow-hidden max-h-[88vh] flex flex-col"
        >
          {/* Top Accent Gradient */}
          <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-teal-600 via-emerald-600 to-teal-700" />

          {/* Close button */}
          <button
            onClick={onClose}
            className="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-700 rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Header */}
          <div className="text-center mb-3.5 pr-6 pt-1">
            <h3 className="text-lg sm:text-xl font-black tracking-tight text-slate-900">
              Pilih Agenda Rapat Anda
            </h3>
          </div>

          {/* Session Cards List */}
          <div className="space-y-2.5 overflow-y-auto pr-1 flex-1 py-1">
            {displaySessions.map((s) => (
              <div
                key={s.id}
                onClick={() => {
                  onSelectSession(s);
                  onClose();
                }}
                className="group p-3.5 sm:p-4 rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-teal-50/50 hover:border-teal-300 hover:shadow-md transition-all cursor-pointer text-left relative overflow-hidden"
              >
                <div className="flex items-center justify-between gap-3">
                  <div className="space-y-1 flex-1 min-w-0">
                    <div>
                      <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        Rapat Berjalan
                      </span>
                    </div>

                    <h4 className="font-bold text-slate-900 text-sm sm:text-base leading-snug group-hover:text-teal-900 transition-colors">
                      {s.agenda || s.title || 'Daftar Hadir Rapat'}
                    </h4>

                    {s.title && s.title !== s.agenda && s.title !== 'Daftar Hadir Rapat' && (
                      <p className="text-xs text-slate-500 font-medium line-clamp-1">
                        {s.title}
                      </p>
                    )}

                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-slate-600 text-[11px] pt-0.5">
                      <span className="flex items-center gap-1 font-medium">
                        <Calendar className="w-3 h-3 text-teal-600 shrink-0" />
                        {s.date}
                      </span>
                      <span className="flex items-center gap-1 font-medium">
                        <Clock className="w-3 h-3 text-teal-600 shrink-0" />
                        {s.timeStart} - {s.timeEnd || 'Selesai'} WIB
                      </span>
                      {s.location && (
                        <span className="flex items-start sm:items-center gap-1 font-medium text-slate-700 w-full sm:w-auto">
                          <MapPin className="w-3 h-3 text-teal-600 shrink-0 mt-0.5 sm:mt-0" />
                          <span className="break-words leading-tight">{s.location}</span>
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="p-2 rounded-xl bg-white border border-slate-200 group-hover:bg-teal-700 group-hover:text-white group-hover:border-teal-700 transition-all shrink-0 shadow-2xs">
                    <ChevronRight className="w-5 h-5" />
                  </div>
                </div>
              </div>
            ))}
          </div>

          <div className="pt-2.5 mt-1 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
            <span>Poltekkes Kemenkes Bandung</span>
            <span>OSDM Portal Presensi</span>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

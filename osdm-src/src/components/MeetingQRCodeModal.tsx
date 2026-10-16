import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  QrCode,
  Copy,
  Check,
  Download,
  Calendar,
  Clock,
  MapPin,
  ExternalLink,
} from 'lucide-react';
import { MeetingSession } from '../types';

interface MeetingQRCodeModalProps {
  isOpen: boolean;
  onClose: () => void;
  session: MeetingSession;
}

export const MeetingQRCodeModal: React.FC<MeetingQRCodeModalProps> = ({
  isOpen,
  onClose,
  session,
}) => {
  const [copied, setCopied] = useState(false);

  if (!isOpen) return null;

  // Build direct URL targeting this specific meeting
  const baseUrl = window.location.href.split('#')[0].split('?')[0];
  const meetingDirectUrl = `${baseUrl}?meetId=${encodeURIComponent(session.id)}`;

  const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(
    meetingDirectUrl
  )}&bgcolor=ffffff&color=0f172a&margin=1`;

  const handleCopy = () => {
    navigator.clipboard.writeText(meetingDirectUrl);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownloadQR = async () => {
    try {
      const response = await fetch(qrCodeUrl);
      const blob = await response.blob();
      const blobUrl = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = blobUrl;
      const cleanTitle = (session.agenda || session.title || 'Rapat')
        .replace(/[^a-zA-Z0-9]/g, '_')
        .substring(0, 30);
      a.download = `QR_Presensi_${cleanTitle}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(blobUrl);
    } catch {
      window.open(qrCodeUrl, '_blank');
    }
  };

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs font-sans">
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 15 }}
          className="relative w-full max-w-lg bg-white border border-slate-200 rounded-3xl p-6 sm:p-7 shadow-2xl text-slate-900 overflow-hidden max-h-[90vh] flex flex-col"
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
          <div className="text-center mb-4 pr-6">
            <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 border border-teal-100 mb-2.5 shadow-xs">
              <QrCode className="w-6 h-6" />
            </div>
            <h3 className="text-base sm:text-lg font-black tracking-tight text-slate-900">
              QR Code & Tautan Khusus Presensi Rapat
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Peserta cukup scan QR code ini dengan kamera HP untuk langsung mengisi daftar hadir
            </p>
          </div>

          {/* Meeting Info Card */}
          <div className="bg-teal-50/70 border border-teal-100 rounded-2xl p-3.5 mb-4 text-xs space-y-1.5">
            <span className="text-[10px] font-bold text-teal-700 uppercase tracking-wider block">
              Agenda Rapat
            </span>
            <h4 className="font-extrabold text-slate-900 text-sm leading-snug line-clamp-2">
              {session.agenda || session.title || 'Daftar Hadir Rapat'}
            </h4>
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-slate-600 text-[11px] pt-1 border-t border-teal-100/60">
              <span className="flex items-center gap-1 font-medium">
                <Calendar className="w-3 h-3 text-teal-600" />
                {session.date}
              </span>
              <span className="flex items-center gap-1 font-medium">
                <Clock className="w-3 h-3 text-teal-600" />
                {session.timeStart} - {session.timeEnd || 'Selesai'} WIB
              </span>
              {session.location && (
                <span className="flex items-center gap-1 font-medium">
                  <MapPin className="w-3 h-3 text-teal-600" />
                  {session.location}
                </span>
              )}
            </div>
          </div>

          {/* QR Container */}
          <div className="flex flex-col items-center justify-center p-4 sm:p-5 bg-slate-50 rounded-2xl border border-slate-200 mb-4">
            <div className="p-3 bg-white rounded-2xl shadow-sm border border-slate-200">
              <img
                src={qrCodeUrl}
                alt="QR Code Presensi Rapat"
                className="w-48 h-48 sm:w-56 sm:h-56 object-contain"
                loading="eager"
              />
            </div>
            <p className="text-[11px] text-slate-500 font-medium mt-2.5 text-center">
              Arahkan kamera smartphone peserta ke kode QR di atas
            </p>
          </div>

          {/* Direct Link Input & Action Buttons */}
          <div className="space-y-3 mt-auto">
            <div className="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl p-2">
              <input
                type="text"
                readOnly
                value={meetingDirectUrl}
                className="bg-transparent text-xs text-slate-700 w-full outline-none px-1 select-all font-mono"
              />
              <button
                onClick={handleCopy}
                className="flex items-center gap-1.5 px-3 py-1.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-lg transition-colors shrink-0 shadow-xs"
              >
                {copied ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
                <span>{copied ? 'Tersalin' : 'Salin'}</span>
              </button>
            </div>

            <div className="grid grid-cols-2 gap-2">
              <button
                onClick={handleDownloadQR}
                className="flex items-center justify-center gap-2 py-2.5 px-3 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 shadow-xs transition-colors"
              >
                <Download className="w-4 h-4 text-teal-600" />
                <span>Unduh Gambar QR</span>
              </button>
              <button
                onClick={() => window.open(meetingDirectUrl, '_blank')}
                className="flex items-center justify-center gap-2 py-2.5 px-3 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors"
              >
                <ExternalLink className="w-4 h-4" />
                <span>Buka Formulir</span>
              </button>
            </div>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

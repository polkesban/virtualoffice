import React, { useState, useEffect } from 'react';
import { Lock, KeyRound, Eye, EyeOff, X, ArrowRight, AlertCircle, ShieldCheck } from 'lucide-react';

export type AdminRole = 'superadmin' | 'wfa_admin' | 'kebugaran_admin' | 'meeting_admin' | 'monitoring_all_admin';

interface AdminAuthModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: (role: AdminRole) => void;
  savedPin: string;
  savedWfaPin?: string;
  savedKebugaranPin?: string;
  savedMeetingPin?: string;
  savedMonitoringAllPin?: string;
}

export const AdminAuthModal: React.FC<AdminAuthModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  savedPin,
  savedWfaPin = 'wfa123',
  savedKebugaranPin = 'fit123',
  savedMeetingPin = 'rapat123',
  savedMonitoringAllPin = 'pantau123',
}) => {
  const [pinInput, setPinInput] = useState('');
  const [showPin, setShowPin] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Reset all state whenever modal is opened
  useEffect(() => {
    if (isOpen) {
      setPinInput('');
      setShowPin(false);
      setErrorMsg('');
      setIsSubmitting(false);
    }
  }, [isOpen]);

  const handleClose = () => {
    setPinInput('');
    setErrorMsg('');
    setIsSubmitting(false);
    onClose();
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');

    // Validate PIN
    const input = pinInput.trim();
    if (!input) {
      setErrorMsg('Harap masukkan PIN / Password Akses');
      setIsSubmitting(false);
      return;
    }

    setIsSubmitting(true);

    try {
      if (input === savedPin.trim()) {
        // 1. Superadmin Success
        setErrorMsg('');
        setPinInput('');
        onSuccess('superadmin');
      } else if (savedWfaPin && input === savedWfaPin.trim()) {
        // 2. WFA Admin Success
        setErrorMsg('');
        setPinInput('');
        onSuccess('wfa_admin');
      } else if (savedKebugaranPin && input === savedKebugaranPin.trim()) {
        // 3. Kebugaran Admin Success
        setErrorMsg('');
        setPinInput('');
        onSuccess('kebugaran_admin');
      } else if (savedMeetingPin && input === savedMeetingPin.trim()) {
        // 4. Meeting Admin Success
        setErrorMsg('');
        setPinInput('');
        onSuccess('meeting_admin');
      } else if (savedMonitoringAllPin && input === savedMonitoringAllPin.trim()) {
        // 5. Monitoring All Admin Success
        setErrorMsg('');
        setPinInput('');
        onSuccess('monitoring_all_admin');
      } else {
        setErrorMsg('PIN / Password salah. Silakan coba lagi.');
      }
    } finally {
      // Crucial: Always reset submitting state so button is never permanently disabled
      setIsSubmitting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div
        className="w-full max-w-sm bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden text-slate-900 animate-in zoom-in-95 duration-200"
        role="dialog"
        aria-modal="true"
      >
        {/* Eye-catching Gradient Header */}
        <div className="relative overflow-hidden">
          {/* Gradient Background */}
          <div className="bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 pt-8 pb-10 text-center relative">
            {/* Decorative Glow Circles */}
            <div className="absolute -top-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl" />
            <div className="absolute -bottom-8 -right-8 w-32 h-32 bg-purple-400/20 rounded-full blur-2xl" />
            <div className="absolute top-4 right-12 w-16 h-16 bg-indigo-400/15 rounded-full blur-xl" />

            {/* Close Button */}
            <button
              onClick={handleClose}
              className="absolute top-3 right-3 p-1.5 rounded-full text-white/50 hover:text-white hover:bg-white/15 transition-all cursor-pointer"
              title="Tutup"
            >
              <X className="w-4 h-4" />
            </button>

            {/* Security Access Shield Icon */}
            <div className="relative z-10 mx-auto w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/25 flex items-center justify-center mb-4 shadow-lg shadow-indigo-900/30">
              <div className="absolute inset-0 rounded-2xl bg-gradient-to-br from-white/20 to-transparent" />
              <ShieldCheck className="w-8 h-8 text-white relative z-10" />
            </div>

            {/* Title */}
            <h2 className="text-lg font-extrabold text-white tracking-tight relative z-10">
              Akses Pengelola Portal
            </h2>
            <p className="text-xs text-indigo-200/90 mt-1.5 relative z-10 font-medium">
              Tim Kerja OSDM • Poltekkes Kemenkes Bandung
            </p>
          </div>

          {/* Wave Separator */}
          <div className="absolute -bottom-1 left-0 right-0">
            <svg viewBox="0 0 400 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full" preserveAspectRatio="none">
              <path d="M0 24V8C40 2 80 0 120 2C160 4 200 12 240 14C280 16 320 10 360 6C380 4 400 4 400 4V24H0Z" fill="white" />
            </svg>
          </div>
        </div>

        {/* Body Form */}
        <form onSubmit={handleSubmit} className="px-6 pb-6 pt-2 space-y-4">
          <div>
            <label className="block text-xs font-bold text-slate-700 mb-2">
              PIN / Password Akses
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <KeyRound className="w-4 h-4" />
              </div>
              <input
                type={showPin ? 'text' : 'password'}
                autoFocus
                value={pinInput}
                onChange={(e) => {
                  setPinInput(e.target.value);
                  if (errorMsg) setErrorMsg('');
                }}
                placeholder="Masukkan PIN Akses..."
                className="w-full pl-10 pr-10 py-3 bg-slate-50/80 border border-slate-200 rounded-xl text-sm font-mono text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:bg-white transition-all"
              />
              <button
                type="button"
                onClick={() => setShowPin(!showPin)}
                className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
              >
                {showPin ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>

            {errorMsg && (
              <div className="flex items-center gap-1.5 text-xs text-red-600 mt-2 font-medium animate-in fade-in duration-150">
                <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                <span>{errorMsg}</span>
              </div>
            )}
          </div>

          {/* Help hint */}
          <div className="text-center pt-0.5">
            <p className="text-[11px] text-slate-400">
              Lupa PIN akses? <span className="text-indigo-600 font-medium">Hubungi Tim Kerja OSDM</span>
            </p>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center gap-2.5 pt-1">
            <button
              type="button"
              onClick={handleClose}
              className="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="flex-[1.4] py-2.5 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-xs font-bold text-white shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-1.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            >
              <Lock className="w-3.5 h-3.5" />
              <span>Masuk</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

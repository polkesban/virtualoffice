import React, { useState, useEffect, useRef, useMemo } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { Lock, Unlock, X, AlertCircle, Eye, EyeOff, ArrowRight, Sparkles, MessageCircle } from 'lucide-react';
import { MenuItem } from '../types';

interface MenuPinModalProps {
  isOpen: boolean;
  menu: MenuItem | null;
  logoUrl?: string;
  adminPin?: string;
  osdmContactWa?: string;
  onClose: () => void;
  onSuccess: (menu: MenuItem) => void;
}

export const MenuPinModal: React.FC<MenuPinModalProps> = ({
  isOpen,
  menu,
  adminPin,
  osdmContactWa = '08119712525',
  onClose,
  onSuccess,
}) => {
  const [pinInput, setPinInput] = useState('');
  const [showPin, setShowPin] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  // International WhatsApp format
  const waNumber = useMemo(() => {
    const raw = (osdmContactWa || '08119712525').trim().replace(/[^0-9]/g, '');
    return raw.startsWith('0') ? '62' + raw.slice(1) : raw;
  }, [osdmContactWa]);

  const waUrl = useMemo(() => {
    const text = encodeURIComponent(
      `Halo Tim Kerja OSDM Poltekkes Kemenkes Bandung, saya ingin menanyakan kode PIN akses untuk "${menu?.title || 'layanan'}". Terima kasih.`
    );
    return `https://wa.me/${waNumber}?text=${text}`;
  }, [waNumber, menu?.title]);

  useEffect(() => {
    if (isOpen) {
      setPinInput('');
      setErrorMessage(null);
      setShowPin(false);
      setIsSuccess(false);
      const timer = setTimeout(() => {
        inputRef.current?.focus();
      }, 120);
      return () => clearTimeout(timer);
    }
  }, [isOpen, menu]);

  // Handle Escape key
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen || !menu) return null;

  const handleVerify = (e?: React.FormEvent) => {
    if (e) e.preventDefault();

    const itemPin = (menu.pinCode || '').trim();
    const globalPin = (adminPin || '').trim();
    const enteredPin = pinInput.trim();

    if (!enteredPin) {
      setErrorMessage('Harap masukkan kode PIN.');
      inputRef.current?.focus();
      return;
    }

    // Accepts item-specific PIN, global Admin PIN, or default '1234'
    const isMatched =
      (itemPin && enteredPin === itemPin) ||
      (globalPin && enteredPin === globalPin) ||
      (!itemPin && enteredPin === '1234');

    if (isMatched) {
      setIsSuccess(true);
      setErrorMessage(null);
      setTimeout(() => {
        onSuccess(menu);
        onClose();
      }, 350);
    } else {
      setErrorMessage('Kode PIN salah. Silakan coba lagi.');
      inputRef.current?.focus();
      inputRef.current?.select();
    }
  };

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-xs">
        {/* Backdrop click to close */}
        <div className="absolute inset-0" onClick={onClose} />

        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 8 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 8 }}
          transition={{ duration: 0.18, ease: 'easeOut' }}
          className="relative w-full max-w-[340px] bg-white rounded-2xl shadow-xl border border-slate-200/80 p-5 text-slate-800 z-10"
        >
          {/* Close button */}
          <button
            type="button"
            onClick={onClose}
            className="absolute top-3.5 right-3.5 p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
            title="Tutup"
          >
            <X className="w-4 h-4" />
          </button>

          {/* Header: Centered Lock Icon & Clean Title */}
          <div className="text-center pt-1">
            <div
              className={`w-11 h-11 mx-auto rounded-xl flex items-center justify-center transition-all ${
                isSuccess
                  ? 'bg-emerald-100 text-emerald-600 scale-105'
                  : 'bg-emerald-50 text-emerald-600 border border-emerald-100/80'
              }`}
            >
              {isSuccess ? (
                <Unlock className="w-5 h-5 text-emerald-600 animate-bounce" />
              ) : (
                <Lock className="w-5 h-5 text-emerald-600" />
              )}
            </div>

            <h3 className="text-sm font-bold text-slate-900 mt-2.5 px-3 leading-snug">
              {menu.title}
            </h3>

            <p className="text-[11px] text-slate-500 mt-0.5">
              Masukkan PIN untuk membuka akses
            </p>

            {menu.pinHint && (
              <div className="mt-1.5 inline-flex items-center gap-1 text-[11px] text-amber-800 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-md">
                <Sparkles className="w-3 h-3 text-amber-600 shrink-0" />
                <span>Petunjuk: {menu.pinHint}</span>
              </div>
            )}
          </div>

          {/* Form Input PIN */}
          <form onSubmit={handleVerify} className="mt-3.5">
            <div className="relative">
              <input
                ref={inputRef}
                type={showPin ? 'text' : 'password'}
                placeholder="Masukkan PIN..."
                value={pinInput}
                onChange={(e) => {
                  setPinInput(e.target.value);
                  if (errorMessage) setErrorMessage(null);
                }}
                autoComplete="off"
                className={`w-full h-10 px-3 pr-9 bg-slate-50 border rounded-xl text-center font-mono font-bold tracking-[0.25em] text-base text-slate-900 placeholder:font-sans placeholder:font-normal placeholder:tracking-normal placeholder:text-xs placeholder:text-slate-400 focus:bg-white focus:outline-none transition-all ${
                  errorMessage
                    ? 'border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                    : isSuccess
                    ? 'border-emerald-500 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20'
                    : 'border-slate-200 hover:border-slate-300 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20'
                }`}
              />

              <button
                type="button"
                onClick={() => setShowPin(!showPin)}
                className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
                tabIndex={-1}
                title={showPin ? 'Sembunyikan PIN' : 'Lihat PIN'}
              >
                {showPin ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>

            {/* Error Message */}
            {errorMessage && (
              <motion.div
                initial={{ opacity: 0, y: -4 }}
                animate={{ opacity: 1, y: 0 }}
                className="mt-2 p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-[11px] leading-tight flex items-center justify-center gap-1.5"
              >
                <AlertCircle className="w-3.5 h-3.5 shrink-0 text-rose-500" />
                <span>{errorMessage}</span>
              </motion.div>
            )}

            {/* Success Message */}
            {isSuccess && (
              <motion.div
                initial={{ opacity: 0, y: -4 }}
                animate={{ opacity: 1, y: 0 }}
                className="mt-2 p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-semibold flex items-center justify-center gap-1.5"
              >
                <Unlock className="w-3.5 h-3.5 shrink-0 text-emerald-600" />
                <span>PIN Benar! Membuka...</span>
              </motion.div>
            )}

            {/* Action Buttons */}
            <div className="flex gap-2 mt-3.5">
              <button
                type="button"
                onClick={onClose}
                className="w-1/3 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-600 text-xs font-semibold transition-colors cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={isSuccess}
                className="w-2/3 h-9 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-1.5 disabled:opacity-75 cursor-pointer"
              >
                <span>{isSuccess ? 'Membuka...' : 'Buka Akses'}</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </button>
            </div>

            {/* Contact OSDM Help Note */}
            <div className="mt-3.5 pt-2.5 border-t border-slate-100 text-center">
              <a
                href={waUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1 text-[11px] text-slate-500 hover:text-emerald-700 font-medium transition-colors"
                title="Hubungi Tim Kerja OSDM via WhatsApp"
              >
                <MessageCircle className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <span>Butuh PIN? Hubungi Tim Kerja OSDM</span>
              </a>
            </div>
          </form>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

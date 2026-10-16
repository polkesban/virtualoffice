import React, { useState, useRef, useEffect } from 'react';
import {
  SlidersHorizontal,
  QrCode,
  Zap,
  ShieldAlert,
  LogOut,
  Send,
  Check,
  Menu,
  X,
  LayoutGrid,
  Palette,
  Users,
  BarChart3,
  Sparkles,
  Activity,
  Download,
  Lock,
  FileCheck2,
  Settings,
  MessageCircle,
  ClipboardList,
} from 'lucide-react';
import { MicrositeProfile } from '../types';
import { AdminRole } from './AdminAuthModal';

export type AdminTabKey = 'menus' | 'theme' | 'employees' | 'analytics' | 'wfa_monitoring' | 'kebugaran_monitoring' | 'meeting_attendance' | 'pegawai_forms' | 'export' | 'security' | 'whatsapp_settings';

interface NavbarProps {
  currentView: 'public' | 'admin' | 'split';
  setCurrentView: (view: 'public' | 'admin' | 'split') => void;
  onOpenQR: () => void;
  onResetDemo?: () => void;
  onLogout: () => void;
  onPublish?: () => void;
  isPublishing?: boolean;
  lastPublishedAt?: string | null;
  profile: MicrositeProfile;
  totalClicks: number;
  adminActiveTab?: AdminTabKey;
  onSelectAdminTab?: (tab: AdminTabKey) => void;
  wfaPendingCount?: number;
  kebugaranCount?: number;
  meetingAttendeesCount?: number;
  pegawaiResponsesCount?: number;
  menusCount?: number;
  adminRole?: AdminRole;
}

export const Navbar: React.FC<NavbarProps> = ({
  currentView,
  setCurrentView,
  onOpenQR,
  onLogout,
  onPublish,
  isPublishing = false,
  lastPublishedAt,
  profile,
  totalClicks,
  adminActiveTab = 'menus',
  onSelectAdminTab,
  wfaPendingCount = 0,
  kebugaranCount = 0,
  meetingAttendeesCount = 0,
  pegawaiResponsesCount = 0,
  menusCount = 0,
  adminRole = 'superadmin',
}) => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  const adminModules = [
    {
      key: 'menus' as AdminTabKey,
      label: 'Kelola Menu',
      icon: LayoutGrid,
      iconColor: 'text-indigo-400',
      badge: menusCount > 0 ? menusCount : undefined,
      badgeClass: 'bg-indigo-500/30 text-indigo-200 border border-indigo-400/30',
    },
    {
      key: 'theme' as AdminTabKey,
      label: 'Tema & Profil',
      icon: Palette,
      iconColor: 'text-pink-400',
    },
    {
      key: 'employees' as AdminTabKey,
      label: 'Database Pegawai',
      icon: Users,
      iconColor: 'text-emerald-400',
    },
    {
      key: 'analytics' as AdminTabKey,
      label: 'Analitik Klik',
      icon: BarChart3,
      iconColor: 'text-amber-400',
    },
    {
      key: 'wfa_monitoring' as AdminTabKey,
      label: 'Monitoring WFA',
      icon: Sparkles,
      iconColor: 'text-emerald-400',
      badge: wfaPendingCount > 0 ? wfaPendingCount : undefined,
      badgeClass: 'bg-amber-500 text-slate-950 font-black animate-pulse',
    },
    {
      key: 'kebugaran_monitoring' as AdminTabKey,
      label: 'Monitoring Kebugaran',
      icon: Activity,
      iconColor: 'text-sky-400',
      badge: kebugaranCount > 0 ? kebugaranCount : undefined,
      badgeClass: 'bg-sky-500 text-white font-bold',
    },
    {
      key: 'meeting_attendance' as AdminTabKey,
      label: 'Daftar Hadir Rapat',
      icon: FileCheck2,
      iconColor: 'text-teal-400',
      badge: meetingAttendeesCount > 0 ? meetingAttendeesCount : undefined,
      badgeClass: 'bg-teal-500 text-white font-bold',
    },
    {
      key: 'pegawai_forms' as AdminTabKey,
      label: 'Formulir Pegawai',
      icon: ClipboardList,
      iconColor: 'text-indigo-400',
      badge: pegawaiResponsesCount > 0 ? pegawaiResponsesCount : undefined,
      badgeClass: 'bg-indigo-600 text-white font-bold',
    },
    {
      key: 'security' as AdminTabKey,
      label: 'Keamanan & PIN',
      icon: Lock,
      iconColor: 'text-rose-400',
    },
    {
      key: 'whatsapp_settings' as AdminTabKey,
      label: 'WhatsApp Admin',
      icon: MessageCircle,
      iconColor: 'text-emerald-400',
    },
  ];

  // Close mobile menu when clicking outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMobileMenuOpen(false);
      }
    };
    if (mobileMenuOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [mobileMenuOpen]);


  const getRoleInfo = () => {
    switch (adminRole) {
      case 'wfa_admin':
        return {
          title: 'Monitoring WFA',
          badgeText: 'Khusus WFA',
          badgeStyle: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
          roleLabel: 'Pengelola WFA',
          roleStyle: 'bg-emerald-950/60 border-emerald-800 text-emerald-300',
        };
      case 'kebugaran_admin':
        return {
          title: 'Monitoring Kebugaran',
          badgeText: 'Khusus Kebugaran',
          badgeStyle: 'bg-sky-500/20 text-sky-300 border-sky-500/30',
          roleLabel: 'Pengelola Kebugaran',
          roleStyle: 'bg-sky-950/60 border-sky-800 text-sky-300',
        };
      case 'meeting_admin':
        return {
          title: 'Daftar Hadir Rapat',
          badgeText: 'Khusus Rapat',
          badgeStyle: 'bg-teal-500/20 text-teal-300 border-teal-500/30',
          roleLabel: 'Pengelola Hadir Rapat',
          roleStyle: 'bg-teal-950/60 border-teal-800 text-teal-300',
        };
      case 'monitoring_all_admin':
        return {
          title: 'Monitoring Terpadu',
          badgeText: 'WFA • Kebugaran • Rapat',
          badgeStyle: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
          roleLabel: 'Pengelola 3 Monitoring',
          roleStyle: 'bg-purple-950/60 border-purple-800 text-purple-300',
        };
      default:
        return {
          title: 'Panel Admin',
          badgeText: 'Superadmin',
          badgeStyle: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
          roleLabel: 'Superadmin',
          roleStyle: 'bg-indigo-950/60 border-indigo-800 text-indigo-300',
        };
    }
  };

  const roleInfo = getRoleInfo();

  const getFilteredModules = () => {
    switch (adminRole) {
      case 'wfa_admin':
        return adminModules.filter((m) => m.key === 'wfa_monitoring');
      case 'kebugaran_admin':
        return adminModules.filter((m) => m.key === 'kebugaran_monitoring');
      case 'meeting_admin':
        return adminModules.filter((m) => m.key === 'meeting_attendance');
      case 'monitoring_all_admin':
        return adminModules.filter((m) => ['wfa_monitoring', 'kebugaran_monitoring', 'meeting_attendance'].includes(m.key));
      default:
        return adminModules;
    }
  };

  const filteredAdminModules = getFilteredModules();

  return (
    <nav className="w-full bg-slate-900 border-b border-slate-800 text-slate-100 z-40 relative shadow-sm" ref={menuRef}>
      {/* Main navbar row */}
      <div className="px-3 sm:px-6 py-2.5 flex items-center justify-between gap-2 sm:gap-3">
        {/* Brand Identity & Admin Status */}
        <div className="flex items-center gap-2 sm:gap-3 min-w-0 shrink">
          {(profile.faviconUrl || profile.avatarUrl) ? (
            <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-white/10 border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
              <img
                src={profile.faviconUrl || profile.avatarUrl}
                alt="Logo"
                className="w-full h-full object-contain p-0.5"
                onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }}
              />
            </div>
          ) : (
            <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-900/40 shrink-0">
              <ShieldAlert className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-200" />
            </div>
          )}
          <div className="min-w-0">
            <div className="flex items-center gap-1.5 sm:gap-2 flex-wrap">
              <h1 className="text-xs sm:text-sm font-bold tracking-tight text-white flex items-center gap-1.5 whitespace-nowrap">
                <span>{roleInfo.title}</span>
                <span className="text-slate-400 font-normal hidden lg:inline truncate max-w-[180px]">• {profile.name}</span>
              </h1>
              {adminRole !== 'superadmin' ? (
                <span className={`px-2 py-0.5 border text-[10px] font-bold rounded flex items-center gap-1 ${roleInfo.badgeStyle}`}>
                  <span>{roleInfo.badgeText}</span>
                </span>
              ) : (
                <span className="hidden sm:flex px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-bold rounded items-center gap-1 whitespace-nowrap" title="Tersinkronisasi ke Database Server: Setiap klik Posting akan langsung tayang di semua HP/perangkat pegawai secara otomatis">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                  <span>Cloud Sync</span>
                </span>
              )}
            </div>
          </div>
        </div>

        {/* Right Controls – visible on md+ */}
        <div className="hidden md:flex items-center gap-2 shrink-0">
          {adminRole !== 'superadmin' ? (
            <div className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold ${roleInfo.roleStyle}`}>
              <Sparkles className="w-3.5 h-3.5 text-indigo-300" />
              <span>{roleInfo.roleLabel}</span>
            </div>
          ) : (
            <>
              {/* Tombol Posting / Update Halaman Pegawai */}
              {onPublish && (
                <button
                  type="button"
                  onClick={() => onPublish()}
                  disabled={isPublishing}
                  title="Posting dan publikasikan perubahan agar langsung tayang di portal pegawai"
                  className="flex items-center gap-1.5 px-3 lg:px-4 py-1.5 rounded-md bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-md shadow-emerald-950/40 border border-emerald-400/30 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-75"
                >
                  {isPublishing ? (
                    <>
                      <span className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                      <span>Posting...</span>
                    </>
                  ) : (
                    <>
                      <Send className="w-3.5 h-3.5 text-emerald-100" />
                      <span className="hidden lg:inline">Posting / Update Portal</span>
                      <span className="lg:hidden">Posting</span>
                    </>
                  )}
                </button>
              )}
            </>
          )}

          {/* QR Code Action */}
          <button
            onClick={onOpenQR}
            title="Tampilkan Barcode QR Code"
            className="flex items-center gap-1.5 px-2.5 lg:px-3 py-1.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-slate-700 transition-colors"
          >
            <QrCode className="w-3.5 h-3.5 text-indigo-400" />
            <span className="hidden lg:inline">QR Code</span>
          </button>

          {/* Logout from Admin */}
          <button
            onClick={onLogout}
            title="Keluar dari Akses Admin (Kunci Portal)"
            className="flex items-center gap-1.5 px-2.5 lg:px-3 py-1.5 rounded-md bg-red-950/50 hover:bg-red-900/70 text-red-300 hover:text-white text-xs font-semibold border border-red-800/60 transition-colors ml-1"
          >
            <LogOut className="w-3.5 h-3.5 text-red-400" />
            <span className="hidden lg:inline">Keluar Admin</span>
          </button>
        </div>

        {/* Mobile right: compact publish + hamburger */}
        <div className="flex md:hidden items-center gap-1.5">
          {adminRole !== 'superadmin' ? (
            <div className={`flex items-center gap-1 px-2 py-1 rounded-md border text-[10px] font-bold ${roleInfo.roleStyle}`}>
              <span>{roleInfo.badgeText}</span>
            </div>
          ) : (
            onPublish && (
              <button
                type="button"
                onClick={() => onPublish()}
                disabled={isPublishing}
                title="Posting perubahan"
                className="flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-[11px] font-bold shadow-sm border border-emerald-400/30 transition-all active:scale-95 disabled:opacity-75"
              >
                {isPublishing ? (
                  <span className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                ) : (
                  <Send className="w-3.5 h-3.5" />
                )}
                <span>{isPublishing ? 'Posting...' : 'Posting'}</span>
              </button>
            )
          )}

          {/* Hamburger toggle */}
          <button
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className={`flex items-center justify-center w-9 h-9 rounded-lg transition-colors border ${
              mobileMenuOpen
                ? 'bg-indigo-600 text-white border-indigo-500 shadow-sm'
                : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border-slate-700'
            }`}
            aria-label="Toggle menu"
          >
            {mobileMenuOpen ? (
              <X className="w-4.5 h-4.5" />
            ) : (
              <Menu className="w-4.5 h-4.5" />
            )}
          </button>
        </div>
      </div>

      {/* Mobile dropdown menu */}
      {mobileMenuOpen && (
        <div className="md:hidden border-t border-slate-800 bg-slate-900/98 backdrop-blur-xl animate-in slide-in-from-top-2 duration-200 max-h-[calc(100vh-60px)] overflow-y-auto">
          {/* Admin Navigation Modules Section (When in Admin mode) */}
          {currentView === 'admin' && onSelectAdminTab && (
            <div className="px-3 pt-3 pb-3 border-b border-slate-800/80">
              <div className="flex items-center justify-between mb-2 px-1">
                <p className="text-[10px] font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                  <SlidersHorizontal className="w-3 h-3" />
                  <span>Menu Modul Admin</span>
                </p>
                <span className="text-[10px] text-slate-400 font-mono bg-slate-800/80 px-2 py-0.5 rounded-full">
                  {`${filteredAdminModules.length} Modul Pengelola`}
                </span>
              </div>

              <div className="grid grid-cols-2 gap-1.5">
                {filteredAdminModules.map((item) => {
                  const isActive = adminActiveTab === item.key;
                  const Icon = item.icon;
                  return (
                    <button
                      key={item.key}
                      type="button"
                      onClick={() => {
                        onSelectAdminTab(item.key);
                        setMobileMenuOpen(false);
                      }}
                      className={`flex items-center justify-between px-2.5 py-2.5 rounded-lg text-left transition-all border ${
                        isActive
                          ? 'bg-indigo-600 text-white border-indigo-400 shadow-sm font-bold scale-[1.01]'
                          : 'bg-slate-800/60 hover:bg-slate-800 text-slate-300 hover:text-white border-slate-700/60 font-medium active:scale-98'
                      }`}
                    >
                      <div className="flex items-center gap-2 min-w-0">
                        <Icon className={`w-3.5 h-3.5 shrink-0 ${isActive ? 'text-white' : item.iconColor || 'text-slate-400'}`} />
                        <span className="text-xs truncate">{item.label}</span>
                      </div>
                      {item.badge !== undefined && item.badge !== null && item.badge !== 0 && (
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ml-1 shrink-0 ${item.badgeClass || 'bg-slate-700 text-slate-300'}`}>
                          {item.badge}
                        </span>
                      )}
                    </button>
                  );
                })}
              </div>
            </div>
          )}



          {/* Quick Actions */}
          <div className="px-3 pb-3">
            <p className="text-[10px] font-semibold text-slate-500 uppercase tracking-widest mb-2 px-1">Aksi Cepat</p>
            <div className="grid grid-cols-2 gap-1.5">
              <button
                onClick={() => { onOpenQR(); setMobileMenuOpen(false); }}
                className="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-slate-700 transition-colors active:scale-98"
              >
                <QrCode className="w-3.5 h-3.5 text-indigo-400" />
                <span>QR Code</span>
              </button>

              <button
                onClick={() => { onLogout(); setMobileMenuOpen(false); }}
                className="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-lg bg-red-950/50 hover:bg-red-900/70 text-red-300 text-xs font-semibold border border-red-800/60 transition-colors active:scale-98"
              >
                <LogOut className="w-3.5 h-3.5 text-red-400" />
                <span>Keluar Admin</span>
              </button>
            </div>
          </div>

          {/* Cloud Status indicator for mobile */}
          <div className="px-3 pb-3">
            <div className="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-950/60 border border-slate-800">
              <span className="flex items-center gap-1.5 text-emerald-300 text-[10px] font-bold">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                Cloud Realtime Sync
              </span>
              <span className="flex items-center gap-1.5 text-slate-400 text-[10px] font-mono">
                <Zap className="w-3 h-3 text-amber-400" />
                {totalClicks} Klik
              </span>
            </div>
          </div>
        </div>
      )}
    </nav>
  );
};

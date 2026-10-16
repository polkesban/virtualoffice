import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  Plus,
  GripVertical,
  Edit2,
  Trash2,
  Copy,
  Eye,
  EyeOff,
  Palette,
  LayoutGrid,
  BarChart3,
  Download,
  Settings,
  ArrowUp,
  ArrowDown,
  Sparkles,
  Smartphone,
  Check,
  Maximize2,
  FileSpreadsheet,
  FileText,
  Save,
  RotateCcw,
  ExternalLink,
  Layers,
  Zap,
  HelpCircle,
  Tag,
  Upload,
  Image as ImageIcon,
  X,
  Sliders,
  FileImage,
  Lock,
  KeyRound,
  ShieldAlert,
  Key,
  LogOut,
  CheckCheck,
  CheckCircle2,
  Send,
  Phone,
  Mail,
  Globe,
  MessageCircle,
  MapPin,
  AlignLeft,
  FileSignature,
  Share2,
  RefreshCw,
  Activity,
  Users,
  FileCode,
  ChevronDown,
  FileCheck2,
  ClipboardList,
} from 'lucide-react';
import { MenuItem, MicrositeProfile, ClickLog, ButtonSize, ThemeConfig, WfaSubmission, WfaValidationStatus, KebugaranSubmission, ClickStatsSummary, MeetingSession, MeetingAttendanceRecord, PegawaiForm, PegawaiFormResponse } from '../types';
import { THEME_PRESETS, CATEGORIES_PRESET } from '../data/initialData';
import { INITIAL_MEETING_SESSION, INITIAL_MEETING_ATTENDANCE } from '../data/meetingInitialData';
import { getIconComponent } from '../utils/iconMap';
import { PublicMicrosite } from './PublicMicrosite';
import { AdminMenuEditorModal } from './AdminMenuEditorModal';
import { AdminExportImportModal } from './AdminExportImportModal';
import { AnalyticsView } from './AnalyticsView';
import { WfaMonitoringView } from './WfaMonitoringView';
import { KebugaranMonitoringView } from './KebugaranMonitoringView';
import { MeetingManagerView } from './MeetingManagerView';
import { PegawaiFormManagerView } from './PegawaiFormManagerView';
import { PegawaiFormCreateModal } from './PegawaiFormCreateModal';
import { EmployeeManagerView } from './EmployeeManagerView';
import { AdminTabKey } from './Navbar';
import { AdminRole } from './AdminAuthModal';
import { exportToCSV, exportToPDF } from '../utils/exportUtils';
import { optimizeImageForStorage } from '../utils/imageOptimizer';

interface AdminDashboardProps {
  menus: MenuItem[];
  setMenus: React.Dispatch<React.SetStateAction<MenuItem[]>>;
  profile: MicrositeProfile;
  setProfile: React.Dispatch<React.SetStateAction<MicrositeProfile>>;
  liveMenus?: MenuItem[];
  liveProfile?: MicrositeProfile;
  logs: ClickLog[];
  setLogs: React.Dispatch<React.SetStateAction<ClickLog[]>>;
  clickStats?: ClickStatsSummary | null;
  onRefreshAnalytics?: () => Promise<void> | void;
  onOpenPublicPreview: () => void;
  onOpenQR: () => void;
  onSimulateClick: () => void;
  onClearLogs: () => void;
  adminPin?: string;
  setAdminPin?: (newPin: string) => void;
  onLogout?: () => void;
  onPublish?: () => void;
  isPublishing?: boolean;
  lastPublishedAt?: string | null;
  wfaSubmissions?: WfaSubmission[];
  onUpdateWfaStatus?: (id: string, status: WfaValidationStatus, notes?: string) => Promise<{ success: boolean; error?: string }>;
  onUpdateWfaLink?: (id: string, newLink: string) => Promise<{ success: boolean; linkSuratTugas?: string; error?: string }>;
  onDeleteWfaSubmission?: (id: string) => Promise<{ success: boolean; error?: string }>;
  onRefreshWfa?: () => void;
  kebugaranSubmissions?: KebugaranSubmission[];
  onDeleteKebugaranSubmission?: (id: string) => Promise<{ success: boolean; error?: string }>;
  onRefreshKebugaran?: () => void;
  onOpenKebugaranModal?: () => void;
  meetingSession?: MeetingSession;
  meetingSessions?: MeetingSession[];
  activeMeetingId?: string;
  onSelectMeetingSession?: (id: string) => void;
  onCreateMeetingSession?: (newSession: MeetingSession) => void;
  onDeleteMeetingSession?: (id: string) => void;
  onToggleMeetingSessionStatus?: (id: string, isActive: boolean) => void;
  onUpdateMeetingSession?: (updatedSession: MeetingSession) => void;
  onResetMeetingSession?: () => void;
  meetingAttendees?: MeetingAttendanceRecord[];
  onDeleteMeetingAttendee?: (id: string) => void;
  onDeleteMeetingAttendeesByAgenda?: (agenda: string) => void;
  onAddMeetingAttendee?: (attendee: Omit<MeetingAttendanceRecord, 'id' | 'timestamp'>) => void;
  onOpenMeetingForm?: () => void;
  activeTab?: AdminTabKey;
  setActiveTab?: (tab: AdminTabKey) => void;
  adminRole?: AdminRole;
  wfaAdminPin?: string;
  onUpdateWfaAdminPin?: (newPin: string) => Promise<void> | void;
  kebugaranAdminPin?: string;
  onUpdateKebugaranAdminPin?: (newPin: string) => Promise<void> | void;
  meetingAdminPin?: string;
  onUpdateMeetingAdminPin?: (newPin: string) => Promise<void> | void;
  monitoringAllAdminPin?: string;
  onUpdateMonitoringAllAdminPin?: (newPin: string) => Promise<void> | void;
  pegawaiForms?: PegawaiForm[];
  selectedPegawaiFormId?: string;
  onSelectPegawaiForm?: (formId: string) => void;
  pegawaiResponses?: PegawaiFormResponse[];
  onDeletePegawaiResponse?: (id: string) => Promise<boolean>;
  onOpenPegawaiFillModal?: (form: PegawaiForm) => void;
  onCreatePegawaiForm?: (form: PegawaiForm) => Promise<boolean>;
  onTogglePegawaiFormStatus?: (id: string, isActive: boolean) => Promise<boolean>;
  onUpdatePegawaiForm?: (form: PegawaiForm) => Promise<boolean>;
  onDeletePegawaiForm?: (id: string) => Promise<boolean>;
}

export const AdminDashboard: React.FC<AdminDashboardProps> = ({
  menus,
  setMenus,
  profile,
  setProfile,
  liveMenus,
  liveProfile,
  logs,
  setLogs,
  clickStats,
  onRefreshAnalytics,
  onOpenPublicPreview,
  onOpenQR,
  onSimulateClick,
  onClearLogs,
  adminPin = 'admin123',
  setAdminPin,
  onLogout,
  onPublish,
  isPublishing = false,
  lastPublishedAt,
  wfaSubmissions = [],
  onUpdateWfaStatus,
  onUpdateWfaLink,
  onDeleteWfaSubmission,
  onRefreshWfa,
  kebugaranSubmissions = [],
  onDeleteKebugaranSubmission,
  onRefreshKebugaran,
  onOpenKebugaranModal,
  meetingSession = INITIAL_MEETING_SESSION,
  meetingSessions = [],
  activeMeetingId,
  onSelectMeetingSession,
  onCreateMeetingSession,
  onDeleteMeetingSession,
  onToggleMeetingSessionStatus,
  onUpdateMeetingSession,
  onResetMeetingSession,
  meetingAttendees = INITIAL_MEETING_ATTENDANCE,
  onDeleteMeetingAttendee,
  onDeleteAttendeesByAgenda,
  onAddMeetingAttendee,
  onOpenMeetingForm,
  activeTab: externalActiveTab,
  setActiveTab: externalSetActiveTab,
  adminRole = 'superadmin' as AdminRole,
  wfaAdminPin = 'wfa123',
  onUpdateWfaAdminPin,
  kebugaranAdminPin = 'fit123',
  onUpdateKebugaranAdminPin,
  meetingAdminPin = 'rapat123',
  onUpdateMeetingAdminPin,
  monitoringAllAdminPin = 'pantau123',
  onUpdateMonitoringAllAdminPin,
  pegawaiForms = [],
  selectedPegawaiFormId = '',
  onSelectPegawaiForm = (_id: string) => {},
  pegawaiResponses = [],
  onDeletePegawaiResponse = async () => true,
  onOpenPegawaiFillModal = () => {},
  onCreatePegawaiForm = async (_form: PegawaiForm) => true,
  onTogglePegawaiFormStatus = async (_id: string, _isActive: boolean) => true,
  onUpdatePegawaiForm = async (_form: PegawaiForm) => true,
  onDeletePegawaiForm = async (_id: string) => true,
}) => {
  const getDefaultTab = (role: AdminRole): AdminTabKey => {
    if (role === 'wfa_admin') return 'wfa_monitoring';
    if (role === 'kebugaran_admin') return 'kebugaran_monitoring';
    if (role === 'meeting_admin') return 'meeting_attendance';
    if (role === 'monitoring_all_admin') return 'wfa_monitoring';
    return 'menus';
  };

  const [internalActiveTab, setInternalActiveTab] = useState<AdminTabKey>(getDefaultTab(adminRole));
  const activeTab = externalActiveTab !== undefined ? externalActiveTab : internalActiveTab;
  const setActiveTab = (tab: AdminTabKey) => {
    if (externalSetActiveTab) {
      externalSetActiveTab(tab);
    } else {
      setInternalActiveTab(tab);
    }
  };

  const [openDropdown, setOpenDropdown] = useState<'menu' | 'monitoring' | 'settings' | null>(null);
  const menuDropdownRef = useRef<HTMLDivElement>(null);
  const monitoringDropdownRef = useRef<HTMLDivElement>(null);
  const settingsDropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Node;
      if (
        menuDropdownRef.current &&
        !menuDropdownRef.current.contains(target) &&
        monitoringDropdownRef.current &&
        !monitoringDropdownRef.current.contains(target) &&
        settingsDropdownRef.current &&
        !settingsDropdownRef.current.contains(target)
      ) {
        setOpenDropdown(null);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);
  const [editingMenu, setEditingMenu] = useState<MenuItem | null>(null);
  const [isEditorOpen, setIsEditorOpen] = useState(false);
  const [showLiveSidePreview, setShowLiveSidePreview] = useState(true);
  const isPreviewApplicable = adminRole !== 'wfa_admin' && (activeTab === 'menus' || activeTab === 'theme');
  const isSidePreviewVisible = showLiveSidePreview && isPreviewApplicable;
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const [saveToast, setSaveToast] = useState(false);
  const [isBgDragging, setIsBgDragging] = useState(false);
  const [isLogoDragging, setIsLogoDragging] = useState(false);
  const [isFaviconDragging, setIsFaviconDragging] = useState(false);
  const [copiedShareLink, setCopiedShareLink] = useState(false);

  // Auto publish toggle (defaults to true for zero-friction employee sync)
  const [autoPublishEnabled, setAutoPublishEnabled] = useState<boolean>(() => {
    try {
      const saved = localStorage.getItem('direct_menu_auto_publish');
      return saved !== null ? saved === 'true' : true;
    } catch {
      return true;
    }
  });

  const handleToggleAutoPublish = () => {
    const next = !autoPublishEnabled;
    setAutoPublishEnabled(next);
    try {
      localStorage.setItem('direct_menu_auto_publish', String(next));
    } catch {}
  };

  // Compare draft vs live to see if there are unpublished changes
  const hasUnpublishedChanges = React.useMemo(() => {
    if (!liveMenus || !liveProfile) return false;
    try {
      const normMenus = JSON.stringify(menus, (k, v) => (v === undefined ? null : v));
      const normLiveMenus = JSON.stringify(liveMenus, (k, v) => (v === undefined ? null : v));
      const normProfile = JSON.stringify(profile, (k, v) => (v === undefined ? null : v));
      const normLiveProfile = JSON.stringify(liveProfile, (k, v) => (v === undefined ? null : v));

      return normMenus !== normLiveMenus || normProfile !== normLiveProfile;
    } catch {
      return false;
    }
  }, [menus, profile, liveMenus, liveProfile]);

  // Debounced auto-publish effect when autoPublishEnabled is true
  React.useEffect(() => {
    if (!autoPublishEnabled || !onPublish || isPublishing) return;
    if (!hasUnpublishedChanges) return;

    const timer = setTimeout(() => {
      onPublish();
    }, 1800);

    return () => clearTimeout(timer);
  }, [autoPublishEnabled, hasUnpublishedChanges, onPublish, isPublishing]);

  const handleCopyShareLink = () => {
    try {
      const url = `${window.location.origin}${window.location.pathname}?mode=public`;
      navigator.clipboard.writeText(url);
      setCopiedShareLink(true);
      setTimeout(() => setCopiedShareLink(false), 2500);
    } catch (e) {
      console.warn('Copy link error:', e);
    }
  };

  // Export / Import modal state
  const [isExportImportOpen, setIsExportImportOpen] = useState(false);

  const handleImportConfig = (imported: { menus: MenuItem[]; profile: MicrositeProfile; pin?: string }) => {
    if (imported.menus && Array.isArray(imported.menus)) {
      setMenus(imported.menus);
    }
    if (imported.profile) {
      setProfile(imported.profile);
    }
    if (imported.pin && setAdminPin) {
      setAdminPin(imported.pin);
    }
    triggerSaveFeedback();
  };

  // Security tab local state
  const [newPinInput, setNewPinInput] = useState('');
  const [confirmPinInput, setConfirmPinInput] = useState('');
  const [pinChangeSuccess, setPinChangeSuccess] = useState(false);
  const [pinChangeError, setPinChangeError] = useState('');

  // Matriks Akses & PIN Monitoring Khusus (Tabel Ringkas & Cerdas)
  const [revealedPinIds, setRevealedPinIds] = useState<Record<string, boolean>>({});
  const [showAllMonitoringPins, setShowAllMonitoringPins] = useState(false);
  const [activeEditPinModuleId, setActiveEditPinModuleId] = useState<string | null>(null);
  const [modalNewPin, setModalNewPin] = useState('');
  const [modalConfirmPin, setModalConfirmPin] = useState('');
  const [showModalPin, setShowModalPin] = useState(false);
  const [modalPinError, setModalPinError] = useState('');
  const [modalPinSuccess, setModalPinSuccess] = useState(false);
  const [isSavingModalPin, setIsSavingModalPin] = useState(false);
  const [resettingPinModuleId, setResettingPinModuleId] = useState<string | null>(null);
  const [pinTableAlert, setPinTableAlert] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [isCreatePegawaiFormOpen, setIsCreatePegawaiFormOpen] = useState(false);

  // Role guard effect for tab access
  useEffect(() => {
    if (adminRole === 'wfa_admin' && activeTab !== 'wfa_monitoring') {
      setActiveTab('wfa_monitoring');
    } else if (adminRole === 'kebugaran_admin' && activeTab !== 'kebugaran_monitoring') {
      setActiveTab('kebugaran_monitoring');
    } else if (adminRole === 'meeting_admin' && activeTab !== 'meeting_attendance') {
      setActiveTab('meeting_attendance');
    } else if (adminRole === 'monitoring_all_admin' && !['wfa_monitoring', 'kebugaran_monitoring', 'meeting_attendance'].includes(activeTab)) {
      setActiveTab('wfa_monitoring');
    }
  }, [adminRole, activeTab]);

  const availableCategories = CATEGORIES_PRESET;

  // File Upload Helper
  const handleLocalImageUpload = async (
    file: File,
    onSuccess: (dataUrl: string) => void,
    maxWidth = 280,
    maxHeight = 280
  ) => {
    if (!file.type.startsWith('image/')) {
      alert('Harap unggah file gambar (PNG, JPG, WebP, SVG)');
      return;
    }
    try {
      const optimized = await optimizeImageForStorage(file, maxWidth, maxHeight, 0.85);
      if (optimized) {
        onSuccess(optimized);
        triggerSaveFeedback();
      }
    } catch (err) {
      console.warn('Image optimization error:', err);
      const reader = new FileReader();
      reader.onload = (e) => {
        if (e.target?.result) {
          onSuccess(e.target.result as string);
          triggerSaveFeedback();
        }
      };
      reader.readAsDataURL(file);
    }
  };

  // Handlers for Menu Management
  const handleAddNewMenu = () => {
    setEditingMenu(null);
    setIsEditorOpen(true);
  };

  const handleEditMenu = (menu: MenuItem) => {
    setEditingMenu(menu);
    setIsEditorOpen(true);
  };

  const handleDuplicateMenu = (menu: MenuItem) => {
    const duplicated: MenuItem = {
      ...menu,
      id: `menu-${Date.now()}`,
      title: `${menu.title} (Salinan)`,
      order: menus.length + 1,
      clickCount: 0,
    };
    setMenus((prev) => [...prev, duplicated]);
    triggerSaveFeedback();
  };

  const handleDeleteMenu = (id: string) => {
    if (window.confirm('Yakin ingin menghapus tombol menu ini?')) {
      setMenus((prev) => prev.filter((m) => m.id !== id));
      triggerSaveFeedback();
    }
  };

  const handleToggleActive = (id: string) => {
    setMenus((prev) =>
      prev.map((m) => (m.id === id ? { ...m, isActive: !m.isActive } : m))
    );
    triggerSaveFeedback();
  };

  const handleQuickSizeChange = (id: string, newSize: ButtonSize) => {
    setMenus((prev) =>
      prev.map((m) => (m.id === id ? { ...m, size: newSize } : m))
    );
    triggerSaveFeedback();
  };

  const handleSaveMenu = (savedMenu: MenuItem) => {
    setMenus((prev) => {
      const exists = prev.some((m) => m.id === savedMenu.id);
      if (exists) {
        return prev.map((m) => (m.id === savedMenu.id ? savedMenu : m));
      } else {
        return [...prev, savedMenu];
      }
    });
    triggerSaveFeedback();
  };

  // Reorder Handlers
  const handleMove = (index: number, direction: 'up' | 'down') => {
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= menus.length) return;

    const updated = [...menus];
    const temp = updated[index];
    updated[index] = updated[newIndex];
    updated[newIndex] = temp;

    // update orders
    const reordered = updated.map((m, idx) => ({ ...m, order: idx + 1 }));
    setMenus(reordered);
    triggerSaveFeedback();
  };

  // HTML5 Drag and Drop Reordering
  const handleDragStart = (index: number) => {
    setDraggedIndex(index);
  };

  const handleDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    if (draggedIndex === null || draggedIndex === index) return;

    const updated = [...menus];
    const draggedItem = updated[draggedIndex];
    updated.splice(draggedIndex, 1);
    updated.splice(index, 0, draggedItem);

    setDraggedIndex(index);
    setMenus(updated.map((m, idx) => ({ ...m, order: idx + 1 })));
  };

  const handleDragEnd = () => {
    setDraggedIndex(null);
    triggerSaveFeedback();
  };

  const triggerSaveFeedback = () => {
    setSaveToast(true);
    setTimeout(() => setSaveToast(false), 2000);
  };

  return (
    <div className="w-full min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
      {/* Top Admin Sub-Header Navigation */}
      <header className="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-xs">
        {/* Mobile Tab Navigation Bar (sm:hidden) */}
        <div className="sm:hidden px-2.5 pt-2 pb-1 bg-white">
          <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar scroll-smooth py-1 px-0.5">
            {(() => {
              if (adminRole === 'wfa_admin') {
                return [
                  {
                    id: 'wfa_monitoring' as const,
                    label: 'Monitoring WFA Bimbingan',
                    icon: Sparkles,
                    badge: wfaSubmissions.filter((s) => s.status === 'Menunggu Validasi').length || undefined,
                    badgeClass: 'bg-amber-500 text-slate-950 font-black animate-pulse',
                    activeBg: 'bg-emerald-600',
                  },
                ];
              }
              if (adminRole === 'kebugaran_admin') {
                return [
                  {
                    id: 'kebugaran_monitoring' as const,
                    label: 'Monitoring Kebugaran',
                    icon: Activity,
                    badge: kebugaranSubmissions.length || undefined,
                    badgeClass: 'bg-sky-500 text-white font-bold',
                    activeBg: 'bg-sky-600',
                  },
                ];
              }
              if (adminRole === 'meeting_admin') {
                return [
                  {
                    id: 'meeting_attendance' as const,
                    label: 'Daftar Hadir Rapat',
                    icon: FileCheck2,
                    badge: meetingAttendees.length || undefined,
                    badgeClass: 'bg-teal-500 text-white font-bold',
                    activeBg: 'bg-teal-700',
                  },
                ];
              }
              if (adminRole === 'monitoring_all_admin') {
                return [
                  {
                    id: 'wfa_monitoring' as const,
                    label: 'WFA',
                    icon: Sparkles,
                    badge: wfaSubmissions.filter((s) => s.status === 'Menunggu Validasi').length || undefined,
                    badgeClass: 'bg-amber-500 text-slate-950 font-black animate-pulse',
                    activeBg: 'bg-emerald-600',
                  },
                  {
                    id: 'kebugaran_monitoring' as const,
                    label: 'Kebugaran',
                    icon: Activity,
                    badge: kebugaranSubmissions.length || undefined,
                    badgeClass: 'bg-sky-500 text-white font-bold',
                    activeBg: 'bg-sky-600',
                  },
                  {
                    id: 'meeting_attendance' as const,
                    label: 'Daftar Hadir',
                    icon: FileCheck2,
                    badge: meetingAttendees.length || undefined,
                    badgeClass: 'bg-teal-500 text-white font-bold',
                    activeBg: 'bg-teal-700',
                  },
                ];
              }
              return [
                { id: 'menus' as const, label: `Menu (${menus.length})`, icon: LayoutGrid },
                { id: 'theme' as const, label: 'Tema & Profil', icon: Palette },
                { id: 'employees' as const, label: 'Pegawai', icon: Users },
                { id: 'analytics' as const, label: 'Analitik', icon: BarChart3 },
                {
                  id: 'wfa_monitoring' as const,
                  label: 'WFA',
                  icon: Sparkles,
                  badge: wfaSubmissions.filter((s) => s.status === 'Menunggu Validasi').length || undefined,
                  badgeClass: 'bg-amber-500 text-slate-950 font-black animate-pulse',
                  activeBg: 'bg-emerald-600',
                },
                {
                  id: 'kebugaran_monitoring' as const,
                  label: 'Kebugaran',
                  icon: Activity,
                  badge: kebugaranSubmissions.length || undefined,
                  badgeClass: 'bg-sky-500 text-white font-bold',
                  activeBg: 'bg-sky-600',
                },
                {
                  id: 'meeting_attendance' as const,
                  label: 'Daftar Hadir',
                  icon: FileCheck2,
                  badge: meetingAttendees.length || undefined,
                  badgeClass: 'bg-teal-500 text-white font-bold',
                  activeBg: 'bg-teal-700',
                },
                { id: 'security' as const, label: 'Keamanan & PIN', icon: Lock },
                { id: 'whatsapp_settings' as const, label: 'Nomor WA Admin', icon: MessageCircle, activeBg: 'bg-emerald-600' },
              ];
            })().map((tab) => {
              const Icon = tab.icon;
              const isActive = activeTab === tab.id;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all shrink-0 border ${
                    isActive
                      ? `${tab.activeBg || 'bg-indigo-600'} text-white border-transparent shadow-xs font-bold scale-[1.02]`
                      : 'bg-slate-100/90 hover:bg-slate-200/80 text-slate-700 border-slate-200/60 active:scale-95'
                  }`}
                >
                  <Icon className="w-3.5 h-3.5 shrink-0" />
                  <span>{tab.label}</span>
                  {tab.badge !== undefined && tab.badge > 0 && (
                    <span className={`px-1.5 py-0.2 rounded-full font-black text-[10px] ${tab.badgeClass}`}>
                      {tab.badge}
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </div>

        {/* Desktop Tab Navigation Bar (hidden sm:block) */}
        <div className="hidden sm:block relative z-20">
          <div className="flex items-center gap-1 sm:gap-1.5 p-1 mx-3 sm:mx-6 my-2 bg-slate-100 border border-slate-200 rounded-lg relative">
            {adminRole === 'wfa_admin' ? (
              <>
                <button
                  onClick={() => setActiveTab('wfa_monitoring')}
                  className="flex items-center gap-1.5 sm:gap-2 px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-emerald-600 text-white shadow-xs"
                >
                  <Sparkles className="w-3.5 h-3.5 text-emerald-200 shrink-0" />
                  <span>Monitoring Pengajuan WFA Bimbingan</span>
                  {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length > 0 && (
                    <span className="px-1.5 py-0.2 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] animate-pulse">
                      {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length}
                    </span>
                  )}
                </button>
                <div className="ml-auto flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-md text-emerald-800 text-xs font-semibold">
                  <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                  <span>Akses Otorisasi: Pengelola Khusus WFA Bimbingan</span>
                </div>
              </>
            ) : adminRole === 'kebugaran_admin' ? (
              <>
                <button
                  onClick={() => setActiveTab('kebugaran_monitoring')}
                  className="flex items-center gap-1.5 sm:gap-2 px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-sky-600 text-white shadow-xs"
                >
                  <Activity className="w-3.5 h-3.5 text-sky-200 shrink-0" />
                  <span>Monitoring Kebugaran Jasmani</span>
                  {kebugaranSubmissions.length > 0 && (
                    <span className="px-1.5 py-0.2 rounded-full bg-sky-100 text-sky-800 font-bold text-[10px]">
                      {kebugaranSubmissions.length} Data
                    </span>
                  )}
                </button>
                <div className="ml-auto flex items-center gap-1.5 px-3 py-1 bg-sky-50 border border-sky-200 rounded-md text-sky-800 text-xs font-semibold">
                  <span className="w-2 h-2 rounded-full bg-sky-500 animate-pulse" />
                  <span>Akses Otorisasi: Pengelola Khusus Kebugaran Jasmani</span>
                </div>
              </>
            ) : adminRole === 'meeting_admin' ? (
              <>
                <button
                  onClick={() => setActiveTab('meeting_attendance')}
                  className="flex items-center gap-1.5 sm:gap-2 px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-teal-700 text-white shadow-xs"
                >
                  <FileCheck2 className="w-3.5 h-3.5 text-teal-200 shrink-0" />
                  <span>Daftar Hadir Pertemuan & Rapat</span>
                  {meetingAttendees.length > 0 && (
                    <span className="px-1.5 py-0.2 rounded-full bg-teal-100 text-teal-900 font-bold text-[10px]">
                      {meetingAttendees.length} Hadir
                    </span>
                  )}
                </button>
                <div className="ml-auto flex items-center gap-1.5 px-3 py-1 bg-teal-50 border border-teal-200 rounded-md text-teal-800 text-xs font-semibold">
                  <span className="w-2 h-2 rounded-full bg-teal-500 animate-pulse" />
                  <span>Akses Otorisasi: Pengelola Khusus Daftar Hadir Rapat</span>
                </div>
              </>
            ) : adminRole === 'monitoring_all_admin' ? (
              <>
                <div className="flex items-center gap-1.5">
                  <button
                    onClick={() => setActiveTab('wfa_monitoring')}
                    className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                      activeTab === 'wfa_monitoring'
                        ? 'bg-emerald-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                    }`}
                  >
                    <Sparkles className="w-3.5 h-3.5 shrink-0" />
                    <span>Monitoring WFA</span>
                    {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length > 0 && (
                      <span className="px-1.5 py-0.2 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] animate-pulse">
                        {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length}
                      </span>
                    )}
                  </button>

                  <button
                    onClick={() => setActiveTab('kebugaran_monitoring')}
                    className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                      activeTab === 'kebugaran_monitoring'
                        ? 'bg-sky-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                    }`}
                  >
                    <Activity className="w-3.5 h-3.5 shrink-0" />
                    <span>Monitoring Kebugaran</span>
                    {kebugaranSubmissions.length > 0 && (
                      <span className="px-1.5 py-0.2 rounded-full bg-sky-100 text-sky-800 font-bold text-[10px]">
                        {kebugaranSubmissions.length}
                      </span>
                    )}
                  </button>

                  <button
                    onClick={() => setActiveTab('meeting_attendance')}
                    className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                      activeTab === 'meeting_attendance'
                        ? 'bg-teal-700 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                    }`}
                  >
                    <FileCheck2 className="w-3.5 h-3.5 shrink-0" />
                    <span>Daftar Hadir Rapat</span>
                    {meetingAttendees.length > 0 && (
                      <span className="px-1.5 py-0.2 rounded-full bg-teal-100 text-teal-900 font-bold text-[10px]">
                        {meetingAttendees.length}
                      </span>
                    )}
                  </button>
                </div>

                <div className="ml-auto flex items-center gap-1.5 px-3 py-1 bg-purple-50 border border-purple-200 rounded-md text-purple-800 text-xs font-semibold">
                  <span className="w-2 h-2 rounded-full bg-purple-500 animate-pulse" />
                  <span>Akses Otorisasi: Pengelola 3 Monitoring Terpadu</span>
                </div>
              </>
            ) : (
              <>
                {/* 1. Group Kelola Menu Dropdown */}
                <div className="relative" ref={menuDropdownRef}>
                  <button
                    onClick={() => setOpenDropdown(openDropdown === 'menu' ? null : 'menu')}
                    className={`flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 rounded-md text-xs font-medium transition-all whitespace-nowrap ${
                      activeTab === 'menus' || activeTab === 'theme'
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                    }`}
                  >
                    {activeTab === 'theme' ? (
                      <Palette className="w-3.5 h-3.5 shrink-0" />
                    ) : (
                      <LayoutGrid className="w-3.5 h-3.5 shrink-0" />
                    )}
                    <span>
                      {activeTab === 'theme' ? 'Kelola Menu: Tema & Profil' : `Kelola Menu (${menus.length})`}
                    </span>
                    <ChevronDown className={`w-3 h-3 transition-transform duration-200 ${openDropdown === 'menu' ? 'rotate-180' : ''}`} />
                  </button>

                  {openDropdown === 'menu' && (
                    <div className="absolute left-0 top-full mt-1.5 w-60 bg-white rounded-xl shadow-xl border border-slate-200/90 p-1.5 z-50 text-slate-800 animate-in fade-in zoom-in-95 duration-100">
                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('menus');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all ${
                          activeTab === 'menus'
                            ? 'bg-indigo-50 text-indigo-700 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'menus' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                            <LayoutGrid className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Susunan Menu</div>
                            <div className="text-[10px] text-slate-400 font-normal">Tautan & tombol portal</div>
                          </div>
                        </div>
                        <span className={`px-1.5 py-0.5 rounded-full text-[10px] font-bold ${
                          activeTab === 'menus' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'
                        }`}>
                          {menus.length}
                        </span>
                      </button>

                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('theme');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all mt-0.5 ${
                          activeTab === 'theme'
                            ? 'bg-indigo-50 text-indigo-700 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'theme' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                            <Palette className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Tema & Profil</div>
                            <div className="text-[10px] text-slate-400 font-normal">Warna, logo & identitas</div>
                          </div>
                        </div>
                        {activeTab === 'theme' && (
                          <span className="w-2 h-2 rounded-full bg-indigo-600 mr-1" />
                        )}
                      </button>
                    </div>
                  )}
                </div>

                {/* 2. Database Pegawai */}
                <button
                  onClick={() => {
                    setActiveTab('employees');
                    setOpenDropdown(null);
                  }}
                  className={`flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3.5 py-1.5 rounded-md text-xs font-medium transition-all whitespace-nowrap ${
                    activeTab === 'employees'
                      ? 'bg-indigo-600 text-white shadow-xs'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                  }`}
                >
                  <Users className="w-3.5 h-3.5 shrink-0" />
                  <span>Database Pegawai</span>
                </button>

                {/* 3. Analitik Klik */}
                <button
                  onClick={() => {
                    setActiveTab('analytics');
                    setOpenDropdown(null);
                  }}
                  className={`flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3.5 py-1.5 rounded-md text-xs font-medium transition-all whitespace-nowrap ${
                    activeTab === 'analytics'
                      ? 'bg-indigo-600 text-white shadow-xs'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                  }`}
                >
                  <BarChart3 className="w-3.5 h-3.5 shrink-0" />
                  <span>Analitik Klik</span>
                </button>

                {/* 4. Group Monitoring Dropdown */}
                <div className="relative" ref={monitoringDropdownRef}>
                  <button
                    onClick={() => setOpenDropdown(openDropdown === 'monitoring' ? null : 'monitoring')}
                    className={`flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 rounded-md text-xs font-medium transition-all whitespace-nowrap ${
                      activeTab === 'wfa_monitoring'
                        ? 'bg-emerald-600 text-white shadow-xs'
                        : activeTab === 'kebugaran_monitoring'
                        ? 'bg-sky-600 text-white shadow-xs'
                        : activeTab === 'meeting_attendance'
                        ? 'bg-teal-700 text-white shadow-xs font-bold'
                        : activeTab === 'pegawai_forms'
                        ? 'bg-indigo-600 text-white shadow-xs font-bold'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                    }`}
                  >
                    {activeTab === 'wfa_monitoring' ? (
                      <Sparkles className="w-3.5 h-3.5 text-emerald-200 shrink-0" />
                    ) : activeTab === 'kebugaran_monitoring' ? (
                      <Activity className="w-3.5 h-3.5 text-sky-200 shrink-0" />
                    ) : activeTab === 'meeting_attendance' ? (
                      <FileCheck2 className="w-3.5 h-3.5 text-teal-200 shrink-0" />
                    ) : activeTab === 'pegawai_forms' ? (
                      <ClipboardList className="w-3.5 h-3.5 text-indigo-200 shrink-0" />
                    ) : (
                      <Activity className="w-3.5 h-3.5 shrink-0" />
                    )}
                    <span>
                      {activeTab === 'wfa_monitoring'
                        ? 'Monitoring: WFA'
                        : activeTab === 'kebugaran_monitoring'
                        ? 'Monitoring: Kebugaran'
                        : activeTab === 'meeting_attendance'
                        ? 'Monitoring: Hadir Rapat'
                        : activeTab === 'pegawai_forms'
                        ? 'Monitoring: Formulir'
                        : 'Monitoring'}
                    </span>
                    <ChevronDown className={`w-3 h-3 transition-transform duration-200 ${openDropdown === 'monitoring' ? 'rotate-180' : ''}`} />
                  </button>

                  {openDropdown === 'monitoring' && (
                    <div className="absolute left-0 top-full mt-1.5 w-64 bg-white rounded-xl shadow-xl border border-slate-200/90 p-1.5 z-50 text-slate-800 animate-in fade-in zoom-in-95 duration-100">
                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('wfa_monitoring');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all ${
                          activeTab === 'wfa_monitoring'
                            ? 'bg-emerald-50 text-emerald-800 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'wfa_monitoring' ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700'}`}>
                            <Sparkles className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Pengajuan WFA</div>
                            <div className="text-[10px] text-slate-400 font-normal">Validasi jadwal bimbingan</div>
                          </div>
                        </div>
                        {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length > 0 ? (
                          <span className="px-1.5 py-0.5 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] animate-pulse">
                            {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length}
                          </span>
                        ) : (
                          <span className="px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                            {wfaSubmissions.length}
                          </span>
                        )}
                      </button>

                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('kebugaran_monitoring');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all mt-0.5 ${
                          activeTab === 'kebugaran_monitoring'
                            ? 'bg-sky-50 text-sky-800 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'kebugaran_monitoring' ? 'bg-sky-600 text-white' : 'bg-sky-100 text-sky-700'}`}>
                            <Activity className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Data Kebugaran</div>
                            <div className="text-[10px] text-slate-400 font-normal">Rockport & Single Test</div>
                          </div>
                        </div>
                        <span className="px-1.5 py-0.5 rounded-full bg-sky-100 text-sky-700 text-[10px] font-bold">
                          {kebugaranSubmissions.length}
                        </span>
                      </button>

                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('meeting_attendance');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all mt-0.5 ${
                          activeTab === 'meeting_attendance'
                            ? 'bg-teal-50 text-teal-800 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'meeting_attendance' ? 'bg-teal-700 text-white' : 'bg-teal-100 text-teal-800'}`}>
                            <FileCheck2 className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Daftar Hadir Rapat</div>
                            <div className="text-[10px] text-slate-400 font-normal">Presensi, agenda & paraf</div>
                          </div>
                        </div>
                        {meetingAttendees.length > 0 && (
                          <span className={`px-1.5 py-0.5 rounded-full text-[10px] font-bold ${activeTab === 'meeting_attendance' ? 'bg-teal-700 text-white' : 'bg-teal-100 text-teal-800'}`}>
                            {meetingAttendees.length}
                          </span>
                        )}
                      </button>

                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('pegawai_forms');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all mt-0.5 ${
                          activeTab === 'pegawai_forms'
                            ? 'bg-indigo-50 text-indigo-800 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'pegawai_forms' ? 'bg-indigo-600 text-white' : 'bg-indigo-100 text-indigo-800'}`}>
                            <ClipboardList className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Formulir Pegawai</div>
                            <div className="text-[10px] text-slate-400 font-normal">Survei, MOOC & pelacak</div>
                          </div>
                        </div>
                        {pegawaiResponses.length > 0 && (
                          <span className={`px-1.5 py-0.5 rounded-full text-[10px] font-bold ${activeTab === 'pegawai_forms' ? 'bg-indigo-600 text-white' : 'bg-indigo-100 text-indigo-800'}`}>
                            {pegawaiResponses.length}
                          </span>
                        )}
                      </button>
                    </div>
                  )}
                </div>

                {/* 5. Pengaturan Dropdown (Keamanan & PIN, Nomor Whatsapp Admin, Cadangan Data) */}
                <div className="relative" ref={settingsDropdownRef}>
                  <button
                    onClick={() => setOpenDropdown(openDropdown === 'settings' ? null : 'settings')}
                    className={`flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3.5 py-1.5 rounded-md text-xs font-medium transition-all whitespace-nowrap ${
                      activeTab === 'security' || activeTab === 'export' || activeTab === 'whatsapp_settings'
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70'
                    }`}
                  >
                    <Settings className="w-3.5 h-3.5 shrink-0" />
                    <span>Pengaturan</span>
                    <ChevronDown className={`w-3 h-3 transition-transform duration-200 ${openDropdown === 'settings' ? 'rotate-180' : ''}`} />
                  </button>

                  {openDropdown === 'settings' && (
                    <div className="absolute right-0 top-full mt-1.5 w-64 bg-white rounded-xl shadow-xl border border-slate-200/90 p-1.5 z-50 text-slate-800 animate-in fade-in zoom-in-95 duration-100">
                      {/* 1. Keamanan & PIN */}
                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('security');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all ${
                          activeTab === 'security'
                            ? 'bg-indigo-50 text-indigo-700 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'security' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                            <Lock className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Keamanan & PIN</div>
                            <div className="text-[10px] text-slate-400 font-normal">PIN Super Admin & hak akses</div>
                          </div>
                        </div>
                      </button>

                      {/* 2. Nomor Whatsapp Admin */}
                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('whatsapp_settings');
                          setOpenDropdown(null);
                        }}
                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all mt-0.5 ${
                          activeTab === 'whatsapp_settings'
                            ? 'bg-emerald-50 text-emerald-700 font-bold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`p-1.5 rounded-md ${activeTab === 'whatsapp_settings' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                            <MessageCircle className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Nomor Whatsapp Admin</div>
                            <div className="text-[10px] text-slate-400 font-normal">Kontak bantuan & helpdesk OSDM</div>
                          </div>
                        </div>
                      </button>

                      {/* 3. Cadangan Data (Ekspor / Impor) */}
                      <button
                        type="button"
                        onClick={() => {
                          setIsExportImportOpen(true);
                          setOpenDropdown(null);
                        }}
                        className="w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs transition-all mt-0.5 text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium"
                      >
                        <div className="flex items-center gap-2">
                          <div className="p-1.5 rounded-md bg-slate-100 text-slate-600">
                            <FileCode className="w-3.5 h-3.5" />
                          </div>
                          <div className="text-left">
                            <div className="text-xs font-semibold">Cadangan Data (Ekspor / Impor)</div>
                            <div className="text-[10px] text-slate-400 font-normal">Cadangkan, unduh & pulihkan data</div>
                          </div>
                        </div>
                      </button>
                    </div>
                  )}
                </div>

                {/* Right Action Controls: Live Preview HP, Tambah Menu, & Notification Badges (docked at the far right) */}
                {adminRole === 'superadmin' && (
                  <div className="ml-auto flex items-center gap-1.5 shrink-0">
                    {(activeTab === 'menus' || activeTab === 'theme') && (
                      <button
                        onClick={() => setShowLiveSidePreview(!showLiveSidePreview)}
                        className={`hidden xl:flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium border transition-all shrink-0 ${
                          showLiveSidePreview
                            ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                        }`}
                        title="Tampilkan / Sembunyikan Pratinjau Smartphone di samping"
                      >
                        <Smartphone className="w-3.5 h-3.5" />
                        <span>Live Preview HP</span>
                      </button>
                    )}

                    {activeTab === 'menus' && (
                      <button
                        onClick={handleAddNewMenu}
                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium shadow-sm transition-all shrink-0 whitespace-nowrap mr-1"
                        title="Tambah tautan menu baru ke portal"
                      >
                        <Plus className="w-4 h-4" />
                        <span>Tambah Menu</span>
                      </button>
                    )}

                    {/* Notification Badges: WFA & Kebugaran at far right */}
                    {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length > 0 && (
                      <div
                        className="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-500/15 border border-amber-400/40 text-amber-900 select-none cursor-default shadow-2xs"
                        title={`${wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length} pengajuan WFA menunggu validasi`}
                      >
                        <Sparkles className="w-3.5 h-3.5 text-amber-600" />
                        <span className="text-[11px] font-bold whitespace-nowrap">WFA</span>
                        <span className="px-1.5 py-0.5 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] animate-pulse min-w-[18px] text-center">
                          {wfaSubmissions.filter(s => s.status === 'Menunggu Validasi').length}
                        </span>
                      </div>
                    )}

                    {kebugaranSubmissions.length > 0 && (
                      <div
                        className="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-sky-500/15 border border-sky-400/40 text-sky-900 select-none cursor-default shadow-2xs"
                        title={`${kebugaranSubmissions.length} data kebugaran tercatat`}
                      >
                        <Activity className="w-3.5 h-3.5 text-sky-600" />
                        <span className="text-[11px] font-bold whitespace-nowrap">Kebugaran</span>
                        <span className="px-1.5 py-0.5 rounded-full bg-sky-600 text-white font-bold text-[10px] min-w-[18px] text-center">
                          {kebugaranSubmissions.length}
                        </span>
                      </div>
                    )}

                    {meetingAttendees.length > 0 && (
                      <div
                        className="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-teal-500/15 border border-teal-400/40 text-teal-900 select-none cursor-default shadow-2xs"
                        title={`${meetingAttendees.length} peserta tercatat dalam daftar hadir rapat`}
                      >
                        <FileCheck2 className="w-3.5 h-3.5 text-teal-700" />
                        <span className="text-[11px] font-bold whitespace-nowrap">Rapat</span>
                        <span className="px-1.5 py-0.5 rounded-full bg-teal-700 text-white font-bold text-[10px] min-w-[18px] text-center">
                          {meetingAttendees.length}
                        </span>
                      </div>
                    )}
                  </div>
                )}
              </>
            )}
          </div>
        </div>

        {/* Mobile Action Controls (sm:hidden) */}
        <div className="sm:hidden flex items-center justify-between gap-2 px-3 pb-2 pt-0.5 border-t border-slate-100">
          <div className="flex items-center gap-1.5">
            {adminRole !== 'wfa_admin' && (
              <button
                onClick={() => setIsExportImportOpen(true)}
                className="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold border border-slate-200/80 transition-colors active:scale-95"
                title="Backup / Impor JSON"
              >
                <FileCode className="w-3.5 h-3.5 text-slate-600" />
                <span>Backup</span>
              </button>
            )}
          </div>

          {adminRole !== 'wfa_admin' && activeTab === 'menus' && (
            <button
              onClick={handleAddNewMenu}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all active:scale-95"
            >
              <Plus className="w-4 h-4" />
              <span>+ Tambah Menu</span>
            </button>
          )}
        </div>
      </header>


      {/* Main Admin Workspace with Optional Live Preview Frame */}
      <div className="flex-1 p-4 sm:p-8 max-w-7xl mx-auto w-full space-y-6">
        {/* Status & Quick Sync Mini Bar (Superadmin - Menus & Theme or when changes exist) */}
        {adminRole !== 'wfa_admin' && (activeTab === 'menus' || activeTab === 'theme' || hasUnpublishedChanges) && (
          <div
            className={`flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-3.5 py-2 sm:py-2.5 rounded-xl border text-xs transition-all shadow-2xs ${
              hasUnpublishedChanges
                ? 'bg-amber-500/10 border-amber-300 text-amber-950'
                : 'bg-emerald-500/10 border-emerald-300 text-emerald-950'
            }`}
          >
            {/* Status Indicator */}
            <div className="flex items-center gap-2 min-w-0">
              <span
                className={`w-2.5 h-2.5 rounded-full shrink-0 ${
                  hasUnpublishedChanges ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'
                }`}
              />
              <span className="font-bold text-xs truncate">
                {hasUnpublishedChanges
                  ? '⚠️ Ada perubahan belum diposting'
                  : '✅ Tampilan Pegawai Tersinkronisasi'}
              </span>
              <span className="text-[10px] text-slate-500 font-mono hidden sm:inline">
                {lastPublishedAt
                  ? `(Diposting: ${new Date(lastPublishedAt).toLocaleTimeString('id-ID', {
                      hour: '2-digit',
                      minute: '2-digit',
                      second: '2-digit',
                    })})`
                  : '(Belum diposting)'}
              </span>
            </div>

            {/* Quick Actions */}
            <div className="flex items-center gap-2 shrink-0">
              <label
                className="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/80 border border-slate-200 text-slate-700 cursor-pointer hover:bg-white text-[11px] shadow-2xs transition-colors"
                title="Jika aktif, setiap perubahan menu/tema otomatis tersimpan ke Cloud untuk pegawai"
              >
                <input
                  type="checkbox"
                  checked={autoPublishEnabled}
                  onChange={handleToggleAutoPublish}
                  className="rounded text-emerald-600 focus:ring-emerald-500 w-3 h-3 cursor-pointer"
                />
                <span className="font-medium whitespace-nowrap">⚡ Auto-Posting</span>
              </label>

              <button
                type="button"
                onClick={handleCopyShareLink}
                className="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white hover:bg-slate-50 text-slate-800 text-[11px] font-semibold border border-slate-300 shadow-2xs transition-colors cursor-pointer whitespace-nowrap"
                title="Salin tautan resmi yang dapat langsung dibagikan ke pegawai"
              >
                <Share2 className="w-3 h-3 text-indigo-600" />
                <span>{copiedShareLink ? 'Link Tersalin!' : 'Salin Link'}</span>
              </button>
            </div>
          </div>
        )}

        <div className={`grid grid-cols-1 ${isSidePreviewVisible ? 'xl:grid-cols-12' : ''} gap-8 items-start`}>
          {/* Main Editing Column */}
          <div className={isSidePreviewVisible ? 'xl:col-span-8' : 'w-full'}>
            {/* TAB 1: KELOLA MENU (Drag and Drop, Size, Color, CRUD) */}
            {activeTab === 'menus' && (
              <div className="space-y-6">
                {/* Header info banner */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 sm:p-5 bg-white border border-slate-200 rounded-xl shadow-xs">
                  <div>
                    <h2 className="text-base font-bold text-slate-900 tracking-tight">Daftar Tombol Direct Menu</h2>
                    <p className="text-xs text-slate-500 mt-0.5">
                      Urutan tombol menu yang tampil di microsite pegawai.
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-slate-500 font-medium px-2.5 py-1 bg-slate-100 rounded-md border border-slate-200">
                      {menus.filter((m) => m.isActive).length} Aktif / {menus.length} Total
                    </span>
                    <button
                      onClick={handleAddNewMenu}
                      className="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200 text-xs font-bold hover:bg-indigo-100 transition-colors"
                    >
                      + Tambah Menu
                    </button>
                  </div>
                </div>

                {/* Draggable Menu List */}
                <div className="space-y-3">
                  {menus.length > 0 ? (
                    menus.map((menu, index) => {
                      const isDragging = draggedIndex === index;

                      return (
                        <div
                          key={menu.id}
                          draggable
                          onDragStart={() => handleDragStart(index)}
                          onDragOver={(e) => handleDragOver(e, index)}
                          onDragEnd={handleDragEnd}
                          className={`
                            group p-3 sm:p-3.5 bg-white border rounded-xl transition-all duration-150
                            ${
                              isDragging
                                ? 'opacity-40 border-dashed border-indigo-500 scale-95 shadow-lg bg-indigo-50/50'
                                : 'border-slate-200 hover:border-slate-300 shadow-xs hover:shadow-sm'
                            }
                            ${!menu.isActive ? 'bg-slate-50/80 border-slate-200 opacity-60' : ''}
                          `}
                        >
                          {/* Mobile-Optimized View (sm:hidden) */}
                          <div className="sm:hidden flex flex-col gap-2.5">
                            {/* Top Row: Icon + Title + Metadata + Eye Toggle */}
                            <div className="flex items-start gap-2.5">
                              {/* Index & Icon */}
                              <div className="flex items-center gap-1.5 shrink-0">
                                <span className="w-5 text-center text-xs font-bold font-mono text-slate-400">
                                  #{index + 1}
                                </span>
                                <div
                                  style={{
                                    background: menu.isGradient
                                      ? `linear-gradient(135deg, ${menu.bgColor}, ${menu.gradientTo || menu.bgColor})`
                                      : menu.bgColor,
                                    color: menu.textColor || '#ffffff',
                                  }}
                                  className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-xs overflow-hidden p-2"
                                >
                                  {getIconComponent(menu.iconName, 'w-5 h-5')}
                                </div>
                              </div>

                              {/* Title & Metadata */}
                              <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-1.5 flex-wrap">
                                  <h3 className="text-sm font-bold text-slate-900 truncate">
                                    {menu.title}
                                  </h3>
                                  {menu.badgeText && (
                                    <span
                                      style={{
                                        backgroundColor: menu.badgeBgColor || '#f59e0b',
                                        color: menu.badgeTextColor || '#000',
                                      }}
                                      className="text-[9px] font-extrabold px-1.5 py-0.5 rounded uppercase"
                                    >
                                      {menu.badgeText}
                                    </span>
                                  )}
                                  {menu.category && (
                                    <span className="text-[9px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                      {menu.category}
                                    </span>
                                  )}
                                  {menu.isProtected && (
                                    <span className="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-300 flex items-center gap-0.5">
                                      <Lock className="w-2.5 h-2.5 text-amber-600" />
                                      <span>PIN</span>
                                    </span>
                                  )}
                                </div>

                                <div className="flex items-center gap-2 text-[11px] text-slate-500 mt-1">
                                  <span className="truncate max-w-[130px] font-mono text-[10px] text-slate-400">
                                    {menu.url}
                                  </span>
                                  <span className="font-mono text-indigo-600 font-semibold flex items-center gap-0.5 shrink-0">
                                    <Zap className="w-2.5 h-2.5 text-amber-500" />
                                    {menu.clickCount}
                                  </span>
                                </div>
                              </div>

                              {/* Visibility Toggle Button */}
                              <button
                                onClick={() => handleToggleActive(menu.id)}
                                title={menu.isActive ? 'Status: Aktif' : 'Status: Nonaktif'}
                                className={`p-2 rounded-lg border transition-all shrink-0 active:scale-95 ${
                                  menu.isActive
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 shadow-2xs'
                                    : 'bg-slate-100 text-slate-400 border-slate-200'
                                }`}
                              >
                                {menu.isActive ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
                              </button>
                            </div>

                            {/* Bottom Toolbar: Reorder, Size, Actions */}
                            <div className="pt-2 border-t border-slate-100 flex items-center justify-between gap-1.5">
                              {/* Reorder Arrows */}
                              <div className="flex items-center bg-slate-100 border border-slate-200 rounded-lg p-0.5">
                                <button
                                  onClick={() => handleMove(index, 'up')}
                                  disabled={index === 0}
                                  title="Pindah ke Atas"
                                  className="p-1.5 text-slate-600 hover:text-slate-900 disabled:opacity-30 rounded hover:bg-white active:bg-white"
                                >
                                  <ArrowUp className="w-3.5 h-3.5" />
                                </button>
                                <button
                                  onClick={() => handleMove(index, 'down')}
                                  disabled={index === menus.length - 1}
                                  title="Pindah ke Bawah"
                                  className="p-1.5 text-slate-600 hover:text-slate-900 disabled:opacity-30 rounded hover:bg-white active:bg-white"
                                >
                                  <ArrowDown className="w-3.5 h-3.5" />
                                </button>
                              </div>

                              {/* Size Selector */}
                              <div className="flex items-center bg-slate-100 border border-slate-200 rounded-lg p-0.5">
                                {([
                                  { size: 'compact', label: 'C' },
                                  { size: 'medium', label: 'M' },
                                  { size: 'large', label: 'L' },
                                  { size: 'featured', label: 'Hero' },
                                ] as const).map((s) => (
                                  <button
                                    key={s.size}
                                    onClick={() => handleQuickSizeChange(menu.id, s.size)}
                                    className={`px-2 py-1 text-[10px] font-bold rounded transition-all ${
                                      menu.size === s.size
                                        ? 'bg-white text-indigo-600 shadow-xs border border-slate-200/80'
                                        : 'text-slate-500 hover:text-slate-900'
                                    }`}
                                  >
                                    {s.label}
                                  </button>
                                ))}
                              </div>

                              {/* Actions: Edit, Duplicate, Delete */}
                              <div className="flex items-center gap-1">
                                <button
                                  onClick={() => handleEditMenu(menu)}
                                  title="Edit Menu"
                                  className="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition-colors active:scale-95"
                                >
                                  <Edit2 className="w-3.5 h-3.5" />
                                  <span>Edit</span>
                                </button>

                                <button
                                  onClick={() => handleDuplicateMenu(menu)}
                                  title="Duplikat"
                                  className="p-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors active:scale-95"
                                >
                                  <Copy className="w-3.5 h-3.5" />
                                </button>

                                <button
                                  onClick={() => handleDeleteMenu(menu.id)}
                                  title="Hapus Menu"
                                  className="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 transition-colors active:scale-95"
                                >
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              </div>
                            </div>
                          </div>

                          {/* Desktop-Optimized View (hidden sm:flex) */}
                          <div className="hidden sm:flex sm:items-center justify-between gap-3">
                            {/* Left: Drag Handle, Icon, Title, Subtitle, Badges */}
                            <div className="flex items-center gap-3 flex-1 min-w-0">
                              {/* Drag Handle */}
                              <div
                                title="Tahan dan geser untuk memindahkan posisi"
                                className="cursor-grab active:cursor-grabbing p-1.5 rounded-md text-slate-300 group-hover:text-slate-500 hover:bg-slate-100 transition-colors shrink-0"
                              >
                                <GripVertical className="w-5 h-5" />
                              </div>

                              {/* Icon Box with Button Color Accent */}
                              <div
                                style={{
                                  background: menu.isGradient
                                    ? `linear-gradient(135deg, ${menu.bgColor}, ${menu.gradientTo || menu.bgColor})`
                                    : menu.bgColor,
                                  color: menu.textColor || '#ffffff',
                                }}
                                className="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 shadow-xs overflow-hidden p-1.5"
                              >
                                {getIconComponent(menu.iconName, 'w-full h-full max-w-[24px] max-h-[24px]')}
                              </div>

                              {/* Title & Metadata */}
                              <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 flex-wrap">
                                  <h3 className="text-sm font-semibold text-slate-900 truncate">
                                    {menu.title}
                                  </h3>
                                  {menu.badgeText && (
                                    <span
                                      style={{
                                        backgroundColor: menu.badgeBgColor || '#f59e0b',
                                        color: menu.badgeTextColor || '#000',
                                      }}
                                      className="text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider"
                                    >
                                      {menu.badgeText}
                                    </span>
                                  )}
                                  {menu.category && (
                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                      {menu.category}
                                    </span>
                                  )}
                                  {menu.isProtected && (
                                    <span
                                      title={`Menu terproteksi PIN: ${menu.pinCode || 'Aktif'}`}
                                      className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-300 flex items-center gap-1"
                                    >
                                      <Lock className="w-2.5 h-2.5 text-amber-600" />
                                      <span>PIN: {menu.pinCode || 'Aktif'}</span>
                                    </span>
                                  )}
                                </div>

                                <div className="flex items-center gap-3 text-xs text-slate-400 mt-0.5 flex-wrap">
                                  <span className="truncate max-w-[220px] font-mono text-[11px] text-slate-500">
                                    {menu.url}
                                  </span>
                                  <span className="font-mono text-indigo-600 text-[11px] font-semibold flex items-center gap-1">
                                    <Zap className="w-3 h-3 text-amber-500" />
                                    {menu.clickCount} klik
                                  </span>
                                </div>
                              </div>
                            </div>

                            {/* Right: Real-time Quick Size Selector & Actions */}
                            <div className="flex items-center gap-1.5 self-end sm:self-center shrink-0 flex-wrap">
                              {/* Quick Size Switcher */}
                              <div className="flex items-center bg-slate-100 border border-slate-200 rounded-md p-0.5">
                                {(
                                  [
                                    { size: 'compact', label: 'C' },
                                    { size: 'medium', label: 'M' },
                                    { size: 'large', label: 'L' },
                                    { size: 'featured', label: 'Hero' },
                                  ] as const
                                ).map((s) => (
                                  <button
                                    key={s.size}
                                    onClick={() => handleQuickSizeChange(menu.id, s.size)}
                                    title={`Ubah ukuran ke: ${s.size.toUpperCase()}`}
                                    className={`px-2 py-0.5 text-[10px] font-semibold rounded transition-all ${
                                      menu.size === s.size
                                        ? 'bg-white text-indigo-600 shadow-xs font-bold border border-slate-200'
                                        : 'text-slate-500 hover:text-slate-900'
                                    }`}
                                  >
                                    {s.label}
                                  </button>
                                ))}
                              </div>

                              {/* Up / Down Reorder Arrows */}
                              <div className="flex items-center bg-slate-100 border border-slate-200 rounded-md p-0.5">
                                <button
                                  onClick={() => handleMove(index, 'up')}
                                  disabled={index === 0}
                                  title="Pindah ke Atas"
                                  className="p-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 rounded hover:bg-white"
                                >
                                  <ArrowUp className="w-3.5 h-3.5" />
                                </button>
                                <button
                                  onClick={() => handleMove(index, 'down')}
                                  disabled={index === menus.length - 1}
                                  title="Pindah ke Bawah"
                                  className="p-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 rounded hover:bg-white"
                                >
                                  <ArrowDown className="w-3.5 h-3.5" />
                                </button>
                              </div>

                              {/* Active Status Toggle */}
                              <button
                                onClick={() => handleToggleActive(menu.id)}
                                title={menu.isActive ? 'Nonaktifkan' : 'Aktifkan'}
                                className={`p-1.5 rounded-md border transition-colors ${
                                  menu.isActive
                                    ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
                                    : 'bg-slate-100 text-slate-400 border-slate-200 hover:bg-slate-200'
                                }`}
                              >
                                {menu.isActive ? <Eye className="w-3.5 h-3.5" /> : <EyeOff className="w-3.5 h-3.5" />}
                              </button>

                              {/* Edit Modal Button */}
                              <button
                                onClick={() => handleEditMenu(menu)}
                                title="Edit Detail, Warna & Ukuran"
                                className="p-1.5 rounded-md bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors hover:text-indigo-600 shadow-xs"
                              >
                                <Edit2 className="w-3.5 h-3.5" />
                              </button>

                              {/* Duplicate Button */}
                              <button
                                onClick={() => handleDuplicateMenu(menu)}
                                title="Duplikat Tombol"
                                className="p-1.5 rounded-md bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors hover:text-slate-900 shadow-xs"
                              >
                                <Copy className="w-3.5 h-3.5" />
                              </button>

                              {/* Delete Button */}
                              <button
                                onClick={() => handleDeleteMenu(menu.id)}
                                title="Hapus Menu"
                                className="p-1.5 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 transition-colors"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </div>
                        </div>
                      );
                    })
                  ) : (
                    <div className="p-10 text-center bg-white border border-slate-200 rounded-xl space-y-3 shadow-xs">
                      <p className="text-slate-500 text-sm">Belum ada tombol menu direct yang dibuat.</p>
                      <button
                        onClick={handleAddNewMenu}
                        className="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 shadow-xs"
                      >
                        + Tambah Menu Pertama
                      </button>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* TAB 2: KUSTOMISASI TEMA & PROFIL */}
            {activeTab === 'theme' && (
              <div className="space-y-6">
                {/* Profile Information */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-5 shadow-xs">
                  <div className="flex items-center gap-2.5">
                    <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                      <Settings className="w-5 h-5" />
                    </div>
                    <div>
                      <h3 className="text-sm font-bold uppercase tracking-wider text-slate-500">Identitas Portal & Instansi / Perusahaan</h3>
                      <p className="text-xs text-slate-400">
                        Atur nama portal pegawai, logo organisasi, deskripsi panduan, dan kontak layanan internal
                      </p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                        Nama Portal Pegawai / Perusahaan
                      </label>
                      <input
                        type="text"
                        value={profile.name}
                        onChange={(e) => setProfile({ ...profile, name: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tagline / Subjudul Portal
                      </label>
                      <input
                        type="text"
                        value={profile.tagline}
                        onChange={(e) => setProfile({ ...profile, tagline: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      />
                    </div>

                    <div className="sm:col-span-2">
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                        Deskripsi / Panduan Singkat untuk Pegawai
                      </label>
                      <textarea
                        rows={2}
                        value={profile.bio}
                        onChange={(e) => setProfile({ ...profile, bio: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      />
                    </div>

                    {/* Enhanced Flexible Logo Section */}
                    <div className="sm:col-span-2 p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                      <div className="flex items-center justify-between">
                        <div>
                          <label className="block text-xs font-bold text-slate-800">
                            🖼️ Logo & Ikon Portal (Upload dari Komputer atau URL)
                          </label>
                          <p className="text-[11px] text-slate-500">
                            Upload file logo dari komputer atau masukkan URL. Format landscape/panjang akan terlihat utuh.
                          </p>
                        </div>
                        {profile.avatarUrl && (
                          <span className="text-[10px] font-semibold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                            Tinggi: {profile.logoHeight || 70}px
                          </span>
                        )}
                      </div>

                      {/* File Upload Drop Zone & URL Input */}
                      <div className="space-y-3">
                        <div className="flex flex-col sm:flex-row gap-3 items-start">
                          <div className="flex-1 w-full space-y-2">
                            {/* Drag & Drop / Click Upload Box */}
                            <div
                              onDragOver={(e) => {
                                e.preventDefault();
                                setIsLogoDragging(true);
                              }}
                              onDragLeave={() => setIsLogoDragging(false)}
                              onDrop={(e) => {
                                e.preventDefault();
                                setIsLogoDragging(false);
                                if (e.dataTransfer.files?.[0]) {
                                  handleLocalImageUpload(e.dataTransfer.files[0], (dataUrl) => {
                                    setProfile({
                                      ...profile,
                                      avatarUrl: dataUrl,
                                      logoShape: profile.logoShape || 'landscape',
                                    });
                                  });
                                }
                              }}
                              className={`relative border-2 border-dashed rounded-xl p-3 text-center transition-all cursor-pointer ${
                                isLogoDragging
                                  ? 'border-indigo-500 bg-indigo-50/80 scale-[1.01]'
                                  : 'border-slate-300 bg-white hover:border-indigo-400 hover:bg-slate-50/50'
                              }`}
                            >
                              <input
                                type="file"
                                accept="image/*"
                                onChange={(e) => {
                                  if (e.target.files?.[0]) {
                                    handleLocalImageUpload(e.target.files[0], (dataUrl) => {
                                      setProfile({
                                        ...profile,
                                        avatarUrl: dataUrl,
                                        logoShape: profile.logoShape || 'landscape',
                                      });
                                    });
                                  }
                                }}
                                className="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                              />
                              <div className="flex items-center justify-center gap-2 text-xs font-semibold text-indigo-600">
                                <Upload className="w-4 h-4" />
                                <span>Klik untuk Pilih File Logo dari Komputer atau Drag & Drop</span>
                              </div>
                              <p className="text-[10px] text-slate-400 mt-0.5">
                                Mendukung PNG (Transparan), JPG, WebP, SVG
                              </p>
                            </div>

                            {/* Or Manual URL */}
                            <div className="flex items-center gap-2">
                              <span className="text-[10px] uppercase font-bold text-slate-400 shrink-0">atau URL:</span>
                              <input
                                type="text"
                                placeholder="https://.../logo-perusahaan.png"
                                value={profile.avatarUrl}
                                onChange={(e) => setProfile({ ...profile, avatarUrl: e.target.value })}
                                className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:border-indigo-500"
                              />
                            </div>

                            <div className="flex flex-wrap items-center gap-1.5 pt-1">
                              <span className="text-[10px] text-slate-400">Contoh Cepat:</span>
                              <button
                                type="button"
                                onClick={() => setProfile({
                                  ...profile,
                                  avatarUrl: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&auto=format&fit=crop&q=80',
                                  logoShape: 'landscape',
                                  logoHeight: 68
                                })}
                                className="text-[10px] px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-600 rounded border border-slate-200 transition-colors"
                              >
                                Gedung Korporat
                              </button>
                              <button
                                type="button"
                                onClick={() => setProfile({
                                  ...profile,
                                  avatarUrl: 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/375px-Google_2015_logo.svg.png',
                                  logoShape: 'landscape',
                                  logoHeight: 56,
                                  logoBackground: 'white'
                                })}
                                className="text-[10px] px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-600 rounded border border-slate-200 transition-colors"
                              >
                                Logo Teks Lebar (Google)
                              </button>
                              {profile.avatarUrl && (
                                <button
                                  type="button"
                                  onClick={() => setProfile({ ...profile, avatarUrl: '' })}
                                  className="text-[10px] px-2 py-0.5 bg-red-50 hover:bg-red-100 text-red-600 rounded border border-red-200 transition-colors"
                                >
                                  Hapus Logo
                                </button>
                              )}
                            </div>
                          </div>

                          {/* Live Mini Preview */}
                          <div className="shrink-0 flex flex-col items-center">
                            <div
                              style={{ height: `${Math.min(profile.logoHeight || 70, 85)}px` }}
                              className={`min-w-[110px] max-w-[180px] px-3 py-1.5 rounded-xl border border-slate-200 flex items-center justify-center transition-all ${
                                profile.logoBackground === 'white'
                                  ? 'bg-white shadow-xs'
                                  : profile.logoBackground === 'dark'
                                  ? 'bg-slate-900'
                                  : profile.logoBackground === 'transparent'
                                  ? 'bg-transparent border-dashed'
                                  : 'bg-slate-800 text-white'
                              }`}
                            >
                              {profile.avatarUrl ? (
                                <img
                                  src={profile.avatarUrl}
                                  alt="Preview Logo"
                                  style={{ maxHeight: `${Math.min((profile.logoHeight || 70) - 10, 75)}px` }}
                                  className="w-auto h-auto max-w-full object-contain"
                                />
                              ) : (
                                <span className="text-[10px] text-slate-400">Tanpa Logo</span>
                              )}
                            </div>
                            <span className="text-[9px] text-slate-400 mt-1">Live Mini Preview</span>
                          </div>
                        </div>
                      </div>

                      {/* Shape & Height Settings */}
                      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-200">
                        {/* Format / Bentuk */}
                        <div>
                          <label className="block text-[11px] font-semibold text-slate-700 mb-1">
                            Bentuk Tampilan Logo
                          </label>
                          <select
                            value={profile.logoShape || 'landscape'}
                            onChange={(e) => setProfile({ ...profile, logoShape: e.target.value as any })}
                            className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-500"
                          >
                            <option value="landscape">🏞️ Horizontal / Panjang (Landscape)</option>
                            <option value="banner">🏷️ Banner Lebar</option>
                            <option value="rounded-square">🔲 Kotak Rounded (App Icon)</option>
                            <option value="circle">🔘 Lingkaran (Avatar Bulat)</option>
                          </select>
                        </div>

                        {/* Tinggi Logo Slider */}
                        <div>
                          <div className="flex items-center justify-between mb-1">
                            <label className="block text-[11px] font-semibold text-slate-700">
                              Tinggi Logo: <span className="text-indigo-600 font-bold">{profile.logoHeight || 70}px</span>
                            </label>
                          </div>
                          <div className="flex items-center gap-2">
                            <input
                              type="range"
                              min="36"
                              max="130"
                              step="2"
                              value={profile.logoHeight || 70}
                              onChange={(e) => setProfile({ ...profile, logoHeight: parseInt(e.target.value) })}
                              className="w-full accent-indigo-600 cursor-pointer"
                            />
                          </div>
                          <div className="flex justify-between text-[9px] text-slate-400 mt-0.5">
                            <span>36px (Kecil)</span>
                            <span>70px (Standar)</span>
                            <span>130px (Besar)</span>
                          </div>
                        </div>

                        {/* Latar Belakang Wadah Logo */}
                        <div>
                          <label className="block text-[11px] font-semibold text-slate-700 mb-1">
                            Latar Belakang Wadah
                          </label>
                          <select
                            value={profile.logoBackground || 'glass'}
                            onChange={(e) => setProfile({ ...profile, logoBackground: e.target.value as any })}
                            className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-500"
                          >
                            <option value="glass">✨ Glass / Transparan Halus</option>
                            <option value="white">⚪ Putih Bersih (Cocok utk Logo Gelap)</option>
                            <option value="dark">⚫ Gelap / Slate Dark</option>
                            <option value="transparent">🚫 Tanpa Wadah (Transparan Penuh)</option>
                          </select>
                        </div>
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                        Lokasi Kantor / Wilayah Kerja
                      </label>
                      <input
                        type="text"
                        value={profile.location}
                        onChange={(e) => setProfile({ ...profile, location: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                        Jam Layanan Helpdesk & Sistem
                      </label>
                      <input
                        type="text"
                        value={profile.openingHours || ''}
                        onChange={(e) => setProfile({ ...profile, openingHours: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      />
                    </div>

                    <div className="flex items-center pt-6">
                      <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input
                          type="checkbox"
                          checked={profile.isVerified}
                          onChange={(e) => setProfile({ ...profile, isVerified: e.target.checked })}
                          className="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500"
                        />
                        <span>Tampilkan Lencana Terverifikasi (Portal Resmi Internal)</span>
                      </label>
                    </div>
                  </div>
                </div>

                {/* PENGATURAN FAVICON & JUDUL TAB BROWSER */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-5 shadow-xs">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex items-center gap-2.5">
                      <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                        <Globe className="w-5 h-5" />
                      </div>
                      <div>
                        <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700">
                          🌐 Pengaturan Favicon & Judul Tab Browser
                        </h3>
                        <p className="text-xs text-slate-400">
                          Ubah ikon favicon pada tab browser dan nama aplikasi yang tertera di tab browser (Document Title)
                        </p>
                      </div>
                    </div>

                    <span className="text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 shrink-0 flex items-center gap-1.5 self-start sm:self-auto">
                      <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                      Real-time Browser Sync
                    </span>
                  </div>

                  {/* Browser Window Mockup Preview */}
                  <div className="bg-slate-900 rounded-xl overflow-hidden border border-slate-800 shadow-md">
                    {/* Browser Title Bar & Window Controls */}
                    <div className="bg-slate-800/90 px-3 py-2 flex items-center gap-3 border-b border-slate-700/60">
                      {/* Red / Yellow / Green Window Dots */}
                      <div className="flex items-center gap-1.5">
                        <div className="w-2.5 h-2.5 rounded-full bg-rose-500/80"></div>
                        <div className="w-2.5 h-2.5 rounded-full bg-amber-500/80"></div>
                        <div className="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></div>
                      </div>

                      {/* Mock Browser Tab */}
                      <div className="flex items-center gap-2 bg-slate-900 text-slate-100 px-3 py-1.5 rounded-t-lg border-t border-x border-slate-700/80 max-w-xs shadow-xs">
                        {/* Favicon in Tab Preview */}
                        <div className="w-4 h-4 rounded shrink-0 flex items-center justify-center overflow-hidden bg-slate-800">
                          {profile.faviconUrl ? (
                            <img
                              src={profile.faviconUrl}
                              alt="Favicon"
                              className="w-full h-full object-contain"
                            />
                          ) : profile.avatarUrl ? (
                            <img
                              src={profile.avatarUrl}
                              alt="Favicon"
                              className="w-full h-full object-contain"
                            />
                          ) : (
                            <span className="text-xs">🏢</span>
                          )}
                        </div>
                        <span className="text-xs font-medium truncate">
                          {profile.tabTitle?.trim() || profile.name?.trim() || 'Portal Layanan Pegawai'}
                        </span>
                        <X className="w-3 h-3 text-slate-400 ml-auto shrink-0 hover:text-white" />
                      </div>

                      <div className="text-[11px] text-slate-400 font-mono hidden md:block">
                        + Tab Baru
                      </div>
                    </div>

                    {/* Browser Address Bar Mockup */}
                    <div className="px-4 py-2 bg-slate-900/95 flex items-center gap-2 text-xs text-slate-300">
                      <div className="flex items-center gap-1.5 bg-slate-800/80 border border-slate-700 rounded-lg px-3 py-1 w-full max-w-md font-mono text-[11px] text-slate-300">
                        <Lock className="w-3 h-3 text-emerald-400 shrink-0" />
                        <span className="text-emerald-400 font-semibold">https://</span>
                        <span className="text-slate-200">portal.perusahaan.internal</span>
                      </div>
                      <span className="text-[11px] text-slate-400 ml-auto hidden sm:inline">
                        Pratinjau tampilan tab browser pengguna
                      </span>
                    </div>
                  </div>

                  {/* Settings Form */}
                  <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 pt-1">
                    {/* Left Column: Tab Title */}
                    <div className="space-y-3">
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1">
                          Nama Aplikasi pada Tab Browser (Tab Title)
                        </label>
                        <p className="text-[11px] text-slate-400 mb-1.5">
                          Teks ini akan langsung muncul pada tab browser dan judul saat di-bookmark pengguna.
                        </p>
                        <input
                          type="text"
                          placeholder="Contoh: Portal Layanan Pegawai • PT Nusantara"
                          value={profile.tabTitle || ''}
                          onChange={(e) => setProfile({ ...profile, tabTitle: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-medium"
                        />
                      </div>

                      {/* Quick Title Presets */}
                      <div className="flex flex-wrap items-center gap-1.5">
                        <span className="text-[10px] text-slate-400 font-semibold">Opsi Cepat:</span>
                        <button
                          type="button"
                          onClick={() => setProfile({ ...profile, tabTitle: profile.name || 'Portal Layanan Pegawai' })}
                          className="text-[10px] px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-200 transition-colors"
                        >
                          Samakan dgn Nama Portal
                        </button>
                        <button
                          type="button"
                          onClick={() =>
                            setProfile({
                              ...profile,
                              tabTitle: `${profile.name || 'Portal Pegawai'} | Direct Menu`,
                            })
                          }
                          className="text-[10px] px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-200 transition-colors"
                        >
                          + Slogan Direct Menu
                        </button>
                        {profile.tabTitle && (
                          <button
                            type="button"
                            onClick={() => setProfile({ ...profile, tabTitle: '' })}
                            className="text-[10px] px-2 py-0.5 text-rose-600 hover:bg-rose-50 rounded border border-rose-200 transition-colors"
                          >
                            Reset ke Bawaan
                          </button>
                        )}
                      </div>
                    </div>

                    {/* Right Column: Favicon Settings */}
                    <div className="space-y-3">
                      <div>
                        <div className="flex items-center justify-between mb-1">
                          <label className="block text-xs font-bold text-slate-700">
                            Ikon Favicon pada Tab Browser
                          </label>
                          {profile.faviconUrl && (
                            <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-semibold">
                              Favicon Kustom Aktif
                            </span>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-400 mb-2">
                          Upload file ikon dari komputer (.ico, .png, .svg, .jpg) atau gunakan simbol emoji cepat.
                        </p>

                        {/* Favicon Upload Dropzone */}
                        <div
                          onDragOver={(e) => {
                            e.preventDefault();
                            setIsFaviconDragging(true);
                          }}
                          onDragLeave={() => setIsFaviconDragging(false)}
                          onDrop={(e) => {
                            e.preventDefault();
                            setIsFaviconDragging(false);
                            const file = e.dataTransfer.files?.[0];
                            if (file) {
                              handleLocalImageUpload(file, (url) => {
                                setProfile({ ...profile, faviconUrl: url });
                              });
                            }
                          }}
                          className={`
                            border-2 border-dashed rounded-xl p-3 text-center transition-all cursor-pointer flex items-center justify-center gap-3
                            ${
                              isFaviconDragging
                                ? 'border-indigo-500 bg-indigo-50'
                                : 'border-slate-200 bg-slate-50/60 hover:border-indigo-400 hover:bg-indigo-50/20'
                            }
                          `}
                        >
                          <input
                            id="favicon-upload-input"
                            type="file"
                            accept="image/*"
                            className="hidden"
                            onChange={(e) => {
                              const file = e.target.files?.[0];
                              if (file) {
                                handleLocalImageUpload(file, (url) => {
                                  setProfile({ ...profile, faviconUrl: url });
                                });
                              }
                            }}
                          />
                          <label
                            htmlFor="favicon-upload-input"
                            className="cursor-pointer flex items-center gap-2.5 w-full justify-center"
                          >
                            <div className="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                              <Upload className="w-4 h-4" />
                            </div>
                            <div className="text-left">
                              <span className="text-xs font-bold text-indigo-600 hover:text-indigo-700 block">
                                Upload Favicon dari Komputer
                              </span>
                              <span className="text-[10px] text-slate-400">
                                Format .ico, .png, atau .svg (32x32 / 64x64)
                              </span>
                            </div>
                          </label>
                        </div>
                      </div>

                      {/* Favicon URL input & Action buttons */}
                      <div className="space-y-2">
                        <div className="flex items-center gap-2">
                          <input
                            type="url"
                            placeholder="Atau tempel URL gambar Favicon (https://...)"
                            value={profile.faviconUrl || ''}
                            onChange={(e) => setProfile({ ...profile, faviconUrl: e.target.value })}
                            className="flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-500"
                          />
                          {profile.avatarUrl && (
                            <button
                              type="button"
                              onClick={() => setProfile({ ...profile, faviconUrl: profile.avatarUrl })}
                              className="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition-colors shrink-0"
                              title="Gunakan Logo Portal yang telah diupload sebagai Favicon"
                            >
                              Gunakan Logo Portal
                            </button>
                          )}
                          {profile.faviconUrl && (
                            <button
                              type="button"
                              onClick={() => setProfile({ ...profile, faviconUrl: '' })}
                              className="p-1.5 text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg transition-colors shrink-0"
                              title="Hapus Favicon Kustom"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          )}
                        </div>

                        {/* Quick Favicon Emojis / Presets */}
                        <div className="flex flex-wrap items-center gap-1.5 pt-1">
                          <span className="text-[10px] text-slate-400 font-semibold">Simbol Cepat:</span>
                          {[
                            { label: '🏢 Kantor', emoji: '🏢' },
                            { label: '🏛️ Instansi', emoji: '🏛️' },
                            { label: '💼 Kerja', emoji: '💼' },
                            { label: '📋 HR', emoji: '📋' },
                            { label: '⚡ Kilat', emoji: '⚡' },
                            { label: '🛡️ Aman', emoji: '🛡️' },
                            { label: '🌟 Bintang', emoji: '🌟' },
                            { label: '🌐 Web', emoji: '🌐' },
                          ].map((item) => (
                            <button
                              key={item.emoji}
                              type="button"
                              onClick={() => {
                                const svgFavicon = `data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>${item.emoji}</text></svg>`;
                                setProfile({ ...profile, faviconUrl: svgFavicon });
                              }}
                              className="text-xs px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md border border-slate-200 transition-colors"
                              title={`Gunakan emoji ${item.emoji} sebagai favicon`}
                            >
                              {item.emoji}
                            </button>
                          ))}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Theme Presets */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-4 shadow-xs">
                  <h3 className="text-sm font-bold uppercase tracking-wider text-slate-500">Preset Tema Microsite</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    {THEME_PRESETS.map((preset) => (
                      <button
                        key={preset.id}
                        type="button"
                        onClick={() => {
                          setProfile({ ...profile, theme: { ...preset } });
                          triggerSaveFeedback();
                        }}
                        className={`
                          p-4 rounded-xl border text-left transition-all
                          ${
                            profile.theme.id === preset.id
                              ? 'border-indigo-600 bg-indigo-50/70 ring-2 ring-indigo-500 text-slate-900 shadow-xs'
                              : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                          }
                        `}
                      >
                        <div className="flex items-center justify-between mb-2">
                          <span className="text-xs font-bold text-slate-900">{preset.name}</span>
                          {profile.theme.id === preset.id && (
                            <span className="text-[10px] bg-indigo-600 text-white px-2 py-0.5 rounded font-bold">
                              Aktif
                            </span>
                          )}
                        </div>
                        <div className="flex items-center gap-1.5 h-6 rounded-lg overflow-hidden border border-slate-200 p-0.5 bg-white">
                          <div style={{ backgroundColor: preset.primaryBg }} className="flex-1 h-full rounded" />
                          <div style={{ backgroundColor: preset.secondaryBg }} className="flex-1 h-full rounded" />
                          <div style={{ backgroundColor: preset.accentColor }} className="w-6 h-full rounded" />
                        </div>
                      </button>
                    ))}
                  </div>
                </div>

                {/* Custom Background Upload Card */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-5 shadow-xs">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                      <div className="flex items-center gap-2">
                        <div className="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                          <Upload className="w-4 h-4" />
                        </div>
                        <h3 className="text-sm font-bold text-slate-900 tracking-tight">
                          Background Kustom (Upload dari Komputer)
                        </h3>
                      </div>
                      <p className="text-xs text-slate-500 mt-1">
                        Ganti latar belakang portal dengan foto kantor, banner perusahaan, atau gambar desain sendiri
                      </p>
                    </div>

                    {profile.theme.customBgImage && (
                      <div className="flex items-center gap-2">
                        <span className={`text-[11px] font-bold px-2.5 py-1 rounded-full border ${
                          profile.theme.bgType === 'custom-image'
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-slate-100 text-slate-600 border-slate-200'
                        }`}>
                          {profile.theme.bgType === 'custom-image' ? '● Background Aktif' : '○ Background Tersimpan'}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Drag & Drop Upload Zone */}
                  <div
                    onDragOver={(e) => {
                      e.preventDefault();
                      setIsBgDragging(true);
                    }}
                    onDragLeave={() => setIsBgDragging(false)}
                    onDrop={(e) => {
                      e.preventDefault();
                      setIsBgDragging(false);
                      if (e.dataTransfer.files?.[0]) {
                        handleLocalImageUpload(e.dataTransfer.files[0], (dataUrl) => {
                          setProfile({
                            ...profile,
                            theme: {
                              ...profile.theme,
                              customBgImage: dataUrl,
                              bgType: 'custom-image',
                              bgOverlayOpacity: profile.theme.bgOverlayOpacity ?? 70,
                              bgBlur: profile.theme.bgBlur ?? 2,
                              bgFit: profile.theme.bgFit ?? 'cover',
                              bgOverlayColor: profile.theme.bgOverlayColor ?? '#0f172a',
                            },
                          });
                        });
                      }
                    }}
                    className={`relative border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer ${
                      isBgDragging
                        ? 'border-indigo-500 bg-indigo-50/80 scale-[1.01]'
                        : 'border-slate-300 bg-slate-50/60 hover:border-indigo-400 hover:bg-slate-50'
                    }`}
                  >
                    <input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        if (e.target.files?.[0]) {
                          handleLocalImageUpload(e.target.files[0], (dataUrl) => {
                            setProfile({
                              ...profile,
                              theme: {
                                ...profile.theme,
                                customBgImage: dataUrl,
                                bgType: 'custom-image',
                                bgOverlayOpacity: profile.theme.bgOverlayOpacity ?? 70,
                                bgBlur: profile.theme.bgBlur ?? 2,
                                bgFit: profile.theme.bgFit ?? 'cover',
                                bgOverlayColor: profile.theme.bgOverlayColor ?? '#0f172a',
                              },
                            });
                          });
                        }
                      }}
                      className="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                    />

                    <div className="flex flex-col items-center justify-center space-y-2">
                      <div className="p-3 bg-white rounded-full shadow-xs border border-slate-200 text-indigo-600">
                        <Upload className="w-6 h-6" />
                      </div>
                      <div className="text-xs font-bold text-slate-800">
                        Klik untuk Memilih File Background dari Komputer atau Drag & Drop ke Sini
                      </div>
                      <p className="text-[11px] text-slate-500">
                        Mendukung format gambar resolusi tinggi (PNG, JPG, JPEG, WebP)
                      </p>
                    </div>
                  </div>

                  {/* Preset Background Cepat & URL Input */}
                  <div className="space-y-2 pt-1">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <span className="text-[11px] font-semibold text-slate-600">Preset Wallpaper Kantor & Tekstur:</span>
                      <div className="flex flex-wrap items-center gap-1.5">
                        {[
                          {
                            name: '🏢 Gedung Modern',
                            url: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1600&auto=format&fit=crop&q=80',
                          },
                          {
                            name: '💻 Ruang Rapat Tech',
                            url: 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1600&auto=format&fit=crop&q=80',
                          },
                          {
                            name: '🌿 Kantor Hijau Eco',
                            url: 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=1600&auto=format&fit=crop&q=80',
                          },
                          {
                            name: '🌌 Dark Sleek Grid',
                            url: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1600&auto=format&fit=crop&q=80',
                          },
                        ].map((bgItem) => (
                          <button
                            key={bgItem.name}
                            type="button"
                            onClick={() => {
                              setProfile({
                                ...profile,
                                theme: {
                                  ...profile.theme,
                                  customBgImage: bgItem.url,
                                  bgType: 'custom-image',
                                  bgOverlayOpacity: profile.theme.bgOverlayOpacity ?? 70,
                                  bgBlur: profile.theme.bgBlur ?? 2,
                                  bgFit: 'cover',
                                },
                              });
                              triggerSaveFeedback();
                            }}
                            className="text-[10px] px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg border border-slate-200 transition-colors"
                          >
                            {bgItem.name}
                          </button>
                        ))}
                      </div>
                    </div>

                    <div className="flex items-center gap-2 pt-1">
                      <span className="text-[10px] uppercase font-bold text-slate-400 shrink-0">atau URL Langsung:</span>
                      <input
                        type="text"
                        placeholder="https://.../gambar-background.jpg"
                        value={profile.theme.customBgImage || ''}
                        onChange={(e) => {
                          setProfile({
                            ...profile,
                            theme: {
                              ...profile.theme,
                              customBgImage: e.target.value,
                              bgType: e.target.value ? 'custom-image' : profile.theme.bgType,
                            },
                          });
                        }}
                        className="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:border-indigo-500"
                      />
                    </div>
                  </div>

                  {/* Active Custom Background Preview & Fine-tune Controls */}
                  {profile.theme.customBgImage && (
                    <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                      {/* Image Card Header */}
                      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                          <div className="w-16 h-12 rounded-lg border border-slate-200 overflow-hidden relative shadow-xs bg-slate-900 shrink-0">
                            <img
                              src={profile.theme.customBgImage}
                              alt="Background Preview"
                              className="w-full h-full object-cover"
                            />
                            <div
                              style={{
                                backgroundColor: profile.theme.bgOverlayColor || '#0f172a',
                                opacity: (profile.theme.bgOverlayOpacity ?? 70) / 100,
                              }}
                              className="absolute inset-0"
                            />
                          </div>
                          <div>
                            <div className="text-xs font-bold text-slate-800">
                              Preview Background & Efek Overlay
                            </div>
                            <div className="text-[11px] text-slate-500">
                              Sesuaikan transparansi dan blur agar teks & tombol tetap kontras
                            </div>
                          </div>
                        </div>

                        <div className="flex items-center gap-2">
                          <button
                            type="button"
                            onClick={() => {
                              setProfile({
                                ...profile,
                                theme: {
                                  ...profile.theme,
                                  bgType: profile.theme.bgType === 'custom-image' ? 'mesh' : 'custom-image',
                                },
                              });
                              triggerSaveFeedback();
                            }}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all ${
                              profile.theme.bgType === 'custom-image'
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                            }`}
                          >
                            {profile.theme.bgType === 'custom-image' ? '✓ Sedang Digunakan' : 'Terapkan Background Ini'}
                          </button>
                          <button
                            type="button"
                            onClick={() => {
                              setProfile({
                                ...profile,
                                theme: {
                                  ...profile.theme,
                                  customBgImage: undefined,
                                  bgType: 'mesh',
                                },
                              });
                              triggerSaveFeedback();
                            }}
                            className="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-medium transition-colors"
                            title="Hapus Background Kustom"
                          >
                            Hapus
                          </button>
                        </div>
                      </div>

                      {/* Fine-Tuning Sliders & Controls */}
                      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-3 border-t border-slate-200">
                        {/* Overlay Darkness Slider */}
                        <div>
                          <div className="flex items-center justify-between mb-1">
                            <label className="text-[11px] font-semibold text-slate-700">
                              Kegelapan Overlay: <span className="text-indigo-600 font-bold">{profile.theme.bgOverlayOpacity ?? 70}%</span>
                            </label>
                          </div>
                          <input
                            type="range"
                            min="0"
                            max="95"
                            step="5"
                            value={profile.theme.bgOverlayOpacity ?? 70}
                            onChange={(e) =>
                              setProfile({
                                ...profile,
                                theme: {
                                  ...profile.theme,
                                  bgOverlayOpacity: parseInt(e.target.value),
                                },
                              })
                            }
                            className="w-full accent-indigo-600 cursor-pointer"
                          />
                          <div className="flex justify-between text-[9px] text-slate-400 mt-0.5">
                            <span>0% (Terang)</span>
                            <span>70% (Standar)</span>
                            <span>95% (Gelap)</span>
                          </div>
                        </div>

                        {/* Blur Slider */}
                        <div>
                          <div className="flex items-center justify-between mb-1">
                            <label className="text-[11px] font-semibold text-slate-700">
                              Efek Blur Latar: <span className="text-indigo-600 font-bold">{profile.theme.bgBlur ?? 2}px</span>
                            </label>
                          </div>
                          <input
                            type="range"
                            min="0"
                            max="20"
                            step="1"
                            value={profile.theme.bgBlur ?? 2}
                            onChange={(e) =>
                              setProfile({
                                ...profile,
                                theme: {
                                  ...profile.theme,
                                  bgBlur: parseInt(e.target.value),
                                },
                              })
                            }
                            className="w-full accent-indigo-600 cursor-pointer"
                          />
                          <div className="flex justify-between text-[9px] text-slate-400 mt-0.5">
                            <span>0px (Tajam)</span>
                            <span>2px (Halus)</span>
                            <span>20px (Glassy)</span>
                          </div>
                        </div>

                        {/* Background Fit */}
                        <div>
                          <label className="block text-[11px] font-semibold text-slate-700 mb-1">
                            Gaya Tampilan (Fit)
                          </label>
                          <select
                            value={profile.theme.bgFit || 'cover'}
                            onChange={(e) =>
                              setProfile({
                                ...profile,
                                theme: {
                                  ...profile.theme,
                                  bgFit: e.target.value as any,
                                },
                              })
                            }
                            className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-500"
                          >
                            <option value="cover">🖼️ Cover (Penuh Layar)</option>
                            <option value="contain">🔍 Contain (Proporsional)</option>
                            <option value="tile">🔲 Tile (Ulang Pola)</option>
                          </select>
                        </div>

                        {/* Tint Color */}
                        <div>
                          <label className="block text-[11px] font-semibold text-slate-700 mb-1">
                            Warna Lapisan Tint
                          </label>
                          <select
                            value={profile.theme.bgOverlayColor || '#0f172a'}
                            onChange={(e) =>
                              setProfile({
                                ...profile,
                                theme: {
                                  ...profile.theme,
                                  bgOverlayColor: e.target.value,
                                },
                              })
                            }
                            className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-500"
                          >
                            <option value="#0f172a">🌑 Slate Navy (Default)</option>
                            <option value="#000000">⬛ Hitam Pekat (True Black)</option>
                            <option value="#0c1b33">🔷 Navy Korporat</option>
                            <option value="#06281e">🌲 Emerald Forest</option>
                            <option value="#1e1b4b">🟣 Deep Indigo</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  )}
                </div>

                {/* Layout & Card Styling */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-4 shadow-xs">
                  <h3 className="text-sm font-bold uppercase tracking-wider text-slate-500">Tata Letak & Gaya Kartu</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-2">
                        Format Tata Letak Tombol
                      </label>
                      <div className="grid grid-cols-2 gap-2">
                        <button
                          type="button"
                          onClick={() => setProfile({ ...profile, theme: { ...profile.theme, layoutStyle: 'stacked' } })}
                          className={`p-3 rounded-lg border text-xs font-medium transition-all ${
                            profile.theme.layoutStyle === 'stacked'
                              ? 'border-2 border-indigo-600 bg-indigo-50 font-bold text-indigo-600'
                              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                          }`}
                        >
                          📜 Kolom Stacked
                        </button>
                        <button
                          type="button"
                          onClick={() => setProfile({ ...profile, theme: { ...profile.theme, layoutStyle: 'bento' } })}
                          className={`p-3 rounded-lg border text-xs font-medium transition-all ${
                            profile.theme.layoutStyle === 'bento'
                              ? 'border-2 border-indigo-600 bg-indigo-50 font-bold text-indigo-600'
                              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                          }`}
                        >
                          🍱 Grid Bento
                        </button>
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-2">
                        Kelengkungan Sudut Tombol (Radius)
                      </label>
                      <div className="grid grid-cols-4 gap-2">
                        {[
                          { value: 'rounded-none', label: 'None' },
                          { value: 'rounded-lg', label: 'Soft' },
                          { value: 'rounded-2xl', label: 'Medium' },
                          { value: 'rounded-full', label: 'Full' },
                        ].map((r) => (
                          <button
                            key={r.value}
                            type="button"
                            onClick={() =>
                              setProfile({
                                ...profile,
                                theme: { ...profile.theme, cardRadius: r.value as any },
                              })
                            }
                            className={`py-2 px-1 border text-xs rounded transition-all text-center ${
                              profile.theme.cardRadius === r.value
                                ? 'border-2 border-indigo-600 bg-indigo-50 font-bold text-indigo-600'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                            }`}
                          >
                            {r.label}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Footer Customization */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-5 shadow-xs">
                  <div className="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                      <FileSignature className="w-4 h-4" />
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-slate-900 tracking-tight">
                        Pengaturan Teks & Lencana Footer
                      </h3>
                      <p className="text-xs text-slate-500">
                        Sesuaikan tulisan keamanan, hak cipta, dan catatan resmi yang tampil di bagian bawah portal
                      </p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Teks Lencana Keamanan / Status (Badge Atas Footer)</span>
                        <span className="text-[10px] text-slate-400 font-normal">Kosongkan jika ingin disembunyikan</span>
                      </label>
                      <input
                        type="text"
                        value={profile.footerBadgeText || ''}
                        placeholder="Contoh: Portal Resmi Pegawai • Akses Terenkripsi & Terverifikasi"
                        onChange={(e) => {
                          setProfile({ ...profile, footerBadgeText: e.target.value });
                          triggerSaveFeedback();
                        }}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-400"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Catatan Tambahan Footer (Deskripsi / SOP Singkat)</span>
                        <span className="text-[10px] text-slate-400 font-normal">Opsional</span>
                      </label>
                      <textarea
                        rows={2}
                        value={profile.footerText || ''}
                        placeholder="Contoh: Gunakan jaringan intranet kantor atau VPN perusahaan saat mengakses database sensitif."
                        onChange={(e) => {
                          setProfile({ ...profile, footerText: e.target.value });
                          triggerSaveFeedback();
                        }}
                        className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-400"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                        Teks Hak Cipta & Kepemilikan (Copyright)
                      </label>
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-slate-500 font-medium px-2 py-2 bg-slate-100 rounded-lg border border-slate-200 whitespace-nowrap">
                          {profile.name} © {new Date().getFullYear()} •
                        </span>
                        <input
                          type="text"
                          value={profile.footerCopyright || ''}
                          placeholder="Contoh: Portal Layanan Internal Pegawai"
                          onChange={(e) => {
                            setProfile({ ...profile, footerCopyright: e.target.value });
                            triggerSaveFeedback();
                          }}
                          className="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-400"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                {/* OSDM WhatsApp Helpdesk Setting */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-4 shadow-xs">
                  <div className="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div className="p-2 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100">
                      <MessageCircle className="w-4 h-4" />
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-slate-900 tracking-tight">
                        Nomor WhatsApp Tim Kerja OSDM (Layanan WFA Bimbingan)
                      </h3>
                      <p className="text-xs text-slate-500">
                        Nomor kontak ini aktif pada tombol &quot;Hubungi Tim Kerja OSDM&quot; di formulir saat pegawai memeriksa status pengajuan WFA yang masih dalam proses
                      </p>
                    </div>
                  </div>

                  <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-1">
                    <div className="flex-1">
                      <label className="block text-xs font-semibold text-slate-700 mb-1">
                        Nomor WhatsApp Helpdesk OSDM
                      </label>
                      <input
                        type="text"
                        value={profile.osdmContactWa || ''}
                        placeholder="Contoh: 08119712525"
                        onChange={(e) => {
                          setProfile({ ...profile, osdmContactWa: e.target.value });
                          triggerSaveFeedback();
                        }}
                        className="w-full px-3.5 py-2 text-sm font-mono font-bold bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 placeholder-slate-400"
                      />
                    </div>
                    <div className="sm:self-end">
                      <a
                        href={`https://wa.me/${(profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '').startsWith('0') ? '62' + (profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '').slice(1) : (profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '')}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg transition-colors flex items-center justify-center gap-1.5 shadow-xs"
                      >
                        <ExternalLink className="w-3.5 h-3.5" />
                        <span>Tes Link WA</span>
                      </a>
                    </div>
                  </div>
                </div>

                {/* Social & Contact Links Management */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-5 shadow-xs">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                      <div className="flex items-center gap-2">
                        <div className="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                          <Globe className="w-4 h-4" />
                        </div>
                        <h3 className="text-sm font-bold text-slate-900 tracking-tight">
                          Kontak Cepat & Media Sosial (Pesan, Email, Telepon, Internet, Lokasi)
                        </h3>
                      </div>
                      <p className="text-xs text-slate-500 mt-1">
                        Atur ikon tombol pintas kontak cepat yang tampil di header profil publik. Anda dapat menambah, mengubah, mengaktifkan/menonaktifkan, atau menghapus tautan kontak.
                      </p>
                    </div>

                    <button
                      type="button"
                      onClick={() => {
                        const newId = `social-${Date.now()}`;
                        const newSocial = {
                          id: newId,
                          platform: 'whatsapp' as const,
                          label: 'WhatsApp Admin',
                          url: 'https://wa.me/6281234567890',
                          isActive: true,
                        };
                        setProfile({
                          ...profile,
                          socialLinks: [...(profile.socialLinks || []), newSocial],
                        });
                        triggerSaveFeedback();
                      }}
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs shrink-0 self-start sm:self-auto"
                    >
                      <Plus className="w-3.5 h-3.5" />
                      <span>Tambah Kontak</span>
                    </button>
                  </div>

                  {(!profile.socialLinks || profile.socialLinks.length === 0) ? (
                    <div className="text-center py-6 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50">
                      <Globe className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                      <p className="text-xs text-slate-500 font-medium">Belum ada ikon kontak cepat.</p>
                      <p className="text-[11px] text-slate-400 mt-0.5">Klik tombol &quot;Tambah Kontak&quot; di atas untuk menambahkan pesan WhatsApp, email, telepon, internet/website, atau Google Maps.</p>
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {profile.socialLinks.map((social, index) => (
                        <div
                          key={social.id || index}
                          className={`p-3.5 rounded-xl border transition-all ${
                            social.isActive
                              ? 'bg-slate-50/80 border-slate-200'
                              : 'bg-slate-100/50 border-slate-200/60 opacity-60'
                          }`}
                        >
                          <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                            {/* Platform selector with icon preview */}
                            <div className="w-full sm:w-44 shrink-0">
                              <label className="block text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                Jenis / Tipe Kontak
                              </label>
                              <select
                                value={social.platform}
                                onChange={(e) => {
                                  const updated = (profile.socialLinks || []).map((s, i) =>
                                    i === index ? { ...s, platform: e.target.value as any } : s
                                  );
                                  setProfile({ ...profile, socialLinks: updated });
                                  triggerSaveFeedback();
                                }}
                                className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:border-indigo-500"
                              >
                                <option value="whatsapp">💬 Pesan (WhatsApp / Chat)</option>
                                <option value="email">✉️ Email (Surel)</option>
                                <option value="phone">📞 Telepon / Hotline</option>
                                <option value="website">🌐 Internet / Website</option>
                                <option value="maps">📍 Lokasi (Google Maps)</option>
                                <option value="instagram">📸 Instagram</option>
                                <option value="youtube">🎥 YouTube</option>
                                <option value="tiktok">🎵 TikTok</option>
                                <option value="facebook">👥 Facebook</option>
                                <option value="twitter">🐦 Twitter / X</option>
                              </select>
                            </div>

                            {/* Label */}
                            <div className="w-full sm:w-44 shrink-0">
                              <label className="block text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                Label / Judul
                              </label>
                              <input
                                type="text"
                                value={social.label}
                                placeholder="Contoh: WhatsApp Tim Kerja OSDM"
                                onChange={(e) => {
                                  const updated = (profile.socialLinks || []).map((s, i) =>
                                    i === index ? { ...s, label: e.target.value } : s
                                  );
                                  setProfile({ ...profile, socialLinks: updated });
                                  triggerSaveFeedback();
                                }}
                                className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-indigo-500"
                              />
                            </div>

                            {/* URL */}
                            <div className="flex-1">
                              <label className="block text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                Tautan URL / Nomor / Alamat
                              </label>
                              <input
                                type="text"
                                value={social.url}
                                placeholder={
                                  social.platform === 'whatsapp'
                                    ? 'https://wa.me/6281234567890'
                                    : social.platform === 'email'
                                    ? 'mailto:helpdesk@perusahaan.co.id'
                                    : social.platform === 'phone'
                                    ? 'tel:0211234567'
                                    : social.platform === 'maps'
                                    ? 'https://maps.google.com/...'
                                    : 'https://...'
                                }
                                onChange={(e) => {
                                  const updated = (profile.socialLinks || []).map((s, i) =>
                                    i === index ? { ...s, url: e.target.value } : s
                                  );
                                  setProfile({ ...profile, socialLinks: updated });
                                  triggerSaveFeedback();
                                }}
                                className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-none focus:border-indigo-500"
                              />
                            </div>

                            {/* Action Buttons: Toggle Active & Delete */}
                            <div className="flex items-center gap-1.5 pt-4 sm:pt-4 self-end sm:self-center shrink-0">
                              <button
                                type="button"
                                title={social.isActive ? 'Nonaktifkan' : 'Aktifkan'}
                                onClick={() => {
                                  const updated = (profile.socialLinks || []).map((s, i) =>
                                    i === index ? { ...s, isActive: !s.isActive } : s
                                  );
                                  setProfile({ ...profile, socialLinks: updated });
                                  triggerSaveFeedback();
                                }}
                                className={`p-1.5 rounded-lg border transition-all ${
                                  social.isActive
                                    ? 'bg-emerald-50 text-emerald-600 border-emerald-200 hover:bg-emerald-100'
                                    : 'bg-slate-200 text-slate-500 border-slate-300 hover:bg-slate-300'
                                }`}
                              >
                                {social.isActive ? <Eye className="w-3.5 h-3.5" /> : <EyeOff className="w-3.5 h-3.5" />}
                              </button>

                              <button
                                type="button"
                                title="Hapus Kontak"
                                onClick={() => {
                                  if (window.confirm(`Hapus kontak "${social.label}"?`)) {
                                    const updated = (profile.socialLinks || []).filter((_, i) => i !== index);
                                    setProfile({ ...profile, socialLinks: updated });
                                    triggerSaveFeedback();
                                  }
                                }}
                                className="p-1.5 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 transition-all"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* TAB 3: ANALITIK KLIK */}
            {activeTab === 'analytics' && (
              <AnalyticsView
                menus={menus}
                logs={logs}
                profile={profile}
                clickStats={clickStats}
                onRefresh={onRefreshAnalytics}
                onSimulateClick={onSimulateClick}
                onClearLogs={onClearLogs}
              />
            )}

            {/* TAB: MONITORING WFA BIMBINGAN */}
            {activeTab === 'wfa_monitoring' && (
              <WfaMonitoringView
                submissions={wfaSubmissions}
                onUpdateStatus={onUpdateWfaStatus || (async () => ({ success: true }))}
                onUpdateLink={onUpdateWfaLink}
                onDeleteSubmission={onDeleteWfaSubmission}
                onRefresh={onRefreshWfa}
                osdmContactWa={profile.osdmContactWa || '08119712525'}
                onUpdateOsdmContactWa={(newWa: string) => {
                  setProfile((prev) => ({
                    ...prev,
                    osdmContactWa: newWa,
                  }));
                  triggerSaveFeedback();
                }}
              />
            )}

            {/* TAB: MONITORING TES KEBUGARAN PEGAWAI */}
            {activeTab === 'kebugaran_monitoring' && (
              <KebugaranMonitoringView
                submissions={kebugaranSubmissions}
                onOpenInputModal={onOpenKebugaranModal}
                onDeleteSubmission={onDeleteKebugaranSubmission}
                onRefresh={onRefreshKebugaran}
                kebugaranMenu={menus.find((m) => m.id === 'menu-kebugaran-jasmani' || m.url === '#input-kebugaran')}
                onUpdateMenuPin={(menuId, isProtected, pinCode) => {
                  setMenus((prev) =>
                    prev.map((m) =>
                      m.id === menuId
                        ? { ...m, isProtected, pinCode, pinHint: isProtected ? 'Masukkan PIN Formulir Kebugaran' : '' }
                        : m
                    )
                  );
                  triggerSaveFeedback();
                }}
              />
            )}

            {/* TAB: DAFTAR HADIR RAPAT & PRESENSI */}
            {activeTab === 'meeting_attendance' && (
              <MeetingManagerView
                session={meetingSession}
                allSessions={meetingSessions}
                onSelectSession={onSelectMeetingSession}
                onCreateNewSession={onCreateMeetingSession}
                onDeleteSession={onDeleteMeetingSession}
                onToggleSessionStatus={onToggleMeetingSessionStatus}
                onUpdateSession={onUpdateMeetingSession || (() => {})}
                onResetSession={onResetMeetingSession || (() => {})}
                attendees={meetingAttendees}
                onDeleteAttendee={onDeleteMeetingAttendee || (() => {})}
                onDeleteAttendeesByAgenda={onDeleteAttendeesByAgenda}
                onAddAttendee={onAddMeetingAttendee || (() => {})}
                onOpenPublicForm={onOpenMeetingForm || (() => {})}
              />
            )}

            {/* TAB: FORMULIR & SURVEI PEGAWAI (MISAL: MOOC ASN BERINTEGRITAS) */}
            {activeTab === 'pegawai_forms' && (
              <PegawaiFormManagerView
                forms={pegawaiForms}
                selectedFormId={selectedPegawaiFormId}
                onSelectForm={onSelectPegawaiForm}
                responses={pegawaiResponses}
                onDeleteResponse={onDeletePegawaiResponse}
                onOpenFillModal={onOpenPegawaiFillModal}
                onCreateNewForm={() => setIsCreatePegawaiFormOpen(true)}
                onToggleFormStatus={onTogglePegawaiFormStatus}
                onUpdateForm={onUpdatePegawaiForm}
                onDeleteForm={onDeletePegawaiForm}
              />
            )}

            {/* TAB: DATABASE MASTER PEGAWAI (ASN & NON ASN) */}
            {activeTab === 'employees' && (
              <EmployeeManagerView />
            )}

            {/* TAB: PENGATURAN, KEAMANAN & CADANGAN DATA */}
            {(activeTab === 'security' || activeTab === 'export') && (
              <div className="space-y-6">
                {/* Header Info */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-4 shadow-xs">
                  <div className="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div className="p-2.5 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100">
                      <ShieldAlert className="w-5 h-5" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold text-slate-900 tracking-tight">
                        Pemisahan Akses Pegawai & Keamanan Super Admin
                      </h2>
                      <p className="text-xs text-slate-500">
                        Atur kata sandi (PIN) Super Admin pengelola portal agar pegawai umum hanya dapat melihat menu tanpa bisa mengubah konfigurasi.
                      </p>
                    </div>
                  </div>

                  {/* Status Banner */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    <div className="p-4 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-1.5 text-xs text-emerald-900">
                      <div className="flex items-center gap-1.5 font-bold text-emerald-800">
                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                        <span>Halaman Pegawai (Publik): Terisolasi Aman</span>
                      </div>
                      <p className="text-[11px] text-emerald-700 leading-relaxed">
                        Saat tautan portal dibuka oleh pegawai biasa, mereka hanya melihat katalog menu layanan tanpa bilah navigasi admin atau kontrol pengeditan.
                      </p>
                    </div>

                    <div className="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-1.5 text-xs text-indigo-900">
                      <div className="flex items-center gap-1.5 font-bold text-indigo-800">
                        <Key className="w-3.5 h-3.5" />
                        <span>Akses Super Admin: Dilindungi PIN</span>
                      </div>
                      <p className="text-[11px] text-indigo-700 leading-relaxed">
                        Pengelola utama portal (Super Admin) dapat masuk melalui tombol kunci tersembunyi di footer, pintasan keyboard <code className="bg-indigo-100/80 px-1 py-0.5 rounded font-mono font-bold">Alt + A</code>, atau URL <code className="bg-indigo-100/80 px-1 py-0.5 rounded font-mono font-bold">#admin</code>.
                      </p>
                    </div>
                  </div>
                </div>

                {/* Change PIN Form */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-5 shadow-xs">
                  <div>
                    <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-[11px] font-bold mb-1 border border-indigo-200">
                      <KeyRound className="w-3.5 h-3.5 text-indigo-600" />
                      <span>Hak Akses Utama (Root / Super Admin)</span>
                    </div>
                    <h3 className="text-sm font-bold text-slate-900">Ubah PIN / Password Super Admin</h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                      Ganti PIN pengelola portal dengan PIN rahasia baru (Akses Super Admin Tim OSDM dengan wewenang penuh portal).
                    </p>
                  </div>

                  {/* Current PIN Alert */}
                  <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs text-slate-700">
                    <span className="text-slate-500 font-medium">PIN Super Admin Saat Ini:</span>
                    <span className="font-mono font-bold bg-white px-2.5 py-1 rounded border border-slate-200 text-slate-900 shadow-2xs">
                      {adminPin}
                    </span>
                  </div>

                  {/* New PIN Input */}
                  <form
                    onSubmit={(e) => {
                      e.preventDefault();
                      setPinChangeError('');
                      setPinChangeSuccess(false);

                      if (!newPinInput.trim()) {
                        setPinChangeError('PIN baru tidak boleh kosong.');
                        return;
                      }
                      if (newPinInput.length < 4) {
                        setPinChangeError('PIN minimal 4 karakter demi keamanan.');
                        return;
                      }
                      if (newPinInput !== confirmPinInput) {
                        setPinChangeError('Konfirmasi PIN tidak cocok dengan PIN baru.');
                        return;
                      }

                      const cleanPin = newPinInput.trim();
                      if (setAdminPin) {
                        setAdminPin(cleanPin);
                      }
                      if (setMenus && Array.isArray(menus)) {
                        const updated = menus.map((m) => {
                          if (m.isProtected) {
                            return { ...m, pinCode: cleanPin };
                          }
                          return m;
                        });
                        setMenus(updated);
                      }
                      setNewPinInput('');
                      setConfirmPinInput('');
                      setPinChangeSuccess(true);
                      triggerSaveFeedback();
                      setTimeout(() => setPinChangeSuccess(false), 4000);
                    }}
                    className="space-y-4 pt-2"
                  >
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          PIN / Password Super Admin Baru
                        </label>
                        <input
                          type="password"
                          value={newPinInput}
                          onChange={(e) => setNewPinInput(e.target.value)}
                          placeholder="Masukkan PIN Super Admin baru..."
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          Ulangi PIN Super Admin Baru
                        </label>
                        <input
                          type="password"
                          value={confirmPinInput}
                          onChange={(e) => setConfirmPinInput(e.target.value)}
                          placeholder="Ketik ulang PIN Super Admin baru..."
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        />
                      </div>
                    </div>

                    {pinChangeError && (
                      <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-xs font-medium text-red-700">
                        {pinChangeError}
                      </div>
                    )}

                    {pinChangeSuccess && (
                      <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-medium text-emerald-800 flex items-center gap-2">
                        <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>PIN Super Admin berhasil diperbarui dan disinkronkan ke Database Server! Berlaku otomatis di semua perangkat dan browser.</span>
                      </div>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                      <button
                        type="submit"
                        className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5"
                      >
                        <KeyRound className="w-3.5 h-3.5" />
                        <span>Simpan PIN Super Admin Baru</span>
                      </button>

                      {onLogout && (
                        <button
                          type="button"
                          onClick={onLogout}
                          className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition-colors flex items-center gap-1.5"
                        >
                          <LogOut className="w-3.5 h-3.5 text-slate-500" />
                          <span>Kunci & Keluar ke Tampilan Pegawai</span>
                        </button>
                      )}
                    </div>
                  </form>
                </div>

                {/* Matriks Akses & PIN Monitoring Khusus (Tabel Ringkas & Cerdas) */}
                {(() => {
                  const monitoringPinModules = [
                    {
                      id: 'wfa',
                      title: 'Monitoring WFA Pegawai',
                      subtitle: 'Verifikasi berkas laporan, telaah bukti kerja harian, dan validasi WFA',
                      badge: 'Akses Khusus Tim WFA',
                      badgeColor: 'bg-emerald-50 text-emerald-800 border-emerald-200',
                      iconColor: 'bg-emerald-100 text-emerald-700',
                      icon: Sparkles,
                      currentPin: wfaAdminPin || 'wfa123',
                      defaultPin: 'wfa123',
                      onUpdate: onUpdateWfaAdminPin,
                    },
                    {
                      id: 'kebugaran',
                      title: 'Monitoring Kebugaran Jasmani',
                      subtitle: 'Penilaian tes kebugaran (Rockport & Harvard Step Test) pegawai',
                      badge: 'Akses Khusus Tim Kebugaran',
                      badgeColor: 'bg-sky-50 text-sky-800 border-sky-200',
                      iconColor: 'bg-sky-100 text-sky-700',
                      icon: Activity,
                      currentPin: kebugaranAdminPin || 'fit123',
                      defaultPin: 'fit123',
                      onUpdate: onUpdateKebugaranAdminPin,
                    },
                    {
                      id: 'meeting',
                      title: 'Monitoring Daftar Hadir Rapat',
                      subtitle: 'Pengelolaan sesi presensi pertemuan/rapat dan rekapitulasi kehadiran',
                      badge: 'Akses Khusus Tim Hadir Rapat',
                      badgeColor: 'bg-teal-50 text-teal-800 border-teal-200',
                      iconColor: 'bg-teal-100 text-teal-700',
                      icon: FileCheck2,
                      currentPin: meetingAdminPin || 'rapat123',
                      defaultPin: 'rapat123',
                      onUpdate: onUpdateMeetingAdminPin,
                    },
                    {
                      id: 'terpadu',
                      title: 'Monitoring Terpadu (3-in-1)',
                      subtitle: 'Akses serentak WFA, Kebugaran, dan Hadir Rapat untuk pimpinan/koordinator',
                      badge: 'Akses Terpadu (WFA • Kebugaran • Rapat)',
                      badgeColor: 'bg-purple-50 text-purple-800 border-purple-200',
                      iconColor: 'bg-purple-100 text-purple-700',
                      icon: Layers,
                      currentPin: monitoringAllAdminPin || 'pantau123',
                      defaultPin: 'pantau123',
                      onUpdate: onUpdateMonitoringAllAdminPin,
                    },
                  ];

                  const activeEditModule = monitoringPinModules.find(m => m.id === activeEditPinModuleId) || null;

                  const handleResetModulePin = async (moduleItem: typeof monitoringPinModules[0]) => {
                    if (confirm(`Kembalikan PIN ${moduleItem.title} ke bawaan sistem (${moduleItem.defaultPin})?`)) {
                      setResettingPinModuleId(moduleItem.id);
                      try {
                        if (moduleItem.onUpdate) {
                          await moduleItem.onUpdate(moduleItem.defaultPin);
                        }
                        triggerSaveFeedback();
                        setPinTableAlert({
                          type: 'success',
                          text: `PIN ${moduleItem.title} berhasil dikembalikan ke bawaan sistem (${moduleItem.defaultPin}).`
                        });
                        setTimeout(() => setPinTableAlert(null), 5000);
                      } catch {
                        setPinTableAlert({
                          type: 'error',
                          text: `Gagal mereset PIN ${moduleItem.title}. Silakan coba lagi.`
                        });
                      } finally {
                        setResettingPinModuleId(null);
                      }
                    }
                  };

                  return (
                    <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-4 shadow-xs">
                      {/* Header Matriks */}
                      <div className="flex items-center justify-between flex-wrap gap-3 pb-3 border-b border-slate-100">
                        <div>
                          <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-[11px] font-bold mb-1">
                            <ShieldAlert className="w-3.5 h-3.5 text-indigo-600" />
                            <span>Hak Akses Terisolasi (RBAC)</span>
                          </div>
                          <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span>Matriks Akses & PIN Monitoring Khusus</span>
                          </h3>
                          <p className="text-xs text-slate-500 mt-0.5 max-w-2xl">
                            Kelola hak akses dan PIN verifikator untuk setiap modul pemantauan. Petugas monitoring hanya dapat membuka tab terkait tanpa hak mengubah tema atau konfigurasi portal.
                          </p>
                        </div>

                        <div className="flex items-center gap-2">
                          <button
                            type="button"
                            onClick={() => setShowAllMonitoringPins(prev => !prev)}
                            className="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                            title={showAllMonitoringPins ? "Sembunyikan seluruh PIN" : "Tampilkan seluruh PIN"}
                          >
                            {showAllMonitoringPins ? (
                              <>
                                <EyeOff className="w-3.5 h-3.5 text-slate-500" />
                                <span>Sembunyikan PIN</span>
                              </>
                            ) : (
                              <>
                                <Eye className="w-3.5 h-3.5 text-slate-500" />
                                <span>Lihat Seluruh PIN</span>
                              </>
                            )}
                          </button>
                        </div>
                      </div>

                      {/* Global Alert Notification */}
                      {pinTableAlert && (
                        <div className={`p-3 rounded-lg border text-xs font-medium flex items-center justify-between gap-2 ${
                          pinTableAlert.type === 'success' 
                            ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
                            : 'bg-red-50 border-red-200 text-red-700'
                        }`}>
                          <div className="flex items-center gap-2">
                            {pinTableAlert.type === 'success' ? (
                              <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                            ) : (
                              <ShieldAlert className="w-4 h-4 text-red-600 shrink-0" />
                            )}
                            <span>{pinTableAlert.text}</span>
                          </div>
                          <button
                            type="button"
                            onClick={() => setPinTableAlert(null)}
                            className="text-slate-400 hover:text-slate-600 p-0.5"
                          >
                            <X className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      )}

                      {/* Tabel Matriks Ringkas */}
                      <div className="overflow-x-auto rounded-lg border border-slate-200">
                        <table className="w-full text-left text-xs">
                          <thead className="bg-slate-50/80 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                            <tr>
                              <th scope="col" className="px-4 py-3">Modul Monitoring</th>
                              <th scope="col" className="px-4 py-3">Hak Akses & Lingkup</th>
                              <th scope="col" className="px-4 py-3">PIN Aktif</th>
                              <th scope="col" className="px-4 py-3 text-right">Aksi</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-slate-100 bg-white">
                            {monitoringPinModules.map((item) => {
                              const isRevealed = showAllMonitoringPins || !!revealedPinIds[item.id];
                              const isDefault = item.currentPin === item.defaultPin;
                              const isResetting = resettingPinModuleId === item.id;

                              return (
                                <tr key={item.id} className="hover:bg-slate-50/70 transition-colors">
                                  {/* Modul Monitoring */}
                                  <td className="px-4 py-3.5">
                                    <div className="flex items-center gap-3">
                                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${item.iconColor} border border-black/5`}>
                                        <item.icon className="w-4 h-4" />
                                      </div>
                                      <div>
                                        <div className="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                          <span>{item.title}</span>
                                          {isDefault ? (
                                            <span className="text-[10px] font-medium px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                              Default
                                            </span>
                                          ) : (
                                            <span className="text-[10px] font-semibold px-1.5 py-0.2 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                              Kustom
                                            </span>
                                          )}
                                        </div>
                                        <p className="text-[11px] text-slate-500 line-clamp-1 mt-0.5 max-w-sm">{item.subtitle}</p>
                                      </div>
                                    </div>
                                  </td>

                                  {/* Hak Akses & Lingkup */}
                                  <td className="px-4 py-3.5 whitespace-nowrap">
                                    <span className={`inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full border ${item.badgeColor}`}>
                                      {item.badge}
                                    </span>
                                  </td>

                                  {/* PIN Aktif */}
                                  <td className="px-4 py-3.5 whitespace-nowrap">
                                    <div className="flex items-center gap-2">
                                      <span className="font-mono text-xs font-bold px-2.5 py-1 rounded bg-slate-50 border border-slate-200 text-slate-800 tracking-wider shadow-2xs min-w-[76px] text-center">
                                        {isRevealed ? item.currentPin : '••••••••'}
                                      </span>
                                      <button
                                        type="button"
                                        onClick={() => setRevealedPinIds(prev => ({ ...prev, [item.id]: !prev[item.id] }))}
                                        className="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded transition-colors"
                                        title={isRevealed ? "Sembunyikan PIN" : "Tampilkan PIN"}
                                      >
                                        {isRevealed ? (
                                          <EyeOff className="w-3.5 h-3.5" />
                                        ) : (
                                          <Eye className="w-3.5 h-3.5" />
                                        )}
                                      </button>
                                    </div>
                                  </td>

                                  {/* Aksi */}
                                  <td className="px-4 py-3.5 whitespace-nowrap text-right">
                                    <div className="flex items-center justify-end gap-2">
                                      <button
                                        type="button"
                                        onClick={() => {
                                          setActiveEditPinModuleId(item.id);
                                          setModalNewPin('');
                                          setModalConfirmPin('');
                                          setModalPinError('');
                                          setModalPinSuccess(false);
                                          setShowModalPin(false);
                                        }}
                                        className="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold border border-indigo-200 flex items-center gap-1.5 transition-colors shadow-2xs"
                                        title={`Ubah PIN ${item.title}`}
                                      >
                                        <KeyRound className="w-3.5 h-3.5 text-indigo-600" />
                                        <span>Ubah PIN</span>
                                      </button>

                                      <button
                                        type="button"
                                        disabled={isResetting || isDefault}
                                        onClick={() => handleResetModulePin(item)}
                                        className="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium border border-slate-200 flex items-center gap-1 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                        title={isDefault ? `PIN sudah bawaan sistem (${item.defaultPin})` : `Kembalikan ke default (${item.defaultPin})`}
                                      >
                                        <RotateCcw className={`w-3.5 h-3.5 text-slate-500 ${isResetting ? 'animate-spin' : ''}`} />
                                        <span>Reset</span>
                                      </button>
                                    </div>
                                  </td>
                                </tr>
                              );
                            })}
                          </tbody>
                        </table>
                      </div>

                      {/* Footer Info */}
                      <div className="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                        <div className="flex items-center gap-1.5">
                          <KeyRound className="w-3.5 h-3.5 text-slate-400" />
                          <span>Tersinkronisasi ke server. Penambahan modul monitoring baru di masa depan akan otomatis rapi di matriks ini.</span>
                        </div>
                        <span className="font-medium text-slate-500">{monitoringPinModules.length} Modul Terdaftar</span>
                      </div>

                      {/* Modal Dialog Ubah PIN */}
                      {activeEditModule && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-150">
                          <div className="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-150">
                            {/* Modal Header */}
                            <div className="p-5 border-b border-slate-100 flex items-start justify-between bg-slate-50/70">
                              <div className="flex items-center gap-3">
                                <div className={`p-2.5 rounded-xl ${activeEditModule.iconColor} border border-black/5`}>
                                  <activeEditModule.icon className="w-5 h-5" />
                                </div>
                                <div>
                                  <h4 className="text-sm font-bold text-slate-900">Ubah PIN Akses</h4>
                                  <p className="text-xs font-semibold text-slate-600 mt-0.5">{activeEditModule.title}</p>
                                </div>
                              </div>
                              <button
                                type="button"
                                onClick={() => setActiveEditPinModuleId(null)}
                                className="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors"
                              >
                                <X className="w-4 h-4" />
                              </button>
                            </div>

                            {/* Modal Body */}
                            <form
                              onSubmit={async (e) => {
                                e.preventDefault();
                                setModalPinError('');
                                setModalPinSuccess(false);

                                const cleanPin = modalNewPin.trim();
                                if (!cleanPin) {
                                  setModalPinError('PIN baru tidak boleh kosong.');
                                  return;
                                }
                                if (cleanPin.length < 4) {
                                  setModalPinError('PIN minimal 4 karakter.');
                                  return;
                                }
                                if (cleanPin !== modalConfirmPin.trim()) {
                                  setModalPinError('Konfirmasi PIN tidak cocok dengan PIN baru.');
                                  return;
                                }

                                setIsSavingModalPin(true);
                                try {
                                  if (activeEditModule.onUpdate) {
                                    await activeEditModule.onUpdate(cleanPin);
                                  }
                                  triggerSaveFeedback();
                                  setModalPinSuccess(true);
                                  setPinTableAlert({
                                    type: 'success',
                                    text: `PIN ${activeEditModule.title} berhasil diperbarui dan disinkronkan ke server!`
                                  });
                                  setTimeout(() => {
                                    setActiveEditPinModuleId(null);
                                    setModalNewPin('');
                                    setModalConfirmPin('');
                                    setModalPinSuccess(false);
                                  }, 1200);
                                } catch {
                                  setModalPinError('Gagal menyimpan PIN baru ke server. Silakan coba lagi.');
                                } finally {
                                  setIsSavingModalPin(false);
                                }
                              }}
                              className="p-5 space-y-4"
                            >
                              {/* Reference current PIN */}
                              <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-medium">PIN Aktif Saat Ini:</span>
                                <span className="font-mono font-bold text-slate-800 bg-white px-2.5 py-0.5 rounded border border-slate-200 shadow-2xs">
                                  {activeEditModule.currentPin}
                                </span>
                              </div>

                              {/* Inputs */}
                              <div className="space-y-3">
                                <div>
                                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    PIN Baru
                                  </label>
                                  <div className="relative">
                                    <input
                                      type={showModalPin ? "text" : "password"}
                                      value={modalNewPin}
                                      onChange={(e) => setModalNewPin(e.target.value)}
                                      placeholder="Masukkan PIN baru minimal 4 karakter..."
                                      className="w-full px-3 py-2 pr-9 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                      autoFocus
                                    />
                                    <button
                                      type="button"
                                      onClick={() => setShowModalPin(prev => !prev)}
                                      className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                      {showModalPin ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                    </button>
                                  </div>
                                </div>

                                <div>
                                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Ulangi PIN Baru
                                  </label>
                                  <input
                                    type={showModalPin ? "text" : "password"}
                                    value={modalConfirmPin}
                                    onChange={(e) => setModalConfirmPin(e.target.value)}
                                    placeholder="Ketik ulang PIN baru..."
                                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                  />
                                </div>
                              </div>

                              {modalPinError && (
                                <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-xs font-medium text-red-700 flex items-center gap-2">
                                  <ShieldAlert className="w-4 h-4 shrink-0 text-red-600" />
                                  <span>{modalPinError}</span>
                                </div>
                              )}

                              {modalPinSuccess && (
                                <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-medium text-emerald-800 flex items-center gap-2">
                                  <CheckCircle2 className="w-4 h-4 shrink-0 text-emerald-600" />
                                  <span>PIN berhasil diperbarui dan disinkronkan!</span>
                                </div>
                              )}

                              {/* Modal Actions */}
                              <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                  type="button"
                                  onClick={() => setActiveEditPinModuleId(null)}
                                  className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-colors"
                                >
                                  Batal
                                </button>
                                <button
                                  type="submit"
                                  disabled={isSavingModalPin}
                                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5 disabled:opacity-50"
                                >
                                  <KeyRound className="w-3.5 h-3.5" />
                                  <span>{isSavingModalPin ? 'Menyimpan...' : 'Simpan PIN Baru'}</span>
                                </button>
                              </div>
                            </form>
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })()}

                {/* Cadangan & Sinkronisasi Konfigurasi (Backup Data & Export/Import) */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-4 shadow-xs">
                  <div className="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-100">
                    <div>
                      <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>💾 Cadangan Konfigurasi & Sinkronisasi Data</span>
                        <span className="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-semibold">Multi-Device Ready</span>
                      </h3>
                      <p className="text-xs text-slate-500 mt-0.5">
                        Cadangkan atau pulihkan seluruh konfigurasi portal, menu, profil, dan PIN ke file JSON atau file kode TypeScript.
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={() => setIsExportImportOpen(true)}
                      className="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 shadow-xs transition-colors flex items-center gap-1.5"
                    >
                      <Upload className="w-3.5 h-3.5 text-indigo-600" />
                      <span>Buka Dialog Cadangan & Impor</span>
                    </button>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    {/* Unduh initialData.ts */}
                    <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                      <div className="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                        <FileText className="w-4 h-4 text-indigo-600" />
                        <span>Unduh File Kode Sumber (initialData.ts)</span>
                      </div>
                      <p className="text-[11px] text-slate-500 leading-relaxed">
                        Unduh berkas TypeScript untuk menggantikan <code>src/data/initialData.ts</code> pada repositori kode Anda.
                      </p>
                      <button
                        onClick={() => {
                          const fileContent = `import { MenuItem, MicrositeProfile, ClickLog, ThemeConfig } from '../types';\n\nexport const THEME_PRESETS: ThemeConfig[] = ${JSON.stringify(THEME_PRESETS, null, 2)};\n\nexport const CATEGORIES_PRESET: string[] = ${JSON.stringify(CATEGORIES_PRESET, null, 2)};\n\nexport const INITIAL_MENUS: MenuItem[] = ${JSON.stringify(menus, null, 2)};\n\nexport const INITIAL_PROFILE: MicrositeProfile = ${JSON.stringify(profile, null, 2)};\n\nexport const INITIAL_CLICK_LOGS: ClickLog[] = [];\n`;
                          const blob = new Blob([fileContent], { type: 'text/typescript' });
                          const url = URL.createObjectURL(blob);
                          const a = document.createElement('a');
                          a.href = url;
                          a.download = `initialData.ts`;
                          a.click();
                          URL.revokeObjectURL(url);
                        }}
                        className="w-full py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors flex items-center justify-center gap-1.5"
                      >
                        <Download className="w-3.5 h-3.5" />
                        <span>Unduh initialData.ts</span>
                      </button>
                    </div>

                    {/* JSON Backup & Instant Restore */}
                    <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                      <div className="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                        <FileSpreadsheet className="w-4 h-4 text-emerald-600" />
                        <span>Cadangkan & Salin Konfigurasi JSON</span>
                      </div>
                      <p className="text-[11px] text-slate-500 leading-relaxed">
                        Simpan seluruh konfigurasi ke file JSON atau salin teksnya langsung untuk disinkronkan ke perangkat lain.
                      </p>
                      <div className="flex flex-wrap gap-2">
                        <button
                          onClick={() => {
                            const dataStr = JSON.stringify({ menus, profile, adminPin }, null, 2);
                            const blob = new Blob([dataStr], { type: 'application/json' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `Backup_DirectMenu_${Date.now()}.json`;
                            a.click();
                            URL.revokeObjectURL(url);
                          }}
                          className="flex-1 min-w-[120px] py-2 px-2.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-xs transition-colors flex items-center justify-center gap-1"
                        >
                          <Download className="w-3.5 h-3.5 text-slate-600" />
                          <span>Unduh JSON</span>
                        </button>
                        <button
                          onClick={() => {
                            const dataStr = JSON.stringify({ menus, profile, adminPin }, null, 2);
                            navigator.clipboard.writeText(dataStr);
                            alert('Teks Konfigurasi JSON berhasil disalin ke clipboard! Anda bisa mengirimkannya ke chat AI atau mengimpornya di device lain.');
                          }}
                          className="flex-1 min-w-[120px] py-2 px-2.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-xs transition-colors flex items-center justify-center gap-1"
                        >
                          <Copy className="w-3.5 h-3.5 text-slate-600" />
                          <span>Salin Teks JSON</span>
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Ekspor Laporan Rekap Cepat */}
                  <div className="mt-3 p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl flex items-center justify-between flex-wrap gap-3">
                    <div className="flex items-center gap-2">
                      <BarChart3 className="w-4 h-4 text-indigo-600 shrink-0" />
                      <div>
                        <div className="text-xs font-bold text-slate-800">Ekspor Laporan & Statistik Pengunjung</div>
                        <div className="text-[11px] text-slate-500">Laporan lengkap CSV & PDF juga tersedia langsung di menu <strong>Analitik Klik</strong>.</div>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <button
                        onClick={() => exportToCSV(menus, logs, profile)}
                        className="py-1.5 px-3 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-xs transition-all flex items-center gap-1.5"
                      >
                        <FileSpreadsheet className="w-3.5 h-3.5 text-emerald-600" />
                        <span>Ekspor CSV</span>
                      </button>
                      <button
                        onClick={() => exportToPDF(menus, logs, profile)}
                        className="py-1.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-all flex items-center gap-1.5"
                      >
                        <FileText className="w-3.5 h-3.5" />
                        <span>Ekspor PDF</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* TAB: PENGATURAN NOMOR WHATSAPP ADMIN */}
            {activeTab === 'whatsapp_settings' && (
              <div className="space-y-6">
                {/* Header Info */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-4 shadow-xs">
                  <div className="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div className="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-xs">
                      <MessageCircle className="w-5 h-5" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Pengaturan Nomor WhatsApp Admin</span>
                        <span className="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold">Helpdesk OSDM</span>
                      </h2>
                      <p className="text-xs text-slate-500 mt-0.5">
                        Kelola nomor WhatsApp narahubung resmi Tim Kerja OSDM Poltekkes Kemenkes Bandung untuk konsultasi & bantuan pegawai.
                      </p>
                    </div>
                  </div>

                  {/* Informational Cards */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                    <div className="p-3.5 bg-emerald-50/60 border border-emerald-200/80 rounded-xl text-xs space-y-1">
                      <div className="font-bold text-emerald-900 flex items-center gap-1.5">
                        <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                        <span>Status Pengajuan WFA</span>
                      </div>
                      <p className="text-[11px] text-emerald-700 leading-relaxed">
                        Muncul otomatis pada kartu verifikasi WFA saat status pegawai <em>Menunggu Validasi</em> atau <em>Pengajuan Ditolak</em>.
                      </p>
                    </div>

                    <div className="p-3.5 bg-indigo-50/60 border border-indigo-200/80 rounded-xl text-xs space-y-1">
                      <div className="font-bold text-indigo-900 flex items-center gap-1.5">
                        <HelpCircle className="w-3.5 h-3.5 text-indigo-600" />
                        <span>Bimbingan & Konsultasi</span>
                      </div>
                      <p className="text-[11px] text-indigo-700 leading-relaxed">
                        Memudahkan pegawai berkonsultasi mengenai presensi e-Office Mobile, surat tugas, atau perbaikan pengajuan WFA.
                      </p>
                    </div>

                    <div className="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
                      <div className="font-bold text-slate-900 flex items-center gap-1.5">
                        <CheckCircle2 className="w-3.5 h-3.5 text-slate-600" />
                        <span>Sinkronisasi Otomatis</span>
                      </div>
                      <p className="text-[11px] text-slate-600 leading-relaxed">
                        Perubahan nomor kontak langsung disimpan ke server database cloud dan aktif secara langsung di semua portal pegawai.
                      </p>
                    </div>
                  </div>
                </div>

                {/* Form Input & Test Connection */}
                <div className="p-6 bg-white border border-slate-200 rounded-xl space-y-5 shadow-xs">
                  <div>
                    <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                      <Phone className="w-4 h-4 text-emerald-600" />
                      <span>Nomor WhatsApp Helpdesk OSDM</span>
                    </h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                      Masukkan nomor kontak aktif dengan awalan <strong>08...</strong> atau kode negara <strong>628...</strong>
                    </p>
                  </div>

                  <div className="space-y-4 max-w-xl">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                        Nomor WhatsApp Helpdesk Tim OSDM:
                      </label>
                      <div className="relative">
                        <input
                          type="text"
                          value={profile.osdmContactWa || ''}
                          placeholder="Contoh: 08119712525 atau 628119712525"
                          onChange={(e) => {
                            setProfile({ ...profile, osdmContactWa: e.target.value });
                            triggerSaveFeedback();
                          }}
                          className="w-full px-4 py-2.5 text-sm font-mono font-bold bg-slate-50/60 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 placeholder-slate-400"
                        />
                        <div className="absolute right-3 top-1/2 -translate-y-1/2">
                          <span className="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                            Auto-Saved
                          </span>
                        </div>
                      </div>
                      <p className="text-[11px] text-slate-400 mt-1.5">
                        Tautan WhatsApp otomatis dikonversi ke format internasional <code className="bg-slate-100 px-1 py-0.5 rounded text-slate-600 font-mono">https://wa.me/628...</code> saat diakses oleh pengguna.
                      </p>
                    </div>

                    {/* Action Buttons: Tes Tautan */}
                    <div className="flex items-center gap-3 pt-2">
                      <a
                        href={`https://wa.me/${(profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '').startsWith('0') ? '62' + (profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '').slice(1) : (profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '')}?text=${encodeURIComponent('Halo Tim Kerja OSDM Poltekkes Kemenkes Bandung, saya ingin berkonsultasi mengenai pengajuan WFA / layanan kepegawaian.')}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-2 shadow-xs hover:shadow"
                      >
                        <ExternalLink className="w-3.5 h-3.5" />
                        <span>Uji Coba Tautan WhatsApp (Buka Chat WA)</span>
                      </a>
                    </div>
                  </div>
                </div>

                {/* Pratinjau Tampilan Pegawai (Interactive Simulation) */}
                <div className="p-6 bg-slate-50/70 border border-slate-200 rounded-xl space-y-4 shadow-xs">
                  <div className="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-200/80">
                    <div>
                      <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <span>Simulasi Pratinjau di Layar Pegawai</span>
                      </h4>
                      <p className="text-[11px] text-slate-500 mt-0.5">
                        Contoh tombol dan dialog narahubung yang dilihat pegawai ketika mereka memerlukan bantuan Tim OSDM:
                      </p>
                    </div>
                    <span className="text-[10px] font-mono font-semibold bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full">
                      wa.me/{(profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '').startsWith('0') ? '62' + (profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '').slice(1) : (profile.osdmContactWa || '08119712525').replace(/[^0-9]/g, '')}
                    </span>
                  </div>

                  <div className="p-4 bg-white border border-slate-200/80 rounded-xl max-w-md shadow-xs space-y-3">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white shadow-sm shrink-0">
                        <MessageCircle className="w-5 h-5" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                          <span>Helpdesk Tim Kerja OSDM</span>
                          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                        </div>
                        <div className="text-[11px] text-slate-500 font-mono">
                          {profile.osdmContactWa || '08119712525'}
                        </div>
                      </div>
                    </div>

                    <div className="p-2.5 bg-slate-50 rounded-lg text-[11px] text-slate-600 border border-slate-100 flex items-start gap-2">
                      <Send className="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" />
                      <span>&quot;Halo Tim Kerja OSDM Poltekkes Kemenkes Bandung, saya ingin konfirmasi terkait pengajuan WFA saya...&quot;</span>
                    </div>

                    <div className="pt-1">
                      <div className="w-full py-2.5 px-3 bg-emerald-600 text-white rounded-lg text-xs font-bold flex items-center justify-center gap-2 shadow-xs cursor-default">
                        <MessageCircle className="w-4 h-4" />
                        <span>Hubungi Tim Kerja OSDM via WhatsApp</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Right Column: Sleek Live Mobile Phone Mockup */}
          {isSidePreviewVisible && (
            <div className="xl:col-span-4 sticky top-20 hidden xl:block">
              <div className="bg-slate-200/70 rounded-3xl p-6 border border-slate-300/80 relative overflow-hidden flex flex-col items-center">
                {/* Subtle dot matrix grid background from sleek design */}
                <div className="absolute inset-0 bg-dot-pattern-light opacity-35 pointer-events-none" />

                {/* Floating Top Header */}
                <div className="w-full flex items-center justify-between mb-4 z-10">
                  <div className="bg-white/90 backdrop-blur px-3 py-1 rounded-full border border-slate-200 text-[10px] font-bold text-slate-600 shadow-xs uppercase tracking-tight">
                    Live Mobile Preview
                  </div>
                  <button
                    onClick={onOpenPublicPreview}
                    className="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 bg-white/80 px-2 py-0.5 rounded-md"
                  >
                    Buka Penuh <ExternalLink className="w-3 h-3" />
                  </button>
                </div>

                {/* Smartphone Device Frame Mockup with Sleek Bezel */}
                <div className="relative w-[300px] h-[580px] bg-white rounded-[40px] border-[8px] border-slate-900 shadow-2xl flex flex-col overflow-hidden z-10">
                  {/* Dynamic Island / Notch */}
                  <div className="h-6 w-32 bg-slate-900 absolute top-0 left-1/2 -translate-x-1/2 rounded-b-xl z-20" />

                  {/* Screen Content */}
                  <div className="flex-1 w-full overflow-y-auto no-scrollbar scrollbar-none pt-4">
                    <PublicMicrosite
                      profile={profile}
                      menus={menus}
                      onMenuClick={(m) => {
                        // In live simulator preview, record simulation click
                        onSimulateClick();
                      }}
                      onOpenQR={onOpenQR}
                      isStandalone={false}
                      wfaSubmissions={wfaSubmissions}
                      kebugaranSubmissions={kebugaranSubmissions}
                    />
                  </div>

                  {/* Home Indicator Bar */}
                  <div className="w-24 h-1 bg-slate-400/40 rounded-full mx-auto my-1.5 shrink-0" />
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Save feedback toast */}
      <AnimatePresence>
        {saveToast && (
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 30 }}
            className="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-lg shadow-xl font-medium text-xs border border-slate-800"
          >
            <Check className="w-4 h-4 text-emerald-400" />
            <span>Perubahan tersimpan otomatis!</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Menu Editor Modal */}
      <AdminMenuEditorModal
        isOpen={isEditorOpen}
        onClose={() => setIsEditorOpen(false)}
        onSave={handleSaveMenu}
        initialMenu={editingMenu}
        currentTheme={profile.theme}
        availableCategories={availableCategories}
      />

      {/* Backup & Import JSON Modal */}
      <AdminExportImportModal
        isOpen={isExportImportOpen}
        onClose={() => setIsExportImportOpen(false)}
        menus={menus}
        profile={profile}
        adminPin={adminPin}
        onImport={handleImportConfig}
      />

      {/* Pegawai Form Create Modal */}
      <PegawaiFormCreateModal
        isOpen={isCreatePegawaiFormOpen}
        onClose={() => setIsCreatePegawaiFormOpen(false)}
        onCreate={async (newForm) => {
          const success = await onCreatePegawaiForm(newForm);
          if (success) {
            onSelectPegawaiForm(newForm.id);
          }
          return success;
        }}
      />
    </div>
  );
};

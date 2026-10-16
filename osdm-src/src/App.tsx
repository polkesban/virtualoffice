import React, { useState, useEffect, useRef, useMemo } from 'react';
import { Navbar, AdminTabKey } from './components/Navbar';
import { PublicMicrosite } from './components/PublicMicrosite';
import { AdminDashboard } from './components/AdminDashboard';
import { QRCodeModal } from './components/QRCodeModal';
import { AdminAuthModal, AdminRole } from './components/AdminAuthModal';
import { MenuItem, MicrositeProfile, ClickLog, ClickStatsSummary, WfaSubmission, WfaValidationStatus, KebugaranSubmission, MeetingSession, MeetingAttendanceRecord, PegawaiForm, PegawaiFormResponse } from './types';
import { INITIAL_MENUS, INITIAL_PROFILE, INITIAL_CLICK_LOGS, ensureHasWfaMenu } from './data/initialData';
import { INITIAL_WFA_SUBMISSIONS } from './data/employeeDatabase';
import { INITIAL_KEBUGARAN_SUBMISSIONS } from './data/kebugaranInitialData';
import { INITIAL_MEETING_SESSION, INITIAL_MEETING_ATTENDANCE } from './data/meetingInitialData';
import { INITIAL_PEGAWAI_FORMS, INITIAL_PEGAWAI_FORM_RESPONSES } from './data/pegawaiFormInitialData';
import { KebugaranModal } from './components/KebugaranModal';
import { MeetingAttendanceModal } from './components/MeetingAttendanceModal';
import { PegawaiFormModal } from './components/PegawaiFormModal';
import { motion, AnimatePresence } from 'motion/react';
import { CheckCheck, Sparkles, Send, Cloud, CloudCheck, Wifi } from 'lucide-react';
import { 
  subscribeToLivePortal, 
  publishLivePortalToCloud, 
  logClickToCloud,
  fetchClickLogs,
  deleteClickLogsFromCloud,
  subscribeToAdminSecurity,
  saveAdminPinToCloud,
  saveWfaAdminPinToCloud,
  saveKebugaranAdminPinToCloud,
  saveMeetingAdminPinToCloud,
  saveMonitoringAllAdminPinToCloud,
  subscribeToAdminDraft,
  saveAdminDraftToCloud,
  subscribeToClickLogs,
  subscribeToWfaSubmissions,
  createWfaSubmissionInCloud,
  updateWfaStatusInCloud,
  updateWfaLinkSuratTugasInCloud,
  deleteWfaSubmissionInCloud,
  subscribeToKebugaranSubmissions,
  createKebugaranSubmissionInCloud,
  deleteKebugaranSubmissionInCloud,
  subscribeToMeetingSession,
  subscribeToMeetingSessions,
  saveMeetingSessionToCloud,
  saveAllMeetingSessionsToCloud,
  deleteMeetingSessionFromCloud,
  subscribeToMeetingAttendees,
  createMeetingAttendeeInCloud,
  deleteMeetingAttendeeInCloud,
  subscribeToMeetingAgendas,
  saveMeetingAgendaToCloud,
  deleteMeetingAgendaFromCloud,
  subscribeToPegawaiForms,
  savePegawaiFormToCloud,
  deletePegawaiFormFromCloud,
  subscribeToPegawaiFormResponses,
  savePegawaiFormResponseToCloud,
  deletePegawaiFormResponseFromCloud,
} from './lib/api';
import { saveAgendaPermanently, syncSavedAgendasWithCloud, removeSavedAgenda, getDeletedAgendas, LEGACY_MOCK_AGENDAS } from './utils/meetingAgendaHelper';

const LOCAL_STORAGE_MENUS_KEY = 'direct_menu_items_v2';
const LOCAL_STORAGE_PROFILE_KEY = 'direct_menu_profile_v2';
const LOCAL_STORAGE_LOGS_KEY = 'direct_menu_logs_v2';
const LOCAL_STORAGE_ADMIN_PIN_KEY = 'direct_menu_admin_pin_v2';
const LOCAL_STORAGE_WFA_ADMIN_PIN_KEY = 'direct_menu_wfa_admin_pin_v1';
const LOCAL_STORAGE_KEBUGARAN_ADMIN_PIN_KEY = 'direct_menu_kebugaran_admin_pin_v1';
const LOCAL_STORAGE_MEETING_ADMIN_PIN_KEY = 'direct_menu_meeting_admin_pin_v1';
const LOCAL_STORAGE_MONITORING_ALL_ADMIN_PIN_KEY = 'direct_menu_monitoring_all_admin_pin_v1';
const SESSION_ADMIN_AUTH_KEY = 'direct_menu_admin_auth_v2';
const SESSION_ADMIN_ROLE_KEY = 'direct_menu_admin_role_v1';
const LOCAL_STORAGE_WFA_SUBMISSIONS_KEY = 'direct_menu_wfa_submissions_v1';
const LOCAL_STORAGE_KEBUGARAN_SUBMISSIONS_KEY = 'direct_menu_kebugaran_submissions_v1';
const LOCAL_STORAGE_MEETING_SESSIONS_KEY = 'direct_menu_meeting_sessions_v1';
const LOCAL_STORAGE_MEETING_SESSION_KEY = 'direct_menu_meeting_session_v1';
const LOCAL_STORAGE_MEETING_ATTENDANCE_KEY = 'direct_menu_meeting_attendance_v1';

// Live published storage keys (what employees see on public page)
const LOCAL_STORAGE_LIVE_MENUS_KEY = 'direct_menu_live_items_v2';
const LOCAL_STORAGE_LIVE_PROFILE_KEY = 'direct_menu_live_profile_v2';
const LOCAL_STORAGE_LAST_PUBLISHED_KEY = 'direct_menu_last_published_v2';

export default function App() {
  const [isCloudSynced, setIsCloudSynced] = useState(false);
  const isInitialDraftLoadedFromCloudRef = useRef(false);

  // Load initial draft states (Admin working copy)
  const [menus, setMenus] = useState<MenuItem[]>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_MENUS_KEY);
      if (saved) return ensureHasWfaMenu(JSON.parse(saved));
    } catch {
      // ignore
    }
    return ensureHasWfaMenu(INITIAL_MENUS);
  });

  const [profile, setProfile] = useState<MicrositeProfile>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_PROFILE_KEY);
      if (saved) return JSON.parse(saved);
    } catch {
      // ignore
    }
    return INITIAL_PROFILE;
  });

  // Live published state (What regular employees see)
  const [liveMenus, setLiveMenus] = useState<MenuItem[]>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_LIVE_MENUS_KEY);
      if (saved) return ensureHasWfaMenu(JSON.parse(saved));
      const draftSaved = localStorage.getItem(LOCAL_STORAGE_MENUS_KEY);
      if (draftSaved) return ensureHasWfaMenu(JSON.parse(draftSaved));
    } catch {
      // ignore
    }
    return ensureHasWfaMenu(INITIAL_MENUS);
  });

  const [liveProfile, setLiveProfile] = useState<MicrositeProfile>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_LIVE_PROFILE_KEY);
      if (saved) return JSON.parse(saved);
      const draftSaved = localStorage.getItem(LOCAL_STORAGE_PROFILE_KEY);
      if (draftSaved) return JSON.parse(draftSaved);
    } catch {
      // ignore
    }
    return INITIAL_PROFILE;
  });

  const [lastPublishedAt, setLastPublishedAt] = useState<string | null>(() => {
    try {
      return localStorage.getItem(LOCAL_STORAGE_LAST_PUBLISHED_KEY);
    } catch {
      return null;
    }
  });

  const [isPublishing, setIsPublishing] = useState(false);
  const [publishSuccessToast, setPublishSuccessToast] = useState(false);
  const [showPublishSuccessModal, setShowPublishSuccessModal] = useState(false);

  const [publishStatus, setPublishStatus] = useState<{
    success: boolean;
    message: string;
    cloudSynced: boolean;
  } | null>(null);

  const [logs, setLogs] = useState<ClickLog[]>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_LOGS_KEY);
      if (saved) return JSON.parse(saved);
    } catch {
      // ignore
    }
    return INITIAL_CLICK_LOGS;
  });

  const [clickStats, setClickStats] = useState<ClickStatsSummary | null>(null);

  const [adminRole, setAdminRole] = useState<AdminRole>(() => {
    try {
      const savedRole = sessionStorage.getItem(SESSION_ADMIN_ROLE_KEY);
      if (savedRole && ['superadmin', 'wfa_admin', 'kebugaran_admin', 'meeting_admin', 'monitoring_all_admin'].includes(savedRole)) {
        return savedRole as AdminRole;
      }
    } catch {
      // ignore
    }
    return 'superadmin';
  });

  const [adminPin, setAdminPin] = useState<string>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_ADMIN_PIN_KEY);
      if (saved) return saved;
    } catch {
      // ignore
    }
    return 'admin123';
  });

  const [wfaAdminPin, setWfaAdminPin] = useState<string>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_WFA_ADMIN_PIN_KEY);
      if (saved) return saved;
    } catch {
      // ignore
    }
    return 'wfa123';
  });

  const [kebugaranAdminPin, setKebugaranAdminPin] = useState<string>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_KEBUGARAN_ADMIN_PIN_KEY);
      if (saved) return saved;
    } catch {
      // ignore
    }
    return 'fit123';
  });

  const [meetingAdminPin, setMeetingAdminPin] = useState<string>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_MEETING_ADMIN_PIN_KEY);
      if (saved) return saved;
    } catch {
      // ignore
    }
    return 'rapat123';
  });

  const [monitoringAllAdminPin, setMonitoringAllAdminPin] = useState<string>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_MONITORING_ALL_ADMIN_PIN_KEY);
      if (saved) return saved;
    } catch {
      // ignore
    }
    return 'pantau123';
  });

  // WFA Bimbingan Submissions state
  const [wfaSubmissions, setWfaSubmissions] = useState<WfaSubmission[]>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_WFA_SUBMISSIONS_KEY);
      if (saved) return JSON.parse(saved);
    } catch {
      // ignore
    }
    return INITIAL_WFA_SUBMISSIONS;
  });

  // Kebugaran Jasmani Submissions state
  const [kebugaranSubmissions, setKebugaranSubmissions] = useState<KebugaranSubmission[]>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_KEBUGARAN_SUBMISSIONS_KEY);
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) {
          const existingIds = new Set(parsed.map((item: KebugaranSubmission) => item.id));
          const missing = INITIAL_KEBUGARAN_SUBMISSIONS.filter((item) => !existingIds.has(item.id));
          if (missing.length > 0) {
            return [...parsed, ...missing];
          }
          return parsed;
        }
      }
    } catch {
      // ignore
    }
    return INITIAL_KEBUGARAN_SUBMISSIONS;
  });

  const [isAdminKebugaranModalOpen, setIsAdminKebugaranModalOpen] = useState(false);

  // Meeting Sessions & Attendance state (Multi-Session support)
  const [meetingSessions, setMeetingSessions] = useState<MeetingSession[]>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_MEETING_SESSIONS_KEY);
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) return parsed;
      }
      const single = localStorage.getItem(LOCAL_STORAGE_MEETING_SESSION_KEY);
      if (single) {
        const parsedSingle = JSON.parse(single);
        if (parsedSingle && parsedSingle.id) return [parsedSingle];
      }
    } catch {}
    return [INITIAL_MEETING_SESSION];
  });

  const [activeMeetingId, setActiveMeetingId] = useState<string>(() => {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const paramId = urlParams.get('meetId') || urlParams.get('meetingId') || urlParams.get('meet');
      if (paramId) return paramId;
    } catch {}
    return INITIAL_MEETING_SESSION.id;
  });

  const activeMeetingSession = useMemo(() => {
    if (meetingSessions.length === 0) return INITIAL_MEETING_SESSION;
    const found = meetingSessions.find((s) => s.id === activeMeetingId);
    return found || meetingSessions[0];
  }, [meetingSessions, activeMeetingId]);

  // Backward compatibility alias for single meetingSession
  const meetingSession = activeMeetingSession;
  const setMeetingSession = (sessionOrUpdater: MeetingSession | ((prev: MeetingSession) => MeetingSession)) => {
    setMeetingSessions((prev) => {
      const current = prev.find((s) => s.id === activeMeetingId) || prev[0] || INITIAL_MEETING_SESSION;
      const nextSession = typeof sessionOrUpdater === 'function' ? sessionOrUpdater(current) : sessionOrUpdater;
      const exists = prev.some((s) => s.id === nextSession.id);
      if (exists) {
        return prev.map((s) => (s.id === nextSession.id ? nextSession : s));
      }
      return [nextSession, ...prev];
    });
  };

  const [meetingAttendees, setMeetingAttendees] = useState<MeetingAttendanceRecord[]>(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_MEETING_ATTENDANCE_KEY);
      if (saved) {
        const parsed: MeetingAttendanceRecord[] = JSON.parse(saved);
        const deletedAgendas = getDeletedAgendas();
        const MOCK_ATT_IDS = new Set(['att-001', 'att-002', 'att-003', 'att-004', 'att-1789655539746-6wkt']);
        if (Array.isArray(parsed)) {
          return parsed.filter((att) => {
            if (MOCK_ATT_IDS.has(att.id)) return false;
            if (att.meetingId === 'meet-session-current') return false;
            if (att.agendas && att.agendas.length > 0) {
              const hasLegacy = att.agendas.some((ag) => LEGACY_MOCK_AGENDAS.has(ag.trim().toLowerCase()));
              if (hasLegacy) return false;
              return att.agendas.some((ag) => !deletedAgendas.has(ag.trim().toLowerCase()));
            }
            if ((att as any).agenda) {
              const clean = (att as any).agenda.trim().toLowerCase();
              if (LEGACY_MOCK_AGENDAS.has(clean)) return false;
              return !deletedAgendas.has(clean);
            }
            return true;
          });
        }
        return [];
      }
    } catch {}
    return [];
  });

  const [isMeetingFormModalOpen, setIsMeetingFormModalOpen] = useState(false);

  const [pegawaiForms, setPegawaiForms] = useState<PegawaiForm[]>(() => {
    try {
      const saved = localStorage.getItem('osdm_pegawai_forms');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) {
          return parsed.map((f: PegawaiForm) => ({
            ...f,
            sections: f.sections?.map((sec) => ({
              ...sec,
              questions: sec.questions?.map((q) =>
                q.id === 'status_pelatihan' && q.type === 'radio'
                  ? { ...q, type: 'dropdown', placeholder: q.placeholder || '-- Pilih Status Pelatihan Anda --' }
                  : q
              ),
            })),
          }));
        }
      }
    } catch {}
    return INITIAL_PEGAWAI_FORMS;
  });

  const [selectedPegawaiFormId, setSelectedPegawaiFormId] = useState<string>(() => {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const formParam = urlParams.get('form');
      if (formParam && formParam !== 'true' && formParam !== 'kebugaran' && formParam !== 'wfa' && formParam !== 'rapat') {
        return formParam;
      }
    } catch {}
    return INITIAL_PEGAWAI_FORMS[0]?.id || 'form-mooc-asn-berintegritas';
  });

  const [pegawaiResponses, setPegawaiResponses] = useState<PegawaiFormResponse[]>(() => {
    try {
      const saved = localStorage.getItem('osdm_pegawai_form_responses');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed)) return parsed;
      }
    } catch {}
    return INITIAL_PEGAWAI_FORM_RESPONSES;
  });

  const [isPegawaiFormModalOpen, setIsPegawaiFormModalOpen] = useState(false);
  const [activePegawaiFormId, setActivePegawaiFormId] = useState<string>(() => {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const formParam = urlParams.get('form');
      if (formParam && formParam !== 'true' && formParam !== 'kebugaran' && formParam !== 'wfa' && formParam !== 'rapat') {
        return formParam;
      }
    } catch {}
    return 'form-mooc-asn-berintegritas';
  });

  // Dynamically resolve active form from the current pegawaiForms list
  // Ensures any saved custom banner / questions are immediately shown
  const activeFormForModal = useMemo<PegawaiForm>(() => {
    const match = pegawaiForms.find(
      (f) => f.id === activePegawaiFormId || f.id.includes(activePegawaiFormId)
    );
    if (match) return match;
    if (selectedPegawaiFormId) {
      const matchSelected = pegawaiForms.find(
        (f) => f.id === selectedPegawaiFormId || f.id.includes(selectedPegawaiFormId)
      );
      if (matchSelected) return matchSelected;
    }
    if (pegawaiForms.length > 0) return pegawaiForms[0];
    return INITIAL_PEGAWAI_FORMS[0];
  }, [pegawaiForms, activePegawaiFormId, selectedPegawaiFormId]);

  const handleOpenPegawaiFormModal = (formId?: string) => {
    const targetId = formId || selectedPegawaiFormId || (pegawaiForms[0] ? pegawaiForms[0].id : 'form-mooc-asn-berintegritas');
    setActivePegawaiFormId(targetId);
    setIsPegawaiFormModalOpen(true);
  };

  // 1. Real-time Cloud Sync for Live Portal across all devices
  useEffect(() => {
    const unsubscribe = subscribeToLivePortal(
      (cloudData) => {
        if (cloudData && Array.isArray(cloudData.menus) && cloudData.profile) {
          const syncedMenus = ensureHasWfaMenu(cloudData.menus);
          setLiveMenus(syncedMenus);
          setLiveProfile(cloudData.profile);
          if (cloudData.lastPublishedAt) {
            setLastPublishedAt(cloudData.lastPublishedAt);
          }
          setIsCloudSynced(true);

          // Only seed draft from cloud if the user has NO local draft saved yet
          const hasLocalDraft = !!localStorage.getItem(LOCAL_STORAGE_MENUS_KEY);
          if (!isInitialDraftLoadedFromCloudRef.current && !hasLocalDraft) {
            setMenus(syncedMenus);
            setProfile(cloudData.profile);
            isInitialDraftLoadedFromCloudRef.current = true;
          }
        }
      },
      (err) => {
        console.warn('Live portal subscription status:', err);
      },
      async () => {
        // Document doesn't exist yet on Database Server!
        // Only seed if logged in as Admin to prevent public viewers from executing seed.
        if (sessionStorage.getItem(SESSION_ADMIN_AUTH_KEY) === 'true') {
          try {
            console.log('Seeding initial portal live data to Database Server...');
            await publishLivePortalToCloud(menus, profile);
            setIsCloudSynced(true);
          } catch (e) {
            console.warn('Database auto-seed error:', e);
          }
        }
      }
    );

    // Multi-tab instant synchronization in the same browser (0ms latency)
    let channel: BroadcastChannel | null = null;
    if (typeof BroadcastChannel !== 'undefined') {
      try {
        channel = new BroadcastChannel('direct_menu_live_sync');
        channel.onmessage = (event) => {
          if (event.data?.type === 'PORTAL_LIVE_UPDATE') {
            const { menus: pubMenus, profile: pubProfile, timestamp } = event.data;
            if (pubMenus && Array.isArray(pubMenus)) {
              setLiveMenus(ensureHasWfaMenu(pubMenus));
            }
            if (pubProfile) {
              setLiveProfile(pubProfile);
            }
            if (timestamp) {
              setLastPublishedAt(timestamp);
            }
            setIsCloudSynced(true);
          }
        };
      } catch (e) {
        console.warn('BroadcastChannel error:', e);
      }
    }

    // Storage event listener fallback for multi-tab sync
    const handleStorageChange = (e: StorageEvent) => {
      if (e.key === LOCAL_STORAGE_LIVE_MENUS_KEY && e.newValue) {
        try {
          setLiveMenus(ensureHasWfaMenu(JSON.parse(e.newValue)));
        } catch {}
      }
      if (e.key === LOCAL_STORAGE_LIVE_PROFILE_KEY && e.newValue) {
        try {
          setLiveProfile(JSON.parse(e.newValue));
        } catch {}
      }
      if (e.key === LOCAL_STORAGE_LAST_PUBLISHED_KEY && e.newValue) {
        setLastPublishedAt(e.newValue);
      }
      if (e.key === LOCAL_STORAGE_WFA_SUBMISSIONS_KEY && e.newValue) {
        try {
          setWfaSubmissions(JSON.parse(e.newValue));
        } catch {}
      }
    };
    window.addEventListener('storage', handleStorageChange);

    return () => {
      unsubscribe();
      if (channel) {
        channel.close();
      }
      window.removeEventListener('storage', handleStorageChange);
    };
  }, []);

  // 2. Real-time Cloud Sync for Admin PIN & WFA PIN across all devices/browsers
  useEffect(() => {
    const unsubscribe = subscribeToAdminSecurity((data) => {
      if (data?.pin && typeof data.pin === 'string') {
        setAdminPin(data.pin);
        try {
          localStorage.setItem(LOCAL_STORAGE_ADMIN_PIN_KEY, data.pin);
        } catch {
          // ignore
        }
      }
      if (data?.wfaPin && typeof data.wfaPin === 'string') {
        setWfaAdminPin(data.wfaPin);
        try {
          localStorage.setItem(LOCAL_STORAGE_WFA_ADMIN_PIN_KEY, data.wfaPin);
        } catch {
          // ignore
        }
      }
      if (data?.kebugaranPin && typeof data.kebugaranPin === 'string') {
        setKebugaranAdminPin(data.kebugaranPin);
        try {
          localStorage.setItem(LOCAL_STORAGE_KEBUGARAN_ADMIN_PIN_KEY, data.kebugaranPin);
        } catch {
          // ignore
        }
      }
      if (data?.meetingPin && typeof data.meetingPin === 'string') {
        setMeetingAdminPin(data.meetingPin);
        try {
          localStorage.setItem(LOCAL_STORAGE_MEETING_ADMIN_PIN_KEY, data.meetingPin);
        } catch {
          // ignore
        }
      }
      if (data?.monitoringAllPin && typeof data.monitoringAllPin === 'string') {
        setMonitoringAllAdminPin(data.monitoringAllPin);
        try {
          localStorage.setItem(LOCAL_STORAGE_MONITORING_ALL_ADMIN_PIN_KEY, data.monitoringAllPin);
        } catch {
          // ignore
        }
      }
    });

    return () => {
      unsubscribe();
    };
  }, []);

  // 3. Real-time Cloud Sync for Admin Draft (work-in-progress)
  useEffect(() => {
    const unsubscribe = subscribeToAdminDraft((draftData) => {
      if (draftData && Array.isArray(draftData.menus) && draftData.profile) {
        if (!isInitialDraftLoadedFromCloudRef.current) {
          const syncedDraftMenus = ensureHasWfaMenu(draftData.menus);
          setMenus(syncedDraftMenus);
          setProfile(draftData.profile);
          isInitialDraftLoadedFromCloudRef.current = true;
        }
      }
    });

    return () => {
      unsubscribe();
    };
  }, []);

  // 4. Real-time Cloud Sync for Click Logs / Analytics
  useEffect(() => {
    const unsubscribe = subscribeToClickLogs((cloudLogs, cloudStats) => {
      if (Array.isArray(cloudLogs) && cloudLogs.length > 0) {
        setLogs(cloudLogs);
      }
      if (cloudStats) {
        setClickStats(cloudStats);
      }
    });

    return () => {
      unsubscribe();
    };
  }, []);

  const [isAdminAuthenticated, setIsAdminAuthenticated] = useState<boolean>(() => {
    try {
      return sessionStorage.getItem(SESSION_ADMIN_AUTH_KEY) === 'true';
    } catch {
      return false;
    }
  });

  const [currentView, setCurrentView] = useState<'public' | 'admin' | 'split'>(() => {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('mode') === 'public') {
        return 'public';
      }
      const isAuth = sessionStorage.getItem(SESSION_ADMIN_AUTH_KEY) === 'true';
      if (isAuth) return 'admin';
    } catch {
      // ignore
    }
    return 'public';
  });

  const [adminActiveTab, setAdminActiveTab] = useState<AdminTabKey>('menus');
  const [isQRModalOpen, setIsQRModalOpen] = useState(false);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);

  // Sync draft states to LocalStorage & Database Server
  // Keep menus and profile saved to LocalStorage for instant persistence
  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_MENUS_KEY, JSON.stringify(menus));
    } catch {
      // ignore storage overflow
    }
  }, [menus]);

  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_PROFILE_KEY, JSON.stringify(profile));
    } catch {
      // ignore
    }
  }, [profile]);

  // Sync published states to LocalStorage
  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_LIVE_MENUS_KEY, JSON.stringify(liveMenus));
    } catch {
      // ignore
    }
  }, [liveMenus]);

  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_LIVE_PROFILE_KEY, JSON.stringify(liveProfile));
    } catch {
      // ignore
    }
  }, [liveProfile]);

  useEffect(() => {
    try {
      if (lastPublishedAt) {
        localStorage.setItem(LOCAL_STORAGE_LAST_PUBLISHED_KEY, lastPublishedAt);
      }
    } catch {
      // ignore
    }
  }, [lastPublishedAt]);

  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_LOGS_KEY, JSON.stringify(logs));
    } catch {
      // ignore
    }
  }, [logs]);

  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_ADMIN_PIN_KEY, adminPin);
    } catch {
      // ignore
    }
  }, [adminPin]);

  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_MEETING_SESSIONS_KEY, JSON.stringify(meetingSessions));
      localStorage.setItem(LOCAL_STORAGE_MEETING_SESSION_KEY, JSON.stringify(activeMeetingSession));
    } catch {}
  }, [meetingSessions, activeMeetingSession]);

  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_MEETING_ATTENDANCE_KEY, JSON.stringify(meetingAttendees));
    } catch {}
  }, [meetingAttendees]);

  // Real-time Cloud Sync for Meeting Sessions, Attendees & Agendas across all devices
  useEffect(() => {
    let isMounted = true;
    const unsubSessions = subscribeToMeetingSessions((cloudSessions) => {
      if (Array.isArray(cloudSessions) && cloudSessions.length > 0 && isMounted) {
        setMeetingSessions((prev) => {
          if (JSON.stringify(prev) === JSON.stringify(cloudSessions)) {
            return prev;
          }
          return cloudSessions;
        });
      }
    });

    const unsubAttendees = subscribeToMeetingAttendees((cloudAttendees) => {
      if (Array.isArray(cloudAttendees) && isMounted) {
        const deletedAgendas = getDeletedAgendas();
        const MOCK_ATT_IDS = new Set(['att-001', 'att-002', 'att-003', 'att-004', 'att-1789655539746-6wkt']);
        const validAttendees = cloudAttendees.filter((att) => {
          if (MOCK_ATT_IDS.has(att.id)) return false;
          if (att.meetingId === 'meet-session-current') return false;
          if (att.agendas && att.agendas.length > 0) {
            const hasLegacy = att.agendas.some((ag) => LEGACY_MOCK_AGENDAS.has(ag.trim().toLowerCase()));
            if (hasLegacy) return false;
            return !deletedAgendas.size || att.agendas.some((ag) => !deletedAgendas.has(ag.trim().toLowerCase()));
          }
          if ((att as any).agenda) {
            const clean = (att as any).agenda.trim().toLowerCase();
            if (LEGACY_MOCK_AGENDAS.has(clean)) return false;
            return !deletedAgendas.has(clean);
          }
          return true;
        });

        setMeetingAttendees((prev) => {
          if (
            prev &&
            prev.length === validAttendees.length &&
            JSON.stringify(prev) === JSON.stringify(validAttendees)
          ) {
            return prev;
          }
          return validAttendees;
        });
      }
    });

    const unsubAgendas = subscribeToMeetingAgendas((cloudAgendas) => {
      if (Array.isArray(cloudAgendas) && isMounted) {
        syncSavedAgendasWithCloud(cloudAgendas);
      }
    });

    return () => {
      isMounted = false;
      unsubSessions();
      unsubAttendees();
      unsubAgendas();
    };
  }, []);

  // Save Pegawai Forms & Responses to localStorage
  useEffect(() => {
    try {
      localStorage.setItem('osdm_pegawai_forms', JSON.stringify(pegawaiForms));
    } catch {}
  }, [pegawaiForms]);

  useEffect(() => {
    try {
      if (pegawaiResponses && pegawaiResponses.length > 0) {
        localStorage.setItem('osdm_pegawai_form_responses', JSON.stringify(pegawaiResponses));
      }
    } catch {}
  }, [pegawaiResponses]);

  // Real-time Cloud Sync for Pegawai Custom Forms & Responses across all devices
  useEffect(() => {
    let isMounted = true;
    const unsubForms = subscribeToPegawaiForms((cloudForms) => {
      if (Array.isArray(cloudForms) && cloudForms.length > 0 && isMounted) {
        setPegawaiForms((prev) => {
          if (JSON.stringify(prev) === JSON.stringify(cloudForms)) return prev;
          return cloudForms;
        });
      }
    });

    const unsubResponses = subscribeToPegawaiFormResponses((cloudResponses) => {
      if (Array.isArray(cloudResponses) && isMounted) {
        setPegawaiResponses((prev) => {
          // Guard: Do not let unexpected empty array from transient glitch wipe existing loaded responses
          if (cloudResponses.length === 0 && prev.length > 0) {
            return prev;
          }
          if (JSON.stringify(prev) === JSON.stringify(cloudResponses)) return prev;
          return cloudResponses;
        });
      }
    });

    return () => {
      isMounted = false;
      unsubForms();
      unsubResponses();
    };
  }, []);

  // Real-time Cloud Sync for WFA Submissions
  useEffect(() => {
    const unsubscribe = subscribeToWfaSubmissions((cloudSubmissions) => {
      if (Array.isArray(cloudSubmissions)) {
        setWfaSubmissions(cloudSubmissions);
      }
    });
    return () => unsubscribe();
  }, []);

  // Multi-tab sync channel for WFA operations
  useEffect(() => {
    let wfaChannel: BroadcastChannel | null = null;
    if (typeof BroadcastChannel !== 'undefined') {
      try {
        wfaChannel = new BroadcastChannel('wfa_sync_channel');
        wfaChannel.onmessage = (event) => {
          if (event.data?.type === 'WFA_DELETE' && event.data.id) {
            setWfaSubmissions((prev) => prev.filter((s) => s.id !== event.data.id));
          } else if (event.data?.type === 'WFA_STATUS_UPDATE' && event.data.id) {
            setWfaSubmissions((prev) =>
              prev.map((sub) =>
                sub.id === event.data.id
                  ? {
                      ...sub,
                      status: event.data.status,
                      catatanPengelola: event.data.notes !== undefined ? event.data.notes : sub.catatanPengelola,
                      validatedAt: new Date().toISOString(),
                      validatedBy: 'Tim OSDM Poltekkes',
                    }
                  : sub
              )
            );
          } else if (event.data?.type === 'WFA_LINK_UPDATE' && event.data.id) {
            setWfaSubmissions((prev) =>
              prev.map((sub) =>
                sub.id === event.data.id
                  ? {
                      ...sub,
                      linkSuratTugas: event.data.linkSuratTugas,
                    }
                  : sub
              )
            );
          } else if (event.data?.type === 'WFA_NEW' && event.data.submission) {
            setWfaSubmissions((prev) => [
              event.data.submission,
              ...prev.filter((s) => s.id !== event.data.submission.id),
            ]);
          }
        };
      } catch (err) {
        console.warn('WFA BroadcastChannel setup error:', err);
      }
    }

    return () => {
      if (wfaChannel) {
        wfaChannel.close();
      }
    };
  }, []);

  // Sync WFA Submissions to localStorage
  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_WFA_SUBMISSIONS_KEY, JSON.stringify(wfaSubmissions));
    } catch {
      // ignore
    }
  }, [wfaSubmissions]);

  // Real-time Cloud Sync for Kebugaran Submissions
  useEffect(() => {
    const unsubscribe = subscribeToKebugaranSubmissions((cloudSubmissions) => {
      if (Array.isArray(cloudSubmissions)) {
        setKebugaranSubmissions(cloudSubmissions);
      }
    });
    return () => unsubscribe();
  }, []);

  // Multi-tab sync channel for Kebugaran operations
  useEffect(() => {
    let kebugaranChannel: BroadcastChannel | null = null;
    if (typeof BroadcastChannel !== 'undefined') {
      try {
        kebugaranChannel = new BroadcastChannel('kebugaran_sync_channel');
        kebugaranChannel.onmessage = (event) => {
          if (event.data?.type === 'KEBUGARAN_DELETE' && event.data.id) {
            setKebugaranSubmissions((prev) => prev.filter((s) => s.id !== event.data.id));
          } else if (event.data?.type === 'KEBUGARAN_NEW' && event.data.submission) {
            setKebugaranSubmissions((prev) => [
              event.data.submission,
              ...prev.filter((s) => s.id !== event.data.submission.id),
            ]);
          }
        };
      } catch (err) {
        console.warn('Kebugaran BroadcastChannel setup error:', err);
      }
    }

    return () => {
      if (kebugaranChannel) {
        kebugaranChannel.close();
      }
    };
  }, []);

  // Sync Kebugaran Submissions to localStorage
  useEffect(() => {
    try {
      localStorage.setItem(LOCAL_STORAGE_KEBUGARAN_SUBMISSIONS_KEY, JSON.stringify(kebugaranSubmissions));
    } catch {
      // ignore
    }
  }, [kebugaranSubmissions]);

  // Ensure WFA & Kebugaran menus exist in menus & liveMenus
  useEffect(() => {
    setMenus((prev) => ensureHasWfaMenu(prev));
    setLiveMenus((prev) => ensureHasWfaMenu(prev));
  }, []);

  const handleCreateWfaSubmission = async (data: Omit<WfaSubmission, 'id' | 'status' | 'createdAt'>) => {
    // Duplicate rejection: Pegawai tidak boleh mengajukan WFA pada tanggal yang sama dua kali
    const cleanNip = data.nip.replace(/[\s.-]/g, '').trim().toLowerCase();
    const cleanDate = data.tanggalWfa.trim();

    const isDuplicate = wfaSubmissions.some((sub) => {
      const subNip = sub.nip.replace(/[\s.-]/g, '').trim().toLowerCase();
      return subNip === cleanNip && sub.tanggalWfa === cleanDate && sub.status !== 'Ditolak';
    });

    if (isDuplicate) {
      return {
        success: false,
        error: `Pengajuan ditolak: Pegawai dengan NIP ${data.nip} sudah memiliki jadwal pengajuan WFA pada tanggal ${data.tanggalWfa}. Pegawai tidak diperbolehkan mengajukan WFA pada tanggal yang sama dua kali.`,
      };
    }

    try {
      const res = await createWfaSubmissionInCloud(data);
      if (res.success && res.submission) {
        const newSub = res.submission;
        setWfaSubmissions((prev) => [newSub, ...prev.filter((s) => s.id !== newSub.id)]);
        
        try {
          if (typeof BroadcastChannel !== 'undefined') {
            const bc = new BroadcastChannel('wfa_sync_channel');
            bc.postMessage({ type: 'WFA_NEW', submission: newSub });
            bc.close();
          }
        } catch {}

        return { success: true, submission: newSub };
      }
      throw new Error(res.error || 'Gagal menyimpan ke server');
    } catch (err: any) {
      console.error('Cloud WFA submission failed, saving locally:', err);
      const localSub: WfaSubmission = {
        ...data,
        id: `wfa-${Date.now()}`,
        status: 'Menunggu Validasi',
        createdAt: new Date().toISOString(),
      };
      setWfaSubmissions((prev) => [localSub, ...prev]);

      try {
        if (typeof BroadcastChannel !== 'undefined') {
          const bc = new BroadcastChannel('wfa_sync_channel');
          bc.postMessage({ type: 'WFA_NEW', submission: localSub });
          bc.close();
        }
      } catch {}

      return { success: true, submission: localSub };
    }
  };

  const handleUpdateWfaStatus = async (id: string, status: WfaValidationStatus, notes?: string) => {
    // 1. Optimistically update local state immediately
    setWfaSubmissions((prev) =>
      prev.map((sub) =>
        sub.id === id
          ? {
              ...sub,
              status,
              catatanPengelola: notes !== undefined ? notes : sub.catatanPengelola,
              validatedAt: new Date().toISOString(),
              validatedBy: 'Tim OSDM Poltekkes',
            }
          : sub
      )
    );

    // 2. Broadcast immediately across open tabs
    try {
      if (typeof BroadcastChannel !== 'undefined') {
        const bc = new BroadcastChannel('wfa_sync_channel');
        bc.postMessage({ type: 'WFA_STATUS_UPDATE', id, status, notes });
        bc.close();
      }
    } catch {}

    // 3. Persist to Database Server
    try {
      await updateWfaStatusInCloud(id, status, notes, 'Tim OSDM Poltekkes');
      return { success: true };
    } catch (err: any) {
      console.error('Cloud WFA update status failed, kept locally:', err);
      return { success: true };
    }
  };

  const handleUpdateWfaLink = async (id: string, newLink: string): Promise<{ success: boolean; linkSuratTugas?: string; error?: string }> => {
    let formattedLink = newLink.trim();
    if (formattedLink && !/^https?:\/\//i.test(formattedLink)) {
      formattedLink = 'https://' + formattedLink;
    }

    // 1. Optimistically update local state immediately
    setWfaSubmissions((prev) =>
      prev.map((sub) =>
        sub.id === id
          ? {
              ...sub,
              linkSuratTugas: formattedLink,
            }
          : sub
      )
    );

    // 2. Broadcast immediately across open tabs
    try {
      if (typeof BroadcastChannel !== 'undefined') {
        const bc = new BroadcastChannel('wfa_sync_channel');
        bc.postMessage({ type: 'WFA_LINK_UPDATE', id, linkSuratTugas: formattedLink });
        bc.close();
      }
    } catch {}

    // 3. Persist to Database Server
    try {
      const res = await updateWfaLinkSuratTugasInCloud(id, formattedLink);
      if (!res.success) {
        throw new Error(res.error || 'Gagal memperbarui ke server');
      }
      return { success: true, linkSuratTugas: res.linkSuratTugas || formattedLink };
    } catch (err: any) {
      console.error('Cloud WFA update link failed:', err);
      return { success: false, error: err?.message || 'Gagal memperbarui ke server' };
    }
  };

  const handleDeleteWfaSubmission = async (id: string) => {
    // 1. Optimistically remove from state immediately
    setWfaSubmissions((prev) => {
      const filtered = prev.filter((s) => s.id !== id);
      try {
        localStorage.setItem(LOCAL_STORAGE_WFA_SUBMISSIONS_KEY, JSON.stringify(filtered));
      } catch {}
      return filtered;
    });

    // 2. Broadcast immediately across open tabs
    try {
      if (typeof BroadcastChannel !== 'undefined') {
        const bc = new BroadcastChannel('wfa_sync_channel');
        bc.postMessage({ type: 'WFA_DELETE', id });
        bc.close();
      }
    } catch {}

    // 3. Delete from Database Server
    try {
      await deleteWfaSubmissionInCloud(id);
      return { success: true };
    } catch (err: any) {
      console.error('Cloud WFA delete failed, removed locally:', err);
      return { success: true };
    }
  };

  const handleCreateKebugaranSubmission = async (data: Omit<KebugaranSubmission, 'id' | 'createdAt'>) => {
    // Duplicate rejection: Pegawai tidak boleh mengisi formulir kebugaran pada periode yang sama dua kali
    const cleanNip = data.nip.replace(/[\s.-]/g, '').trim().toLowerCase();
    const cleanPeriode = data.periode.trim();

    const isDuplicate = kebugaranSubmissions.some((sub) => {
      const subNip = sub.nip.replace(/[\s.-]/g, '').trim().toLowerCase();
      return subNip === cleanNip && sub.periode.toLowerCase() === cleanPeriode.toLowerCase();
    });

    if (isDuplicate) {
      return {
        success: false,
        error: `Data Ditolak: Pegawai dengan NIP ${data.nip} (${data.namaPegawai}) sudah terdaftar mengisi formulir data kebugaran untuk ${data.periode}. Setiap pegawai hanya mengisi 1 kali per periode triwulan.`,
      };
    }

    try {
      const res = await createKebugaranSubmissionInCloud(data);
      if (res.success && res.submission) {
        const newSub = res.submission;
        setKebugaranSubmissions((prev) => [newSub, ...prev.filter((s) => s.id !== newSub.id)]);

        try {
          if (typeof BroadcastChannel !== 'undefined') {
            const bc = new BroadcastChannel('kebugaran_sync_channel');
            bc.postMessage({ type: 'KEBUGARAN_NEW', submission: newSub });
            bc.close();
          }
        } catch {}

        return { success: true, submission: newSub };
      }
      throw new Error(res.error || 'Gagal menyimpan ke server');
    } catch (err: any) {
      console.error('Cloud Kebugaran submission failed, saving locally:', err);
      const localSub: KebugaranSubmission = {
        ...data,
        id: `kbg-${Date.now()}`,
        createdAt: new Date().toISOString(),
      };
      setKebugaranSubmissions((prev) => [localSub, ...prev]);

      try {
        if (typeof BroadcastChannel !== 'undefined') {
          const bc = new BroadcastChannel('kebugaran_sync_channel');
          bc.postMessage({ type: 'KEBUGARAN_NEW', submission: localSub });
          bc.close();
        }
      } catch {}

      return { success: true, submission: localSub };
    }
  };

  const handleDeleteKebugaranSubmission = async (id: string) => {
    // 1. Optimistic local delete
    setKebugaranSubmissions((prev) => {
      const filtered = prev.filter((s) => s.id !== id);
      try {
        localStorage.setItem(LOCAL_STORAGE_KEBUGARAN_SUBMISSIONS_KEY, JSON.stringify(filtered));
      } catch {}
      return filtered;
    });

    // 2. Broadcast to other tabs
    try {
      if (typeof BroadcastChannel !== 'undefined') {
        const bc = new BroadcastChannel('kebugaran_sync_channel');
        bc.postMessage({ type: 'KEBUGARAN_DELETE', id });
        bc.close();
      }
    } catch {}

    // 3. Delete from Database Server
    try {
      await deleteKebugaranSubmissionInCloud(id);
      return { success: true };
    } catch (err: any) {
      console.error('Cloud Kebugaran delete failed, removed locally:', err);
      return { success: true };
    }
  };

  // Meeting Attendance Handlers (Synced to Cloud across all devices)
  const handleCreateMeetingAttendance = async (data: Omit<MeetingAttendanceRecord, 'id' | 'timestamp'>) => {
    const newRecord: MeetingAttendanceRecord = {
      ...data,
      id: `att-${Date.now()}-${Math.random().toString(36).substr(2, 4)}`,
      timestamp: new Date().toISOString(),
    };
    setMeetingAttendees((prev) => [newRecord, ...prev]);
    try {
      await createMeetingAttendeeInCloud(newRecord);
    } catch (err) {
      console.warn('Meeting attendee cloud save error:', err);
    }
    return true;
  };

  const handleDeleteMeetingAttendee = async (id: string) => {
    setMeetingAttendees((prev) => prev.filter((a) => a.id !== id));
    try {
      await deleteMeetingAttendeeInCloud(id);
    } catch (err) {
      console.warn('Meeting attendee cloud delete error:', err);
    }
  };

  const handleDeleteMeetingAttendeesByAgenda = async (agendaName: string) => {
    const clean = agendaName.trim().toLowerCase();

    // 1. Filter out attendees of this agenda from state
    setMeetingAttendees((prev) =>
      prev.filter((att) => {
        if (att.agendas && att.agendas.length > 0) {
          return !att.agendas.some((ag) => ag.trim().toLowerCase() === clean);
        }
        if ((att as any).agenda) {
          return (att as any).agenda.trim().toLowerCase() !== clean;
        }
        return true;
      })
    );

    // 2. Clean session if it uses this agenda
    if (activeMeetingSession.agenda && activeMeetingSession.agenda.trim().toLowerCase() === clean) {
      const updatedSession: MeetingSession = {
        ...activeMeetingSession,
        agenda: '',
        agendas: (activeMeetingSession.agendas || []).filter(
          (a) => a.trim().toLowerCase() !== clean
        ),
      };
      setMeetingSessions((prev) =>
        prev.map((s) => (s.id === updatedSession.id ? updatedSession : s))
      );
      saveMeetingSessionToCloud(updatedSession).catch(() => {});
    }

    // 3. Delete agenda from cloud (backend cascade deletes its attendees)
    try {
      await deleteMeetingAgendaFromCloud(agendaName);
    } catch (err) {
      console.warn('Error deleting meeting agenda and attendees from cloud:', err);
    }
  };

  const handleSelectMeetingSession = (sessionId: string) => {
    setActiveMeetingId(sessionId);
  };

  const handleCreateMeetingSession = async (newSession: MeetingSession) => {
    setMeetingSessions((prev) => [newSession, ...prev.filter((s) => s.id !== newSession.id)]);
    setActiveMeetingId(newSession.id);
    try {
      await saveMeetingSessionToCloud(newSession);
    } catch (err) {
      console.warn('Error saving new meeting session to cloud:', err);
    }
  };

  const handleDeleteMeetingSession = async (sessionId: string) => {
    setMeetingSessions((prev) => {
      const remaining = prev.filter((s) => s.id !== sessionId);
      if (remaining.length > 0 && activeMeetingId === sessionId) {
        setActiveMeetingId(remaining[0].id);
      }
      return remaining.length > 0 ? remaining : [INITIAL_MEETING_SESSION];
    });
    try {
      await deleteMeetingSessionFromCloud(sessionId);
    } catch (err) {
      console.warn('Error deleting meeting session from cloud:', err);
    }
  };

  const handleToggleMeetingSessionStatus = async (sessionId: string, isActive: boolean) => {
    setMeetingSessions((prev) =>
      prev.map((s) => {
        if (s.id === sessionId) {
          const updated = { ...s, isActive };
          saveMeetingSessionToCloud(updated).catch(() => {});
          return updated;
        }
        return s;
      })
    );
  };

  const handleUpdateMeetingSession = async (updatedSession: MeetingSession) => {
    if (updatedSession.agenda) {
      saveAgendaPermanently(updatedSession.agenda);
      saveMeetingAgendaToCloud(updatedSession.agenda);
    }
    setMeetingSessions((prev) =>
      prev.map((s) => (s.id === updatedSession.id ? updatedSession : s))
    );
    try {
      await saveMeetingSessionToCloud(updatedSession);
    } catch (err) {
      console.warn('Meeting session cloud save error:', err);
    }
  };

  const handleResetMeetingSession = async () => {
    if (activeMeetingSession.agenda) {
      saveAgendaPermanently(activeMeetingSession.agenda);
      saveMeetingAgendaToCloud(activeMeetingSession.agenda);
    }
    const newSession: MeetingSession = {
      ...INITIAL_MEETING_SESSION,
      id: `meet-${Date.now()}`,
      title: 'Rapat Koordinasi Poltekkes Kemenkes Bandung',
      agenda: '',
      agendas: [],
      date: new Date().toISOString().split('T')[0],
      createdAt: new Date().toISOString(),
      isActive: true,
    };
    setMeetingSessions((prev) => [newSession, ...prev]);
    setActiveMeetingId(newSession.id);
    try {
      await saveMeetingSessionToCloud(newSession);
    } catch (err) {
      console.warn('Meeting session cloud reset error:', err);
    }
  };

  // Pegawai Forms & Responses Handlers
  const handleCreatePegawaiResponse = async (payload: PegawaiFormResponse) => {
    const cleanNip = payload.nip.replace(/[\s.-]/g, '').toLowerCase();
    setPegawaiResponses((prev) => {
      const idx = prev.findIndex(
        (r) => r.formId === payload.formId && r.nip.replace(/[\s.-]/g, '').toLowerCase() === cleanNip
      );
      let updated: PegawaiFormResponse[];
      if (idx >= 0) {
        updated = [...prev];
        updated[idx] = { ...prev[idx], ...payload, id: prev[idx].id };
      } else {
        updated = [payload, ...prev];
      }
      try {
        localStorage.setItem('osdm_pegawai_form_responses', JSON.stringify(updated));
      } catch {}
      return updated;
    });

    savePegawaiFormResponseToCloud(payload).catch(() => {});
    return { success: true };
  };

  const handleDeletePegawaiResponse = async (id: string) => {
    setPegawaiResponses((prev) => {
      const filtered = prev.filter((r) => r.id !== id);
      try {
        localStorage.setItem('osdm_pegawai_form_responses', JSON.stringify(filtered));
      } catch {}
      return filtered;
    });
    deletePegawaiFormResponseFromCloud(id).catch(() => {});
    return true;
  };

  const handleCreatePegawaiForm = async (newForm: PegawaiForm) => {
    setPegawaiForms((prev) => {
      const updated = [newForm, ...prev.filter((f) => f.id !== newForm.id)];
      try {
        localStorage.setItem('osdm_pegawai_forms', JSON.stringify(updated));
      } catch {}
      return updated;
    });
    setSelectedPegawaiFormId(newForm.id);
    savePegawaiFormToCloud(newForm).catch(() => {});
    return true;
  };

  const handleTogglePegawaiFormStatus = async (id: string, isActive: boolean) => {
    setPegawaiForms((prev) => {
      const updated = prev.map((f) => (f.id === id ? { ...f, isActive } : f));
      try {
        localStorage.setItem('osdm_pegawai_forms', JSON.stringify(updated));
      } catch {}
      const target = updated.find((f) => f.id === id);
      if (target) savePegawaiFormToCloud(target).catch(() => {});
      return updated;
    });
    return true;
  };

  const handleUpdatePegawaiForm = async (updatedForm: PegawaiForm) => {
    setPegawaiForms((prev) => {
      const updated = prev.map((f) => (f.id === updatedForm.id ? updatedForm : f));
      try {
        localStorage.setItem('osdm_pegawai_forms', JSON.stringify(updated));
      } catch {}
      return updated;
    });
    setActivePegawaiFormId(updatedForm.id);
    savePegawaiFormToCloud(updatedForm).catch(() => {});
    return true;
  };

  const handleDeletePegawaiForm = async (id: string) => {
    setPegawaiForms((prev) => {
      const filtered = prev.filter((f) => f.id !== id);
      try {
        localStorage.setItem('osdm_pegawai_forms', JSON.stringify(filtered));
      } catch {}
      if (selectedPegawaiFormId === id) {
        setSelectedPegawaiFormId(filtered.length > 0 ? filtered[0].id : '');
      }
      return filtered;
    });
    setPegawaiResponses((prev) => {
      const filtered = prev.filter((r) => r.formId !== id);
      try {
        localStorage.setItem('osdm_pegawai_form_responses', JSON.stringify(filtered));
      } catch {}
      return filtered;
    });
    deletePegawaiFormFromCloud(id).catch(() => {});
    return true;
  };

  // Dynamic Browser Tab Title and Favicon Management
  useEffect(() => {
    const activeProf = currentView === 'admin' ? profile : liveProfile;
    const baseTitle = (activeProf.tabTitle && activeProf.tabTitle.trim())
      ? activeProf.tabTitle.trim()
      : (activeProf.name || 'Portal Layanan Pegawai');

    document.title = currentView === 'admin' ? `Admin Panel • ${baseTitle}` : baseTitle;

    // Favicon Link in DOM
    let link = document.querySelector("link[rel~='icon']") as HTMLLinkElement | null;
    if (!link) {
      link = document.createElement('link');
      link.rel = 'icon';
      document.head.appendChild(link);
    }

    const customFavicon = activeProf.faviconUrl?.trim();
    if (customFavicon) {
      link.href = customFavicon;
    } else if (activeProf.avatarUrl?.trim()) {
      link.href = activeProf.avatarUrl.trim();
    } else {
      // Default SVG favicon
      link.href = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏢</text></svg>";
    }
  }, [
    currentView,
    profile.tabTitle,
    profile.name,
    profile.faviconUrl,
    profile.avatarUrl,
    liveProfile.tabTitle,
    liveProfile.name,
    liveProfile.faviconUrl,
    liveProfile.avatarUrl
  ]);

  // Handler for Admin PIN change (syncs to Database Server & LocalStorage)
  const handleUpdateAdminPin = async (newPin: string) => {
    const cleanPin = newPin.trim();
    setAdminPin(cleanPin);
    try {
      localStorage.setItem(LOCAL_STORAGE_ADMIN_PIN_KEY, cleanPin);
      await saveAdminPinToCloud(cleanPin);
    } catch (e) {
      console.warn('Failed to sync PIN to cloud:', e);
    }
  };

  // Handler for WFA Admin PIN change (syncs to Cloud & LocalStorage)
  const handleUpdateWfaAdminPin = async (newPin: string) => {
    const cleanPin = newPin.trim();
    setWfaAdminPin(cleanPin);
    try {
      localStorage.setItem(LOCAL_STORAGE_WFA_ADMIN_PIN_KEY, cleanPin);
      await saveWfaAdminPinToCloud(cleanPin);
    } catch (e) {
      console.warn('Failed to sync WFA PIN to cloud:', e);
    }
  };

  // Handler for Kebugaran Admin PIN change (syncs to Cloud & LocalStorage)
  const handleUpdateKebugaranAdminPin = async (newPin: string) => {
    const cleanPin = newPin.trim();
    setKebugaranAdminPin(cleanPin);
    try {
      localStorage.setItem(LOCAL_STORAGE_KEBUGARAN_ADMIN_PIN_KEY, cleanPin);
      await saveKebugaranAdminPinToCloud(cleanPin);
    } catch (e) {
      console.warn('Failed to sync Kebugaran PIN to cloud:', e);
    }
  };

  // Handler for Meeting Admin PIN change (syncs to Cloud & LocalStorage)
  const handleUpdateMeetingAdminPin = async (newPin: string) => {
    const cleanPin = newPin.trim();
    setMeetingAdminPin(cleanPin);
    try {
      localStorage.setItem(LOCAL_STORAGE_MEETING_ADMIN_PIN_KEY, cleanPin);
      await saveMeetingAdminPinToCloud(cleanPin);
    } catch (e) {
      console.warn('Failed to sync Meeting PIN to cloud:', e);
    }
  };

  // Handler for Monitoring All Admin PIN change (syncs to Cloud & LocalStorage)
  const handleUpdateMonitoringAllAdminPin = async (newPin: string) => {
    const cleanPin = newPin.trim();
    setMonitoringAllAdminPin(cleanPin);
    try {
      localStorage.setItem(LOCAL_STORAGE_MONITORING_ALL_ADMIN_PIN_KEY, cleanPin);
      await saveMonitoringAllAdminPinToCloud(cleanPin);
    } catch (e) {
      console.warn('Failed to sync Monitoring All PIN to cloud:', e);
    }
  };

  // Handle URL parameters or Hash (#admin or ?admin=true) & Keyboard shortcut Alt+A
  useEffect(() => {
    const checkAdminTrigger = () => {
      const hasAdminHash = window.location.hash.toLowerCase().includes('admin');
      const urlParams = new URLSearchParams(window.location.search);
      const hasAdminQuery = urlParams.has('admin');

      if (hasAdminHash || hasAdminQuery) {
        if (!isAdminAuthenticated) {
          setIsAuthModalOpen(true);
        } else {
          setCurrentView('admin');
        }
      }
    };

    // Keyboard shortcut handler
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.altKey && (e.key === 'a' || e.key === 'A')) {
        e.preventDefault();
        if (!isAdminAuthenticated) {
          setIsAuthModalOpen(true);
        } else {
          setCurrentView(prev => prev === 'admin' ? 'public' : 'admin');
        }
      }
    };

    checkAdminTrigger();
    window.addEventListener('hashchange', checkAdminTrigger);
    window.addEventListener('keydown', handleKeyDown);

    return () => {
      window.removeEventListener('hashchange', checkAdminTrigger);
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [isAdminAuthenticated]);

  // Auth Success Handler
  const handleAuthSuccess = (role: AdminRole = 'superadmin') => {
    setIsAdminAuthenticated(true);
    setAdminRole(role);
    setIsAuthModalOpen(false);
    setCurrentView('admin');
    try {
      sessionStorage.setItem(SESSION_ADMIN_AUTH_KEY, 'true');
      sessionStorage.setItem(SESSION_ADMIN_ROLE_KEY, role);
    } catch {
      // ignore
    }
    if (role === 'wfa_admin') {
      setAdminActiveTab('wfa_monitoring');
    } else if (role === 'kebugaran_admin') {
      setAdminActiveTab('kebugaran_monitoring');
    } else if (role === 'meeting_admin') {
      setAdminActiveTab('meeting_attendance');
    } else if (role === 'monitoring_all_admin') {
      setAdminActiveTab('wfa_monitoring');
    }
  };

  // Logout Handler
  const handleAdminLogout = () => {
    setIsAdminAuthenticated(false);
    setAdminRole('superadmin');
    setCurrentView('public');
    try {
      sessionStorage.removeItem(SESSION_ADMIN_AUTH_KEY);
      sessionStorage.removeItem(SESSION_ADMIN_ROLE_KEY);
      if (window.location.hash.includes('admin')) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }
    } catch {
      // ignore
    }
  };

  // Function to Publish/Update Live Portal for Employees (Real-time Cloud Sync)
  const handlePublishLive = async () => {
    if (isPublishing) return;
    setIsPublishing(true);

    try {
      const updatedMenus = Array.isArray(menus) ? menus.map((m) => ({ ...m })) : [];
      const updatedProfile = profile ? { ...profile } : INITIAL_PROFILE;
      const now = new Date().toISOString();

      // 1. Update local state and localStorage immediately
      setLiveMenus(updatedMenus);
      setLiveProfile(updatedProfile);
      setLastPublishedAt(now);

      try {
        localStorage.setItem(LOCAL_STORAGE_LIVE_MENUS_KEY, JSON.stringify(updatedMenus));
        localStorage.setItem(LOCAL_STORAGE_LIVE_PROFILE_KEY, JSON.stringify(updatedProfile));
        localStorage.setItem(LOCAL_STORAGE_LAST_PUBLISHED_KEY, now);
      } catch (e) {
        console.warn('LocalStorage save error:', e);
      }

      // 2. Broadcast immediately to any other tabs or windows in the same browser (0ms latency)
      if (typeof BroadcastChannel !== 'undefined') {
        try {
          const bc = new BroadcastChannel('direct_menu_live_sync');
          bc.postMessage({
            type: 'PORTAL_LIVE_UPDATE',
            menus: updatedMenus,
            profile: updatedProfile,
            timestamp: now,
          });
          bc.close();
        } catch (e) {
          console.warn('Broadcast error:', e);
        }
      }

      // 3. Sync directly to Database Server so ALL devices and public URLs receive updates immediately
      const result = await publishLivePortalToCloud(updatedMenus, updatedProfile);
      setLastPublishedAt(result.timestamp || now);

      if (!result.success) {
        setIsCloudSynced(false);
        setPublishStatus({
          success: false,
          cloudSynced: false,
          message: `Perubahan tersimpan lokal. (${result.error || 'Terhubung via lokal'})`
        });
      } else {
        setIsCloudSynced(true);
        setPublishStatus({
          success: true,
          cloudSynced: true,
          message: result.error 
            ? `Berhasil diposting! (${result.error})`
            : 'Berhasil diposting ke Database Server! Semua perangkat pegawai & tab browser otomatis diperbarui.'
        });
      }
    } catch (error: any) {
      console.warn('Cloud publishing failed, fell back to local storage:', error);
      setIsCloudSynced(false);
      setPublishStatus({
        success: false,
        cloudSynced: false,
        message: `Tersimpan di browser ini saja. (${error?.message || 'Gagal menyimpan'})`
      });
    } finally {
      setIsPublishing(false);
      setPublishSuccessToast(true);
      setShowPublishSuccessModal(true);
      setTimeout(() => setPublishSuccessToast(false), 4000);
    }
  };

  // Dynamic menus enriched with real-time database click counts
  const menusWithClicks = useMemo(() => {
    if (!clickStats) return menus;
    return menus.map((m) => {
      let count = m.clickCount || 0;
      if (clickStats.menuCounts && clickStats.menuCounts[m.id] !== undefined) {
        count = clickStats.menuCounts[m.id];
      } else if (clickStats.menuTitleCounts && clickStats.menuTitleCounts[m.title] !== undefined) {
        count = clickStats.menuTitleCounts[m.title];
      }
      return {
        ...m,
        clickCount: count,
      };
    });
  }, [menus, clickStats]);

  // Click tracking event dispatcher
  const handleMenuClick = (clickedMenu: MenuItem) => {
    // 1. Increment menu click count in both draft and live
    setMenus((prev) =>
      prev.map((m) =>
        m.id === clickedMenu.id ? { ...m, clickCount: (m.clickCount || 0) + 1 } : m
      )
    );
    setLiveMenus((prev) =>
      prev.map((m) =>
        m.id === clickedMenu.id ? { ...m, clickCount: (m.clickCount || 0) + 1 } : m
      )
    );

    // 2. Detect device type
    const ua = navigator.userAgent;
    let device: 'Mobile' | 'Desktop' | 'Tablet' = 'Desktop';
    if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
      device = 'Tablet';
    } else if (
      /Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/i.test(
        ua
      )
    ) {
      device = 'Mobile';
    }

    // 3. Create log
    const newLog: ClickLog = {
      id: `log-${Date.now()}-${Math.random().toString(36).substr(2, 4)}`,
      menuId: clickedMenu.id,
      menuTitle: clickedMenu.title,
      category: clickedMenu.category || 'Umum',
      timestamp: new Date().toISOString(),
      device,
      browser: /Chrome/i.test(ua) ? 'Chrome' : /Safari/i.test(ua) ? 'Safari' : 'Browser',
      referrer: document.referrer ? new URL(document.referrer).hostname : 'Direct / QR',
    };

    setLogs((prev) => [newLog, ...prev.slice(0, 199)]);

    // Update clickStats locally for instant visual feedback
    setClickStats((prev) => {
      const prevTotal = prev ? prev.totalClicks : 0;
      const prevMenuCounts = prev ? { ...prev.menuCounts } : {};
      const prevTitleCounts = prev ? { ...prev.menuTitleCounts } : {};
      const prevDevices = prev ? { ...prev.deviceCounts } : { mobile: 0, desktop: 0, tablet: 0 };

      prevMenuCounts[clickedMenu.id] = (prevMenuCounts[clickedMenu.id] || clickedMenu.clickCount || 0) + 1;
      prevTitleCounts[clickedMenu.title] = (prevTitleCounts[clickedMenu.title] || clickedMenu.clickCount || 0) + 1;
      if (device === 'Mobile') prevDevices.mobile++;
      else if (device === 'Desktop') prevDevices.desktop++;
      else if (device === 'Tablet') prevDevices.tablet++;

      return {
        totalClicks: prevTotal + 1,
        menuCounts: prevMenuCounts,
        menuTitleCounts: prevTitleCounts,
        deviceCounts: prevDevices,
      };
    });

    // Log to cloud asynchronously
    logClickToCloud(newLog);
  };

  // Simulate click for demo
  const handleSimulateClick = () => {
    if (menus.length === 0) return;
    const randomMenu = menus[Math.floor(Math.random() * menus.length)];
    const devices: Array<'Mobile' | 'Desktop' | 'Tablet'> = ['Mobile', 'Mobile', 'Mobile', 'Desktop', 'Tablet'];
    const referrers = ['Instagram Bio', 'WhatsApp Share', 'TikTok Profile', 'Google Search', 'Direct QR'];
    const randomDevice = devices[Math.floor(Math.random() * devices.length)];
    const randomRef = referrers[Math.floor(Math.random() * referrers.length)];

    setMenus((prev) =>
      prev.map((m) =>
        m.id === randomMenu.id ? { ...m, clickCount: (m.clickCount || 0) + 1 } : m
      )
    );

    const simulatedLog: ClickLog = {
      id: `log-${Date.now()}-${Math.random().toString(36).substr(2, 4)}`,
      menuId: randomMenu.id,
      menuTitle: randomMenu.title,
      category: randomMenu.category || 'Umum',
      timestamp: new Date().toISOString(),
      device: randomDevice,
      browser: randomDevice === 'Mobile' ? 'Chrome Mobile' : 'Chrome 122',
      referrer: randomRef,
    };

    setLogs((prev) => [simulatedLog, ...prev.slice(0, 199)]);

    // Update clickStats locally
    setClickStats((prev) => {
      const prevTotal = prev ? prev.totalClicks : 0;
      const prevMenuCounts = prev ? { ...prev.menuCounts } : {};
      const prevTitleCounts = prev ? { ...prev.menuTitleCounts } : {};
      const prevDevices = prev ? { ...prev.deviceCounts } : { mobile: 0, desktop: 0, tablet: 0 };

      prevMenuCounts[randomMenu.id] = (prevMenuCounts[randomMenu.id] || randomMenu.clickCount || 0) + 1;
      prevTitleCounts[randomMenu.title] = (prevTitleCounts[randomMenu.title] || randomMenu.clickCount || 0) + 1;
      if (randomDevice === 'Mobile') prevDevices.mobile++;
      else if (randomDevice === 'Desktop') prevDevices.desktop++;
      else if (randomDevice === 'Tablet') prevDevices.tablet++;

      return {
        totalClicks: prevTotal + 1,
        menuCounts: prevMenuCounts,
        menuTitleCounts: prevTitleCounts,
        deviceCounts: prevDevices,
      };
    });

    // Also persist to cloud so it is permanently saved in the database
    logClickToCloud(simulatedLog);
  };

  const handleClearLogs = async () => {
    if (window.confirm('Hapus seluruh riwayat log klik analitik dari server dan database?')) {
      setLogs([]);
      setClickStats({
        totalClicks: 0,
        menuCounts: {},
        menuTitleCounts: {},
        deviceCounts: { mobile: 0, desktop: 0, tablet: 0 },
      });
      setMenus((prev) => prev.map((m) => ({ ...m, clickCount: 0 })));
      setLiveMenus((prev) => prev.map((m) => ({ ...m, clickCount: 0 })));
      await deleteClickLogsFromCloud();
    }
  };

  const handleRefreshClickLogs = async () => {
    try {
      const res = await fetchClickLogs();
      if (res.logs && res.logs.length > 0) {
        setLogs(res.logs);
      }
      if (res.stats) {
        setClickStats(res.stats);
      }
    } catch {
      // ignore
    }
  };

  const totalClicks = clickStats?.totalClicks ?? menus.reduce((acc, m) => acc + (m.clickCount || 0), 0);

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col selection:bg-indigo-600 selection:text-white font-sans antialiased">
      {/* Top Navbar: ONLY rendered when Admin is Authenticated */}
      {isAdminAuthenticated && (
        <Navbar
          currentView={currentView}
          setCurrentView={setCurrentView}
          onOpenQR={() => setIsQRModalOpen(true)}
          onLogout={handleAdminLogout}
          onPublish={handlePublishLive}
          isPublishing={isPublishing}
          lastPublishedAt={lastPublishedAt}
          profile={profile}
          totalClicks={totalClicks}
          adminActiveTab={adminActiveTab}
          onSelectAdminTab={setAdminActiveTab}
          wfaPendingCount={wfaSubmissions.filter((s) => s.status === 'Menunggu Validasi').length}
          kebugaranCount={kebugaranSubmissions.length}
          meetingAttendeesCount={meetingAttendees.length}
          pegawaiResponsesCount={pegawaiResponses.length}
          menusCount={menus.length}
          adminRole={adminRole}
        />
      )}

      {/* Main View Area */}
      <main className="flex-1 flex flex-col">
        {/* PUBLIC MICROSITE VIEW (Employees see the LIVE published version) */}
        {(!isAdminAuthenticated || currentView === 'public') && (
          <div className="flex-1 flex flex-col justify-start">
            {/* Quick Admin Return Bar when viewing public mode as Admin */}
            {isAdminAuthenticated && (
              <div className="bg-indigo-900 text-indigo-100 px-4 py-2 border-b border-indigo-700/50 flex items-center justify-between text-xs sticky top-14 z-30 shadow-md">
                <div className="flex items-center gap-2">
                  <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                  <span className="font-semibold text-white">Mode Tampilan Resmi Pegawai (Live)</span>
                  <span className="text-[11px] text-indigo-200 hidden sm:inline">— Ini adalah tampilan yang dilihat oleh seluruh pegawai</span>
                </div>
                <button
                  onClick={() => setCurrentView('admin')}
                  className="px-3 py-1 bg-white/10 hover:bg-white/20 text-white rounded text-xs font-bold transition-colors flex items-center gap-1.5 border border-white/20"
                >
                  <span>⚙️ {adminRole === 'wfa_admin' ? 'Kembali ke Monitoring WFA' : 'Kembali ke Dashboard'}</span>
                </button>
              </div>
            )}
            <PublicMicrosite
              profile={liveProfile}
              menus={liveMenus}
              adminPin={adminPin}
              onMenuClick={handleMenuClick}
              onOpenQR={() => setIsQRModalOpen(true)}
              onOpenAdmin={() => {
                if (isAdminAuthenticated) {
                  setCurrentView('admin');
                } else {
                  setIsAuthModalOpen(true);
                }
              }}
              isStandalone={true}
              lastPublishedAt={lastPublishedAt}
              wfaSubmissions={wfaSubmissions}
              onSubmitWfa={handleCreateWfaSubmission}
              kebugaranSubmissions={kebugaranSubmissions}
              onSubmitKebugaran={handleCreateKebugaranSubmission}
              meetingSession={activeMeetingSession}
              meetingSessions={meetingSessions}
              activeMeetingId={activeMeetingId}
              onSelectMeetingSession={handleSelectMeetingSession}
              meetingAttendees={meetingAttendees}
              onSubmitMeetingAttendance={handleCreateMeetingAttendance}
              isMeetingModalOpen={isMeetingFormModalOpen}
              onCloseMeetingModal={() => setIsMeetingFormModalOpen(false)}
              onOpenPegawaiForm={handleOpenPegawaiFormModal}
            />
          </div>
        )}

        {/* ADMIN DASHBOARD VIEW (Admin edits the working draft) */}
        {isAdminAuthenticated && currentView === 'admin' && (
          <AdminDashboard
            menus={menusWithClicks}
            setMenus={setMenus}
            profile={profile}
            setProfile={setProfile}
            liveMenus={liveMenus}
            liveProfile={liveProfile}
            logs={logs}
            setLogs={setLogs}
            clickStats={clickStats}
            onRefreshAnalytics={handleRefreshClickLogs}
            onOpenPublicPreview={() => setCurrentView('public')}
            onOpenQR={() => setIsQRModalOpen(true)}
            onSimulateClick={handleSimulateClick}
            onClearLogs={handleClearLogs}
            adminPin={adminPin}
            setAdminPin={handleUpdateAdminPin}
            onLogout={handleAdminLogout}
            onPublish={handlePublishLive}
            isPublishing={isPublishing}
            lastPublishedAt={lastPublishedAt}
            wfaSubmissions={wfaSubmissions}
            onUpdateWfaStatus={handleUpdateWfaStatus}
            onUpdateWfaLink={handleUpdateWfaLink}
            onDeleteWfaSubmission={handleDeleteWfaSubmission}
            kebugaranSubmissions={kebugaranSubmissions}
            onDeleteKebugaranSubmission={handleDeleteKebugaranSubmission}
            onOpenKebugaranModal={() => setIsAdminKebugaranModalOpen(true)}
            meetingSession={activeMeetingSession}
            meetingSessions={meetingSessions}
            activeMeetingId={activeMeetingId}
            onSelectMeetingSession={handleSelectMeetingSession}
            onCreateMeetingSession={handleCreateMeetingSession}
            onDeleteMeetingSession={handleDeleteMeetingSession}
            onToggleMeetingSessionStatus={handleToggleMeetingSessionStatus}
            onUpdateMeetingSession={handleUpdateMeetingSession}
            onResetMeetingSession={handleResetMeetingSession}
            meetingAttendees={meetingAttendees}
            onDeleteMeetingAttendee={handleDeleteMeetingAttendee}
            onDeleteMeetingAttendeesByAgenda={handleDeleteMeetingAttendeesByAgenda}
            onAddMeetingAttendee={handleCreateMeetingAttendance}
            onOpenMeetingForm={() => setIsMeetingFormModalOpen(true)}
            activeTab={adminActiveTab}
            setActiveTab={setAdminActiveTab}
            adminRole={adminRole}
            wfaAdminPin={wfaAdminPin}
            onUpdateWfaAdminPin={handleUpdateWfaAdminPin}
            kebugaranAdminPin={kebugaranAdminPin}
            onUpdateKebugaranAdminPin={handleUpdateKebugaranAdminPin}
            meetingAdminPin={meetingAdminPin}
            onUpdateMeetingAdminPin={handleUpdateMeetingAdminPin}
            monitoringAllAdminPin={monitoringAllAdminPin}
            onUpdateMonitoringAllAdminPin={handleUpdateMonitoringAllAdminPin}
            pegawaiForms={pegawaiForms}
            selectedPegawaiFormId={selectedPegawaiFormId}
            onSelectPegawaiForm={setSelectedPegawaiFormId}
            pegawaiResponses={pegawaiResponses}
            onDeletePegawaiResponse={handleDeletePegawaiResponse}
            onOpenPegawaiFillModal={(form) => {
              setActivePegawaiFormId(form.id);
              setIsPegawaiFormModalOpen(true);
            }}
            onCreatePegawaiForm={handleCreatePegawaiForm}
            onTogglePegawaiFormStatus={handleTogglePegawaiFormStatus}
            onUpdatePegawaiForm={handleUpdatePegawaiForm}
            onDeletePegawaiForm={handleDeletePegawaiForm}
          />
        )}

        {/* SPLIT DUAL VIEW (Admin Workspace on Left + Live Interactive Preview on Right) */}
        {isAdminAuthenticated && currentView === 'split' && (
          <div className="flex-1 grid grid-cols-1 lg:grid-cols-12 overflow-hidden min-h-[calc(100vh-56px)] bg-slate-100/60">
            {/* Left 7 cols: Admin Controls */}
            <div className="lg:col-span-7 border-r border-slate-200 overflow-y-auto max-h-[calc(100vh-56px)] bg-slate-50">
              <AdminDashboard
                menus={menusWithClicks}
                setMenus={setMenus}
                profile={profile}
                setProfile={setProfile}
                liveMenus={liveMenus}
                liveProfile={liveProfile}
                logs={logs}
                setLogs={setLogs}
                clickStats={clickStats}
                onRefreshAnalytics={handleRefreshClickLogs}
                onOpenPublicPreview={() => setCurrentView('public')}
                onOpenQR={() => setIsQRModalOpen(true)}
                onSimulateClick={handleSimulateClick}
                onClearLogs={handleClearLogs}
                adminPin={adminPin}
                setAdminPin={handleUpdateAdminPin}
                onLogout={handleAdminLogout}
                onPublish={handlePublishLive}
                isPublishing={isPublishing}
                lastPublishedAt={lastPublishedAt}
                wfaSubmissions={wfaSubmissions}
                activeTab={adminActiveTab}
                setActiveTab={setAdminActiveTab}
                onUpdateWfaStatus={handleUpdateWfaStatus}
                onUpdateWfaLink={handleUpdateWfaLink}
                onDeleteWfaSubmission={handleDeleteWfaSubmission}
                kebugaranSubmissions={kebugaranSubmissions}
                onDeleteKebugaranSubmission={handleDeleteKebugaranSubmission}
                onOpenKebugaranModal={() => setIsAdminKebugaranModalOpen(true)}
                meetingSession={activeMeetingSession}
                meetingSessions={meetingSessions}
                activeMeetingId={activeMeetingId}
                onSelectMeetingSession={handleSelectMeetingSession}
                onCreateMeetingSession={handleCreateMeetingSession}
                onDeleteMeetingSession={handleDeleteMeetingSession}
                onToggleMeetingSessionStatus={handleToggleMeetingSessionStatus}
                onUpdateMeetingSession={handleUpdateMeetingSession}
                onResetMeetingSession={handleResetMeetingSession}
                meetingAttendees={meetingAttendees}
                onDeleteMeetingAttendee={handleDeleteMeetingAttendee}
                onDeleteMeetingAttendeesByAgenda={handleDeleteMeetingAttendeesByAgenda}
                onAddMeetingAttendee={handleCreateMeetingAttendance}
                onOpenMeetingForm={() => setIsMeetingFormModalOpen(true)}
                adminRole={adminRole}
                wfaAdminPin={wfaAdminPin}
                onUpdateWfaAdminPin={handleUpdateWfaAdminPin}
                kebugaranAdminPin={kebugaranAdminPin}
                onUpdateKebugaranAdminPin={handleUpdateKebugaranAdminPin}
                meetingAdminPin={meetingAdminPin}
                onUpdateMeetingAdminPin={handleUpdateMeetingAdminPin}
                monitoringAllAdminPin={monitoringAllAdminPin}
                onUpdateMonitoringAllAdminPin={handleUpdateMonitoringAllAdminPin}
                pegawaiForms={pegawaiForms}
                selectedPegawaiFormId={selectedPegawaiFormId}
                onSelectPegawaiForm={setSelectedPegawaiFormId}
                pegawaiResponses={pegawaiResponses}
                onDeletePegawaiResponse={handleDeletePegawaiResponse}
                onOpenPegawaiFillModal={(form) => {
                  setActivePegawaiFormId(form.id);
                  setIsPegawaiFormModalOpen(true);
                }}
                onCreatePegawaiForm={handleCreatePegawaiForm}
                onTogglePegawaiFormStatus={handleTogglePegawaiFormStatus}
                onUpdatePegawaiForm={handleUpdatePegawaiForm}
                onDeletePegawaiForm={handleDeletePegawaiForm}
              />
            </div>

            {/* Right 5 cols: Live Public Microsite Screen */}
            <div className="lg:col-span-5 bg-slate-200/50 overflow-y-auto max-h-[calc(100vh-56px)] p-6 flex flex-col items-center justify-start">
              <div className="w-full max-w-md bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xl">
                <div className="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between text-xs text-slate-500">
                  <span className="font-bold text-slate-800 flex items-center gap-1.5">
                    <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                    Pratinjau Hasil Edit
                  </span>
                  <span className="text-[11px] text-slate-400 font-medium">
                    (Mode Pratinjau Interaktif)
                  </span>
                </div>
                <PublicMicrosite
                  profile={profile}
                  menus={menus}
                  adminPin={adminPin}
                  onMenuClick={handleMenuClick}
                  onOpenQR={() => setIsQRModalOpen(true)}
                  isStandalone={false}
                  lastPublishedAt={lastPublishedAt}
                  wfaSubmissions={wfaSubmissions}
                  onSubmitWfa={handleCreateWfaSubmission}
                  kebugaranSubmissions={kebugaranSubmissions}
                  onSubmitKebugaran={handleCreateKebugaranSubmission}
                  meetingSession={activeMeetingSession}
                  meetingSessions={meetingSessions}
                  activeMeetingId={activeMeetingId}
                  onSelectMeetingSession={handleSelectMeetingSession}
                  meetingAttendees={meetingAttendees}
                  onSubmitMeetingAttendance={handleCreateMeetingAttendance}
                  isMeetingModalOpen={isMeetingFormModalOpen}
                  onCloseMeetingModal={() => setIsMeetingFormModalOpen(false)}
                  onOpenPegawaiForm={handleOpenPegawaiFormModal}
                />
              </div>
            </div>
          </div>
        )}
      </main>

      {/* Global Publish Success Toast Notification */}
      <AnimatePresence>
        {publishSuccessToast && (
          <motion.div
            initial={{ opacity: 0, y: 40, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 20, scale: 0.95 }}
            className="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 bg-slate-900 text-white rounded-xl shadow-2xl border border-emerald-500/40 text-xs"
          >
            <div className="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/30">
              <CheckCheck className="w-4 h-4" />
            </div>
            <div>
              <div className="font-bold text-white flex items-center gap-1.5">
                <span>Berhasil Diposting!</span>
                <span className="text-[10px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.2 rounded">Live</span>
              </div>
              <p className="text-slate-300 text-[11px] mt-0.5">
                Halaman pegawai telah diperbarui sesuai perubahan admin terbaru.
              </p>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Publish Success Interactive Dialog */}
      <AnimatePresence>
        {showPublishSuccessModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <motion.div
              initial={{ opacity: 0, scale: 0.9, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: 20 }}
              className="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full overflow-hidden"
            >
              <div className="p-6 text-center space-y-4">
                <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/30">
                  <CheckCheck className="w-9 h-9" />
                </div>

                <div className="space-y-1.5">
                  <h3 className="text-lg font-bold text-slate-900">
                    Perubahan Berhasil Diposting!
                  </h3>
                  <p className="text-xs text-slate-600 leading-relaxed">
                    Semua perubahan menu, tautan, dan tema telah diperbarui dan langsung tayang pada <strong>Halaman Portal Pegawai</strong>.
                  </p>
                </div>

                <div className="p-3 bg-slate-50 border border-slate-200 rounded-xl text-left text-xs space-y-1.5 font-mono">
                  <div className="flex justify-between text-slate-600">
                    <span>Total Tombol Aktif:</span>
                    <span className="font-bold text-slate-900">{liveMenus.filter(m => m.isActive).length} Menu</span>
                  </div>
                  <div className="flex justify-between text-slate-600">
                    <span>Tema Tampilan:</span>
                    <span className="font-bold text-slate-900">{liveProfile.theme.name}</span>
                  </div>
                  <div className="flex justify-between text-slate-600">
                    <span>Status Database Server:</span>
                    <span className={`font-bold flex items-center gap-1 ${publishStatus?.cloudSynced ? 'text-emerald-600' : 'text-amber-600'}`}>
                      {publishStatus?.cloudSynced ? '🟢 Sinkron (Semua Device)' : '🟡 Tersimpan Lokal'}
                    </span>
                  </div>
                  {!publishStatus?.cloudSynced && (
                    <div className="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-[11px] text-amber-800 font-sans leading-relaxed">
                      💡 <strong>Pemberitahuan Status:</strong> Server database sementara tidak dapat dijangkau. Perubahan tersimpan lokal di browser ini. Periksa koneksi jaringan atau gunakan fitur <strong>Backup / Impor JSON</strong> di Dashboard.
                    </div>
                  )}
                  <div className="flex justify-between text-slate-600">
                    <span>Waktu Publikasi:</span>
                    <span className="font-bold text-emerald-600">
                      {new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} WIB
                    </span>
                  </div>
                </div>

                <div className="pt-2 flex flex-col sm:flex-row gap-2.5">
                  <button
                    onClick={() => {
                      setShowPublishSuccessModal(false);
                      setCurrentView('public');
                    }}
                    className="flex-1 py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95"
                  >
                    <span>👁️ Buka Halaman Pegawai</span>
                  </button>
                  <button
                    onClick={() => setShowPublishSuccessModal(false)}
                    className="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors"
                  >
                    Tetap di Dashboard
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Admin Authentication Modal */}
      <AdminAuthModal
        isOpen={isAuthModalOpen}
        onClose={() => setIsAuthModalOpen(false)}
        onSuccess={handleAuthSuccess}
        savedPin={adminPin}
        savedWfaPin={wfaAdminPin}
        savedKebugaranPin={kebugaranAdminPin}
        savedMeetingPin={meetingAdminPin}
        savedMonitoringAllPin={monitoringAllAdminPin}
      />

      {/* QR Code Sharing Modal */}
      <QRCodeModal
        isOpen={isQRModalOpen}
        onClose={() => setIsQRModalOpen(false)}
        profile={profile}
        publicUrl={window.location.href.split('#')[0].split('?')[0]}
      />

      {/* Floating Kebugaran Modal (when triggered directly from Admin Monitoring tab) */}
      <KebugaranModal
        isOpen={isAdminKebugaranModalOpen}
        onClose={() => setIsAdminKebugaranModalOpen(false)}
        onSubmit={handleCreateKebugaranSubmission}
        allSubmissions={kebugaranSubmissions}
        logoUrl={profile.avatarUrl}
      />

      {/* Floating Meeting Attendance Modal (when triggered from Admin tab) */}
      <MeetingAttendanceModal
        isOpen={isMeetingFormModalOpen}
        onClose={() => setIsMeetingFormModalOpen(false)}
        session={activeMeetingSession}
        hasMultipleSessions={meetingSessions.length > 1}
        existingAttendees={meetingAttendees}
        onSubmitAttendance={handleCreateMeetingAttendance}
      />

      {/* Floating Pegawai Custom Form Modal (MOOC ASN Berintegritas, dll) */}
      <PegawaiFormModal
        isOpen={isPegawaiFormModalOpen}
        onClose={() => setIsPegawaiFormModalOpen(false)}
        form={activeFormForModal}
        onSubmit={handleCreatePegawaiResponse}
        existingResponses={pegawaiResponses}
        logoUrl={profile.avatarUrl}
      />
    </div>
  );
}


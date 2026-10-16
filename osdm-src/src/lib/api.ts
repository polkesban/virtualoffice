/**
 * OSDM API Client — REST API for MariaDB Database Server
 * All functions maintain signatures for App.tsx and components.
 */
import {
  MenuItem,
  MicrositeProfile,
  ClickLog,
  ClickStatsSummary,
  WfaSubmission,
  WfaValidationStatus,
  KebugaranSubmission,
  EmployeeRecord,
  MeetingSession,
  MeetingAttendanceRecord,
} from '../types';
import { INITIAL_MENUS, INITIAL_PROFILE, INITIAL_CLICK_LOGS } from '../data/initialData';

const API_BASE = '/api/osdm';

async function apiFetch(path: string, options?: RequestInit) {
  const url = `${API_BASE}${path}`;
  const res = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options?.headers,
    },
    ...options,
  });
  return res;
}

export interface LivePortalData {
  menus: MenuItem[];
  profile: MicrositeProfile;
  lastPublishedAt?: string;
  updatedAt?: any;
}

// ─── Portal ────────────────────────────────────────────────────────────────

/**
 * Subscribe to live portal — polls the REST API every 10 seconds.
 * Returns an unsubscribe function (clears interval).
 */
export function subscribeToLivePortal(
  onUpdate: (data: LivePortalData) => void,
  onError?: (error: any) => void,
  onDocMissing?: () => void
) {
  let active = true;

  const fetchLive = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/portal/live');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (data && Array.isArray(data.menus) && data.menus.length > 0 && data.profile) {
        onUpdate(data as LivePortalData);
      } else if (onDocMissing) {
        onDocMissing();
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  // Initial fetch
  fetchLive();
  // Poll every 10 seconds
  const intervalId = setInterval(fetchLive, 10000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

/**
 * Publish menus + profile to live.
 */
export async function publishLivePortalToCloud(
  menus: MenuItem[],
  profile: MicrositeProfile
): Promise<{ success: boolean; timestamp: string; error?: string }> {
  const now = new Date().toISOString();
  try {
    const res = await apiFetch('/portal/publish', {
      method: 'POST',
      body: JSON.stringify({ menus, profile }),
    });
    const data = await res.json();
    if (data.success) {
      return { success: true, timestamp: data.timestamp || now };
    }
    return { success: false, timestamp: now, error: data.error || 'Gagal menyimpan' };
  } catch (err: any) {
    return { success: false, timestamp: now, error: err?.message || 'Network error' };
  }
}

/**
 * Get live portal once.
 */
export async function getLivePortalOnce(): Promise<LivePortalData | null> {
  try {
    const res = await apiFetch('/portal/live');
    if (!res.ok) return null;
    const data = await res.json();
    if (data && Array.isArray(data.menus) && data.profile) {
      return data as LivePortalData;
    }
  } catch (e) {
    console.warn('Failed to fetch portal:', e);
  }
  return null;
}

// ─── Admin Security / PIN ──────────────────────────────────────────────────

export function subscribeToAdminSecurity(
  onPinUpdate: (data: {
    pin: string;
    wfaPin?: string;
    kebugaranPin?: string;
    meetingPin?: string;
    monitoringAllPin?: string;
  }) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchPin = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/security/pin');
      if (!res.ok) return;
      const data = await res.json();
      if (data?.pin) {
        onPinUpdate({
          pin: data.pin,
          wfaPin: data.wfa_pin,
          kebugaranPin: data.kebugaran_pin,
          meetingPin: data.meeting_pin,
          monitoringAllPin: data.monitoring_all_pin,
        });
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchPin();
  const intervalId = setInterval(fetchPin, 30000); // Poll every 30s

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function saveAdminPinToCloud(newPin: string): Promise<boolean> {
  try {
    const res = await apiFetch('/security/pin', {
      method: 'POST',
      body: JSON.stringify({ pin: newPin.trim() }),
    });
    const data = await res.json();
    return data.success === true;
  } catch {
    return false;
  }
}

export async function saveWfaAdminPinToCloud(newPin: string): Promise<boolean> {
  try {
    const res = await apiFetch('/security/wfa-pin', {
      method: 'POST',
      body: JSON.stringify({ pin: newPin.trim() }),
    });
    const data = await res.json();
    return data.success === true;
  } catch {
    return false;
  }
}

export async function saveKebugaranAdminPinToCloud(newPin: string): Promise<boolean> {
  try {
    const res = await apiFetch('/security/kebugaran-pin', {
      method: 'POST',
      body: JSON.stringify({ pin: newPin.trim() }),
    });
    const data = await res.json();
    return data.success === true;
  } catch {
    return false;
  }
}

export async function saveMeetingAdminPinToCloud(newPin: string): Promise<boolean> {
  try {
    const res = await apiFetch('/security/meeting-pin', {
      method: 'POST',
      body: JSON.stringify({ pin: newPin.trim() }),
    });
    const data = await res.json();
    return data.success === true;
  } catch {
    return false;
  }
}

export async function saveMonitoringAllAdminPinToCloud(newPin: string): Promise<boolean> {
  try {
    const res = await apiFetch('/security/monitoring-all-pin', {
      method: 'POST',
      body: JSON.stringify({ pin: newPin.trim() }),
    });
    const data = await res.json();
    return data.success === true;
  } catch {
    return false;
  }
}

// ─── Admin Draft ───────────────────────────────────────────────────────────

export function subscribeToAdminDraft(
  onDraftUpdate: (data: { menus: MenuItem[]; profile: MicrositeProfile }) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchDraft = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/portal/draft');
      if (!res.ok) return;
      const data = await res.json();
      if (data && Array.isArray(data.menus) && data.profile) {
        onDraftUpdate({ menus: data.menus, profile: data.profile });
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchDraft();
  const intervalId = setInterval(fetchDraft, 15000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function saveAdminDraftToCloud(
  menus: MenuItem[],
  profile: MicrositeProfile
): Promise<boolean> {
  try {
    const res = await apiFetch('/portal/draft', {
      method: 'POST',
      body: JSON.stringify({ menus, profile }),
    });
    const data = await res.json();
    return data.success === true;
  } catch {
    return false;
  }
}

// ─── Click Logs ────────────────────────────────────────────────────────────

export async function logClickToCloud(log: ClickLog): Promise<void> {
  try {
    await apiFetch('/click-logs', {
      method: 'POST',
      body: JSON.stringify(log),
    });
  } catch {
    // Silent fail — analytics shouldn't block UX
  }
}

export async function fetchClickLogs(): Promise<{ logs: ClickLog[]; stats?: ClickStatsSummary }> {
  try {
    const res = await apiFetch('/click-logs');
    if (!res.ok) return { logs: [] };
    const data = await res.json();
    if (Array.isArray(data)) {
      return { logs: data };
    }
    return {
      logs: Array.isArray(data.logs) ? data.logs : [],
      stats: data.stats,
    };
  } catch {
    return { logs: [] };
  }
}

export async function deleteClickLogsFromCloud(): Promise<boolean> {
  try {
    const res = await apiFetch('/click-logs', {
      method: 'DELETE',
    });
    return res.ok;
  } catch {
    return false;
  }
}

export function subscribeToClickLogs(
  onLogsUpdate: (logs: ClickLog[], stats?: ClickStatsSummary) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchLogs = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/click-logs');
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data)) {
        onLogsUpdate(data);
      } else if (data && typeof data === 'object') {
        const logs = Array.isArray(data.logs) ? data.logs : [];
        const stats = data.stats as ClickStatsSummary | undefined;
        onLogsUpdate(logs, stats);
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchLogs();
  // Fast polling interval (5 seconds) for snappy real-time analytics
  const intervalId = setInterval(fetchLogs, 5000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

// ─── WFA Submissions ───────────────────────────────────────────────────────

export function subscribeToWfaSubmissions(
  onUpdate: (submissions: WfaSubmission[]) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchWfa = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/wfa');
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data)) {
        onUpdate(data);
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchWfa();
  const intervalId = setInterval(fetchWfa, 10000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function createWfaSubmissionInCloud(
  submissionData: Omit<WfaSubmission, 'id' | 'status' | 'createdAt'>
): Promise<{ success: boolean; submission?: WfaSubmission; error?: string }> {
  try {
    const res = await apiFetch('/wfa', {
      method: 'POST',
      body: JSON.stringify(submissionData),
    });
    const data = await res.json();
    if (data.success && data.submission) {
      return { success: true, submission: data.submission };
    }
    return { success: false, error: data.error || 'Gagal menyimpan' };
  } catch (err: any) {
    return { success: false, error: err?.message || 'Network error' };
  }
}

export async function updateWfaStatusInCloud(
  submissionId: string,
  status: WfaValidationStatus,
  catatanPengelola?: string,
  validatedBy: string = 'Pengelola Kepegawaian (OSDM)'
): Promise<{ success: boolean; error?: string }> {
  try {
    const res = await apiFetch(`/wfa/${submissionId}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status, catatanPengelola, validatedBy }),
    });
    const data = await res.json();
    return { success: data.success === true };
  } catch (err: any) {
    return { success: false, error: err?.message };
  }
}

export async function updateWfaLinkSuratTugasInCloud(
  submissionId: string,
  linkSuratTugas: string
): Promise<{ success: boolean; linkSuratTugas?: string; error?: string }> {
  try {
    const res = await apiFetch(`/wfa/${submissionId}/link`, {
      method: 'PUT',
      body: JSON.stringify({ linkSuratTugas }),
    });
    const data = await res.json();
    if (!res.ok || data.success === false) {
      return { success: false, error: data.error || 'Gagal memperbarui link surat tugas' };
    }
    return { success: true, linkSuratTugas: data.linkSuratTugas || linkSuratTugas };
  } catch (err: any) {
    return { success: false, error: err?.message || 'Koneksi bermasalah' };
  }
}

export async function deleteWfaSubmissionInCloud(
  submissionId: string
): Promise<{ success: boolean; error?: string }> {
  try {
    const res = await apiFetch(`/wfa/${submissionId}`, { method: 'DELETE' });
    const data = await res.json();
    return { success: data.success === true };
  } catch {
    return { success: true }; // Optimistic
  }
}

// ─── Kebugaran Submissions ─────────────────────────────────────────────────

export function subscribeToKebugaranSubmissions(
  onUpdate: (submissions: KebugaranSubmission[]) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchKebugaran = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/kebugaran');
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data)) {
        onUpdate(data);
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchKebugaran();
  const intervalId = setInterval(fetchKebugaran, 10000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function createKebugaranSubmissionInCloud(
  submissionData: Omit<KebugaranSubmission, 'id' | 'createdAt'>
): Promise<{ success: boolean; submission?: KebugaranSubmission; error?: string }> {
  try {
    const res = await apiFetch('/kebugaran', {
      method: 'POST',
      body: JSON.stringify(submissionData),
    });
    const data = await res.json();
    if (data.success && data.submission) {
      return { success: true, submission: data.submission };
    }
    return { success: false, error: data.error || 'Gagal menyimpan' };
  } catch (err: any) {
    return { success: false, error: err?.message || 'Network error' };
  }
}

export async function deleteKebugaranSubmissionInCloud(
  submissionId: string
): Promise<{ success: boolean; error?: string }> {
  try {
    const res = await apiFetch(`/kebugaran/${submissionId}`, { method: 'DELETE' });
    const data = await res.json();
    return { success: data.success === true };
  } catch {
    return { success: true }; // Optimistic
  }
}

// ─── Kebugaran Seeding (no-op, data now lives in MariaDB) ──────────────────

export async function seedKebugaranSubmissionsToCloud(
  _submissions?: KebugaranSubmission[]
): Promise<{ success: boolean; count: number; error?: string }> {
  // No-op: data is already seeded via Laravel seeder
  return { success: true, count: 0 };
}

// ─── Quota helpers (no-op) ─────────────────────────────────────────────────

export function handleQuotaError(_err: any): boolean {
  return false;
}

// ─── Employee Management (MySQL Cloud Database) ───────────────────────────

export async function fetchEmployeesFromCloud(): Promise<EmployeeRecord[]> {
  try {
    const res = await apiFetch('/employees');
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  } catch {
    return [];
  }
}

export function subscribeToEmployees(
  onUpdate: (employees: EmployeeRecord[]) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchEmployees = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/employees');
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        onUpdate(data);
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchEmployees();
  const intervalId = setInterval(fetchEmployees, 15000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function createEmployeeInCloud(
  emp: EmployeeRecord
): Promise<{ success: boolean; employee?: EmployeeRecord; error?: string }> {
  try {
    const res = await apiFetch('/employees', {
      method: 'POST',
      body: JSON.stringify(emp),
    });
    const data = await res.json();
    if (data.success && data.employee) {
      return { success: true, employee: data.employee };
    }
    return { success: false, error: data.error || 'Gagal menambahkan pegawai ke server database.' };
  } catch (err: any) {
    return { success: false, error: err?.message || 'Gagal terhubung ke server database.' };
  }
}

export async function updateEmployeeInCloud(
  nip: string,
  patch: Partial<EmployeeRecord>
): Promise<{ success: boolean; employee?: EmployeeRecord; error?: string }> {
  try {
    const cleanNip = nip.replace(/[\s.-]/g, '').trim();
    const res = await apiFetch(`/employees/${encodeURIComponent(cleanNip)}`, {
      method: 'PUT',
      body: JSON.stringify(patch),
    });
    const data = await res.json();
    if (data.success && data.employee) {
      return { success: true, employee: data.employee };
    }
    return { success: false, error: data.error || 'Gagal memperbarui pegawai di server database.' };
  } catch (err: any) {
    return { success: false, error: err?.message || 'Gagal terhubung ke server database.' };
  }
}

export async function deleteEmployeeInCloud(
  nip: string
): Promise<{ success: boolean; error?: string }> {
  try {
    const cleanNip = nip.replace(/[\s.-]/g, '').trim();
    const res = await apiFetch(`/employees/${encodeURIComponent(cleanNip)}`, {
      method: 'DELETE',
    });
    const data = await res.json();
    if (data.success) {
      return { success: true };
    }
    return { success: false, error: data.error || 'Gagal menghapus pegawai dari server database.' };
  } catch (err: any) {
    return { success: false, error: err?.message || 'Gagal terhubung ke server database.' };
  }
}

// ─── Meeting Session & Attendees (Real-time Cloud Sync) ─────────────────────

export function subscribeToMeetingSessions(
  onUpdate: (sessions: MeetingSession[]) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchSessions = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/meeting/sessions');
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data)) {
        onUpdate(data as MeetingSession[]);
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchSessions();
  const intervalId = setInterval(fetchSessions, 4000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export function subscribeToMeetingSession(
  onUpdate: (session: MeetingSession) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchSession = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/meeting/session');
      if (!res.ok) return;
      const data = await res.json();
      if (data && typeof data === 'object' && data.id) {
        onUpdate(data as MeetingSession);
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchSession();
  const intervalId = setInterval(fetchSession, 4000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function saveMeetingSessionToCloud(
  session: MeetingSession
): Promise<{ success: boolean; error?: string }> {
  try {
    const res = await apiFetch('/meeting/sessions', {
      method: 'POST',
      body: JSON.stringify(session),
    });
    const data = await res.json();
    return { success: !!data.success };
  } catch (err: any) {
    return { success: false, error: err?.message };
  }
}

export async function saveAllMeetingSessionsToCloud(
  sessions: MeetingSession[]
): Promise<{ success: boolean; error?: string }> {
  try {
    const res = await apiFetch('/meeting/sessions', {
      method: 'POST',
      body: JSON.stringify({ sessions }),
    });
    const data = await res.json();
    return { success: !!data.success };
  } catch (err: any) {
    return { success: false, error: err?.message };
  }
}

export async function deleteMeetingSessionFromCloud(
  id: string
): Promise<{ success: boolean; error?: string }> {
  try {
    const res = await apiFetch(`/meeting/sessions/${id}`, {
      method: 'DELETE',
    });
    const data = await res.json();
    return { success: !!data.success };
  } catch (err: any) {
    return { success: false, error: err?.message };
  }
}

export function subscribeToMeetingAttendees(
  onUpdate: (attendees: MeetingAttendanceRecord[]) => void,
  onError?: (error: any) => void
) {
  let active = true;

  const fetchAttendees = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/meeting/attendees');
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data)) {
        onUpdate(data as MeetingAttendanceRecord[]);
      }
    } catch (err) {
      if (onError) onError(err);
    }
  };

  fetchAttendees();
  const intervalId = setInterval(fetchAttendees, 4000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function createMeetingAttendeeInCloud(
  attendee: Omit<MeetingAttendanceRecord, 'id' | 'timestamp'> & { id?: string; timestamp?: string }
): Promise<{ success: boolean; attendee?: MeetingAttendanceRecord; error?: string }> {
  try {
    const res = await apiFetch('/meeting/attendees', {
      method: 'POST',
      body: JSON.stringify(attendee),
    });
    const data = await res.json();
    if (data.success && data.attendee) {
      return { success: true, attendee: data.attendee };
    }
    return { success: false, error: data.error || 'Gagal menyimpan' };
  } catch (err: any) {
    return { success: false, error: err?.message };
  }
}

export async function deleteMeetingAttendeeInCloud(
  id: string
): Promise<{ success: boolean }> {
  try {
    const res = await apiFetch(`/meeting/attendees/${id}`, {
      method: 'DELETE',
    });
    const data = await res.json();
    return { success: !!data.success };
  } catch {
    return { success: false };
  }
}

export function subscribeToMeetingAgendas(
  onUpdate: (agendas: string[]) => void
) {
  let active = true;

  const fetchAgendas = async () => {
    if (!active) return;
    try {
      const res = await apiFetch('/meeting/agendas');
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data)) {
        onUpdate(data);
      }
    } catch {}
  };

  fetchAgendas();
  const intervalId = setInterval(fetchAgendas, 6000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export async function saveMeetingAgendaToCloud(agenda: string): Promise<boolean> {
  try {
    const res = await apiFetch('/meeting/agendas', {
      method: 'POST',
      body: JSON.stringify({ agenda }),
    });
    return res.ok;
  } catch {
    return false;
  }
}

export async function deleteMeetingAgendaFromCloud(agenda: string): Promise<boolean> {
  try {
    const res = await apiFetch('/meeting/agendas', {
      method: 'DELETE',
      body: JSON.stringify({ agenda }),
    });
    return res.ok;
  } catch {
    return false;
  }
}

// ─── Pegawai Custom Forms & Responses ────────────────────────────────────────

export async function fetchPegawaiFormsFromCloud(): Promise<any[] | null> {
  try {
    const res = await apiFetch('/pegawai/forms');
    if (!res.ok) {
      console.warn(`[api] fetchPegawaiForms HTTP ${res.status}`);
      return null;
    }
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  } catch (err) {
    console.warn('[api] fetchPegawaiForms network error:', err);
    return null;
  }
}

export async function savePegawaiFormToCloud(form: any): Promise<boolean> {
  try {
    const res = await apiFetch('/pegawai/forms', {
      method: 'POST',
      body: JSON.stringify(form),
    });
    return res.ok;
  } catch {
    return false;
  }
}

export async function saveAllPegawaiFormsToCloud(forms: any[]): Promise<boolean> {
  try {
    const res = await apiFetch('/pegawai/forms', {
      method: 'POST',
      body: JSON.stringify({ forms }),
    });
    return res.ok;
  } catch {
    return false;
  }
}

export async function deletePegawaiFormFromCloud(id: string): Promise<boolean> {
  try {
    const res = await apiFetch(`/pegawai/forms/${id}`, {
      method: 'DELETE',
    });
    return res.ok;
  } catch {
    return false;
  }
}

export async function fetchPegawaiFormResponsesFromCloud(formId?: string): Promise<any[] | null> {
  try {
    const url = formId ? `/pegawai/form-responses?formId=${encodeURIComponent(formId)}` : '/pegawai/form-responses';
    const res = await apiFetch(url);
    if (!res.ok) {
      console.warn(`[api] fetchPegawaiFormResponses HTTP ${res.status}`);
      return null;
    }
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  } catch (err) {
    console.warn('[api] fetchPegawaiFormResponses network error:', err);
    return null;
  }
}

export async function savePegawaiFormResponseToCloud(response: any): Promise<{ success: boolean; error?: string }> {
  try {
    const res = await apiFetch('/pegawai/form-responses', {
      method: 'POST',
      body: JSON.stringify(response),
    });
    const data = await res.json();
    return { success: !!data.success, error: data.error };
  } catch (err: any) {
    return { success: false, error: err?.message };
  }
}

export async function deletePegawaiFormResponseFromCloud(id: string): Promise<boolean> {
  try {
    const res = await apiFetch(`/pegawai/form-responses/${id}`, {
      method: 'DELETE',
    });
    return res.ok;
  } catch {
    return false;
  }
}

export function subscribeToPegawaiForms(
  onUpdate: (forms: any[]) => void,
  onError?: (err: Error) => void
): () => void {
  let active = true;

  const fetchForms = async () => {
    if (!active) return;
    if (typeof document !== 'undefined' && document.hidden) return;
    try {
      const data = await fetchPegawaiFormsFromCloud();
      if (active && data !== null && Array.isArray(data)) {
        onUpdate(data);
      }
    } catch (err: any) {
      if (active && onError) onError(err);
    }
  };

  fetchForms();
  const intervalId = setInterval(fetchForms, 15000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

export function subscribeToPegawaiFormResponses(
  onUpdate: (responses: any[]) => void,
  onError?: (err: Error) => void
): () => void {
  let active = true;

  const fetchResponses = async () => {
    if (!active) return;
    if (typeof document !== 'undefined' && document.hidden) return;
    try {
      const data = await fetchPegawaiFormResponsesFromCloud();
      if (active && data !== null && Array.isArray(data)) {
        onUpdate(data);
      }
    } catch (err: any) {
      if (active && onError) onError(err);
    }
  };

  fetchResponses();
  const intervalId = setInterval(fetchResponses, 10000);

  return () => {
    active = false;
    clearInterval(intervalId);
  };
}

// Dummy db export for backward compatibility if anything references it
export const db = null;



import { MeetingSession, MeetingAttendanceRecord } from '../types';
import { deleteMeetingAgendaFromCloud } from '../lib/api';

export const LOCAL_STORAGE_SAVED_AGENDAS_KEY = 'direct_menu_meeting_saved_agendas_v1';
export const LOCAL_STORAGE_DELETED_AGENDAS_KEY = 'direct_menu_meeting_deleted_agendas_v1';

export const LEGACY_MOCK_AGENDAS = new Set([
  'evaluasi capaian kinerja triwulan iii (skp)',
  'verifikasi presensi wfa',
  'sosialisasi tes kebugaran jasmani pegawai',
  'evaluasi capaian kinerja triwulan iii',
]);

// Initial master agendas
const DEFAULT_MASTER_AGENDAS: string[] = [];

/**
 * Mendapatkan daftar agenda yang telah dihapus oleh pengguna.
 */
export function getDeletedAgendas(): Set<string> {
  try {
    const raw = localStorage.getItem(LOCAL_STORAGE_DELETED_AGENDAS_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        return new Set(parsed.map((s) => String(s).trim().toLowerCase()));
      }
    }
  } catch {}
  return new Set();
}

/**
 * Mendapatkan daftar agenda yang tersimpan permanen di localStorage.
 */
export function getSavedAgendas(): string[] {
  const deleted = getDeletedAgendas();
  try {
    const raw = localStorage.getItem(LOCAL_STORAGE_SAVED_AGENDAS_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        return parsed
          .map((s) => String(s).trim())
          .filter(
            (s) =>
              s &&
              !deleted.has(s.toLowerCase()) &&
              !LEGACY_MOCK_AGENDAS.has(s.toLowerCase())
          );
      }
    }
  } catch (err) {
    console.warn('Error reading saved agendas:', err);
  }
  return [];
}

/**
 * Menyimpan agenda baru ke daftar riwayat permanen di localStorage.
 */
function notifyAgendasUpdated() {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('meeting_agendas_updated'));
  }
}

/**
 * Menyimpan agenda baru ke daftar riwayat permanen di localStorage.
 */
export function saveAgendaPermanently(agendaName: string): string[] {
  const clean = (agendaName || '').trim();
  if (!clean || clean.length < 2) return getSavedAgendas();

  // Jika pernah dihapus sebelumnya, pulihkan dari daftar deleted
  const deleted = getDeletedAgendas();
  if (deleted.has(clean.toLowerCase())) {
    deleted.delete(clean.toLowerCase());
    try {
      localStorage.setItem(LOCAL_STORAGE_DELETED_AGENDAS_KEY, JSON.stringify(Array.from(deleted)));
    } catch {}
  }

  const current = getSavedAgendas();
  const exists = current.some((item) => item.toLowerCase() === clean.toLowerCase());

  if (!exists) {
    const updated = [clean, ...current];
    try {
      localStorage.setItem(LOCAL_STORAGE_SAVED_AGENDAS_KEY, JSON.stringify(updated));
      notifyAgendasUpdated();
    } catch (err) {
      console.warn('Error saving agenda permanently:', err);
    }
    return updated;
  }

  return current;
}

/**
 * Menyelaraskan riwayat agenda di localStorage dengan database cloud server.
 * Mencegah timbulnya agenda lama / zombie agendas di perangkat lain (seperti handphone).
 */
export function syncSavedAgendasWithCloud(cloudAgendas: string[]): string[] {
  try {
    const deleted = getDeletedAgendas();
    const cleanCloud = (cloudAgendas || [])
      .map((s) => String(s).trim())
      .filter(
        (s) =>
          s &&
          s.length >= 2 &&
          !deleted.has(s.toLowerCase()) &&
          !LEGACY_MOCK_AGENDAS.has(s.toLowerCase())
      );

    const prevRaw = localStorage.getItem(LOCAL_STORAGE_SAVED_AGENDAS_KEY) || '[]';
    const nextRaw = JSON.stringify(cleanCloud);
    if (prevRaw !== nextRaw) {
      localStorage.setItem(LOCAL_STORAGE_SAVED_AGENDAS_KEY, nextRaw);
      notifyAgendasUpdated();
    }
    return cleanCloud;
  } catch (err) {
    console.warn('Error syncing cloud agendas with local storage:', err);
    return getSavedAgendas();
  }
}

/**
 * Menghapus agenda dari riwayat tersimpan permanen & database server.
 */
export function removeSavedAgenda(agendaName: string): string[] {
  const clean = (agendaName || '').trim();
  if (!clean) return getSavedAgendas();
  const lower = clean.toLowerCase();

  // 1. Masukkan ke blacklist deleted agar tidak muncul lagi
  const deleted = getDeletedAgendas();
  deleted.add(lower);
  try {
    localStorage.setItem(LOCAL_STORAGE_DELETED_AGENDAS_KEY, JSON.stringify(Array.from(deleted)));
  } catch {}

  // 2. Hapus dari saved agendas
  const current = getSavedAgendas();
  const updated = current.filter((item) => item.trim().toLowerCase() !== lower);
  try {
    localStorage.setItem(LOCAL_STORAGE_SAVED_AGENDAS_KEY, JSON.stringify(updated));
    notifyAgendasUpdated();
  } catch (err) {
    console.warn('Error removing agenda:', err);
  }

  // 3. Hapus dari cloud database server
  deleteMeetingAgendaFromCloud(clean).catch(() => {});

  return updated;
}

/**
 * Mendapatkan daftar seluruh agenda permanen yang tersedia:
 * Menggabungkan agenda tersimpan di storage, agenda sesi saat ini, dan seluruh agenda yang pernah dihadiri peserta.
 */
export function getAllPermanentAgendas(
  session?: MeetingSession | null,
  attendees?: MeetingAttendanceRecord[],
  allSessions?: MeetingSession[]
): string[] {
  const result: string[] = [];
  const seen = new Set<string>();
  const deleted = getDeletedAgendas();

  const addAgenda = (name?: string | null) => {
    if (!name) return;
    const clean = name.trim();
    if (!clean || clean.length < 2) return;
    const key = clean.toLowerCase();
    if (deleted.has(key)) return; // Lewati agenda yang telah dihapus
    if (LEGACY_MOCK_AGENDAS.has(key)) return; // Lewati agenda dummy masa lalu

    if (!seen.has(key)) {
      seen.add(key);
      result.push(clean);
    }
  };

  // 1. Tambahkan agenda sesi aktif saat ini (jika tidak dihapus)
  if (session?.agenda) {
    addAgenda(session.agenda);
  }
  if (session?.agendas && Array.isArray(session.agendas)) {
    session.agendas.forEach(addAgenda);
  }

  // 2. Tambahkan agenda dari seluruh sesi aktif/tersedia (allSessions)
  if (allSessions && Array.isArray(allSessions)) {
    allSessions.forEach((s) => {
      if (s.agenda) addAgenda(s.agenda);
      if (Array.isArray(s.agendas)) s.agendas.forEach(addAgenda);
    });
  }

  // 3. Tambahkan agenda yang tercatat pada peserta hadir (jika tidak dihapus)
  if (attendees && Array.isArray(attendees)) {
    attendees.forEach((att) => {
      if (Array.isArray(att.agendas)) {
        att.agendas.forEach(addAgenda);
      }
    });
  }

  // 4. Tambahkan agenda dari riwayat tersimpan permanen
  const saved = getSavedAgendas();
  saved.forEach(addAgenda);

  return result;
}

/**
 * Mendapatkan daftar agenda terperinci dari sesi rapat.
 */
export function getMeetingAgendas(session: MeetingSession): string[] {
  if (session.agendas && session.agendas.length > 0) {
    return session.agendas.map((a) => a.trim()).filter(Boolean);
  }

  if (!session.agenda) return [];

  // Jika input hanya 1 baris agenda, langsung kembalikan agenda tersebut
  const trimmed = session.agenda.trim();
  if (!trimmed.includes('\n') && !trimmed.includes(';')) {
    return [trimmed];
  }

  // Auto-split fallback jika ada format multiline masa lalu
  const items = trimmed
    .split(/[\n;]+/)
    .map((s) => s.trim().replace(/^(dan\s+|and\s+|\d+[\.\)]\s*)/i, '').trim())
    .filter((s) => s.length > 2);

  return items.length > 0 ? items : [trimmed];
}

/**
 * Memeriksa apakah agenda tertentu termasuk dalam daftar agenda yang dihapus.
 */
export function isAgendaDeleted(agendaName?: string | null): boolean {
  if (!agendaName) return false;
  const deleted = getDeletedAgendas();
  return deleted.has(agendaName.trim().toLowerCase());
}

/**
 * Filter peserta rapat berdasarkan agenda yang dipilih.
 * Jika agendaName adalah undefined, null, 'ALL', atau 'Semua Agenda', semua peserta dikembalikan.
 * Jika agendaName kosong string "", tidak ada peserta yang dikembalikan (karena tidak ada agenda terpilih).
 */
export function filterAttendeesByAgenda(
  attendees: MeetingAttendanceRecord[],
  agendaName?: string | null
): MeetingAttendanceRecord[] {
  if (agendaName === undefined || agendaName === null || agendaName === 'ALL' || agendaName === 'Semua Agenda') {
    return attendees;
  }

  const cleanAgenda = (agendaName || '').trim();
  if (!cleanAgenda) {
    return [];
  }

  const targetLower = cleanAgenda.toLowerCase();

  return attendees.filter((att) => {
    if (!att.agendas || att.agendas.length === 0) {
      const legacyAgenda = (att as any).agenda;
      return legacyAgenda ? legacyAgenda.trim().toLowerCase() === targetLower : false;
    }
    return att.agendas.some((ag) => ag.trim().toLowerCase() === targetLower);
  });
}

/**
 * Menghitung jumlah kehadiran per agenda
 */
export function countAttendeesForAgenda(
  attendees: MeetingAttendanceRecord[],
  agendaName: string
): number {
  return filterAttendeesByAgenda(attendees, agendaName).length;
}

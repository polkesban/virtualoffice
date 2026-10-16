import { MeetingSession, MeetingAttendanceRecord } from '../types';

export const INITIAL_MEETING_SESSION: MeetingSession = {
  id: 'meet-session-current',
  title: 'Rapat Koordinasi dan Evaluasi Capaian Sasaran Kinerja Pegawai (SKP) Triwulan III',
  date: '2026-09-17',
  timeStart: '09:00',
  timeEnd: '12:00',
  location: 'Ruang Rapat Utama Gedung Direktorat Lt. 2 Poltekkes Kemenkes Bandung',
  agenda: 'Evaluasi Capaian Kinerja Triwulan III (SKP), Verifikasi Presensi WFA, dan Sosialisasi Tes Kebugaran Jasmani Pegawai',
  agendas: [
    'Evaluasi Capaian Kinerja Triwulan III (SKP)',
    'Verifikasi Presensi WFA',
    'Sosialisasi Tes Kebugaran Jasmani Pegawai',
  ],
  pimpinanRapat: 'Dr. H. Ahmad Sudrajat, S.Kp., M.Kes.',
  jabatanPimpinan: 'Ketua Tim Kerja OSDM Poltekkes Kemenkes Bandung',
  notulis: 'Femmy Anissa Ayuningtias',
  isActive: true,
  createdAt: new Date().toISOString(),
};

// Simple sample signature SVG data URL helper for demo preview
const createSampleSignatureUrl = (initials: string): string => {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="180" height="60" viewBox="0 0 180 60">
    <path d="M 20 40 Q 35 10 50 35 T 80 25 Q 110 5 130 38 T 165 20" fill="none" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M 30 45 Q 60 50 110 42" fill="none" stroke="#0f172a" stroke-width="1.8" stroke-linecap="round"/>
    <text x="140" y="52" font-family="sans-serif" font-size="11" font-weight="bold" fill="#64748b">${initials}</text>
  </svg>`;
  return `data:image/svg+xml;base64,${btoa(svg)}`;
};

export const INITIAL_MEETING_ATTENDANCE: MeetingAttendanceRecord[] = [];

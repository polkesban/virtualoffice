import { MenuItem, MicrositeProfile, ClickLog, ThemeConfig } from '../types';

export const THEME_PRESETS: ThemeConfig[] = [
  {
    id: 'poltekkes-emerald',
    name: 'Poltekkes Kemenkes Emerald (Resmi)',
    bgType: 'mesh',
    primaryBg: '#042f2e',
    secondaryBg: '#0f766e',
    accentColor: '#10b981',
    textColor: '#ffffff',
    subtextColor: '#ccfbf1',
    cardRadius: 'rounded-xl',
    cardGlassEffect: true,
    fontFamily: 'sans',
    layoutStyle: 'stacked',
  },
  {
    id: 'corporate-navy',
    name: 'Corporate Navy & Blue',
    bgType: 'gradient',
    primaryBg: '#0f172a',
    secondaryBg: '#1e3a8a',
    accentColor: '#38bdf8',
    textColor: '#ffffff',
    subtextColor: '#cbd5e1',
    cardRadius: 'rounded-xl',
    cardGlassEffect: true,
    fontFamily: 'sans',
    layoutStyle: 'stacked',
  },
  {
    id: 'midnight-emerald',
    name: 'Midnight Teal & Emerald',
    bgType: 'mesh',
    primaryBg: '#090d16',
    secondaryBg: '#064e3b',
    accentColor: '#10b981',
    textColor: '#ffffff',
    subtextColor: '#94a3b8',
    cardRadius: 'rounded-2xl',
    cardGlassEffect: true,
    fontFamily: 'outfit',
    layoutStyle: 'stacked',
  },
  {
    id: 'cyber-indigo',
    name: 'Executive Indigo & Slate',
    bgType: 'gradient',
    primaryBg: '#0f172a',
    secondaryBg: '#4f46e5',
    accentColor: '#818cf8',
    textColor: '#ffffff',
    subtextColor: '#cbd5e1',
    cardRadius: 'rounded-xl',
    cardGlassEffect: true,
    fontFamily: 'sans',
    layoutStyle: 'stacked',
  },
  {
    id: 'luxe-clean',
    name: 'Minimal Modern Charcoal',
    bgType: 'dark-neon',
    primaryBg: '#0a0a0a',
    secondaryBg: '#171717',
    accentColor: '#38bdf8',
    textColor: '#f8fafc',
    subtextColor: '#94a3b8',
    cardRadius: 'rounded-2xl',
    cardGlassEffect: true,
    fontFamily: 'sans',
    layoutStyle: 'stacked',
  },
  {
    id: 'clean-bento',
    name: 'Enterprise Bento Layout',
    bgType: 'mesh',
    primaryBg: '#0f172a',
    secondaryBg: '#334155',
    accentColor: '#60a5fa',
    textColor: '#ffffff',
    subtextColor: '#94a3b8',
    cardRadius: 'rounded-xl',
    cardGlassEffect: true,
    fontFamily: 'sans',
    layoutStyle: 'bento',
  }
];

export const INITIAL_PROFILE: MicrositeProfile = {
  name: 'Portal Layanan Pegawai',
  tagline: 'PUSAT AKSES LAYANAN INTERNAL',
  bio: 'Portal satu pintu bagi seluruh pegawai Poltekkes Kemenkes Bandung',
  avatarUrl: 'https://poltekkesbandung.ac.id/wp-content/uploads/2026/05/cropped-logo-transparan-2.png',
  logoShape: 'landscape',
  logoHeight: 74,
  logoBackground: 'glass',
  coverUrl: 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1200&auto=format&fit=crop&q=80',
  isVerified: true,
  location: 'Jl. Padjadjaran No. 56 Bandung',
  openingHours: 'Layanan : 08.00 - 16.00 WIB',
  socialLinks: [
    { id: '1', platform: 'whatsapp', url: 'https://wa.me/6281234567890?text=Halo%20Admin%20OSDM%20Poltekkes%20Bandung', label: 'Helpdesk WhatsApp OSDM', isActive: true },
    { id: '2', platform: 'email', url: 'mailto:kepegawaian@poltekkesbandung.ac.id', label: 'Email Kepegawaian', isActive: true },
    { id: '3', platform: 'phone', url: 'tel:+62224231627', label: 'Telepon Kantor (022-4231627)', isActive: true },
    { id: '4', platform: 'website', url: 'https://www.poltekkesbandung.ac.id', label: 'Portal Utama Poltekkes Bandung', isActive: true },
    { id: '5', platform: 'maps', url: 'https://maps.google.com/?q=Politeknik+Kesehatan+Kemenkes+Bandung+Jl+Pajajaran+No+56', label: 'Lokasi Kampus Direktorat', isActive: true }
  ],
  footerBadgeText: 'Portal Resmi OSDM',
  footerCopyright: '© 2026 OSDM Poltekkes Kemenkes Bandung. Hak Cipta Dilindungi.',
  tabTitle: 'Portal OSDM Polkesban',
  faviconUrl: '',
  theme: THEME_PRESETS[0],
  osdmContactWa: '082119712525'
};

export const INITIAL_MENUS: MenuItem[] = [
  {
    id: 'menu-dashboard-pegawai',
    title: 'Dashboard Pegawai',
    subtitle: 'Realtime Seluruh Pegawai Poltekkes Kemenkes Bandung',
    url: 'https://datastudio.google.com/u/0/reporting/2b27a6ed-04d0-4853-b7d3-fa96df19ab4e/page/LL2xC',
    type: 'link',
    size: 'medium',
    bgColor: '#047857',
    textColor: '#ffffff',
    borderColor: '#10b981',
    isGradient: true,
    gradientTo: '#065f46',
    gradientAngle: 135,
    iconName: '🏢',
    badgeText: '',
    isActive: true,
    order: 1,
    animation: 'none',
    clickCount: 850,
    category: 'Kepegawaian & HR',
    openInNewTab: true
  },
  {
    id: 'menu-1788927778299',
    title: 'Database Pegawai',
    subtitle: 'Data Seluruh Pegawai Poltekkes Kemenkes Bandung',
    url: 'https://docs.google.com/spreadsheets/d/1sk1OEIpdK13uHgsUZc6VnMzc1HyNcA6Ryb_tA22ZDzE/edit?usp=sharing',
    type: 'link',
    size: 'compact',
    bgColor: '#0d9488',
    textColor: '#ffffff',
    borderColor: '#14b8a6',
    isGradient: true,
    gradientTo: '#0f766e',
    gradientAngle: 135,
    iconName: '🎫',
    badgeText: '',
    badgeBgColor: '#3b82f6',
    badgeTextColor: '#ffffff',
    isActive: true,
    order: 2,
    animation: 'none',
    clickCount: 710,
    category: 'Kepegawaian & HR',
    openInNewTab: true,
    isProtected: true,
    pinCode: '132525'
  },
  {
    id: 'menu-1788927883724',
    title: 'Cuti Pegawai',
    subtitle: 'Ajukan Cuti melalui Portal e-Office',
    url: 'https://portal-eoffice.kemkes.go.id',
    type: 'link',
    size: 'compact',
    bgColor: '#0d9488',
    textColor: '#ffffff',
    borderColor: '#14b8a6',
    isGradient: true,
    gradientTo: '#0f766e',
    gradientAngle: 135,
    iconName: '🌴',
    badgeText: '',
    badgeBgColor: '#3b82f6',
    badgeTextColor: '#ffffff',
    isActive: true,
    order: 3,
    animation: 'none',
    clickCount: 430,
    category: 'Kepegawaian & HR',
    openInNewTab: true,
    isProtected: false,
    pinCode: '132525'
  },
  {
    id: 'menu-wfa-bimbingan',
    title: 'Formulir Pengajuan WFA Bimbingan',
    subtitle: 'Pengajuan jadwal WFA bimbingan dosen di Kota/Kabupaten',
    url: '#wfa-bimbingan',
    type: 'link',
    size: 'compact',
    bgColor: '#0d9488',
    textColor: '#ffffff',
    borderColor: '#14b8a6',
    isGradient: true,
    gradientTo: '#0f766e',
    gradientAngle: 135,
    iconName: '📄',
    badgeText: 'POPULER',
    badgeBgColor: '#8b5cf6',
    badgeTextColor: '#ffffff',
    isActive: true,
    order: 4,
    animation: 'none',
    clickCount: 620,
    category: 'Kepegawaian & HR',
    openInNewTab: false,
    isProtected: true,
    pinCode: 'polkesban56'
  },
  {
    id: 'menu-kebugaran-jasmani',
    title: 'Input Hasil Kebugaran Pegawai',
    subtitle: 'Lengkapi Data Kebugaran Terakhir Anda',
    url: '#input-kebugaran',
    type: 'link',
    size: 'compact',
    bgColor: '#0d9488',
    textColor: '#ffffff',
    borderColor: '#14b8a6',
    isGradient: true,
    gradientTo: '#0f766e',
    gradientAngle: 135,
    iconName: '🛡️',
    badgeText: 'NEWS',
    badgeBgColor: '#ef4444',
    badgeTextColor: '#ffffff',
    isActive: true,
    order: 5,
    animation: 'pulse',
    clickCount: 450,
    category: 'Kepegawaian & HR',
    openInNewTab: false,
    isProtected: true,
    pinCode: 'kebugaran56'
  },
  {
    id: 'menu-daftar-hadir-rapat',
    title: 'Daftar Hadir Rapat',
    subtitle: 'Formulir Presensi & Paraf Digital Peserta Rapat',
    url: '#daftar-hadir-rapat',
    type: 'link',
    size: 'compact',
    bgColor: '#0d9488',
    textColor: '#ffffff',
    borderColor: '#14b8a6',
    isGradient: true,
    gradientTo: '#0f766e',
    gradientAngle: 135,
    iconName: '📝',
    badgeText: 'PRESENSI',
    badgeBgColor: '#059669',
    badgeTextColor: '#ffffff',
    isActive: true,
    order: 6,
    animation: 'none',
    clickCount: 380,
    category: 'Kepegawaian & HR',
    openInNewTab: false,
    isProtected: false
  }
];

export const INITIAL_CLICK_LOGS: ClickLog[] = [
  {
    id: 'log-1',
    menuId: 'menu-1',
    menuTitle: '🕒 Presensi & Absensi Online Pegawai',
    category: 'Kepegawaian & Presensi',
    timestamp: new Date(Date.now() - 1000 * 60 * 3).toISOString(),
    device: 'Mobile',
    browser: 'Chrome Mobile',
    referrer: 'Akses Portal Pegawai'
  },
  {
    id: 'log-2',
    menuId: 'menu-2',
    menuTitle: '📊 e-Kinerja BKN & SKP Kemenkes',
    category: 'Kepegawaian & Presensi',
    timestamp: new Date(Date.now() - 1000 * 60 * 12).toISOString(),
    device: 'Desktop',
    browser: 'Chrome 122',
    referrer: 'Portal OSDM'
  },
  {
    id: 'log-3',
    menuId: 'menu-3',
    menuTitle: '🌴 Pengajuan Cuti Pegawai Online (E-Cuti)',
    category: 'Layanan Administrasi SDM',
    timestamp: new Date(Date.now() - 1000 * 60 * 25).toISOString(),
    device: 'Mobile',
    browser: 'Safari iOS',
    referrer: 'WhatsApp Pegawai'
  }
];

export const CATEGORIES_PRESET = [
  'Semua',
  'Kepegawaian & Presensi',
  'Layanan Administrasi SDM',
  'Kesejahteraan & Remunerasi',
  'Pengembangan Karir & Diklat',
  'Persuratan & Dokumen',
  'Bantuan & Kontak OSDM'
];

export const DEFAULT_WFA_MENU: MenuItem = INITIAL_MENUS[0];
export const DEFAULT_KEBUGARAN_MENU: MenuItem = INITIAL_MENUS[1];
export const DEFAULT_MEETING_ATTENDANCE_MENU: MenuItem = INITIAL_MENUS[INITIAL_MENUS.length - 1];

export const DEFAULT_MOOC_ASN_MENU: MenuItem = {
  id: 'menu-mooc-asn',
  title: 'Konfirmasi MOOC ASN Berintegritas',
  subtitle: 'Konfirmasi Pelatihan MOOC & Unggah Sertifikat',
  url: '#mooc-asn',
  type: 'link',
  size: 'compact',
  bgColor: '#1e40af',
  textColor: '#ffffff',
  borderColor: '#3b82f6',
  isGradient: true,
  gradientTo: '#1e3a8a',
  gradientAngle: 135,
  iconName: '🎓',
  badgeText: 'WAJIB',
  badgeBgColor: '#ef4444',
  badgeTextColor: '#ffffff',
  isActive: true,
  order: 7,
  animation: 'none',
  clickCount: 150,
  category: 'Pengembangan Karir & Diklat',
  openInNewTab: false,
  isProtected: false
};

export const ensureHasWfaMenu = (menuList: MenuItem[]): MenuItem[] => {
  if (!Array.isArray(menuList) || menuList.length === 0) {
    return [...INITIAL_MENUS, DEFAULT_MOOC_ASN_MENU];
  }
  let result = [...menuList];

  const hasWfa = result.some(
    (m) =>
      m.id === 'menu-wfa-bimbingan' ||
      m.url === '#wfa-bimbingan' ||
      m.title?.toLowerCase().includes('wfa bimbingan') ||
      m.title?.toLowerCase().includes('formulir pengajuan wfa')
  );
  if (!hasWfa) {
    result.unshift(DEFAULT_WFA_MENU);
  }

  const hasKebugaran = result.some(
    (m) =>
      m.id === 'menu-kebugaran-jasmani' ||
      m.url === '#input-kebugaran' ||
      m.title?.toLowerCase().includes('kebugaran')
  );
  if (!hasKebugaran) {
    const wfaIdx = result.findIndex((m) => m.id === 'menu-wfa-bimbingan' || m.url === '#wfa-bimbingan');
    if (wfaIdx !== -1) {
      result.splice(wfaIdx + 1, 0, DEFAULT_KEBUGARAN_MENU);
    } else {
      result.unshift(DEFAULT_KEBUGARAN_MENU);
    }
  }

  const hasMeeting = result.some(
    (m) =>
      m.id === 'menu-daftar-hadir-rapat' ||
      m.url === '#daftar-hadir-rapat' ||
      m.title?.toLowerCase().includes('daftar hadir rapat') ||
      m.title?.toLowerCase().includes('presensi rapat')
  );
  if (!hasMeeting) {
    const kbgIdx = result.findIndex((m) => m.id === 'menu-kebugaran-jasmani' || m.url === '#input-kebugaran');
    if (kbgIdx !== -1) {
      result.splice(kbgIdx + 1, 0, DEFAULT_MEETING_ATTENDANCE_MENU);
    } else {
      result.push(DEFAULT_MEETING_ATTENDANCE_MENU);
    }
  }

  const hasMooc = result.some(
    (m) =>
      m.id === 'menu-mooc-asn' ||
      m.url === '#mooc-asn' ||
      m.title?.toLowerCase().includes('mooc asn') ||
      m.title?.toLowerCase().includes('asn berintegritas')
  );
  if (!hasMooc) {
    const meetIdx = result.findIndex((m) => m.id === 'menu-daftar-hadir-rapat' || m.url === '#daftar-hadir-rapat');
    if (meetIdx !== -1) {
      result.splice(meetIdx + 1, 0, DEFAULT_MOOC_ASN_MENU);
    } else {
      result.push(DEFAULT_MOOC_ASN_MENU);
    }
  }

  return result;
};



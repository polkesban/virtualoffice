/**
 * Utility & Storage Helper untuk Pengaturan Penandatangan & Tanda Tangan Digital (Format PNG)
 * Digunakan secara terpadu oleh Dokumen PDF Dashboard WFA dan Rekap Laporan WFA.
 */

export interface WfaSignerConfig {
  cityDate: string; // e.g. "Bandung, 07 Oktober 2026"
  title: string;    // e.g. "Penanggung Jawab Tim Kerja OSDM,"
  name: string;     // e.g. "Tim Kerja OSDM" atau nama pejabat
  institution: string; // e.g. "Poltekkes Kemenkes Bandung" atau NIP
  signaturePng: string | null; // Data URL format PNG (disarankan transparan)
  signatureFilename?: string | null;
}

export const STORAGE_KEY_SIGNER = 'wfa_signer_config';

/**
 * Format tanggal hari ini dalam format Bahasa Indonesia (misal: "Bandung, 07 Oktober 2026")
 */
export const getDefaultCityDate = (): string => {
  const todayStr = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(new Date());
  return `Bandung, ${todayStr}`;
};

/**
 * Konfigurasi bawaan standar penandatangan
 */
export const getDefaultSignerConfig = (): WfaSignerConfig => {
  return {
    cityDate: getDefaultCityDate(),
    title: 'Penanggung Jawab Tim Kerja OSDM,',
    name: 'Tim Kerja OSDM',
    institution: 'Poltekkes Kemenkes Bandung',
    signaturePng: null,
    signatureFilename: null,
  };
};

/**
 * Membaca konfigurasi penandatangan dari localStorage
 */
export const loadSignerConfig = (): WfaSignerConfig => {
  const def = getDefaultSignerConfig();
  if (typeof window === 'undefined') return def;
  try {
    const raw = localStorage.getItem(STORAGE_KEY_SIGNER);
    if (raw) {
      const parsed = JSON.parse(raw);
      return {
        cityDate: parsed.cityDate?.trim() || def.cityDate,
        title: parsed.title?.trim() || def.title,
        name: parsed.name?.trim() || def.name,
        institution: parsed.institution?.trim() || def.institution,
        signaturePng: parsed.signaturePng || null,
        signatureFilename: parsed.signatureFilename || null,
      };
    }
  } catch (err) {
    console.warn('Gagal membaca wfa_signer_config dari localStorage:', err);
  }
  return def;
};

/**
 * Menyimpan konfigurasi penandatangan ke localStorage
 */
export const saveSignerConfig = (config: WfaSignerConfig): void => {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY_SIGNER, JSON.stringify(config));
  } catch (err) {
    console.warn('Gagal menyimpan wfa_signer_config ke localStorage:', err);
  }
};

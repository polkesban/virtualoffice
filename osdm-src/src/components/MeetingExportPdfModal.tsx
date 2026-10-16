import React, { useState, useMemo, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  Download,
  FileText,
  Layers,
  CheckCircle2,
  Upload,
  Image as ImageIcon,
  Trash2,
  RotateCcw,
  Users,
  CheckSquare,
  Square,
  AlertCircle,
  Building2,
  Calendar,
  Check,
} from 'lucide-react';
import { MeetingSession, MeetingAttendanceRecord } from '../types';
import {
  getMeetingAgendas,
  filterAttendeesByAgenda,
  getAllPermanentAgendas,
  removeSavedAgenda,
} from '../utils/meetingAgendaHelper';
import { generateMeetingPdfReport } from '../utils/meetingPdfReport';
import { getKopSuratDataUrl } from '../utils/kopSuratCanvas';

interface MeetingExportPdfModalProps {
  isOpen: boolean;
  onClose: () => void;
  session: MeetingSession;
  attendees: MeetingAttendanceRecord[];
  initialSelectedAgenda?: string;
}

const LOCAL_STORAGE_CUSTOM_KOP_KEY = 'direct_menu_meeting_custom_kop_v1';

export const MeetingExportPdfModal: React.FC<MeetingExportPdfModalProps> = ({
  isOpen,
  onClose,
  session,
  attendees,
  initialSelectedAgenda,
}) => {
  // Tick to trigger agenda list refresh after deletion
  const [refreshTick, setRefreshTick] = useState<number>(0);
  const [isManagingAgendas, setIsManagingAgendas] = useState<boolean>(false);

  // All agendas defined in this session and historical attendance records (permanently saved)
  const availableAgendas = useMemo(
    () => getAllPermanentAgendas(session, attendees),
    [session, attendees, refreshTick]
  );

  // Selected agenda filter: 'ALL' or the specific agenda string
  const [selectedAgenda, setSelectedAgenda] = useState<string>(initialSelectedAgenda || 'ALL');

  // Sync initialSelectedAgenda when modal opens
  useEffect(() => {
    if (isOpen && initialSelectedAgenda) {
      setSelectedAgenda(initialSelectedAgenda);
    }
  }, [isOpen, initialSelectedAgenda]);

  // Handle deleting an agenda permanently
  const handleDeleteAgenda = (agendaToDelete: string) => {
    if (!agendaToDelete || agendaToDelete === 'ALL') return;

    const count = filterAttendeesByAgenda(attendees, agendaToDelete).length;
    const confirmMsg =
      count > 0
        ? `Apakah Anda yakin ingin menghapus agenda:\n"${agendaToDelete}"?\n\nPerhatian: Terdapat ${count} peserta tercatat hadir pada agenda ini. Menghapus agenda akan menghilangkannya dari daftar pilihan rekap.`
        : `Apakah Anda yakin ingin menghapus agenda:\n"${agendaToDelete}" dari daftar pilihan rekap?`;

    if (!window.confirm(confirmMsg)) return;

    removeSavedAgenda(agendaToDelete);
    setRefreshTick((prev) => prev + 1);
    if (selectedAgenda === agendaToDelete) {
      setSelectedAgenda('ALL');
    }
  };

  // Kop surat mode: 'standard' | 'custom'
  const [kopMode, setKopMode] = useState<'standard' | 'custom'>('standard');
  const [customKopImage, setCustomKopImage] = useState<string | null>(null);
  const [standardKopPreview, setStandardKopPreview] = useState<string | null>(null);

  // Signing block customizer
  const [signerName, setSignerName] = useState(session.pimpinanRapat || '');
  const [signerJabatan, setSignerJabatan] = useState(
    session.jabatanPimpinan || 'Ketua Tim Kerja OSDM Poltekkes Kemenkes Bandung'
  );

  // Status
  const [isExporting, setIsExporting] = useState(false);
  const [exportError, setExportError] = useState<string | null>(null);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Load saved custom kop from localStorage or session
  useEffect(() => {
    try {
      const saved = localStorage.getItem(LOCAL_STORAGE_CUSTOM_KOP_KEY);
      if (saved) {
        setCustomKopImage(saved);
        setKopMode('custom');
      } else if (session.customKopSurat) {
        setCustomKopImage(session.customKopSurat);
        setKopMode('custom');
      }
    } catch {
      // ignore
    }

    // Load standard kop preview
    getKopSuratDataUrl().then((url) => {
      setStandardKopPreview(url);
    });
  }, [session]);

  // Sync signer with session when session updates
  useEffect(() => {
    setSignerName(session.pimpinanRapat || '');
    setSignerJabatan(session.jabatanPimpinan || 'Ketua Tim Kerja OSDM Poltekkes Kemenkes Bandung');
  }, [session]);

  // Filter attendees based on selected agenda
  const attendeesForAgenda = useMemo(() => {
    return filterAttendeesByAgenda(attendees, selectedAgenda);
  }, [attendees, selectedAgenda]);

  // Attendees directly derived from the selected agenda
  const finalExportAttendees = attendeesForAgenda;

  // Handle Kop Surat file upload
  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    setUploadError(null);
    const file = e.target.files?.[0];
    if (!file) return;

    // Check size (< 4MB)
    if (file.size > 4 * 1024 * 1024) {
      setUploadError('Ukuran file maksimal 4MB.');
      return;
    }

    if (!file.type.startsWith('image/')) {
      setUploadError('Mohon upload file gambar (PNG, JPG, JPEG, SVG, WebP).');
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      const result = event.target?.result as string;
      if (result) {
        setCustomKopImage(result);
        setKopMode('custom');
        try {
          localStorage.setItem(LOCAL_STORAGE_CUSTOM_KOP_KEY, result);
        } catch {
          // localStorage might be full
        }
      }
    };
    reader.onerror = () => {
      setUploadError('Gagal membaca file gambar.');
    };
    reader.readAsDataURL(file);
  };

  const handleRemoveCustomKop = () => {
    setCustomKopImage(null);
    setKopMode('standard');
    try {
      localStorage.removeItem(LOCAL_STORAGE_CUSTOM_KOP_KEY);
    } catch {
      // ignore
    }
  };

  // Perform PDF Generation
  const handleDownloadPdf = async () => {
    if (finalExportAttendees.length === 0) {
      setExportError('Pilih minimal 1 peserta untuk dicantumkan dalam rekapitulasi PDF.');
      return;
    }

    try {
      setIsExporting(true);
      setExportError(null);

      const agendaTitleForPdf =
        selectedAgenda === 'ALL'
          ? session.agenda
          : selectedAgenda;

      const safeAgendaSlug = selectedAgenda === 'ALL' ? 'Semua_Agenda' : selectedAgenda.replace(/[^a-zA-Z0-9]/g, '_').substring(0, 30);
      const customFilename = `Rekap_Daftar_Hadir_${safeAgendaSlug}_${session.date}`;

      const sessionForReport: MeetingSession = {
        ...session,
        agenda: agendaTitleForPdf,
        pimpinanRapat: signerName.trim() || session.pimpinanRapat,
        jabatanPimpinan: signerJabatan.trim() || session.jabatanPimpinan,
      };

      await generateMeetingPdfReport({
        session: sessionForReport,
        attendees: finalExportAttendees,
        customKopSuratImage: kopMode === 'custom' ? customKopImage : null,
        customFilename,
      });

      onClose();
    } catch (err: any) {
      setExportError(`Gagal membuat PDF: ${err?.message || 'Terjadi kesalahan sistem'}`);
    } finally {
      setIsExporting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-sm overflow-y-auto">
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 15 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 15 }}
          transition={{ duration: 0.2 }}
          className="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl my-8 overflow-hidden flex flex-col max-h-[92vh]"
        >
          {/* Header */}
          <div className="bg-gradient-to-r from-teal-800 via-teal-900 to-emerald-950 p-4 sm:p-5 text-white flex items-center justify-between shrink-0">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-teal-300">
                <Download className="w-5 h-5" />
              </div>
              <div>
                <h2 className="text-base sm:text-lg font-bold">Unduh Rekapitulasi Daftar Hadir PDF</h2>
                <p className="text-xs text-teal-200/90 line-clamp-1">
                  Pilih agenda spesifik & sesuaikan kop surat resmi dokumen
                </p>
              </div>
            </div>
            <button
              type="button"
              onClick={onClose}
              className="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer"
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          {/* Body */}
          <div className="p-4 sm:p-6 overflow-y-auto space-y-6 flex-1 text-slate-800 text-xs sm:text-sm">
            {exportError && (
              <div className="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
                <span>{exportError}</span>
              </div>
            )}

            {/* 1. SELEKSI AGENDA YANG DIBAHAS (DROPDOWN HEMAT RUANG + TOMBOL HAPUS) */}
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                  <Layers className="w-3.5 h-3.5 text-teal-700" />
                  <span>1. Pilih Agenda yang Ingin Direkap</span>
                </label>
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => setIsManagingAgendas((prev) => !prev)}
                    className="text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1 cursor-pointer"
                  >
                    <Trash2 className="w-3 h-3" />
                    <span>{isManagingAgendas ? 'Tutup Kelola' : 'Kelola / Hapus'}</span>
                  </button>
                  <span className="text-[11px] font-semibold text-teal-800 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-200/70">
                    {attendeesForAgenda.length} Peserta Terpilih
                  </span>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <div className="relative flex-1">
                  <select
                    value={selectedAgenda}
                    onChange={(e) => setSelectedAgenda(e.target.value)}
                    className="w-full text-xs sm:text-sm font-semibold p-3 pl-3.5 pr-10 bg-slate-50 hover:bg-white border-2 border-slate-300 focus:border-teal-600 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600/30 text-slate-900 transition-all cursor-pointer shadow-2xs"
                  >
                    <option value="ALL">
                      Semua Agenda (Gabungan Seluruh Peserta) — ({attendees.length} Peserta)
                    </option>
                    {availableAgendas.map((agendaItem, idx) => {
                      const count = filterAttendeesByAgenda(attendees, agendaItem).length;
                      return (
                        <option key={idx} value={agendaItem}>
                          Agenda {idx + 1}: {agendaItem} — ({count} Peserta)
                        </option>
                      );
                    })}
                  </select>
                </div>

                {selectedAgenda !== 'ALL' && (
                  <button
                    type="button"
                    onClick={() => handleDeleteAgenda(selectedAgenda)}
                    className="px-3.5 py-3 text-xs font-bold text-rose-700 hover:text-white bg-rose-50 hover:bg-rose-600 border-2 border-rose-300 hover:border-rose-600 rounded-xl transition-all flex items-center gap-1.5 shrink-0 shadow-2xs cursor-pointer"
                    title="Hapus agenda terpilih ini dari daftar pilihan"
                  >
                    <Trash2 className="w-4 h-4" />
                    <span className="hidden sm:inline">Hapus Agenda</span>
                  </button>
                )}
              </div>

              {/* Panel Kelola / Hapus Agenda */}
              {isManagingAgendas && (
                <div className="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2 mt-2">
                  <div className="flex items-center justify-between text-xs font-semibold text-slate-700">
                    <span>Daftar Agenda Tersimpan ({availableAgendas.length})</span>
                    <span className="text-[10px] text-slate-400 font-normal">Klik ikon tempat sampah untuk menghapus</span>
                  </div>
                  {availableAgendas.length === 0 ? (
                    <p className="text-xs text-slate-400 italic py-1">Belum ada agenda yang tersimpan.</p>
                  ) : (
                    <div className="max-h-44 overflow-y-auto space-y-1.5 pr-1">
                      {availableAgendas.map((item, idx) => {
                        const count = filterAttendeesByAgenda(attendees, item).length;
                        return (
                          <div
                            key={idx}
                            className="flex items-center justify-between p-2 bg-white border border-slate-200 rounded-lg text-xs hover:border-rose-200 transition-colors"
                          >
                            <div className="flex-1 pr-2 min-w-0">
                              <p className="font-semibold text-slate-800 truncate">
                                {idx + 1}. {item}
                              </p>
                              <span className="text-[10px] text-slate-500">
                                {count} peserta hadir
                              </span>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleDeleteAgenda(item)}
                              className="px-2.5 py-1 text-xs font-medium text-rose-600 hover:text-white hover:bg-rose-600 border border-rose-200 hover:border-rose-600 rounded-md transition-colors cursor-pointer shrink-0 flex items-center gap-1"
                              title={`Hapus "${item}"`}
                            >
                              <Trash2 className="w-3 h-3" />
                              <span>Hapus</span>
                            </button>
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              )}

              <div className="flex items-center justify-between text-[11px] text-slate-500 px-1">
                <span>
                  {selectedAgenda === 'ALL'
                    ? 'Mencakup seluruh daftar peserta hadir dari seluruh agenda pembahasan.'
                    : `Menyaring khusus peserta yang tercatat hadir pada: "${selectedAgenda}"`}
                </span>
              </div>
            </div>

            {/* 2. STATUS FILTER OTOMATIS KEHADIRAN PESERTA */}
            <div className="p-3.5 bg-teal-50/70 border border-teal-200/80 rounded-xl flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <Users className="w-4 h-4 text-teal-700 shrink-0" />
                <div>
                  <div className="text-xs font-bold text-teal-950">
                    Filter Otomatis Peserta Aktif
                  </div>
                  <div className="text-[11px] text-teal-700">
                    {selectedAgenda === 'ALL'
                      ? 'Seluruh peserta rapat otomatis dimasukkan ke dokumen.'
                      : `Hanya pegawai yang hadir pada agenda "${selectedAgenda}" yang otomatis dicetak ke PDF.`}
                  </div>
                </div>
              </div>
              <span className="text-xs font-bold text-teal-800 bg-white px-3 py-1.5 rounded-lg border border-teal-200 shadow-2xs shrink-0">
                {attendeesForAgenda.length} Pegawai Otomatis
              </span>
            </div>

            {/* 3. PENGATURAN KOP SURAT (HEADER PDF) */}
            <div className="space-y-3 pt-1">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                  <ImageIcon className="w-3.5 h-3.5 text-teal-700" />
                  <span>2. Kop Surat Dokumen (Header PDF)</span>
                </label>
                <div className="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200">
                  <button
                    type="button"
                    onClick={() => setKopMode('standard')}
                    className={`px-2.5 py-1 rounded-md text-[11px] font-semibold transition-all cursor-pointer ${
                      kopMode === 'standard'
                        ? 'bg-white text-teal-800 shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    Kop Standar
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setKopMode('custom');
                      if (!customKopImage && fileInputRef.current) {
                        fileInputRef.current.click();
                      }
                    }}
                    className={`px-2.5 py-1 rounded-md text-[11px] font-semibold transition-all cursor-pointer ${
                      kopMode === 'custom'
                        ? 'bg-white text-teal-800 shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    Upload Kop Kustom
                  </button>
                </div>
              </div>

              {uploadError && (
                <div className="p-2.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
                  <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                  <span>{uploadError}</span>
                </div>
              )}

              {/* Preview Kop Surat Sesuai Pilihan */}
              {kopMode === 'standard' ? (
                <div className="border border-slate-200 rounded-xl p-3 bg-slate-50 space-y-2">
                  <div className="flex items-center justify-between text-[11px]">
                    <span className="font-semibold text-slate-700">Kop Resmi Standar Poltekkes Kemenkes Bandung</span>
                    <span className="text-emerald-700 font-bold bg-emerald-100 px-2 py-0.5 rounded-full text-[10px]">
                      Aktif
                    </span>
                  </div>
                  {standardKopPreview && (
                    <div className="bg-white border border-slate-200 rounded-lg p-2 max-h-24 overflow-hidden flex items-center justify-center">
                      <img
                        src={standardKopPreview}
                        alt="Kop Standar Poltekkes"
                        className="max-h-20 w-auto object-contain"
                      />
                    </div>
                  )}
                  <p className="text-[10px] text-slate-500">
                    Menggunakan tata letak resmi Kementerian Kesehatan RI dengan logo dan alamat terverifikasi.
                  </p>
                </div>
              ) : (
                <div className="border border-teal-300 rounded-xl p-3 bg-teal-50/40 space-y-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <span className="font-bold text-xs text-teal-950">Kop Surat Kustom</span>
                      <p className="text-[10px] text-slate-600">
                        Upload banner / header kop surat instansi (PNG, JPG, SVG).
                      </p>
                    </div>
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                      onChange={handleFileUpload}
                      className="hidden"
                    />
                    <button
                      type="button"
                      onClick={() => fileInputRef.current?.click()}
                      className="px-3 py-1.5 rounded-lg text-xs font-bold text-teal-800 bg-white border border-teal-300 hover:bg-teal-50 transition-colors shadow-2xs cursor-pointer flex items-center gap-1.5"
                    >
                      <Upload className="w-3.5 h-3.5" />
                      <span>{customKopImage ? 'Ganti File Kop' : 'Pilih File Kop'}</span>
                    </button>
                  </div>

                  {customKopImage ? (
                    <div className="space-y-2">
                      <div className="relative bg-white border border-teal-200 rounded-lg p-2 max-h-28 overflow-hidden flex items-center justify-center shadow-inner">
                        <img
                          src={customKopImage}
                          alt="Custom Kop Surat"
                          className="max-h-24 w-auto object-contain"
                        />
                      </div>
                      <div className="flex items-center justify-between text-[11px] text-slate-500 pt-0.5">
                        <span className="text-emerald-700 font-medium flex items-center gap-1">
                          <CheckCircle2 className="w-3 h-3" />
                          Kop kustom tersimpan & siap digunakan
                        </span>
                        <button
                          type="button"
                          onClick={handleRemoveCustomKop}
                          className="text-rose-600 hover:text-rose-800 font-semibold flex items-center gap-1 cursor-pointer"
                        >
                          <Trash2 className="w-3 h-3" />
                          <span>Hapus Kop Kustom</span>
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div
                      onClick={() => fileInputRef.current?.click()}
                      className="border-2 border-dashed border-teal-300 hover:border-teal-500 bg-white/70 rounded-xl p-6 text-center cursor-pointer transition-colors"
                    >
                      <Upload className="w-8 h-8 mx-auto mb-2 text-teal-600" />
                      <p className="text-xs font-bold text-slate-800">
                        Klik untuk mengunggah file Kop Surat
                      </p>
                      <p className="text-[10px] text-slate-500 mt-0.5">
                        Format PNG, JPG, atau SVG (Rekomendasi rasio horizontal ~5:1 atau 6:1)
                      </p>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* 4. PENANDATANGAN DOKUMEN */}
            <div className="space-y-2 pt-1 border-t border-slate-100">
              <label className="text-xs font-bold uppercase tracking-wider text-slate-700 block">
                3. Informasi Pengesahan / Pimpinan Rapat
              </label>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="text-[11px] font-semibold text-slate-600 block mb-1">Nama Pimpinan</label>
                  <input
                    type="text"
                    value={signerName}
                    onChange={(e) => setSignerName(e.target.value)}
                    placeholder="Nama lengkap pimpinan beserta gelar"
                    className="w-full text-xs p-2 bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600"
                  />
                </div>
                <div>
                  <label className="text-[11px] font-semibold text-slate-600 block mb-1">Jabatan Pimpinan</label>
                  <input
                    type="text"
                    value={signerJabatan}
                    onChange={(e) => setSignerJabatan(e.target.value)}
                    placeholder="Jabatan pimpinan"
                    className="w-full text-xs p-2 bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-600"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Footer Actions */}
          <div className="bg-slate-50 border-t border-slate-200 p-4 sm:p-5 flex items-center justify-between gap-3 shrink-0">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-300 hover:bg-slate-100 transition-colors cursor-pointer"
            >
              Batal
            </button>

            <button
              type="button"
              onClick={handleDownloadPdf}
              disabled={isExporting || finalExportAttendees.length === 0}
              className="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-teal-700 to-emerald-700 hover:from-teal-800 hover:to-emerald-800 shadow-md hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            >
              {isExporting ? (
                <>
                  <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  <span>Menyusun Dokumen PDF...</span>
                </>
              ) : (
                <>
                  <Download className="w-4 h-4" />
                  <span>
                    Unduh Rekap PDF ({finalExportAttendees.length} Peserta)
                  </span>
                </>
              )}
            </button>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

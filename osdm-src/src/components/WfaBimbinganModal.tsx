import React, { useState, useEffect, useId, useMemo, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  Calendar,
  MapPin,
  FileText,
  UserCheck,
  Search,
  CheckCircle2,
  Clock,
  AlertCircle,
  XCircle,
  AlertTriangle,
  ExternalLink,
  Sparkles,
  Building2,
  Send,
  RotateCcw,
  Check,
  Briefcase,
  MessageCircle,
  ShieldCheck,
  Info,
  User,
  ChevronDown,
  ChevronUp
} from 'lucide-react';
import { WfaSubmission, WfaLocation, EmployeeRecord } from '../types';
import {
  findEmployeeByNip,
  searchEmployees,
  getLocationsForUnitKerja,
  subscribeEmployeeChanges,
} from '../data/employeeDatabase';

interface WfaBimbinganModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (submission: Omit<WfaSubmission, 'id' | 'status' | 'createdAt'>) => Promise<{ success: boolean; error?: string }>;
  allSubmissions: WfaSubmission[];
  logoUrl?: string;
  osdmContactWa?: string;
}

export const WfaBimbinganModal: React.FC<WfaBimbinganModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  allSubmissions,
  logoUrl = 'https://poltekkesbandung.ac.id/wp-content/uploads/2026/05/cropped-logo-transparan-2.png',
  osdmContactWa = '08119712525',
}) => {
  const formId = useId();
  // Mode: 'form' (Pengajuan Baru) vs 'check' (Pengecekan Pengajuan)
  const [activeTab, setActiveTab] = useState<'form' | 'check'>('form');

  // Form Fields
  const [nip, setNip] = useState('');
  const [employeeName, setEmployeeName] = useState('');
  const [unitKerja, setUnitKerja] = useState('');
  const [jabatan, setJabatan] = useState('');
  const [nomorWa, setNomorWa] = useState('');
  const [tanggalWfa, setTanggalWfa] = useState('');
  const [namaKegiatan, setNamaKegiatan] = useState('');
  const [lokasiKegiatan, setLokasiKegiatan] = useState<WfaLocation | ''>('');
  const [lokasiLahanBimbingan, setLokasiLahanBimbingan] = useState('');

  // Status WFA states:
  // For 'Kota Bandung': single choice 'WFA Datang' or 'WFA Pulang'
  const [statusKota, setStatusKota] = useState<'WFA Datang' | 'WFA Pulang' | ''>('');
  // For 'Kabupaten Bandung': checkboxes (can choose both)
  const [kabDatang, setKabDatang] = useState(false);
  const [kabPulang, setKabPulang] = useState(false);

  const [linkSuratTugas, setLinkSuratTugas] = useState('');

  // UI States
  const modalBodyRef = useRef<HTMLDivElement>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, _setErrorMessage] = useState<string | null>(null);
  const [isErrorPopupOpen, setIsErrorPopupOpen] = useState(false);

  const setErrorMessage = (msg: string | null) => {
    _setErrorMessage(msg);
    if (msg) {
      setIsErrorPopupOpen(true);
    } else {
      setIsErrorPopupOpen(false);
    }
  };

  const closeErrorPopup = () => {
    setIsErrorPopupOpen(false);
    _setErrorMessage(null);
    // Smoothly scroll modal body to top to make it easy to see/fix the fields
    modalBodyRef.current?.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const isRejection = useMemo(() => {
    if (!errorMessage) return false;
    const lower = errorMessage.toLowerCase();
    return lower.includes('ditolak') || lower.includes('dibatasi') || errorMessage.includes('⛔');
  }, [errorMessage]);

  const isIncomplete = useMemo(() => {
    if (!errorMessage || isRejection) return false;
    const lower = errorMessage.toLowerCase();
    return lower.includes('kosong') || lower.includes('lengkapi');
  }, [errorMessage, isRejection]);

  const missingList = useMemo(() => {
    if (!errorMessage || !errorMessage.includes('Mohon lengkapi:')) return [];
    const parts = errorMessage.split('Mohon lengkapi:');
    if (parts.length < 2) return [];
    return parts[1]
      .replace(/\.$/, '')
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean);
  }, [errorMessage]);

  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [submittedData, setSubmittedData] = useState<any | null>(null);
  const [isEmployeeFound, setIsEmployeeFound] = useState(false);
  const [employeeStatus, setEmployeeStatus] = useState<string>('Aktif');
  const [showNipSuggestions, setShowNipSuggestions] = useState(false);
  const [nipSearchKeyword, setNipSearchKeyword] = useState('');
  const [hasImgError, setHasImgError] = useState(false);

  // Urgent Submission Outside Service Hours Warning Modal
  const [urgentWarningModal, setUrgentWarningModal] = useState<{
    isOpen: boolean;
    title: string;
    badge: string;
    message: string;
  }>({
    isOpen: false,
    title: 'Pengajuan WFA Bimbingan',
    badge: 'Pemberitahuan Waktu Layanan',
    message: '',
  });

  // Helper untuk mendapatkan komponen waktu saat ini dalam WIB (Asia/Jakarta)
  const getWibDateTimeInfo = () => {
    const now = new Date();
    const formatter = new Intl.DateTimeFormat('en-CA', {
      timeZone: 'Asia/Jakarta',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hourCycle: 'h23',
    });

    const parts = formatter.formatToParts(now);
    const partMap: Record<string, string> = {};
    parts.forEach((p) => {
      partMap[p.type] = p.value;
    });

    const year = parseInt(partMap.year, 10);
    const month = parseInt(partMap.month, 10) - 1; // 0-indexed
    const day = parseInt(partMap.day, 10);
    const hour = parseInt(partMap.hour, 10);
    const minute = parseInt(partMap.minute, 10);
    const totalMinutes = hour * 60 + minute;

    const pad = (n: number) => String(n).padStart(2, '0');
    const todayWib = `${partMap.year}-${partMap.month}-${partMap.day}`;

    const tomorrowDate = new Date(Date.UTC(year, month, day + 1));
    const tomorrowWib = `${tomorrowDate.getUTCFullYear()}-${pad(tomorrowDate.getUTCMonth() + 1)}-${pad(tomorrowDate.getUTCDate())}`;

    // Hari dalam seminggu WIB: 0 = Minggu, 1 = Senin, ..., 5 = Jumat, 6 = Sabtu
    const dayOfWeek = new Date(Date.UTC(year, month, day)).getUTCDay();
    const isFriday = dayOfWeek === 5;
    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;

    return {
      todayWib,
      tomorrowWib,
      hour,
      minute,
      totalMinutes,
      dayOfWeek,
      isFriday,
      isWeekend,
    };
  };

  // Helper untuk mengecek peringatan pengajuan di luar jam kerja (Sabtu & Minggu, malam, subuh, sore WFA Pulang)
  const getOffHoursWarningInfo = (
    selectedDateStr: string,
    isPulangSelected: boolean = false
  ): { isOffHours: boolean; message: string } => {
    if (!selectedDateStr) return { isOffHours: false, message: '' };
    try {
      const { todayWib, tomorrowWib, totalMinutes, isFriday, isWeekend } = getWibDateTimeInfo();

      // 1. Pengajuan yang dilakukan hari Sabtu dan Minggu
      if (isWeekend) {
        return {
          isOffHours: true,
          message:
            'pengajuan diluar hari kerja akan berdampak terhadap layanan yang akan anda terima menjadi kurang optimal, jika anda yakin silahkan lanjutkan pengisian formulir',
        };
      }

      // 2. Hari kerja (Senin s.d Jum'at)
      // - Pukul 19.00 s.d 23.59 WIB & memilih besok hari
      const isNightNextDay = totalMinutes >= 1140 && totalMinutes <= 1439 && selectedDateStr === tomorrowWib;

      // - Pukul 00.00 s.d 07.30 WIB & memilih hari H
      const isEarlyMorningSameDay = totalMinutes >= 0 && totalMinutes <= 450 && selectedDateStr === todayWib;

      // - Memilih hari H & memilih WFA Pulang di luar jam kerja sore
      //   * Khusus hari Jum'at: 16.31 s.d 18.59 WIB (16:31 = 991, 18:59 = 1139)
      //   * Hari selain Jum'at: 16.01 s.d 18.59 WIB (16:01 = 961, 18:59 = 1139)
      const isAfterWorkPulangTime = isFriday
        ? totalMinutes >= 991 && totalMinutes <= 1139
        : totalMinutes >= 961 && totalMinutes <= 1139;

      const isLateAfternoonPulangSameDay =
        Boolean(isPulangSelected) && isAfterWorkPulangTime && selectedDateStr === todayWib;

      if (isNightNextDay) {
        return {
          isOffHours: true,
          message:
            'pengajuan diluar jam kerja akan berdampak terhadap layanan yang akan anda terima menjadi kurang optimal, jika anda yakin silahkan lanjutkan pengisian formulir',
        };
      }

      if (isEarlyMorningSameDay || isLateAfternoonPulangSameDay) {
        return {
          isOffHours: true,
          message:
            'pengajuan mendadak diluar jam kerja akan berdampak terhadap layanan yang akan anda terima menjadi kurang optimal, jika anda yakin silahkan lanjutkan pengisian formulir',
        };
      }

      return { isOffHours: false, message: '' };
    } catch (err) {
      console.error('Gagal mengecek waktu WIB di luar jam kerja:', err);
      return { isOffHours: false, message: '' };
    }
  };

  const checkIsUrgentOffHoursWfa = (selectedDateStr: string, isPulangSelected: boolean = false): boolean => {
    return getOffHoursWarningInfo(selectedDateStr, isPulangSelected).isOffHours;
  };

  const checkIsUrgentNightWfa = checkIsUrgentOffHoursWfa;

  // 2. Cek pengajuan mendadak WFA Pulang di hari H pada rentang waktu jam kerja menjelang jam pulang:
  // - Hari selain Jum'at: 15.00 s.d 16.00 WIB
  // - Hari Jum'at: 15.00 s.d 16.30 WIB
  const checkIsUrgentWfaPulang = (targetDate: string, isPulangSelected: boolean): boolean => {
    if (!targetDate || !isPulangSelected) return false;
    try {
      const { todayWib, totalMinutes, isFriday, isWeekend } = getWibDateTimeInfo();
      // Akhir pekan tidak dihitung jam kerja sore
      if (isWeekend) return false;

      // Jam kerja: Jum'at s.d 16.30 WIB (990 menit), hari lain s.d 16.00 WIB (960 menit)
      const maxWindowMinutes = isFriday ? 990 : 960;
      const isAfternoonWindow = totalMinutes >= 900 && totalMinutes <= maxWindowMinutes;
      if (!isAfternoonWindow) return false;

      return targetDate === todayWib;
    } catch (err) {
      console.error('Gagal mengecek waktu WIB sore:', err);
      return false;
    }
  };

  const triggerUrgentOffHoursWarning = (customMessage?: string) => {
    const isPulangActive = statusKota === 'WFA Pulang' || kabPulang;
    const warningInfo = getOffHoursWarningInfo(tanggalWfa, isPulangActive);
    const messageToUse =
      customMessage ||
      warningInfo.message ||
      'pengajuan diluar jam kerja akan berdampak terhadap layanan yang akan anda terima menjadi kurang optimal, jika anda yakin silahkan lanjutkan pengisian formulir';

    setUrgentWarningModal({
      isOpen: true,
      title: 'Pengajuan WFA Bimbingan',
      badge: 'Pemberitahuan Waktu Layanan',
      message: messageToUse,
    });
  };

  const triggerUrgentNightWarning = triggerUrgentOffHoursWarning;

  const triggerUrgentPulangWarning = () => {
    setUrgentWarningModal({
      isOpen: true,
      title: 'Pengajuan WFA Pulang',
      badge: 'Peringatan Pengajuan Hari H',
      message: 'hindari pengajuan mendadak pada WFA pulang untuk di hari H rentan waktu pukul 15.00 s.d 16.30 WIB agar petugas kami dapat melayani anda dengan optimal',
    });
  };

  const handleTanggalWfaChange = (val: string) => {
    setTanggalWfa(val);
    setErrorMessage(null);

    const isPulangActive = statusKota === 'WFA Pulang' || kabPulang;

    // Cek pengajuan di luar jam kerja (19.00-23.59 besok H, 00.00-07.30 hari H, atau sore hari H jika WFA Pulang)
    if (checkIsUrgentOffHoursWfa(val, isPulangActive)) {
      triggerUrgentOffHoursWarning();
      return;
    }

    // Cek pengajuan WFA Pulang sore hari H jika WFA Pulang sudah terpilih
    if (checkIsUrgentWfaPulang(val, isPulangActive)) {
      triggerUrgentPulangWarning();
    }
  };

  // Status Checker States
  const [checkNip, setCheckNip] = useState('');
  const [checkTanggal, setCheckTanggal] = useState('');
  const [checkResult, setCheckResult] = useState<{
    searched: boolean;
    found: boolean;
    submission?: WfaSubmission;
    statusText?: string;
    isSuccessState?: boolean;
    isPendingState?: boolean;
    isRejectedState?: boolean;
  } | null>(null);
  const [showCheckResultDetail, setShowCheckResultDetail] = useState(false);

  // Calculate allowed date boundaries (Hari H s/d H+3 kalender, tidak boleh tanggal mundur)
  const { minDate, maxDate, formattedMinDate, formattedMaxDate } = useMemo(() => {
    const { todayWib } = getWibDateTimeInfo();
    const [year, month, day] = todayWib.split('-').map(Number);
    const pad = (n: number) => String(n).padStart(2, '0');
    const min = todayWib;

    const maxDateObj = new Date(Date.UTC(year, month - 1, day + 3));
    const max = `${maxDateObj.getUTCFullYear()}-${pad(maxDateObj.getUTCMonth() + 1)}-${pad(maxDateObj.getUTCDate())}`;

    const formatIndo = (dateStr: string) => {
      const [y, m, d] = dateStr.split('-').map(Number);
      const dt = new Date(Date.UTC(y, m - 1, d));
      return dt.toLocaleDateString('id-ID', {
        timeZone: 'UTC',
        weekday: 'long',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      });
    };

    return {
      minDate: min,
      maxDate: max,
      formattedMinDate: formatIndo(min),
      formattedMaxDate: formatIndo(max),
    };
  }, []);

  // Format WhatsApp number for international wa.me standard
  const { rawWaNumber, waLinkNumber } = useMemo(() => {
    const raw = (osdmContactWa || '08119712525').trim();
    const digitsOnly = raw.replace(/[^0-9]/g, '');
    const intl = digitsOnly.startsWith('0')
      ? '62' + digitsOnly.slice(1)
      : digitsOnly.startsWith('62')
      ? digitsOnly
      : '62' + digitsOnly;
    return {
      rawWaNumber: raw,
      waLinkNumber: intl,
    };
  }, [osdmContactWa]);

  // Listen to employee changes from admin monitor or other tabs
  const [empDbVersion, setEmpDbVersion] = useState(0);
  useEffect(() => {
    const unsubscribe = subscribeEmployeeChanges(() => {
      setEmpDbVersion((v) => v + 1);
    });
    return unsubscribe;
  }, []);

  // Auto-detect employee name when NIP is changed
  useEffect(() => {
    if (!nip || nip.trim().length === 0) {
      setEmployeeName('');
      setUnitKerja('');
      setJabatan('');
      setIsEmployeeFound(false);
      setEmployeeStatus('Aktif');
      return;
    }

    const matched = findEmployeeByNip(nip);
    if (matched) {
      setEmployeeName(matched.name);
      setUnitKerja(matched.unitKerja);
      setJabatan(matched.jabatan);
      setEmployeeStatus(matched.status || 'Aktif');
      if (matched.nomorWa) {
        setNomorWa(matched.nomorWa);
      } else {
        const cleanNip = nip.replace(/[\s.-]/g, '').toLowerCase();
        const prevSub = allSubmissions.find((s) => s.nip.replace(/[\s.-]/g, '').toLowerCase() === cleanNip && s.nomorWa);
        if (prevSub?.nomorWa) {
          setNomorWa(prevSub.nomorWa);
        }
      }
      setIsEmployeeFound(true);
    } else {
      setEmployeeName('');
      setUnitKerja('');
      setJabatan('');
      setIsEmployeeFound(false);
      setEmployeeStatus('Aktif');
    }
  }, [nip, empDbVersion, allSubmissions]);

  // Dynamic Location Options based on Unit Kerja Pegawai
  // - Keperawatan Bogor & Kebidanan Bogor: Kota Bogor & Kabupaten Bogor
  // - Kebidanan Karawang: Kota Karawang & Kabupaten Karawang
  // - Lainnya: Kota Bandung & Kabupaten Bandung
  const locationConfig = useMemo(() => {
    return getLocationsForUnitKerja(unitKerja);
  }, [unitKerja]);

  // If unit kerja changes and current lokasiKegiatan is not in available options, reset it
  useEffect(() => {
    if (lokasiKegiatan && !locationConfig.options.includes(lokasiKegiatan)) {
      setLokasiKegiatan('');
      setStatusKota('');
      setKabDatang(false);
      setKabPulang(false);
    }
  }, [locationConfig, lokasiKegiatan]);

  // When location changes, reset choices to prevent mismatched state
  const handleLokasiChange = (loc: WfaLocation | '') => {
    setLokasiKegiatan(loc);
    setStatusKota('');
    setKabDatang(false);
    setKabPulang(false);
    setErrorMessage(null);
  };

  // Select employee from autocomplete suggestion
  const handleSelectEmployee = (emp: EmployeeRecord) => {
    setNip(emp.nip);
    setEmployeeName(emp.name);
    setUnitKerja(emp.unitKerja);
    setJabatan(emp.jabatan);
    setEmployeeStatus(emp.status || 'Aktif');
    if (emp.nomorWa) {
      setNomorWa(emp.nomorWa);
    } else {
      const cleanNip = emp.nip.replace(/[\s.-]/g, '').toLowerCase();
      const prevSub = allSubmissions.find((s) => s.nip.replace(/[\s.-]/g, '').toLowerCase() === cleanNip && s.nomorWa);
      if (prevSub?.nomorWa) {
        setNomorWa(prevSub.nomorWa);
      }
    }
    setIsEmployeeFound(true);
    setShowNipSuggestions(false);
    setErrorMessage(null);
  };

  // Reset form
  const handleResetForm = () => {
    setNip('');
    setEmployeeName('');
    setUnitKerja('');
    setJabatan('');
    setNomorWa('');
    setEmployeeStatus('Aktif');
    setTanggalWfa('');
    setNamaKegiatan('');
    setLokasiKegiatan('');
    setLokasiLahanBimbingan('');
    setStatusKota('');
    setKabDatang(false);
    setKabPulang(false);
    setLinkSuratTugas('');
    setErrorMessage(null);
    setSuccessMessage(null);
    setSubmittedData(null);
    setUrgentWarningModal((prev) => ({ ...prev, isOpen: false }));
  };

  const isKota = lokasiKegiatan.startsWith('Kota');
  const isKabupaten = lokasiKegiatan.startsWith('Kabupaten');

  // Determine computed statusWfa string
  const getComputedStatusWfa = (): string => {
    if (isKota) {
      return statusKota;
    }
    if (isKabupaten) {
      if (kabDatang && kabPulang) {
        return 'WFA Datang & WFA Pulang';
      }
      if (kabDatang) return 'WFA Datang';
      if (kabPulang) return 'WFA Pulang';
    }
    return '';
  };

  // Form Submission Handler
  const handleSubmitForm = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    // 1. Mandatory field checks
    const missingFields: string[] = [];

    if (!nip.trim()) missingFields.push('NIP Pegawai');
    if (!employeeName.trim()) missingFields.push('Nama Pegawai');
    if (!nomorWa.trim()) missingFields.push('Nomor WhatsApp Pegawai');
    if (!tanggalWfa.trim()) missingFields.push('Tanggal WFA');
    if (!namaKegiatan.trim()) missingFields.push('Nama Kegiatan');
    if (!lokasiKegiatan) missingFields.push('Lokasi Kegiatan');
    if (!lokasiLahanBimbingan.trim()) missingFields.push('Lokasi Lahan Bimbingan');

    const finalStatusWfa = getComputedStatusWfa();
    if (!finalStatusWfa) {
      missingFields.push('Pilihan Status WFA');
    }

    if (!linkSuratTugas.trim()) {
      missingFields.push('Link Surat Tugas');
    }

    if (missingFields.length > 0) {
      setErrorMessage(`Data tidak boleh kosong! Mohon lengkapi: ${missingFields.join(', ')}.`);
      return;
    }

    // 1.5 Validasi NIP wajib terdaftar di pangkalan data & nama otomatis muncul
    const matchedEmployee = findEmployeeByNip(nip);
    if (!matchedEmployee || !isEmployeeFound || !employeeName.trim()) {
      setErrorMessage(
        `Pengajuan DITOLAK! Nomor NIP "${nip.trim()}" tidak terdaftar dalam pangkalan data pegawai Poltekkes Bandung dan nama pegawai tidak muncul secara otomatis. Mohon periksa kembali nomor NIP Anda untuk menghindari kesalahan ketik (typo), atau gunakan tombol "Pilih dari Pangkalan Data".`
      );
      return;
    }

    // Check WhatsApp phone number validity (min 9 digits)
    const cleanWaDigits = nomorWa.replace(/[^0-9]/g, '');
    if (cleanWaDigits.length < 9) {
      setErrorMessage('Nomor WhatsApp tidak valid. Masukkan minimal 9-14 digit nomor WhatsApp aktif (contoh: 081234567890).');
      return;
    }

    // 2. Strict Date Limits (Hari H s/d H+3 kalender, tidak boleh tanggal mundur)
    if (tanggalWfa < minDate) {
      setErrorMessage(
        `Pengajuan ditolak! Tanggal tidak boleh tanggal mundur. Pengajuan WFA hanya diperbolehkan mulai hari ini (${formattedMinDate}) sampai dengan H+3 hari kalender.`
      );
      return;
    }

    if (tanggalWfa > maxDate) {
      setErrorMessage(
        `Pengajuan ditolak! Batas waktu pengajuan maksimal sampai dengan H+3 hari kalender (${formattedMaxDate}). Tanggal yang Anda pilih melebihi batas waktu yang diizinkan.`
      );
      return;
    }

    // 2.5 Status check: Only active employees can apply for WFA
    const effectiveStatus = matchedEmployee?.status || employeeStatus;
    if (effectiveStatus && effectiveStatus !== 'Aktif') {
      setErrorMessage(
        `⛔ Pengajuan WFA Dibatasi : Pegawai atas nama ${employeeName || 'Pegawai'} saat ini sedang berstatus ${effectiveStatus}. tidak diperkenankan mengajukan permohonan WFA Bimbingan.`
      );
      return;
    }

    // 3. Duplicate Submission Check (tidak boleh NIP sama mengajukan di tanggal yang sama dua kali)
    const cleanNip = nip.replace(/[\s.-]/g, '').trim().toLowerCase();
    const cleanDate = tanggalWfa.trim();

    const duplicateSubmission = allSubmissions.find((sub) => {
      const subNip = sub.nip.replace(/[\s.-]/g, '').trim().toLowerCase();
      return subNip === cleanNip && sub.tanggalWfa === cleanDate && sub.status !== 'Ditolak';
    });

    if (duplicateSubmission) {
      setErrorMessage(
        `Pengajuan DITOLAK! Pegawai dengan NIP ${nip.trim()} (${employeeName || 'Pegawai'}) sudah memiliki pengajuan WFA pada tanggal ${tanggalWfa} (Status saat ini: "${duplicateSubmission.status}"). Pegawai tidak diperbolehkan mengajukan WFA pada tanggal yang sama dua kali.`
      );
      return;
    }

    setIsSubmitting(true);

    try {
      const payload: Omit<WfaSubmission, 'id' | 'status' | 'createdAt'> = {
        nip: nip.trim(),
        employeeName: employeeName.trim(),
        unitKerja: unitKerja.trim(),
        jabatan: jabatan.trim(),
        nomorWa: nomorWa.trim(),
        tanggalWfa: tanggalWfa.trim(),
        namaKegiatan: namaKegiatan.trim(),
        lokasiKegiatan: lokasiKegiatan as WfaLocation,
        lokasiLahanBimbingan: lokasiLahanBimbingan.trim(),
        statusWfa: finalStatusWfa,
        linkSuratTugas: linkSuratTugas.trim(),
      };

      const result = await onSubmit(payload);

      if (result.success) {
        // Required exact phrasing: "jadwal WFA berhasil di ajukan, mohon menunggu validasi dari pengelola kepegawaian"
        setSuccessMessage('jadwal WFA berhasil di ajukan, mohon menunggu validasi dari pengelola kepegawaian');
        setSubmittedData({
          ...payload,
          status: 'Menunggu Validasi',
        });
      } else {
        setErrorMessage(result.error || 'Terjadi kendala saat mengajukan. Silakan coba kembali.');
      }
    } catch (err: any) {
      setErrorMessage(err?.message || 'Gagal mengajukan jadwal WFA.');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Status Check Handler
  const handleCheckStatus = (e: React.FormEvent) => {
    e.preventDefault();
    setShowCheckResultDetail(false);
    if (!checkNip.trim() || !checkTanggal.trim()) {
      setErrorMessage('Mohon lengkapi NIP dan Tanggal WFA yang ingin diperiksa terlebih dahulu.');
      return;
    }

    const cleanInputNip = checkNip.replace(/[\s.-]/g, '').trim().toLowerCase();
    const cleanDate = checkTanggal.trim();

    // Match all submissions for this NIP and Date
    const matches = allSubmissions.filter((item) => {
      const itemNip = item.nip.replace(/[\s.-]/g, '').trim().toLowerCase();
      return itemNip === cleanInputNip && item.tanggalWfa === cleanDate;
    });

    if (matches.length === 0) {
      setCheckResult({
        searched: true,
        found: false,
      });
      return;
    }

    // Sort to prioritize latest status update/creation
    const sorted = [...matches].sort((a, b) => {
      const timeA = new Date(a.createdAt || 0).getTime();
      const timeB = new Date(b.createdAt || 0).getTime();
      return timeB - timeA;
    });
    const matched = sorted[0];

    if (matched.status === 'Valid') {
      setCheckResult({
        searched: true,
        found: true,
        submission: matched,
        statusText: 'pengajuan WFA anda pada tanggal tersebut sudah "VALID dan Sudah terjadwal WFA di e-Office Mobile"',
        isSuccessState: true,
      });
    } else if (matched.status === 'Ditolak') {
      setCheckResult({
        searched: true,
        found: true,
        submission: matched,
        statusText: 'Pengajuan WFA Anda pada tanggal tersebut DITOLAK oleh pengelola OSDM',
        isRejectedState: true,
      });
    } else {
      // Pending / Menunggu Validasi
      setCheckResult({
        searched: true,
        found: true,
        submission: matched,
        statusText: 'pengajuan anda masih dalam proses verifikasi',
        isPendingState: true,
      });
    }
  };

  // Real-time synchronization for check status screen when admin updates status or deletes data
  useEffect(() => {
    if (checkResult?.searched && checkNip && checkTanggal) {
      const cleanInputNip = checkNip.replace(/[\s.-]/g, '').trim();
      const cleanDate = checkTanggal.trim();

      const matches = allSubmissions.filter((item) => {
        const itemNip = item.nip.replace(/[\s.-]/g, '').trim();
        return itemNip === cleanInputNip && item.tanggalWfa === cleanDate;
      });

      if (matches.length > 0) {
        const sorted = [...matches].sort((a, b) => {
          const timeA = new Date(a.createdAt || 0).getTime();
          const timeB = new Date(b.createdAt || 0).getTime();
          return timeB - timeA;
        });
        const matched = sorted[0];

        if (matched.status === 'Valid') {
          setCheckResult({
            searched: true,
            found: true,
            submission: matched,
            statusText: 'pengajuan WFA anda pada tanggal tersebut sudah "VALID dan Sudah terjadwal WFA di e-Office Mobile"',
            isSuccessState: true,
          });
        } else if (matched.status === 'Ditolak') {
          setCheckResult({
            searched: true,
            found: true,
            submission: matched,
            statusText: 'Pengajuan WFA Anda pada tanggal tersebut DITOLAK oleh pengelola kepegawaian (OSDM)',
            isRejectedState: true,
          });
        } else {
          setCheckResult({
            searched: true,
            found: true,
            submission: matched,
            statusText: 'pengajuan anda masih dalam proses verifikasi',
            isPendingState: true,
          });
        }
      } else {
        // Submission has been deleted by admin or removed from database
        setCheckResult({
          searched: true,
          found: false,
          statusText: 'Data pengajuan tidak ditemukan. Pengajuan Anda mungkin telah dihapus oleh pengelola kepegawaian (OSDM) atau belum terdaftar.',
        });
      }
    }
  }, [allSubmissions, checkNip, checkTanggal, checkResult?.searched]);

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto bg-slate-950/80 backdrop-blur-md">
        <motion.div
          initial={{ opacity: 0, scale: 0.96, y: 12 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.96, y: 12 }}
          transition={{ duration: 0.22, ease: 'easeOut' }}
          className="relative w-full max-w-3xl xl:max-w-4xl bg-white border border-slate-200/90 rounded-2xl shadow-2xl overflow-hidden my-auto max-h-[92vh] flex flex-col text-slate-800"
        >
          {/* Top Header Banner - Eye Catching Vibrant Institutional Theme */}
          <div className="py-3.5 px-4 sm:px-6 bg-gradient-to-r from-teal-800 via-emerald-800 to-teal-900 text-white shrink-0 shadow-md relative overflow-hidden">
            {/* Subtle decorative background glow */}
            <div className="absolute inset-0 pointer-events-none">
              <div className="absolute -top-10 -right-10 w-44 h-44 bg-emerald-400/10 rounded-full blur-2xl" />
              <div className="absolute -bottom-10 -left-10 w-44 h-44 bg-teal-400/10 rounded-full blur-2xl" />
            </div>

            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 relative">
              {/* Institutional Branding (+ Close button for mobile) */}
              <div className="flex items-center justify-between sm:justify-start gap-3 w-full sm:w-auto">
                <div className="flex items-center gap-3">
                  <div className="bg-white px-2.5 sm:px-3 py-1 rounded-lg border border-white/20 shadow-xs shrink-0">
                    {!hasImgError && logoUrl ? (
                      <img
                        src={logoUrl}
                        alt="Logo Poltekkes Kemenkes Bandung"
                        className="h-6 sm:h-7 md:h-8 w-auto max-w-[150px] sm:max-w-[180px] object-contain"
                        referrerPolicy="no-referrer"
                        onError={() => setHasImgError(true)}
                      />
                    ) : (
                      <div className="flex items-center gap-1.5 text-emerald-950 font-bold text-xs tracking-tight">
                        <span className="w-2 h-2 rounded-full bg-emerald-600" />
                        <span>POLTEKKES KEMENKES BANDUNG</span>
                      </div>
                    )}
                  </div>
                  <div className="text-left hidden md:block">
                    <span className="text-[10px] font-bold tracking-wider text-emerald-200 uppercase block">
                      OSDM Poltekkes Kemenkes Bandung
                    </span>
                    <h2 className="text-xs sm:text-sm md:text-base font-extrabold text-white leading-tight">
                      Portal Layanan Pengajuan WFA Bimbingan
                    </h2>
                  </div>
                </div>

                {/* Mobile Close Button (< sm) */}
                <button
                  onClick={onClose}
                  aria-label="Tutup Formulir"
                  className="sm:hidden p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-all border border-white/20 shadow-xs shrink-0 cursor-pointer"
                  title="Tutup Formulir"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              {/* Navigation Switch Tabs (+ Desktop Close Button) */}
              <div className="flex items-center justify-center sm:justify-end gap-2.5 w-full sm:w-auto">
                <div className="flex items-center p-0.5 bg-black/25 rounded-xl border border-white/20 w-full sm:w-auto justify-center sm:justify-start shadow-inner backdrop-blur-xs">
                  <button
                    type="button"
                    onClick={() => {
                      setActiveTab('form');
                      setErrorMessage(null);
                    }}
                    className={`flex-1 sm:flex-initial py-1.5 px-3 sm:px-3.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                      activeTab === 'form'
                        ? 'bg-white text-emerald-900 shadow-sm'
                        : 'text-emerald-100 hover:text-white hover:bg-white/10'
                    }`}
                  >
                    <FileText className="w-3.5 h-3.5 shrink-0" />
                    <span>Formulir Pengajuan</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setActiveTab('check');
                      setErrorMessage(null);
                    }}
                    className={`flex-1 sm:flex-initial py-1.5 px-3 sm:px-3.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                      activeTab === 'check'
                        ? 'bg-white text-emerald-900 shadow-sm'
                        : 'text-emerald-100 hover:text-white hover:bg-white/10'
                    }`}
                  >
                    <Search className="w-3.5 h-3.5 shrink-0" />
                    <span>Monitoring Status</span>
                  </button>
                </div>

                {/* Desktop Close Button (>= sm) */}
                <button
                  onClick={onClose}
                  aria-label="Tutup Formulir"
                  className="hidden sm:flex items-center justify-center p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-all border border-white/20 shadow-xs shrink-0 cursor-pointer"
                  title="Tutup Formulir"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          {/* Scrollable Modal Body - Crisp, High-Contrast & Eye-Catching */}
          <div
            ref={modalBodyRef}
            className="p-4 sm:p-6 overflow-y-auto space-y-4 text-xs sm:text-sm bg-slate-50/70"
          >
             {/* TAB 1: FORMULIR PENGAJUAN */}
            {activeTab === 'form' && (
              <>
                {successMessage ? (
                  /* Success Confirmation Screen */
                  <motion.div
                    initial={{ opacity: 0, scale: 0.96 }}
                    animate={{ opacity: 1, scale: 1 }}
                    className="relative overflow-hidden p-4 sm:p-5 rounded-2xl bg-white border border-emerald-200 text-center space-y-3 max-w-xl sm:max-w-2xl mx-auto shadow-xl"
                  >
                    {/* Top Accent Gradient Bar */}
                    <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-600" />

                    {/* Submission Sent Icon (Distinct from 'Valid' Checkmark) */}
                    <div className="relative mx-auto w-12 h-12 flex items-center justify-center mt-1">
                      <div className="relative w-12 h-12 rounded-full bg-gradient-to-tr from-teal-600 via-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-600/25">
                        <Send className="w-5 h-5 -translate-x-0.5 translate-y-0.5 text-white" />
                      </div>
                    </div>

                    {/* Header Text */}
                    <div className="space-y-0.5 relative">
                      <h3 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                        Jadwal WFA Berhasil Diajukan
                      </h3>
                      <p className="text-xs text-slate-600 max-w-sm mx-auto leading-relaxed">
                        Mohon menunggu proses verifikasi dan validasi dari Pengelola Kepegawaian.
                      </p>
                    </div>

                    {/* Summary Card (Compact Ticket Style) */}
                    {submittedData && (
                      <div className="rounded-xl bg-slate-50 border border-slate-200 text-left p-3.5 sm:p-4 space-y-2.5 shadow-xs relative overflow-hidden">
                        {/* Row 1: Pegawai Info & Live Status Badge */}
                        <div className="pb-2.5 border-b border-slate-200 space-y-2">
                          {/* Top row: Label & Status Pill */}
                          <div className="flex items-center justify-between gap-2">
                            <span className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pegawai Pemohon</span>
                            {/* Animated Status Pill */}
                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-semibold border border-amber-300 shrink-0 shadow-xs">
                              <span className="relative flex h-1.5 w-1.5">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-1.5 w-1.5 bg-amber-600"></span>
                              </span>
                              Menunggu Validasi Pengelola
                            </span>
                          </div>

                          {/* Employee Detail with Full Width */}
                          <div className="flex items-center gap-2.5 min-w-0">
                            <div className="w-8 h-8 rounded-lg bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-700 shrink-0">
                              <User className="w-3.5 h-3.5" />
                            </div>
                            <div className="min-w-0 flex-1 overflow-hidden">
                              <h4 
                                className={`font-bold text-slate-900 tracking-tight whitespace-nowrap leading-snug ${
                                  submittedData.employeeName.length > 32 
                                    ? 'text-[11px] sm:text-xs md:text-sm' 
                                    : submittedData.employeeName.length > 22 
                                    ? 'text-xs sm:text-sm md:text-base' 
                                    : 'text-[13px] sm:text-base'
                                }`}
                                title={submittedData.employeeName}
                              >
                                {submittedData.employeeName}
                              </h4>
                              <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-600 mt-0.5">
                                <span className="font-mono text-slate-700 font-semibold text-[11px] sm:text-xs">NIP: {submittedData.nip}</span>
                                {submittedData.nomorWa && (
                                  <>
                                    <span className="text-slate-300">•</span>
                                    <span className="inline-flex items-center gap-1 text-emerald-700 font-mono text-[10px] sm:text-[11px] font-semibold">
                                      <MessageCircle className="w-3 h-3" />
                                      {submittedData.nomorWa}
                                    </span>
                                  </>
                                )}
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Row 2: 2-Column Grid (Tanggal/Status & Lahan Bimbingan) */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                          {/* Box 1: Waktu & Lokasi */}
                          <div className="p-2.5 rounded-lg bg-white border border-slate-200 flex items-start gap-2 shadow-xs">
                            <div className="w-6 h-6 rounded-md bg-teal-50 text-teal-700 border border-teal-200 flex items-center justify-center shrink-0 mt-0.5">
                              <Calendar className="w-3 h-3" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <div className="flex items-center justify-between gap-1.5">
                                <span className="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Tanggal Pelaksanaan</span>
                                {submittedData.statusWfa && (
                                  <span className="hidden sm:inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-bold shrink-0">
                                    ({submittedData.statusWfa})
                                  </span>
                                )}
                              </div>
                              <div className="mt-0.5 flex flex-col sm:flex-row sm:items-center sm:gap-2">
                                <span className="font-bold text-slate-900 text-xs shrink-0">{submittedData.tanggalWfa}</span>
                                <div className="flex items-center gap-1 text-[11px] text-slate-800 font-semibold leading-tight">
                                  <span className="hidden sm:inline text-slate-300">•</span>
                                  <MapPin className="w-3 h-3 shrink-0 text-amber-600" />
                                  <span className="truncate">{submittedData.lokasiKegiatan}</span>
                                </div>
                              </div>
                              {submittedData.statusWfa && (
                                <div className="mt-1 sm:hidden">
                                  <span className="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-bold">
                                    ({submittedData.statusWfa})
                                  </span>
                                </div>
                              )}
                            </div>
                          </div>

                          {/* Box 2: Lahan Bimbingan */}
                          <div className="p-2.5 rounded-lg bg-white border border-slate-200 flex items-start gap-2 shadow-xs">
                            <div className="w-6 h-6 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0 mt-0.5">
                              <Building2 className="w-3 h-3" />
                            </div>
                            <div className="min-w-0">
                              <span className="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Lahan Bimbingan</span>
                              <span className="font-bold text-emerald-900 text-xs block mt-0.5 leading-snug line-clamp-2" title={submittedData.lokasiLahanBimbingan}>
                                {submittedData.lokasiLahanBimbingan}
                              </span>
                            </div>
                          </div>
                        </div>

                        {/* Bottom Hint */}
                        <div className="pt-0.5 flex items-center gap-1.5 text-[11px] text-slate-600">
                          <Info className="w-3.5 h-3.5 text-emerald-700 shrink-0" />
                          <span>Pantau status pengajuan kapan saja via tab <strong>Monitoring Status</strong>.</span>
                        </div>
                      </div>
                    )}

                    {/* Action Buttons */}
                    <div className="pt-1 flex flex-col sm:flex-row items-center justify-center gap-2.5">
                      <button
                        type="button"
                        onClick={() => {
                          if (submittedData) {
                            setCheckNip(submittedData.nip);
                            setCheckTanggal(submittedData.tanggalWfa);
                          }
                          setActiveTab('check');
                          setSuccessMessage(null);
                        }}
                        className="w-full sm:w-auto px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-xs transition-all shadow-md active:scale-[0.98] flex items-center justify-center gap-1.5 cursor-pointer"
                      >
                        <Search className="w-3.5 h-3.5" />
                        <span>Cek Status Pengajuan Ini</span>
                      </button>

                      <button
                        type="button"
                        onClick={handleResetForm}
                        className="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 font-semibold text-xs transition-all border border-slate-200 active:scale-[0.98] flex items-center justify-center gap-1.5 cursor-pointer"
                      >
                        <RotateCcw className="w-3.5 h-3.5 text-slate-500" />
                        <span>Ajukan Jadwal Lain</span>
                      </button>
                    </div>
                  </motion.div>
                ) : (
                  <form onSubmit={handleSubmitForm} className="space-y-4">
                    {/* Policy Banner with Crisp Light Emerald Contrast */}
                    <div className="p-3.5 sm:p-4 rounded-xl bg-gradient-to-r from-emerald-50 via-teal-50/60 to-emerald-50 border border-emerald-200 flex items-start gap-3 shadow-xs">
                      <div className="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                        <ShieldCheck className="w-4 h-4" />
                      </div>
                      <div className="space-y-0.5 text-xs">
                        <div className="flex items-center gap-2 flex-wrap">
                          <span className="font-bold text-slate-900 text-xs sm:text-sm">Ketentuan Pengajuan WFA Bimbingan</span>
                          <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold border border-emerald-200">
                            Resmi OSDM
                          </span>
                        </div>
                        <p className="text-[11px] sm:text-xs text-slate-700 leading-relaxed">
                          Pengajuan diperbolehkan mulai hari ini (<strong className="text-emerald-800 font-bold">{formattedMinDate}</strong>) sampai dengan <strong className="text-emerald-800 font-bold">H+3 hari kalender ({formattedMaxDate})</strong>. Tidak diperbolehkan memilih tanggal mundur dan tidak diperbolehkan mengajukan dua kali di tanggal yang sama.
                        </p>
                        <p className="text-[11px] text-amber-900 font-medium flex items-center gap-1 pt-0.5">
                          <span>* Seluruh kolom bertanda</span>
                          <span className="text-rose-600 font-bold">*</span>
                          <span>wajib diisi dengan lengkap.</span>
                        </p>
                      </div>
                    </div>

                    {/* SECTION 1: DATA IDENTITAS PEGAWAI */}
                    <div className="p-4 sm:p-5 rounded-xl bg-white border border-slate-200 shadow-xs space-y-4">
                      <div className="flex flex-wrap items-center justify-between gap-2.5 border-b border-slate-100 pb-3">
                        <div className="flex items-center gap-2.5">
                          <span className="w-6 h-6 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shadow-xs shrink-0">
                            1
                          </span>
                          <h4 className="font-bold text-sm text-slate-900 tracking-tight">
                            Identitas Pegawai
                          </h4>
                        </div>
                        <button
                          type="button"
                          onClick={() => setShowNipSuggestions(!showNipSuggestions)}
                          className="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 hover:text-emerald-900 font-semibold text-xs flex items-center gap-1.5 transition-all cursor-pointer"
                        >
                          <Search className="w-3.5 h-3.5 text-emerald-700" />
                          <span>Pilih dari Pangkalan Data</span>
                        </button>
                      </div>

                      {/* Autocomplete Directory Popup */}
                      {showNipSuggestions && (
                        <div className="p-3.5 rounded-xl bg-white border border-emerald-300 shadow-xl space-y-2.5">
                          <div className="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                              <Building2 className="w-3.5 h-3.5 text-emerald-700" />
                              <span>Pangkalan Data Pegawai Poltekkes Bandung:</span>
                            </span>
                            <button
                              type="button"
                              onClick={() => setShowNipSuggestions(false)}
                              className="text-slate-500 hover:text-slate-800 text-[11px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 border border-slate-200 font-medium cursor-pointer transition-colors"
                            >
                              Tutup ✕
                            </button>
                          </div>
                          <input
                            type="text"
                            placeholder="Ketik nama atau 18 digit NIP pegawai untuk mencari..."
                            value={nipSearchKeyword}
                            onChange={(e) => setNipSearchKeyword(e.target.value)}
                            className="w-full px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-500/20"
                          />
                          <div className="space-y-1 divide-y divide-slate-100 max-h-52 overflow-y-auto pr-1">
                            {searchEmployees(nipSearchKeyword).map((emp) => (
                              <button
                                key={emp.nip}
                                type="button"
                                onClick={() => handleSelectEmployee(emp)}
                                className="w-full text-left py-2 px-2.5 rounded-lg hover:bg-emerald-50/70 transition-colors flex items-center justify-between gap-3 group cursor-pointer"
                              >
                                <div className="min-w-0">
                                  <div className="flex items-center gap-2">
                                    <p className="text-xs font-semibold text-slate-900 group-hover:text-emerald-800 truncate">
                                      {emp.name}
                                    </p>
                                    {emp.status && emp.status !== 'Aktif' && (
                                      <span className="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200 shrink-0">
                                        {emp.status}
                                      </span>
                                    )}
                                  </div>
                                  <p className="text-[11px] font-mono text-slate-500 mt-0.5">
                                    NIP: {emp.nip} • {emp.unitKerja}
                                  </p>
                                </div>
                                <span className="text-xs text-emerald-700 shrink-0 font-bold opacity-0 group-hover:opacity-100 transition-opacity">
                                  Pilih →
                                </span>
                              </button>
                            ))}
                          </div>
                        </div>
                      )}

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
                        {/* WARNING BANNER FOR NON-ACTIVE STATUS */}
                        {isEmployeeFound && employeeStatus !== 'Aktif' && (
                          <div className="md:col-span-2 p-3 sm:p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5 shadow-xs">
                            <AlertCircle className="w-4 h-4 shrink-0 text-rose-600 mt-0.5" />
                            <div className="space-y-0.5">
                              <p className="font-semibold text-rose-900 text-xs leading-relaxed">
                                ⛔ Pengajuan WFA Dibatasi : Pegawai atas nama <strong>{employeeName}</strong> saat ini sedang berstatus <strong>{employeeStatus}</strong>. Tidak diperkenankan mengajukan permohonan WFA Bimbingan.
                              </p>
                            </div>
                          </div>
                        )}

                        {/* FIELD 1: NIP */}
                        <div className="space-y-1.5">
                          <label htmlFor={`${formId}-nip`} className="text-xs font-bold text-slate-700 flex items-center justify-between">
                            <span className="flex items-center gap-0.5">
                              <span>Nomor Induk Pegawai (NIP)</span>
                              <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                            </span>
                            {isEmployeeFound && (
                              <span className="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md text-[11px] font-bold flex items-center gap-1">
                                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                <span>Terverifikasi</span>
                              </span>
                            )}
                          </label>

                          <div className="relative">
                            <input
                              id={`${formId}-nip`}
                              type="text"
                              placeholder="Ketik 18 digit NIP..."
                              value={nip}
                              onChange={(e) => {
                                setNip(e.target.value);
                                setErrorMessage(null);
                              }}
                              className={`w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border ${
                                isEmployeeFound
                                  ? employeeStatus !== 'Aktif'
                                    ? 'border-amber-400 bg-amber-50/40 text-slate-900'
                                    : 'border-emerald-500 bg-emerald-50/20 text-slate-900'
                                  : nip.trim().length >= 4
                                  ? 'border-rose-300 bg-rose-50/20 text-slate-900 focus:border-rose-500'
                                  : 'border-slate-200 hover:border-slate-300 focus:border-emerald-600 focus:bg-white text-slate-900'
                              } text-xs sm:text-sm font-mono font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all placeholder:text-slate-400`}
                            />
                          </div>
                          <p className="text-[11px] text-slate-500">
                            {isEmployeeFound ? (
                              employeeStatus !== 'Aktif' ? (
                                <span className="text-amber-800 font-semibold flex items-center gap-1">
                                  <AlertTriangle className="w-3.5 h-3.5 text-amber-600 shrink-0" />
                                  <span>Status: {employeeStatus} (WFA Tidak Diperbolehkan).</span>
                                </span>
                              ) : (
                                <span className="text-emerald-700 font-semibold flex items-center gap-1">
                                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                  <span>NIP terdaftar di database Poltekkes Bandung (Status: Aktif).</span>
                                </span>
                              )
                            ) : nip.trim().length >= 4 ? (
                              <span className="text-rose-600 font-semibold flex items-center gap-1">
                                <AlertCircle className="w-3.5 h-3.5 text-rose-500 shrink-0" />
                                <span>NIP tidak ditemukan dalam database. Pastikan tidak ada kesalahan ketik (typo).</span>
                              </span>
                            ) : (
                              'Ketik 18 digit NIP untuk memuat nama & unit kerja secara otomatis.'
                            )}
                          </p>
                        </div>

                        {/* FIELD 2: NAMA PEGAWAI (READONLY / OTOMATIS DARI NIP) */}
                        <div className="space-y-1.5">
                          <div className="flex items-center justify-between">
                            <label htmlFor={`${formId}-nama-pegawai`} className="text-xs font-bold text-slate-700 flex items-center gap-0.5">
                              <span>Nama Lengkap Pegawai</span>
                              <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                            </label>
                            <span className="text-[10px] text-slate-500 font-medium bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                              Terkunci (Otomatis dari NIP)
                            </span>
                          </div>
                          <div className="relative">
                            <input
                              id={`${formId}-nama-pegawai`}
                              type="text"
                              readOnly
                              tabIndex={-1}
                              placeholder="Nama pegawai terisi otomatis dari NIP..."
                              value={employeeName}
                              className={`w-full px-3.5 py-2.5 rounded-xl border ${
                                isEmployeeFound
                                  ? 'bg-slate-100/90 border-emerald-300 text-slate-900 font-semibold cursor-not-allowed select-none'
                                  : 'bg-slate-100/60 border-slate-200 text-slate-500 cursor-not-allowed select-none'
                              } text-xs sm:text-sm transition-all placeholder:text-slate-400 focus:outline-none`}
                            />
                            {isEmployeeFound && (
                              <div className="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60 shadow-xs pointer-events-none">
                                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                <span>Terverifikasi</span>
                              </div>
                            )}
                          </div>
                          {unitKerja && (
                            <div className="flex items-center gap-1.5 flex-wrap pt-0.5">
                              <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-100 text-[11px] text-slate-700 border border-slate-200 font-medium">
                                <Building2 className="w-3 h-3 text-emerald-700" />
                                <span>{unitKerja}</span>
                              </span>
                              {employeeStatus && (
                                <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-bold border ${
                                  employeeStatus === 'Aktif'
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-rose-50 text-rose-800 border-rose-200'
                                }`}>
                                  <span>Status: {employeeStatus}</span>
                                </span>
                              )}
                              {jabatan && (
                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-teal-50 text-[11px] text-teal-800 border border-teal-200 font-medium">
                                  <Briefcase className="w-3 h-3 text-teal-700" />
                                  <span>{jabatan}</span>
                                </span>
                              )}
                            </div>
                          )}
                        </div>

                        {/* FIELD 3: NOMOR WHATSAPP PEGAWAI */}
                        <div className="md:col-span-2 space-y-1.5 pt-2.5 border-t border-slate-100">
                          <div className="flex items-center justify-between flex-wrap gap-2">
                            <label htmlFor={`${formId}-nomor-wa`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                              <MessageCircle className="w-3.5 h-3.5 text-emerald-700" />
                              <span>Nomor WhatsApp Pegawai (Aktif)</span>
                              <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                            </label>
                            <span className="text-[11px] px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                              Untuk Komunikasi &amp; Konfirmasi Persyaratan OSDM
                            </span>
                          </div>
                          <div className="relative">
                            <div className="absolute left-3.5 top-1/2 -translate-y-1/2 flex items-center gap-1 text-xs font-mono font-bold text-slate-500 pointer-events-none">
                              <span className="text-emerald-700 font-bold">WA</span>
                              <span className="text-slate-300">|</span>
                            </div>
                            <input
                              id={`${formId}-nomor-wa`}
                              type="tel"
                              placeholder="Contoh: 081234567890 atau 6281234567890"
                              value={nomorWa}
                              onChange={(e) => {
                                setNomorWa(e.target.value);
                                setErrorMessage(null);
                              }}
                              className="w-full pl-14 pr-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-mono font-medium focus:outline-none placeholder:text-slate-400 transition-all"
                            />
                          </div>
                          <p className="text-[11px] text-slate-500 leading-relaxed">
                            Nomor WhatsApp aktif digunakan oleh Admin Pengelola OSDM untuk konfirmasi penugasan dan kelengkapan berkas WFA Anda secara langsung.
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* SECTION 2: DETAIL WAKTU & LOKASI KEGIATAN */}
                    <div className="p-4 sm:p-5 rounded-xl bg-white border border-slate-200 shadow-xs space-y-4">
                      <div className="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                        <span className="w-6 h-6 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shadow-xs shrink-0">
                          2
                        </span>
                        <h4 className="font-bold text-sm text-slate-900 tracking-tight">
                          Waktu &amp; Lokasi Penugasan Bimbingan
                        </h4>
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
                        {/* FIELD 4: TANGGAL WFA */}
                        <div className="space-y-1.5">
                          <div className="flex items-center justify-between flex-wrap gap-2">
                            <label htmlFor={`${formId}-tanggal-wfa`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                              <Calendar className="w-3.5 h-3.5 text-emerald-700" />
                              <span>Tanggal Pelaksanaan WFA</span>
                              <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                            </label>
                            <span className="text-[11px] px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                              Hari H s/d H+3 Kalender
                            </span>
                          </div>

                          <input
                            id={`${formId}-tanggal-wfa`}
                            type="date"
                            min={minDate}
                            max={maxDate}
                            value={tanggalWfa}
                            onChange={(e) => handleTanggalWfaChange(e.target.value)}
                            onClick={(e) => {
                              try {
                                e.currentTarget.showPicker();
                              } catch {}
                            }}
                            className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-medium focus:outline-none cursor-pointer transition-all"
                          />
                          {checkIsUrgentOffHoursWfa(tanggalWfa, statusKota === 'WFA Pulang' || kabPulang) && (
                            <div className="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-center justify-between gap-2 shadow-xs">
                              <span className="flex items-center gap-1.5 font-medium leading-tight">
                                <AlertTriangle className="w-3.5 h-3.5 text-amber-600 shrink-0" />
                                <span>{getOffHoursWarningInfo(tanggalWfa, statusKota === 'WFA Pulang' || kabPulang).message}</span>
                              </span>
                              <button
                                type="button"
                                onClick={triggerUrgentOffHoursWarning}
                                className="text-amber-800 hover:text-amber-950 font-bold underline shrink-0 cursor-pointer text-[11px]"
                              >
                                Lihat Peringatan
                              </button>
                            </div>
                          )}
                          {checkIsUrgentWfaPulang(tanggalWfa, statusKota === 'WFA Pulang' || kabPulang) && (
                            <div className="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-center justify-between gap-2 shadow-xs">
                              <span className="flex items-center gap-1.5 font-medium leading-tight">
                                <AlertTriangle className="w-3.5 h-3.5 text-amber-600 shrink-0" />
                                <span>Pengajuan mendadak WFA Pulang di hari H pukul 15.00 s.d 16.30 WIB.</span>
                              </span>
                              <button
                                type="button"
                                onClick={triggerUrgentPulangWarning}
                                className="text-amber-800 hover:text-amber-950 font-bold underline shrink-0 cursor-pointer text-[11px]"
                              >
                                Lihat Peringatan
                              </button>
                            </div>
                          )}
                          <p className="text-[11px] text-slate-500 flex items-center gap-1">
                            <Info className="w-3.5 h-3.5 text-emerald-700 shrink-0" />
                            <span>Rentang: <strong className="text-slate-800 font-mono">{minDate}</strong> s/d <strong className="text-slate-800 font-mono">{maxDate}</strong> (tidak boleh tanggal mundur).</span>
                          </p>
                        </div>

                        {/* FIELD 5: LOKASI KEGIATAN */}
                        <div className="space-y-1.5">
                          <label htmlFor={`${formId}-lokasi-kegiatan`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <MapPin className="w-3.5 h-3.5 text-rose-500" />
                            <span>Wilayah Lokasi Kegiatan</span>
                            <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                          </label>
                          <select
                            id={`${formId}-lokasi-kegiatan`}
                            value={lokasiKegiatan}
                            onChange={(e) => handleLokasiChange(e.target.value as WfaLocation)}
                            className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-medium focus:outline-none cursor-pointer transition-all"
                          >
                            <option value="">-- Pilih Wilayah Kegiatan --</option>
                            {locationConfig.options.map((opt) => (
                              <option key={opt} value={opt}>
                                {opt}
                              </option>
                            ))}
                          </select>
                          <p className="text-[11px] text-slate-500">
                            Wilayah penugasan otomatis untuk unit kerja {unitKerja ? `(${unitKerja})` : 'Anda'}: {locationConfig.options.join(' atau ')}.
                          </p>
                        </div>
                      </div>

                      {/* SECTION 2B: PILIHAN STATUS WFA SESUAI ATURAN WILAYAH */}
                      {isKota && (
                        <motion.div
                          initial={{ opacity: 0, y: -4 }}
                          animate={{ opacity: 1, y: 0 }}
                          className="p-3.5 sm:p-4 rounded-xl bg-indigo-50/50 border border-indigo-200 space-y-2.5"
                        >
                          <div className="flex items-center justify-between flex-wrap gap-2">
                            <label className="text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                              <span className="w-2 h-2 rounded-full bg-indigo-600" />
                              <span>Ketentuan Status Presensi (Khusus {lokasiKegiatan}):</span>
                              <span className="text-rose-500 font-bold text-xs">*</span>
                            </label>
                            <span className="text-[10px] px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800 font-bold border border-indigo-200">
                              Wajib Pilih Salah Satu
                            </span>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-0.5">
                            {/* Option 1: WFA Datang */}
                            <label
                              className={`flex items-start gap-2.5 p-3 rounded-xl border transition-all cursor-pointer ${
                                statusKota === 'WFA Datang'
                                  ? 'bg-emerald-50/90 border-emerald-500 text-emerald-950 shadow-xs ring-1 ring-emerald-500/30'
                                  : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50/50'
                              }`}
                            >
                              <input
                                type="radio"
                                name="wfa-kota-choice"
                                value="WFA Datang"
                                checked={statusKota === 'WFA Datang'}
                                onChange={() => {
                                  setStatusKota('WFA Datang');
                                  setErrorMessage(null);
                                }}
                                className="mt-0.5 accent-emerald-600 w-4 h-4 cursor-pointer shrink-0"
                              />
                              <div>
                                <p className="font-bold text-xs sm:text-sm text-slate-900">WFA Datang</p>
                                <p className="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                                  Presensi datang di luar area kantor, presensi pulang di dalam area kantor.
                                </p>
                              </div>
                            </label>

                            {/* Option 2: WFA Pulang */}
                            <label
                              className={`flex items-start gap-2.5 p-3 rounded-xl border transition-all cursor-pointer ${
                                statusKota === 'WFA Pulang'
                                  ? 'bg-emerald-50/90 border-emerald-500 text-emerald-950 shadow-xs ring-1 ring-emerald-500/30'
                                  : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50/50'
                              }`}
                            >
                              <input
                                type="radio"
                                name="wfa-kota-choice"
                                value="WFA Pulang"
                                checked={statusKota === 'WFA Pulang'}
                                onChange={() => {
                                  setStatusKota('WFA Pulang');
                                  setErrorMessage(null);
                                  if (checkIsUrgentOffHoursWfa(tanggalWfa, true)) {
                                    triggerUrgentOffHoursWarning();
                                  } else if (checkIsUrgentWfaPulang(tanggalWfa, true)) {
                                    triggerUrgentPulangWarning();
                                  }
                                }}
                                className="mt-0.5 accent-emerald-600 w-4 h-4 cursor-pointer shrink-0"
                              />
                              <div>
                                <p className="font-bold text-xs sm:text-sm text-slate-900">WFA Pulang</p>
                                <p className="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                                  Presensi datang di dalam area kantor, presensi pulang di luar area kantor.
                                </p>
                              </div>
                            </label>
                          </div>
                        </motion.div>
                      )}

                      {isKabupaten && (
                        <motion.div
                          initial={{ opacity: 0, y: -4 }}
                          animate={{ opacity: 1, y: 0 }}
                          className="p-3.5 sm:p-4 rounded-xl bg-teal-50/50 border border-teal-200 space-y-2.5"
                        >
                          <div className="flex items-center justify-between flex-wrap gap-2">
                            <label className="text-xs font-bold text-teal-950 flex items-center gap-1.5">
                              <span className="w-2 h-2 rounded-full bg-teal-600" />
                              <span>Ketentuan Status Presensi (Khusus {lokasiKegiatan}):</span>
                              <span className="text-rose-500 font-bold text-xs">*</span>
                            </label>
                            <span className="text-[10px] px-2 py-0.5 rounded-md bg-teal-100 text-teal-800 font-bold border border-teal-200">
                              Dapat Dipilih Salah Satu atau Keduanya
                            </span>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-0.5">
                            {/* Checkbox 1: WFA Datang */}
                            <label
                              className={`flex items-start gap-2.5 p-3 rounded-xl border transition-all cursor-pointer ${
                                kabDatang
                                  ? 'bg-emerald-50/90 border-emerald-500 text-emerald-950 shadow-xs ring-1 ring-emerald-500/30'
                                  : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50/50'
                              }`}
                            >
                              <input
                                type="checkbox"
                                checked={kabDatang}
                                onChange={(e) => {
                                  setKabDatang(e.target.checked);
                                  setErrorMessage(null);
                                }}
                                className="mt-0.5 accent-emerald-600 w-4 h-4 cursor-pointer rounded shrink-0"
                              />
                              <div>
                                <p className="font-bold text-xs sm:text-sm text-slate-900">WFA Datang</p>
                                <p className="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                                  Presensi datang dilakukan di lokasi bimbingan luar kampus.
                                </p>
                              </div>
                            </label>

                            {/* Checkbox 2: WFA Pulang */}
                            <label
                              className={`flex items-start gap-2.5 p-3 rounded-xl border transition-all cursor-pointer ${
                                kabPulang
                                  ? 'bg-emerald-50/90 border-emerald-500 text-emerald-950 shadow-xs ring-1 ring-emerald-500/30'
                                  : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50/50'
                              }`}
                            >
                              <input
                                type="checkbox"
                                checked={kabPulang}
                                onChange={(e) => {
                                  const checked = e.target.checked;
                                  setKabPulang(checked);
                                  setErrorMessage(null);
                                  if (checked) {
                                    if (checkIsUrgentOffHoursWfa(tanggalWfa, true)) {
                                      triggerUrgentOffHoursWarning();
                                    } else if (checkIsUrgentWfaPulang(tanggalWfa, true)) {
                                      triggerUrgentPulangWarning();
                                    }
                                  }
                                }}
                                className="mt-0.5 accent-emerald-600 w-4 h-4 cursor-pointer rounded shrink-0"
                              />
                              <div>
                                <p className="font-bold text-xs sm:text-sm text-slate-900">WFA Pulang</p>
                                <p className="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                                  Presensi pulang dilakukan di lokasi bimbingan luar kampus.
                                </p>
                              </div>
                            </label>
                          </div>

                          {kabDatang && kabPulang && (
                            <div className="p-2.5 rounded-lg bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-semibold flex items-center gap-2">
                              <Check className="w-4 h-4 text-emerald-700 shrink-0" />
                              <span>
                                Terpilih: <strong className="text-emerald-950">WFA Datang &amp; WFA Pulang</strong> (Full Day WFA {lokasiKegiatan}).
                              </span>
                            </div>
                          )}
                        </motion.div>
                      )}

                      {/* Alert banner jika WFA Pulang di hari H dipilih di luar jam kerja (16.01/16.31 s.d 18.59 WIB) */}
                      {checkIsUrgentOffHoursWfa(tanggalWfa, statusKota === 'WFA Pulang' || kabPulang) && (statusKota === 'WFA Pulang' || kabPulang) && (
                        <div className="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-center justify-between gap-2 shadow-xs">
                          <span className="flex items-center gap-1.5 font-medium leading-tight">
                            <AlertTriangle className="w-3.5 h-3.5 text-amber-600 shrink-0" />
                            <span>{getOffHoursWarningInfo(tanggalWfa, statusKota === 'WFA Pulang' || kabPulang).message}</span>
                          </span>
                          <button
                            type="button"
                            onClick={triggerUrgentOffHoursWarning}
                            className="text-amber-800 hover:text-amber-950 font-bold underline shrink-0 cursor-pointer text-[11px]"
                          >
                            Lihat Peringatan
                          </button>
                        </div>
                      )}

                      {/* Alert banner jika WFA Pulang di hari H dipilih pada rentang jam kerja menjelang jam pulang */}
                      {checkIsUrgentWfaPulang(tanggalWfa, statusKota === 'WFA Pulang' || kabPulang) && (
                        <div className="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-center justify-between gap-2 shadow-xs">
                          <span className="flex items-center gap-1.5 font-medium leading-tight">
                            <AlertTriangle className="w-3.5 h-3.5 text-amber-600 shrink-0" />
                            <span>Pengajuan mendadak WFA Pulang untuk hari ini (Hari H) menjelang jam pulang kantor.</span>
                          </span>
                          <button
                            type="button"
                            onClick={triggerUrgentPulangWarning}
                            className="text-amber-800 hover:text-amber-950 font-bold underline shrink-0 cursor-pointer text-[11px]"
                          >
                            Lihat Peringatan
                          </button>
                        </div>
                      )}
                    </div>

                    {/* SECTION 3: FASILITAS LAHAN & DOKUMEN */}
                    <div className="p-4 sm:p-5 rounded-xl bg-white border border-slate-200 shadow-xs space-y-4">
                      <div className="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                        <span className="w-6 h-6 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shadow-xs shrink-0">
                          3
                        </span>
                        <h4 className="font-bold text-sm text-slate-900 tracking-tight">
                          Detail Lahan Bimbingan &amp; Berkas Tugas
                        </h4>
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
                        {/* FIELD 6: LOKASI LAHAN BIMBINGAN */}
                        <div className="space-y-1.5">
                          <label htmlFor={`${formId}-lokasi-lahan-bimbingan`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <MapPin className="w-3.5 h-3.5 text-emerald-700" />
                            <span>Lokasi Lahan Bimbingan</span>
                            <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                          </label>
                          <input
                            id={`${formId}-lokasi-lahan-bimbingan`}
                            type="text"
                            placeholder="Contoh: RSUP Dr. Hasan Sadikin, RSUD Al-Ihsan, Puskesmas Ibrahim Adjie..."
                            value={lokasiLahanBimbingan}
                            onChange={(e) => {
                              setLokasiLahanBimbingan(e.target.value);
                              setErrorMessage(null);
                            }}
                            className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-medium focus:outline-none placeholder:text-slate-400 transition-all"
                          />
                          <p className="text-[11px] text-slate-500">
                            Nama rumah sakit, puskesmas, klinik, atau fasilitas lahan bimbingan praktik.
                          </p>
                        </div>

                        {/* FIELD 7: LINK SURAT TUGAS */}
                        <div className="space-y-1.5">
                          <label htmlFor={`${formId}-link-surat-tugas`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <FileText className="w-3.5 h-3.5 text-emerald-700" />
                            <span>Link Surat Tugas (Google Drive)</span>
                            <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                          </label>
                          <input
                            id={`${formId}-link-surat-tugas`}
                            type="url"
                            placeholder="https://drive.google.com/..."
                            value={linkSuratTugas}
                            onChange={(e) => {
                              setLinkSuratTugas(e.target.value);
                              setErrorMessage(null);
                            }}
                            className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-mono focus:outline-none placeholder:text-slate-400 transition-all"
                          />
                          <p className="text-[11px] text-slate-500">
                            Salin tautan folder/file Google Drive surat tugas resmi (pastikan akses tautan publik/dapat dilihat pengelola).
                          </p>
                        </div>
                      </div>

                      {/* FIELD 8: NAMA KEGIATAN (Full Width) */}
                      <div className="space-y-1.5 pt-0.5">
                        <label htmlFor={`${formId}-nama-kegiatan`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                          <FileText className="w-3.5 h-3.5 text-emerald-700" />
                          <span>Deskripsi Nama Kegiatan Bimbingan</span>
                          <span className="text-rose-500 font-bold text-xs" title="Wajib diisi">*</span>
                        </label>
                        <textarea
                          id={`${formId}-nama-kegiatan`}
                          rows={2}
                          placeholder="Contoh: Bimbingan Praktik Klinik Keperawatan / Kebidanan Mahasiswa Semester VI"
                          value={namaKegiatan}
                          onChange={(e) => {
                            setNamaKegiatan(e.target.value);
                            setErrorMessage(null);
                          }}
                          className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-medium focus:outline-none placeholder:text-slate-400 resize-none leading-relaxed transition-all"
                        />
                      </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="pt-3 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200">
                      <p className="text-[11px] text-slate-500 text-center sm:text-left">
                        Pastikan seluruh isian data penugasan telah benar sebelum menekan tombol ajukan.
                      </p>

                      <div className="flex items-center gap-2.5 w-full sm:w-auto">
                        <button
                          type="button"
                          onClick={onClose}
                          className="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs sm:text-sm font-semibold transition-all border border-slate-200 cursor-pointer"
                        >
                          Batal
                        </button>

                        <button
                          type="submit"
                          disabled={
                            isSubmitting ||
                            (isEmployeeFound && employeeStatus !== 'Aktif') ||
                            (nip.trim().length > 0 && !isEmployeeFound)
                          }
                          className={`flex-1 sm:flex-none px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer ${
                            isSubmitting ||
                            (isEmployeeFound && employeeStatus !== 'Aktif') ||
                            (nip.trim().length > 0 && !isEmployeeFound)
                              ? 'bg-slate-200 text-slate-400 border border-slate-200 cursor-not-allowed'
                              : 'bg-emerald-600 hover:bg-emerald-700 text-white hover:scale-[1.01] active:scale-[0.99]'
                          }`}
                        >
                          {isSubmitting ? (
                            <>
                              <span className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                              <span>Memproses Pengajuan...</span>
                            </>
                          ) : isEmployeeFound && employeeStatus !== 'Aktif' ? (
                            <>
                              <AlertCircle className="w-4 h-4 text-rose-500" />
                              <span>WFA Tidak Diperkenankan ({employeeStatus})</span>
                            </>
                          ) : nip.trim().length > 0 && !isEmployeeFound ? (
                            <>
                              <AlertCircle className="w-4 h-4 text-rose-400" />
                              <span>NIP Tidak Terdaftar</span>
                            </>
                          ) : (
                            <>
                              <Send className="w-4 h-4" />
                              <span>Ajukan Jadwal WFA</span>
                            </>
                          )}
                        </button>
                      </div>
                    </div>
                  </form>
                )}
              </>
            )}

            {/* TAB 2: PENGECEKAN PENGAJUAN (CEK STATUS MANDIRI) */}
            {activeTab === 'check' && (
              <div className="space-y-4 max-w-2xl mx-auto">
                <div className="p-4 sm:p-5 rounded-xl bg-white border border-slate-200 space-y-3.5 shadow-xs">
                  <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div className="flex items-center gap-2 text-emerald-800 text-sm sm:text-base font-bold">
                      <Search className="w-4 h-4 text-emerald-700" />
                      <span>Monitoring Status Pengajuan WFA Bimbingan</span>
                    </div>
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                      <span className="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse" />
                      <span>Server OSDM Terhubung (Real-time)</span>
                    </span>
                  </div>
                  <p className="text-xs text-slate-600 leading-relaxed">
                    Masukkan 18 digit NIP dan Tanggal WFA Anda untuk mengetahui status pengajuan.
                  </p>

                  <form onSubmit={handleCheckStatus} className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                    <div className="space-y-1.5">
                      <label htmlFor={`${formId}-check-nip`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                        <User className="w-3.5 h-3.5 text-emerald-700" />
                        <span>NIP Pegawai:</span>
                      </label>
                      <input
                        id={`${formId}-check-nip`}
                        type="text"
                        placeholder="Contoh: 198501012010121001"
                        value={checkNip}
                        onChange={(e) => setCheckNip(e.target.value)}
                        className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-mono font-medium focus:outline-none placeholder:text-slate-400 transition-all"
                      />
                    </div>

                    <div className="space-y-1.5">
                      <label htmlFor={`${formId}-check-tanggal`} className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                        <Calendar className="w-3.5 h-3.5 text-emerald-700" />
                        <span>Tanggal WFA:</span>
                      </label>
                      <input
                        id={`${formId}-check-tanggal`}
                        type="date"
                        value={checkTanggal}
                        onChange={(e) => setCheckTanggal(e.target.value)}
                        onClick={(e) => {
                          try {
                            e.currentTarget.showPicker();
                          } catch {}
                        }}
                        className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 text-slate-900 text-xs sm:text-sm font-medium focus:outline-none cursor-pointer transition-all"
                      />
                    </div>

                    <div className="sm:col-span-2 pt-1 flex items-center justify-end">
                      <button
                        type="submit"
                        className="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
                      >
                        <Search className="w-4 h-4" />
                        <span>Cek Status Pengajuan</span>
                      </button>
                    </div>
                  </form>
                </div>

                {/* RESULT DISPLAY */}
                {checkResult?.searched && (
                  <motion.div
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="space-y-4"
                  >
                    {/* CONDITION 1: STATUS VALID */}
                    {checkResult.isSuccessState && checkResult.submission && (
                      <div className="p-4 sm:p-5 rounded-2xl bg-gradient-to-b from-emerald-50 to-white border border-emerald-300 shadow-lg text-center space-y-3">
                        <div className="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center mx-auto shadow-sm">
                          <CheckCircle2 className="w-5 h-5 text-white" />
                        </div>

                        <div>
                          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-600 text-white font-bold text-[10px] uppercase tracking-wider mb-1.5 shadow-xs">
                            ✓ STATUS: VALID
                          </span>

                          {/* Required exact phrasing */}
                          <h3 className="text-xs sm:text-sm font-bold text-slate-900 leading-relaxed max-w-md mx-auto">
                            pengajuan WFA anda telah &quot;VALID dan Sudah terjadwal WFA di e-Office Mobile&quot;
                          </h3>
                        </div>

                        {/* Submission details compact pass card */}
                        <div className="p-3.5 sm:p-4 rounded-xl bg-white border border-emerald-200 text-left space-y-2.5 max-w-xl sm:max-w-2xl mx-auto shadow-xs">
                          {/* Employee Header */}
                          <div className="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                            <div className="w-8 h-8 rounded-lg bg-emerald-100 border border-emerald-200 text-emerald-800 flex items-center justify-center shrink-0">
                              <User className="w-3.5 h-3.5 text-emerald-700" />
                            </div>
                            <div className="min-w-0 flex-1 overflow-x-auto no-scrollbar">
                              <h4 
                                className={`font-bold text-slate-900 tracking-tight whitespace-nowrap leading-snug ${
                                  checkResult.submission.employeeName.length > 35 
                                    ? 'text-xs sm:text-sm md:text-base' 
                                    : 'text-sm sm:text-base'
                                }`}
                                title={checkResult.submission.employeeName}
                              >
                                {checkResult.submission.employeeName}
                              </h4>
                              <p className="text-[11px] text-slate-500 mt-0.5">
                                NIP: <span className="font-mono text-slate-800 font-bold">{checkResult.submission.nip}</span>
                              </p>
                            </div>
                          </div>

                          {/* 2-Column Info Grid: Tanggal/Sesi & Lokasi/Lahan */}
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div className="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5">
                              <div className="flex items-center gap-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                <Calendar className="w-3 h-3 text-emerald-700" />
                                <span>Tanggal WFA</span>
                              </div>
                              <p className="font-bold text-slate-900 text-xs">
                                {checkResult.submission.tanggalWfa}
                              </p>
                              <span className="inline-block px-1.5 py-0.5 rounded bg-amber-100 border border-amber-300 text-amber-800 font-bold text-[10px]">
                                {checkResult.submission.statusWfa}
                              </span>
                            </div>

                            <div className="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5">
                              <div className="flex items-center gap-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                <MapPin className="w-3 h-3 text-emerald-700" />
                                <span>Lokasi Bimbingan</span>
                              </div>
                              <p className="font-bold text-emerald-900 text-xs truncate" title={checkResult.submission.lokasiKegiatan}>
                                {checkResult.submission.lokasiKegiatan}
                              </p>
                              <p className="text-[11px] text-slate-700 truncate" title={checkResult.submission.lokasiLahanBimbingan || '-'}>
                                {checkResult.submission.lokasiLahanBimbingan || '-'}
                              </p>
                            </div>
                          </div>

                          {/* Surat Tugas Button */}
                          {checkResult.submission.linkSuratTugas && (
                            <a
                              href={checkResult.submission.linkSuratTugas}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="w-full py-2 px-3 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 hover:text-emerald-900 font-semibold text-xs flex items-center justify-center gap-1.5 transition-all shadow-xs active:scale-[0.99]"
                            >
                              <FileText className="w-3.5 h-3.5 text-emerald-700" />
                              <span>Lihat Surat Tugas Terlampir</span>
                              <ExternalLink className="w-3 h-3 opacity-70" />
                            </a>
                          )}

                          {/* Waktu Validasi (Langsung Tampil) */}
                          {checkResult.submission.validatedAt && (
                            <div className="flex items-center justify-between text-[11px] px-2.5 py-1.5 bg-emerald-50/50 rounded-lg border border-emerald-100">
                              <span className="flex items-center gap-1 text-slate-600">
                                <Clock className="w-3 h-3 text-emerald-700" />
                                <span>Waktu Validasi:</span>
                              </span>
                              <span className="font-mono text-emerald-800 font-bold">
                                {new Date(checkResult.submission.validatedAt).toLocaleString('id-ID')}
                              </span>
                            </div>
                          )}

                          {/* Toggle Rincian Tambahan */}
                          <div className="pt-0.5 border-t border-slate-100">
                            <button
                              type="button"
                              onClick={() => setShowCheckResultDetail((prev) => !prev)}
                              className="w-full py-1 px-2 text-slate-500 hover:text-slate-800 text-xs font-semibold flex items-center justify-center gap-1 transition-colors cursor-pointer"
                            >
                              <span>{showCheckResultDetail ? 'Sembunyikan Rincian' : 'Lihat Rincian Tambahan'}</span>
                              {showCheckResultDetail ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                            </button>

                            {showCheckResultDetail && (
                              <div className="mt-1.5 pt-1.5 border-t border-slate-100 space-y-1.5 text-xs text-slate-700">
                                {checkResult.submission.nomorWa && (
                                  <div className="flex justify-between items-center py-0.5">
                                    <span className="text-slate-500">WhatsApp Pegawai:</span>
                                    <span className="font-mono text-emerald-700 font-bold">{checkResult.submission.nomorWa}</span>
                                  </div>
                                )}
                                <div className="py-0.5">
                                  <span className="text-slate-500 block mb-0.5">Uraian Kegiatan:</span>
                                  <p className="bg-slate-50 p-2 rounded-lg border border-slate-200 text-slate-800 leading-relaxed text-xs">
                                    {checkResult.submission.namaKegiatan}
                                  </p>
                                </div>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    )}

                    {/* CONDITION 2: STATUS BELUM VALID (MASIH DALAM PROSES) */}
                    {checkResult.isPendingState && checkResult.submission && (
                      <div className="p-4 sm:p-5 rounded-2xl bg-gradient-to-b from-amber-50 to-white border border-amber-300 shadow-lg text-center space-y-3">
                        <div className="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center mx-auto shadow-sm">
                          <Clock className="w-5 h-5 animate-pulse text-white" />
                        </div>

                        <div>
                          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-bold text-[10px] uppercase tracking-wider mb-1.5 border border-amber-300">
                            ⏳ STATUS: MENUNGGU VALIDASI
                          </span>

                          {/* Required exact phrasing */}
                          <h3 className="text-xs sm:text-sm font-bold text-slate-900 leading-relaxed max-w-md mx-auto">
                            pengajuan anda masih dalam proses verifikasi
                          </h3>
                        </div>

                        {/* Submission details compact pass card */}
                        <div className="p-3.5 sm:p-4 rounded-xl bg-white border border-amber-200 text-left space-y-2.5 max-w-xl sm:max-w-2xl mx-auto shadow-xs">
                          {/* Employee Header */}
                          <div className="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                            <div className="w-8 h-8 rounded-lg bg-amber-100 border border-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                              <User className="w-3.5 h-3.5 text-amber-700" />
                            </div>
                            <div className="min-w-0 flex-1 overflow-x-auto no-scrollbar">
                              <h4 
                                className={`font-bold text-slate-900 tracking-tight whitespace-nowrap leading-snug ${
                                  checkResult.submission.employeeName.length > 35 
                                    ? 'text-xs sm:text-sm md:text-base' 
                                    : 'text-sm sm:text-base'
                                }`}
                                title={checkResult.submission.employeeName}
                              >
                                {checkResult.submission.employeeName}
                              </h4>
                              <p className="text-[11px] text-slate-500 mt-0.5">
                                NIP: <span className="font-mono text-slate-800 font-bold">{checkResult.submission.nip}</span>
                              </p>
                            </div>
                          </div>

                          {/* 2-Column Info Grid: Tanggal & Lokasi/Lahan */}
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div className="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5">
                              <div className="flex items-center gap-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                <Calendar className="w-3 h-3 text-amber-700" />
                                <span>Tanggal WFA</span>
                              </div>
                              <p className="font-bold text-slate-900 text-xs">
                                {checkResult.submission.tanggalWfa}
                              </p>
                              <span className="inline-block px-1.5 py-0.5 rounded bg-amber-100 border border-amber-300 text-amber-900 font-bold text-[10px]">
                                Dalam Antrean OSDM
                              </span>
                            </div>

                            <div className="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5">
                              <div className="flex items-center gap-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                <MapPin className="w-3 h-3 text-amber-700" />
                                <span>Lokasi Bimbingan</span>
                              </div>
                              <p className="font-bold text-amber-900 text-xs truncate" title={checkResult.submission.lokasiKegiatan}>
                                {checkResult.submission.lokasiKegiatan}
                              </p>
                              <p className="text-[11px] text-slate-700 truncate" title={checkResult.submission.lokasiLahanBimbingan || '-'}>
                                {checkResult.submission.lokasiLahanBimbingan || '-'}
                              </p>
                            </div>
                          </div>

                          {/* WhatsApp Contact Button */}
                          <a
                            href={`https://wa.me/${waLinkNumber}?text=${encodeURIComponent(
                              `Halo Tim Kerja OSDM Poltekkes Bandung, saya ${checkResult.submission.employeeName} (NIP: ${checkResult.submission.nip}) ingin konfirmasi status pengajuan WFA Bimbingan saya untuk tanggal ${checkResult.submission.tanggalWfa}. Mohon bantuannya. Terima kasih.`
                            )}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="w-full py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-all shadow-xs active:scale-[0.99]"
                          >
                            <MessageCircle className="w-3.5 h-3.5" />
                            <span>Hubungi Tim Kerja OSDM (via WhatsApp)</span>
                          </a>

                          {/* Toggle Rincian Tambahan */}
                          <div className="pt-0.5 border-t border-slate-100">
                            <button
                              type="button"
                              onClick={() => setShowCheckResultDetail((prev) => !prev)}
                              className="w-full py-1 px-2 text-slate-500 hover:text-slate-800 text-xs font-semibold flex items-center justify-center gap-1 transition-colors cursor-pointer"
                            >
                              <span>{showCheckResultDetail ? 'Sembunyikan Rincian' : 'Lihat Rincian Tambahan'}</span>
                              {showCheckResultDetail ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                            </button>

                            {showCheckResultDetail && (
                              <div className="mt-1.5 pt-1.5 border-t border-slate-100 space-y-1.5 text-xs text-slate-700">
                                {checkResult.submission.nomorWa && (
                                  <div className="flex justify-between items-center py-0.5">
                                    <span className="text-slate-500">WhatsApp Pegawai:</span>
                                    <span className="font-mono text-emerald-700 font-bold">{checkResult.submission.nomorWa}</span>
                                  </div>
                                )}
                                <div className="py-0.5">
                                  <span className="text-slate-500 block mb-0.5">Uraian Kegiatan:</span>
                                  <p className="bg-slate-50 p-2 rounded-lg border border-slate-200 text-slate-800 leading-relaxed text-xs">
                                    {checkResult.submission.namaKegiatan}
                                  </p>
                                </div>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    )}

                    {/* CONDITION 3: STATUS DITOLAK / TIDAK DISETUJUI */}
                    {checkResult.isRejectedState && checkResult.submission && (
                      <div className="p-4 sm:p-5 rounded-2xl bg-gradient-to-b from-rose-50 to-white border border-rose-300 shadow-lg text-center space-y-3">
                        <div className="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center mx-auto shadow-sm">
                          <XCircle className="w-5 h-5 text-white" />
                        </div>

                        <div>
                          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-600 text-white font-bold text-[10px] uppercase tracking-wider mb-1.5 shadow-xs">
                            ❌ STATUS: PENGAJUAN DITOLAK
                          </span>

                          <h3 className="text-xs sm:text-sm font-bold text-slate-900 leading-relaxed max-w-md mx-auto">
                            Pengajuan WFA Anda pada tanggal tersebut DITOLAK
                          </h3>
                        </div>

                        {/* Submission details compact pass card */}
                        <div className="p-3.5 sm:p-4 rounded-xl bg-white border border-rose-200 text-left space-y-2.5 max-w-xl sm:max-w-2xl mx-auto shadow-xs">
                          {/* Reason / Catatan Pengelola Box */}
                          <div className="p-2.5 rounded-lg bg-rose-50 border border-rose-200 space-y-1">
                            <div className="flex items-center gap-1.5 text-rose-800 font-bold text-[10px] uppercase tracking-wider">
                              <AlertTriangle className="w-3 h-3 shrink-0 text-rose-600" />
                              <span>Alasan Penolakan dari Admin OSDM:</span>
                            </div>
                            <p className="text-xs text-rose-950 font-medium leading-relaxed">
                              &quot;{checkResult.submission.catatanPengelola || 'Pengajuan berkas belum memenuhi persyaratan atau tidak sesuai ketentuan WFA Bimbingan. Silakan hubungi tim kerja OSDM untuk informasi lebih lanjut.'}&quot;
                            </p>
                          </div>

                          {/* Employee Header */}
                          <div className="flex items-center gap-2.5 py-2 border-y border-slate-100">
                            <div className="w-8 h-8 rounded-lg bg-rose-100 border border-rose-200 text-rose-700 flex items-center justify-center shrink-0">
                              <User className="w-3.5 h-3.5 text-rose-600" />
                            </div>
                            <div className="min-w-0 flex-1 overflow-x-auto no-scrollbar">
                              <h4 
                                className={`font-bold text-slate-900 tracking-tight whitespace-nowrap leading-snug ${
                                  checkResult.submission.employeeName.length > 35 
                                    ? 'text-xs sm:text-sm md:text-base' 
                                    : 'text-sm sm:text-base'
                                }`}
                                title={checkResult.submission.employeeName}
                              >
                                {checkResult.submission.employeeName}
                              </h4>
                              <p className="text-[11px] text-slate-500 mt-0.5">
                                NIP: <span className="font-mono text-slate-800 font-bold">{checkResult.submission.nip}</span> • Tanggal: <span className="text-rose-700 font-bold">{checkResult.submission.tanggalWfa}</span>
                              </p>
                            </div>
                          </div>

                          {/* Action Buttons: Ajukan Ulang / Hubungi WA */}
                          <div className="pt-0.5 flex flex-col sm:flex-row items-center justify-center gap-2">
                            <button
                              type="button"
                              onClick={() => {
                                if (checkResult.submission) {
                                  setNip(checkResult.submission.nip);
                                  setEmployeeName(checkResult.submission.employeeName);
                                  setUnitKerja(checkResult.submission.unitKerja || '');
                                  setJabatan(checkResult.submission.jabatan || '');
                                  setTanggalWfa(checkResult.submission.tanggalWfa);
                                  setNamaKegiatan(checkResult.submission.namaKegiatan);
                                  setLokasiKegiatan(checkResult.submission.lokasiKegiatan);
                                  setLokasiLahanBimbingan(checkResult.submission.lokasiLahanBimbingan);
                                  setLinkSuratTugas(checkResult.submission.linkSuratTugas);
                                  setIsEmployeeFound(true);
                                  setActiveTab('form');
                                  setErrorMessage(null);
                                }
                              }}
                              className="w-full sm:w-1/2 py-2 px-3 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-all shadow-xs flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer"
                            >
                              <RotateCcw className="w-3.5 h-3.5" />
                              <span>Ajukan Ulang Berkas</span>
                            </button>

                            <a
                              href={`https://wa.me/${waLinkNumber}?text=${encodeURIComponent(
                                `Halo Tim Kerja OSDM Poltekkes Bandung, saya ${checkResult.submission.employeeName} (NIP: ${checkResult.submission.nip}) ingin konfirmasi dan klarifikasi mengenai pengajuan WFA Bimbingan saya pada tanggal ${checkResult.submission.tanggalWfa} yang berstatus DITOLAK (Catatan: "${checkResult.submission.catatanPengelola || '-'}"). Mohon petunjuk perbaikannya. Terima kasih.`
                              )}`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="w-full sm:w-1/2 py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-all shadow-xs flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer"
                            >
                              <MessageCircle className="w-3.5 h-3.5" />
                              <span>Hubungi via WA</span>
                            </a>
                          </div>

                          {/* Waktu Penolakan (Langsung Tampil) */}
                          {checkResult.submission.validatedAt && (
                            <div className="flex items-center justify-between text-[11px] px-2.5 py-1.5 bg-rose-50/50 rounded-lg border border-rose-100">
                              <span className="flex items-center gap-1 text-slate-600">
                                <Clock className="w-3 h-3 text-rose-600" />
                                <span>Waktu Penolakan:</span>
                              </span>
                              <span className="font-mono text-rose-700 font-bold">
                                {new Date(checkResult.submission.validatedAt).toLocaleString('id-ID')}
                              </span>
                            </div>
                          )}

                          {/* Toggle Rincian Tambahan */}
                          <div className="pt-0.5 border-t border-slate-100">
                            <button
                              type="button"
                              onClick={() => setShowCheckResultDetail((prev) => !prev)}
                              className="w-full py-1 px-2 text-slate-500 hover:text-slate-800 text-xs font-semibold flex items-center justify-center gap-1 transition-colors cursor-pointer"
                            >
                              <span>{showCheckResultDetail ? 'Sembunyikan Rincian' : 'Lihat Rincian Tambahan'}</span>
                              {showCheckResultDetail ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                            </button>

                            {showCheckResultDetail && (
                              <div className="mt-1.5 pt-1.5 border-t border-slate-100 space-y-1.5 text-xs text-slate-700">
                                {checkResult.submission.nomorWa && (
                                  <div className="flex justify-between items-center py-0.5">
                                    <span className="text-slate-500">WhatsApp Pegawai:</span>
                                    <span className="font-mono text-emerald-700 font-bold">{checkResult.submission.nomorWa}</span>
                                  </div>
                                )}
                                <div className="flex justify-between items-center py-0.5">
                                  <span className="text-slate-500">Lokasi &amp; Lahan:</span>
                                  <span className="text-slate-800 font-semibold">{checkResult.submission.lokasiKegiatan} ({checkResult.submission.lokasiLahanBimbingan || '-'})</span>
                                </div>
                                <div className="py-0.5">
                                  <span className="text-slate-500 block mb-0.5">Uraian Kegiatan:</span>
                                  <p className="bg-slate-50 p-2 rounded-lg border border-slate-200 text-slate-800 leading-relaxed text-xs">
                                    {checkResult.submission.namaKegiatan}
                                  </p>
                                </div>
                                {checkResult.submission.validatedBy && (
                                  <div className="flex justify-between items-center py-0.5 text-[11px] text-slate-500 border-t border-slate-100 pt-1">
                                    <span>Ditolak oleh:</span>
                                    <span className="text-rose-700 font-mono font-bold">
                                      {checkResult.submission.validatedBy}
                                    </span>
                                  </div>
                                )}
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    )}

                    {/* CONDITION 4: NOT FOUND / TELAH DIHAPUS */}
                    {!checkResult.found && (
                      <div className="p-5 sm:p-6 rounded-2xl bg-white border border-slate-200 text-center space-y-2.5 shadow-xs">
                        <div className="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto text-slate-500">
                          <AlertCircle className="w-5 h-5 text-slate-500" />
                        </div>
                        <h4 className="text-xs sm:text-sm font-bold text-slate-900">
                          Data Pengajuan Tidak Ditemukan
                        </h4>
                        <p className="text-xs text-slate-600 max-w-md mx-auto leading-relaxed">
                          Tidak ditemukan berkas pengajuan WFA untuk NIP <span className="font-mono font-bold text-slate-900">{checkNip}</span> pada tanggal <span className="font-bold text-slate-900">{checkTanggal}</span>. Berkas pengajuan mungkin telah <span className="text-amber-800 font-bold">dihapus / dibatalkan oleh pengelola kepegawaian (OSDM)</span> atau belum diajukan.
                        </p>
                        <div className="pt-1.5 flex flex-col sm:flex-row items-center justify-center gap-2">
                          <button
                            type="button"
                            onClick={() => {
                              setNip(checkNip);
                              setTanggalWfa(checkTanggal);
                              setActiveTab('form');
                            }}
                            className="w-full sm:w-auto px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-all shadow-xs flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer"
                          >
                            <span>+ Ajukan Jadwal WFA Baru</span>
                          </button>
                          <button
                            type="button"
                            onClick={handleCheckStatus}
                            className="w-full sm:w-auto px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all border border-slate-200 flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer"
                          >
                            <RotateCcw className="w-3.5 h-3.5 text-slate-500" />
                            <span>Cek Ulang Server</span>
                          </button>
                        </div>
                      </div>
                    )}
                  </motion.div>
                )}
              </div>
            )}
          </div>
        </motion.div>

        {/* POP-UP PERINGATAN PENGAJUAN MENDADAK DI LUAR JAM KERJA (19.00-23.59 WIB esok hari, 00.00-07.30 WIB hari H, 16.01-18.59 WIB hari H, ATAU 15.00-16.30 WIB WFA Pulang hari H) */}
        {urgentWarningModal.isOpen && (
          <div className="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <motion.div
              initial={{ opacity: 0, scale: 0.92, y: 16 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.92, y: 16 }}
              transition={{ duration: 0.2, ease: 'easeOut' }}
              className="relative w-full max-w-md bg-white border border-amber-200 rounded-2xl shadow-2xl overflow-hidden p-5 sm:p-6 text-slate-800"
            >
              {/* Close Button Top Right */}
              <button
                type="button"
                onClick={() => setUrgentWarningModal((prev) => ({ ...prev, isOpen: false }))}
                className="absolute top-3.5 right-3.5 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                aria-label="Tutup Peringatan"
              >
                <X className="w-4 h-4" />
              </button>

              <div className="flex flex-col items-center text-center space-y-4">
                {/* Visual Icon Badge */}
                <div className="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shadow-xs">
                  <Clock className="w-7 h-7 text-amber-600 animate-pulse" />
                </div>

                <div className="space-y-1">
                  <span className="inline-block px-2.5 py-0.5 rounded-full bg-amber-100 border border-amber-200 text-amber-900 text-[11px] font-bold tracking-wide uppercase">
                    {urgentWarningModal.badge}
                  </span>
                  <h3 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                    {urgentWarningModal.title}
                  </h3>
                </div>

                {/* Main Warning Text as requested */}
                <div className="p-4 rounded-xl bg-amber-50/90 border border-amber-200 text-slate-800 text-xs sm:text-sm leading-relaxed text-left space-y-2">
                  <div className="flex items-start gap-2.5">
                    <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                    <p className="font-medium text-slate-800 leading-normal">
                      {urgentWarningModal.message}
                    </p>
                  </div>
                </div>

                {/* Action button */}
                <div className="w-full pt-1">
                  <button
                    type="button"
                    onClick={() => setUrgentWarningModal((prev) => ({ ...prev, isOpen: false }))}
                    className="w-full py-2.5 sm:py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2"
                  >
                    <span>lanjutkan ke formulir</span>
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}

        {/* POP-UP MODAL PERINGATAN / PENOLAKAN PENGAJUAN */}
        {isErrorPopupOpen && errorMessage && (
          <div
            className="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm animate-in fade-in duration-200"
            onClick={(e) => {
              if (e.target === e.currentTarget) {
                closeErrorPopup();
              }
            }}
          >
            <motion.div
              initial={{ opacity: 0, scale: 0.9, y: 16 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: 16 }}
              transition={{ duration: 0.22, ease: 'easeOut' }}
              className={`relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden border p-5 sm:p-6 text-slate-800 ${
                isRejection
                  ? 'border-rose-300 ring-4 ring-rose-500/10'
                  : 'border-amber-300 ring-4 ring-amber-500/10'
              }`}
            >
              {/* Close Button Top Right */}
              <button
                type="button"
                onClick={() => closeErrorPopup()}
                className="absolute top-3.5 right-3.5 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer z-10"
                aria-label="Tutup Peringatan"
              >
                <X className="w-4 h-4" />
              </button>

              <div className="flex flex-col items-center text-center space-y-4">
                {/* Visual Icon Badge */}
                <div
                  className={`w-14 h-14 rounded-2xl flex items-center justify-center shadow-xs border ${
                    isRejection
                      ? 'bg-rose-50 border-rose-200 text-rose-600'
                      : 'bg-amber-50 border-amber-200 text-amber-600'
                  }`}
                >
                  {isRejection ? (
                    <XCircle className="w-8 h-8 text-rose-600 animate-pulse" />
                  ) : (
                    <AlertTriangle className="w-8 h-8 text-amber-600 animate-pulse" />
                  )}
                </div>

                <div className="space-y-1">
                  <span
                    className={`inline-block px-3 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase border ${
                      isRejection
                        ? 'bg-rose-100 border-rose-200 text-rose-900'
                        : 'bg-amber-100 border-amber-200 text-amber-900'
                    }`}
                  >
                    {isRejection
                      ? 'Pengajuan Ditolak Sistem'
                      : isIncomplete
                      ? 'Formulir Belum Lengkap'
                      : 'Perhatian / Peringatan'}
                  </span>
                  <h3 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                    {isRejection
                      ? 'Pengajuan WFA Tidak Dapat Diproses'
                      : isIncomplete
                      ? 'Mohon Lengkapi Data Pengajuan'
                      : 'Peringatan Validasi Formulir'}
                  </h3>
                  <p className="text-xs text-slate-500">
                    {isRejection
                      ? 'Data pengajuan belum memenuhi syarat dan ketentuan yang berlaku.'
                      : 'Silakan periksa kembali kolom isian formulir di bawah ini.'}
                  </p>
                </div>

                {/* Message Container */}
                <div
                  className={`w-full p-4 rounded-xl border text-xs sm:text-sm leading-relaxed text-left space-y-2.5 ${
                    isRejection
                      ? 'bg-rose-50/90 border-rose-200 text-rose-950'
                      : 'bg-amber-50/90 border-amber-200 text-amber-950'
                  }`}
                >
                  {missingList.length > 0 ? (
                    <div className="space-y-2.5">
                      <div className="flex items-start gap-2.5">
                        <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                          <p className="font-bold text-amber-950 text-xs sm:text-sm">
                            Data pengajuan tidak boleh kosong!
                          </p>
                          <p className="text-amber-900 text-xs mt-0.5">
                            Mohon lengkapi <span className="font-bold text-rose-600">{missingList.length}</span> kolom wajib berikut:
                          </p>
                        </div>
                      </div>

                      <div className="flex flex-wrap gap-1.5 pt-1">
                        {missingList.map((field, idx) => (
                          <span
                            key={idx}
                            className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-amber-300 text-amber-950 font-bold text-xs shadow-2xs"
                          >
                            <span className="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0" />
                            {field}
                          </span>
                        ))}
                      </div>
                    </div>
                  ) : (
                    <div className="flex items-start gap-2.5">
                      {isRejection ? (
                        <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                      ) : (
                        <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                      )}
                      <div className="flex-1 font-medium leading-relaxed">
                        {errorMessage}
                      </div>
                    </div>
                  )}

                  {/* Contextual hint for NIP not found */}
                  {errorMessage.toLowerCase().includes('tidak terdaftar') && (
                    <div className="pt-2 border-t border-rose-200 flex items-start gap-2 text-[11px] text-rose-900">
                      <Info className="w-3.5 h-3.5 text-rose-600 shrink-0 mt-0.5" />
                      <p>
                        <strong>Tips:</strong> Gunakan tombol <strong>"Pilih dari Pangkalan Data"</strong> di samping kolom NIP untuk memilih pegawai secara otomatis tanpa salah ketik.
                      </p>
                    </div>
                  )}
                </div>

                {/* Action button */}
                <div className="w-full pt-1">
                  <button
                    type="button"
                    onClick={() => closeErrorPopup()}
                    className={`w-full py-3 px-4 rounded-xl font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2 text-white ${
                      isRejection
                        ? 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800'
                        : 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800'
                    }`}
                  >
                    <span>
                      {isRejection
                        ? 'Tutup & Periksa Data'
                        : 'Lengkapi Formulir Sekarang'}
                    </span>
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </div>
    </AnimatePresence>
  );
};

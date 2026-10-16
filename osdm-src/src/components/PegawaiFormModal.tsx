import React, { useState, useEffect, useId, useMemo, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  User,
  Search,
  CheckCircle2,
  AlertCircle,
  Building2,
  Phone,
  Send,
  RotateCcw,
  Sparkles,
  ShieldCheck,
  Check,
  Upload,
  FileText,
  FileCheck,
  HelpCircle,
  Clock,
  ArrowRight,
  ArrowLeft,
  Eye,
  Trash2,
  Info,
  Link2,
  ExternalLink,
  ChevronDown,
  Lock
} from 'lucide-react';
import { PegawaiForm, PegawaiFormResponse, EmployeeRecord, FormSection, FormQuestion } from '../types';
import { findEmployeeByNip, searchEmployees } from '../data/employeeDatabase';
import bannerMoocAsn from '../assets/banner-mooc-asn.png';

interface PegawaiFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  form: PegawaiForm;
  onSubmit: (response: PegawaiFormResponse) => Promise<{ success: boolean; error?: string }>;
  existingResponses?: PegawaiFormResponse[];
  logoUrl?: string;
  bannerUrl?: string;
}

export const PegawaiFormModal: React.FC<PegawaiFormModalProps> = ({
  isOpen,
  onClose,
  form,
  onSubmit,
  existingResponses,
  logoUrl = 'https://poltekkesbandung.ac.id/wp-content/uploads/2026/05/cropped-logo-transparan-2.png',
  bannerUrl,
}) => {
  const formHtmlId = useId();
  const effectiveBannerUrl = bannerUrl || form.bannerUrl || bannerMoocAsn;

  // Employee Identity States
  const [nipInput, setNipInput] = useState('');
  const [selectedEmployee, setSelectedEmployee] = useState<EmployeeRecord | null>(null);
  const [suggestions, setSuggestions] = useState<EmployeeRecord[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [nomorWa, setNomorWa] = useState('');

  // Form Answers State: key -> value
  const [answers, setAnswers] = useState<Record<string, any>>({});
  const [uploadedFile, setUploadedFile] = useState<{
    fileName: string;
    fileSize: string;
    fileType: string;
    dataUrl: string;
  } | null>(null);

  // Submission & UI States
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successSubmitted, setSuccessSubmitted] = useState<PegawaiFormResponse | null>(null);

  // Multi-step Wizard Navigation (Langkah 1: Identitas & Status, Langkah 2: Bukti / Alasan)
  const [currentStep, setCurrentStep] = useState<1 | 2>(1);
  const [stepDirection, setStepDirection] = useState<1 | -1>(1);
  const [canSubmitStep2, setCanSubmitStep2] = useState(true);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const lastLoadedEmployeeNipRef = useRef<string | null>(null);

  // Reset form when modal opens or form changes
  useEffect(() => {
    if (isOpen) {
      setNipInput('');
      setSelectedEmployee(null);
      lastLoadedEmployeeNipRef.current = null;
      setSuggestions([]);
      setNomorWa('');
      setAnswers({});
      setUploadedFile(null);
      setErrorMessage(null);
      setSuccessSubmitted(null);
      setIsSubmitting(false);
      setCurrentStep(1);
      setStepDirection(1);
      setCanSubmitStep2(true);
    }
  }, [isOpen, form.id]);

  // Employee Search Autocomplete
  const handleNipChange = (val: string) => {
    setNipInput(val);
    if (!val.trim()) {
      setSuggestions([]);
      setSelectedEmployee(null);
      return;
    }

    const trimmed = val.trim();
    // Direct exact NIP lookup
    const directMatch = findEmployeeByNip(trimmed);
    if (directMatch) {
      setSelectedEmployee(directMatch);
      setNomorWa(directMatch.nomorWa || '');
      setSuggestions([]);
      return;
    }

    // Name or partial NIP search
    if (trimmed.length >= 2) {
      setIsSearching(true);
      const results = searchEmployees(trimmed).slice(0, 6);
      setSuggestions(results);
      setIsSearching(false);
    } else {
      setSuggestions([]);
    }
  };

  const handleSelectSuggestion = (emp: EmployeeRecord) => {
    setSelectedEmployee(emp);
    setNipInput(emp.nip);
    setNomorWa(emp.nomorWa || '');
    setSuggestions([]);
  };

  // Helper to determine if a status is considered "Completed" (Sudah menyelesaikan/lulus/selesai)
  const isStatusCompleted = (statusStr?: string): boolean => {
    if (!statusStr) return false;
    const s = String(statusStr).toLowerCase();
    return (
      s.includes('selesai') ||
      s.includes('menyelesaikan') ||
      s.includes('lulus') ||
      s.includes('completed')
    );
  };

  // Live validation for WhatsApp Number
  const waValidation = useMemo(() => {
    const raw = (nomorWa || '').trim();
    if (!raw) {
      return { state: 'empty', message: 'Wajib diisi untuk konfirmasi (contoh: 081234567890)' };
    }
    const clean = raw.replace(/[\s\-_().]/g, '');
    const digits = clean.replace(/\D/g, '');

    if (!/^\+?[0-9]+$/.test(clean)) {
      return { state: 'invalid', message: 'Hanya boleh berisi karakter angka (contoh: 081234567890)' };
    }

    if (clean.startsWith('+62')) {
      if (!clean.startsWith('+628')) {
        return { state: 'invalid', message: 'Harus nomor ponsel aktif (+628...), bukan telepon kantor' };
      }
      if (digits.length < 11) {
        return { state: 'incomplete', message: `Nomor belum lengkap (${digits.length}/11-14 digit)` };
      }
      if (digits.length > 15) {
        return { state: 'invalid', message: 'Nomor terlalu panjang (maksimal 15 digit)' };
      }
      return { state: 'valid', message: `Format nomor WhatsApp valid (${digits.length} digit)` };
    }

    if (clean.startsWith('62')) {
      if (!clean.startsWith('628')) {
        return { state: 'invalid', message: 'Harus nomor ponsel aktif (628...), bukan telepon kantor' };
      }
      if (digits.length < 11) {
        return { state: 'incomplete', message: `Nomor belum lengkap (${digits.length}/11-14 digit)` };
      }
      if (digits.length > 15) {
        return { state: 'invalid', message: 'Nomor terlalu panjang (maksimal 15 digit)' };
      }
      return { state: 'valid', message: `Format nomor WhatsApp valid (${digits.length} digit)` };
    }

    if (clean.startsWith('08')) {
      if (digits.length < 10) {
        return { state: 'incomplete', message: `Nomor belum lengkap (${digits.length}/10-13 digit)` };
      }
      if (digits.length > 14) {
        return { state: 'invalid', message: 'Nomor terlalu panjang (maksimal 14 digit)' };
      }
      return { state: 'valid', message: `Format nomor WhatsApp valid (${digits.length} digit)` };
    }

    if (clean.startsWith('8')) {
      if (digits.length < 9) {
        return { state: 'incomplete', message: `Nomor belum lengkap (${digits.length}/10-13 digit, awali 08)` };
      }
      if (digits.length > 13) {
        return { state: 'invalid', message: 'Nomor terlalu panjang' };
      }
      return { state: 'valid', message: `Format nomor WhatsApp valid (${digits.length} digit)` };
    }

    return { state: 'invalid', message: 'Nomor WhatsApp harus diawali dengan 08 atau 628' };
  }, [nomorWa]);

  // Live responses from props or localStorage
  const effectiveResponses = useMemo(() => {
    if (existingResponses && existingResponses.length > 0) return existingResponses;
    try {
      const raw = localStorage.getItem('osdm_pegawai_form_responses');
      if (raw) return JSON.parse(raw) as PegawaiFormResponse[];
    } catch {}
    return [];
  }, [existingResponses, isOpen]);

  // Find if selected employee already submitted this form previously
  const previousResponse = useMemo(() => {
    if (!selectedEmployee || !form) return null;
    const cleanNip = selectedEmployee.nip.replace(/[\s.-]/g, '').toLowerCase();
    return (
      effectiveResponses.find(
        (r) =>
          r.formId === form.id &&
          r.nip.replace(/[\s.-]/g, '').toLowerCase() === cleanNip
      ) || null
    );
  }, [selectedEmployee, form, effectiveResponses]);

  const previousStatus = useMemo(() => {
    if (!previousResponse) return null;
    return (
      previousResponse.answers?.status_pelatihan ||
      previousResponse.answers?.['status_pelatihan'] ||
      Object.values(previousResponse.answers || {}).find(
        (v) =>
          typeof v === 'string' &&
          (v.toLowerCase().includes('selesai') ||
            v.toLowerCase().includes('mulai') ||
            v.toLowerCase().includes('belum'))
      ) ||
      'Sudah Mengisi'
    );
  }, [previousResponse]);

  // Flag if the employee has already completed the form (LOCKED: Cannot resubmit)
  const isAlreadyCompleted = useMemo(() => {
    if (!previousResponse) return false;
    return isStatusCompleted(String(previousStatus));
  }, [previousResponse, previousStatus]);

  // Flag if employee previously chose "Sudah mulai" or "Belum mulai" (CAN UPDATE)
  const isUpdatingPrevious = useMemo(() => {
    return !!previousResponse && !isAlreadyCompleted;
  }, [previousResponse, isAlreadyCompleted]);

  // Target Kepegawaian Check: ASN vs Non ASN
  const isTargetMismatch = useMemo(() => {
    if (!selectedEmployee || !form.targetKepegawaian || form.targetKepegawaian === 'ALL') {
      return false;
    }
    const empType = selectedEmployee.jenisKepegawaian || 'ASN';
    if (form.targetKepegawaian === 'ASN' && empType !== 'ASN') {
      return true;
    }
    if (form.targetKepegawaian === 'Non ASN' && empType !== 'Non ASN') {
      return true;
    }
    return false;
  }, [selectedEmployee, form.targetKepegawaian]);

  // Auto pre-fill answers when selecting employee with updatable status
  useEffect(() => {
    if (!selectedEmployee) {
      if (lastLoadedEmployeeNipRef.current !== null) {
        lastLoadedEmployeeNipRef.current = null;
        setAnswers({});
        setNomorWa('');
      }
      return;
    }

    const cleanNip = selectedEmployee.nip.replace(/[\s.-]/g, '').toLowerCase();

    // Guard: Only initialize/prefill when the selected employee actually changes!
    // Do NOT re-run and wipe out answers during background polls or component re-renders
    if (lastLoadedEmployeeNipRef.current === cleanNip) {
      return;
    }

    lastLoadedEmployeeNipRef.current = cleanNip;

    const prev = effectiveResponses.find(
      (r) =>
        r.formId === form.id &&
        r.nip.replace(/[\s.-]/g, '').toLowerCase() === cleanNip
    );

    if (prev) {
      if (prev.nomorWa) {
        setNomorWa(prev.nomorWa);
      } else if (selectedEmployee.nomorWa) {
        setNomorWa(selectedEmployee.nomorWa);
      }
      setAnswers({ ...(prev.answers || {}) });
    } else {
      if (selectedEmployee.nomorWa) {
        setNomorWa(selectedEmployee.nomorWa);
      } else {
        setNomorWa('');
      }
      setAnswers({});
    }
  }, [selectedEmployee, form.id, effectiveResponses]);

  // Determine if form has branching logic configured
  const hasBranching = useMemo(() => {
    if (!form.sections || form.sections.length <= 1) return false;
    return form.sections.some((sec) =>
      sec.questions.some((q) => q.options?.some((opt) => !!opt.nextSectionId))
    );
  }, [form.sections]);

  // First section of the form
  const firstSection = form.sections?.[0] || null;

  // Find branching trigger question in the first section
  const branchingQuestion = useMemo(() => {
    if (!firstSection) return null;
    return (
      firstSection.questions.find((q) =>
        q.options?.some((opt) => !!opt.nextSectionId)
      ) || null
    );
  }, [firstSection]);

  // Active branching target section (if any option with nextSectionId is selected)
  const activeConditionalSection = useMemo<FormSection | null>(() => {
    if (!hasBranching || !branchingQuestion) return null;
    const selectedAnswer = answers[branchingQuestion.id] || answers['status_pelatihan'] || '';
    if (!selectedAnswer) return null;

    const cleanAnswer = String(selectedAnswer).trim().toLowerCase();
    const matchedOpt = branchingQuestion.options?.find(
      (opt) =>
        opt.label.trim().toLowerCase() === cleanAnswer ||
        opt.id.toLowerCase() === cleanAnswer
    );

    if (matchedOpt && matchedOpt.nextSectionId) {
      return form.sections.find((s) => s.id === matchedOpt.nextSectionId) || null;
    }
    return null;
  }, [hasBranching, branchingQuestion, answers, form.sections]);

  // Selected Primary Status from Section 1
  const selectedPrimaryStatus = useMemo(() => {
    return (
      answers['status_pelatihan'] ||
      (branchingQuestion ? answers[branchingQuestion.id] : '') ||
      (firstSection?.questions[0] ? answers[firstSection.questions[0].id] : '') ||
      ''
    );
  }, [answers, branchingQuestion, firstSection]);

  // Total Steps: 2 if branching or multiple sections, 1 if single section or already completed
  const totalSteps = useMemo(() => {
    if (isAlreadyCompleted) return 1;
    if (hasBranching) return 2;
    if (form.sections && form.sections.length > 1) return 2;
    return 1;
  }, [isAlreadyCompleted, hasBranching, form.sections]);

  const handleNextStep = () => {
    setErrorMessage(null);
    if (!selectedEmployee) {
      setErrorMessage('Silakan cari dan pilih data pegawai Anda terlebih dahulu.');
      return;
    }

    if (isTargetMismatch) {
      setErrorMessage(
        form.targetKepegawaian === 'ASN'
          ? 'Formulir ini khusus untuk Pegawai ASN.'
          : 'Formulir ini khusus untuk Pegawai Non-ASN.'
      );
      return;
    }

    if (waValidation.state !== 'valid') {
      setErrorMessage(
        waValidation.message ||
        'Nomor WhatsApp aktif wajib diisi dan lengkap (minimal 10-13 digit angka, contoh: 081234567890).'
      );
      return;
    }

    if (!selectedPrimaryStatus || String(selectedPrimaryStatus).trim() === '') {
      setErrorMessage('Silakan pilih status keikutsertaan Anda terlebih dahulu pada formulir di atas.');
      return;
    }

    // Always clear error message before transitioning to Step 2
    setErrorMessage(null);

    // Prevent premature submit from touch bleed / fast double-tap on mobile
    setCanSubmitStep2(false);
    setTimeout(() => {
      setCanSubmitStep2(true);
    }, 450);

    setStepDirection(1);
    setCurrentStep(2);
  };

  const handlePrevStep = () => {
    setErrorMessage(null);
    setStepDirection(-1);
    setCurrentStep(1);
  };

  // Helper to update answers
  const handleAnswerChange = (questionId: string, val: any) => {
    setAnswers((prev) => {
      const next = { ...prev, [questionId]: val };
      // Backward compatibility: if branching question, also set 'status_pelatihan'
      if (branchingQuestion && branchingQuestion.id === questionId) {
        next.status_pelatihan = val;
      }
      return next;
    });
    setErrorMessage(null);
  };

  // Handle File Upload
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>, questionId: string) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      setErrorMessage('Ukuran file maksimal 5MB. Silakan kompres atau pilih file yang lebih kecil.');
      return;
    }

    setErrorMessage(null);
    const reader = new FileReader();
    reader.onload = () => {
      const dataUrl = reader.result as string;
      const sizeKb = Math.round(file.size / 1024);
      const sizeStr = sizeKb > 1024 ? `${(sizeKb / 1024).toFixed(1)} MB` : `${sizeKb} KB`;

      const fileData = {
        fileName: file.name,
        fileSize: sizeStr,
        fileType: file.type,
        dataUrl,
      };
      setUploadedFile(fileData);
      setAnswers((prev) => ({
        ...prev,
        [questionId]: fileData,
        bukti_sertifikat: fileData,
      }));
    };
    reader.readAsDataURL(file);
  };

  const handleRemoveFile = (questionId: string) => {
    setUploadedFile(null);
    setAnswers((prev) => {
      const copy = { ...prev };
      delete copy[questionId];
      delete copy.bukti_sertifikat;
      return copy;
    });
    if (fileInputRef.current) fileInputRef.current.value = '';
  };

  // Helper to determine if a question is a Link / Google Drive question
  const isLinkQuestion = (q: FormQuestion) => {
    if (q.type === 'link') return true;
    const lowerId = q.id.toLowerCase();
    const lowerTitle = q.title.toLowerCase();
    return (
      lowerId.includes('drive') ||
      lowerId.includes('link') ||
      lowerTitle.includes('drive') ||
      lowerTitle.includes('link') ||
      lowerTitle.includes('tautan')
    );
  };

  // Form Validation & Submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // Guard: In 2-step mode, never submit while on Step 1
    if (currentStep === 1 && totalSteps > 1) {
      handleNextStep();
      return;
    }

    // Guard: Prevent rapid accidental submit during step transition cooldown
    if (!canSubmitStep2 && totalSteps > 1) {
      return;
    }

    setErrorMessage(null);

    if (!selectedEmployee) {
      setErrorMessage('Silakan pilih data Pegawai terlebih dahulu melalui kolom pencarian NIP atau Nama.');
      return;
    }

    if (isTargetMismatch) {
      setErrorMessage(
        form.targetKepegawaian === 'ASN'
          ? 'Formulir ini khusus untuk Pegawai ASN.'
          : 'Formulir ini khusus untuk Pegawai Non-ASN.'
      );
      return;
    }

    // Validasi Wajib & Kelengkapan Nomor WhatsApp (Tolak jika tidak lengkap)
    const rawWa = (nomorWa || '').trim();
    const cleanWa = rawWa.replace(/[\s\-_().]/g, '');
    const digitsOnly = cleanWa.replace(/\D/g, '');

    if (!cleanWa || digitsOnly.length === 0) {
      setErrorMessage('Kolom Nomor WhatsApp Aktif wajib diisi untuk keperluan konfirmasi data.');
      return;
    }

    if (!/^\+?[0-9]+$/.test(cleanWa)) {
      setErrorMessage('Nomor WhatsApp hanya boleh berupa angka (contoh: 081234567890).');
      return;
    }

    if (cleanWa.startsWith('+62')) {
      if (!cleanWa.startsWith('+628')) {
        setErrorMessage('Nomor WhatsApp harus nomor ponsel aktif (diawali +628..., bukan nomor telepon kantor/rumah).');
        return;
      }
      if (digitsOnly.length < 11) {
        setErrorMessage(`Nomor WhatsApp tidak lengkap (saat ini ${digitsOnly.length} digit). Nomor WhatsApp dengan awalan +62 minimal 11-14 digit (contoh: +6281234567890).`);
        return;
      }
      if (digitsOnly.length > 15) {
        setErrorMessage(`Nomor WhatsApp terlalu panjang (${digitsOnly.length} digit). Maksimal 15 digit.`);
        return;
      }
    } else if (cleanWa.startsWith('62')) {
      if (!cleanWa.startsWith('628')) {
        setErrorMessage('Nomor WhatsApp harus nomor ponsel aktif (diawali 628..., bukan nomor telepon kantor/rumah).');
        return;
      }
      if (digitsOnly.length < 11) {
        setErrorMessage(`Nomor WhatsApp tidak lengkap (saat ini ${digitsOnly.length} digit). Nomor WhatsApp dengan awalan 62 minimal 11-14 digit (contoh: 6281234567890).`);
        return;
      }
      if (digitsOnly.length > 15) {
        setErrorMessage(`Nomor WhatsApp terlalu panjang (${digitsOnly.length} digit). Maksimal 15 digit.`);
        return;
      }
    } else if (cleanWa.startsWith('08')) {
      if (digitsOnly.length < 10) {
        setErrorMessage(`Nomor WhatsApp tidak lengkap (saat ini ${digitsOnly.length} digit). Nomor ponsel/WhatsApp di Indonesia minimal 10-13 digit (contoh: 081234567890).`);
        return;
      }
      if (digitsOnly.length > 14) {
        setErrorMessage(`Nomor WhatsApp terlalu panjang (${digitsOnly.length} digit). Maksimal 14 digit.`);
        return;
      }
    } else if (cleanWa.startsWith('8')) {
      if (digitsOnly.length < 9) {
        setErrorMessage(`Nomor WhatsApp tidak lengkap (saat ini ${digitsOnly.length} digit). Masukkan nomor WhatsApp lengkap minimal 10 digit (contoh: 081234567890).`);
        return;
      }
      if (digitsOnly.length > 13) {
        setErrorMessage(`Nomor WhatsApp terlalu panjang (${digitsOnly.length} digit).`);
        return;
      }
    } else {
      setErrorMessage('Nomor WhatsApp tidak valid. Masukkan nomor ponsel aktif yang diawali dengan 08... atau 628... (contoh: 081234567890).');
      return;
    }

    if (isAlreadyCompleted) {
      setErrorMessage(
        'Anda sudah tercatat telah menyelesaikan pelatihan ini. Formulir tidak dapat dikirim kembali.'
      );
      return;
    }

    // Validate Section 1 questions
    if (firstSection) {
      for (const q of firstSection.questions) {
        if (q.required) {
          const val = answers[q.id];
          if (val === undefined || val === null || String(val).trim() === '') {
            setErrorMessage(`Kolom "${q.title}" wajib diisi.`);
            return;
          }
        }
      }
    }

    // Validate Conditional Target Section (if branching)
    if (hasBranching) {
      if (activeConditionalSection) {
        for (const q of activeConditionalSection.questions) {
          if (q.required) {
            const val = answers[q.id];
            if (val === undefined || val === null || String(val).trim() === '') {
              setErrorMessage(`Kolom "${q.title}" wajib diisi.`);
              return;
            }
          }

          // If link question, validate URL format
          if (isLinkQuestion(q)) {
            const linkVal = (answers[q.id] || '').toString().trim();
            if (q.required && !linkVal) {
              setErrorMessage(`Tautan / Link pada "${q.title}" wajib diisi.`);
              return;
            }
            if (linkVal && !linkVal.startsWith('http://') && !linkVal.startsWith('https://')) {
              setErrorMessage(
                `Tautan pada "${q.title}" harus diawali dengan http:// atau https:// (contoh: https://drive.google.com/...)`
              );
              return;
            }
          }
        }
      }
    } else {
      // Validate all sequential sections (if linear)
      for (const sec of form.sections) {
        for (const q of sec.questions) {
          if (q.required) {
            const val = answers[q.id];
            if (val === undefined || val === null || String(val).trim() === '') {
              setErrorMessage(`Kolom "${q.title}" wajib diisi.`);
              return;
            }
          }
          if (isLinkQuestion(q)) {
            const linkVal = (answers[q.id] || '').toString().trim();
            if (linkVal && !linkVal.startsWith('http://') && !linkVal.startsWith('https://')) {
              setErrorMessage(
                `Tautan pada "${q.title}" harus diawali dengan http:// atau https://`
              );
              return;
            }
          }
        }
      }
    }

    setIsSubmitting(true);

    try {
      // Extract main status / choice and gdrive link for backward compatibility
      let primaryStatus =
        answers['status_pelatihan'] ||
        (branchingQuestion ? answers[branchingQuestion.id] : '') ||
        (firstSection?.questions[0] ? answers[firstSection.questions[0].id] : '');

      let primaryGdriveLink = answers['link_gdrive_sertifikat'] || '';
      if (!primaryGdriveLink) {
        // search for any answer that has drive or link in question id or value
        for (const key of Object.keys(answers)) {
          if (
            (key.includes('drive') || key.includes('link')) &&
            typeof answers[key] === 'string' &&
            answers[key].startsWith('http')
          ) {
            primaryGdriveLink = answers[key];
            break;
          }
        }
      }

      const responsePayload: PegawaiFormResponse = {
        id: previousResponse ? previousResponse.id : `resp-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`,
        formId: form.id,
        nip: selectedEmployee.nip,
        namaPegawai: selectedEmployee.name,
        unitKerja: selectedEmployee.unitKerja,
        jabatan: selectedEmployee.jabatan,
        nomorWa: cleanWa || selectedEmployee.nomorWa || '',
        answers: {
          ...answers,
          status_pelatihan: primaryStatus || answers.status_pelatihan || '-',
          link_gdrive_sertifikat: primaryGdriveLink,
        },
        submittedAt: new Date().toISOString(),
      };

      const res = await onSubmit(responsePayload);
      if (res.success) {
        setSuccessSubmitted(responsePayload);
      } else {
        setErrorMessage(res.error || 'Terjadi kesalahan saat menyimpan konfirmasi.');
      }
    } catch (err: any) {
      setErrorMessage(err?.message || 'Gagal mengirimkan formulir ke server.');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Render an Individual Question
  const renderQuestion = (q: FormQuestion, secId: string) => {
    const isLink = isLinkQuestion(q);
    const currentValue = answers[q.id] || '';
    const isDropdown = q.type === 'dropdown' || q.id === 'status_pelatihan';

    return (
      <div key={q.id} className="space-y-1.5">
        <div className="flex items-center justify-between">
          <label className="block text-xs font-bold text-slate-800">
            {q.title} {q.required && <span className="text-rose-500">*</span>}
          </label>
          {isLink && (
            <span className="text-[10px] font-bold text-teal-700 uppercase bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">
              Google Drive / Link
            </span>
          )}
        </div>

        {q.description && (
          <p className="text-[11px] text-slate-500 leading-relaxed">{q.description}</p>
        )}

        {/* 1. Dropdown Select */}
        {isDropdown && (
          <div className="space-y-2">
            <div className="relative">
              <select
                value={currentValue}
                onChange={(e) => handleAnswerChange(q.id, e.target.value)}
                className="w-full pl-3.5 pr-10 py-3 bg-white hover:bg-slate-50 border-2 border-teal-500/50 focus:border-teal-600 rounded-xl text-xs sm:text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500/20 cursor-pointer transition-all shadow-xs appearance-none"
                required={q.required}
              >
                <option value="" disabled className="text-slate-400">
                  {q.placeholder || `-- Pilih Status Pelatihan Anda --`}
                </option>
                {q.options?.map((opt) => (
                  <option key={opt.id} value={opt.label} className="py-1">
                    {opt.label}
                  </option>
                ))}
              </select>
              <div className="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-teal-700 flex items-center">
                <ChevronDown className="w-4 h-4 sm:w-5 sm:h-5" />
              </div>
            </div>
          </div>
        )}

        {/* 2. Radio Options (if not dropdown) */}
        {!isDropdown && q.type === 'radio' && (
          <div className="space-y-2 pt-1">
            {q.options?.map((opt) => {
              const isChecked = currentValue === opt.label;
              return (
                <label
                  key={opt.id}
                  onClick={() => handleAnswerChange(q.id, opt.label)}
                  className={`flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all ${
                    isChecked
                      ? 'bg-teal-50/90 border-teal-600 shadow-xs text-teal-900 font-bold'
                      : 'bg-white border-slate-200 hover:border-slate-300 text-slate-700'
                  }`}
                >
                  <input
                    type="radio"
                    name={q.id}
                    checked={isChecked}
                    onChange={() => handleAnswerChange(q.id, opt.label)}
                    className="w-4 h-4 text-teal-600 focus:ring-teal-500 border-slate-300"
                  />
                  <span className="text-xs leading-snug">{opt.label}</span>
                </label>
              );
            })}
          </div>
        )}

        {/* 3. Link / Google Drive Question */}
        {isLink && (
          <div className="space-y-2">
            <div className="relative">
              <input
                type="url"
                value={currentValue}
                onChange={(e) => handleAnswerChange(q.id, e.target.value)}
                placeholder={q.placeholder || 'https://drive.google.com/file/d/.../view?usp=sharing'}
                className="w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-300 focus:border-teal-600 rounded-xl font-medium text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500/20 placeholder:text-slate-400 shadow-xs"
                required={q.required}
              />
              <Link2 className="w-4 h-4 text-teal-600 absolute left-3 top-3" />
            </div>

            {/* Fitur Uji Coba Buka Link Google Drive */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-0.5">
              <button
                type="button"
                onClick={() => {
                  const raw = (currentValue || '').trim();
                  if (!raw) {
                    alert(
                      'Silakan tempelkan (paste) tautan Google Drive Anda terlebih dahulu sebelum melakukan uji coba.'
                    );
                    return;
                  }
                  let target = raw;
                  if (!target.startsWith('http://') && !target.startsWith('https://')) {
                    target = 'https://' + target;
                  }
                  window.open(target, '_blank', 'noopener,noreferrer');
                }}
                className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-teal-50 hover:bg-teal-100/90 text-teal-800 border border-teal-200/80 text-xs font-bold transition-all shadow-xs active:scale-[0.98] w-full sm:w-auto"
                title="Buka link di tab baru untuk memverifikasi apakah dapat diakses oleh publik"
              >
                <ExternalLink className="w-3.5 h-3.5 text-teal-700" />
                <span>Uji Coba Buka Link Google Drive</span>
              </button>

              {currentValue && (
                <span className="text-[11px] text-emerald-700 font-bold flex items-center justify-center gap-1 bg-emerald-50 px-2.5 py-1.5 rounded-lg border border-emerald-200 w-full sm:w-auto">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                  Tautan siap diuji coba
                </span>
              )}
            </div>

            {/* Kotak Petunjuk Akses Google Drive */}
            <div className="p-3 bg-amber-50/90 border border-amber-200/80 rounded-2xl text-[11px] text-amber-900 space-y-1 mt-2 shadow-xs">
              <div className="font-bold flex items-center gap-1.5 text-amber-800">
                <AlertCircle className="w-3.5 h-3.5 shrink-0 text-amber-600" />
                <span>Penting: Pastikan Izin Akses Link Tidak Terkunci!</span>
              </div>
              <p className="text-amber-800/90 leading-relaxed pl-5">
                Pada berkas di Google Drive: Klik tombol <strong>Bagikan (Share)</strong> ➜ Ubah Akses
                Umum menjadi <strong>&quot;Siapa saja yang memiliki link&quot; (Anyone with the link)</strong>{' '}
                dengan peran <strong>Pelihat (Viewer)</strong> agar admin OSDM dapat melihat berkas Anda.
                Silakan klik tombol <em>Uji Coba Buka Link</em> di atas untuk memastikannya.
              </p>
            </div>
          </div>
        )}

        {/* 4. Textarea */}
        {!isLink && q.type === 'textarea' && (
          <textarea
            rows={3}
            value={currentValue}
            onChange={(e) => handleAnswerChange(q.id, e.target.value)}
            placeholder={q.placeholder || 'Tuliskan jawaban atau penjelasan Anda...'}
            className="w-full px-3.5 py-2.5 bg-white border border-slate-300 focus:border-teal-600 rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 shadow-xs"
            required={q.required}
          />
        )}

        {/* 5. Regular Text Input */}
        {!isLink && q.type === 'text' && (
          <input
            type="text"
            value={currentValue}
            onChange={(e) => handleAnswerChange(q.id, e.target.value)}
            placeholder={q.placeholder || 'Tuliskan jawaban Anda...'}
            className="w-full px-3.5 py-2.5 bg-white border border-slate-300 focus:border-teal-600 rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 shadow-xs"
            required={q.required}
          />
        )}

        {/* 6. File Upload (Optional) */}
        {q.type === 'file' && (
          <div className="space-y-2">
            <input
              type="file"
              ref={fileInputRef}
              onChange={(e) => handleFileChange(e, q.id)}
              className="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
            />
          </div>
        )}
      </div>
    );
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/80 backdrop-blur-md overflow-hidden">
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 15 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 15 }}
        transition={{ duration: 0.2 }}
        className="relative w-full max-w-xl sm:max-w-2xl bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col my-auto"
        style={{ maxHeight: 'calc(100dvh - 16px)' }}
      >
        {/* Header Bersih: Banner Image menyesuaikan layar HP tanpa terpotong */}
        {effectiveBannerUrl ? (
          <div className="relative w-full overflow-hidden bg-slate-100 border-b border-slate-200 shrink-0">
            <img
              src={effectiveBannerUrl}
              alt={form.title || 'Banner Formulir Pegawai'}
              className="w-full h-auto max-h-[125px] sm:max-h-[190px] object-cover object-center block select-none"
              onError={(e) => {
                if (e.currentTarget.src !== bannerMoocAsn) {
                  e.currentTarget.src = bannerMoocAsn;
                }
              }}
            />
            {/* Tombol Tutup Floating */}
            <button
              type="button"
              onClick={onClose}
              className="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 p-1 sm:p-1.5 text-slate-700 hover:text-slate-950 bg-white/90 hover:bg-white active:bg-slate-100 backdrop-blur-md rounded-full transition-all shadow-md hover:shadow-lg z-20 cursor-pointer border border-white/80"
              aria-label="Tutup"
              title="Tutup Formulir"
            >
              <X className="w-4 h-4 sm:w-4.5 sm:h-4.5" />
            </button>
          </div>
        ) : (
          /* Fallback Header jika tidak ada gambar banner */
          <div className="bg-gradient-to-br from-teal-950 via-teal-900 to-emerald-950 text-white p-4 sm:p-6 relative overflow-hidden shadow-sm shrink-0 border-b border-teal-800/40">
            {/* Subtle Ambient Glow */}
            <div className="absolute -top-12 -right-12 w-48 h-48 bg-teal-400/15 rounded-full blur-2xl pointer-events-none" />
            <div className="absolute -bottom-10 left-1/4 w-40 h-40 bg-emerald-500/15 rounded-full blur-xl pointer-events-none" />

            {/* Baris Atas Header: Logo Polkesban (Kiri) & Tombol Tutup (Kanan) */}
            <div className="flex items-center justify-between gap-3 relative z-10 mb-2.5 sm:mb-3">
              <div className="bg-white/95 rounded-xl px-2.5 py-1 sm:px-3 sm:py-1.5 shadow-sm border border-white/40 flex items-center justify-center shrink-0">
                <img
                  src={logoUrl}
                  alt="Logo Poltekkes Bandung"
                  className="h-7 sm:h-9 w-auto max-w-[125px] sm:max-w-[160px] object-contain"
                />
              </div>

              <button
                type="button"
                onClick={onClose}
                className="p-1.5 sm:p-2 text-teal-200 hover:text-white bg-white/10 hover:bg-white/20 active:bg-white/30 rounded-full transition-all shrink-0 flex items-center justify-center cursor-pointer shadow-xs"
                aria-label="Tutup"
                title="Tutup Formulir"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Konten Judul & Kategori */}
            <div className="space-y-1 relative z-10">
              <div className="flex items-center gap-2 flex-wrap">
                <span className="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-teal-500/25 text-teal-200 border border-teal-400/30">
                  {form.category || 'Formulir Pegawai'}
                </span>
                <span className="inline-flex items-center gap-1.5 text-[11px] text-teal-200 font-semibold">
                  <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                  <ShieldCheck className="w-3.5 h-3.5 text-teal-300" />
                  Portal Resmi OSDM
                </span>
              </div>

              <h2 className="text-base sm:text-lg font-black text-white leading-snug tracking-tight">
                {form.title}
              </h2>

              {form.description && (
                <p className="text-[11px] sm:text-xs text-teal-100/90 leading-relaxed bg-teal-950/60 p-2.5 sm:p-3 rounded-xl border border-teal-500/25 mt-2">
                  {form.description}
                </p>
              )}
            </div>
          </div>
        )}

        {/* Success View */}
        {successSubmitted ? (
          <div className="p-4 sm:p-8 text-center space-y-4 sm:space-y-5 overflow-y-auto flex-1">
            <div className="w-14 h-14 sm:w-16 sm:h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
              <CheckCircle2 className="w-8 h-8 sm:w-10 sm:h-10" />
            </div>

            <div className="space-y-1">
              <h3 className="text-base sm:text-lg font-bold text-slate-800">
                {isUpdatingPrevious
                  ? 'Status Berhasil Diperbarui'
                  : 'Formulir Berhasil Terkirim'}
              </h3>
              <p className="text-xs text-slate-500 max-w-md mx-auto">
                Data Anda telah berhasil tersimpan.
              </p>
            </div>

            {/* Ringkasan Data */}
            <div className="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-left text-xs space-y-2 max-w-md mx-auto">
              <div className="flex justify-between py-1 border-b border-slate-200">
                <span className="text-slate-500">Nama Pegawai:</span>
                <span className="font-bold text-slate-800">{successSubmitted.namaPegawai}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-slate-200">
                <span className="text-slate-500">NIP:</span>
                <span className="font-mono font-semibold text-slate-800">{successSubmitted.nip}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-slate-200">
                <span className="text-slate-500">Unit Kerja:</span>
                <span className="font-semibold text-slate-800">{successSubmitted.unitKerja}</span>
              </div>
              {successSubmitted.answers.status_pelatihan && (
                <div className="flex justify-between py-1 border-b border-slate-200">
                  <span className="text-slate-500">Status / Pilihan:</span>
                  <span className="font-bold text-teal-700">{successSubmitted.answers.status_pelatihan}</span>
                </div>
              )}
              {successSubmitted.answers.link_gdrive_sertifikat && (
                <div className="flex justify-between items-center py-1 border-b border-slate-200">
                  <span className="text-slate-500">Tautan Berkas:</span>
                  <a
                    href={successSubmitted.answers.link_gdrive_sertifikat}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="font-semibold text-teal-700 hover:text-teal-800 hover:underline flex items-center gap-1 text-[11px]"
                  >
                    <span>Buka Link GDrive</span>
                    <ExternalLink className="w-3 h-3" />
                  </a>
                </div>
              )}
              <div className="flex justify-between py-1">
                <span className="text-slate-500">Waktu Submit:</span>
                <span className="text-slate-700">
                  {new Date(successSubmitted.submittedAt).toLocaleString('id-ID')}
                </span>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row gap-2 justify-center pt-2">
              <button
                type="button"
                onClick={() => {
                  setSuccessSubmitted(null);
                  setNipInput('');
                  setSelectedEmployee(null);
                  lastLoadedEmployeeNipRef.current = null;
                  setAnswers({});
                  setUploadedFile(null);
                  setCurrentStep(1);
                  setStepDirection(1);
                  setCanSubmitStep2(true);
                }}
                className="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-100 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                Isi untuk Pegawai Lain
              </button>
              <button
                type="button"
                onClick={onClose}
                className="px-6 py-2.5 rounded-xl bg-teal-700 text-white text-xs font-bold hover:bg-teal-800 shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <Check className="w-4 h-4" />
                Selesai & Tutup
              </button>
            </div>
          </div>
        ) : (
          /* Form Input View */
          <form
            onSubmit={(e) => {
              if (currentStep === 1 && totalSteps > 1) {
                e.preventDefault();
                handleNextStep();
                return;
              }
              handleSubmit(e);
            }}
            className="flex flex-col flex-1 overflow-hidden min-h-0"
          >
            {/* Stepper Header (Langkah 1 & 2) */}
            {totalSteps > 1 && !isAlreadyCompleted && (
              <div className="shrink-0 bg-gradient-to-r from-slate-50 via-teal-50/30 to-slate-50 border-b border-slate-200 px-4 py-2 sm:py-2.5 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span
                    className={`w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold transition-all ${
                      currentStep === 1
                        ? 'bg-teal-700 text-white shadow-xs ring-2 ring-teal-200'
                        : 'bg-emerald-600 text-white shadow-xs'
                    }`}
                  >
                    {currentStep > 1 ? <Check className="w-3.5 h-3.5" /> : '1'}
                  </span>
                  <div className="text-left">
                    <span
                      className={`text-xs block font-bold transition-colors ${
                        currentStep === 1 ? 'text-teal-950 font-extrabold' : 'text-slate-500'
                      }`}
                    >
                      Identitas & Status
                    </span>
                  </div>
                </div>

                <div className="flex-1 max-w-[50px] sm:max-w-[80px] mx-2 h-1 bg-slate-200 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-teal-600 transition-all duration-300 rounded-full"
                    style={{ width: currentStep === 2 ? '100%' : '20%' }}
                  />
                </div>

                <div className="flex items-center gap-2">
                  <span
                    className={`w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold transition-all ${
                      currentStep === 2
                        ? 'bg-teal-700 text-white shadow-xs ring-2 ring-teal-200'
                        : 'bg-slate-200 text-slate-500'
                    }`}
                  >
                    2
                  </span>
                  <div className="text-left">
                    <span
                      className={`text-xs block font-bold transition-colors ${
                        currentStep === 2 ? 'text-teal-950 font-extrabold' : 'text-slate-500'
                      }`}
                    >
                      Bukti / Alasan
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* Scrollable Form Body */}
            <div className="flex-1 overflow-y-auto p-3.5 sm:p-5 space-y-4 sm:space-y-5">
              {errorMessage && (
                <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-2.5 animate-shake">
                  <AlertCircle className="w-4 h-4 shrink-0 mt-0.5 text-rose-500" />
                  <span className="font-medium leading-relaxed">{errorMessage}</span>
                </div>
              )}

              <AnimatePresence mode="wait" custom={stepDirection}>
                {currentStep === 1 ? (
                  <motion.div
                    key="step-1"
                    custom={stepDirection}
                    initial={{ opacity: 0, x: stepDirection > 0 ? 30 : -30 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: stepDirection < 0 ? 30 : -30 }}
                    transition={{ duration: 0.2 }}
                    className="space-y-4 sm:space-y-5"
                  >
                    {/* 1. BAGIAN IDENTITAS PEGAWAI */}
                    <div className="bg-slate-50 border border-slate-200/80 rounded-2xl p-3.5 sm:p-5 space-y-3.5">
                      <div className="flex items-center justify-between text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <div className="flex items-center gap-2">
                          <User className="w-4 h-4 text-teal-600" />
                          <span>1. Identitas Pegawai</span>
                        </div>
                        {form.targetKepegawaian === 'ASN' ? (
                          <span className="text-[10px] font-extrabold text-blue-800 bg-blue-100/90 border border-blue-200 px-2 py-0.5 rounded-full capitalize">
                            🏛️ Khusus ASN
                          </span>
                        ) : form.targetKepegawaian === 'Non ASN' ? (
                          <span className="text-[10px] font-extrabold text-amber-800 bg-amber-100/90 border border-amber-200 px-2 py-0.5 rounded-full capitalize">
                            📋 Khusus Non-ASN
                          </span>
                        ) : null}
                      </div>

                      {/* NIP or Nama Input with autocomplete */}
                      <div className="relative">
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          Ketik NIP atau Nama Lengkap Anda <span className="text-rose-500">*</span>
                        </label>
                        <div className="relative">
                          <input
                            type="text"
                            value={nipInput}
                            onChange={(e) => handleNipChange(e.target.value)}
                            placeholder="Contoh: 19850101... atau ketik nama lengkap Anda"
                            className="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-900 font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-600 shadow-inner"
                            autoComplete="off"
                          />
                          <Search className="w-4 h-4 text-slate-400 absolute right-3 top-3.5" />
                        </div>

                        {/* Suggestions List */}
                        {suggestions.length > 0 && (
                          <div className="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-20 max-h-48 overflow-y-auto divide-y divide-slate-100">
                            {suggestions.map((emp) => (
                              <button
                                type="button"
                                key={emp.nip}
                                onClick={() => handleSelectSuggestion(emp)}
                                className="w-full text-left px-3.5 py-2.5 hover:bg-teal-50 text-xs transition-colors flex items-center justify-between cursor-pointer"
                              >
                                <div>
                                  <div className="font-bold text-slate-800">{emp.name}</div>
                                  <div className="text-[11px] text-slate-500">
                                    NIP: {emp.nip} • {emp.unitKerja}
                                  </div>
                                </div>
                                <span className="text-[10px] font-bold text-teal-700 bg-teal-100 px-2 py-0.5 rounded-full shrink-0">
                                  Pilih
                                </span>
                              </button>
                            ))}
                          </div>
                        )}
                      </div>

                      {/* Selected Employee Badge Details - Aligned Labels & Colons */}
                      {selectedEmployee && (
                        <div className="p-3.5 sm:p-4 bg-gradient-to-br from-teal-50/90 via-emerald-50/30 to-teal-50/70 border border-teal-200/90 rounded-2xl shadow-xs space-y-2.5 text-xs">
                          <div className="flex items-center justify-between border-b border-teal-200/60 pb-2">
                            <div className="flex items-center gap-1.5 text-teal-950 font-bold">
                              <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                              <span>Pegawai Terverifikasi:</span>
                            </div>
                            <span className="text-[10px] font-bold text-emerald-700 bg-white/95 border border-emerald-200 px-2 py-0.5 rounded-full flex items-center gap-1 shadow-2xs">
                              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                              Data Sesuai
                            </span>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5 text-slate-700 pt-0.5">
                            {/* Nama */}
                            <div className="flex items-start">
                              <span className="w-20 text-slate-500 font-medium shrink-0">Nama</span>
                              <span className="text-slate-400 font-bold mx-1 shrink-0">:</span>
                              <span className="font-bold text-slate-900 break-words flex-1 leading-snug">
                                {selectedEmployee.name}
                              </span>
                            </div>

                            {/* NIP */}
                            <div className="flex items-start">
                              <span className="w-20 text-slate-500 font-medium shrink-0">NIP</span>
                              <span className="text-slate-400 font-bold mx-1 shrink-0">:</span>
                              <span className="font-mono font-bold text-teal-800 break-all flex-1 leading-snug">
                                {selectedEmployee.nip}
                              </span>
                            </div>

                            {/* Unit Kerja */}
                            <div className="flex items-start">
                              <span className="w-20 text-slate-500 font-medium shrink-0">Unit Kerja</span>
                              <span className="text-slate-400 font-bold mx-1 shrink-0">:</span>
                              <span className="font-semibold text-slate-800 break-words flex-1 leading-snug">
                                {selectedEmployee.unitKerja}
                              </span>
                            </div>

                            {/* Jabatan */}
                            <div className="flex items-start">
                              <span className="w-20 text-slate-500 font-medium shrink-0">Jabatan</span>
                              <span className="text-slate-400 font-bold mx-1 shrink-0">:</span>
                              <span className="font-medium text-slate-800 break-words flex-1 leading-snug">
                                {selectedEmployee.jabatan || '-'}
                              </span>
                            </div>

                            {/* Status Kepegawaian */}
                            <div className="flex items-start">
                              <span className="w-20 text-slate-500 font-medium shrink-0">Status</span>
                              <span className="text-slate-400 font-bold mx-1 shrink-0">:</span>
                              <span className="break-words flex-1 leading-snug">
                                <span className={`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold ${
                                  (selectedEmployee.jenisKepegawaian || 'ASN') === 'Non ASN'
                                    ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                    : 'bg-blue-100 text-blue-900 border border-blue-300'
                                }`}>
                                  {selectedEmployee.jenisKepegawaian || 'ASN'}
                                </span>
                              </span>
                            </div>

                            {/* WhatsApp jika Terkunci / Sudah Menyelesaikan */}
                            {isAlreadyCompleted && (
                              <div className="flex items-start pt-1.5 border-t border-emerald-100/70 sm:col-span-2">
                                <span className="w-20 text-slate-500 font-medium shrink-0">WhatsApp</span>
                                <span className="text-slate-400 font-bold mx-1 shrink-0">:</span>
                                <span className="font-semibold text-slate-800 break-words flex-1 leading-snug flex items-center gap-1.5">
                                  <span>{previousResponse?.nomorWa || nomorWa || selectedEmployee.nomorWa || '-'}</span>
                                  <span className="text-[10px] text-emerald-700 bg-emerald-100 font-semibold px-1.5 py-0.5 rounded">
                                    Terdaftar
                                  </span>
                                </span>
                              </div>
                            )}
                          </div>
                        </div>
                      )}

                      {/* Alert Peringatan Jika Pegawai Tidak Sesuai Sasaran Formulir (Ringkas & Efektif) */}
                      {selectedEmployee && isTargetMismatch && (
                        <div className="p-3 bg-rose-50 border border-rose-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs text-rose-900 shadow-2xs">
                          <div className="flex items-center gap-2">
                            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                            <span className="font-semibold leading-snug">
                              {form.targetKepegawaian === 'ASN'
                                ? 'Khusus Pegawai ASN (Non-ASN tidak dapat mengisi formulir ini).'
                                : 'Khusus Pegawai Non-ASN (ASN tidak dapat mengisi formulir ini).'}
                            </span>
                          </div>
                          <button
                            type="button"
                            onClick={() => {
                              setSelectedEmployee(null);
                              setNipInput('');
                              setErrorMessage(null);
                            }}
                            className="px-2.5 py-1 bg-white hover:bg-rose-100 border border-rose-300 rounded-lg font-bold text-rose-800 self-end sm:self-auto text-[11px] transition-colors cursor-pointer shrink-0"
                          >
                            Ganti Pegawai
                          </button>
                        </div>
                      )}

                      {/* Nomor WhatsApp Wajib dengan Validasi Ketat - HANYA jika BELUM SELESAI / DAPAT MENGISI & SESUAI SASARAN */}
                      {!isAlreadyCompleted && !isTargetMismatch && (
                        <div>
                          <div className="flex items-center justify-between mb-1">
                            <label className="text-xs font-bold text-slate-700 flex items-center gap-1">
                              <span>Nomor WhatsApp Aktif (untuk konfirmasi)</span>
                              <span className="text-rose-500 font-bold">*</span>
                            </label>
                            <span className="text-[10px] text-rose-500 font-semibold bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">
                              Wajib Diisi
                            </span>
                          </div>
                          <div className="relative">
                            <input
                              type="tel"
                              value={nomorWa}
                              onChange={(e) => setNomorWa(e.target.value)}
                              placeholder="Contoh: 081234567890"
                              className={`w-full px-3.5 py-2.5 bg-white border rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-colors ${
                                waValidation.state === 'valid'
                                  ? 'border-emerald-400 focus:ring-2 focus:ring-emerald-500 bg-emerald-50/20'
                                  : waValidation.state === 'incomplete' || waValidation.state === 'invalid'
                                  ? 'border-rose-300 focus:ring-2 focus:ring-rose-400 bg-rose-50/20'
                                  : 'border-slate-300 focus:ring-2 focus:ring-teal-500'
                              }`}
                              required
                            />
                            <div className="absolute right-3 top-3 flex items-center gap-1.5 pointer-events-none">
                              {waValidation.state === 'valid' && (
                                <Check className="w-4 h-4 text-emerald-600" />
                              )}
                              <Phone className={`w-3.5 h-3.5 ${waValidation.state === 'valid' ? 'text-emerald-500' : 'text-slate-400'}`} />
                            </div>
                          </div>
                          {/* Feedback Helper Text Realtime */}
                          <div className="mt-1 flex items-center gap-1 text-[11px]">
                            {waValidation.state === 'valid' ? (
                              <span className="text-emerald-600 font-medium flex items-center gap-1">
                                <CheckCircle2 className="w-3 h-3 text-emerald-600 shrink-0" />
                                {waValidation.message}
                              </span>
                            ) : waValidation.state === 'incomplete' ? (
                              <span className="text-amber-700 font-medium flex items-center gap-1 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                <AlertCircle className="w-3 h-3 text-amber-500 shrink-0" />
                                {waValidation.message}
                              </span>
                            ) : waValidation.state === 'invalid' ? (
                              <span className="text-rose-600 font-medium flex items-center gap-1 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">
                                <AlertCircle className="w-3 h-3 text-rose-500 shrink-0" />
                                {waValidation.message}
                              </span>
                            ) : (
                              <span className="text-slate-400">
                                {waValidation.message}
                              </span>
                            )}
                          </div>
                        </div>
                      )}
                    </div>

                    {/* Status: SUDAH MENYELESAIKAN (TERKUNCI) */}
                    {selectedEmployee && !isTargetMismatch && isAlreadyCompleted && (
                      <div className="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs space-y-2 animate-in fade-in">
                        <div className="flex items-center justify-between gap-2">
                          <span className="font-bold text-emerald-900 flex items-center gap-1.5">
                            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                            <span>Status: {previousStatus}</span>
                          </span>
                          <span className="text-[11px] font-semibold text-emerald-700 bg-white border border-emerald-200 px-2 py-0.5 rounded-md flex items-center gap-1 shadow-2xs shrink-0">
                            <Lock className="w-3 h-3 text-emerald-600" />
                            Terkunci
                          </span>
                        </div>

                        <div className="pt-2 border-t border-emerald-100 flex flex-wrap items-center justify-between gap-2 text-slate-600">
                          <span className="text-slate-500">
                            Waktu: {new Date(previousResponse!.submittedAt).toLocaleDateString('id-ID', {
                              day: 'numeric',
                              month: 'short',
                              year: 'numeric',
                            })}, {new Date(previousResponse!.submittedAt).toLocaleTimeString('id-ID', {
                              hour: '2-digit',
                              minute: '2-digit',
                            })} WIB
                          </span>

                          <div className="flex items-center gap-2">
                            {(previousResponse?.nomorWa || nomorWa) && (
                              <span className="text-[11px] text-emerald-900 font-medium flex items-center gap-1 bg-white/80 border border-emerald-200 px-2 py-0.5 rounded">
                                <Phone className="w-3 h-3 text-emerald-600" />
                                <span>{previousResponse?.nomorWa || nomorWa}</span>
                              </span>
                            )}
                            {previousResponse?.answers?.link_gdrive_sertifikat && (
                              <a
                                href={previousResponse.answers.link_gdrive_sertifikat}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-bold text-teal-700 hover:text-teal-900 hover:underline flex items-center gap-1 text-[11px]"
                              >
                                <span>Bukti Sertifikat</span>
                                <ExternalLink className="w-3 h-3" />
                              </a>
                            )}
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Status: SUDAH MULAI / BELUM MULAI (BISA UPDATE) */}
                    {selectedEmployee && !isTargetMismatch && isUpdatingPrevious && (
                      <div className="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs space-y-2 animate-in fade-in">
                        <div className="flex items-center justify-between gap-2">
                          <span className="font-bold text-amber-900 flex items-center gap-1.5">
                            <RotateCcw className="w-4 h-4 text-amber-600 shrink-0" />
                            <span>Status Saat Ini: {previousStatus}</span>
                          </span>
                          <span className="text-[11px] font-semibold text-amber-800 bg-white border border-amber-200 px-2 py-0.5 rounded-md shadow-2xs shrink-0">
                            Dapat Diperbarui
                          </span>
                        </div>
                        <div className="pt-2 border-t border-amber-100 text-slate-500">
                          Waktu Terakhir: {new Date(previousResponse!.submittedAt).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                          })}, {new Date(previousResponse!.submittedAt).toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                          })} WIB
                        </div>
                      </div>
                    )}

                    {/* 2. FORM SECTION 1 (Pilihan Status Utama) */}
                    {!isAlreadyCompleted && !isTargetMismatch && firstSection && (
                      <div className="bg-white border-2 border-teal-600/30 rounded-2xl p-3.5 sm:p-5 shadow-xs space-y-3.5">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-2.5">
                          <span className="text-xs font-bold text-teal-800 uppercase tracking-wider flex items-center gap-1.5">
                            <Sparkles className="w-4 h-4 text-teal-600" />
                            {firstSection.title || 'Bagian 1: Pilihan Status'}
                          </span>
                          <span className="text-[11px] font-bold text-rose-500">* Wajib Diisi</span>
                        </div>

                        {firstSection.description && (
                          <p className="text-xs text-slate-600 leading-relaxed">{firstSection.description}</p>
                        )}

                        <div className="space-y-3.5">
                          {firstSection.questions.map((q) => renderQuestion(q, firstSection.id))}
                        </div>
                      </div>
                    )}
                  </motion.div>
                ) : (
                  <motion.div
                    key="step-2"
                    custom={stepDirection}
                    initial={{ opacity: 0, x: stepDirection > 0 ? 30 : -30 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: stepDirection < 0 ? 30 : -30 }}
                    transition={{ duration: 0.2 }}
                    className="space-y-4 sm:space-y-5"
                  >
                    {/* Ringkasan Pegawai & Status Terpilih (Chip) */}
                    <div className="p-3 bg-gradient-to-r from-teal-50 to-emerald-50/50 border border-teal-200 rounded-xl flex items-center justify-between gap-3 text-xs">
                      <div className="min-w-0">
                        <div className="font-bold text-slate-900 truncate">
                          {selectedEmployee?.name}
                        </div>
                        <div className="text-[11px] text-teal-800 flex items-center gap-1.5 flex-wrap">
                          <span className="font-mono">NIP: {selectedEmployee?.nip}</span>
                          <span>•</span>
                          <span className="font-bold px-2 py-0.5 rounded-md bg-white border border-teal-300 text-teal-900 shadow-2xs">
                            {selectedPrimaryStatus}
                          </span>
                        </div>
                      </div>
                      <button
                        type="button"
                        onClick={handlePrevStep}
                        className="text-[11px] font-bold text-teal-700 hover:text-teal-900 hover:underline px-2.5 py-1 bg-white rounded-lg border border-teal-200 shrink-0 shadow-2xs cursor-pointer"
                        title="Kembali ke Langkah 1 untuk ubah data"
                      >
                        Ubah
                      </button>
                    </div>

                    {/* Conditional Target Section atau Linear Section 2..n */}
                    {hasBranching ? (
                      activeConditionalSection ? (
                        <div className="bg-slate-50/80 border-2 border-teal-500/40 rounded-2xl p-3.5 sm:p-5 shadow-sm space-y-3.5">
                          <div className="border-b border-slate-200 pb-3">
                            <h4 className="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">
                              {activeConditionalSection.title}
                            </h4>
                            {activeConditionalSection.description && (
                              <p className="text-xs text-slate-600 mt-1 leading-relaxed">
                                {activeConditionalSection.description}
                              </p>
                            )}
                          </div>

                          <div className="space-y-3.5">
                            {activeConditionalSection.questions.map((q) =>
                              renderQuestion(q, activeConditionalSection.id)
                            )}
                          </div>
                        </div>
                      ) : (
                        <div className="p-6 text-center text-slate-500 bg-slate-50 rounded-2xl border border-slate-200 text-xs">
                          Tidak ada pertanyaan tambahan untuk status ini. Silakan langsung klik tombol <strong>Kirim</strong> di bawah.
                        </div>
                      )
                    ) : (
                      form.sections.slice(1).map((sec, secIdx) => (
                        <div
                          key={sec.id}
                          className="bg-white border-2 border-slate-200 rounded-2xl p-3.5 sm:p-5 shadow-xs space-y-3.5"
                        >
                          <div className="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <span className="text-xs font-bold text-teal-800 uppercase tracking-wider flex items-center gap-1.5">
                              <Sparkles className="w-4 h-4 text-teal-600" />
                              {sec.title || `Bagian ${secIdx + 2}`}
                            </span>
                            <span className="text-[10px] font-semibold text-slate-400">
                              Bagian {secIdx + 2} dari {form.sections.length}
                            </span>
                          </div>

                          {sec.description && (
                            <p className="text-xs text-slate-600 leading-relaxed">{sec.description}</p>
                          )}

                          <div className="space-y-3.5">
                            {sec.questions.map((q) => renderQuestion(q, sec.id))}
                          </div>
                        </div>
                      ))
                    )}
                  </motion.div>
                )}
              </AnimatePresence>
            </div>

            {/* Footer Buttons (Pinned at Bottom with Safe Area Padding for Mobile) */}
            <div
              className="shrink-0 p-3 sm:p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 shadow-[0_-4px_12px_rgba(0,0,0,0.04)]"
              style={{ paddingBottom: 'max(14px, env(safe-area-inset-bottom, 14px))' }}
            >
              {isAlreadyCompleted ? (
                <>
                  <span className="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
                    <Lock className="w-3.5 h-3.5 text-slate-400" />
                    <span>Formulir telah selesai (Terkunci)</span>
                  </span>

                  <button
                    type="button"
                    onClick={onClose}
                    className="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-200 hover:bg-slate-300 transition-all cursor-pointer"
                  >
                    Tutup
                  </button>
                </>
              ) : currentStep === 1 && totalSteps > 1 ? (
                <>
                  <button
                    type="button"
                    onClick={onClose}
                    disabled={isSubmitting}
                    className="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-100 transition-all cursor-pointer"
                  >
                    Batal
                  </button>

                  <button
                    type="button"
                    onClick={handleNextStep}
                    disabled={isSubmitting || !selectedEmployee || isTargetMismatch}
                    className={`px-5 sm:px-6 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer ${
                      isSubmitting || !selectedEmployee || isTargetMismatch
                        ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none'
                        : 'text-white bg-teal-700 hover:bg-teal-800 shadow-md shadow-teal-900/20 active:scale-[0.98]'
                    }`}
                  >
                    <span>{isTargetMismatch ? 'Tidak Memenuhi Sasaran' : 'Berikutnya'}</span>
                    {!isTargetMismatch && <ArrowRight className="w-4 h-4" />}
                  </button>
                </>
              ) : (
                <>
                  {totalSteps > 1 ? (
                    <button
                      type="button"
                      onClick={handlePrevStep}
                      disabled={isSubmitting}
                      className="px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                    >
                      <ArrowLeft className="w-3.5 h-3.5" />
                      <span>Kembali</span>
                    </button>
                  ) : (
                    <button
                      type="button"
                      onClick={onClose}
                      disabled={isSubmitting}
                      className="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-100 transition-all cursor-pointer"
                    >
                      Batal
                    </button>
                  )}

                  <button
                    type="submit"
                    disabled={isSubmitting || !selectedEmployee || isTargetMismatch || !canSubmitStep2}
                    className={`px-5 sm:px-6 py-2.5 rounded-xl text-xs font-bold text-white transition-all shadow-md flex items-center gap-2 cursor-pointer ${
                      isSubmitting || !selectedEmployee || isTargetMismatch || !canSubmitStep2
                        ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none'
                        : isUpdatingPrevious
                        ? 'bg-amber-600 hover:bg-amber-700 shadow-amber-900/20 active:scale-[0.98]'
                        : 'bg-teal-700 hover:bg-teal-800 shadow-teal-900/20 active:scale-[0.98]'
                    }`}
                  >
                    {isSubmitting ? (
                      <>
                        <div className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                        <span>Menyimpan...</span>
                      </>
                    ) : isUpdatingPrevious ? (
                      <>
                        <RotateCcw className="w-3.5 h-3.5" />
                        <span>Perbarui Status</span>
                      </>
                    ) : (
                      <>
                        <Send className="w-3.5 h-3.5" />
                        <span>Kirim</span>
                      </>
                    )}
                  </button>
                </>
              )}
            </div>
          </form>
        )}
      </motion.div>
    </div>
  );
};

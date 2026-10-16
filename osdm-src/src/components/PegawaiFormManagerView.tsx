import React, { useState, useMemo, useEffect, useRef } from 'react';
import {
  FileText,
  Users,
  CheckCircle2,
  AlertTriangle,
  FileSpreadsheet,
  Search,
  Filter,
  ExternalLink,
  MessageCircle,
  Copy,
  Check,
  Eye,
  Trash2,
  FilePlus,
  PlusCircle,
  RefreshCw,
  QrCode,
  Layers,
  ArrowRight,
  Download,
  X,
  FileCheck,
  Building2,
  UserCheck,
  UserX,
  Sliders,
  ChevronDown
} from 'lucide-react';
import { PegawaiForm, PegawaiFormResponse, EmployeeRecord } from '../types';
import { getActiveEmployees, getPoltekkesUnitKerjaList } from '../data/employeeDatabase';
import bannerMoocAsn from '../assets/banner-mooc-asn.png';
import { PegawaiFormConfigModal } from './PegawaiFormConfigModal';
import {
  exportFormResponsesToExcel,
  exportFormUnsubmittedEmployeesToExcel,
  generateWhatsAppReminderUrl
} from '../utils/pegawaiFormHelper';

interface PegawaiFormManagerViewProps {
  forms: PegawaiForm[];
  selectedFormId: string;
  onSelectForm: (formId: string) => void;
  responses: PegawaiFormResponse[];
  onDeleteResponse: (responseId: string) => Promise<boolean>;
  onOpenFillModal: (form: PegawaiForm) => void;
  onCreateNewForm?: () => void;
  onToggleFormStatus?: (formId: string, isActive: boolean) => Promise<boolean>;
  onUpdateForm?: (form: PegawaiForm) => Promise<boolean>;
  onDeleteForm?: (formId: string) => Promise<boolean>;
}

export const PegawaiFormManagerView: React.FC<PegawaiFormManagerViewProps> = ({
  forms,
  selectedFormId,
  onSelectForm,
  responses,
  onDeleteResponse,
  onOpenFillModal,
  onCreateNewForm,
  onToggleFormStatus,
  onUpdateForm,
  onDeleteForm,
}) => {
  // Active sub-tab: 'sudah' (Respon Masuk) vs 'belum' (Pegawai Belum Mengisi)
  const [activeSubTab, setActiveSubTab] = useState<'sudah' | 'belum'>('sudah');

  // Filters & Search
  const [unitFilter, setUnitFilter] = useState<string>('Semua Unit');
  const [selectedStatuses, setSelectedStatuses] = useState<string[]>([]);
  const [isStatusDropdownOpen, setIsStatusDropdownOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState<string>('');

  const statusDropdownRef = useRef<HTMLDivElement>(null);

  // Close status dropdown on outside click
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        statusDropdownRef.current &&
        !statusDropdownRef.current.contains(event.target as Node)
      ) {
        setIsStatusDropdownOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  // Reset status filter when active form changes
  useEffect(() => {
    setSelectedStatuses([]);
  }, [selectedFormId]);

  // UI state for preview modal
  const [previewFile, setPreviewFile] = useState<{
    fileName: string;
    dataUrl: string;
    fileType?: string;
  } | null>(null);

  const [copiedSessionId, setCopiedSessionId] = useState<string | null>(null);
  const [saveSuccessToast, setSaveSuccessToast] = useState(false);

  // Modal Konfigurasi state
  const [isConfigModalOpen, setIsConfigModalOpen] = useState(false);
  const [configTargetForm, setConfigTargetForm] = useState<PegawaiForm | null>(null);

  const handleOpenConfigModal = (f: PegawaiForm) => {
    setConfigTargetForm(f);
    setIsConfigModalOpen(true);
  };

  // Active Selected Form
  const currentForm = useMemo(() => {
    return forms.find((f) => f.id === selectedFormId) || forms[0] || null;
  }, [forms, selectedFormId]);

  // Master active employees of Poltekkes Bandung
  const allMasterEmployees = useMemo(() => {
    return getActiveEmployees();
  }, []);

  const allUnits = useMemo(() => {
    return getPoltekkesUnitKerjaList();
  }, []);

  // Filter responses belonging to current form
  const formResponses = useMemo(() => {
    if (!currentForm) return [];
    return responses.filter((r) => r.formId === currentForm.id);
  }, [responses, currentForm]);

  // Set of NIPs that have submitted for current form
  const submittedNipSet = useMemo(() => {
    const set = new Set<string>();
    formResponses.forEach((r) => {
      if (r.nip) {
        set.add(r.nip.replace(/[\s.-]/g, '').toLowerCase());
      }
    });
    return set;
  }, [formResponses]);

  const [detailModalResponse, setDetailModalResponse] = useState<PegawaiFormResponse | null>(null);

  // Available status options from current form
  const availableStatusOptions = useMemo(() => {
    const set = new Set<string>();
    currentForm?.sections.forEach((sec) => {
      sec.questions.forEach((q) => {
        q.options?.forEach((opt) => {
          if (opt.label) set.add(opt.label);
        });
      });
    });
    formResponses.forEach((r) => {
      if (r.answers?.status_pelatihan) set.add(r.answers.status_pelatihan);
    });
    const result = Array.from(set);
    if (result.length > 0) return result;
    return [
      'Sudah menyelesaikan Pelatihan',
      'Sudah mulai Pelatihan',
      'Belum mulai Pelatihan',
    ];
  }, [currentForm, formResponses]);

  const handleToggleStatus = (status: string) => {
    setSelectedStatuses((prev) => {
      if (prev.length === 0) {
        return [status];
      }
      if (prev.includes(status)) {
        return prev.filter((s) => s !== status);
      } else {
        const next = [...prev, status];
        if (next.length === availableStatusOptions.length) {
          return [];
        }
        return next;
      }
    });
  };

  const handleToggleAllStatuses = () => {
    setSelectedStatuses([]);
  };

  const statusButtonLabel = useMemo(() => {
    if (selectedStatuses.length === 0 || selectedStatuses.length === availableStatusOptions.length) {
      return 'Semua Status / Pilihan';
    }
    if (selectedStatuses.length === 1) {
      return selectedStatuses[0];
    }
    return `${selectedStatuses.length} Status Terpilih`;
  }, [selectedStatuses, availableStatusOptions]);

  // Filtered Submissions (Sudah Mengisi)
  const filteredSubmissions = useMemo(() => {
    return formResponses.filter((sub) => {
      // Unit filter
      if (unitFilter !== 'Semua Unit' && sub.unitKerja !== unitFilter) {
        return false;
      }

      // Status filter (multi-select)
      if (selectedStatuses.length > 0 && selectedStatuses.length < availableStatusOptions.length) {
        const currentAns =
          sub.answers?.status_pelatihan ||
          sub.answers?.[currentForm?.sections[0]?.questions[0]?.id] ||
          '';
        if (!selectedStatuses.includes(currentAns)) return false;
      }

      // Search Query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchNip = sub.nip.toLowerCase().includes(q);
        const matchName = sub.namaPegawai.toLowerCase().includes(q);
        const matchUnit = sub.unitKerja.toLowerCase().includes(q);
        if (!matchNip && !matchName && !matchUnit) {
          return false;
        }
      }

      return true;
    });
  }, [formResponses, currentForm, unitFilter, selectedStatuses, availableStatusOptions, searchQuery]);

  // Unsubmitted Employees (Belum Mengisi)
  const unsubmittedEmployees = useMemo(() => {
    return allMasterEmployees.filter((emp) => {
      // Exclude non-active statuses
      if (emp.status && emp.status !== 'Aktif') {
        return false;
      }

      // Filter target sasaran kepegawaian (ASN vs Non ASN)
      if (currentForm?.targetKepegawaian === 'ASN') {
        const empType = emp.jenisKepegawaian || 'ASN';
        if (empType !== 'ASN') return false;
      } else if (currentForm?.targetKepegawaian === 'Non ASN') {
        if (emp.jenisKepegawaian !== 'Non ASN') return false;
      }

      const cleanNip = emp.nip.replace(/[\s.-]/g, '').toLowerCase();
      const hasSubmitted = submittedNipSet.has(cleanNip);
      if (hasSubmitted) return false;

      // Unit filter
      if (unitFilter !== 'Semua Unit' && emp.unitKerja !== unitFilter) {
        return false;
      }

      // Search Query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchNip = emp.nip.toLowerCase().includes(q);
        const matchName = emp.name.toLowerCase().includes(q);
        const matchUnit = emp.unitKerja.toLowerCase().includes(q);
        if (!matchNip && !matchName && !matchUnit) {
          return false;
        }
      }

      return true;
    });
  }, [allMasterEmployees, submittedNipSet, unitFilter, searchQuery, currentForm?.targetKepegawaian]);

  // Target in scope for KPI cards
  const targetEmployeesInScope = useMemo(() => {
    let active = allMasterEmployees.filter((e) => !e.status || e.status === 'Aktif');
    if (currentForm?.targetKepegawaian === 'ASN') {
      active = active.filter((e) => (e.jenisKepegawaian || 'ASN') === 'ASN');
    } else if (currentForm?.targetKepegawaian === 'Non ASN') {
      active = active.filter((e) => e.jenisKepegawaian === 'Non ASN');
    }
    if (unitFilter === 'Semua Unit') return active;
    return active.filter((e) => e.unitKerja === unitFilter);
  }, [allMasterEmployees, unitFilter, currentForm?.targetKepegawaian]);

  // Breakdown statistics for current form
  const stats = useMemo(() => {
    let selesaiCount = 0;
    let mulaiCount = 0;
    let belumCount = 0;

    formResponses.forEach((r) => {
      const st = r.answers?.status_pelatihan || '';
      if (st.includes('Sudah menyelesaikan') || st.includes('Selesai')) {
        selesaiCount++;
      } else if (st.includes('Sudah mulai')) {
        mulaiCount++;
      } else if (st.includes('Belum')) {
        belumCount++;
      }
    });

    const totalTarget = targetEmployeesInScope.length || 1;
    const sudahMengisiCount = submittedNipSet.size;
    const persentaseSudah = Math.min(100, Math.round((sudahMengisiCount / totalTarget) * 100));

    return {
      selesaiCount,
      mulaiCount,
      belumCount,
      totalTarget,
      sudahMengisiCount,
      persentaseSudah,
    };
  }, [formResponses, targetEmployeesInScope, submittedNipSet]);

  const getFormPublicUrl = () => {
    if (!currentForm) return '';
    return `${window.location.origin}${window.location.pathname}?form=${encodeURIComponent(currentForm.id)}`;
  };

  if (!currentForm) {
    return (
      <div className="bg-white p-10 sm:p-14 rounded-2xl border border-slate-200 text-center space-y-4 shadow-xs">
        <div className="w-16 h-16 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center mx-auto border border-teal-100">
          <Layers className="w-8 h-8" />
        </div>
        <div className="space-y-1">
          <h3 className="text-base font-bold text-slate-800">Belum Ada Formulir Pegawai</h3>
          <p className="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
            Belum ada formulir aktif yang tersedia. Anda dapat membuat formulir survei atau kuesioner baru untuk mulai mengumpulkan data pegawai.
          </p>
        </div>
        {onCreateNewForm && (
          <button
            onClick={onCreateNewForm}
            className="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-700 hover:bg-teal-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
          >
            <FilePlus className="w-4 h-4" />
            <span>Buat Formulir Baru</span>
          </button>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* 1. Multi-Form Switcher Header & Cards (Model Kartu seperti Daftar Hadir Rapat) */}
      <div className="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-xl bg-teal-50 text-teal-700 border border-teal-100">
              <Layers className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-sm sm:text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>Daftar Formulir & Survei Pegawai</span>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">
                  {forms.length || 1} Formulir
                </span>
              </h2>
              <p className="text-[11px] text-slate-500">
                Pilih kartu formulir untuk memantau responden, atau klik tombol <strong>Konfigurasi</strong> untuk mengedit banner 5:1 dan struktur pertanyaan.
              </p>
            </div>
          </div>

          {onCreateNewForm && (
            <button
              type="button"
              onClick={onCreateNewForm}
              className="group px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-xs hover:shadow-sm cursor-pointer shrink-0"
            >
              <PlusCircle className="w-4 h-4 text-teal-200 group-hover:text-white group-hover:scale-110 transition-all shrink-0" />
              <span>Buat Form Baru</span>
            </button>
          )}
        </div>

        {/* Horizontal Form Cards Row */}
        <div className="flex items-stretch gap-3.5 overflow-x-auto pb-2 pt-0.5 scrollbar-thin">
          {forms.map((f) => {
            const isCurrent = f.id === currentForm.id;
            const count = responses.filter((r) => r.formId === f.id).length;
            const isActive = f.isActive !== false;
            const qCount = f.sections?.reduce((acc, sec) => acc + (sec.questions?.length || 0), 0) || 0;

            return (
              <div
                key={f.id}
                onClick={() => onSelectForm && onSelectForm(f.id)}
                className={`group flex-shrink-0 cursor-pointer rounded-2xl p-3.5 border transition-all text-left w-84 sm:w-96 relative flex flex-col justify-between overflow-hidden shadow-xs hover:shadow-md ${
                  isCurrent
                    ? 'bg-teal-50/95 border-teal-500 shadow-sm ring-2 ring-teal-600/30'
                    : 'bg-slate-50/80 border-slate-200 hover:bg-white hover:border-slate-300'
                }`}
              >
                <div>
                  {/* Mini Banner Preview (Rasio 4:1) */}
                  <div className="relative w-full aspect-[4/1] rounded-xl overflow-hidden bg-slate-100 border border-slate-200 mb-2.5 shadow-2xs">
                    <img
                      src={f.bannerUrl || bannerMoocAsn}
                      alt={f.title}
                      className="w-full h-full object-cover object-center block select-none"
                    />
                    <div className="absolute top-1.5 left-1.5 flex items-center gap-1 flex-wrap max-w-[90%]">
                      <span className="bg-black/60 backdrop-blur-xs text-white text-[9px] font-bold px-1.5 py-0.5 rounded-md border border-white/20">
                        {f.category || 'Formulir'}
                      </span>
                      {f.targetKepegawaian === 'ASN' ? (
                        <span className="bg-blue-600/90 backdrop-blur-xs text-white text-[9px] font-bold px-1.5 py-0.5 rounded-md border border-white/20">
                          Khusus ASN
                        </span>
                      ) : f.targetKepegawaian === 'Non ASN' ? (
                        <span className="bg-amber-600/90 backdrop-blur-xs text-white text-[9px] font-bold px-1.5 py-0.5 rounded-md border border-white/20">
                          Khusus Non-ASN
                        </span>
                      ) : null}
                    </div>
                  </div>

                  <div className="flex items-center justify-between gap-2 mb-1.5">
                    <span className="inline-flex items-center gap-1.5 text-[10px] font-bold">
                      <span
                        className={`w-2 h-2 rounded-full ${
                          isActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'
                        }`}
                      />
                      <span className={isActive ? 'text-emerald-700 font-bold' : 'text-slate-500'}>
                        {isActive ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </span>
                    <span className="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-white border border-slate-200 text-teal-800 shadow-2xs">
                      {count} Respon
                    </span>
                  </div>

                  <h4
                    className={`text-xs font-black line-clamp-1 leading-snug ${
                      isCurrent ? 'text-teal-950' : 'text-slate-800 group-hover:text-teal-700'
                    }`}
                    title={f.title}
                  >
                    {f.title}
                  </h4>

                  <p className="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                    {f.description || 'Tidak ada deskripsi tambahan.'}
                  </p>

                  <div className="flex items-center gap-2 text-[10px] text-slate-500 mt-1.5 truncate">
                    <span>{f.sections?.length || 1} Bagian</span>
                    <span>•</span>
                    <span>{qCount} Pertanyaan</span>
                    <span>•</span>
                    <span className={`font-bold ${
                      f.targetKepegawaian === 'ASN'
                        ? 'text-blue-700'
                        : f.targetKepegawaian === 'Non ASN'
                        ? 'text-amber-700'
                        : 'text-slate-600'
                    }`}>
                      {f.targetKepegawaian === 'ASN'
                        ? '🏛️ Sasaran: ASN'
                        : f.targetKepegawaian === 'Non ASN'
                        ? '📋 Sasaran: Non-ASN'
                        : '👥 Semua Pegawai'}
                    </span>
                  </div>
                </div>

                {/* Card Quick Action Buttons */}
                <div className="flex items-center justify-between pt-2.5 mt-2.5 border-t border-slate-200/60">
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleOpenConfigModal(f);
                    }}
                    className="text-[10px] font-bold text-teal-800 bg-white hover:bg-teal-100/70 border border-slate-200 hover:border-teal-300 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    title="Buka jendela konfigurasi untuk mengedit banner 5:1 dan struktur pertanyaan formulir ini"
                  >
                    <Sliders className="w-3 h-3 text-teal-700" />
                    <span>Konfigurasi</span>
                  </button>

                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        onOpenFillModal(f);
                      }}
                      className="p-1 rounded-lg text-slate-500 hover:text-teal-700 hover:bg-white transition-colors cursor-pointer"
                      title="Buka & Isi Formulir"
                    >
                      <ExternalLink className="w-3.5 h-3.5" />
                    </button>

                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        const baseUrl = window.location.origin + window.location.pathname;
                        navigator.clipboard.writeText(`${baseUrl}?form=${encodeURIComponent(f.id)}`);
                        setCopiedSessionId(f.id);
                        setTimeout(() => setCopiedSessionId(null), 2000);
                      }}
                      className="p-1 rounded-lg text-slate-500 hover:text-teal-700 hover:bg-white transition-colors cursor-pointer"
                      title={copiedSessionId === f.id ? 'Tautan disalin!' : 'Salin tautan formulir ini'}
                    >
                      {copiedSessionId === f.id ? (
                        <Check className="w-3.5 h-3.5 text-emerald-600" />
                      ) : (
                        <Copy className="w-3.5 h-3.5" />
                      )}
                    </button>

                    {forms.length > 1 && onDeleteForm && (
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          if (
                            window.confirm(
                              `Hapus formulir "${f.title}" beserta seluruh respon yang masuk?`
                            )
                          ) {
                            onDeleteForm(f.id);
                          }
                        }}
                        className="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-white transition-colors cursor-pointer"
                        title="Hapus formulir ini"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {saveSuccessToast && (
        <div className="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs animate-in fade-in slide-in-from-top-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
          <span>Konfigurasi formulir berhasil disimpan!</span>
        </div>
      )}

      {/* 2. Kartu KPI & Ringkasan Kepatuhan */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Total Target */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
          <div className="flex items-center justify-between text-slate-500">
            <span className="text-xs font-bold">Target Pegawai Aktif</span>
            <Users className="w-4 h-4 text-slate-400" />
          </div>
          <div className="text-2xl font-black text-slate-900">{stats.totalTarget}</div>
          <div className="text-[11px] text-slate-500 flex items-center justify-between gap-1">
            <span className="truncate">{unitFilter === 'Semua Unit' ? 'Seluruh Unit Polkesban' : unitFilter}</span>
            <span className={`font-bold px-1.5 py-0.5 rounded text-[10px] shrink-0 ${
              currentForm?.targetKepegawaian === 'ASN'
                ? 'bg-blue-100 text-blue-800'
                : currentForm?.targetKepegawaian === 'Non ASN'
                ? 'bg-amber-100 text-amber-800'
                : 'bg-slate-100 text-slate-700'
            }`}>
              {currentForm?.targetKepegawaian === 'ASN'
                ? 'Khusus ASN'
                : currentForm?.targetKepegawaian === 'Non ASN'
                ? 'Khusus Non-ASN'
                : 'Semua'}
            </span>
          </div>
        </div>

        {/* Sudah Mengisi */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-teal-200 shadow-xs space-y-1 bg-gradient-to-br from-teal-50/50 to-white">
          <div className="flex items-center justify-between text-teal-700">
            <span className="text-xs font-bold">Sudah Mengisi</span>
            <UserCheck className="w-4 h-4 text-teal-600" />
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl font-black text-teal-900">{stats.sudahMengisiCount}</span>
            <span className="text-xs font-bold text-teal-700">({stats.persentaseSudah}%)</span>
          </div>
          {/* Mini progress bar */}
          <div className="w-full bg-teal-100 rounded-full h-1.5 mt-1 overflow-hidden">
            <div
              className="bg-teal-600 h-1.5 rounded-full transition-all duration-500"
              style={{ width: `${stats.persentaseSudah}%` }}
            />
          </div>
        </div>

        {/* Belum Mengisi */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-rose-200 shadow-xs space-y-1 bg-gradient-to-br from-rose-50/50 to-white">
          <div className="flex items-center justify-between text-rose-700">
            <span className="text-xs font-bold">Belum Mengisi</span>
            <UserX className="w-4 h-4 text-rose-600" />
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl font-black text-rose-900">
              {Math.max(0, stats.totalTarget - stats.sudahMengisiCount)}
            </span>
            <span className="text-xs font-bold text-rose-700">
              ({Math.max(0, 100 - stats.persentaseSudah)}%)
            </span>
          </div>
          <div className="text-[11px] text-rose-600 font-semibold flex items-center gap-1">
            <AlertTriangle className="w-3 h-3" />
            <span>Perlu Tindak Lanjut / Reminder</span>
          </div>
        </div>

        {/* Rincian Status Jawaban */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
          <div className="flex items-center justify-between text-slate-500">
            <span className="text-xs font-bold">Status Pelatihan</span>
            <CheckCircle2 className="w-4 h-4 text-emerald-500" />
          </div>
          <div className="grid grid-cols-3 gap-1 pt-1 text-center">
            <div className="bg-emerald-50 p-1.5 rounded-lg border border-emerald-200">
              <div className="text-xs font-extrabold text-emerald-800">{stats.selesaiCount}</div>
              <div className="text-[9px] text-emerald-700 font-bold leading-tight">Selesai</div>
            </div>
            <div className="bg-amber-50 p-1.5 rounded-lg border border-amber-200">
              <div className="text-xs font-extrabold text-amber-800">{stats.mulaiCount}</div>
              <div className="text-[9px] text-amber-700 font-bold leading-tight">Mulai</div>
            </div>
            <div className="bg-rose-50 p-1.5 rounded-lg border border-rose-200">
              <div className="text-xs font-extrabold text-rose-800">{stats.belumCount}</div>
              <div className="text-[9px] text-rose-700 font-bold leading-tight">Belum</div>
            </div>
          </div>
        </div>
      </div>

      {/* 3. Sub-Tab Switcher (Sudah Mengisi vs Belum Mengisi) + Excel Action */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-100 p-1.5 rounded-2xl">
        <div className="flex items-center gap-1.5 w-full sm:w-auto">
          {/* Tab: Respon Masuk */}
          <button
            onClick={() => setActiveSubTab('sudah')}
            className={`flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all ${
              activeSubTab === 'sudah'
                ? 'bg-white text-teal-800 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <CheckCircle2 className="w-4 h-4 text-teal-600" />
            <span>Data Respon Masuk</span>
            <span className="px-2 py-0.5 rounded-full text-[10px] bg-teal-100 text-teal-800 font-extrabold">
              {filteredSubmissions.length}
            </span>
          </button>

          {/* Tab: Pegawai Belum Mengisi */}
          <button
            onClick={() => setActiveSubTab('belum')}
            className={`flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all ${
              activeSubTab === 'belum'
                ? 'bg-white text-rose-700 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <AlertTriangle className="w-4 h-4 text-rose-500" />
            <span>Pegawai Belum Mengisi</span>
            <span className="px-2 py-0.5 rounded-full text-[10px] bg-rose-100 text-rose-800 font-extrabold">
              {unsubmittedEmployees.length}
            </span>
          </button>
        </div>

        {/* Download Excel CTA per tab */}
        {activeSubTab === 'belum' ? (
          <button
            onClick={() =>
              exportFormUnsubmittedEmployeesToExcel(currentForm, unsubmittedEmployees, unitFilter)
            }
            className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-all w-full sm:w-auto justify-center"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>Download Excel Pegawai Belum Mengisi ({unsubmittedEmployees.length})</span>
          </button>
        ) : (
          <button
            onClick={() => exportFormResponsesToExcel(currentForm, filteredSubmissions, unitFilter)}
            className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold shadow-xs transition-all w-full sm:w-auto justify-center"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>Download Excel Rekap Hasil ({filteredSubmissions.length})</span>
          </button>
        )}
      </div>

      {/* 4. Filter Toolbar (Unit Kerja, Status, Search) */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs grid grid-cols-1 sm:grid-cols-3 gap-3">
        {/* Dropdown Unit Kerja */}
        <div>
          <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
            Filter Unit Kerja / Jurusan
          </label>
          <select
            value={unitFilter}
            onChange={(e) => setUnitFilter(e.target.value)}
            className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer"
          >
            <option value="Semua Unit">Semua Unit Kerja ({allUnits.length})</option>
            {allUnits.map((u) => (
              <option key={u} value={u}>
                {u}
              </option>
            ))}
          </select>
        </div>

        {/* Dropdown Multi-Select Status Pilihan (hanya di tab 'sudah') */}
        <div className="relative" ref={statusDropdownRef}>
          <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
            Filter Status / Jawaban
          </label>
          <button
            type="button"
            disabled={activeSubTab === 'belum'}
            onClick={() => setIsStatusDropdownOpen((prev) => !prev)}
            className={`w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 flex items-center justify-between gap-2 text-left transition-all ${
              activeSubTab === 'belum'
                ? 'opacity-50 cursor-not-allowed'
                : 'cursor-pointer hover:bg-slate-100/70 hover:border-slate-300'
            }`}
          >
            <div className="flex items-center gap-1.5 min-w-0 flex-1">
              <span className="truncate">{statusButtonLabel}</span>
              {selectedStatuses.length > 1 && selectedStatuses.length < availableStatusOptions.length && (
                <span className="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-100 text-teal-800 shrink-0">
                  {selectedStatuses.length}
                </span>
              )}
            </div>
            <ChevronDown
              className={`w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200 ${
                isStatusDropdownOpen ? 'rotate-180 text-teal-600' : ''
              }`}
            />
          </button>

          {/* Menu Popover Checkbox */}
          {isStatusDropdownOpen && activeSubTab !== 'belum' && (
            <div className="absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg p-2 space-y-1 animate-in fade-in duration-100">
              {/* Opsi: Semua Status / Pilihan */}
              <button
                type="button"
                onClick={handleToggleAllStatuses}
                className="w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors text-left text-xs font-semibold text-slate-700 cursor-pointer"
              >
                <input
                  type="checkbox"
                  readOnly
                  checked={
                    selectedStatuses.length === 0 ||
                    selectedStatuses.length === availableStatusOptions.length
                  }
                  className="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300 pointer-events-none accent-teal-600"
                />
                <span className="flex-1 font-bold text-slate-800">
                  Semua Status / Pilihan
                </span>
              </button>

              <div className="h-px bg-slate-100 my-1" />

              {/* Opsi Status Individual */}
              <div className="max-h-52 overflow-y-auto space-y-0.5 pr-0.5">
                {availableStatusOptions.map((st) => {
                  const isChecked = selectedStatuses.includes(st);
                  return (
                    <button
                      key={st}
                      type="button"
                      onClick={() => handleToggleStatus(st)}
                      className={`w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg transition-colors text-left text-xs cursor-pointer ${
                        isChecked
                          ? 'bg-teal-50/70 text-teal-900 font-semibold'
                          : 'hover:bg-slate-50 text-slate-700'
                      }`}
                    >
                      <input
                        type="checkbox"
                        readOnly
                        checked={isChecked}
                        className="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300 pointer-events-none accent-teal-600"
                      />
                      <span className="flex-1 leading-snug break-words">{st}</span>
                    </button>
                  );
                })}
              </div>

              {selectedStatuses.length > 0 && selectedStatuses.length < availableStatusOptions.length && (
                <div className="pt-1 border-t border-slate-100 flex justify-between items-center px-1">
                  <span className="text-[10px] text-slate-400 font-medium">
                    {selectedStatuses.length} dari {availableStatusOptions.length} dipilih
                  </span>
                  <button
                    type="button"
                    onClick={() => setSelectedStatuses([])}
                    className="text-[10px] text-rose-600 hover:text-rose-700 font-bold hover:underline cursor-pointer"
                  >
                    Reset Filter
                  </button>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Pencarian (NIP / Nama) */}
        <div>
          <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
            Cari Nama atau NIP Pegawai
          </label>
          <div className="relative">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Ketik NIP / Nama..."
              className="w-full px-3 py-2 pl-9 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500"
            />
            <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        </div>
      </div>

      {/* 5. Tabel Konten Sesuai Tab */}
      {activeSubTab === 'sudah' ? (
        /* TAB: DATA RESPON MASUK */
        <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="p-4 border-b border-slate-100 flex items-center justify-between">
            <div className="text-xs font-bold text-slate-700 flex items-center gap-2">
              <FileCheck className="w-4 h-4 text-teal-600" />
              <span>Daftar Pegawai yang Telah Mengisi Formulir</span>
            </div>
            <span className="text-[11px] text-slate-500">
              Total {filteredSubmissions.length} Responden
            </span>
          </div>

          {filteredSubmissions.length === 0 ? (
            <div className="p-12 text-center text-slate-400 space-y-2">
              <FileText className="w-10 h-10 mx-auto text-slate-300" />
              <div className="text-xs font-semibold">Belum ada respon yang sesuai filter.</div>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="bg-slate-50 text-slate-600 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-200">
                    <th className="py-3 px-4 w-12 text-center">No</th>
                    <th className="py-3 px-4">Pegawai</th>
                    <th className="py-3 px-4">Unit Kerja / Jabatan</th>
                    <th className="py-3 px-4">Status Pelatihan</th>
                    <th className="py-3 px-4">Bukti / Alasan</th>
                    <th className="py-3 px-4">Waktu Submit</th>
                    <th className="py-3 px-4 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {filteredSubmissions.map((sub, idx) => {
                    const st = sub.answers?.status_pelatihan || '';
                    const isSelesai = st.includes('Sudah menyelesaikan') || st.includes('Selesai');
                    const isMulai = st.includes('Sudah mulai');
                    const fileObj = sub.answers?.bukti_sertifikat;

                    return (
                      <tr key={sub.id} className="hover:bg-slate-50/80 transition-colors">
                        <td className="py-3 px-4 text-center text-slate-400 font-mono text-[11px]">
                          {idx + 1}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-bold text-slate-900">{sub.namaPegawai}</div>
                          <div className="text-[11px] font-mono text-slate-500">
                            NIP: {sub.nip}
                          </div>
                          {sub.nomorWa && (
                            <div className="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                              <MessageCircle className="w-3 h-3 text-emerald-500" />
                              <span>{sub.nomorWa}</span>
                            </div>
                          )}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-medium text-slate-800">{sub.unitKerja}</div>
                          <div className="text-[11px] text-slate-500">{sub.jabatan}</div>
                        </td>
                        <td className="py-3 px-4">
                          <span
                            className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold ${
                              isSelesai
                                ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                : isMulai
                                ? 'bg-amber-100 text-amber-800 border border-amber-200'
                                : 'bg-rose-100 text-rose-800 border border-rose-200'
                            }`}
                          >
                            <span
                              className={`w-1.5 h-1.5 rounded-full ${
                                isSelesai ? 'bg-emerald-600' : isMulai ? 'bg-amber-600' : 'bg-rose-600'
                              }`}
                            />
                            {st || '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4 max-w-xs">
                          {isSelesai ? (
                            <div className="space-y-1">
                              {(() => {
                                const gdrive =
                                  sub.answers?.link_gdrive_sertifikat ||
                                  (typeof sub.answers?.keterangan_sertifikat === 'string' &&
                                  sub.answers.keterangan_sertifikat.startsWith('http')
                                    ? sub.answers.keterangan_sertifikat
                                    : null);
                                const noCert =
                                  sub.answers?.nomor_sertifikat ||
                                  (!gdrive ? sub.answers?.keterangan_sertifikat : null);

                                return (
                                  <>
                                    {gdrive ? (
                                      <a
                                        href={gdrive}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold text-[11px] transition-all border border-teal-200"
                                        title={gdrive}
                                      >
                                        <ExternalLink className="w-3.5 h-3.5 text-teal-600" />
                                        <span>Buka Link GDrive</span>
                                      </a>
                                    ) : fileObj ? (
                                      <button
                                        onClick={() => setPreviewFile(fileObj)}
                                        className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 font-semibold text-[11px] transition-all border border-teal-200"
                                      >
                                        <FileCheck className="w-3.5 h-3.5 text-teal-600" />
                                        <span className="truncate max-w-[120px]">{fileObj.fileName || 'Lihat Bukti'}</span>
                                      </button>
                                    ) : (
                                      <span className="text-[11px] text-slate-400">Tidak ada tautan</span>
                                    )}

                                    {noCert && (
                                      <div className="text-[10px] text-slate-500 font-mono">
                                        No: {noCert}
                                      </div>
                                    )}
                                  </>
                                );
                              })()}
                            </div>
                          ) : (
                            <div className="text-[11px] text-slate-600 leading-snug line-clamp-2">
                              {sub.answers?.alasan_belum_selesai ||
                                sub.answers?.alasan_belum_mulai ||
                                sub.answers?.catatan ||
                                (typeof sub.answers?.[currentForm?.sections[1]?.questions[0]?.id] === 'string'
                                  ? sub.answers[currentForm.sections[1].questions[0].id]
                                  : '-')}
                            </div>
                          )}
                        </td>
                        <td className="py-3 px-4 text-[11px] text-slate-500 whitespace-nowrap">
                          {new Date(sub.submittedAt).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                          })}
                        </td>
                        <td className="py-3 px-4 text-center">
                          <div className="flex items-center justify-center gap-1">
                            <button
                              onClick={() => setDetailModalResponse(sub)}
                              className="p-1.5 text-slate-400 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors"
                              title="Lihat semua rincian jawaban formulir pegawai ini"
                            >
                              <Eye className="w-3.5 h-3.5" />
                            </button>
                            <button
                              onClick={async () => {
                                if (confirm(`Hapus respon dari ${sub.namaPegawai}?`)) {
                                  await onDeleteResponse(sub.id);
                                }
                              }}
                              className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                              title="Hapus respon ini"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      ) : (
        /* TAB: PEGAWAI BELUM MENGISI (RECONCILIATION & WHATSAPP REMINDER) */
        <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div className="text-xs font-bold text-slate-800 flex items-center gap-2">
              <AlertTriangle className="w-4 h-4 text-rose-500" />
              <span>Daftar Pegawai yang Belum Mengisi ({unsubmittedEmployees.length} orang)</span>
            </div>
            <div className="text-[11px] text-slate-500">
              Klik tombol WhatsApp untuk langsung mengirim pengingat ke nomor pegawai
            </div>
          </div>

          {unsubmittedEmployees.length === 0 ? (
            <div className="p-12 text-center text-emerald-600 space-y-2">
              <CheckCircle2 className="w-10 h-10 mx-auto text-emerald-500" />
              <div className="text-sm font-bold">Luar Biasa! Semua Pegawai Sudah Mengisi</div>
              <div className="text-xs text-slate-500">
                Tidak ada pegawai aktif yang belum mengisi formulir untuk filter unit ini.
              </div>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="bg-slate-50 text-slate-600 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-200">
                    <th className="py-3 px-4 w-12 text-center">No</th>
                    <th className="py-3 px-4">Pegawai</th>
                    <th className="py-3 px-4">Unit Kerja / Jurusan</th>
                    <th className="py-3 px-4">Jabatan</th>
                    <th className="py-3 px-4">Status Form</th>
                    <th className="py-3 px-4 text-center">Kirim Reminder</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {unsubmittedEmployees.map((emp, idx) => {
                    const waUrl = generateWhatsAppReminderUrl(
                      emp.nomorWa,
                      emp.name,
                      currentForm.title,
                      getFormPublicUrl()
                    );

                    return (
                      <tr key={emp.nip} className="hover:bg-slate-50/80 transition-colors">
                        <td className="py-3 px-4 text-center text-slate-400 font-mono text-[11px]">
                          {idx + 1}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-bold text-slate-900">{emp.name}</div>
                          <div className="text-[11px] font-mono text-slate-500">
                            NIP: {emp.nip}
                          </div>
                        </td>
                        <td className="py-3 px-4 font-medium text-slate-800">
                          {emp.unitKerja}
                        </td>
                        <td className="py-3 px-4 text-slate-600">
                          {emp.jabatan}
                        </td>
                        <td className="py-3 px-4">
                          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">
                            <span className="w-1.5 h-1.5 rounded-full bg-rose-500" />
                            Belum Mengisi
                          </span>
                        </td>
                        <td className="py-3 px-4 text-center">
                          <a
                            href={waUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs transition-all"
                            title={`Kirim pesan pengingat WA ke ${emp.name}`}
                          >
                            <MessageCircle className="w-3.5 h-3.5" />
                            <span>Japri WA</span>
                          </a>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* 6. Preview Modal for Uploaded Certificates / Proofs */}
      {previewFile && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
          <div className="relative w-full max-w-lg bg-white rounded-3xl overflow-hidden shadow-2xl p-5 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center gap-2">
                <FileCheck className="w-5 h-5 text-teal-600" />
                <h3 className="text-sm font-bold text-slate-900 truncate max-w-xs">
                  {previewFile.fileName}
                </h3>
              </div>
              <button
                onClick={() => setPreviewFile(null)}
                className="p-1.5 text-slate-400 hover:text-slate-700 rounded-full hover:bg-slate-100"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="max-h-[60vh] overflow-y-auto flex items-center justify-center bg-slate-50 rounded-2xl p-2 border border-slate-200">
              {previewFile.dataUrl.startsWith('data:image/') ? (
                <img
                  src={previewFile.dataUrl}
                  alt={previewFile.fileName}
                  className="max-h-[50vh] object-contain rounded-xl shadow-xs"
                />
              ) : (
                <div className="p-8 text-center space-y-3">
                  <FileText className="w-16 h-16 text-teal-600 mx-auto" />
                  <div className="text-xs font-bold text-slate-800">{previewFile.fileName}</div>
                  <div className="text-[11px] text-slate-500">Dokumen PDF Terlampir</div>
                  <a
                    href={previewFile.dataUrl}
                    download={previewFile.fileName}
                    className="inline-flex items-center gap-1.5 px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                  >
                    <Download className="w-3.5 h-3.5" />
                    Unduh Dokumen
                  </a>
                </div>
              )}
            </div>

            <div className="flex justify-end pt-2">
              <button
                onClick={() => setPreviewFile(null)}
                className="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-all"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 7. Modal Rincian Lengkap Jawaban Pegawai */}
      {detailModalResponse && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
          <div className="relative w-full max-w-xl bg-white rounded-3xl overflow-hidden shadow-2xl my-auto border border-slate-200">
            {/* Header */}
            <div className="bg-teal-800 text-white p-5 flex items-center justify-between">
              <div>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-600/80 text-teal-100 uppercase tracking-wider">
                  Rincian Jawaban Formulir
                </span>
                <h3 className="text-base font-bold mt-1 text-white">
                  {detailModalResponse.namaPegawai}
                </h3>
                <p className="text-xs text-teal-200">
                  NIP: {detailModalResponse.nip} • {detailModalResponse.unitKerja}
                </p>
              </div>
              <button
                onClick={() => setDetailModalResponse(null)}
                className="p-1.5 text-teal-200 hover:text-white rounded-full hover:bg-teal-700/50"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Content */}
            <div className="p-5 space-y-4 max-h-[70vh] overflow-y-auto text-xs">
              <div className="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-2">
                <div className="flex justify-between py-1 border-b border-slate-200">
                  <span className="text-slate-500 font-semibold">Formulir:</span>
                  <span className="font-bold text-slate-800">{currentForm?.title}</span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-200">
                  <span className="text-slate-500 font-semibold">Jabatan:</span>
                  <span className="text-slate-800">{detailModalResponse.jabatan}</span>
                </div>
                {detailModalResponse.nomorWa && (
                  <div className="flex justify-between py-1 border-b border-slate-200">
                    <span className="text-slate-500 font-semibold">WhatsApp:</span>
                    <span className="text-emerald-700 font-bold">{detailModalResponse.nomorWa}</span>
                  </div>
                )}
                <div className="flex justify-between py-1">
                  <span className="text-slate-500 font-semibold">Waktu Pengisian:</span>
                  <span className="text-slate-700">
                    {new Date(detailModalResponse.submittedAt).toLocaleString('id-ID')}
                  </span>
                </div>
              </div>

              {/* Answers by Section */}
              <div className="space-y-3">
                <h4 className="font-bold text-slate-800 uppercase text-[11px] tracking-wider">
                  Daftar Jawaban yang Diisi:
                </h4>

                {currentForm?.sections.map((sec, secIdx) => {
                  const secQuestions = sec.questions.filter((q) => {
                    const ans = detailModalResponse.answers?.[q.id];
                    return ans !== undefined && ans !== null && ans !== '';
                  });

                  if (secQuestions.length === 0) return null;

                  return (
                    <div
                      key={sec.id}
                      className="bg-white border border-slate-200 rounded-xl p-3.5 space-y-2.5 shadow-xs"
                    >
                      <div className="font-bold text-teal-800 text-[11px] border-b border-slate-100 pb-1">
                        {sec.title || `Bagian ${secIdx + 1}`}
                      </div>

                      <div className="space-y-2 pl-2">
                        {secQuestions.map((q) => {
                          const val = detailModalResponse.answers?.[q.id];
                          const isUrl = typeof val === 'string' && val.startsWith('http');

                          return (
                            <div key={q.id} className="space-y-0.5">
                              <span className="text-[11px] font-semibold text-slate-500 block">
                                {q.title}
                              </span>
                              {isUrl ? (
                                <a
                                  href={val}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold rounded-lg border border-teal-200 text-xs mt-0.5"
                                >
                                  <ExternalLink className="w-3.5 h-3.5 text-teal-600" />
                                  <span>Buka Tautan Google Drive</span>
                                </a>
                              ) : typeof val === 'object' && val?.dataUrl ? (
                                <button
                                  type="button"
                                  onClick={() => setPreviewFile(val)}
                                  className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold rounded-lg border border-teal-200 text-xs mt-0.5"
                                >
                                  <FileCheck className="w-3.5 h-3.5 text-teal-600" />
                                  <span>Lihat Lampiran</span>
                                </button>
                              ) : (
                                <p className="font-bold text-slate-800 text-xs whitespace-pre-wrap">
                                  {String(val)}
                                </p>
                              )}
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Footer */}
            <div className="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
              <button
                type="button"
                onClick={() => setDetailModalResponse(null)}
                className="px-5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs transition-all"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Konfigurasi Formulir Pegawai (Edit Banner & Struktur) */}
      <PegawaiFormConfigModal
        isOpen={isConfigModalOpen}
        onClose={() => {
          setIsConfigModalOpen(false);
          setConfigTargetForm(null);
        }}
        form={configTargetForm}
        onSave={async (updated) => {
          if (onUpdateForm) {
            await onUpdateForm(updated);
          }
          setIsConfigModalOpen(false);
          setConfigTargetForm(null);
          setSaveSuccessToast(true);
          setTimeout(() => setSaveSuccessToast(false), 3000);
        }}
        onDelete={(id) => {
          if (onDeleteForm) {
            onDeleteForm(id);
          }
          setIsConfigModalOpen(false);
          setConfigTargetForm(null);
        }}
        canDelete={forms.length > 1}
      />
    </div>
  );
};

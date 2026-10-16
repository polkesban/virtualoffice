import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  X,
  Plus,
  Trash2,
  Layers,
  FileText,
  Check,
  CheckCircle2,
  AlertCircle,
  HelpCircle,
  ArrowRight,
  GitBranch,
  Settings2,
  FolderPlus,
  ListPlus,
  Sparkles,
  Link2,
  AlignLeft,
  CheckSquare,
  ChevronDown,
  Upload,
  RotateCcw,
  Image as ImageIcon,
  Save,
  Sliders,
  Users
} from 'lucide-react';
import { PegawaiForm, FormSection, FormQuestion, FormQuestionType, FormOption, TargetKepegawaian } from '../types';
import { optimizeImageForStorage } from '../utils/imageOptimizer';
import bannerMoocAsn from '../assets/banner-mooc-asn.png';

interface PegawaiFormConfigModalProps {
  isOpen: boolean;
  onClose: () => void;
  form: PegawaiForm | null;
  onSave: (updatedForm: PegawaiForm) => Promise<boolean> | void;
  onDelete?: (formId: string) => void;
  canDelete?: boolean;
}

export const PegawaiFormConfigModal: React.FC<PegawaiFormConfigModalProps> = ({
  isOpen,
  onClose,
  form,
  onSave,
  onDelete,
  canDelete = false,
}) => {
  // Tabs: 'info' (Identitas & Banner) | 'builder' (Desain Struktur & Pertanyaan)
  const [activeTab, setActiveTab] = useState<'info' | 'builder'>('info');

  // Form Basic Info
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState('Pelatihan & Pengembangan');
  const [isActive, setIsActive] = useState(true);
  const [targetKepegawaian, setTargetKepegawaian] = useState<TargetKepegawaian>('ALL');
  const [bannerUrl, setBannerUrl] = useState('');
  const [isDraggingBanner, setIsDraggingBanner] = useState(false);
  const bannerInputRef = useRef<HTMLInputElement>(null);

  // Form Sections & Questions State
  const [sections, setSections] = useState<FormSection[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Sync state when modal opens or form changes
  useEffect(() => {
    if (form && isOpen) {
      setTitle(form.title || '');
      setDescription(form.description || '');
      setCategory(form.category || 'Pelatihan & Pengembangan');
      setIsActive(form.isActive !== false);
      setTargetKepegawaian(form.targetKepegawaian || 'ALL');
      setBannerUrl(form.bannerUrl || '');
      setSections(form.sections && form.sections.length > 0 ? form.sections : [
        {
          id: `sec-${Date.now()}`,
          title: 'Bagian 1',
          description: '',
          questions: [
            {
              id: `q-${Date.now()}-1`,
              title: 'Pertanyaan',
              type: 'text',
              required: true,
            }
          ]
        }
      ]);
      setError(null);
      setIsSubmitting(false);
      setActiveTab('info');
    }
  }, [form, isOpen]);

  if (!isOpen || !form) return null;

  // Banner Upload Handler (Compacts to 4:1 ratio, 1200x300px)
  const handleBannerUpload = async (file: File) => {
    if (!file.type.startsWith('image/')) {
      alert('Format file harus berupa gambar (PNG, JPG, JPEG, WebP).');
      return;
    }
    try {
      const optimized = await optimizeImageForStorage(file, 1200, 300, 0.85);
      setBannerUrl(optimized);
    } catch (err) {
      console.warn('Gagal mengoptimasi gambar banner:', err);
      const reader = new FileReader();
      reader.onload = (e) => {
        if (e.target?.result) setBannerUrl(e.target.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  // Section Management Handlers
  const handleAddSection = () => {
    const newSecId = `sec-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`;
    const newSection: FormSection = {
      id: newSecId,
      title: `Bagian ${sections.length + 1}`,
      description: '',
      questions: [
        {
          id: `q-${Date.now()}-1`,
          title: 'Pertanyaan Baru',
          type: 'text',
          required: true,
          placeholder: 'Masukkan jawaban Anda...',
        },
      ],
    };
    setSections((prev) => [...prev, newSection]);
  };

  const handleRemoveSection = (secIndex: number) => {
    if (sections.length <= 1) {
      alert('Formulir harus memiliki minimal 1 bagian.');
      return;
    }
    const secToRemove = sections[secIndex];
    if (
      confirm(
        `Hapus "${secToRemove.title || `Bagian ${secIndex + 1}`}" beserta seluruh pertanyaan di dalamnya?`
      )
    ) {
      setSections((prev) => prev.filter((_, idx) => idx !== secIndex));
    }
  };

  const handleUpdateSectionTitle = (secIndex: number, val: string) => {
    setSections((prev) => {
      const copy = [...prev];
      copy[secIndex] = { ...copy[secIndex], title: val };
      return copy;
    });
  };

  const handleUpdateSectionDesc = (secIndex: number, val: string) => {
    setSections((prev) => {
      const copy = [...prev];
      copy[secIndex] = { ...copy[secIndex], description: val };
      return copy;
    });
  };

  // Question Management Handlers
  const handleAddQuestion = (secIndex: number) => {
    const newQId = `q-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`;
    const newQ: FormQuestion = {
      id: newQId,
      title: '',
      type: 'text',
      required: true,
      placeholder: '',
    };

    setSections((prev) => {
      const copy = [...prev];
      const targetSec = { ...copy[secIndex] };
      targetSec.questions = [...targetSec.questions, newQ];
      copy[secIndex] = targetSec;
      return copy;
    });
  };

  const handleRemoveQuestion = (secIndex: number, qIndex: number) => {
    setSections((prev) => {
      const copy = [...prev];
      const targetSec = { ...copy[secIndex] };
      if (targetSec.questions.length <= 1) {
        alert('Setiap bagian harus memiliki minimal 1 pertanyaan.');
        return prev;
      }
      targetSec.questions = targetSec.questions.filter((_, idx) => idx !== qIndex);
      copy[secIndex] = targetSec;
      return copy;
    });
  };

  const handleUpdateQuestion = (
    secIndex: number,
    qIndex: number,
    updatedFields: Partial<FormQuestion>
  ) => {
    setSections((prev) => {
      const copy = [...prev];
      const targetSec = { ...copy[secIndex] };
      const targetQ = { ...targetSec.questions[qIndex], ...updatedFields };

      if (
        (targetQ.type === 'dropdown' || targetQ.type === 'radio') &&
        (!targetQ.options || targetQ.options.length === 0)
      ) {
        targetQ.options = [
          { id: `opt-${Date.now()}-1`, label: 'Pilihan 1' },
          { id: `opt-${Date.now()}-2`, label: 'Pilihan 2' },
        ];
      }

      targetSec.questions = [...targetSec.questions];
      targetSec.questions[qIndex] = targetQ;
      copy[secIndex] = targetSec;
      return copy;
    });
  };

  // Option & Branching Handlers
  const handleAddOption = (secIndex: number, qIndex: number) => {
    setSections((prev) => {
      const copy = [...prev];
      const targetSec = { ...copy[secIndex] };
      const targetQ = { ...targetSec.questions[qIndex] };
      const currentOpts = targetQ.options || [];

      targetQ.options = [
        ...currentOpts,
        {
          id: `opt-${Date.now()}-${currentOpts.length + 1}`,
          label: `Pilihan ${currentOpts.length + 1}`,
        },
      ];

      targetSec.questions = [...targetSec.questions];
      targetSec.questions[qIndex] = targetQ;
      copy[secIndex] = targetSec;
      return copy;
    });
  };

  const handleRemoveOption = (secIndex: number, qIndex: number, optIndex: number) => {
    setSections((prev) => {
      const copy = [...prev];
      const targetSec = { ...copy[secIndex] };
      const targetQ = { ...targetSec.questions[qIndex] };
      const currentOpts = targetQ.options || [];

      if (currentOpts.length <= 1) {
        alert('Minimal harus ada 1 opsi pilihan.');
        return prev;
      }

      targetQ.options = currentOpts.filter((_, idx) => idx !== optIndex);
      targetSec.questions = [...targetSec.questions];
      targetSec.questions[qIndex] = targetQ;
      copy[secIndex] = targetSec;
      return copy;
    });
  };

  const handleUpdateOption = (
    secIndex: number,
    qIndex: number,
    optIndex: number,
    updatedOpt: Partial<FormOption>
  ) => {
    setSections((prev) => {
      const copy = [...prev];
      const targetSec = { ...copy[secIndex] };
      const targetQ = { ...targetSec.questions[qIndex] };
      const currentOpts = [...(targetQ.options || [])];

      currentOpts[optIndex] = { ...currentOpts[optIndex], ...updatedOpt };
      targetQ.options = currentOpts;

      targetSec.questions = [...targetSec.questions];
      targetSec.questions[qIndex] = targetQ;
      copy[secIndex] = targetSec;
      return copy;
    });
  };

  const totalQuestionsCount = sections.reduce(
    (acc, sec) => acc + (sec.questions?.length || 0),
    0
  );

  // Form Submit Handler
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!title.trim()) {
      setError('Judul formulir wajib diisi.');
      setActiveTab('info');
      return;
    }

    if (sections.length === 0) {
      setError('Formulir harus memiliki minimal 1 bagian.');
      setActiveTab('builder');
      return;
    }

    // Validate questions have titles
    for (let sIdx = 0; sIdx < sections.length; sIdx++) {
      const sec = sections[sIdx];
      for (let qIdx = 0; qIdx < sec.questions.length; qIdx++) {
        const q = sec.questions[qIdx];
        if (!q.title.trim()) {
          setError(
            `Judul pertanyaan ke-${qIdx + 1} di "${sec.title || `Bagian ${sIdx + 1}`}" belum diisi.`
          );
          setActiveTab('builder');
          return;
        }
      }
    }

    setIsSubmitting(true);

    const updatedForm: PegawaiForm = {
      ...form,
      title: title.trim(),
      description: description.trim(),
      category: category.trim(),
      isActive: isActive,
      targetKepegawaian: targetKepegawaian,
      bannerUrl: bannerUrl.trim() || undefined,
      sections: sections,
      updatedAt: new Date().toISOString(),
    };

    try {
      const ok = await onSave(updatedForm);
      if (ok !== false) {
        onClose();
      } else {
        setError('Gagal menyimpan perubahan formulir.');
      }
    } catch (err: any) {
      setError(err?.message || 'Terjadi kesalahan sistem.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = () => {
    if (!onDelete || !form) return;
    if (
      window.confirm(
        `Apakah Anda yakin ingin menghapus formulir:\n"${form.title}"?\n\nSemua data respon yang telah masuk untuk formulir ini juga akan terhapus.`
      )
    ) {
      onDelete(form.id);
      onClose();
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto">
      <motion.div
        initial={{ opacity: 0, scale: 0.96, y: 10 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.96, y: 10 }}
        className="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200 my-auto flex flex-col max-h-[92vh]"
      >
        {/* Modal Header */}
        <div className="bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-900 text-white p-5 flex items-center justify-between shrink-0 shadow-sm relative overflow-hidden">
          <div className="absolute -top-10 -right-10 w-36 h-36 bg-teal-400/20 rounded-full blur-xl pointer-events-none" />

          <div className="flex items-center gap-3 relative z-10">
            <div className="p-2.5 bg-teal-700/80 rounded-2xl shadow-inner border border-teal-600/50">
              <Sliders className="w-5 h-5 text-teal-100" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-sm sm:text-base font-black tracking-tight">
                  Konfigurasi Formulir Pegawai
                </h3>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-500/30 text-teal-100 border border-teal-400/30">
                  ID: {form.id}
                </span>
              </div>
              <p className="text-xs text-teal-100/90 mt-0.5">
                Sesuaikan gambar banner (rasio ~5:1), identitas, serta struktur bagian & pertanyaan formulir
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="p-1.5 sm:p-2 text-teal-200 hover:text-white bg-white/10 hover:bg-white/20 active:bg-white/30 rounded-full transition-all shrink-0 cursor-pointer shadow-xs relative z-10"
            aria-label="Tutup"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Tab Navigation */}
        <div className="flex items-center border-b border-slate-200 bg-slate-50 px-5 pt-2.5 gap-2 shrink-0">
          <button
            type="button"
            onClick={() => setActiveTab('info')}
            className={`px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-t border-x flex items-center gap-2 ${
              activeTab === 'info'
                ? 'bg-white border-slate-200 text-teal-900 border-b-2 border-b-white -mb-[1px] shadow-xs'
                : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/70'
            }`}
          >
            <ImageIcon className="w-4 h-4 text-teal-600" />
            <span>1. Identitas & Banner Header</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('builder')}
            className={`px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-t border-x flex items-center gap-2 ${
              activeTab === 'builder'
                ? 'bg-white border-slate-200 text-teal-900 border-b-2 border-b-white -mb-[1px] shadow-xs'
                : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/70'
            }`}
          >
            <GitBranch className="w-4 h-4 text-teal-600" />
            <span>2. Desain Struktur & Pertanyaan</span>
            <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">
              {sections.length} Bagian • {totalQuestionsCount} Soal
            </span>
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="flex flex-col flex-1 overflow-hidden min-h-0">
          <div className="flex-1 overflow-y-auto p-5 sm:p-7 space-y-6">
            {error && (
              <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5 animate-shake">
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-500" />
                <span className="font-semibold leading-relaxed">{error}</span>
              </div>
            )}

            {/* TAB 1: IDENTITAS & BANNER HEADER */}
            {activeTab === 'info' && (
              <div className="space-y-6">
                <div className="bg-slate-50/80 p-5 rounded-2xl border border-slate-200 space-y-4">
                  <h4 className="font-black text-slate-800 text-xs sm:text-sm flex items-center gap-1.5">
                    <FileText className="w-4 h-4 text-teal-600" />
                    <span>Informasi Pokok Formulir</span>
                  </h4>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div className="sm:col-span-2">
                      <label className="block font-bold text-slate-700 mb-1">
                        Judul Formulir <span className="text-rose-500">*</span>
                      </label>
                      <input
                        type="text"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        placeholder="Contoh: Konfirmasi Pelatihan MOOC ASN Berintegritas"
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs"
                        required
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-700 mb-1">Kategori Formulir</label>
                      <input
                        type="text"
                        value={category}
                        onChange={(e) => setCategory(e.target.value)}
                        placeholder="Contoh: Pelatihan & Pengembangan"
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-700 mb-1">
                        Status Ketersediaan Formulir
                      </label>
                      <button
                        type="button"
                        onClick={() => setIsActive(!isActive)}
                        className={`w-full px-3.5 py-2.5 rounded-xl border text-xs font-bold transition-all flex items-center justify-between cursor-pointer ${
                          isActive
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                            : 'bg-slate-100 border-slate-300 text-slate-600'
                        }`}
                      >
                        <span className="flex items-center gap-2">
                          <span
                            className={`w-2.5 h-2.5 rounded-full ${
                              isActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'
                            }`}
                          />
                          <span>{isActive ? 'Aktif (Bisa Diisi Pegawai)' : 'Nonaktif (Ditutup Sementara)'}</span>
                        </span>
                        <span className="text-[10px] underline">{isActive ? 'Klik Nonaktifkan' : 'Klik Aktifkan'}</span>
                      </button>
                    </div>

                    <div className="sm:col-span-2">
                      <label className="block font-bold text-slate-700 mb-1">
                        Deskripsi / Petunjuk Pengisian Formulir
                      </label>
                      <textarea
                        rows={3}
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        placeholder="Jelaskan tujuan dan instruksi pengisian formulir untuk pegawai..."
                        className="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs"
                      />
                    </div>

                    {/* Target Sasaran Pegawai Pengisi Formulir */}
                    <div className="sm:col-span-2 space-y-2 pt-2 border-t border-slate-200">
                      <div className="flex items-center justify-between">
                        <label className="block font-bold text-slate-700 text-xs sm:text-sm flex items-center gap-1.5">
                          <Users className="w-4 h-4 text-teal-600" />
                          <span>Target Sasaran Pegawai Pengisi Formulir</span>
                        </label>
                        <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full border ${
                          targetKepegawaian === 'ASN'
                            ? 'bg-blue-50 text-blue-700 border-blue-200'
                            : targetKepegawaian === 'Non ASN'
                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : 'bg-teal-50 text-teal-700 border-teal-200'
                        }`}>
                          {targetKepegawaian === 'ASN'
                            ? 'Khusus Pegawai ASN'
                            : targetKepegawaian === 'Non ASN'
                            ? 'Khusus Pegawai Non-ASN'
                            : 'Seluruh Pegawai'}
                        </span>
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        {/* Pilihan 1: Semua Pegawai */}
                        <button
                          type="button"
                          onClick={() => setTargetKepegawaian('ALL')}
                          className={`p-3 rounded-xl border text-left transition-all cursor-pointer flex flex-col justify-between ${
                            targetKepegawaian === 'ALL'
                              ? 'bg-teal-50/90 border-teal-500 ring-2 ring-teal-500/20 shadow-xs'
                              : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-1.5">
                            <span className="font-extrabold text-xs text-slate-800 flex items-center gap-1.5">
                              <span>👥 Semua Pegawai</span>
                            </span>
                            {targetKepegawaian === 'ALL' && (
                              <CheckCircle2 className="w-4 h-4 text-teal-600 shrink-0" />
                            )}
                          </div>
                          <p className="text-[11px] text-slate-500 leading-snug">
                            Bisa diisi oleh seluruh pegawai Poltekkes Bandung (ASN & Non-ASN).
                          </p>
                        </button>

                        {/* Pilihan 2: Khusus ASN */}
                        <button
                          type="button"
                          onClick={() => setTargetKepegawaian('ASN')}
                          className={`p-3 rounded-xl border text-left transition-all cursor-pointer flex flex-col justify-between ${
                            targetKepegawaian === 'ASN'
                              ? 'bg-blue-50/90 border-blue-500 ring-2 ring-blue-500/20 shadow-xs'
                              : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-1.5">
                            <span className="font-extrabold text-xs text-blue-900 flex items-center gap-1.5">
                              <span>🏛️ Khusus ASN</span>
                            </span>
                            {targetKepegawaian === 'ASN' && (
                              <CheckCircle2 className="w-4 h-4 text-blue-600 shrink-0" />
                            )}
                          </div>
                          <p className="text-[11px] text-slate-500 leading-snug">
                            Hanya untuk PNS / PPPK. Pegawai Non-ASN <strong>tidak dapat</strong> mengisi.
                          </p>
                        </button>

                        {/* Pilihan 3: Khusus Non-ASN */}
                        <button
                          type="button"
                          onClick={() => setTargetKepegawaian('Non ASN')}
                          className={`p-3 rounded-xl border text-left transition-all cursor-pointer flex flex-col justify-between ${
                            targetKepegawaian === 'Non ASN'
                              ? 'bg-amber-50/90 border-amber-500 ring-2 ring-amber-500/20 shadow-xs'
                              : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-1.5">
                            <span className="font-extrabold text-xs text-amber-900 flex items-center gap-1.5">
                              <span>📋 Khusus Non-ASN</span>
                            </span>
                            {targetKepegawaian === 'Non ASN' && (
                              <CheckCircle2 className="w-4 h-4 text-amber-600 shrink-0" />
                            )}
                          </div>
                          <p className="text-[11px] text-slate-500 leading-snug">
                            Hanya untuk Pegawai Non-ASN. Pegawai ASN <strong>tidak dapat</strong> mengisi.
                          </p>
                        </button>
                      </div>
                    </div>

                    {/* Banner Header Formulir (Rasio ~4:1) */}
                    <div className="sm:col-span-2 space-y-2 pt-2 border-t border-slate-200">
                      <div className="flex items-center justify-between">
                        <label className="block font-bold text-slate-700 text-xs sm:text-sm flex items-center gap-1.5">
                          <ImageIcon className="w-4 h-4 text-teal-600" />
                          <span>Gambar Banner Header Formulir (Rasio ~4:1)</span>
                        </label>
                        <span className="text-[10px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">
                          Rasio 4:1 (Ideal & Tidak Terpotong)
                        </span>
                      </div>
                      <p className="text-[11px] text-slate-500 leading-relaxed">
                        Unggah gambar banner dengan rasio sekitar <strong>4:1</strong> (rekomendasi: <strong>1200 x 300 px</strong> atau <strong>1024 x 256 px</strong>, format PNG/JPG/WebP) agar banner tampil utuh tanpa terpotong dan header formulir tampil bersih tanpa teks narasi bertumpuk.
                      </p>

                      {bannerUrl ? (
                        /* Live Preview Banner 4:1 */
                        <div className="space-y-2">
                          <div className="relative w-full aspect-[4/1] rounded-2xl overflow-hidden border-2 border-teal-500/50 shadow-sm bg-slate-100 group">
                            <img
                              src={bannerUrl}
                              alt="Preview Banner Formulir"
                              className="w-full h-full object-cover object-center"
                            />
                            <div className="absolute top-2 left-2 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md border border-white/20">
                              Pratinjau Banner Rasio 4:1
                            </div>
                            <div className="absolute top-2 right-2 flex items-center gap-1.5 opacity-90 group-hover:opacity-100 transition-opacity">
                              <button
                                type="button"
                                onClick={() => bannerInputRef.current?.click()}
                                className="px-2.5 py-1 bg-white/90 hover:bg-white text-slate-700 text-[11px] font-bold rounded-lg shadow-sm backdrop-blur-xs transition-all border border-slate-200 cursor-pointer"
                              >
                                Ganti Banner
                              </button>
                              <button
                                type="button"
                                onClick={() => setBannerUrl('')}
                                className="p-1 bg-rose-500 hover:bg-rose-600 text-white rounded-lg shadow-sm transition-all cursor-pointer"
                                title="Hapus banner (gunakan default)"
                              >
                                <X className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </div>
                          <div className="flex items-center justify-between text-[11px] text-slate-500">
                            <span className="text-emerald-600 font-semibold flex items-center gap-1">
                              <CheckCircle2 className="w-3.5 h-3.5" /> Banner kustom aktif
                            </span>
                            <button
                              type="button"
                              onClick={() => setBannerUrl('')}
                              className="text-rose-600 hover:text-rose-700 font-semibold cursor-pointer"
                            >
                              Hapus & Gunakan Banner Default
                            </button>
                          </div>
                        </div>
                      ) : (
                        /* Upload Box Drag & Drop */
                        <div
                          onClick={() => bannerInputRef.current?.click()}
                          onDragOver={(e) => {
                            e.preventDefault();
                            setIsDraggingBanner(true);
                          }}
                          onDragLeave={() => setIsDraggingBanner(false)}
                          onDrop={(e) => {
                            e.preventDefault();
                            setIsDraggingBanner(false);
                            const file = e.dataTransfer.files?.[0];
                            if (file) handleBannerUpload(file);
                          }}
                          className={`w-full aspect-[4/1] min-h-[90px] rounded-2xl border-2 border-dashed transition-all flex flex-col items-center justify-center p-3 cursor-pointer text-center relative ${
                            isDraggingBanner
                              ? 'border-teal-500 bg-teal-50/70 scale-[0.99]'
                              : 'border-slate-300 hover:border-teal-500 bg-slate-50/60 hover:bg-teal-50/30'
                          }`}
                        >
                          <div className="flex items-center gap-2 text-teal-700 font-bold text-xs sm:text-sm">
                            <Upload className="w-4 h-4 text-teal-600" />
                            <span>Klik atau Seret File Gambar Banner Baru ke Sini</span>
                          </div>
                          <p className="text-[10px] text-slate-400 mt-1">
                            PNG, JPG, atau WebP (Rasio ideal 4:1, misal 1200 x 300 px atau 1024 x 256 px, otomatis dioptimasi)
                          </p>
                          <span className="mt-1 text-[10px] font-semibold text-slate-500">
                            (Opsional: Kosongkan jika ingin menggunakan banner default sistem)
                          </span>
                        </div>
                      )}

                      <input
                        ref={bannerInputRef}
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        className="hidden"
                        onChange={(e) => {
                          const file = e.target.files?.[0];
                          if (file) handleBannerUpload(file);
                        }}
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* TAB 2: DESAIN STRUKTUR & PERTANYAAN */}
            {activeTab === 'builder' && (
              <div className="space-y-6">
                <div className="flex items-center justify-between bg-teal-50/60 p-4 rounded-2xl border border-teal-200/80">
                  <div>
                    <h4 className="font-black text-teal-950 text-xs sm:text-sm flex items-center gap-1.5">
                      <Layers className="w-4 h-4 text-teal-700" />
                      <span>Struktur Bagian Formulir</span>
                    </h4>
                    <p className="text-[11px] text-teal-800/80 mt-0.5">
                      Bagian 1 adalah bagian utama. Anda dapat menambahkan percabangan logika (*conditional branching*) pada pertanyaan dropdown/radio di Bagian 1 untuk melompat ke bagian lain.
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={handleAddSection}
                    className="px-3.5 py-2 bg-teal-700 hover:bg-teal-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 shrink-0 cursor-pointer"
                  >
                    <FolderPlus className="w-3.5 h-3.5" />
                    <span>Tambah Bagian</span>
                  </button>
                </div>

                {/* Section Accordions / Cards */}
                <div className="space-y-5">
                  {sections.map((sec, secIdx) => (
                    <div
                      key={sec.id}
                      className="bg-white rounded-2xl border-2 border-slate-200 overflow-hidden shadow-xs space-y-4 p-4 sm:p-5"
                    >
                      {/* Section Header */}
                      <div className="flex items-center justify-between pb-3 border-b border-slate-100 gap-2">
                        <div className="flex items-center gap-2 flex-1">
                          <span className="w-6 h-6 rounded-lg bg-teal-100 text-teal-800 font-black text-xs flex items-center justify-center shrink-0">
                            {secIdx + 1}
                          </span>
                          <input
                            type="text"
                            value={sec.title}
                            onChange={(e) => handleUpdateSectionTitle(secIdx, e.target.value)}
                            placeholder={`Judul Bagian ${secIdx + 1}...`}
                            className="font-bold text-xs sm:text-sm text-slate-800 bg-transparent hover:bg-slate-50 px-2 py-1 rounded-lg border border-transparent hover:border-slate-200 focus:border-teal-500 focus:bg-white focus:outline-none flex-1"
                          />
                        </div>

                        <div className="flex items-center gap-2">
                          <span className="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">
                            ID: {sec.id}
                          </span>
                          {sections.length > 1 && (
                            <button
                              type="button"
                              onClick={() => handleRemoveSection(secIdx)}
                              className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                              title="Hapus Bagian Ini"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          )}
                        </div>
                      </div>

                      {/* Section Description */}
                      <div>
                        <input
                          type="text"
                          value={sec.description || ''}
                          onChange={(e) => handleUpdateSectionDesc(secIdx, e.target.value)}
                          placeholder="Deskripsi singkat bagian ini (opsional)..."
                          className="w-full text-xs text-slate-600 bg-slate-50/50 px-3 py-1.5 rounded-lg border border-slate-200 focus:border-teal-500 focus:bg-white focus:outline-none"
                        />
                      </div>

                      {/* Questions List */}
                      <div className="space-y-3 pt-2">
                        <div className="flex items-center justify-between text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                          <span>Daftar Pertanyaan di Bagian Ini</span>
                          <button
                            type="button"
                            onClick={() => handleAddQuestion(secIdx)}
                            className="text-teal-700 hover:text-teal-800 flex items-center gap-1 font-bold lowercase text-xs cursor-pointer"
                          >
                            <Plus className="w-3.5 h-3.5" />
                            <span>tambah pertanyaan</span>
                          </button>
                        </div>

                        <div className="space-y-3">
                          {sec.questions.map((q, qIdx) => (
                            <div
                              key={q.id}
                              className="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-slate-50/60 space-y-3 transition-all hover:border-slate-300"
                            >
                              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                <div className="flex items-center gap-2 flex-1">
                                  <span className="text-xs font-bold text-slate-400 w-5">
                                    {qIdx + 1}.
                                  </span>
                                  <input
                                    type="text"
                                    value={q.title}
                                    onChange={(e) =>
                                      handleUpdateQuestion(secIdx, qIdx, { title: e.target.value })
                                    }
                                    placeholder="Tuliskan teks pertanyaan di sini..."
                                    className="font-bold text-xs sm:text-sm text-slate-900 bg-white px-3 py-1.5 rounded-lg border border-slate-200 focus:border-teal-500 focus:outline-none flex-1 shadow-2xs"
                                    required
                                  />
                                </div>

                                <div className="flex items-center gap-2 self-end sm:self-auto">
                                  {/* Question Type Selector */}
                                  <select
                                    value={q.type}
                                    onChange={(e) =>
                                      handleUpdateQuestion(secIdx, qIdx, {
                                        type: e.target.value as FormQuestionType,
                                      })
                                    }
                                    className="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:outline-none focus:border-teal-500 cursor-pointer shadow-2xs"
                                  >
                                    <option value="dropdown">Dropdown (Pilihan Tunggal)</option>
                                    <option value="radio">Radio Button</option>
                                    <option value="text">Teks Singkat (Input)</option>
                                    <option value="textarea">Teks Panjang (Paragraf)</option>
                                    <option value="link">Tautan / Link Google Drive</option>
                                    <option value="file">Unggah Berkas (Upload File)</option>
                                  </select>

                                  {/* Required Toggle */}
                                  <label className="flex items-center gap-1.5 text-xs font-semibold text-slate-600 bg-white px-2 py-1.5 rounded-lg border border-slate-200 cursor-pointer shadow-2xs">
                                    <input
                                      type="checkbox"
                                      checked={q.required}
                                      onChange={(e) =>
                                        handleUpdateQuestion(secIdx, qIdx, {
                                          required: e.target.checked,
                                        })
                                      }
                                      className="rounded text-teal-600 focus:ring-teal-500"
                                    />
                                    <span>Wajib</span>
                                  </label>

                                  {sec.questions.length > 1 && (
                                    <button
                                      type="button"
                                      onClick={() => handleRemoveQuestion(secIdx, qIdx)}
                                      className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                      title="Hapus Pertanyaan"
                                    >
                                      <Trash2 className="w-3.5 h-3.5" />
                                    </button>
                                  )}
                                </div>
                              </div>

                              {/* Description & Placeholder inputs */}
                              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <input
                                  type="text"
                                  value={q.description || ''}
                                  onChange={(e) =>
                                    handleUpdateQuestion(secIdx, qIdx, {
                                      description: e.target.value,
                                    })
                                  }
                                  placeholder="Keterangan / instruksi tambahan (opsional)..."
                                  className="w-full px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-600 focus:outline-none focus:border-teal-500"
                                />
                                <input
                                  type="text"
                                  value={q.placeholder || ''}
                                  onChange={(e) =>
                                    handleUpdateQuestion(secIdx, qIdx, {
                                      placeholder: e.target.value,
                                    })
                                  }
                                  placeholder="Placeholder teks / contoh isian..."
                                  className="w-full px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-600 focus:outline-none focus:border-teal-500"
                                />
                              </div>

                              {/* Options Editor (For Dropdown & Radio) */}
                              {(q.type === 'dropdown' || q.type === 'radio') && (
                                <div className="p-3 bg-white rounded-xl border border-slate-200 space-y-2.5 mt-2">
                                  <div className="flex items-center justify-between text-[11px] font-bold text-slate-600">
                                    <span className="flex items-center gap-1">
                                      <ListPlus className="w-3.5 h-3.5 text-teal-600" />
                                      <span>Opsi Pilihan & Aksi Percabangan Bagian</span>
                                    </span>
                                    <button
                                      type="button"
                                      onClick={() => handleAddOption(secIdx, qIdx)}
                                      className="text-teal-700 hover:text-teal-800 font-bold flex items-center gap-1 cursor-pointer"
                                    >
                                      <Plus className="w-3 h-3" />
                                      <span>Tambah Opsi</span>
                                    </button>
                                  </div>

                                  <div className="space-y-1.5">
                                    {q.options?.map((opt, optIdx) => (
                                      <div
                                        key={opt.id}
                                        className="flex items-center gap-2 text-xs bg-slate-50 p-2 rounded-lg border border-slate-200"
                                      >
                                        <span className="text-slate-400 font-mono text-[10px] w-4">
                                          {optIdx + 1}.
                                        </span>
                                        <input
                                          type="text"
                                          value={opt.label}
                                          onChange={(e) =>
                                            handleUpdateOption(secIdx, qIdx, optIdx, {
                                              label: e.target.value,
                                            })
                                          }
                                          placeholder={`Pilihan ${optIdx + 1}`}
                                          className="flex-1 bg-white px-2.5 py-1 rounded border border-slate-200 font-medium text-slate-800 focus:outline-none focus:border-teal-500"
                                        />

                                        {/* Branching target selection */}
                                        <div className="flex items-center gap-1.5 shrink-0">
                                          <GitBranch className="w-3 h-3 text-teal-600" />
                                          <select
                                            value={opt.nextSectionId || ''}
                                            onChange={(e) =>
                                              handleUpdateOption(secIdx, qIdx, optIdx, {
                                                nextSectionId: e.target.value || undefined,
                                              })
                                            }
                                            className="px-2 py-1 bg-white border border-slate-200 rounded text-[11px] text-slate-700 font-medium focus:outline-none focus:border-teal-500 cursor-pointer"
                                            title="Lompat ke bagian ini jika opsi ini dipilih"
                                          >
                                            <option value="">Lanjut Normal (Bagian Berikutnya)</option>
                                            {sections
                                              .filter((_, idx) => idx !== secIdx)
                                              .map((targetSec) => (
                                                <option key={targetSec.id} value={targetSec.id}>
                                                  Lompat ke: {targetSec.title || targetSec.id}
                                                </option>
                                              ))}
                                          </select>
                                        </div>

                                        {(q.options?.length || 0) > 1 && (
                                          <button
                                            type="button"
                                            onClick={() =>
                                              handleRemoveOption(secIdx, qIdx, optIdx)
                                            }
                                            className="p-1 text-slate-400 hover:text-rose-600 rounded transition-colors cursor-pointer"
                                            title="Hapus opsi ini"
                                          >
                                            <X className="w-3.5 h-3.5" />
                                          </button>
                                        )}
                                      </div>
                                    ))}
                                  </div>
                                </div>
                              )}
                            </div>
                          ))}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Modal Footer Controls */}
          <div className="p-4 sm:p-5 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
            <div>
              {canDelete && onDelete && (
                <button
                  type="button"
                  onClick={handleDelete}
                  className="px-3.5 py-2 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
                >
                  <Trash2 className="w-3.5 h-3.5 text-rose-600" />
                  <span>Hapus Formulir</span>
                </button>
              )}
            </div>

            <div className="flex items-center gap-2.5">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-200/80 transition-all cursor-pointer"
              >
                Batal
              </button>

              <button
                type="submit"
                disabled={isSubmitting}
                className="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 active:scale-95 shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <Save className="w-4 h-4" />
                <span>{isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan Formulir'}</span>
              </button>
            </div>
          </div>
        </form>
      </motion.div>
    </div>
  );
};

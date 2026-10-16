import React, { useState, useRef } from 'react';
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
  Image as ImageIcon,
  Users
} from 'lucide-react';
import { PegawaiForm, FormSection, FormQuestion, FormQuestionType, FormOption, TargetKepegawaian } from '../types';
import { optimizeImageForStorage } from '../utils/imageOptimizer';

interface PegawaiFormCreateModalProps {
  isOpen: boolean;
  onClose: () => void;
  onCreate: (newForm: PegawaiForm) => Promise<boolean>;
}

// Preset Templates
const PRESET_TEMPLATES = [
  {
    id: 'pelatihan_bercabang',
    name: 'Pelatihan dengan Percabangan (MOOC / Bimtek)',
    badge: 'Logika Bercabang',
    desc: 'Memiliki alur cabang otomatis: Selesai → Tautan Google Drive Sertifikat; Belum Selesai/Mulai → Alasan kendala.',
    category: 'Pelatihan & Pengembangan',
    createSections: (): FormSection[] => [
      {
        id: 'sec-status-utama',
        title: 'Status Pelatihan Anda',
        description: 'Pilih status keikutsertaan Anda saat ini.',
        questions: [
          {
            id: 'status_pelatihan',
            title: 'Status Anda saat ini',
            type: 'dropdown',
            required: true,
            placeholder: '-- Pilih Status Keikutsertaan Pelatihan Anda --',
            options: [
              {
                id: 'opt-selesai',
                label: 'Sudah menyelesaikan Pelatihan',
                nextSectionId: 'sec-unggah-bukti',
              },
              {
                id: 'opt-mulai',
                label: 'Sudah mulai Pelatihan',
                nextSectionId: 'sec-alasan-belum-selesai',
              },
              {
                id: 'opt-belum',
                label: 'Belum mulai Pelatihan',
                nextSectionId: 'sec-alasan-belum-mulai',
              },
            ],
          },
        ],
      },
      {
        id: 'sec-unggah-bukti',
        title: 'BUKTI KELULUSAN / SERTIFIKAT PELATIHAN',
        description: 'Silakan masukkan tautan / link Google Drive bukti sertifikat kelulusan Anda.',
        questions: [
          {
            id: 'link_gdrive_sertifikat',
            title: 'Tautan / Link Google Drive Sertifikat',
            description: 'Pastikan hak akses link Google Drive telah dibuka ("Siapa saja yang memiliki link").',
            type: 'link',
            required: true,
            placeholder: 'https://drive.google.com/file/d/.../view?usp=sharing',
          },
        ],
      },
      {
        id: 'sec-alasan-belum-selesai',
        title: 'Alasan Belum Menyelesaikan',
        description: 'Berikan penjelasan kendala kenapa belum menyelesaikan pelatihan ini.',
        questions: [
          {
            id: 'alasan_belum_selesai',
            title: 'Alasan / Kendala Belum Menyelesaikan',
            type: 'textarea',
            required: true,
            placeholder: 'Tuliskan progres dan kendala Anda...',
          },
        ],
      },
      {
        id: 'sec-alasan-belum-mulai',
        title: 'Alasan Belum Memulai',
        description: 'Berikan penjelasan kendala kenapa belum memulai pelatihan ini.',
        questions: [
          {
            id: 'alasan_belum_mulai',
            title: 'Alasan / Kendala Belum Memulai',
            type: 'textarea',
            required: true,
            placeholder: 'Tuliskan alasan kenapa belum dapat memulai...',
          },
        ],
      },
    ],
  },
  {
    id: 'survei_kuesioner',
    name: 'Kuesioner / Survei Pegawai Standar',
    badge: 'Linear Berurutan',
    desc: 'Formulir survei berurutan untuk pengumpulan feedback, kepuasan layanan, atau evaluasi internal.',
    category: 'Survei & Evaluasi',
    createSections: (): FormSection[] => [
      {
        id: 'sec-survei-1',
        title: 'Penilaian & Masukan Pegawai',
        description: 'Mohon berikan penilaian objektif Anda untuk perbaikan layanan kepegawaian.',
        questions: [
          {
            id: 'tingkat_kepuasan',
            title: 'Bagaimana penilaian kepuasan Anda terhadap layanan OSDM saat ini?',
            type: 'dropdown',
            required: true,
            options: [
              { id: 'opt-sangat-puas', label: 'Sangat Puas' },
              { id: 'opt-puas', label: 'Puas' },
              { id: 'opt-cukup', label: 'Cukup' },
              { id: 'opt-kurang', label: 'Kurang Puas' },
            ],
          },
          {
            id: 'aspek_layanan',
            title: 'Layanan kepegawaian apa yang paling sering Anda akses?',
            type: 'radio',
            required: true,
            options: [
              { id: 'opt-kenaikan-pangkat', label: 'Kenaikan Pangkat / Jabatan Fungsional' },
              { id: 'opt-cuti', label: 'Layanan Cuti Pegawai' },
              { id: 'opt-pelatihan', label: 'Pengembangan Kompetensi & Tugas Belajar' },
              { id: 'opt-lainnya', label: 'Lainnya' },
            ],
          },
          {
            id: 'saran_masukan',
            title: 'Saran & Masukan untuk Peningkatan Layanan',
            type: 'textarea',
            required: false,
            placeholder: 'Tuliskan masukan atau saran konstruktif Anda...',
          },
        ],
      },
    ],
  },
  {
    id: 'pengumpulan_berkas',
    name: 'Pendataan Dokumen / Tautan Berkas',
    badge: 'Tautan GDrive',
    desc: 'Formulir pengumpulan berkas portofolio, sertifikat pendidik, SK, atau laporan berkas via Google Drive.',
    category: 'Pengumpulan Berkas',
    createSections: (): FormSection[] => [
      {
        id: 'sec-berkas-1',
        title: 'Unggah Tautan Berkas Dokumen',
        description: 'Pastikan berkas telah disimpan di Google Drive Anda dengan hak akses publik/pelihat.',
        questions: [
          {
            id: 'nama_dokumen',
            title: 'Nama / Judul Dokumen yang Dikumpulkan',
            type: 'text',
            required: true,
            placeholder: 'Contoh: Laporan Kinerja Tahunan / Sertifikat Pendidik',
          },
          {
            id: 'link_gdrive_dokumen',
            title: 'Tautan / Link Google Drive Berkas',
            type: 'link',
            required: true,
            placeholder: 'https://drive.google.com/file/d/.../view?usp=sharing',
          },
          {
            id: 'catatan_dokumen',
            title: 'Keterangan Tambahan (Opsional)',
            type: 'textarea',
            required: false,
            placeholder: 'Catatan tambahan atau keterangan nomor dokumen jika ada...',
          },
        ],
      },
    ],
  },
  {
    id: 'kustom_kosong',
    name: 'Formulir Kosong (Desain Bebas)',
    badge: 'Struktur Bebas',
    desc: 'Mulai dari kanvas kosong untuk membuat struktur bagian dan pertanyaan sesuai kebutuhan Anda sendiri.',
    category: 'Umum',
    createSections: (): FormSection[] => [
      {
        id: `sec-${Date.now()}-1`,
        title: 'Bagian 1: Data Isian',
        description: 'Silakan isi data formulir di bawah ini dengan benar.',
        questions: [
          {
            id: `q-${Date.now()}-1`,
            title: 'Pertanyaan Pertama Anda',
            type: 'text',
            required: true,
            placeholder: 'Tuliskan jawaban...',
          },
        ],
      },
    ],
  },
];

export const PegawaiFormCreateModal: React.FC<PegawaiFormCreateModalProps> = ({
  isOpen,
  onClose,
  onCreate,
}) => {
  // Tabs: 'info' (Identitas & Template) | 'builder' (Desain Struktur & Pertanyaan)
  const [activeTab, setActiveTab] = useState<'info' | 'builder'>('info');

  // Form Basic Info
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState('Pelatihan & Pengembangan');
  const [targetKepegawaian, setTargetKepegawaian] = useState<TargetKepegawaian>('ALL');
  const [selectedTemplateId, setSelectedTemplateId] = useState('pelatihan_bercabang');
  const [bannerUrl, setBannerUrl] = useState('');
  const [isDraggingBanner, setIsDraggingBanner] = useState(false);
  const bannerInputRef = useRef<HTMLInputElement>(null);

  // Form Sections & Questions State
  const [sections, setSections] = useState<FormSection[]>(() =>
    PRESET_TEMPLATES[0].createSections()
  );

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleBannerUpload = async (file: File) => {
    if (!file.type.startsWith('image/')) {
      alert('Format file harus berupa gambar (PNG, JPG, JPEG, WebP).');
      return;
    }
    try {
      // Compress and resize for 4:1 ratio banner (width 1200, height 300)
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

  if (!isOpen) return null;

  // Handle Template Selection
  const handleSelectTemplate = (templateId: string) => {
    const tmpl = PRESET_TEMPLATES.find((t) => t.id === templateId);
    if (!tmpl) return;

    setSelectedTemplateId(templateId);
    if (tmpl.category) setCategory(tmpl.category);
    setSections(tmpl.createSections());
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

      // If switched to dropdown or radio and has no options, populate defaults
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

  // Total questions count calculation
  const totalQuestionsCount = sections.reduce(
    (acc, sec) => acc + (sec.questions?.length || 0),
    0
  );

  // Form Submit Handler
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) {
      setActiveTab('info');
      setError('Judul formulir wajib diisi.');
      return;
    }

    // Validate that questions have titles
    for (let sIdx = 0; sIdx < sections.length; sIdx++) {
      const sec = sections[sIdx];
      for (let qIdx = 0; qIdx < sec.questions.length; qIdx++) {
        const q = sec.questions[qIdx];
        if (!q.title.trim()) {
          setActiveTab('builder');
          setError(
            `Pertanyaan nomor ${qIdx + 1} di "${sec.title || `Bagian ${sIdx + 1}`}" belum memiliki judul pertanyaan.`
          );
          return;
        }
      }
    }

    setIsSubmitting(true);
    setError(null);

    const newFormId = `form-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`;
    const newForm: PegawaiForm = {
      id: newFormId,
      title: title.trim(),
      description: description.trim(),
      category: category.trim(),
      bannerUrl: bannerUrl.trim() || undefined,
      isActive: true,
      targetKepegawaian: targetKepegawaian,
      startDate: new Date().toISOString().slice(0, 10),
      createdAt: new Date().toISOString(),
      sections: sections,
    };

    try {
      const ok = await onCreate(newForm);
      if (ok) {
        setTitle('');
        setDescription('');
        setBannerUrl('');
        setTargetKepegawaian('ALL');
        onClose();
      } else {
        setError('Gagal menyimpan formulir baru.');
      }
    } catch (err: any) {
      setError(err?.message || 'Terjadi kesalahan sistem.');
    } finally {
      setIsSubmitting(false);
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
              <Layers className="w-5 h-5 text-teal-100" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-sm sm:text-base font-black tracking-tight">
                  Buat Formulir Pegawai Baru
                </h3>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-500/30 text-teal-100 border border-teal-400/30">
                  Desain Struktur Kustom
                </span>
              </div>
              <p className="text-[11px] text-teal-200 mt-0.5">
                Tentukan struktur bagian, jenis pertanyaan, dan logika percabangan sesuai kebutuhan data Anda
              </p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 text-teal-200 hover:text-white rounded-full hover:bg-white/10 transition-colors relative z-10"
            aria-label="Tutup"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Tab Navigation Header */}
        <div className="bg-slate-50 border-b border-slate-200 px-5 pt-3 flex items-center justify-between shrink-0 gap-2">
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setActiveTab('info')}
              className={`px-4 py-2.5 rounded-t-xl text-xs font-bold transition-all border-b-2 flex items-center gap-2 ${
                activeTab === 'info'
                  ? 'bg-white text-teal-800 border-teal-600 shadow-xs'
                  : 'text-slate-500 border-transparent hover:text-slate-800 hover:bg-slate-100/70'
              }`}
            >
              <FileText className="w-4 h-4" />
              <span>1. Identitas & Template Formulir</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('builder')}
              className={`px-4 py-2.5 rounded-t-xl text-xs font-bold transition-all border-b-2 flex items-center gap-2 ${
                activeTab === 'builder'
                  ? 'bg-white text-teal-800 border-teal-600 shadow-xs'
                  : 'text-slate-500 border-transparent hover:text-slate-800 hover:bg-slate-100/70'
              }`}
            >
              <Settings2 className="w-4 h-4" />
              <span>2. Desain Struktur & Pertanyaan</span>
              <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-100 text-teal-800">
                {sections.length} Bagian • {totalQuestionsCount} Soal
              </span>
            </button>
          </div>
        </div>

        {/* Error Banner */}
        {error && (
          <div className="mx-6 mt-4 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5 animate-shake">
            <AlertCircle className="w-4 h-4 shrink-0 text-rose-500" />
            <span className="font-semibold">{error}</span>
          </div>
        )}

        {/* Modal Body / Tab Contents */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6 text-xs">
          {activeTab === 'info' ? (
            /* TAB 1: INFORMASI FORMULIR & PILIHAN TEMPLATE */
            <div className="space-y-6">
              {/* Form Metadata */}
              <div className="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 sm:p-5 space-y-4">
                <div className="font-bold text-slate-800 text-xs flex items-center gap-2">
                  <FileText className="w-4 h-4 text-teal-600" />
                  <span>Informasi Pokok Formulir</span>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    <label className="block font-bold text-slate-700 mb-1">Status Ketersediaan</label>
                    <div className="w-full px-3.5 py-2.5 bg-emerald-50 border border-emerald-300 rounded-xl text-xs font-bold text-emerald-800 flex items-center gap-2">
                      <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" />
                      <span>Aktif (Langsung Terbuka)</span>
                    </div>
                  </div>

                  {/* Target Sasaran Pegawai Pengisi Formulir */}
                  <div className="sm:col-span-2 space-y-2 pt-1 border-t border-slate-200">
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

                  {/* Banner Header Formulir (Rasio ~5:1) */}
                  <div className="sm:col-span-2 space-y-2 pt-1 border-t border-slate-200/80">
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
                      Unggah gambar banner dengan rasio sekitar <strong>4:1</strong> (rekomendasi: <strong>1200 x 300 px</strong> atau <strong>1024 x 256 px</strong>, format PNG/JPG/WebP) agar header tampil utuh tanpa terpotong dan langsung menampilkan judul grafis formulir.
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
                              title="Hapus banner"
                            >
                              <X className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        </div>
                        <div className="flex items-center justify-between text-[11px] text-slate-500">
                          <span className="text-emerald-600 font-semibold flex items-center gap-1">
                            <CheckCircle2 className="w-3.5 h-3.5" /> Banner kustom siap digunakan
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
                          <span>Klik atau Seret File Gambar Banner ke Sini</span>
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

              {/* Template Presets Picker */}
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-black text-slate-800 text-xs sm:text-sm flex items-center gap-1.5">
                      <Sparkles className="w-4 h-4 text-teal-600" />
                      <span>Pilih Template Awal (Bisa Diedit Bebas)</span>
                    </h4>
                    <p className="text-[11px] text-slate-500">
                      Pilih fondasi awal struktur form. Anda dapat leluasa menambah, menghapus, atau mengubah struktur di Tab 2.
                    </p>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {PRESET_TEMPLATES.map((tmpl) => {
                    const isSelected = selectedTemplateId === tmpl.id;
                    return (
                      <div
                        key={tmpl.id}
                        onClick={() => handleSelectTemplate(tmpl.id)}
                        className={`p-4 rounded-2xl border-2 transition-all cursor-pointer relative flex flex-col justify-between ${
                          isSelected
                            ? 'bg-teal-50/70 border-teal-600 shadow-sm'
                            : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                        }`}
                      >
                        <div className="space-y-1.5">
                          <div className="flex items-center justify-between">
                            <span
                              className={`text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full ${
                                isSelected
                                  ? 'bg-teal-600 text-white'
                                  : 'bg-slate-100 text-slate-600'
                              }`}
                            >
                              {tmpl.badge}
                            </span>
                            {isSelected && (
                              <span className="w-5 h-5 rounded-full bg-teal-600 text-white flex items-center justify-center">
                                <Check className="w-3 h-3 stroke-[3]" />
                              </span>
                            )}
                          </div>
                          <h5 className="font-bold text-slate-900 text-xs leading-snug">
                            {tmpl.name}
                          </h5>
                          <p className="text-[11px] text-slate-500 leading-relaxed">
                            {tmpl.desc}
                          </p>
                        </div>

                        <div className="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                          <span className="text-teal-700 font-semibold">Gunakan Template Ini</span>
                          <ArrowRight className="w-3.5 h-3.5 text-teal-600" />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          ) : (
            /* TAB 2: DESAIN STRUKTUR & PERTANYAAN (FORM BUILDER) */
            <div className="space-y-6">
              {/* Builder Info & Top Actions */}
              <div className="flex flex-wrap items-center justify-between gap-2 bg-teal-50 border border-teal-200/80 p-3.5 rounded-2xl">
                <div className="flex items-center gap-2">
                  <div className="p-2 bg-teal-600 text-white rounded-xl">
                    <GitBranch className="w-4 h-4" />
                  </div>
                  <div>
                    <h5 className="font-bold text-teal-900 text-xs">
                      Struktur Formulir Kustom
                    </h5>
                    <p className="text-[11px] text-teal-700">
                      Kelola setiap bagian, jenis kolom isian, dan alur percabangan (skip logic).
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handleAddSection}
                  className="px-3.5 py-2 bg-teal-700 hover:bg-teal-800 text-white rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-xs transition-all active:scale-[0.98]"
                >
                  <FolderPlus className="w-4 h-4" />
                  <span>Tambah Bagian Baru</span>
                </button>
              </div>

              {/* Sections List */}
              <div className="space-y-6">
                {sections.map((section, sIdx) => (
                  <div
                    key={section.id}
                    className="bg-white border-2 border-slate-200 hover:border-teal-500/50 rounded-2xl overflow-hidden shadow-xs transition-all"
                  >
                    {/* Section Header */}
                    <div className="bg-slate-50/90 border-b border-slate-200 p-4 space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <span className="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-teal-800 text-white shadow-xs">
                            Bagian {sIdx + 1} dari {sections.length}
                          </span>
                          <span className="text-slate-400 font-mono text-[10px]">
                            ID: {section.id}
                          </span>
                        </div>

                        {sections.length > 1 && (
                          <button
                            type="button"
                            onClick={() => handleRemoveSection(sIdx)}
                            className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors flex items-center gap-1 text-[11px] font-bold"
                            title="Hapus bagian ini"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                            <span>Hapus Bagian</span>
                          </button>
                        )}
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                          <label className="block text-[11px] font-bold text-slate-700 mb-1">
                            Judul Bagian <span className="text-rose-500">*</span>
                          </label>
                          <input
                            type="text"
                            value={section.title}
                            onChange={(e) => handleUpdateSectionTitle(sIdx, e.target.value)}
                            placeholder="Contoh: Status Pelatihan / Bukti Kelulusan"
                            className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-teal-500"
                            required
                          />
                        </div>

                        <div>
                          <label className="block text-[11px] font-bold text-slate-700 mb-1">
                            Petunjuk / Deskripsi Bagian
                          </label>
                          <input
                            type="text"
                            value={section.description || ''}
                            onChange={(e) => handleUpdateSectionDesc(sIdx, e.target.value)}
                            placeholder="Penjelasan petunjuk untuk pegawai pada bagian ini..."
                            className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-medium text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-teal-500"
                          />
                        </div>
                      </div>
                    </div>

                    {/* Questions inside Section */}
                    <div className="p-4 sm:p-5 space-y-4">
                      <div className="flex items-center justify-between text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <span>Daftar Pertanyaan di Bagian Ini ({section.questions.length})</span>
                      </div>

                      {section.questions.map((question, qIdx) => (
                        <div
                          key={question.id}
                          className="bg-slate-50/60 border border-slate-200 rounded-xl p-3.5 sm:p-4 space-y-3"
                        >
                          {/* Question Top Row: Title, Delete */}
                          <div className="flex items-start gap-2.5">
                            <span className="w-6 h-6 rounded-lg bg-teal-100 text-teal-800 font-extrabold flex items-center justify-center shrink-0 text-[11px]">
                              {qIdx + 1}
                            </span>
                            <div className="flex-1 space-y-1">
                              <input
                                type="text"
                                value={question.title}
                                onChange={(e) =>
                                  handleUpdateQuestion(sIdx, qIdx, { title: e.target.value })
                                }
                                placeholder="Tuliskan teks pertanyaan di sini..."
                                className="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg font-bold text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500"
                                required
                              />
                            </div>
                            {section.questions.length > 1 && (
                              <button
                                type="button"
                                onClick={() => handleRemoveQuestion(sIdx, qIdx)}
                                className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                title="Hapus pertanyaan ini"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            )}
                          </div>

                          {/* Question Settings Row: Type, Required, Placeholder */}
                          <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pl-8">
                            <div>
                              <label className="block text-[10px] font-bold text-slate-500 mb-0.5">
                                Tipe Pertanyaan
                              </label>
                              <select
                                value={question.type}
                                onChange={(e) =>
                                  handleUpdateQuestion(sIdx, qIdx, {
                                    type: e.target.value as FormQuestionType,
                                  })
                                }
                                className="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer"
                              >
                                <option value="dropdown">Dropdown Pilihan (Select)</option>
                                <option value="radio">Pilihan Ganda (Radio)</option>
                                <option value="link">Tautan Google Drive / Link URL</option>
                                <option value="text">Teks Singkat</option>
                                <option value="textarea">Paragraf / Teks Panjang</option>
                              </select>
                            </div>

                            <div>
                              <label className="block text-[10px] font-bold text-slate-500 mb-0.5">
                                Placeholder / Contoh
                              </label>
                              <input
                                type="text"
                                value={question.placeholder || ''}
                                onChange={(e) =>
                                  handleUpdateQuestion(sIdx, qIdx, { placeholder: e.target.value })
                                }
                                placeholder="Teks bantuan di dalam kolom..."
                                className="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-500"
                              />
                            </div>

                            <div className="flex items-end pb-1.5">
                              <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                  type="checkbox"
                                  checked={question.required}
                                  onChange={(e) =>
                                    handleUpdateQuestion(sIdx, qIdx, { required: e.target.checked })
                                  }
                                  className="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300"
                                />
                                <span className="text-xs font-bold text-slate-700">
                                  Wajib Diisi (Required)
                                </span>
                              </label>
                            </div>
                          </div>

                          {/* Options Manager & Logic Branching for Dropdown & Radio */}
                          {(question.type === 'dropdown' || question.type === 'radio') && (
                            <div className="pl-8 pt-2 border-t border-slate-200/80 space-y-2">
                              <div className="flex items-center justify-between">
                                <span className="text-[11px] font-bold text-teal-800 flex items-center gap-1.5">
                                  <GitBranch className="w-3.5 h-3.5 text-teal-600" />
                                  <span>Pilihan Opsi & Alur Percabangan Logika</span>
                                </span>
                                <button
                                  type="button"
                                  onClick={() => handleAddOption(sIdx, qIdx)}
                                  className="text-[10px] font-bold text-teal-700 hover:text-teal-900 bg-teal-50 hover:bg-teal-100 px-2 py-1 rounded-md border border-teal-200 flex items-center gap-1"
                                >
                                  <Plus className="w-3 h-3" />
                                  <span>Tambah Opsi</span>
                                </button>
                              </div>

                              <div className="space-y-1.5">
                                {(question.options || []).map((opt, optIdx) => (
                                  <div
                                    key={opt.id}
                                    className="flex flex-wrap sm:flex-nowrap items-center gap-2 bg-white p-2 rounded-lg border border-slate-200"
                                  >
                                    <div className="flex-1 min-w-[140px]">
                                      <input
                                        type="text"
                                        value={opt.label}
                                        onChange={(e) =>
                                          handleUpdateOption(sIdx, qIdx, optIdx, {
                                            label: e.target.value,
                                          })
                                        }
                                        placeholder={`Opsi ${optIdx + 1}`}
                                        className="w-full px-2.5 py-1 text-xs font-semibold text-slate-800 border border-slate-200 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500"
                                      />
                                    </div>

                                    {/* Branching target selection */}
                                    <div className="flex items-center gap-1.5">
                                      <span className="text-[10px] font-bold text-slate-400 whitespace-nowrap">
                                        Lompat ke:
                                      </span>
                                      <select
                                        value={opt.nextSectionId || ''}
                                        onChange={(e) =>
                                          handleUpdateOption(sIdx, qIdx, optIdx, {
                                            nextSectionId: e.target.value || undefined,
                                          })
                                        }
                                        className="px-2 py-1 text-[11px] font-medium bg-slate-50 border border-slate-200 rounded-md text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-500 cursor-pointer"
                                      >
                                        <option value="">Lanjut Otomatis (Bagian Berikutnya)</option>
                                        {sections
                                          .filter((_, targetIdx) => targetIdx !== sIdx)
                                          .map((otherSec, targetIdx) => (
                                            <option key={otherSec.id} value={otherSec.id}>
                                              Lompat ke: {otherSec.title || `Bagian ID ${otherSec.id}`}
                                            </option>
                                          ))}
                                      </select>
                                    </div>

                                    {(question.options || []).length > 1 && (
                                      <button
                                        type="button"
                                        onClick={() => handleRemoveOption(sIdx, qIdx, optIdx)}
                                        className="p-1 text-slate-400 hover:text-rose-600 rounded"
                                        title="Hapus opsi"
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

                      {/* Add Question Button */}
                      <button
                        type="button"
                        onClick={() => handleAddQuestion(sIdx)}
                        className="w-full py-2.5 border-2 border-dashed border-teal-300 hover:border-teal-500 bg-teal-50/40 hover:bg-teal-50 text-teal-800 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all"
                      >
                        <ListPlus className="w-4 h-4 text-teal-600" />
                        <span>Tambah Pertanyaan ke {section.title || `Bagian ${sIdx + 1}`}</span>
                      </button>
                    </div>
                  </div>
                ))}
              </div>

              {/* Bottom Add Section Button */}
              <button
                type="button"
                onClick={handleAddSection}
                className="w-full py-3 border-2 border-dashed border-slate-300 hover:border-teal-500 bg-slate-50 hover:bg-teal-50/40 text-slate-700 hover:text-teal-800 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-xs"
              >
                <FolderPlus className="w-4 h-4 text-teal-600" />
                <span>+ Tambah Bagian Baru (Section)</span>
              </button>
            </div>
          )}

          {/* Modal Footer */}
          <div className="pt-4 border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition-all text-xs"
            >
              Batal
            </button>

            <div className="flex items-center gap-2">
              {activeTab === 'info' ? (
                <button
                  type="button"
                  onClick={() => {
                    if (!title.trim()) {
                      setError('Judul formulir wajib diisi.');
                      return;
                    }
                    setError(null);
                    setActiveTab('builder');
                  }}
                  className="px-5 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold transition-all shadow-xs flex items-center gap-1.5 text-xs"
                >
                  <span>Lanjut Desain Struktur</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
              ) : (
                <>
                  <button
                    type="button"
                    onClick={() => setActiveTab('info')}
                    className="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition-all text-xs"
                  >
                    Kembali ke Info
                  </button>

                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="px-6 py-2.5 rounded-xl bg-gradient-to-r from-teal-700 via-teal-800 to-emerald-800 hover:from-teal-800 hover:to-emerald-900 text-white font-black transition-all shadow-md flex items-center gap-1.5 text-xs active:scale-[0.98]"
                  >
                    <Check className="w-4 h-4" />
                    <span>{isSubmitting ? 'Menyimpan...' : 'Simpan & Aktifkan Formulir'}</span>
                  </button>
                </>
              )}
            </div>
          </div>
        </form>
      </motion.div>
    </div>
  );
};

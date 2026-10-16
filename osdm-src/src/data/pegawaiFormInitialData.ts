import { PegawaiForm, PegawaiFormResponse } from '../types';

export const INITIAL_PEGAWAI_FORMS: PegawaiForm[] = [
  {
    id: 'form-mooc-asn-berintegritas',
    title: 'Konfirmasi Pelatihan MOOC ASN Berintegritas',
    description: 'Formulir Pelaporan dan Konfirmasi Keikutsertaan E-Learning Aparatur Sipil Negara (ASN) Berintegritas bagi Pegawai Poltekkes Kemenkes Bandung.',
    category: 'Pelatihan & Pengembangan Pegawai',
    bannerUrl: './banner-mooc-asn.png',
    isActive: true,
    targetKepegawaian: 'ASN',
    startDate: new Date().toISOString().slice(0, 10),
    createdAt: new Date().toISOString(),
    sections: [
      {
        id: 'sec-status-utama',
        title: 'Status Pelatihan Anda',
        description: 'Silahkan pilih status keikutsertaan Anda saat ini pada Pelatihan MOOC ASN Berintegritas.',
        questions: [
          {
            id: 'status_pelatihan',
            title: 'Status Anda saat ini',
            description: 'Pilih salah satu kondisi yang sesuai dengan status pelatihan Anda.',
            type: 'dropdown',
            placeholder: '-- Pilih Status Pelatihan Anda --',
            required: true,
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
        description: 'Silakan masukkan tautan / link Google Drive bukti sertifikat kelulusan Pelatihan MOOC ASN Berintegritas Anda.',
        questions: [
          {
            id: 'link_gdrive_sertifikat',
            title: 'Tautan / Link Google Drive Sertifikat',
            description: 'Pastikan hak akses tautan Google Drive telah dibuka ("Siapa saja yang memiliki link").',
            type: 'text',
            required: true,
            placeholder: 'https://drive.google.com/file/d/.../view?usp=sharing',
          },
        ],
      },
      {
        id: 'sec-alasan-belum-selesai',
        title: 'Alasan Belum Menyelesaikan E-Learning Aparatur Sipil Negara (ASN) Berintegritas',
        description: 'Berikan alasan Anda kenapa belum menyelesaikan pelatihan ini.',
        questions: [
          {
            id: 'alasan_belum_selesai',
            title: 'Alasan',
            description: 'Jelaskan kendala atau hal yang menyebabkan Anda belum menyelesaikan pelatihan ini.',
            type: 'textarea',
            required: true,
            placeholder: 'Tuliskan kendala atau progres pembelajaran Anda saat ini...',
          },
        ],
      },
      {
        id: 'sec-alasan-belum-mulai',
        title: 'Alasan Belum Memulai E-Learning Aparatur Sipil Negara (ASN) Berintegritas',
        description: 'Berikan alasan Anda kenapa belum memulai pelatihan ini.',
        questions: [
          {
            id: 'alasan_belum_mulai',
            title: 'Alasan',
            description: 'Jelaskan kendala yang menyebabkan Anda belum dapat memulai pelatihan ini.',
            type: 'textarea',
            required: true,
            placeholder: 'Tuliskan kendala Anda (misal: kendala akses login, padat agenda dinas, dll)...',
          },
        ],
      },
    ],
  },
];

export const INITIAL_PEGAWAI_FORM_RESPONSES: PegawaiFormResponse[] = [];

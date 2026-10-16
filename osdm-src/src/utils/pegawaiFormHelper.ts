import { PegawaiForm, PegawaiFormResponse, EmployeeRecord } from '../types';

/**
 * Escape CSV string to handle quotes, commas, and newlines safely
 */
export function escapeCSV(value: any): string {
  if (value === null || value === undefined) return '""';
  const str = String(value).replace(/"/g, '""').replace(/\r\n/g, ' ').replace(/\n/g, ' ');
  return `"${str}"`;
}

/**
 * Generate WhatsApp reminder URL for employee who has not yet filled the form
 */
export function generateWhatsAppReminderUrl(
  phone: string | undefined,
  employeeName: string,
  formTitle: string,
  formUrl: string
): string {
  let cleanPhone = (phone || '').replace(/[^0-9]/g, '');
  if (cleanPhone.startsWith('0')) {
    cleanPhone = '62' + cleanPhone.slice(1);
  } else if (!cleanPhone.startsWith('62') && cleanPhone.length > 0) {
    cleanPhone = '62' + cleanPhone;
  }

  const message = `Yth. Bapak/Ibu *${employeeName}*,\n\nMohon kesediaannya untuk segera mengisi formulir resmi:\n📋 *${formTitle}*\n\nPengisian dapat dilakukan melalui tautan berikut:\n👉 ${formUrl}\n\nTerima kasih atas kerja samanya.\n_Tim OSDM Poltekkes Kemenkes Bandung_`;

  if (!cleanPhone) {
    // If no phone number, open web share without target number
    return `https://wa.me/?text=${encodeURIComponent(message)}`;
  }

  return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
}

/**
 * Export responses of a custom form to CSV / Excel
 */
export function exportFormResponsesToExcel(
  form: PegawaiForm,
  responses: PegawaiFormResponse[],
  unitFilter: string = 'Semua Unit'
) {
  const nowStr = new Date().toISOString().slice(0, 10);
  const nowTime = new Date().toLocaleString('id-ID');

  // Extract all questions from the form's sections dynamically
  const formQuestions = (form.sections || []).flatMap((s) => s.questions || []);

  const baseHeaders = [
    'No',
    'Waktu Pengisian',
    'NIP Pegawai',
    'Nama Lengkap Pegawai',
    'Unit Kerja / Jurusan',
    'Jabatan',
    'Nomor WhatsApp',
  ];

  const questionHeaders = formQuestions.length > 0
    ? formQuestions.map((q) => q.title || `Pertanyaan ${q.id}`)
    : [
        'Status Pilihan',
        'Bukti Link Google Drive Sertifikat',
        'Alasan Belum Selesai',
        'Alasan Belum Mulai',
      ];

  const headers = [...baseHeaders, ...questionHeaders, 'ID Respon'];

  const rows = responses.map((res, idx) => {
    const ans = res.answers || {};

    const questionValues = formQuestions.length > 0
      ? formQuestions.map((q) => {
          let val = ans[q.id];
          if (val === undefined || val === null || val === '') {
            if (q.id === 'status_pelatihan' || q.title.toLowerCase().includes('status')) {
              val = ans.status_pelatihan;
            } else if (
              q.id === 'link_gdrive_sertifikat' ||
              q.title.toLowerCase().includes('drive') ||
              q.title.toLowerCase().includes('link')
            ) {
              val = ans.link_gdrive_sertifikat || ans.keterangan_sertifikat;
            }
          }
          if (typeof val === 'object' && val?.fileName) {
            val = `File: ${val.fileName}`;
          }
          return escapeCSV(val || '-');
        })
      : [
          escapeCSV(ans.status_pelatihan || '-'),
          escapeCSV(
            ans.link_gdrive_sertifikat ||
              (typeof ans.keterangan_sertifikat === 'string' && ans.keterangan_sertifikat.startsWith('http')
                ? ans.keterangan_sertifikat
                : ans.bukti_sertifikat?.fileName
                ? `File: ${ans.bukti_sertifikat.fileName}`
                : '-')
          ),
          escapeCSV(ans.alasan_belum_selesai || '-'),
          escapeCSV(ans.alasan_belum_mulai || '-'),
        ];

    return [
      idx + 1,
      escapeCSV(res.submittedAt ? new Date(res.submittedAt).toLocaleString('id-ID') : '-'),
      escapeCSV(`'${res.nip}`), // Apostrophe to preserve leading zeros in Excel
      escapeCSV(res.namaPegawai),
      escapeCSV(res.unitKerja),
      escapeCSV(res.jabatan),
      escapeCSV(res.nomorWa ? `'${res.nomorWa}` : '-'),
      ...questionValues,
      escapeCSV(res.id),
    ].join(',');
  });

  let csvContent = '\uFEFF'; // UTF-8 BOM
  csvContent += `REKAPITULASI RESPONDEN FORMULIR PEGAWAI POLTEKKES KEMENKES BANDUNG\n`;
  csvContent += `Judul Formulir,"${form.title}"\n`;
  csvContent += `Filter Unit Kerja,"${unitFilter}"\n`;
  csvContent += `Waktu Tarikan Data,"${nowTime}"\n`;
  csvContent += `Total Responden Mengisi,${responses.length}\n\n`;
  csvContent += headers.join(',') + '\r\n';
  csvContent += rows.join('\r\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  const safeTitle = form.title.replace(/[^a-zA-Z0-9]/g, '_').slice(0, 35);
  link.setAttribute('download', `rekap_${safeTitle}_${unitFilter.replace(/\s+/g, '_')}_${nowStr}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

/**
 * Export unsubmitted employees to CSV / Excel
 */
export function exportFormUnsubmittedEmployeesToExcel(
  form: PegawaiForm,
  unsubmittedEmployees: EmployeeRecord[],
  unitFilter: string = 'Semua Unit'
) {
  const nowStr = new Date().toISOString().slice(0, 10);
  const nowTime = new Date().toLocaleString('id-ID');

  const headers = [
    'No',
    'NIP Pegawai',
    'Nama Lengkap Pegawai',
    'Unit Kerja / Jurusan',
    'Jabatan',
    'Status Kepegawaian',
    'Nomor WhatsApp',
    'Email Pegawai',
    'Keterangan'
  ];

  const rows = unsubmittedEmployees.map((emp, idx) => {
    return [
      idx + 1,
      escapeCSV(`'${emp.nip}`),
      escapeCSV(emp.name),
      escapeCSV(emp.unitKerja),
      escapeCSV(emp.jabatan),
      escapeCSV(emp.status || 'Aktif'),
      escapeCSV(emp.nomorWa ? `'${emp.nomorWa}` : '-'),
      escapeCSV(emp.email || '-'),
      escapeCSV('BELUM MENGISI')
    ].join(',');
  });

  let csvContent = '\uFEFF'; // UTF-8 BOM
  csvContent += `DAFTAR PEGAWAI BELUM MENGISI FORMULIR\n`;
  csvContent += `Judul Formulir,"${form.title}"\n`;
  csvContent += `Filter Unit Kerja,"${unitFilter}"\n`;
  csvContent += `Waktu Tarikan Data,"${nowTime}"\n`;
  csvContent += `Total Pegawai Belum Mengisi,${unsubmittedEmployees.length}\n\n`;
  csvContent += headers.join(',') + '\r\n';
  csvContent += rows.join('\r\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  const safeTitle = form.title.replace(/[^a-zA-Z0-9]/g, '_').slice(0, 35);
  link.setAttribute('download', `belum_isi_${safeTitle}_${unitFilter.replace(/\s+/g, '_')}_${nowStr}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

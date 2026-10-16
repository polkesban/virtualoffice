# Roadmap Fitur Akreditasi Website Poltekkes Bandung

Dokumen ini menyusun prioritas fitur website Poltekkes Bandung agar lebih siap mendukung kebutuhan akreditasi berdasarkan Buku II Panduan Penilaian Akreditasi Program Studi Pendidikan Profesi Dietisien untuk Asesor, LAM-PTKes 2025.

Fokus roadmap ini adalah menambah kemampuan eviden, monitoring, dan publikasi data mutu tanpa mengganggu pengguna yang sedang beraktivitas di website.

## Prinsip Implementasi Aman

- Tahap awal harus bersifat admin-first dan read-only untuk publik.
- Hindari perubahan pada route publik yang sudah dipakai pengguna sampai konten dan data pengganti siap.
- Gunakan tabel baru untuk modul akreditasi baru; jangan memaksa migrasi besar pada tabel yang sudah aktif dipakai.
- Pekerjaan berat seperti ekspor, pencarian full-text, dan rekap indikator dijalankan async melalui queue.
- Rilis menu baru hanya untuk role terkait setelah data awal sudah terisi.
- Backfill data dan sinkronisasi dokumen dilakukan di luar jam sibuk.

## Kondisi Repo Saat Ini

Fondasi yang sudah ada dan bisa dipakai ulang:

- Ruang eviden akreditasi admin-only yang tetap dorman sebelum migrasi modul dijalankan.
- Dashboard kelengkapan per kriteria di ruang eviden untuk melihat folder terisi, siap review, dan prioritas tindak lanjut.
- Halaman detail eviden kini dapat menampilkan pratinjau isi file privat dan panel `Teks Terbaca` untuk PDF digital atau file teks yang didukung.
- Panel `Teks Terbaca` sekarang mendukung pencarian kata dan navigasi antar hasil agar reviewer bisa lompat ke bagian penting dokumen.
- PDF scan kini punya jalur OCR opsional berbasis utilitas server; jika utilitas belum ada, UI tetap memberi fallback yang jelas tanpa mengganggu workflow admin.
- Halaman detail eviden kini memiliki bookmark dan anotasi reviewer per eviden untuk menandai bagian penting sebelum asesmen atau visitasi.
- Anotasi reviewer kini memiliki status tindak lanjut agar tim mutu bisa memisahkan catatan yang belum dikerjakan, sedang diproses, dan sudah selesai.
- Dashboard ruang eviden kini merangkum catatan reviewer yang masih aktif dan bisa memfilter daftar eviden berdasarkan status tindak lanjut anotasi.
- Dashboard juga bisa mengekspor daftar tindak lanjut reviewer ke CSV sesuai filter aktif agar koordinasi tindak lanjut bisa dilakukan di luar panel admin tanpa membuka eviden satu per satu.
- Ekspor tindak lanjut reviewer kini tersedia dalam CSV dan XLSX agar tim mutu bisa memilih format kerja yang paling cocok.
- Dashboard admin utama kini menampilkan ringkasan tindak lanjut reviewer untuk admin yang memang punya akses ke ruang eviden, sehingga isu akreditasi aktif terlihat sejak login.
- Kartu dashboard admin utama kini juga menampilkan kriteria paling prioritas berdasarkan catatan reviewer aktif, eviden tanpa sumber, review dekat jatuh tempo, dan folder yang masih kosong.
- Sidebar admin kini memberi badge jumlah catatan reviewer aktif pada menu ruang eviden agar tindak lanjut terbuka tetap terlihat di seluruh area admin.
- Kartu dashboard utama kini punya filter cepat ke catatan aktif, eviden tanpa sumber, dan review dekat jatuh tempo; ruang eviden juga punya filter khusus `Sumber Eviden`.
- Ringkasan dashboard utama kini juga menampilkan PIC prioritas, dan ruang eviden punya filter `PIC` khusus agar tindak lanjut bisa dibagi per penanggung jawab.
- Kartu dashboard utama kini juga memberi quick link ke eviden kadaluarsa dan eviden yang masa berlakunya habis dalam 30 hari.
- Review yang sudah lewat jatuh tempo kini dipisahkan dari review `≤ 30 hari`, baik di quick link dashboard utama maupun di filter ruang eviden.
- Ruang eviden kini dapat mengekspor daftar eviden sesuai filter aktif ke CSV/XLSX, sehingga backlog review per PIC bisa dibagikan tanpa rekap manual.
- Kartu dashboard utama kini juga menyediakan tombol ekspor cepat CSV/XLSX khusus backlog `Review Lewat`, sehingga antrean yang terlambat bisa langsung dibagikan tanpa membuka ruang eviden.
- Kartu `PIC Prioritas` kini juga menyediakan aksi `Lihat`, `CSV`, dan `XLSX` untuk backlog `Review Lewat` per PIC, sehingga distribusi tindak lanjut ke penanggung jawab bisa dilakukan langsung dari dashboard utama.
- Kartu `Kriteria Prioritas` dan `PIC Prioritas` kini membedakan chip `Review Lewat` dari chip `Review/berlaku dekat`, sehingga indikator prioritas tidak lagi mencampur backlog terlambat dengan alert masa berlaku atau review upcoming.
- Kartu prioritas kini juga memisahkan chip `Review ≤ 30 hari` dari chip `Berlaku habis/kadaluarsa`, sehingga tim mutu bisa melihat apakah urgensi datang dari jadwal review atau masa berlaku dokumen.
- Kartu `Kriteria Prioritas` kini juga menyediakan aksi cepat langsung ke antrean `Review Lewat`, `Review ≤ 30 hari`, dan `Berlaku habis/kadaluarsa` per kriteria; filter ruang eviden juga mengenali nilai gabungan `Berlaku habis/kadaluarsa`.
- Jalur test SQLite untuk modul eviden dan fitur terkait kini sudah hijau, termasuk guard untuk migration legacy dan MySQL-specific behavior yang sebelumnya menghambat feature test.
- Full test suite Laravel kini lulus penuh dengan `53 passed` dan `231 assertions`, sehingga perubahan eviden akreditasi, halaman publik terkait, dan jalur event peserta sudah tervalidasi end-to-end.
- Smoke check read-only produksi juga mengonfirmasi halaman publik inti tetap normal, route eviden admin tetap terlindungi, dan route publik form pesanan Unit Bisnis yang benar adalah `/layanan-bisnis/pesan`.
- Command bootstrap dan runbook aktivasi off-peak untuk backfill awal dari Dokumen Mutu secara terkendali.
- Halaman akreditasi institusi dan program studi.
- Dokumen mutu publik dan admin.
- Halaman statis yang sudah bisa dikelola admin.
- Halaman unit berbasis page builder.
- Publikasi dan penelitian.
- Pojok alumni dan testimoni alumni.
- Modul knowledge document untuk indeks dokumen.
- Struktur role yang sudah memisahkan Pusat Mutu, Litmas, P3, Wadir, Humas, Kaprodi, dan unit lain.

Gap utama terhadap kebutuhan asesor:

- Halaman akreditasi masih bersifat ringkas, belum menjadi ruang eviden.
- Belum ada repositori bukti per kriteria, butir, PIC, status verifikasi, dan masa berlaku.
- Belum ada dashboard LKPS, indikator mutu, dan capaian tahunan.
- Tracer alumni yang ada belum menjadi tracer study formal.
- Belum ada engine survei kepuasan mahasiswa, alumni, pengguna lulusan, mitra, dan layanan.
- Belum ada modul kerja sama yang menyimpan MoU, MoA, IA, luaran, dan masa aktif.
- Belum ada direktori dosen, tenaga kependidikan, preceptor atau CI, serta bukti pengembangan profesional.
- Belum ada workflow AMI, temuan, tindak lanjut, dan audit trail SPMI.
- Beberapa route publik strategis masih placeholder, terutama akademik, penelitian, dan kerja sama.

## Kebutuhan dari Buku Asesor

Panduan asesor menilai delapan kriteria berikut:

1. Visi, misi, tujuan, dan strategi.
2. Kurikulum.
3. Penilaian.
4. Mahasiswa.
5. Dosen, tenaga kependidikan, penelitian, dan pengabdian kepada masyarakat.
6. Sarana, prasarana pendidikan, dan keuangan.
7. Penjaminan mutu.
8. Tata kelola dan administrasi.

Selain itu, asesor juga melihat pertimbangan pakar yang menekankan:

- relevansi lulusan dan kurikulum terhadap kebutuhan pasar kerja,
- suasana akademik,
- manajemen internal,
- keberlanjutan,
- efisiensi dan efektivitas pendidikan,
- kepemimpinan,
- aksesibilitas dan pemerataan.

Artinya, website tidak cukup hanya menampilkan profil dan berita. Website perlu membantu kampus menyiapkan bukti yang rapi, mudah telusur, terhubung dengan indikator, dan mudah dibuka saat asesmen lapangan.

## Rekomendasi Fitur per Kriteria

### 1. Visi, Misi, Tujuan, dan Strategi

Fitur yang disarankan:

- Ruang eviden VMTS yang menyimpan notulen perumusan, SK pengesahan, daftar hadir, media sosialisasi, renstra, renop, dan bukti pencapaian.
- Matriks keterkaitan VMTS dengan kurikulum, penelitian, pengabmas, kerja sama, dan indikator tahunan.
- Log sosialisasi VMTS beserta kanal publikasi dan tindak lanjut evaluasinya.
- Ringkasan pencapaian VMTS per tahun untuk ditampilkan publik secara aman.

Nilai tambah:

- Membantu asesor melihat bahwa VMTS bukan hanya slogan, tetapi diterjemahkan ke program dan indikator.

### 2. Kurikulum

Fitur yang disarankan:

- Modul peta CPL, CPMK, mata kuliah, rotasi praktik, dan profil lulusan.
- Halaman kurikulum prodi berbasis data, bukan hanya narasi statis.
- Repositori dokumen kurikulum: struktur kurikulum, peta kurikulum, evaluasi kurikulum, berita acara review, dan bukti keterlibatan stakeholder.
- Dashboard tracer study yang menghubungkan profil lulusan, masa tunggu kerja, kesesuaian kerja, dan capaian pembelajaran.
- Halaman unggulan prodi yang memperjelas kaitan kurikulum dengan kebutuhan layanan kesehatan dan lokalitas.

Nilai tambah:

- Menjawab kebutuhan asesor pada keselarasan CPL, kurikulum, stakeholder, dan karir lulusan.

### 3. Penilaian

Fitur yang disarankan:

- Repositori kebijakan penilaian, blueprint asesmen, rubrik, moderasi nilai, dan SOP ujian.
- Audit trail evaluasi penilaian per semester.
- Ringkasan mutu asesmen: ketepatan waktu input nilai, variasi metode asesmen, tindak lanjut evaluasi pembelajaran.
- Folder eviden ujian profesi dan evaluasi penjaminan mutu penilaian.

Nilai tambah:

- Menunjukkan penilaian berlangsung objektif, terukur, dan dievaluasi berkelanjutan.

### 4. Mahasiswa

Fitur yang disarankan:

- Halaman penerimaan mahasiswa baru yang benar-benar memuat kebijakan seleksi, afirmasi, kapasitas, tahapan, FAQ, dan mekanisme pengaduan.
- Modul layanan kemahasiswaan: beasiswa, konseling, dukungan akademik, dukungan karir, layanan kesehatan, dan minat bakat.
- Engine survei kepuasan mahasiswa untuk proses pendidikan dan layanan manajemen.
- Sistem pengaduan atau ticketing untuk layanan mahasiswa.
- Modul kampus sehat untuk dokumentasi kebijakan anti kekerasan seksual, anti perundungan, keselamatan mahasiswa, dan tindak lanjut kasus.
- Halaman lingkungan kerja dan belajar: jadwal praktik, beban kerja, panduan rotasi, panduan ujian profesi, dan informasi keselamatan.

Nilai tambah:

- Kriteria mahasiswa dalam buku asesor sangat kuat pada bukti kebijakan, layanan, aksesibilitas, kerahasiaan, survei, dan pengaduan.

### 5. Dosen, Tenaga Kependidikan, Penelitian, dan Pengabdian kepada Masyarakat

Fitur yang disarankan:

- Direktori dosen dan tendik yang memuat pendidikan, jabatan, sertifikasi, kompetensi, NIDN atau identitas internal, serta bidang kepakaran.
- Direktori pembimbing klinik atau CI dan wahana praktik.
- Portofolio pengembangan profesional berkelanjutan untuk dosen dan pembimbing klinik.
- Dashboard penelitian dan pengabdian: tema, relevansi dengan unggulan prodi, sumber dana, mitra, luaran, HKI, publikasi, dan dampak.
- Hubungan data publikasi yang sudah ada dengan dosen, tema unggulan, dan program studi.

Nilai tambah:

- Kriteria ini tidak cukup dipenuhi oleh daftar publikasi saja; asesor biasanya mencari relevansi, distribusi SDM, dan pembinaan berkelanjutan.

### 6. Sarana, Prasarana Pendidikan, dan Keuangan

Fitur yang disarankan:

- Inventaris fasilitas pembelajaran, laboratorium, perpustakaan, ruang praktik, dan sarana pendukung per unit.
- Direktori wahana praktik atau mitra layanan kesehatan lengkap dengan kapasitas, jenis layanan, dan status kerja sama.
- Ringkasan sumber belajar digital dan fisik.
- Dashboard ringkas pembiayaan pendidikan yang aman untuk dipublikasikan sesuai level akses.
- Status kelayakan fasilitas dan jadwal pemutakhiran data.

Nilai tambah:

- Asesor melihat kecukupan sumber daya untuk menopang proses pembelajaran, bukan hanya foto fasilitas.

### 7. Penjaminan Mutu

Fitur yang disarankan:

- Ruang SPMI yang menyimpan kebijakan, manual, standar, formulir, audit, dan siklus PPEPP.
- Workflow AMI: temuan, akar masalah, rencana tindak lanjut, PIC, tenggat, status, dan eviden penyelesaian.
- Pengingat masa berlaku dokumen dan review berkala.
- Dashboard kelengkapan bukti akreditasi per kriteria dan subkriteria.
- Audit trail perubahan dokumen penting.

Nilai tambah:

- Ini adalah area yang paling sering menjadi pembeda antara website yang hanya informatif dan website yang benar-benar siap asesmen.

### 8. Tata Kelola dan Administrasi

Fitur yang disarankan:

- Repositori tata kelola: struktur organisasi, SK tim, komite, jobdesk, SOP administrasi, dan peta proses bisnis.
- Arsip rapat strategis dan tindak lanjut keputusan penting.
- Matriks kewenangan unit dan PIC per proses.
- Dashboard kepatuhan administrasi: dokumen wajib, masa berlaku, status pembaruan.
- Ruang visitasi asesor yang berisi shortcut ke dokumen inti, PIC, jadwal, dan paket unduh per kriteria.

Nilai tambah:

- Membuat asesmen lapangan lebih tertata dan mengurangi pencarian dokumen manual.

## Backlog Prioritas yang Paling Aman

### Prioritas 0: Tanpa Gangguan ke Pengguna Publik

Fase ini sepenuhnya admin-only dan tidak menyentuh alur publik yang sedang dipakai.

1. Modul ruang eviden akreditasi.
2. Dashboard kelengkapan eviden per kriteria.
3. Metadata dokumen: kategori, subkriteria, PIC, status verifikasi, masa berlaku, sumber, dan relasi unit.
4. Paket unduh eviden per kriteria.
5. Reminder review dokumen dan notifikasi masa berlaku.

### Prioritas 1: Low Risk untuk Publik

Fase ini menambah konten publik yang memang sudah dibutuhkan asesor, tetapi tidak mengubah flow inti pengguna.

1. Ubah halaman akreditasi menjadi data-driven dan tampilkan SK, masa berlaku, serta status prodi.
2. Isi route placeholder akademik, penelitian, dan kerja sama dengan halaman berbasis data yang dikelola admin.
3. Perluas pojok alumni menjadi tracer study yang terstruktur.
4. Tampilkan ringkasan hasil survei dan tindak lanjut mutu yang aman untuk publik.

### Prioritas 2: Modul Data Mutu Menengah

1. Engine survei kepuasan dan tracer study.
2. Repositori kerja sama lengkap dengan MoU, MoA, IA, dan luaran.
3. Direktori dosen, tendik, dan pembimbing klinik.
4. Dashboard indikator lulusan, mahasiswa, penelitian, dan pengabdian.

### Prioritas 3: Modul Transformasional

1. Workflow AMI dan corrective action management.
2. Peta kurikulum OBE dan CPL ke CPMK ke asesmen.
3. Integrasi knowledge search untuk pencarian eviden cepat saat visitasi.
4. Portal khusus asesor untuk asesmen lapangan.

## Reuse Komponen yang Sudah Ada

Supaya implementasi cepat dan aman, modul berikut sebaiknya dipakai ulang:

- `dokumen_mutu` sebagai basis awal klasifikasi dokumen mutu dan akreditasi.
- `halaman_statis` untuk menghidupkan halaman strategis seperti akreditasi, penelitian, kerja sama, dan penerimaan.
- `publikasi` untuk dasar dashboard penelitian dan pengabdian.
- `testimoni_alumni` sebagai pondasi awal tracer study, meski perlu diperluas model dan formulirnya.
- `knowledge_documents` untuk indeks dokumen dan pencarian eviden saat persiapan visitasi.
- `unit_pages` dan section builder untuk halaman prodi atau unit yang membutuhkan eviden naratif dan visual.

## Rekomendasi Role dan Otorisasi

Role yang sudah ada dapat langsung dipakai:

- `pusat_mutu` sebagai owner ruang eviden, SPMI, dan dashboard akreditasi.
- `wadir_1` untuk area akademik, mahasiswa, dan prodi.
- `pusat_litmas` untuk penelitian, pengabdian, dan luaran.
- `pusat_p3` untuk kurikulum dan pengembangan pembelajaran.
- `unit_humas_kerjasama` untuk halaman publik kerja sama dan publikasi tertentu.
- `kaprodi` untuk eviden program studi, kurikulum, tracer, dan data mahasiswa.
- `spi` untuk akses baca terhadap evidence trail dan kepatuhan internal.

Jika modul akreditasi dibangun, sebaiknya menu baru dibagi menjadi:

- `akreditasi.evidence`
- `akreditasi.dashboard`
- `akreditasi.surveys`
- `akreditasi.partnerships`
- `akreditasi.staff-profiles`
- `akreditasi.qa`

## Rancangan Data Minimal

Untuk menjaga rollout tetap aman, gunakan tabel baru seperti berikut:

- `accreditation_folders`
- `accreditation_items`
- `accreditation_evidences`
- `accreditation_evidence_links`
- `survey_templates`
- `survey_questions`
- `survey_responses`
- `graduate_outcome_snapshots`
- `partnership_agreements`
- `staff_profiles`
- `staff_certifications`
- `quality_audits`
- `quality_findings`
- `corrective_actions`

Dengan pendekatan ini, tabel lama tetap stabil dan modul baru bisa diluncurkan bertahap.

## Sprint yang Direkomendasikan

### Sprint 1

- Bangun modul admin `Ruang Eviden Akreditasi`.
- Tambahkan struktur folder per kriteria dan status kelengkapan.
- Tambahkan pencarian dokumen, tag subkriteria, dan masa berlaku.
- Buat paket unduh per kriteria.

### Sprint 2

- Hidupkan halaman akademik, penelitian, dan kerja sama menggunakan data yang sudah bisa diinput admin.
- Perbaiki halaman akreditasi agar data-driven.
- Tambahkan ringkasan indikator utama per prodi.

### Sprint 3

- Kembangkan tracer study dan survei kepuasan.
- Tambahkan dashboard hasil dan tindak lanjut.
- Tambahkan direktori dosen, tendik, dan pembimbing klinik.

### Sprint 4

- Bangun workflow AMI dan CAPA.
- Tambahkan portal visitasi asesor.
- Integrasikan pencarian knowledge document untuk eviden visitasi.

## Keputusan Praktis

Jika hanya satu modul yang boleh dikerjakan lebih dulu, pilih `Ruang Eviden Akreditasi`.

Alasannya:

- paling kuat dampaknya ke kesiapan asesor,
- paling aman karena dapat dibuat admin-only,
- bisa memakai role dan pola dokumen yang sudah ada,
- menjadi fondasi untuk dashboard, visitasi, dan SPMI,
- tidak mengganggu pengguna publik yang sedang aktif.

## Ringkasan

Website Poltekkes Bandung sudah memiliki fondasi konten institusi yang baik, tetapi untuk kebutuhan akreditasi asesor masih perlu bertransformasi dari situs informasi menjadi sistem eviden mutu. Prioritas terbaik dan paling aman adalah membangun lapisan admin khusus akreditasi, lalu bertahap memperkaya halaman publik dengan data yang sudah tervalidasi.
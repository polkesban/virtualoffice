# Aktivasi Aman Ruang Eviden Akreditasi

Dokumen ini menjelaskan cara mengaktifkan modul `Ruang Eviden Akreditasi` dengan risiko serendah mungkin terhadap pengguna yang sedang memakai website.

## Kondisi Saat Ini

- Kode modul sudah tersedia di aplikasi.
- Route admin sudah terdaftar.
- Menu sidebar tidak akan muncul sebelum tabel `accreditation_folders` dan `accreditation_evidences` tersedia.
- Tidak ada route publik yang berubah.
- Tidak ada migrasi live yang sudah dijalankan dari sesi kerja ini.

Artinya, deploy kode saja belum mengubah perilaku website untuk pengguna umum.

## Status Verifikasi Terakhir

- Jalur test SQLite untuk modul akreditasi sudah kompatibel dan `pdo_sqlite` aktif di PHP CLI.
- Full test suite Laravel lulus penuh pada 21 April 2026: `53 passed`, `231 assertions`.
- Feature test `AccreditationEvidenceAdminTest` lulus penuh: `16 passed`, `72 assertions`.
- Smoke check read-only pada produksi menunjukkan:
  - beranda publik merender normal,
  - halaman LAKIP `bidang-administrasi-umum/lakip` merender normal,
  - halaman admin login merespons `200`,
  - route admin eviden akreditasi mengarah ke login saat anonim, sesuai proteksi akses.
- Route publik form pesanan Unit Bisnis yang benar adalah `/layanan-bisnis/pesan`, bukan `/layanan-bisnis/order`.

## Target Aktivasi

Target aktivasi paling aman adalah:

1. Membuat tabel modul baru.
2. Mengaktifkan menu admin secara otomatis lewat guard berbasis schema.
3. Melakukan backfill awal dari Dokumen Mutu secara manual dan terkontrol.
4. Verifikasi akses admin tanpa menyentuh alur publik.

## Waktu yang Disarankan

Lakukan aktivasi pada jam sepi atau maintenance window ringan, karena meskipun perubahan ini kecil, migrasi database tetap merupakan operasi live.

## Checklist Pra-Aktivasi

1. Pastikan backup database terbaru tersedia.
2. Pastikan tidak ada deployment lain yang berjalan bersamaan.
3. Pastikan akun admin `pusat_mutu` atau `super_admin` tersedia untuk verifikasi UI.
4. Pastikan storage lokal server memiliki ruang cukup untuk file eviden privat.

## Langkah Aktivasi Aman

### 1. Jalankan migrasi modul

Gunakan path spesifik agar hanya dua migration modul akreditasi ini yang dieksekusi.

```bash
php artisan migrate \
  --path=database/migrations/2026_04_21_140000_create_accreditation_evidence_tables.php \
  --path=database/migrations/2026_04_21_141000_add_eviden_akreditasi_menu_to_roles_and_nav.php \
  --path=database/migrations/2026_04_21_150000_create_accreditation_evidence_annotations_table.php
```

Catatan:

- Migration ini hanya membuat tabel baru dan menambah satu key menu baru.
- Tidak memodifikasi tabel publik yang dipakai pengunjung website.
- Fitur bookmark dan anotasi reviewer baru aktif setelah migration anotasi dijalankan.
- Bookmark reviewer kini memiliki status tindak lanjut: belum, proses, dan selesai.

### 2. Lakukan dry-run backfill dari Dokumen Mutu

Perintah ini aman karena tidak menulis data.

```bash
php artisan akreditasi:bootstrap-evidence --dry-run
```

Default command:

- menyinkronkan 8 folder default,
- memakai folder `7` sebagai tujuan import awal Dokumen Mutu,
- hanya membaca Dokumen Mutu aktif,
- melewati dokumen yang sudah tertaut,
- melewati dokumen yang belum punya `file_url`.

### 3. Jalankan backfill jika preview sudah sesuai

```bash
php artisan akreditasi:bootstrap-evidence
```

Contoh variasi:

```bash
php artisan akreditasi:bootstrap-evidence --folder-code=8 --status=ready
php artisan akreditasi:bootstrap-evidence --include-inactive --dry-run
```

## Verifikasi Pasca-Aktivasi

### Verifikasi route admin

```bash
php artisan route:list --name=eviden-akreditasi
```

### Verifikasi command tersedia

```bash
php artisan list | grep akreditasi
```

### Verifikasi UI admin

1. Login sebagai `super_admin` atau `pusat_mutu`.
2. Pastikan menu `Ruang Eviden Akreditasi` muncul di sidebar admin.
3. Buka daftar eviden.
4. Pastikan folder default 1 sampai 8 tampil.
5. Coba buka satu eviden hasil bootstrap jika ada.
6. Jika eviden memiliki file privat, pastikan halaman detail eviden dapat menampilkan pratinjau isi file dari panel admin.
7. Jika file berupa PDF digital atau file teks, pastikan panel `Teks Terbaca` muncul untuk membantu review cepat tanpa keluar dari admin.
8. Pastikan section `Bookmark & Anotasi Reviewer` muncul di halaman detail eviden dan Anda bisa menambah satu anotasi percobaan.
9. Ubah status anotasi percobaan ke `Sedang Ditindaklanjuti` atau `Selesai` untuk memastikan alur reviewer notes berjalan.
10. Kembali ke dashboard `Ruang Eviden Akreditasi` dan pastikan kartu `Tindak Lanjut Catatan Reviewer` serta filter `Catatan Reviewer` muncul bila migration anotasi sudah aktif.
11. Gunakan tombol `Ekspor Catatan CSV` dan `Ekspor Catatan XLSX` untuk memastikan daftar tindak lanjut reviewer bisa diunduh sesuai filter dashboard yang sedang aktif.
12. Buka dashboard admin utama dan pastikan kartu `Tindak Lanjut Akreditasi` muncul hanya pada akun yang memang punya akses ke ruang eviden.
13. Di kartu dashboard admin utama, pastikan daftar `Kriteria Prioritas` menampilkan folder yang paling perlu dibenahi dan tautannya membuka filter folder yang sesuai.
14. Periksa sidebar admin dan pastikan menu `Ruang Eviden Akreditasi` menampilkan badge jumlah catatan reviewer aktif bila masih ada tindak lanjut terbuka.
15. Di ruang eviden, uji filter `Sumber Eviden` untuk memastikan item `Tanpa sumber` bisa dipisahkan dari eviden yang sudah memiliki file, tautan dokumen mutu, atau URL eksternal.
16. Uji filter `PIC` di ruang eviden dan pastikan kartu `PIC Prioritas` di dashboard utama mengarah ke daftar eviden penanggung jawab yang sesuai.
17. Dari dashboard admin utama, uji quick link `Kadaluarsa` dan `Berlaku Habis ≤ 30 Hari` untuk memastikan keduanya membuka daftar eviden sesuai masa berlaku dokumen.
18. Uji filter `Review lewat jatuh tempo` di ruang eviden dan quick link `Review Lewat` di dashboard utama untuk memastikan backlog review terpisah dari item yang masih upcoming.
19. Dengan kombinasi filter `PIC` dan `Review lewat jatuh tempo`, uji tombol `Ekspor Eviden CSV/XLSX` untuk memastikan backlog review per PIC bisa dibagikan langsung.
20. Bila ada backlog `Review Lewat`, uji tombol ekspor cepat CSV/XLSX pada kartu dashboard admin utama dan pastikan file yang dihasilkan hanya berisi antrean review yang sudah lewat jatuh tempo.
21. Pada kartu `PIC Prioritas`, uji aksi `Lihat`, `CSV`, dan `XLSX` untuk PIC yang memiliki backlog `Review Lewat` dan pastikan semua aksi mempertahankan filter PIC yang benar.
22. Periksa kartu `Kriteria Prioritas` dan `PIC Prioritas` lalu pastikan chip `Review Lewat` terpisah dari chip `Review/berlaku dekat`, sehingga backlog terlambat tidak tercampur dengan alert review atau masa berlaku yang masih upcoming.
23. Periksa kartu prioritas dan pastikan chip `Review ≤ 30 hari` terpisah dari chip `Berlaku habis/kadaluarsa`, sehingga sumber urgensi bisa dibaca tanpa membuka tabel eviden.
24. Pada kartu `Kriteria Prioritas`, uji aksi cepat `Review Lewat`, `Review ≤ 30 hari`, dan `Berlaku` lalu pastikan masing-masing membuka antrean terfilter untuk kriteria yang benar. Filter `Perhatian` di ruang eviden juga harus menerima opsi gabungan `Berlaku habis/kadaluarsa`.

### Smoke check read-only yang sudah terverifikasi

Checklist ini aman dijalankan kapan saja karena tidak menulis data.

Alternatif cepat:

```bash
./scripts/smoke-check-public.sh https://polkesban.ac.id
```

1. Buka `/` dan pastikan blok `Berita Terbaru`, `Pengumuman`, `Mahasiswa Berprestasi`, dan `Program Studi` muncul.
2. Buka `/unit/bidang-administrasi-umum/lakip` dan pastikan marker `LAKIP 2025`, `Ringkasan Eksekutif`, dan `22 IKU` terlihat.
3. Buka `/layanan-bisnis/pesan` dan pastikan marker `Form Pemesanan Layanan & Produk`, `Data Pemesan`, dan `Item Pesanan` terlihat.
4. Buka `/admin/login` dan pastikan halaman login admin merespons normal.
5. Buka `/admin/eviden-akreditasi` tanpa login dan pastikan browser diarahkan ke `/admin/login`, bukan error page.

## OCR PDF Scan Bersifat Opsional

Jika server nantinya memiliki utilitas `tesseract` dan `pdftoppm`, halaman detail eviden akan otomatis mencoba OCR untuk PDF scan yang tidak memiliki lapisan teks.

Jika utilitas itu belum tersedia, modul tetap aman:

1. Tidak ada error ke pengguna admin.
2. Panel `Teks Terbaca` hanya menampilkan pesan bahwa OCR opsional belum tersedia.
3. Pratinjau file privat dan unduhan tetap berjalan seperti biasa.

## Jika Sidebar Belum Memperlihatkan Menu

Dalam kondisi normal, menu akan muncul otomatis setelah migration selesai karena layout memeriksa keberadaan tabel secara langsung.

Jika menu masih belum muncul:

1. Logout lalu login kembali ke admin.
2. Verifikasi migration memang sukses.
3. Verifikasi role `pusat_mutu`, `wadir_1`, `wadir_2`, `wadir_3`, `kaprodi`, atau `spi` sudah memuat menu `eviden-akreditasi` di tabel `roles`.

Reload `php-fpm` tidak perlu dijadikan langkah default. Pertimbangkan hanya jika ada indikasi opcache lama masih tertahan, dan lakukan itu di jam sepi.

## Perilaku Aman yang Sudah Disiapkan di Kode

- Menu modul disembunyikan jika tabel belum ada.
- Controller modul mengembalikan `503` jika diakses sebelum migration selesai.
- File eviden privat disimpan di disk lokal aplikasi, bukan storage publik.
- Hak akses dibatasi ke `super_admin` atau role yang memiliki menu `eviden-akreditasi`.

## Langkah Lanjutan yang Aman

Setelah aktivasi berhasil, prioritas berikutnya yang masih rendah risiko adalah:

1. Menambah filter dashboard kelengkapan per kriteria.
2. Menambah paket unduh eviden per folder.
3. Membuat impor terarah dari Dokumen Mutu ke folder lain selain folder `7`.
4. Menambahkan indikator review jatuh tempo ke dashboard admin.
# Ringkasan Deploy dan Verifikasi - 21 April 2026

Dokumen ini merangkum perubahan penting, status verifikasi, dan catatan operasional dari pekerjaan sesi 21 April 2026.

## Ringkasan Singkat

- Jalur test Laravel kini hijau penuh pada environment kerja.
- Kompatibilitas SQLite untuk feature test sudah dibuka dan tervalidasi.
- Fitur admin Ruang Eviden Akreditasi yang dikerjakan pada sesi ini tetap aman untuk publik dan masih dormant sampai migrasi modul dijalankan live.
- Smoke check read-only pada produksi menunjukkan halaman publik utama tetap normal.

## Perubahan Utama

### 1. Stabilitas dan kompatibilitas test

- `pdo_sqlite` untuk PHP CLI sudah tersedia dan aktif.
- Sejumlah migration lama yang bergantung pada perilaku MySQL atau tabel legacy sudah dibuat aman untuk SQLite test path.
- Ditambahkan migration dasar `unit_pages` agar jalur test untuk fitur event peserta dan halaman unit dapat berjalan konsisten.
- Komponen publik tertentu dibuat fail-closed pada database test yang belum memiliki tabel konten opsional, sehingga halaman tetap bisa dirender tanpa error.

### 2. Penyempurnaan modul Ruang Eviden Akreditasi

- Review yang sudah lewat jatuh tempo dipisahkan dari review `<= 30 hari`.
- Daftar eviden bisa diekspor ke CSV dan XLSX sesuai filter aktif.
- Dashboard admin utama memiliki quick link dan ekspor cepat untuk backlog review lewat jatuh tempo.
- Kartu prioritas folder dan PIC kini memisahkan sumber urgensi antara review lewat, review dekat, dan masa berlaku dokumen.
- Halaman detail eviden mendukung pratinjau teks, pencarian dalam teks, serta anotasi reviewer dengan status tindak lanjut.

### 3. Penyesuaian minor yang tervalidasi

- Setup test LAKIP administrasi umum sudah diselaraskan dengan seed `unit_pages`.
- Assertion test untuk order layanan bisnis diselaraskan dengan teks hasil render HTML.
- Route publik form pesanan Unit Bisnis yang benar dipastikan tetap `/layanan-bisnis/pesan`.

## Status Verifikasi

### Test suite

- `php artisan test` lulus penuh.
- Hasil terakhir: `53 passed`, `231 assertions`.

### Slice penting yang tervalidasi

- `Tests\\Feature\\AccreditationEvidenceAdminTest`: lulus penuh (`16 passed`, `72 assertions`).
- `Tests\\Feature\\UnitEventParticipantImportTest`: lulus penuh.
- `Tests\\Feature\\LoginCaptchaTest`: lulus penuh.
- `Tests\\Feature\\LakipAdministrasiUmumPageTest`: lulus penuh.
- `Tests\\Feature\\LayananBisnisOrderTest`: lulus penuh.

### Smoke check read-only produksi

Terverifikasi tanpa menulis data:

- `/` merender normal.
- `/unit/bidang-administrasi-umum/lakip` merender normal.
- `/layanan-bisnis/pesan` merespons `200` dan menampilkan marker form pemesanan.
- `/admin/login` merespons `200`.
- `/admin/eviden-akreditasi` saat anonim diarahkan ke login admin, sesuai proteksi akses.

Smoke check di atas sekarang bisa diulang dengan skrip:

```bash
./scripts/smoke-check-public.sh https://polkesban.ac.id
```

## Dampak ke Pengguna

- Tidak ada aktivasi live migration modul akreditasi yang dijalankan dari sesi ini.
- Tidak ada perubahan yang sengaja membuka route admin ke publik.
- Tidak ada gangguan yang terdeteksi pada beranda, halaman LAKIP, atau halaman pesanan Unit Bisnis pada smoke check produksi.

## Catatan Operasional

- Deploy kode saja tetap aman untuk pengguna umum karena modul eviden akreditasi bergantung pada guard schema dan migrasi aktivasi terpisah.
- Jika ingin mengaktifkan modul eviden akreditasi secara live, gunakan runbook di `docs/aktivasi-ruang-eviden-akreditasi.md`.
- Jika ingin melihat arah fitur jangka menengah, gunakan `docs/roadmap-akreditasi-lamptkes.md`.

## Pending yang Masih Relevan

- Smoke test admin-authenticated untuk Ruang Eviden Akreditasi belum dijalankan karena kredensial uji belum dipakai dalam sesi ini.
- Aktivasi live modul eviden akreditasi masih menunggu window operasional yang sesuai.

## Rekomendasi Lanjut

1. Jika hanya deploy kode, lanjutkan deploy normal lalu lakukan smoke check read-only yang sama.
2. Jika ingin mengaktifkan modul eviden, jalankan langkah pada runbook aktivasi di jam sepi.
3. Jika kredensial admin uji tersedia, lakukan smoke test login untuk memverifikasi menu, filter, ekspor, dan detail eviden langsung dari UI.
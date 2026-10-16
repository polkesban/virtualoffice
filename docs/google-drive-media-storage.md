# Google Drive Media Storage

Panduan ini memindahkan media upload persisten aplikasi ke Google Drive agar storage VPS tidak cepat penuh. Integrasi memakai `google/apiclient` dan refresh token dari OAuth Playground.

## Cakupan

- Media publik: image, video, file lampiran, flyer, cover, portofolio, galeri, bukti pembayaran, file page builder, dan aset upload sejenis.
- Media privat: knowledge base dan eviden akreditasi dapat ikut memakai Google Drive tanpa perlu dibagikan publik, karena aplikasi tetap bisa membaca file lewat API.
- File sementara AI dan transkripsi tetap lokal karena sifatnya sementara dan tidak dimaksudkan sebagai penyimpanan permanen.

## 1. Buat kredensial OAuth di Google Cloud

1. Buka Google Cloud Console.
2. Buat project baru atau pakai project yang sudah ada.
3. Aktifkan Google Drive API.
4. Buka menu Credentials.
5. Buat OAuth Client ID tipe Web application.
6. Isi Authorized redirect URI dengan `https://developers.google.com/oauthplayground`.
7. Simpan `client_id` dan `client_secret`.

## 2. Ambil refresh token via OAuth Playground

1. Buka OAuth Playground.
2. Klik ikon gear di kanan atas, lalu centang `Use your own OAuth credentials`.
3. Masukkan `client_id` dan `client_secret` dari Google Cloud.
4. Pada daftar scope, tambahkan scope berikut:

```text
https://www.googleapis.com/auth/drive
```

5. Klik `Authorize APIs`.
6. Login dengan akun Google Drive yang akan menjadi tempat penyimpanan media aplikasi.
7. Klik `Exchange authorization code for tokens`.
8. Salin nilai `refresh_token`.

## 3. Siapkan folder induk di Google Drive

1. Buat folder khusus, misalnya `Poltekkes Bandung Media Storage`.
2. Buka folder itu dan ambil ID folder dari URL.

Contoh:

```text
https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUvWxYz
```

ID folder adalah bagian setelah `/folders/`.

## 4. Isi environment aplikasi

Tambahkan variabel berikut ke `.env` produksi:

```dotenv
MEDIA_STORAGE_DISK=google_drive
MEDIA_STORAGE_LOCAL_DISK=public

GOOGLE_DRIVE_APPLICATION_NAME="Poltekkes Bandung Media Storage"
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REFRESH_TOKEN=
GOOGLE_DRIVE_FOLDER_ID=
GOOGLE_DRIVE_SHARE_PUBLICLY=true
```

Catatan:

- Gunakan `MEDIA_STORAGE_DISK=public` jika ingin rollback cepat ke local storage.
- `GOOGLE_DRIVE_SHARE_PUBLICLY=true` diperlukan untuk media yang harus tampil langsung di website publik.
- Untuk modul privat, aplikasi tetap bisa mengakses file lewat API meski file tidak dibuka publik.

## 5. Refresh config aplikasi

Jalankan sebagai `www-data`:

```bash
runuser -u www-data -- php artisan config:clear
runuser -u www-data -- php artisan cache:clear
runuser -u www-data -- php artisan view:clear
```

## 6. Uji upload

Uji minimal dari modul berikut:

1. Upload cover berita.
2. Upload lampiran pengumuman.
3. Upload gambar galeri.
4. Upload satu foto mahasiswa atau portofolio.
5. Upload file page builder Google Drive atau materi event.

Jika berhasil, nilai file yang tersimpan di database akan berbentuk referensi `gdrive:FILE_ID|nama-file.ext`.

## 7. Catatan operasional

- File lama yang masih tersimpan lokal tetap bisa dibaca karena helper media mendukung path lama dan referensi Google Drive sekaligus.
- Upload baru sesudah konfigurasi aktif akan langsung masuk ke Google Drive.
- Jika ingin migrasi file lama, buat batch migrasi terpisah agar file lama diunggah ulang ke Google Drive lalu nilai path di database diperbarui.
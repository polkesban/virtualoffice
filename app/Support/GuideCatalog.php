<?php

namespace App\Support;

use Illuminate\Support\Str;

class GuideCatalog
{
    public function current(?string $routeName): array
    {
        $routeName = $routeName ?: 'unknown';

        foreach ($this->guides() as $guide) {
            foreach ($guide['patterns'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $this->normalize($guide);
                }
            }
        }

        return $this->fallbackFor($routeName);
    }

    public function groupsForCenter(): array
    {
        $groups = [];

        foreach ($this->groupMeta() as $groupKey => $meta) {
            $guides = [];

            foreach ($this->guides() as $guide) {
                if ($guide['group'] !== $groupKey) {
                    continue;
                }

                $guides[] = $this->normalize($guide);
            }

            if ($guides === []) {
                continue;
            }

            $groups[] = array_merge($meta, [
                'key' => $groupKey,
                'guides' => $guides,
            ]);
        }

        return $groups;
    }

    public function stats(): array
    {
        $guides = $this->guides();
        $featureCount = 0;
        $patternCount = 0;

        foreach ($guides as $guide) {
            $featureCount += count($guide['features'] ?? []);
            $patternCount += count($guide['patterns'] ?? []);
        }

        return [
            'guide_count' => count($guides),
            'feature_count' => $featureCount,
            'pattern_count' => $patternCount,
            'group_count' => count($this->groupMeta()),
        ];
    }

    private function fallbackFor(string $routeName): array
    {
        $fallbackKey = 'public.general';

        if (Str::startsWith($routeName, 'admin.')) {
            $fallbackKey = 'admin.general';
        } elseif (Str::startsWith($routeName, 'mahasiswa.')) {
            $fallbackKey = 'portal.general';
        }

        foreach ($this->guides() as $guide) {
            if ($guide['key'] === $fallbackKey) {
                return $this->normalize($guide);
            }
        }

        return $this->normalize($this->guides()[0]);
    }

    private function normalize(array $guide): array
    {
        $meta = $this->groupMeta()[$guide['group']] ?? [];

        return array_merge($guide, $meta, [
            'steps' => array_values($guide['steps'] ?? []),
            'features' => array_values($guide['features'] ?? []),
            'tips' => array_values($guide['tips'] ?? []),
            'patterns' => array_values($guide['patterns'] ?? []),
        ]);
    }

    private function groupMeta(): array
    {
        return [
            'public' => [
                'group_label' => 'Website Publik',
                'group_icon' => '🌐',
                'group_description' => 'Panduan untuk pengunjung, calon mahasiswa, alumni, dan mitra kampus.',
                'badge_class' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            ],
            'portal' => [
                'group_label' => 'Portal Mahasiswa',
                'group_icon' => '🎓',
                'group_description' => 'Panduan registrasi, login, profil, dan pengelolaan portofolio mahasiswa.',
                'badge_class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            ],
            'admin' => [
                'group_label' => 'Admin Konten',
                'group_icon' => '🛠️',
                'group_description' => 'Panduan operasional untuk pengelola konten, halaman, media, layanan, dan AI.',
                'badge_class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            ],
            'super_admin' => [
                'group_label' => 'Super Admin',
                'group_icon' => '👑',
                'group_description' => 'Kontrol penuh konfigurasi sistem, kredensial, akun, dan tata kelola fitur lintas modul.',
                'badge_class' => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300',
            ],
        ];
    }

    private function guides(): array
    {
        return [
            [
                'key' => 'public.general',
                'group' => 'public',
                'title' => 'Panduan Halaman Publik',
                'summary' => 'Gunakan menu utama untuk berpindah antar informasi kampus, manfaatkan tombol aksi yang terlihat jelas, dan perhatikan kartu atau blok interaktif di setiap halaman.',
                'patterns' => [],
                'features' => [
                    'Navigasi publik menggunakan menu utama dan footer.',
                    'Sebagian halaman memiliki blok interaktif, CTA, atau formulir singkat.',
                    'Tema terang dan gelap bisa berubah mengikuti preferensi pengguna.',
                ],
                'steps' => [
                    'Mulai dari menu utama untuk memilih kategori informasi kampus.',
                    'Gunakan tombol ajakan seperti daftar, lihat detail, atau hubungi kami untuk aksi cepat.',
                    'Jika halaman memuat daftar konten, buka item untuk melihat informasi lengkap.',
                ],
                'tips' => [
                    'Pada perangkat mobile, gulir sampai bagian bawah untuk menemukan tautan tambahan di footer.',
                    'Cermati badge, label, dan heading besar karena itu menandai blok informasi utama.',
                ],
            ],
            [
                'key' => 'public.home',
                'group' => 'public',
                'title' => 'Panduan Beranda',
                'summary' => 'Beranda merangkum jalur tercepat menuju program studi, berita, layanan, aktivitas kampus, dan CTA penerimaan mahasiswa baru.',
                'patterns' => ['home'],
                'features' => [
                    'Hero video dan CTA penerimaan di bagian atas.',
                    'Statistik kampus, mode tampilan, dan rekomendasi jurusan interaktif.',
                    'Galeri Aktivitas Kampus mendukung popup gambar penuh saat foto diklik.',
                ],
                'steps' => [
                    'Gunakan kartu besar di bagian atas untuk masuk ke informasi atau aksi yang paling sering dicari.',
                    'Klik foto pada bagian Aktivitas Kampus untuk melihat popup ukuran besar.',
                    'Lanjutkan ke blok program studi, berita, atau layanan untuk eksplorasi lebih dalam.',
                ],
                'tips' => [
                    'Beranda dirancang seperti dashboard informasi, jadi scroll dari atas ke bawah untuk melihat semua blok penting.',
                    'Jika ingin kembali cepat ke bagian utama, gunakan menu atas daripada mengandalkan tombol back browser.',
                ],
            ],
            [
                'key' => 'public.catalog',
                'group' => 'public',
                'title' => 'Panduan Katalog Konten Publik',
                'summary' => 'Halaman daftar seperti program studi, berita, pengumuman, galeri, agenda, layanan, dan unit dibuat untuk membantu pengguna membandingkan konten sebelum membuka detail.',
                'patterns' => [
                    'program-studi*', 'berita*', 'pengumuman*', 'galeri', 'agenda', 'berprestasi', 'layanan-bisnis*',
                    'layanan-lab*', 'fasilitas-asrama*', 'koleksi-perpus*', 'publikasi*', 'dokumen-mutu*',
                    'layanan-ti*', 'pelatihan*', 'unit.*', 'portal-mahasiswa*', 'pojok-alumni*', 'profil*',
                    'akademik*', 'penelitian', 'kerjasama', 'kebijakan-privasi', 'syarat-ketentuan'
                ],
                'features' => [
                    'Daftar kartu atau tabel untuk menelusuri konten terkelola.',
                    'Halaman detail memuat deskripsi lengkap, media, dan lampiran relevan.',
                    'Beberapa halaman publik berasal dari page builder dan bisa berubah dinamis sesuai update admin.',
                ],
                'steps' => [
                    'Pilih item dari daftar yang paling relevan dengan kebutuhan Anda.',
                    'Buka halaman detail untuk membaca deskripsi lengkap atau melihat media pendukung.',
                    'Gunakan tautan aksi di dalam detail untuk mendaftar, menghubungi, atau mengunduh dokumen bila tersedia.',
                ],
                'tips' => [
                    'Untuk pencarian cepat, gunakan fitur pencarian browser di halaman yang panjang.',
                    'Pada halaman layanan dan unit, cek bagian CTA di tengah atau akhir halaman untuk tindak lanjut.',
                ],
            ],
            [
                'key' => 'public.contact',
                'group' => 'public',
                'title' => 'Panduan Kontak dan Formulir Publik',
                'summary' => 'Formulir publik dipakai untuk mengirim pesan, berlangganan newsletter, atau memesan layanan bisnis. Pastikan data yang diisi valid agar pesan dapat diproses oleh admin.',
                'patterns' => ['kontak*', 'newsletter.*', 'layanan-bisnis.order*'],
                'features' => [
                    'Form kontak langsung ke admin.',
                    'Newsletter untuk pembaruan informasi kampus.',
                    'Form pemesanan layanan bisnis untuk permintaan layanan tertentu.',
                ],
                'steps' => [
                    'Isi nama, email, dan informasi inti yang diminta dengan lengkap.',
                    'Tulis pesan atau detail kebutuhan secara spesifik agar admin mudah menindaklanjuti.',
                    'Periksa kembali data sebelum menekan tombol kirim atau pesan.',
                ],
                'tips' => [
                    'Gunakan email aktif agar balasan admin bisa diterima.',
                    'Jika formulir terkait pemesanan layanan, sertakan konteks, jadwal, atau jumlah kebutuhan sedetail mungkin.',
                ],
            ],
            [
                'key' => 'portal.general',
                'group' => 'portal',
                'title' => 'Panduan Portal Mahasiswa',
                'summary' => 'Portal mahasiswa dipakai untuk login, mengelola profil, dan menyusun portofolio yang dapat dipakai untuk seleksi atau publikasi capaian mahasiswa.',
                'patterns' => [],
                'features' => [
                    'Login mahasiswa dengan NIM dan password.',
                    'Profil mahasiswa dan foto untuk identitas portal.',
                    'Portofolio prestasi, sertifikat, dan karya yang dapat ditampilkan.',
                ],
                'steps' => [
                    'Pastikan akun mahasiswa aktif sebelum mencoba masuk ke portal.',
                    'Lengkapi profil dasar terlebih dahulu setelah login.',
                    'Tambahkan portofolio secara bertahap dan tampilkan hanya item yang memang siap dipublikasi.',
                ],
                'tips' => [
                    'Gunakan judul portofolio yang singkat namun jelas agar mudah dibaca saat ditampilkan.',
                    'Perbarui profil dan foto secara berkala untuk menjaga identitas portal tetap akurat.',
                ],
            ],
            [
                'key' => 'portal.auth',
                'group' => 'portal',
                'title' => 'Panduan Login dan Registrasi Mahasiswa',
                'summary' => 'Halaman ini dipakai untuk membuat akun mahasiswa baru atau masuk ke portal yang sudah aktif. Data harus mengikuti data akademik agar admin mudah memverifikasi.',
                'patterns' => ['mahasiswa.login*', 'mahasiswa.register*', 'mahasiswa.password.*'],
                'features' => [
                    'Registrasi akun mahasiswa baru.',
                    'Login menggunakan NIM dan password.',
                    'Captcha sederhana untuk mengurangi login otomatis.',
                    'Reset password melalui tautan email jika lupa kredensial.',
                ],
                'steps' => [
                    'Saat mendaftar, isi NIM, prodi, angkatan, dan email sesuai data resmi.',
                    'Gunakan password yang kuat dan simpan dengan aman.',
                    'Jika lupa password, gunakan menu lupa password dan cek email Anda untuk tautan reset.',
                    'Setelah login berhasil, lanjutkan ke halaman profil atau portofolio.',
                ],
                'tips' => [
                    'Jika gagal login, cek kembali NIM, captcha, dan status aktivasi akun.',
                    'Gunakan email mahasiswa yang aktif untuk mempermudah komunikasi lanjutan.',
                ],
            ],
            [
                'key' => 'portal.workspace',
                'group' => 'portal',
                'title' => 'Panduan Area Kerja Mahasiswa',
                'summary' => 'Setelah login, mahasiswa dapat menambah portofolio, mengubah profil, dan mengelola data yang ingin dipakai untuk penilaian atau publikasi internal.',
                'patterns' => ['mahasiswa.portofolio.*', 'mahasiswa.profil.*'],
                'features' => [
                    'Tambah, edit, dan hapus portofolio.',
                    'Atur profil pribadi dan foto mahasiswa.',
                    'Kelola item yang ditampilkan agar hanya data terbaik yang muncul.',
                ],
                'steps' => [
                    'Masuk ke menu Portofolio Saya untuk melihat seluruh capaian yang sudah dimasukkan.',
                    'Gunakan Tambah Pencapaian untuk memasukkan karya, penghargaan, atau sertifikat baru.',
                    'Periksa kembali item yang akan ditampilkan ke publik sebelum menyimpan perubahan.',
                ],
                'tips' => [
                    'Unggah deskripsi yang ringkas namun konkret, termasuk tanggal dan peran Anda.',
                    'Pilih hanya capaian yang paling kuat untuk ditampilkan agar profil tidak terlalu penuh.',
                ],
            ],
            [
                'key' => 'admin.general',
                'group' => 'admin',
                'title' => 'Panduan Umum Admin',
                'summary' => 'Panel admin dipakai untuk mengelola seluruh konten website, halaman builder, media, data publik, dan modul operasional lain sesuai hak akses role masing-masing.',
                'patterns' => [],
                'features' => [
                    'Sidebar role-based untuk memfilter modul yang dapat diakses.',
                    'Flash message untuk konfirmasi simpan, update, atau error.',
                    'Banyak modul menggunakan editor rich text dan page builder berbasis blok.',
                ],
                'steps' => [
                    'Mulai dari sidebar kiri untuk memilih modul sesuai kebutuhan kerja.',
                    'Periksa pesan sukses atau error setelah menyimpan perubahan.',
                    'Jika bekerja di halaman builder, simpan per bagian dan cek hasil publik setelah update besar.',
                ],
                'tips' => [
                    'Gunakan preview publik setelah perubahan signifikan pada konten, layout, atau media.',
                    'Jaga konsistensi judul, ringkasan, dan media agar halaman publik tetap rapi.',
                ],
            ],
            [
                'key' => 'admin.auth',
                'group' => 'admin',
                'title' => 'Panduan Login Admin',
                'summary' => 'Halaman ini dipakai admin dan super admin untuk masuk ke panel pengelolaan website. Login memerlukan email, password, dan captcha sederhana.',
                'patterns' => ['admin.login*', 'admin.password.*'],
                'features' => [
                    'Autentikasi email dan password.',
                    'Captcha sederhana untuk membatasi percobaan otomatis.',
                    'Opsi ingat saya agar sesi admin bertahan lebih lama di perangkat pribadi.',
                    'Reset password admin melalui tautan yang dikirim ke email akun.',
                ],
                'steps' => [
                    'Masukkan email admin dan password yang benar.',
                    'Jika lupa password, buka menu lupa password dan gunakan tautan yang masuk ke email admin.',
                    'Isi captcha sesuai pertanyaan yang tampil.',
                    'Gunakan hanya perangkat yang aman jika mencentang Ingat saya.',
                ],
                'tips' => [
                    'Jika gagal login berulang, periksa captcha dan pastikan keyboard tidak mengubah karakter password.',
                    'Akun admin sebaiknya tidak dipakai bergantian antar pengguna.',
                ],
            ],
            [
                'key' => 'admin.content_engine',
                'group' => 'admin',
                'title' => 'Panduan Content Engine dan AI',
                'summary' => 'Modul AI dipakai untuk menghasilkan draft konten, menganalisis media, mengelola knowledge base, dan membantu page builder menyusun konten yang lebih cepat.',
                'patterns' => ['admin.content-engine.*', 'admin.knowledge.*'],
                'features' => [
                    'Generate draft konten berbasis prompt dan media.',
                    'Knowledge base untuk dokumen referensi yang bisa diindeks ulang.',
                    'Integrasi AI ke halaman statis, halaman unit, dan blok builder tertentu.',
                ],
                'steps' => [
                    'Unggah bahan atau media bila ingin AI menyesuaikan konteks konten.',
                    'Tinjau hasil draft AI sebelum dipublikasikan ke halaman publik.',
                    'Perbarui knowledge base jika referensi resmi kampus berubah.',
                ],
                'tips' => [
                    'Gunakan prompt yang spesifik agar hasil AI lebih stabil.',
                    'AI mempercepat drafting, tetapi validasi akhir tetap harus dilakukan admin.',
                ],
            ],
            [
                'key' => 'admin.api_keys',
                'group' => 'admin',
                'title' => 'Panduan API Keys dan Editor',
                'summary' => 'Halaman API key dipakai untuk mengelola key Gemini, OpenAI, OpenRouter, dan TinyMCE Cloud beserta fallback, pengujian koneksi, dan preview editor rich text.',
                'patterns' => ['admin.api-keys.*'],
                'features' => [
                    'Priority dan fallback key aktif.',
                    'Tombol uji koneksi per key.',
                    'Preview TinyMCE untuk memastikan editor cloud atau fallback lokal berjalan.',
                ],
                'steps' => [
                    'Tambahkan key dengan label dan priority yang jelas.',
                    'Gunakan tombol Uji untuk memverifikasi koneksi provider sebelum dipakai operasional.',
                    'Buka preview TinyMCE untuk memastikan editor dapat dipakai admin di form rich text.',
                ],
                'tips' => [
                    'Priority kecil dicoba lebih dulu, jadi gunakan urutan yang konsisten antar akun.',
                    'Jika key exhausted, reset hanya setelah quota benar-benar diisi ulang.',
                ],
            ],
            [
                'key' => 'admin.users',
                'group' => 'super_admin',
                'title' => 'Panduan Pengelola, Users, dan Kredensial',
                'summary' => 'Super admin memakai modul users untuk membuat akun, mengatur role, mencetak kredensial, dan melakukan login-as saat perlu membantu unit atau admin lain.',
                'patterns' => ['admin.users.*'],
                'features' => [
                    'Manajemen akun admin dan role.',
                    'Cetak tabel kredensial awal.',
                    'Fitur login-as untuk bantuan operasional terbatas.',
                ],
                'steps' => [
                    'Tentukan role pengguna sebelum membuat akun baru.',
                    'Gunakan login-as hanya untuk troubleshooting atau pendampingan operasional.',
                    'Kembalikan sesi ke super admin segera setelah tugas selesai.',
                ],
                'tips' => [
                    'Gunakan role sekecil mungkin sesuai kebutuhan kerja.',
                    'Dokumentasikan pemberian kredensial awal agar audit akses tetap jelas.',
                ],
            ],
            [
                'key' => 'admin_navigation',
                'group' => 'super_admin',
                'title' => 'Panduan Navigasi dan Konfigurasi Sidebar',
                'summary' => 'Super admin dapat mengatur menu publik dan sidebar admin agar urutan, label, ikon, dan visibilitas fitur tetap sesuai struktur organisasi kampus.',
                'patterns' => ['admin.nav-menus.*', 'admin.nav-admin.*'],
                'features' => [
                    'Kelola navigasi publik.',
                    'Atur urutan, label, dan ikon sidebar admin.',
                    'Sembunyikan menu tertentu tanpa menghapus datanya.',
                ],
                'steps' => [
                    'Ubah label dan ikon hanya jika memang ada keputusan penamaan resmi.',
                    'Cek hasil di website publik atau panel admin setelah mengubah urutan menu.',
                    'Sembunyikan menu dengan hati-hati agar role terkait tidak kehilangan akses penting.',
                ],
                'tips' => [
                    'Sebelum mengubah struktur besar, catat kondisi sebelumnya sebagai referensi rollback manual.',
                    'Jaga konsistensi antara nama modul publik dan nama unit di organisasi nyata.',
                ],
            ],
            [
                'key' => 'admin_pages',
                'group' => 'admin',
                'title' => 'Panduan Halaman Statis dan Halaman Unit',
                'summary' => 'Page builder digunakan untuk menyusun halaman statis dan halaman unit menggunakan blok-blok konten, AI draft, serta rich text editor untuk area narasi utama.',
                'patterns' => ['admin.halaman-statis.*', 'admin.halaman-unit.*'],
                'features' => [
                    'Page builder berbasis blok.',
                    'AI draft untuk blok teks dan beberapa blok terstruktur.',
                    'Rich text editor untuk konten naratif HTML.',
                ],
                'steps' => [
                    'Tentukan struktur halaman terlebih dahulu sebelum menambah banyak blok.',
                    'Gunakan AI draft untuk mempercepat pengisian awal, lalu edit manual sesuai kebutuhan.',
                    'Setelah menyimpan, cek hasil publik untuk memastikan urutan blok dan media sudah tepat.',
                ],
                'tips' => [
                    'Blok yang terlalu banyak dapat membuat halaman berat, jadi gabungkan bagian yang memang bisa disatukan.',
                    'Gunakan judul blok yang konsisten agar halaman mudah dipindahkan atau diaudit ulang.',
                ],
            ],
            [
                'key' => 'admin_editorial',
                'group' => 'admin',
                'title' => 'Panduan Editorial dan Konten Publik',
                'summary' => 'Modul berita, pengumuman, galeri, agenda, program studi, dan mahasiswa berprestasi dipakai untuk menjaga website publik tetap aktif, relevan, dan rapi.',
                'patterns' => ['admin.berita.*', 'admin.pengumuman.*', 'admin.galeri.*', 'admin.agenda.*', 'admin.program-studi.*', 'admin.berprestasi.*'],
                'features' => [
                    'CRUD konten editorial publik.',
                    'Editor rich text untuk narasi yang lebih panjang.',
                    'Kontrol status aktif, publikasi, media, dan metadata inti.',
                ],
                'steps' => [
                    'Isi judul, ringkasan, isi utama, dan media dengan kualitas yang konsisten.',
                    'Gunakan preview mental dari sudut pandang pengunjung: apakah informasi utama langsung terlihat.',
                    'Pastikan status aktif atau published sesuai jadwal tampil yang diinginkan.',
                ],
                'tips' => [
                    'Gunakan alt text yang jelas untuk gambar publik.',
                    'Untuk galeri dan agenda, prioritaskan materi terbaru atau yang paling representatif.',
                ],
            ],
            [
                'key' => 'admin_services',
                'group' => 'admin',
                'title' => 'Panduan Layanan, Unit, dan Data Khusus',
                'summary' => 'Kelompok modul ini mencakup layanan bisnis, laboratorium, asrama, koleksi perpustakaan, publikasi, dokumen mutu, layanan TI, dan pelatihan.',
                'patterns' => [
                    'admin.layanan-bisnis.*', 'admin.layanan-lab.*', 'admin.fasilitas-asrama.*', 'admin.koleksi-perpus.*',
                    'admin.publikasi.*', 'admin.dokumen-mutu.*', 'admin.layanan-ti.*', 'admin.pelatihan.*'
                ],
                'features' => [
                    'Kelola layanan operasional kampus dan informasi tarif atau detailnya.',
                    'Kelola dokumen, katalog, dan publikasi unit tertentu.',
                    'Tiap modul dapat memiliki struktur field dan media yang berbeda.',
                ],
                'steps' => [
                    'Pastikan Anda berada di modul unit yang tepat sebelum mengubah data.',
                    'Gunakan judul dan deskripsi yang mengikuti istilah resmi unit.',
                    'Periksa hasil publik jika modul memiliki halaman frontend yang aktif.',
                ],
                'tips' => [
                    'Dokumen dan layanan teknis cenderung sensitif terhadap detail, jadi periksa file, tautan, dan tarif sebelum publish.',
                    'Jangan hapus data lama jika masih dibutuhkan sebagai arsip kecuali memang ada kebijakan penghapusan.',
                ],
            ],
            [
                'key' => 'super_admin_guide_center',
                'group' => 'super_admin',
                'title' => 'Panduan Pusat Panduan Super Admin',
                'summary' => 'Halaman ini merangkum seluruh modul, fitur, cakupan route, dan langkah operasional website publik, portal mahasiswa, admin konten, dan kontrol super admin.',
                'patterns' => ['admin.guides.*'],
                'features' => [
                    'Ikhtisar seluruh modul utama sistem.',
                    'Ringkasan fitur, langkah cepat, dan route terkait.',
                    'Referensi operasional untuk onboarding admin baru atau audit internal.',
                ],
                'steps' => [
                    'Gunakan kartu statistik di bagian atas untuk membaca cakupan sistem secara cepat.',
                    'Buka grup yang relevan untuk melihat panduan modul tertentu.',
                    'Gunakan halaman ini sebagai referensi saat memberi akses atau saat melakukan handover pekerjaan.',
                ],
                'tips' => [
                    'Halaman ini ideal sebagai dokumen kerja saat training admin baru.',
                    'Jika ada modul baru, tambahkan panduannya ke katalog agar pusat panduan tetap lengkap.',
                ],
            ],
        ];
    }
}
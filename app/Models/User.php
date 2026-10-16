<?php

namespace App\Models;

use App\Notifications\PortalResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Fillable(['name', 'email', 'identity_number', 'password', 'is_admin', 'role', 'program_studi_id', 'jurusan_id', 'managed_unit_page_id', 'custom_menus', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ─── Role constants ───────────────────────────────────────────────────────
    const ROLE_SUPER_ADMIN   = 'super_admin';
    const ROLE_DIREKTUR      = 'direktur';      // Direktur Poltekkes Bandung
    const ROLE_WADIR_1       = 'wadir_1';       // Akademik & Kemahasiswaan
    const ROLE_WADIR_2       = 'wadir_2';       // Administrasi Umum
    const ROLE_WADIR_3       = 'wadir_3';       // Kemahasiswaan
    const ROLE_KABAG_ADUM    = 'kabag_adum';    // Kepala Bagian Administrasi Umum
    const ROLE_PUSAT_MUTU    = 'pusat_mutu';    // Penjaminan Mutu
    const ROLE_PUSAT_LITMAS  = 'pusat_litmas';  // Penelitian & Pengabmas
    const ROLE_PUSAT_P3      = 'pusat_p3';      // Pengembangan Pendidikan
    const ROLE_KAJUR         = 'kajur';         // Ketua Jurusan
    const ROLE_SEKJUR        = 'sekjur';        // Sekretaris Jurusan
    const ROLE_ADMIN_JURUSAN = 'admin_jurusan'; // Admin Jurusan
    const ROLE_KAPRODI       = 'kaprodi';       // Ketua Program Studi
    const ROLE_SUBBAG_AKADEMIK = 'subbag_akademik'; // Sub Bagian Administrasi Akademik & Kemahasiswaan
    const ROLE_UNIT_TI       = 'unit_ti';       // Teknologi & Informasi
    const ROLE_UNIT_HUMAS_KERJASAMA = 'unit_humas_kerjasama'; // Humas & Kerjasama
    const ROLE_UNIT_PERPUS   = 'unit_perpus';   // Perpustakaan Terpadu
    const ROLE_UNIT_LAB      = 'unit_lab';      // Laboratorium Terpadu
    const ROLE_UNIT_BAHASA   = 'unit_bahasa';   // Unit Bahasa
    const ROLE_UNIT_BISNIS   = 'unit_bisnis';   // Unit Bisnis
    const ROLE_UNIT_ASRAMA   = 'unit_asrama';   // Unit Asrama
    const ROLE_SPI           = 'spi';           // Satuan Pengawas Internal
    const ROLE_PAGE_ADMIN    = 'page_admin';    // Admin halaman publik tertentu
    const ROLE_DOSEN         = 'dosen';         // Dosen Poltekkes Bandung
    const ROLE_EMPLOYEE      = 'employee';      // Pegawai portal non-admin
    const ROLE_PJ_KURIKULUM  = 'pj_kurikulum';  // PJ Kurikulum & Pengembangan Prodi
    const ROLE_PJ_IPE_PUI    = 'pj_ipe_pui';    // PJ IPE & PUI
    const ROLE_CI            = 'ci';            // Clinical Instructor (RS/Lahan Praktik)

    public static function defaultRoleLabels(): array
    {
        return [
            self::ROLE_SUPER_ADMIN  => 'Super Admin',
            self::ROLE_DIREKTUR     => 'Direktur',
            self::ROLE_WADIR_1      => 'Wakil Direktur I (Akademik)',
            self::ROLE_WADIR_2      => 'Wakil Direktur II (Administrasi Umum)',
            self::ROLE_WADIR_3      => 'Wakil Direktur III (Kemahasiswaan)',
            self::ROLE_KABAG_ADUM   => 'Kepala Bagian Administrasi Umum',
            self::ROLE_PUSAT_MUTU   => 'Pusat Penjaminan Mutu',
            self::ROLE_PUSAT_LITMAS => 'Pusat Penelitian & Pengabmas',
            self::ROLE_PUSAT_P3     => 'Kepala Pusat Pengembangan Pendidikan',
            self::ROLE_KAJUR        => 'Ketua Jurusan',
            self::ROLE_SEKJUR       => 'Sekretaris Jurusan',
            self::ROLE_ADMIN_JURUSAN => 'Admin Jurusan',
            self::ROLE_KAPRODI      => 'Ketua Program Studi',
            self::ROLE_SUBBAG_AKADEMIK => 'Sub Bagian Administrasi Akademik dan Kemahasiswaan',
            self::ROLE_UNIT_TI      => 'Unit Teknologi & Informasi',
            self::ROLE_UNIT_HUMAS_KERJASAMA => 'Unit Humas dan Kerjasama',
            self::ROLE_UNIT_PERPUS  => 'Unit Perpustakaan Terpadu',
            self::ROLE_UNIT_LAB     => 'Unit Laboratorium Terpadu',
            self::ROLE_UNIT_BAHASA  => 'Unit Bahasa',
            self::ROLE_UNIT_BISNIS  => 'Unit Bisnis',
            self::ROLE_UNIT_ASRAMA  => 'Unit Asrama',
            self::ROLE_SPI          => 'Satuan Pengawas Internal',
            self::ROLE_PAGE_ADMIN   => 'Admin Halaman Publik',
            self::ROLE_DOSEN        => 'Dosen',
            self::ROLE_EMPLOYEE     => 'Pegawai Poltekkes Bandung',
            self::ROLE_PJ_KURIKULUM => 'PJ Kurikulum dan Pengembangan Prodi',
            self::ROLE_PJ_IPE_PUI   => 'PJ IPE dan PUI',
            self::ROLE_CI           => 'Clinical Instructor',
        ];
    }

    public static function defaultRoleMenus(): array
    {
        return [
            self::ROLE_SUPER_ADMIN  => ['*'],
            self::ROLE_DIREKTUR     => ['halaman-unit', 'rekap-rapat', 'eviden-akreditasi'],
            self::ROLE_WADIR_1      => ['halaman-unit', 'rekap-rapat', 'berita', 'pengumuman', 'program-studi', 'agenda', 'eviden-akreditasi'],
            self::ROLE_WADIR_2      => ['halaman-unit', 'rekap-rapat', 'eviden-akreditasi'],
            self::ROLE_WADIR_3      => ['halaman-unit', 'rekap-rapat', 'berita', 'pengumuman', 'berprestasi', 'agenda', 'eviden-akreditasi'],
            self::ROLE_KABAG_ADUM   => ['halaman-unit', 'rekap-rapat', 'eviden-akreditasi'],
            self::ROLE_PUSAT_MUTU   => ['halaman-unit', 'dokumen-mutu', 'eviden-akreditasi', 'halaman-statis:akreditasi', 'halaman-statis:visi_misi'],
            self::ROLE_PUSAT_LITMAS => ['halaman-unit', 'publikasi', 'berita', 'pengumuman', 'agenda', 'halaman-statis:penelitian'],
            self::ROLE_PUSAT_P3     => ['halaman-unit', 'pelatihan', 'berita', 'pengumuman', 'halaman-statis:kurikulum'],
            self::ROLE_KAJUR        => ['halaman-unit', 'program-studi', 'berita', 'pengumuman', 'agenda', 'eviden-akreditasi'],
            self::ROLE_SEKJUR       => ['halaman-unit', 'program-studi', 'berita', 'pengumuman', 'agenda', 'eviden-akreditasi'],
            self::ROLE_ADMIN_JURUSAN => ['halaman-unit', 'program-studi', 'berita', 'pengumuman', 'agenda', 'eviden-akreditasi'],
            self::ROLE_KAPRODI      => ['halaman-unit', 'program-studi', 'berita', 'pengumuman', 'eviden-akreditasi'],
            self::ROLE_SUBBAG_AKADEMIK => ['halaman-unit', 'program-studi', 'berprestasi', 'agenda'],
            self::ROLE_UNIT_TI      => ['halaman-unit', 'layanan-ti', 'halaman-statis:sejarah'],
            self::ROLE_UNIT_HUMAS_KERJASAMA => ['halaman-unit', 'berita', 'pengumuman', 'galeri', 'agenda', 'halaman-statis:kerjasama', 'halaman-statis:akreditasi'],
            self::ROLE_UNIT_PERPUS  => ['halaman-unit', 'koleksi-perpus', 'halaman-statis:perpustakaan', 'berita'],
            self::ROLE_UNIT_LAB     => ['halaman-unit', 'layanan-lab', 'berita', 'halaman-statis:laboratorium'],
            self::ROLE_UNIT_BAHASA  => ['halaman-unit', 'berita', 'pengumuman', 'agenda'],
            self::ROLE_UNIT_BISNIS  => ['halaman-unit', 'layanan-bisnis', 'galeri', 'berita', 'halaman-statis:kerjasama'],
            self::ROLE_UNIT_ASRAMA  => ['halaman-unit', 'fasilitas-asrama', 'berita', 'halaman-statis:asrama'],
            self::ROLE_SPI          => ['halaman-unit', 'eviden-akreditasi'],
            self::ROLE_PAGE_ADMIN   => ['halaman-unit'],
            self::ROLE_DOSEN        => ['halaman-unit', 'wfh'],
            self::ROLE_EMPLOYEE     => ['halaman-unit', 'wfh'],
            self::ROLE_PJ_KURIKULUM => ['halaman-unit', 'berita', 'pengumuman', 'halaman-statis:kurikulum'],
            self::ROLE_PJ_IPE_PUI   => ['halaman-unit', 'berita', 'pengumuman'],
            self::ROLE_CI           => ['halaman-unit', 'clinical-attendance'],
        ];
    }

    public static function refreshRoleConfig(): void
    {
        self::$roleLabels = self::defaultRoleLabels();
        self::$roleMenus = self::defaultRoleMenus();

        try {
            if (! Schema::hasTable('roles')) {
                return;
            }

            Role::query()
                ->orderBy('label')
                ->get(['key', 'label', 'menus'])
                ->each(function (Role $role): void {
                    self::$roleLabels[$role->key] = $role->label;
                    self::$roleMenus[$role->key] = is_string($role->menus) ? json_decode($role->menus, true) : ($role->menus ?? []);
                });
        } catch (Throwable $e) {
            // Silence if table not migrated yet
        }
    }

    public static array $roleLabels = [];
    public static array $roleMenus = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'custom_menus'      => 'array',
        ];
    }

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function managedUnitPage()
    {
        return $this->belongsTo(UnitPage::class, 'managed_unit_page_id');
    }

    public function roleDefinition()
    {
        return $this->belongsTo(Role::class, 'role', 'key');
    }

    public function isSuperAdmin(): bool   { return $this->role === self::ROLE_SUPER_ADMIN; }
    public function isDirektur(): bool     { return $this->role === self::ROLE_DIREKTUR; }
    public function isWadir1(): bool       { return $this->role === self::ROLE_WADIR_1; }
    public function isWadir2(): bool       { return $this->role === self::ROLE_WADIR_2; }
    public function isWadir3(): bool       { return $this->role === self::ROLE_WADIR_3; }
    public function isKabagAdum(): bool    { return $this->role === self::ROLE_KABAG_ADUM; }
    public function isKajur(): bool        { return $this->role === self::ROLE_KAJUR; }
    public function isSekjur(): bool       { return $this->role === self::ROLE_SEKJUR; }
    public function isAdminJurusan(): bool { return $this->role === self::ROLE_ADMIN_JURUSAN; }
    public function isJurusanAdmin(): bool { return in_array($this->role, [self::ROLE_KAJUR, self::ROLE_SEKJUR, self::ROLE_ADMIN_JURUSAN], true); }
    public function isKaprodi(): bool      { return $this->role === self::ROLE_KAPRODI; }
    public function isSubbagAkademik(): bool { return $this->role === self::ROLE_SUBBAG_AKADEMIK; }
    public function isUnitBahasa(): bool   { return $this->role === self::ROLE_UNIT_BAHASA; }
    public function isManagedPageAdmin(): bool { return $this->role === self::ROLE_PAGE_ADMIN; }
    public function isDosen(): bool        { return $this->role === self::ROLE_DOSEN; }
    public function isEmployee(): bool     { return $this->role === self::ROLE_EMPLOYEE; }
    public function isCi(): bool           { return $this->role === self::ROLE_CI; }

    public function canAccessWfhRecaps(): bool
    {
        return $this->isSuperAdmin() || $this->isDirektur() || $this->isWadir1() || $this->isWadir2() || $this->isWadir3() || $this->isKabagAdum();
    }

    public function canAccessMeetingRecaps(): bool
    {
        if ($this->isSuperAdmin() || $this->isDirektur() || $this->isWadir1() || $this->isWadir2() || $this->isWadir3() || $this->isKabagAdum()) {
            return true;
        }

        if ($this->hasRoleAccess('rekap-rapat')) {
            return true;
        }

        return $this->managedUnitPage?->slug === 'bidang-administrasi-umum';
    }

    public function hasRoleAccess(string $menu): bool
    {
        if ($this->isSuperAdmin()) return true;
        if ($menu === 'pengaduan') {
            return $this->hasRoleAccess('halaman-unit');
        }
        // custom_menus (not null) = explicit override set by super admin
        $menus = $this->custom_menus ?? (self::$roleMenus[$this->role] ?? []);
        if (in_array('*', $menus)) return true;
        // exact match or prefix match (e.g. 'halaman-statis' matches 'halaman-statis:kunci')
        foreach ($menus as $m) {
            if ($m === $menu) return true;
            $base = explode(':', $m)[0];
            if ($base === $menu) return true;
        }
        return false;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::$roleLabels[$this->role] ?? $this->role;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PortalResetPasswordNotification(
            token: $token,
            routeName: 'admin.password.reset',
            portalLabel: 'Admin Poltekkes Bandung',
            brokerName: 'users',
        ));
    }
}

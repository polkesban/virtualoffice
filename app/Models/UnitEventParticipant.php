<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class UnitEventParticipant extends Model
{
    protected $fillable = [
        'unit_event_id',
        'peran',
        'participant_category',
        'sumber_registrasi',
        'nama',
        'email',
        'identity_number',
        'no_hp',
        'instansi',
        'tempat_praktik',
        'nama_pembimbing',
        'jabatan',
        'catatan',
        'foto_path',
        'kode_registrasi',
        'access_token',
        'access_password',
        'access_password_encrypted',
        'credentials_generated_at',
        'registered_at',
        'attended_at',
        'attendance_notes',
        'pre_test_nilai',
        'post_test_nilai',
        'evaluasi_dikirim_at',
        'sertifikat_path',
        'sertifikat_uploaded_at',
        'surat_tugas_path',
        'surat_tugas_uploaded_at',
        'linked_user_id',
        'linked_mahasiswa_id',
        'meta',
    ];

    protected static function booted()
    {
        static::created(function ($participant) {
            if ($participant->participant_category === 'student' && filled($participant->identity_number)) {
                $instansi = trim((string) $participant->instansi);
                
                // Only for Poltekkes Kemenkes Bandung (or if empty, since default student is Poltekkes)
                $isPoltekkes = $instansi === '' 
                    || str_ireplace(' ', '', strtolower($instansi)) === 'poltekkeskemenkesbandung' 
                    || str_ireplace(' ', '', strtolower($instansi)) === 'poltekkesbandung';

                if ($isPoltekkes) {
                    // Check if Mahasiswa record already exists
                    $mahasiswa = Mahasiswa::whereRaw('LOWER(nim) = ?', [strtolower($participant->identity_number)])->first();
                    
                    if (!$mahasiswa) {
                        // Dynamically resolve program study
                        $prodiId = null;
                        if ($participant->event && $participant->event->page) {
                            $prodi = ProgramStudi::where('unit_page_id', $participant->event->page->id)->first();
                            if ($prodi) {
                                $prodiId = $prodi->id;
                            }
                        }
                        if (!$prodiId) {
                            $prodiId = ProgramStudi::where('aktif', true)->first()?->id;
                        }

                        $angkatanStr = '2024';
                        if (preg_match('/^p173\d{3}(\d{2})/i', trim($participant->identity_number), $matches)) {
                            $angkatanStr = '20' . $matches[1];
                        } else {
                            $angkatanStr = (string) (date('Y') - 2);
                        }

                        $mahasiswa = Mahasiswa::create([
                            'nim' => $participant->identity_number,
                            'name' => $participant->nama,
                            'email' => $participant->email ?: ($participant->identity_number . '@polkesban.ac.id'),
                            'no_hp' => $participant->no_hp,
                            'program_studi_id' => $prodiId,
                            'angkatan' => $angkatanStr,
                            'password' => \Illuminate\Support\Facades\Hash::make('polkesban'), // default/temporary password
                        ]);
                    }

                    // Link them
                    if ($mahasiswa && blank($participant->linked_mahasiswa_id)) {
                        $participant->updateQuietly([
                            'linked_mahasiswa_id' => $mahasiswa->id
                        ]);
                    }
                }
            }
        });
    }

    public static array $peranLabels = [
        'participant' => 'Peserta',
        'speaker' => 'Narasumber',
        'committee' => 'Panitia',
        'pembimbing_institusi' => 'Pembimbing Institusi',
        'preseptor' => 'Preseptor',
        'koordinator_matakuliah' => 'Koordinator Matakuliah',
        'kaprodi' => 'Kaprodi',
    ];

    public static array $participantCategoryLabels = [
        'employee' => 'Pegawai Poltekkes Bandung',
        'student' => 'Mahasiswa',
        'public' => 'Non Pegawai / Umum',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'credentials_generated_at' => 'datetime',
            'attended_at' => 'datetime',
            'pre_test_nilai' => 'float',
            'post_test_nilai' => 'float',
            'evaluasi_dikirim_at' => 'datetime',
            'sertifikat_uploaded_at' => 'datetime',
            'surat_tugas_uploaded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }

    public function linkedUser()
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function linkedMahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'linked_mahasiswa_id');
    }

    public function submissions()
    {
        return $this->hasMany(UnitEventSubmission::class, 'unit_event_participant_id');
    }

    public function attendances()
    {
        return $this->hasMany(UnitEventAttendance::class, 'unit_event_participant_id')
            ->orderBy('attendance_date')
            ->orderBy('signed_at');
    }

    public function getPeranLabelAttribute(): string
    {
        if ($this->event && $this->event->jenis_kegiatan === 'wisuda' && $this->peran === 'participant') {
            return 'Undangan';
        }
        return self::$peranLabels[$this->peran] ?? $this->peran;
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (filled($this->foto_path)) {
            return \App\Support\MediaStorage::url($this->foto_path);
        }

        if (!empty($this->meta['foto_path'])) {
            return \App\Support\MediaStorage::url($this->meta['foto_path']);
        }

        return null;
    }

    public function getParticipantCategoryLabelAttribute(): string
    {
        return self::$participantCategoryLabels[$this->participant_category] ?? Str::headline((string) $this->participant_category);
    }

    public function attendanceCount(): int
    {
        if ($this->relationLoaded('attendances')) {
            return $this->attendances->count();
        }

        return $this->attendances()->count();
    }

    public function attendanceRecordFor(CarbonInterface|string $date): ?UnitEventAttendance
    {
        $dateString = $date instanceof CarbonInterface ? $date->toDateString() : (string) $date;

        if ($this->relationLoaded('attendances')) {
            return $this->attendances->first(fn (UnitEventAttendance $attendance) => $attendance->attendance_date?->toDateString() === $dateString);
        }

        return $this->attendances()->whereDate('attendance_date', $dateString)->first();
    }

    public function hasAttendanceForDate(CarbonInterface|string $date): bool
    {
        return $this->attendanceRecordFor($date) !== null;
    }

    public function hasAccessCredentials(): bool
    {
        return filled($this->access_password);
    }

    public function usesLinkedApplicationAccess(): bool
    {
        return filled($this->linked_user_id) || filled($this->linked_mahasiswa_id);
    }

    public function hasPrintableAccess(): bool
    {
        return $this->usesLinkedApplicationAccess() || $this->hasAccessCredentials();
    }

    public function getParticipantLoginUsernameAttribute(): string
    {
        return (string) $this->kode_registrasi;
    }

    public function getLinkedAccessPortalAttribute(): ?string
    {
        if ($this->linked_mahasiswa_id) {
            return 'mahasiswa';
        }

        if ($this->linked_user_id) {
            return 'pegawai';
        }

        return null;
    }

    public function getLinkedAccessPortalLabelAttribute(): ?string
    {
        return match ($this->linked_access_portal) {
            'mahasiswa' => 'Portal Mahasiswa',
            'pegawai' => 'Portal Pegawai',
            'admin' => 'Akun Aplikasi',
            default => null,
        };
    }

    public function getLinkedAccessIdentifierAttribute(): ?string
    {
        if ($this->linkedMahasiswa) {
            return $this->identity_number ?: $this->linkedMahasiswa->nim ?: $this->linkedMahasiswa->email;
        }

        if ($this->linkedUser) {
            return $this->identity_number ?: $this->linkedUser->identity_number ?: $this->linkedUser->email;
        }

        return $this->identity_number ?: null;
    }

    public function getFormattedIdentityNumberAttribute(): ?string
    {
        $value = trim((string) $this->identity_number);

        return $value !== '' ? Str::upper($value) : null;
    }

    public function getTemporaryAccessPasswordAttribute(): ?string
    {
        if (blank($this->access_password_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->access_password_encrypted);
        } catch (DecryptException) {
            return null;
        }
    }
}
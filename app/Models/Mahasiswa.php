<?php

namespace App\Models;

use App\Notifications\PortalResetPasswordNotification;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nim','name','email','password','no_hp','foto','program_studi_id','angkatan','google_id'])]
class Mahasiswa extends Authenticatable
{
    use Notifiable;

    protected $table = 'mahasiswa';
    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'program_studi_id' => 'integer'];
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function portofolio(): HasMany
    {
        return $this->hasMany(PortofolioMahasiswa::class, 'mahasiswa_id');
    }

    public function obePortofolios(): HasMany
    {
        return $this->hasMany(MahasiswaObePortofolio::class, 'mahasiswa_id');
    }

    public function berprestasi(): HasMany
    {
        return $this->hasMany(MahasiswaBerprestasi::class, 'mahasiswa_id');
    }

    /** URL foto profil — fallback ke avatar UI Avatars */
    public function getFotoUrlAttribute(): string
    {
        if ($this->foto) {
            return MediaStorage::url($this->foto) ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&size=128&background=0ea5e9&color=fff';
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&size=128&background=0ea5e9&color=fff';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PortalResetPasswordNotification(
            token: $token,
            routeName: 'mahasiswa.password.reset',
            portalLabel: 'Portal Mahasiswa Poltekkes Bandung',
            brokerName: 'mahasiswa',
        ));
    }
}

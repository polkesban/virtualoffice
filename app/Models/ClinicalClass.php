<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalClass extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function unitPage()
    {
        return $this->belongsTo(UnitPage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(ClinicalClassMember::class);
    }

    public function sessions()
    {
        return $this->hasMany(ClinicalSession::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_image) {
            return null;
        }
        return \App\Support\MediaStorage::url($this->cover_image);
    }
}

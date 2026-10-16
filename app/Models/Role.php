<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'label', 'menus', 'is_system'])]
class Role extends Model
{
    protected function casts(): array
    {
        return [
            'menus' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'key');
    }

    public function unitPages(): HasMany
    {
        return $this->hasMany(UnitPage::class, 'role', 'key');
    }
}
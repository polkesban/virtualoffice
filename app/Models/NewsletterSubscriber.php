<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email', 'token', 'confirmed', 'confirmed_at',
        'unsubscribed_at', 'ip_address',
    ];

    protected $casts = [
        'confirmed'        => 'boolean',
        'confirmed_at'     => 'datetime',
        'unsubscribed_at'  => 'datetime',
    ];

    protected $hidden = ['token'];

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isActive(): bool
    {
        return $this->confirmed && $this->unsubscribed_at === null;
    }
}

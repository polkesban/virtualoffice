<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderConfig extends Model
{
    protected $fillable = [
        'provider',
        'chat_model',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSectionVote extends Model
{
    protected $table = 'page_section_votes';
    public $timestamps = false;
    protected $fillable = ['section_id', 'session_key', 'pilihan'];
    protected $casts = ['pilihan' => 'integer', 'created_at' => 'datetime'];
}

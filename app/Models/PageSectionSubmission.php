<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSectionSubmission extends Model
{
    protected $table = 'page_section_submissions';
    protected $fillable = ['section_id', 'data', 'ip'];
    protected $casts = ['data' => 'array'];
}

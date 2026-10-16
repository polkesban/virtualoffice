<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationRubricItem extends Model
{
    protected $table = 'evaluation_rubric_items';

    protected $fillable = [
        'evaluation_rubric_id',
        'parent_id',
        'nama_kriteria',
        'deskripsi',
        'bobot',
        'urutan',
        'levels',
    ];

    protected $casts = [
        'bobot'  => 'float',
        'urutan' => 'integer',
        'levels' => 'array',
    ];

    public function rubric()
    {
        return $this->belongsTo(EvaluationRubric::class, 'evaluation_rubric_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subItems()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('urutan')->orderBy('id');
    }
}

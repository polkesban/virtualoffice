<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitEventQuestion extends Model
{
    protected $fillable = [
        'unit_event_id',
        'fase',
        'tipe_pertanyaan',
        'pertanyaan',
        'opsi',
        'jawaban_benar',
        'wajib',
        'bobot',
        'urutan',
    ];

    public static array $faseLabels = [
        'pre_test' => 'Pre Test',
        'post_test' => 'Post Test',
        'evaluation' => 'Evaluasi',
    ];

    public static array $tipeLabels = [
        'multiple_choice' => 'Pilihan Ganda',
        'text' => 'Jawaban Teks',
        'rating' => 'Rating 1-5',
    ];

    protected function casts(): array
    {
        return [
            'opsi' => 'array',
            'wajib' => 'boolean',
            'bobot' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }
}
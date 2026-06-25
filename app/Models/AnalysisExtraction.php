<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisExtraction extends Model
{
    protected $fillable = [
        'draw_id', 'draw_date',
        'gdb_full', 'g1_full',
        'gdb_first2', 'gdb_last2',
        'g1_first2', 'g1_last2',
    ];

    protected $casts = [
        'draw_date' => 'date',
    ];

    public function draw()
    {
        return $this->belongsTo(Draw::class);
    }

    /**
     * Get all 4 analysis numbers as an array
     */
    public function getAnalysisNumbers(): array
    {
        return [
            $this->gdb_first2,
            $this->gdb_last2,
            $this->g1_first2,
            $this->g1_last2,
        ];
    }
}

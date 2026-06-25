<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrawResult extends Model
{
    use HasFactory;

    protected $fillable = ['draw_id', 'prize_tier', 'full_number', 'loto_number'];
    
    // Quan hệ thuộc về Draw
    public function draw()
    {
        return $this->belongsTo(Draw::class);
    }
}

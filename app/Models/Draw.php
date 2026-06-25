<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Draw extends Model
{
    use HasFactory;

    // Khai báo các cột được phép gán dữ liệu
    protected $fillable = ['draw_date', 'status'];

    public function results() {
        return $this->hasMany(DrawResult::class);
    }
}



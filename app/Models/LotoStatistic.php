<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotoStatistic extends Model
{
    use HasFactory;

    // Đổi khóa chính thành loto_number thay vì id mặc định
    protected $primaryKey = 'loto_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'loto_number', 
        'total_appearances', 
        'last_appeared_date', 
        'current_gan_days', 
        'max_gan_days'
    ];
}

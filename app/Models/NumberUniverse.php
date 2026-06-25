<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberUniverse extends Model
{
    protected $table = 'number_universe';
    protected $primaryKey = 'number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'number', 'head', 'tail', 'is_even', 'is_big',
        'digit_sum', 'mod2', 'mod3', 'mod4',
    ];

    protected $casts = [
        'head' => 'integer',
        'tail' => 'integer',
        'is_even' => 'boolean',
        'is_big' => 'boolean',
        'digit_sum' => 'integer',
        'mod2' => 'integer',
        'mod3' => 'integer',
        'mod4' => 'integer',
    ];
}

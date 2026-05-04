<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turkey extends Model
{
    protected $fillable = [
        'ordinal_number',
        'input_sequence_number',
        'car_number',
        'date',
        'entrance',
        'company_name',
        'phone',
    ];
}

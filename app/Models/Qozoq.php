<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Qozoq extends Model
{
    protected $table = 'qozoqs';

    protected $fillable = [
        'boundary_name',
        'car_number',
        'date_and_time',
        'status',
        'company_name',
    ];
}

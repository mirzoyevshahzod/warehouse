<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportQozoq extends Model
{
    protected $table = 'import_qozoqs';

    protected $fillable = [
        'boundary_name',
        'car_number',
        'date_and_time',
        'status',
        'company_name',
        'phone'
    ];
}

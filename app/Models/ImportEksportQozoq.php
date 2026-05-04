<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportEksportQozoq extends Model
{
    
    protected $table = 'import_eksport_qozoqs';

     protected $fillable = [
        'boundary_name',
        'car_number',
        'date_and_time',
        'status',
        'company_name',
    ];
}

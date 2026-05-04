<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AVIAEOmbor extends Model
{
    protected $fillable = [
        'air_waybill_number',
        'flight_number',
        'registration_date',
        'consignee',
        'total_net_weight',
        'total_weight',
        'border_customs_post_code'
    ];
}

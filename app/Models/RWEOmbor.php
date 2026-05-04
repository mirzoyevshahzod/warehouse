<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RWEOmbor extends Model
{
    protected $fillable = [
        'document_number',
        'custom_code',
        'custom_date',
        'TEBHN_number',
        'transport_number',
        'gross_weight',
        'inn',
        'recipient_name',
        'delivery_post',
        'delivery_date',
        'arrival_place',
        'status',

    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Belarus extends Model
{
    protected $table = 'belarus';
    protected $fillable = [
        'call_order',
        'queue_type',
        'car_number',
        'date_of_registration_in_the_zo',
        'status_changed',
        'status'
    ];
}

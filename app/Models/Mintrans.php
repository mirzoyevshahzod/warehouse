<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mintrans extends Model
{
    protected $table = 'mintrans';
    protected $fillable = [
        'model',
        'load_capacity',
        'license_number',
        'state_number',
        'company_name',
        'phone_number',
        'type_of_activity',
        'transport_type',
        'cargo_type',
        'date_given',
        'validity_period',
        'status',
        'territorial_management',
        'inn',
    ];
}

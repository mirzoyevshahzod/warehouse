<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auto extends Model
{
    protected $table = 'autos';

    protected $fillable = [
        'state_number',
        'company_name',
    ];
}

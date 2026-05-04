<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'full_name',
        'number',
        'phone',
        'length',
        'width',
        'height',
        'trailer_number',
        'capacity',
        'lifting',
        'brand',
    ];


}

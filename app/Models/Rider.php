<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'vehicle_type',
        'motorcycle_reg_number',
        'current_latitude',
        'current_longitude',
        'is_available',
    ];
}

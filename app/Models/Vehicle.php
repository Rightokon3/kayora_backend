<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Vehicle extends Model
{
    protected $fillable = [
        'brand', 'model', 'vehicle_type', 'plate_number', 'engine_number',
        'chassis_number', 'color', 'image_path', 'registration_image_path',
        'status', 'assigned_driver_id',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }
}

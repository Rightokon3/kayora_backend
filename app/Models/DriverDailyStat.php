<?php

// app/Models/DriverDailyStat.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverDailyStat extends Model
{
    protected $fillable = ['driver_id', 'date', 'distance_km', 'last_latitude', 'last_longitude'];
    protected $casts = ['date' => 'date'];
}
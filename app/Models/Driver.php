<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'driver_id',
        'name',
        'email',
        'password',
        'phone',
        'vehicle',
        'plate_number',
        // These four were missing before — that's why duty_status and
        // current_latitude/current_longitude silently never saved despite
        // updateOrCreate()/update() calls "succeeding" with no error.
        'duty_status',
        'current_latitude',
        'current_longitude',
        'last_seen_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function vehicleAssignment()
    {
        return $this->hasOne(Vehicle::class, 'assigned_driver_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
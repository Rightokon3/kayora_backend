<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Model
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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function profile()
{
    return $this->hasOne(DriverProfile::class);
}

public function vehicle()
{
    return $this->hasOne(Vehicle::class, 'assigned_driver_id');
}
public function orders()
{
    return $this->hasMany(Order::class);
}
}

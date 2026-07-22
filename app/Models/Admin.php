<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'employee_id',
        'username',
        'name',
        'email',
        'phone',
        'profile_picture',
        'password',
        'role',
        'status',
        'last_login_at',
        'notify_system',
        'notify_new_orders',
        'notify_driver_alerts',
        'notify_customer_reports',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
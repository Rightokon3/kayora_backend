<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPushToken extends Model
{
    protected $fillable = ['admin_id', 'expo_push_token'];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
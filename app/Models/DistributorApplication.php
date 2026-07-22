<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributorApplication extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'business_name',
        'business_type',
        'city',
        'lga',
        'state',
        'phone',
        'whatsapp',
        'email',
        'estimated_monthly_volume',
        'years_in_business',
        'additional_info',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
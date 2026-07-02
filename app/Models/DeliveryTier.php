<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryTier extends Model
{
    protected $fillable = ['location_name', 'fee_kobo'];
}
<?php

// app/Models/OrderDecline.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDecline extends Model
{
    protected $fillable = ['order_id', 'driver_id'];
}

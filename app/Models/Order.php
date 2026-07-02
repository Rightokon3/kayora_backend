<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'address_id', 'payment_method', 'delivery_timing',
        'delivery_date_time', 'cart_items', 'subtotal', 'delivery_fee',
        'service_fee', 'total', 'status'
    ];

    // This converts JSON text into arrays seamlessly when read/written
    protected $casts = [
        'cart_items' => 'array',
        'delivery_date_time' => 'datetime'
    ];
}
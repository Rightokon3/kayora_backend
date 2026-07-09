<?php

// app/Models/Order.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'driver_id', 'customer_name', 'customer_phone', 'customer_email',
        'delivery_address', 'nearest_landmark', 'latitude', 'longitude', 'amount', 'status',
        'payment_method', 'payment_status', 'transaction_id', 'delivery_type',
        'scheduled_date', 'scheduled_time', 'priority', 'special_instructions',
        'distance_km', 'eta', 'assigned_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'driver_id',
        'offered_driver_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'nearest_landmark',
        'latitude',
        'longitude',
        'amount',
        'status',
        'payment_method',
        'payment_status',
        'transaction_id',
        'delivery_type',
        'scheduled_date',
        'scheduled_time',
        'priority',
        'special_instructions',
        'distance_km',
        'eta',
        'assigned_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'scheduled_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * The driver currently being asked to accept/decline this order.
     * Only relevant while status === 'Pending'; cleared the moment the
     * order is either accepted (-> driver_id set) or declined.
     */
    public function offeredDriver()
    {
        return $this->belongsTo(Driver::class, 'offered_driver_id');
    }

    public function declines()
    {
        return $this->hasMany(OrderDecline::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
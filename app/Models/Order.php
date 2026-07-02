<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'rider_id', 'delivery_timing', 'delivery_date_time', 'status', 
        'payment_method', 'subtotal_kobo', 'delivery_fee_kobo', 'discount_kobo', 
        'total_kobo', 'address_label', 'delivery_address', 'latitude', 'longitude', 'delivery_completed_at'
    ];

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function rider() {
        return $this->belongsTo(Rider::class);
    }
}
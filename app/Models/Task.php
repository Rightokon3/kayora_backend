<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',               // e.g., "TSK-2026-001"
        'driver_id',             // Foreign key linking to users table
        'customer_name',
        'address',
        'status',                // 'pending' or 'completed'
        'items_count',
        'scheduled_date',        // The delivery date assigned by admin
        'current_latitude',      // Real-time tracking latitude
        'current_longitude',     // Real-time tracking longitude
        'distance_completed_km', // Running distance tally updated by tracking delta coordinates
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'items_count' => 'integer',
        'driver_id' => 'integer',
        'current_latitude' => 'float',
        'current_longitude' => 'float',
        'distance_completed_km' => 'float',
        'scheduled_date' => 'date',
    ];

    /**
     * Relationship back to the User model (Driver).
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueEntry extends Model
{
    protected $fillable = ['entry_date', 'amount', 'note', 'created_by'];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
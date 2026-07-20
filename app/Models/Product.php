<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'size', 'tagline', 'price', 'heroDesc', 'aboutTitle', 'aboutBody',
        'bestUsedTitle', 'usedFor', 'specs', 'regulatory', 'imageColor', 'image_url',
        'orderTitle', 'orderDesc', 'is_popular', 'available', 'status',
    ];

    protected $casts = [
        'usedFor' => 'array',
        'specs' => 'array',
        'regulatory' => 'array',
        'is_popular' => 'boolean',
        'available' => 'boolean',
        'price' => 'integer',
    ];
}
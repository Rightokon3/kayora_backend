<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $casts = [
        'price' => 'integer',
        'usedFor' => 'array',
        'specs' => 'array',
        'regulatory' => 'array',
        'is_popular' => 'boolean'
    ];
}
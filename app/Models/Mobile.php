<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Mobile extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'mobiles';

    protected $fillable = [
        'asin',
        'image_url',
        'last_updated',
        'price',
        'product_url',
        'rating',
        'reviews',
        'tags',
        'title',
    ];

    protected $casts = [
        'price' => 'integer',
        'rating' => 'float',
        'reviews' => 'integer',
        'tags' => 'array',
        'last_updated' => 'datetime',
    ];
}
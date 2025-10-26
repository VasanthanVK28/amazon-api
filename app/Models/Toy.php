<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Toy extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'toys';

    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

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
        'rating' => 'integer',
        'reviews' => 'integer',
        'tags' => 'array',
        'last_updated' => 'datetime',
    ];
}
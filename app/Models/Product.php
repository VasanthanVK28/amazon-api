<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @method static \Jenssegers\Mongodb\Eloquent\Builder|Product where(string $field, $operator = null, $value = null, $boolean = 'and')
 * @method static \Jenssegers\Mongodb\Eloquent\Builder|Product query()
 * @method static \Jenssegers\Mongodb\Eloquent\Builder|Product paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', int|null $page = null)
 */

class Product extends Model
{
    protected $connection = 'mongodb'; // MongoDB connection
    protected $collection = 'products'; // Collection name

    protected $fillable = [
        'asin',
        'title',
        'image_url',
        'product_url',
        'price',
        'rating',
        'reviews',
        'tags',
        'brand',
        'last_updated',
    ];
    protected $casts = [
    'last_updated' => 'datetime',
];

}

<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ProductAnalytics extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'product_analytics';

    protected $fillable = [
        'product_id',
        'product_name',
        'impressions',
        'clicks',
        'date'
    ];

    protected $casts = [
        'impressions' => 'integer',
        'clicks' => 'integer',
        'date' => 'date'
    ];

    // Automatically calculate CTR (not stored)
    protected $appends = ['ctr'];

    public function getCtrAttribute()
    {
        if ($this->impressions > 0) {
            return round(($this->clicks / $this->impressions) * 100, 2);
        }
        return 0;
    }
}

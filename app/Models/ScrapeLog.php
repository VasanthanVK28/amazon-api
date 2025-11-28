<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as Eloquent;

class ScrapeLog extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'scrape_logs';

    protected $fillable = [
        'schedule_id',    // reference to ScrapeSchedule _id
        'frequency',
        'categories',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'categories' => 'array',
    ];
}

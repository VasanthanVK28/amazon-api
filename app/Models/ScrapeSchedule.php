<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as Eloquent;

class ScrapeSchedule extends Eloquent
{
    protected $connection = 'mongodb';           // MongoDB connection
    protected $collection = 'scrape_schedules';  // MongoDB collection name

    protected $fillable = [
        'frequency',  // 'hourly', 'daily', 'weekly'
        'time',       // e.g., '03:00' for daily/weekly
        'day',        // 'mon', 'tue', ... for weekly
        'status',     // 'active' or 'inactive'
        'last_run',
        'is_running'
    ];

    protected $hidden = [
        // Optional: hide MongoDB _id if you want cleaner API output
        '_id',
    ];
}

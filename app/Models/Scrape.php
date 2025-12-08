<?php

namespace App\Models;

use MongoDB\Client;

class Scrape
{
    protected static function collection()
    {
        $client = new Client("mongodb://127.0.0.1:27017"); // your MongoDB URL
        return $client->selectDatabase('amazon_scraper')->selectCollection('scraped_products');
    }

    public static function all()
    {
        return iterator_to_array(self::collection()->find());
    }

    public static function count()
    {
        return self::collection()->countDocuments();
    }

    public static function first()
    {
        return self::collection()->findOne();
    }
}

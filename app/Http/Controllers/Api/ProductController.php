<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MongoDB\Client as MongoClient;

class ProductController extends Controller
{
    protected $dbName = 'amazon_scraper';
    protected $collections = ['laptops', 'mobiles', 'shirts', 'sofas', 'toys'];

    // GET /api/products
    public function index(Request $request)
    {
        // Connect to MongoDB
        $client = new MongoClient(env('MONGO_DB_URI', 'mongodb://127.0.0.1:27017'));
        $db = $client->{$this->dbName};

        $allProducts = [];

        // Loop through each collection and merge results
        foreach ($this->collections as $collection) {
            $items = $db->{$collection}->find()->toArray();
            $allProducts = array_merge($allProducts, $items);
        }

        // Optional: simple pagination
        $page = (int) $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($allProducts, $offset, $perPage);

        return response()->json([
            'current_page' => $page,
            'data' => $paginated,
            'total' => count($allProducts),
            'per_page' => $perPage
        ]);
    }

    // GET /api/products/{asin}
    public function show($asin)
    {
        $client = new MongoClient(env('MONGO_DB_URI', 'mongodb://127.0.0.1:27017'));
        $db = $client->{$this->dbName};

        foreach ($this->collections as $collection) {
            $product = $db->{$collection}->findOne(['asin' => $asin]);
            if ($product) {
                return response()->json($product);
            }
        }

        return response()->json(['message' => 'Product not found'], 404);
    }

    // GET /api/products/filter
    public function filter(Request $request)
    {
        $client = new MongoClient(env('MONGO_DB_URI', 'mongodb://127.0.0.1:27017'));
        $db = $client->{$this->dbName};

        $allProducts = [];

        foreach ($this->collections as $collection) {
            $query = [];

            if ($request->has('keyword')) {
                $query['title'] = ['$regex' => $request->keyword, '$options' => 'i'];
            }

            if ($request->has('category')) {
                $query['tags'] = $request->category;
            }

            if ($request->has('rating')) {
                $query['rating'] = ['$gte' => (float)$request->rating];
            }

            $items = $db->{$collection}->find($query)->toArray();
            $allProducts = array_merge($allProducts, $items);
        }

        // Simple pagination
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($allProducts, $offset, $perPage);

        return response()->json([
            'current_page' => (int)$page,
            'data' => $paginated,
            'total' => count($allProducts),
            'per_page' => $perPage
        ]);
    }
}

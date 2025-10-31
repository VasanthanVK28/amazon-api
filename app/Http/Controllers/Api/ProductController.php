<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;
use MongoDB\Client as MongoClient;
use Illuminate\Routing\Controller as BaseController;
class ProductController extends BaseController

{

    protected $dbName = 'amazon_scraper';
    protected $collections = ['laptops', 'mobiles', 'shirts', 'sofas', 'toys'];

      public function __construct()
    {
        // Public: index, show, filter
        // Protected: others (like create/update/delete if added)
        $this->middleware('jwt.auth')->except(['index', 'show', 'filter']);
    }


    // 🔹 MongoDB connection
    protected function getDB()
    {
        $client = new MongoClient(env('MONGO_DB_URI', 'mongodb://127.0.0.1:27017'));
        return $client->{$this->dbName};
    }

    // 🔹 Build product URL dynamically using tags + brand + rating + asin
    protected function buildProductUrl($product)
    {
        $tags = $product['tags'] ?? [];
        $category = !empty($tags) ? strtolower($tags[0]) : 'unknown';
        $brand = strtolower($product['brand'] ?? ($product['title'] ? explode(' ', strtolower($product['title']))[0] : 'unknown'));
        $rating = $product['rating'] ?? 0;
        $asin = $product['asin'] ?? '';

        return url("/api/products/{$category}/{$brand}/{$rating}/{$asin}");
    }

    // ✅ GET /api/products
    public function index(Request $request)
    {
        $db = $this->getDB();
        $allProducts = [];

        foreach ($this->collections as $collection) {
            $items = $db->{$collection}->find()->toArray();
            foreach ($items as &$item) {
                $item['url'] = $this->buildProductUrl($item);
            }
            $allProducts = array_merge($allProducts, $items);
        }

        // Pagination
        $page = (int) $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($allProducts, $offset, $perPage);

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => count($allProducts),
            'data' => $paginated,
        ]);
    }

    // ✅ GET /api/products/filter?category=mobile&brand=realme&rating=4
  

public function filter(Request $request)
{
    $db = $this->getDB();
    $allProducts = [];

    // ✅ Extract query params
    $id        = $request->query('id');
    $keyword   = $request->query('keyword');
    $title     = $request->query('title');
    $categories = $request->query('category');
    $brands     = $request->query('brand');
    $rating     = $request->query('rating');
    $minRating  = $request->query('min_rating');
    $maxRating  = $request->query('max_rating');
    $asin       = $request->query('asin');
    $price      = $request->query('price');
    $minPrice   = $request->query('min_price');
    $maxPrice   = $request->query('max_price');
    $reviews    = $request->query('reviews');

    // Convert comma-separated strings into arrays
    $categoryArray = $categories ? array_map('strtolower', array_map('trim', explode(',', $categories))) : [];
    $brandArray = $brands ? array_map('strtolower', array_map('trim', explode(',', $brands))) : [];

    // ✅ Category → collection mapping
    $categoryMap = [
        'electronics'  => ['laptops', 'mobiles'],
        'laptop'       => ['laptops'],
        'laptops'      => ['laptops'],
        'mobile'       => ['mobiles'],
        'mobiles'      => ['mobiles'],
        'computer'     => ['laptops'],
        'fashion'      => ['shirts'],
        'clothing'     => ['shirts'],
        'shirt'        => ['shirts'],
        'furniture'    => ['sofas'],
        'home'         => ['sofas'],
        'sofa'         => ['sofas'],
        'kids'         => ['toys'],
        'entertainment'=> ['toys'],
        'toys'         => ['toys'],
    ];

    // 🧠 Determine relevant collections
    $selectedCollections = $this->collections;
    if (!empty($categoryArray)) {
        $selectedCollections = [];
        foreach ($categoryArray as $cat) {
            if (isset($categoryMap[$cat])) {
                $selectedCollections = array_merge($selectedCollections, $categoryMap[$cat]);
            }
        }
        $selectedCollections = array_unique($selectedCollections);
        if (empty($selectedCollections)) {
            $selectedCollections = $this->collections;
        }
    }

    // 🌀 Loop through collections
    foreach ($selectedCollections as $collection) {
        $query = [];

        // 🆔 ID filter
        if ($id) {
            try {
                $query['_id'] = new ObjectId($id);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid ID format'], 400);
            }
        }

        // 🔍 Keyword or title search
        if ($keyword) {
            $query['title'] = ['$regex' => $keyword, '$options' => 'i'];
        } elseif ($title) {
            $query['title'] = ['$regex' => $title, '$options' => 'i'];
        }

        // 🏷️ Category tag filter (case-insensitive)
        if (!empty($categoryArray)) {
            $query['$and'][] = [
                '$or' => array_map(fn($cat) => [
                    'tags' => ['$regex' => "^$cat$", '$options' => 'i']
                ], $categoryArray)
            ];
        }

        // 🏭 Brand filter (match inside title)
        if (!empty($brandArray)) {
            $query['$and'][] = [
                '$or' => array_map(fn($brand) => [
                    'brand' => ['$regex' => $brand, '$options' => 'i']
                ], $brandArray)
            ];
        }

        // ⭐ Rating filter
        $ratingQuery = [];
        if ($rating) $ratingQuery['$gte'] = (float) $rating;
        if ($minRating) $ratingQuery['$gte'] = (float) $minRating;
        if ($maxRating) $ratingQuery['$lte'] = (float) $maxRating;
        if (!empty($ratingQuery)) $query['rating'] = $ratingQuery;

        // 💰 Price filter
        $priceQuery = [];
        if ($price) $priceQuery['$eq'] = (float) $price;
        if ($minPrice) $priceQuery['$gte'] = (float) $minPrice;
        if ($maxPrice) $priceQuery['$lte'] = (float) $maxPrice;
        if (!empty($priceQuery)) $query['price'] = $priceQuery;

        // 💬 Reviews
        if ($reviews) $query['reviews'] = ['$gte' => (int) $reviews];

        // 🔹 ASIN
        if ($asin) $query['asin'] = $asin;

        // Fetch from Mongo
        $items = $db->{$collection}->find($query)->toArray();

        foreach ($items as &$item) {
            $objectId = (string) ($item['_id'] ?? '');
            $item = [
                'id' => $objectId,
                'asin' => $item['asin'] ?? '',
                'title' => $item['title'] ?? '',
                'price' => $item['price'] ?? null,
                'rating' => $item['rating'] ?? null,
                'reviews' => $item['reviews'] ?? null,
                'tags' => $item['tags'] ?? [],
                'brand' => $item['brand'] ?? null,
                'image_url' => $item['image_url'] ?? null,
                'product_url' => $item['product_url'] ?? null,
                'last_updated' => $item['last_updated'] ?? null,
                'url' => $this->buildProductUrl($item),
            ];
        }

        $allProducts = array_merge($allProducts, $items);
    }

    // 🚨 No results
    if (empty($allProducts)) {
        return response()->json(['message' => 'No products found'], 404);
    }

     $sortBy = $request->query('sort_by', 'rating'); // can be 'price' or 'rating'
    $order = $request->query('order', 'desc'); // 'asc' or 'desc'

    usort($allProducts, function($a, $b) use ($sortBy, $order) {
        $aVal = $a[$sortBy] ?? 0;
        $bVal = $b[$sortBy] ?? 0;
        return $order === 'asc' ? $aVal <=> $bVal : $bVal <=> $aVal;
    });

    // 🏭 Brand counts (across all pages)
    $brandCounts = [];
    foreach ($allProducts as $product) {
        $brand = strtolower(trim($product['brand'] ?? 'Unknown'));
        if (!empty($brand)) {
            $brandCounts[$brand] = ($brandCounts[$brand] ?? 0) + 1;
        }
    }

    // 📄 Pagination
    $page = (int) $request->get('page', 1);
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    $paginated = array_slice($allProducts, $offset, $perPage);

    return response()->json([
        'current_page' => $page,
        'per_page' => $perPage,
        'total' => count($allProducts),
        'data' => $paginated,
        'brand_counts' => $brandCounts,
    ]);
}


    // ✅ GET /api/products/{category}/{brand}/{rating}/{asin}
    public function showDetailed($category, $brand, $rating, $asin)
    {
        $db = $this->getDB();

        foreach ($this->collections as $collection) {
            $product = $db->{$collection}->findOne(['asin' => $asin]);
            if ($product) {
                $product['url'] = $this->buildProductUrl($product);
                return response()->json([
                    'url_params' => compact('category', 'brand', 'rating', 'asin'),
                    'product' => $product
                ]);
            }
        }

        return response()->json(['message' => 'Product not found'], 404);
    }

    // ✅ Simple ASIN lookup
    public function show($asin)
    {
        $db = $this->getDB();

        foreach ($this->collections as $collection) {
            $product = $db->{$collection}->findOne(['asin' => $asin]);
            if ($product) {
                $product['url'] = $this->buildProductUrl($product);
                return response()->json($product);
            }
        }

        return response()->json(['message' => 'Product not found', 'asin' => $asin], 404);

    }
 
    public function getBrands(Request $request)
{
    $db = $this->getDB();
    $categories = strtolower($request->query('category', ''));
    $categoryArray = array_map('trim', explode(',', $categories));

    $categoryMap = [
        'laptop' => ['laptops'],
        'laptops' => ['laptops'],
        'mobile' => ['mobiles'],
        'mobiles' => ['mobiles'],
        'shirt' => ['shirts'],
        'shirts' => ['shirts'],
        'sofa' => ['sofas'],
        'sofas' => ['sofas'],
        'toy' => ['toys'],
        'toys' => ['toys'],
    ];

    $collections = [];
    foreach ($categoryArray as $cat) {
        if (isset($categoryMap[$cat])) {
            $collections = array_merge($collections, $categoryMap[$cat]);
        }
    }

    $collections = array_unique($collections);

    $brands = [];
    foreach ($collections as $collection) {
        $items = $db->{$collection}->find([], ['projection' => ['brand' => 1]])->toArray();
        foreach ($items as $item) {
            if (!empty($item['brand'])) {
                $brands[] = $item['brand'];
            }
        }
    }

    return response()->json([
        'brands' => array_values(array_unique($brands)),
    ]);
}

public function getCategories()
{
    $db = $this->getDB();
    $collections = $this->collections ?? ['laptops', 'mobiles', 'shirts', 'sofas', 'toys'];

    $categories = [];

    foreach ($collections as $collection) {
        $items = $db->{$collection}->find([], ['projection' => ['tags' => 1]])->toArray();
        foreach ($items as $item) {
            if (!empty($item['tags']) && is_array($item['tags'])) {
                foreach ($item['tags'] as $tag) {
                    $categories[] = strtolower(trim($tag));
                }
            } else {
                $categories[] = $collection;
            }
        }
    }

    $categories = array_values(array_unique($categories));

    return response()->json(['categories' => $categories]);
}



}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;
use MongoDB\Client as MongoClient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller as BaseController;
use Stichoza\GoogleTranslate\GoogleTranslate;


class ProductController extends BaseController
{
    protected $dbName = 'amazon_scraper';
    protected $collections = ['laptops', 'mobiles', 'shirts', 'sofas', 'toys'];

    public function __construct()
    {
        $this->middleware('jwt.auth')->except([
            'index', 'show', 'filter', 'embedProducts', 'defaultApiKey', 'getBrands', 'getCategories','getProducts'
        ]);
    }

    // 🔹 MongoDB connection
    protected function getDB()
    {
        $dsn = config('database.connections.mongodb.dsn');        // mongodb://127.0.0.1:27017
        $database = config('database.connections.mongodb.database'); // amazon_scraper

        $client = new MongoClient($dsn);
        return $client->{$database};
    }

    // 🔹 Build product URL dynamically
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
        $page = (int) $request->get('page', 1);
        $perPage = 10;
        $cacheKey = "products.page.{$page}";

        $paginated = Cache::remember($cacheKey, 60 * 5, function () use ($request, $page, $perPage) {
            $db = $this->getDB();
            $allProducts = [];

            foreach ($this->collections as $collection) {
                $items = $db->{$collection}->find()->toArray();
                foreach ($items as &$item) {
                    $item['url'] = $this->buildProductUrl($item);
                }
                $allProducts = array_merge($allProducts, $items);
            }

            $offset = ($page - 1) * $perPage;
            return array_slice($allProducts, $offset, $perPage);
        });

        $total = Cache::remember('products.total', 60 * 5, function () {
            $db = $this->getDB();
            $count = 0;
            foreach ($this->collections as $collection) {
                $count += $db->{$collection}->countDocuments();
            }
            return $count;
        });

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'data' => $paginated,
        ]);
    }

    // ✅ GET /api/products/filter
    public function filter(Request $request)
    {
        $cacheKey = 'products.filter.' . md5(json_encode($request->query()));
        $page = (int) $request->get('page', 1);
        $perPage = 10;

        $allProducts = Cache::remember($cacheKey, 60 * 5, function () use ($request) {
            $db = $this->getDB();
            $allProducts = [];

            // Extract query params
            $id = $request->query('id');
            $keyword = $request->query('keyword');
            $title = $request->query('title');
            $categories = $request->query('category');
            $brands = $request->query('brand');
            $rating = $request->query('rating');
            $minRating = $request->query('min_rating');
            $maxRating = $request->query('max_rating');
            $asin = $request->query('asin');
            $price = $request->query('price');
            $minPrice = $request->query('min_price');
            $maxPrice = $request->query('max_price');
            $reviews = $request->query('reviews');

            $categoryArray = $categories ? array_map('strtolower', array_map('trim', explode(',', $categories))) : [];
            $brandArray = $brands ? array_map('strtolower', array_map('trim', explode(',', $brands))) : [];

            // Category mapping
            $categoryMap = [
                'electronics' => ['laptops', 'mobiles'],
                'laptop' => ['laptops'],
                'laptops' => ['laptops'],
                'mobile' => ['mobiles'],
                'mobiles' => ['mobiles'],
                'computer' => ['laptops'],
                'fashion' => ['shirts'],
                'clothing' => ['shirts'],
                'shirt' => ['shirts'],
                'furniture' => ['sofas'],
                'home' => ['sofas'],
                'sofa' => ['sofas'],
                'kids' => ['toys'],
                'entertainment' => ['toys'],
                'toys' => ['toys'],
            ];

            $selectedCollections = $this->collections;
            if (!empty($categoryArray)) {
                $selectedCollections = [];
                foreach ($categoryArray as $cat) {
                    if (isset($categoryMap[$cat])) {
                        $selectedCollections = array_merge($selectedCollections, $categoryMap[$cat]);
                    }
                }
                $selectedCollections = array_unique($selectedCollections);
                if (empty($selectedCollections)) $selectedCollections = $this->collections;
            }

            foreach ($selectedCollections as $collection) {
                $query = [];

                if ($id) {
                    try {
                        $query['_id'] = new ObjectId($id);
                    } catch (\Exception $e) {
                        return response()->json(['error' => 'Invalid ID format'], 400);
                    }
                }

                if ($keyword) $query['title'] = ['$regex' => $keyword, '$options' => 'i'];
                elseif ($title) $query['title'] = ['$regex' => $title, '$options' => 'i'];

                if (!empty($categoryArray)) {
                    $query['$and'][] = [
                        '$or' => array_map(fn($cat) => ['tags' => ['$regex' => "^$cat$", '$options' => 'i']], $categoryArray)
                    ];
                }

                if (!empty($brandArray)) {
                    $brandConditions = [];
                    foreach ($brandArray as $brand) {
                        $brand = preg_quote(trim(strtolower($brand))); // escape regex special chars
                        $brandConditions[] = [
                            'brand' => ['$regex' => "^$brand$", '$options' => 'i']
                        ];
                    }

                    if (!isset($query['$and'])) $query['$and'] = [];
                    $query['$and'][] = ['$or' => $brandConditions];
                }


                $ratingQuery = [];
                if ($rating) $ratingQuery['$gte'] = (float)$rating;
                if ($minRating) $ratingQuery['$gte'] = (float)$minRating;
                if ($maxRating) $ratingQuery['$lte'] = (float)$maxRating;
                if (!empty($ratingQuery)) $query['rating'] = $ratingQuery;

                $priceQuery = [];
                if ($price) $priceQuery['$eq'] = (float)$price;
                if ($minPrice) $priceQuery['$gte'] = (float)$minPrice;
                if ($maxPrice) $priceQuery['$lte'] = (float)$maxPrice;
                if (!empty($priceQuery)) $query['price'] = $priceQuery;

                if ($reviews) $query['reviews'] = ['$gte' => (int)$reviews];
                if ($asin) $query['asin'] = $asin;

                $items = $db->{$collection}->find($query)->toArray();

                foreach ($items as &$item) {
                    $objectId = (string)($item['_id'] ?? '');
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

            return $allProducts;
        });

        if (empty($allProducts)) {
            return response()->json(['message' => 'No products found'], 404);
        }

        $sortBy = $request->query('sort_by', 'rating');
        $order = $request->query('order', 'desc');

        usort($allProducts, fn($a, $b) => $order === 'asc'
            ? ($a[$sortBy] ?? 0) <=> ($b[$sortBy] ?? 0)
            : ($b[$sortBy] ?? 0) <=> ($a[$sortBy] ?? 0));

        $brandCounts = [];
        foreach ($allProducts as $product) {
            $brand = strtolower(trim($product['brand'] ?? 'Unknown'));
            if (!empty($brand)) $brandCounts[$brand] = ($brandCounts[$brand] ?? 0) + 1;
        }

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

    // ✅ Single product by ASIN
    public function show($asin)
    {
        $cacheKey = "products.{$asin}";

        $product = Cache::remember($cacheKey, 60 * 10, function () use ($asin) {
            $db = $this->getDB();
            foreach ($this->collections as $collection) {
                $product = $db->{$collection}->findOne(['asin' => $asin]);
                if ($product) {
                    $product['url'] = $this->buildProductUrl($product);
                    return $product;
                }
            }
            return null;
        });

        if (!$product) return response()->json(['message' => 'Product not found', 'asin' => $asin], 404);

        return response()->json($product);
    }

    // ✅ Detailed product
    public function showDetailed($category, $brand, $rating, $asin)
    {
        $cacheKey = "products.detailed.{$asin}";
        $product = Cache::remember($cacheKey, 60 * 10, function () use ($asin) {
            $db = $this->getDB();
            foreach ($this->collections as $collection) {
                $product = $db->{$collection}->findOne(['asin' => $asin]);
                if ($product) {
                    $product['url'] = $this->buildProductUrl($product);
                    return $product;
                }
            }
            return null;
        });

        if (!$product) return response()->json(['message' => 'Product not found'], 404);

        return response()->json([
            'url_params' => compact('category', 'brand', 'rating', 'asin'),
            'product' => $product
        ]);
    }

    // ✅ Embed products
    public function embedProducts(Request $request)
    {
        $apiKey = $request->query('apiKey');
        if (!$apiKey) return response()->json(['error' => 'API key is required'], 400);

        $user = User::where('api_key', $apiKey)->first();
        if (!$user) return response()->json(['error' => 'Invalid API key'], 401);

        $cacheKey = "embedProducts.{$apiKey}";
        $products = Cache::remember($cacheKey, 60 * 5, fn() => Product::take(10)->get());

        return response()->json($products);
    }

    // ✅ Default API key
    public function defaultApiKey()
    {
        $cacheKey = "defaultApiKey";
        $user = Cache::remember($cacheKey, 60 * 5, fn() => User::whereNotNull('api_key')->first());

        if (!$user) return response()->json(['error' => 'No default API key found'], 404);
        return response()->json(['api_key' => $user->api_key]);
    }

    // ✅ Get brands
    public function getBrands(Request $request)
    {
        $cacheKey = "brands." . md5(json_encode($request->query()));
        $brands = Cache::remember($cacheKey, 60 * 5, function () use ($request) {
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
                if (isset($categoryMap[$cat])) $collections = array_merge($collections, $categoryMap[$cat]);
            }
            $collections = array_unique($collections);

            $brands = [];
            foreach ($collections as $collection) {
                $items = $db->{$collection}->find([], ['projection' => ['brand' => 1]])->toArray();
                foreach ($items as $item) {
                    if (!empty($item['brand'])) $brands[] = $item['brand'];
                }
            }

            return array_values(array_unique($brands));
        });

        return response()->json(['brands' => $brands]);
    }

    // ✅ Get categories
    public function getCategories()
    {
        $cacheKey = "categories";
        $categories = Cache::remember($cacheKey, 60 * 5, function () {
            $db = $this->getDB();
            $collections = $this->collections ?? ['laptops', 'mobiles', 'shirts', 'sofas', 'toys'];
            $categories = [];

            foreach ($collections as $collection) {
                $items = $db->{$collection}->find([], ['projection' => ['tags' => 1]])->toArray();
                foreach ($items as $item) {
                    if (!empty($item['tags']) && is_array($item['tags'])) {
                        foreach ($item['tags'] as $tag) $categories[] = strtolower(trim($tag));
                    } else {
                        $categories[] = $collection;
                    }
                }
            }
            return array_values(array_unique($categories));
        });

        return response()->json(['categories' => $categories]);
    }

    // 🔍 Real-time search suggestions
// 🔍 Real-time search suggestions
public function suggestions(Request $request)
{
    $query = trim($request->query('q'));

    if (!$query) {
        return response()->json([]);
    }

    $db = $this->getDB();
    $regex = new \MongoDB\BSON\Regex($query, 'i');

    $results = [];

    foreach ($this->collections as $collection) {
        $items = $db->{$collection}->find(
            [   
                '$or' => [
                    ['title'       => $regex],
                    ['brand'       => $regex],
                    ['tags'        => $regex],
                    ['description' => $regex],   // ✅ NEW (important)
                    ['category'    => $regex],   // ✅ NEW
                    ['keywords'    => $regex],   // ✅ NEW
                ]
            ],
            [
                'limit' => 10,
                'projection' => [
                    'title'     => 1,
                    'brand'     => 1,
                    'image_url' => 1,
                    'price'     => 1,
                    'asin'      => 1,
                    'tags'      => 1,
                ]
            ]
        )->toArray();

        foreach ($items as $item) {
            $item['_id'] = (string)$item['_id'];
            $results[] = $item;
        }
    }

    return response()->json($results);
}

   public function allProducts()
{
    $db = $this->getDB();
    $collections = $this->collections; // ['laptops','mobiles','shirts','sofas','toys'];

    $allProducts = [];

    foreach ($collections as $collection) {

        // Fetch ALL documents from each collection
        $cursor = $db->{$collection}->find([]);

        foreach ($cursor as $doc) {

            // Convert MongoDB BSON to PHP array
            $doc['_id'] = (string)$doc['_id'];
            $doc['collection'] = $collection; // useful for frontend

            $allProducts[] = $doc;
        }
    }

    return response()->json([
        'status' => true,
        'count' => count($allProducts),
        'data' => $allProducts
    ]);
}


   
}

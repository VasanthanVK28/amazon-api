<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Kernel;
Route::get('/debug-mongo', function () {
    try {
        $client = new MongoDB\Client('mongodb://127.0.0.1:27017');
        $dbs = $client->listDatabases();
        return response()->json(['status' => '✅ MongoDB Connected', 'databases' => $dbs]);
    } catch (Exception $e) {
        return response()->json(['status' => '❌ MongoDB Connection Failed', 'error' => $e->getMessage()], 500);
    }
});

// 🔓 PUBLIC ROUTES (no throttle)
Route::group([], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/filter', [ProductController::class, 'filter']);
});

// 🔒 PROTECTED ROUTES (no throttle)
Route::middleware(['jwt.auth'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::prefix('products')->group(function () {
        Route::get('/{asin}', [ProductController::class, 'show']);
        Route::get('/{category}/{brand}/{rating}/{asin}', [ProductController::class, 'showDetailed']);
    });
    
});
$kernel = app(Kernel::class);

// $reflection = new \ReflectionClass($kernel);
// $property = $reflection->getProperty('middlewareAliases');
// $property->setAccessible(true);
// $aliases = $property->getValue($kernel);
// dd($aliases);
#dd(app('router')->getMiddleware());

// ✅ EXTERNAL API ROUTES (rate-limited + secured)
// ✅ External Public API (secured with API key only)
Route::middleware(['check.apikey'])->group(function () {
    Route::get('external/products', [ProductController::class, 'index']);
    Route::get('external/products/filter', [ProductController::class, 'filter']);
    Route::get('external/products/brands', [ProductController::class, 'getBrands']);
    Route::get('external/products/categories', [ProductController::class, 'getCategories']);
    Route::get('external/products/{asin}', [ProductController::class, 'show']); // ✅ ADD THIS LINE


    
});





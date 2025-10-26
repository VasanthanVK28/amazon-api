<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);       // /api/products
    Route::get('/filter', [ProductController::class, 'filter']); // /api/products/filter
    Route::get('/{asin}', [ProductController::class, 'show']);   // /api/products/{asin}
});

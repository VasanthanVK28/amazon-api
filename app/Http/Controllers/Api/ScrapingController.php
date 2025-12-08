<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scrape;
use Illuminate\Http\JsonResponse;

class ScrapingController extends Controller
{
     public function index(): JsonResponse
    {
        $products = Scrape::all();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scrape;
use Illuminate\Http\JsonResponse;

class ScrapingController extends Controller
{
    public function index(): JsonResponse
    {
        $rawData = Scrape::all();

        // Convert MongoDB BSON objects to clean JSON with proper types
        $products = array_map(function ($item) {
            return [
                '_id' => (string)($item['_id'] ?? ''),
                'asin' => $item['asin'] ?? '',
                'brand' => $item['brand'] ?? '',
                'title' => $item['title'] ?? '',
                'price' => isset($item['price']) ? floatval($item['price']) : 0,  // <--- cast to float
                'rating' => isset($item['rating']) ? floatval($item['rating']) : 0,
                'reviews' => isset($item['reviews']) ? intval($item['reviews']) : 0,
                'tags' => $item['tags'] ?? [],
                'image_url' => $item['image_url'] ?? '',
            ];
        }, $rawData);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

     public function first()
    {
        $data = Scrape::first();
        return response()->json($data);
    }

    // Count documents
    public function count()
    {
        $count = Scrape::count();
        return response()->json(['count' => $count]);
    }

    
}

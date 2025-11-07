<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductAnalytics;
use Carbon\Carbon;
use MongoDB\Client as Mongo;
use MongoDB\BSON\ObjectId;

class AnalyticsController extends Controller
{
    /**
     * 🧭 List all analytics (optionally filter by date range)
     */
    public function index(Request $request)
    {
        $query = ProductAnalytics::query();

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to = Carbon::parse($request->to)->endOfDay();
            $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
        }

        $analytics = $query->get()->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'impressions' => $item->impressions,
                'clicks' => $item->clicks,
                'ctr' => $item->ctr ?? ($item->impressions > 0 ? round(($item->clicks / $item->impressions) * 100, 2) : 0),
                'date' => $item->date instanceof Carbon ? $item->date->toDateString() : $item->date,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * 🔍 Show analytics for a single product
     */
    public function show($product_id, Request $request)
    {
        $query = ProductAnalytics::where('product_id', $product_id);

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to = Carbon::parse($request->to)->endOfDay();
            $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
        }

        $analytics = $query->get()->map(function ($item) {
            return [
                'product_name' => $item->product_name,
                'date' => $item->date instanceof Carbon ? $item->date->toDateString() : $item->date,
                'impressions' => $item->impressions,
                'clicks' => $item->clicks,
                'ctr' => $item->ctr ?? ($item->impressions > 0 ? round(($item->clicks / $item->impressions) * 100, 2) : 0),
            ];
        });

        if ($analytics->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No analytics found for this product'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * ➕ Create / update analytics manually (for testing or admin update)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
            'impressions' => 'required|integer',
            'clicks' => 'required|integer',
            'date' => 'required|date',
        ]);

        // 🔍 Fetch product title automatically from MongoDB
        $product_name = $this->fetchProductName($validated['product_id']);

        $analytics = ProductAnalytics::updateOrCreate(
            [
                'product_id' => $validated['product_id'],
                'date' => $validated['date'],
            ],
            [
                'product_name' => $product_name,
                'impressions' => $validated['impressions'],
                'clicks' => $validated['clicks'],
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'product_id' => $analytics->product_id,
                'product_name' => $analytics->product_name,
                'impressions' => $analytics->impressions,
                'clicks' => $analytics->clicks,
                'ctr' => $analytics->ctr ?? ($analytics->impressions > 0 ? round(($analytics->clicks / $analytics->impressions) * 100, 2) : 0),
                'date' => $analytics->date,
            ]
        ]);
    }

    /**
     * 👁️ Track an impression (auto increment)
     */
    public function trackImpression(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
        ]);

        $date = Carbon::now()->toDateString();
        $product_name = $this->fetchProductName($validated['product_id']);

        $analytics = ProductAnalytics::firstOrCreate(
            ['product_id' => $validated['product_id'], 'date' => $date],
            ['product_name' => $product_name, 'impressions' => 0, 'clicks' => 0]
        );

        $analytics->increment('impressions');

        return response()->json(['status' => 'success', 'message' => 'Impression recorded']);
    }

    /**
     * 🖱️ Track a click (auto increment)
     */
    public function trackClick(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
        ]);

        $date = Carbon::now()->toDateString();
        $product_name = $this->fetchProductName($validated['product_id']);

        $analytics = ProductAnalytics::firstOrCreate(
            ['product_id' => $validated['product_id'], 'date' => $date],
            ['product_name' => $product_name, 'impressions' => 0, 'clicks' => 0]
        );

        $analytics->increment('clicks');

        return response()->json(['status' => 'success', 'message' => 'Click recorded']);
    }

    /**
     * 🧠 Helper — Fetch product title from laptops collection
     */
    private function fetchProductName($productId)
    {
        try {
            $mongo = new Mongo(env('MONGO_DSN', 'mongodb://127.0.0.1:27017'));
            $db = $mongo->selectDatabase(env('DB_DATABASE', 'amazon_clone'));
            $laptops = $db->selectCollection('laptops');

            $product = $laptops->findOne(['_id' => new ObjectId($productId)]);
            return $product['title'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}

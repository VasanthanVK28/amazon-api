<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;

class EmbedController extends Controller
{
    public function getWidgetData($api_key)
    {
        $user = User::where('api_key', $api_key)->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid API key'], 403);
        }

        $products = Product::orderBy('popularity', 'desc')->take(10)->get();

        $settings = json_decode($user->widget_settings ?? '{}');

        return response()->json([
            'status' => 'success',
            'data' => [
                'products' => $products,
                'settings' => $settings
            ]
        ]);
    }
}

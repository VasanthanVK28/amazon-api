<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmbedController extends Controller
{
    
    public function popularProducts(Request $request)
    {
        // Optional: fetch product data from your main database or API
        // Here just return your React widget view

        return response()->view('embed.popular-products', [], 200)
            ->header('Content-Type', 'text/html');
    }
}

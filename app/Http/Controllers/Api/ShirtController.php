<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shirt;

class ShirtController extends Controller
{
    public function index()
    {
        $shirts = Shirt::all();
        $count = Shirt::count();

        return response()->json([
            'status' => 'success',
            'count' => $count,
            'shirts' => $shirts
        ], 200);
    }
}

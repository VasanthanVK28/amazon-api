<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laptop;
use Illuminate\Http\Request;

class LaptopController extends Controller
{
    public function index()
    {
        return response()->json([
            'count' => Laptop::count(),
            'laptops' => Laptop::all(),
        ]);
    }
}

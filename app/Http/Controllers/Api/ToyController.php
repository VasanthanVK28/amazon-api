<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Toy;

class ToyController extends Controller
{
    public function index()
    {
        $toys = Toy::all();
        $count = Toy::count();

        return response()->json([
            'status' => 'success',
            'count' => $count,
            'toys' => $toys
        ], 200);
    }
}

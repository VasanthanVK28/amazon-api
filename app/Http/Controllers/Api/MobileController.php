<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mobile;

class MobileController extends Controller
{
    public function index()
    {
        $mobiles = Mobile::all();
        $count = Mobile::count();

        return response()->json([
            'status' => 'success',
            'count' => $count,
            'mobiles' => $mobiles
        ], 200);
    }
}

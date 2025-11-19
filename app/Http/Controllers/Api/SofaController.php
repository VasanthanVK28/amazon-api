<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sofa;

class SofaController extends Controller
{
    public function index()
    {
        $sofas = Sofa::all();
        $count = Sofa::count();

        return response()->json([
            'status' => 'success',
            'count' => $count,
            'sofas' => $sofas
        ]);
    }
}

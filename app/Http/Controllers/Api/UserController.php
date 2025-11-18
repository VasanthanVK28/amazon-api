<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Get total number of users
     */
    public function totalUsers()
    {
        // Count all users in the MongoDB collection
        $count = User::count();

        return response()->json([
            'status' => 'success',
            'total_users' => $count
        ]);
    }
}

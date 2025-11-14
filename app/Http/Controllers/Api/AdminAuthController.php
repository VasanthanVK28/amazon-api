<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    // ------------------------ Admin Register ------------------------
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:admins,username',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6',
        ]);

        $admin = Admin::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Admin registered successfully!',
            'admin' => [
                'username' => $admin->username,
                'email' => $admin->email,
            ]
        ], 201);
    }

    // ------------------------ Admin Login ------------------------
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $validated['email'])->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        // Store admin session
        session([
            'admin_id' => $admin->id,   // use id, not _id
            'admin_email' => $admin->email
        ]);

        return response()->json([
            'message' => 'Login successful!',
            'admin' => [
                'username' => $admin->username,
                'email' => $admin->email,
            ]
        ]);
    }

    // ------------------------ Admin Logout ------------------------
    public function logout()
    {
        session()->forget(['admin_id', 'admin_email']);

        return response()->json(['message' => 'Logged out successfully']);
    }

    // ------------------------ Admin Profile ------------------------
    public function profile()
    {
        $adminId = session('admin_id');

        if (!$adminId) {
            return response()->json(['message' => 'Not logged in'], 401);
        }

        $admin = Admin::find($adminId);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        return response()->json([
            'username' => $admin->username,
            'email' => $admin->email,
        ]);
    }
}

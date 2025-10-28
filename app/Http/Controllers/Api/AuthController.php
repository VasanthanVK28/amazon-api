<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * @method void middleware($middleware, array $options = [])
 */

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['login', 'register']]);
    }

    /**
     * 🧩 Register a new user
     */
    
    public function register(Request $request)
    {
        // Decode JSON body into an associative array
        $data = json_decode($request->getContent(), true);

        // Validate input data
        $validated = validator($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ])->validate();

        // Create new user with hashed password
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'api_key' => Str::random(60), // 👈 unique API key per user
        ]);

        return response()->json([
            'message' => '✅ User registered successfully!',
            'user' => $user
        ], 201);
    }

    /**
     * 🔐 User login and issue JWT token
     */
    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    try {
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => '❌ Invalid credentials'], 401);
        }
    } catch (JWTException $e) {
        return response()->json(['error' => '⚠️ Could not create token'], 500);
    }

    // ✅ Get authenticated user
    $user = JWTAuth::user();

    // 🔁 Optional: generate new API key on each login
    $user->api_key = Str::random(60);
    $user->save();

    return response()->json([
        'message' => '✅ Login successful',
        'token' => $token,
        'token_type' => 'bearer',
        'expires_in' => JWTAuth::factory()->getTTL() * 60,
        'api_key' => $user->api_key, // 👈 include it in response
    ]);
}

    /**
     * 👤 Get the authenticated user (using Bearer token)
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => '⚠️ Token is invalid or expired'], 401);
        }
    }

    /**
     * 📋 Get user profile (authenticated user via Request)
     */
    public function profile(Request $request)
    {
        return response()->json([
            'message' => '✅ Authenticated user profile',
            'user' => $request->user()
        ]);
    }

    /**
     * 🔁 Refresh the JWT token (optional)
     */
    public function refresh()
{
    try {
        $newToken = JWTAuth::parseToken()->refresh();

        return response()->json([
            'message' => '🔄 Token refreshed successfully',
            'token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    } catch (JWTException $e) {
        return response()->json(['error' => '⚠️ Unable to refresh token'], 401);
    }
}

    /**
     * 🚪 Logout (invalidate the JWT)
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => '✅ Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => '⚠️ Failed to logout, token invalid'], 500);
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

class CheckApiKey
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('CheckApiKey middleware executed', [
            'path' => $request->path(),
            'ip'   => $request->ip(),
        ]);

        // 🧾 Log all headers for debugging
        Log::info('Incoming headers', $request->headers->all());

        // 🔑 Accept API key from header OR query parameter
        $apiKey = $request->header('x-api-key')
                 ?? $request->header('X-API-KEY');
                 

        // 🚨 Step 1: Check if API key is provided
        if (empty($apiKey)) {
            Log::warning('Missing API key in request.', [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing API key.',
            ], 401);
        }

        // 🧠 Step 2: Validate API key against MongoDB users
        $user = User::where('api_key', trim($apiKey))->first();

        if (!$user) {
            Log::warning('Invalid API key attempt detected.', [
                'ip' => $request->ip(),
                'provided_key' => $apiKey,
            ]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key.',
            ], 401);
        }

        // ✅ Step 3: Apply per-user rate limiting
        $rateLimitResponse = $this->checkRateLimit($user->_id, $request);
        if ($rateLimitResponse) return $rateLimitResponse;

        // 👇 Optional: make user available in request
        $request->merge(['api_user' => $user]);

        return $next($request);
    }

    protected function checkRateLimit($userId, Request $request)
    {
        $key = Str::lower($userId . '|' . $request->ip());
        $maxAttempts = 60; // 60 requests/minute
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            Log::warning('Rate limit exceeded for user', [
                'user_id' => $userId,
                'ip' => $request->ip(),
                'retry_after' => $retryAfter,
            ]);

            return response()->json([
                'error' => 'Too Many Requests',
                'message' => "Rate limit exceeded. Try again in {$retryAfter} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, $decaySeconds);
        return null;
    }
}

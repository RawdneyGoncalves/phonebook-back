<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        $publicRoutes = [
            'api/auth/register',
            'api/auth/login',
        ];

        foreach ($publicRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Missing or invalid authentication token'
            ], 401);
        }

        return $next($request);
    }
}

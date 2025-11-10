<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Authenticate extends Middleware
{
    /**
     * Handle unauthenticated requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }

    /**
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthenticated($request, array $guards): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }
}

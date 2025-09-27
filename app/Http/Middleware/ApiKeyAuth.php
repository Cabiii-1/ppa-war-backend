<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey || !hash_equals(config('app.external_api_key'), $apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized API access'
            ], 401);
        }

        return $next($request);
    }
}

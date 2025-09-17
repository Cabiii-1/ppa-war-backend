<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use App\AesGcmHelper;

/**
 * ValidateAuthentication Middleware
 * Validates user tokens against SSO database and attaches user to request
 * Based on Sir Pold's implementation from the existing SSO system
 */
class ValidateAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
                'code' => 'AUTH_REQUIRED'
            ], 401);
        }

        try {
            // Extract the token (removes "Bearer ")
            $base64Token = substr($authorization, 7);
            $aes256gcmToken = base64_decode($base64Token);
            $passKey = env('APP_AES256GCM_KEY');
            $key = AesGcmHelper::normalizeKey($passKey);
            $md5token = AesGcmHelper::decrypt($aes256gcmToken, $key);

            // Check if token exists in RefreshToken table
            $hasToken = pgc_sso()->table('RefreshToken')
                ->where('token', $md5token)
                ->first();

            if ($hasToken) {
                $now = new \DateTime();
                $expiresAt = new \DateTime($hasToken->expiresAt);
                $ttl = (int) env('APP_TTL', 2) * 3600; // Convert hours to seconds

                // Check if token is still valid (not expired)
                if ($now <= $expiresAt) {
                    // Extend token expiration (automatic refresh on use)
                    $newExpiresAt = (clone $now)->modify('+' . env('APP_TTL', 2) . ' hours')->format('Y-m-d H:i:s');

                    pgc_sso()->table('RefreshToken')
                        ->where('userId', $hasToken->userId)
                        ->where('token', $hasToken->token)
                        ->update(['expiresAt' => $newExpiresAt]);

                    // Get user from SSO database
                    $users_employee = pgc_sso()->table('users_employee')
                        ->where('employee_id', $hasToken->userId)
                        ->first();

                    // Get employee details from employee database
                    $user = pgc_employee()->table('vEmployee')
                        ->where('emp_no', $hasToken->userId)
                        ->first();

                    if ($user && $users_employee) {
                        // Merge SSO and employee data
                        $user->username = $users_employee->username;
                        $user->token = $base64Token;

                        // Set user resolver for the request
                        $request->setUserResolver(function () use ($user) {
                            return $user;
                        });

                        return $next($request);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
                'code' => 'INVALID_TOKEN'
            ], 401);

        } catch (\Exception $e) {
            // Handle decryption or other errors
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
                'code' => 'TOKEN_ERROR'
            ], 401);
        }
    }
}
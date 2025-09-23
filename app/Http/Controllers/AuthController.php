<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\AesGcmHelper;

/**
 * Authentication Controller
 * Handles login, logout, and user info retrieval
 * Based on Sir Pold's implementation from the existing SSO system
 */
class AuthController extends Controller
{
    /**
     * Handle user login
     * Validates credentials against SSO database and generates token
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $req)
    {
        $req->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $username = $req->username;
        $password = $req->password;

        // Query SSO database for user account
        $accounts = pgc_sso()->table('users_employee')
            ->where('username', $username)
            ->first();

        if (!$accounts) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $now = new \DateTime();
        $hitRate = $accounts->hit_rate;
        $bannedTime = $accounts->banned_time ? new \DateTime($accounts->banned_time) : null;
        $lastAttempt = $accounts->last_failed_attempt ? new \DateTime($accounts->last_failed_attempt) : null;

        // Reset hit_rate if no attempt in the last 1 minute and hit_rate < 5
        if ($hitRate < 5 && $lastAttempt && $now->getTimestamp() - $lastAttempt->getTimestamp() >= 60) {
            $hitRate = 0;
            pgc_sso()->table('users_employee')
                ->where('id', $accounts->id)
                ->update([
                    'hit_rate' => 0,
                ]);
        }

        // Check if user is banned and 5 minutes have not passed
        if ($hitRate >= 5 && $bannedTime && $now->getTimestamp() - $bannedTime->getTimestamp() < 300) {
            return response()->json([
                'message' => 'Login temporarily disabled. Please try again after 5 minutes.'
            ], 429);
        }

        // Password match (plaintext check as per existing implementation)
        if (trim($accounts->password) === trim($password)) {
            // Reset failed attempt counters on successful login
            pgc_sso()->table('users_employee')
                ->where('id', $accounts->id)
                ->update([
                    'last_failed_attempt' => null,
                    'hit_rate' => 0,
                    'banned_time' => null,
                ]);

            // Generate token using the same method as Sir Pold's implementation
            $now = new \DateTime();
            $createdAt = $now->format('Y-m-d H:i:s');
            $expiresAt = (clone $now)->modify('+' . env('APP_TTL', 2) . ' hours')->format('Y-m-d H:i:s');
            $userId = $accounts->employee_id;

            // Generate MD5 token (matching existing SSO implementation)
            $md5token = md5($userId . $accounts->username . $expiresAt);

            // Save token in RefreshToken table
            pgc_sso()->table('RefreshToken')
                ->insert([
                    'createdAt' => $createdAt,
                    'expiresAt' => $expiresAt,
                    'userId' => $userId,
                    'token' => $md5token,
                ]);

            // Encrypt token using AES-256-GCM
            $passKey = env('APP_AES256GCM_KEY');
            $key = AesGcmHelper::normalizeKey($passKey);
            $aes256gcmToken = AesGcmHelper::encrypt($md5token, $key);
            $base64Token = base64_encode($aes256gcmToken);

            // Get employee details from employee database using employee_id
            $employeeDetails = pgc_employee()->table('vEmployee')
                ->where('emp_no', $accounts->employee_id)
                ->first();

            // Get user data for response
            $userData = [
                'id' => $accounts->id,
                'employee_id' => $accounts->employee_id ?? $accounts->emp_no ?? 'N/A',
                'name' => $employeeDetails->Fullname ?? $accounts->Fullname ?? $accounts->name ?? $accounts->username ?? 'Unknown User',
                'email' => $employeeDetails->EmailAdd ?? $accounts->EmailAdd ?? $accounts->email ?? 'N/A',
                'department' => $employeeDetails->DeptDesc ?? $accounts->DeptDesc ?? $accounts->department ?? 'N/A',
                'position' => $employeeDetails->PosDesc ?? $accounts->PosDesc ?? $accounts->position ?? 'N/A',
                'username' => $accounts->username,
                'manager_id' => $accounts->manager_id ?? null,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $base64Token,
                    'token_type' => 'Bearer',
                    'expires_in' => env('APP_TTL', 2) * 3600, // Convert hours to seconds
                    'user' => $userData
                ]
            ]);
        } else {
            // Invalid password - increment hit rate and update last_failed_attempt
            $hitRate += 1;

            $update = [
                'hit_rate' => $hitRate,
                'last_failed_attempt' => $now->format('Y-m-d H:i:s'),
            ];

            if ($hitRate >= 5) {
                $update['banned_time'] = $now->format('Y-m-d H:i:s');
            }

            pgc_sso()->table('users_employee')
                ->where('id', $accounts->id)
                ->update($update);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }
    }

    /**
     * Handle user logout
     * Invalidates the current token by removing it from RefreshToken table
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $req)
    {
        $user = $req->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 401);
        }

        $base64Token = $user->token;
        $aes256gcmToken = base64_decode($base64Token);
        $passKey = env('APP_AES256GCM_KEY');
        $key = AesGcmHelper::normalizeKey($passKey);
        $md5token = AesGcmHelper::decrypt($aes256gcmToken, $key);

        // Remove token from RefreshToken table
        pgc_sso()->table('RefreshToken')
            ->where('token', $md5token)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get current authenticated user information
     * Returns user details from employee database
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $req)
    {
        $user = $req->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 401);
        }

        // Get employee details from employee database using emp_no
        $employeeDetails = pgc_employee()->table('vEmployee')
            ->where('emp_no', $user->emp_no)
            ->first();

        // Format user data according to the implementation guide
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id ?? null,
                'employee_id' => $user->emp_no ?? 'N/A',
                'name' => $employeeDetails->Fullname ?? $user->Fullname ?? $user->username ?? 'Unknown User',
                'email' => $employeeDetails->EmailAdd ?? $user->EmailAdd ?? 'N/A',
                'department' => $employeeDetails->DeptDesc ?? $user->DeptDesc ?? 'N/A',
                'position' => $employeeDetails->PosDesc ?? $user->PosDesc ?? 'N/A',
                'username' => $user->username ?? 'N/A',
                'permissions' => ['view_reports', 'create_reports', 'edit_own_reports']
            ]
        ]);
    }

    /**
     * Logout from all devices
     * Removes all tokens for the current user
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAllDevices(Request $req)
    {
        $user = $req->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 401);
        }

        // Remove all tokens for this user
        pgc_sso()->table('RefreshToken')
            ->where('userId', $user->emp_no)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out from all devices'
        ]);
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use DateTime;

class SSOAuthService
{
    private const ACCESS_TOKEN_EXPIRY = 15; // 15 minutes
    private const REFRESH_TOKEN_EXPIRY = 7 * 24 * 60; // 7 days in minutes

    /**
     * Validate credentials against SSO database
     *
     * @param string $username Username or employee ID
     * @param string $password Plain text password
     * @return array|null User data if valid, null if invalid
     */
    public function validateCredentials(string $username, string $password): ?array
    {
        try {
            // Look up user in users_employee table
            $user = DB::connection('sso_db')
                ->table('users_employee')
                ->where('username', $username)
                ->orWhere('employee_id', $username)
                ->where('is_active', 1)
                ->first();

            if (!$user) {
                return null;
            }

            // Check if password matches (assuming plain text comparison for now)
            // Note: In production, you'd want to use proper hashing
            if ($user->password !== $password) {
                return null;
            }

            // Get employee details from employee database
            $employeeDetails = $this->getUserDetails($user->employee_id);

            return [
                'sso_user' => $user,
                'employee' => $employeeDetails,
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'username' => $user->username
            ];

        } catch (Exception $e) {
            throw new Exception('SSO validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate authentication tokens (access + refresh)
     *
     * @param int $userId User ID from SSO system
     * @param string $employeeId Employee ID
     * @return array Contains access_token, refresh_token, expires_in
     */
    public function generateTokens(int $userId, string $employeeId): array
    {
        try {
            $now = time() * 1000; // milliseconds

            // Generate access token payload
            $accessPayload = [
                'sub' => (string)$userId,
                'employee_id' => $employeeId,
                'iat' => $now,
                'exp' => $now + (self::ACCESS_TOKEN_EXPIRY * 60 * 1000)
            ];

            // Generate refresh token payload with JTI
            $jti = $this->generateJTI();
            $refreshPayload = [
                'sub' => (string)$userId,
                'employee_id' => $employeeId,
                'jti' => $jti,
                'iat' => $now,
                'exp' => $now + (self::REFRESH_TOKEN_EXPIRY * 60 * 1000)
            ];

            // Encrypt tokens
            $accessToken = \AesGcmHelper::encryptPayload($accessPayload);
            $refreshToken = \AesGcmHelper::encryptPayload($refreshPayload);

            // Store refresh token in database
            $this->storeRefreshToken($userId, $jti, $refreshToken, $refreshPayload['exp']);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => self::ACCESS_TOKEN_EXPIRY * 60 // seconds
            ];

        } catch (Exception $e) {
            throw new Exception('Token generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate existing access token
     *
     * @param string $token Encrypted access token
     * @return array|null User data if valid, null if invalid/expired
     */
    public function validateAccessToken(string $token): ?array
    {
        try {
            $payload = \AesGcmHelper::decryptPayload($token);

            // Check expiration
            if (time() * 1000 > $payload['exp']) {
                return null; // Token expired
            }

            // Get user details
            $userDetails = $this->getUserById((int)$payload['sub']);
            if (!$userDetails) {
                return null;
            }

            return [
                'user_id' => (int)$payload['sub'],
                'employee_id' => $payload['employee_id'],
                'user_details' => $userDetails
            ];

        } catch (Exception $e) {
            return null; // Invalid token
        }
    }

    /**
     * Validate refresh token and generate new tokens
     *
     * @param string $refreshToken Encrypted refresh token
     * @return array|null New tokens if valid, null if invalid/expired
     */
    public function refreshTokens(string $refreshToken): ?array
    {
        try {
            $payload = \AesGcmHelper::decryptPayload($refreshToken);

            // Check expiration
            if (time() * 1000 > $payload['exp']) {
                return null; // Token expired
            }

            // Check if token exists in DB and is not revoked
            $tokenRecord = DB::connection('sso_db')
                ->table('RefreshToken')
                ->where('jti', $payload['jti'])
                ->whereNull('revokedAt')
                ->first();

            if (!$tokenRecord) {
                return null; // Token not found or revoked
            }

            // Generate new tokens
            $newTokens = $this->generateTokens((int)$payload['sub'], $payload['employee_id']);

            // Revoke old refresh token
            $this->revokeRefreshToken($payload['jti']);

            return $newTokens;

        } catch (Exception $e) {
            return null; // Invalid token
        }
    }

    /**
     * Revoke refresh token (logout)
     *
     * @param string $refreshToken Encrypted refresh token
     * @return bool Success status
     */
    public function revokeToken(string $refreshToken): bool
    {
        try {
            $payload = \AesGcmHelper::decryptPayload($refreshToken);
            return $this->revokeRefreshToken($payload['jti']);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get user details from employee database
     *
     * @param string $employeeId Employee ID
     * @return array|null Employee information
     */
    public function getUserDetails(string $employeeId): ?array
    {
        try {
            $employee = DB::connection('employee_db')
                ->table('Employee')
                ->where('emp_no', $employeeId)
                ->first();

            if (!$employee) {
                return null;
            }

            // Get department name
            $department = DB::connection('employee_db')
                ->table('Department')
                ->where('DeptCode', $employee->Department)
                ->first();

            return [
                'employee_id' => $employee->emp_no,
                'id_number' => $employee->id_number,
                'name' => trim($employee->First_name . ' ' . $employee->Last_name),
                'first_name' => $employee->First_name,
                'last_name' => $employee->Last_name,
                'middle_name' => $employee->Mid_name,
                'department_code' => $employee->Department,
                'department_name' => $department ? $department->DeptDesc : null,
                'division' => $employee->Division,
                'section' => $employee->Section,
                'position_code' => $employee->Pos_Code,
                'status' => $employee->Emp_Status
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to get user details: ' . $e->getMessage());
        }
    }

    /**
     * Get user by ID from SSO database
     */
    private function getUserById(int $userId): ?array
    {
        $user = DB::connection('sso_db')
            ->table('users_employee')
            ->where('id', $userId)
            ->where('is_active', 1)
            ->first();

        if (!$user) {
            return null;
        }

        $employeeDetails = $this->getUserDetails($user->employee_id);

        return [
            'sso_user' => $user,
            'employee' => $employeeDetails
        ];
    }

    /**
     * Generate unique identifier for refresh token
     */
    private function generateJTI(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Store refresh token in database
     */
    private function storeRefreshToken(int $userId, string $jti, string $encryptedToken, int $expiresAt): void
    {
        DB::connection('sso_db')
            ->table('RefreshToken')
            ->insert([
                'userId' => $userId,
                'jti' => $jti,
                'encryptedToken' => $encryptedToken,
                'expiresAt' => new DateTime('@' . ($expiresAt / 1000)),
                'created_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * Revoke refresh token by JTI
     */
    private function revokeRefreshToken(string $jti): bool
    {
        $affected = DB::connection('sso_db')
            ->table('RefreshToken')
            ->where('jti', $jti)
            ->update([
                'revokedAt' => now(),
                'updated_at' => now()
            ]);

        return $affected > 0;
    }
}
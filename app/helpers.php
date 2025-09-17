<?php

use Illuminate\Support\Facades\DB;

/**
 * Database helper functions for accessing PGC databases
 * Based on Sir Pold's implementation from the existing SSO system
 */

if (!function_exists('pgc_sso')) {
    /**
     * Get a connection to the PGC SSO database
     * This database contains authentication tables (users_employee, RefreshToken, etc.)
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    function pgc_sso()
    {
        return DB::connection('sso_db');
    }
}

if (!function_exists('pgc_employee')) {
    /**
     * Get a connection to the PGC Employee database
     * This database contains employee information (vEmployee table, etc.)
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    function pgc_employee()
    {
        return DB::connection('employee_db');
    }
}

/**
 * AES-GCM encryption/decryption helper class
 * Based on the existing SSO system's encryption methods
 */
class AesGcmHelper
{
    private const IV_SIZE = 12; // 96 bits
    private const TAG_SIZE = 16; // 128 bits
    private const KEY_SIZE = 32; // 256 bits

    public static function normalizeKey(string $key): string
    {
        $keyBuffer = $key;

        if (strlen($keyBuffer) > self::KEY_SIZE) {
            return substr($keyBuffer, 0, self::KEY_SIZE);
        }

        if (strlen($keyBuffer) < self::KEY_SIZE) {
            return str_pad($keyBuffer, self::KEY_SIZE, '0');
        }

        return $keyBuffer;
    }

    public static function encrypt(string $plaintext, string $key): string
    {
        $normalizedKey = self::normalizeKey($key);
        $iv = random_bytes(self::IV_SIZE);

        $encrypted = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $normalizedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new Exception('Encryption failed');
        }

        $combined = $iv . $tag . $encrypted;
        return base64_encode($combined);
    }

    public static function decrypt(string $base64Input, string $key): string
    {
        $normalizedKey = self::normalizeKey($key);
        $input = base64_decode($base64Input);

        if ($input === false) {
            throw new Exception('Invalid base64 input');
        }

        $iv = substr($input, 0, self::IV_SIZE);
        $tag = substr($input, self::IV_SIZE, self::TAG_SIZE);
        $ciphertext = substr($input, self::IV_SIZE + self::TAG_SIZE);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $normalizedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new Exception('Decryption failed');
        }

        return $decrypted;
    }

    public static function encryptPayload(array $payload): string
    {
        $jsonPayload = json_encode($payload);
        $key = config('app.key') ?? 'default-encryption-key-256-bit';
        return self::encrypt($jsonPayload, $key);
    }

    public static function decryptPayload(string $encryptedPayload): array
    {
        $key = config('app.key') ?? 'default-encryption-key-256-bit';
        $jsonPayload = self::decrypt($encryptedPayload, $key);
        $payload = json_decode($jsonPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON payload after decryption');
        }

        return $payload;
    }
}
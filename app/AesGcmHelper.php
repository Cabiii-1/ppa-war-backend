<?php

namespace App;

/**
 * AES-256-GCM Helper class for token encryption/decryption
 * Based on Sir Pold's implementation from the existing SSO system
 */
class AesGcmHelper
{
    const IV_SIZE = 12;   // 96-bit nonce (standard for GCM)
    const TAG_SIZE = 16;  // 128-bit tag (recommended)

    /**
     * Encrypt plaintext using AES-256-GCM
     *
     * @param string $plaintext The text to encrypt
     * @param string $key The encryption key
     * @return string Base64 encoded encrypted data with format: [IV][TAG][CIPHERTEXT]
     */
    public static function encrypt(string $plaintext, string $key): string
    {
        $iv = random_bytes(self::IV_SIZE);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            self::normalizeKey($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_SIZE
        );

        // Final format: [IV][TAG][CIPHERTEXT]
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt base64 encoded data using AES-256-GCM
     *
     * @param string $base64Input Base64 encoded encrypted data
     * @param string $key The decryption key
     * @return string Decrypted plaintext
     */
    public static function decrypt(string $base64Input, string $key): string
    {
        $raw = base64_decode($base64Input);

        $iv = substr($raw, 0, self::IV_SIZE);
        $tag = substr($raw, self::IV_SIZE, self::TAG_SIZE);
        $ciphertext = substr($raw, self::IV_SIZE + self::TAG_SIZE);

        return openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            self::normalizeKey($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            ''
        );
    }

    /**
     * Normalize key to 32 bytes (AES-256 key length)
     * Pads or trims key to ensure it's exactly 32 bytes
     *
     * @param string $key The original key
     * @return string Normalized 32-byte key
     */
    public static function normalizeKey(string $key): string
    {
        // Pads or trims to 32 bytes (AES-256 key length)
        return substr(str_pad($key, 32, '0'), 0, 32);
    }
}
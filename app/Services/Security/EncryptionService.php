<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * AES-256 Encryption Service
 *
 * Provides client-side encryption for files before storage.
 * SOC2 Type II compliant with AES-256 encryption at rest.
 */
class EncryptionService
{
    /**
     * The cipher algorithm used for encryption.
     */
    public const CIPHER = 'aes-256-gcm';

    /**
     * The key derivation algorithm.
     */
    public const KEY_DERIVATION = 'sha256';

    /**
     * IV length for AES-256-GCM.
     */
    public const IV_LENGTH = 12;

    /**
     * Authentication tag length for GCM mode.
     */
    public const TAG_LENGTH = 16;

    /**
     * Generate a new encryption key for a data room.
     */
    public function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Encrypt file contents using AES-256-GCM.
     *
     * @param  string  $contents  Raw file contents
     * @param  string  $key  Base64-encoded encryption key
     * @return array{encrypted: string, iv: string, tag: string, checksum: string}
     */
    public function encryptFile(string $contents, string $key): array
    {
        $decodedKey = base64_decode($key);
        if (strlen($decodedKey) !== 32) {
            throw new RuntimeException('Invalid encryption key length. Expected 256-bit key.');
        }

        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $encrypted = openssl_encrypt(
            $contents,
            self::CIPHER,
            $decodedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed: '.openssl_error_string());
        }

        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'checksum' => hash('sha256', $contents),
        ];
    }

    /**
     * Decrypt file contents using AES-256-GCM.
     *
     * @param  string  $encryptedContents  Base64-encoded encrypted contents
     * @param  string  $key  Base64-encoded encryption key
     * @param  string  $iv  Base64-encoded initialization vector
     * @param  string  $tag  Base64-encoded authentication tag
     * @return string Decrypted contents
     */
    public function decryptFile(string $encryptedContents, string $key, string $iv, string $tag): string
    {
        $decodedKey = base64_decode($key);
        $decodedIv = base64_decode($iv);
        $decodedTag = base64_decode($tag);
        $decodedContents = base64_decode($encryptedContents);

        if (strlen($decodedKey) !== 32) {
            throw new RuntimeException('Invalid encryption key length. Expected 256-bit key.');
        }

        $decrypted = openssl_decrypt(
            $decodedContents,
            self::CIPHER,
            $decodedKey,
            OPENSSL_RAW_DATA,
            $decodedIv,
            $decodedTag
        );

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed: '.openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Verify file integrity using SHA-256 checksum.
     */
    public function verifyChecksum(string $contents, string $expectedChecksum): bool
    {
        return hash_equals($expectedChecksum, hash('sha256', $contents));
    }

    /**
     * Encrypt a string value for database storage.
     */
    public function encryptString(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a string value from database storage.
     */
    public function decryptString(string $encryptedValue): string
    {
        return Crypt::decryptString($encryptedValue);
    }

    /**
     * Generate a secure random token.
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash a value using SHA-256.
     */
    public function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Derive a key from a password using PBKDF2.
     */
    public function deriveKey(string $password, string $salt, int $iterations = 100000): string
    {
        return base64_encode(
            hash_pbkdf2(self::KEY_DERIVATION, $password, $salt, $iterations, 32, true)
        );
    }

    /**
     * Generate a salt for key derivation.
     */
    public function generateSalt(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Encrypt data for secure transmission.
     *
     * @return array{data: string, nonce: string}
     */
    public function seal(string $data, string $key): array
    {
        $nonce = random_bytes(self::IV_LENGTH);
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            base64_decode($key),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH
        );

        return [
            'data' => base64_encode($encrypted.$tag),
            'nonce' => base64_encode($nonce),
        ];
    }

    /**
     * Decrypt sealed data.
     */
    public function unseal(string $sealedData, string $nonce, string $key): string
    {
        $decoded = base64_decode($sealedData);
        $decodedNonce = base64_decode($nonce);
        $decodedKey = base64_decode($key);

        $tag = substr($decoded, -self::TAG_LENGTH);
        $encrypted = substr($decoded, 0, -self::TAG_LENGTH);

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $decodedKey,
            OPENSSL_RAW_DATA,
            $decodedNonce,
            $tag
        );

        if ($decrypted === false) {
            throw new RuntimeException('Failed to unseal data: '.openssl_error_string());
        }

        return $decrypted;
    }
}

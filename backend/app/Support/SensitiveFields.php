<?php

namespace App\Support;

/**
 * SensitiveFields — Sensitive Data Masking Utility
 *
 * Provides centralized sensitive field name registry and masking functions.
 * Prevents passwords, tokens, credit cards, and other PII from appearing
 * in logs, error responses, and telemetry.
 *
 * Usage:
 *   $maskedData = SensitiveFields::mask($data);
 *   // { "password": "***", "token": "tok_****...6k", "credit_card": "****-1234" }
 */
class SensitiveFields
{
    /**
     * Registry of sensitive field names and their masking rules.
     *
     * Each rule can be:
     * - 'full': Mask entire value (e.g., "***")
     * - 'partial': Show last 4 chars (e.g., "****-1234")
     * - 'truncate': Show prefix only (e.g., "tok_****...")
     *
     * @var array<string, string>
     */
    private static array $sensitiveFields = [
        // Authentication
        'password' => 'full',
        'password_confirmation' => 'full',
        'current_password' => 'full',
        'new_password' => 'full',
        'reset_token' => 'full',
        'remember_token' => 'full',

        // Tokens & Keys
        'token' => 'truncate',
        'access_token' => 'truncate',
        'refresh_token' => 'truncate',
        'bearer_token' => 'truncate',
        'api_token' => 'truncate',
        'api_key' => 'truncate',
        'secret' => 'full',
        'api_secret' => 'full',
        'private_key' => 'full',
        'secret_key' => 'full',

        // Payment Information
        'credit_card' => 'partial',
        'card_number' => 'partial',
        'card' => 'partial',
        'ccv' => 'full',
        'cvv' => 'full',
        'cvc' => 'full',
        'expiration_date' => 'full',
        'exp_month' => 'full',
        'exp_year' => 'full',

        // Personal Information
        'ssn' => 'partial',
        'social_security_number' => 'partial',
        'passport_number' => 'partial',
        'driver_license' => 'partial',
        'phone' => 'partial',
        'phone_number' => 'partial',
        'mobile' => 'partial',
        'email' => 'partial',
        'email_address' => 'partial',

        // OAuth & External Auth
        'oauth_token' => 'truncate',
        'oauth_secret' => 'full',
        'github_token' => 'truncate',
        'google_token' => 'truncate',
        'facebook_token' => 'truncate',

        // Bank & Financial
        'account_number' => 'partial',
        'routing_number' => 'partial',
        'bank_account' => 'partial',
        'iban' => 'partial',
        'swift' => 'partial',
    ];

    /**
     * Mask sensitive fields in an array recursively.
     *
     * @param  array  $data  The data array to mask
     * @return array The masked data
     *
     * @example
     *   $data = ['password' => 'secret123', 'email' => 'user@example.com'];
     *   $masked = SensitiveFields::mask($data);
     *   // Result: ['password' => '***', 'email' => 'r...m']
     */
    public static function mask(array $data): array
    {
        return self::maskRecursive($data);
    }

    /**
     * Check if a field name is sensitive.
     *
     * @param  string  $fieldName  The field name to check
     * @return bool True if the field is sensitive, false otherwise
     */
    public static function isSensitive(string $fieldName): bool
    {
        $lowerName = strtolower($fieldName);

        return isset(self::$sensitiveFields[$lowerName]);
    }

    /**
     * Recursively mask sensitive fields in nested arrays.
     *
     * @param  mixed  $data  The data to mask
     * @return mixed The masked data
     */
    private static function maskRecursive(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = self::maskRecursive($value);
                } elseif (self::isSensitive($key)) {
                    $data[$key] = self::applyMask($value, self::$sensitiveFields[strtolower($key)]);
                }
            }
        }

        return $data;
    }

    /**
     * Apply masking rule to a field value.
     *
     * @param  mixed  $value  The value to mask
     * @param  string  $rule  The masking rule ('full', 'partial', 'truncate')
     * @return string The masked value
     */
    private static function applyMask(mixed $value, string $rule): string
    {
        $value = (string) $value;

        return match ($rule) {
            'full' => '***',
            'partial' => self::maskPartial($value),
            'truncate' => self::maskTruncate($value),
            default => '***',
        };
    }

    /**
     * Mask value showing only the last 4 characters.
     *
     * @param  string  $value  The value to mask
     * @return string Masked value (e.g., "****-1234")
     */
    private static function maskPartial(string $value): string
    {
        $length = strlen($value);

        if ($length <= 4) {
            return '****';
        }

        $lastFour = substr($value, -4);

        return '****-'.$lastFour;
    }

    /**
     * Mask value showing prefix only (e.g., "tok_****...").
     *
     * @param  string  $value  The value to mask
     * @return string Masked value (e.g., "tok_****...")
     */
    private static function maskTruncate(string $value): string
    {
        // Try to find a prefix (e.g., "tok_", "sk_")
        if (preg_match('/^([a-z]+_)/', $value, $matches)) {
            $prefix = $matches[1];

            return $prefix.'****...';
        }

        // Fallback: just show first 3 chars
        if (strlen($value) > 3) {
            return substr($value, 0, 3).'****...';
        }

        return '****...';
    }
}

<?php

namespace App\Logging;

use App\Support\SensitiveFields;

/**
 * MaskSensitiveProcessor — Monolog processor to mask PII in logs
 *
 * This processor masks sensitive values in the log 'message' string and
 * in the 'context' array before the record is written to handlers.
 */
class MaskSensitiveProcessor
{
    /**
     * Invoke the processor.
     */
    public function __invoke(array $record): array
    {
        // Mask common "password: value" patterns in the message
        if (! empty($record['message']) && is_string($record['message'])) {
            // Replace password: VALUE -> password: ***
            $record['message'] = preg_replace_callback(
                '/(password["\']?\s*[:=]\s*)([^\s,}]+)/i',
                fn ($m) => $m[1].'***',
                $record['message']
            );

            // Mask full credit card numbers (show only last 4)
            $record['message'] = preg_replace(
                '/\b(\d{4})[-\s]?(\d{4})[-\s]?(\d{4})[-\s]?(\d{4})\b/',
                '****-****-****-$4',
                $record['message']
            );

            // Mask token-like values starting with known prefixes
            $record['message'] = preg_replace('/\b(tok_[A-Za-z0-9_\-]{4,})\b/i', 'tok_****...', $record['message']);
            $record['message'] = preg_replace('/\b(sk_[A-Za-z0-9_\-]{4,})\b/i', 'sk_****...', $record['message']);
        }

        // Mask context array values using SensitiveFields::mask
        if (isset($record['context']) && is_array($record['context'])) {
            $record['context'] = SensitiveFields::mask($record['context']);
        }

        return $record;
    }
}

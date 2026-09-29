<?php

namespace App\Support;

/**
 * Normalizes phone numbers to E.164 (+265991234567).
 * Accepts local ("0991234567"), national without prefix ("991234567"),
 * international ("265991234567", "+265 99 123 4567").
 */
class PhoneNumber
{
    public static function normalize(string $input, ?string $countryCode = null): ?string
    {
        $countryCode ??= (string) config('bento.phone.default_country_code');
        $hasPlus = str_starts_with(trim($input), '+');
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            $e164 = $digits;
        } elseif (str_starts_with($digits, '00')) {
            $e164 = substr($digits, 2);
        } elseif (str_starts_with($digits, $countryCode) && strlen($digits) > 10) {
            $e164 = $digits;
        } else {
            $e164 = $countryCode.ltrim($digits, '0');
        }

        // E.164 allows up to 15 digits; require a sensible minimum.
        if (strlen($e164) < 8 || strlen($e164) > 15) {
            return null;
        }

        return '+'.$e164;
    }
}

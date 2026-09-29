<?php

namespace App\Rules;

use App\Services\LocaleService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every key of a `translations` object must be a registered language code. Inactive
 * languages are allowed so content can be prepared before the language goes live.
 */
class SupportedLocaleKeys implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $registered = app(LocaleService::class)->registeredLocales();
        foreach (array_keys($value) as $locale) {
            if (! in_array((string) $locale, $registered, true)) {
                $fail('validation.supported_locale')->translate(['locale' => (string) $locale]);
            }
        }
    }
}

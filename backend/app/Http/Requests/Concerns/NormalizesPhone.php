<?php

namespace App\Http\Requests\Concerns;

use App\Support\PhoneNumber;

trait NormalizesPhone
{
    protected function normalizePhoneFields(string ...$fields): void
    {
        foreach ($fields as $field) {
            $value = $this->input($field);
            if (is_string($value) && ($normalized = PhoneNumber::normalize($value)) !== null) {
                $this->merge([$field => $normalized]);
            }
        }
    }

    protected static function phoneRule(): string
    {
        return 'regex:/^\+[1-9]\d{7,14}$/';
    }
}

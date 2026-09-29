<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneFields('phone');
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', self::phoneRule()],
            'code' => ['required', 'string', 'regex:/^\d{4,8}$/'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['nullable', 'string', 'max:10'],
        ];
    }
}

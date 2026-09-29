<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
        ];
    }
}

<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneFields('phone');
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:80'],
            'latitude' => [$required, 'numeric', 'between:-90,90'],
            'longitude' => [$required, 'numeric', 'between:-180,180'],
            'area' => ['nullable', 'string', 'max:120'],
            'street' => ['nullable', 'string', 'max:190'],
            'building' => ['nullable', 'string', 'max:190'],
            'landmark' => ['nullable', 'string', 'max:190'],
            'delivery_note' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', self::phoneRule()],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}

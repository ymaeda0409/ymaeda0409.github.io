<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'max:10'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\AuthorizesResource;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
{
    use AuthorizesResource;

    protected function authorizedResource(): array
    {
        return [Language::class, 'language'];
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';

        return [
            // BCP-47 style: "ny", "tum", "zh-Hans", "pt-BR"
            'code' => $creating
                ? ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', Rule::unique('languages', 'code')]
                : ['prohibited'],
            'name' => [$required, 'string', 'max:60'],
            'native_name' => [$required, 'string', 'max:60'],
            'direction' => ['sometimes', Rule::in(['ltr', 'rtl'])],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

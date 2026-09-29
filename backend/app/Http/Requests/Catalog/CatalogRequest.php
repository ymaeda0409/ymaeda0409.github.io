<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class CatalogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'featured' => ['nullable', 'boolean'],
        ];
    }
}

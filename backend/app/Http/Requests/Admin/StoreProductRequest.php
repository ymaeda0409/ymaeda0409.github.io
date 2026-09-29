<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('manageProducts', $this->route('store'));

        return true;
    }

    public function rules(): array
    {
        return [
            'is_available' => ['sometimes', 'boolean'],
            'price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'stock_quantity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

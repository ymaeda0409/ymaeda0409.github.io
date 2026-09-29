<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Price preview: either a saved address (signed in) or a raw GPS point.
 */
class QuoteRequest extends FormRequest
{
    use OrderItemsRules;

    public function rules(): array
    {
        return [
            ...$this->itemRules(),
            'delivery_address_id' => ['required_without_all:latitude,longitude', 'nullable', 'integer'],
            'latitude' => ['required_without:delivery_address_id', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_without:delivery_address_id', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}

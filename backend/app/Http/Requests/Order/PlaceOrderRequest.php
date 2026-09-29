<?php

namespace App\Http\Requests\Order;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    use OrderItemsRules;

    public function rules(): array
    {
        return [
            ...$this->itemRules(),
            'delivery_address_id' => ['required', 'integer'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'scheduled_at' => ['nullable', 'date', 'after:now', 'before:+'.config('bento.pricing.schedule_max_days').' days'],
        ];
    }
}

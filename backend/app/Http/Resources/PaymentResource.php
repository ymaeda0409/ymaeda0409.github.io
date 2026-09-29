<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'method' => $this->method->value,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'phone' => $this->phone,
            'reference' => $this->reference,
            'failure_code' => $this->failure_code,
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'method', 'gateway', 'status', 'amount', 'currency', 'phone', 'reference',
    'gateway_reference', 'failure_code', 'payload', 'paid_at', 'refunded_at',
])]
#[Hidden(['payload'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [PaymentStatus::PAID, PaymentStatus::REFUNDED], true);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

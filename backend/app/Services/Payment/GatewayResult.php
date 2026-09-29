<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;

final readonly class GatewayResult
{
    public function __construct(
        public PaymentStatus $status,
        public ?string $gatewayReference = null,
        public ?string $failureCode = null,
        public array $payload = [],
    ) {}
}

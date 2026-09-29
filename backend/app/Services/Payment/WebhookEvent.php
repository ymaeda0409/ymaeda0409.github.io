<?php

namespace App\Services\Payment;

final readonly class WebhookEvent
{
    /**
     * @param  string  $reference  our payments.reference
     */
    public function __construct(public string $reference, public array $payload = []) {}
}

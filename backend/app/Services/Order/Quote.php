<?php

namespace App\Services\Order;

use App\Services\Delivery\AvailableStore;

/**
 * Server-side price calculation. Clients may display estimates, but only this is charged.
 */
final readonly class Quote
{
    /**
     * @param  list<QuoteLine>  $lines
     */
    public function __construct(
        public AvailableStore $delivery,
        public array $lines,
        public int $subtotal,
        public int $deliveryFee,
        public int $serviceFee,
        public int $discount,
    ) {}

    public function total(): int
    {
        return max(0, $this->subtotal + $this->deliveryFee + $this->serviceFee - $this->discount);
    }
}

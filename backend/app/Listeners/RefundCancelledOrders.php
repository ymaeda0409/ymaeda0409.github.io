<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Services\Payment\PaymentService;

class RefundCancelledOrders
{
    public function __construct(private readonly PaymentService $payments) {}

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to === OrderStatus::CANCELLED) {
            $this->payments->refundFor($event->order);
        }
    }
}

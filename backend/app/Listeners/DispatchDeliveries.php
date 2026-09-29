<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Services\Delivery\DeliveryAssignmentService;

/**
 * READY → start looking for a driver; CANCELLED → withdraw open offers.
 */
class DispatchDeliveries
{
    public function __construct(private readonly DeliveryAssignmentService $assignments) {}

    public function handle(OrderStatusChanged $event): void
    {
        match ($event->to) {
            OrderStatus::READY_FOR_PICKUP => $this->assignments->offerNext($event->order),
            OrderStatus::CANCELLED => $this->assignments->withdraw($event->order),
            default => null,
        };
    }
}

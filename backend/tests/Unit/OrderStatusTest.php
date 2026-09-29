<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_happy_path_is_allowed_step_by_step(): void
    {
        $path = [
            OrderStatus::NEW, OrderStatus::CONFIRMED, OrderStatus::COOKING, OrderStatus::READY_FOR_PICKUP,
            OrderStatus::RIDER_ASSIGNED, OrderStatus::PICKED_UP, OrderStatus::ON_THE_WAY, OrderStatus::ARRIVED,
            OrderStatus::DELIVERED,
        ];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue($path[$i]->canTransitionTo($path[$i + 1]), "{$path[$i]->value} → {$path[$i + 1]->value}");
        }
    }

    public function test_skipping_steps_or_going_back_is_rejected(): void
    {
        $this->assertFalse(OrderStatus::NEW->canTransitionTo(OrderStatus::COOKING));
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::READY_FOR_PICKUP));
        $this->assertFalse(OrderStatus::COOKING->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::PICKED_UP->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_final_statuses_have_no_exit(): void
    {
        foreach ([OrderStatus::DELIVERED, OrderStatus::CANCELLED, OrderStatus::FAILED_DELIVERY] as $status) {
            $this->assertTrue($status->isFinal());
            foreach (OrderStatus::cases() as $to) {
                $this->assertFalse($status->canTransitionTo($to));
            }
        }
    }

    public function test_every_status_with_an_entry_timestamp_is_mapped(): void
    {
        $this->assertSame('accepted_at', OrderStatus::CONFIRMED->timestampColumn());
        $this->assertSame('ready_at', OrderStatus::READY_FOR_PICKUP->timestampColumn());
        $this->assertSame('delivered_at', OrderStatus::DELIVERED->timestampColumn());
        $this->assertNull(OrderStatus::NEW->timestampColumn());
    }
}

<?php

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Events\DeliveryOffered;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Jobs\SendNotification;
use App\Models\Order;

/**
 * Maps domain events to notification codes. Texts live in templates, never here.
 * (Registered by Laravel's listener discovery through the typed handle* methods.)
 */
class SendOrderNotifications
{
    private const ORDER_STATUS_CODES = [
        'CONFIRMED' => 'ORDER_CONFIRMED',
        'COOKING' => 'ORDER_COOKING',
        'RIDER_ASSIGNED' => 'DRIVER_ASSIGNED',
        'ON_THE_WAY' => 'ORDER_ON_THE_WAY',
        'ARRIVED' => 'DRIVER_ARRIVED',
        'DELIVERED' => 'ORDER_DELIVERED',
        'CANCELLED' => 'ORDER_CANCELLED',
        'FAILED_DELIVERY' => 'DELIVERY_FAILED',
    ];

    public function handleOrderStatus(OrderStatusChanged $event): void
    {
        $code = self::ORDER_STATUS_CODES[$event->to->value] ?? null;
        if ($code) {
            $this->notifyCustomer($event->order, $code);
        }
    }

    public function handlePayment(PaymentStatusChanged $event): void
    {
        $code = match ($event->payment->status) {
            PaymentStatus::PAID => 'PAYMENT_RECEIVED',
            PaymentStatus::FAILED => 'PAYMENT_FAILED',
            default => null,
        };
        if ($code) {
            $this->notifyCustomer($event->payment->order, $code);
        }
    }

    public function handleOffer(DeliveryOffered $event): void
    {
        $order = $event->assignment->order;
        SendNotification::dispatch($event->assignment->driver->user_id, 'DRIVER_NEW_DELIVERY', [
            'order_number' => $order->order_number,
            'store' => $order->store->name,
        ], $order->id);
    }

    private function notifyCustomer(Order $order, string $code): void
    {
        SendNotification::dispatch($order->customer_id, $code, [
            'order_number' => $order->order_number,
            'store' => $order->store->name,
            'pin' => $order->delivery_pin,
        ], $order->id);
    }
}

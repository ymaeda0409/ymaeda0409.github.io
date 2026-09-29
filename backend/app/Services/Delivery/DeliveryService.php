<?php

namespace App\Services\Delivery;

use App\Enums\ErrorCode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\ApiException;
use App\Models\Driver;
use App\Models\DriverLocation;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Driver-side delivery actions: availability, GPS, pickup, arrival and PIN hand-over.
 */
class DeliveryService
{
    public function __construct(
        private readonly OrderStatusService $statuses,
        private readonly DeliveryAssignmentService $assignments,
    ) {}

    public function goOnline(Driver $driver, ?float $latitude = null, ?float $longitude = null): Driver
    {
        $driver->is_online = true;
        if ($latitude !== null && $longitude !== null) {
            $this->applyPosition($driver, $latitude, $longitude, now());
        }
        $driver->save();

        // Someone just became available: retry orders that are waiting for a driver.
        $this->assignments->dispatchPending();

        return $driver->refresh();
    }

    public function goOffline(Driver $driver): Driver
    {
        $driver->update(['is_online' => false]);

        foreach ($driver->assignments()->pending()->with('order')->get() as $offer) {
            $this->assignments->withdraw($offer->order, $driver);
            $this->assignments->offerNext($offer->order);
        }

        return $driver;
    }

    /**
     * Stores GPS points (possibly buffered while offline) and moves the driver's current
     * position to the newest one. Points for orders not assigned to the driver are ignored.
     *
     * @param  list<array{latitude: float, longitude: float, timestamp?: string|null, order_id?: int|null}>  $points
     */
    public function recordLocations(Driver $driver, array $points): int
    {
        $ownOrders = $driver->orders()->whereIn('id', array_filter(array_column($points, 'order_id')))->pluck('id')->all();
        $rows = [];
        $latest = null;

        foreach ($points as $point) {
            $at = isset($point['timestamp']) ? CarbonImmutable::parse($point['timestamp'])->utc() : CarbonImmutable::now();
            // Clock skew on cheap phones: never accept points from the future.
            $at = $at->isFuture() ? CarbonImmutable::now() : $at;
            $orderId = in_array($point['order_id'] ?? null, $ownOrders, true) ? $point['order_id'] : null;

            $rows[] = [
                'driver_id' => $driver->id,
                'order_id' => $orderId,
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'recorded_at' => $at,
                'created_at' => now(),
            ];
            if ($latest === null || $at->greaterThan($latest[2])) {
                $latest = [$point['latitude'], $point['longitude'], $at];
            }
        }

        DB::transaction(function () use ($driver, $rows, $latest) {
            DriverLocation::insert($rows);
            if ($latest && ($driver->location_updated_at === null || $latest[2]->greaterThan($driver->location_updated_at))) {
                $this->applyPosition($driver, ...$latest);
                $driver->save();
            }
        });

        return count($rows);
    }

    /** RIDER_ASSIGNED → PICKED_UP → ON_THE_WAY (the rider leaves as soon as food is collected). */
    public function pickup(Driver $driver, Order $order): Order
    {
        $order = $this->statuses->transition($order, OrderStatus::PICKED_UP, $driver->user);

        return $this->statuses->transition($order, OrderStatus::ON_THE_WAY, $driver->user);
    }

    public function arrive(Driver $driver, Order $order): Order
    {
        return $this->statuses->transition($order, OrderStatus::ARRIVED, $driver->user);
    }

    /**
     * Hands over the food after the customer tells the rider their 4-digit PIN.
     * Cash on delivery is marked as paid at this point.
     */
    public function complete(Driver $driver, Order $order, string $pin): Order
    {
        if ($order->status !== OrderStatus::ARRIVED) {
            throw ApiException::of(ErrorCode::INVALID_STATUS_TRANSITION);
        }
        if ($order->delivery_pin_attempts >= (int) config('bento.dispatch.max_pin_attempts')) {
            throw ApiException::of(ErrorCode::DELIVERY_PIN_LOCKED);
        }
        if (! hash_equals($order->delivery_pin, $pin)) {
            $order->increment('delivery_pin_attempts');
            throw ApiException::of(ErrorCode::DELIVERY_PIN_INVALID);
        }

        return $this->statuses->transition($order, OrderStatus::DELIVERED, $driver->user, mutate: function (Order $locked) {
            if ($locked->payment_method === PaymentMethod::CASH) {
                $locked->payment_status = PaymentStatus::PAID;
            }
        });
    }

    public function fail(Driver $driver, Order $order, string $reasonCode): Order
    {
        return $this->statuses->transition($order, OrderStatus::FAILED_DELIVERY, $driver->user, $reasonCode);
    }

    private function applyPosition(Driver $driver, float $latitude, float $longitude, \DateTimeInterface $at): void
    {
        $driver->current_latitude = $latitude;
        $driver->current_longitude = $longitude;
        $driver->location_updated_at = $at;
    }
}

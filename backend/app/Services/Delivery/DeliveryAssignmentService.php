<?php

namespace App\Services\Delivery;

use App\Enums\AssignmentStatus;
use App\Enums\DriverStatus;
use App\Enums\ErrorCode;
use App\Enums\OrderStatus;
use App\Events\DeliveryOffered;
use App\Exceptions\ApiException;
use App\Models\DeliveryAssignment;
use App\Models\Driver;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use App\Support\Geo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Offers READY orders to the nearest online, free driver of the same franchise
 * (distance from the kitchen), one driver at a time. Declined / expired offers move on
 * to the next candidate.
 */
class DeliveryAssignmentService
{
    public function __construct(private readonly OrderStatusService $statuses) {}

    /**
     * Creates the next offer for an order if it still needs a driver. Returns null when
     * no candidate is available (the scheduler retries every minute).
     */
    public function offerNext(Order $order): ?DeliveryAssignment
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->with(['kitchen', 'store'])->whereKey($order->id)->lockForUpdate()->first();

            if (! $order || $order->status !== OrderStatus::READY_FOR_PICKUP || $order->driver_id !== null
                || $order->assignments()->pending()->exists()) {
                return null;
            }

            [$originLat, $originLng] = $this->origin($order);
            $candidate = $this->nearestCandidate($order, $originLat, $originLng, excludeExpired: true)
                ?? $this->nearestCandidate($order, $originLat, $originLng, excludeExpired: false);

            if ($candidate === null) {
                Log::info('No driver available for order', ['order_id' => $order->id]);

                return null;
            }

            [$driver, $distance] = $candidate;

            $offer = $order->assignments()->create([
                'driver_id' => $driver->id,
                'status' => AssignmentStatus::OFFERED,
                'distance_km' => round($distance, 2),
                'offered_at' => now(),
                'expires_at' => now()->addSeconds((int) config('bento.dispatch.offer_ttl_seconds')),
            ]);
            DeliveryOffered::dispatch($offer);

            return $offer;
        });
    }

    public function accept(Driver $driver, Order $order): Order
    {
        return DB::transaction(function () use ($driver, $order) {
            $offer = $this->pendingOffer($driver, $order);

            $assigned = $this->statuses->transition(
                $order,
                OrderStatus::RIDER_ASSIGNED,
                $driver->user,
                mutate: function (Order $locked) use ($driver) {
                    if ($locked->driver_id !== null) {
                        throw ApiException::of(ErrorCode::OFFER_NOT_AVAILABLE);
                    }
                    $locked->driver_id = $driver->id;
                },
            );

            $offer->update(['status' => AssignmentStatus::ACCEPTED, 'responded_at' => now()]);

            return $assigned;
        });
    }

    public function decline(Driver $driver, Order $order): void
    {
        $this->pendingOffer($driver, $order)->update(['status' => AssignmentStatus::DECLINED, 'responded_at' => now()]);
        $this->offerNext($order);
    }

    /**
     * Expires unanswered offers and (re)offers every READY order that has no driver.
     * Run every minute by the scheduler (`deliveries:dispatch`).
     */
    public function dispatchPending(): int
    {
        DeliveryAssignment::query()
            ->where('status', AssignmentStatus::OFFERED)
            ->where('expires_at', '<=', now())
            ->update(['status' => AssignmentStatus::EXPIRED]);

        $offers = 0;
        Order::query()
            ->where('status', OrderStatus::READY_FOR_PICKUP)
            ->whereNull('driver_id')
            ->orderBy('ready_at')
            ->each(function (Order $order) use (&$offers) {
                $offers += $this->offerNext($order) ? 1 : 0;
            });

        return $offers;
    }

    /**
     * Withdraws open offers of an order (cancelled, or the driver went offline).
     */
    public function withdraw(Order $order, ?Driver $onlyFor = null): void
    {
        $order->assignments()
            ->where('status', AssignmentStatus::OFFERED)
            ->when($onlyFor, fn ($q) => $q->where('driver_id', $onlyFor->id))
            ->update(['status' => AssignmentStatus::WITHDRAWN, 'responded_at' => now()]);
    }

    private function pendingOffer(Driver $driver, Order $order): DeliveryAssignment
    {
        $offer = DeliveryAssignment::query()
            ->where('order_id', $order->id)
            ->where('driver_id', $driver->id)
            ->pending()
            ->lockForUpdate()
            ->first();

        if (! $offer) {
            throw ApiException::of(ErrorCode::OFFER_NOT_AVAILABLE);
        }

        return $offer;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function origin(Order $order): array
    {
        $kitchen = $order->kitchen;
        if ($kitchen?->latitude !== null && $kitchen?->longitude !== null) {
            return [$kitchen->latitude, $kitchen->longitude];
        }

        return [$order->store->latitude, $order->store->longitude];
    }

    /**
     * @return array{0: Driver, 1: float}|null
     */
    private function nearestCandidate(Order $order, float $lat, float $lng, bool $excludeExpired): ?array
    {
        $excluded = $excludeExpired ? [AssignmentStatus::DECLINED, AssignmentStatus::EXPIRED] : [AssignmentStatus::DECLINED];

        $drivers = Driver::query()
            ->where('franchise_id', $order->franchise_id)
            ->where('status', DriverStatus::ACTIVE)
            ->where('is_online', true)
            ->where(fn ($q) => $q->whereNull('store_id')->orWhere('store_id', $order->store_id))
            ->whereNotNull('current_latitude')
            ->where('location_updated_at', '>=', now()->subMinutes((int) config('bento.dispatch.location_max_age_minutes')))
            ->withoutActiveDelivery()
            // One open offer per driver at a time.
            ->whereDoesntHave('assignments', fn ($q) => $q->pending())
            ->whereDoesntHave('assignments', fn ($q) => $q->where('order_id', $order->id)->whereIn('status', $excluded))
            ->get();

        return $drivers
            ->map(fn (Driver $d) => [$d, Geo::distanceKm($lat, $lng, $d->current_latitude, $d->current_longitude)])
            ->sortBy(fn (array $pair) => $pair[1])
            ->first();
    }
}

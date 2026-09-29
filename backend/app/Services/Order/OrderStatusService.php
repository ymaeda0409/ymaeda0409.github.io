<?php

namespace App\Services\Order;

use App\Enums\ErrorCode;
use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * All order status changes go through here: transition rules, timestamps, history, stock
 * restoration and the OrderStatusChanged event.
 */
class OrderStatusService
{
    public const REASON_CUSTOMER_CANCELLED = 'CUSTOMER_CANCELLED';

    public const REASON_STORE_CANCELLED = 'STORE_CANCELLED';

    /** Customers may cancel until the kitchen starts cooking. */
    private const CUSTOMER_CANCELLABLE = [OrderStatus::NEW, OrderStatus::CONFIRMED];

    public function recordInitial(Order $order, ?User $actor): void
    {
        $order->statusHistories()->create([
            'from_status' => null,
            'to_status' => $order->status,
            'actor_user_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    public function accept(Order $order, User $actor): Order
    {
        if (! $order->isPayable()) {
            throw ApiException::of(ErrorCode::PAYMENT_REQUIRED);
        }

        return $this->transition($order, OrderStatus::CONFIRMED, $actor);
    }

    public function startCooking(Order $order, User $actor): Order
    {
        return $this->transition($order, OrderStatus::COOKING, $actor);
    }

    public function markReady(Order $order, User $actor): Order
    {
        return $this->transition($order, OrderStatus::READY_FOR_PICKUP, $actor);
    }

    public function cancelByCustomer(Order $order, User $customer): Order
    {
        if (! in_array($order->status, self::CUSTOMER_CANCELLABLE, true)) {
            throw ApiException::of(ErrorCode::INVALID_STATUS_TRANSITION);
        }

        return $this->transition($order, OrderStatus::CANCELLED, $customer, self::REASON_CUSTOMER_CANCELLED);
    }

    public function cancelByStore(Order $order, User $actor, ?string $reason = null): Order
    {
        return $this->transition($order, OrderStatus::CANCELLED, $actor, $reason ?? self::REASON_STORE_CANCELLED);
    }

    /**
     * @param  (callable(Order): void)|null  $mutate  extra changes applied under the same row lock
     *                                                (may throw to abort the transition)
     */
    public function transition(Order $order, OrderStatus $to, ?User $actor, ?string $reasonCode = null, ?callable $mutate = null): Order
    {
        [$order, $from] = DB::transaction(function () use ($order, $to, $actor, $reasonCode, $mutate) {
            // Re-read under lock so two taps (or two staff) cannot double-transition.
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ApiException::of(ErrorCode::INVALID_STATUS_TRANSITION);
            }

            if ($mutate) {
                $mutate($locked);
            }
            $locked->status = $to;
            if ($column = $to->timestampColumn()) {
                $locked->{$column} = now();
            }
            if ($to === OrderStatus::CANCELLED) {
                $locked->cancel_reason_code = $reasonCode;
                $this->restock($locked);
            }
            $locked->save();

            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'actor_user_id' => $actor?->id,
                'reason_code' => $reasonCode,
                'created_at' => now(),
            ]);

            return [$locked, $from];
        });

        OrderStatusChanged::dispatch($order, $from, $to);

        return $order;
    }

    private function restock(Order $order): void
    {
        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            StoreProduct::query()
                ->where('store_id', $order->store_id)
                ->where('product_id', $item->product_id)
                ->whereNotNull('stock_quantity')
                ->increment('stock_quantity', $item->quantity);
        }
    }
}

<?php

namespace App\Enums;

/**
 * Internal order status codes. Display text is always translated by clients
 * (`order.status.<code>`); never store display strings.
 */
enum OrderStatus: string
{
    case NEW = 'NEW';
    case CONFIRMED = 'CONFIRMED';
    case COOKING = 'COOKING';
    case READY_FOR_PICKUP = 'READY_FOR_PICKUP';
    case RIDER_ASSIGNED = 'RIDER_ASSIGNED';
    case PICKED_UP = 'PICKED_UP';
    case ON_THE_WAY = 'ON_THE_WAY';
    case ARRIVED = 'ARRIVED';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';
    case FAILED_DELIVERY = 'FAILED_DELIVERY';

    /**
     * The single source of truth for allowed transitions.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NEW => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::COOKING, self::CANCELLED],
            self::COOKING => [self::READY_FOR_PICKUP, self::CANCELLED],
            self::READY_FOR_PICKUP => [self::RIDER_ASSIGNED, self::CANCELLED],
            self::RIDER_ASSIGNED => [self::PICKED_UP, self::READY_FOR_PICKUP],
            self::PICKED_UP => [self::ON_THE_WAY],
            self::ON_THE_WAY => [self::ARRIVED, self::FAILED_DELIVERY],
            self::ARRIVED => [self::DELIVERED, self::FAILED_DELIVERY],
            self::DELIVERED, self::CANCELLED, self::FAILED_DELIVERY => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Timestamp column set when the order enters this status.
     */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::CONFIRMED => 'accepted_at',
            self::COOKING => 'cooking_started_at',
            self::READY_FOR_PICKUP => 'ready_at',
            self::RIDER_ASSIGNED => 'assigned_at',
            self::PICKED_UP => 'picked_up_at',
            self::ARRIVED => 'arrived_at',
            self::DELIVERED => 'delivered_at',
            self::CANCELLED, self::FAILED_DELIVERY => 'cancelled_at',
            default => null,
        };
    }

    /**
     * Statuses shown on the kitchen board.
     *
     * @return list<self>
     */
    public static function kitchenBoard(): array
    {
        return [self::NEW, self::CONFIRMED, self::COOKING, self::READY_FOR_PICKUP];
    }

    /**
     * Orders still in progress (everything that is not final), in flow order.
     *
     * @return list<self>
     */
    public static function activeStatuses(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => ! $s->isFinal()));
    }
}

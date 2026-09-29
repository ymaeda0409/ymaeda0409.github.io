<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number', 'organization_id', 'franchise_id', 'store_id', 'kitchen_id', 'customer_id',
    'delivery_address_id', 'delivery_zone_id', 'driver_id', 'status', 'payment_status', 'payment_method',
    'currency', 'subtotal', 'delivery_fee', 'service_fee', 'discount', 'total', 'delivery_latitude',
    'delivery_longitude', 'delivery_distance_km', 'delivery_address_snapshot', 'delivery_pin', 'locale',
    'cancel_reason_code', 'scheduled_at', 'ordered_at',
])]
#[Hidden(['delivery_pin'])]
class Order extends Model
{
    use BelongsToTenant, HasFactory;

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id', 'franchise' => 'franchise_id', 'store' => 'store_id'];
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'service_fee' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'delivery_distance_km' => 'float',
            'delivery_address_snapshot' => 'array',
            'scheduled_at' => 'datetime',
            'ordered_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cooking_started_at' => 'datetime',
            'ready_at' => 'datetime',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'arrived_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Kitchens may only start work on orders that are paid or cash on delivery.
     */
    public function isPayable(): bool
    {
        return ! $this->payment_method->isPrepaid() || $this->payment_status === PaymentStatus::PAID;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function kitchen(): BelongsTo
    {
        return $this->belongsTo(Kitchen::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }
}

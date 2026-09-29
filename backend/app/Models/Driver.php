<?php

namespace App\Models;

use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Enums\VehicleType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'organization_id', 'franchise_id', 'store_id', 'vehicle_type', 'vehicle_number', 'status',
    'is_online', 'current_latitude', 'current_longitude', 'location_updated_at', 'rating',
])]
class Driver extends Model
{
    use BelongsToTenant, HasFactory;

    /** Order statuses during which a driver is busy with a delivery. */
    public const ACTIVE_DELIVERY_STATUSES = [
        OrderStatus::RIDER_ASSIGNED, OrderStatus::PICKED_UP, OrderStatus::ON_THE_WAY, OrderStatus::ARRIVED,
    ];

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['status' => 'ACTIVE', 'is_online' => false];

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id', 'franchise' => 'franchise_id', 'store' => 'store_id'];
    }

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'status' => DriverStatus::class,
            'is_online' => 'boolean',
            'current_latitude' => 'float',
            'current_longitude' => 'float',
            'location_updated_at' => 'datetime',
            'rating' => 'decimal:2',
        ];
    }

    public function scopeWithoutActiveDelivery(Builder $query): Builder
    {
        return $query->whereDoesntHave('orders', fn ($q) => $q->whereIn('status', self::ACTIVE_DELIVERY_STATUSES));
    }

    public function activeOrder(): ?Order
    {
        return $this->orders()->whereIn('status', self::ACTIVE_DELIVERY_STATUSES)->latest('id')->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }
}

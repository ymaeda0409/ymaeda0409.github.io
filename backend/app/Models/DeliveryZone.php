<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id', 'franchise_id', 'store_id', 'kitchen_id', 'name', 'base_fee', 'base_distance_km',
    'additional_fee_per_km', 'max_delivery_distance_km', 'polygon', 'is_active',
])]
class DeliveryZone extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['base_fee' => 0, 'base_distance_km' => 0, 'additional_fee_per_km' => 0, 'is_active' => true];

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id', 'franchise' => 'franchise_id', 'store' => 'store_id'];
    }

    protected function casts(): array
    {
        return [
            'base_fee' => 'integer',
            'base_distance_km' => 'float',
            'additional_fee_per_km' => 'integer',
            'max_delivery_distance_km' => 'float',
            'polygon' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function kitchen(): BelongsTo
    {
        return $this->belongsTo(Kitchen::class);
    }

    public function coversDistance(float $distanceKm): bool
    {
        return $distanceKm <= $this->max_delivery_distance_km;
    }

    /**
     * base_fee + (started km beyond base distance) × additional_fee_per_km, in minor units.
     */
    public function feeForDistance(float $distanceKm): int
    {
        $extraKm = max(0.0, $distanceKm - $this->base_distance_km);

        return $this->base_fee + (int) ceil(round($extraKm, 6)) * $this->additional_fee_per_km;
    }
}

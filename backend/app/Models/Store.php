<?php

namespace App\Models;

use App\Enums\BusinessType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasTranslations;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id', 'franchise_id', 'code', 'name', 'business_type', 'phone', 'email', 'city', 'address',
    'latitude', 'longitude', 'timezone', 'currency', 'opening_hours', 'is_active', 'is_accepting_orders',
])]
class Store extends Model
{
    use BelongsToTenant, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['business_type' => 'FOOD', 'is_active' => true, 'is_accepting_orders' => true];

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id', 'franchise' => 'franchise_id', 'store' => 'id'];
    }

    public static function translatedAttributes(): array
    {
        return ['description', 'announcement'];
    }

    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'opening_hours' => 'array',
            'is_active' => 'boolean',
            'is_accepting_orders' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Whether the store is taking orders at the given moment, evaluated in the store's timezone.
     * opening_hours: {"mon": [["08:00", "20:00"]], ...}; null means always open. Intervals may
     * cross midnight (["18:00", "02:00"]).
     */
    public function isOpenAt(CarbonInterface $at): bool
    {
        if (! $this->is_active || ! $this->is_accepting_orders) {
            return false;
        }

        if ($this->opening_hours === null) {
            return true;
        }

        $local = $at->copy()->setTimezone($this->timezone);
        $time = $local->format('H:i');
        $today = strtolower($local->format('D'));
        $yesterday = strtolower($local->copy()->subDay()->format('D'));

        foreach ($this->opening_hours[$today] ?? [] as [$open, $close]) {
            if ($open <= $close ? ($time >= $open && $time < $close) : $time >= $open) {
                return true;
            }
        }

        // Overnight intervals started yesterday.
        foreach ($this->opening_hours[$yesterday] ?? [] as [$open, $close]) {
            if ($open > $close && $time < $close) {
                return true;
            }
        }

        return false;
    }

    public function isOpenNow(): bool
    {
        return $this->isOpenAt(now());
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(StoreTranslation::class);
    }

    public function kitchens(): HasMany
    {
        return $this->hasMany(Kitchen::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function storeProducts(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }
}

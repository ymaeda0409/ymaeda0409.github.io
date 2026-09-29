<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A delivery offer to one driver. Drivers accept or decline; unanswered offers expire.
 */
#[Fillable(['order_id', 'driver_id', 'status', 'distance_km', 'offered_at', 'expires_at', 'responded_at'])]
class DeliveryAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'distance_km' => 'float',
            'offered_at' => 'datetime',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::OFFERED)->where('expires_at', '>', now());
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

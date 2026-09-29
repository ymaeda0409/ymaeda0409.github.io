<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'name', 'latitude', 'longitude', 'area', 'street', 'building', 'landmark',
    'delivery_note', 'phone', 'is_default',
])]
class UserAddress extends Model
{
    use HasFactory;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['is_default' => false];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

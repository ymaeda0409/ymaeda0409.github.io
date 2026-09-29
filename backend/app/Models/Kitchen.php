<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['organization_id', 'franchise_id', 'store_id', 'name', 'latitude', 'longitude', 'is_active'])]
class Kitchen extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['is_active' => true];

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id', 'franchise' => 'franchise_id', 'store' => 'store_id'];
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

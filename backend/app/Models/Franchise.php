<?php

namespace App\Models;

use App\Enums\FranchiseStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['organization_id', 'code', 'name', 'owner_name', 'phone', 'email', 'region', 'status', 'commission_rate'])]
class Franchise extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['status' => 'PENDING', 'commission_rate' => 0];

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id', 'franchise' => 'id'];
    }

    protected function casts(): array
    {
        return [
            'status' => FranchiseStatus::class,
            'commission_rate' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}

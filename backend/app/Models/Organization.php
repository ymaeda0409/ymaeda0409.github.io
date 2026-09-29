<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'default_locale', 'currency', 'timezone', 'is_active'])]
class Organization extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['currency' => 'MWK', 'timezone' => 'Africa/Blantyre', 'default_locale' => 'en', 'is_active' => true];

    public static function tenantColumns(): array
    {
        return ['organization' => 'id'];
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function franchises(): HasMany
    {
        return $this->hasMany(Franchise::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}

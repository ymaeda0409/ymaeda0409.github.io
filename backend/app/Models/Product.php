<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id', 'category_id', 'sku', 'image_url', 'price', 'preparation_minutes',
    'is_featured', 'is_active', 'sort_order',
])]
class Product extends Model
{
    use BelongsToTenant, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['preparation_minutes' => 15, 'is_featured' => false, 'is_active' => true, 'sort_order' => 0];

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id'];
    }

    public static function translatedAttributes(): array
    {
        return ['name', 'description'];
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'preparation_minutes' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(ProductOptionGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    public function storeProducts(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }
}

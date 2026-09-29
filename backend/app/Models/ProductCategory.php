<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['organization_id', 'code', 'image_url', 'sort_order', 'is_active'])]
class ProductCategory extends Model
{
    use BelongsToTenant, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['sort_order' => 0, 'is_active' => true];

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id'];
    }

    public static function translatedAttributes(): array
    {
        return ['name'];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductCategoryTranslation::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}

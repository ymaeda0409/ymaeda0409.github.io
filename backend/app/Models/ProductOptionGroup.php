<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'min_select', 'max_select', 'sort_order'])]
class ProductOptionGroup extends Model
{
    use HasTranslations;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['min_select' => 0, 'max_select' => 1, 'sort_order' => 0];

    public static function translatedAttributes(): array
    {
        return ['name'];
    }

    protected function casts(): array
    {
        return [
            'min_select' => 'integer',
            'max_select' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductOptionGroupTranslation::class, 'option_group_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class, 'option_group_id')->orderBy('sort_order')->orderBy('id');
    }
}

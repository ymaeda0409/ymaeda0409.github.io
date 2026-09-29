<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['option_group_id', 'price', 'is_active', 'sort_order'])]
class ProductOption extends Model
{
    use HasTranslations;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['price' => 0, 'is_active' => true, 'sort_order' => 0];

    public static function translatedAttributes(): array
    {
        return ['name'];
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'option_group_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductOptionTranslation::class, 'option_id');
    }
}

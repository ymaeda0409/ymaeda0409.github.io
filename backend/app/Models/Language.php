<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'native_name', 'direction', 'is_active', 'is_default', 'sort_order'])]
class Language extends Model
{
    use HasFactory;

    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}

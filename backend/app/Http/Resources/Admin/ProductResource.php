<?php

namespace App\Http\Resources\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Back-office product with every translation, for the language-tab editor.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'category_id' => $this->category_id,
            'sku' => $this->sku,
            'image_url' => $this->image_url,
            'price' => $this->price,
            'preparation_minutes' => $this->preparation_minutes,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'translations' => $this->translationsByLocale(),
            'option_groups' => $this->whenLoaded('optionGroups', fn () => $this->optionGroups->map(fn ($group) => [
                'id' => $group->id,
                'min_select' => $group->min_select,
                'max_select' => $group->max_select,
                'sort_order' => $group->sort_order,
                'translations' => $group->translationsByLocale(),
                'options' => $group->options->map(fn ($option) => [
                    'id' => $option->id,
                    'price' => $option->price,
                    'is_active' => $option->is_active,
                    'sort_order' => $option->sort_order,
                    'translations' => $option->translationsByLocale(),
                ])->all(),
            ])->all()),
        ];
    }
}

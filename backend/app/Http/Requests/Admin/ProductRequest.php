<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\AuthorizesResource;
use App\Http\Requests\Concerns\HasTranslationRules;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    use AuthorizesResource, HasTranslationRules;

    protected function authorizedResource(): array
    {
        return [Product::class, 'product'];
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';
        $product = $this->route('product');
        $organizationId = $product?->organization_id ?? $this->organizationId();

        return [
            'organization_id' => [$creating ? 'nullable' : 'prohibited', 'integer', 'exists:organizations,id'],
            'category_id' => [$required, 'integer',
                Rule::exists('product_categories', 'id')->where('organization_id', $organizationId)->whereNull('deleted_at')],
            'sku' => [$required, 'string', 'max:60', 'alpha_dash',
                Rule::unique('products', 'sku')->where('organization_id', $organizationId)->ignore($product?->id)],
            'image_url' => ['nullable', 'url', 'max:255'],
            'price' => [$required, 'integer', 'min:0'],
            'preparation_minutes' => ['sometimes', 'integer', 'min:0', 'max:600'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            ...$this->translationRules('translations', ['name' => 150, 'description' => 2000], $creating),

            'option_groups' => ['sometimes', 'array', 'max:20'],
            'option_groups.*.id' => ['sometimes', 'integer'],
            'option_groups.*.min_select' => ['required', 'integer', 'min:0'],
            'option_groups.*.max_select' => ['required', 'integer', 'min:1', 'gte:option_groups.*.min_select'],
            'option_groups.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            ...$this->translationRules('option_groups.*.translations', ['name' => 150], true),
            'option_groups.*.options' => ['required', 'array', 'min:1', 'max:50'],
            'option_groups.*.options.*.id' => ['sometimes', 'integer'],
            'option_groups.*.options.*.price' => ['required', 'integer', 'min:0'],
            'option_groups.*.options.*.is_active' => ['sometimes', 'boolean'],
            'option_groups.*.options.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            ...$this->translationRules('option_groups.*.options.*.translations', ['name' => 150], true),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->input('option_groups', []) as $i => $group) {
                    if (($group['min_select'] ?? 0) > count($group['options'] ?? [])) {
                        $validator->errors()->add("option_groups.{$i}.min_select", __('validation.lte.numeric', [
                            'attribute' => "option_groups.{$i}.min_select",
                            'value' => count($group['options'] ?? []),
                        ]));
                    }
                }
            },
        ];
    }

    public function organizationId(): ?int
    {
        return $this->integer('organization_id') ?: $this->user()->organization_id;
    }
}

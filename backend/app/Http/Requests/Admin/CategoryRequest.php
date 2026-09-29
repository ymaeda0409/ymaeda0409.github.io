<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\AuthorizesResource;
use App\Http\Requests\Concerns\HasTranslationRules;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    use AuthorizesResource, HasTranslationRules;

    protected function authorizedResource(): array
    {
        return [ProductCategory::class, 'category'];
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $category = $this->route('category');
        $organizationId = $category?->organization_id ?? $this->organizationId();

        return [
            'organization_id' => [$creating ? 'nullable' : 'prohibited', 'integer', 'exists:organizations,id'],
            'code' => [$creating ? 'required' : 'sometimes', 'string', 'max:60', 'alpha_dash',
                Rule::unique('product_categories', 'code')->where('organization_id', $organizationId)->ignore($category?->id)],
            'image_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            ...$this->translationRules('translations', ['name' => 150], $creating),
        ];
    }

    public function organizationId(): ?int
    {
        return $this->integer('organization_id') ?: $this->user()->organization_id;
    }
}

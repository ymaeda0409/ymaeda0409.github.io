<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Http\Requests\Concerns\AuthorizesResource;
use App\Http\Requests\Concerns\HasTranslationRules;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Franchise;
use App\Models\Store;
use App\Rules\VisibleTo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use AuthorizesResource, HasTranslationRules, NormalizesPhone;

    protected function authorizedResource(): array
    {
        return [Store::class, 'store'];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneFields('phone');
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';
        $store = $this->route('store');
        $time = 'date_format:H:i';

        return [
            // Moving a store between franchises is a platform-level operation.
            'franchise_id' => $this->user()->hasPermission(Permission::STORES_CREATE)
                ? [$required, 'integer', new VisibleTo(Franchise::class, $this->user())]
                : ['prohibited'],
            'code' => [$required, 'string', 'max:40', 'alpha_dash', Rule::unique('stores', 'code')->ignore($store?->id)],
            'name' => [$required, 'string', 'max:150'],
            'phone' => ['nullable', 'string', self::phoneRule()],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => [$required, 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => [$required, 'numeric', 'between:-90,90'],
            'longitude' => [$required, 'numeric', 'between:-180,180'],
            'timezone' => ['sometimes', 'timezone'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'opening_hours' => ['nullable', 'array:mon,tue,wed,thu,fri,sat,sun'],
            'opening_hours.*' => ['array'],
            'opening_hours.*.*' => ['array', 'size:2'],
            'opening_hours.*.*.*' => ['string', $time],
            'is_active' => ['sometimes', 'boolean'],
            'is_accepting_orders' => ['sometimes', 'boolean'],
            ...$this->translationRules('translations', ['description' => 2000, 'announcement' => 1000], false, null),
        ];
    }
}

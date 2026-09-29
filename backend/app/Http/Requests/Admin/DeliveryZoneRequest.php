<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\AuthorizesResource;
use App\Models\DeliveryZone;
use App\Models\Store;
use App\Rules\VisibleTo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryZoneRequest extends FormRequest
{
    use AuthorizesResource;

    protected function authorizedResource(): array
    {
        return [DeliveryZone::class, 'delivery_zone'];
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';
        $storeId = $this->route('delivery_zone')?->store_id ?? $this->input('store_id');

        return [
            'store_id' => $creating ? ['required', 'integer', new VisibleTo(Store::class, $this->user())] : ['prohibited'],
            'kitchen_id' => ['nullable', 'integer',
                Rule::exists('kitchens', 'id')->where('store_id', $storeId)->whereNull('deleted_at')],
            'name' => [$required, 'string', 'max:120'],
            'base_fee' => [$required, 'integer', 'min:0'],
            'base_distance_km' => [$required, 'numeric', 'min:0', 'max:1000'],
            'additional_fee_per_km' => [$required, 'integer', 'min:0'],
            'max_delivery_distance_km' => [$required, 'numeric', 'gt:0', 'max:'.config('bento.geo.max_zone_radius_km')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

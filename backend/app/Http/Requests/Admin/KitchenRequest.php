<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\AuthorizesResource;
use App\Models\Kitchen;
use App\Models\Store;
use App\Rules\VisibleTo;
use Illuminate\Foundation\Http\FormRequest;

class KitchenRequest extends FormRequest
{
    use AuthorizesResource;

    protected function authorizedResource(): array
    {
        return [Kitchen::class, 'kitchen'];
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'store_id' => $creating ? ['required', 'integer', new VisibleTo(Store::class, $this->user())] : ['prohibited'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

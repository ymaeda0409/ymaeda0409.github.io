<?php

namespace App\Http\Requests\Admin;

use App\Enums\DriverStatus;
use App\Enums\VehicleType;
use App\Http\Requests\Concerns\AuthorizesResource;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Driver;
use App\Models\Franchise;
use App\Models\Store;
use App\Rules\VisibleTo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverRequest extends FormRequest
{
    use AuthorizesResource, NormalizesPhone;

    protected function authorizedResource(): array
    {
        return [Driver::class, 'driver'];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneFields('phone');
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $user = $this->user();
        $driver = $this->route('driver');

        return [
            'phone' => $creating
                ? ['required', 'string', self::phoneRule(), Rule::unique('users', 'phone')]
                : ['prohibited'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:150'],
            'preferred_language' => ['sometimes', 'string', Rule::exists('languages', 'code')->where('is_active', true)],
            // Franchise admins always register drivers into their own franchise.
            'franchise_id' => $creating && $user->franchise_id === null
                ? ['required', 'integer', new VisibleTo(Franchise::class, $user)]
                : ['prohibited'],
            'store_id' => ['nullable', 'integer', new VisibleTo(Store::class, $user),
                Rule::exists('stores', 'id')->where('franchise_id', $driver?->franchise_id ?? $this->input('franchise_id', $user->franchise_id))],
            'vehicle_type' => [$creating ? 'required' : 'sometimes', Rule::enum(VehicleType::class)],
            'vehicle_number' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::enum(DriverStatus::class)],
        ];
    }

    public function franchiseId(): int
    {
        return (int) ($this->validated('franchise_id') ?? $this->user()->franchise_id);
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Enums\FranchiseStatus;
use App\Http\Requests\Concerns\AuthorizesResource;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Franchise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FranchiseRequest extends FormRequest
{
    use AuthorizesResource, NormalizesPhone;

    protected function authorizedResource(): array
    {
        return [Franchise::class, 'franchise'];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneFields('phone');
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';
        $franchise = $this->route('franchise');
        $organizationId = $franchise?->organization_id ?? $this->input('organization_id', $this->user()->organization_id);

        return [
            'organization_id' => [$creating ? 'nullable' : 'prohibited', 'integer', 'exists:organizations,id'],
            'code' => [$required, 'string', 'max:40', 'alpha_dash',
                Rule::unique('franchises', 'code')->where('organization_id', $organizationId)->ignore($franchise?->id)],
            'name' => [$required, 'string', 'max:150'],
            'owner_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', self::phoneRule()],
            'email' => ['nullable', 'email', 'max:255'],
            'region' => [$required, 'string', 'max:80'],
            'status' => ['sometimes', Rule::enum(FranchiseStatus::class)],
            'commission_rate' => ['sometimes', 'numeric', 'between:0,100'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null && $this->isMethod('POST')) {
            $data['organization_id'] ??= $this->user()->organization_id;
        }

        return $data;
    }
}

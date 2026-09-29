<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role->value,
            'preferred_language' => $this->preferred_language,
            $this->mergeWhen($this->isStaff(), fn () => [
                'organization_id' => $this->organization_id,
                'franchise_id' => $this->franchise_id,
                'store_id' => $this->store_id,
                'is_active' => $this->is_active,
                'permissions' => array_map(fn ($p) => $p->value, $this->role->permissions()),
            ]),
        ];
    }
}

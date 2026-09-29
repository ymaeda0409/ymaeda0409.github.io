<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function create(User $user, array $data): UserAddress
    {
        return DB::transaction(function () use ($user, $data) {
            // The first address becomes the default automatically.
            $data['is_default'] = ($data['is_default'] ?? false) || ! $user->addresses()->exists();

            $address = $user->addresses()->create($data);
            $this->ensureSingleDefault($address);

            return $address;
        });
    }

    public function update(UserAddress $address, array $data): UserAddress
    {
        return DB::transaction(function () use ($address, $data) {
            $address->update($data);
            $this->ensureSingleDefault($address);

            return $address;
        });
    }

    public function delete(UserAddress $address): void
    {
        DB::transaction(function () use ($address) {
            $wasDefault = $address->is_default;
            $userId = $address->user_id;
            $address->delete();

            if ($wasDefault) {
                UserAddress::query()->where('user_id', $userId)->oldest('id')->first()?->update(['is_default' => true]);
            }
        });
    }

    private function ensureSingleDefault(UserAddress $address): void
    {
        if ($address->is_default) {
            UserAddress::query()
                ->where('user_id', $address->user_id)
                ->whereKeyNot($address->id)
                ->update(['is_default' => false]);
        }
    }
}

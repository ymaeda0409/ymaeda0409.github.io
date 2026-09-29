<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\Franchise;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DriverService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Registers a driver: a DRIVER user (phone + OTP login) plus the driver profile.
     */
    public function create(int $franchiseId, array $data): Driver
    {
        return DB::transaction(function () use ($franchiseId, $data) {
            $franchise = Franchise::findOrFail($franchiseId);
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'role' => UserRole::DRIVER,
                'organization_id' => $franchise->organization_id,
                'franchise_id' => $franchise->id,
                'store_id' => $data['store_id'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? config('bento.default_locale'),
            ]);
            $driver = Driver::create([
                ...Arr::only($data, ['store_id', 'vehicle_type', 'vehicle_number', 'status']),
                'user_id' => $user->id,
                'organization_id' => $franchise->organization_id,
                'franchise_id' => $franchise->id,
            ]);
            $this->audit->log('driver.created', $driver, null, $driver->getAttributes());

            return $driver;
        });
    }

    public function update(Driver $driver, array $data): Driver
    {
        return DB::transaction(function () use ($driver, $data) {
            $driver->fill(Arr::only($data, ['store_id', 'vehicle_type', 'vehicle_number', 'status']));
            if (array_key_exists('status', $data) && $data['status'] === 'SUSPENDED') {
                $driver->is_online = false;
            }
            $this->audit->logChanges('driver.updated', $driver);
            $driver->save();
            $driver->user->fill(Arr::only($data, ['name', 'preferred_language']))->save();

            return $driver;
        });
    }
}

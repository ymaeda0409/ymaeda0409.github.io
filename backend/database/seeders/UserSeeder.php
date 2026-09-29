<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Franchise;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Development test accounts. Staff password: "password". Phone users log in with OTP (test mode: 123456).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'malawi-bento')->firstOrFail();
        $franchise = Franchise::where('organization_id', $organization->id)->where('code', 'LLW')->firstOrFail();
        $store = Store::where('code', 'LLW-CENTRAL')->firstOrFail();
        $password = Hash::make('password');

        $staff = [
            ['superadmin@malawibento.test', 'Super Admin', UserRole::SUPER_ADMIN, [], 'en'],
            ['franchise.admin@malawibento.test', 'Lilongwe Franchise Admin', UserRole::FRANCHISE_ADMIN, ['franchise_id' => $franchise->id], 'en'],
            ['store.manager@malawibento.test', 'Lilongwe Central Manager', UserRole::STORE_MANAGER, ['franchise_id' => $franchise->id, 'store_id' => $store->id], 'en'],
            ['kitchen@malawibento.test', 'Lilongwe Central Kitchen Staff', UserRole::KITCHEN_STAFF, ['franchise_id' => $franchise->id, 'store_id' => $store->id], 'ny'],
        ];

        foreach ($staff as [$email, $name, $role, $scope, $language]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'organization_id' => $organization->id,
                'franchise_id' => $scope['franchise_id'] ?? null,
                'store_id' => $scope['store_id'] ?? null,
                'preferred_language' => $language,
                'is_active' => true,
            ]);
        }

        // Driver profiles (vehicle, online status) are added in PHASE 4.
        $drivers = [
            ['+265990000001', 'Driver One', 'en'],
            ['+265990000002', 'Driver Two', 'ny'],
            ['+265990000003', 'Driver Three', 'ja'],
        ];
        foreach ($drivers as [$phone, $name, $language]) {
            User::updateOrCreate(['phone' => $phone], [
                'name' => $name,
                'role' => UserRole::DRIVER,
                'organization_id' => $organization->id,
                'franchise_id' => $franchise->id,
                'preferred_language' => $language,
                'is_active' => true,
                'phone_verified_at' => now(),
            ]);
        }

        $customer = User::updateOrCreate(['phone' => '+265991234567'], [
            'name' => 'Test Customer',
            'role' => UserRole::CUSTOMER,
            'preferred_language' => 'en',
            'is_active' => true,
            'phone_verified_at' => now(),
        ]);

        $customer->addresses()->updateOrCreate(['name' => 'Home'], [
            'latitude' => -13.9700,
            'longitude' => 33.7800,
            'area' => 'Area 10',
            'street' => 'Presidential Way',
            'landmark' => 'Near the blue gate',
            'delivery_note' => 'Call on arrival',
            'phone' => '+265991234567',
            'is_default' => true,
        ]);
    }
}

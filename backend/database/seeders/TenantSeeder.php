<?php

namespace Database\Seeders;

use App\Enums\FranchiseStatus;
use App\Models\DeliveryZone;
use App\Models\Franchise;
use App\Models\Kitchen;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * MVP tenant: Malawi Bento → Lilongwe Franchise → Lilongwe Central Store → Kitchen → Zone.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::updateOrCreate(['slug' => 'malawi-bento'], [
            'name' => 'Malawi Bento',
            'default_locale' => 'en',
            'currency' => 'MWK',
            'timezone' => 'Africa/Blantyre',
            'is_active' => true,
        ]);

        $franchise = Franchise::updateOrCreate(['organization_id' => $organization->id, 'code' => 'LLW'], [
            'name' => 'Lilongwe Franchise',
            'owner_name' => 'Lilongwe Franchise Owner',
            'phone' => '+265999000100',
            'email' => 'lilongwe@malawibento.test',
            'region' => 'Central',
            'status' => FranchiseStatus::ACTIVE,
            'commission_rate' => 10,
        ]);

        $store = Store::updateOrCreate(['code' => 'LLW-CENTRAL'], [
            'organization_id' => $organization->id,
            'franchise_id' => $franchise->id,
            'name' => 'Lilongwe Central Store',
            'business_type' => 'FOOD',
            'phone' => '+265999000200',
            'city' => 'Lilongwe',
            'address' => 'City Centre, Lilongwe',
            'latitude' => -13.9626,
            'longitude' => 33.7741,
            'timezone' => 'Africa/Blantyre',
            'currency' => 'MWK',
            'opening_hours' => array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], [['07:00', '22:00']]),
            'is_active' => true,
            'is_accepting_orders' => true,
        ]);

        $translations = [
            'en' => ['description' => 'Freshly made bento, delivered across Lilongwe.', 'announcement' => null],
            'ny' => ['description' => 'Bento zophikidwa kumene, zobweretsedwa ku Lilongwe konse.', 'announcement' => null],
            'ja' => ['description' => 'できたてのお弁当をリロングウェ全域へお届けします。', 'announcement' => null],
        ];
        foreach ($translations as $locale => $values) {
            $store->translations()->updateOrCreate(['locale' => $locale], $values);
        }

        $kitchen = Kitchen::updateOrCreate(['store_id' => $store->id, 'name' => 'Lilongwe Central Kitchen'], [
            'organization_id' => $organization->id,
            'franchise_id' => $franchise->id,
            'latitude' => -13.9626,
            'longitude' => 33.7741,
            'is_active' => true,
        ]);

        DeliveryZone::updateOrCreate(['store_id' => $store->id, 'name' => 'Lilongwe Central 10km'], [
            'organization_id' => $organization->id,
            'franchise_id' => $franchise->id,
            'kitchen_id' => $kitchen->id,
            'base_fee' => 150000,              // MK 1,500.00
            'base_distance_km' => 3,
            'additional_fee_per_km' => 30000,  // MK 300.00 per started km
            'max_delivery_distance_km' => 10,
            'is_active' => true,
        ]);
    }
}

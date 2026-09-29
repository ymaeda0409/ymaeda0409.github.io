<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Franchise;
use Tests\FeatureTestCase;

class PermissionTest extends FeatureTestCase
{
    private function franchisePayload(): array
    {
        return ['code' => 'BT', 'name' => 'Blantyre Franchise', 'region' => 'Southern', 'status' => 'ACTIVE', 'commission_rate' => 12.5];
    }

    public function test_only_super_admin_can_create_franchises(): void
    {
        $tenant = $this->createTenant();

        foreach ([
            [UserRole::FRANCHISE_ADMIN, null, $tenant['franchise']],
            [UserRole::STORE_MANAGER, $tenant['store'], null],
            [UserRole::KITCHEN_STAFF, $tenant['store'], null],
        ] as [$role, $store, $franchise]) {
            $this->actingAsRole($role, $store, $franchise);
            // 403 even with an empty body: authorization runs before validation.
            $this->postJson('/api/admin/franchises', [])->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
        }

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $this->postJson('/api/admin/franchises', $this->franchisePayload())
            ->assertCreated()
            ->assertJsonPath('data.organization_id', $this->organization->id)
            ->assertJsonPath('data.status', 'ACTIVE');
        $this->assertDatabaseHas('audit_logs', ['action' => 'franchise.created']);
    }

    public function test_super_admin_expands_to_a_new_region_without_code_changes(): void
    {
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $franchiseId = $this->postJson('/api/admin/franchises', $this->franchisePayload())->assertCreated()->json('data.id');

        $storeId = $this->postJson('/api/admin/stores', [
            'franchise_id' => $franchiseId, 'code' => 'BT-LIMBE', 'name' => 'Limbe Store', 'city' => 'Blantyre',
            'latitude' => -15.8100, 'longitude' => 35.0600,
            'translations' => ['en' => ['description' => 'Our Blantyre store'], 'ja' => ['description' => 'ブランタイヤ店']],
        ])->assertCreated()
            ->assertJsonPath('data.organization_id', $this->organization->id)
            ->assertJsonPath('data.currency', 'MWK')
            ->assertJsonPath('data.translations.ja.description', 'ブランタイヤ店')
            ->json('data.id');

        $kitchenId = $this->postJson('/api/admin/kitchens', ['store_id' => $storeId, 'name' => 'Limbe Kitchen'])
            ->assertCreated()->json('data.id');

        $this->postJson('/api/admin/delivery-zones', [
            'store_id' => $storeId, 'kitchen_id' => $kitchenId, 'name' => 'Limbe 8km',
            'base_fee' => 150000, 'base_distance_km' => 3, 'additional_fee_per_km' => 30000, 'max_delivery_distance_km' => 8,
        ])->assertCreated()->assertJsonPath('data.franchise_id', $franchiseId);

        // Customers in Blantyre can now find the new store.
        $this->getJson('/api/stores/available?latitude=-15.8000&longitude=35.0500')
            ->assertJsonPath('data.0.store.id', $storeId);
    }

    public function test_franchise_admin_cannot_create_stores_or_move_them(): void
    {
        $tenant = $this->createTenant();
        $otherFranchise = Franchise::factory()->create(['organization_id' => $this->organization->id]);
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $tenant['franchise']);

        $this->postJson('/api/admin/stores', [])->assertStatus(403);
        $this->putJson("/api/admin/stores/{$tenant['store']->id}", ['franchise_id' => $otherFranchise->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['franchise_id']]]);
    }

    public function test_kitchen_staff_can_view_but_not_edit_store(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsRole(UserRole::KITCHEN_STAFF, $tenant['store']);

        $this->getJson("/api/admin/stores/{$tenant['store']->id}")->assertOk();
        $this->putJson("/api/admin/stores/{$tenant['store']->id}", ['name' => 'X'])->assertStatus(403);
        $this->getJson('/api/admin/delivery-zones')->assertStatus(403);
    }

    public function test_store_manager_cannot_create_kitchens_but_manages_zones(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsRole(UserRole::STORE_MANAGER, $tenant['store']);

        $this->postJson('/api/admin/kitchens', ['store_id' => $tenant['store']->id, 'name' => 'K2'])->assertStatus(403);
        $this->putJson("/api/admin/delivery-zones/{$tenant['zone']->id}", ['max_delivery_distance_km' => 12])
            ->assertOk()
            ->assertJsonPath('data.max_delivery_distance_km', 12);
    }

    public function test_catalog_is_managed_only_by_super_admin(): void
    {
        $tenant = $this->createTenant();
        $product = $this->createProduct($tenant['store']);
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $tenant['franchise']);

        $this->getJson('/api/admin/products')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson("/api/admin/products/{$product->id}", ['price' => 1])->assertStatus(403);
        $this->postJson('/api/admin/categories', [])->assertStatus(403);
        $this->getJson('/api/admin/languages')->assertStatus(403);
    }

    public function test_non_staff_cannot_reach_admin_api(): void
    {
        foreach ([UserRole::CUSTOMER, UserRole::DRIVER] as $role) {
            $this->actingAsRole($role);
            $this->getJson('/api/admin/stores')->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
        }
    }

    public function test_franchise_with_stores_cannot_be_deleted(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $this->deleteJson("/api/admin/franchises/{$tenant['franchise']->id}")->assertStatus(409);
    }
}

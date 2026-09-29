<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Store;
use Tests\FeatureTestCase;

/**
 * A franchise / store must never see or modify another franchise's data.
 */
class TenantIsolationTest extends FeatureTestCase
{
    public function test_franchise_admin_only_lists_own_franchise_data(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);

        $this->getJson('/api/admin/franchises')->assertOk()->assertJsonPath('data.*.id', [$own['franchise']->id]);
        $this->getJson('/api/admin/stores')->assertOk()->assertJsonPath('data.*.id', [$own['store']->id]);
        $this->getJson('/api/admin/kitchens')->assertOk()->assertJsonPath('data.*.id', [$own['kitchen']->id]);
        $this->getJson('/api/admin/delivery-zones')->assertOk()->assertJsonPath('data.*.id', [$own['zone']->id]);
        $this->assertNotContains($other['store']->id, $this->getJson('/api/admin/stores')->json('data.*.id'));
    }

    public function test_other_franchise_records_are_not_found(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);

        foreach ([
            "/api/admin/franchises/{$other['franchise']->id}",
            "/api/admin/stores/{$other['store']->id}",
            "/api/admin/kitchens/{$other['kitchen']->id}",
            "/api/admin/delivery-zones/{$other['zone']->id}",
            "/api/admin/stores/{$other['store']->id}/products",
        ] as $url) {
            $this->getJson($url)->assertStatus(404)->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
        }

        $this->putJson("/api/admin/stores/{$other['store']->id}", ['name' => 'Hijacked'])->assertStatus(404);
        $this->putJson("/api/admin/delivery-zones/{$other['zone']->id}", ['base_fee' => 0])->assertStatus(404);
        $this->assertNotSame('Hijacked', $other['store']->fresh()->name);
    }

    public function test_cannot_create_records_under_another_franchise_store(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);

        $this->postJson('/api/admin/kitchens', ['store_id' => $other['store']->id, 'name' => 'Sneaky Kitchen'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['store_id']]]);

        $this->postJson('/api/admin/kitchens', ['store_id' => $own['store']->id, 'name' => 'Second Kitchen'])
            ->assertCreated()
            ->assertJsonPath('data.franchise_id', $own['franchise']->id)
            ->assertJsonPath('data.organization_id', $this->organization->id);
    }

    public function test_store_manager_is_limited_to_own_store(): void
    {
        $tenant = $this->createTenant();
        $sibling = $this->createTenant();
        // A second store in the same franchise.
        $sameFranchiseStore = Store::factory()->create(['franchise_id' => $tenant['franchise']->id]);
        $this->actingAsRole(UserRole::STORE_MANAGER, $tenant['store']);

        $this->getJson('/api/admin/stores')->assertJsonPath('data.*.id', [$tenant['store']->id]);
        $this->getJson("/api/admin/stores/{$sameFranchiseStore->id}")->assertStatus(404);
        $this->getJson("/api/admin/stores/{$sibling['store']->id}")->assertStatus(404);

        $this->putJson("/api/admin/stores/{$tenant['store']->id}", ['is_accepting_orders' => false])
            ->assertOk()
            ->assertJsonPath('data.is_accepting_orders', false);
    }

    public function test_store_product_settings_are_isolated(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $product = $this->createProduct($other['store']);
        $this->actingAsRole(UserRole::STORE_MANAGER, $own['store']);

        $this->putJson("/api/admin/stores/{$other['store']->id}/products/{$product->id}", ['is_available' => false])
            ->assertStatus(404);

        $this->putJson("/api/admin/stores/{$own['store']->id}/products/{$product->id}", ['price' => 380000])
            ->assertOk()
            ->assertJsonPath('data.effective_price', 380000);

        // The other store's listing is untouched.
        $this->assertTrue($other['store']->storeProducts()->where('product_id', $product->id)->first()->is_available);
    }

    public function test_audit_logs_are_scoped_to_franchise(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        AuditLog::create(['action' => 'x.own', 'organization_id' => $this->organization->id, 'franchise_id' => $own['franchise']->id, 'created_at' => now()]);
        AuditLog::create(['action' => 'x.other', 'organization_id' => $this->organization->id, 'franchise_id' => $other['franchise']->id, 'created_at' => now()]);

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);

        $this->getJson('/api/admin/audit-logs')->assertOk()->assertJsonPath('data.*.action', ['x.own']);
    }

    public function test_super_admin_sees_every_franchise(): void
    {
        $this->createTenant();
        $this->createTenant();
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $this->getJson('/api/admin/stores')->assertJsonCount(2, 'data');
        $this->getJson('/api/admin/franchises')->assertJsonCount(2, 'data');
    }
}

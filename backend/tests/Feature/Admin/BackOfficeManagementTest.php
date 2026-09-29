<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\FeatureTestCase;

/**
 * Staff accounts and customers seen from the back office (PHASE 6).
 */
class BackOfficeManagementTest extends FeatureTestCase
{
    private function staffPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Grace Banda',
            'email' => 'grace@example.test',
            'password' => 'secret-pass',
            'role' => 'KITCHEN_STAFF',
            'preferred_language' => 'ny',
        ];
    }

    public function test_franchise_admin_creates_store_staff_in_own_franchise_only(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);

        $created = $this->postJson('/api/admin/staff', $this->staffPayload(['store_id' => $own['store']->id]))
            ->assertCreated()
            ->assertJsonPath('data.role', 'KITCHEN_STAFF')
            ->assertJsonPath('data.franchise_id', $own['franchise']->id)
            ->assertJsonPath('data.store_id', $own['store']->id)
            ->assertJsonPath('data.preferred_language', 'ny')
            ->json('data');
        $this->assertTrue(AuditLog::where('action', 'staff.created')->where('target_id', $created['id'])->exists());

        // Other franchise's store, a higher role, or a tenant id for a store-level role are rejected.
        $this->postJson('/api/admin/staff', $this->staffPayload(['email' => 'x@example.test', 'store_id' => $other['store']->id]))
            ->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['store_id']]]);
        $this->postJson('/api/admin/staff', $this->staffPayload(['email' => 'y@example.test', 'role' => 'FRANCHISE_ADMIN', 'franchise_id' => $own['franchise']->id]))
            ->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['role']]]);
        $this->postJson('/api/admin/staff', $this->staffPayload(['email' => 'z@example.test', 'store_id' => $own['store']->id, 'franchise_id' => $own['franchise']->id]))
            ->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['franchise_id']]]);
    }

    public function test_staff_lists_are_scoped_and_other_tenants_are_not_found(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $mine = User::factory()->kitchenStaff($own['store'])->create();
        $theirs = User::factory()->kitchenStaff($other['store'])->create();
        $manager = User::factory()->storeManager($own['store'])->create();

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);
        $ids = $this->getJson('/api/admin/staff')->assertOk()->json('data.*.id');
        $this->assertEqualsCanonicalizing([$mine->id, $manager->id], $ids);
        $this->getJson("/api/admin/staff/{$theirs->id}")->assertNotFound();
        $this->putJson("/api/admin/staff/{$theirs->id}", ['name' => 'Hijacked'])->assertNotFound();

        // A store manager manages only kitchen staff of their own store (not themselves or peers).
        Sanctum::actingAs($manager);
        $this->assertSame([$mine->id], $this->getJson('/api/admin/staff')->assertOk()->json('data.*.id'));
    }

    public function test_deactivating_staff_revokes_their_sessions(): void
    {
        $tenant = $this->createTenant();
        $staff = User::factory()->kitchenStaff($tenant['store'])->create();
        $staff->createToken('kitchen-web');
        $this->actingAsRole(UserRole::STORE_MANAGER, $tenant['store']);

        $this->getJson("/api/admin/staff/{$staff->id}")->assertOk()->assertJsonPath('data.is_active', true);
        $this->putJson("/api/admin/staff/{$staff->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.id', $staff->id)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($staff->fresh()->is_active);
        $this->assertSame(0, $staff->tokens()->count());
    }

    public function test_store_manager_moves_staff_only_between_visible_stores(): void
    {
        $tenant = $this->createTenant();
        $foreign = Store::factory()->create(['franchise_id' => $tenant['franchise']->id]);
        $staff = User::factory()->kitchenStaff($tenant['store'])->create();
        $this->actingAsRole(UserRole::STORE_MANAGER, $tenant['store']);

        $this->putJson("/api/admin/staff/{$staff->id}", ['store_id' => $foreign->id])->assertStatus(422);
        $this->assertSame($tenant['store']->id, $staff->fresh()->store_id);
    }

    public function test_customers_are_visible_only_after_ordering_in_scope(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $ownOrder = $this->placeOrder($own['store']);
        $otherOrder = $this->placeOrder($other['store']);

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);
        $rows = $this->getJson('/api/admin/customers')->assertOk()->json('data');
        $this->assertSame([$ownOrder->customer_id], array_column($rows, 'id'));
        $this->assertSame(1, $rows[0]['orders_count']);

        $this->getJson("/api/admin/customers/{$otherOrder->customer_id}")->assertNotFound();
        $this->getJson("/api/admin/customers/{$ownOrder->customer_id}")
            ->assertOk()
            ->assertJsonPath('data.recent_orders.0.id', $ownOrder->id);

        $this->actingAsRole(UserRole::KITCHEN_STAFF, $own['store']);
        $this->getJson('/api/admin/customers')->assertForbidden();
    }

    public function test_customers_can_be_searched_by_name_or_phone(): void
    {
        $tenant = $this->createTenant();
        $order = $this->placeOrder($tenant['store']);
        $order->customer->update(['name' => 'Chikondi Phiri', 'phone' => '+265991112233']);
        $this->placeOrder($tenant['store']);

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $this->assertSame([$order->customer_id], $this->getJson('/api/admin/customers?search=chikondi')->json('data.*.id'));
        $this->assertSame([$order->customer_id], $this->getJson('/api/admin/customers?search=0991112233')->json('data.*.id'));
        $this->assertCount(0, $this->getJson('/api/admin/customers?search=nobody')->json('data'));
    }
}

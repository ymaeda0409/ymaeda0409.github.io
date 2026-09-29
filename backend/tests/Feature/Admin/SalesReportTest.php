<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Tests\FeatureTestCase;

class SalesReportTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 10:00 in Blantyre (UTC+2).
        $this->travelTo(CarbonImmutable::parse('2026-03-10 08:00:00', 'UTC'));
    }

    /**
     * A delivered + paid order placed at the given local (Africa/Blantyre) time.
     */
    private function sale(Store $store, string $localTime, array $overrides = []): Order
    {
        $order = $this->placeOrder($store);
        $order->forceFill($overrides + [
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'ordered_at' => CarbonImmutable::parse($localTime, 'Africa/Blantyre')->utc(),
        ])->save();

        return $order->refresh();
    }

    public function test_summary_counts_only_delivered_and_paid_orders(): void
    {
        $tenant = $this->createTenant();
        $a = $this->sale($tenant['store'], '2026-03-09 12:00');
        $b = $this->sale($tenant['store'], '2026-03-10 09:00');
        $this->sale($tenant['store'], '2026-03-10 09:30', ['status' => OrderStatus::CANCELLED, 'payment_status' => PaymentStatus::PENDING]);
        $this->sale($tenant['store'], '2026-03-10 09:40', ['status' => OrderStatus::COOKING]);

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $summary = $this->getJson('/api/admin/sales?from=2026-03-09&to=2026-03-10')->assertOk()->json('data.summary');

        $this->assertSame(2, $summary['orders']);
        $this->assertSame($a->total + $b->total, $summary['gross_sales']);
        $this->assertSame($a->subtotal + $b->subtotal, $summary['subtotal']);
        $this->assertSame($a->delivery_fee + $b->delivery_fee, $summary['delivery_fees']);
        $this->assertSame(intdiv($a->total + $b->total, 2), $summary['average_order_value']);
        $this->assertSame(1, $summary['cancelled']);
    }

    public function test_days_follow_the_local_timezone_and_include_empty_days(): void
    {
        $tenant = $this->createTenant();
        // 01:30 local on the 10th is still the 9th in UTC; it must count for the 10th.
        $late = $this->sale($tenant['store'], '2026-03-10 01:30');

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $rows = $this->getJson('/api/admin/sales?from=2026-03-08&to=2026-03-10&group_by=day')->assertOk()->json('data.rows');

        $this->assertSame(['2026-03-08', '2026-03-09', '2026-03-10'], array_column($rows, 'key'));
        $this->assertSame([0, 0, 1], array_column($rows, 'orders'));
        $this->assertSame($late->total, $rows[2]['gross_sales']);
    }

    public function test_franchise_rows_include_commission_on_subtotal(): void
    {
        $tenant = $this->createTenant();
        $tenant['franchise']->update(['commission_rate' => 10]);
        $order = $this->sale($tenant['store'], '2026-03-10 09:00');

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $row = $this->getJson('/api/admin/sales?from=2026-03-10&to=2026-03-10&group_by=franchise')->assertOk()->json('data.rows.0');

        $this->assertSame($tenant['franchise']->id, $row['key']);
        $this->assertEquals(10, $row['commission_rate']);
        $this->assertSame((int) round($order->subtotal * 0.10), $row['commission']);
    }

    public function test_product_rows_use_the_viewers_language(): void
    {
        $tenant = $this->createTenant();
        $this->sale($tenant['store'], '2026-03-10 09:00');
        $this->sale($tenant['store'], '2026-03-10 09:10');

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $rows = $this->getJson('/api/admin/sales?from=2026-03-10&to=2026-03-10&group_by=product', ['Accept-Language' => 'ja'])
            ->assertOk()->json('data.rows');

        $this->assertCount(1, $rows);
        $this->assertSame('チキン弁当', $rows[0]['label']);
        $this->assertSame(2, $rows[0]['quantity']);
        $this->assertSame(2, $rows[0]['orders']);
    }

    public function test_franchise_admin_never_sees_other_franchise_sales(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $mine = $this->sale($own['store'], '2026-03-10 09:00');
        $this->sale($other['store'], '2026-03-10 09:00');

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);
        $data = $this->getJson('/api/admin/sales?from=2026-03-10&to=2026-03-10&group_by=store')->assertOk()->json('data');

        $this->assertSame(1, $data['summary']['orders']);
        $this->assertSame($mine->total, $data['summary']['gross_sales']);
        $this->assertSame([$own['store']->id], array_column($data['rows'], 'key'));

        // Filtering by another franchise's store is rejected like a non-existent id.
        $this->getJson("/api/admin/sales?store_id={$other['store']->id}")->assertStatus(422);
    }

    public function test_store_manager_sees_only_own_store_and_kitchen_staff_is_forbidden(): void
    {
        $tenant = $this->createTenant();
        $secondStore = Store::factory()->create(['franchise_id' => $tenant['franchise']->id]);
        $this->sale($tenant['store'], '2026-03-10 09:00');

        $this->actingAsRole(UserRole::STORE_MANAGER, $secondStore);
        $this->getJson('/api/admin/sales?from=2026-03-10&to=2026-03-10')->assertOk()->assertJsonPath('data.summary.orders', 0);

        $this->actingAsRole(UserRole::KITCHEN_STAFF, $tenant['store']);
        $this->getJson('/api/admin/sales')->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_range_is_validated_and_csv_export_uses_major_units(): void
    {
        $tenant = $this->createTenant();
        $order = $this->sale($tenant['store'], '2026-03-10 09:00');
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $this->getJson('/api/admin/sales?from=2026-03-10&to=2026-03-01')->assertStatus(422);
        $this->getJson('/api/admin/sales?from=2025-01-01&to=2026-03-10')->assertStatus(422);

        $response = $this->get('/api/admin/sales?from=2026-03-10&to=2026-03-10&group_by=store&format=csv')->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $lines = array_map('str_getcsv', explode("\n", trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));

        $this->assertSame('key', $lines[0][0]);
        $this->assertContains('currency', $lines[0]);
        $gross = $lines[1][array_search('gross_sales', $lines[0], true)];
        $this->assertSame(number_format($order->total / 100, 2, '.', ''), $gross);
    }

    public function test_dashboard_is_scoped_and_hides_money_without_sales_permission(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $this->sale($own['store'], '2026-03-10 09:00');
        $this->placeOrder($own['store']);
        $this->placeOrder($other['store']);

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);
        $data = $this->getJson('/api/admin/dashboard')->assertOk()->json('data');
        $this->assertSame(2, $data['orders_today']);
        $this->assertSame(1, $data['active_orders']['NEW']);
        $this->assertSame(1, $data['sales_today']['orders']);
        $this->assertCount(7, $data['last_7_days']);
        $this->assertSame(1, $data['stores_total']);

        $this->actingAsRole(UserRole::KITCHEN_STAFF, $own['store']);
        $data = $this->getJson('/api/admin/dashboard')->assertOk()->json('data');
        $this->assertArrayNotHasKey('sales_today', $data);
        $this->assertArrayNotHasKey('top_products', $data);
    }
}

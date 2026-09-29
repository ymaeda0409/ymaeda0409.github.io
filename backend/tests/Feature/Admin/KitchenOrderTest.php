<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Store;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\FeatureTestCase;

class KitchenOrderTest extends FeatureTestCase
{
    private function placeOrder(Store $store, string $paymentMethod = 'CASH', string $locale = 'en'): Order
    {
        $product = $store->storeProducts()->first()?->product ?? $this->createProduct($store);
        $customer = $this->actingAsRole(UserRole::CUSTOMER);
        $address = UserAddress::factory()->create([
            'user_id' => $customer->id,
            'latitude' => $store->latitude - 0.005,
            'longitude' => $store->longitude,
        ]);

        $id = $this->postJson('/api/orders', [
            'store_id' => $store->id,
            'delivery_address_id' => $address->id,
            'payment_method' => $paymentMethod,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], ['Accept-Language' => $locale])->assertCreated()->json('data.id');

        return Order::findOrFail($id);
    }

    public function test_kitchen_flow_accept_cook_ready(): void
    {
        Event::fake([OrderStatusChanged::class]);
        ['store' => $store] = $this->createTenant();
        $order = $this->placeOrder($store);
        $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);

        $this->postJson("/api/admin/orders/{$order->id}/accept")->assertOk()->assertJsonPath('data.status', 'CONFIRMED');
        $this->postJson("/api/admin/orders/{$order->id}/start-cooking")->assertOk()->assertJsonPath('data.status', 'COOKING');
        $this->postJson("/api/admin/orders/{$order->id}/ready")
            ->assertOk()
            ->assertJsonPath('data.status', 'READY_FOR_PICKUP')
            ->assertJsonPath('data.timeline.*.status', ['NEW', 'CONFIRMED', 'COOKING', 'READY_FOR_PICKUP']);

        $order->refresh();
        $this->assertNotNull($order->accepted_at);
        $this->assertNotNull($order->cooking_started_at);
        $this->assertNotNull($order->ready_at);
        Event::assertDispatched(OrderStatusChanged::class, fn ($e) => $e->to === OrderStatus::READY_FOR_PICKUP);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        ['store' => $store] = $this->createTenant();
        $order = $this->placeOrder($store);
        $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);

        $this->postJson("/api/admin/orders/{$order->id}/ready")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_STATUS_TRANSITION');
        $this->postJson("/api/admin/orders/{$order->id}/accept")->assertOk();
        $this->postJson("/api/admin/orders/{$order->id}/accept")->assertJsonPath('error.code', 'INVALID_STATUS_TRANSITION');
    }

    public function test_unpaid_mobile_money_orders_cannot_be_accepted(): void
    {
        ['store' => $store] = $this->createTenant();
        $order = $this->placeOrder($store, 'AIRTEL_MONEY');
        $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);

        $this->getJson("/api/admin/orders/{$order->id}")->assertJsonPath('data.is_payable', false);
        $this->postJson("/api/admin/orders/{$order->id}/accept")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'PAYMENT_REQUIRED');

        $order->update(['payment_status' => 'PAID']);
        $this->postJson("/api/admin/orders/{$order->id}/accept")->assertOk();
    }

    public function test_kitchen_board_lists_active_orders_oldest_first(): void
    {
        ['store' => $store] = $this->createTenant();
        $first = $this->placeOrder($store);
        $second = $this->placeOrder($store);
        $done = $this->placeOrder($store);
        $done->update(['status' => OrderStatus::DELIVERED]);
        $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);

        $this->getJson('/api/admin/orders?board=kitchen')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$first->id, $second->id]);
    }

    public function test_kitchen_sees_item_names_in_staff_language(): void
    {
        ['store' => $store] = $this->createTenant();
        $order = $this->placeOrder($store, locale: 'ja');
        $staff = $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);
        $staff->update(['preferred_language' => 'ny']);
        Sanctum::actingAs($staff->fresh());

        $this->getJson("/api/admin/orders/{$order->id}")
            ->assertJsonPath('meta.locale', 'ny')
            ->assertJsonPath('data.items.0.name', 'Bento ya Nkhuku')
            ->assertJsonPath('data.items.0.name_snapshot', 'チキン弁当')
            ->assertJsonPath('data.locale', 'ja');
    }

    public function test_other_franchise_orders_are_invisible_and_untouchable(): void
    {
        ['store' => $own, 'franchise' => $ownFranchise] = $this->createTenant();
        ['store' => $other] = $this->createTenant();
        $foreign = $this->placeOrder($other);
        $mine = $this->placeOrder($own);

        foreach ([
            fn () => $this->actingAsRole(UserRole::KITCHEN_STAFF, $own),
            fn () => $this->actingAsRole(UserRole::STORE_MANAGER, $own),
            fn () => $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $ownFranchise),
        ] as $login) {
            $login();
            $this->getJson('/api/admin/orders')->assertJsonPath('data.*.id', [$mine->id]);
            $this->getJson("/api/admin/orders/{$foreign->id}")->assertStatus(404);
            $this->postJson("/api/admin/orders/{$foreign->id}/accept")->assertStatus(404);
        }

        $this->assertSame(OrderStatus::NEW, $foreign->fresh()->status);
    }

    public function test_store_cancel_restocks_and_records_reason(): void
    {
        ['store' => $store] = $this->createTenant();
        $this->createProduct($store, storeOverrides: ['stock_quantity' => 3]);
        $order = $this->placeOrder($store);
        $this->actingAsRole(UserRole::STORE_MANAGER, $store);

        $this->postJson("/api/admin/orders/{$order->id}/cancel", ['reason_code' => 'OUT_OF_INGREDIENTS'])
            ->assertOk()
            ->assertJsonPath('data.cancel_reason_code', 'OUT_OF_INGREDIENTS');
        $this->assertSame(3, $store->storeProducts()->first()->stock_quantity);
    }

    public function test_customers_and_drivers_cannot_use_kitchen_actions(): void
    {
        ['store' => $store] = $this->createTenant();
        $order = $this->placeOrder($store);

        $this->actingAsRole(UserRole::DRIVER);
        $this->postJson("/api/admin/orders/{$order->id}/accept")->assertStatus(403);
    }
}

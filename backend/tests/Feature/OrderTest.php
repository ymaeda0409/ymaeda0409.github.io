<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Event;
use Tests\FeatureTestCase;

class OrderTest extends FeatureTestCase
{
    private Store $store;

    private User $customer;

    private UserAddress $address;

    private Product $bento;

    private Product $water;

    protected function setUp(): void
    {
        parent::setUp();

        ['store' => $this->store] = $this->createTenant(['code' => 'LLW-CENTRAL']);
        $category = $this->createCategory();
        $this->bento = $this->createProduct($this->store, $category, attributes: ['price' => 350000]);
        $group = $this->bento->optionGroups()->create(['min_select' => 1, 'max_select' => 1]);
        $group->translations()->create(['locale' => 'en', 'name' => 'Rice size']);
        foreach ([['Regular', 'ご飯普通', 0], ['Large', 'ご飯大盛り', 50000]] as [$en, $ja, $price]) {
            $option = $group->options()->create(['price' => $price]);
            $option->translations()->createMany([['locale' => 'en', 'name' => $en], ['locale' => 'ja', 'name' => $ja]]);
        }
        $this->water = $this->createProduct($this->store, $category, [
            'en' => ['name' => 'Water'], 'ja' => ['name' => '水'],
        ], ['price' => 50000], ['stock_quantity' => 5]);

        $this->customer = $this->actingAsRole(UserRole::CUSTOMER);
        // ~1 km from the store: base delivery fee.
        $this->address = UserAddress::factory()->create(['user_id' => $this->customer->id, 'latitude' => -13.97, 'longitude' => 33.78]);
    }

    private function largeOptionId(): int
    {
        return $this->bento->optionGroups()->first()->options()->where('price', 50000)->value('id');
    }

    private function regularOptionId(): int
    {
        return $this->bento->optionGroups()->first()->options()->where('price', 0)->value('id');
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'store_id' => $this->store->id,
            'delivery_address_id' => $this->address->id,
            'payment_method' => 'CASH',
            'items' => [
                ['product_id' => $this->bento->id, 'quantity' => 2, 'option_ids' => [$this->largeOptionId()]],
                ['product_id' => $this->water->id, 'quantity' => 1],
            ],
        ];
    }

    public function test_place_order_calculates_amounts_on_the_server(): void
    {
        Event::fake([OrderPlaced::class]);
        config(['bento.pricing.service_fee' => 20000]);

        $response = $this->postJson('/api/orders', $this->payload())->assertCreated();

        // (3500 + 500) × 2 + 500 = 8,500 ; delivery 1,500 ; service 200
        $response->assertJsonPath('data.status', 'NEW')
            ->assertJsonPath('data.payment_status', 'PENDING')
            ->assertJsonPath('data.subtotal', 850000)
            ->assertJsonPath('data.delivery_fee', 150000)
            ->assertJsonPath('data.service_fee', 20000)
            ->assertJsonPath('data.total', 1020000)
            ->assertJsonPath('data.items.0.unit_price', 350000)
            ->assertJsonPath('data.items.0.option_amount', 50000)
            ->assertJsonPath('data.items.0.total', 800000)
            ->assertJsonPath('data.timeline.0.status', 'NEW');

        $this->assertMatchesRegularExpression('/^\d{4}$/', $response->json('data.delivery_pin'));
        $this->assertMatchesRegularExpression('/^LLW-CENTRAL-\d{6}-0001$/', $response->json('data.order_number'));
        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_client_supplied_prices_are_ignored(): void
    {
        $payload = $this->payload();
        $payload['total'] = 1;
        $payload['items'][0]['unit_price'] = 1;

        $this->postJson('/api/orders', $payload)->assertCreated()->assertJsonPath('data.subtotal', 850000);
    }

    public function test_store_price_override_is_used(): void
    {
        $this->bento->storeProducts()->where('store_id', $this->store->id)->update(['price' => 300000]);

        $this->postJson('/api/orders', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.items.0.unit_price', 300000);
    }

    public function test_item_names_are_snapshotted_in_the_order_language(): void
    {
        $id = $this->postJson('/api/orders', $this->payload(), ['Accept-Language' => 'ja'])->json('data.id');

        $this->bento->translations()->where('locale', 'ja')->update(['name' => '改名された弁当']);
        $this->bento->update(['price' => 999900]);

        $this->getJson("/api/orders/{$id}")
            ->assertJsonPath('data.items.0.name', 'チキン弁当')
            ->assertJsonPath('data.items.0.options.0.name', 'ご飯大盛り')
            ->assertJsonPath('data.items.0.unit_price', 350000);
        $this->assertSame('ja', Order::find($id)->locale);
    }

    public function test_address_outside_the_delivery_zone_is_rejected(): void
    {
        $far = UserAddress::factory()->create(['user_id' => $this->customer->id, 'latitude' => -15.78, 'longitude' => 35.00]);

        $this->postJson('/api/orders', $this->payload(['delivery_address_id' => $far->id]))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'OUT_OF_DELIVERY_AREA');
    }

    public function test_another_customers_address_cannot_be_used(): void
    {
        $other = UserAddress::factory()->create();

        $this->postJson('/api/orders', $this->payload(['delivery_address_id' => $other->id]))->assertStatus(404);
    }

    public function test_unavailable_or_sold_out_products_are_rejected(): void
    {
        $this->water->storeProducts()->update(['stock_quantity' => 0]);
        $this->postJson('/api/orders', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'PRODUCT_NOT_AVAILABLE')
            ->assertJsonStructure(['error' => ['fields' => ['items.1.product_id']]]);

        $this->water->storeProducts()->update(['stock_quantity' => null, 'is_available' => false]);
        $this->postJson('/api/orders', $this->payload())->assertJsonPath('error.code', 'PRODUCT_NOT_AVAILABLE');
    }

    public function test_option_rules_are_enforced(): void
    {
        $missingRequired = $this->payload();
        $missingRequired['items'][0]['option_ids'] = [];
        $this->postJson('/api/orders', $missingRequired)
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['items.0.option_ids']]]);

        $tooMany = $this->payload();
        $tooMany['items'][0]['option_ids'] = [$this->largeOptionId(), $this->regularOptionId()];
        $this->postJson('/api/orders', $tooMany)->assertStatus(422);

        $foreign = $this->payload();
        $foreign['items'][1]['option_ids'] = [$this->largeOptionId()];
        $this->postJson('/api/orders', $foreign)->assertStatus(422);
    }

    public function test_stock_is_decremented_and_restored_on_cancel(): void
    {
        $id = $this->postJson('/api/orders', $this->payload())->json('data.id');
        $this->assertSame(4, $this->water->storeProducts()->first()->stock_quantity);

        $this->postJson("/api/orders/{$id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'CANCELLED')
            ->assertJsonPath('data.cancel_reason_code', 'CUSTOMER_CANCELLED');
        $this->assertSame(5, $this->water->storeProducts()->first()->stock_quantity);
    }

    public function test_customer_cannot_cancel_once_cooking(): void
    {
        $id = $this->postJson('/api/orders', $this->payload())->json('data.id');
        Order::whereKey($id)->update(['status' => 'COOKING']);

        $this->postJson("/api/orders/{$id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_STATUS_TRANSITION');
    }

    public function test_closed_store_rejects_orders_but_accepts_scheduled_ones_for_open_hours(): void
    {
        $this->store->update(['opening_hours' => array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], [['10:00', '11:00']])]);
        $this->travelTo(now('Africa/Blantyre')->setTime(20, 0));

        $this->postJson('/api/orders', $this->payload())->assertJsonPath('error.code', 'STORE_NOT_AVAILABLE');

        $tomorrow = now('Africa/Blantyre')->addDay()->setTime(10, 30)->toIso8601String();
        $this->postJson('/api/orders', $this->payload(['scheduled_at' => $tomorrow]))
            ->assertCreated()
            ->assertJsonPath('data.scheduled_at', now('Africa/Blantyre')->addDay()->setTime(10, 30)->utc()->toIso8601String());
    }

    public function test_order_numbers_are_sequential_per_store_and_day(): void
    {
        $first = $this->postJson('/api/orders', $this->payload())->json('data.order_number');
        $second = $this->postJson('/api/orders', $this->payload())->json('data.order_number');

        $this->assertStringEndsWith('-0001', $first);
        $this->assertStringEndsWith('-0002', $second);
    }

    public function test_customers_only_see_their_own_orders(): void
    {
        $mine = $this->postJson('/api/orders', $this->payload())->json('data.id');

        $this->actingAsRole(UserRole::CUSTOMER);
        $this->getJson("/api/orders/{$mine}")->assertStatus(404);
        $this->postJson("/api/orders/{$mine}/cancel")->assertStatus(404);
        $this->getJson('/api/orders')->assertJsonCount(0, 'data');
    }

    public function test_order_history_is_paginated_newest_first(): void
    {
        $first = $this->postJson('/api/orders', $this->payload())->json('data.id');
        $second = $this->postJson('/api/orders', $this->payload())->json('data.id');

        $this->getJson('/api/orders')
            ->assertJsonPath('data.*.id', [$second, $first])
            ->assertJsonPath('data.0.item_count', 3)
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_quote_matches_the_placed_order(): void
    {
        $quote = $this->postJson('/api/orders/quote', collect($this->payload())->except('payment_method')->all(), ['Accept-Language' => 'ja'])
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'チキン弁当')
            ->json('data');

        $order = $this->postJson('/api/orders', $this->payload())->json('data');
        $this->assertSame($quote['total'], $order['total']);
        $this->assertSame($quote['delivery_fee'], $order['delivery_fee']);
    }

    public function test_guest_quote_by_gps_point(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/orders/quote', [
            'store_id' => $this->store->id,
            'latitude' => -13.97,
            'longitude' => 33.78,
            'items' => [['product_id' => $this->water->id, 'quantity' => 2]],
        ])->assertOk()->assertJsonPath('data.total', 100000 + 150000);
    }

    public function test_only_customers_can_place_orders(): void
    {
        $this->actingAsRole(UserRole::STORE_MANAGER, $this->store);

        $this->postJson('/api/orders', $this->payload())->assertStatus(403);
    }
}

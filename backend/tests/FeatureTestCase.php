<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\DeliveryZone;
use App\Models\Franchise;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Order\OrderStatusService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Database-backed tests with en/ny/ja languages seeded and tenant builders.
 */
abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);

        // Symfony adds "Accept-Language: en-us" to every test request; mobile clients send
        // nothing unless the user picked a language, so start from no header.
        $this->withHeader('Accept-Language', '');
        $this->organization = Organization::factory()->create(['slug' => 'malawi-bento']);
    }

    /**
     * Franchise → Store → Kitchen → DeliveryZone, located in Lilongwe by default.
     *
     * @return array{franchise: Franchise, store: Store, kitchen: Kitchen, zone: DeliveryZone}
     */
    protected function createTenant(array $storeAttributes = [], array $zoneAttributes = []): array
    {
        $franchise = Franchise::factory()->create(['organization_id' => $this->organization->id]);
        $store = Store::factory()->create(['franchise_id' => $franchise->id] + $storeAttributes);
        $kitchen = Kitchen::factory()->create(['store_id' => $store->id]);
        $zone = DeliveryZone::factory()->create(['store_id' => $store->id, 'kitchen_id' => $kitchen->id] + $zoneAttributes);

        return compact('franchise', 'store', 'kitchen', 'zone');
    }

    protected function createCategory(array $names = ['en' => 'Bento', 'ja' => '弁当', 'ny' => 'Bento']): ProductCategory
    {
        return ProductCategory::factory()->named($names)->create(['organization_id' => $this->organization->id]);
    }

    protected function createProduct(Store $store, ?ProductCategory $category = null, array $translations = [], array $attributes = [], array $storeOverrides = []): Product
    {
        $category ??= $this->createCategory();
        $translations = $translations ?: [
            'en' => ['name' => 'Chicken Bento', 'description' => 'Grilled chicken with rice'],
            'ja' => ['name' => 'チキン弁当', 'description' => 'グリルチキンとご飯'],
            'ny' => ['name' => 'Bento ya Nkhuku', 'description' => 'Nkhuku ndi mpunga'],
        ];

        return Product::factory()
            ->translated($translations)
            ->soldAt($store, $storeOverrides)
            ->create(['category_id' => $category->id] + $attributes);
    }

    protected function actingAsRole(UserRole $role, ?Store $store = null, ?Franchise $franchise = null): User
    {
        $factory = User::factory();
        $user = (match ($role) {
            UserRole::SUPER_ADMIN => $factory->superAdmin($this->organization->id),
            UserRole::FRANCHISE_ADMIN => $factory->franchiseAdmin($franchise ?? $store->franchise),
            UserRole::STORE_MANAGER => $factory->storeManager($store),
            UserRole::KITCHEN_STAFF => $factory->kitchenStaff($store),
            UserRole::DRIVER => $factory->driver(),
            UserRole::CUSTOMER => $factory,
        })->create();

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Places a real order through the API as a fresh customer ~0.5 km from the store.
     */
    protected function placeOrder(Store $store, string $paymentMethod = 'CASH', string $locale = 'en'): Order
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

    /**
     * Moves an order through the kitchen to READY_FOR_PICKUP (triggers driver dispatch).
     */
    protected function makeReady(Order $order): Order
    {
        $statuses = app(OrderStatusService::class);
        $staff = User::factory()->kitchenStaff($order->store)->create();
        $statuses->accept($order, $staff);
        $statuses->startCooking($order, $staff);

        return $statuses->markReady($order, $staff)->refresh();
    }
}

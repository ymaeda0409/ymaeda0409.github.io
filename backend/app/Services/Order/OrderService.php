<?php

namespace App\Services\Order;

use App\Enums\ErrorCode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Catalog\CatalogService;
use App\Services\Delivery\AvailableStore;
use App\Services\Delivery\StoreLocatorService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly StoreLocatorService $locator,
        private readonly OrderPricingService $pricing,
        private readonly OrderStatusService $statuses,
    ) {}

    /**
     * Resolves store, delivery zone and fee for a delivery point, or fails with a code.
     */
    public function deliveryFor(int $storeId, float $latitude, float $longitude): AvailableStore
    {
        $store = $this->catalog->findPublicStore($storeId);
        $match = $this->locator->matchStore($store, $latitude, $longitude);

        if ($match === null) {
            throw ApiException::of(ErrorCode::OUT_OF_DELIVERY_AREA);
        }

        return $match;
    }

    /**
     * @param  array{store_id: int, delivery_address_id: int, payment_method: string, scheduled_at?: string|null,
     *               items: list<array{product_id: int, quantity: int, option_ids?: list<int>}>}  $data
     */
    public function place(User $customer, array $data): Order
    {
        /** @var UserAddress $address */
        $address = $customer->addresses()->findOrFail($data['delivery_address_id']);
        $delivery = $this->deliveryFor($data['store_id'], $address->latitude, $address->longitude);
        // Stored in UTC: the DB column has no offset, so an unconverted "+02:00" time would shift.
        $scheduledAt = isset($data['scheduled_at']) ? CarbonImmutable::parse($data['scheduled_at'])->utc() : null;

        if (! $delivery->store->isOpenAt($scheduledAt ?? now())) {
            throw ApiException::of(ErrorCode::STORE_NOT_AVAILABLE);
        }

        $order = DB::transaction(function () use ($customer, $data, $address, $delivery, $scheduledAt) {
            // Serializes order numbering per store.
            $store = Store::query()->whereKey($delivery->store->id)->lockForUpdate()->firstOrFail();
            $quote = $this->pricing->quote($delivery, $data['items'], lockStock: true);
            $method = PaymentMethod::from($data['payment_method']);

            $order = Order::create([
                'order_number' => $this->nextOrderNumber($store),
                'organization_id' => $store->organization_id,
                'franchise_id' => $store->franchise_id,
                'store_id' => $store->id,
                'kitchen_id' => $delivery->kitchen?->id,
                'customer_id' => $customer->id,
                'delivery_address_id' => $address->id,
                'delivery_zone_id' => $delivery->zone->id,
                'status' => OrderStatus::NEW,
                'payment_status' => PaymentStatus::PENDING,
                'payment_method' => $method,
                'currency' => $store->currency,
                'subtotal' => $quote->subtotal,
                'delivery_fee' => $quote->deliveryFee,
                'service_fee' => $quote->serviceFee,
                'discount' => $quote->discount,
                'total' => $quote->total(),
                'delivery_latitude' => $address->latitude,
                'delivery_longitude' => $address->longitude,
                'delivery_distance_km' => $delivery->distanceKm,
                'delivery_address_snapshot' => $address->only(['name', 'area', 'street', 'building', 'landmark', 'delivery_note', 'phone']),
                'delivery_pin' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                'locale' => App::getLocale(),
                'scheduled_at' => $scheduledAt,
                'ordered_at' => now(),
            ]);

            foreach ($quote->lines as $line) {
                $item = $order->items()->create([
                    'product_id' => $line->product->id,
                    'locale' => App::getLocale(),
                    'product_name_snapshot' => $line->product->translate('name') ?? $line->product->sku,
                    'product_description_snapshot' => $line->product->translate('description'),
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unitPrice,
                    'option_amount' => $line->optionAmount,
                    'total' => $line->total(),
                ]);
                foreach ($line->options as $option) {
                    $item->options()->create([
                        'option_id' => $option->id,
                        'option_name_snapshot' => $option->translate('name') ?? '',
                        'price' => $option->price,
                    ]);
                }
                if ($line->storeProduct->stock_quantity !== null) {
                    $line->storeProduct->decrement('stock_quantity', $line->quantity);
                }
            }

            $this->statuses->recordInitial($order, $customer);

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    /**
     * {STORE CODE}-{yymmdd in store timezone}-{daily sequence}, e.g. LLW-CENTRAL-260929-0001.
     * Called while the store row is locked.
     */
    private function nextOrderNumber(Store $store): string
    {
        $today = now($store->timezone);
        $sequence = Order::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $today->copy()->startOfDay()->utc())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $store->code, $today->format('ymd'), $sequence);
    }
}

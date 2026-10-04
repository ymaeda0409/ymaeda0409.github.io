<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Store;
use App\Services\Admin\DriverService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DriverController extends Controller
{
    public function __construct(private readonly DriverService $drivers) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Driver::class);

        $drivers = Driver::query()
            ->visibleTo($request->user())
            ->with('user')
            ->when($request->has('online'), fn ($q) => $q->where('is_online', $request->boolean('online')))
            ->orderByDesc('is_online')
            ->orderBy('id')
            ->paginate($this->perPage($request, 50));

        return ApiResponse::success(DriverResource::collection($drivers));
    }

    /**
     * Live dispatch map: riders who are online or on a delivery (with their GPS and the
     * order they carry), orders waiting for a rider, and the stores, all in the
     * viewer's scope. Polled by the admin map every few seconds.
     */
    public function live(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Driver::class);
        $user = $request->user();
        $freshMinutes = (int) config('bento.dispatch.location_max_age_minutes');

        $active = Order::query()->visibleTo($user)
            ->whereIn('status', Driver::ACTIVE_DELIVERY_STATUSES)
            ->whereNotNull('driver_id')
            ->with('kitchen', 'store')
            ->get()
            ->keyBy('driver_id');

        $drivers = Driver::query()->visibleTo($user)
            ->with('user')
            ->where(fn ($q) => $q->where('is_online', true)->orWhereIn('id', $active->keys()))
            ->orderBy('id')
            ->get()
            ->map(function (Driver $driver) use ($active, $freshMinutes) {
                $order = $active->get($driver->id);

                return [
                    'id' => $driver->id,
                    'name' => $driver->user?->name,
                    'phone' => $driver->user?->phone,
                    'vehicle_type' => $driver->vehicle_type->value,
                    'is_online' => $driver->is_online,
                    'latitude' => $driver->current_latitude,
                    'longitude' => $driver->current_longitude,
                    'location_updated_at' => $driver->location_updated_at?->toIso8601String(),
                    'location_fresh' => $driver->location_updated_at?->greaterThanOrEqualTo(now()->subMinutes($freshMinutes)) ?? false,
                    'delivery' => $order ? [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status->value,
                        'pickup' => $this->point($order->kitchen?->latitude ?? $order->store->latitude, $order->kitchen?->longitude ?? $order->store->longitude),
                        'dropoff' => $this->point($order->delivery_latitude, $order->delivery_longitude),
                    ] : null,
                ];
            });

        $waiting = Order::query()->visibleTo($user)
            ->where('status', OrderStatus::READY_FOR_PICKUP)
            ->whereNull('driver_id')
            ->with('kitchen', 'store')
            ->orderBy('ready_at')
            ->get()
            ->map(fn (Order $order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'ready_at' => $order->ready_at?->toIso8601String(),
                'pickup' => $this->point($order->kitchen?->latitude ?? $order->store->latitude, $order->kitchen?->longitude ?? $order->store->longitude),
                'dropoff' => $this->point($order->delivery_latitude, $order->delivery_longitude),
            ]);

        $stores = Store::query()->visibleTo($user)->where('is_active', true)->get()
            ->map(fn (Store $store) => ['id' => $store->id, 'name' => $store->name] + $this->point($store->latitude, $store->longitude));

        return ApiResponse::success([
            'drivers' => $drivers->values(),
            'waiting_orders' => $waiting->values(),
            'stores' => $stores->values(),
            'fresh_minutes' => $freshMinutes,
        ]);
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    private function point(mixed $latitude, mixed $longitude): array
    {
        return ['latitude' => (float) $latitude, 'longitude' => (float) $longitude];
    }

    public function store(DriverRequest $request): JsonResponse
    {
        $driver = $this->drivers->create($request->franchiseId(), $request->validated());

        return ApiResponse::created(new DriverResource($driver->load('user')));
    }

    public function show(Driver $driver): JsonResponse
    {
        Gate::authorize('view', $driver);

        return ApiResponse::success(new DriverResource($driver->load('user')));
    }

    public function update(DriverRequest $request, Driver $driver): JsonResponse
    {
        return ApiResponse::success(new DriverResource($this->drivers->update($driver, $request->validated())->load('user')));
    }
}

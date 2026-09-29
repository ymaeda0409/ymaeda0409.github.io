<?php

namespace App\Http\Controllers\Api\Driver;

use App\Enums\DriverStatus;
use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\LocationRequest;
use App\Http\Resources\DeliveryRequestResource;
use App\Http\Resources\DriverDeliveryResource;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\Order;
use App\Services\Delivery\DeliveryAssignmentService;
use App\Services\Delivery\DeliveryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Driver app API. Every action is limited to the signed-in driver's own offers/orders.
 */
class DriverController extends Controller
{
    public function __construct(
        private readonly DeliveryService $deliveries,
        private readonly DeliveryAssignmentService $assignments,
    ) {}

    public function me(Request $request): JsonResponse
    {
        $driver = $this->driver($request);
        $active = $driver->activeOrder();

        return ApiResponse::success([
            'driver' => new DriverResource($driver->load('user')),
            'active_delivery' => $active ? new DriverDeliveryResource($this->loadDelivery($active)) : null,
        ]);
    }

    public function online(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);
        $driver = $this->deliveries->goOnline(
            $this->driver($request),
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
        );

        return ApiResponse::success(new DriverResource($driver->load('user')));
    }

    public function offline(Request $request): JsonResponse
    {
        return ApiResponse::success(new DriverResource($this->deliveries->goOffline($this->driver($request))->load('user')));
    }

    public function location(LocationRequest $request): JsonResponse
    {
        $stored = $this->deliveries->recordLocations($this->driver($request), $request->points());

        return ApiResponse::success(['stored' => $stored]);
    }

    public function requests(Request $request): JsonResponse
    {
        $offers = $this->driver($request)->assignments()
            ->pending()
            ->with(['order' => fn ($q) => $q->with(['store', 'kitchen', 'customer', 'items'])])
            ->get();

        return ApiResponse::success(DeliveryRequestResource::collection($offers));
    }

    public function accept(Request $request, int $order): JsonResponse
    {
        $accepted = $this->assignments->accept($this->driver($request), Order::findOrFail($order));

        return ApiResponse::success(new DriverDeliveryResource($this->loadDelivery($accepted)));
    }

    public function decline(Request $request, int $order): JsonResponse
    {
        $this->assignments->decline($this->driver($request), Order::findOrFail($order));

        return ApiResponse::success();
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->driver($request)->orders()
            ->with(['store', 'kitchen', 'customer', 'items'])
            ->latest('id')
            ->paginate($this->perPage($request));

        return ApiResponse::success(DriverDeliveryResource::collection($orders));
    }

    public function show(Request $request, int $order): JsonResponse
    {
        return ApiResponse::success(new DriverDeliveryResource($this->loadDelivery($this->own($request, $order))));
    }

    public function pickup(Request $request, int $order): JsonResponse
    {
        return $this->respond($this->deliveries->pickup($this->driver($request), $this->own($request, $order)));
    }

    public function arrive(Request $request, int $order): JsonResponse
    {
        return $this->respond($this->deliveries->arrive($this->driver($request), $this->own($request, $order)));
    }

    public function complete(Request $request, int $order): JsonResponse
    {
        $pin = $request->validate(['pin' => ['required', 'string', 'regex:/^\d{4}$/']])['pin'];

        return $this->respond($this->deliveries->complete($this->driver($request), $this->own($request, $order), $pin));
    }

    public function fail(Request $request, int $order): JsonResponse
    {
        $reason = $request->validate(['reason_code' => ['required', 'string', 'max:40', 'regex:/^[A-Z_]+$/']])['reason_code'];

        return $this->respond($this->deliveries->fail($this->driver($request), $this->own($request, $order), $reason));
    }

    private function driver(Request $request): Driver
    {
        $driver = $request->user()->driver;
        if (! $driver) {
            throw ApiException::of(ErrorCode::FORBIDDEN);
        }
        if ($driver->status !== DriverStatus::ACTIVE) {
            throw ApiException::of(ErrorCode::ACCOUNT_DISABLED);
        }

        return $driver;
    }

    /** Orders of other drivers are indistinguishable from missing ones (404). */
    private function own(Request $request, int $order): Order
    {
        return $this->driver($request)->orders()->findOrFail($order);
    }

    private function loadDelivery(Order $order): Order
    {
        return $order->load(['store', 'kitchen', 'customer', 'items']);
    }

    private function respond(Order $order): JsonResponse
    {
        return ApiResponse::success(new DriverDeliveryResource($this->loadDelivery($order)));
    }
}

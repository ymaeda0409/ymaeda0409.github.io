<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\QuoteRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderTrackingResource;
use App\Http\Resources\QuoteResource;
use App\Models\Order;
use App\Services\Order\OrderPricingService;
use App\Services\Order\OrderService;
use App\Services\Order\OrderStatusService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * Server-side totals for the cart (the app shows these before placing the order).
     */
    public function quote(QuoteRequest $request, OrderPricingService $pricing): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['delivery_address_id'])) {
            $address = $request->user('sanctum')?->addresses()->findOrFail($data['delivery_address_id']);
            abort_if($address === null, 401);
            [$lat, $lng] = [$address->latitude, $address->longitude];
        } else {
            [$lat, $lng] = [(float) $data['latitude'], (float) $data['longitude']];
        }

        $delivery = $this->orders->deliveryFor($data['store_id'], $lat, $lng);

        return ApiResponse::success(new QuoteResource($pricing->quote($delivery, $data['items'])));
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place($request->user(), $request->validated());

        return ApiResponse::created(new OrderResource($this->loadDetail($order)));
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with(['store', 'items'])
            ->latest('id')
            ->paginate($this->perPage($request));

        return ApiResponse::success(OrderResource::collection($orders));
    }

    public function show(Request $request, int $order): JsonResponse
    {
        return ApiResponse::success(new OrderResource($this->loadDetail($this->own($request, $order))));
    }

    public function tracking(Request $request, int $order): JsonResponse
    {
        $tracked = $this->own($request, $order)->load(['store', 'kitchen', 'driver.user', 'statusHistories']);

        return ApiResponse::success(new OrderTrackingResource($tracked));
    }

    public function cancel(Request $request, int $order, OrderStatusService $statuses): JsonResponse
    {
        $cancelled = $statuses->cancelByCustomer($this->own($request, $order), $request->user());

        return ApiResponse::success(new OrderResource($this->loadDetail($cancelled)));
    }

    /**
     * Other customers' orders are indistinguishable from missing ones (404).
     */
    private function own(Request $request, int $id): Order
    {
        return Order::query()->where('customer_id', $request->user()->id)->findOrFail($id);
    }

    private function loadDetail(Order $order): Order
    {
        return $order->load(['store', 'items.options', 'statusHistories']);
    }
}

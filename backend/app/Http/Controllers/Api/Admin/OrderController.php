<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Orders for back-office staff, including the kitchen board actions.
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderStatusService $statuses) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $request->validate([
            'status' => ['sometimes', 'array'],
            'status.*' => [Rule::enum(OrderStatus::class)],
            'board' => ['sometimes', Rule::in(['kitchen'])],
            'store_id' => ['sometimes', 'integer'],
        ]);

        $kitchenBoard = $request->query('board') === 'kitchen';
        $statuses = $kitchenBoard
            ? array_map(fn (OrderStatus $s) => $s->value, OrderStatus::kitchenBoard())
            : $request->query('status');

        $orders = Order::query()
            ->visibleTo($request->user())
            ->when($statuses, fn ($q) => $q->whereIn('status', (array) $statuses))
            ->when($request->integer('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->with($this->relations())
            // Kitchen works first-in-first-out; history lists newest first.
            ->orderBy('id', $kitchenBoard ? 'asc' : 'desc')
            ->paginate($this->perPage($request, $kitchenBoard ? 100 : 20));

        return ApiResponse::success(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return ApiResponse::success(new OrderResource($order->load([...$this->relations(), 'statusHistories'])));
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        return $this->operate($order, fn () => $this->statuses->accept($order, $request->user()));
    }

    public function startCooking(Request $request, Order $order): JsonResponse
    {
        return $this->operate($order, fn () => $this->statuses->startCooking($order, $request->user()));
    }

    public function ready(Request $request, Order $order): JsonResponse
    {
        return $this->operate($order, fn () => $this->statuses->markReady($order, $request->user()));
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $reason = $request->validate(['reason_code' => ['nullable', 'string', 'max:40', 'regex:/^[A-Z_]+$/']])['reason_code'] ?? null;

        return $this->operate($order, fn () => $this->statuses->cancelByStore($order, $request->user(), $reason));
    }

    private function operate(Order $order, callable $action): JsonResponse
    {
        Gate::authorize('operate', $order);
        $updated = $action();

        return ApiResponse::success(new OrderResource($updated->load([...$this->relations(), 'statusHistories'])));
    }

    private function relations(): array
    {
        return [
            'customer',
            'items.product' => fn ($q) => $q->withTranslations(),
            'items.options.option' => fn ($q) => $q->withTranslations(),
        ];
    }
}

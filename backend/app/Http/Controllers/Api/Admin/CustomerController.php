<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ErrorCode;
use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Customers are not owned by a franchise. A franchise / store sees a customer only once
 * they have ordered there, and only the orders placed there.
 */
class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, Permission::CUSTOMERS_VIEW);
        $request->validate(['search' => ['sometimes', 'nullable', 'string', 'max:100']]);
        $viewer = $request->user();

        $customers = $this->scope($viewer)
            ->when($request->query('search'), function (Builder $q, string $search) {
                $like = '%'.mb_strtolower($search).'%';
                // Local format "0991…" matches the stored "+265991…".
                $digits = ltrim(preg_replace('/\D/', '', $search), '0');
                $q->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->when($digits !== '', fn ($w) => $w->orWhere('phone', 'like', "%{$digits}%")));
            })
            ->withCount(['orders' => fn ($q) => $q->visibleTo($viewer)])
            ->withSum(['orders as total_spent' => fn ($q) => $q->visibleTo($viewer)->where('status', OrderStatus::DELIVERED)], 'total')
            ->withMax(['orders as last_order_at' => fn ($q) => $q->visibleTo($viewer)], 'ordered_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        $customers->getCollection()->transform(fn (User $u) => $this->present($u));

        return ApiResponse::success($customers->items(), meta: ['pagination' => [
            'current_page' => $customers->currentPage(),
            'per_page' => $customers->perPage(),
            'total' => $customers->total(),
            'last_page' => $customers->lastPage(),
        ]]);
    }

    public function show(Request $request, int $customer): JsonResponse
    {
        $this->requirePermission($request, Permission::CUSTOMERS_VIEW);
        $viewer = $request->user();

        $user = $this->scope($viewer)
            ->withCount(['orders' => fn ($q) => $q->visibleTo($viewer)])
            ->withSum(['orders as total_spent' => fn ($q) => $q->visibleTo($viewer)->where('status', OrderStatus::DELIVERED)], 'total')
            ->withMax(['orders as last_order_at' => fn ($q) => $q->visibleTo($viewer)], 'ordered_at')
            ->find($customer) ?? throw ApiException::of(ErrorCode::RESOURCE_NOT_FOUND);

        $orders = Order::query()->visibleTo($viewer)->where('customer_id', $user->id)
            ->latest('id')->limit(20)->get();

        return ApiResponse::success($this->present($user) + [
            'recent_orders' => OrderResource::collection($orders)->resolve($request),
        ]);
    }

    private function scope(User $viewer): Builder
    {
        $query = User::query()->where('role', UserRole::CUSTOMER);

        return $viewer->role->tenantLevel() === null
            ? $query
            : $query->whereHas('orders', fn ($q) => $q->visibleTo($viewer));
    }

    private function present(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'phone' => $u->phone,
            'preferred_language' => $u->preferred_language,
            'is_active' => $u->is_active,
            'orders_count' => (int) $u->orders_count,
            'total_spent' => (int) ($u->total_spent ?? 0),
            'last_order_at' => $u->last_order_at ? Carbon::parse($u->last_order_at)->toIso8601String() : null,
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}

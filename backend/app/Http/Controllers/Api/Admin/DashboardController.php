<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Store;
use App\Services\Admin\SalesService;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live overview for the signed-in staff member's scope (platform, franchise or store).
 * Money figures are only included for roles with sales.view.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, SalesService $sales): JsonResponse
    {
        $this->requirePermission($request, Permission::ORDERS_VIEW);
        $user = $request->user();
        $timezone = $sales->timezone($user);
        $today = CarbonImmutable::now($timezone)->startOfDay();
        [$start, $end] = $sales->bounds($today, $today, $timezone);

        $active = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::activeStatuses());
        $byStatus = Order::query()->visibleTo($user)->whereIn('status', $active)
            ->toBase()->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status');

        $stores = Store::query()->visibleTo($user)->where('is_active', true)->get();

        $data = [
            'date' => $today->toDateString(),
            'timezone' => $timezone,
            'orders_today' => Order::query()->visibleTo($user)->where('ordered_at', '>=', $start)->where('ordered_at', '<', $end)->count(),
            'active_orders' => collect($active)->mapWithKeys(fn ($s) => [$s => (int) ($byStatus[$s] ?? 0)])->all(),
            'awaiting_payment' => Order::query()->visibleTo($user)
                ->where('status', OrderStatus::NEW)
                ->where('payment_method', '!=', PaymentMethod::CASH)
                ->where('payment_status', '!=', PaymentStatus::PAID)
                ->count(),
            'drivers_online' => Driver::query()->visibleTo($user)->where('is_online', true)->count(),
            'stores_total' => $stores->count(),
            'stores_open' => $stores->filter(fn (Store $s) => $s->isOpenNow())->count(),
        ];

        if ($user->hasPermission(Permission::SALES_VIEW)) {
            $weekFrom = $today->subDays(6);
            $data['currency'] = $user->organization?->currency ?? config('bento.default_currency');
            $data['sales_today'] = $sales->summary($user, $start, $end);
            $week = $sales->report($user, $weekFrom, $today, 'day');
            $data['last_7_days'] = array_map(fn ($r) => ['date' => $r['key'], 'orders' => $r['orders'], 'gross_sales' => $r['gross_sales']], $week['rows']);
            $data['top_products'] = array_slice($sales->report($user, $weekFrom, $today, 'product')['rows'], 0, 5);
        }

        return ApiResponse::success($data);
    }
}

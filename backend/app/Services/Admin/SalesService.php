<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Franchise;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Sales figures for the back office, always inside the viewer's tenant scope.
 *
 * "Sales" = orders that were DELIVERED and PAID, grouped by the local date they were
 * ordered (organization timezone). Amounts are integer minor units.
 */
class SalesService
{
    public const GROUPS = ['day', 'store', 'franchise', 'product', 'payment_method'];

    public const MAX_DAYS = 366;

    /**
     * @param  array{franchise_id?: int|null, store_id?: int|null}  $filters
     */
    public function report(User $user, CarbonImmutable $from, CarbonImmutable $to, string $groupBy, array $filters = []): array
    {
        $timezone = $this->timezone($user);
        [$start, $end] = $this->bounds($from, $to, $timezone);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'timezone' => $timezone,
            'currency' => $user->organization?->currency ?? config('bento.default_currency'),
            'group_by' => $groupBy,
            'summary' => $this->summary($user, $start, $end, $filters),
            'rows' => match ($groupBy) {
                'day' => $this->byDay($user, $from, $to, $timezone, $filters),
                'store' => $this->byStore($user, $start, $end, $filters),
                'franchise' => $this->byFranchise($user, $start, $end, $filters),
                'product' => $this->byProduct($user, $start, $end, $filters),
                'payment_method' => $this->byPaymentMethod($user, $start, $end, $filters),
            },
        ];
    }

    public function timezone(User $user): string
    {
        return $user->organization?->timezone ?? config('app.timezone');
    }

    /**
     * Local calendar dates → UTC [start, end) instants.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function bounds(CarbonImmutable $from, CarbonImmutable $to, string $timezone): array
    {
        return [
            CarbonImmutable::parse($from->toDateString(), $timezone)->startOfDay()->utc(),
            CarbonImmutable::parse($to->toDateString(), $timezone)->addDay()->startOfDay()->utc(),
        ];
    }

    public function summary(User $user, CarbonImmutable $start, CarbonImmutable $end, array $filters = []): array
    {
        $totals = $this->totals($this->sales($user, $start, $end, $filters))->first();
        $row = $this->row($totals);

        $placed = $this->scoped($user, $filters)->where('ordered_at', '>=', $start)->where('ordered_at', '<', $end);

        return $row + [
            'cancelled' => (clone $placed)->where('status', OrderStatus::CANCELLED)->count(),
            'failed_deliveries' => (clone $placed)->where('status', OrderStatus::FAILED_DELIVERY)->count(),
        ];
    }

    /**
     * Completed sales in the viewer's scope.
     */
    public function sales(User $user, CarbonImmutable $start, CarbonImmutable $end, array $filters = []): Builder
    {
        return $this->scoped($user, $filters)
            ->where('status', OrderStatus::DELIVERED)
            ->where('payment_status', PaymentStatus::PAID)
            ->where('ordered_at', '>=', $start)
            ->where('ordered_at', '<', $end);
    }

    private function scoped(User $user, array $filters): Builder
    {
        return Order::query()
            ->visibleTo($user)
            ->when($filters['franchise_id'] ?? null, fn ($q, $id) => $q->where('franchise_id', $id))
            ->when($filters['store_id'] ?? null, fn ($q, $id) => $q->where('store_id', $id));
    }

    private function totals(Builder $query): QueryBuilder
    {
        return $query->toBase()->selectRaw(
            'COUNT(*) AS orders, COALESCE(SUM(total), 0) AS gross_sales, COALESCE(SUM(subtotal), 0) AS subtotal, '
            .'COALESCE(SUM(delivery_fee), 0) AS delivery_fees, COALESCE(SUM(service_fee), 0) AS service_fees, '
            .'COALESCE(SUM(discount), 0) AS discounts'
        );
    }

    private function row(?object $totals, array $extra = []): array
    {
        $orders = (int) ($totals->orders ?? 0);
        $gross = (int) ($totals->gross_sales ?? 0);

        return $extra + [
            'orders' => $orders,
            'gross_sales' => $gross,
            'subtotal' => (int) ($totals->subtotal ?? 0),
            'delivery_fees' => (int) ($totals->delivery_fees ?? 0),
            'service_fees' => (int) ($totals->service_fees ?? 0),
            'discounts' => (int) ($totals->discounts ?? 0),
            'average_order_value' => $orders > 0 ? intdiv($gross, $orders) : 0,
        ];
    }

    /**
     * Every day in the range appears (zero rows included) so charts have no gaps.
     * Bucketing happens in PHP to stay database-agnostic (SQLite / PostgreSQL / MySQL).
     */
    private function byDay(User $user, CarbonImmutable $from, CarbonImmutable $to, string $timezone, array $filters): array
    {
        [$start, $end] = $this->bounds($from, $to, $timezone);
        $days = [];
        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            $days[$date->toDateString()] = (object) [
                'orders' => 0, 'gross_sales' => 0, 'subtotal' => 0, 'delivery_fees' => 0, 'service_fees' => 0, 'discounts' => 0,
            ];
        }

        $this->sales($user, $start, $end, $filters)
            ->select(['id', 'ordered_at', 'total', 'subtotal', 'delivery_fee', 'service_fee', 'discount'])
            ->lazyById(500)
            ->each(function (Order $order) use (&$days, $timezone) {
                $day = $order->ordered_at->setTimezone($timezone)->toDateString();
                if (! isset($days[$day])) {
                    return;
                }
                $bucket = $days[$day];
                $bucket->orders++;
                $bucket->gross_sales += $order->total;
                $bucket->subtotal += $order->subtotal;
                $bucket->delivery_fees += $order->delivery_fee;
                $bucket->service_fees += $order->service_fee;
                $bucket->discounts += $order->discount;
            });

        return collect($days)->map(fn ($totals, $day) => $this->row($totals, ['key' => $day, 'label' => $day]))->values()->all();
    }

    private function byStore(User $user, CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $rows = $this->totals($this->sales($user, $start, $end, $filters))->addSelect('store_id')->groupBy('store_id')->get();
        $names = Store::query()->whereIn('id', $rows->pluck('store_id'))->pluck('name', 'id');

        return $rows
            ->map(fn ($r) => $this->row($r, ['key' => (int) $r->store_id, 'label' => $names[$r->store_id] ?? (string) $r->store_id]))
            ->sortByDesc('gross_sales')->values()->all();
    }

    /**
     * Includes the franchise fee owed to the organization: subtotal × commission_rate
     * (delivery and service fees are excluded from the base).
     */
    private function byFranchise(User $user, CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $rows = $this->totals($this->sales($user, $start, $end, $filters))->addSelect('franchise_id')->groupBy('franchise_id')->get();
        $franchises = Franchise::query()->whereIn('id', $rows->pluck('franchise_id'))->get()->keyBy('id');

        return $rows->map(function ($r) use ($franchises) {
            $franchise = $franchises[$r->franchise_id] ?? null;
            $rate = (float) ($franchise?->commission_rate ?? 0);
            $row = $this->row($r, ['key' => (int) $r->franchise_id, 'label' => $franchise?->name ?? (string) $r->franchise_id]);

            return $row + ['commission_rate' => $rate, 'commission' => (int) round($row['subtotal'] * $rate / 100)];
        })->sortByDesc('gross_sales')->values()->all();
    }

    /**
     * Product names follow the viewer's language (current catalog translation, else the
     * name captured on the order).
     */
    private function byProduct(User $user, CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $orderIds = $this->sales($user, $start, $end, $filters)->select('id');

        $rows = OrderItem::query()
            ->toBase()
            ->whereIn('order_id', $orderIds)
            ->selectRaw('product_id, MAX(product_name_snapshot) AS snapshot, SUM(quantity) AS quantity, '
                .'SUM(total) AS sales, COUNT(DISTINCT order_id) AS orders')
            ->groupBy('product_id')
            ->get();

        $products = Product::query()->whereIn('id', $rows->pluck('product_id')->filter())->withTranslations()->get()->keyBy('id');

        return $rows->map(fn ($r) => [
            'key' => $r->product_id === null ? null : (int) $r->product_id,
            'label' => $products->get($r->product_id)?->translate('name') ?? $r->snapshot,
            'sku' => $products->get($r->product_id)?->sku,
            'quantity' => (int) $r->quantity,
            'orders' => (int) $r->orders,
            'sales' => (int) $r->sales,
        ])->sortByDesc('sales')->values()->all();
    }

    private function byPaymentMethod(User $user, CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        return $this->totals($this->sales($user, $start, $end, $filters))
            ->addSelect('payment_method')->groupBy('payment_method')->get()
            ->map(fn ($r) => $this->row($r, ['key' => $r->payment_method, 'label' => $r->payment_method]))
            ->sortByDesc('gross_sales')->values()->all();
    }
}

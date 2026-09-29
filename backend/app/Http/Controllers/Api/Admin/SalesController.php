<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Store;
use App\Rules\VisibleTo;
use App\Services\Admin\SalesService;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /admin/sales?from=YYYY-MM-DD&to=YYYY-MM-DD&group_by=day|store|franchise|product|payment_method
 *   [&franchise_id=][&store_id=][&format=csv]
 */
class SalesController extends Controller
{
    public function __construct(private readonly SalesService $sales) {}

    public function index(Request $request): JsonResponse|StreamedResponse
    {
        $this->requirePermission($request, Permission::SALES_VIEW);
        $user = $request->user();
        $today = CarbonImmutable::now($this->sales->timezone($user))->toDateString();

        $data = $request->validate([
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'group_by' => ['sometimes', Rule::in(SalesService::GROUPS)],
            'franchise_id' => ['sometimes', 'integer', new VisibleTo(Franchise::class, $user)],
            'store_id' => ['sometimes', 'integer', new VisibleTo(Store::class, $user)],
            'format' => ['sometimes', Rule::in(['json', 'csv'])],
        ]);
        $from = CarbonImmutable::parse($data['from'] ?? CarbonImmutable::parse($today)->subDays(6)->toDateString());
        $maxTo = $from->addDays(SalesService::MAX_DAYS - 1)->toDateString();
        $to = CarbonImmutable::parse($request->validate([
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:'.$from->toDateString(), 'before_or_equal:'.$maxTo],
        ])['to'] ?? max($today, $from->toDateString()));

        $report = $this->sales->report($user, $from, $to, $data['group_by'] ?? 'day', $data);

        return ($data['format'] ?? 'json') === 'csv'
            ? $this->csv($report)
            : ApiResponse::success($report);
    }

    /**
     * Column names are stable codes (spreadsheets / imports); amounts are in major units.
     */
    private function csv(array $report): StreamedResponse
    {
        $exponent = (int) config("bento.currencies.{$report['currency']}.exponent", 2);
        $moneyColumns = ['gross_sales', 'subtotal', 'delivery_fees', 'service_fees', 'discounts', 'average_order_value', 'commission', 'sales'];
        $columns = array_keys($report['rows'][0] ?? ['key' => null, 'label' => null]);
        $filename = sprintf('sales_%s_%s_%s.csv', $report['group_by'], $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report, $columns, $moneyColumns, $exponent) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so spreadsheet apps show non-Latin names correctly
            fputcsv($out, [...$columns, 'currency'], escape: '');
            foreach ($report['rows'] as $row) {
                $line = [];
                foreach ($columns as $column) {
                    $value = $row[$column] ?? null;
                    $line[] = in_array($column, $moneyColumns, true) && $value !== null
                        ? number_format($value / (10 ** $exponent), $exponent, '.', '')
                        : $value;
                }
                fputcsv($out, [...$line, $report['currency']], escape: '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

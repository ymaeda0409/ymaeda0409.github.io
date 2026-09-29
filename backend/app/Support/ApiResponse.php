<?php

namespace App\Support;

use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * Builds the unified response envelope:
 *   { success, data, meta: { locale, pagination? } }  /  { success: false, error: { code, message, fields } }
 */
class ApiResponse
{
    // Raw UTF-8 keeps non-Latin text (e.g. Japanese) at half the size of \u escapes on slow networks.
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public static function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $meta['pagination'] = self::pagination($data->resource);
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        } elseif ($data instanceof Collection) {
            $data = $data
                ->map(fn ($item) => $item instanceof JsonResource ? $item->resolve(request()) : $item)
                ->values()
                ->all();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => ['locale' => App::getLocale()] + $meta,
        ], $status, [], self::JSON_FLAGS);
    }

    public static function created(mixed $data = null): JsonResponse
    {
        return self::success($data, 201);
    }

    /**
     * @param  array<string, list<string>>|null  $fields
     */
    public static function error(ErrorCode $code, ?array $fields = null, array $replace = [], array $headers = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code->value,
                'message' => $code->message($replace),
                'fields' => $fields,
            ],
        ], $code->httpStatus(), $headers, self::JSON_FLAGS);
    }

    private static function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}

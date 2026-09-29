<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\AvailableStoresRequest;
use App\Http\Resources\AvailableStoreResource;
use App\Http\Resources\StoreResource;
use App\Services\Catalog\CatalogService;
use App\Services\Delivery\StoreLocatorService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function available(AvailableStoresRequest $request, StoreLocatorService $locator): JsonResponse
    {
        $stores = $locator->findAvailableStores(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );

        return ApiResponse::success(AvailableStoreResource::collection($stores));
    }

    public function show(int $store, CatalogService $catalog): JsonResponse
    {
        return ApiResponse::success(new StoreResource($catalog->findPublicStore($store)));
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Resources\Admin\StoreProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Services\Admin\StoreProductService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreProductController extends Controller
{
    public function index(Request $request, Store $store): JsonResponse
    {
        Gate::authorize('manageProducts', $store);

        $storeProducts = $store->storeProducts()
            ->with(['product' => fn ($q) => $q->withTranslations()])
            ->whereHas('product')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($request, 50));

        return ApiResponse::success(StoreProductResource::collection($storeProducts));
    }

    public function update(StoreProductRequest $request, Store $store, int $product, StoreProductService $service): JsonResponse
    {
        $product = Product::query()
            ->where('organization_id', $store->organization_id)
            ->withTranslations()
            ->findOrFail($product);

        return ApiResponse::success(new StoreProductResource($service->upsert($store, $product, $request->validated())));
    }
}

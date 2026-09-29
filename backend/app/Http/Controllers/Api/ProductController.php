<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CatalogRequest;
use App\Http\Resources\ProductResource;
use App\Services\Catalog\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(CatalogRequest $request): JsonResponse
    {
        $store = $this->catalog->findPublicStore((int) $request->validated('store_id'));
        $products = $this->catalog->productsForStore(
            $store,
            $request->validated('category_id') ? (int) $request->validated('category_id') : null,
            $request->boolean('featured'),
        );

        return ApiResponse::success(ProductResource::forStore($products, $store));
    }

    public function show(CatalogRequest $request, int $product): JsonResponse
    {
        $store = $this->catalog->findPublicStore((int) $request->validated('store_id'));

        return ApiResponse::success(new ProductResource($this->catalog->productForStore($store, $product), $store));
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Services\Admin\ProductService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);

        $search = trim((string) $request->query('search'));
        $products = Product::query()
            ->visibleTo($request->user())
            ->with('translations')
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('sku', 'like', "%{$search}%")
                ->orWhereHas('translations', fn ($t) => $t->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']))))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return ApiResponse::success(ProductResource::collection($products));
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->organizationId(), $request->validated());

        return ApiResponse::created(new ProductResource($this->loadForEditor($product)));
    }

    public function show(Product $product): JsonResponse
    {
        Gate::authorize('view', $product);

        return ApiResponse::success(new ProductResource($this->loadForEditor($product)));
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->products->update($product, $request->validated());

        return ApiResponse::success(new ProductResource($this->loadForEditor($product)));
    }

    public function destroy(Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);
        $this->products->delete($product);

        return ApiResponse::success();
    }

    private function loadForEditor(Product $product): Product
    {
        return $product->load(['translations', 'optionGroups.translations', 'optionGroups.options.translations']);
    }
}

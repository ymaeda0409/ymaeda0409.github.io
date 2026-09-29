<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Models\ProductCategory;
use App\Services\Admin\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->visibleTo($request->user())
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($request, 50));

        return ApiResponse::success(CategoryResource::collection($categories));
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->organizationId(), $request->validated());

        return ApiResponse::created(new CategoryResource($category));
    }

    public function show(ProductCategory $category): JsonResponse
    {
        Gate::authorize('view', $category);

        return ApiResponse::success(new CategoryResource($category));
    }

    public function update(CategoryRequest $request, ProductCategory $category): JsonResponse
    {

        return ApiResponse::success(new CategoryResource($this->categories->update($category, $request->validated())));
    }

    public function destroy(ProductCategory $category): JsonResponse
    {
        Gate::authorize('delete', $category);
        $this->categories->delete($category);

        return ApiResponse::success();
    }
}

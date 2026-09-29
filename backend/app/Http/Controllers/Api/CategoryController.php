<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CatalogRequest;
use App\Http\Resources\CategoryResource;
use App\Services\Catalog\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(CatalogRequest $request, CatalogService $catalog): JsonResponse
    {
        $store = $catalog->findPublicStore((int) $request->validated('store_id'));

        return ApiResponse::success(CategoryResource::collection($catalog->categoriesForStore($store)));
    }
}

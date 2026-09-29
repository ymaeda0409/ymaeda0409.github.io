<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Http\Resources\Admin\StoreResource;
use App\Models\Store;
use App\Services\Admin\StoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreController extends Controller
{
    public function __construct(private readonly StoreService $stores) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Store::class);

        $stores = Store::query()
            ->visibleTo($request->user())
            ->with('translations')
            ->when($request->integer('franchise_id'), fn ($q, $id) => $q->where('franchise_id', $id))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::success(StoreResource::collection($stores));
    }

    public function store(StoreRequest $request): JsonResponse
    {

        return ApiResponse::created(new StoreResource($this->stores->create($request->validated())));
    }

    public function show(Store $store): JsonResponse
    {
        Gate::authorize('view', $store);

        return ApiResponse::success(new StoreResource($store));
    }

    public function update(StoreRequest $request, Store $store): JsonResponse
    {

        return ApiResponse::success(new StoreResource($this->stores->update($store, $request->validated())));
    }

    public function destroy(Store $store): JsonResponse
    {
        Gate::authorize('delete', $store);
        $this->stores->delete($store);

        return ApiResponse::success();
    }
}

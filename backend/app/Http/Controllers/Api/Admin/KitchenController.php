<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KitchenRequest;
use App\Http\Resources\Admin\KitchenResource;
use App\Models\Kitchen;
use App\Models\Store;
use App\Services\Admin\KitchenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KitchenController extends Controller
{
    public function __construct(private readonly KitchenService $kitchens) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Kitchen::class);

        $kitchens = Kitchen::query()
            ->visibleTo($request->user())
            ->when($request->integer('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return ApiResponse::success(KitchenResource::collection($kitchens));
    }

    public function store(KitchenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $store = Store::findOrFail($data['store_id']);

        return ApiResponse::created(new KitchenResource($this->kitchens->create($store, $data)));
    }

    public function show(Kitchen $kitchen): JsonResponse
    {
        Gate::authorize('view', $kitchen);

        return ApiResponse::success(new KitchenResource($kitchen));
    }

    public function update(KitchenRequest $request, Kitchen $kitchen): JsonResponse
    {

        return ApiResponse::success(new KitchenResource($this->kitchens->update($kitchen, $request->validated())));
    }

    public function destroy(Kitchen $kitchen): JsonResponse
    {
        Gate::authorize('delete', $kitchen);
        $this->kitchens->delete($kitchen);

        return ApiResponse::success();
    }
}

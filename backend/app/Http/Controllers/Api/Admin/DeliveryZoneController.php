<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryZoneRequest;
use App\Http\Resources\Admin\DeliveryZoneResource;
use App\Models\DeliveryZone;
use App\Models\Store;
use App\Services\Admin\DeliveryZoneService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeliveryZoneController extends Controller
{
    public function __construct(private readonly DeliveryZoneService $zones) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DeliveryZone::class);

        $zones = DeliveryZone::query()
            ->visibleTo($request->user())
            ->when($request->integer('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return ApiResponse::success(DeliveryZoneResource::collection($zones));
    }

    public function store(DeliveryZoneRequest $request): JsonResponse
    {
        $data = $request->validated();
        $store = Store::findOrFail($data['store_id']);

        return ApiResponse::created(new DeliveryZoneResource($this->zones->create($store, $data)));
    }

    public function show(DeliveryZone $deliveryZone): JsonResponse
    {
        Gate::authorize('view', $deliveryZone);

        return ApiResponse::success(new DeliveryZoneResource($deliveryZone));
    }

    public function update(DeliveryZoneRequest $request, DeliveryZone $deliveryZone): JsonResponse
    {

        return ApiResponse::success(new DeliveryZoneResource($this->zones->update($deliveryZone, $request->validated())));
    }

    public function destroy(DeliveryZone $deliveryZone): JsonResponse
    {
        Gate::authorize('delete', $deliveryZone);
        $this->zones->delete($deliveryZone);

        return ApiResponse::success();
    }
}

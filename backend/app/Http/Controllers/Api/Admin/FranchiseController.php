<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FranchiseRequest;
use App\Http\Resources\Admin\FranchiseResource;
use App\Models\Franchise;
use App\Services\Admin\FranchiseService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FranchiseController extends Controller
{
    public function __construct(private readonly FranchiseService $franchises) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Franchise::class);

        $franchises = Franchise::query()
            ->visibleTo($request->user())
            ->withCount('stores')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::success(FranchiseResource::collection($franchises));
    }

    public function store(FranchiseRequest $request): JsonResponse
    {

        return ApiResponse::created(new FranchiseResource($this->franchises->create($request->validated())));
    }

    public function show(Franchise $franchise): JsonResponse
    {
        Gate::authorize('view', $franchise);

        return ApiResponse::success(new FranchiseResource($franchise->loadCount('stores')));
    }

    public function update(FranchiseRequest $request, Franchise $franchise): JsonResponse
    {

        return ApiResponse::success(new FranchiseResource($this->franchises->update($franchise, $request->validated())));
    }

    public function destroy(Franchise $franchise): JsonResponse
    {
        Gate::authorize('delete', $franchise);
        $this->franchises->delete($franchise);

        return ApiResponse::success();
    }
}

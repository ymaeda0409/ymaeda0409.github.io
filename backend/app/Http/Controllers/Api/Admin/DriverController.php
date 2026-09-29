<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Services\Admin\DriverService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DriverController extends Controller
{
    public function __construct(private readonly DriverService $drivers) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Driver::class);

        $drivers = Driver::query()
            ->visibleTo($request->user())
            ->with('user')
            ->when($request->has('online'), fn ($q) => $q->where('is_online', $request->boolean('online')))
            ->orderByDesc('is_online')
            ->orderBy('id')
            ->paginate($this->perPage($request, 50));

        return ApiResponse::success(DriverResource::collection($drivers));
    }

    public function store(DriverRequest $request): JsonResponse
    {
        $driver = $this->drivers->create($request->franchiseId(), $request->validated());

        return ApiResponse::created(new DriverResource($driver->load('user')));
    }

    public function show(Driver $driver): JsonResponse
    {
        Gate::authorize('view', $driver);

        return ApiResponse::success(new DriverResource($driver->load('user')));
    }

    public function update(DriverRequest $request, Driver $driver): JsonResponse
    {
        return ApiResponse::success(new DriverResource($this->drivers->update($driver, $request->validated())->load('user')));
    }
}

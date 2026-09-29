<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\UserAddress;
use App\Services\AddressService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get();

        return ApiResponse::success(AddressResource::collection($addresses));
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $address = $this->addresses->create($request->user(), $request->validated());

        return ApiResponse::created(new AddressResource($address));
    }

    public function update(AddressRequest $request, UserAddress $address): JsonResponse
    {
        Gate::authorize('manage', $address);

        return ApiResponse::success(new AddressResource($this->addresses->update($address, $request->validated())));
    }

    public function destroy(UserAddress $address): JsonResponse
    {
        Gate::authorize('manage', $address);
        $this->addresses->delete($address);

        return ApiResponse::success();
    }
}

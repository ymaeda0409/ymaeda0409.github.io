<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->visibleTo($request->user())
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', $action))
            ->when($request->query('target_type'), fn ($q, $type) => $q->where('target_type', $type))
            ->latest('id')
            ->paginate($this->perPage($request, 50));

        return ApiResponse::success(AuditLogResource::collection($logs));
    }
}

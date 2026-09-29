<?php

namespace App\Http\Controllers;

use App\Enums\ErrorCode;
use App\Enums\Permission;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function perPage(Request $request, int $default = 20): int
    {
        return min(max($request->integer('per_page', $default), 1), 100);
    }

    /**
     * For endpoints that are not tied to a single model (reports, settings, …).
     */
    protected function requirePermission(Request $request, Permission $permission): void
    {
        if (! $request->user()->hasPermission($permission)) {
            throw ApiException::of(ErrorCode::FORBIDDEN);
        }
    }
}

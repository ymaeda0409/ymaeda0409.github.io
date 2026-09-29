<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse route-group guard: `role:CUSTOMER`, `role:staff` (any back-office role).
 * Fine-grained checks happen in policies via permissions.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw ApiException::of(ErrorCode::UNAUTHENTICATED);
        }

        if (! $user->is_active) {
            throw ApiException::of(ErrorCode::ACCOUNT_DISABLED);
        }

        $allowed = collect($roles)->contains(
            fn (string $role) => $role === 'staff' ? $user->isStaff() : $user->role === UserRole::from($role),
        );

        if (! $allowed) {
            throw ApiException::of(ErrorCode::FORBIDDEN);
        }

        return $next($request);
    }
}

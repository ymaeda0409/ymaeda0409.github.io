<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ErrorCode;
use App\Enums\Permission;
use App\Enums\TenantLevel;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Franchise;
use App\Models\Store;
use App\Models\User;
use App\Rules\VisibleTo;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Back-office accounts. Each role manages only lower roles (UserRole::manageableRoles)
 * inside its own tenant: a franchise admin creates store managers / kitchen staff for
 * their franchise's stores, a store manager creates kitchen staff for their store.
 */
class StaffController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, Permission::STAFF_MANAGE);
        $request->validate(['role' => ['sometimes', Rule::enum(UserRole::class)]]);

        $staff = $this->scope($request->user())
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->orderBy('name')
            ->paginate($this->perPage($request, 50));

        return ApiResponse::success(UserResource::collection($staff));
    }

    public function store(Request $request): JsonResponse
    {
        $this->requirePermission($request, Permission::STAFF_MANAGE);
        $data = $request->validate($this->rules($request, null));

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::create($this->attributes($request->user(), $data));
            $this->audit->log('staff.created', $user, null, $user->only(['name', 'email', 'role', 'franchise_id', 'store_id']));

            return $user;
        });

        return ApiResponse::created(new UserResource($user));
    }

    public function show(Request $request, int $staff): JsonResponse
    {
        $this->requirePermission($request, Permission::STAFF_MANAGE);

        return ApiResponse::success(new UserResource($this->find($request->user(), $staff)));
    }

    public function update(Request $request, int $staff): JsonResponse
    {
        $this->requirePermission($request, Permission::STAFF_MANAGE);
        $user = $this->find($request->user(), $staff);
        $data = $request->validate($this->rules($request, $user));

        DB::transaction(function () use ($request, $user, $data) {
            $user->fill($this->attributes($request->user(), $data + ['role' => $user->role->value], $user));
            $this->audit->logChanges('staff.updated', $user);
            $user->save();

            // A deactivated account is signed out everywhere immediately.
            if (! $user->is_active) {
                $user->tokens()->delete();
            }
        });

        return ApiResponse::success(new UserResource($user->refresh()));
    }

    private function scope(User $viewer): Builder
    {
        $roles = array_map(fn (UserRole $r) => $r->value, $viewer->role->manageableRoles());
        $query = User::query()->whereIn('role', $roles)->whereKeyNot($viewer->id);

        return match ($viewer->role->tenantLevel()) {
            null => $query,
            TenantLevel::FRANCHISE => $query->where('franchise_id', $viewer->franchise_id),
            TenantLevel::STORE => $query->where('store_id', $viewer->store_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function find(User $viewer, int $id): User
    {
        return $this->scope($viewer)->find($id) ?? throw ApiException::of(ErrorCode::RESOURCE_NOT_FOUND);
    }

    private function rules(Request $request, ?User $user): array
    {
        $viewer = $request->user();
        $creating = $user === null;
        $required = $creating ? 'required' : 'sometimes';
        $role = UserRole::tryFrom((string) $request->input('role', $user?->role->value));

        return [
            'role' => [$required, Rule::in(array_map(fn (UserRole $r) => $r->value, $viewer->role->manageableRoles()))],
            'name' => [$required, 'string', 'max:150'],
            'email' => [$required, 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$creating ? 'required' : 'sometimes', 'string', 'min:8', 'max:100'],
            'preferred_language' => ['sometimes', 'string', Rule::exists('languages', 'code')->where('is_active', true)],
            'is_active' => ['sometimes', 'boolean'],
            'franchise_id' => $role === UserRole::FRANCHISE_ADMIN
                ? [$creating || $request->has('role') ? 'required' : 'sometimes', 'integer', new VisibleTo(Franchise::class, $viewer)]
                : ['prohibited'],
            'store_id' => in_array($role, [UserRole::STORE_MANAGER, UserRole::KITCHEN_STAFF], true)
                ? [$creating || $request->has('role') ? 'required' : 'sometimes', 'integer', new VisibleTo(Store::class, $viewer)]
                : ['prohibited'],
        ];
    }

    /**
     * Tenant columns are always derived server-side from the chosen franchise / store.
     */
    private function attributes(User $viewer, array $data, ?User $user = null): array
    {
        $attributes = array_intersect_key($data, array_flip(['name', 'email', 'password', 'preferred_language', 'is_active', 'role']));
        $role = UserRole::from($data['role']);
        $organizationId = $user?->organization_id ?? $viewer->organization_id;

        if (isset($data['store_id'])) {
            $store = Store::findOrFail($data['store_id']);
            $attributes += ['organization_id' => $store->organization_id, 'franchise_id' => $store->franchise_id, 'store_id' => $store->id];
        } elseif (isset($data['franchise_id'])) {
            $franchise = Franchise::findOrFail($data['franchise_id']);
            $attributes += ['organization_id' => $franchise->organization_id, 'franchise_id' => $franchise->id, 'store_id' => null];
        } elseif ($role === UserRole::SUPER_ADMIN) {
            $attributes += ['organization_id' => $organizationId, 'franchise_id' => null, 'store_id' => null];
        }

        return $attributes;
    }
}

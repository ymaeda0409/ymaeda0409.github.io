<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ErrorCode;
use App\Enums\Permission;
use App\Enums\SettingScope;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * GET /admin/settings?scope_type=STORE&scope_id=1
 * PUT /admin/settings { scope_type, scope_id, values: { key: value | null } }
 */
class SettingController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(Request $request): JsonResponse
    {
        [$scope, $id] = $this->scope($request);

        return ApiResponse::success([
            'scope_type' => $scope->value,
            'scope_id' => $id,
            'settings' => $this->settings->describe($scope, $id),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): JsonResponse
    {
        [$scope, $id] = $this->scope($request);

        $rules = ['values' => ['required', 'array:'.implode(',', array_keys(SettingsService::DEFINITIONS))]];
        foreach (SettingsService::DEFINITIONS as $key => $definition) {
            $rules["values.{$key}"] = ['nullable', ...$definition['rules']];
        }
        $values = $request->validate($rules)['values'];

        DB::transaction(function () use ($scope, $id, $values, $audit) {
            $before = collect($this->settings->describe($scope, $id))->pluck('value', 'key')->all();
            $this->settings->put($scope, $id, $values);
            $audit->log('settings.updated', null, ['scope' => "{$scope->value}:{$id}"] + $before, $values);
        });

        return ApiResponse::success([
            'scope_type' => $scope->value,
            'scope_id' => $id,
            'settings' => $this->settings->describe($scope, $id),
        ]);
    }

    /**
     * Platform-wide scopes are for platform admins; franchise / store scopes must be
     * inside the user's tenant (other tenants look non-existent).
     *
     * @return array{0: SettingScope, 1: int|null}
     */
    private function scope(Request $request): array
    {
        $this->requirePermission($request, Permission::SETTINGS_MANAGE);
        $data = $request->validate([
            'scope_type' => ['required', Rule::enum(SettingScope::class)],
            'scope_id' => ['nullable', 'integer'],
        ]);
        $user = $request->user();
        $scope = SettingScope::from($data['scope_type']);
        $id = isset($data['scope_id']) ? (int) $data['scope_id'] : null;

        $allowed = match ($scope) {
            SettingScope::GLOBAL => $user->role->tenantLevel() === null && $id === null,
            SettingScope::ORGANIZATION => $user->role->tenantLevel() === null && $id === $user->organization_id,
            SettingScope::FRANCHISE => $this->visible(Franchise::class, $id, $user),
            SettingScope::STORE => $this->visible(Store::class, $id, $user),
        };

        if (! $allowed) {
            throw ApiException::of(in_array($scope, [SettingScope::GLOBAL, SettingScope::ORGANIZATION], true)
                ? ErrorCode::FORBIDDEN
                : ErrorCode::RESOURCE_NOT_FOUND);
        }

        return [$scope, $id];
    }

    private function visible(string $model, ?int $id, User $user): bool
    {
        return $id !== null && $model::query()->visibleTo($user)->whereKey($id)->exists();
    }
}

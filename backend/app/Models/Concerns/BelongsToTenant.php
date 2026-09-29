<?php

namespace App\Models\Concerns;

use App\Enums\TenantLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant data separation (Organization → Franchise → Store).
 *
 * Models declare which of their columns identify each tenant level via tenantColumns().
 * A user pinned to a level sees rows matching the most specific column the model has,
 * never more specific than the user's own level.
 */
trait BelongsToTenant
{
    /**
     * @return array{organization?: string, franchise?: string, store?: string}
     */
    abstract public static function tenantColumns(): array;

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $constraint = self::tenantConstraint($user);

        if ($constraint === true) {
            return $query;
        }

        if ($constraint === false) {
            return $query->whereRaw('1 = 0');
        }

        [$column, $value] = $constraint;

        return $query->where($query->qualifyColumn($column), $value);
    }

    public function isVisibleTo(User $user): bool
    {
        $constraint = self::tenantConstraint($user);

        if (is_bool($constraint)) {
            return $constraint;
        }

        [$column, $value] = $constraint;

        return (int) $this->getAttribute($column) === (int) $value;
    }

    /**
     * @return bool|array{0: string, 1: int|null} true = unrestricted, false = nothing, or [column, value]
     */
    private static function tenantConstraint(User $user): bool|array
    {
        $level = $user->role->tenantLevel();

        if ($level === null) {
            return true;
        }

        $columns = static::tenantColumns();
        $candidates = match ($level) {
            TenantLevel::STORE => ['store' => $user->store_id, 'franchise' => $user->franchise_id, 'organization' => $user->organization_id],
            TenantLevel::FRANCHISE => ['franchise' => $user->franchise_id, 'organization' => $user->organization_id],
            TenantLevel::NONE => [],
        };

        foreach ($candidates as $key => $value) {
            if (isset($columns[$key])) {
                return $value === null ? false : [$columns[$key], $value];
            }
        }

        return false;
    }
}

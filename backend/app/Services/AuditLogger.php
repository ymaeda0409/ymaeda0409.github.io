<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Franchise;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    private const HIDDEN = ['password', 'remember_token', 'code_hash'];

    public function log(string $action, ?Model $target = null, ?array $before = null, ?array $after = null, ?User $actor = null): AuditLog
    {
        $actor ??= Auth::user();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $this->tenantValue($target, 'organization_id') ?? $actor?->organization_id,
            'franchise_id' => $this->tenantValue($target, 'franchise_id') ?? $actor?->franchise_id,
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'before' => $this->clean($before),
            'after' => $this->clean($after),
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Records a model change using its dirty attributes. Call before save().
     */
    public function logChanges(string $action, Model $model): void
    {
        $dirty = $model->getDirty();
        if ($dirty === []) {
            return;
        }

        $before = array_intersect_key($model->getOriginal(), $dirty);
        $this->log($action, $model, $before, $dirty);
    }

    private function tenantValue(?Model $target, string $column): ?int
    {
        if ($target === null) {
            return null;
        }

        if ($column === 'franchise_id' && $target instanceof Franchise) {
            return $target->getKey();
        }

        $value = $target->getAttribute($column);

        return $value === null ? null : (int) $value;
    }

    private function clean(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return array_map(
            fn ($v) => $v instanceof BackedEnum ? $v->value : $v,
            array_diff_key($values, array_flip(self::HIDDEN)),
        );
    }
}

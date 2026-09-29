<?php

namespace App\Services;

use App\Enums\SettingScope;
use App\Models\Franchise;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * Operational settings with scoped overrides:
 *   STORE → FRANCHISE → ORGANIZATION → GLOBAL → config default.
 *
 * Only keys registered in DEFINITIONS exist; each maps to the config value it overrides.
 */
class SettingsService
{
    /**
     * type: how the admin UI edits it (money = minor units); rules: validation of one value.
     */
    public const DEFINITIONS = [
        'service_fee' => [
            'type' => 'money',
            'config' => 'bento.pricing.service_fee',
            'rules' => ['integer', 'min:0', 'max:100000000'],
        ],
        'delivery_offer_ttl_seconds' => [
            'type' => 'integer',
            'config' => 'bento.dispatch.offer_ttl_seconds',
            'rules' => ['integer', 'min:15', 'max:600'],
        ],
    ];

    /**
     * Effective value for a store.
     */
    public function forStore(string $key, Store $store): mixed
    {
        return $this->resolve($key, $this->chainFor(SettingScope::STORE, $store->id, $store))['value'];
    }

    /**
     * All keys at one scope: the value set exactly there (or null) and the effective value
     * with the scope it came from.
     *
     * @return list<array{key: string, type: string, value: mixed, effective: mixed, source: string}>
     */
    public function describe(SettingScope $scope, ?int $scopeId): array
    {
        $store = $scope === SettingScope::STORE ? Store::find($scopeId) : null;
        $chain = $this->chainFor($scope, $scopeId, $store);

        return collect(self::DEFINITIONS)->map(function (array $definition, string $key) use ($chain) {
            $rows = $this->rows($key, $chain);
            $own = $rows->first(fn (Setting $s) => $s->scope_type === $chain[0][0] && $s->scope_id === $chain[0][1]);
            $resolved = $this->resolve($key, $chain, $rows);

            return [
                'key' => $key,
                'type' => $definition['type'],
                'value' => $own?->value,
                'effective' => $resolved['value'],
                'source' => $resolved['source'],
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $values  null removes the override at this scope
     */
    public function put(SettingScope $scope, ?int $scopeId, array $values): void
    {
        foreach ($values as $key => $value) {
            $match = ['scope_type' => $scope, 'scope_id' => $scopeId, 'key' => $key];
            if ($value === null) {
                Setting::query()->where($match)->delete();
            } else {
                Setting::query()->updateOrCreate($match, ['value' => $value]);
            }
        }
    }

    /**
     * @return list<array{0: SettingScope, 1: int|null}>
     */
    private function chainFor(SettingScope $scope, ?int $scopeId, ?Store $store): array
    {
        $chain = [[$scope, $scopeId]];
        if ($scope === SettingScope::STORE && $store) {
            $chain[] = [SettingScope::FRANCHISE, $store->franchise_id];
            $chain[] = [SettingScope::ORGANIZATION, $store->organization_id];
        } elseif ($scope === SettingScope::FRANCHISE) {
            $organizationId = Franchise::query()->whereKey($scopeId)->value('organization_id');
            $chain[] = [SettingScope::ORGANIZATION, $organizationId];
        }
        if ($scope !== SettingScope::GLOBAL) {
            $chain[] = [SettingScope::GLOBAL, null];
        }

        return $chain;
    }

    private function rows(string $key, array $chain): Collection
    {
        return Setting::query()
            ->where('key', $key)
            ->where(function ($q) use ($chain) {
                foreach ($chain as [$scope, $id]) {
                    $q->orWhere(fn ($w) => $w->where('scope_type', $scope)->where('scope_id', $id));
                }
            })
            ->get();
    }

    /**
     * @return array{value: mixed, source: string}
     */
    private function resolve(string $key, array $chain, ?Collection $rows = null): array
    {
        $rows ??= $this->rows($key, $chain);
        foreach ($chain as [$scope, $id]) {
            $hit = $rows->first(fn (Setting $s) => $s->scope_type === $scope && $s->scope_id === $id);
            if ($hit) {
                return ['value' => $hit->value, 'source' => $scope->value];
            }
        }

        return ['value' => config(self::DEFINITIONS[$key]['config']), 'source' => 'DEFAULT'];
    }
}

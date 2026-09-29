<?php

namespace App\Services\Admin;

use App\Models\DeliveryZone;
use App\Models\Franchise;
use App\Models\Kitchen;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\TranslationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TranslationService $translations,
    ) {}

    public function create(array $data): Store
    {
        return DB::transaction(function () use ($data) {
            $franchise = Franchise::with('organization')->findOrFail($data['franchise_id']);
            $attributes = Arr::except($data, ['translations']);
            $attributes['organization_id'] = $franchise->organization_id;
            $attributes['currency'] ??= $franchise->organization->currency;
            $attributes['timezone'] ??= $franchise->organization->timezone;
            $this->assertCurrency($attributes['currency'], $franchise->organization->currency);

            $store = Store::create($attributes);
            $this->translations->sync($store, $data['translations'] ?? []);
            $this->audit->log('store.created', $store, null, $store->getAttributes());

            return $store;
        });
    }

    public function update(Store $store, array $data): Store
    {
        return DB::transaction(function () use ($store, $data) {
            $store->fill(Arr::except($data, ['translations']));

            if ($store->isDirty('franchise_id')) {
                $this->moveToFranchise($store, Franchise::findOrFail($store->franchise_id));
            }

            if ($store->isDirty('currency')) {
                $this->assertCurrency($store->currency, $store->organization->currency);
            }

            $this->audit->logChanges('store.updated', $store);
            $store->save();

            if (array_key_exists('translations', $data)) {
                $this->translations->sync($store, $data['translations']);
            }

            return $store;
        });
    }

    public function delete(Store $store): void
    {
        DB::transaction(function () use ($store) {
            $this->audit->log('store.deleted', $store, $store->getAttributes());
            $store->deliveryZones()->update(['is_active' => false]);
            $store->delete();
        });
    }

    /**
     * Keeps denormalized tenant columns consistent when a store changes franchise.
     * Historical orders intentionally keep their original franchise (sales attribution).
     */
    private function moveToFranchise(Store $store, Franchise $franchise): void
    {
        if ($franchise->organization_id !== $store->organization_id) {
            throw ValidationException::withMessages(['franchise_id' => __('validation.exists', ['attribute' => 'franchise_id'])]);
        }

        $tenant = ['franchise_id' => $franchise->id];
        Kitchen::withTrashed()->where('store_id', $store->id)->update($tenant);
        DeliveryZone::query()->where('store_id', $store->id)->update($tenant);
    }

    /**
     * Catalog prices are organization-level, so stores must use the organization currency (MVP).
     */
    private function assertCurrency(string $currency, string $organizationCurrency): void
    {
        if ($currency !== $organizationCurrency) {
            throw ValidationException::withMessages(['currency' => __('validation.in', ['attribute' => 'currency'])]);
        }
    }
}

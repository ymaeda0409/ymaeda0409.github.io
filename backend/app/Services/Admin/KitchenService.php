<?php

namespace App\Services\Admin;

use App\Models\Kitchen;
use App\Models\Store;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class KitchenService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(Store $store, array $data): Kitchen
    {
        return DB::transaction(function () use ($store, $data) {
            $kitchen = Kitchen::create([
                ...$data,
                'store_id' => $store->id,
                'organization_id' => $store->organization_id,
                'franchise_id' => $store->franchise_id,
            ]);
            $this->audit->log('kitchen.created', $kitchen, null, $kitchen->getAttributes());

            return $kitchen;
        });
    }

    public function update(Kitchen $kitchen, array $data): Kitchen
    {
        return DB::transaction(function () use ($kitchen, $data) {
            $kitchen->fill($data);
            $this->audit->logChanges('kitchen.updated', $kitchen);
            $kitchen->save();

            return $kitchen;
        });
    }

    public function delete(Kitchen $kitchen): void
    {
        DB::transaction(function () use ($kitchen) {
            $this->audit->log('kitchen.deleted', $kitchen, $kitchen->getAttributes());
            $kitchen->delete();
        });
    }
}

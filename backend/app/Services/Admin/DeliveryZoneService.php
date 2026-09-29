<?php

namespace App\Services\Admin;

use App\Models\DeliveryZone;
use App\Models\Store;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class DeliveryZoneService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(Store $store, array $data): DeliveryZone
    {
        return DB::transaction(function () use ($store, $data) {
            $zone = DeliveryZone::create([
                ...$data,
                'store_id' => $store->id,
                'organization_id' => $store->organization_id,
                'franchise_id' => $store->franchise_id,
            ]);
            $this->audit->log('delivery_zone.created', $zone, null, $zone->getAttributes());

            return $zone;
        });
    }

    public function update(DeliveryZone $zone, array $data): DeliveryZone
    {
        return DB::transaction(function () use ($zone, $data) {
            $zone->fill($data);
            $this->audit->logChanges('delivery_zone.updated', $zone);
            $zone->save();

            return $zone;
        });
    }

    public function delete(DeliveryZone $zone): void
    {
        DB::transaction(function () use ($zone) {
            $this->audit->log('delivery_zone.deleted', $zone, $zone->getAttributes());
            $zone->delete();
        });
    }
}

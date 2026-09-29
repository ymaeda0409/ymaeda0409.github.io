<?php

namespace App\Services\Admin;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Franchise;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class FranchiseService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(array $data): Franchise
    {
        return DB::transaction(function () use ($data) {
            $franchise = Franchise::create($data);
            $this->audit->log('franchise.created', $franchise, null, $franchise->getAttributes());

            return $franchise;
        });
    }

    public function update(Franchise $franchise, array $data): Franchise
    {
        return DB::transaction(function () use ($franchise, $data) {
            $franchise->fill($data);
            $this->audit->logChanges('franchise.updated', $franchise);
            $franchise->save();

            return $franchise;
        });
    }

    public function delete(Franchise $franchise): void
    {
        if ($franchise->stores()->exists()) {
            throw ApiException::of(ErrorCode::CONFLICT);
        }

        DB::transaction(function () use ($franchise) {
            $this->audit->log('franchise.deleted', $franchise, $franchise->getAttributes());
            $franchise->delete();
        });
    }
}

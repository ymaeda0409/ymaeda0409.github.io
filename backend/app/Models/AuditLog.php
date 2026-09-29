<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(timestamps: false)]
#[Fillable([
    'user_id', 'organization_id', 'franchise_id', 'action', 'target_type', 'target_id',
    'before', 'after', 'ip_address', 'created_at',
])]
class AuditLog extends Model
{
    use BelongsToTenant;

    public static function tenantColumns(): array
    {
        return ['organization' => 'organization_id', 'franchise' => 'franchise_id'];
    }

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

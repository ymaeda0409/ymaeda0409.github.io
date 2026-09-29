<?php

namespace App\Models;

use App\Enums\SettingScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['scope_type', 'scope_id', 'key', 'value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'scope_type' => SettingScope::class,
            'value' => 'json',
        ];
    }
}

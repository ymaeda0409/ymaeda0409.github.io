<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Editable text of a notification (code × channel), translated per locale.
 * Placeholders such as :order_number are replaced when sending.
 */
#[Fillable(['code', 'channel', 'is_active'])]
class NotificationTemplate extends Model
{
    use HasTranslations;

    protected $attributes = ['is_active' => true];

    public static function translatedAttributes(): array
    {
        return ['title', 'body'];
    }

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NotificationTemplateTranslation::class, 'template_id');
    }
}

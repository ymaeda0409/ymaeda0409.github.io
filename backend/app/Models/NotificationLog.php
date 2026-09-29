<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(timestamps: false)]
#[Fillable(['user_id', 'order_id', 'channel', 'template_code', 'locale', 'status', 'error', 'created_at'])]
class NotificationLog extends Model {}

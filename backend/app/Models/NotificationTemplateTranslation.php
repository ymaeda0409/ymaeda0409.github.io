<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['template_id', 'locale', 'title', 'body'])]
class NotificationTemplateTranslation extends Model {}

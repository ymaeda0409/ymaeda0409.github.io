<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['option_id', 'locale', 'name'])]
class ProductOptionTranslation extends Model {}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['option_group_id', 'locale', 'name'])]
class ProductOptionGroupTranslation extends Model {}

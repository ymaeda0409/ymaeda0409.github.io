<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Like `exists`, but only for rows inside the user's tenant scope, so ids from
 * other franchises/stores are rejected exactly like non-existent ones.
 */
class VisibleTo implements ValidationRule
{
    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(private readonly string $model, private readonly User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = $this->model::query()->visibleTo($this->user)->whereKey($value)->exists();

        if (! $exists) {
            $fail('validation.exists')->translate();
        }
    }
}

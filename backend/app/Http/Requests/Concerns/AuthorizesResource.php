<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\Gate;

/**
 * Authorizes admin writes before validation runs, so callers without permission get
 * 403 (or 404 for records outside their tenant) instead of validation details.
 */
trait AuthorizesResource
{
    /**
     * @return array{0: class-string, 1: string} [model class, route parameter name]
     */
    abstract protected function authorizedResource(): array;

    public function authorize(): bool
    {
        [$modelClass, $parameter] = $this->authorizedResource();

        if ($this->isMethod('POST')) {
            Gate::authorize('create', $modelClass);
        } else {
            Gate::authorize('update', $this->route($parameter));
        }

        return true;
    }
}

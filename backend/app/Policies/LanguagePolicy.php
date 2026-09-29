<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Language;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LanguagePolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::LANGUAGES_MANAGE);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::LANGUAGES_MANAGE);
    }

    public function update(User $user, Language $language): Response
    {
        return $this->allows($user, Permission::LANGUAGES_MANAGE);
    }
}

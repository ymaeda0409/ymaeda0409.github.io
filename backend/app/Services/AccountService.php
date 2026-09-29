<?php

namespace App\Services;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\User;

class AccountService
{
    public function __construct(
        private readonly LocaleService $locales,
        private readonly AuditLogger $audit,
    ) {}

    public function update(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user;
    }

    public function changeLanguage(User $user, string $language): User
    {
        if (! $this->locales->isSupported($language)) {
            throw ApiException::of(ErrorCode::LANGUAGE_NOT_SUPPORTED);
        }

        $user->preferred_language = $language;
        $this->audit->logChanges('user.language_changed', $user);
        $user->save();

        return $user;
    }
}

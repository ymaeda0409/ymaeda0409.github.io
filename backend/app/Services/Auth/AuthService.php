<?php

namespace App\Services\Auth;

use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\LocaleService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly LocaleService $locales,
    ) {}

    /**
     * Verifies the OTP and signs the user in, registering a CUSTOMER on first login.
     *
     * @return array{user: User, token: string, is_new_user: bool}
     */
    public function loginWithOtp(string $phone, string $code, string $deviceName, ?string $preferredLanguage = null): array
    {
        $this->otp->verify($phone, $code);

        return DB::transaction(function () use ($phone, $deviceName, $preferredLanguage) {
            $user = User::query()->where('phone', $phone)->lockForUpdate()->first();
            $isNew = $user === null;

            if ($isNew) {
                $user = User::create([
                    'phone' => $phone,
                    'role' => UserRole::CUSTOMER,
                    'preferred_language' => $this->locales->isSupported($preferredLanguage)
                        ? $preferredLanguage
                        : App::getLocale(),
                    'is_active' => true,
                ]);
            }

            $this->ensureActive($user);

            $user->forceFill([
                'phone_verified_at' => $user->phone_verified_at ?? now(),
                'last_login_at' => now(),
            ])->save();

            return [
                'user' => $user,
                'token' => $user->createToken($deviceName)->plainTextToken,
                'is_new_user' => $isNew,
            ];
        });
    }

    /**
     * E-mail / password login for back-office staff (admin, kitchen).
     *
     * @return array{user: User, token: string}
     */
    public function loginStaff(string $email, string $password, string $deviceName): array
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password) || ! $user->isStaff()) {
            throw ApiException::of(ErrorCode::INVALID_CREDENTIALS);
        }

        $this->ensureActive($user);
        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }
    }

    private function ensureActive(User $user): void
    {
        if (! $user->is_active) {
            throw ApiException::of(ErrorCode::ACCOUNT_DISABLED);
        }
    }
}

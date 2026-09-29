<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendSms;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Queue;
use Tests\FeatureTestCase;

class OtpAuthTest extends FeatureTestCase
{
    public function test_send_otp_normalizes_phone_and_queues_localized_sms(): void
    {
        Queue::fake();

        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567'], ['Accept-Language' => 'ja'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '+265991234567');

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->to === '+265991234567'
            && str_contains($job->message, '123456')
            && str_contains($job->message, '認証コード'));
        $this->assertDatabaseHas('otp_codes', ['phone' => '+265991234567']);
    }

    public function test_verify_otp_registers_a_new_customer_with_selected_language(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567']);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '+265 99 123 4567',
            'code' => '123456',
            'preferred_language' => 'ny',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.user.role', 'CUSTOMER')
            ->assertJsonPath('data.user.preferred_language', 'ny');
        $this->assertNotEmpty($response->json('data.token'));

        $this->withToken($response->json('data.token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '+265991234567');
    }

    public function test_existing_user_logs_in_without_changing_language(): void
    {
        User::factory()->create(['phone' => '+265991234567', 'preferred_language' => 'ja']);
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567']);

        $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '123456', 'preferred_language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.is_new_user', false)
            ->assertJsonPath('data.user.preferred_language', 'ja');
    }

    public function test_wrong_code_is_rejected_and_attempts_are_limited(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '000000'])
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'OTP_INVALID');
        }

        // Even the right code no longer works after max attempts.
        $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '123456'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'OTP_EXPIRED');
    }

    public function test_expired_or_consumed_code_is_rejected(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567']);
        $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '123456'])->assertOk();

        $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '123456'])
            ->assertJsonPath('error.code', 'OTP_EXPIRED');

        $this->travel(2)->minutes();
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567'])->assertOk();
        $this->travel(6)->minutes();
        $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '123456'])
            ->assertJsonPath('error.code', 'OTP_EXPIRED');
    }

    public function test_resend_is_throttled_per_phone(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567'])->assertOk();

        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');

        $this->travel(61)->seconds();
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567'])->assertOk();
    }

    public function test_invalid_phone_fails_validation(): void
    {
        $this->postJson('/api/auth/send-otp', ['phone' => 'abc'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['fields' => ['phone']]]);
    }

    public function test_fixed_test_code_is_never_used_in_production(): void
    {
        Queue::fake();
        $this->app['env'] = 'production';

        $otp = app(OtpService::class);
        $this->assertFalse($otp->isTestMode());

        $otp->send('+265991234567');
        $this->assertFalse(password_verify('123456', OtpCode::latest('id')->first()->code_hash));
    }

    public function test_disabled_account_cannot_log_in(): void
    {
        User::factory()->create(['phone' => '+265991234567', 'is_active' => false]);
        $this->postJson('/api/auth/send-otp', ['phone' => '0991234567']);

        $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '123456'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCOUNT_DISABLED');
    }

    public function test_staff_login_and_logout(): void
    {
        $user = User::factory()->superAdmin()->create(['email' => 'admin@example.test']);

        $token = $this->postJson('/api/auth/login', ['email' => 'admin@example.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.role', UserRole::SUPER_ADMIN->value)
            ->json('data.token');

        $this->postJson('/api/auth/login', ['email' => 'admin@example.test', 'password' => 'wrong'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_customers_cannot_use_staff_login(): void
    {
        User::factory()->create(['email' => 'customer@example.test', 'password' => 'password']);

        $this->postJson('/api/auth/login', ['email' => 'customer@example.test', 'password' => 'password'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_protected_route_requires_token(): void
    {
        $this->getJson('/api/account')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}

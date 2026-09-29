<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use App\Services\LocaleService;
use Laravel\Sanctum\Sanctum;
use Tests\FeatureTestCase;

class LocaleTest extends FeatureTestCase
{
    public function test_languages_endpoint_lists_active_languages_in_order(): void
    {
        $this->getJson('/api/languages')
            ->assertOk()
            ->assertJsonPath('data.*.code', ['en', 'ny', 'ja'])
            ->assertJsonPath('data.2.native_name', '日本語')
            ->assertJsonPath('data.0.is_default', true);
    }

    public function test_accept_language_selects_locale(): void
    {
        foreach (['en' => 'en', 'ny' => 'ny', 'ja' => 'ja', 'ja-JP' => 'ja', 'fr-FR, ny;q=0.8' => 'ny', 'en;q=0.1, ja;q=0.9' => 'ja'] as $header => $expected) {
            $this->getJson('/api/languages', ['Accept-Language' => $header])
                ->assertHeader('Content-Language', $expected)
                ->assertJsonPath('meta.locale', $expected);
        }
    }

    public function test_unsupported_accept_language_falls_back_to_english(): void
    {
        $this->getJson('/api/languages', ['Accept-Language' => 'fr-FR, de;q=0.9, *;q=0.5'])
            ->assertJsonPath('meta.locale', 'en');
    }

    public function test_user_preferred_language_is_used_without_explicit_header(): void
    {
        Sanctum::actingAs(User::factory()->create(['preferred_language' => 'ja']));

        $this->getJson('/api/account')->assertJsonPath('meta.locale', 'ja');
    }

    public function test_explicit_accept_language_wins_over_user_preference(): void
    {
        Sanctum::actingAs(User::factory()->create(['preferred_language' => 'ja']));

        $this->getJson('/api/account', ['Accept-Language' => 'ny'])->assertJsonPath('meta.locale', 'ny');
    }

    public function test_inactive_language_is_ignored_and_falls_back(): void
    {
        Language::where('code', 'ja')->update(['is_active' => false]);
        $this->flushLocales();
        Sanctum::actingAs(User::factory()->create(['preferred_language' => 'ja']));

        $this->getJson('/api/account', ['Accept-Language' => 'ja'])->assertJsonPath('meta.locale', 'en');
    }

    public function test_error_messages_are_localized(): void
    {
        $this->postJson('/api/auth/verify-otp', ['phone' => '0991234567', 'code' => '000000'], ['Accept-Language' => 'ja'])
            ->assertJsonPath('error.code', 'OTP_EXPIRED')
            ->assertJsonPath('error.message', __('errors.OTP_EXPIRED', [], 'ja'));

        $this->getJson('/api/does-not-exist', ['Accept-Language' => 'ny'])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'ROUTE_NOT_FOUND')
            ->assertJsonPath('error.message', __('errors.ROUTE_NOT_FOUND', [], 'ny'));
    }

    public function test_validation_messages_fall_back_to_english_per_key(): void
    {
        // ny/validation.php has no "alpha_dash" entry → English text, not the raw key.
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $message = $this->postJson('/api/admin/franchises', ['code' => 'has spaces', 'name' => 'X', 'region' => 'Central'], ['Accept-Language' => 'ny'])
            ->assertStatus(422)
            ->json('error.fields.code.0');

        $this->assertStringNotContainsString('validation.', $message);
    }

    public function test_user_can_change_language_and_it_is_audited(): void
    {
        $user = User::factory()->create(['preferred_language' => 'en']);
        Sanctum::actingAs($user);

        $this->putJson('/api/account/language', ['language' => 'ny'])
            ->assertOk()
            ->assertJsonPath('data.preferred_language', 'ny');

        $this->assertSame('ny', $user->fresh()->preferred_language);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'user.language_changed']);

        // Subsequent requests without a header now use the new language immediately.
        $this->getJson('/api/account')->assertJsonPath('meta.locale', 'ny');
    }

    public function test_unsupported_language_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/account/language', ['language' => 'xx'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'LANGUAGE_NOT_SUPPORTED');
    }

    public function test_adding_a_language_needs_no_code_change(): void
    {
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $this->postJson('/api/admin/languages', [
            'code' => 'tum', 'name' => 'Tumbuka', 'native_name' => 'Chitumbuka', 'is_active' => true, 'sort_order' => 4,
        ])->assertCreated();

        $this->getJson('/api/languages')->assertJsonPath('data.3.code', 'tum');
        $this->assertTrue(app(LocaleService::class)->isSupported('tum'));
    }

    public function test_default_language_cannot_be_deactivated(): void
    {
        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $en = Language::where('code', 'en')->first();

        $this->putJson("/api/admin/languages/{$en->id}", ['is_active' => false])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    }

    public function test_changing_default_language_keeps_a_single_default(): void
    {
        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $ja = Language::where('code', 'ja')->first();

        $this->putJson("/api/admin/languages/{$ja->id}", ['is_default' => true])->assertOk();

        $this->assertSame(['ja'], Language::where('is_default', true)->pluck('code')->all());
        $this->assertSame('ja', app(LocaleService::class)->defaultLocale());
    }
}

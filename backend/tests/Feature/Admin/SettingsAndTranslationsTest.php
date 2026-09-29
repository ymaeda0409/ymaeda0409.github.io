<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Product;
use App\Services\LocaleService;
use Database\Seeders\NotificationTemplateSeeder;
use Tests\FeatureTestCase;

class SettingsAndTranslationsTest extends FeatureTestCase
{
    public function test_service_fee_resolves_store_then_franchise_then_global_then_config(): void
    {
        config(['bento.pricing.service_fee' => 5000]);
        $tenant = $this->createTenant();
        $store = $tenant['store'];
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $fee = fn () => $this->placeOrder($store)->service_fee;
        $this->assertSame(5000, $fee());

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $this->putJson('/api/admin/settings', ['scope_type' => 'GLOBAL', 'values' => ['service_fee' => 7000]])->assertOk();
        $this->assertSame(7000, $fee());

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $tenant['franchise']);
        $this->putJson('/api/admin/settings', ['scope_type' => 'FRANCHISE', 'scope_id' => $tenant['franchise']->id, 'values' => ['service_fee' => 8000]])->assertOk();
        $this->putJson('/api/admin/settings', ['scope_type' => 'STORE', 'scope_id' => $store->id, 'values' => ['service_fee' => 9000]])
            ->assertOk()
            ->assertJsonPath('data.settings.0.key', 'service_fee')
            ->assertJsonPath('data.settings.0.value', 9000)
            ->assertJsonPath('data.settings.0.source', 'STORE');
        $this->assertSame(9000, $fee());

        // Removing the store override falls back to the franchise value.
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $tenant['franchise']);
        $this->putJson('/api/admin/settings', ['scope_type' => 'STORE', 'scope_id' => $store->id, 'values' => ['service_fee' => null]])
            ->assertOk()
            ->assertJsonPath('data.settings.0.value', null)
            ->assertJsonPath('data.settings.0.effective', 8000)
            ->assertJsonPath('data.settings.0.source', 'FRANCHISE');
        $this->assertSame(8000, $fee());
    }

    public function test_settings_scope_and_validation(): void
    {
        $own = $this->createTenant();
        $other = $this->createTenant();
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own['franchise']);

        $this->getJson('/api/admin/settings?scope_type=GLOBAL')->assertForbidden();
        $this->getJson("/api/admin/settings?scope_type=STORE&scope_id={$other['store']->id}")->assertNotFound();
        $this->putJson('/api/admin/settings', ['scope_type' => 'STORE', 'scope_id' => $own['store']->id, 'values' => ['service_fee' => -1]])
            ->assertStatus(422);
        $this->putJson('/api/admin/settings', ['scope_type' => 'STORE', 'scope_id' => $own['store']->id, 'values' => ['unknown_key' => 1]])
            ->assertStatus(422);

        $this->actingAsRole(UserRole::STORE_MANAGER, $own['store']);
        $this->getJson("/api/admin/settings?scope_type=STORE&scope_id={$own['store']->id}")->assertForbidden();
    }

    public function test_translation_coverage_and_missing_list(): void
    {
        $tenant = $this->createTenant();
        $this->createProduct($tenant['store']);
        $this->createProduct($tenant['store'], translations: ['en' => ['name' => 'Samosa']]);
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $coverage = $this->getJson('/api/admin/translations')->assertOk()->json('data.coverage.products');
        $this->assertSame(2, $coverage['total']);
        $this->assertSame(['en' => 2, 'ny' => 1, 'ja' => 1], array_intersect_key($coverage['translated'], ['en' => 0, 'ny' => 0, 'ja' => 0]));

        $missing = $this->getJson('/api/admin/translations/products?locale=ny&missing=1')->assertOk()->json('data');
        $this->assertCount(1, $missing);
        $this->assertSame('Samosa', $missing[0]['source']['name']);
        $this->assertNull($missing[0]['translation']);
    }

    public function test_translator_fills_one_language_without_touching_others(): void
    {
        $tenant = $this->createTenant();
        $product = $this->createProduct($tenant['store'], translations: ['en' => ['name' => 'Samosa', 'description' => 'Crispy']]);
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $this->putJson("/api/admin/translations/products/{$product->id}", ['locale' => 'ny', 'values' => ['name' => 'Samosa ya ku Malawi']])
            ->assertOk()
            ->assertJsonPath('data.translations.ny.name', 'Samosa ya ku Malawi')
            ->assertJsonPath('data.translations.en.description', 'Crispy');

        $this->getJson("/api/products/{$product->id}?store_id={$tenant['store']->id}", ['Accept-Language' => 'ny'])
            ->assertOk()->assertJsonPath('data.name', 'Samosa ya ku Malawi');

        // The default language's name is the fallback for everyone and cannot be cleared.
        $this->putJson("/api/admin/translations/products/{$product->id}", ['locale' => 'en', 'values' => ['name' => '']])->assertStatus(422);
        $this->putJson("/api/admin/translations/products/{$product->id}", ['locale' => 'xx', 'values' => ['name' => 'A']])->assertStatus(422);
        $this->putJson('/api/admin/translations/unknown/1', ['locale' => 'ny', 'values' => ['name' => 'A']])->assertNotFound();
        $this->assertSame('Samosa', Product::find($product->id)->translate('name', 'en'));
    }

    public function test_a_new_language_can_be_translated_before_it_goes_live(): void
    {
        Language::create(['code' => 'tum', 'name' => 'Tumbuka', 'native_name' => 'Chitumbuka', 'is_active' => false, 'sort_order' => 4]);
        app(LocaleService::class)->flush();
        $tenant = $this->createTenant();
        $product = $this->createProduct($tenant['store'], translations: ['en' => ['name' => 'Samosa']]);
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $summary = $this->getJson('/api/admin/translations')->assertOk()->json('data');
        $this->assertContains('tum', $summary['locales']);
        $this->assertSame(0, $summary['coverage']['products']['translated']['tum']);

        $this->putJson("/api/admin/translations/products/{$product->id}", ['locale' => 'tum', 'values' => ['name' => 'Samosa ya Chitumbuka']])->assertOk();
        $this->assertSame(1, $this->getJson('/api/admin/translations')->json('data.coverage.products.translated.tum'));

        // Customers never get an inactive language: it falls back to the default.
        $this->getJson("/api/products/{$product->id}?store_id={$tenant['store']->id}", ['Accept-Language' => 'tum'])
            ->assertOk()->assertJsonPath('data.name', 'Samosa');

        // Unknown codes are still rejected everywhere.
        $this->putJson("/api/admin/products/{$product->id}", ['translations' => ['xx' => ['name' => 'X']]])->assertStatus(422);
    }

    public function test_notification_templates_are_translatable_and_only_translation_managers_may_edit(): void
    {
        $this->seed(NotificationTemplateSeeder::class);
        $tenant = $this->createTenant();

        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $templates = $this->getJson('/api/admin/translations/notification_templates?locale=ja')->assertOk()->json('data');
        $this->assertNotEmpty($templates);
        $this->assertNotNull($templates[0]['translation']['body']);

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $tenant['franchise']);
        $this->getJson('/api/admin/translations')->assertForbidden();
        $this->putJson('/api/admin/translations/products/1', ['locale' => 'ny', 'values' => ['name' => 'X']])->assertForbidden();
    }
}

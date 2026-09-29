<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Product;
use Tests\FeatureTestCase;

class CatalogManagementTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(UserRole::SUPER_ADMIN);
    }

    private function productPayload(int $categoryId): array
    {
        return [
            'category_id' => $categoryId,
            'sku' => 'BENTO-CHICKEN',
            'price' => 350000,
            'preparation_minutes' => 15,
            'is_featured' => true,
            'translations' => [
                'en' => ['name' => 'Chicken Bento', 'description' => 'Grilled chicken with rice'],
                'ny' => ['name' => 'Bento ya Nkhuku', 'description' => 'Nkhuku yowotcha ndi mpunga'],
                'ja' => ['name' => 'チキン弁当', 'description' => 'グリルチキンとご飯'],
            ],
            'option_groups' => [[
                'min_select' => 1,
                'max_select' => 1,
                'translations' => ['en' => ['name' => 'Rice size'], 'ja' => ['name' => 'ご飯の量']],
                'options' => [
                    ['price' => 0, 'translations' => ['en' => ['name' => 'Regular'], 'ja' => ['name' => '普通']]],
                    ['price' => 50000, 'translations' => ['en' => ['name' => 'Large'], 'ja' => ['name' => '大盛り']]],
                ],
            ]],
        ];
    }

    public function test_create_category_with_translations(): void
    {
        $this->postJson('/api/admin/categories', [
            'code' => 'bento',
            'translations' => ['en' => ['name' => 'Bento'], 'ny' => ['name' => 'Bento'], 'ja' => ['name' => '弁当']],
        ])->assertCreated()
            ->assertJsonPath('data.translations.ja.name', '弁当')
            ->assertJsonPath('data.organization_id', $this->organization->id);
    }

    public function test_create_product_with_translations_and_options(): void
    {
        $category = $this->createCategory();

        $response = $this->postJson('/api/admin/products', $this->productPayload($category->id))
            ->assertCreated()
            ->assertJsonPath('data.translations.en.name', 'Chicken Bento')
            ->assertJsonPath('data.translations.ny.name', 'Bento ya Nkhuku')
            ->assertJsonPath('data.translations.ja.name', 'チキン弁当')
            ->assertJsonPath('data.option_groups.0.translations.ja.name', 'ご飯の量')
            ->assertJsonPath('data.option_groups.0.options.1.price', 50000);

        $product = Product::findOrFail($response->json('data.id'));
        $this->assertSame('チキン弁当', $product->translate('name', 'ja'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.created', 'target_id' => $product->id]);
    }

    public function test_english_name_is_required_and_other_languages_are_optional(): void
    {
        $category = $this->createCategory();
        $payload = $this->productPayload($category->id);
        unset($payload['translations']['en']);

        $this->postJson('/api/admin/products', $payload)
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['translations.en.name']]]);

        $payload = $this->productPayload($category->id);
        $payload['translations'] = ['en' => ['name' => 'Chicken Bento']];
        $this->postJson('/api/admin/products', $payload)->assertCreated();
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $category = $this->createCategory();
        $payload = $this->productPayload($category->id);
        $payload['translations']['xx'] = ['name' => 'Unknown'];

        $this->postJson('/api/admin/products', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['fields' => ['translations']]]);
    }

    public function test_updating_one_language_keeps_the_others(): void
    {
        $category = $this->createCategory();
        $id = $this->postJson('/api/admin/products', $this->productPayload($category->id))->json('data.id');

        $this->putJson("/api/admin/products/{$id}", ['translations' => ['ny' => ['name' => 'Bento ya Nkhuku Yokazinga']]])
            ->assertOk()
            ->assertJsonPath('data.translations.ny.name', 'Bento ya Nkhuku Yokazinga')
            ->assertJsonPath('data.translations.ja.name', 'チキン弁当')
            ->assertJsonPath('data.translations.en.name', 'Chicken Bento');
    }

    public function test_option_groups_are_synced_by_id(): void
    {
        $category = $this->createCategory();
        $created = $this->postJson('/api/admin/products', $this->productPayload($category->id))->json('data');
        $group = $created['option_groups'][0];

        $this->putJson("/api/admin/products/{$created['id']}", ['option_groups' => [[
            'id' => $group['id'],
            'min_select' => 1,
            'max_select' => 1,
            'translations' => ['en' => ['name' => 'Rice size']],
            'options' => [
                ['id' => $group['options'][1]['id'], 'price' => 60000, 'translations' => ['en' => ['name' => 'Large']]],
            ],
        ]]])->assertOk()
            ->assertJsonCount(1, 'data.option_groups.0.options')
            ->assertJsonPath('data.option_groups.0.id', $group['id'])
            ->assertJsonPath('data.option_groups.0.options.0.id', $group['options'][1]['id'])
            ->assertJsonPath('data.option_groups.0.options.0.price', 60000);
    }

    public function test_option_group_min_select_cannot_exceed_option_count(): void
    {
        $category = $this->createCategory();
        $payload = $this->productPayload($category->id);
        $payload['option_groups'][0]['min_select'] = 3;
        $payload['option_groups'][0]['max_select'] = 3;

        $this->postJson('/api/admin/products', $payload)
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['option_groups.0.min_select']]]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        ['store' => $store] = $this->createTenant();
        $category = $this->createCategory();
        $this->createProduct($store, $category);

        $this->deleteJson("/api/admin/categories/{$category->id}")->assertStatus(409);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

class CatalogTest extends FeatureTestCase
{
    public function test_product_is_returned_in_requested_language(): void
    {
        ['store' => $store] = $this->createTenant();
        $product = $this->createProduct($store);

        foreach (['en' => 'Chicken Bento', 'ja' => 'チキン弁当', 'ny' => 'Bento ya Nkhuku'] as $locale => $name) {
            $this->getJson("/api/products/{$product->id}?store_id={$store->id}", ['Accept-Language' => $locale])
                ->assertOk()
                ->assertJsonPath('data.name', $name)
                ->assertJsonPath('meta.locale', $locale);
        }
    }

    public function test_missing_translation_falls_back_to_english(): void
    {
        ['store' => $store] = $this->createTenant();
        $product = $this->createProduct($store, translations: [
            'en' => ['name' => 'Fish Bento', 'description' => 'Chambo with rice'],
            'ja' => ['name' => 'フィッシュ弁当', 'description' => null],
        ]);

        $this->getJson("/api/products/{$product->id}?store_id={$store->id}", ['Accept-Language' => 'ny'])
            ->assertJsonPath('data.name', 'Fish Bento')
            ->assertJsonPath('data.description', 'Chambo with rice');
    }

    public function test_product_list_uses_store_price_and_availability(): void
    {
        ['store' => $store] = $this->createTenant();
        ['store' => $otherStore] = $this->createTenant();
        $category = $this->createCategory();

        $regular = $this->createProduct($store, $category, attributes: ['price' => 350000, 'sort_order' => 1]);
        $overridden = $this->createProduct($store, $category, attributes: ['price' => 400000, 'sort_order' => 2], storeOverrides: ['price' => 420000]);
        $soldOut = $this->createProduct($store, $category, attributes: ['sort_order' => 3], storeOverrides: ['stock_quantity' => 0]);
        $this->createProduct($store, $category, attributes: ['sort_order' => 4], storeOverrides: ['is_available' => false]);
        $this->createProduct($otherStore, $category);
        $this->createProduct($store, $category, attributes: ['is_active' => false]);

        $this->getJson("/api/products?store_id={$store->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $regular->id)
            ->assertJsonPath('data.0.price', 350000)
            ->assertJsonPath('data.0.currency', 'MWK')
            ->assertJsonPath('data.1.id', $overridden->id)
            ->assertJsonPath('data.1.price', 420000)
            ->assertJsonPath('data.2.id', $soldOut->id)
            ->assertJsonPath('data.2.is_sold_out', true);
    }

    public function test_categories_are_translated_and_filtered_by_store(): void
    {
        ['store' => $store] = $this->createTenant();
        $bento = $this->createCategory(['en' => 'Bento', 'ja' => '弁当', 'ny' => 'Bento']);
        $this->createCategory(['en' => 'Drinks', 'ja' => 'ドリンク', 'ny' => 'Zakumwa']); // no products
        $this->createProduct($store, $bento);

        $this->getJson("/api/categories?store_id={$store->id}", ['Accept-Language' => 'ja'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '弁当');
    }

    public function test_product_detail_includes_translated_options(): void
    {
        ['store' => $store] = $this->createTenant();
        $product = $this->createProduct($store);
        $group = $product->optionGroups()->create(['min_select' => 1, 'max_select' => 1]);
        $group->translations()->createMany([['locale' => 'en', 'name' => 'Rice size'], ['locale' => 'ja', 'name' => 'ご飯の量']]);
        $large = $group->options()->create(['price' => 50000]);
        $large->translations()->createMany([['locale' => 'en', 'name' => 'Large'], ['locale' => 'ja', 'name' => '大盛り']]);
        ProductOption::create(['option_group_id' => $group->id, 'price' => 0, 'is_active' => false]);

        $this->getJson("/api/products/{$product->id}?store_id={$store->id}", ['Accept-Language' => 'ja'])
            ->assertJsonPath('data.option_groups.0.name', 'ご飯の量')
            ->assertJsonCount(1, 'data.option_groups.0.options')
            ->assertJsonPath('data.option_groups.0.options.0.name', '大盛り')
            ->assertJsonPath('data.option_groups.0.options.0.price', 50000);
    }

    public function test_only_requested_and_fallback_translations_are_loaded(): void
    {
        ['store' => $store] = $this->createTenant();
        $category = $this->createCategory();
        for ($i = 0; $i < 5; $i++) {
            $this->createProduct($store, $category);
        }

        DB::enableQueryLog();
        $this->getJson("/api/products?store_id={$store->id}", ['Accept-Language' => 'ja'])->assertJsonCount(5, 'data');
        $queries = collect(DB::getQueryLog())->pluck('query');

        $translationQueries = $queries->filter(fn ($q) => str_contains($q, 'from "product_translations"'));
        $this->assertCount(1, $translationQueries, 'Translations must be eager loaded in one query (no N+1)');
        $this->assertStringContainsString('"locale" in', $translationQueries->first());
    }

    public function test_product_from_another_store_or_inactive_store_is_not_found(): void
    {
        ['store' => $store] = $this->createTenant();
        ['store' => $otherStore] = $this->createTenant();
        $product = $this->createProduct($otherStore);

        $this->getJson("/api/products/{$product->id}?store_id={$store->id}")
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');

        $otherStore->update(['is_active' => false]);
        $this->getJson("/api/products?store_id={$otherStore->id}")->assertStatus(404);
    }

    public function test_store_id_is_required(): void
    {
        $this->getJson('/api/products')->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}

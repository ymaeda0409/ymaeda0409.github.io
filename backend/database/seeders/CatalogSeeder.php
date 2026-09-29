<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Initial menu with en / ny / ja translations. Prices are minor units (MWK × 100).
 * Chichewa strings are an initial translation and need native-speaker review.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'malawi-bento')->firstOrFail();

        $categories = [
            'bento' => ['sort' => 1, 'names' => ['en' => 'Bento', 'ny' => 'Bento', 'ja' => '弁当']],
            'drinks' => ['sort' => 2, 'names' => ['en' => 'Drinks', 'ny' => 'Zakumwa', 'ja' => 'ドリンク']],
        ];

        $categoryIds = [];
        foreach ($categories as $code => $category) {
            $model = ProductCategory::updateOrCreate(
                ['organization_id' => $organization->id, 'code' => $code],
                ['sort_order' => $category['sort'], 'is_active' => true],
            );
            foreach ($category['names'] as $locale => $name) {
                $model->translations()->updateOrCreate(['locale' => $locale], ['name' => $name]);
            }
            $categoryIds[$code] = $model->id;
        }

        foreach ($this->products() as $index => $data) {
            $product = Product::updateOrCreate(
                ['organization_id' => $organization->id, 'sku' => $data['sku']],
                [
                    'category_id' => $categoryIds[$data['category']],
                    'price' => $data['price'],
                    'preparation_minutes' => $data['minutes'],
                    'is_featured' => $data['featured'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );

            foreach ($data['translations'] as $locale => $values) {
                $product->translations()->updateOrCreate(['locale' => $locale], $values);
            }

            if ($data['category'] === 'bento') {
                $this->seedRiceOption($product);
            }
        }

        Store::where('organization_id', $organization->id)->each(function (Store $store) use ($organization) {
            Product::where('organization_id', $organization->id)->each(
                fn (Product $product) => $store->storeProducts()->firstOrCreate(
                    ['product_id' => $product->id],
                    ['is_available' => true, 'sort_order' => $product->sort_order],
                ),
            );
        });
    }

    private function seedRiceOption(Product $product): void
    {
        if ($product->optionGroups()->exists()) {
            return;
        }

        $group = $product->optionGroups()->create(['min_select' => 1, 'max_select' => 1, 'sort_order' => 1]);
        foreach (['en' => 'Rice size', 'ny' => 'Kukula kwa mpunga', 'ja' => 'ご飯の量'] as $locale => $name) {
            $group->translations()->create(['locale' => $locale, 'name' => $name]);
        }

        $options = [
            ['price' => 0, 'names' => ['en' => 'Regular', 'ny' => 'Wamba', 'ja' => '普通']],
            ['price' => 50000, 'names' => ['en' => 'Large', 'ny' => 'Wochuluka', 'ja' => '大盛り']],
        ];
        foreach ($options as $sort => $option) {
            $model = $group->options()->create(['price' => $option['price'], 'is_active' => true, 'sort_order' => $sort + 1]);
            foreach ($option['names'] as $locale => $name) {
                $model->translations()->create(['locale' => $locale, 'name' => $name]);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'sku' => 'BENTO-CHICKEN', 'category' => 'bento', 'price' => 350000, 'minutes' => 15, 'featured' => true,
                'translations' => [
                    'en' => ['name' => 'Chicken Bento', 'description' => 'Grilled chicken with rice and seasonal vegetables.'],
                    'ny' => ['name' => 'Bento ya Nkhuku', 'description' => 'Nkhuku yowotcha ndi mpunga komanso ndiwo zamasamba.'],
                    'ja' => ['name' => 'チキン弁当', 'description' => 'グリルチキンとご飯、季節の野菜の弁当です。'],
                ],
            ],
            [
                'sku' => 'BENTO-BEEF', 'category' => 'bento', 'price' => 400000, 'minutes' => 15, 'featured' => true,
                'translations' => [
                    'en' => ['name' => 'Beef Bento', 'description' => 'Tender stewed beef with rice.'],
                    'ny' => ['name' => 'Bento ya Ng\'ombe', 'description' => 'Nyama ya ng\'ombe yofewa ndi mpunga.'],
                    'ja' => ['name' => 'ビーフ弁当', 'description' => 'やわらかく煮込んだ牛肉とご飯の弁当です。'],
                ],
            ],
            [
                'sku' => 'BENTO-FISH', 'category' => 'bento', 'price' => 380000, 'minutes' => 20, 'featured' => false,
                'translations' => [
                    'en' => ['name' => 'Fish Bento', 'description' => 'Fried chambo from Lake Malawi with rice.'],
                    'ny' => ['name' => 'Bento ya Nsomba', 'description' => 'Chambo yokazinga ya ku Nyanja ya Malawi ndi mpunga.'],
                    'ja' => ['name' => 'フィッシュ弁当', 'description' => 'マラウイ湖のチャンボのフライとご飯の弁当です。'],
                ],
            ],
            [
                'sku' => 'BENTO-VEG', 'category' => 'bento', 'price' => 300000, 'minutes' => 12, 'featured' => false,
                'translations' => [
                    'en' => ['name' => 'Vegetarian Bento', 'description' => 'Beans, greens and vegetables with rice.'],
                    'ny' => ['name' => 'Bento ya Ndiwo Zamasamba', 'description' => 'Nyemba, masamba ndi ndiwo zina ndi mpunga.'],
                    'ja' => ['name' => 'ベジタリアン弁当', 'description' => '豆と青菜、野菜とご飯の弁当です。'],
                ],
            ],
            [
                'sku' => 'DRINK-WATER', 'category' => 'drinks', 'price' => 50000, 'minutes' => 0, 'featured' => false,
                'translations' => [
                    'en' => ['name' => 'Water', 'description' => 'Bottled water 500ml.'],
                    'ny' => ['name' => 'Madzi', 'description' => 'Madzi a m\'botolo 500ml.'],
                    'ja' => ['name' => '水', 'description' => 'ボトル入り飲料水 500ml'],
                ],
            ],
            [
                'sku' => 'DRINK-COKE', 'category' => 'drinks', 'price' => 80000, 'minutes' => 0, 'featured' => false,
                'translations' => [
                    'en' => ['name' => 'Coke', 'description' => 'Coca-Cola 500ml.'],
                    'ny' => ['name' => 'Coke', 'description' => 'Coca-Cola 500ml.'],
                    'ja' => ['name' => 'コーラ', 'description' => 'コカ・コーラ 500ml'],
                ],
            ],
        ];
    }
}

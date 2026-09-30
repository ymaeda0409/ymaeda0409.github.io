<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Launch menu of the Lilongwe store with en / ny / ja translations.
 * Prices are minor units (MWK × 100). Photos live in public/images/menu.
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
                    'image_url' => isset($data['image']) ? url("/images/menu/{$data['image']}") : null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );

            foreach ($data['translations'] as $locale => $values) {
                $product->translations()->updateOrCreate(['locale' => $locale], $values);
            }

            if ($data['category'] === 'bento' && ! $product->optionGroups()->exists()) {
                foreach ($this->bentoOptions($data['staples']) as $sort => $group) {
                    $this->seedOptionGroup($product, $group, $sort + 1);
                }
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

    /**
     * @param  array{min: int, max: int, names: array<string, string>, options: list<array{price: int, names: array<string, string>}>}  $group
     */
    private function seedOptionGroup(Product $product, array $group, int $sort): void
    {
        $model = $product->optionGroups()->create(['min_select' => $group['min'], 'max_select' => $group['max'], 'sort_order' => $sort]);
        foreach ($group['names'] as $locale => $name) {
            $model->translations()->create(['locale' => $locale, 'name' => $name]);
        }
        foreach ($group['options'] as $optionSort => $option) {
            $optionModel = $model->options()->create(['price' => $option['price'], 'is_active' => true, 'sort_order' => $optionSort + 1]);
            foreach ($option['names'] as $locale => $name) {
                $optionModel->translations()->create(['locale' => $locale, 'name' => $name]);
            }
        }
    }

    /**
     * Option groups shared by every bento; the staple list puts the bento's own staple first.
     *
     * @param  list<string>  $staples  keys of self::STAPLES
     */
    private function bentoOptions(array $staples): array
    {
        return [
            [
                'min' => 1, 'max' => 1,
                'names' => ['en' => 'Choose your staple', 'ny' => 'Sankhani chakudya chachikulu', 'ja' => '主食を選ぶ'],
                'options' => array_map(fn ($key) => self::STAPLES[$key], $staples),
            ],
            [
                'min' => 1, 'max' => 1,
                'names' => ['en' => 'Portion', 'ny' => 'Kuchuluka', 'ja' => 'おかずの量'],
                'options' => [
                    ['price' => 0, 'names' => ['en' => 'Regular', 'ny' => 'Wamba', 'ja' => '普通']],
                    ['price' => 250000, 'names' => ['en' => 'Large (1.5× meat)', 'ny' => 'Chachikulu (nyama 1.5×)', 'ja' => '大盛り（お肉 1.5 倍）']],
                ],
            ],
            [
                'min' => 0, 'max' => 3,
                'names' => ['en' => 'Extras (up to 3)', 'ny' => 'Zowonjezera (mpaka 3)', 'ja' => 'トッピング（3 つまで）'],
                'options' => [
                    ['price' => 80000, 'names' => ['en' => 'Extra kachumbari salad', 'ny' => 'Kachumbari yowonjezera', 'ja' => 'カチュンバリ（トマトときゅうりのサラダ）追加']],
                    ['price' => 30000, 'names' => ['en' => 'Malawian chilli sauce', 'ny' => 'Tsabola wa ku Malawi', 'ja' => 'マラウイ風チリソース']],
                    ['price' => 70000, 'names' => ['en' => 'Boiled egg', 'ny' => 'Dzira lowiritsa', 'ja' => 'ゆで卵']],
                    ['price' => 100000, 'names' => ['en' => 'Chapati', 'ny' => 'Chapati', 'ja' => 'チャパティ']],
                ],
            ],
            [
                'min' => 0, 'max' => 1,
                'names' => ['en' => 'Add a drink', 'ny' => 'Onjezani chakumwa', 'ja' => 'ドリンクを追加'],
                'options' => [
                    ['price' => 80000, 'names' => ['en' => 'Bottled water 500ml', 'ny' => 'Madzi a m\'botolo 500ml', 'ja' => 'ミネラルウォーター 500ml']],
                    ['price' => 130000, 'names' => ['en' => 'Coca-Cola 500ml', 'ny' => 'Coca-Cola 500ml', 'ja' => 'コカ・コーラ 500ml']],
                    ['price' => 150000, 'names' => ['en' => 'Fresh mango juice', 'ny' => 'Madzi a mango', 'ja' => '生マンゴージュース']],
                ],
            ],
        ];
    }

    private const STAPLES = [
        'yellow_rice' => ['price' => 0, 'names' => ['en' => 'Yellow rice', 'ny' => 'Mpunga wachikasu', 'ja' => 'イエローライス']],
        'pilau' => ['price' => 0, 'names' => ['en' => 'Pilau rice', 'ny' => 'Pilau', 'ja' => 'ピラウ（スパイスご飯）']],
        'potatoes' => ['price' => 0, 'names' => ['en' => 'Rosemary potatoes', 'ny' => 'Mbatata za rosemary', 'ja' => 'ローズマリーポテト']],
        'nsima' => ['price' => 0, 'names' => ['en' => 'Nsima', 'ny' => 'Nsima', 'ja' => 'シマ（とうもろこしの主食）']],
        'chips' => ['price' => 100000, 'names' => ['en' => 'Chips', 'ny' => 'Chipisi', 'ja' => 'フライドポテト']],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'sku' => 'BENTO-COMBO', 'category' => 'bento', 'price' => 950000, 'minutes' => 15, 'featured' => true,
                'image' => 'combo-chicken-beef.jpg', 'staples' => ['yellow_rice', 'pilau', 'nsima', 'chips'],
                'translations' => [
                    'en' => ['name' => 'Chicken & Beef Combo', 'description' => 'Crispy fried chicken and beef stir-fried with peppers and onion, served with yellow rice, pea and carrot stew and a fresh kachumbari salad.'],
                    'ny' => ['name' => 'Nkhuku ndi Ng\'ombe', 'description' => 'Nkhuku yokazinga ndi nyama ya ng\'ombe yokazinga ndi tsabola ndi anyezi, pamodzi ndi mpunga wachikasu, nsawawa ndi karoti, komanso saladi ya kachumbari.'],
                    'ja' => ['name' => 'チキン＆ビーフのコンボ弁当', 'description' => 'カリッと揚げたチキンと、ピーマン・玉ねぎと炒めた牛肉。イエローライス、グリーンピースとにんじんの煮込み、トマトときゅうりのカチュンバリ付き。'],
                ],
            ],
            [
                'sku' => 'BENTO-BEEF-FISH', 'category' => 'bento', 'price' => 1050000, 'minutes' => 20, 'featured' => true,
                'image' => 'beef-stew-fish.jpg', 'staples' => ['potatoes', 'yellow_rice', 'nsima', 'chips'],
                'translations' => [
                    'en' => ['name' => 'Beef Stew & Crumbed Fish', 'description' => 'Slow-cooked beef stew and a golden crumbed fish fillet with rosemary potatoes, broccoli, cauliflower and carrot, plus pea stew and kachumbari.'],
                    'ny' => ['name' => 'Nyama ya Ng\'ombe ndi Nsomba', 'description' => 'Nyama ya ng\'ombe yophika pang\'onopang\'ono ndi nsomba yokazinga, mbatata za rosemary, broccoli, cauliflower ndi karoti, nsawawa ndi kachumbari.'],
                    'ja' => ['name' => '牛肉の煮込み＆白身魚フライ弁当', 'description' => 'じっくり煮込んだ牛肉と、衣サクサクの白身魚フライ。ローズマリーポテト、ブロッコリー、カリフラワー、にんじん、グリーンピースの煮込みとカチュンバリ付き。'],
                ],
            ],
            [
                'sku' => 'BENTO-GOAT-PILAU', 'category' => 'bento', 'price' => 1100000, 'minutes' => 20, 'featured' => true,
                'image' => 'goat-stew-pilau.jpg', 'staples' => ['pilau', 'yellow_rice', 'nsima', 'chips'],
                'translations' => [
                    'en' => ['name' => 'Goat Stew & Pilau', 'description' => 'Tender bone-in goat stewed with tomato and spices, fragrant pilau rice with a fried chicken wing, pea and carrot stew and kachumbari.'],
                    'ny' => ['name' => 'Nyama ya Mbuzi ndi Pilau', 'description' => 'Nyama ya mbuzi yofewa yophikidwa ndi phwetekere ndi zonunkhira, pilau, phiko la nkhuku lokazinga, nsawawa ndi kachumbari.'],
                    'ja' => ['name' => 'ヤギ肉の煮込み＆ピラウ弁当', 'description' => 'トマトとスパイスで骨付きのままやわらかく煮込んだヤギ肉。スパイス香るピラウ、手羽先フライ、グリーンピースの煮込み、カチュンバリ付き。'],
                ],
            ],
            [
                'sku' => 'BENTO-BEEF-VEG', 'category' => 'bento', 'price' => 980000, 'minutes' => 15, 'featured' => false,
                'image' => 'braised-beef-vegetables.jpg', 'staples' => ['pilau', 'yellow_rice', 'nsima', 'chips'],
                'translations' => [
                    'en' => ['name' => 'Braised Beef & Roast Vegetables', 'description' => 'Beef braised in a rich tomato gravy with pilau rice, roasted peppers and aubergine, broccoli and carrot, and kachumbari.'],
                    'ny' => ['name' => 'Nyama ya Ng\'ombe ndi Ndiwo Zowotcha', 'description' => 'Nyama ya ng\'ombe yophikidwa mu msuzi wa phwetekere, pilau, tsabola ndi biringanya zowotcha, broccoli, karoti ndi kachumbari.'],
                    'ja' => ['name' => '牛肉のトマト煮込み＆焼き野菜弁当', 'description' => '濃厚なトマトソースで煮込んだ牛肉。ピラウ、ローストしたパプリカとなす、ブロッコリー、にんじん、カチュンバリ付き。'],
                ],
            ],
            [
                'sku' => 'DRINK-WATER', 'category' => 'drinks', 'price' => 80000, 'minutes' => 0, 'featured' => false,
                'translations' => [
                    'en' => ['name' => 'Bottled Water', 'description' => 'Still mineral water, 500ml.'],
                    'ny' => ['name' => 'Madzi a M\'botolo', 'description' => 'Madzi akumwa, 500ml.'],
                    'ja' => ['name' => 'ミネラルウォーター', 'description' => '500ml'],
                ],
            ],
            [
                'sku' => 'DRINK-COLA', 'category' => 'drinks', 'price' => 130000, 'minutes' => 0, 'featured' => false,
                'translations' => [
                    'en' => ['name' => 'Coca-Cola', 'description' => 'Chilled, 500ml bottle.'],
                    'ny' => ['name' => 'Coca-Cola', 'description' => 'Yozizira, botolo la 500ml.'],
                    'ja' => ['name' => 'コカ・コーラ', 'description' => '冷えた 500ml ボトル'],
                ],
            ],
            [
                'sku' => 'DRINK-MANGO', 'category' => 'drinks', 'price' => 150000, 'minutes' => 3, 'featured' => false,
                'translations' => [
                    'en' => ['name' => 'Fresh Mango Juice', 'description' => 'Made from Malawian mangoes, no added sugar, 350ml.'],
                    'ny' => ['name' => 'Madzi a Mango', 'description' => 'Opangidwa ndi mango a ku Malawi, opanda shuga wowonjezera, 350ml.'],
                    'ja' => ['name' => '生マンゴージュース', 'description' => 'マラウイ産マンゴー使用、砂糖不使用。350ml'],
                ],
            ],
        ];
    }
}

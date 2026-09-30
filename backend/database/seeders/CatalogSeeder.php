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
                'options' => array_map(
                    fn (array $drink) => ['price' => $drink['price'], 'names' => array_map(fn ($t) => $t['name'], $drink['translations'])],
                    $this->drinks(),
                ),
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
            ...$this->drinks(),
        ];
    }

    /**
     * Bottled drinks (500ml); also offered as the bento "add a drink" option.
     *
     * @return list<array<string, mixed>>
     */
    private function drinks(): array
    {
        return [
            [
                'sku' => 'DRINK-THOBWA', 'category' => 'drinks', 'price' => 150000, 'minutes' => 0, 'featured' => false,
                'image' => 'thobwa.jpg',
                'translations' => [
                    'en' => ['name' => 'Thobwa', 'description' => 'Traditional Malawian drink of lightly fermented maize: creamy and gently tangy. Rich in B vitamins, no added sugar. 500ml.'],
                    'ny' => ['name' => 'Thobwa', 'description' => 'Chakumwa chachikhalidwe cha ku Malawi chopangidwa ndi chimanga: chokoma komanso chowawasa pang\'ono. Chili ndi mavitamini a B, chopanda shuga wowonjezera. 500ml.'],
                    'ja' => ['name' => 'トブワ（伝統のとうもろこしドリンク）', 'description' => 'とうもろこしを軽く発酵させたマラウイ伝統の飲み物。とろりとして、ほのかな酸味。ビタミン B 豊富、砂糖不使用。500ml'],
                ],
            ],
            [
                'sku' => 'DRINK-HIBISCUS', 'category' => 'drinks', 'price' => 180000, 'minutes' => 0, 'featured' => false,
                'image' => 'hibiscus-ginger.jpg',
                'translations' => [
                    'en' => ['name' => 'Hibiscus Ginger Drink', 'description' => 'Hibiscus flowers and fresh ginger: tart, bright and refreshing. Rich in antioxidants, no added sugar. 500ml.'],
                    'ny' => ['name' => 'Hibiscus ndi Ginger', 'description' => 'Maluwa a hibiscus ndi ginger watsopano: chowawasa komanso chotsitsimula. Chopanda shuga wowonjezera. 500ml.'],
                    'ja' => ['name' => 'ハイビスカス・ジンジャー', 'description' => 'ハイビスカスの花と生姜の、すっきりとした酸味。抗酸化成分たっぷり、砂糖不使用。500ml'],
                ],
            ],
            [
                'sku' => 'DRINK-ICED-TEA', 'category' => 'drinks', 'price' => 180000, 'minutes' => 0, 'featured' => false,
                'image' => 'highlands-iced-tea.jpg',
                'translations' => [
                    'en' => ['name' => 'Highlands Iced Tea', 'description' => 'Brewed from real Malawian black tea leaves, served chilled. No added sugar. 500ml.'],
                    'ny' => ['name' => 'Tiyi Wozizira wa Highlands', 'description' => 'Tiyi wopangidwa ndi masamba enieni a tiyi wa ku Malawi, wozizira. Wopanda shuga wowonjezera. 500ml.'],
                    'ja' => ['name' => 'ハイランズ・アイスティー', 'description' => 'マラウイ高原の紅茶葉で淹れたアイスティー。無糖。500ml'],
                ],
            ],
            [
                'sku' => 'DRINK-BAOBAB', 'category' => 'drinks', 'price' => 200000, 'minutes' => 0, 'featured' => false,
                'image' => 'baobab-juice.jpg',
                'translations' => [
                    'en' => ['name' => 'Baobab Juice', 'description' => 'Sweet-and-sour juice of the baobab fruit. Rich in vitamin C, no added sugar. 500ml.'],
                    'ny' => ['name' => 'Madzi a Malambe', 'description' => 'Madzi a zipatso za mlambe, otsekemera komanso owawasa. Ali ndi vitamini C wambiri, opanda shuga wowonjezera. 500ml.'],
                    'ja' => ['name' => 'バオバブジュース', 'description' => 'バオバブの実の甘酸っぱいジュース。ビタミン C 豊富、砂糖不使用。500ml'],
                ],
            ],
            [
                'sku' => 'DRINK-ORANGE-SODA', 'category' => 'drinks', 'price' => 130000, 'minutes' => 0, 'featured' => false,
                'image' => 'orange-soda.jpg',
                'translations' => [
                    'en' => ['name' => 'Orange Soda', 'description' => 'Fizzy orange soda, served ice-cold. 500ml.'],
                    'ny' => ['name' => 'Soda ya Lalanje', 'description' => 'Soda ya lalanje yozizira kwambiri. 500ml.'],
                    'ja' => ['name' => 'オレンジソーダ', 'description' => 'キンキンに冷えたオレンジソーダ。500ml'],
                ],
            ],
        ];
    }
}

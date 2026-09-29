<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Services\LocaleService;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'sort_order' => 1],
            ['code' => 'ny', 'name' => 'Chichewa', 'native_name' => 'Chichewa', 'is_default' => false, 'sort_order' => 2],
            ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語', 'is_default' => false, 'sort_order' => 3],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], $language + ['direction' => 'ltr', 'is_active' => true]);
        }

        app(LocaleService::class)->flush();
    }
}

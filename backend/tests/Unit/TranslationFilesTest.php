<?php

namespace Tests\Unit;

use App\Enums\ErrorCode;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Every locale directory must provide the same keys as English for app-owned files.
 * (validation.php intentionally relies on per-key fallback to English.)
 */
class TranslationFilesTest extends TestCase
{
    private const FILES = ['errors', 'sms'];

    public function test_every_locale_has_the_same_keys_as_english(): void
    {
        $locales = array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR));
        $this->assertContains('ny', $locales);
        $this->assertContains('ja', $locales);

        foreach (self::FILES as $file) {
            $reference = array_keys(Arr::dot(require lang_path("en/{$file}.php")));
            sort($reference);

            foreach ($locales as $locale) {
                $path = lang_path("{$locale}/{$file}.php");
                $this->assertFileExists($path);
                $keys = array_keys(Arr::dot(require $path));
                sort($keys);
                $this->assertSame($reference, $keys, "lang/{$locale}/{$file}.php keys differ from English");
            }
        }
    }

    public function test_every_error_code_has_a_message(): void
    {
        $messages = require lang_path('en/errors.php');

        foreach (ErrorCode::cases() as $code) {
            $this->assertArrayHasKey($code->value, $messages);
        }
    }

    public function test_placeholders_are_preserved_in_translations(): void
    {
        $reference = Arr::dot(require lang_path('en/sms.php'));

        foreach (['ny', 'ja'] as $locale) {
            foreach (Arr::dot(require lang_path("{$locale}/sms.php")) as $key => $text) {
                preg_match_all('/:\w+/', $reference[$key], $expected);
                preg_match_all('/:\w+/', $text, $actual);
                sort($expected[0]);
                sort($actual[0]);
                $this->assertSame($expected[0], $actual[0], "Placeholders differ in lang/{$locale}/sms.php [{$key}]");
            }
        }
    }
}

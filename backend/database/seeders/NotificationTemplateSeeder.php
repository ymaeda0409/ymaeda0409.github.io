<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\Language;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Lang;

/**
 * Copies the shipped texts (lang/<locale>/notifications.php and sms.php) into editable
 * DB templates for every active language. Existing translations are not overwritten.
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $locales = Language::query()->where('is_active', true)->pluck('code');

        foreach (array_keys(Lang::get('notifications', [], 'en')) as $code) {
            $template = NotificationTemplate::firstOrCreate(['code' => $code, 'channel' => NotificationChannel::PUSH]);
            foreach ($locales as $locale) {
                if (Lang::hasForLocale("notifications.{$code}", $locale)) {
                    $text = Lang::get("notifications.{$code}", [], $locale);
                    $template->translations()->firstOrCreate(['locale' => $locale], ['title' => $text['title'], 'body' => $text['body']]);
                }
            }
        }

        foreach (array_keys(Lang::get('sms', [], 'en')) as $key) {
            if ($key === 'otp') {
                continue; // Sent directly by the OTP service.
            }
            $template = NotificationTemplate::firstOrCreate(['code' => strtoupper($key), 'channel' => NotificationChannel::SMS]);
            foreach ($locales as $locale) {
                if (Lang::hasForLocale("sms.{$key}", $locale)) {
                    $template->translations()->firstOrCreate(['locale' => $locale], ['body' => Lang::get("sms.{$key}", [], $locale)]);
                }
            }
        }
    }
}

<?php

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Services\LocaleService;
use Illuminate\Support\Facades\Lang;

/**
 * Resolves the text of a notification. Order:
 *   DB template (locale) → DB template (default locale) → lang file (locale) → lang file (default).
 * DB rows are editable by admins; lang files are the shipped defaults.
 */
class NotificationTemplateService
{
    public function __construct(private readonly LocaleService $locales) {}

    /**
     * @return array{title: ?string, body: string, locale: string}|null null = channel not used for this code
     */
    public function render(string $code, NotificationChannel $channel, string $locale, array $replace = []): ?array
    {
        $default = $this->locales->defaultLocale();
        $template = NotificationTemplate::query()
            ->where('code', $code)
            ->where('channel', $channel)
            ->with(['translations' => fn ($q) => $q->whereIn('locale', array_unique([$locale, $default]))])
            ->first();

        if ($template && ! $template->is_active) {
            return null;
        }

        foreach (array_unique([$locale, $default]) as $candidate) {
            $row = $template?->translations->firstWhere('locale', $candidate);
            if ($row) {
                return $this->fill($row->title, $row->body, $candidate, $replace);
            }
        }

        foreach (array_unique([$locale, $default]) as $candidate) {
            $key = $channel === NotificationChannel::SMS ? 'sms.'.strtolower($code) : "notifications.{$code}";
            if (! Lang::hasForLocale($key, $candidate)) {
                continue;
            }
            $text = Lang::get($key, [], $candidate);

            return is_array($text)
                ? $this->fill($text['title'] ?? null, $text['body'], $candidate, $replace)
                : $this->fill(null, $text, $candidate, $replace);
        }

        return null;
    }

    private function fill(?string $title, string $body, string $locale, array $replace): array
    {
        $apply = fn (?string $text) => $text === null ? null : strtr($text, collect($replace)->mapWithKeys(fn ($v, $k) => [":{$k}" => (string) $v])->all());

        return ['title' => $apply($title), 'body' => $apply($body), 'locale' => $locale];
    }
}

<?php

namespace App\Services\Sms;

use App\Jobs\SendSms;
use App\Services\LocaleService;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(private readonly LocaleService $locales) {}

    /**
     * Sends a translated SMS (lang/<locale>/sms.php). Falls back to the default locale when the
     * localized text exceeds the configured segment budget (e.g. UCS-2 scripts), to control cost.
     */
    public function sendTranslated(string $to, string $key, array $replace, string $locale): void
    {
        $message = __('sms.'.$key, $replace, $locale);
        $maxSegments = (int) config('bento.sms.max_segments');

        if (SmsMessage::segments($message) > $maxSegments) {
            $fallback = $this->locales->defaultLocale();
            Log::warning('SMS exceeds segment budget, using fallback locale', [
                'key' => $key, 'locale' => $locale, 'fallback' => $fallback,
            ]);
            $message = __('sms.'.$key, $replace, $fallback);
        }

        SendSms::dispatch($to, $message);
    }
}

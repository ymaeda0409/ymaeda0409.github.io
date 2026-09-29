<?php

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Jobs\SendSms;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Contracts\PushNotifier;
use App\Services\LocaleService;
use App\Services\Sms\SmsMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a notification to one user in their preferred language, on every channel that has
 * a template for the code (push always when templated; SMS only for the important codes).
 */
class NotificationService
{
    public function __construct(
        private readonly NotificationTemplateService $templates,
        private readonly PushNotifier $push,
        private readonly LocaleService $locales,
    ) {}

    public function send(User $user, string $code, array $replace = [], ?int $orderId = null): void
    {
        $locale = $this->locales->isSupported($user->preferred_language) ? $user->preferred_language : $this->locales->defaultLocale();

        $this->sendPush($user, $code, $locale, $replace, $orderId);
        $this->sendSms($user, $code, $locale, $replace, $orderId);
    }

    private function sendPush(User $user, string $code, string $locale, array $replace, ?int $orderId): void
    {
        $message = $this->templates->render($code, NotificationChannel::PUSH, $locale, $replace);
        $tokens = $user->deviceTokens()->pluck('token')->all();
        if ($message === null || $tokens === []) {
            return;
        }

        try {
            // `code` lets a foreground app show its own (bundled) translation instead.
            $invalid = $this->push->send($tokens, (string) $message['title'], $message['body'], array_filter([
                'code' => $code,
                'order_id' => $orderId === null ? null : (string) $orderId,
            ]));
            if ($invalid !== []) {
                $user->deviceTokens()->whereIn('token', $invalid)->delete();
            }
            $this->log($user, NotificationChannel::PUSH, $code, $message['locale'], 'SENT', $orderId);
        } catch (Throwable $e) {
            Log::warning('Push failed', ['code' => $code, 'user' => $user->id, 'error' => $e->getMessage()]);
            $this->log($user, NotificationChannel::PUSH, $code, $message['locale'], 'FAILED', $orderId, $e->getMessage());
        }
    }

    private function sendSms(User $user, string $code, string $locale, array $replace, ?int $orderId): void
    {
        if (! $user->phone) {
            return;
        }
        $message = $this->templates->render($code, NotificationChannel::SMS, $locale, $replace);
        if ($message === null) {
            return;
        }

        // Cost control: long texts (e.g. UCS-2 scripts) fall back to the default language.
        if (SmsMessage::segments($message['body']) > (int) config('bento.sms.max_segments')) {
            $fallback = $this->templates->render($code, NotificationChannel::SMS, $this->locales->defaultLocale(), $replace);
            $message = $fallback ?? $message;
        }

        SendSms::dispatch($user->phone, $message['body']);
        $this->log($user, NotificationChannel::SMS, $code, $message['locale'], 'QUEUED', $orderId);
    }

    private function log(User $user, NotificationChannel $channel, string $code, string $locale, string $status, ?int $orderId, ?string $error = null): void
    {
        NotificationLog::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'channel' => $channel->value,
            'template_code' => $code,
            'locale' => $locale,
            'status' => $status,
            'error' => $error === null ? null : mb_substr($error, 0, 255),
            'created_at' => now(),
        ]);
    }
}

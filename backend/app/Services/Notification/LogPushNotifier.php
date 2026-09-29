<?php

namespace App\Services\Notification;

use App\Services\Contracts\PushNotifier;
use Illuminate\Support\Facades\Log;

/** Development driver: writes pushes to the log. */
class LogPushNotifier implements PushNotifier
{
    public function send(array $tokens, string $title, string $body, array $data = []): array
    {
        Log::info('Push (log driver)', compact('tokens', 'title', 'body', 'data'));

        return [];
    }
}

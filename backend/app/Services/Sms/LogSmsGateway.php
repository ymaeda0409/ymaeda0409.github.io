<?php

namespace App\Services\Sms;

use App\Services\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Development gateway: writes messages to the log instead of sending them.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        Log::info('SMS (log driver)', ['to' => $to, 'message' => $message]);
    }
}

<?php

namespace App\Jobs;

use App\Services\Contracts\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public readonly string $to, public readonly string $message) {}

    public function handle(SmsGateway $gateway): void
    {
        $gateway->send($this->to, $this->message);
    }
}

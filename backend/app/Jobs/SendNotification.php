<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Queued so API requests never wait for FCM / SMS providers. */
class SendNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(
        public readonly int $userId,
        public readonly string $code,
        public readonly array $replace = [],
        public readonly ?int $orderId = null,
    ) {
        // Only send once the transaction that triggered it has committed.
        $this->afterCommit();
    }

    public function handle(NotificationService $notifications): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $notifications->send($user, $this->code, $this->replace, $this->orderId);
        }
    }
}

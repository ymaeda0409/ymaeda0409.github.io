<?php

namespace App\Console\Commands;

use App\Services\Delivery\DeliveryAssignmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('deliveries:dispatch')]
#[Description('Expire unanswered delivery offers and offer waiting orders to available drivers')]
class DispatchDeliveriesCommand extends Command
{
    public function handle(DeliveryAssignmentService $assignments): int
    {
        $this->info(sprintf('%d offer(s) created', $assignments->dispatchPending()));

        return self::SUCCESS;
    }
}

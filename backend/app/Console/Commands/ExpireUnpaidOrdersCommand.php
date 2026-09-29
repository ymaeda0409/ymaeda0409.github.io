<?php

namespace App\Console\Commands;

use App\Services\Payment\PaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire-unpaid')]
#[Description('Cancel mobile-money orders that were not paid in time')]
class ExpireUnpaidOrdersCommand extends Command
{
    public function handle(PaymentService $payments): int
    {
        $this->info(sprintf('%d order(s) cancelled', $payments->expireUnpaid()));

        return self::SUCCESS;
    }
}

<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentStatusChanged
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment) {}
}

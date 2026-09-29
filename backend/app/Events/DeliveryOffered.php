<?php

namespace App\Events;

use App\Models\DeliveryAssignment;
use Illuminate\Foundation\Events\Dispatchable;

class DeliveryOffered
{
    use Dispatchable;

    public function __construct(public readonly DeliveryAssignment $assignment) {}
}

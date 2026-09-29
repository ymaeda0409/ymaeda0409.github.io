<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expired delivery offers move on to the next driver; waiting orders are re-offered.
Schedule::command('deliveries:dispatch')->everyMinute()->withoutOverlapping();

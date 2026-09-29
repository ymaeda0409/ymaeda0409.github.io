<?php

namespace Tests\Unit;

use App\Models\Store;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StoreHoursTest extends TestCase
{
    private function store(?array $hours, bool $accepting = true): Store
    {
        return new Store([
            'timezone' => 'Africa/Blantyre',
            'opening_hours' => $hours,
            'is_active' => true,
            'is_accepting_orders' => $accepting,
        ]);
    }

    public function test_null_hours_means_always_open(): void
    {
        $this->assertTrue($this->store(null)->isOpenAt(Carbon::parse('2026-09-28 01:00', 'UTC')));
    }

    public function test_hours_are_evaluated_in_store_timezone(): void
    {
        $store = $this->store(['mon' => [['08:00', '20:00']]]);

        // 2026-09-28 is a Monday. Africa/Blantyre is UTC+2.
        $this->assertTrue($store->isOpenAt(Carbon::parse('2026-09-28 06:30', 'UTC')));   // 08:30 local
        $this->assertFalse($store->isOpenAt(Carbon::parse('2026-09-28 05:30', 'UTC')));  // 07:30 local
        $this->assertFalse($store->isOpenAt(Carbon::parse('2026-09-28 18:00', 'UTC')));  // 20:00 local
    }

    public function test_overnight_interval(): void
    {
        $store = $this->store(['mon' => [['18:00', '02:00']]]);

        $this->assertTrue($store->isOpenAt(Carbon::parse('2026-09-28 20:00', 'UTC')));  // Mon 22:00
        $this->assertTrue($store->isOpenAt(Carbon::parse('2026-09-28 23:00', 'UTC')));  // Tue 01:00
        $this->assertFalse($store->isOpenAt(Carbon::parse('2026-09-29 01:00', 'UTC'))); // Tue 03:00
    }

    public function test_paused_store_is_closed(): void
    {
        $this->assertFalse($this->store(null, accepting: false)->isOpenAt(now()));
    }
}

<?php

namespace Tests;

use App\Services\LocaleService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['bento.otp.test_mode' => true, 'bento.otp.test_code' => '123456']);
    }

    protected function flushLocales(): void
    {
        app(LocaleService::class)->flush();
    }
}

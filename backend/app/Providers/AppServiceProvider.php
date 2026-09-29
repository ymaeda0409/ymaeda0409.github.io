<?php

namespace App\Providers;

use App\Services\Contracts\PaymentGatewayInterface;
use App\Services\Contracts\PushNotifier;
use App\Services\Contracts\SmsGateway;
use App\Services\LocaleService;
use App\Services\Notification\FcmPushNotifier;
use App\Services\Notification\LogPushNotifier;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\PayChanguGateway;
use App\Services\Sms\LogSmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LocaleService::class);

        $this->app->bind(PaymentGatewayInterface::class, fn () => match (config('bento.payment.gateway')) {
            'fake' => new FakePaymentGateway,
            'paychangu' => new PayChanguGateway,
            default => throw new InvalidArgumentException('Unsupported payment gateway: '.config('bento.payment.gateway')),
        });

        $this->app->bind(PushNotifier::class, fn () => match (config('bento.push.driver')) {
            'log' => new LogPushNotifier,
            'fcm' => FcmPushNotifier::fromConfig(),
            default => throw new InvalidArgumentException('Unsupported push driver: '.config('bento.push.driver')),
        });

        $this->app->bind(SmsGateway::class, fn () => match (config('bento.sms.driver')) {
            'log' => new LogSmsGateway,
            default => throw new InvalidArgumentException('Unsupported SMS driver: '.config('bento.sms.driver')),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}

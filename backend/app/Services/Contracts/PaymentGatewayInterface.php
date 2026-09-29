<?php

namespace App\Services\Contracts;

use App\Models\Payment;
use App\Services\Payment\GatewayResult;
use App\Services\Payment\WebhookEvent;
use Illuminate\Http\Request;

/**
 * Mobile money / card provider. Implementations: FakePaymentGateway (dev/test),
 * PayChanguGateway (Airtel Money, TNM Mpamba). Selected with PAYMENT_GATEWAY.
 */
interface PaymentGatewayInterface
{
    public function name(): string;

    /** Starts a charge (e.g. USSD push to the customer's phone). Usually returns PENDING. */
    public function pay(Payment $payment): GatewayResult;

    /** Asks the provider for the current status of a charge. */
    public function verify(Payment $payment): GatewayResult;

    public function refund(Payment $payment): GatewayResult;

    /** Validates the signature and extracts our payment reference; null when the signature is invalid. */
    public function parseWebhook(Request $request): ?WebhookEvent;
}

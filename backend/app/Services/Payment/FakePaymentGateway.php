<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;

/**
 * Development gateway simulating mobile money:
 *  - phone ending in 0000 → declined immediately (INSUFFICIENT_FUNDS)
 *  - phone ending in 9999 → never approved (stays PENDING, e.g. customer ignores the prompt)
 *  - otherwise → PENDING on pay, PAID on the first verify (customer approved on the phone)
 * Webhooks are signed with HMAC-SHA256 (header X-Fake-Signature).
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'fake';
    }

    public function pay(Payment $payment): GatewayResult
    {
        if (str_ends_with((string) $payment->phone, '0000')) {
            return new GatewayResult(PaymentStatus::FAILED, 'FAKE-'.$payment->reference, 'INSUFFICIENT_FUNDS');
        }

        return new GatewayResult(PaymentStatus::PENDING, 'FAKE-'.$payment->reference);
    }

    public function verify(Payment $payment): GatewayResult
    {
        if (str_ends_with((string) $payment->phone, '9999')) {
            return new GatewayResult(PaymentStatus::PENDING, $payment->gateway_reference);
        }

        return new GatewayResult(PaymentStatus::PAID, $payment->gateway_reference);
    }

    public function refund(Payment $payment): GatewayResult
    {
        return new GatewayResult(PaymentStatus::REFUNDED, $payment->gateway_reference);
    }

    public function parseWebhook(Request $request): ?WebhookEvent
    {
        $expected = hash_hmac('sha256', $request->getContent(), (string) config('bento.payment.fake.webhook_secret'));
        if (! hash_equals($expected, (string) $request->header('X-Fake-Signature'))) {
            return null;
        }

        $reference = $request->json('reference');

        return is_string($reference) ? new WebhookEvent($reference, $request->json()->all()) : null;
    }
}

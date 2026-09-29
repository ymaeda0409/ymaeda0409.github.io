<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayChangu mobile money (Airtel Money / TNM Mpamba) adapter.
 *
 * Endpoint paths and field names follow PayChangu's public mobile-money API; confirm them
 * against the current PayChangu documentation and a sandbox account before going live.
 * Webhooks are never trusted on their own: PaymentService re-verifies every event.
 */
class PayChanguGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'paychangu';
    }

    public function pay(Payment $payment): GatewayResult
    {
        $operator = config('bento.payment.paychangu.operators.'.$payment->method->value);

        return $this->call(fn () => $this->http()->post('/mobile-money/payments/initialize', [
            'mobile' => $this->localNumber((string) $payment->phone),
            'mobile_money_operator_ref_id' => $operator,
            'amount' => $this->majorUnits($payment),
            'charge_id' => $payment->reference,
        ]), $payment);
    }

    public function verify(Payment $payment): GatewayResult
    {
        return $this->call(fn () => $this->http()->get("/mobile-money/payments/{$payment->reference}/verify"), $payment);
    }

    /**
     * Mobile money reversals are handled by the operator/merchant dashboard; the order keeps
     * PAID and an audit entry so staff can refund manually.
     */
    public function refund(Payment $payment): GatewayResult
    {
        return new GatewayResult(PaymentStatus::PAID, $payment->gateway_reference, 'REFUND_MANUAL');
    }

    public function parseWebhook(Request $request): ?WebhookEvent
    {
        $secret = (string) config('bento.payment.paychangu.webhook_secret');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if ($secret === '' || ! hash_equals($expected, (string) $request->header('Signature'))) {
            return null;
        }

        $reference = $request->json('charge_id') ?? $request->json('data.charge_id');

        return is_string($reference) ? new WebhookEvent($reference, $request->json()->all()) : null;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('bento.payment.paychangu.base_url'))
            ->withToken((string) config('bento.payment.paychangu.secret_key'))
            ->acceptJson()
            ->timeout(20);
    }

    private function call(callable $request, Payment $payment): GatewayResult
    {
        try {
            $response = $request();
        } catch (ConnectionException $e) {
            Log::warning('PayChangu unreachable', ['payment' => $payment->reference, 'error' => $e->getMessage()]);

            return new GatewayResult(PaymentStatus::PENDING, $payment->gateway_reference, 'GATEWAY_UNREACHABLE');
        }

        $data = (array) $response->json('data', []);
        $status = strtolower((string) ($data['status'] ?? $response->json('status')));

        if ($response->failed()) {
            Log::warning('PayChangu error', ['payment' => $payment->reference, 'status' => $response->status()]);

            return new GatewayResult(PaymentStatus::FAILED, $payment->gateway_reference, 'GATEWAY_ERROR', (array) $response->json());
        }

        return new GatewayResult(
            match ($status) {
                'success', 'successful', 'paid' => PaymentStatus::PAID,
                'failed', 'cancelled', 'canceled' => PaymentStatus::FAILED,
                default => PaymentStatus::PENDING,
            },
            (string) ($data['ref_id'] ?? $data['charge_id'] ?? $payment->gateway_reference ?? ''),
            $status === 'failed' ? 'DECLINED' : null,
            (array) $response->json(),
        );
    }

    /** +265991234567 → 0991234567 (local format expected by Malawian operators). */
    private function localNumber(string $e164): string
    {
        return '0'.substr(preg_replace('/\D/', '', $e164), 3);
    }

    private function majorUnits(Payment $payment): int
    {
        $exponent = (int) config("bento.currencies.{$payment->currency}.exponent", 2);

        return intdiv($payment->amount, 10 ** $exponent);
    }
}

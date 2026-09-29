<?php

namespace App\Services\Payment;

use App\Enums\ErrorCode;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentStatusChanged;
use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AuditLogger;
use App\Services\Contracts\PaymentGatewayInterface;
use App\Services\Order\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mobile money payments for orders. Cash on delivery never goes through here
 * (it is marked PAID when the rider confirms the PIN).
 */
class PaymentService
{
    public const REASON_PAYMENT_TIMEOUT = 'PAYMENT_TIMEOUT';

    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly OrderStatusService $statuses,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Starts (or resumes) the payment of an order. Idempotent while a charge is pending.
     */
    public function start(Order $order, ?string $phone): Payment
    {
        if (! $order->payment_method->isPrepaid()) {
            throw ApiException::of(ErrorCode::PAYMENT_NOT_REQUIRED);
        }
        if ($order->payment_status === PaymentStatus::PAID) {
            throw ApiException::of(ErrorCode::CONFLICT);
        }
        if ($order->status !== OrderStatus::NEW) {
            throw ApiException::of(ErrorCode::INVALID_STATUS_TRANSITION);
        }

        $pending = $order->payments()->where('status', PaymentStatus::PENDING)->latest('id')->first();
        if ($pending) {
            return $this->refresh($pending);
        }

        $payment = $order->payments()->create([
            'method' => $order->payment_method,
            'gateway' => $this->gateway->name(),
            'status' => PaymentStatus::PENDING,
            'amount' => $order->total,
            'currency' => $order->currency,
            'phone' => $phone ?? $order->customer->phone,
            'reference' => 'MB'.strtoupper(Str::random(20)),
        ]);

        return $this->apply($payment, $this->gateway->pay($payment));
    }

    /** Polls the provider while the charge is pending (the app calls this every few seconds). */
    public function refresh(Payment $payment): Payment
    {
        return $payment->status === PaymentStatus::PENDING
            ? $this->apply($payment, $this->gateway->verify($payment))
            : $payment;
    }

    /**
     * Provider callback. The payload only tells us *which* payment changed; the status is
     * always re-read from the provider so a forged or replayed webhook cannot mark it paid.
     */
    public function handleWebhook(Request $request): ?Payment
    {
        $event = $this->gateway->parseWebhook($request);
        if ($event === null) {
            throw ApiException::of(ErrorCode::INVALID_SIGNATURE);
        }

        $payment = Payment::query()->where('reference', $event->reference)->first();
        if (! $payment) {
            Log::warning('Webhook for unknown payment', ['reference' => $event->reference]);

            return null;
        }

        return $this->refresh($payment);
    }

    /** Refunds prepaid orders that were cancelled after payment. */
    public function refundFor(Order $order): void
    {
        foreach ($order->payments()->where('status', PaymentStatus::PAID)->get() as $payment) {
            $result = $this->gateway->refund($payment);
            if ($result->status === PaymentStatus::REFUNDED) {
                $this->apply($payment, $result);
            } else {
                // e.g. operator reversals done by staff in the provider dashboard.
                $this->audit->log('payment.refund_pending', $order, null, [
                    'payment_id' => $payment->id,
                    'reason' => $result->failureCode,
                ]);
            }
        }
    }

    /** Cancels mobile-money orders that were never paid (scheduled every minute). */
    public function expireUnpaid(): int
    {
        $cutoff = now()->subMinutes((int) config('bento.payment.unpaid_timeout_minutes'));
        $expired = 0;

        Order::query()
            ->where('status', OrderStatus::NEW)
            ->where('payment_method', '!=', 'CASH')
            ->where('payment_status', '!=', PaymentStatus::PAID)
            ->where('ordered_at', '<', $cutoff)
            ->each(function (Order $order) use (&$expired) {
                try {
                    $this->statuses->transition($order, OrderStatus::CANCELLED, null, self::REASON_PAYMENT_TIMEOUT);
                    $expired++;
                } catch (ApiException) {
                    // Changed meanwhile (e.g. just paid and accepted).
                }
            });

        return $expired;
    }

    private function apply(Payment $payment, GatewayResult $result): Payment
    {
        $changed = DB::transaction(function () use ($payment, $result) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Final states never go back (duplicate / out-of-order callbacks).
            if ($locked->isFinal() && $result->status !== PaymentStatus::REFUNDED) {
                return [$locked, false];
            }
            if ($locked->status === $result->status && $locked->gateway_reference === $result->gatewayReference) {
                return [$locked, false];
            }

            $locked->fill([
                'status' => $result->status,
                'gateway_reference' => $result->gatewayReference ?: $locked->gateway_reference,
                'failure_code' => $result->failureCode,
                'payload' => $result->payload ?: $locked->payload,
            ]);
            if ($result->status === PaymentStatus::PAID) {
                $locked->paid_at = now();
            }
            if ($result->status === PaymentStatus::REFUNDED) {
                $locked->refunded_at = now();
            }
            $locked->save();

            if (in_array($result->status, [PaymentStatus::PAID, PaymentStatus::FAILED, PaymentStatus::REFUNDED], true)) {
                Order::query()->whereKey($locked->order_id)->lockForUpdate()->first()
                    ?->forceFill(['payment_status' => $result->status])->save();
            }

            return [$locked, true];
        });

        [$payment, $wasChanged] = $changed;
        if ($wasChanged) {
            PaymentStatusChanged::dispatch($payment);
        }

        return $payment;
    }
}

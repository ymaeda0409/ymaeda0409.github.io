<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use App\Support\ApiResponse;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use NormalizesPhone;

    public function __construct(private readonly PaymentService $payments) {}

    /** POST /payments { order_id, phone? } — starts the mobile money charge. */
    public function store(Request $request): JsonResponse
    {
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => PhoneNumber::normalize($request->input('phone')) ?? $request->input('phone')]);
        }
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'phone' => ['nullable', 'string', self::phoneRule()],
        ]);
        $order = Order::query()->where('customer_id', $request->user()->id)->findOrFail($data['order_id']);

        return ApiResponse::success(new PaymentResource($this->payments->start($order, $data['phone'] ?? null)));
    }

    /** GET /payments/{id} — current status (re-checked with the provider while pending). */
    public function show(Request $request, int $payment): JsonResponse
    {
        $model = Payment::query()
            ->whereHas('order', fn ($q) => $q->where('customer_id', $request->user()->id))
            ->findOrFail($payment);

        return ApiResponse::success(new PaymentResource($this->payments->refresh($model)));
    }

    /** POST /payments/webhook — provider callback (signature checked by the gateway). */
    public function webhook(Request $request): JsonResponse
    {
        $payment = $this->payments->handleWebhook($request);

        return ApiResponse::success(['received' => true, 'status' => $payment?->status->value]);
    }
}

<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Notification\FcmPushNotifier;
use App\Services\Payment\PayChanguGateway;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayAdaptersTest extends TestCase
{
    private function payment(): Payment
    {
        return new Payment([
            'method' => PaymentMethod::AIRTEL_MONEY,
            'amount' => 950000,
            'currency' => 'MWK',
            'phone' => '+265991234567',
            'reference' => 'MBTESTREF',
        ]);
    }

    public function test_paychangu_charge_uses_local_number_major_units_and_operator(): void
    {
        config(['bento.payment.paychangu' => [
            'base_url' => 'https://paychangu.test', 'secret_key' => 'sk', 'webhook_secret' => 'wh',
            'operators' => ['AIRTEL_MONEY' => 'airtel-ref', 'TNM_MPAMBA' => 'tnm-ref'],
        ]]);
        Http::fake(['paychangu.test/*' => Http::response(['status' => 'success', 'data' => ['status' => 'pending', 'ref_id' => 'PC-1']])]);

        $result = (new PayChanguGateway)->pay($this->payment());

        $this->assertSame(PaymentStatus::PENDING, $result->status);
        $this->assertSame('PC-1', $result->gatewayReference);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://paychangu.test/mobile-money/payments/initialize'
            && $r['mobile'] === '0991234567'
            && $r['amount'] === 9500
            && $r['mobile_money_operator_ref_id'] === 'airtel-ref'
            && $r['charge_id'] === 'MBTESTREF'
            && $r->hasHeader('Authorization', 'Bearer sk'));
    }

    public function test_paychangu_verify_maps_statuses_and_webhook_signature(): void
    {
        config(['bento.payment.paychangu.base_url' => 'https://paychangu.test', 'bento.payment.paychangu.webhook_secret' => 'wh']);
        Http::fake(['paychangu.test/*' => Http::response(['data' => ['status' => 'success']])]);

        $this->assertSame(PaymentStatus::PAID, (new PayChanguGateway)->verify($this->payment())->status);

        $body = json_encode(['charge_id' => 'MBTESTREF']);
        $good = Request::create('/', 'POST', server: ['HTTP_SIGNATURE' => hash_hmac('sha256', $body, 'wh'), 'CONTENT_TYPE' => 'application/json'], content: $body);
        $bad = Request::create('/', 'POST', server: ['HTTP_SIGNATURE' => 'x', 'CONTENT_TYPE' => 'application/json'], content: $body);

        $this->assertSame('MBTESTREF', (new PayChanguGateway)->parseWebhook($good)?->reference);
        $this->assertNull((new PayChanguGateway)->parseWebhook($bad));
    }

    public function test_fcm_signs_a_service_account_jwt_and_reports_dead_tokens(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test']),
            'fcm.googleapis.com/*/messages:send' => Http::sequence()
                ->push(['name' => 'ok'])
                ->push(['error' => ['status' => 'UNREGISTERED']], 404),
        ]);

        $fcm = new FcmPushNotifier('bento-test', ['client_email' => 'svc@bento.iam', 'private_key' => $pem, 'token_uri' => 'https://oauth2.googleapis.com/token']);
        $invalid = $fcm->send(['good', 'dead'], 'Title', 'Body', ['code' => 'ORDER_CONFIRMED', 'order_id' => '7']);

        $this->assertSame(['dead'], $invalid);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'oauth2')
            && $r['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && count(explode('.', $r['assertion'])) === 3);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'projects/bento-test/messages:send')
            && $r->hasHeader('Authorization', 'Bearer ya29.test')
            && $r['message']['notification']['title'] === 'Title'
            && $r['message']['data']['code'] === 'ORDER_CONFIRMED');
    }
}

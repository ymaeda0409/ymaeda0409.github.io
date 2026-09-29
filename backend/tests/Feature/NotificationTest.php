<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\UserRole;
use App\Jobs\SendSms;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Contracts\PushNotifier;
use App\Services\Notification\NotificationService;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\FeatureTestCase;

class NotificationTest extends FeatureTestCase
{
    /** @var list<array{tokens: list<string>, title: string, body: string, data: array}> */
    private array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NotificationTemplateSeeder::class);
        $this->app->instance(PushNotifier::class, new class($this->pushes) implements PushNotifier
        {
            public function __construct(private array &$sent) {}

            public function send(array $tokens, string $title, string $body, array $data = []): array
            {
                $this->sent[] = compact('tokens', 'title', 'body', 'data');

                return array_values(array_filter($tokens, fn ($t) => str_starts_with($t, 'dead-')));
            }
        });
    }

    private function customerWithDevice(string $language): User
    {
        $user = User::factory()->create(['preferred_language' => $language]);
        $user->deviceTokens()->create(['token' => 'tok-'.$user->id, 'platform' => 'android', 'app' => 'customer']);

        return $user;
    }

    public function test_order_updates_are_pushed_in_each_customers_language(): void
    {
        ['store' => $store] = $this->createTenant();

        foreach (['en' => 'Order confirmed', 'ny' => 'Oda yalandiridwa', 'ja' => 'ご注文を受け付けました'] as $language => $title) {
            $order = $this->placeOrder($store);
            $order->customer->update(['preferred_language' => $language]);
            $order->customer->deviceTokens()->create(['token' => "tok-{$language}", 'platform' => 'android', 'app' => 'customer']);
            $this->pushes = [];

            $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);
            $this->postJson("/api/admin/orders/{$order->id}/accept")->assertOk();

            $this->assertSame($title, $this->pushes[0]['title'], $language);
            $this->assertStringContainsString($order->order_number, $this->pushes[0]['body']);
            $this->assertSame(['code' => 'ORDER_CONFIRMED', 'order_id' => (string) $order->id], $this->pushes[0]['data']);
        }
    }

    public function test_important_updates_also_go_by_sms_with_the_pin(): void
    {
        Queue::fake([SendSms::class]);
        ['store' => $store] = $this->createTenant();
        $order = $this->placeOrder($store);
        $order->customer->update(['preferred_language' => 'ny']);
        $driver = Driver::factory()->onlineAt($store->latitude, $store->longitude)->create(['franchise_id' => $store->franchise_id]);
        $this->makeReady($order);

        Sanctum::actingAs($driver->user()->first());
        $this->postJson("/api/driver/deliveries/{$order->id}/accept")->assertOk();
        $this->postJson("/api/driver/deliveries/{$order->id}/pickup")->assertOk();

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->to === $order->customer->phone
            && str_contains($job->message, 'ili pa njira')
            && str_contains($job->message, $order->delivery_pin));

        // Non-important updates (e.g. COOKING) are push only.
        $this->assertSame(0, NotificationLog::where('template_code', 'ORDER_COOKING')->where('channel', 'SMS')->count());
    }

    public function test_long_unicode_sms_falls_back_to_english(): void
    {
        Queue::fake([SendSms::class]);
        config(['bento.sms.max_segments' => 1]);
        $template = NotificationTemplate::where('code', 'ORDER_CONFIRMED')->where('channel', NotificationChannel::SMS)->first();
        $template->translations()->where('locale', 'ja')->update(['body' => str_repeat('注文を受け付けました。', 10)]);
        $user = $this->customerWithDevice('ja');

        app(NotificationService::class)->send($user, 'ORDER_CONFIRMED', ['order_number' => 'X-1', 'store' => 'S']);

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->message === 'Malawi Bento: Order X-1 confirmed.');
    }

    public function test_admin_edited_template_wins_over_shipped_text(): void
    {
        $this->actingAsRole(UserRole::SUPER_ADMIN);
        $template = NotificationTemplate::where('code', 'ORDER_DELIVERED')->where('channel', 'PUSH')->first();

        $this->putJson("/api/admin/notification-templates/{$template->id}", [
            'translations' => ['ny' => ['title' => 'Zafika!', 'body' => 'Oda :order_number yafika kwanu.']],
        ])->assertOk()->assertJsonPath('data.translations.ny.title', 'Zafika!');

        $user = $this->customerWithDevice('ny');
        app(NotificationService::class)->send($user, 'ORDER_DELIVERED', ['order_number' => 'A-7']);

        $this->assertSame('Zafika!', end($this->pushes)['title']);
        $this->assertSame('Oda A-7 yafika kwanu.', end($this->pushes)['body']);
    }

    public function test_missing_translation_falls_back_to_english_and_disabled_template_is_skipped(): void
    {
        $template = NotificationTemplate::where('code', 'ORDER_CANCELLED')->where('channel', 'PUSH')->first();
        $template->translations()->where('locale', 'ja')->delete();
        $user = $this->customerWithDevice('ja');
        $service = app(NotificationService::class);

        $service->send($user, 'ORDER_CANCELLED', ['order_number' => 'B-1']);
        $this->assertSame('Order cancelled', end($this->pushes)['title']);

        $template->update(['is_active' => false]);
        $count = count($this->pushes);
        $service->send($user, 'ORDER_CANCELLED', ['order_number' => 'B-2']);
        $this->assertCount($count, $this->pushes);
    }

    public function test_driver_gets_a_push_for_a_new_offer(): void
    {
        ['store' => $store] = $this->createTenant();
        $driver = Driver::factory()->onlineAt($store->latitude, $store->longitude)->create(['franchise_id' => $store->franchise_id]);
        $driver->user->update(['preferred_language' => 'ja']);
        $driver->user->deviceTokens()->create(['token' => 'driver-tok', 'platform' => 'android', 'app' => 'driver']);

        $this->makeReady($this->placeOrder($store));

        $push = collect($this->pushes)->firstWhere('tokens', ['driver-tok']);
        $this->assertSame('新しい配達依頼', $push['title']);
        $this->assertSame('DRIVER_NEW_DELIVERY', $push['data']['code']);
    }

    public function test_device_registration_and_invalid_token_cleanup(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/devices', ['token' => 'dead-123', 'platform' => 'android', 'app' => 'customer'])->assertOk();
        $this->postJson('/api/devices', ['token' => 'tok-live', 'platform' => 'ios', 'app' => 'customer'])->assertOk();

        app(NotificationService::class)->send($user, 'ORDER_DELIVERED', ['order_number' => 'C-1']);

        $this->assertSame(['tok-live'], $user->deviceTokens()->pluck('token')->all());

        $this->deleteJson('/api/devices', ['token' => 'tok-live'])->assertOk();
        $this->assertSame(0, $user->deviceTokens()->count());
    }

    public function test_only_translation_managers_edit_templates(): void
    {
        ['franchise' => $franchise] = $this->createTenant();
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $franchise);

        $this->getJson('/api/admin/notification-templates')->assertStatus(403);
    }
}

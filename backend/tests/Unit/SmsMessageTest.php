<?php

namespace Tests\Unit;

use App\Services\Sms\SmsMessage;
use Tests\TestCase;

class SmsMessageTest extends TestCase
{
    public function test_gsm7_segments(): void
    {
        $this->assertTrue(SmsMessage::isGsm7('Malawi Bento code: 123456'));
        $this->assertSame(1, SmsMessage::segments(str_repeat('a', 160)));
        $this->assertSame(2, SmsMessage::segments(str_repeat('a', 161)));
    }

    public function test_ucs2_segments_for_japanese(): void
    {
        $this->assertFalse(SmsMessage::isGsm7('認証コード'));
        $this->assertSame(1, SmsMessage::segments(str_repeat('あ', 70)));
        $this->assertSame(2, SmsMessage::segments(str_repeat('あ', 71)));
    }

    public function test_all_otp_messages_fit_in_one_segment(): void
    {
        foreach (['en', 'ny', 'ja'] as $locale) {
            $text = __('sms.otp', ['code' => '123456', 'minutes' => 5], $locale);
            $this->assertSame(1, SmsMessage::segments($text), "OTP SMS for {$locale} exceeds one segment");
        }
    }
}

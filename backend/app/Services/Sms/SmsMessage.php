<?php

namespace App\Services\Sms;

/**
 * Segment calculation for SMS cost control (GSM-7: 160/153 chars, UCS-2: 70/67 chars).
 */
class SmsMessage
{
    private const GSM7_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const GSM7_EXTENDED = "^{}\\[~]|€\f";

    public static function isGsm7(string $text): bool
    {
        foreach (mb_str_split($text) as $char) {
            if (! str_contains(self::GSM7_BASIC, $char) && ! str_contains(self::GSM7_EXTENDED, $char)) {
                return false;
            }
        }

        return true;
    }

    public static function segments(string $text): int
    {
        if (self::isGsm7($text)) {
            $length = 0;
            foreach (mb_str_split($text) as $char) {
                $length += str_contains(self::GSM7_EXTENDED, $char) ? 2 : 1;
            }

            return $length <= 160 ? 1 : (int) ceil($length / 153);
        }

        $length = mb_strlen($text);

        return $length <= 70 ? 1 : (int) ceil($length / 67);
    }
}

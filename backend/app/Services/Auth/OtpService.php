<?php

namespace App\Services\Auth;

use App\Enums\ErrorCode;
use App\Enums\OtpPurpose;
use App\Exceptions\ApiException;
use App\Models\OtpCode;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(private readonly SmsService $sms) {}

    /**
     * Fixed-code test mode is never honoured in production, whatever the .env says.
     */
    public function isTestMode(): bool
    {
        return (bool) config('bento.otp.test_mode') && ! App::environment('production');
    }

    public function send(string $phone, ?string $ip = null, OtpPurpose $purpose = OtpPurpose::LOGIN): OtpCode
    {
        $latest = $this->latest($phone, $purpose);
        $resendSeconds = (int) config('bento.otp.resend_seconds');

        if ($latest && $latest->created_at->diffInSeconds(now()) < $resendSeconds) {
            throw ApiException::of(ErrorCode::TOO_MANY_REQUESTS);
        }

        $code = $this->generateCode();

        $otp = OtpCode::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addSeconds((int) config('bento.otp.ttl_seconds')),
            'ip_address' => $ip,
        ]);

        $this->sms->sendTranslated($phone, 'otp', [
            'code' => $code,
            'minutes' => intdiv((int) config('bento.otp.ttl_seconds'), 60),
        ], App::getLocale());

        return $otp;
    }

    /**
     * Consumes the latest OTP for the phone if the code matches.
     */
    public function verify(string $phone, string $code, OtpPurpose $purpose = OtpPurpose::LOGIN): void
    {
        $otp = $this->latest($phone, $purpose);

        if (! $otp || $otp->consumed_at || $otp->expires_at->isPast()
            || $otp->attempts >= (int) config('bento.otp.max_attempts')) {
            throw ApiException::of(ErrorCode::OTP_EXPIRED);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ApiException::of(ErrorCode::OTP_INVALID);
        }

        $otp->forceFill(['consumed_at' => now()])->save();
    }

    private function latest(string $phone, OtpPurpose $purpose): ?OtpCode
    {
        return OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();
    }

    private function generateCode(): string
    {
        if ($this->isTestMode()) {
            return (string) config('bento.otp.test_code');
        }

        $length = (int) config('bento.otp.length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}

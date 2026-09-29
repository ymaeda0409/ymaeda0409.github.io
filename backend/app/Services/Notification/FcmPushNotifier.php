<?php

namespace App\Services\Notification;

use App\Services\Contracts\PushNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Firebase Cloud Messaging HTTP v1 with a service-account OAuth token (no SDK needed).
 */
class FcmPushNotifier implements PushNotifier
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(private readonly string $projectId, private readonly array $serviceAccount) {}

    public static function fromConfig(): self
    {
        $path = (string) config('bento.firebase.credentials');
        $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (! is_array($json) || empty($json['private_key']) || empty($json['client_email'])) {
            throw new RuntimeException('FIREBASE_CREDENTIALS must point to a service-account JSON file.');
        }

        return new self((string) (config('bento.firebase.project_id') ?: $json['project_id']), $json);
    }

    public function send(array $tokens, string $title, string $body, array $data = []): array
    {
        $invalid = [];
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        foreach ($tokens as $token) {
            $response = Http::withToken($this->accessToken())->timeout(10)->post($url, [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map('strval', $data),
                    'android' => ['priority' => 'high'],
                ],
            ]);

            if ($response->successful()) {
                continue;
            }
            $status = $response->json('error.status');
            if ($response->status() === 404 || in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                $invalid[] = $token;
            } else {
                Log::warning('FCM send failed', ['status' => $response->status(), 'error' => $status]);
            }
        }

        return $invalid;
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm.access_token.'.md5($this->serviceAccount['client_email']), 3000, function () {
            $now = time();
            $jwt = $this->jwt([
                'iss' => $this->serviceAccount['client_email'],
                'scope' => self::SCOPE,
                'aud' => $this->serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);

            $response = Http::asForm()->post($this->serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->throw();

            return (string) $response->json('access_token');
        });
    }

    private function jwt(array $claims): string
    {
        $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');
        $input = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims);
        if (! openssl_sign($input, $signature, $this->serviceAccount['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the FCM service-account JWT.');
        }

        return $input.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }
}

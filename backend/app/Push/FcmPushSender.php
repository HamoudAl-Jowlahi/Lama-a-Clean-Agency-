<?php

namespace App\Push;

use App\Contracts\PushSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Firebase Cloud Messaging — HTTP v1 API، بدون مكتبات خارجية.
 *
 * المصادقة: Service Account (JSON من Firebase Console) → JWT موقّع RS256 →
 * access token من Google (يُخزن مؤقتاً قرابة ساعة). ملف الاعتماد خارج Git.
 */
class FcmPushSender implements PushSender
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_CACHE_KEY = 'push.fcm.access_token';

    /** @param array{credentials: ?string, credentials_base64?: ?string, project_id: ?string, timeout: int} $config */
    public function __construct(private array $config) {}

    public function send(array $tokens, string $title, string $body, array $data = []): array
    {
        if ($tokens === []) {
            return [];
        }

        $credentials = $this->credentials();
        $projectId = $this->config['project_id'] ?: $credentials['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $accessToken = $this->accessToken($credentials);

        $invalid = [];
        foreach ($tokens as $token) {
            $response = Http::withToken($accessToken)
                ->timeout($this->config['timeout'] ?? 10)
                ->post($url, ['message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map('strval', $data),
                    'android' => ['priority' => 'high', 'notification' => ['sound' => 'default']],
                    'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                ]]);

            if ($response->successful()) {
                continue;
            }

            // الجهاز لم يعد مسجلاً → نحذف الرمز. أخطاء أخرى تُسجل ولا توقف بقية الأجهزة.
            $code = $response->json('error.details.0.errorCode') ?? $response->json('error.status');
            if ($response->status() === 404 || in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                $invalid[] = $token;
            } else {
                Log::warning('[push] FCM send failed', ['status' => $response->status(), 'code' => $code]);
            }
        }

        return $invalid;
    }

    /** @return array{project_id: string, client_email: string, private_key: string, token_uri: string} */
    private function credentials(): array
    {
        // الاستضافة بدون ملفات (Render وغيره): محتوى JSON مشفّر Base64 في متغير بيئة
        if ($encoded = $this->config['credentials_base64'] ?? null) {
            $json = json_decode((string) base64_decode($encoded, true), true);
        } else {
            $path = $this->config['credentials'] ?? null;
            if (! $path || ! is_readable($path)) {
                throw new RuntimeException('FCM credentials file is missing. Set FIREBASE_CREDENTIALS (path) or FIREBASE_CREDENTIALS_BASE64 in .env.');
            }
            $json = json_decode((string) file_get_contents($path), true);
        }
        foreach (['project_id', 'client_email', 'private_key'] as $key) {
            if (empty($json[$key])) {
                throw new RuntimeException("FCM credentials file is missing [{$key}].");
            }
        }

        return $json + ['token_uri' => 'https://oauth2.googleapis.com/token'];
    }

    private function accessToken(array $credentials): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(55), function () use ($credentials) {
            $response = Http::asForm()->timeout($this->config['timeout'] ?? 10)->post($credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->signedJwt($credentials),
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('FCM auth failed: HTTP '.$response->status());
            }

            return $response->json('access_token');
        });
    }

    private function signedJwt(array $credentials): string
    {
        $now = time();
        $segments = [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => $credentials['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ])),
        ];

        $signature = '';
        if (! openssl_sign(implode('.', $segments), $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('FCM: could not sign JWT (invalid private key).');
        }
        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

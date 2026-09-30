<?php

namespace Tests\Feature\Notifications;

use App\Push\FcmPushSender;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/** مرسل FCM (HTTP v1) مع خوادم Google وهمية — بدون أي حساب حقيقي. */
class FcmPushSenderTest extends TestCase
{
    private string $credentialsPath;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $config = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        if (is_file($cnf = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf')) {
            $config['config'] = $cnf; // XAMPP على Windows
        }
        $key = openssl_pkey_new($config);
        openssl_pkey_export($key, $privatePem, null, $config);
        $this->publicKey = openssl_pkey_get_details($key)['key'];

        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'fcm').'.json';
        file_put_contents($this->credentialsPath, json_encode([
            'project_id' => 'lamaa-test',
            'client_email' => 'push@lamaa-test.iam.gserviceaccount.com',
            'private_key' => $privatePem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);
        parent::tearDown();
    }

    public function test_sends_with_a_signed_token_and_reports_unregistered_devices(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::sequence()
                ->push(['name' => 'projects/lamaa-test/messages/1'])
                ->push(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
        ]);

        $sender = new FcmPushSender(['credentials' => $this->credentialsPath, 'project_id' => null, 'timeout' => 5]);
        $invalid = $sender->send(['live-token', 'dead-token'], 'تم تأكيد طلبك', 'نص', ['event' => 'booking.confirmed', 'subject_id' => 7]);

        $this->assertSame(['dead-token'], $invalid);

        // طلب الـ access token: JWT موقّع بمفتاح الـ Service Account
        Http::assertSent(function (Request $request) {
            if ($request->url() !== 'https://oauth2.googleapis.com/token') {
                return false;
            }
            [$header, $claims, $signature] = explode('.', $request['assertion']);
            $decoded = json_decode(base64_decode(strtr($claims, '-_', '+/')), true);
            $valid = openssl_verify("{$header}.{$claims}", base64_decode(strtr($signature, '-_', '+/')), $this->publicKey, OPENSSL_ALGO_SHA256);

            return $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
                && $decoded['iss'] === 'push@lamaa-test.iam.gserviceaccount.com'
                && $decoded['scope'] === 'https://www.googleapis.com/auth/firebase.messaging'
                && $valid === 1;
        });

        // رسالة FCM: التوكن + النص + البيانات نصية
        Http::assertSent(fn (Request $request) => $request->url() === 'https://fcm.googleapis.com/v1/projects/lamaa-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer ya29.test')
            && $request['message']['token'] === 'live-token'
            && $request['message']['notification']['title'] === 'تم تأكيد طلبك'
            && $request['message']['data'] === ['event' => 'booking.confirmed', 'subject_id' => '7']);

        // الـ access token يُعاد استخدامه (طلب مصادقة واحد لرسالتين)
        Http::assertSentCount(3);
    }

    public function test_missing_credentials_fail_clearly(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FIREBASE_CREDENTIALS');

        (new FcmPushSender(['credentials' => null, 'project_id' => null, 'timeout' => 5]))->send(['t'], 'x', 'y');
    }
}

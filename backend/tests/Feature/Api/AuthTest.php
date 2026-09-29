<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;

class AuthTest extends ApiTestCase
{
    public function test_customer_can_register_and_gets_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'سارة أحمد',
            'phone' => '0551234567',
            'password' => 'secret-pass',
            'role' => 'worker', // يُتجاهل: الدور يحدده الخادم
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.role.value', 'customer')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'phone']]]);

        $user = User::where('phone', '0551234567')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNotNull($user->customer()->first());
    }

    public function test_register_validates_input_in_arabic(): void
    {
        $this->postJson('/api/v1/auth/register', ['phone' => 'abc'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('errors.name.0', 'الاسم مطلوب.')
            ->assertJsonPath('errors.phone.0', 'صيغة رقم الجوال غير صحيحة.');
    }

    public function test_login_with_valid_and_invalid_credentials(): void
    {
        $user = User::factory()->create(['phone' => '0550000001', 'password' => 'secret-pass']);

        $this->postJson('/api/v1/auth/login', ['phone' => '0550000001', 'password' => 'secret-pass'])
            ->assertOk()->assertJsonPath('data.user.id', $user->id);

        $this->postJson('/api/v1/auth/login', ['phone' => '0550000001', 'password' => 'wrong-pass'])
            ->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');

        // رقم غير موجود: نفس الرد (لا نكشف وجود الحساب)
        $this->postJson('/api/v1/auth/login', ['phone' => '0559999999', 'password' => 'secret-pass'])
            ->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_suspended_account_cannot_login_or_use_existing_token(): void
    {
        $user = User::factory()->suspended()->create(['phone' => '0550000002', 'password' => 'secret-pass']);

        $this->postJson('/api/v1/auth/login', ['phone' => '0550000002', 'password' => 'secret-pass'])
            ->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_SUSPENDED');

        $this->actingAsUser($user)->getJson('/api/v1/bookings')
            ->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_SUSPENDED');
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['phone' => '0550000003', 'password' => 'secret-pass']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['phone' => '0550000003', 'password' => 'wrong']);
        }

        $this->postJson('/api/v1/auth/login', ['phone' => '0550000003', 'password' => 'secret-pass'])
            ->assertStatus(429)->assertJsonPath('code', 'TOO_MANY_REQUESTS');
    }

    public function test_me_and_logout(): void
    {
        [$user] = $this->customer();
        $token = $user->createToken('test', ['customer'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.phone', $user->phone);
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_unauthenticated_requests_get_401_json(): void
    {
        $this->getJson('/api/v1/bookings')->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_english_messages_with_accept_language(): void
    {
        $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/bookings')
            ->assertStatus(401)->assertJsonPath('message', 'Please sign in.');
    }
}

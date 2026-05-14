<?php

namespace Tests\Feature\Api;

use App\Models\Ad;
use App\Models\Subscriber;
use App\Mail\VerifySubscriptionMail;
use App\Services\Olx\PriceFetcherInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(PriceFetcherInterface::class, function ($mock) {
            $mock->shouldReceive('getPrice')->andReturn(150000); // 1500.00 грн
        });
    }

    public function test_user_can_subscribe_to_ad_price_changes(): void
    {
        Mail::fake();

        $url = 'https://www.olx.ua/d/obyavlenie/test-ad-ID123.html';
        $email = 'test@example.com';

        $response = $this->postJson('/api/subscribe', [
            'url' => $url,
            'email' => $email,
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Confirmation email sent']);

        $this->assertDatabaseHas('ads', ['url' => $url]);
        $this->assertDatabaseHas('subscribers', ['email' => $email]);

        $ad = Ad::where('url', $url)->first();
        $subscriber = Subscriber::where('email', $email)->first();

        $this->assertDatabaseHas('ad_subscriber', [
            'ad_id' => $ad->id,
            'subscriber_id' => $subscriber->id,
            'verified_at' => null,
        ]);

        Mail::assertSent(VerifySubscriptionMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    public function test_user_cannot_subscribe_twice_to_same_ad(): void
    {
        Mail::fake();

        $data = [
            'url' => 'https://www.olx.ua/d/obyavlenie/duplicate-test-ID777.html',
            'email' => 'repeat@example.com',
        ];

        $this->postJson('/api/subscribe', $data);

        $response = $this->postJson('/api/subscribe', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'You are already subscribed to this ad.']);

        Mail::assertSent(VerifySubscriptionMail::class, 1);
    }

    public function test_subscription_requires_valid_data(): void
    {
        $response = $this->postJson('/api/subscribe', [
            'url' => 'not-a-url',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'email']);
    }

    public function test_user_can_verify_subscription_with_valid_token(): void
    {
        $ad = Ad::create(['url' => 'https://olx.ua/test-verify', 'current_price' => 10000]);
        $subscriber = Subscriber::create(['email' => 'verify@example.com']);

        $token = 'test-verification-token-123';

        $ad->subscribers()->attach($subscriber->id, [
            'token' => $token,
            'verified_at' => null
        ]);

        $response = $this->getJson("/verify/{$token}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Subscription verified successfully']);

        $this->assertDatabaseHas('ad_subscriber', [
            'ad_id' => $ad->id,
            'subscriber_id' => $subscriber->id,
            'token' => null, // Мы решили занулять токен после успеха
        ]);

        $pivot = \DB::table('ad_subscriber')
            ->where('ad_id', $ad->id)
            ->where('subscriber_id', $subscriber->id)
            ->first();

        $this->assertNotNull($pivot->verified_at);
    }

    public function test_verification_fails_with_invalid_token(): void
    {
        $response = $this->getJson("/verify/wrong-token");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Invalid token']);
    }
}
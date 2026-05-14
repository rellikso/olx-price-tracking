<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Subscriber;
use App\Mail\PriceChangedMail;
use App\Services\Olx\PriceFetcherInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OlxTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_detects_price_change_and_queues_mail(): void
    {
        Mail::fake();

        $oldPrice = 10000;
        $newPrice = 8000;

        $user = Subscriber::factory()->create(['email' => 'oskiller@example.com']);
        $ad = Ad::factory()->create([
            'url' => 'https://www.olx.ua/d/test.html',
            'current_price' => $oldPrice,
            'last_checked_at' => now()->subHours(2),
        ]);
        $ad->subscribers()->attach($user);

        $this->mock(PriceFetcherInterface::class, function ($mock) use ($ad, $newPrice) {
            $mock->shouldReceive('getPrice')
                ->once()
                ->with($ad->url)
                ->andReturn($newPrice);
        });

        $this->artisan('app:track-ad-prices');

        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'current_price' => $newPrice,
        ]);

        Mail::assertQueued(PriceChangedMail::class, function ($mail) use ($user, $newPrice) {
            return $mail->hasTo($user->email) && $mail->newPrice === $newPrice;
        });
    }
}
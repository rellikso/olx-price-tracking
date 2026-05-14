<?php

namespace Tests\Unit\Mail;

use App\Mail\PriceChangedMail;
use App\Models\Ad;
use Tests\TestCase;

class PriceChangedMailTest extends TestCase
{
    public function test_mail_renders_correct_prices_and_url(): void
    {
        $testUrl = 'https://www.olx.ua/d/obyavlenie/iphone-test-ID123.html';
        $ad = Ad::factory()->make([
            'url' => $testUrl,
            'current_price' => 300000,
        ]);

        $newPrice = 250000;

        $mailable = new PriceChangedMail($ad, $newPrice);

        $mailable->assertHasSubject(__('subscriptions.email.subject'));

        $mailable->assertSeeInHtml($testUrl);

        $mailable->assertSeeInHtml('3');
        $mailable->assertSeeInHtml('000.00');
        $mailable->assertSeeInHtml('2');
        $mailable->assertSeeInHtml('500.00');
        $mailable->assertSeeInHtml('грн.');
    }
}
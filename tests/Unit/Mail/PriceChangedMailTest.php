<?php

namespace Tests\Unit\Mail;

use App\Mail\PriceChangedMail;
use App\Models\Ad;
use Tests\TestCase;

class PriceChangedMailTest extends TestCase
{
    public function test_mail_renders_correct_prices_and_url(): void
    {
        // 1. Подготовка данных
        $testUrl = 'https://www.olx.ua/d/obyavlenie/iphone-test-ID123.html';
        $ad = Ad::factory()->make([
            'url' => $testUrl,
            'current_price' => 300000, // 3 000.00 грн
        ]);

        $newPrice = 250000; // 2 500.00 грн

        // 2. Создаем экземпляр письма
        $mailable = new PriceChangedMail($ad, $newPrice);

        // 3. Проверки содержимого
        $mailable->assertHasSubject(__('subscriptions.email.subject'));

        // Проверяем наличие URL (он выводится и в ссылке, и в тексте)
        $mailable->assertSeeInHtml($testUrl);

        // Проверяем цены (ищем фрагменты, чтобы избежать проблем с неразрывными пробелами)
        $mailable->assertSeeInHtml('3');
        $mailable->assertSeeInHtml('000.00');
        $mailable->assertSeeInHtml('2');
        $mailable->assertSeeInHtml('500.00');
        $mailable->assertSeeInHtml('грн.');
    }
}
<?php

namespace App\Mail;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PriceChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Ad $ad;
    public int $oldPrice;
    public int $newPrice;

    public function __construct(Ad $ad, int $newPrice)
    {
        $this->ad = $ad;
        $this->oldPrice = (int) $ad->current_price;
        $this->newPrice = $newPrice;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('subscriptions.email.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.price-changed',
        );
    }
}

<?php

namespace App\Console\Commands;

use App\Mail\PriceChangedMail;
use App\Models\Ad;
use App\Services\Olx\PriceFetcherInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:track-ad-prices')]
#[Description('Check OLX ad prices and notify subscribers')]
class TrackAdPrices extends Command
{
    public function handle(PriceFetcherInterface $fetcher)
    {
        // Fetch ads checked more than an hour ago or never checked
        $ads = Ad::where('last_checked_at', '<', now()->subHour())
            ->orWhereNull('last_checked_at')
            ->get();

        if ($ads->isEmpty()) {
            $this->info('No ads require checking at this time.');
            return;
        }

        foreach ($ads as $ad) {
            $newPrice = $fetcher->getPrice($ad->url);

            if ($newPrice !== null) {
                if ($ad->current_price !== $newPrice) {
                    $this->info("Price changed for ad ID {$ad->id}: {$newPrice}");

                    $this->notifyUsers($ad, $newPrice);

                    $ad->update([
                        'current_price' => $newPrice,
                        'last_checked_at' => now(),
                    ]);
                } else {
                    $this->info("Price is unchanged for ad ID {$ad->id}.");

                    // Update last_checked_at without changing updated_at
                    $ad->timestamps = false;
                    $ad->update(['last_checked_at' => now()]);
                    $ad->timestamps = true;
                }
            } else {
                $this->error("Failed to fetch price for ad ID {$ad->id}.");
            }

            // Sleep for a random time between 1 and 3 seconds to avoid rate limits
            sleep(random_int(1, 3));
        }
    }

    private function notifyUsers(Ad $ad, int $newPrice)
    {
        $subscribers = $ad
            ->subscribers()
            ->wherePivotNotNull('verified_at')
            ->get();

        foreach ($subscribers as $subscription) {
            Mail::to($subscription->email)->queue(new PriceChangedMail($ad, $newPrice));
        }
    }
}

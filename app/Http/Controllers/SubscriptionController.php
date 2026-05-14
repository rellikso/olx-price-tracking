<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionRequest;
use App\Mail\VerifySubscriptionMail;
use App\Models\Ad;
use App\Models\Subscriber;
use App\Services\Olx\PriceFetcherInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function subscribe(SubscriptionRequest $request)
    {
        $ad = Ad::firstOrCreate(['url' => $request->url]);

        if ($ad->wasRecentlyCreated) {
            $price = app(PriceFetcherInterface::class)->getPrice($ad->url);

            if ($price) {
                $ad->update([
                    'current_price' => $price,
                    'last_checked_at' => now(),
                ]);
            }
        }

        $subscriber = Subscriber::firstOrCreate(['email' => $request->email]);

        if ($ad->subscribers()->where('subscriber_id', $subscriber->id)->exists()) {
            return response()->json([
                'message' => 'You are already subscribed to this ad.'
            ], 200);
        }

        $token = Str::random(32);

        // Linking subscriber to ad with token
        $ad->subscribers()->syncWithoutDetaching([
            $subscriber->id => ['token' => $token]
        ]);

        // Sending email containing a link to /verify/{token}
        Mail::to($subscriber->email)->send(new VerifySubscriptionMail($ad, $token));

        return response()->json([
            'message' => 'Confirmation email sent'
        ], 201);
    }

    public function verify($token)
    {
        $subscription = \DB::table('ad_subscriber')
            ->where('token', $token)
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Invalid token'], 404);
        }

        \DB::table('ad_subscriber')
            ->where('token', $token)
            ->update([
                'verified_at' => now(),
                'token' => null,
            ]);

        return response()->json(['message' => 'Subscription verified successfully']);
    }
}

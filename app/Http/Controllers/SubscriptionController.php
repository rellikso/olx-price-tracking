<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionRequest;
use App\Models\Ad;
use App\Models\AdUser;
use App\Services\Olx\PriceFetcherInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function subscribe(SubscriptionRequest $request)
    {
        $subscriber = $request->user();

        try {
            $isNewSubscription = DB::transaction(function () use ($subscriber, $request) {
                $ad = Ad::firstOrCreate(['url' => $request->input('url')]);

                if ($ad->wasRecentlyCreated) {
                    $price = app(PriceFetcherInterface::class)->getPrice($ad->url);

                    if ($price) {
                        $ad->update([
                            'current_price' => $price,
                            'last_checked_at' => now(),
                        ]);
                    }
                }

                $pivot = AdUser::firstOrCreate([
                    'ad_id'   => $ad->id,
                    'user_id' => $subscriber->id,
                ]);

                return $pivot->wasRecentlyCreated;
            });

            if (!$isNewSubscription) {
                return redirect()->route('subscriptions.index')
                    ->with('info', __('subscriptions.info'));
            }

            return redirect()->route('subscriptions.index')
                ->with('success', __('subscriptions.success'));

        } catch (\Throwable $exception) {
            return redirect()->route('subscriptions.index')
                ->withErrors(__('subscriptions.error') . ' ' . $exception->getMessage());
        }
    }
}
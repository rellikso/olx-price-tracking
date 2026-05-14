OLX Tracker

{{ __('subscriptions.email.price_change_news') }}

{{ __('subscriptions.email.price_change_text') }}

{{ __('subscriptions.email.price_change_old_price') }}: {{ number_format($ad->current_price / 100, 2, '.', ' ') }} грн.
{{ __('subscriptions.email.price_change_new_price') }}: {{ number_format($newPrice / 100, 2, '.', ' ') }} грн.

{{ __('subscriptions.email.go_to_ad') }}:
{{ $ad->url }}

---
{{ __('subscriptions.email.footer') }}
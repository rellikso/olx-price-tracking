<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('subscribe', [SubscriptionController::class, 'subscribe']);

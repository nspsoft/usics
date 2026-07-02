<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsappWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// WhatsApp Webhook (Fonnte)
Route::prefix('whatsapp')->group(function () {
    Route::post('/webhook', [WhatsappWebhookController::class, 'handle']);
    Route::get('/webhook', [WhatsappWebhookController::class, 'verify']);
});

// WhatsApp Webhook (Wablas)
Route::post('/wablas/webhook', [WhatsappWebhookController::class, 'handle']);

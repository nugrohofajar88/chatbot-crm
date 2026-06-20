<?php

use App\Http\Controllers\FonnteWebhookController;
use App\Http\Controllers\MetaWebhookController;
use App\Http\Controllers\WablasWebhookController;
use Illuminate\Support\Facades\Route;

// Webhook publik gateway WhatsApp (dipanggil layanan luar). Tanpa auth/CSRF.
// URL: /api/webhooks/fonnte/{secret?}  atau  /api/webhooks/wablas/{secret?}
Route::match(['get', 'post'], 'webhooks/fonnte/{secret?}', [FonnteWebhookController::class, 'handle'])
    ->name('webhooks.fonnte');

Route::match(['get', 'post'], 'webhooks/wablas/{secret?}', [WablasWebhookController::class, 'handle'])
    ->name('webhooks.wablas');

// Webhook Meta (Facebook Messenger & Instagram). GET = verifikasi langganan,
// POST = event pesan masuk. Satu URL melayani kedua platform.
Route::match(['get', 'post'], 'webhooks/meta/{secret?}', [MetaWebhookController::class, 'handle'])
    ->name('webhooks.meta');

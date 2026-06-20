<?php

use App\Http\Controllers\FonnteWebhookController;
use App\Http\Controllers\MetaWebhookController;
use App\Http\Controllers\WablasWebhookController;
use Illuminate\Support\Facades\Route;

// Webhook publik gateway WhatsApp (dipanggil layanan luar). Tanpa auth/CSRF.
// URL: /api/webhooks/fonnte/{secret?}  atau  /api/webhooks/wablas/{secret?}
Route::match(['get', 'post'], 'webhooks/fonnte/{secret?}', [FonnteWebhookController::class, 'handle'])
    ->name('webhooks.fonnte');

// Tracking/status callback Wablas (delivered/read). HARUS didaftarkan sebelum
// route 'webhooks/wablas/{secret?}' agar segmen 'tracking' tak dianggap secret.
Route::match(['get', 'post'], 'webhooks/wablas/tracking/{secret?}', [WablasWebhookController::class, 'track'])
    ->name('webhooks.wablas.tracking');

Route::match(['get', 'post'], 'webhooks/wablas/{secret?}', [WablasWebhookController::class, 'handle'])
    ->name('webhooks.wablas');

// Webhook Meta (Facebook Messenger & Instagram). GET = verifikasi langganan,
// POST = event pesan masuk. Satu URL melayani kedua platform.
Route::match(['get', 'post'], 'webhooks/meta/{secret?}', [MetaWebhookController::class, 'handle'])
    ->name('webhooks.meta');

<?php

use App\Http\Controllers\FonnteWebhookController;
use App\Http\Controllers\WablasWebhookController;
use Illuminate\Support\Facades\Route;

// Webhook publik gateway WhatsApp (dipanggil layanan luar). Tanpa auth/CSRF.
// URL: /api/webhooks/fonnte/{secret?}  atau  /api/webhooks/wablas/{secret?}
Route::match(['get', 'post'], 'webhooks/fonnte/{secret?}', [FonnteWebhookController::class, 'handle'])
    ->name('webhooks.fonnte');

Route::match(['get', 'post'], 'webhooks/wablas/{secret?}', [WablasWebhookController::class, 'handle'])
    ->name('webhooks.wablas');

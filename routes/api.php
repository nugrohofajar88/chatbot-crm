<?php

use App\Http\Controllers\FonnteWebhookController;
use Illuminate\Support\Facades\Route;

// Webhook publik Fonnte (dipanggil layanan luar). Tanpa auth/CSRF.
// URL: /api/webhooks/fonnte/{secret?}
Route::match(['get', 'post'], 'webhooks/fonnte/{secret?}', [FonnteWebhookController::class, 'handle'])
    ->name('webhooks.fonnte');

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Gateway WhatsApp aktif: 'fonnte' atau 'wablas'.
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'fonnte'),
    ],

    'fonnte' => [
        'base_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com'),
        'token' => env('FONNTE_TOKEN'),
        'webhook_secret' => env('FONNTE_WEBHOOK_SECRET'),
    ],

    'wablas' => [
        'base_url' => env('WABLAS_BASE_URL', 'https://wablas.com'),
        'token' => env('WABLAS_TOKEN'),
        'secret_key' => env('WABLAS_SECRET_KEY'),
        'webhook_secret' => env('WABLAS_WEBHOOK_SECRET'),
    ],

    // Meta: Facebook Messenger & Instagram (Graph API + Webhooks).
    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),    // Messenger (graph.facebook.com)
        'ig_access_token' => env('META_IG_ACCESS_TOKEN'),        // Instagram Login (graph.instagram.com)
        'ig_app_secret' => env('META_IG_APP_SECRET'),            // utk verifikasi signature webhook IG
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),

        // Saklar per-channel: nonaktifkan bila izin belum siap. Webhook channel
        // yang dimatikan diabaikan (pesan tidak diproses). Nyalakan lagi tanpa ubah kode.
        'messenger_enabled' => env('META_MESSENGER_ENABLED', true),
        'instagram_enabled' => env('META_INSTAGRAM_ENABLED', true),
    ],

];

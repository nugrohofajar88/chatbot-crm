<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interval Auto-Scoring Lead
    |--------------------------------------------------------------------------
    |
    | Skor lead dihitung otomatis saat pesan lead pertama, lalu tiap kelipatan
    | nilai ini. Contoh 3 = pesan lead ke-1, 3, 6, 9, ...
    | Nilai 0 menonaktifkan auto-scoring (skor hanya via tombol manual).
    |
    | Ini hanya DEFAULT; nilai aktif dapat diubah operator dari menu
    | Pengaturan AI (disimpan di tabel settings, key 'scoring_interval').
    |
    */

    'scoring_interval' => (int) env('SCORING_INTERVAL', 3),

];

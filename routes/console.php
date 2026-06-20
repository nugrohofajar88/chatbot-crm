<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Refresh token Instagram tiap hari (hanya benar-benar refresh saat mendekati kedaluwarsa).
Schedule::command('meta:refresh-ig-token')->dailyAt('03:00')->withoutOverlapping();

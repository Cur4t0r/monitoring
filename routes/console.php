<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('log:generate')
    ->everyFiveMinutes()
    ->withoutOverlapping()        // Cegah race condition jika command lambat
    ->runInBackground()           // Tidak memblokir scheduler tick berikutnya
    ->appendOutputTo(storage_path('logs/bandwidth-polling.log')); // Log hasil

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

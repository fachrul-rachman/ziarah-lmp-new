<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler wiring (minute precision; do not depend on seconds).
Schedule::command('bookings:complete-expired')
    ->everyMinute()
    ->timezone('Asia/Jakarta');

// Run every minute, but the command itself checks HH:MM against setting (ignores seconds).
Schedule::command('discord:notify-h1')
    ->everyMinute()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

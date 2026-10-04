<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios de citas a n8n (1 día, 1 hora y 10 min antes).
// Requiere el cron del sistema: * * * * * php artisan schedule:run
Schedule::command('appointments:notify-reminders')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

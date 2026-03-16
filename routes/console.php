<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:detect-overdue')->dailyAt('06:00');
Schedule::command('tasks:send-daily-summary')->dailyAt('07:00');
Schedule::command('tasks:send-due-reminders')->dailyAt('08:00');

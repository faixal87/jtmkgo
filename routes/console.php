<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Modules\GantiGo\Services\ClassReplacementWorkflowService;
use App\Services\NotificationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:birthday', function (NotificationService $notifications) {
    $sent = $notifications->sendBirthdayNotifications();

    $this->info("Birthday notifications sent: {$sent}");
})->purpose('Send daily JTMK birthday notifications');

Artisan::command('ganti-go:mark-overdue', function (ClassReplacementWorkflowService $workflow) {
    $count = $workflow->markOverdueRecords();

    $this->info("Ganti Go overdue records updated: {$count}");
})->purpose('Mark past planned Ganti Go replacements as overdue');

Schedule::command('notifications:birthday')->dailyAt('08:00');
Schedule::command('ganti-go:mark-overdue')->dailyAt('00:10');

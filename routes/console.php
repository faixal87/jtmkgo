<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Modules\GantiGo\Services\ClassReplacementWorkflowService;
use App\Modules\AcademicCore\Services\AcademicCoreDoctorService;
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

Artisan::command('ganti-go:remind-implementation', function (ClassReplacementWorkflowService $workflow) {
    $count = $workflow->sendImplementationReminders();

    $this->info("Ganti Go implementation reminders sent: {$count}");
})->purpose('Remind lecturers to mark planned Ganti Go replacements as implemented');

Artisan::command('academic-core:doctor', function (AcademicCoreDoctorService $doctor) {
    $report = $doctor->report();

    $this->info('Academic Core Doctor');
    $this->line("Current semester: {$report['current_semester']}");
    $this->newLine();

    $this->table(['Metric', 'Value'], collect($report['counts'])->map(
        fn ($value, string $label): array => [$label, $value]
    )->values()->all());

    $this->newLine();
    $this->info('Ganti Go source status');
    $this->table(['Metric', 'Value'], collect($report['ganti_go'])->map(
        fn ($value, string $label): array => [$label, $value]
    )->values()->all());

    $this->newLine();
    $this->info('SubjekGo source status');
    $this->table(['Metric', 'Value'], collect($report['subjek_go'])->map(
        fn ($value, string $label): array => [$label, $value]
    )->values()->all());

    $this->newLine();
    $this->info('Legacy tables still present');
    $this->table(['Table', 'Rows'], collect($report['legacy_tables'])->map(
        fn ($value, string $label): array => [$label, $value]
    )->values()->all());

    $this->newLine();
    $this->info('Orphan checks');
    $this->table(['Check', 'Rows'], collect($report['orphans'])->map(
        fn ($value, string $label): array => [$label, $value]
    )->values()->all());
})->purpose('Report Academic Core integration health and remaining legacy dependencies');

Schedule::command('notifications:birthday')->dailyAt('08:00');
Schedule::command('ganti-go:remind-implementation')->dailyAt('08:15');
Schedule::command('ganti-go:mark-overdue')->dailyAt('00:10');

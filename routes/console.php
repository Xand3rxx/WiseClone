<?php

declare(strict_types=1);

use App\Jobs\BackupDatabaseJob;
use App\Jobs\ExportImmutableAuditLogJob;
use App\Jobs\ReconcileLedgerJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// You can add scheduled tasks here
Schedule::job(new ReconcileLedgerJob)->hourly();
Schedule::job(new ExportImmutableAuditLogJob)->dailyAt('00:15');
Schedule::job(new BackupDatabaseJob)->dailyAt('00:30');

<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('test
    {--filter= : Filter which tests to run}
    {--testsuite= : Run a specific PHPUnit test suite}
    {--group= : Only run tests from the specified group}
    {--exclude-group= : Exclude tests from the specified group}
    {--stop-on-failure : Stop execution upon first error or failure}', function () {
    $command = [base_path('vendor/bin/phpunit')];

    foreach (['filter', 'testsuite', 'group', 'exclude-group'] as $option) {
        if ($value = $this->option($option)) {
            $command[] = "--{$option}={$value}";
        }
    }

    if ($this->option('stop-on-failure')) {
        $command[] = '--stop-on-failure';
    }

    $process = new Process($command, base_path(), [
        'APP_ENV' => 'testing',
        'CACHE_STORE' => 'array',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
    ]);
    $process->run(function (string $type, string $output): void {
        $this->output->write($output);
    });

    return $process->getExitCode();
})->purpose('Run the PHPUnit test suite');

// You can add scheduled tasks here
// Schedule::command('cache:clear')->daily();

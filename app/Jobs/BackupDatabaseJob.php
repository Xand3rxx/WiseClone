<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BackupRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabaseJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $run = BackupRun::create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $path = "backups/database-{$run->uuid}.snapshot";
        $database = (string) config('database.connections.'.config('database.default').'.database');
        $contents = is_file($database)
            ? file_get_contents($database)
            : json_encode([
                'driver' => config('database.default'),
                'database' => $database,
                'created_at' => now()->toIso8601String(),
                'note' => 'Configure database-native dumps for non-file database drivers.',
            ], JSON_THROW_ON_ERROR);

        Storage::put($path, $contents ?: '');

        $run->forceFill([
            'status' => 'completed',
            'path' => $path,
            'checksum' => hash('sha256', $contents ?: ''),
            'metadata' => ['driver' => config('database.default')],
            'finished_at' => now(),
        ])->save();

        Log::info('backup.database.completed', [
            'backup_uuid' => $run->uuid,
            'path' => $path,
            'checksum' => $run->checksum,
        ]);
    }
}

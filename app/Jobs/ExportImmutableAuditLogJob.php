<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditExport;
use App\Models\LedgerEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportImmutableAuditLogJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $export = AuditExport::create([
            'type' => 'ledger_entries',
            'status' => 'running',
        ]);

        $path = "audit/ledger-{$export->uuid}.jsonl";
        $contents = '';

        LedgerEntry::with(['account', 'currency'])
            ->orderBy('id')
            ->chunkById(500, function ($entries) use (&$contents): void {
                foreach ($entries as $entry) {
                    $contents .= json_encode([
                        'uuid' => $entry->uuid,
                        'ledger_event_uuid' => $entry->ledger_event_uuid,
                        'transfer_group_uuid' => $entry->transfer_group_uuid,
                        'account_uuid' => $entry->account?->uuid,
                        'user_id' => $entry->user_id,
                        'currency' => $entry->currency?->code,
                        'event_type' => $entry->event_type,
                        'direction' => $entry->direction,
                        'status' => $entry->status,
                        'amount' => (string) $entry->amount,
                        'balance_after' => (string) $entry->balance_after,
                        'posted_at' => $entry->posted_at?->toIso8601String(),
                        'created_at' => $entry->created_at?->toIso8601String(),
                    ], JSON_THROW_ON_ERROR).PHP_EOL;
                }
            });

        Storage::put($path, $contents);

        $export->forceFill([
            'status' => 'completed',
            'path' => $path,
            'checksum' => hash('sha256', $contents),
            'metadata' => ['bytes' => strlen($contents)],
            'exported_at' => now(),
        ])->save();

        Log::info('audit.ledger_export.completed', [
            'export_uuid' => $export->uuid,
            'path' => $path,
            'checksum' => $export->checksum,
        ]);
    }
}

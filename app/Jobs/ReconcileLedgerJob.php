<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\ReconciliationRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReconcileLedgerJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $run = ReconciliationRun::create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $mismatches = [];
        $accountsChecked = 0;

        Account::query()->chunkById(100, function ($accounts) use (&$accountsChecked, &$mismatches): void {
            foreach ($accounts as $account) {
                $accountsChecked++;
                $latestEntry = LedgerEntry::where('account_id', $account->id)
                    ->whereIn('status', [LedgerEntry::STATUS_POSTED, LedgerEntry::STATUS_SETTLED])
                    ->latest('id')
                    ->first();

                if (! $latestEntry) {
                    continue;
                }

                if ((string) $latestEntry->balance_after !== (string) $account->balance) {
                    $mismatches[] = [
                        'account_id' => $account->id,
                        'account_balance' => (string) $account->balance,
                        'ledger_balance' => (string) $latestEntry->balance_after,
                    ];
                }
            }
        });

        $run->forceFill([
            'status' => $mismatches === [] ? 'passed' : 'failed',
            'accounts_checked' => $accountsChecked,
            'mismatches_found' => count($mismatches),
            'summary' => ['mismatches' => $mismatches],
            'finished_at' => now(),
        ])->save();

        Log::info('ledger.reconciliation.completed', [
            'run_uuid' => $run->uuid,
            'status' => $run->status,
            'accounts_checked' => $accountsChecked,
            'mismatches_found' => count($mismatches),
        ]);
    }
}

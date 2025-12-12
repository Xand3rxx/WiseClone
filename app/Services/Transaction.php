<?php

namespace App\Services;

class Transaction
{
    /**
     * Get status display properties.
     *
     * @param string $status The transaction status
     * @return object Object containing status display properties
     */
    public function status(string $status): object
    {
        $statusConfig = match ($status) {
            'Pending' => [
                'name' => 'Pending',
                'class' => 'light-warning',
                'color' => '#ffc107',
            ],
            'Success' => [
                'name' => 'Success',
                'class' => 'light-primary',
                'color' => '#0d6efd',
            ],
            'Failed' => [
                'name' => 'Failed',
                'class' => 'light-danger',
                'color' => '#dc3545',
            ],
            default => [
                'name' => 'Unknown',
                'class' => 'light-secondary',
                'color' => '#6c757d',
            ],
        };

        return (object) $statusConfig;
    }

    /**
     * Get transaction type display properties.
     *
     * @param string $type The transaction type
     * @return object Object containing type display properties
     */
    public function type(string $type): object
    {
        $typeConfig = match ($type) {
            'Credit' => [
                'name' => 'Credit',
                'class' => 'light-primary',
                'sign' => '+',
                'signClass' => 'primary',
                'color' => '#0d6efd',
            ],
            'Debit' => [
                'name' => 'Debit',
                'class' => 'light-danger',
                'sign' => '-',
                'signClass' => 'danger',
                'color' => '#dc3545',
            ],
            default => [
                'name' => 'Unknown',
                'class' => 'light-secondary',
                'sign' => '',
                'signClass' => 'secondary',
                'color' => '#6c757d',
            ],
        };

        return (object) $typeConfig;
    }

    /**
     * Get summary statistics for transactions.
     *
     * @param \Illuminate\Support\Collection $transactions Collection of transactions
     * @return array<string, mixed> Summary statistics
     */
    public function getSummary($transactions): array
    {
        $totalDebit = $transactions->where('type', 'Debit')->sum('amount');
        $totalCredit = $transactions->where('type', 'Credit')->sum('amount');
        $successCount = $transactions->where('status', 'Success')->count();
        $failedCount = $transactions->where('status', 'Failed')->count();
        $pendingCount = $transactions->where('status', 'Pending')->count();

        return [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'net_flow' => $totalCredit - $totalDebit,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
            'total_count' => $transactions->count(),
        ];
    }
}

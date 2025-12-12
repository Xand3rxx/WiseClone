<?php

namespace App\Models;

use App\Services\Transaction as TransactionService;
use App\Traits\GenerateUniqueIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use SoftDeletes;

    public const STATUS = [
        'Success' => 'Success',
        'Pending' => 'Pending',
        'Failed' => 'Failed',
    ];

    public const TYPE = [
        'Debit' => 'Debit',
        'Credit' => 'Credit',
    ];

    protected $guarded = ['deleted_at', 'created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta_data' => 'array',
            'amount' => 'decimal:2',
            'rate' => 'decimal:6',
            'transfer_fee' => 'decimal:2',
            'variable_fee' => 'decimal:2',
            'fixed_fee' => 'decimal:2',
        ];
    }

    /**
     * Model event to trigger action on creating
     */
    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction): void {
            // Generate unique uuid for a new transaction.
            $transaction->uuid = (string) Str::uuid();

            // Generate a random unique transaction reference.
            $transaction->reference = GenerateUniqueIdentity::generateReference('transactions');
        });
    }

    /**
     * Store the newly created transaction using double-entry accounting.
     *
     * @param array<string, mixed> $validated
     */
    public static function doubleEntryRecord(array $validated, float $amount): bool
    {
        $transactionCreated = false;

        DB::transaction(function () use ($validated, $amount, &$transactionCreated): void {
            // Get latest currency balance of the validated user id
            $user = User::where('id', $validated['user_id'])->firstOrFail();
            $currencyBalance = $user->latestCurrencyBalance;

            if (!$currencyBalance) {
                throw new \RuntimeException('User has no currency balance record.');
            }

            $currencyId = (int) $validated['currency_id'];
            $isDebit = auth()->id() === $validated['user_id'];

            // Record transaction
            $transaction = self::create([
                'user_id' => $validated['user_id'],
                'recipient_id' => $validated['recipient_id'],
                'source_currency_id' => $validated['source_currency_id'],
                'target_currency_id' => $validated['target_currency_id'],
                'amount' => self::removeComma($amount),
                'rate' => $validated['rate'],
                'transfer_fee' => $validated['transferFee'],
                'variable_fee' => $validated['variableFee'],
                'fixed_fee' => $validated['fixedFee'],
                'type' => self::TYPE[$validated['type']],
                'status' => self::STATUS['Success'],
                'meta_data' => $validated,
            ]);

            // Calculate new balances based on currency and transaction type
            $newBalances = self::calculateNewBalances(
                $currencyBalance,
                $currencyId,
                $amount,
                $isDebit
            );

            // Create a new currency balance record
            CurrencyBalance::create([
                'user_id' => $validated['user_id'],
                'transaction_id' => $transaction->id,
                'USD' => $newBalances['USD'],
                'EUR' => $newBalances['EUR'],
                'NGN' => $newBalances['NGN'],
            ]);

            $transactionCreated = true;
        }, 3);

        return $transactionCreated;
    }

    /**
     * Calculate new balances after a transaction.
     *
     * @return array<string, float>
     */
    private static function calculateNewBalances(
        CurrencyBalance $currentBalance,
        int $currencyId,
        float $amount,
        bool $isDebit
    ): array {
        $currencyMap = [
            1 => 'EUR',
            2 => 'NGN',
            3 => 'USD',
        ];

        $balances = [
            'USD' => (float) $currentBalance->USD,
            'EUR' => (float) $currentBalance->EUR,
            'NGN' => (float) $currentBalance->NGN,
        ];

        $currency = $currencyMap[$currencyId] ?? 'USD';

        if ($isDebit) {
            $balances[$currency] -= $amount;
        } else {
            $balances[$currency] += $amount;
        }

        return $balances;
    }

    /**
     * Record a failed transaction.
     *
     * @param array<string, mixed> $validated
     */
    public static function failedTransaction(
        array $validated,
        float $amount,
        string $type,
        int $userId,
        int $recipientId
    ): Transaction {
        return self::create([
            'user_id' => $userId,
            'recipient_id' => $recipientId,
            'source_currency_id' => $validated['source_currency_id'],
            'target_currency_id' => $validated['target_currency_id'],
            'amount' => self::removeComma($amount),
            'rate' => $validated['rate'],
            'transfer_fee' => $validated['transferFee'],
            'variable_fee' => $validated['variableFee'],
            'fixed_fee' => $validated['fixedFee'],
            'type' => $type,
            'status' => self::STATUS['Failed'],
            'meta_data' => $validated,
        ]);
    }

    /**
     * Remove comma from number format without removing decimal point.
     */
    public static function removeComma(float|string $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        return (float) preg_replace('/[^\d.]/', '', $value);
    }

    /**
     * Format the amount value.
     */
    public function amount(): string
    {
        return number_format((float) $this->amount, 2);
    }

    /**
     * Get the status details of a transaction.
     */
    public function status(): object
    {
        return (new TransactionService)->status($this->status);
    }

    /**
     * Get the type details of a transaction.
     */
    public function type(): object
    {
        return (new TransactionService)->type($this->type);
    }

    /**
     * Get the sender associated with the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the recipient associated with the transaction.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the source currency of the transaction.
     */
    public function sourceCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'source_currency_id');
    }

    /**
     * Get the target currency of the transaction.
     */
    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }

    /**
     * Get the currency balance record for this transaction.
     */
    public function currencyBalance(): HasOne
    {
        return $this->hasOne(CurrencyBalance::class);
    }

    /**
     * Scope a query to get transactions for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->orWhere('recipient_id', $userId);
    }
}

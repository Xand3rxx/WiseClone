<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\IdempotencyKey;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\TransferQuote;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class LedgerService
{
    public function balanceFor(User $user, Currency $currency): string
    {
        return (string) $this->accountFor($user, $currency)->balance;
    }

    public function accountFor(User $user, Currency $currency): Account
    {
        $account = Account::forUserCurrency($user, $currency);

        if (Money::compare($account->balance, '0') === 0 && $user->latestCurrencyBalance) {
            $snapshotBalance = match ($currency->code) {
                'USD' => (string) $user->latestCurrencyBalance->USD,
                'EUR' => (string) $user->latestCurrencyBalance->EUR,
                'NGN' => (string) $user->latestCurrencyBalance->NGN,
                default => '0',
            };
            if (Money::isGreaterThan((string) $snapshotBalance, '0')) {
                $account->forceFill(['balance' => Money::round((string) $snapshotBalance)])->save();
            }
        }

        return $account;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function recordTransfer(array $validated, TransferQuote $quote, ?IdempotencyKey $idempotencyKey = null): bool
    {
        return DB::transaction(function () use ($validated, $quote, $idempotencyKey): bool {
            $sender = User::whereKey((int) $validated['user_id'])->lockForUpdate()->firstOrFail();
            $recipient = User::query()
                ->whereKey((int) $validated['recipient_id'])
                ->whereNull('blocked_at')
                ->lockForUpdate()
                ->firstOrFail();

            $sourceCurrency = Currency::findOrFail((int) $validated['source_currency_id']);
            $targetCurrency = Currency::findOrFail((int) $validated['target_currency_id']);
            $transferGroupUuid = $validated['transfer_group_uuid'] ?? (string) Str::uuid();
            $ledgerEventUuid = (string) Str::uuid();

            $this->createInformationalEntry(
                $sender,
                $sourceCurrency,
                $quote,
                $transferGroupUuid,
                $ledgerEventUuid,
                LedgerEntry::EVENT_TRANSFER_PENDING,
                LedgerEntry::STATUS_PENDING,
                $quote->source_amount,
                $idempotencyKey,
                ['message' => 'Transfer accepted for posting.']
            );

            $debitTransaction = $this->createTransactionProjection(
                $sender,
                $recipient,
                $quote,
                Transaction::TYPE['Debit'],
                $quote->source_amount,
                $transferGroupUuid,
                $validated
            );

            $this->postEntry(
                user: $sender,
                currency: $sourceCurrency,
                amount: (string) $quote->source_amount,
                direction: LedgerEntry::DIRECTION_DEBIT,
                eventType: LedgerEntry::EVENT_TRANSFER_DEBIT,
                status: LedgerEntry::STATUS_POSTED,
                transferGroupUuid: $transferGroupUuid,
                ledgerEventUuid: $ledgerEventUuid,
                transaction: $debitTransaction,
                quote: $quote,
                idempotencyKey: $idempotencyKey,
                metadata: $this->participantMetadata($sender, $recipient)
            );

            $this->createBalanceProjection($sender, $debitTransaction);

            $creditTransaction = $this->createTransactionProjection(
                $recipient,
                $sender,
                $quote,
                Transaction::TYPE['Credit'],
                $quote->target_amount,
                $transferGroupUuid,
                array_merge($validated, ['type' => 'Credit', 'sign' => '+'])
            );

            $this->postEntry(
                user: $recipient,
                currency: $targetCurrency,
                amount: (string) $quote->target_amount,
                direction: LedgerEntry::DIRECTION_CREDIT,
                eventType: LedgerEntry::EVENT_TRANSFER_CREDIT,
                status: LedgerEntry::STATUS_POSTED,
                transferGroupUuid: $transferGroupUuid,
                ledgerEventUuid: $ledgerEventUuid,
                transaction: $creditTransaction,
                quote: $quote,
                idempotencyKey: $idempotencyKey,
                metadata: $this->participantMetadata($sender, $recipient)
            );

            $this->createBalanceProjection($recipient, $creditTransaction);

            $this->createInformationalEntry(
                $sender,
                $sourceCurrency,
                $quote,
                $transferGroupUuid,
                $ledgerEventUuid,
                LedgerEntry::EVENT_TRANSFER_SETTLED,
                LedgerEntry::STATUS_SETTLED,
                '0.00',
                $idempotencyKey,
                ['message' => 'Transfer settled.', 'credit_transaction_id' => $creditTransaction->id]
            );

            $quote->forceFill([
                'recipient_id' => $recipient->id,
                'accepted_at' => now(),
                'accepted_metadata' => array_merge($this->participantMetadata($sender, $recipient), [
                    'transfer_group_uuid' => $transferGroupUuid,
                    'debit_transaction_id' => $debitTransaction->id,
                    'credit_transaction_id' => $creditTransaction->id,
                ]),
            ])->save();

            Log::info('transfer.settled', [
                'transfer_group_uuid' => $transferGroupUuid,
                'quote_uuid' => $quote->uuid,
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'source_amount' => (string) $quote->source_amount,
                'target_amount' => (string) $quote->target_amount,
            ]);

            return true;
        }, 3);
    }

    public function fundAccount(User $user, User $systemUser, Currency $currency, float|string $amount, ?IdempotencyKey $idempotencyKey = null): Transaction
    {
        return DB::transaction(function () use ($user, $systemUser, $currency, $amount, $idempotencyKey): Transaction {
            $transferGroupUuid = (string) Str::uuid();
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'recipient_id' => $systemUser->id,
                'transfer_group_uuid' => $transferGroupUuid,
                'source_currency_id' => $currency->id,
                'target_currency_id' => $currency->id,
                'amount' => Money::round($amount),
                'rate' => 1.0,
                'transfer_fee' => 0,
                'variable_fee' => 0,
                'fixed_fee' => 0,
                'type' => Transaction::TYPE['Credit'],
                'status' => Transaction::STATUS['Success'],
                'meta_data' => [
                    'sender_name' => $systemUser->full_name,
                    'sender_email' => $systemUser->email,
                    'receiver_name' => $user->full_name,
                    'receiver_email' => $user->email,
                    'transfer_group_uuid' => $transferGroupUuid,
                ],
            ]);

            $this->postEntry(
                user: $user,
                currency: $currency,
                amount: $amount,
                direction: LedgerEntry::DIRECTION_CREDIT,
                eventType: LedgerEntry::EVENT_ACCOUNT_FUNDING,
                status: LedgerEntry::STATUS_SETTLED,
                transferGroupUuid: $transferGroupUuid,
                ledgerEventUuid: (string) Str::uuid(),
                transaction: $transaction,
                quote: null,
                idempotencyKey: $idempotencyKey,
                metadata: ['funded_by' => $systemUser->id]
            );

            $this->createBalanceProjection($user, $transaction);

            Log::info('account.funded', [
                'transfer_group_uuid' => $transferGroupUuid,
                'user_id' => $user->id,
                'currency' => $currency->code,
                'amount' => Money::round($amount),
            ]);

            return $transaction;
        }, 3);
    }

    public function recordFailedTransfer(User $user, Currency $currency, ?TransferQuote $quote, string $transferGroupUuid, string $message, ?IdempotencyKey $idempotencyKey = null): void
    {
        $this->createInformationalEntry(
            $user,
            $currency,
            $quote,
            $transferGroupUuid,
            (string) Str::uuid(),
            LedgerEntry::EVENT_TRANSFER_FAILED,
            LedgerEntry::STATUS_FAILED,
            $quote?->source_amount ?? '0.00',
            $idempotencyKey,
            ['message' => $message]
        );

        Log::warning('transfer.failed', [
            'transfer_group_uuid' => $transferGroupUuid,
            'quote_uuid' => $quote?->uuid,
            'user_id' => $user->id,
            'message' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function postEntry(
        User $user,
        Currency $currency,
        float|string $amount,
        string $direction,
        string $eventType,
        string $status,
        string $transferGroupUuid,
        string $ledgerEventUuid,
        ?Transaction $transaction,
        ?TransferQuote $quote,
        ?IdempotencyKey $idempotencyKey,
        array $metadata
    ): LedgerEntry {
        $account = Account::where('user_id', $user->id)
            ->where('currency_id', $currency->id)
            ->where('type', Account::TYPE_CUSTOMER)
            ->lockForUpdate()
            ->first();

        if (! $account) {
            $account = $this->accountFor($user, $currency);
            $account->refresh();
        }

        $balanceAfter = $direction === LedgerEntry::DIRECTION_DEBIT
            ? Money::subtract($account->balance, $amount)
            : Money::add($account->balance, $amount);

        if ($direction === LedgerEntry::DIRECTION_DEBIT && Money::isLessThan($balanceAfter, '0')) {
            throw new RuntimeException("Insufficient {$currency->code} balance for this transaction.");
        }

        $account->forceFill(['balance' => $balanceAfter])->save();

        return LedgerEntry::create([
            'ledger_event_uuid' => $ledgerEventUuid,
            'transfer_group_uuid' => $transferGroupUuid,
            'account_id' => $account->id,
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'transaction_id' => $transaction?->id,
            'transfer_quote_id' => $quote?->id,
            'idempotency_key_id' => $idempotencyKey?->id,
            'event_type' => $eventType,
            'direction' => $direction,
            'status' => $status,
            'amount' => Money::round($amount),
            'balance_after' => $balanceAfter,
            'metadata' => $metadata,
            'posted_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function createInformationalEntry(
        User $user,
        Currency $currency,
        ?TransferQuote $quote,
        string $transferGroupUuid,
        string $ledgerEventUuid,
        string $eventType,
        string $status,
        float|string $amount,
        ?IdempotencyKey $idempotencyKey,
        array $metadata
    ): LedgerEntry {
        $account = $this->accountFor($user, $currency);

        return LedgerEntry::create([
            'ledger_event_uuid' => $ledgerEventUuid,
            'transfer_group_uuid' => $transferGroupUuid,
            'account_id' => $account->id,
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'transfer_quote_id' => $quote?->id,
            'idempotency_key_id' => $idempotencyKey?->id,
            'event_type' => $eventType,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'status' => $status,
            'amount' => Money::round($amount),
            'balance_after' => $account->balance,
            'metadata' => $metadata,
            'posted_at' => $status === LedgerEntry::STATUS_PENDING ? null : now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createTransactionProjection(
        User $owner,
        User $counterparty,
        TransferQuote $quote,
        string $type,
        float|string $amount,
        string $transferGroupUuid,
        array $validated
    ): Transaction {
        $sender = $type === Transaction::TYPE['Credit'] ? $counterparty : $owner;
        $receiver = $type === Transaction::TYPE['Credit'] ? $owner : $counterparty;
        unset($validated['quote']);

        return Transaction::create([
            'user_id' => $owner->id,
            'recipient_id' => $counterparty->id,
            'transfer_group_uuid' => $transferGroupUuid,
            'source_currency_id' => $quote->source_currency_id,
            'target_currency_id' => $quote->target_currency_id,
            'amount' => Money::round($amount),
            'rate' => $quote->rate,
            'transfer_fee' => $quote->transfer_fee,
            'variable_fee' => $quote->variable_fee,
            'fixed_fee' => $quote->fixed_fee,
            'type' => $type,
            'status' => Transaction::STATUS['Success'],
            'meta_data' => array_merge($validated, [
                'quote_uuid' => $quote->uuid,
                'transfer_group_uuid' => $transferGroupUuid,
                'sender_name' => $sender->full_name,
                'sender_email' => $sender->email,
                'receiver_name' => $receiver->full_name,
                'receiver_email' => $receiver->email,
            ]),
        ]);
    }

    private function createBalanceProjection(User $user, Transaction $transaction): void
    {
        $usd = $this->balanceForCode($user, 'USD');
        $eur = $this->balanceForCode($user, 'EUR');
        $ngn = $this->balanceForCode($user, 'NGN');

        CurrencyBalance::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'USD' => $usd,
            'EUR' => $eur,
            'NGN' => $ngn,
        ]);
    }

    private function balanceForCode(User $user, string $currencyCode): string
    {
        $currency = Currency::where('code', $currencyCode)->first();

        if (! $currency) {
            return '0.00';
        }

        return (string) Account::where('user_id', $user->id)
            ->where('currency_id', $currency->id)
            ->where('type', Account::TYPE_CUSTOMER)
            ->value('balance') ?: '0.00';
    }

    /**
     * @return array<string, string>
     */
    private function participantMetadata(User $sender, User $recipient): array
    {
        return [
            'sender_name' => $sender->full_name,
            'sender_email' => $sender->email,
            'receiver_name' => $recipient->full_name,
            'receiver_email' => $recipient->email,
        ];
    }
}

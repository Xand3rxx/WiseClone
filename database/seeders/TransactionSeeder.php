<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usdCurrency = Currency::where('code', 'USD')->first();
        $eurCurrency = Currency::where('code', 'EUR')->first();
        $ngnCurrency = Currency::where('code', 'NGN')->first();

        $adminUser = User::where('email', 'admin@wiseclone.com')->first();
        $customers = User::where('role_id', 2)->get();

        // Initial funding for all customers - give each $1000 USD
        $this->command->info('Creating initial funding transactions...');

        foreach ($customers as $customer) {
            $this->createInitialFunding($customer, $usdCurrency, $adminUser);
        }

        // Create some demo transactions between users
        $this->command->info('Creating demo transactions...');

        $mainUser = User::where('email', 'user@wiseclone.com')->first();
        $recipients = $customers->where('id', '!=', $mainUser->id)->take(5);

        // Create various transaction scenarios for the main demo user
        $this->createDemoTransactions($mainUser, $recipients, $usdCurrency, $eurCurrency, $ngnCurrency);

        $this->command->info('Transactions seeded successfully!');
    }

    /**
     * Create initial funding for a customer.
     */
    private function createInitialFunding(User $customer, Currency $usdCurrency, User $admin): void
    {
        $fundingAmount = 1000.00;

            $transaction = Transaction::create([
            'user_id' => $customer->id,
            'recipient_id' => $admin->id,
            'source_currency_id' => $usdCurrency->id,
            'target_currency_id' => $usdCurrency->id,
            'amount' => $fundingAmount,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        CurrencyBalance::create([
            'user_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'USD' => $fundingAmount,
            'EUR' => 0,
            'NGN' => 0,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);
    }

    /**
     * Create demo transactions for testing.
     */
    private function createDemoTransactions(
        User $sender,
        $recipients,
        Currency $usdCurrency,
        Currency $eurCurrency,
        Currency $ngnCurrency
    ): void {
        $recipientArray = $recipients->values()->all();

        if (count($recipientArray) < 3) {
            return;
        }

        // Transaction 1: USD to USD transfer (5 days ago)
        $this->createTransactionPair(
            $sender,
            $recipientArray[0],
            100.00,
            $usdCurrency,
            $usdCurrency,
            4.86,
            1.0,
            now()->subDays(5)
        );

        // Transaction 2: USD to EUR transfer (3 days ago)
        $this->createTransactionPair(
            $sender,
            $recipientArray[1],
            200.00,
            $usdCurrency,
            $eurCurrency,
            5.51,
            0.95,  // December 2024 USD to EUR rate
            now()->subDays(3)
        );

        // Transaction 3: USD to NGN transfer (1 day ago)
        $this->createTransactionPair(
            $sender,
            $recipientArray[2],
            150.00,
            $usdCurrency,
            $ngnCurrency,
            5.90,
            1600.00,  // December 2024 USD to NGN rate
            now()->subDays(1)
        );

        // Transaction 4: Incoming transfer from another user (2 days ago)
        $this->createIncomingTransaction(
            $recipientArray[0],
            $sender,
            50.00,
            $usdCurrency,
            $usdCurrency,
            4.86,
            1.0,
            now()->subDays(2)
        );
    }

    /**
     * Create a pair of transactions (debit for sender, credit for recipient).
     */
    private function createTransactionPair(
        User $sender,
        User $recipient,
        float $amount,
        Currency $sourceCurrency,
        Currency $targetCurrency,
        float $transferFee,
        float $rate,
        $timestamp
    ): void {
        $amountToConvert = $amount - $transferFee;
        $targetAmount = $amountToConvert * $rate;

        // Debit transaction for sender
        $debitTx = Transaction::create([
            'user_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'source_currency_id' => $sourceCurrency->id,
            'target_currency_id' => $targetCurrency->id,
            'amount' => $amount,
            'rate' => $rate,
            'transfer_fee' => $transferFee,
            'variable_fee' => $transferFee - 4.86,
            'fixed_fee' => 4.86,
            'type' => Transaction::TYPE['Debit'],
            'status' => Transaction::STATUS['Success'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        // Update sender's balance
        $senderBalance = $sender->latestCurrencyBalance;
        if ($senderBalance) {
            $newUSD = $sourceCurrency->code === 'USD'
                ? (float) $senderBalance->USD - $amount
                : (float) $senderBalance->USD;
            $newEUR = $sourceCurrency->code === 'EUR'
                ? (float) $senderBalance->EUR - $amount
                : (float) $senderBalance->EUR;
            $newNGN = $sourceCurrency->code === 'NGN'
                ? (float) $senderBalance->NGN - $amount
                : (float) $senderBalance->NGN;

            CurrencyBalance::create([
                'user_id' => $sender->id,
                'transaction_id' => $debitTx->id,
                'USD' => max(0, $newUSD),
                'EUR' => max(0, $newEUR),
                'NGN' => max(0, $newNGN),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        // Credit transaction for recipient
        $creditTx = Transaction::create([
            'user_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'source_currency_id' => $sourceCurrency->id,
            'target_currency_id' => $targetCurrency->id,
            'amount' => $targetAmount,
            'rate' => $rate,
            'transfer_fee' => $transferFee,
            'variable_fee' => $transferFee - 4.86,
            'fixed_fee' => 4.86,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        // Update recipient's balance
        $recipientBalance = $recipient->latestCurrencyBalance;
        if ($recipientBalance) {
            $newUSD = $targetCurrency->code === 'USD'
                ? (float) $recipientBalance->USD + $targetAmount
                : (float) $recipientBalance->USD;
            $newEUR = $targetCurrency->code === 'EUR'
                ? (float) $recipientBalance->EUR + $targetAmount
                : (float) $recipientBalance->EUR;
            $newNGN = $targetCurrency->code === 'NGN'
                ? (float) $recipientBalance->NGN + $targetAmount
                : (float) $recipientBalance->NGN;

            CurrencyBalance::create([
                'user_id' => $recipient->id,
                'transaction_id' => $creditTx->id,
                'USD' => $newUSD,
                'EUR' => $newEUR,
                'NGN' => $newNGN,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    /**
     * Create an incoming transaction (credit for receiver).
     */
    private function createIncomingTransaction(
        User $sender,
        User $recipient,
        float $amount,
        Currency $sourceCurrency,
        Currency $targetCurrency,
        float $transferFee,
        float $rate,
        $timestamp
    ): void {
        $targetAmount = ($amount - $transferFee) * $rate;

        // Credit transaction for the main user (recipient)
        $creditTx = Transaction::create([
            'user_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'source_currency_id' => $sourceCurrency->id,
            'target_currency_id' => $targetCurrency->id,
            'amount' => $targetAmount,
            'rate' => $rate,
            'transfer_fee' => $transferFee,
            'variable_fee' => 0,
            'fixed_fee' => $transferFee,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        // Update recipient's balance
        $recipientBalance = $recipient->latestCurrencyBalance;
        if ($recipientBalance) {
            CurrencyBalance::create([
                'user_id' => $recipient->id,
                'transaction_id' => $creditTx->id,
                'USD' => (float) $recipientBalance->USD + $targetAmount,
                'EUR' => (float) $recipientBalance->EUR,
                'NGN' => (float) $recipientBalance->NGN,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }
}

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('transactions', 'transfer_group_uuid')) {
                $table->uuid('transfer_group_uuid')->nullable()->after('recipient_id');
                $table->index('transfer_group_uuid');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->restrictForeignKey('users', 'role_id', 'roles');
        $this->restrictForeignKey('users', 'currency_id', 'currencies');
        $this->restrictForeignKey('transactions', 'user_id', 'users');
        $this->restrictForeignKey('transactions', 'recipient_id', 'users');
        $this->restrictForeignKey('transactions', 'source_currency_id', 'currencies');
        $this->restrictForeignKey('transactions', 'target_currency_id', 'currencies');
        $this->restrictForeignKey('charges', 'source_currency_id', 'currencies');
        $this->restrictForeignKey('charges', 'target_currency_id', 'currencies');
        $this->restrictForeignKey('currency_balances', 'user_id', 'users');
        $this->restrictForeignKey('currency_balances', 'transaction_id', 'transactions');
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'transfer_group_uuid')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropIndex(['transfer_group_uuid']);
                $table->dropColumn('transfer_group_uuid');
            });
        }
    }

    private function restrictForeignKey(string $table, string $column, string $references): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($column, $references): void {
            $blueprint->dropForeign([$column]);
            $blueprint->foreign($column)->references('id')->on($references)->restrictOnDelete();
        });
    }
};

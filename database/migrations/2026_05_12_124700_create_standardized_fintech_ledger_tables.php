<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('type', 30)->default('customer');
            $table->decimal('balance', 20, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'currency_id', 'type']);
            $table->index(['currency_id', 'closed_at']);
        });

        Schema::create('transfer_quotes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('source_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('target_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('source_amount', 20, 2);
            $table->decimal('target_amount', 20, 2);
            $table->decimal('rate', 20, 8);
            $table->decimal('fixed_fee', 20, 2);
            $table->decimal('variable_fee', 20, 2);
            $table->decimal('transfer_fee', 20, 2);
            $table->decimal('amount_to_convert', 20, 2);
            $table->json('fee_breakdown');
            $table->json('accepted_metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
            $table->index('accepted_at');
        });

        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('key', 100);
            $table->string('scope', 80);
            $table->string('request_hash', 64);
            $table->string('status', 30)->default('processing');
            $table->json('response_payload')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scope', 'key']);
            $table->index(['scope', 'status']);
        });

        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('ledger_event_uuid')->index();
            $table->uuid('transfer_group_uuid')->nullable()->index();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->restrictOnDelete();
            $table->foreignId('transfer_quote_id')->nullable()->constrained('transfer_quotes')->restrictOnDelete();
            $table->foreignId('idempotency_key_id')->nullable()->constrained('idempotency_keys')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('direction', 10);
            $table->string('status', 30);
            $table->decimal('amount', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'currency_id', 'status']);
            $table->index(['account_id', 'created_at']);
            $table->index(['event_type', 'status']);
        });

        Schema::create('reconciliation_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 30);
            $table->unsignedInteger('accounts_checked')->default(0);
            $table->unsignedInteger('mismatches_found')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_exports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 50);
            $table->string('status', 30);
            $table->string('path')->nullable();
            $table->string('checksum')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 30);
            $table->string('path')->nullable();
            $table->string('checksum')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
        Schema::dropIfExists('audit_exports');
        Schema::dropIfExists('reconciliation_runs');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('transfer_quotes');
        Schema::dropIfExists('accounts');
    }
};

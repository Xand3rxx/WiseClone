<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('source_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('target_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('reference', 50)->unique();
            $table->decimal('rate', 12, 6);
            $table->decimal('transfer_fee', 10, 2);
            $table->decimal('variable_fee', 10, 2);
            $table->decimal('fixed_fee', 10, 2);
            $table->enum('type', ['Debit', 'Credit']);
            $table->enum('status', ['Success', 'Pending', 'Failed'])->default('Pending');
            $table->json('meta_data')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'recipient_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};


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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('source_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('target_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('rate', 12, 6);
            $table->decimal('variable_percentage', 8, 4);
            $table->decimal('fixed_fee', 10, 4);
            $table->timestamps();

            $table->unique(['source_currency_id', 'target_currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};


<?php

declare(strict_types=1);

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
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('blocked_at')->nullable()->after('remember_token');
            $table->softDeletes()->after('blocked_at');
            $table->index(['blocked_at', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['blocked_at', 'deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn('blocked_at');
        });
    }
};

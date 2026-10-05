<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (!Schema::hasColumn('teachers', 'payout_by_user_id')) {
                    $table->foreignId('payout_by_user_id')->nullable()->after('payout_amount')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('teachers', 'payout_by_user_name')) {
                    $table->string('payout_by_user_name')->nullable()->after('payout_by_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (Schema::hasColumn('teachers', 'payout_by_user_id')) {
                    $table->dropConstrainedForeignId('payout_by_user_id');
                }
                if (Schema::hasColumn('teachers', 'payout_by_user_name')) {
                    $table->dropColumn('payout_by_user_name');
                }
            });
        }
    }
};

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
                if (!Schema::hasColumn('teachers', 'payout_status')) {
                    $table->string('payout_status')->default('pending')->after('salary_per_lesson');
                }
                if (!Schema::hasColumn('teachers', 'payout_date')) {
                    $table->date('payout_date')->nullable()->after('payout_status');
                }
                if (!Schema::hasColumn('teachers', 'payout_month')) {
                    $table->string('payout_month')->nullable()->after('payout_date');
                }
                if (!Schema::hasColumn('teachers', 'payout_amount')) {
                    $table->decimal('payout_amount', 10, 2)->nullable()->after('payout_month');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                foreach (['payout_status', 'payout_date', 'payout_month', 'payout_amount'] as $col) {
                    if (Schema::hasColumn('teachers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

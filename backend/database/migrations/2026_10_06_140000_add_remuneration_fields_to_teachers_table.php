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
                if (!Schema::hasColumn('teachers', 'remuneration_type')) {
                    $table->string('remuneration_type')->default('percentage')->after('weekly_lessons');
                }
                if (!Schema::hasColumn('teachers', 'rate_percentage')) {
                    $table->decimal('rate_percentage', 5, 2)->default(70.00)->after('remuneration_type');
                }
                if (!Schema::hasColumn('teachers', 'salary_per_lesson')) {
                    $table->decimal('salary_per_lesson', 8, 2)->default(35.00)->after('rate_percentage');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (Schema::hasColumn('teachers', 'rate_percentage')) {
                    $table->dropColumn('rate_percentage');
                }
                if (Schema::hasColumn('teachers', 'remuneration_type')) {
                    $table->dropColumn('remuneration_type');
                }
            });
        }
    }
};

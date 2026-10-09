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
                if (Schema::hasColumn('teachers', 'rate_percentage')) {
                    $table->decimal('rate_percentage', 5, 2)->default(70.00)->nullable()->change();
                }
                if (Schema::hasColumn('teachers', 'salary_per_lesson')) {
                    $table->decimal('salary_per_lesson', 8, 2)->default(25.00)->nullable()->change();
                }
                if (Schema::hasColumn('teachers', 'fixed_student_rate')) {
                    $table->decimal('fixed_student_rate', 8, 2)->default(15.00)->nullable()->change();
                }
                if (Schema::hasColumn('teachers', 'center_fixed_per_student')) {
                    $table->decimal('center_fixed_per_student', 8, 2)->default(20.00)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Pas besoin de rollback restrictif
    }
};

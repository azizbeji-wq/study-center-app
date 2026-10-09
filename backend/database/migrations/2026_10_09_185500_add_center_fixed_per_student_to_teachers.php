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
                if (!Schema::hasColumn('teachers', 'center_fixed_per_student')) {
                    $table->decimal('center_fixed_per_student', 8, 2)->default(20.00)->after('fixed_student_rate');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (Schema::hasColumn('teachers', 'center_fixed_per_student')) {
                    $table->dropColumn('center_fixed_per_student');
                }
            });
        }
    }
};

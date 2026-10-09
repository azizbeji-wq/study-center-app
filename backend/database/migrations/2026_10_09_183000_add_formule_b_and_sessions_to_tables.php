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
                if (!Schema::hasColumn('teachers', 'fixed_student_rate')) {
                    $table->decimal('fixed_student_rate', 8, 2)->default(15.00)->after('salary_per_lesson');
                }
            });
        }

        if (Schema::hasTable('student_subject_enrollments')) {
            Schema::table('student_subject_enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('student_subject_enrollments', 'sessions_per_month')) {
                    $table->integer('sessions_per_month')->default(4)->after('group_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (Schema::hasColumn('teachers', 'fixed_student_rate')) {
                    $table->dropColumn('fixed_student_rate');
                }
            });
        }

        if (Schema::hasTable('student_subject_enrollments')) {
            Schema::table('student_subject_enrollments', function (Blueprint $table) {
                if (Schema::hasColumn('student_subject_enrollments', 'sessions_per_month')) {
                    $table->dropColumn('sessions_per_month');
                }
            });
        }
    }
};

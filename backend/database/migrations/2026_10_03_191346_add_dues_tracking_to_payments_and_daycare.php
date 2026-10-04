<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('id')
                    ->constrained('students')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'daycare_child_id')) {
                $table->foreignId('daycare_child_id')->nullable()->after('student_id')
                    ->constrained('daycare_children')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->after('daycare_child_id')
                    ->constrained('teachers')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'sessions_paid')) {
                $table->unsignedInteger('sessions_paid')->default(0);
            }
            if (! Schema::hasColumn('payments', 'months_paid')) {
                $table->unsignedInteger('months_paid')->default(0);
            }
        });

        Schema::table('daycare_children', function (Blueprint $table) {
            if (! Schema::hasColumn('daycare_children', 'monthly_fee')) {
                $table->decimal('monthly_fee', 8, 2)->default(0);
            }
            if (! Schema::hasColumn('daycare_children', 'start_date')) {
                $table->date('start_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['student_id', 'daycare_child_id', 'teacher_id'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
            foreach (['sessions_paid', 'months_paid'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('daycare_children', function (Blueprint $table) {
            foreach (['monthly_fee', 'start_date'] as $column) {
                if (Schema::hasColumn('daycare_children', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
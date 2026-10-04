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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'group_name')) {
                $table->string('group_name')->nullable()->after('level');
            }
            if (!Schema::hasColumn('students', 'discount_type')) {
                $table->string('discount_type')->default('fixed')->after('remise'); // 'percentage' or 'fixed'
            }
            if (!Schema::hasColumn('students', 'base_fee')) {
                $table->decimal('base_fee', 8, 2)->default(0)->after('discount_type');
            }
        });

        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('student_subject_enrollments', 'group_name')) {
                $table->string('group_name')->nullable()->after('subject_name');
            }
            if (!Schema::hasColumn('student_subject_enrollments', 'price_per_month')) {
                $table->decimal('price_per_month', 8, 2)->default(0)->after('teacher_id');
            }
            if (!Schema::hasColumn('student_subject_enrollments', 'price_per_session')) {
                $table->decimal('price_per_session', 8, 2)->default(0)->after('price_per_month');
            }
        });

        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
                $table->string('group_name')->nullable();
                $table->string('subject_name');
                $table->date('session_date');
                $table->string('status')->default('present'); // present, absent, excused, late
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');

        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->dropColumn(['group_name', 'price_per_month', 'price_per_session']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['group_name', 'discount_type', 'base_fee']);
        });
    }
};

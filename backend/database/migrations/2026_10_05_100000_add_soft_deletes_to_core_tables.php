<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('teachers') && !Schema::hasColumn('teachers', 'deleted_at')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'deleted_at')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('daycare_children') && !Schema::hasColumn('daycare_children', 'deleted_at')) {
            Schema::table('daycare_children', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'deleted_at')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'deleted_at')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('daycare_children') && Schema::hasColumn('daycare_children', 'deleted_at')) {
            Schema::table('daycare_children', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

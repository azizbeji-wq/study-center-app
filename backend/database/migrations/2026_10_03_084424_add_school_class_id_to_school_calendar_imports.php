<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_calendar_imports', function (Blueprint $table) {
            $table->foreignId('school_class_id')
                ->nullable()
                ->after('id')
                ->constrained('school_classes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('school_calendar_imports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_class_id');
        });
    }
};
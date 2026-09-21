<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_calendars', function (Blueprint $table) {

            $table->id();

            $table->foreignId('school_class_id')
                ->constrained('school_classes')
                ->cascadeOnDelete();

            $table->foreignId('school_calendar_import_id')
                ->nullable()
                ->constrained('school_calendar_imports')
                ->nullOnDelete();

            $table->string('day');

            $table->string('subject');

            $table->string('teacher_name')
                ->nullable();

            $table->time('start_time');

            $table->time('end_time');

            $table->string('room')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_calendars');
    }
};
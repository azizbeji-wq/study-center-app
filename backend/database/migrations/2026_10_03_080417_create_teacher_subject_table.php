<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1A — Table pivot teacher_subject.
 *
 * Un professeur peut enseigner plusieurs matières.
 * Contrainte unique : un même couple (teacher, subject) ne peut apparaître qu'une fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unique(['teacher_id', 'subject_id']); // Un prof n'enseigne pas 2x la même matière
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subject');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1A — Création de la table subjects.
 *
 * Les anciennes colonnes (teachers.subject, student_subject_enrollments.subject_name)
 * sont CONSERVÉES en parallèle. La suppression se fera dans une migration finale validée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Ex : "Mathématiques"
            $table->string('slug')->unique();               // Normalisé : "mathematiques"
            $table->string('color', 7)->default('#6366f1'); // Couleur UI hexadécimale
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};


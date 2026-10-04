<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1A — Ajout de subject_id (FK nullable) sur student_subject_enrollments et attendances.
 *
 * RÈGLE : les anciennes colonnes subject_name sont CONSERVÉES (compatibilité front Vue actuel).
 * subject_id est nullable = la ligne peut coexister sans être rattachée à un Subject Eloquent
 * pendant la période de transition.
 *
 * La migration inverse supprime uniquement les colonnes ajoutées ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. student_subject_enrollments : ajout de subject_id (nullable FK)
        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('student_subject_enrollments', 'subject_id')) {
                $table->foreignId('subject_id')
                    ->nullable()
                    ->after('subject_name')
                    ->constrained('subjects')
                    ->nullOnDelete();
            }
        });

        // 2. attendances : ajout de subject_id (nullable FK)
        //    (attendances.subject_name restera en place pour la compatibilité actuelle)
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'subject_id')) {
                $table->foreignId('subject_id')
                    ->nullable()
                    ->after('subject_name')
                    ->constrained('subjects')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
        });

        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
        });
    }
};


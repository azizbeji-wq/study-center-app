<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix 1 : groups.teacher_id – séparer la notion "créateur utilisateur" et "enseignant responsable".
 *         Ajouter user_id (FK → users) pour stocker le créateur.
 *         teacher_id est maintenant strictement réservé à la FK vers teachers.
 *
 * Fix 2 : attendances – contrainte UNIQUE (student_id, subject_name, session_date)
 *         pour éviter les doublons de pointage.
 *
 * Fix 3 : student_subject_enrollments – contrainte UNIQUE (student_id, teacher_id, subject_name)
 *         pour éviter les inscriptions en double.
 *
 * Fix 4 : payments – s'assurer que student_id est renseigné sur les anciens enregistrements
 *         qui n'ont que student_name.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // FIX 1 — groups : ajouter user_id, corriger teacher_id
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('groups', function (Blueprint $table) {
            // 1a. Nouvelle colonne user_id = créateur du groupe (directeur, secrétaire ou enseignant)
            if (!Schema::hasColumn('groups', 'user_id')) {
                $table->foreignId('user_id')
                      ->nullable()
                      ->after('teacher_id')
                      ->constrained('users')
                      ->nullOnDelete();
            }
        });

        // 1b. Migrer les données existantes (MySQL uniquement) :
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE `groups`
                SET `user_id` = `teacher_id`
                WHERE `user_id` IS NULL
                  AND `teacher_id` IS NOT NULL
                  AND NOT EXISTS (
                    SELECT 1 FROM teachers WHERE teachers.id = `groups`.`teacher_id` AND teachers.deleted_at IS NULL
                  )
            ");

            DB::statement("
                UPDATE `groups`
                INNER JOIN teachers ON teachers.user_id = `groups`.user_id
                SET `groups`.teacher_id = teachers.id
                WHERE `groups`.user_id IS NOT NULL
                  AND NOT EXISTS (
                    SELECT 1 FROM teachers t2 WHERE t2.id = `groups`.teacher_id AND t2.deleted_at IS NULL
                  )
            ");

            DB::statement("
                UPDATE `groups`
                SET `teacher_id` = NULL
                WHERE `teacher_id` IS NOT NULL
                  AND NOT EXISTS (
                    SELECT 1 FROM teachers WHERE teachers.id = `groups`.`teacher_id` AND teachers.deleted_at IS NULL
                  )
            ");
        }

        // ─────────────────────────────────────────────────────────────────────
        // FIX 2 — attendances : contrainte UNIQUE
        // ─────────────────────────────────────────────────────────────────────
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                DELETE a1 FROM attendances a1
                INNER JOIN attendances a2
                ON a1.student_id = a2.student_id
                AND a1.subject_name = a2.subject_name
                AND a1.session_date = a2.session_date
                WHERE a1.id < a2.id
            ");
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (!$this->indexExists('attendances', 'attendances_student_subject_date_unique')) {
                $table->unique(
                    ['student_id', 'subject_name', 'session_date'],
                    'attendances_student_subject_date_unique'
                );
            }
        });

        // ─────────────────────────────────────────────────────────────────────
        // FIX 3 — student_subject_enrollments : contrainte UNIQUE
        // ─────────────────────────────────────────────────────────────────────
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                DELETE e1 FROM student_subject_enrollments e1
                INNER JOIN student_subject_enrollments e2
                ON e1.student_id = e2.student_id
                AND e1.teacher_id = e2.teacher_id
                AND e1.subject_name = e2.subject_name
                WHERE e1.id < e2.id
            ");
        }

        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            if (!$this->indexExists('student_subject_enrollments', 'enrollments_student_teacher_subject_unique')) {
                $table->unique(
                    ['student_id', 'teacher_id', 'subject_name'],
                    'enrollments_student_teacher_subject_unique'
                );
            }
        });

        // ─────────────────────────────────────────────────────────────────────
        // FIX 4 — payments : renseigner student_id depuis student_name (anciens enregistrements)
        // ─────────────────────────────────────────────────────────────────────
        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('payments', 'student_id') && Schema::hasColumn('payments', 'student_name')) {
            DB::statement("
                UPDATE payments p
                INNER JOIN students s ON s.name = p.student_name AND s.deleted_at IS NULL
                SET p.student_id = s.id
                WHERE p.student_id IS NULL
                  AND p.student_name IS NOT NULL
                  AND p.student_name != ''
            ");
        }
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if ($this->indexExists('attendances', 'attendances_student_subject_date_unique')) {
                $table->dropUnique('attendances_student_subject_date_unique');
            }
        });

        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            if ($this->indexExists('student_subject_enrollments', 'enrollments_student_teacher_subject_unique')) {
                $table->dropUnique('enrollments_student_teacher_subject_unique');
            }
        });
    }

    /**
     * Vérifie si un index nommé existe sur une table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};

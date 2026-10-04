<?php

namespace Tests\Feature\Phase1A;

use App\Models\Room;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests Feature — Phase 1A
 *
 * Vérifie : migration structure, déduplication subjects, relations Eloquent,
 * CRUD API /api/subjects, contraintes d'intégrité.
 *
 * Base : SQLite en mémoire (phpunit.xml — DB_CONNECTION=sqlite, DB_DATABASE=:memory:)
 */
class SubjectMigrationTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════════════════════════════════════
    // A. Structure des tables
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_subjects_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('subjects'));
    }

    public function test_subjects_table_has_expected_columns(): void
    {
        foreach (['id', 'name', 'slug', 'color', 'is_active', 'created_at', 'updated_at'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('subjects', $col),
                "Colonne manquante dans subjects : {$col}"
            );
        }
    }

    public function test_rooms_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('rooms'));
    }

    public function test_rooms_table_has_expected_columns(): void
    {
        foreach (['id', 'name', 'capacity', 'description', 'is_active'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('rooms', $col),
                "Colonne manquante dans rooms : {$col}"
            );
        }
    }

    public function test_teacher_subject_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('teacher_subject'));
    }

    public function test_student_subject_enrollments_has_subject_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('student_subject_enrollments', 'subject_id'),
            'La colonne subject_id est absente de student_subject_enrollments'
        );
    }

    public function test_student_subject_enrollments_still_has_subject_name_column(): void
    {
        // Compatibilité front Vue — subject_name DOIT rester en place
        $this->assertTrue(
            Schema::hasColumn('student_subject_enrollments', 'subject_name'),
            'La colonne subject_name a été supprimée (violation règle compatibilité Phase 1A)'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // B. Modèle Subject
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_can_create_subject(): void
    {
        $subject = Subject::create([
            'name'  => 'Mathématiques',
            'slug'  => 'mathematiques',
            'color' => '#6366f1',
        ]);

        $this->assertDatabaseHas('subjects', ['slug' => 'mathematiques']);
        $this->assertSame('Mathématiques', $subject->name);
        $this->assertTrue((bool) $subject->fresh()->is_active); // défaut true (cast via fresh() pour SQLite)
    }

    public function test_slug_is_unique_constraint(): void
    {
        Subject::create(['name' => 'Maths', 'slug' => 'maths']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Subject::create(['name' => 'Maths (dupliqué)', 'slug' => 'maths']);
    }

    public function test_normalize_slug_helper(): void
    {
        $this->assertSame('mathematiques', Subject::normalizeSlug('Mathématiques'));
        $this->assertSame('anglais', Subject::normalizeSlug('Anglais'));
        $this->assertSame('physique-chimie', Subject::normalizeSlug('Physique-Chimie'));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // C. Relations Eloquent
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_teacher_can_have_multiple_subjects(): void
    {
        $teacher  = Teacher::create(['name' => 'Prof. Test', 'subject' => 'Maths', 'phone' => '00000000', 'email' => 'prof@test.com']);
        $maths    = Subject::create(['name' => 'Mathématiques', 'slug' => 'mathematiques']);
        $physique = Subject::create(['name' => 'Physique', 'slug' => 'physique']);

        $teacher->subjects()->attach([$maths->id, $physique->id]);

        $this->assertCount(2, $teacher->fresh()->subjects);
    }

    public function test_teacher_subject_pivot_has_unique_constraint(): void
    {
        $teacher = Teacher::create(['name' => 'Prof. Test', 'subject' => 'Maths', 'phone' => '00000000', 'email' => 'prof2@test.com']);
        $maths   = Subject::create(['name' => 'Mathématiques', 'slug' => 'mathematiques']);

        $teacher->subjects()->attach($maths->id);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $teacher->subjects()->attach($maths->id); // doublon interdit par unique constraint
    }

    public function test_subject_belongs_to_many_teachers(): void
    {
        $t1 = Teacher::create(['name' => 'Prof A', 'subject' => 'Maths', 'phone' => '111', 'email' => 'a@test.com']);
        $t2 = Teacher::create(['name' => 'Prof B', 'subject' => 'Maths', 'phone' => '222', 'email' => 'b@test.com']);
        $s  = Subject::create(['name' => 'Mathématiques', 'slug' => 'mathematiques']);

        $s->teachers()->attach([$t1->id, $t2->id]);

        $this->assertCount(2, $s->fresh()->teachers);
    }

    public function test_enrollment_can_have_subject_id(): void
    {
        $student = Student::create(['name' => 'Élève Test', 'level' => '7ème', 'phone' => '000']);
        $teacher = Teacher::create(['name' => 'Prof Test', 'subject' => 'Maths', 'phone' => '111', 'email' => 'c@test.com']);
        $subject = Subject::create(['name' => 'Mathématiques', 'slug' => 'mathematiques']);

        $enrollment = StudentSubjectEnrollment::create([
            'student_id'  => $student->id,
            'subject_name'=> 'Mathématiques', // ancien champ conservé
            'subject_id'  => $subject->id,    // nouvelle FK
            'teacher_id'  => $teacher->id,
        ]);

        $this->assertSame($subject->id, $enrollment->fresh()->subject_id);
        $this->assertInstanceOf(Subject::class, $enrollment->fresh()->subject);
    }

    public function test_enrollment_subject_id_can_be_null(): void
    {
        // Compatibilité : subject_id nullable pendant la période de transition
        $student = Student::create(['name' => 'Élève 2', 'level' => '8ème', 'phone' => '123']);
        $teacher = Teacher::create(['name' => 'Prof 2', 'subject' => 'Français', 'phone' => '456', 'email' => 'd@test.com']);

        $enrollment = StudentSubjectEnrollment::create([
            'student_id'  => $student->id,
            'subject_name'=> 'Français',
            'subject_id'  => null,
            'teacher_id'  => $teacher->id,
        ]);

        $this->assertNull($enrollment->fresh()->subject_id);
        $this->assertNull($enrollment->fresh()->subject);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // D. API /api/subjects
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_api_index_returns_subjects(): void
    {
        Subject::create(['name' => 'Mathématiques', 'slug' => 'mathematiques']);
        Subject::create(['name' => 'Français', 'slug' => 'francais']);

        $response = $this->getJson('/api/subjects');

        $response->assertOk()
                 ->assertJsonCount(2)
                 ->assertJsonFragment(['slug' => 'mathematiques'])
                 ->assertJsonFragment(['slug' => 'francais']);
    }

    public function test_api_store_creates_subject(): void
    {
        $response = $this->postJson('/api/subjects', [
            'name'  => 'Physique',
            'color' => '#10b981',
        ]);

        $response->assertCreated()
                 ->assertJsonFragment(['slug' => 'physique', 'name' => 'Physique']);

        $this->assertDatabaseHas('subjects', ['slug' => 'physique']);
    }

    public function test_api_store_rejects_duplicate_slug(): void
    {
        Subject::create(['name' => 'Maths', 'slug' => 'maths']);

        $response = $this->postJson('/api/subjects', ['name' => 'Maths']);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Une matière avec un nom similaire existe déjà (slug : maths).']);
    }

    public function test_api_update_subject(): void
    {
        $subject = Subject::create(['name' => 'Ang', 'slug' => 'ang']);

        $response = $this->putJson("/api/subjects/{$subject->id}", ['name' => 'Anglais']);

        $response->assertOk()
                 ->assertJsonFragment(['slug' => 'anglais', 'name' => 'Anglais']);
    }

    public function test_api_destroy_with_enrollments_is_blocked(): void
    {
        $student = Student::create(['name' => 'Élève', 'level' => '9ème', 'phone' => '999']);
        $teacher = Teacher::create(['name' => 'Prof', 'subject' => 'X', 'phone' => '888', 'email' => 'e@test.com']);
        $subject = Subject::create(['name' => 'Chimie', 'slug' => 'chimie']);

        StudentSubjectEnrollment::create([
            'student_id'   => $student->id,
            'subject_name' => 'Chimie',
            'subject_id'   => $subject->id,
            'teacher_id'   => $teacher->id,
        ]);

        $response = $this->deleteJson("/api/subjects/{$subject->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_api_destroy_subject_without_enrollments(): void
    {
        $subject = Subject::create(['name' => 'Latin', 'slug' => 'latin']);

        $response = $this->deleteJson("/api/subjects/{$subject->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // E. Modèle Room
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_can_create_room(): void
    {
        $room = Room::create(['name' => 'Salle 101', 'capacity' => 25]);

        $this->assertDatabaseHas('rooms', ['name' => 'Salle 101']);
        $this->assertSame(25, $room->fresh()->capacity);
        $this->assertTrue((bool) $room->fresh()->is_active); // cast via fresh() pour SQLite
    }

    public function test_room_name_is_unique(): void
    {
        Room::create(['name' => 'Salle 101']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Room::create(['name' => 'Salle 101']);
    }
}


<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemunerationAndDiscountFormulasTest extends TestCase
{
    use RefreshDatabase;

    private User $director;

    protected function setUp(): void
    {
        parent::setUp();
        $this->director = User::factory()->create([
            'role' => 'director',
            'email' => 'directeur@test.tn',
        ]);
    }

    public function test_student_monthly_fee_calculates_sessions_times_session_price_and_sums_multiple_subjects(): void
    {
        // Enseignant 1 (Math): Prix séance fixé par l'administration = 25 TND
        $teacherMath = Teacher::create([
            'name' => 'Prof Math',
            'subject' => 'Mathématiques',
            'phone' => '99000111',
            'email' => 'math@test.tn',
            'salary_per_lesson' => 25.00,
            'remuneration_type' => 'percentage',
            'rate_percentage' => 70.00,
        ]);

        // Enseignant 2 (Physique): Prix séance fixé par l'administration = 20 TND
        $teacherPhys = Teacher::create([
            'name' => 'Prof Physique',
            'subject' => 'Physique',
            'phone' => '99000222',
            'email' => 'phys@test.tn',
            'salary_per_lesson' => 20.00,
            'remuneration_type' => 'fixed',
            'fixed_student_rate' => 15.00,
        ]);

        // Création d'un élève avec 2 matières :
        // Math: 4 séances * 25 = 100 TND
        // Physique: 4 séances * 20 = 80 TND
        // Total brut = 180 TND
        // Remise = 20 TND
        // Net à payer = 160 TND
        $payload = [
            'name' => 'Yassine Ben Ali',
            'level' => '3ème Sciences Expérimentales',
            'phone' => '22111333',
            'status' => 'active',
            'is_remise' => true,
            'remise' => 20.00,
            'discount_type' => 'fixed',
            'subjects' => [
                [
                    'subject' => 'Mathématiques',
                    'teacher_id' => $teacherMath->id,
                    'sessions_per_month' => 4,
                    'price_per_session' => 25.00,
                    'price_per_month' => 100.00,
                ],
                [
                    'subject' => 'Physique',
                    'teacher_id' => $teacherPhys->id,
                    'sessions_per_month' => 4,
                    'price_per_session' => 20.00,
                    'price_per_month' => 80.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->director)->postJson('/api/students', $payload);
        $response->assertStatus(201);

        $json = $response->json();
        $this->assertEquals(180.00, $json['calculated_fee']['gross']);
        $this->assertEquals(20.00, $json['calculated_fee']['discount']);
        $this->assertEquals(160.00, $json['calculated_fee']['netPayable']);
        $this->assertEquals(20.00, $json['calculated_fee']['center_absorbed_discount']);
        $this->assertCount(2, $json['subjects']);
    }

    public function test_discount_is_deducted_exclusively_from_administration_share_in_formule_c(): void
    {
        // Prof en Formule C (70%)
        // Prix séance = 25 TND. 4 séances = 100 TND brut.
        $teacher = Teacher::create([
            'name' => 'Prof Karim',
            'subject' => 'Mathématiques',
            'phone' => '98111222',
            'email' => 'karim@test.tn',
            'salary_per_lesson' => 25.00,
            'remuneration_type' => 'percentage',
            'rate_percentage' => 70.00,
        ]);

        $student = Student::create([
            'name' => 'Mariem Toumi',
            'level' => 'Bac Math',
            'phone' => '55443322',
            'is_remise' => true,
            'remise' => 20.00,
            'discount_type' => 'fixed',
        ]);

        StudentSubjectEnrollment::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'subject_name' => 'Mathématiques',
            'sessions_per_month' => 4,
            'price_per_session' => 25.00,
            'price_per_month' => 100.00,
        ]);

        // L'élève a payé 80 TND (100 - 20 TND remise) pour 4 séances
        Payment::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'student_name' => $student->name,
            'amount' => 80.00,
            'sessions_paid' => 4,
            'status' => 'paid',
            'date' => now()->toDateString(),
            'month' => now()->format('Y-m'),
        ]);

        // Vérification dans l'extrait officiel enseignant (extractTeachers)
        $extractRes = $this->actingAs($this->director)->getJson("/api/payments/extract-teachers?teacher_ids={$teacher->id}");
        $extractRes->assertOk();

        $tData = $extractRes->json('data.0');
        // Part enseignant protégée calculée sur le brut (100 TND * 70% = 70 TND)
        $this->assertEquals(70.00, $tData['summary']['total_teacher_share']);
        // Marge centre : 80 TND récoltés - 70 TND prof = 10 TND (le centre a absorbé les 20 TND de remise)
        $this->assertEquals(10.00, $tData['summary']['total_center_margin']);
    }

    public function test_formule_b_fixed_rate_per_student_remuneration(): void
    {
        // Prof en Formule B (Part fixe par élève = 15 TND)
        // Prix séance idara = 20 TND. 4 séances = 80 TND brut par élève.
        $teacher = Teacher::create([
            'name' => 'Prof Sami',
            'subject' => 'Sciences',
            'phone' => '97111222',
            'email' => 'sami@test.tn',
            'salary_per_lesson' => 20.00,
            'remuneration_type' => 'fixed',
            'fixed_student_rate' => 15.00,
        ]);

        $student1 = Student::create(['name' => 'Élève 1', 'level' => '1ère', 'phone' => '22000001']);
        $student2 = Student::create(['name' => 'Élève 2', 'level' => '1ère', 'phone' => '22000002']);

        StudentSubjectEnrollment::create([
            'student_id' => $student1->id,
            'teacher_id' => $teacher->id,
            'subject_name' => 'Sciences',
            'sessions_per_month' => 4,
            'price_per_session' => 20.00,
            'price_per_month' => 80.00,
        ]);
        StudentSubjectEnrollment::create([
            'student_id' => $student2->id,
            'teacher_id' => $teacher->id,
            'subject_name' => 'Sciences',
            'sessions_per_month' => 4,
            'price_per_session' => 20.00,
            'price_per_month' => 80.00,
        ]);

        // Les deux élèves paient 80 TND chacun (total 160 TND récoltés)
        Payment::create([
            'student_id' => $student1->id,
            'teacher_id' => $teacher->id,
            'student_name' => $student1->name,
            'amount' => 80.00,
            'sessions_paid' => 4,
            'status' => 'paid',
            'date' => now()->toDateString(),
            'month' => now()->format('Y-m'),
        ]);
        Payment::create([
            'student_id' => $student2->id,
            'teacher_id' => $teacher->id,
            'student_name' => $student2->name,
            'amount' => 80.00,
            'sessions_paid' => 4,
            'status' => 'paid',
            'date' => now()->toDateString(),
            'month' => now()->format('Y-m'),
        ]);

        // Vérification de la fiche enseignant (TeacherController index / show)
        $teacherRes = $this->actingAs($this->director)->getJson("/api/teachers/{$teacher->id}");
        $teacherRes->assertOk();

        // 2 élèves * 15 TND = 30 TND
        $this->assertEquals(30.00, $teacherRes->json('teacher_share'));
        // Marge centre : 160 - 30 = 130 TND
        $this->assertEquals(130.00, $teacherRes->json('center_share'));
    }
}

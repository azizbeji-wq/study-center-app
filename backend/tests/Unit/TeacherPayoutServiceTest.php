<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Session;
use App\Services\TeacherPayoutService;
use Tests\TestCase;

class TeacherPayoutServiceTest extends TestCase
{
    private TeacherPayoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TeacherPayoutService();
    }

    /**
     * TEST DU CAS CRITIQUE :
     * Un cours avec 3 élèves à plein tarif et 1 élève avec -50% de remise
     * doit rapporter EXACTEMENT la même rémunération à l'enseignant qu'un cours sans remise.
     * La remise commerciale est absorbée à 100 % par l'administration (marge du centre).
     */
    public function test_discount_absorption_rule_teacher_earns_same_with_and_without_discount(): void
    {
        // Enseignant rémunéré à 70% (Formule C)
        $teacher = new Teacher([
            'name' => 'Professeur Anis',
            'remuneration_type' => 'percentage',
            'rate_percentage' => 70.0,
            'salary_per_lesson' => 20.0,
        ]);

        $sessionPrice = 20.0; // 20 TND par élève pour la séance

        // ── CAS 1 : Cours SANS remise (4 élèves à plein tarif 20 TND) ───────────────
        $studentsNoDiscount = [];
        for ($i = 1; $i <= 4; $i++) {
            $student = new Student([
                'name' => "Élève Plein Tarif {$i}",
                'is_remise' => false,
                'remise' => 0.0,
            ]);
            $student->id = $i;
            $att = new Attendance([
                'student_id' => $student->id,
                'status' => 'present',
            ]);
            $att->setRelation('student', $student);
            $studentsNoDiscount[] = $att;
        }

        $sessionNoDiscount = new Session(
            $teacher,
            'Mathématiques',
            '2026-10-13',
            'Groupe Bac',
            $studentsNoDiscount,
            $sessionPrice
        );

        // ── CAS 2 : Cours AVEC remise (3 élèves à plein tarif + 1 élève à -50% de remise) ──
        $studentsWithDiscount = [];
        for ($i = 1; $i <= 3; $i++) {
            $student = new Student([
                'name' => "Élève Plein Tarif {$i}",
                'is_remise' => false,
                'remise' => 0.0,
            ]);
            $student->id = $i;
            $att = new Attendance([
                'student_id' => $student->id,
                'status' => 'present',
            ]);
            $att->setRelation('student', $student);
            $studentsWithDiscount[] = $att;
        }

        // 4ème élève bénéficiant de 50% de remise commerciale
        $discountedStudent = new Student([
            'name' => 'Élève Boursier (-50%)',
            'is_remise' => true,
            'discount_type' => 'percentage',
            'remise' => 50.0,
        ]);
        $discountedStudent->id = 4;
        $discountedAtt = new Attendance([
            'student_id' => $discountedStudent->id,
            'status' => 'present',
        ]);
        $discountedAtt->setRelation('student', $discountedStudent);
        $studentsWithDiscount[] = $discountedAtt;

        $sessionWithDiscount = new Session(
            $teacher,
            'Mathématiques',
            '2026-10-13',
            'Groupe Bac',
            $studentsWithDiscount,
            $sessionPrice
        );

        // ── EXÉCUTION DES CALCULS ──────────────────────────────────────────────
        $teacherEarningsNoDiscount = $this->service->calculateTeacherEarnings($sessionNoDiscount, $teacher);
        $teacherEarningsWithDiscount = $this->service->calculateTeacherEarnings($sessionWithDiscount, $teacher);

        $centerRevenueNoDiscount = $this->service->calculateCenterRevenue($sessionNoDiscount, $teacher);
        $centerRevenueWithDiscount = $this->service->calculateCenterRevenue($sessionWithDiscount, $teacher);

        // ── VÉRIFICATIONS DU CAS CRITIQUE ──────────────────────────────────────
        // 4 élèves à 20 TND = 80 TND brut. À 70%, l'enseignant gagne 4 * 14 TND = 56 TND.
        $this->assertEquals(56.0, $teacherEarningsNoDiscount);

        // 1. La part enseignant avec remise est STRICTEMENT ÉGALE à la part sans remise !
        $this->assertEquals(
            $teacherEarningsNoDiscount,
            $teacherEarningsWithDiscount,
            "La rémunération de l'enseignant ne doit JAMAIS chuter en présence d'un élève bénéficiant d'une remise !"
        );
        $this->assertEquals(56.0, $teacherEarningsWithDiscount);

        // 2. L'administration absorbe l'intégralité de la remise de 10 TND (50% de 20 TND) :
        // Sans remise : le centre encaisse 80 TND - 56 TND prof = 24 TND.
        $this->assertEquals(24.0, $centerRevenueNoDiscount);

        // Avec remise : le centre encaisse 70 TND (3*20 + 1*10) - 56 TND prof = 14 TND.
        // La marge du centre baisse exactement de 10 TND (24 - 14 = 10 TND).
        $this->assertEquals(14.0, $centerRevenueWithDiscount);
        $this->assertEquals(
            10.0,
            round($centerRevenueNoDiscount - $centerRevenueWithDiscount, 2),
            "La remise doit être absorbée à 100% par la marge du centre d'études !"
        );
    }

    /**
     * Test du mode FIXED_CENTER (Formule A) :
     * Le centre prélève sa part fixe par élève, le professeur perçoit le reste sur le prix de référence.
     */
    public function test_fixed_center_mode_preserves_teacher_share(): void
    {
        $teacher = new Teacher([
            'name' => 'Professeur Fatma',
            'remuneration_type' => 'fixed_center',
            'center_fixed_per_student' => 20.0, // 20 TND par mois => 5 TND par séance
            'salary_per_lesson' => 25.0,
        ]);

        $sessionPrice = 25.0; // 25 TND par élève par séance

        // 2 élèves : 1 plein tarif (25 TND) et 1 élève avec remise de 10 TND
        $s1 = new Student(['name' => 'Élève 1', 'is_remise' => false]);
        $s1->id = 1;
        $att1 = new Attendance(['student_id' => 1, 'status' => 'present']);
        $att1->setRelation('student', $s1);

        $s2 = new Student(['name' => 'Élève 2 Remise', 'is_remise' => true, 'discount_type' => 'fixed', 'remise' => 40.0]); // 40/4 = 10 TND remise
        $s2->id = 2;
        $att2 = new Attendance(['student_id' => 2, 'status' => 'present']);
        $att2->setRelation('student', $s2);

        $session = new Session($teacher, 'Physique', '2026-10-13', 'Groupe 1', [
            $att1,
            $att2,
        ], $sessionPrice);

        // Pour chaque élève, prix de référence = 25 TND.
        // Part centre par séance = 20/4 = 5 TND.
        // Part prof par élève = 25 - 5 = 20 TND.
        // Total prof pour 2 élèves = 40 TND (non impacté par la remise de l'élève 2).
        $teacherEarnings = $this->service->calculateTeacherEarnings($session, $teacher);
        $this->assertEquals(40.0, $teacherEarnings);

        // Chiffre d'affaires effectif encaissé : Élève 1 = 25 TND, Élève 2 = 15 TND => Total = 40 TND.
        // Marge centre = 40 TND effectif - 40 TND prof = 0 TND. (Le centre absorbe la réduction).
        $centerRevenue = $this->service->calculateCenterRevenue($session, $teacher);
        $this->assertEquals(0.0, $centerRevenue);
    }

    /**
     * Test du mode FIXED (Formule B) :
     * Rémunération fixe par élève ou par séance.
     */
    public function test_fixed_rate_mode(): void
    {
        $teacher = new Teacher([
            'name' => 'Professeur Sami',
            'remuneration_type' => 'fixed',
            'fixed_student_rate' => 15.0, // 15 TND par élève présent
        ]);

        $att1 = new Attendance(['status' => 'present']);
        $att1->setRelation('student', new Student(['is_remise' => false]));

        $att2 = new Attendance(['status' => 'present']);
        $att2->setRelation('student', new Student(['is_remise' => true, 'remise' => 50, 'discount_type' => 'percentage']));

        $att3 = new Attendance(['status' => 'absent']);
        $att3->setRelation('student', new Student());

        $students = [$att1, $att2, $att3];

        $session = new Session($teacher, 'Informatique', '2026-10-13', 'Groupe Info', $students, 25.0);

        // 2 élèves présents * 15 TND = 30 TND
        $earnings = $this->service->calculateTeacherEarnings($session, $teacher);
        $this->assertEquals(30.0, $earnings);
    }

    /**
     * Test des règles de cumul / rôles multiples :
     * 1) Directeur : isolation du salaire administratif et des gains de cours.
     * 2) Personnel : cumul distinct du salaire fixe avec les gains de cours.
     */
    public function test_multi_role_compensation_rules(): void
    {
        // 1. Directeur avec forfait de direction de 1200 TND
        $directorUser = new User([
            'id' => 1,
            'name' => 'Directeur Général',
            'role' => 'director',
        ]);

        $directorComp = $this->service->calculateMultiRoleCompensation($directorUser, '2026-10', 1200.0);
        $this->assertEquals('director', $directorComp['role']);
        $this->assertEquals('isolation', $directorComp['rule']);
        $this->assertEquals(1200.0, $directorComp['admin_base_salary']);
        $this->assertArrayHasKey('teaching_variable_earnings', $directorComp);

        // 2. Personnel avec salaire de base de 800 TND
        $staffUser = new User([
            'id' => 2,
            'name' => 'Secrétaire Sarah',
            'role' => 'secretary',
        ]);

        $staffComp = $this->service->calculateMultiRoleCompensation($staffUser, '2026-10', 800.0);
        $this->assertEquals('secretary', $staffComp['role']);
        $this->assertEquals('cumul_distinct', $staffComp['rule']);
        $this->assertEquals(800.0, $staffComp['staff_fixed_salary']);
        $this->assertArrayHasKey('total_combined_payout', $staffComp);
    }
}

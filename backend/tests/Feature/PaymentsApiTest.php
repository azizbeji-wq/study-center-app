<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DaycareChild;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::factory()->create([
            'role' => 'director',
            'is_active' => true,
        ]);

        $this->secretary = User::factory()->create([
            'role' => 'secretary',
            'is_active' => true,
            'permissions' => ['payments', 'dashboard'], // Pas de 'can_delete'
        ]);
    }

    /**
     * Happy Path (200) : Consultation de l'historique des paiements.
     */
    public function test_can_list_payments(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create();
        Payment::factory()->count(2)->create(['student_id' => $student->id]);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(2);
    }

    /**
     * Happy Path (200) : Calcul automatique des soldes et impayés (/api/payment-dues).
     */
    public function test_can_calculate_payment_dues(): void
    {
        Sanctum::actingAs($this->secretary);

        $teacher = Teacher::factory()->create(['salary_per_lesson' => 30.0]);
        $student = Student::factory()->create(['status' => 'active']);

        StudentSubjectEnrollment::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'subject_name' => 'Mathématiques',
            'sessions_per_month' => 4,
            'price_per_month' => 120.0,
            'price_per_session' => 30.0,
        ]);

        // Simuler 5 séances de présence
        for ($i = 0; $i < 5; $i++) {
            Attendance::create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'subject_name' => 'Mathématiques',
                'session_date' => now()->subDays($i)->toDateString(),
                'status' => 'present',
            ]);
        }

        // Payer 2 séances
        Payment::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'teacher_id' => $teacher->id,
            'sessions_paid' => 2,
            'amount' => 60.0,
            'month' => now()->format('Y-m'),
            'status' => 'paid',
            'date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/payment-dues');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'thresholds',
                'pricing',
                'students',
                'daycare',
                'totals',
            ]);

        // 5 présences - 2 payées = 3 impayées
        $response->assertJsonFragment([
            'studentId' => $student->id,
            'sessionsAttended' => 5,
            'sessionsPaid' => 2,
            'sessionsUnpaid' => 3,
            'amountDue' => 90.0,
        ]);
    }

    /**
     * Happy Path (201) : Enregistrement d'un paiement pour un élève avec génération de reçu.
     */
    public function test_can_create_student_payment(): void
    {
        Sanctum::actingAs($this->secretary);

        $student = Student::factory()->create(['name' => 'Yassine Ayari']);
        $teacher = Teacher::factory()->create();

        $payload = [
            'studentId' => $student->id,
            'teacherId' => $teacher->id,
            'sessionsPaid' => 4,
            'amount' => 140.0,
            'date' => now()->toDateString(),
            'month' => now()->format('Y-m'),
            'paymentMethod' => 'cash',
        ];

        $response = $this->postJson('/api/payments', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'studentId',
                'amount',
                'receiptNumber',
            ])
            ->assertJson([
                'studentId' => $student->id,
                'amount' => 140.0,
                'sessionsPaid' => 4,
            ]);

        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'amount' => 140.0,
            'sessions_paid' => 4,
            'status' => 'paid',
        ]);

        // Vérification de la traçabilité dans payment_audit_logs
        $this->assertDatabaseHas('payment_audit_logs', [
            'action' => 'created',
            'amount' => 140.0,
        ]);
    }

    /**
     * Validation (422) : Échec si ni studentId ni daycareChildId n'est fourni.
     */
    public function test_create_payment_validation_missing_beneficiary(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/payments', [
            'amount' => 100.0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['studentId', 'daycareChildId']);
    }

    /**
     * Validation (422) : studentId fourni sans teacherId ni sessionsPaid.
     */
    public function test_create_payment_validation_student_requires_teacher_and_sessions(): void
    {
        Sanctum::actingAs($this->secretary);
        $student = Student::factory()->create();

        $response = $this->postJson('/api/payments', [
            'studentId' => $student->id,
            'amount' => 50.0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teacherId', 'sessionsPaid']);
    }

    /**
     * Authentification (401) : Accès refusé pour invité.
     */
    public function test_unauthenticated_cannot_access_payments(): void
    {
        $response = $this->getJson('/api/payments');
        $response->assertStatus(401);
    }

    /**
     * Autorisation / RBAC (403) : Secrétaire sans can_delete ne peut pas supprimer un paiement.
     */
    public function test_secretary_cannot_delete_payment(): void
    {
        Sanctum::actingAs($this->secretary);
        $payment = Payment::factory()->create();

        $response = $this->deleteJson("/api/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    /**
     * Happy Path / RBAC (200) : Le directeur peut supprimer un paiement (Soft Delete).
     */
    public function test_director_can_delete_payment(): void
    {
        Sanctum::actingAs($this->director);
        $payment = Payment::factory()->create();

        $response = $this->deleteJson("/api/payments/{$payment->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }
}

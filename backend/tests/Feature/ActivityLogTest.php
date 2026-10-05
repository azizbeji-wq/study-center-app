<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $director;
    protected User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::create([
            'name'      => 'Directeur Fondateur',
            'email'     => 'director@studycenter.com',
            'password'  => Hash::make('password123'),
            'role'      => 'director',
            'is_active' => true,
        ]);

        $this->secretary = User::create([
            'name'      => 'Secrétaire Principale',
            'email'     => 'secretary@studycenter.com',
            'password'  => Hash::make('password123'),
            'role'      => 'secretary',
            'is_active' => true,
        ]);
    }

    public function test_activity_log_records_user_name_and_role(): void
    {
        $log = ActivityLog::record(
            $this->secretary,
            'payment',
            'created',
            'Paiement de 150 TND pour Ahmed Ben Ali'
        );

        $this->assertNotNull($log);
        $this->assertEquals('Secrétaire Principale', $log->user_name);
        $this->assertEquals('secretary', $log->user_role);
        $this->assertEquals($this->secretary->id, $log->user_id);
    }

    public function test_creating_student_logs_activity_with_user(): void
    {
        $response = $this->actingAs($this->secretary)->postJson('/api/students', [
            'name'   => 'Nouvel Élève Test',
            'level'  => '8ème année',
            'phone'  => '22334455',
            'status' => 'active',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'Secrétaire Principale',
            'user_role' => 'secretary',
            'type'      => 'student',
            'action'    => 'created',
        ]);
    }

    public function test_dashboard_recent_activity_includes_user_name_and_role(): void
    {
        ActivityLog::record(
            $this->secretary,
            'payment',
            'created',
            'Paiement de 120 TND encaissé'
        );

        $response = $this->actingAs($this->director)->getJson('/api/stats');
        $response->assertOk();

        $activities = $response->json('recentActivity');
        $this->assertNotEmpty($activities);

        $first = $activities[0];
        $this->assertArrayHasKey('user_name', $first);
        $this->assertArrayHasKey('user_role', $first);
        $this->assertEquals('Secrétaire Principale', $first['user_name']);
        $this->assertEquals('secretary', $first['user_role']);
    }

    public function test_payment_and_expense_history_show_user_who_did_action(): void
    {
        // 1. Payment created by secretary
        $student = Student::create(['name' => 'Test Student', 'level' => '7ème', 'phone' => '11223344']);
        $payment = Payment::create([
            'student_id'     => $student->id,
            'student_name'   => $student->name,
            'month'          => '2026-10',
            'amount'         => 90.00,
            'status'         => 'paid',
            'payment_method' => 'especes',
            'created_by'     => $this->secretary->id,
        ]);

        $resPayments = $this->actingAs($this->director)->getJson('/api/payments');
        $resPayments->assertOk();
        $payments = $resPayments->json();
        $this->assertNotEmpty($payments);
        $found = collect($payments)->firstWhere('id', $payment->id);
        $this->assertNotNull($found);
        $this->assertEquals('Secrétaire Principale', $found['createdByName']);
        $this->assertEquals('secretary', $found['createdByRole']);

        // 2. Expense created by director
        $expense = \App\Models\Expense::create([
            'title'          => 'Fournitures de bureau',
            'category'       => 'fournitures',
            'amount'         => 45.50,
            'date'           => now()->toDateString(),
            'payment_method' => 'especes',
            'created_by'     => $this->director->id,
        ]);

        $resExpenses = $this->actingAs($this->director)->getJson('/api/accounting/expenses');
        $resExpenses->assertOk();
        $expensesData = $resExpenses->json('data');
        $this->assertNotEmpty($expensesData);
        $foundExp = collect($expensesData)->firstWhere('id', $expense->id);
        $this->assertNotNull($foundExp);
        $this->assertEquals('Directeur Fondateur', $foundExp['creator']['name']);
        $this->assertEquals('director', $foundExp['creator']['role']);
    }
}

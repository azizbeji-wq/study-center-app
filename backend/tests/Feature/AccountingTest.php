<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\MonthlyClosing;
use App\Models\Payment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_cannot_access_accounting_summary(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);
        Sanctum::actingAs($secretary);

        $response = $this->getJson('/api/accounting/summary');
        $response->assertStatus(403);
    }

    public function test_director_can_access_accounting_summary_with_real_kpis(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        Sanctum::actingAs($director);

        $response = $this->getJson('/api/accounting/summary');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'selectedMonth',
                'range',
                'kpis' => [
                    'totalRevenue',
                    'totalExpenses',
                    'totalTeacherPayouts',
                    'netProfit',
                    'recoveryRate',
                    'totalUnpaid',
                    'unpaidStudents',
                    'unpaidDaycare',
                    'monthlyDiscounts',
                    'payingStudentsCount',
                    'averageRevenuePerStudent',
                    'projectedNextMonthRevenue',
                ],
                'monthsTrend',
                'byTeacher',
                'bySubject',
                'byLevel',
                'expenseDonut',
                'topDebtors',
                'dailyCashJournal',
                'monthlyClosing',
            ]);
    }

    public function test_director_can_crud_expenses(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        Sanctum::actingAs($director);

        // Create
        $response = $this->postJson('/api/accounting/expenses', [
            'title'               => 'Test Facture Climatiseur',
            'category'            => 'entretien',
            'amount'              => 120.50,
            'date'                => '2026-10-05',
            'payment_method'      => 'especes',
            'is_recurring'        => false,
            'receipt_reference'   => 'REC-TEST-001',
            'notes'               => 'Entretien annuel climatiseur salle 101',
        ]);
        $response->assertStatus(201);
        $expenseId = $response->json('id');

        // Read
        $listResponse = $this->getJson('/api/accounting/expenses?category=entretien');
        $listResponse->assertStatus(200);

        // Update
        $updateResponse = $this->putJson("/api/accounting/expenses/{$expenseId}", [
            'amount' => 150.00,
        ]);
        $updateResponse->assertStatus(200);

        // Delete
        $deleteResponse = $this->deleteJson("/api/accounting/expenses/{$expenseId}");
        $deleteResponse->assertStatus(200);
    }

    public function test_director_can_close_and_reopen_month(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        Sanctum::actingAs($director);

        $month = '2026-08';

        $response = $this->postJson('/api/accounting/close-month', [
            'month'       => $month,
            'school_year' => '2025-2026',
            'notes'       => 'Test clôture comptable.',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('closing.is_closed', true);

        // Reopen
        $reopenResponse = $this->postJson('/api/accounting/reopen-month', [
            'month' => $month,
        ]);
        $reopenResponse->assertStatus(200)
            ->assertJsonPath('closing.is_closed', false);
    }
}

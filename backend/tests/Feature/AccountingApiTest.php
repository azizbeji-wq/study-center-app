<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\MonthlyClosing;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingApiTest extends TestCase
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
            'permissions' => ['students', 'payments'], // Ne possède PAS 'reports'
        ]);
    }

    /**
     * Happy Path (200) : Le directeur consulte la synthèse financière globale (KPIs).
     */
    public function test_director_can_get_accounting_summary(): void
    {
        Sanctum::actingAs($this->director);

        $student = Student::factory()->create();
        Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 500.0,
            'status' => 'paid',
            'date' => now()->toDateString(),
        ]);

        Expense::factory()->create([
            'title' => 'Loyer',
            'amount' => 200.0,
            'category' => 'loyer',
            'date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/accounting/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'selectedMonth',
                'kpis' => [
                    'totalRevenue',
                    'totalExpenses',
                    'totalTeacherPayouts',
                    'netProfit',
                    'recoveryRate',
                ],
                'monthsTrend',
                'expenseDonut',
            ]);
    }

    /**
     * Autorisation / RBAC (403) : La secrétaire sans la permission reports se voit refuser l'accès.
     */
    public function test_secretary_cannot_access_accounting_without_permission(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->getJson('/api/accounting/summary');

        $response->assertStatus(403);
    }

    /**
     * Authentification (401) : Accès refusé pour invité non connecté.
     */
    public function test_unauthenticated_cannot_access_accounting(): void
    {
        $response = $this->getJson('/api/accounting/summary');

        $response->assertStatus(401);
    }

    /**
     * Happy Path (201) : Enregistrement d'une dépense réelle.
     */
    public function test_can_create_expense(): void
    {
        Sanctum::actingAs($this->director);

        $payload = [
            'title' => 'Facture Internet Fibre',
            'category' => 'internet_tel',
            'amount' => 75.50,
            'date' => now()->toDateString(),
            'payment_method' => 'virement',
            'is_recurring' => true,
            'recurring_frequency' => 'monthly',
            'notes' => 'Abonnement mensuel Tunisie Télécom',
        ];

        $response = $this->postJson('/api/accounting/expenses', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'category',
                'amount',
                'date',
            ])
            ->assertJson([
                'title' => 'Facture Internet Fibre',
                'amount' => 75.50,
                'category' => 'internet_tel',
            ]);

        $this->assertDatabaseHas('expenses', [
            'title' => 'Facture Internet Fibre',
            'amount' => 75.50,
            'category' => 'internet_tel',
            'is_recurring' => true,
        ]);
    }

    /**
     * Validation (422) : Échec si la catégorie de dépense est invalide ou montant négatif.
     */
    public function test_create_expense_validation_errors(): void
    {
        Sanctum::actingAs($this->director);

        $response = $this->postJson('/api/accounting/expenses', [
            'title' => 'Dépense Invalide',
            'category' => 'categorie_inconnue',
            'amount' => -20,
            'date' => 'date-invalide',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category', 'amount', 'date']);
    }

    /**
     * Happy Path (200) : Clôture mensuelle d'un exercice comptable.
     */
    public function test_can_close_month(): void
    {
        Sanctum::actingAs($this->director);

        $month = now()->format('Y-m');

        $payload = [
            'month' => $month,
            'school_year' => '2025-2026',
            'notes' => 'Clôture mensuelle validée par le directeur.',
        ];

        $response = $this->postJson('/api/accounting/close-month', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => "Mois {$month} clôturé avec succès.",
            ]);

        $this->assertDatabaseHas('monthly_closings', [
            'month' => $month,
            'is_closed' => true,
        ]);
    }

    /**
     * Happy Path (200) : Réouverture d'un mois clôturé.
     */
    public function test_can_reopen_month(): void
    {
        Sanctum::actingAs($this->director);

        $month = now()->format('Y-m');

        MonthlyClosing::create([
            'month' => $month,
            'is_closed' => true,
            'closed_at' => now(),
            'total_revenue' => 1000.0,
            'total_expenses' => 400.0,
            'net_profit' => 600.0,
        ]);

        $response = $this->postJson('/api/accounting/reopen-month', [
            'month' => $month,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => "Le mois {$month} a été réouvert.",
            ]);

        $this->assertDatabaseHas('monthly_closings', [
            'month' => $month,
            'is_closed' => false,
        ]);
    }
}

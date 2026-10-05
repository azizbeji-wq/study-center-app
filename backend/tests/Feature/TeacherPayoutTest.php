<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_mark_teacher_payout_as_paid_and_pending(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        Sanctum::actingAs($director);

        $teacher = Teacher::create([
            'name' => 'Professeur Anis',
            'subject' => 'Mathématiques',
            'phone' => '22113344',
            'email' => 'anis@test.com',
            'weekly_lessons' => 4,
            'salary_per_lesson' => 35,
            'payout_status' => 'pending',
        ]);

        // 1. Le directeur marque que le prof a pris son argent
        $res = $this->postJson("/api/teachers/{$teacher->id}/toggle-payout", [
            'status' => 'paid',
            'amount' => 560,
            'date'   => '2026-10-05',
            'month'  => 'Octobre 2026',
        ]);

        $res->assertOk();
        $this->assertEquals('paid', $teacher->fresh()->payout_status);
        $this->assertEquals('2026-10-05', $teacher->fresh()->payout_date->format('Y-m-d'));
        $this->assertEquals(560, (float) $teacher->fresh()->payout_amount);

        // 2. Le directeur peut annuler / remettre en attente
        $res2 = $this->postJson("/api/teachers/{$teacher->id}/toggle-payout", [
            'status' => 'pending',
        ]);

        $res2->assertOk();
        $this->assertEquals('pending', $teacher->fresh()->payout_status);
        $this->assertNull($teacher->fresh()->payout_date);
    }

    public function test_secretary_cannot_toggle_teacher_payout(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);
        Sanctum::actingAs($secretary);

        $teacher = Teacher::create([
            'name' => 'Professeur Khaled',
            'subject' => 'Physique',
            'phone' => '99887766',
            'email' => 'khaled@test.com',
        ]);

        // La secrétaire ne doit pas avoir le droit de modifier le statut de versement
        $res = $this->postJson("/api/teachers/{$teacher->id}/toggle-payout", [
            'status' => 'paid',
        ]);

        $res->assertStatus(403);
    }

    public function test_accounting_summary_reflects_paid_status_and_zero_remaining(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        Sanctum::actingAs($director);

        $teacher = Teacher::create([
            'name' => 'Professeur Anis',
            'subject' => 'Mathématiques',
            'phone' => '22113344',
            'email' => 'anis@test.com',
            'salary_per_lesson' => 35,
            'payout_status' => 'pending',
        ]);

        \App\Models\Payment::create([
            'student_name' => 'Eleve Test',
            'teacher_id' => $teacher->id,
            'amount' => 100,
            'sessions_paid' => 2,
            'status' => 'paid',
            'date' => now()->toDateString(),
            'month' => now()->format('Y-m'),
            'method' => 'cash',
        ]);

        // Toggle to paid
        $this->postJson("/api/teachers/{$teacher->id}/toggle-payout", [
            'status' => 'paid',
            'amount' => 70,
        ])->assertOk();

        // Check accounting summary
        $res = $this->getJson('/api/accounting/summary');
        $res->assertOk();
        $teachers = collect($res->json('byTeacher'));
        $target = $teachers->firstWhere('id', $teacher->id);

        $this->assertNotNull($target);
        $this->assertEquals('paid', $target['payoutStatus']);
        $this->assertEquals(70, $target['paidAmount']);
        $this->assertEquals(0, $target['remainingToPay']);
    }
}


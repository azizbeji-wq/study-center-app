<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserGuideProgress;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $secretary;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::create([
            'name'      => 'Directeur Test',
            'email'     => 'dir@test.tn',
            'password'  => bcrypt('secret'),
            'role'      => 'director',
            'is_active' => true,
        ]);

        $this->secretary = User::create([
            'name'      => 'Secrétaire Test',
            'email'     => 'sec@test.tn',
            'password'  => bcrypt('secret'),
            'role'      => 'secretary',
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name'      => 'Enseignant Test',
            'email'     => 'ens@test.tn',
            'password'  => bcrypt('secret'),
            'role'      => 'teacher',
            'is_active' => true,
        ]);
    }

    // ── 1. Non connecté → 401 ───────────────────────────────
    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/guide/progress')->assertStatus(401);
        $this->postJson('/api/guide/progress/tour.students')->assertStatus(401);
        $this->deleteJson('/api/guide/progress/tour.students')->assertStatus(401);
    }

    // ── 2. Clé inconnue → 422 ───────────────────────────────
    public function test_unknown_key_is_rejected_with_422(): void
    {
        $this->actingAs($this->director)
            ->postJson('/api/guide/progress/hack.admin')
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Clé inconnue.']);

        $this->actingAs($this->director)
            ->deleteJson('/api/guide/progress/hack.admin')
            ->assertStatus(422);
    }

    // ── 3. Marquer fait et récupérer ────────────────────────
    public function test_director_can_mark_and_retrieve_progress(): void
    {
        $this->actingAs($this->director)
            ->postJson('/api/guide/progress/tour.students')
            ->assertStatus(200)
            ->assertJsonFragment(['key' => 'tour.students']);

        $this->actingAs($this->director)
            ->getJson('/api/guide/progress')
            ->assertStatus(200)
            ->assertJsonFragment(['completed_keys' => ['tour.students']]);
    }

    // ── 4. Un utilisateur ne voit que sa propre progression ─
    public function test_user_cannot_see_another_users_progress(): void
    {
        // Le directeur marque une clé
        $this->actingAs($this->director)
            ->postJson('/api/guide/progress/tour.students');

        // La secrétaire récupère sa progression : vide
        $response = $this->actingAs($this->secretary)
            ->getJson('/api/guide/progress')
            ->assertStatus(200);

        $this->assertEmpty($response->json('completed_keys'));
    }

    // ── 5. Annuler une clé ──────────────────────────────────
    public function test_user_can_unmark_a_key(): void
    {
        $this->actingAs($this->director)
            ->postJson('/api/guide/progress/banner.students');

        $this->actingAs($this->director)
            ->deleteJson('/api/guide/progress/banner.students')
            ->assertStatus(200);

        $response = $this->actingAs($this->director)
            ->getJson('/api/guide/progress')
            ->assertStatus(200);

        $this->assertNotContains('banner.students', $response->json('completed_keys'));
    }

    // ── 6. Les booléens de démarrage ne contiennent aucune donnée personnelle ─
    public function test_starting_checks_contain_no_personal_data(): void
    {
        $response = $this->actingAs($this->director)
            ->getJson('/api/guide/progress')
            ->assertStatus(200);

        $checks = $response->json('starting_checks');
        $this->assertNotNull($checks);

        // Vérifie que les valeurs sont des booléens uniquement
        foreach ($checks as $value) {
            $this->assertIsBool($value);
        }

        // Vérifie qu'aucun nom, email ou montant n'est exposé
        $json = $response->getContent();
        $this->assertStringNotContainsString('@', $json);
        $this->assertStringNotContainsString('Directeur Test', $json);
        $this->assertStringNotContainsString('Secrétaire Test', $json);
    }

    // ── 7. L'enseignant n'obtient pas les booléens de démarrage du directeur ─
    public function test_teacher_does_not_get_starting_checks(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/guide/progress')
            ->assertStatus(200);

        $this->assertNull($response->json('starting_checks'));
    }

    // ── 8. Les booléens détectent les matières, enseignants, élèves ─
    public function test_starting_checks_reflect_database_state(): void
    {
        // Au départ : tout est vide
        $response = $this->actingAs($this->director)
            ->getJson('/api/guide/progress')
            ->assertStatus(200);

        $checks = $response->json('starting_checks');
        $this->assertFalse($checks['has_subjects']);
        $this->assertFalse($checks['has_teachers']);
        $this->assertFalse($checks['has_students']);

        // Créer une matière et vérifier
        Subject::create(['name' => 'Mathématiques', 'slug' => 'mathematiques', 'color' => '#3B82F6']);

        $response2 = $this->actingAs($this->director)
            ->getJson('/api/guide/progress')
            ->assertStatus(200);

        $this->assertTrue($response2->json('starting_checks.has_subjects'));
        // Les autres restent faux
        $this->assertFalse($response2->json('starting_checks.has_students'));
    }

    // ── 9. Double marquage idempotent ───────────────────────
    public function test_marking_twice_does_not_create_duplicates(): void
    {
        $this->actingAs($this->director)
            ->postJson('/api/guide/progress/tour.groups')
            ->assertStatus(200);

        $this->actingAs($this->director)
            ->postJson('/api/guide/progress/tour.groups')
            ->assertStatus(200);

        $count = UserGuideProgress::where('user_id', $this->director->id)
            ->where('key', 'tour.groups')
            ->count();

        $this->assertEquals(1, $count);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\StudentSubjectEnrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 1A — Commande de migration des matières.
 *
 * En mode --dry-run : affiche un rapport SANS rien écrire en base.
 * Sans --dry-run    : crée les subjects, remplit teacher_subject et subject_id.
 *
 * Usage :
 *   php artisan subjects:migrate --dry-run
 *   php artisan subjects:migrate
 */
class SubjectsMigrate extends Command
{
    protected $signature = 'subjects:migrate
                            {--dry-run : Affiche le rapport sans écrire en base}';

    protected $description = 'Phase 1A : Migre les matières (texte libre) vers la table subjects structurée.';

    // ─── Couleurs affectées par ordre d'apparition ────────────────────────────
    private array $palette = [
        '#6366f1', '#ec4899', '#f59e0b', '#10b981',
        '#3b82f6', '#ef4444', '#8b5cf6', '#14b8a6',
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('╔══════════════════════════════════════════════════════╗');
            $this->warn('║  MODE DRY-RUN — Aucune écriture en base de données  ║');
            $this->warn('╚══════════════════════════════════════════════════════╝');
        } else {
            $this->info('╔══════════════════════════════════════════════════════╗');
            $this->info('║  MODE RÉEL — Écriture en base de données             ║');
            $this->info('╚══════════════════════════════════════════════════════╝');
        }

        // ── 1. Collecte des noms de matières ───────────────────────────────────
        $rawNames = collect();

        // Depuis teachers.subject
        Teacher::whereNotNull('subject')
            ->where('subject', '!=', '')
            ->get(['id', 'name', 'subject'])
            ->each(fn ($t) => $rawNames->push([
                'source'     => 'teachers',
                'row_id'     => $t->id,
                'row_label'  => $t->name,
                'raw'        => $t->subject,
            ]));

        // Depuis student_subject_enrollments.subject_name
        StudentSubjectEnrollment::whereNotNull('subject_name')
            ->where('subject_name', '!=', '')
            ->get(['id', 'subject_name', 'teacher_id'])
            ->each(fn ($e) => $rawNames->push([
                'source'     => 'enrollments',
                'row_id'     => $e->id,
                'row_label'  => "enrollment#{$e->id}",
                'raw'        => $e->subject_name,
            ]));

        // ── 2. Déduplication par slug ──────────────────────────────────────────
        // On groupe toutes les valeurs brutes par leur slug normalisé.
        $grouped = $rawNames->groupBy(fn ($r) => Str::slug($r['raw'], '-'));

        // ── 3. Rapport : liste des subjects à créer ────────────────────────────
        $this->newLine();
        $this->info('═══ MATIÈRES DÉTECTÉES ═══════════════════════════════════');

        $subjectMap = []; // slug → Subject instance (ou tableau si dry-run)
        $colorIndex = 0;

        foreach ($grouped as $slug => $entries) {
            // Le nom canonique = la valeur la plus fréquente dans le groupe
            $canonicalName = $entries->countBy('raw')->sortDesc()->keys()->first();

            // Toutes les variantes du slug (pour détecter les aliases)
            $variants = $entries->pluck('raw')->unique()->values()->toArray();
            $isAlias  = count($variants) > 1;

            if ($isAlias) {
                $this->warn("  [VARIANTES] slug=\"{$slug}\"  canonical=\"{$canonicalName}\"");
                foreach ($variants as $v) {
                    $this->line("              → \"{$v}\"");
                }
            } else {
                $this->line("  [OK]        \"{$canonicalName}\"  (slug={$slug})");
            }

            $color = $this->palette[$colorIndex % count($this->palette)];
            $colorIndex++;

            // Enregistrement réel si pas dry-run
            if (! $isDryRun) {
                $subject = Subject::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name'      => $canonicalName,
                        'color'     => $color,
                        'is_active' => true,
                    ]
                );
                $subjectMap[$slug] = $subject;
            } else {
                $subjectMap[$slug] = ['slug' => $slug, 'name' => $canonicalName];
            }
        }

        // ── 4. Rapport : rattachement teachers → teacher_subject ───────────────
        $this->newLine();
        $this->info('═══ RATTACHEMENT ENSEIGNANTS → MATIÈRES ══════════════════');

        $teacherReport = ['paired' => [], 'not_found' => []];

        Teacher::whereNotNull('subject')->where('subject', '!=', '')->get()->each(function ($teacher) use (&$subjectMap, &$teacherReport, $isDryRun) {
            $slug = Str::slug($teacher->subject, '-');

            if (! isset($subjectMap[$slug])) {
                $teacherReport['not_found'][] = [
                    'teacher_id'   => $teacher->id,
                    'teacher_name' => $teacher->name,
                    'subject_raw'  => $teacher->subject,
                    'slug'         => $slug,
                ];
                $this->error("  [INTROUVABLE] Teacher #{$teacher->id} \"{$teacher->name}\" → subject=\"{$teacher->subject}\" (slug={$slug})");
                return;
            }

            $subjectEntry = $subjectMap[$slug];
            $subjectName  = is_array($subjectEntry) ? $subjectEntry['name'] : $subjectEntry->name;
            $this->line("  [OK] Teacher #{$teacher->id} \"{$teacher->name}\" → \"{$subjectName}\"");

            $teacherReport['paired'][] = ['teacher_id' => $teacher->id, 'subject_slug' => $slug];

            if (! $isDryRun && $subjectEntry instanceof Subject) {
                $teacher->subjects()->syncWithoutDetaching([$subjectEntry->id]);
            }
        });

        // ── 5. Rapport : rattachement enrollments → subject_id ────────────────
        $this->newLine();
        $this->info('═══ RATTACHEMENT INSCRIPTIONS → subject_id ═══════════════');

        $enrollReport = ['paired' => 0, 'not_found' => []];

        StudentSubjectEnrollment::whereNull('subject_id')
            ->whereNotNull('subject_name')
            ->where('subject_name', '!=', '')
            ->get()
            ->each(function ($enrollment) use (&$subjectMap, &$enrollReport, $isDryRun) {
                $slug = Str::slug($enrollment->subject_name, '-');

                if (! isset($subjectMap[$slug])) {
                    $enrollReport['not_found'][] = [
                        'enrollment_id' => $enrollment->id,
                        'subject_raw'   => $enrollment->subject_name,
                        'slug'          => $slug,
                    ];
                    $this->error("  [INTROUVABLE] Enrollment #{$enrollment->id} → subject_name=\"{$enrollment->subject_name}\"");
                    return;
                }

                $subjectEntry = $subjectMap[$slug];
                $enrollReport['paired']++;

                if (! $isDryRun && $subjectEntry instanceof Subject) {
                    $enrollment->update(['subject_id' => $subjectEntry->id]);
                }
            });

        $this->line("  → {$enrollReport['paired']} inscription(s) appariée(s)");
        if (! empty($enrollReport['not_found'])) {
            $this->warn("  → " . count($enrollReport['not_found']) . " inscription(s) introuvable(s) — subject_id restera NULL");
        }

        // ── 6. Résumé ──────────────────────────────────────────────────────────
        $this->newLine();
        $this->info('═══ RÉSUMÉ ════════════════════════════════════════════════');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Matières distinctes (par slug)',   count($grouped)],
                ['Enseignants appariés',             count($teacherReport['paired'])],
                ['Enseignants introuvables',         count($teacherReport['not_found'])],
                ['Inscriptions appariées',           $enrollReport['paired']],
                ['Inscriptions introuvables',        count($enrollReport['not_found'])],
                ['Mode',                             $isDryRun ? '🔍 DRY-RUN (aucune écriture)' : '✅ RÉEL'],
            ]
        );

        if (! empty($teacherReport['not_found']) || ! empty($enrollReport['not_found'])) {
            $this->newLine();
            $this->warn('⚠  Des lignes n\'ont pas pu être appariées automatiquement.');
            $this->warn('   Vérifiez les noms ci-dessus et créez les matières manuellement si besoin.');
            $this->warn('   Ces lignes ne seront PAS supprimées — subject_id restera NULL.');
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('Dry-run terminé. Aucune donnée modifiée.');
            $this->line('Pour exécuter la migration réelle :');
            $this->line('  php artisan subjects:migrate');
        } else {
            $this->newLine();
            $this->info('✅ Migration terminée avec succès.');
        }

        return Command::SUCCESS;
    }
}

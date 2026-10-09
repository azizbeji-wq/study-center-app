<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ResetBusinessDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'center:reset-data
                            {--force : Exécuter le nettoyage sans demander de confirmation}
                            {--with-catalogs : Supprimer également les matières, salles, écoles et classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vide toutes les données opérationnelles et de test en préservant STRICTEMENT les comptes de connexion (users, tokens).';

    /**
     * Tables protégées qui ne doivent JAMAIS être vidées ou altérées.
     */
    private const PROTECTED_TABLES = [
        'users',
        'personal_access_tokens',
        'sessions',
        'password_reset_tokens',
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    /**
     * Tables de données opérationnelles à vider par défaut.
     */
    private const DEFAULT_BUSINESS_TABLES = [
        'payment_audit_logs',
        'payments',
        'attendances',
        'student_subject_enrollments',
        'students',
        'teacher_subject',
        'teachers',
        'daycare_children',
        'schedule_items',
        'expenses',
        'monthly_closings',
        'activity_logs',
        'school_calendar_imports',
    ];

    /**
     * Tables de catalogues / référentiels (optionnelles via --with-catalogs).
     */
    private const CATALOG_TABLES = [
        'school_classes',
        'schools',
        'subjects',
        'rooms',
    ];

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║  NETTOYAGE DES DONNÉES DU CENTRE DE SOUTIEN                  ║');
        $this->info('║  (Comptes de connexion et sessions préservés à 100%)         ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');

        $usersCount = Schema::hasTable('users') ? DB::table('users')->count() : 0;
        $tokensCount = Schema::hasTable('personal_access_tokens') ? DB::table('personal_access_tokens')->count() : 0;

        $this->line("Comptes utilisateurs protégés détectés : <fg=green;options=bold>{$usersCount}</>");
        $this->line("Tokens de session protégés détectés    : <fg=green;options=bold>{$tokensCount}</>");

        if (!$this->option('force')) {
            $confirmed = $this->confirm(
                'ATTENTION: Vous êtes sur le point de supprimer TOUTES les données de test (élèves, profs, paiements, etc.). Continuer ?',
                false
            );

            if (!$confirmed) {
                $this->warn('Opération annulée par l’utilisateur.');
                return 0;
            }
        }

        $tablesToWipe = self::DEFAULT_BUSINESS_TABLES;
        if ($this->option('with-catalogs')) {
            $tablesToWipe = array_merge($tablesToWipe, self::CATALOG_TABLES);
        }

        $driver = DB::getDriverName();
        $this->disableForeignKeys($driver);

        $totalWipedRows = 0;

        try {
            foreach ($tablesToWipe as $table) {
                // Double vérification de sécurité : interdiction formelle de toucher aux tables protégées
                if (in_array($table, self::PROTECTED_TABLES, true)) {
                    $this->warn("Table protégée ignorée : {$table}");
                    continue;
                }

                if (!Schema::hasTable($table)) {
                    continue;
                }

                $rowCount = DB::table($table)->count();
                DB::table($table)->truncate();
                $totalWipedRows += $rowCount;

                $this->line("  ✓ Table <fg=yellow>{$table}</> vidée (<fg=cyan>{$rowCount}</> enregistrements supprimés)");
            }
        } finally {
            $this->enableForeignKeys($driver);
        }

        $remainingUsers = Schema::hasTable('users') ? DB::table('users')->count() : 0;
        $remainingTokens = Schema::hasTable('personal_access_tokens') ? DB::table('personal_access_tokens')->count() : 0;

        $this->newLine();
        $this->info("Nettoyage terminé avec succès !");
        $this->info("Total d'enregistrements supprimés : {$totalWipedRows}");
        $this->line("Comptes utilisateurs conservés    : <fg=green;options=bold>{$remainingUsers}</> (intacts)");
        $this->line("Sessions de connexion conservées  : <fg=green;options=bold>{$remainingTokens}</> (intactes)");

        Log::info("ResetBusinessDataCommand executed. Total rows wiped: {$totalWipedRows}. Users preserved: {$remainingUsers}.");

        return 0;
    }

    private function disableForeignKeys(string $driver): void
    {
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }
    }

    private function enableForeignKeys(string $driver): void
    {
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }
}

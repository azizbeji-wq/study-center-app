<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\MonthlyClosing;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\DaycareChild;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $director = User::where('role', 'director')->first();
        $secretary = User::where('role', 'secretary')->first();
        $teachers = Teacher::all();
        $students = Student::all();
        $daycare = DaycareChild::all();

        if ($teachers->isEmpty() || $students->isEmpty()) {
            return;
        }

        // 1. Ajouter des dépenses réalistes sur les 12 derniers mois
        $expenseCategories = [
            ['title' => 'Loyer mensuel du local', 'category' => 'loyer', 'amount' => 1200.00, 'is_recurring' => true, 'recurring_frequency' => 'monthly', 'payment_method' => 'virement'],
            ['title' => 'Facture STEG Électricité & Eau', 'category' => 'électricité', 'amount' => 210.00, 'is_recurring' => false, 'recurring_frequency' => null, 'payment_method' => 'especes'],
            ['title' => 'Abonnement Internet Fibre Topnet', 'category' => 'internet_tel', 'amount' => 85.00, 'is_recurring' => true, 'recurring_frequency' => 'monthly', 'payment_method' => 'virement'],
            ['title' => 'Rames papier & Feutres tableaux', 'category' => 'fournitures', 'amount' => 140.00, 'is_recurring' => false, 'recurring_frequency' => null, 'payment_method' => 'especes'],
            ['title' => 'Produits d\'entretien & Ménage', 'category' => 'entretien', 'amount' => 95.00, 'is_recurring' => false, 'recurring_frequency' => null, 'payment_method' => 'especes'],
            ['title' => 'Campagne Facebook & Instagram', 'category' => 'publicité', 'amount' => 160.00, 'is_recurring' => false, 'recurring_frequency' => null, 'payment_method' => 'carte'],
            ['title' => 'Aide ménagère & gardiennage', 'category' => 'salaires', 'amount' => 350.00, 'is_recurring' => true, 'recurring_frequency' => 'monthly', 'payment_method' => 'especes'],
            ['title' => 'Maintenance climatiseurs & pauses café', 'category' => 'autres', 'amount' => 75.00, 'is_recurring' => false, 'recurring_frequency' => null, 'payment_method' => 'especes'],
        ];

        // Pour chaque mois des 12 derniers mois (ou si pas de dépenses encore)
        if (Expense::count() === 0) {
            for ($i = 11; $i >= 0; $i--) {
                $monthDate = now()->subMonths($i);
                $monthKey = $monthDate->format('Y-m');

                foreach ($expenseCategories as $idx => $exp) {
                    // Varier un peu les montants hors loyer/fibre
                    $variance = ($exp['category'] === 'loyer' || $exp['category'] === 'internet_tel') ? 0 : (rand(-20, 30));
                    $day = min(28, rand(2, 25));
                    $expenseDate = Carbon::create($monthDate->year, $monthDate->month, $day)->toDateString();

                    Expense::create([
                        'title'               => $exp['title'],
                        'category'            => $exp['category'],
                        'amount'              => max(30, $exp['amount'] + $variance),
                        'date'                => $expenseDate,
                        'payment_method'      => $exp['payment_method'],
                        'is_recurring'        => $exp['is_recurring'],
                        'recurring_frequency' => $exp['recurring_frequency'],
                        'receipt_reference'   => 'FACT-' . $monthKey . '-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT),
                        'notes'               => 'Dépense comptabilisée pour ' . $monthDate->locale('fr')->translatedFormat('F Y'),
                        'created_by'          => $director?->id,
                    ]);
                }
            }
        }

        // 2. Générer des paiements élèves & garderie variés sur les 12 derniers mois s'il y a peu de paiements
        $methods = ['especes', 'especes', 'especes', 'cheque', 'virement', 'carte'];

        for ($i = 11; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $monthLabel = ucfirst($monthDate->locale('fr')->translatedFormat('F Y'));

            // Paiements élèves
            foreach ($students as $s) {
                // Vérifier si paiement déjà enregistré ce mois pour cet élève
                $already = Payment::where('student_id', $s->id)
                    ->whereYear('date', $monthDate->year)
                    ->whereMonth('date', $monthDate->month)
                    ->exists();

                if (! $already) {
                    $teacher = $teachers->random();
                    $sessions = rand(3, 8);
                    $price = 35.0;
                    $amt = $sessions * $price;
                    $payDay = min(28, rand(1, 26));
                    $payDate = Carbon::create($monthDate->year, $monthDate->month, $payDay)->toDateString();
                    $method = $methods[array_rand($methods)];

                    $p = Payment::create([
                        'student_id'     => $s->id,
                        'teacher_id'     => $teacher->id,
                        'student_name'   => $s->name,
                        'month'          => $monthLabel,
                        'amount'         => $amt,
                        'date'           => $payDate,
                        'status'         => 'paid',
                        'payment_method' => $method,
                        'created_by'     => $secretary?->id ?? $director?->id,
                        'sessions_paid'  => $sessions,
                        'months_paid'    => 0,
                    ]);

                    // Audit log
                    PaymentAuditLog::create([
                        'payment_id'     => $p->id,
                        'user_id'        => $secretary?->id ?? $director?->id,
                        'user_name'      => 'Secrétaire (Mme Nadia)',
                        'user_role'      => 'secretary',
                        'action'         => 'created',
                        'amount'         => $p->amount,
                        'student_name'   => $p->student_name,
                        'payment_method' => $method,
                        'details'        => ['sessions' => $sessions, 'month' => $monthLabel, 'source' => 'comptabilité'],
                        'ip_address'     => '192.168.1.42',
                        'created_at'     => Carbon::parse($payDate)->addHours(rand(9, 16)),
                    ]);
                }
            }

            // Paiements garderie
            foreach ($daycare->take(4) as $dc) {
                $alreadyDc = Payment::where('daycare_child_id', $dc->id)
                    ->whereYear('date', $monthDate->year)
                    ->whereMonth('date', $monthDate->month)
                    ->exists();

                if (! $alreadyDc) {
                    $amt = (float) $dc->monthly_fee > 0 ? (float) $dc->monthly_fee : 250.0;
                    $payDay = min(28, rand(1, 10));
                    $payDate = Carbon::create($monthDate->year, $monthDate->month, $payDay)->toDateString();
                    $method = $methods[array_rand($methods)];

                    $p = Payment::create([
                        'daycare_child_id' => $dc->id,
                        'student_name'     => $dc->name,
                        'month'            => $monthLabel,
                        'amount'           => $amt,
                        'date'             => $payDate,
                        'status'           => 'paid',
                        'payment_method'   => $method,
                        'created_by'       => $director?->id,
                        'sessions_paid'    => 0,
                        'months_paid'      => 1,
                    ]);

                    PaymentAuditLog::create([
                        'payment_id'     => $p->id,
                        'user_id'        => $director?->id,
                        'user_name'      => 'Directeur (M. Mansour)',
                        'user_role'      => 'director',
                        'action'         => 'created',
                        'amount'         => $p->amount,
                        'student_name'   => $p->student_name,
                        'payment_method' => $method,
                        'details'        => ['months' => 1, 'month' => $monthLabel, 'service' => 'garderie'],
                        'ip_address'     => '192.168.1.10',
                        'created_at'     => Carbon::parse($payDate)->addHours(rand(8, 14)),
                    ]);
                }
            }
        }

        // 3. Clôture des mois passés (exemple mois précédent)
        $prevMonth = now()->subMonth()->format('Y-m');
        if (! MonthlyClosing::where('month', $prevMonth)->exists()) {
            MonthlyClosing::create([
                'month'                 => $prevMonth,
                'school_year'           => '2025-2026',
                'total_revenue'         => 3450.00,
                'total_expenses'        => 2240.00,
                'total_teacher_payouts' => 780.00,
                'net_profit'            => 430.00,
                'closed_at'             => now()->subDays(4),
                'closed_by'             => $director?->id,
                'notes'                 => 'Clôture comptable validée avec le commissaire aux comptes.',
                'is_closed'             => true,
            ]);
        }
    }
}

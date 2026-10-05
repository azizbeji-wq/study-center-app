<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DaycareChild;
use App\Models\Expense;
use App\Models\MonthlyClosing;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Pricing;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    /**
     * Synthèse financière complète réservée au directeur.
     * GET /api/accounting/summary
     */
    public function summary(Request $request)
    {
        if ($request->user() && $request->user()->role !== 'director') {
            return response()->json(['message' => 'Accès réservé au directeur.'], 403);
        }

        $period = $request->input('period', '12months'); // '12months', 'school_year', 'current_month'
        $selectedSchoolYear = $request->input('school_year', '2025-2026');
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $journalDate = $request->input('journal_date', now()->toDateString());

        // 1. Définition de la plage de dates
        $now = now();
        if ($period === 'school_year') {
            $years = explode('-', $selectedSchoolYear);
            $startYear = (int) ($years[0] ?? $now->year);
            $endYear = (int) ($years[1] ?? ($startYear + 1));
            $startDate = Carbon::create($startYear, 9, 1)->startOfDay();
            $endDate = Carbon::create($endYear, 6, 30)->endOfDay();
        } elseif ($period === 'current_month') {
            $cMonth = Carbon::parse($selectedMonth . '-01');
            $startDate = $cMonth->copy()->startOfMonth();
            $endDate = $cMonth->copy()->endOfMonth();
        } else {
            // 12 derniers mois par défaut
            $startDate = $now->copy()->subMonths(11)->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        }

        // 2. Récupération des paiements et dépenses réels sur la période
        $payments = Payment::where('status', 'paid')
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->get();

        $expenses = Expense::whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->get();

        $teachers = Teacher::all()->keyBy('id');
        $students = Student::with('enrollments')->get()->keyBy('id');
        $daycareChildren = DaycareChild::all()->keyBy('id');

        // 3. Tendance mensuelle (12 mois ou mois de l'année scolaire)
        $monthsTrend = [];
        $cursor = $startDate->copy()->startOfMonth();
        $endCursor = $endDate->copy()->startOfMonth();

        while ($cursor->lte($endCursor)) {
            $mKey = $cursor->format('Y-m');
            $mLabel = ucfirst($cursor->locale('fr')->translatedFormat('M Y'));

            $mPayments = $payments->filter(function ($p) use ($mKey) {
                if ($p->date) {
                    return substr($p->date, 0, 7) === $mKey;
                }
                return false;
            });

            $mExpenses = $expenses->filter(function ($e) use ($mKey) {
                $d = $e->date instanceof Carbon ? $e->date->format('Y-m') : substr((string) $e->date, 0, 7);
                return $d === $mKey;
            });

            $studentRev = (float) $mPayments->whereNull('daycare_child_id')->sum('amount');
            $daycareRev = (float) $mPayments->whereNotNull('daycare_child_id')->sum('amount');
            $totalRev = $studentRev + $daycareRev;
            $expTotal = (float) $mExpenses->sum('amount');

            // Calcul des parts enseignants pour ce mois
            $tPayouts = 0.0;
            foreach ($mPayments->whereNull('daycare_child_id') as $mp) {
                if ($mp->teacher_id && isset($teachers[$mp->teacher_id])) {
                    $t = $teachers[$mp->teacher_id];
                    $remType = $t->remuneration_type ?? 'percentage';
                    $ratePct = $t->rate_percentage !== null ? (float) $t->rate_percentage : 70.0;
                    $salaryPerLesson = $t->salary_per_lesson !== null ? (float) $t->salary_per_lesson : 35.0;

                    if ($remType === 'percentage' || ($ratePct > 0 && $salaryPerLesson == 0)) {
                        $tPayouts += round(((float) $mp->amount * $ratePct) / 100.0, 2);
                    } else {
                        $tPayouts += round((int) $mp->sessions_paid * $salaryPerLesson, 2);
                    }
                }
            }

            $monthsTrend[] = [
                'month'          => $mKey,
                'label'          => $mLabel,
                'students'       => round($studentRev, 2),
                'daycare'        => round($daycareRev, 2),
                'totalRevenue'   => round($totalRev, 2),
                'expenses'       => round($expTotal, 2),
                'teacherPayouts' => round($tPayouts, 2),
                'netProfit'      => round($totalRev - $expTotal - $tPayouts, 2),
            ];

            $cursor->addMonth();
        }

        // 4. Répartition par Enseignant
        $byTeacher = [];
        $teacherPaymentsGroup = $payments->whereNotNull('teacher_id')->groupBy('teacher_id');

        foreach ($teachers as $tId => $t) {
            $tPayments = $teacherPaymentsGroup->get($tId, collect());
            $collected = (float) $tPayments->sum('amount');
            $sessions = (int) $tPayments->sum('sessions_paid');

            $remType = $t->remuneration_type ?? 'percentage';
            $ratePct = $t->rate_percentage !== null ? (float) $t->rate_percentage : 70.0;
            $salaryPerLesson = $t->salary_per_lesson !== null ? (float) $t->salary_per_lesson : 35.0;

            $teacherShare = 0.0;
            foreach ($tPayments as $tp) {
                if ($remType === 'percentage' || ($ratePct > 0 && $salaryPerLesson == 0)) {
                    $teacherShare += round(((float) $tp->amount * $ratePct) / 100.0, 2);
                } else {
                    $teacherShare += round((int) $tp->sessions_paid * $salaryPerLesson, 2);
                }
            }

            $isPaid = in_array($t->payout_status, ['paid', 'taken']);
            $paidAmount = $isPaid ? (float) ($t->payout_amount ?? $teacherShare) : 0.0;
            $remaining = max(0.0, round($teacherShare - $paidAmount, 2));

            $byTeacher[] = [
                'id'                => $t->id,
                'name'              => $t->name,
                'subject'           => $t->subject,
                'phone'             => $t->phone,
                'remunerationType'  => $remType,
                'ratePercentage'    => $ratePct,
                'salaryPerLesson'   => $salaryPerLesson,
                'sessionsCount'     => $sessions,
                'revenueCollected'  => round($collected, 2),
                'teacherShare'      => round($teacherShare, 2),
                'centerMargin'      => round(max(0, $collected - $teacherShare), 2),
                'paidAmount'        => round($paidAmount, 2),
                'remainingToPay'    => $remaining,
                'payoutStatus'      => $isPaid ? 'paid' : 'pending',
                'payoutDate'        => $t->payout_date ? $t->payout_date->toDateString() : null,
            ];
        }

        // 5. Répartition par Matière
        $bySubject = [];
        $subjectRevenues = [];
        foreach ($payments->whereNull('daycare_child_id') as $p) {
            $subj = 'Général';
            if ($p->teacher_id && isset($teachers[$p->teacher_id])) {
                $subj = $teachers[$p->teacher_id]->subject ?: 'Général';
            }
            if (! isset($subjectRevenues[$subj])) {
                $subjectRevenues[$subj] = 0.0;
            }
            $subjectRevenues[$subj] += (float) $p->amount;
        }
        $totalSubjRev = max(1.0, array_sum($subjectRevenues));
        foreach ($subjectRevenues as $subjName => $amt) {
            $bySubject[] = [
                'subject'    => $subjName,
                'amount'     => round($amt, 2),
                'percentage' => round(($amt / $totalSubjRev) * 100, 1),
            ];
        }

        // 6. Répartition par Niveau Scolaire (élèves + garderie)
        $byLevel = [];
        $levelRevenues = [];
        foreach ($payments as $p) {
            if ($p->daycare_child_id) {
                $lvl = 'Garderie';
            } elseif ($p->student_id && isset($students[$p->student_id])) {
                $lvl = $students[$p->student_id]->level ?: 'Non spécifié';
            } else {
                $lvl = 'Autres';
            }

            if (! isset($levelRevenues[$lvl])) {
                $levelRevenues[$lvl] = 0.0;
            }
            $levelRevenues[$lvl] += (float) $p->amount;
        }
        $totalLvlRev = max(1.0, array_sum($levelRevenues));
        foreach ($levelRevenues as $lvlName => $amt) {
            $byLevel[] = [
                'level'      => $lvlName,
                'amount'     => round($amt, 2),
                'percentage' => round(($amt / $totalLvlRev) * 100, 1),
            ];
        }

        // 7. Répartition des Dépenses par Catégorie (pour Donut Chart)
        $expenseCategories = [
            'loyer'        => 'Loyer',
            'électricité'  => 'Électricité / Eau',
            'fournitures'  => 'Fournitures & Copies',
            'entretien'    => 'Entretien & Nettoyage',
            'publicité'    => 'Publicité & Marketing',
            'internet_tel' => 'Internet & Télécoms',
            'salaires'     => 'Salaires & Primes Annexes',
            'autres'       => 'Autres Dépenses',
        ];

        $byCategory = [];
        $expGroup = $expenses->groupBy('category');
        $totalExpAmount = (float) $expenses->sum('amount');

        foreach ($expenseCategories as $catKey => $catLabel) {
            $catItems = $expGroup->get($catKey, collect());
            $catTotal = (float) $catItems->sum('amount');
            $byCategory[] = [
                'category'   => $catKey,
                'label'      => $catLabel,
                'amount'     => round($catTotal, 2),
                'count'      => $catItems->count(),
                'percentage' => $totalExpAmount > 0 ? round(($catTotal / $totalExpAmount) * 100, 1) : 0,
            ];
        }

        // 8. Reste à encaisser et Plus Gros Retards (calcul en direct)
        $duesData = $this->calculateRealDues();
        $totalUnpaidStudents = $duesData['totalStudentsDue'];
        $totalUnpaidDaycare = $duesData['totalDaycareDue'];
        $grandTotalUnpaid = round($totalUnpaidStudents + $totalUnpaidDaycare, 2);
        $topDebtors = $duesData['topDebtors'];

        // 9. Indicateurs Utiles (KPIs Réels)
        $grandTotalRevenue = round((float) $payments->sum('amount'), 2);
        $grandTotalExpenses = round($totalExpAmount, 2);
        $grandTotalTeacherPayouts = round(array_sum(array_column($byTeacher, 'teacherShare')), 2);
        $netProfit = round($grandTotalRevenue - $grandTotalExpenses - $grandTotalTeacherPayouts, 2);

        $recoveryDenominator = $grandTotalRevenue + $grandTotalUnpaid;
        $recoveryRate = $recoveryDenominator > 0 ? round(($grandTotalRevenue / $recoveryDenominator) * 100, 1) : 100.0;

        // Manque à gagner mensuel (remises actives)
        $monthlyDiscounts = 0.0;
        foreach ($students as $s) {
            if ($s->status === 'active' && $s->is_remise && (float) $s->remise > 0) {
                // calcul estimation remise mensuelle
                $monthlyDiscounts += round((float) $s->remise, 2);
            }
        }

        // Nombre d'élèves payants actifs & Revenu moyen par élève
        $activePayingStudentIds = $payments->whereNotNull('student_id')->pluck('student_id')->unique()->count();
        $studentRevenueTotal = (float) $payments->whereNull('daycare_child_id')->sum('amount');
        $averageRevenuePerStudent = $activePayingStudentIds > 0 ? round($studentRevenueTotal / $activePayingStudentIds, 2) : 0.0;

        // Recettes prévues le mois prochain (inscriptions actives)
        $projectedNextMonthRevenue = $this->calculateProjectedNextMonthRevenue($students, $daycareChildren);

        // 10. Journal de Caisse Quotidien
        $dailyJournal = $this->calculateDailyCashJournal($journalDate);

        // 11. Clôture mensuelle du mois en cours / sélectionné
        $closing = MonthlyClosing::where('month', $selectedMonth)->first();

        return response()->json([
            'period'           => $period,
            'selectedMonth'    => $selectedMonth,
            'schoolYear'       => $selectedSchoolYear,
            'range'            => [
                'start' => $startDate->toDateString(),
                'end'   => $endDate->toDateString(),
            ],
            'kpis'             => [
                'totalRevenue'              => $grandTotalRevenue,
                'totalExpenses'             => $grandTotalExpenses,
                'totalTeacherPayouts'       => $grandTotalTeacherPayouts,
                'netProfit'                 => $netProfit,
                'recoveryRate'              => $recoveryRate,
                'totalUnpaid'               => $grandTotalUnpaid,
                'unpaidStudents'            => $totalUnpaidStudents,
                'unpaidDaycare'             => $totalUnpaidDaycare,
                'monthlyDiscounts'          => round($monthlyDiscounts, 2),
                'payingStudentsCount'       => $activePayingStudentIds,
                'averageRevenuePerStudent'  => $averageRevenuePerStudent,
                'projectedNextMonthRevenue' => $projectedNextMonthRevenue,
            ],
            'monthsTrend'      => $monthsTrend,
            'byTeacher'        => $byTeacher,
            'bySubject'        => $bySubject,
            'byLevel'          => $byLevel,
            'expenseDonut'     => $byCategory,
            'topDebtors'       => $topDebtors,
            'dailyCashJournal' => $dailyJournal,
            'monthlyClosing'   => $closing ? [
                'isClosed'   => $closing->is_closed,
                'closedAt'   => $closing->closed_at ? $closing->closed_at->toIso8601String() : null,
                'notes'      => $closing->notes,
                'netProfit'  => $closing->net_profit,
            ] : [
                'isClosed'   => false,
                'closedAt'   => null,
                'notes'      => null,
                'netProfit'  => null,
            ],
        ]);
    }

    /**
     * Liste des dépenses réelles.
     * GET /api/accounting/expenses
     */
    public function expenses(Request $request)
    {
        $query = Expense::with('creator:id,name,role')->orderByDesc('date')->orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->input('to'));
        }

        if ($request->filled('search')) {
            $s = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)
                  ->orWhere('receipt_reference', 'like', $s)
                  ->orWhere('notes', 'like', $s);
            });
        }

        if ($request->boolean('is_recurring')) {
            $query->where('is_recurring', true);
        }

        $expenses = $query->get();

        return response()->json([
            'totalAmount' => round((float) $expenses->sum('amount'), 2),
            'count'       => $expenses->count(),
            'data'        => $expenses,
        ]);
    }

    /**
     * Enregistrer une nouvelle dépense.
     * POST /api/accounting/expenses
     */
    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'category'            => 'required|string|in:loyer,électricité,fournitures,entretien,publicité,internet_tel,salaires,autres',
            'amount'              => 'required|numeric|min:0.01',
            'date'                => 'required|date',
            'payment_method'      => 'nullable|string|in:especes,cheque,virement,carte,autre',
            'is_recurring'        => 'nullable|boolean',
            'recurring_frequency' => 'nullable|string|in:monthly,annual',
            'receipt_reference'   => 'nullable|string|max:255',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $data['payment_method'] = $data['payment_method'] ?? 'especes';
        $data['is_recurring'] = $request->boolean('is_recurring');
        $data['created_by'] = $request->user()?->id;

        $expense = Expense::create($data);

        \App\Models\ActivityLog::record(
            $request->user(),
            'expense',
            'created',
            "Dépense enregistrée : {$expense->title} ({$expense->amount} TND)",
            ['expense_id' => $expense->id, 'category' => $expense->category, 'amount' => $expense->amount],
            $request->ip()
        );

        $expense->load('creator:id,name,role');
        return response()->json($expense, 201);
    }

    /**
     * Mettre à jour une dépense existante.
     * PUT /api/accounting/expenses/{id}
     */
    public function updateExpense(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $data = $request->validate([
            'title'               => 'sometimes|required|string|max:255',
            'category'            => 'sometimes|required|string|in:loyer,électricité,fournitures,entretien,publicité,internet_tel,salaires,autres',
            'amount'              => 'sometimes|required|numeric|min:0.01',
            'date'                => 'sometimes|required|date',
            'payment_method'      => 'nullable|string|in:especes,cheque,virement,carte,autre',
            'is_recurring'        => 'nullable|boolean',
            'recurring_frequency' => 'nullable|string|in:monthly,annual',
            'receipt_reference'   => 'nullable|string|max:255',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $expense->update($data);

        \App\Models\ActivityLog::record(
            $request->user(),
            'expense',
            'updated',
            "Modification de la dépense : {$expense->title}",
            ['expense_id' => $expense->id, 'amount' => $expense->amount],
            $request->ip()
        );

        $expense->load('creator:id,name,role');
        return response()->json($expense);
    }

    /**
     * Supprimer une dépense (Soft Delete).
     * DELETE /api/accounting/expenses/{id}
     */
    public function destroyExpense(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        $title = $expense->title;
        $amount = $expense->amount;
        $expense->delete();

        \App\Models\ActivityLog::record(
            $request->user(),
            'expense',
            'deleted',
            "Suppression de la dépense : {$title} ({$amount} TND)",
            ['expense_id' => $id, 'amount' => $amount],
            $request->ip()
        );

        return response()->json(['message' => 'Dépense supprimée avec succès.']);
    }

    /**
     * Générer automatiquement les dépenses récurrentes (ex: loyer) pour le mois en cours.
     * POST /api/accounting/expenses/generate-recurring
     */
    public function generateRecurringExpenses(Request $request)
    {
        $targetMonth = $request->input('month', now()->format('Y-m'));
        $targetDate = Carbon::parse($targetMonth . '-01')->toDateString();

        // Récupérer les modèles de dépenses récurrentes
        $recurringTemplates = Expense::where('is_recurring', true)
            ->whereNull('deleted_at')
            ->get()
            ->unique(fn ($e) => $e->title . '-' . $e->category);

        $generated = [];
        foreach ($recurringTemplates as $tpl) {
            $targetC = Carbon::parse($targetMonth . '-01');
            $exists = Expense::where('title', $tpl->title)
                ->where('category', $tpl->category)
                ->whereYear('date', $targetC->year)
                ->whereMonth('date', $targetC->month)
                ->exists();

            if (! $exists) {
                $newExp = Expense::create([
                    'title'               => $tpl->title,
                    'category'            => $tpl->category,
                    'amount'              => $tpl->amount,
                    'date'                => $targetDate,
                    'payment_method'      => $tpl->payment_method ?? 'virement',
                    'is_recurring'        => true,
                    'recurring_frequency' => $tpl->recurring_frequency ?? 'monthly',
                    'receipt_reference'   => 'AUTO-' . $targetMonth,
                    'notes'               => 'Génération automatique récurrente mensuelle',
                    'created_by'          => $request->user()?->id,
                ]);
                $generated[] = $newExp;
            }
        }

        return response()->json([
            'message'   => count($generated) . ' dépense(s) récurrente(s) générée(s) pour ' . $targetMonth,
            'generated' => $generated,
        ]);
    }

    /**
     * Historique des audits de paiements (traçabilité qui a créé/modifié/supprimé).
     * GET /api/accounting/audit-logs
     */
    public function auditLogs(Request $request)
    {
        $query = PaymentAuditLog::orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('search')) {
            $s = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($s) {
                $q->where('user_name', 'like', $s)
                  ->orWhere('student_name', 'like', $s)
                  ->orWhere('action', 'like', $s);
            });
        }

        $logs = $query->take(150)->get();

        return response()->json($logs);
    }

    /**
     * Liste des clôtures mensuelles.
     * GET /api/accounting/closings
     */
    public function closings()
    {
        $closings = MonthlyClosing::with('closedByUser:id,name')->orderByDesc('month')->get();
        return response()->json($closings);
    }

    /**
     * Clôturer un mois financier.
     * POST /api/accounting/close-month
     */
    public function closeMonth(Request $request)
    {
        $data = $request->validate([
            'month'       => 'required|string|regex:/^\d{4}-\d{2}$/',
            'school_year' => 'nullable|string|max:20',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $month = $data['month'];
        $monthC = Carbon::parse($month . '-01');

        // Calculer les totaux réels du mois
        $payments = Payment::where('status', 'paid')
            ->whereYear('date', $monthC->year)
            ->whereMonth('date', $monthC->month)
            ->get();

        $expenses = Expense::whereYear('date', $monthC->year)
            ->whereMonth('date', $monthC->month)
            ->get();

        $totalRevenue = (float) $payments->sum('amount');
        $totalExpenses = (float) $expenses->sum('amount');

        // Parts enseignants
        $teachers = Teacher::all()->keyBy('id');
        $totalTeacherPayouts = 0.0;
        foreach ($payments->whereNull('daycare_child_id') as $p) {
            if ($p->teacher_id && isset($teachers[$p->teacher_id])) {
                $t = $teachers[$p->teacher_id];
                $remType = $t->remuneration_type ?? 'percentage';
                $ratePct = $t->rate_percentage !== null ? (float) $t->rate_percentage : 70.0;
                $salaryPerLesson = $t->salary_per_lesson !== null ? (float) $t->salary_per_lesson : 35.0;

                if ($remType === 'percentage' || ($ratePct > 0 && $salaryPerLesson == 0)) {
                    $totalTeacherPayouts += round(((float) $p->amount * $ratePct) / 100.0, 2);
                } else {
                    $totalTeacherPayouts += round((int) $p->sessions_paid * $salaryPerLesson, 2);
                }
            }
        }

        $netProfit = round($totalRevenue - $totalExpenses - $totalTeacherPayouts, 2);

        $closing = MonthlyClosing::updateOrCreate(
            ['month' => $month],
            [
                'school_year'           => $data['school_year'] ?? '2025-2026',
                'total_revenue'         => $totalRevenue,
                'total_expenses'        => $totalExpenses,
                'total_teacher_payouts' => $totalTeacherPayouts,
                'net_profit'            => $netProfit,
                'closed_at'             => now(),
                'closed_by'             => $request->user()?->id,
                'notes'                 => $data['notes'] ?? 'Clôture mensuelle effectuée par la direction.',
                'is_closed'             => true,
            ]
        );

        \App\Models\ActivityLog::record(
            $request->user(),
            'accounting',
            'closed',
            "Clôture comptable du mois {$month} (Bénéfice Net : {$netProfit} TND)",
            ['month' => $month, 'net_profit' => $netProfit],
            $request->ip()
        );

        return response()->json([
            'message' => 'Mois ' . $month . ' clôturé avec succès.',
            'closing' => $closing,
        ]);
    }

    /**
     * Réouvrir un mois clôturé (Action Directeur).
     * POST /api/accounting/reopen-month
     */
    public function reopenMonth(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $closing = MonthlyClosing::where('month', $data['month'])->firstOrFail();
        $closing->update([
            'is_closed' => false,
            'notes'     => ($closing->notes ? $closing->notes . "\n" : '') . 'Réouvert le ' . now()->toDateTimeString(),
        ]);

        \App\Models\ActivityLog::record(
            $request->user(),
            'accounting',
            'reopened',
            "Réouverture comptable du mois {$data['month']}",
            ['month' => $data['month']],
            $request->ip()
        );

        return response()->json([
            'message' => 'Le mois ' . $data['month'] . ' a été réouvert.',
            'closing' => $closing,
        ]);
    }

    /**
     * Fiche de paie / Décharge imprimable pour un enseignant sur un mois donné.
     * GET /api/accounting/teachers/{id}/payslip
     */
    public function teacherPayslip(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $month = $request->input('month', now()->format('Y-m'));
        $monthC = Carbon::parse($month . '-01');

        $payments = Payment::where('teacher_id', $teacher->id)
            ->where('status', 'paid')
            ->whereYear('date', $monthC->year)
            ->whereMonth('date', $monthC->month)
            ->get();

        $remType = $teacher->remuneration_type ?? 'percentage';
        $ratePct = $teacher->rate_percentage !== null ? (float) $teacher->rate_percentage : 70.0;
        $salaryPerLesson = $teacher->salary_per_lesson !== null ? (float) $teacher->salary_per_lesson : 35.0;

        $totalCollected = 0.0;
        $totalSessions = 0;
        $studentDetails = [];

        foreach ($payments as $p) {
            $amt = (float) $p->amount;
            $sessions = (int) $p->sessions_paid;

            if ($remType === 'percentage' || ($ratePct > 0 && $salaryPerLesson == 0)) {
                $tShare = round(($amt * $ratePct) / 100.0, 2);
            } else {
                $tShare = round($sessions * $salaryPerLesson, 2);
            }

            $totalCollected += $amt;
            $totalSessions += $sessions;

            $studentDetails[] = [
                'paymentId'    => $p->id,
                'receiptNumber'=> sprintf('REC-%s-%05d', date('Y', strtotime($p->date ?? 'now')), $p->id),
                'studentName'  => $p->student_name,
                'date'         => $p->date,
                'sessions'     => $sessions,
                'amountPaid'   => $amt,
                'teacherShare' => $tShare,
            ];
        }

        $grossDue = array_sum(array_column($studentDetails, 'teacherShare'));
        $isPaid = in_array($teacher->payout_status, ['paid', 'taken']);
        $advancesPaid = $isPaid ? (float) ($teacher->payout_amount ?? $grossDue) : 0.0;
        $netPayable = max(0.0, round($grossDue - $advancesPaid, 2));

        $monthName = ucfirst(Carbon::parse($month . '-01')->locale('fr')->translatedFormat('F Y'));

        return response()->json([
            'payslipNumber'   => sprintf('FDP-%s-%04d', str_replace('-', '', $month), $teacher->id),
            'generatedAt'     => now()->toDateTimeString(),
            'month'           => $month,
            'monthLabel'      => $monthName,
            'teacher'         => [
                'id'               => $teacher->id,
                'name'             => $teacher->name,
                'subject'          => $teacher->subject,
                'phone'            => $teacher->phone,
                'email'            => $teacher->email,
                'remunerationType' => $remType,
                'ratePercentage'   => $ratePct,
                'salaryPerLesson'  => $salaryPerLesson,
                'payoutStatus'     => $isPaid ? 'paid' : 'pending',
                'payoutDate'       => $teacher->payout_date ? $teacher->payout_date->toDateString() : null,
            ],
            'summary'         => [
                'totalCollected'  => round($totalCollected, 2),
                'totalSessions'   => $totalSessions,
                'grossDue'        => round($grossDue, 2),
                'advancesPaid'    => round($advancesPaid, 2),
                'netPayable'      => $netPayable,
            ],
            'items'           => $studentDetails,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers Privés
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calcule le reste à encaisser réel (élèves + garderie) et liste les plus gros retards avec lien WhatsApp.
     */
    private function calculateRealDues(): array
    {
        $teacherNames = Teacher::pluck('name', 'id');
        $students = Student::where('status', 'active')->with('enrollments')->get();
        $attendedBy = Attendance::whereIn('status', ['present', 'late'])->get()->groupBy('student_id');

        $paidRows = Payment::whereNotNull('student_id')
            ->whereNotNull('teacher_id')
            ->selectRaw('student_id, teacher_id, SUM(sessions_paid) as paid')
            ->groupBy('student_id', 'teacher_id')
            ->get();

        $paidSessions = [];
        foreach ($paidRows as $row) {
            $paidSessions[$row->student_id . '-' . $row->teacher_id] = (int) $row->paid;
        }

        $studentDues = [];
        $totalStudentsDue = 0.0;

        foreach ($students as $student) {
            $attendances = $attendedBy->get($student->id, collect());
            $discountFactor = ($student->is_remise && $student->discount_type === 'percentage' && $student->remise > 0)
                ? max(0, 1 - ((float) $student->remise / 100))
                : 1;

            foreach ($student->enrollments->groupBy('teacher_id') as $teacherId => $enrollments) {
                $subjects = $enrollments->pluck('subject_name')->unique()->values();
                $attended = $attendances->filter(function ($a) use ($teacherId, $subjects) {
                    if ($a->teacher_id) {
                        return (int) $a->teacher_id === (int) $teacherId;
                    }
                    return $subjects->contains($a->subject_name);
                })->count();

                $paid = $paidSessions[$student->id . '-' . $teacherId] ?? 0;
                $unpaid = max(0, $attended - $paid);
                if ($unpaid === 0) continue;

                [$price] = Pricing::averageSessionPrice($enrollments);
                $unit = round($price * $discountFactor, 2);
                $amtDue = round($unpaid * $unit, 2);
                $totalStudentsDue += $amtDue;

                $phone = $student->parent_phone ?: $student->phone;
                $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);
                // Si numéro tunisien sans indicatif international (8 chiffres)
                if (strlen($cleanPhone) === 8) {
                    $cleanPhone = '216' . $cleanPhone;
                }

                $waMsg = "Bonjour, le Centre d'études vous informe qu'un règlement de {$amtDue} DT ({$unpaid} séances de {$subjects->first()}) est en attente pour l'élève {$student->name}. Merci de régulariser la situation auprès de l'administration. Cordialement.";
                $whatsappUrl = $cleanPhone ? 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($waMsg) : null;

                $studentDues[] = [
                    'key'         => "s{$student->id}-t{$teacherId}",
                    'id'          => $student->id,
                    'name'        => $student->name,
                    'type'        => 'student',
                    'category'    => 'Élève - ' . ($subjects->first() ?: 'Cours'),
                    'parentName'  => null,
                    'phone'       => $phone,
                    'cleanPhone'  => $cleanPhone,
                    'unpaidUnits' => $unpaid . ' séances',
                    'amountDue'   => $amtDue,
                    'teacherName' => $teacherNames[$teacherId] ?? 'N/A',
                    'status'      => $unpaid > 7 ? 'overdue' : 'pending',
                    'whatsappUrl' => $whatsappUrl,
                ];
            }
        }

        // Garderie
        $paidMonths = Payment::whereNotNull('daycare_child_id')
            ->selectRaw('daycare_child_id, SUM(months_paid) as paid')
            ->groupBy('daycare_child_id')
            ->pluck('paid', 'daycare_child_id');

        $now = now();
        $daycareDues = [];
        $totalDaycareDue = 0.0;

        foreach (DaycareChild::all() as $child) {
            $start = $child->start_date ? Carbon::parse($child->start_date) : ($child->created_at ?? $now);
            $monthsDue = max(1, ($now->year - $start->year) * 12 + ($now->month - $start->month) + 1);
            $paid = (int) ($paidMonths[$child->id] ?? 0);
            $unpaid = max(0, $monthsDue - $paid);
            if ($unpaid === 0) continue;

            $fee = (float) $child->monthly_fee > 0 ? (float) $child->monthly_fee : Pricing::DEFAULT_DAYCARE_MONTHLY_FEE;
            $amtDue = round($unpaid * $fee, 2);
            $totalDaycareDue += $amtDue;

            $phone = $child->phone;
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (strlen($cleanPhone) === 8) {
                $cleanPhone = '216' . $cleanPhone;
            }

            $waMsg = "Bonjour {$child->parent_name}, le Centre d'études vous informe que les frais de garderie ({$unpaid} mois - {$amtDue} DT) pour {$child->name} sont en attente. Merci de régulariser la situation auprès de l'administration. Cordialement.";
            $whatsappUrl = $cleanPhone ? 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($waMsg) : null;

            $daycareDues[] = [
                'key'         => "d{$child->id}",
                'id'          => $child->id,
                'name'        => $child->name,
                'type'        => 'daycare',
                'category'    => 'Garderie',
                'parentName'  => $child->parent_name,
                'phone'       => $phone,
                'cleanPhone'  => $cleanPhone,
                'unpaidUnits' => $unpaid . ' mois',
                'amountDue'   => $amtDue,
                'teacherName' => 'Garderie',
                'status'      => $unpaid > 2 ? 'overdue' : 'pending',
                'whatsappUrl' => $whatsappUrl,
            ];
        }

        $allDebtors = array_merge($studentDues, $daycareDues);
        usort($allDebtors, fn ($a, $b) => $b['amountDue'] <=> $a['amountDue']);

        return [
            'totalStudentsDue' => round($totalStudentsDue, 2),
            'totalDaycareDue'  => round($totalDaycareDue, 2),
            'topDebtors'       => array_slice($allDebtors, 0, 15),
        ];
    }

    /**
     * Calcule le journal de caisse pour une date précise.
     */
    private function calculateDailyCashJournal(string $date): array
    {
        $payments = Payment::where('status', 'paid')
            ->whereDate('date', $date)
            ->get();

        $expenses = Expense::whereDate('date', $date)->get();

        $methods = ['especes', 'cheque', 'virement', 'carte', 'autre'];

        $inflowsByMethod = [];
        $outflowsByMethod = [];

        foreach ($methods as $m) {
            $inflowsByMethod[$m] = (float) $payments->where('payment_method', $m)->sum('amount');
            $outflowsByMethod[$m] = (float) $expenses->where('payment_method', $m)->sum('amount');
        }

        $totalInflows = (float) $payments->sum('amount');
        $totalOutflows = (float) $expenses->sum('amount');

        return [
            'date'             => $date,
            'totalInflows'     => round($totalInflows, 2),
            'totalOutflows'    => round($totalOutflows, 2),
            'netTotal'         => round($totalInflows - $totalOutflows, 2),
            'netCashOnly'      => round($inflowsByMethod['especes'] - $outflowsByMethod['especes'], 2),
            'inflowsByMethod'  => $inflowsByMethod,
            'outflowsByMethod' => $outflowsByMethod,
            'inflowsCount'     => $payments->count(),
            'outflowsCount'    => $expenses->count(),
            'recentInflows'    => $payments->take(10)->map(fn ($p) => [
                'id'            => $p->id,
                'receiptNumber' => sprintf('REC-%s-%05d', date('Y', strtotime($p->date ?? 'now')), $p->id),
                'studentName'   => $p->student_name,
                'amount'        => (float) $p->amount,
                'method'        => $p->payment_method ?? 'especes',
            ]),
            'recentOutflows'   => $expenses->take(10)->map(fn ($e) => [
                'id'       => $e->id,
                'title'    => $e->title,
                'category' => $e->category,
                'amount'   => (float) $e->amount,
                'method'   => $e->payment_method ?? 'especes',
            ]),
        ];
    }

    /**
     * Estime les recettes prévues le mois prochain à partir des inscriptions actives.
     */
    private function calculateProjectedNextMonthRevenue($students, $daycareChildren): float
    {
        $projected = 0.0;

        foreach ($students->where('status', 'active') as $student) {
            $discountFactor = ($student->is_remise && $student->discount_type === 'percentage' && $student->remise > 0)
                ? max(0, 1 - ((float) $student->remise / 100))
                : 1;

            foreach ($student->enrollments as $enr) {
                // Par défaut 4 séances par mois
                $sessionPrice = (float) ($enr->price_per_session ?? 0);
                if ($sessionPrice <= 0 && (float) ($enr->monthly_price ?? 0) > 0) {
                    $sessionPrice = round((float) $enr->monthly_price / 4, 2);
                }
                if ($sessionPrice <= 0) {
                    $sessionPrice = Pricing::DEFAULT_SESSION_PRICE;
                }
                $projected += round(4 * $sessionPrice * $discountFactor, 2);
            }
        }

        foreach ($daycareChildren as $child) {
            $fee = (float) $child->monthly_fee > 0 ? (float) $child->monthly_fee : Pricing::DEFAULT_DAYCARE_MONTHLY_FEE;
            $projected += round($fee, 2);
        }

        return round($projected, 2);
    }
}

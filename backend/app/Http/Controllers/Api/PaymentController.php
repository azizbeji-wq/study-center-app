<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DaycareChild;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Pricing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // ---- Seuils de statut (faciles a modifier) ----
    // Eleves : seances non payees, comptees separement pour chaque enseignant
    private const STUDENT_PENDING_FROM  = 4; // 4 seances non payees ou plus : en attente
    private const STUDENT_OVERDUE_AFTER = 7; // plus de 7 seances non payees : en retard
    // Garderie : mois non payes
    private const DAYCARE_PENDING_FROM  = 1; // 1 mois non paye ou plus : en attente
    private const DAYCARE_OVERDUE_AFTER = 2; // plus de 2 mois non payes : en retard

    /**
     * Historique des paiements recus.
     */
    public function index(Request $request)
    {
        $teacherNames = Teacher::pluck('name', 'id');

        if ($request->boolean('paginate') || $request->filled('page')) {
            $perPage = (int) $request->input('per_page', 25);
            $paginated = Payment::with('creator')->orderByDesc('id')->paginate($perPage);
            $paginated->getCollection()->transform(fn ($p) => $this->format($p, $teacherNames));
            return response()->json($paginated);
        }

        $payments = Payment::with('creator')->orderByDesc('id')->get()
            ->map(fn ($p) => $this->format($p, $teacherNames));

        return response()->json($payments);
    }

    /**
     * Soldes a regler, calcules automatiquement :
     *  - eleves : seances suivies (present / en retard) moins seances payees, par enseignant
     *  - garderie : mois ecoules depuis l'inscription moins mois payes
     * GET /api/payment-dues
     */
    public function dues()
    {
        $teacherNames = Teacher::pluck('name', 'id');

        // ---------- Eleves ----------
        $students = Student::where('status', 'active')->with('enrollments')->get();

        // Seances suivies : present ou en retard (les absents / excuses ne comptent pas)
        $attendedBy = Attendance::whereIn('status', ['present', 'late'])->get()->groupBy('student_id');

        // Seances deja payees, par eleve et par enseignant
        $paidSessions = [];
        $paidRows = Payment::whereNotNull('student_id')
            ->whereNotNull('teacher_id')
            ->selectRaw('student_id, teacher_id, SUM(sessions_paid) as paid')
            ->groupBy('student_id', 'teacher_id')
            ->get();
        foreach ($paidRows as $row) {
            $paidSessions[$row->student_id . '-' . $row->teacher_id] = (int) $row->paid;
        }

        $studentRows = [];
        foreach ($students as $student) {
            $attendances = $attendedBy->get($student->id, collect());

            // Remise accordee a l'eleve (pourcentage ou montant fixe)
            $discountFactor = 1.0;
            if ($student->is_remise && $student->remise > 0) {
                if ($student->discount_type === 'percentage') {
                    $discountFactor = max(0.0, 1.0 - ((float) $student->remise / 100.0));
                } else {
                    $grossMonthlyTotal = (float) $student->enrollments->sum('price_per_month');
                    if ($grossMonthlyTotal <= 0) {
                        $grossMonthlyTotal = max(1.0, (float) $student->enrollments->count() * 15.0 * 4.0);
                    }
                    $discountFactor = max(0.0, 1.0 - (min($grossMonthlyTotal, (float) $student->remise) / $grossMonthlyTotal));
                }
            }

            foreach ($student->enrollments->groupBy('teacher_id') as $teacherId => $enrollments) {
                $subjects = $enrollments->pluck('subject_name')->unique()->values();

                // Seances de cet enseignant (ou, si l'enseignant n'est pas renseigne
                // dans la presence, seances de ses matieres)
                $attended = $attendances->filter(function ($a) use ($teacherId, $subjects) {
                    if ($a->teacher_id) {
                        return (int) $a->teacher_id === (int) $teacherId;
                    }
                    return $subjects->contains($a->subject_name);
                })->count();

                $paid   = $paidSessions[$student->id . '-' . $teacherId] ?? 0;
                $unpaid = max(0, $attended - $paid);
                if ($unpaid === 0) {
                    continue;
                }

                // Prix : par seance, sinon prix mensuel / 4, sinon tarif par defaut
                [$price, $priceSource] = Pricing::averageSessionPrice($enrollments);
                $unit = round($price * $discountFactor, 2);

                $studentRows[] = [
                    'key'              => "s{$student->id}-t{$teacherId}",
                    'studentId'        => $student->id,
                    'studentName'      => $student->name,
                    'groupName'        => $student->group_name,
                    'teacherId'        => (int) $teacherId,
                    'teacherName'      => $teacherNames[$teacherId] ?? 'N/A',
                    'subjects'         => $subjects->all(),
                    'sessionsAttended' => $attended,
                    'sessionsPaid'     => $paid,
                    'sessionsUnpaid'   => $unpaid,
                    'pricePerSession'  => $price,
                    'priceSource'      => $priceSource,
                    'unitAmount'       => $unit,
                    'amountDue'        => round($unpaid * $unit, 2),
                    'status'           => $this->studentStatus($unpaid),
                ];
            }
        }

        // ---------- Garderie ----------
        $paidMonths = Payment::whereNotNull('daycare_child_id')
            ->selectRaw('daycare_child_id, SUM(months_paid) as paid')
            ->groupBy('daycare_child_id')
            ->pluck('paid', 'daycare_child_id');

        $now = now();
        $daycareRows = [];
        foreach (DaycareChild::all() as $child) {
            $start     = $child->start_date ? Carbon::parse($child->start_date) : ($child->created_at ?? $now);
            $monthsDue = max(1, ($now->year - $start->year) * 12 + ($now->month - $start->month) + 1);
            $paid      = (int) ($paidMonths[$child->id] ?? 0);
            $unpaid    = max(0, $monthsDue - $paid);
            if ($unpaid === 0) {
                continue;
            }

            $fee       = (float) $child->monthly_fee;
            $feeSource = 'set';
            if ($fee <= 0 && Pricing::DEFAULT_DAYCARE_MONTHLY_FEE > 0) {
                $fee       = Pricing::DEFAULT_DAYCARE_MONTHLY_FEE;
                $feeSource = 'default';
            }

            $daycareRows[] = [
                'key'            => "d{$child->id}",
                'daycareChildId' => $child->id,
                'name'           => $child->name,
                'parentName'     => $child->parent_name,
                'monthlyFee'     => $fee,
                'feeSource'      => $feeSource,
                'monthsDue'      => $monthsDue,
                'monthsPaid'     => $paid,
                'monthsUnpaid'   => $unpaid,
                'amountDue'      => round($unpaid * $fee, 2),
                'status'         => $this->daycareStatus($unpaid),
            ];
        }

        // ---------- Tri et totaux ----------
        $order = ['overdue' => 0, 'pending' => 1, 'ok' => 2];
        $sorter = function ($a, $b) use ($order) {
            return [$order[$a['status']], -$a['amountDue']] <=> [$order[$b['status']], -$b['amountDue']];
        };
        usort($studentRows, $sorter);
        usort($daycareRows, $sorter);

        $all = collect(array_merge($studentRows, $daycareRows));

        return response()->json([
            'thresholds' => [
                'studentPendingFrom'  => self::STUDENT_PENDING_FROM,
                'studentOverdueAfter' => self::STUDENT_OVERDUE_AFTER,
                'daycarePendingFrom'  => self::DAYCARE_PENDING_FROM,
                'daycareOverdueAfter' => self::DAYCARE_OVERDUE_AFTER,
            ],
            'pricing' => [
                'sessionsPerMonth'         => Pricing::SESSIONS_PER_MONTH,
                'defaultSessionPrice'      => Pricing::DEFAULT_SESSION_PRICE,
                'defaultDaycareMonthlyFee' => Pricing::DEFAULT_DAYCARE_MONTHLY_FEE,
            ],
            'students' => $studentRows,
            'daycare'  => $daycareRows,
            'totals'   => [
                'pending'      => round($all->where('status', 'pending')->sum('amountDue'), 2),
                'overdue'      => round($all->where('status', 'overdue')->sum('amountDue'), 2),
                'pendingCount' => $all->where('status', 'pending')->count(),
                'overdueCount' => $all->where('status', 'overdue')->count(),
            ],
        ]);
    }

    /**
     * Enregistrer un paiement recu (pour un eleve + enseignant, ou pour un enfant de garde).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'studentId'      => 'nullable|integer|exists:students,id|required_without:daycareChildId',
            'daycareChildId' => 'nullable|integer|exists:daycare_children,id|required_without:studentId',
            'teacherId'      => 'nullable|integer|exists:teachers,id|required_with:studentId',
            'sessionsPaid'   => 'nullable|integer|min:1|required_with:studentId',
            'monthsPaid'     => 'nullable|integer|min:1|required_with:daycareChildId',
            'month'          => 'nullable|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'date'           => 'nullable|string|max:255',
            'status'         => 'nullable|string|in:paid,pending,overdue',
            'paymentMethod'  => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $payment = DB::transaction(function () use ($data, $request) {
            if (! empty($data['studentId'])) {
                $name  = Student::findOrFail($data['studentId'])->name;
                $links = [
                    'student_id'       => $data['studentId'],
                    'teacher_id'       => $data['teacherId'],
                    'sessions_paid'    => (int) $data['sessionsPaid'],
                    'daycare_child_id' => null,
                    'months_paid'      => 0,
                ];
            } else {
                $name  = DaycareChild::findOrFail($data['daycareChildId'])->name;
                $links = [
                    'daycare_child_id' => $data['daycareChildId'],
                    'months_paid'      => (int) $data['monthsPaid'],
                    'student_id'       => null,
                    'teacher_id'       => null,
                    'sessions_paid'    => 0,
                ];
            }

            $date = $data['date'] ?? now()->toDateString();
            $method = $data['paymentMethod'] ?? $data['payment_method'] ?? 'especes';

            $payment = new Payment();
            $payment->forceFill($links + [
                'student_name'   => $name,
                'month'          => $data['month'] ?? $this->monthLabel($date),
                'amount'         => $data['amount'],
                'date'           => $date,
                'status'         => $data['status'] ?? 'paid',
                'payment_method' => $method,
                'created_by'     => $request->user()?->id,
            ]);
            $payment->save();

            // Trace d'audit
            try {
                PaymentAuditLog::create([
                    'payment_id'     => $payment->id,
                    'user_id'        => $request->user()?->id,
                    'user_name'      => $request->user()?->name ?? 'Directeur / Personnel',
                    'user_role'      => $request->user()?->role ?? 'director',
                    'action'         => 'created',
                    'amount'         => $payment->amount,
                    'student_name'   => $payment->student_name,
                    'payment_method' => $method,
                    'details'        => [
                        'month'    => $payment->month,
                        'sessions' => $payment->sessions_paid,
                        'months'   => $payment->months_paid,
                        'status'   => $payment->status,
                    ],
                    'ip_address'     => $request->ip(),
                    'created_at'     => now(),
                ]);

                \App\Models\ActivityLog::record(
                    $request->user(),
                    'payment',
                    'created',
                    "Paiement de {$payment->amount} TND pour {$payment->student_name} (" . ($payment->status === 'paid' ? 'reçu' : 'en attente') . ")",
                    ['payment_id' => $payment->id, 'amount' => $payment->amount, 'student_name' => $payment->student_name],
                    $request->ip()
                );
            } catch (\Throwable $e) {
                // Ne bloque pas la transaction si l'audit échoue
            }

            return $payment;
        });

        return response()->json($this->format($payment, Teacher::pluck('name', 'id')), 201);
    }

    public function show($id)
    {
        $payment = Payment::with(['student', 'teacher', 'daycareChild', 'creator'])->findOrFail($id);
        return response()->json($this->format($payment, Teacher::pluck('name', 'id')));
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $oldStatus = $payment->status;

        $data = $request->validate([
            'studentName'    => 'sometimes|required|string|max:255',
            'month'          => 'sometimes|required|string|max:255',
            'amount'         => 'sometimes|numeric|min:0',
            'date'           => 'nullable|string|max:255',
            'status'         => 'sometimes|required|string|in:paid,pending,overdue',
            'sessionsPaid'   => 'sometimes|integer|min:0',
            'monthsPaid'     => 'sometimes|integer|min:0',
            'paymentMethod'  => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $map = [
            'studentName'    => 'student_name',
            'sessionsPaid'   => 'sessions_paid',
            'monthsPaid'     => 'months_paid',
            'paymentMethod'  => 'payment_method',
            'payment_method' => 'payment_method',
        ];

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[$map[$key] ?? $key] = $value;
        }

        $payment->forceFill($attributes)->save();

        $action = ($oldStatus !== 'paid' && $payment->status === 'paid') ? 'marked_paid' : 'updated';

        try {
            PaymentAuditLog::create([
                'payment_id'     => $payment->id,
                'user_id'        => $request->user()?->id,
                'user_name'      => $request->user()?->name ?? 'Directeur / Personnel',
                'user_role'      => $request->user()?->role ?? 'director',
                'action'         => $action,
                'amount'         => $payment->amount,
                'student_name'   => $payment->student_name,
                'payment_method' => $payment->payment_method ?? 'especes',
                'details'        => $attributes,
                'ip_address'     => $request->ip(),
                'created_at'     => now(),
            ]);

            \App\Models\ActivityLog::record(
                $request->user(),
                'payment',
                $action,
                $action === 'marked_paid'
                    ? "Paiement de {$payment->amount} TND validé pour {$payment->student_name} (encaissé)"
                    : "Modification du paiement #{$payment->id} pour {$payment->student_name}",
                ['payment_id' => $payment->id, 'amount' => $payment->amount, 'student_name' => $payment->student_name],
                $request->ip()
            );
        } catch (\Throwable $e) {}

        return response()->json($this->format($payment, Teacher::pluck('name', 'id')));
    }

    public function destroy(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $oldAmount = $payment->amount;
        $oldStudent = $payment->student_name;

        try {
            PaymentAuditLog::create([
                'payment_id'     => $payment->id,
                'user_id'        => $request->user()?->id,
                'user_name'      => $request->user()?->name ?? 'Directeur',
                'user_role'      => $request->user()?->role ?? 'director',
                'action'         => 'deleted',
                'amount'         => $payment->amount,
                'student_name'   => $payment->student_name,
                'payment_method' => $payment->payment_method ?? 'especes',
                'details'        => [
                    'month'  => $payment->month,
                    'date'   => $payment->date,
                    'status' => $payment->status,
                ],
                'ip_address'     => $request->ip(),
                'created_at'     => now(),
            ]);

            \App\Models\ActivityLog::record(
                $request->user(),
                'payment',
                'deleted',
                "Suppression du paiement de {$oldAmount} TND pour {$oldStudent}",
                ['payment_id' => $id, 'amount' => $oldAmount, 'student_name' => $oldStudent],
                $request->ip()
            );
        } catch (\Throwable $e) {}

        $payment->delete();

        return response()->json(['message' => 'Paiement supprimé avec succès']);
    }

    // ------------------------------------------------------------------

    // ------------------------------------------------------------------
    // Extraits de Gestion de Paiements (Réservés au Directeur)
    // ------------------------------------------------------------------

    /**
     * Extrait de gestion de paiement pour un ou plusieurs enseignants.
     */
    public function extractTeachers(Request $request)
    {
        if ($request->user() && $request->user()->role !== 'director') {
            return response()->json(['message' => 'Accès réservé au directeur.'], 403);
        }

        $teacherIds = $request->input('teacher_ids');
        $query = Teacher::query();
        if (!empty($teacherIds)) {
            $ids = is_array($teacherIds) ? $teacherIds : explode(',', (string) $teacherIds);
            $query->whereIn('id', array_filter($ids));
        }

        $teachers = $query->orderBy('name')->get();

        $from = $request->input('from');
        $to = $request->input('to');

        $result = [];
        $grandTotalCollected = 0.0;
        $grandTotalTeacherShare = 0.0;
        $grandTotalCenterMargin = 0.0;

        foreach ($teachers as $t) {
            $remType = $t->remuneration_type ?? 'percentage';
            $ratePct = $t->rate_percentage !== null ? (float) $t->rate_percentage : 70.0;
            $salaryPerLesson = $t->salary_per_lesson !== null ? (float) $t->salary_per_lesson : 35.0;

            $paymentsQuery = Payment::where('teacher_id', $t->id)->where('status', 'paid');
            if ($from) $paymentsQuery->whereDate('date', '>=', $from);
            if ($to) $paymentsQuery->whereDate('date', '<=', $to);

            $payments = $paymentsQuery->with('student.enrollments')->orderBy('date')->get();

            $totalCollected = 0.0;
            $totalTeacherShare = 0.0;
            $totalSessions = 0;
            $paymentDetails = [];

            foreach ($payments as $p) {
                $amt = (float) $p->amount;
                $sessions = (int) $p->sessions_paid;

                // Récupération de l'inscription pour déterminer le tarif brut normal de la séance
                $enr = $p->student?->enrollments?->firstWhere('teacher_id', $t->id);
                $sessionPrice = (float) ($enr?->price_per_session > 0 ? $enr->price_per_session : $salaryPerLesson);
                $baseGross = max($amt, round($sessions * $sessionPrice, 2));

                if ($remType === 'percentage' || ($ratePct > 0 && $salaryPerLesson == 0 && $remType !== 'fixed' && $remType !== 'fixed_center')) {
                    // Formule C : Pourcentage calculé sur le tarif brut (remise absorbée par l'administration)
                    $tShare = round(($baseGross * $ratePct) / 100.0, 2);
                } elseif ($remType === 'fixed_center') {
                    // Formule A : Part Fixe Administration par élève (ex: 20 TND/élève) - L'enseignant prend le reste du brut
                    $centerFixed = (float) ($t->center_fixed_per_student ?? 20.0);
                    $adminShareForPayment = round($centerFixed * max(1, round($sessions / 4.0)), 2);
                    $tShare = round(max(0, $baseGross - $adminShareForPayment), 2);
                } else {
                    // Formule B : Montant fixe par élève
                    $fixedRate = (float) ($t->fixed_student_rate ?? $salaryPerLesson);
                    $tShare = round($fixedRate * max(1, round($sessions / 4.0)), 2);
                }
                $cMargin = round(max(0, $amt - $tShare), 2);

                $totalCollected += $amt;
                $totalTeacherShare += $tShare;
                $totalSessions += $sessions;

                $paymentDetails[] = [
                    'id' => $p->id,
                    'date' => $p->date,
                    'month' => $p->month,
                    'student_id' => $p->student_id,
                    'student_name' => $p->student_name,
                    'sessions_paid' => $sessions,
                    'amount' => $amt,
                    'teacher_share' => $tShare,
                    'center_margin' => $cMargin,
                ];
            }

            $totalCenterMargin = round(max(0, $totalCollected - $totalTeacherShare), 2);
            $grandTotalCollected += $totalCollected;
            $grandTotalTeacherShare += $totalTeacherShare;
            $grandTotalCenterMargin += $totalCenterMargin;

            $result[] = [
                'teacher' => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'subject' => $t->subject,
                    'phone' => $t->phone,
                    'email' => $t->email,
                    'remuneration_type' => $remType,
                    'rate_percentage' => $ratePct,
                    'salary_per_lesson' => $salaryPerLesson,
                    'fixed_student_rate' => (float) ($t->fixed_student_rate ?? 15.0),
                    'center_fixed_per_student' => (float) ($t->center_fixed_per_student ?? 20.0),
                    'weekly_lessons' => $t->weekly_lessons,
                ],
                'summary' => [
                    'total_collected' => round($totalCollected, 2),
                    'total_teacher_share' => round($totalTeacherShare, 2),
                    'total_center_margin' => $totalCenterMargin,
                    'total_sessions_paid' => $totalSessions,
                    'payments_count' => count($payments),
                ],
                'payments' => $paymentDetails,
            ];
        }

        if ($request->input('format') === 'csv') {
            return $this->downloadTeachersCsv($result);
        }

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'period' => ['from' => $from, 'to' => $to],
            'totals' => [
                'grand_total_collected' => round($grandTotalCollected, 2),
                'grand_total_teacher_share' => round($grandTotalTeacherShare, 2),
                'grand_total_center_margin' => round($grandTotalCenterMargin, 2),
                'teachers_count' => count($result),
            ],
            'data' => $result,
        ]);
    }

    /**
     * Extrait de gestion de paiement pour un ou plusieurs élèves.
     */
    public function extractStudents(Request $request)
    {
        if ($request->user() && $request->user()->role !== 'director') {
            return response()->json(['message' => 'Accès réservé au directeur.'], 403);
        }

        $studentIds = $request->input('student_ids');
        $query = Student::query();
        if (!empty($studentIds)) {
            $ids = is_array($studentIds) ? $studentIds : explode(',', (string) $studentIds);
            $query->whereIn('id', array_filter($ids));
        }

        $students = $query->with('enrollments')->orderBy('name')->get();
        $teacherNames = Teacher::pluck('name', 'id');

        $from = $request->input('from');
        $to = $request->input('to');

        // Récupérer les présences pour le calcul des soldes
        $attendedBy = Attendance::whereIn('status', ['present', 'late'])->get()->groupBy('student_id');

        $paidRows = Payment::whereNotNull('student_id')
            ->selectRaw('student_id, teacher_id, SUM(sessions_paid) as paid')
            ->groupBy('student_id', 'teacher_id')
            ->get();
        $paidSessions = [];
        foreach ($paidRows as $row) {
            $paidSessions[$row->student_id . '-' . $row->teacher_id] = (int) $row->paid;
        }

        $result = [];
        $grandTotalPaid = 0.0;
        $grandTotalDue = 0.0;

        foreach ($students as $s) {
            $paymentsQuery = Payment::where('student_id', $s->id);
            if ($from) $paymentsQuery->whereDate('date', '>=', $from);
            if ($to) $paymentsQuery->whereDate('date', '<=', $to);
            $payments = $paymentsQuery->orderBy('date')->get();

            $totalPaid = (float) $payments->where('status', 'paid')->sum('amount');
            $grandTotalPaid += $totalPaid;

            // Calcul du solde impayé pour cet élève
            $attendances = $attendedBy->get($s->id, collect());
            $discountFactor = ($s->is_remise && $s->discount_type === 'percentage' && $s->remise > 0)
                ? max(0, 1 - ((float) $s->remise / 100))
                : 1;

            $duesDetails = [];
            $totalDue = 0.0;
            foreach ($s->enrollments->groupBy('teacher_id') as $teacherId => $enrollments) {
                $subjects = $enrollments->pluck('subject_name')->unique()->values();
                $attended = $attendances->filter(function ($a) use ($teacherId, $subjects) {
                    if ($a->teacher_id) {
                        return (int) $a->teacher_id === (int) $teacherId;
                    }
                    return $subjects->contains($a->subject_name);
                })->count();

                $paid = $paidSessions[$s->id . '-' . $teacherId] ?? 0;
                $unpaid = max(0, $attended - $paid);
                if ($unpaid > 0) {
                    $firstEnroll = $enrollments->first();
                    $unitPrice = (float) ($firstEnroll->price_per_session ?? 15.0);
                    if ($unitPrice <= 0 && !empty($firstEnroll->price_per_month)) {
                        $unitPrice = round(((float) $firstEnroll->price_per_month) / 4, 2);
                    }
                    if ($unitPrice <= 0) $unitPrice = 15.0;
                    $finalUnit = round($unitPrice * $discountFactor, 2);
                    $lineDue = round($unpaid * $finalUnit, 2);
                    $totalDue += $lineDue;

                    $duesDetails[] = [
                        'teacher_id' => $teacherId,
                        'teacher_name' => $teacherNames[$teacherId] ?? "Enseignant #{$teacherId}",
                        'subject' => $subjects->implode(', '),
                        'unpaid_sessions' => $unpaid,
                        'unit_price' => $finalUnit,
                        'amount_due' => $lineDue,
                        'status' => $this->studentStatus($unpaid),
                    ];
                }
            }

            $grandTotalDue += $totalDue;

            $result[] = [
                'student' => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'level' => $s->level,
                    'group_name' => $s->group_name,
                    'phone' => $s->phone,
                    'parent_name' => $s->parent_name,
                    'parent_phone' => $s->parent_phone,
                    'is_remise' => (bool) $s->is_remise,
                    'remise' => $s->remise,
                    'discount_type' => $s->discount_type,
                ],
                'summary' => [
                    'total_paid' => round($totalPaid, 2),
                    'total_due' => round($totalDue, 2),
                    'payments_count' => count($payments),
                    'has_overdue' => collect($duesDetails)->contains('status', 'overdue'),
                    'has_pending' => collect($duesDetails)->contains('status', 'pending'),
                ],
                'payments' => $payments->map(fn($p) => $this->format($p, $teacherNames)),
                'dues' => $duesDetails,
            ];
        }

        if ($request->input('format') === 'csv') {
            return $this->downloadStudentsCsv($result);
        }

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'period' => ['from' => $from, 'to' => $to],
            'totals' => [
                'grand_total_paid' => round($grandTotalPaid, 2),
                'grand_total_due' => round($grandTotalDue, 2),
                'students_count' => count($result),
            ],
            'data' => $result,
        ]);
    }

    private function downloadTeachersCsv(array $data)
    {
        $filename = 'Extrait_Paiements_Enseignants_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM pour ouverture correcte dans Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Enseignant', 'Matiere', 'Telephone', 'Formule',
                'Date', 'Mois', 'Eleve', 'Seances Payees', 'Montant Encaisse (TND)',
                'Part Professeur (TND)', 'Marge Centre (TND)'
            ], ';');

            foreach ($data as $item) {
                $t = $item['teacher'];
                $formule = match ($t['remuneration_type'] ?? '') {
                    'percentage' => "Formule C : {$t['rate_percentage']}% du brut",
                    'fixed_center' => "Formule A : Idara " . ($t['center_fixed_per_student'] ?? 20) . " TND / élève",
                    default => "Formule B : " . ($t['fixed_student_rate'] ?? 15) . " TND / élève",
                };

                if (empty($item['payments'])) {
                    fputcsv($file, [
                        $t['name'], $t['subject'], $t['phone'], $formule,
                        '-', '-', 'Aucun paiement', 0, 0, 0, 0
                    ], ';');
                    continue;
                }

                foreach ($item['payments'] as $p) {
                    fputcsv($file, [
                        $t['name'],
                        $t['subject'],
                        $t['phone'],
                        $formule,
                        $p['date'] ?? '-',
                        $p['month'] ?? '-',
                        $p['student_name'] ?? '-',
                        $p['sessions_paid'],
                        number_format($p['amount'], 2, ',', ' '),
                        number_format($p['teacher_share'], 2, ',', ' '),
                        number_format($p['center_margin'], 2, ',', ' '),
                    ], ';');
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function downloadStudentsCsv(array $data)
    {
        $filename = 'Extrait_Paiements_Eleves_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Élève', 'Niveau', 'Groupe', 'Téléphone',
                'Date Paiement', 'Mois', 'Enseignant / Type', 'Séances Payées',
                'Montant Payé (TND)', 'Statut Paiement', 'Encaissé Par', 'Reste Dû (TND)'
            ], ';');

            foreach ($data as $item) {
                $s = $item['student'];
                $totalDue = $item['summary']['total_due'];

                if (empty($item['payments'])) {
                    fputcsv($file, [
                        $s['name'], $s['level'] ?? '-', $s['group_name'] ?? '-', $s['phone'] ?? '-',
                        '-', '-', 'Aucun règlement', 0, 0, 'Sans historique', '-',
                        number_format($totalDue, 2, ',', ' ')
                    ], ';');
                    continue;
                }

                foreach ($item['payments'] as $p) {
                    $collectedBy = ($p['createdByName'] ?? 'Directeur') . ' (' . (($p['createdByRole'] ?? 'director') === 'secretary' ? 'Personnel' : 'Directeur') . ')';
                    fputcsv($file, [
                        $s['name'],
                        $s['level'] ?? '-',
                        $s['group_name'] ?? '-',
                        $s['phone'] ?? '-',
                        $p['date'] ?? '-',
                        $p['month'] ?? '-',
                        $p['teacherName'] ?? ($p['type'] === 'daycare' ? 'Garderie' : 'Cours'),
                        $p['sessionsPaid'] ?? $p['monthsPaid'] ?? 1,
                        number_format((float) $p['amount'], 2, ',', ' '),
                        $p['status'] ?? 'paid',
                        $collectedBy,
                        number_format($totalDue, 2, ',', ' ')
                    ], ';');
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function studentStatus(int $unpaid): string
    {
        if ($unpaid > self::STUDENT_OVERDUE_AFTER) {
            return 'overdue';
        }
        if ($unpaid >= self::STUDENT_PENDING_FROM) {
            return 'pending';
        }
        return 'ok';
    }

    private function daycareStatus(int $unpaid): string
    {
        if ($unpaid > self::DAYCARE_OVERDUE_AFTER) {
            return 'overdue';
        }
        if ($unpaid >= self::DAYCARE_PENDING_FROM) {
            return 'pending';
        }
        return 'ok';
    }

    private function monthLabel(string $date): string
    {
        try {
            return ucfirst(Carbon::parse($date)->locale('fr')->translatedFormat('F Y'));
        } catch (\Throwable $e) {
            return ucfirst(now()->locale('fr')->translatedFormat('F Y'));
        }
    }

    private function format(Payment $p, $teacherNames): array
    {
        $year = date('Y', strtotime($p->date ?? ($p->created_at ? $p->created_at->toDateString() : 'now')));
        $creatorName = $p->creator?->name ?? 'Directeur Fondateur';
        $creatorRole = $p->creator?->role ?? 'director';

        return [
            'id'             => $p->id,
            'receiptNumber'  => sprintf('REC-%s-%05d', $year, $p->id),
            'studentName'    => $p->student_name,
            'month'          => $p->month,
            'amount'         => (float) $p->amount,
            'date'           => $p->date,
            'status'         => $p->status,
            'paymentMethod'  => $p->payment_method ?? 'especes',
            'createdBy'      => $p->created_by,
            'createdByName'  => $creatorName,
            'createdByRole'  => $creatorRole,
            'type'           => $p->daycare_child_id ? 'daycare' : 'student',
            'studentId'      => $p->student_id,
            'daycareChildId' => $p->daycare_child_id,
            'teacherId'      => $p->teacher_id,
            'teacherName'    => $p->teacher_id ? ($teacherNames[$p->teacher_id] ?? null) : null,
            'sessionsPaid'   => (int) $p->sessions_paid,
            'monthsPaid'     => (int) $p->months_paid,
        ];
    }
}
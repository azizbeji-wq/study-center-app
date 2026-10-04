<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DaycareChild;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Pricing;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
    public function index()
    {
        $teacherNames = Teacher::pluck('name', 'id');

        $payments = Payment::orderByDesc('id')->get()
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

            // Remise en pourcentage appliquee au prix de la seance
            $discountFactor = ($student->is_remise && $student->discount_type === 'percentage' && $student->remise > 0)
                ? max(0, 1 - ((float) $student->remise / 100))
                : 1;

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
        ]);

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

        $payment = new Payment();
        $payment->forceFill($links + [
            'student_name' => $name,
            'month'        => $data['month'] ?? $this->monthLabel($date),
            'amount'       => $data['amount'],
            'date'         => $date,
            'status'       => $data['status'] ?? 'paid',
        ]);
        $payment->save();

        return response()->json($this->format($payment, Teacher::pluck('name', 'id')), 201);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $data = $request->validate([
            'studentName'  => 'sometimes|required|string|max:255',
            'month'        => 'sometimes|required|string|max:255',
            'amount'       => 'sometimes|numeric|min:0',
            'date'         => 'nullable|string|max:255',
            'status'       => 'sometimes|required|string|in:paid,pending,overdue',
            'sessionsPaid' => 'sometimes|integer|min:0',
            'monthsPaid'   => 'sometimes|integer|min:0',
        ]);

        $map = [
            'studentName'  => 'student_name',
            'sessionsPaid' => 'sessions_paid',
            'monthsPaid'   => 'months_paid',
        ];

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[$map[$key] ?? $key] = $value;
        }

        $payment->forceFill($attributes)->save();

        return response()->json($this->format($payment, Teacher::pluck('name', 'id')));
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Paiement supprimé avec succès']);
    }

    // ------------------------------------------------------------------

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
        return [
            'id'             => $p->id,
            'studentName'    => $p->student_name,
            'month'          => $p->month,
            'amount'         => $p->amount,
            'date'           => $p->date,
            'status'         => $p->status,
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
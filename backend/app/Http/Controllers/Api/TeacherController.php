<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\TeacherPayoutService;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::withCount('enrollments')
            ->with('enrollments')
            ->get();

        $formatted = $teachers->map(function ($t) use ($request) {
            return $this->formatTeacher($request, $t);
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            // Un enseignant peut donner plusieurs matieres :
            // soit `subject` = "Mathematiques, Physique", soit `subjects` = ["Mathematiques", "Physique"]
            'subject' => 'required_without:subjects|nullable|string|max:255',
            'subjects' => 'nullable|array|min:1',
            'subjects.*' => 'string|max:100',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'weeklyLessons' => 'nullable|integer',
            'remuneration_type' => 'nullable|string|in:percentage,fixed,fixed_center',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'salary_per_lesson' => 'nullable|numeric|min:0',
            'fixed_student_rate' => 'nullable|numeric|min:0',
            'center_fixed_per_student' => 'nullable|numeric|min:0',
        ]);

        $data = $this->normalizeSubjects($data);

        // La remuneration est reservee au directeur
        if ($request->user()->role !== 'director') {
            unset($data['remuneration_type'], $data['rate_percentage'], $data['salary_per_lesson'], $data['fixed_student_rate'], $data['center_fixed_per_student']);
        } else {
            if (array_key_exists('rate_percentage', $data) && $data['rate_percentage'] === null) {
                $data['rate_percentage'] = 70.00;
            }
            if (array_key_exists('salary_per_lesson', $data) && $data['salary_per_lesson'] === null) {
                $data['salary_per_lesson'] = 25.00;
            }
            if (array_key_exists('fixed_student_rate', $data) && $data['fixed_student_rate'] === null) {
                $data['fixed_student_rate'] = 15.00;
            }
            if (array_key_exists('center_fixed_per_student', $data) && $data['center_fixed_per_student'] === null) {
                $data['center_fixed_per_student'] = 20.00;
            }
        }

        // Map weeklyLessons to weekly_lessons in database
        if (isset($data['weeklyLessons'])) {
            $data['weekly_lessons'] = $data['weeklyLessons'];
            unset($data['weeklyLessons']);
        }

        $teacher = Teacher::create($data);
        $teacher->load('enrollments');

        \App\Models\ActivityLog::record(
            $request->user(),
            'teacher',
            'created',
            "Nouvel enseignant ajouté : {$teacher->name} ({$teacher->subject})",
            ['teacher_id' => $teacher->id, 'subject' => $teacher->subject],
            $request->ip()
        );

        return response()->json($this->formatTeacher($request, $teacher), 201);
    }

    public function show(Request $request, $id)
    {
        $teacher = Teacher::withCount('enrollments')
            ->with('enrollments')
            ->findOrFail($id);

        return response()->json($this->formatTeacher($request, $teacher));
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'subject' => 'sometimes|nullable|string|max:255',
            'subjects' => 'nullable|array|min:1',
            'subjects.*' => 'string|max:100',
            'phone' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'weeklyLessons' => 'nullable|integer',
            'remuneration_type' => 'nullable|string|in:percentage,fixed,fixed_center',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'salary_per_lesson' => 'nullable|numeric|min:0',
            'fixed_student_rate' => 'nullable|numeric|min:0',
            'center_fixed_per_student' => 'nullable|numeric|min:0',
        ]);

        $data = $this->normalizeSubjects($data);

        // La remuneration est reservee au directeur
        if ($request->user()->role !== 'director') {
            unset($data['remuneration_type'], $data['rate_percentage'], $data['salary_per_lesson'], $data['fixed_student_rate'], $data['center_fixed_per_student']);
        } else {
            if (array_key_exists('rate_percentage', $data) && $data['rate_percentage'] === null) {
                $data['rate_percentage'] = $teacher->rate_percentage ?? 70.00;
            }
            if (array_key_exists('salary_per_lesson', $data) && $data['salary_per_lesson'] === null) {
                $data['salary_per_lesson'] = $teacher->salary_per_lesson ?? 25.00;
            }
            if (array_key_exists('fixed_student_rate', $data) && $data['fixed_student_rate'] === null) {
                $data['fixed_student_rate'] = $teacher->fixed_student_rate ?? 15.00;
            }
            if (array_key_exists('center_fixed_per_student', $data) && $data['center_fixed_per_student'] === null) {
                $data['center_fixed_per_student'] = $teacher->center_fixed_per_student ?? 20.00;
            }
        }

        if (isset($data['weeklyLessons'])) {
            $data['weekly_lessons'] = $data['weeklyLessons'];
            unset($data['weeklyLessons']);
        }

        $teacher->update($data);
        $teacher->load('enrollments');

        return response()->json($this->formatTeacher($request, $teacher));
    }

    public function destroy(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $name = $teacher->name;
        $teacher->delete();

        \App\Models\ActivityLog::record(
            $request->user(),
            'teacher',
            'deleted',
            "Suppression de l'enseignant : {$name}",
            ['teacher_id' => $id],
            $request->ip()
        );

        return response()->json(['message' => 'Enseignant supprimé avec succès']);
    }

    /**
     * Permet au directeur de marquer si l'enseignant a pris son argent ou non.
     * POST /api/teachers/{id}/toggle-payout
     */
    public function togglePayout(Request $request, $id)
    {
        if ($request->user()->role !== 'director') {
            return response()->json(['message' => 'Accès réservé au directeur.'], 403);
        }

        $teacher = Teacher::findOrFail($id);

        $data = $request->validate([
            'status' => 'nullable|string|in:paid,pending',
            'date'   => 'nullable|date',
            'month'  => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $newStatus = $data['status'] ?? (($teacher->payout_status === 'paid') ? 'pending' : 'paid');

        $teacher->payout_status = $newStatus;
        if ($newStatus === 'paid') {
            $teacher->payout_date = $data['date'] ?? now()->toDateString();
            $teacher->payout_month = $data['month'] ?? ($teacher->payout_month ?: now()->format('Y-m'));
            if (isset($data['amount'])) {
                $teacher->payout_amount = $data['amount'];
            }
            $teacher->payout_by_user_id = $request->user()?->id;
            $teacher->payout_by_user_name = $request->user()?->name ?? 'Directeur Fondateur';
        } else {
            $teacher->payout_date = null;
            $teacher->payout_by_user_id = null;
            $teacher->payout_by_user_name = null;
        }

        $teacher->save();
        $teacher->load('enrollments');

        \App\Models\ActivityLog::record(
            $request->user(),
            'teacher',
            'payout',
            $newStatus === 'paid'
                ? "Rémunération de {$teacher->name} marquée payée (" . ($teacher->payout_amount ?? 0) . " TND remis)"
                : "Rémunération de {$teacher->name} remise en attente",
            ['teacher_id' => $teacher->id, 'status' => $newStatus, 'amount' => $teacher->payout_amount],
            $request->ip()
        );

        return response()->json([
            'message' => $newStatus === 'paid' ? 'Rémunération marquée comme payée (l\'enseignant a pris son argent).' : 'Rémunération remise en attente.',
            'teacher' => $this->formatTeacher($request, $teacher),
        ]);
    }

    /**
     * Formate un enseignant avec le calcul détaillé de sa part et de la marge du centre.
     */
    private function formatTeacher(Request $request, Teacher $t): array
    {
        $payoutService = app(TeacherPayoutService::class);
        $formatted = $payoutService->formatTeacher($t, $request);
        $formatted['subjects'] = $this->splitSubjects($t->subject);

        return $formatted;
    }

    /**
     * Retire les champs de remuneration pour tout utilisateur qui n'est pas directeur.
     */
    private function hideSalary(Request $request, array $row): array
    {
        if ($request->user() && $request->user()->role !== 'director') {
            unset(
                $row['remuneration_type'],
                $row['rate_percentage'],
                $row['salary_per_lesson'],
                $row['fixed_student_rate'],
                $row['center_fixed_per_student'],
                $row['monthly_gross_revenue'],
                $row['teacher_share'],
                $row['center_share'],
                $row['per_session_share'],
                $row['payout_status'],
                $row['payout_date'],
                $row['payout_month'],
                $row['payout_amount'],
                $row['payout_by_user_name']
            );
        }

        return $row;
    }

    /**
     * "Mathematiques, Physique" ou ["Mathematiques", "Physique"] -> champ `subject`
     * ecrit sous la forme "Mathematiques, Physique".
     */
    private function normalizeSubjects(array $data): array
    {
        if (isset($data['subjects'])) {
            $data['subject'] = implode(', ', $this->cleanSubjects($data['subjects']));
        } elseif (isset($data['subject'])) {
            $data['subject'] = implode(', ', $this->splitSubjects($data['subject']));
        }

        unset($data['subjects']);

        return $data;
    }

    /** Separe un texte "A, B; C" en liste ['A', 'B', 'C']. */
    private function splitSubjects(?string $subject): array
    {
        return $this->cleanSubjects(preg_split('/[,;\/،]+/u', (string) $subject) ?: []);
    }

    private function cleanSubjects(array $subjects): array
    {
        $clean = [];
        foreach ($subjects as $subject) {
            $subject = trim((string) $subject);
            if ($subject !== '' && ! in_array($subject, $clean, true)) {
                $clean[] = $subject;
            }
        }

        return $clean;
    }
}
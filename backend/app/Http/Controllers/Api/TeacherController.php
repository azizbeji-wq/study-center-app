<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::withCount('enrollments')->get();

        $formatted = $teachers->map(function ($t) use ($request) {
            return $this->hideSalary($request, [
                'id' => $t->id,
                'name' => $t->name,
                'subject' => $t->subject,
                'subjects' => $this->splitSubjects($t->subject),
                'phone' => $t->phone,
                'email' => $t->email,
                'studentCount' => $t->enrollments_count,
                'weeklyLessons' => $t->weekly_lessons,
                'remuneration_type' => $t->remuneration_type ?? 'percentage',
                'rate_percentage' => (float) ($t->rate_percentage ?? 70),
                'salary_per_lesson' => (float) ($t->salary_per_lesson ?? 35),
            ]);
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
            'remuneration_type' => 'nullable|string|in:percentage,fixed',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'salary_per_lesson' => 'nullable|numeric|min:0',
        ]);

        $data = $this->normalizeSubjects($data);

        // La remuneration est reservee au directeur
        if ($request->user()->role !== 'director') {
            unset($data['remuneration_type'], $data['rate_percentage'], $data['salary_per_lesson']);
        }

        // Map weeklyLessons to weekly_lessons in database
        if (isset($data['weeklyLessons'])) {
            $data['weekly_lessons'] = $data['weeklyLessons'];
            unset($data['weeklyLessons']);
        }

        $teacher = Teacher::create($data);

        return response()->json($this->hideSalary($request, [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'subject' => $teacher->subject,
            'subjects' => $this->splitSubjects($teacher->subject),
            'phone' => $teacher->phone,
            'email' => $teacher->email,
            'studentCount' => 0,
            'weeklyLessons' => $teacher->weekly_lessons,
            'remuneration_type' => $teacher->remuneration_type ?? 'percentage',
            'rate_percentage' => (float) ($teacher->rate_percentage ?? 70),
            'salary_per_lesson' => (float) ($teacher->salary_per_lesson ?? 35),
        ]), 201);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'subject' => 'sometimes|required|string|max:255',
            'subjects' => 'nullable|array|min:1',
            'subjects.*' => 'string|max:100',
            'phone' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'weeklyLessons' => 'nullable|integer',
            'remuneration_type' => 'nullable|string|in:percentage,fixed',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'salary_per_lesson' => 'nullable|numeric|min:0',
        ]);

        $data = $this->normalizeSubjects($data);

        // La remuneration est reservee au directeur
        if ($request->user()->role !== 'director') {
            unset($data['remuneration_type'], $data['rate_percentage'], $data['salary_per_lesson']);
        }

        if (isset($data['weeklyLessons'])) {
            $data['weekly_lessons'] = $data['weeklyLessons'];
            unset($data['weeklyLessons']);
        }

        $teacher->update($data);

        return response()->json($this->hideSalary($request, [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'subject' => $teacher->subject,
            'subjects' => $this->splitSubjects($teacher->subject),
            'phone' => $teacher->phone,
            'email' => $teacher->email,
            'studentCount' => $teacher->enrollments()->count(),
            'weeklyLessons' => $teacher->weekly_lessons,
            'remuneration_type' => $teacher->remuneration_type ?? 'percentage',
            'rate_percentage' => (float) ($teacher->rate_percentage ?? 70),
            'salary_per_lesson' => (float) ($teacher->salary_per_lesson ?? 35),
        ]));
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();

        return response()->json(['message' => 'Enseignant supprimé avec succès']);
    }

    /**
     * Retire les champs de remuneration pour tout utilisateur qui n'est pas directeur.
     */
    private function hideSalary(Request $request, array $row): array
    {
        if ($request->user()->role !== 'director') {
            unset($row['remuneration_type'], $row['rate_percentage'], $row['salary_per_lesson']);
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
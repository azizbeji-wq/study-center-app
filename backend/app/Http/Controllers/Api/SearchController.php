<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\ScheduleItem;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Teacher;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Recherche globale sécurisée dans les élèves et enseignants.
     * GET /api/search?q=
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ], [
            'q.required' => 'Le terme de recherche est requis.',
            'q.min' => 'Le terme de recherche doit comporter au moins 2 caractères.',
        ]);

        $term = trim($validated['q']);
        $user = $request->user();

        $canViewPayments = $user->hasPermission('payments');
        $canViewStudents = $user->hasPermission('students');
        $canViewTeachers = $user->hasPermission('teachers');

        // Cas 1 : Enseignant (filtrage strict par groupes de l'enseignant)
        if ($user->role === 'teacher') {
            $teacher = $this->resolveTeacher($user);

            $allowedStudentIds = [];
            $teacherGroupNames = [];

            if ($teacher) {
                // Inscriptions directes par matière
                $enrolledStudentIds = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
                    ->pluck('student_id')
                    ->toArray();
                $allowedStudentIds = array_merge($allowedStudentIds, $enrolledStudentIds);

                // Groupes rattachés au modèle Group
                $groups = Group::where('teacher_id', $teacher->id)
                    ->orWhere('user_id', $user->id)
                    ->with('students:id')
                    ->get();

                foreach ($groups as $g) {
                    $teacherGroupNames[] = $g->name;
                    $allowedStudentIds = array_merge($allowedStudentIds, $g->students->pluck('id')->toArray());
                }

                // Groupes issus de l'emploi du temps
                $scheduleGroups = ScheduleItem::where(function ($query) use ($teacher) {
                    $query->where('teacher', $teacher->name)
                          ->orWhere('teacher', 'LIKE', '%' . $teacher->name . '%');
                })->whereNotNull('group_name')->pluck('group_name')->toArray();

                $teacherGroupNames = array_unique(array_filter(array_merge($teacherGroupNames, $scheduleGroups)));

                if (!empty($teacherGroupNames)) {
                    $groupStudents = Student::whereIn('group_name', $teacherGroupNames)->pluck('id')->toArray();
                    $allowedStudentIds = array_merge($allowedStudentIds, $groupStudents);
                }
            }

            $allowedStudentIds = array_unique(array_filter($allowedStudentIds));

            // Si l'enseignant n'a aucun élève dans ses groupes, résultat vide immédiat
            if (empty($allowedStudentIds)) {
                return response()->json([
                    'students' => [],
                    'teachers' => [],
                ]);
            }

            $students = Student::whereIn('id', $allowedStudentIds)
                ->where(function ($query) use ($term) {
                    $query->where('name', 'LIKE', "%{$term}%")
                          ->orWhere('level', 'LIKE', "%{$term}%")
                          ->orWhere('group_name', 'LIKE', "%{$term}%")
                          ->orWhereHas('groups', function ($g) use ($term) {
                              $g->where('name', 'LIKE', "%{$term}%");
                          });
                })
                ->with(['groups:id,name'])
                ->limit(10)
                ->get();

            $formattedStudents = $students->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'level' => $s->level,
                    'group_name' => $s->group_name ?: ($s->groups->first()?->name ?? null),
                    'groups' => $s->groups->pluck('name')->toArray(),
                    'phone' => $s->phone,
                    'parent_phone' => $s->parent_phone,
                    'status' => $s->status,
                    'can_view' => true,
                    'can_pay' => false,
                    'can_statement' => false,
                ];
            });

            return response()->json([
                'students' => $formattedStudents,
                'teachers' => [], // Un enseignant ne cherche pas les autres enseignants
            ]);
        }

        // Cas 2 : Direction & Personnel / Secrétariat
        $formattedStudents = [];
        if ($canViewStudents) {
            $students = Student::where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                      ->orWhere('level', 'LIKE', "%{$term}%")
                      ->orWhere('group_name', 'LIKE', "%{$term}%")
                      ->orWhereHas('groups', function ($g) use ($term) {
                          $g->where('name', 'LIKE', "%{$term}%");
                      });
            })
            ->with(['groups:id,name'])
            ->limit(10)
            ->get();

            $formattedStudents = $students->map(function ($s) use ($canViewPayments) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'level' => $s->level,
                    'group_name' => $s->group_name ?: ($s->groups->first()?->name ?? null),
                    'groups' => $s->groups->pluck('name')->toArray(),
                    'phone' => $s->phone,
                    'parent_phone' => $s->parent_phone,
                    'status' => $s->status,
                    'can_view' => true,
                    'can_pay' => $canViewPayments,
                    'can_statement' => $canViewPayments,
                ];
            });
        }

        $formattedTeachers = [];
        if ($canViewTeachers) {
            $teachers = Teacher::where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                      ->orWhere('subject', 'LIKE', "%{$term}%")
                      ->orWhere('phone', 'LIKE', "%{$term}%");
            })
            ->limit(10)
            ->get();

            $formattedTeachers = $teachers->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'subject' => $t->subject,
                    'phone' => $t->phone,
                    'email' => $t->email,
                ];
            });
        }

        return response()->json([
            'students' => $formattedStudents,
            'teachers' => $formattedTeachers,
        ]);
    }

    /**
     * Résout le modèle Teacher associé à un User.
     */
    private function resolveTeacher($user): ?Teacher
    {
        if (!$user) {
            return null;
        }

        $teacher = $user->teacher;
        if ($teacher) {
            return $teacher;
        }

        if ($user->email) {
            $teacher = Teacher::where('email', $user->email)->first();
            if ($teacher) {
                return $teacher;
            }
        }

        if ($user->name) {
            $teacher = Teacher::where('name', $user->name)
                ->orWhere('name', 'LIKE', '%' . $user->name . '%')
                ->first();
            if ($teacher) {
                return $teacher;
            }
        }

        return null;
    }
}

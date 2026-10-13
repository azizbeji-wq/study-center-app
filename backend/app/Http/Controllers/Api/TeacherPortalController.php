<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\ScheduleItem;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Teacher;
use App\Services\TeacherPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherPortalController extends Controller
{
    /**
     * Résout l'enseignant associé à l'utilisateur connecté.
     */
    private function resolveTeacher(Request $request): ?Teacher
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        // 1. Relation directe user_id
        $teacher = $user->teacher;
        if ($teacher) {
            return $teacher;
        }

        // 2. Recherche par email identique
        if ($user->email) {
            $teacher = Teacher::where('email', $user->email)->first();
            if ($teacher) {
                if (!$teacher->user_id) {
                    $teacher->user_id = $user->id;
                    $teacher->save();
                }
                return $teacher;
            }
        }

        // 3. Recherche par nom similaire
        if ($user->name) {
            $teacher = Teacher::where('name', $user->name)
                ->orWhere('name', 'LIKE', '%' . $user->name . '%')
                ->first();
            if ($teacher) {
                if (!$teacher->user_id) {
                    $teacher->user_id = $user->id;
                    $teacher->save();
                }
                return $teacher;
            }
        }

        // 4. Si directeur ou personnel connecté et paramètre teacher_id passé explicitement
        if (in_array(strtolower($user->role), ['director', 'directeur', 'secretary', 'secretaire', 'admin']) && $request->filled('teacher_id')) {
            return Teacher::find($request->input('teacher_id'));
        }

        // 5. Directeur ou Personnel/Secrétaire pouvant enseigner (Double rôle) :
        // Résolution ou création automatique d'une fiche enseignant liée à ce compte
        if (in_array(strtolower($user->role), ['director', 'directeur', 'secretary', 'secretaire', 'admin', 'personnel'])) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher && $user->email) {
                $teacher = Teacher::where('email', $user->email)->first();
            }
            if (!$teacher) {
                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email ?: ('staff_' . $user->id . '@studycenter.tn'),
                    'phone' => $user->phone ?: '20 000 000',
                    'subject' => $user->role === 'director' ? 'Direction & Enseignement' : 'Personnel & Enseignement',
                    'weekly_lessons' => 4,
                    'salary_per_lesson' => 25.00,
                    'center_fixed_per_student' => 20.00,
                    'remuneration_type' => 'percentage',
                    'rate_percentage' => 70.00,
                ]);
            } elseif (!$teacher->user_id) {
                $teacher->user_id = $user->id;
                $teacher->save();
            }
            return $teacher;
        }

        return null;
    }

    /**
     * GET /api/teacher/profile
     * Profil de l'enseignant connecté avec résumé pédagogique et financier.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $teacher = $this->resolveTeacher($request);

        if (!$teacher) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'teacher' => null,
                'is_linked' => false,
                'message' => 'Aucune fiche enseignant liée à ce compte utilisateur.',
            ]);
        }

        $formatted = app(TeacherPayoutService::class)->formatTeacher($teacher, $request);

        // Matières enseignées (sous forme de tableau propre)
        $subjectsList = [];
        if ($teacher && method_exists($teacher, 'subjects') && $teacher->subjects()->exists()) {
            $subjectsList = $teacher->subjects->pluck('name')->toArray();
        }
        if (empty($subjectsList) && !empty($teacher->subject)) {
            $subjectsList = array_map('trim', explode(',', $teacher->subject));
        }
        $formatted['subjects_list'] = array_values(array_unique(array_filter($subjectsList)));

        // Nombre de groupes distincts
        $groupsCount = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->distinct()
            ->count('group_name');

        // Nombre de cours hebdomadaires dans le planning
        $scheduledCount = ScheduleItem::where('teacher', 'LIKE', '%' . $teacher->name . '%')->count();

        $formatted['groups_count'] = $groupsCount;
        $formatted['scheduled_lessons_count'] = $scheduledCount;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'teacher' => $formatted,
            'is_linked' => true,
        ]);
    }

    /**
     * GET /api/teacher/schedule
     * Emploi du temps hebdomadaire filtré pour l'enseignant.
     */
    public function schedule(Request $request)
    {
        $teacher = $this->resolveTeacher($request);

        if (!$teacher) {
            return response()->json([]);
        }

        // Filtrer les créneaux par nom de l'enseignant
        $teacherName = trim($teacher->name);
        $items = ScheduleItem::where(function ($q) use ($teacherName) {
            $q->where('teacher', $teacherName)
              ->orWhere('teacher', 'LIKE', '%' . $teacherName . '%');
        })->get();

        // Si aucun créneau trouvé par nom strict, chercher par matière si spécifique
        if ($items->isEmpty() && !empty($teacher->subject)) {
            $subjects = array_map('trim', explode(',', $teacher->subject));
            $items = ScheduleItem::whereIn('subject', $subjects)->get();
        }

        // Tri par jour canonique tunisien et heure
        $dayOrder = [
            'Lundi' => 1, 'Mardi' => 2, 'Mercredi' => 3, 'Jeudi' => 4,
            'Vendredi' => 5, 'Samedi' => 6, 'Dimanche' => 7,
        ];

        $sorted = $items->sort(function ($a, $b) use ($dayOrder) {
            $orderA = $dayOrder[$a->day] ?? 99;
            $orderB = $dayOrder[$b->day] ?? 99;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }
            return strcmp($a->time, $b->time);
        })->values();

        return response()->json($sorted);
    }

    /**
     * GET /api/teacher/groups
     * Groupes pédagogiques pris en charge par l'enseignant.
     */
    public function groups(Request $request)
    {
        $teacher = $this->resolveTeacher($request);

        if (!$teacher) {
            return response()->json([]);
        }

        $user = $request->user();

        // 1. Groupes issus des inscriptions d'élèves
        $enrollmentGroups = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->select('group_name', 'subject_name')
            ->distinct()
            ->get();

        // 2. Groupes issus de l'emploi du temps
        $scheduleGroups = ScheduleItem::where(function ($q) use ($teacher) {
            $q->where('teacher', $teacher->name)
              ->orWhere('teacher', 'LIKE', '%' . $teacher->name . '%');
        })
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->get();

        // 3. Groupes créés via le modèle Group
        $directGroups = \App\Models\Group::with(['students', 'subject'])
            ->where(function ($q) use ($user, $teacher) {
                if ($user) {
                    $q->where('teacher_id', $user->id);
                }
                if ($teacher) {
                    $q->orWhere('teacher_id', $teacher->id);
                }
            })
            ->get();

        // 4. Regroupement par nom de groupe
        $groupNames = array_unique(array_filter(array_merge(
            $enrollmentGroups->pluck('group_name')->toArray(),
            $scheduleGroups->pluck('group_name')->toArray(),
            $directGroups->pluck('name')->toArray()
        )));

        $result = [];
        foreach ($groupNames as $gName) {
            $matchedDirectGroup = $directGroups->firstWhere('name', $gName);

            // Si le groupe n'existe pas encore sous forme de modèle Group explicite, le persister pour permettre l'attachement d'élèves
            if (!$matchedDirectGroup) {
                $matchedDirectGroup = \App\Models\Group::firstOrCreate(
                    ['name' => $gName],
                    [
                        'teacher_id' => $user?->id ?? $teacher->id,
                        'level' => 'Secondaire',
                        'max_capacity' => 25,
                    ]
                );
            }

            // Nombre d'élèves dans ce groupe pour cet enseignant
            $studentIds = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
                ->where('group_name', $gName)
                ->pluck('student_id')
                ->toArray();

            // Si aucune inscription explicite, chercher les élèves dont student.group_name = $gName
            if (empty($studentIds)) {
                $studentIds = Student::where('group_name', $gName)->pluck('id')->toArray();
            }

            // Si le groupe existe dans le modèle Group, inclure ses élèves rattachés via pivot
            if ($matchedDirectGroup) {
                $directStudentIds = $matchedDirectGroup->students()->pluck('students.id')->toArray();
                $studentIds = array_unique(array_merge($studentIds, $directStudentIds));
            }

            $studentCount = count(array_unique($studentIds));

            // Matières associées à ce groupe
            $directSubj = null;
            if ($matchedDirectGroup) {
                if (is_string($matchedDirectGroup->getAttribute('subject')) && !empty($matchedDirectGroup->getAttribute('subject'))) {
                    $directSubj = $matchedDirectGroup->getAttribute('subject');
                } elseif (is_object($matchedDirectGroup->subject) && isset($matchedDirectGroup->subject->name)) {
                    $directSubj = $matchedDirectGroup->subject->name;
                }
            }

            $subjects = array_unique(array_filter(array_merge(
                $enrollmentGroups->where('group_name', $gName)->pluck('subject_name')->toArray(),
                $scheduleGroups->where('group_name', $gName)->pluck('subject')->toArray(),
                $directSubj ? [$directSubj] : []
            )));

            // Niveaux scolaires associés
            $levels = array_unique(array_filter(array_merge(
                $scheduleGroups->where('group_name', $gName)->pluck('level')->toArray(),
                $matchedDirectGroup && $matchedDirectGroup->level ? [$matchedDirectGroup->level] : []
            )));
            if (empty($levels) && !empty($studentIds)) {
                $levels = array_unique(array_filter(Student::whereIn('id', $studentIds)->pluck('level')->toArray()));
            }

            // Créneaux horaires
            $slots = $scheduleGroups->where('group_name', $gName)->map(function ($s) {
                return [
                    'day' => $s->day,
                    'time' => $s->time,
                    'room' => $s->room,
                    'subject' => $s->subject,
                ];
            })->values();

            $result[] = [
                'id' => $matchedDirectGroup?->id,
                'group_name' => $gName,
                'student_count' => $studentCount,
                'subjects' => !empty($subjects) ? array_values($subjects) : [explode(',', $teacher->subject)[0] ?? 'Cours'],
                'levels' => array_values($levels),
                'level' => implode(', ', $levels) ?: 'Non spécifié',
                'schedule_slots' => $slots,
            ];
        }

        // Tri alphabétique
        usort($result, fn ($a, $b) => strcmp($a['group_name'], $b['group_name']));

        return response()->json($result);
    }

    /**
     * GET /api/teacher/groups/{group}/students
     * Élèves inscrits dans un groupe donné.
     */
    public function groupStudents(Request $request, $group)
    {
        $teacher = $this->resolveTeacher($request);

        $decodedGroup = urldecode($group);

        // Trouver les élèves via enrollments ou student.group_name
        $query = Student::query();

        $enrolledIds = [];
        if ($teacher) {
            $enrolledIds = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
                ->where('group_name', $decodedGroup)
                ->pluck('student_id')
                ->toArray();
        }

        // Vérifier également les élèves rattachés directement via la table pivot group_student
        $directGroup = \App\Models\Group::where('name', $decodedGroup)
            ->orWhere('id', is_numeric($decodedGroup) ? (int)$decodedGroup : 0)
            ->first();
        $directStudentIds = $directGroup ? $directGroup->students()->pluck('students.id')->toArray() : [];

        $allMatchingIds = array_unique(array_filter(array_merge($enrolledIds, $directStudentIds)));

        if (!empty($allMatchingIds)) {
            $query->where(function ($q) use ($allMatchingIds, $decodedGroup) {
                $q->whereIn('id', $allMatchingIds)
                  ->orWhere('group_name', $decodedGroup);
            });
        } else {
            $query->where('group_name', $decodedGroup);
        }

        $students = $query->orderBy('name')->get();

        // Charger les statistiques récentes de présence pour chaque élève
        $dateParam = $request->input('session_date', now()->toDateString());
        $subjectParam = $request->input('subject_name');

        $formatted = $students->map(function ($s) use ($teacher, $decodedGroup, $dateParam, $subjectParam) {
            $attQuery = Attendance::where('student_id', $s->id);
            if ($teacher) {
                $attQuery->where('teacher_id', $teacher->id);
            }

            $total = (clone $attQuery)->count();
            $presents = (clone $attQuery)->where('status', 'present')->count();
            $absents = (clone $attQuery)->where('status', 'absent')->count();

            // Présence actuelle du jour si déjà pointée
            $todayStatus = null;
            $todayNotes = null;
            $todayAttQuery = Attendance::where('student_id', $s->id)
                ->where('session_date', $dateParam);
            if ($subjectParam) {
                $todayAttQuery->where('subject_name', $subjectParam);
            }
            $existing = $todayAttQuery->first();
            if ($existing) {
                $todayStatus = $existing->status;
                $todayNotes = $existing->notes;
            }

            return [
                'id' => $s->id,
                'name' => $s->name,
                'level' => $s->level,
                'group_name' => $s->group_name,
                'phone' => $s->phone,
                'parent_phone' => $s->parent_phone,
                'stats' => [
                    'total_sessions' => $total,
                    'present_count' => $presents,
                    'absent_count' => $absents,
                    'attendance_rate' => $total > 0 ? round(($presents / $total) * 100) : 100,
                ],
                'current_status' => $todayStatus ?? 'present',
                'current_notes' => $todayNotes ?? '',
            ];
        });

        return response()->json($formatted);
    }

    /**
     * POST /api/teacher/attendance/batch
     * Enregistrement en masse des présences par l'enseignant.
     */
    public function batchAttendance(Request $request)
    {
        $teacher = $this->resolveTeacher($request);

        $data = $request->validate([
            'session_date' => 'required|date',
            'subject_name' => 'required|string|max:255',
            'group_name' => 'nullable|string|max:100',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,excused,late',
            'records.*.notes' => 'nullable|string',
        ], [
            'session_date.required' => 'La date de la séance est obligatoire.',
            'subject_name.required' => 'La matière est obligatoire.',
            'records.required' => 'La liste des élèves est obligatoire.',
            'records.min' => 'Veuillez renseigner au moins un élève.',
        ]);

        $teacherId = $teacher ? $teacher->id : null;
        $sessionDate = $data['session_date'];
        $subjectName = $data['subject_name'];
        $groupName = $data['group_name'] ?? null;

        $saved = DB::transaction(function () use ($data, $teacherId, $sessionDate, $subjectName, $groupName) {
            $savedItems = [];
            foreach ($data['records'] as $record) {
                $item = Attendance::updateOrCreate(
                    [
                        'student_id' => $record['student_id'],
                        'subject_name' => $subjectName,
                        'session_date' => $sessionDate,
                    ],
                    [
                        'teacher_id' => $teacherId,
                        'group_name' => $groupName,
                        'status' => $record['status'],
                        'notes' => $record['notes'] ?? null,
                    ]
                );
                $savedItems[] = $item;
            }
            return $savedItems;
        });

        // Enregistrement dans le journal d'activité
        $teacherName = $teacher ? $teacher->name : $request->user()->name;
        ActivityLog::record(
            $request->user(),
            'attendance',
            'recorded',
            "Appel validé par l'enseignant {$teacherName} pour le {$sessionDate} ({$groupName}, {$subjectName} : " . count($saved) . " élèves pointés)",
            [
                'teacher_id' => $teacherId,
                'session_date' => $sessionDate,
                'group_name' => $groupName,
                'subject_name' => $subjectName,
                'count' => count($saved),
            ],
            $request->ip()
        );

        return response()->json([
            'message' => 'Présences enregistrées avec succès.',
            'session_date' => $sessionDate,
            'subject_name' => $subjectName,
            'group_name' => $groupName,
            'count' => count($saved),
        ]);
    }

    /**
     * GET /api/teacher/attendances
     * Historique des présences prises par l'enseignant.
     */
    public function attendances(Request $request)
    {
        $teacher = $this->resolveTeacher($request);

        $query = Attendance::with(['student'])
            ->orderBy('session_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($teacher) {
            $query->where('teacher_id', $teacher->id);
        }

        if ($request->filled('session_date')) {
            $query->where('session_date', $request->input('session_date'));
        }

        if ($request->filled('group_name')) {
            $query->where('group_name', $request->input('group_name'));
        }

        if ($request->filled('subject_name')) {
            $query->where('subject_name', $request->input('subject_name'));
        }

        $records = $query->limit(100)->get();

        return response()->json($records);
    }
}

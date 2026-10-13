<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\ScheduleItem;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Teacher;
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

        // 4. Si directeur connecté et paramètre teacher_id passé
        if ($user->role === 'director' && $request->filled('teacher_id')) {
            return Teacher::find($request->input('teacher_id'));
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

        // Calculs des effectifs et finances
        $enrollmentStudentIds = $teacher->enrollments()->pluck('student_id')->toArray();
        $attendanceStudentIds = Attendance::where('teacher_id', $teacher->id)->pluck('student_id')->toArray();
        $allDistinctStudentIds = array_unique(array_filter(array_merge($enrollmentStudentIds, $attendanceStudentIds)));
        $studentCount = count($allDistinctStudentIds);

        $monthlyGross = (float) $teacher->enrollments->sum('price_per_month');
        if ($monthlyGross == 0 && $studentCount > 0) {
            $monthlyGross = $studentCount * ((float) ($teacher->salary_per_lesson ?? 35) * 4);
        }

        $remType = $teacher->remuneration_type ?? 'percentage';
        $ratePct = (float) ($teacher->rate_percentage ?? 70);
        $salaryPerLesson = (float) ($teacher->salary_per_lesson ?? 25);
        $fixedStudentRate = (float) ($teacher->fixed_student_rate ?? 15);
        $centerFixedPerStudent = (float) ($teacher->center_fixed_per_student ?? 20);
        $weeklyLessons = (int) ($teacher->weekly_lessons ?? 4);
        $monthlyLessons = max(1, $weeklyLessons * 4);

        if ($remType === 'percentage') {
            $teacherShare = round(($monthlyGross * $ratePct) / 100, 2);
            $centerShare = round(max(0, $monthlyGross - $teacherShare), 2);
            $perSessionEst = round($teacherShare / $monthlyLessons, 2);
        } elseif ($remType === 'fixed_center') {
            $totalCenterTarget = round($studentCount * $centerFixedPerStudent, 2);
            $teacherShare = round(max(0, $monthlyGross - $totalCenterTarget), 2);
            $centerShare = round(min($monthlyGross, $totalCenterTarget), 2);
            $perSessionEst = round($teacherShare / max(1, $monthlyLessons), 2);
        } else {
            $teacherShare = round($studentCount * $fixedStudentRate, 2);
            $centerShare = round(max(0, $monthlyGross - $teacherShare), 2);
            $perSessionEst = round($teacherShare / max(1, $monthlyLessons), 2);
        }

        // Nombre de groupes distincts
        $groupsCount = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->distinct()
            ->count('group_name');

        // Nombre de cours hebdomadaires dans le planning
        $scheduledCount = ScheduleItem::where('teacher', 'LIKE', '%' . $teacher->name . '%')->count();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'subject' => $teacher->subject,
                'phone' => $teacher->phone,
                'email' => $teacher->email,
                'weekly_lessons' => $teacher->weekly_lessons,
                'student_count' => $studentCount,
                'groups_count' => $groupsCount,
                'scheduled_lessons_count' => $scheduledCount,
                'remuneration_type' => $remType,
                'rate_percentage' => $ratePct,
                'fixed_student_rate' => $fixedStudentRate,
                'center_fixed_per_student' => $centerFixedPerStudent,
                'teacher_share' => $teacherShare,
                'monthly_gross_revenue' => $monthlyGross,
                'per_session_share' => $perSessionEst,
                'payout_status' => $teacher->payout_status ?: 'pending',
                'payout_date' => $teacher->payout_date ? $teacher->payout_date->format('Y-m-d') : null,
                'payout_month' => $teacher->payout_month,
                'payout_amount' => $teacher->payout_amount ? (float) $teacher->payout_amount : $teacherShare,
            ],
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

        // 3. Regroupement par nom de groupe
        $groupNames = array_unique(array_filter(array_merge(
            $enrollmentGroups->pluck('group_name')->toArray(),
            $scheduleGroups->pluck('group_name')->toArray()
        )));

        $result = [];
        foreach ($groupNames as $gName) {
            // Nombre d'élèves dans ce groupe pour cet enseignant
            $studentIds = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
                ->where('group_name', $gName)
                ->pluck('student_id')
                ->toArray();

            // Si aucune inscription explicite, chercher les élèves dont student.group_name = $gName
            if (empty($studentIds)) {
                $studentIds = Student::where('group_name', $gName)->pluck('id')->toArray();
            }

            $studentCount = count(array_unique($studentIds));

            // Matières associées à ce groupe
            $subjects = array_unique(array_filter(array_merge(
                $enrollmentGroups->where('group_name', $gName)->pluck('subject_name')->toArray(),
                $scheduleGroups->where('group_name', $gName)->pluck('subject')->toArray()
            )));

            // Niveaux scolaires associés
            $levels = array_unique(array_filter($scheduleGroups->where('group_name', $gName)->pluck('level')->toArray()));
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

        if ($teacher) {
            $enrolledIds = StudentSubjectEnrollment::where('teacher_id', $teacher->id)
                ->where('group_name', $decodedGroup)
                ->pluck('student_id')
                ->toArray();

            if (!empty($enrolledIds)) {
                $query->whereIn('id', $enrolledIds);
            } else {
                $query->where('group_name', $decodedGroup);
            }
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

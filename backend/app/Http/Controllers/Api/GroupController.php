<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GroupController extends Controller
{
    /**
     * Lister les groupes avec effectifs et détails.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Group::with(['subject', 'teacher'])
            ->withCount('students')
            ->orderBy('name');

        if ($request->user() && $request->user()->role === 'teacher') {
            $userId = $request->user()->id;
            $teacherId = $request->user()->teacher?->id;
            $query->where(function ($q) use ($userId, $teacherId) {
                $q->where('teacher_id', $userId);
                if ($teacherId) {
                    $q->orWhere('teacher_id', $teacherId);
                }
            });
        }

        $groups = $query->get();

        return response()->json($groups);
    }

    /**
     * Créer un nouveau groupe pédagogique.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Group::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'level' => 'nullable|string|max:100',
            'subject' => 'nullable|string|max:100',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|integer',
            'max_capacity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Le nom du groupe est obligatoire.',
        ]);

        $user = $request->user();

        // Si l'utilisateur connecté est un enseignant ou si aucun teacher_id n'est spécifié,
        // associer automatiquement le teacher_id (en privilégiant la fiche Teacher liée pour satisfaire les FK)
        if ($user && ($user->role === 'teacher' || empty($validated['teacher_id']))) {
            $linkedTeacher = $user->teacher ?? \App\Models\Teacher::where('user_id', $user->id)->orWhere('email', $user->email)->first();
            $validated['teacher_id'] = $linkedTeacher ? $linkedTeacher->id : $user->id;
        }

        // Associer la matière correspondante
        $subjectText = $validated['subject'] ?? null;
        if (!empty($validated['subject']) && empty($validated['subject_id'])) {
            $subjectModel = Subject::where('name', $validated['subject'])->first();
            if ($subjectModel) {
                $validated['subject_id'] = $subjectModel->id;
            }
        } elseif (!empty($validated['subject_id']) && empty($validated['subject'])) {
            $subjectModel = Subject::find($validated['subject_id']);
            if ($subjectModel) {
                $validated['subject'] = $subjectModel->name;
                $subjectText = $subjectModel->name;
            }
        }

        // Sécurité runtime : si la colonne 'subject' n'existe pas encore dans la table groups en base
        if (!\Illuminate\Support\Facades\Schema::hasColumn('groups', 'subject')) {
            unset($validated['subject']);
        }

        $group = Group::create($validated);

        if ($user) {
            ActivityLog::record(
                $user,
                'group',
                'group_created',
                "Création du groupe {$group->name}",
                ['group_id' => $group->id, 'teacher_id' => $group->teacher_id],
                $request->ip()
            );
        }

        $groupData = $group->load(['subject', 'teacher']);

        return response()->json([
            'message' => "Le groupe {$group->name} a été créé avec succès.",
            'group' => $groupData,
            'id' => $group->id,
            'name' => $group->name,
            'level' => $group->level,
            'subject' => $subjectText ?: ($groupData->subject?->name ?? ($groupData->attributes['subject'] ?? null)),
            'teacher_id' => $group->teacher_id,
        ], 201);
    }

    /**
     * Afficher les détails d'un groupe et la liste de ses élèves inscrits.
     */
    public function show($id): JsonResponse
    {
        $group = Group::with(['subject', 'teacher', 'students'])
            ->withCount('students')
            ->findOrFail($id);

        return response()->json($group);
    }

    /**
     * Mettre à jour un groupe existant.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $group = $this->resolveGroupModel($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'level' => 'sometimes|required|string|max:100',
            'subject' => 'nullable|string|max:100',
            'subject_id' => 'nullable|exists:subjects,id',
            'max_capacity' => 'nullable|integer|min:1|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!empty($validated['subject']) && empty($validated['subject_id'])) {
            $subjectModel = Subject::where('name', $validated['subject'])->first();
            if ($subjectModel) {
                $validated['subject_id'] = $subjectModel->id;
            }
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('groups', 'subject')) {
            unset($validated['subject']);
        }

        $oldName = $group->name;
        $group->update($validated);

        // Si le nom du groupe a changé, répercuter sur schedule_items et élèves si nécessaire
        if (!empty($validated['name']) && $validated['name'] !== $oldName) {
            \App\Models\ScheduleItem::where('group_name', $oldName)->update(['group_name' => $validated['name']]);
            Student::where('group_name', $oldName)->update(['group_name' => $validated['name']]);
        }

        if ($request->user()) {
            ActivityLog::record(
                $request->user(),
                'group',
                'group_updated',
                "Modification du groupe {$group->name}",
                ['group_id' => $group->id],
                $request->ip()
            );
        }

        return response()->json([
            'message' => "Le groupe {$group->name} a été mis à jour avec succès.",
            'group' => $group->load(['subject', 'teacher', 'students']),
        ]);
    }

    /**
     * Supprimer un groupe.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $group = $this->resolveGroupModel($id);
        $name = $group->name;

        // Détacher les élèves de la table pivot
        $group->students()->detach();

        // Détacher le nom de groupe dans les créneaux d'emploi du temps
        \App\Models\ScheduleItem::where('group_name', $name)->update(['group_name' => null]);

        $group->delete();

        if ($request->user()) {
            ActivityLog::record(
                $request->user(),
                'group',
                'group_deleted',
                "Suppression du groupe {$name}",
                ['group_name' => $name],
                $request->ip()
            );
        }

        return response()->json([
            'message' => "Le groupe {$name} a été supprimé avec succès.",
        ]);
    }

    /**
     * Attacher un élève existant à un groupe (avec validation et prévention des doublons).
     *
     * @param Request $request
     * @param int|string $groupId
     * @return JsonResponse
     */
    /**
     * Recherche le groupe par identifiant numérique ou nom.
     */
    protected function resolveGroupModel($groupId): Group
    {
        $group = is_numeric($groupId)
            ? Group::find((int) $groupId)
            : Group::where('name', $groupId)->first();

        if (!$group && is_string($groupId)) {
            $group = Group::where('name', urldecode($groupId))->first();
        }

        if (!$group) {
            $group = Group::findOrFail($groupId);
        }

        return $group;
    }

    /**
     * Attacher un élève existant à un groupe (avec validation et prévention des doublons).
     *
     * @param Request $request
     * @param int|string $groupId
     * @return JsonResponse
     */
    public function attachStudent(Request $request, $groupId): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
        ], [
            'student_id.required' => 'L\'identifiant de l\'élève est obligatoire.',
            'student_id.integer' => 'L\'identifiant de l\'élève doit être un entier valide.',
            'student_id.exists' => 'Cet élève n\'existe pas dans la base de données.',
        ]);

        $group = $this->resolveGroupModel($groupId);
        $studentId = (int) $validated['student_id'];
        $student = Student::findOrFail($studentId);

        // 1. Prévention des doublons : vérifier si l'élève est déjà rattaché au groupe
        if ($group->students()->where('students.id', $studentId)->exists()) {
            return response()->json([
                'status' => 'warning',
                'message' => "L'élève {$student->name} est déjà inscrit dans le groupe {$group->name}.",
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                ],
                'student_id' => $studentId,
            ], 409);
        }

        // 2. Attacher l'élève au groupe (remplit automatiquement la table pivot group_student avec timestamps)
        $group->students()->attach($studentId);

        // 3. Mettre à jour le nom de groupe principal si vide
        if (empty($student->group_name)) {
            $student->update(['group_name' => $group->name]);
        }

        // 4. Journal d'activité (si utilisateur authentifié)
        if ($request->user()) {
            ActivityLog::record(
                $request->user(),
                'group',
                'student_attached',
                "Élève {$student->name} rattaché au groupe {$group->name}",
                ['group_id' => $group->id, 'student_id' => $student->id],
                $request->ip()
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "L'élève {$student->name} a été rattaché avec succès au groupe {$group->name}.",
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'students_count' => $group->students()->count(),
            ],
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'level' => $student->level,
                'phone' => $student->phone,
            ],
        ], 200);
    }

    /**
     * Détacher un élève d'un groupe.
     */
    public function detachStudent(Request $request, $groupId, $studentId): JsonResponse
    {
        $group = $this->resolveGroupModel($groupId);
        $student = Student::findOrFail($studentId);

        $isAttached = $group->students()->where('students.id', $studentId)->exists();

        if (!$isAttached) {
            if ($student->group_name === $group->name) {
                $student->update(['group_name' => null]);
                return response()->json([
                    'message' => "L'élève {$student->name} a été retiré du groupe {$group->name}.",
                ]);
            }

            return response()->json([
                'message' => "L'élève {$student->name} n'appartient pas au groupe {$group->name}.",
            ], 404);
        }

        $group->students()->detach($studentId);

        if ($student->group_name === $group->name) {
            $student->update(['group_name' => null]);
        }

        return response()->json([
            'message' => "L'élève {$student->name} a été retiré du groupe {$group->name}.",
        ]);
    }
}

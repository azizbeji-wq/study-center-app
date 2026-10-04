<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    /** GET /api/subjects — Liste toutes les matières actives avec compteurs. */
    public function index(): JsonResponse
    {
        $subjects = Subject::withCount(['teachers', 'enrollments'])
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => $this->format($s));

        return response()->json($subjects);
    }

    /** POST /api/subjects (directeur uniquement) */
    public function store(Request $request): JsonResponse
    {
        $this->ensureDirector($request);

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'color'     => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = Str::slug($data['name'], '-');

        if (Subject::where('slug', $slug)->exists()) {
            return response()->json([
                'message' => "Une matière avec un nom similaire existe déjà (slug : {$slug}).",
            ], 422);
        }

        $subject = Subject::create([
            'name'      => $data['name'],
            'slug'      => $slug,
            'color'     => $data['color'] ?? '#6366f1',
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($this->format($subject->loadCount(['teachers', 'enrollments'])), 201);
    }

    /** GET /api/subjects/{id} */
    public function show(string $id): JsonResponse
    {
        $subject = Subject::withCount(['teachers', 'enrollments'])
            ->with('teachers:id,name,subject')
            ->findOrFail($id);

        return response()->json($this->format($subject));
    }

    /** PUT /api/subjects/{id} (directeur uniquement) */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureDirector($request);

        $subject = Subject::findOrFail($id);

        $data = $request->validate([
            'name'      => 'sometimes|required|string|max:100',
            'color'     => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($data['name'])) {
            $newSlug = Str::slug($data['name'], '-');
            if ($newSlug !== $subject->slug && Subject::where('slug', $newSlug)->exists()) {
                return response()->json([
                    'message' => "Une matière avec un nom similaire existe déjà (slug : {$newSlug}).",
                ], 422);
            }
            $data['slug'] = $newSlug;
        }

        $subject->update($data);

        return response()->json($this->format($subject->loadCount(['teachers', 'enrollments'])));
    }

    /** DELETE /api/subjects/{id} (directeur uniquement) */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->ensureDirector($request);

        $subject = Subject::withCount('enrollments')->findOrFail($id);

        if ($subject->enrollments_count > 0) {
            return response()->json([
                'message' => "Impossible de supprimer : {$subject->enrollments_count} inscription(s) sont liées à cette matière.",
            ], 422);
        }

        $subject->delete();

        return response()->json(['message' => 'Matière supprimée avec succès.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /** Refuse (403) tout utilisateur qui n'est pas directeur. */
    private function ensureDirector(Request $request): void
    {
        abort_unless($request->user()?->role === 'director', 403, 'Accès réservé au directeur.');
    }

    private function format(Subject $s): array
    {
        return [
            'id'               => $s->id,
            'name'             => $s->name,
            'slug'             => $s->slug,
            'color'            => $s->color,
            'is_active'        => (bool) $s->is_active,
            'teachers_count'   => $s->teachers_count ?? 0,
            'enrollments_count'=> $s->enrollments_count ?? 0,
        ];
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SchoolCalendarController extends Controller
{
    /**
     * Liste des fichiers importés, filtrable par école, niveau et classe.
     * GET /api/school-calendar-imports?school_id=&level=&section=
     */
    public function index(Request $request)
    {
        $query = DB::table('school_calendar_imports as i')
            ->join('school_classes as c', 'c.id', '=', 'i.school_class_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->select(
                'i.id',
                'i.file_name',
                'i.file_type',
                'i.created_at',
                's.id as school_id',
                's.name as school_name',
                'c.level',
                'c.section'
            )
            ->orderByDesc('i.created_at');

        if ($request->filled('school_id')) {
            $query->where('s.id', $request->integer('school_id'));
        }
        if ($request->filled('level')) {
            $query->where('c.level', $request->integer('level'));
        }
        if ($request->filled('section')) {
            $query->where('c.section', $request->string('section'));
        }

        return $query->get();
    }

    /**
     * Import d'un fichier pour une classe (ex : 5ème année, classe A, école X).
     * POST /api/school-calendar-imports
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'level'     => ['required', 'integer', 'between:1,6'],
            'section'   => ['required', 'string', 'max:10'],
            'file'      => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $classId = $this->resolveClassId(
            (int) $data['school_id'],
            (int) $data['level'],
            $data['section']
        );

        $file = $request->file('file');
        $path = $file->store('school-calendars', 'local');

        $id = DB::table('school_calendar_imports')->insertGetId([
            'school_class_id' => $classId,
            'file_name'       => $file->getClientOriginalName(),
            'file_path'       => $path,
            'file_type'       => $file->getMimeType(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['id' => $id], 201);
    }

    /**
     * Modification : changer l'école / le niveau / la classe,
     * et optionnellement remplacer le fichier.
     * POST /api/school-calendar-imports/{id}
     * (POST et non PUT : PHP ne lit pas les fichiers envoyés avec PUT)
     */
    public function update(Request $request, int $id)
    {
        $row = DB::table('school_calendar_imports')->where('id', $id)->first();
        abort_if(! $row, 404);

        $data = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'level'     => ['required', 'integer', 'between:1,6'],
            'section'   => ['required', 'string', 'max:10'],
            'file'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $update = [
            'school_class_id' => $this->resolveClassId(
                (int) $data['school_id'],
                (int) $data['level'],
                $data['section']
            ),
            'updated_at' => now(),
        ];

        $oldPath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $update['file_name'] = $file->getClientOriginalName();
            $update['file_path'] = $file->store('school-calendars', 'local');
            $update['file_type'] = $file->getMimeType();
            $oldPath = $row->file_path;
        }

        DB::table('school_calendar_imports')->where('id', $id)->update($update);

        // L'ancien fichier n'est supprimé qu'une fois la base mise à jour.
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json(['id' => $id]);
    }

    /**
     * Suppression de l'enregistrement et du fichier.
     * DELETE /api/school-calendar-imports/{id}
     */
    public function destroy(int $id)
    {
        $row = DB::table('school_calendar_imports')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('school_calendar_imports')->where('id', $id)->delete();
        Storage::disk('local')->delete($row->file_path);

        return response()->json(['message' => 'Emploi du temps supprimé avec succès']);
    }

    /**
     * Envoie le fichier.
     * GET /api/school-calendar-imports/{id}/file
     */
    public function file(int $id)
    {
        $row = DB::table('school_calendar_imports')->where('id', $id)->first();
        abort_if(! $row, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($row->file_path), 404);

        return response()->file($disk->path($row->file_path), [
            'Content-Type' => $row->file_type ?: 'application/octet-stream',
        ]);
    }

    /**
     * Retrouve la classe (école + niveau + section) ou la crée si elle n'existe pas.
     */
    private function resolveClassId(int $schoolId, int $level, string $section): int
    {
        $classId = DB::table('school_classes')
            ->where('school_id', $schoolId)
            ->where('level', $level)
            ->where('section', $section)
            ->value('id');

        if ($classId) {
            return (int) $classId;
        }

        return (int) DB::table('school_classes')->insertGetId([
            'school_id'  => $schoolId,
            'level'      => $level,
            'section'    => $section,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
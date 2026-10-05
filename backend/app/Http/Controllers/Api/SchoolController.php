<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::withCount(['classes'])
            ->orderBy('name')
            ->get();

        return response()->json($schools);
    }

    public function classes(School $school)
    {
        $classes = $school->classes()
            ->orderBy('level')
            ->orderBy('section')
            ->get();

        return response()->json($classes);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255', 'unique:schools,name'],
            'address' => ['nullable', 'string', 'max:255'],
            'city'    => ['nullable', 'string', 'max:100'],
        ]);

        $school = School::create($data);

        return response()->json($school, 201);
    }

    public function update(\Illuminate\Http\Request $request, School $school)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255', 'unique:schools,name,' . $school->id],
            'address' => ['nullable', 'string', 'max:255'],
            'city'    => ['nullable', 'string', 'max:100'],
        ]);

        $school->update($data);

        return response()->json($school);
    }

    public function destroy(School $school)
    {
        $classIds = $school->classes()->pluck('id');
        $imports = \Illuminate\Support\Facades\DB::table('school_calendar_imports')
            ->whereIn('school_class_id', $classIds)
            ->get();

        foreach ($imports as $import) {
            if (!empty($import->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($import->file_path);
            }
        }

        \Illuminate\Support\Facades\DB::table('school_calendar_imports')
            ->whereIn('school_class_id', $classIds)
            ->delete();

        $school->delete();

        return response()->json(['message' => 'École supprimée avec succès']);
    }
}
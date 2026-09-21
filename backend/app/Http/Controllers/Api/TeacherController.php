<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::withCount('enrollments')->get();
        
        $formatted = $teachers->map(function ($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'subject' => $t->subject,
                'phone' => $t->phone,
                'email' => $t->email,
                'studentCount' => $t->enrollments_count,
                'weeklyLessons' => $t->weekly_lessons,
            ];
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'weeklyLessons' => 'nullable|integer',
        ]);

        // Map weeklyLessons to weekly_lessons in database
        if (isset($data['weeklyLessons'])) {
            $data['weekly_lessons'] = $data['weeklyLessons'];
            unset($data['weeklyLessons']);
        }

        $teacher = Teacher::create($data);

        return response()->json([
            'id' => $teacher->id,
            'name' => $teacher->name,
            'subject' => $teacher->subject,
            'phone' => $teacher->phone,
            'email' => $teacher->email,
            'studentCount' => 0,
            'weeklyLessons' => $teacher->weekly_lessons,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'subject' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'weeklyLessons' => 'nullable|integer',
        ]);

        if (isset($data['weeklyLessons'])) {
            $data['weekly_lessons'] = $data['weeklyLessons'];
            unset($data['weeklyLessons']);
        }

        $teacher->update($data);

        return response()->json([
            'id' => $teacher->id,
            'name' => $teacher->name,
            'subject' => $teacher->subject,
            'phone' => $teacher->phone,
            'email' => $teacher->email,
            'studentCount' => $teacher->enrollments()->count(),
            'weeklyLessons' => $teacher->weekly_lessons,
        ]);
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();

        return response()->json(['message' => 'Enseignant supprimé avec succès']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('enrollments.teacher')->get();

        $formatted = $students->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'level' => $s->level,
                'phone' => $s->phone,
                'status' => $s->status,
                'subjects' => $s->enrollments->map(function ($e) {
                    return [
                        'subject' => $e->subject_name,
                        'teacher' => $e->teacher ? $e->teacher->name : 'N/A',
                        'teacher_id' => $e->teacher_id,
                    ];
                }),
            ];
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'subjects' => 'nullable|array',
            'subjects.*.subject' => 'required|string',
            'subjects.*.teacher_id' => 'required|exists:teachers,id',
        ]);

        $student = Student::create([
            'name' => $data['name'],
            'level' => $data['level'],
            'phone' => $data['phone'],
            'status' => $data['status'] ?? 'active',
        ]);

        if (!empty($data['subjects'])) {
            foreach ($data['subjects'] as $sub) {
                StudentSubjectEnrollment::create([
                    'student_id' => $student->id,
                    'subject_name' => $sub['subject'],
                    'teacher_id' => $sub['teacher_id'],
                ]);
            }
        }

        return response()->json($this->formatStudent($student), 201);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'level' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|string|in:active,inactive',
            'subjects' => 'nullable|array',
            'subjects.*.subject' => 'required|string',
            'subjects.*.teacher_id' => 'required|exists:teachers,id',
        ]);

        $student->update($request->only(['name', 'level', 'phone', 'status']));

        if (isset($data['subjects'])) {
            // Drop old enrollments and create new ones to simplify sync
            $student->enrollments()->delete();
            foreach ($data['subjects'] as $sub) {
                StudentSubjectEnrollment::create([
                    'student_id' => $student->id,
                    'subject_name' => $sub['subject'],
                    'teacher_id' => $sub['teacher_id'],
                ]);
            }
        }

        return response()->json($this->formatStudent($student));
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json(['message' => 'Élève supprimé avec succès']);
    }

    private function formatStudent(Student $s)
    {
        $s->load('enrollments.teacher');
        return [
            'id' => $s->id,
            'name' => $s->name,
            'level' => $s->level,
            'phone' => $s->phone,
            'status' => $s->status,
            'subjects' => $s->enrollments->map(function ($e) {
                return [
                    'subject' => $e->subject_name,
                    'teacher' => $e->teacher ? $e->teacher->name : 'N/A',
                    'teacher_id' => $e->teacher_id,
                ];
            }),
        ];
    }
}

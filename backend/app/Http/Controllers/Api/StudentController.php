<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['enrollments.teacher', 'attendances'])->get();

        $formatted = $students->map(function ($s) {
            return $this->formatStudent($s);
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:255',
            'group_name' => 'nullable|string|max:100',
            'phone' => 'required|string|max:255',
            'parent_phone' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'is_remise' => 'nullable|boolean',
            'remise' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:percentage,fixed',
            'base_fee' => 'nullable|numeric|min:0',
            'subjects' => 'nullable|array',
            'subjects.*.subject' => 'required|string',
            'subjects.*.teacher_id' => 'required|exists:teachers,id',
            'subjects.*.group_name' => 'nullable|string|max:100',
            'subjects.*.price_per_month' => 'nullable|numeric|min:0',
            'subjects.*.price_per_session' => 'nullable|numeric|min:0',
        ]);

        $student = DB::transaction(function () use ($data) {
            $student = Student::create([
                'name' => $data['name'],
                'level' => $data['level'],
                'group_name' => $data['group_name'] ?? null,
                'phone' => $data['phone'],
                'parent_phone' => $data['parent_phone'] ?? null,
                'status' => $data['status'] ?? 'active',
                'is_remise' => $data['is_remise'] ?? false,
                'remise' => $data['remise'] ?? 0,
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'base_fee' => $data['base_fee'] ?? 0,
            ]);

            if (!empty($data['subjects'])) {
                foreach ($data['subjects'] as $sub) {
                    StudentSubjectEnrollment::create([
                        'student_id' => $student->id,
                        'subject_name' => $sub['subject'],
                        'teacher_id' => $sub['teacher_id'],
                        'group_name' => $sub['group_name'] ?? $student->group_name,
                        'price_per_month' => $sub['price_per_month'] ?? 0,
                        'price_per_session' => $sub['price_per_session'] ?? 0,
                    ]);
                }
            }

            return $student;
        });

        \App\Models\ActivityLog::record(
            $request->user(),
            'student',
            'created',
            "Nouvel élève inscrit : {$student->name} ({$student->level})",
            ['student_id' => $student->id, 'level' => $student->level],
            $request->ip()
        );

        return response()->json($this->formatStudent($student), 201);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'level' => 'sometimes|required|string|max:255',
            'group_name' => 'nullable|string|max:100',
            'phone' => 'sometimes|required|string|max:255',
            'parent_phone' => 'nullable|string|max:255',
            'status' => 'sometimes|required|string|in:active,inactive',
            'is_remise' => 'nullable|boolean',
            'remise' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:percentage,fixed',
            'base_fee' => 'nullable|numeric|min:0',
            'subjects' => 'nullable|array',
            'subjects.*.subject' => 'required|string',
            'subjects.*.teacher_id' => 'required|exists:teachers,id',
            'subjects.*.group_name' => 'nullable|string|max:100',
            'subjects.*.price_per_month' => 'nullable|numeric|min:0',
            'subjects.*.price_per_session' => 'nullable|numeric|min:0',
        ]);

        $updateData = array_filter([
            'name'          => $data['name']          ?? null,
            'level'         => $data['level']         ?? null,
            'group_name'    => $data['group_name']    ?? null,
            'phone'         => $data['phone']         ?? null,
            'parent_phone'  => $data['parent_phone']  ?? null,
            'status'        => $data['status']        ?? null,
            'is_remise'     => isset($data['is_remise']) ? (bool)$data['is_remise'] : null,
            'remise'        => isset($data['remise']) ? (float)$data['remise'] : null,
            'discount_type' => $data['discount_type'] ?? null,
            'base_fee'      => isset($data['base_fee']) ? (float)$data['base_fee'] : null,
        ], fn($v) => $v !== null);

        DB::transaction(function () use ($student, $updateData, $data) {
            $student->update($updateData);

            if (isset($data['subjects'])) {
                $student->enrollments()->delete();
                foreach ($data['subjects'] as $sub) {
                    StudentSubjectEnrollment::create([
                        'student_id'        => $student->id,
                        'subject_name'      => $sub['subject'],
                        'teacher_id'        => $sub['teacher_id'],
                        'group_name'        => $sub['group_name'] ?? $student->group_name,
                        'price_per_month'   => $sub['price_per_month'] ?? 0,
                        'price_per_session' => $sub['price_per_session'] ?? 0,
                    ]);
                }
            }
        });

        return response()->json($this->formatStudent($student));
    }

    public function destroy(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $name = $student->name;
        $student->delete();

        \App\Models\ActivityLog::record(
            $request->user(),
            'student',
            'deleted',
            "Suppression de l'élève : {$name}",
            ['student_id' => $id],
            $request->ip()
        );

        return response()->json(['message' => 'Élève supprimé avec succès']);
    }

    private function formatStudent(Student $s)
    {
        $s->load(['enrollments.teacher', 'attendances']);

        // Calculate attendance stats
        $totalSessions = $s->attendances->count();
        $presentCount = $s->attendances->where('status', 'present')->count();
        $absentCount = $s->attendances->where('status', 'absent')->count();
        $excusedCount = $s->attendances->where('status', 'excused')->count();

        // Calculate estimated fees:
        // Sum of enrollments price_per_month OR base_fee OR attendance-based (present sessions * price_per_session)
        $monthlySubjectTotal = (float) $s->enrollments->sum('price_per_month');
        $sessionSubjectTotal = 0;
        $usedDefaultPrice = false;
        foreach ($s->enrollments as $enr) {
            // Prix par seance, sinon prix mensuel / 4, sinon tarif par defaut
            [$sessionPrice, $priceSource] = Pricing::enrollmentSessionPrice($enr);
            $attendedForSubject = $s->attendances
                ->where('subject_name', $enr->subject_name)
                ->where('status', 'present')
                ->count();
            if ($attendedForSubject > 0) {
                $sessionSubjectTotal += $attendedForSubject * $sessionPrice;
                if ($priceSource === 'default') {
                    $usedDefaultPrice = true;
                }
            }
        }

        $grossFee = (float) ($s->base_fee > 0 ? $s->base_fee : ($monthlySubjectTotal > 0 ? $monthlySubjectTotal : $sessionSubjectTotal));
        if ($grossFee <= 0 && $monthlySubjectTotal > 0) {
            $grossFee = $monthlySubjectTotal;
        }

        $discountVal = 0;
        if ($s->is_remise && $s->remise > 0) {
            if ($s->discount_type === 'percentage') {
                $discountVal = ($grossFee * (float) $s->remise) / 100.0;
            } else {
                $discountVal = (float) $s->remise;
            }
        }

        $netPayable = max(0, $grossFee - $discountVal);

        // Vrai si le montant vient du tarif par defaut (aucun prix renseigne)
        $isDefaultPrice = $usedDefaultPrice && $s->base_fee <= 0 && $monthlySubjectTotal <= 0;

        return [
            'id'            => $s->id,
            'name'          => $s->name,
            'level'         => $s->level,
            'group_name'    => $s->group_name,
            'phone'         => $s->phone,
            'parent_phone'  => $s->parent_phone,
            'status'        => $s->status,
            'is_remise'     => (bool) $s->is_remise,
            'remise'        => (float) $s->remise,
            'discount_type' => $s->discount_type ?: 'fixed',
            'base_fee'      => (float) $s->base_fee,
            'calculated_fee'=> [
                'gross'      => round($grossFee, 2),
                'discount'   => round($discountVal, 2),
                'netPayable' => round($netPayable, 2),
                'isDefaultPrice' => $isDefaultPrice,
            ],
            'attendance_stats' => [
                'total'   => $totalSessions,
                'present' => $presentCount,
                'absent'  => $absentCount,
                'excused' => $excusedCount,
            ],
            'subjects'      => $s->enrollments->map(function ($e) {
                return [
                    'id'                => $e->id,
                    'subject'           => $e->subject_name,
                    'teacher'           => $e->teacher ? $e->teacher->name : 'N/A',
                    'teacher_id'        => $e->teacher_id,
                    'group_name'        => $e->group_name,
                    'price_per_month'   => (float) $e->price_per_month,
                    'price_per_session' => (float) $e->price_per_session,
                ];
            }),
        ];
    }
}
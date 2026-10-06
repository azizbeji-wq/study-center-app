<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * List attendances with optional filtering by group, date, student, teacher.
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['student', 'teacher'])->orderBy('session_date', 'desc');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('group_name')) {
            $query->where('group_name', $request->group_name);
        }
        if ($request->filled('subject_name')) {
            $query->where('subject_name', $request->subject_name);
        }
        if ($request->filled('session_date')) {
            $query->where('session_date', $request->session_date);
        }

        if ($request->boolean('paginate') || $request->filled('page')) {
            $perPage = (int) $request->input('per_page', 25);
            return response()->json($query->paginate($perPage));
        }

        $attendances = $query->get();

        return response()->json($attendances);
    }

    /**
     * Record single attendance.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'   => 'required|exists:students,id',
            'teacher_id'   => 'nullable|exists:teachers,id',
            'group_name'   => 'nullable|string|max:100',
            'subject_name' => 'required|string|max:255',
            'session_date' => 'required|date',
            'status'       => 'required|in:present,absent,excused,late',
            'notes'        => 'nullable|string',
        ]);

        $attendance = Attendance::updateOrCreate(
            [
                'student_id'   => $data['student_id'],
                'subject_name' => $data['subject_name'],
                'session_date' => $data['session_date'],
            ],
            $data
        );

        $attendance->load(['student', 'teacher']);
        return response()->json($attendance, 201);
    }

    /**
     * Batch record attendance for a whole group session.
     */
    public function batch(Request $request)
    {
        $data = $request->validate([
            'session_date' => 'required|date',
            'subject_name' => 'required|string',
            'teacher_id'   => 'nullable|exists:teachers,id',
            'group_name'   => 'nullable|string',
            'records'      => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status'     => 'required|in:present,absent,excused,late',
            'records.*.notes'      => 'nullable|string',
        ]);

        $saved = DB::transaction(function () use ($data) {
            $saved = [];
            foreach ($data['records'] as $record) {
                $item = Attendance::updateOrCreate(
                    [
                        'student_id'   => $record['student_id'],
                        'subject_name' => $data['subject_name'],
                        'session_date' => $data['session_date'],
                    ],
                    [
                        'teacher_id'   => $data['teacher_id'] ?? null,
                        'group_name'   => $data['group_name'] ?? null,
                        'status'       => $record['status'],
                        'notes'        => $record['notes'] ?? null,
                    ]
                );
                $saved[] = $item;
            }
            return $saved;
        });

        $date = $data['session_date'];
        $subject = $data['subject_name'];
        $group = $data['group_name'] ?? 'N/A';

        \App\Models\ActivityLog::record(
            $request->user(),
            'attendance',
            'recorded',
            "Appel de présence validé pour {$date} ({$group}, {$subject} : " . count($saved) . " élèves pointés)",
            ['date' => $date, 'group' => $group, 'subject' => $subject, 'count' => count($saved)],
            $request->ip()
        );

        return response()->json([
            'message' => 'Présences enregistrées avec succès',
            'count'   => count($saved),
        ]);
    }

    /**
     * Get attendance summary and calculated fee for a specific student.
     */
    public function studentSummary($studentId)
    {
        $student = Student::with(['enrollments.teacher', 'attendances'])->findOrFail($studentId);

        $attendances = $student->attendances()->orderBy('session_date', 'desc')->get();
        $totalSessions = $attendances->count();
        $presentSessions = $attendances->where('status', 'present')->count();
        $absentSessions = $attendances->where('status', 'absent')->count();

        // Calculate amount to pay
        // Mode 1: If base_fee is explicitly set
        // Mode 2: Sum of monthly subjects
        // Mode 3: Session count * price_per_session
        $monthlyFee = (float) $student->enrollments->sum('price_per_month');
        $sessionFee = 0;
        $usedDefaultPrice = false;
        foreach ($student->enrollments as $enr) {
            // Prix par seance, sinon prix mensuel / 4, sinon tarif par defaut
            [$perSession, $priceSource] = Pricing::enrollmentSessionPrice($enr);
            $count = $attendances->where('subject_name', $enr->subject_name)->where('status', 'present')->count();
            if ($count > 0) {
                $sessionFee += $count * $perSession;
                if ($priceSource === 'default') {
                    $usedDefaultPrice = true;
                }
            }
        }

        $gross = (float) ($student->base_fee > 0 ? $student->base_fee : ($monthlyFee > 0 ? $monthlyFee : $sessionFee));
        $discountAmount = 0;
        if ($student->is_remise && $student->remise > 0) {
            if ($student->discount_type === 'percentage') {
                $discountAmount = ($gross * (float) $student->remise) / 100.0;
            } else {
                $discountAmount = (float) $student->remise;
            }
        }
        $net = max(0, $gross - $discountAmount);
        $isDefaultPrice = $usedDefaultPrice && $student->base_fee <= 0 && $monthlyFee <= 0;

        return response()->json([
            'student' => [
                'id'         => $student->id,
                'name'       => $student->name,
                'level'      => $student->level,
                'group_name' => $student->group_name,
                'is_remise'  => (bool) $student->is_remise,
                'remise'     => (float) $student->remise,
                'discount_type' => $student->discount_type ?: 'fixed',
            ],
            'attendance_count' => [
                'total'   => $totalSessions,
                'present' => $presentSessions,
                'absent'  => $absentSessions,
            ],
            'calculation' => [
                'gross_fee'       => round($gross, 2),
                'discount_amount' => round($discountAmount, 2),
                'amount_to_pay'   => round($net, 2),
                'is_default_price' => $isDefaultPrice,
            ],
            'attendances' => $attendances,
        ]);
    }

    public function destroy($id)
    {
        $att = Attendance::findOrFail($id);
        $att->delete();
        return response()->json(['message' => 'Présence supprimée']);
    }
}
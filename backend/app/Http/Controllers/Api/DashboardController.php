<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Payment;
use App\Models\ScheduleItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $totalTeachers = Teacher::count();
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->count();
        $weeklyLessons = Teacher::sum('weekly_lessons');

        // Top teachers list
        // Formatted with name, subject, student count, lessons, and teacher share for director
        $isDirector = $request->user() && $request->user()->role === 'director';
        $teachers = Teacher::withCount('enrollments')->with('enrollments')->get();
        $topTeachers = $teachers->map(function ($t) use ($isDirector) {
            $enrollmentsCount = $t->enrollments_count ?? 0;
            $weeklyLessons = (int) ($t->weekly_lessons ?? 4);
            $monthlyLessons = $weeklyLessons * 4;

            $monthlyGross = (float) $t->enrollments->sum('price_per_month');
            if ($monthlyGross <= 0 && $enrollmentsCount > 0) {
                $monthlyGross = $enrollmentsCount * 60.0;
            }

            $remType = $t->remuneration_type ?? 'percentage';
            $ratePct = $t->rate_percentage !== null ? (float) $t->rate_percentage : 70.0;
            $salaryPerLesson = $t->salary_per_lesson !== null ? (float) $t->salary_per_lesson : 35.0;

            if ($remType === 'percentage' || ($ratePct > 0 && $salaryPerLesson == 0)) {
                $teacherShare = round(($monthlyGross * $ratePct) / 100.0, 2);
                $centerShare = round(max(0, $monthlyGross - $teacherShare), 2);
            } else {
                $teacherShare = round($monthlyLessons * $salaryPerLesson, 2);
                $centerShare = round(max(0, $monthlyGross - $teacherShare), 2);
            }

            $item = [
                'id' => $t->id,
                'name' => $t->name,
                'subject' => $t->subject,
                'students' => $enrollmentsCount,
                'lessons' => $weeklyLessons,
            ];

            if ($isDirector) {
                $item['monthly_gross_revenue'] = $monthlyGross;
                $item['teacher_share'] = $teacherShare;
                $item['center_share'] = $centerShare;
                $item['remuneration_type'] = $remType;
                $item['rate_percentage'] = $ratePct;
                $item['salary_per_lesson'] = $salaryPerLesson;
            }

            return $item;
        });

        // Recent activity with user attribution
        $activities = [];

        // 1. Fetch from dedicated ActivityLog table
        $logs = \App\Models\ActivityLog::latest()->take(10)->get();
        foreach ($logs as $l) {
            $activities[] = [
                'id'        => 'act_' . $l->id,
                'type'      => $l->type,
                'action'    => $l->action,
                'message'   => $l->message,
                'user_name' => $l->user_name ?: 'Directeur',
                'user_role' => $l->user_role ?: 'director',
                'time'      => $l->created_at ? $l->created_at->diffForHumans() : 'Récemment',
                'timestamp' => $l->created_at ? $l->created_at->timestamp : 0,
            ];
        }

        // 2. If activity_logs is sparse, supplement with payments and students, ensuring user_name is ALWAYS attached
        if (count($activities) < 5) {
            $defaultDirectorName = \App\Models\User::where('role', 'director')->value('name') ?? 'Directeur Fondateur';

            $recentPayments = Payment::latest()->take(4)->get();
            foreach ($recentPayments as $p) {
                $audit = \App\Models\PaymentAuditLog::where('payment_id', $p->id)->first();
                $userName = $audit?->user_name ?: $defaultDirectorName;
                $userRole = $audit?->user_role ?: 'director';
                $statusLabel = $p->status === 'paid' ? 'reçu' : ($p->status === 'pending' ? 'en attente' : 'en retard');

                $activities[] = [
                    'id'        => 'p_' . $p->id,
                    'type'      => 'payment',
                    'action'    => 'created',
                    'message'   => "Paiement de {$p->amount} TND pour {$p->student_name} ({$statusLabel})",
                    'user_name' => $userName,
                    'user_role' => $userRole,
                    'time'      => $p->created_at ? $p->created_at->diffForHumans() : 'Récemment',
                    'timestamp' => $p->created_at ? $p->created_at->timestamp : 0,
                ];
            }

            $recentStudents = Student::latest()->take(3)->get();
            foreach ($recentStudents as $s) {
                $activities[] = [
                    'id'        => 's_' . $s->id,
                    'type'      => 'student',
                    'action'    => 'created',
                    'message'   => "Nouvel élève inscrit : {$s->name} ({$s->level})",
                    'user_name' => $defaultDirectorName,
                    'user_role' => 'director',
                    'time'      => $s->created_at ? $s->created_at->diffForHumans() : 'Récemment',
                    'timestamp' => $s->created_at ? $s->created_at->timestamp : 0,
                ];
            }
        }

        // Sort activities by timestamp desc
        usort($activities, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        // Deduplicate and take top 8
        $uniqueActivities = [];
        $seen = [];
        foreach ($activities as $act) {
            $key = $act['message'] . '_' . $act['timestamp'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueActivities[] = $act;
            }
            if (count($uniqueActivities) >= 8) break;
        }

        $recentActivity = $uniqueActivities;

        return response()->json([
            'stats' => [
                'totalStudents' => $totalStudents,
                'activeStudents' => $activeStudents,
                'totalTeachers' => $totalTeachers,
                'totalRevenue' => $totalRevenue,
                'pendingPayments' => $pendingPayments,
                'weeklyLessons' => $weeklyLessons,
            ],
            'topTeachers' => $topTeachers,
            'recentActivity' => $recentActivity,
        ]);
    }
}

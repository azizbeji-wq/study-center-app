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
    public function index()
    {
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $totalTeachers = Teacher::count();
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->count();
        $weeklyLessons = Teacher::sum('weekly_lessons');

        // Top teachers list
        // Formatted with name, subject, student count, lessons
        $teachers = Teacher::withCount('enrollments')->get();
        $topTeachers = $teachers->map(function ($t) {
            return [
                'name' => $t->name,
                'subject' => $t->subject,
                'students' => $t->enrollments_count,
                'lessons' => $t->weekly_lessons,
            ];
        });

        // Recent activity
        // Synthesize recent activities from Database logs
        $activities = [];

        // Add payments
        $recentPayments = Payment::latest()->take(3)->get();
        foreach ($recentPayments as $p) {
            $statusLabel = $p->status === 'paid' ? 'reçu' : ($p->status === 'pending' ? 'en attente' : 'en retard');
            $activities[] = [
                'id' => 'p_' . $p->id,
                'type' => 'payment',
                'message' => "Paiement de {$p->amount} TND pour {$p->student_name} ({$statusLabel})",
                'time' => $p->created_at ? $p->created_at->diffForHumans() : 'Récemment',
                'timestamp' => $p->created_at ? $p->created_at->timestamp : 0,
            ];
        }

        // Add students
        $recentStudents = Student::latest()->take(2)->get();
        foreach ($recentStudents as $s) {
            $activities[] = [
                'id' => 's_' . $s->id,
                'type' => 'student',
                'message' => "Nouvel élève inscrit: {$s->name}",
                'time' => $s->created_at ? $s->created_at->diffForHumans() : 'Récemment',
                'timestamp' => $s->created_at ? $s->created_at->timestamp : 0,
            ];
        }

        // Sort activities by timestamp desc
        usort($activities, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        // Take top 5
        $recentActivity = array_slice($activities, 0, 5);

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

<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Pricing;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherPayoutService
{
    /**
     * Calcule la rémunération exacte d'un enseignant pour une séance donnée.
     * RÈGLE STRICTE : Les remises appliquées aux élèves n'impactent JAMAIS ce montant.
     * Le calcul est effectué sur le prix de référence (plein tarif avant remise).
     */
    public function calculateTeacherEarnings(Session $session, Teacher $teacher): float
    {
        $presentAttendances = $session->getPresentAttendances();
        $studentCount = $presentAttendances->count();

        if ($studentCount === 0) {
            return 0.0;
        }

        $remType = $teacher->remuneration_type ?? 'percentage';
        $ratePct = (float) ($teacher->rate_percentage ?? 70.0);
        $salaryPerLesson = (float) ($teacher->salary_per_lesson ?? 25.0);
        $fixedStudentRate = (float) ($teacher->fixed_student_rate ?? 15.0);
        $centerFixedPerStudent = (float) ($teacher->center_fixed_per_student ?? 20.0);

        // ── MODE 1 : POURCENTAGE (Formule C) ─────────────────────────────────
        if ($remType === 'percentage') {
            $totalEarnings = 0.0;
            foreach ($presentAttendances as $att) {
                // Tarif plein de référence pour cet élève (AVANT toute remise)
                $refPrice = $this->getStudentReferencePrice($att, $session, $teacher);
                $studentShare = ($refPrice * $ratePct) / 100.0;
                $totalEarnings += $studentShare;
            }
            return round($totalEarnings, 2);
        }

        // ── MODE 2 : PART FIXE CENTRE (Formule A) ─────────────────────────────
        // Le centre prélève sa part fixe convenue par élève, le reliquat revient au professeur.
        // Remise absorbée : calculé sur le prix de référence avant remise.
        if ($remType === 'fixed_center') {
            $centerSharePerSession = round($centerFixedPerStudent / Pricing::SESSIONS_PER_MONTH, 2);
            $totalEarnings = 0.0;
            foreach ($presentAttendances as $att) {
                $refPrice = $this->getStudentReferencePrice($att, $session, $teacher);
                $studentShare = max(0.0, $refPrice - $centerSharePerSession);
                $totalEarnings += $studentShare;
            }
            return round($totalEarnings, 2);
        }

        // ── MODE 3 : MONTANT FIXE (Formule B) ─────────────────────────────────
        // Option A : Montant fixe par élève inscrit/présent
        if ($fixedStudentRate > 0) {
            return round($studentCount * $fixedStudentRate, 2);
        }

        // Option B : Montant fixe par séance effectuée (forfait de cours)
        return round($salaryPerLesson > 0 ? $salaryPerLesson : 25.0, 2);
    }

    /**
     * Calcule le revenu net revenant au centre pour la séance après déduction de la part enseignant.
     * RÈGLE STRICTE : Les remises accordées aux élèves sont intégralement déduites de cette marge.
     */
    public function calculateCenterRevenue(Session $session, ?Teacher $teacher = null): float
    {
        $teacher = $teacher ?? $session->teacher;
        if (!$teacher) {
            return 0.0;
        }

        $presentAttendances = $session->getPresentAttendances();
        if ($presentAttendances->isEmpty()) {
            return 0.0;
        }

        // 1. Chiffre d'affaires effectif perçu / dû par les élèves (après remises)
        $effectiveRevenue = 0.0;
        foreach ($presentAttendances as $att) {
            $refPrice = $this->getStudentReferencePrice($att, $session, $teacher);
            $discount = $this->getStudentDiscountAmount($att, $refPrice);
            $effectivePrice = max(0.0, $refPrice - $discount);
            $effectiveRevenue += $effectivePrice;
        }

        // 2. Part due à l'enseignant (calculée sur le plein tarif de référence)
        $teacherEarnings = $this->calculateTeacherEarnings($session, $teacher);

        // 3. Marge nette du centre (absorbe 100% des réductions accordées)
        return round($effectiveRevenue - $teacherEarnings, 2);
    }

    /**
     * Génère une analyse complète et détaillée de la séance (Chiffre d'affaires brut, remises, part prof, marge centre).
     */
    public function calculateSessionBreakdown(Session $session, ?Teacher $teacher = null): array
    {
        $teacher = $teacher ?? $session->teacher;
        $presentAttendances = $session->getPresentAttendances();

        $grossReferenceRevenue = 0.0;
        $effectiveRevenue = 0.0;
        $totalDiscountAbsorbed = 0.0;
        $studentsBreakdown = [];

        $remType = $teacher ? ($teacher->remuneration_type ?? 'percentage') : 'percentage';
        $ratePct = $teacher ? (float) ($teacher->rate_percentage ?? 70.0) : 70.0;
        $fixedCenterPerSession = $teacher ? round(($teacher->center_fixed_per_student ?? 20.0) / Pricing::SESSIONS_PER_MONTH, 2) : 5.0;
        $fixedStudentRate = $teacher ? (float) ($teacher->fixed_student_rate ?? 15.0) : 15.0;

        foreach ($presentAttendances as $att) {
            $student = $this->resolveStudent($att);
            $refPrice = $this->getStudentReferencePrice($att, $session, $teacher);
            $discount = $this->getStudentDiscountAmount($att, $refPrice);
            $effectivePrice = max(0.0, $refPrice - $discount);

            // Calcul part enseignant pour cet élève (sur prix plein avant remise)
            if ($remType === 'percentage') {
                $tShare = round(($refPrice * $ratePct) / 100.0, 2);
            } elseif ($remType === 'fixed_center') {
                $tShare = round(max(0.0, $refPrice - $fixedCenterPerSession), 2);
            } else {
                $tShare = round($fixedStudentRate > 0 ? $fixedStudentRate : (($teacher->salary_per_lesson ?? 25.0) / max(1, $presentAttendances->count())), 2);
            }

            $cShare = round($effectivePrice - $tShare, 2);

            $grossReferenceRevenue += $refPrice;
            $effectiveRevenue += $effectivePrice;
            $totalDiscountAbsorbed += $discount;

            $studentsBreakdown[] = [
                'student_id'       => $student?->id,
                'student_name'     => $student?->name ?? 'Élève inconnu',
                'is_remise'        => (bool) ($student?->is_remise ?? false),
                'discount_type'    => $student?->discount_type ?? 'fixed',
                'discount_value'   => (float) ($student?->remise ?? 0.0),
                'reference_price'  => round($refPrice, 2),
                'discount_amount'  => round($discount, 2),
                'effective_price'  => round($effectivePrice, 2),
                'teacher_share'    => round($tShare, 2),
                'center_share'     => round($cShare, 2),
            ];
        }

        $teacherEarnings = $teacher ? $this->calculateTeacherEarnings($session, $teacher) : 0.0;
        $centerNetRevenue = round($effectiveRevenue - $teacherEarnings, 2);

        return [
            'session_date'             => $session->sessionDate,
            'subject_name'             => $session->subjectName,
            'group_name'               => $session->groupName,
            'total_students'           => $session->attendances->count(),
            'present_students_count'   => $presentAttendances->count(),
            'remuneration_type'        => $remType,
            'gross_reference_revenue'  => round($grossReferenceRevenue, 2),
            'effective_revenue'        => round($effectiveRevenue, 2),
            'total_discount_absorbed'  => round($totalDiscountAbsorbed, 2),
            'teacher_earnings'         => round($teacherEarnings, 2),
            'center_net_revenue'       => round($centerNetRevenue, 2),
            'students_breakdown'       => $studentsBreakdown,
        ];
    }

    /**
     * RÈGLES DE CUMUL / RÔLES MULTIPLES :
     * - Directeur cumulant un rôle d'enseignant : isolation du forfait de direction fixe de la part d'enseignant.
     * - Personnel / Secrétaire animant des séances : cumul distinct du salaire fixe avec les gains de cours.
     */
    public function calculateMultiRoleCompensation(User $user, ?string $month = null, float $baseAdminSalary = 0.0): array
    {
        $teacher = $user->teacher;
        $teachingEarnings = 0.0;
        $sessionsCount = 0;

        if ($teacher) {
            $formatted = $this->formatTeacher($teacher);
            $teachingEarnings = (float) ($formatted['teacher_share'] ?? 0.0);
            $sessionsCount = (int) ($formatted['monthlyLessons'] ?? 0);
        }

        if ($user->role === 'director') {
            return [
                'user_id'                    => $user->id,
                'name'                       => $user->name,
                'role'                       => 'director',
                'rule'                       => 'isolation',
                'admin_base_salary'          => round($baseAdminSalary, 2),
                'teaching_variable_earnings' => round($teachingEarnings, 2),
                'total_compensation'         => round($baseAdminSalary + $teachingEarnings, 2),
                'teaching_sessions_count'    => $sessionsCount,
                'note'                       => 'Salaire administratif de direction strictement isolé de la part variable d\'enseignant.',
            ];
        }

        // Personnel administratif / Secrétaire
        return [
            'user_id'                    => $user->id,
            'name'                       => $user->name,
            'role'                       => $user->role,
            'rule'                       => 'cumul_distinct',
            'staff_fixed_salary'         => round($baseAdminSalary, 2),
            'teaching_earnings'          => round($teachingEarnings, 2),
            'total_combined_payout'      => round($baseAdminSalary + $teachingEarnings, 2),
            'teaching_sessions_count'    => $sessionsCount,
            'note'                       => 'Salaire de base fixe cumulé distinctement avec les gains d\'animation de cours.',
        ];
    }

    /**
     * Formate et calcule la synthèse mensuelle complète d'un enseignant (compatible formatTeacher).
     */
    public function formatTeacher(Teacher $t, ?Request $request = null): array
    {
        $enrollmentStudentIds = $t->enrollments()->pluck('student_id')->toArray();
        $attendanceStudentIds = Attendance::where('teacher_id', $t->id)->pluck('student_id')->toArray();
        $allDistinctStudentIds = array_unique(array_filter(array_merge($enrollmentStudentIds, $attendanceStudentIds)));
        $studentCount = count($allDistinctStudentIds);

        $monthlyGross = (float) $t->enrollments->sum('price_per_month');
        if ($monthlyGross == 0 && $studentCount > 0) {
            $monthlyGross = $studentCount * ((float) ($t->salary_per_lesson ?? 35.0) * Pricing::SESSIONS_PER_MONTH);
        }

        $remType = $t->remuneration_type ?? 'percentage';
        $ratePct = (float) ($t->rate_percentage ?? 70.0);
        $salaryPerLesson = (float) ($t->salary_per_lesson ?? 25.0);
        $fixedStudentRate = (float) ($t->fixed_student_rate ?? 15.0);
        $centerFixedPerStudent = (float) ($t->center_fixed_per_student ?? 20.0);
        $weeklyLessons = (int) ($t->weekly_lessons ?? 4);
        $monthlyLessons = max(1, $weeklyLessons * Pricing::SESSIONS_PER_MONTH);

        if ($remType === 'percentage') {
            // Formule C : Pourcentage (%) du montant brut des cours (remises absorbées par l'administration)
            $teacherShare = round(($monthlyGross * $ratePct) / 100.0, 2);
            $centerShare = round(max(0.0, $monthlyGross - $teacherShare), 2);
            $perSessionEst = round($teacherShare / $monthlyLessons, 2);
        } elseif ($remType === 'fixed_center') {
            // Formule A : Part Fixe Administration par élève (ex: 20 TND/élève) - L'enseignant prend le reste
            $totalCenterTarget = round($studentCount * $centerFixedPerStudent, 2);
            $teacherShare = round(max(0.0, $monthlyGross - $totalCenterTarget), 2);
            $centerShare = round(min($monthlyGross, $totalCenterTarget), 2);
            $perSessionEst = round($teacherShare / max(1, $monthlyLessons), 2);
        } else {
            // Formule B : Montant fixe Enseignant par élève inscrit
            $teacherShare = round($studentCount * $fixedStudentRate, 2);
            $centerShare = round(max(0.0, $monthlyGross - $teacherShare), 2);
            $perSessionEst = round($teacherShare / max(1, $monthlyLessons), 2);
        }

        $payoutDateStr = $t->payout_date instanceof \DateTimeInterface 
            ? $t->payout_date->format('Y-m-d') 
            : ($t->payout_date ?: null);

        $row = [
            'id'                       => $t->id,
            'user_id'                  => $t->user_id,
            'user_role'                => $t->user?->role,
            'name'                     => $t->name,
            'subject'                  => $t->subject,
            'phone'                    => $t->phone,
            'email'                    => $t->email,
            'studentCount'             => $studentCount,
            'lesson_rate'              => $salaryPerLesson,
            'weeklyLessons'            => $weeklyLessons,
            'monthlyLessons'           => $monthlyLessons,
            'remuneration_type'        => $remType,
            'rate_percentage'          => $ratePct,
            'salary_per_lesson'        => $salaryPerLesson,
            'fixed_student_rate'       => $fixedStudentRate,
            'center_fixed_per_student' => $centerFixedPerStudent,
            'monthly_gross_revenue'    => $monthlyGross,
            'teacher_share'            => $teacherShare,
            'center_share'             => $centerShare,
            'per_session_share'        => $perSessionEst,
            'payout_status'            => $t->payout_status ?: 'pending',
            'payout_date'              => $payoutDateStr,
            'payout_month'             => $t->payout_month,
            'payout_amount'            => $t->payout_amount ? (float) $t->payout_amount : $teacherShare,
            'payout_by_user_name'      => $t->payout_by_user_name ?: ($t->payout_status === 'paid' ? 'Directeur Fondateur' : null),
        ];

        // Masquer le salaire si l'utilisateur connecté n'est pas directeur et n'est pas le professeur concerné
        if ($request && $request->user() && $request->user()->role !== 'director') {
            $isOwnProfile = $request->user()->teacher?->id === $t->id || $request->user()->email === $t->email;
            if (!$isOwnProfile) {
                unset(
                    $row['remuneration_type'],
                    $row['rate_percentage'],
                    $row['salary_per_lesson'],
                    $row['fixed_student_rate'],
                    $row['center_fixed_per_student'],
                    $row['monthly_gross_revenue'],
                    $row['teacher_share'],
                    $row['center_share'],
                    $row['per_session_share'],
                    $row['payout_status'],
                    $row['payout_date'],
                    $row['payout_month'],
                    $row['payout_amount'],
                    $row['payout_by_user_name']
                );
            }
        }

        return $row;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // MÉTHODES INTERNES DE RÉSOLUTION TARIFAIRE
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Résout le prix de référence (plein tarif) pour un élève lors d'une séance.
     */
    private function getStudentReferencePrice($attendanceItem, Session $session, ?Teacher $teacher): float
    {
        // 1. Si la session a un tarif explicite fixé
        if ($session->defaultPricePerSession !== null && $session->defaultPricePerSession > 0) {
            return (float) $session->defaultPricePerSession;
        }

        $student = $this->resolveStudent($attendanceItem);
        if (!$student) {
            return (float) ($teacher?->salary_per_lesson > 0 ? $teacher->salary_per_lesson : Pricing::DEFAULT_SESSION_PRICE);
        }

        // 2. Chercher dans les inscriptions de l'élève pour cet enseignant ou cette matière
        if ($teacher && method_exists($student, 'enrollments')) {
            $enrollment = $student->enrollments
                ->where('teacher_id', $teacher->id)
                ->first();

            if (!$enrollment && !empty($session->subjectName)) {
                $enrollment = $student->enrollments
                    ->where('subject_name', $session->subjectName)
                    ->first();
            }

            if ($enrollment) {
                [$sessionPrice] = Pricing::enrollmentSessionPrice($enrollment);
                if ($sessionPrice > 0) {
                    return (float) $sessionPrice;
                }
            }
        }

        // 3. Tarif standard déduit de l'enseignant ou valeur par défaut globale
        if ($teacher && $teacher->salary_per_lesson > 0) {
            return (float) $teacher->salary_per_lesson;
        }

        return Pricing::DEFAULT_SESSION_PRICE;
    }

    /**
     * Calcule le montant de la réduction accordée à un élève sur le prix d'une séance.
     */
    private function getStudentDiscountAmount($attendanceItem, float $referencePrice): float
    {
        $student = $this->resolveStudent($attendanceItem);
        if (!$student || !$student->is_remise || $student->remise <= 0) {
            return 0.0;
        }

        $remiseVal = (float) $student->remise;
        $discountType = $student->discount_type ?: 'fixed';

        if ($discountType === 'percentage') {
            return round(($referencePrice * $remiseVal) / 100.0, 2);
        }

        // Remise forfaitaire fixe : divisée par 4 séances mensuelles pour une séance
        $discountPerSession = round($remiseVal / Pricing::SESSIONS_PER_MONTH, 2);
        return min($referencePrice, $discountPerSession);
    }

    /**
     * Résout l'objet Student à partir de l'élément d'attendance.
     */
    private function resolveStudent($attendanceItem): ?Student
    {
        if ($attendanceItem instanceof Attendance) {
            if ($attendanceItem->relationLoaded('student')) {
                return $attendanceItem->getRelation('student');
            }
            try {
                if ($attendanceItem->student_id) {
                    return $attendanceItem->student;
                }
            } catch (\Throwable) {
                return null;
            }
            return null;
        }

        if (is_array($attendanceItem) && isset($attendanceItem['student'])) {
            return $attendanceItem['student'] instanceof Student ? $attendanceItem['student'] : null;
        }

        if (is_array($attendanceItem) && isset($attendanceItem['student_id'])) {
            try {
                return Student::find($attendanceItem['student_id']);
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_object($attendanceItem) && isset($attendanceItem->student)) {
            return $attendanceItem->student instanceof Student ? $attendanceItem->student : null;
        }

        if (is_object($attendanceItem) && isset($attendanceItem->student_id)) {
            try {
                return Student::find($attendanceItem->student_id);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}

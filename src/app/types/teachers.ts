export type TeacherRemunerationType = 'percentage' | 'fixed' | 'fixed_center';

export interface Teacher {
  id: number;
  name: string;
  subject: string;
  subjects?: string[];
  phone: string;
  email: string;
  studentCount: number;
  weeklyLessons: number;
  monthlyLessons?: number;
  remuneration_type?: TeacherRemunerationType;
  rate_percentage?: number;
  salary_per_lesson?: number;
  fixed_student_rate?: number;
  center_fixed_per_student?: number;
  monthly_gross_revenue?: number;
  teacher_share?: number;
  center_share?: number;
  per_session_share?: number;
  payout_status?: 'paid' | 'pending';
  payout_date?: string | null;
  payout_month?: string | null;
  payout_amount?: number | null;
  user_role?: string;
  user_id?: number | null;
}

export interface TeacherFormData {
  name: string;
  subjects: string[];
  phone: string;
  email: string;
  weeklyLessons: number;
  remuneration_type: TeacherRemunerationType;
  rate_percentage: number;
  salary_per_lesson: number;
  fixed_student_rate: number;
  center_fixed_per_student: number;
}

export type PayoutFilter = 'all' | 'paid' | 'pending';

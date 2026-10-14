export type AccountingSubTab = 'overview' | 'expenses' | 'teachers' | 'dues' | 'cash_audit' | 'closing';

export type AccountingPeriod = '12months' | 'school_year' | 'current_month';

export interface ExpenseItem {
  id: number;
  title: string;
  category: string;
  amount: number;
  date: string;
  payment_method: string;
  receipt_reference?: string;
  is_recurring: boolean;
  notes?: string;
  created_at?: string;
}

export interface TeacherAccountingItem {
  id: number;
  name: string;
  subject?: string;
  remunerationType?: 'percentage' | 'fixed' | 'fixed_center';
  ratePercentage?: number;
  centerFixedPerStudent?: number;
  fixedStudentRate?: number;
  sessionsCount: number;
  revenueCollected: number;
  teacherShare: number;
  centerMargin: number;
  paidAmount: number;
  remainingToPay: number;
  payoutStatus: string;
}

export interface AccountingAuditLog {
  id: number;
  payment_id?: number;
  user_name?: string;
  user_role?: string;
  action: string;
  amount?: number;
  student_name?: string;
  payment_method?: string;
  details?: any;
  ip_address?: string;
  created_at: string;
}

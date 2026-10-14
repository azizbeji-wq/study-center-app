export type DueStatus = 'pending' | 'overdue' | 'ok';

export interface Payment {
  id: number;
  studentName: string;
  month: string;
  amount: number;
  date: string | null;
  status: 'paid' | 'pending' | 'overdue';
  type: 'student' | 'daycare';
  studentId: number | null;
  daycareChildId: number | null;
  teacherId: number | null;
  teacherName: string | null;
  sessionsPaid: number;
  monthsPaid: number;
  createdByName?: string;
  createdByRole?: string;
}

export interface DueStudent {
  key: string;
  studentId: number;
  studentName: string;
  groupName: string | null;
  teacherId: number;
  teacherName: string;
  subjects: string[];
  sessionsAttended: number;
  sessionsPaid: number;
  sessionsUnpaid: number;
  pricePerSession: number;
  priceSource: 'session' | 'monthly' | 'default';
  unitAmount: number;
  amountDue: number;
  status: DueStatus;
}

export interface DueDaycare {
  key: string;
  daycareChildId: number;
  name: string;
  parentName: string;
  monthlyFee: number;
  feeSource?: 'set' | 'default';
  monthsDue: number;
  monthsPaid: number;
  monthsUnpaid: number;
  amountDue: number;
  status: DueStatus;
}

export interface Dues {
  thresholds: {
    studentPendingFrom: number;
    studentOverdueAfter: number;
    daycarePendingFrom: number;
    daycareOverdueAfter: number;
  };
  pricing: { sessionsPerMonth: number; defaultSessionPrice: number; defaultDaycareMonthlyFee: number };
  students: DueStudent[];
  daycare: DueDaycare[];
  totals: { pending: number; overdue: number; pendingCount: number; overdueCount: number };
}

export interface StudentLite {
  id: number;
  name: string;
  level: string;
  group_name?: string;
  is_remise?: boolean;
  remise?: number;
  discount_type?: 'percentage' | 'fixed';
  subjects?: Array<{ teacher_id?: number; teacher?: string; price_per_session?: number; price_per_month?: number }>;
}

export interface ChildLite {
  id: number;
  name: string;
  parentName: string;
  monthlyFee?: number;
}

export type Kind = 'student' | 'daycare';

export interface PaymentFormState {
  kind: Kind;
  studentId: number | null;
  teacherId: number | null;
  daycareChildId: number | null;
  sessionsPaid: number;
  monthsPaid: number;
  amount: number;
  date: string;
  month: string;
  status: 'paid' | 'pending' | 'overdue';
  studentName: string;
}

export interface PaymentPunctuality {
  isLate: boolean;
  label: string;
  badgeClass: string;
}

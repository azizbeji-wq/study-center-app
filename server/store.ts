import bcrypt from 'bcryptjs';

export type UserRole = 'director' | 'secretary' | 'teacher';

export interface UserRecord {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  passwordHash: string;
  role: UserRole;
  permissions: string[] | null;
  is_active: boolean;
  created_at: string;
}

export interface TokenRecord {
  token: string;
  userId: number;
  createdAt: number;
  expiresAt: number;
}

export interface TeacherRecord {
  id: number;
  user_id: number | null;
  name: string;
  subject: string;
  phone: string;
  email: string;
  weekly_lessons: number;
  remuneration_type: 'percentage' | 'fixed' | 'fixed_center';
  rate_percentage: number;
  salary_per_lesson: number;
  fixed_student_rate: number;
  center_fixed_per_student: number;
  payout_status: 'paid' | 'pending';
  payout_date: string | null;
  payout_month: string | null;
  payout_amount: number | null;
  payout_by_user_id: number | null;
  payout_by_user_name: string | null;
}

export interface SubjectRecord {
  id: number;
  name: string;
  slug: string;
  color: string;
  is_active: boolean;
}

export interface GroupRecord {
  id: number;
  name: string;
  level: string | null;
  subject: string | null;
  subject_id: number | null;
  teacher_id: number | null;
  user_id: number | null;
  max_capacity: number;
  notes: string | null;
  student_ids: number[];
}

export interface EnrollmentRecord {
  id: number;
  student_id: number;
  subject_name: string;
  teacher_id: number | null;
  group_name: string | null;
  sessions_per_month: number;
  price_per_month: number;
  price_per_session: number;
}

export interface StudentRecord {
  id: number;
  name: string;
  level: string;
  group_name: string | null;
  phone: string;
  parent_phone: string | null;
  status: 'active' | 'inactive';
  is_remise: boolean;
  remise: number;
  discount_type: 'percentage' | 'fixed';
  base_fee: number;
  created_at: string;
}

export interface AttendanceRecord {
  id: number;
  student_id: number;
  teacher_id: number | null;
  group_name: string | null;
  subject_name: string;
  session_date: string;
  status: 'present' | 'absent' | 'excused' | 'late';
  notes: string | null;
  created_at: string;
}

export interface DaycareChildRecord {
  id: number;
  name: string;
  age: number;
  parent_name: string;
  phone: string;
  schedule: string;
  status: 'present' | 'absent';
  monthly_fee: number;
  start_date: string;
  created_at: string;
}

export interface PaymentRecord {
  id: number;
  student_id: number | null;
  teacher_id: number | null;
  daycare_child_id: number | null;
  student_name: string;
  month: string;
  amount: number;
  date: string;
  status: 'paid' | 'pending' | 'overdue';
  payment_method: string;
  sessions_paid: number;
  months_paid: number;
  created_by: number | null;
  created_at: string;
}

export interface PaymentAuditLogRecord {
  id: number;
  payment_id: number;
  user_id: number | null;
  user_name: string;
  user_role: string;
  action: string;
  amount: number;
  student_name: string;
  payment_method: string;
  details: any;
  ip_address: string;
  created_at: string;
}

export interface ExpenseRecord {
  id: number;
  title: string;
  category: string;
  amount: number;
  date: string;
  payment_method: string;
  is_recurring: boolean;
  recurring_frequency: 'monthly' | 'annual';
  receipt_reference: string | null;
  notes: string | null;
  created_by: number | null;
  created_at: string;
}

export interface MonthlyClosingRecord {
  id: number;
  month: string;
  school_year: string;
  total_revenue: number;
  total_expenses: number;
  total_teacher_payouts: number;
  net_profit: number;
  closed_at: string | null;
  closed_by: number | null;
  notes: string | null;
  is_closed: boolean;
}

export interface ScheduleItemRecord {
  id: number;
  day: string;
  time: string;
  subject: string;
  teacher: string;
  level: string;
  room: string;
  group_name: string | null;
}

export interface SchoolRecord {
  id: number;
  name: string;
  address: string | null;
  city: string | null;
}

export interface SchoolClassRecord {
  id: number;
  school_id: number;
  level: number;
  section: string;
}

export interface SchoolCalendarImportRecord {
  id: number;
  school_class_id: number;
  file_name: string;
  file_type: string;
  buffer: Buffer;
  created_at: string;
}

export interface ActivityLogRecord {
  id: number;
  type: string;
  action: string;
  message: string;
  user_id: number | null;
  user_name: string;
  user_role: string;
  metadata?: any;
  created_at: string;
  timestamp: number;
}

export const VALID_GUIDE_KEYS = [
  'subjects',
  'teachers',
  'pricing',
  'students',
  'attendance',
  'payments',
  'schedule',
  'daycare',
  'reports',
  'users',
  'teacher_portal',
  'groups',
  // Checklist keys
  'checklist.subjects',
  'checklist.teachers',
  'checklist.pricing',
  'checklist.first_student',
  'checklist.first_attendance',
  'checklist.first_payment',
  'checklist.first_expense',
  'checklist.today_sessions',
  // Tour keys
  'tour.dashboard',
  'tour.students',
  'tour.attendance',
  'tour.payments',
  'tour.schedule',
  'tour.teachers',
  'tour.reports',
  'tour.daycare',
  'tour.schoolcalendar',
  'tour.today',
  'tour.groups',
  // Banner keys
  'banner.dashboard',
  'banner.students',
  'banner.attendance',
  'banner.payments',
  'banner.schedule',
  'banner.teachers',
  'banner.reports',
  'banner.daycare',
  'banner.schoolcalendar',
  'banner.today',
  'banner.groups',
];

export const DEFAULT_PERSONNEL_PERMISSIONS = [
  'students',
  'attendance',
  'daycare',
  'teachers',
  'schedule',
  'schoolcalendar',
  'payments',
];

export const DEFAULT_TEACHER_PERMISSIONS = ['teacher_portal', 'schedule', 'attendance'];

export const SESSIONS_PER_MONTH = 4;
export const DEFAULT_SESSION_PRICE = 15.0;
export const DEFAULT_DAYCARE_MONTHLY_FEE = 150.0;

function pad(n: number): string {
  return String(n).padStart(2, '0');
}

function isoDateOffset(daysOffset: number): string {
  const d = new Date();
  d.setDate(d.getDate() + daysOffset);
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function monthKeyOffset(monthsOffset: number): string {
  const d = new Date();
  d.setMonth(d.getMonth() + monthsOffset);
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
}

function frenchMonthLabel(dateStr: string): string {
  const months = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
  ];
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return 'Novembre 2026';
  return `${months[d.getMonth()]} ${d.getFullYear()}`;
}

const todayStr = isoDateOffset(0);
const currentMonthKey = monthKeyOffset(0);
const prevMonthKey = monthKeyOffset(-1);
const twoMonthsAgoKey = monthKeyOffset(-2);
const threeMonthsAgoKey = monthKeyOffset(-3);
const fourMonthsAgoKey = monthKeyOffset(-4);
const fiveMonthsAgoKey = monthKeyOffset(-5);

// Create a tiny valid PNG buffer for sample calendar import previews
const SAMPLE_PNG_BASE64 =
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

export class Store {
  private nextIds: Record<string, number> = {
    user: 4,
    teacher: 4,
    subject: 10,
    group: 4,
    student: 6,
    enrollment: 9,
    attendance: 60,
    daycare: 3,
    payment: 15,
    auditLog: 8,
    expense: 5,
    closing: 2,
    schedule: 7,
    school: 3,
    schoolClass: 3,
    calendarImport: 2,
    activityLog: 6,
  };

  nextId(key: string): number {
    this.nextIds[key] = (this.nextIds[key] || 1) + 1;
    return this.nextIds[key];
  }

  tokens = new Map<string, TokenRecord>();
  guideProgress = new Map<number, Set<string>>();

  users: UserRecord[] = [
    {
      id: 1,
      name: 'Directeur',
      email: 'director@studycenter.com',
      phone: '+216 71 000 001',
      passwordHash: bcrypt.hashSync('director123', 8),
      role: 'director',
      permissions: null,
      is_active: true,
      created_at: '2026-09-01 08:30',
    },
    {
      id: 2,
      name: 'Secrétaire',
      email: 'secretary@studycenter.com',
      phone: '+216 71 000 002',
      passwordHash: bcrypt.hashSync('secretary123', 8),
      role: 'secretary',
      permissions: [...DEFAULT_PERSONNEL_PERMISSIONS],
      is_active: true,
      created_at: '2026-09-01 09:00',
    },
    {
      id: 3,
      name: 'Professeur Mohamed',
      email: 'teacher@studycenter.com',
      phone: '+216 20 000 000',
      passwordHash: bcrypt.hashSync('teacher123', 8),
      role: 'teacher',
      permissions: [...DEFAULT_TEACHER_PERMISSIONS],
      is_active: true,
      created_at: '2026-09-01 09:15',
    },
  ];

  subjects: SubjectRecord[] = [
    { id: 1, name: 'Mathématiques', slug: 'mathematiques', color: '#6366f1', is_active: true },
    { id: 2, name: 'Sciences Physiques', slug: 'sciences-physiques', color: '#0ea5e9', is_active: true },
    { id: 3, name: 'SVT', slug: 'svt', color: '#10b981', is_active: true },
    { id: 4, name: 'Français', slug: 'francais', color: '#ec4899', is_active: true },
    { id: 5, name: 'Anglais', slug: 'anglais', color: '#f59e0b', is_active: true },
    { id: 6, name: 'Informatique', slug: 'informatique', color: '#8b5cf6', is_active: true },
    { id: 7, name: 'Arabe', slug: 'arabe', color: '#14b8a6', is_active: true },
    { id: 8, name: 'Économie & Gestion', slug: 'economie-gestion', color: '#f97316', is_active: true },
    { id: 9, name: 'Philosophie', slug: 'philosophie', color: '#64748b', is_active: true },
  ];

  teachers: TeacherRecord[] = [
    {
      id: 1,
      user_id: 3,
      name: 'Professeur Mohamed',
      subject: 'Mathématiques, Sciences Physiques',
      phone: '+216 20 000 000',
      email: 'teacher@studycenter.com',
      weekly_lessons: 6,
      remuneration_type: 'percentage',
      rate_percentage: 70,
      salary_per_lesson: 35,
      fixed_student_rate: 15,
      center_fixed_per_student: 20,
      payout_status: 'pending',
      payout_date: null,
      payout_month: null,
      payout_amount: null,
      payout_by_user_id: null,
      payout_by_user_name: null,
    },
    {
      id: 2,
      user_id: null,
      name: 'Mme. Amira Ben Salem',
      subject: 'Français, Anglais',
      phone: '+216 22 345 678',
      email: 'amira@studycenter.com',
      weekly_lessons: 4,
      remuneration_type: 'fixed_center',
      rate_percentage: 70,
      salary_per_lesson: 25,
      fixed_student_rate: 15,
      center_fixed_per_student: 20,
      payout_status: 'paid',
      payout_date: todayStr,
      payout_month: currentMonthKey,
      payout_amount: 120,
      payout_by_user_id: 1,
      payout_by_user_name: 'Directeur',
    },
    {
      id: 3,
      user_id: null,
      name: 'M. Karim Trabelsi',
      subject: 'SVT',
      phone: '+216 55 987 654',
      email: 'karim@studycenter.com',
      weekly_lessons: 4,
      remuneration_type: 'fixed',
      rate_percentage: 70,
      salary_per_lesson: 30,
      fixed_student_rate: 18,
      center_fixed_per_student: 20,
      payout_status: 'pending',
      payout_date: null,
      payout_month: null,
      payout_amount: null,
      payout_by_user_id: null,
      payout_by_user_name: null,
    },
  ];

  groups: GroupRecord[] = [
    {
      id: 1,
      name: 'Bac Math A',
      level: '4ème Année Bac Mathématiques',
      subject: 'Mathématiques',
      subject_id: 1,
      teacher_id: 1,
      user_id: 3,
      max_capacity: 15,
      notes: 'Salle 1 — Mardi & Samedi',
      student_ids: [1, 2, 5],
    },
    {
      id: 2,
      name: 'Bac Sc Exp B',
      level: '4ème Année Bac Sciences Expérimentales',
      subject: 'Sciences Physiques',
      subject_id: 2,
      teacher_id: 1,
      user_id: 3,
      max_capacity: 18,
      notes: 'Salle 2 — Mercredi & Dimanche',
      student_ids: [1, 3],
    },
    {
      id: 3,
      name: '9ème Année A',
      level: '9ème Année de Base',
      subject: 'Français',
      subject_id: 4,
      teacher_id: 2,
      user_id: 1,
      max_capacity: 20,
      notes: 'Salle 3 — Lundi & Jeudi',
      student_ids: [4, 5],
    },
  ];

  students: StudentRecord[] = [
    {
      id: 1,
      name: 'Yassine Mansouri',
      level: '4ème Année Bac Mathématiques',
      group_name: 'Bac Math A',
      phone: '55 112 233',
      parent_phone: '98 112 233',
      status: 'active',
      is_remise: false,
      remise: 0,
      discount_type: 'fixed',
      base_fee: 0,
      created_at: `${prevMonthKey}-05T09:00:00Z`,
    },
    {
      id: 2,
      name: 'Nour El Houda Gharbi',
      level: '4ème Année Bac Mathématiques',
      group_name: 'Bac Math A',
      phone: '24 556 778',
      parent_phone: '20 556 778',
      status: 'active',
      is_remise: true,
      remise: 20,
      discount_type: 'percentage',
      base_fee: 0,
      created_at: `${prevMonthKey}-08T10:30:00Z`,
    },
    {
      id: 3,
      name: 'Mehdi Ayari',
      level: '4ème Année Bac Sciences Expérimentales',
      group_name: 'Bac Sc Exp B',
      phone: '52 889 441',
      parent_phone: '99 889 441',
      status: 'active',
      is_remise: false,
      remise: 0,
      discount_type: 'fixed',
      base_fee: 0,
      created_at: `${prevMonthKey}-10T11:00:00Z`,
    },
    {
      id: 4,
      name: 'Sarra Jaziri',
      level: '9ème Année de Base',
      group_name: '9ème Année A',
      phone: '29 334 551',
      parent_phone: '22 334 551',
      status: 'active',
      is_remise: false,
      remise: 0,
      discount_type: 'fixed',
      base_fee: 0,
      created_at: `${prevMonthKey}-12T14:00:00Z`,
    },
    {
      id: 5,
      name: 'Aziz Khelifi',
      level: '9ème Année de Base',
      group_name: '9ème Année A',
      phone: '50 778 990',
      parent_phone: '97 778 990',
      status: 'active',
      is_remise: false,
      remise: 0,
      discount_type: 'fixed',
      base_fee: 0,
      created_at: `${currentMonthKey}-02T15:00:00Z`,
    },
  ];

  enrollments: EnrollmentRecord[] = [
    {
      id: 1,
      student_id: 1,
      subject_name: 'Mathématiques',
      teacher_id: 1,
      group_name: 'Bac Math A',
      sessions_per_month: 4,
      price_per_month: 120,
      price_per_session: 30,
    },
    {
      id: 2,
      student_id: 1,
      subject_name: 'Sciences Physiques',
      teacher_id: 1,
      group_name: 'Bac Sc Exp B',
      sessions_per_month: 4,
      price_per_month: 100,
      price_per_session: 25,
    },
    {
      id: 3,
      student_id: 2,
      subject_name: 'Mathématiques',
      teacher_id: 1,
      group_name: 'Bac Math A',
      sessions_per_month: 4,
      price_per_month: 120,
      price_per_session: 30,
    },
    {
      id: 4,
      student_id: 3,
      subject_name: 'Sciences Physiques',
      teacher_id: 1,
      group_name: 'Bac Sc Exp B',
      sessions_per_month: 4,
      price_per_month: 100,
      price_per_session: 25,
    },
    {
      id: 5,
      student_id: 3,
      subject_name: 'SVT',
      teacher_id: 3,
      group_name: 'Bac Sc Exp B',
      sessions_per_month: 4,
      price_per_month: 100,
      price_per_session: 25,
    },
    {
      id: 6,
      student_id: 4,
      subject_name: 'Français',
      teacher_id: 2,
      group_name: '9ème Année A',
      sessions_per_month: 4,
      price_per_month: 80,
      price_per_session: 20,
    },
    {
      id: 7,
      student_id: 5,
      subject_name: 'Français',
      teacher_id: 2,
      group_name: '9ème Année A',
      sessions_per_month: 4,
      price_per_month: 80,
      price_per_session: 20,
    },
    {
      id: 8,
      student_id: 5,
      subject_name: 'Mathématiques',
      teacher_id: 1,
      group_name: 'Bac Math A',
      sessions_per_month: 4,
      price_per_month: 100,
      price_per_session: 25,
    },
  ];

  attendances: AttendanceRecord[] = [
    // Yassine (id 1) with Teacher 1: 6 sessions attended (4 paid -> 2 unpaid)
    { id: 1, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-21), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 2, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-17), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 3, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-14), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 4, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-10), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 5, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-7), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 6, student_id: 1, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-3), status: 'late', notes: 'Retard 10 min', created_at: new Date().toISOString() },

    // Nour (id 2) with Teacher 1: 5 sessions attended (0 paid -> 5 unpaid = pending)
    { id: 7, student_id: 2, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-21), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 8, student_id: 2, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-17), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 9, student_id: 2, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-14), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 10, student_id: 2, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-10), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 11, student_id: 2, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: isoDateOffset(-7), status: 'present', notes: null, created_at: new Date().toISOString() },

    // Mehdi (id 3) with Teacher 1: 8 sessions attended (0 paid -> 8 unpaid = overdue)
    { id: 12, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-25), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 13, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-21), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 14, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-18), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 15, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-14), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 16, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-11), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 17, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-7), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 18, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-4), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 19, student_id: 3, teacher_id: 1, group_name: 'Bac Sc Exp B', subject_name: 'Sciences Physiques', session_date: isoDateOffset(-1), status: 'present', notes: null, created_at: new Date().toISOString() },

    // Sarra (id 4) with Teacher 2: 4 sessions attended this cycle (4 paid -> 0 unpaid)
    { id: 20, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: isoDateOffset(-15), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 21, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: isoDateOffset(-11), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 22, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: isoDateOffset(-8), status: 'present', notes: null, created_at: new Date().toISOString() },
    { id: 23, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: isoDateOffset(-4), status: 'present', notes: null, created_at: new Date().toISOString() },

    // Historical sessions matched to historical paid student payments (keeps unpaid balances unchanged)
    { id: 24, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fiveMonthsAgoKey}-04`, status: 'present', notes: null, created_at: `${fiveMonthsAgoKey}-04T17:00:00Z` },
    { id: 25, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fiveMonthsAgoKey}-11`, status: 'present', notes: null, created_at: `${fiveMonthsAgoKey}-11T17:00:00Z` },
    { id: 26, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fiveMonthsAgoKey}-18`, status: 'present', notes: null, created_at: `${fiveMonthsAgoKey}-18T17:00:00Z` },
    { id: 27, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fiveMonthsAgoKey}-25`, status: 'present', notes: null, created_at: `${fiveMonthsAgoKey}-25T17:00:00Z` },

    { id: 28, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${twoMonthsAgoKey}-05`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-05T17:00:00Z` },
    { id: 29, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${twoMonthsAgoKey}-12`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-12T17:00:00Z` },
    { id: 30, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${twoMonthsAgoKey}-19`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-19T17:00:00Z` },
    { id: 31, student_id: 1, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${twoMonthsAgoKey}-26`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-26T17:00:00Z` },

    { id: 32, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${fourMonthsAgoKey}-03`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-03T16:00:00Z` },
    { id: 33, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${fourMonthsAgoKey}-10`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-10T16:00:00Z` },
    { id: 34, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${fourMonthsAgoKey}-17`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-17T16:00:00Z` },
    { id: 35, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${fourMonthsAgoKey}-24`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-24T16:00:00Z` },

    { id: 36, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${threeMonthsAgoKey}-04`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-04T16:00:00Z` },
    { id: 37, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${threeMonthsAgoKey}-11`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-11T16:00:00Z` },
    { id: 38, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${threeMonthsAgoKey}-18`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-18T16:00:00Z` },
    { id: 39, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${threeMonthsAgoKey}-25`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-25T16:00:00Z` },

    { id: 40, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${twoMonthsAgoKey}-03`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-03T16:00:00Z` },
    { id: 41, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${twoMonthsAgoKey}-10`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-10T16:00:00Z` },
    { id: 42, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${twoMonthsAgoKey}-17`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-17T16:00:00Z` },
    { id: 43, student_id: 4, teacher_id: 2, group_name: '9ème Année A', subject_name: 'Français', session_date: `${twoMonthsAgoKey}-24`, status: 'present', notes: null, created_at: `${twoMonthsAgoKey}-24T16:00:00Z` },

    { id: 44, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fourMonthsAgoKey}-05`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-05T17:00:00Z` },
    { id: 45, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fourMonthsAgoKey}-12`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-12T17:00:00Z` },
    { id: 46, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fourMonthsAgoKey}-19`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-19T17:00:00Z` },
    { id: 47, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${fourMonthsAgoKey}-26`, status: 'present', notes: null, created_at: `${fourMonthsAgoKey}-26T17:00:00Z` },

    { id: 48, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${threeMonthsAgoKey}-06`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-06T17:00:00Z` },
    { id: 49, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${threeMonthsAgoKey}-13`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-13T17:00:00Z` },
    { id: 50, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${threeMonthsAgoKey}-20`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-20T17:00:00Z` },
    { id: 51, student_id: 5, teacher_id: 1, group_name: 'Bac Math A', subject_name: 'Mathématiques', session_date: `${threeMonthsAgoKey}-27`, status: 'present', notes: null, created_at: `${threeMonthsAgoKey}-27T17:00:00Z` },
  ];

  daycareChildren: DaycareChildRecord[] = [
    {
      id: 1,
      name: 'Adam Bouazizi',
      age: 5,
      parent_name: 'Sonia Bouazizi',
      phone: '21 445 889',
      schedule: '08:00 - 17:00 (Journée complète)',
      status: 'present',
      monthly_fee: 180,
      start_date: `${twoMonthsAgoKey}-01`,
      created_at: `${twoMonthsAgoKey}-01T08:00:00Z`,
    },
    {
      id: 2,
      name: 'Lina Hammami',
      age: 4,
      parent_name: 'Tarek Hammami',
      phone: '50 332 110',
      schedule: '08:30 - 16:30 (Après-midi inclus)',
      status: 'present',
      monthly_fee: 180,
      start_date: `${currentMonthKey}-01`,
      created_at: `${currentMonthKey}-01T08:30:00Z`,
    },
  ];

  payments: PaymentRecord[] = [
    {
      id: 1,
      student_id: 1,
      teacher_id: 1,
      daycare_child_id: null,
      student_name: 'Yassine Mansouri',
      month: frenchMonthLabel(`${prevMonthKey}-15`),
      amount: 110,
      date: `${prevMonthKey}-15`,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 1,
      created_at: `${prevMonthKey}-15T10:00:00Z`,
    },
    {
      id: 2,
      student_id: 4,
      teacher_id: 2,
      daycare_child_id: null,
      student_name: 'Sarra Jaziri',
      month: frenchMonthLabel(todayStr),
      amount: 80,
      date: todayStr,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 2,
      created_at: new Date().toISOString(),
    },
    {
      id: 3,
      student_id: null,
      teacher_id: null,
      daycare_child_id: 1,
      student_name: 'Adam Bouazizi',
      month: frenchMonthLabel(`${prevMonthKey}-05`),
      amount: 180,
      date: `${prevMonthKey}-05`,
      status: 'paid',
      payment_method: 'virement',
      sessions_paid: 0,
      months_paid: 1,
      created_by: 1,
      created_at: `${prevMonthKey}-05T09:15:00Z`,
    },
    {
      id: 4,
      student_id: null,
      teacher_id: null,
      daycare_child_id: 2,
      student_name: 'Lina Hammami',
      month: frenchMonthLabel(todayStr),
      amount: 180,
      date: todayStr,
      status: 'paid',
      payment_method: 'cheque',
      sessions_paid: 0,
      months_paid: 1,
      created_by: 2,
      created_at: new Date().toISOString(),
    },
    {
      id: 5,
      student_id: 5,
      teacher_id: 2,
      daycare_child_id: null,
      student_name: 'Aziz Khelifi',
      month: frenchMonthLabel(`${currentMonthKey}-04`),
      amount: 80,
      date: `${currentMonthKey}-04`,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 1,
      created_at: `${currentMonthKey}-04T11:30:00Z`,
    },
    {
      id: 6,
      student_id: 1,
      teacher_id: 1,
      daycare_child_id: null,
      student_name: 'Yassine Mansouri',
      month: frenchMonthLabel(`${fiveMonthsAgoKey}-18`),
      amount: 120,
      date: `${fiveMonthsAgoKey}-18`,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 1,
      created_at: `${fiveMonthsAgoKey}-18T10:15:00Z`,
    },
    {
      id: 7,
      student_id: 4,
      teacher_id: 2,
      daycare_child_id: null,
      student_name: 'Sarra Jaziri',
      month: frenchMonthLabel(`${fourMonthsAgoKey}-12`),
      amount: 80,
      date: `${fourMonthsAgoKey}-12`,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 2,
      created_at: `${fourMonthsAgoKey}-12T09:40:00Z`,
    },
    {
      id: 8,
      student_id: 5,
      teacher_id: 1,
      daycare_child_id: null,
      student_name: 'Aziz Khelifi',
      month: frenchMonthLabel(`${fourMonthsAgoKey}-16`),
      amount: 100,
      date: `${fourMonthsAgoKey}-16`,
      status: 'paid',
      payment_method: 'virement',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 1,
      created_at: `${fourMonthsAgoKey}-16T14:20:00Z`,
    },
    {
      id: 9,
      student_id: 4,
      teacher_id: 2,
      daycare_child_id: null,
      student_name: 'Sarra Jaziri',
      month: frenchMonthLabel(`${threeMonthsAgoKey}-14`),
      amount: 80,
      date: `${threeMonthsAgoKey}-14`,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 2,
      created_at: `${threeMonthsAgoKey}-14T11:00:00Z`,
    },
    {
      id: 10,
      student_id: 5,
      teacher_id: 1,
      daycare_child_id: null,
      student_name: 'Aziz Khelifi',
      month: frenchMonthLabel(`${threeMonthsAgoKey}-20`),
      amount: 100,
      date: `${threeMonthsAgoKey}-20`,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 1,
      created_at: `${threeMonthsAgoKey}-20T15:10:00Z`,
    },
    {
      id: 11,
      student_id: 1,
      teacher_id: 1,
      daycare_child_id: null,
      student_name: 'Yassine Mansouri',
      month: frenchMonthLabel(`${twoMonthsAgoKey}-14`),
      amount: 120,
      date: `${twoMonthsAgoKey}-14`,
      status: 'paid',
      payment_method: 'especes',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 1,
      created_at: `${twoMonthsAgoKey}-14T10:30:00Z`,
    },
    {
      id: 12,
      student_id: 4,
      teacher_id: 2,
      daycare_child_id: null,
      student_name: 'Sarra Jaziri',
      month: frenchMonthLabel(`${twoMonthsAgoKey}-18`),
      amount: 80,
      date: `${twoMonthsAgoKey}-18`,
      status: 'paid',
      payment_method: 'cheque',
      sessions_paid: 4,
      months_paid: 0,
      created_by: 2,
      created_at: `${twoMonthsAgoKey}-18T16:00:00Z`,
    },
  ];

  paymentAuditLogs: PaymentAuditLogRecord[] = [
    {
      id: 1,
      payment_id: 2,
      user_id: 2,
      user_name: 'Secrétaire',
      user_role: 'secretary',
      action: 'created',
      amount: 80,
      student_name: 'Sarra Jaziri',
      payment_method: 'especes',
      details: { month: frenchMonthLabel(todayStr), sessions: 4, status: 'paid' },
      ip_address: '127.0.0.1',
      created_at: new Date().toISOString(),
    },
    {
      id: 2,
      payment_id: 4,
      user_id: 2,
      user_name: 'Secrétaire',
      user_role: 'secretary',
      action: 'created',
      amount: 180,
      student_name: 'Lina Hammami',
      payment_method: 'cheque',
      details: { month: frenchMonthLabel(todayStr), months: 1, status: 'paid' },
      ip_address: '127.0.0.1',
      created_at: new Date().toISOString(),
    },
  ];

  expenses: ExpenseRecord[] = [
    {
      id: 1,
      title: 'Loyer du Centre',
      category: 'loyer',
      amount: 850,
      date: `${currentMonthKey}-01`,
      payment_method: 'virement',
      is_recurring: true,
      recurring_frequency: 'monthly',
      receipt_reference: 'LOY-2026',
      notes: 'Loyer mensuel local principal',
      created_by: 1,
      created_at: `${currentMonthKey}-01T08:00:00Z`,
    },
    {
      id: 2,
      title: 'Facture STEG (Électricité & Clim)',
      category: 'électricité',
      amount: 145,
      date: `${currentMonthKey}-05`,
      payment_method: 'especes',
      is_recurring: false,
      recurring_frequency: 'monthly',
      receipt_reference: 'STEG-4491',
      notes: null,
      created_by: 1,
      created_at: `${currentMonthKey}-05T10:00:00Z`,
    },
    {
      id: 3,
      title: 'Fournitures, Feutres & Polycopiés',
      category: 'fournitures',
      amount: 65,
      date: todayStr,
      payment_method: 'especes',
      is_recurring: false,
      recurring_frequency: 'monthly',
      receipt_reference: 'PAP-112',
      notes: 'Ramettes papier A4 et marqueurs tableau',
      created_by: 1,
      created_at: new Date().toISOString(),
    },
    {
      id: 4,
      title: 'Internet Fibre Optique',
      category: 'internet_tel',
      amount: 60,
      date: `${prevMonthKey}-10`,
      payment_method: 'virement',
      is_recurring: true,
      recurring_frequency: 'monthly',
      receipt_reference: 'TT-8841',
      notes: null,
      created_by: 1,
      created_at: `${prevMonthKey}-10T09:00:00Z`,
    },
  ];

  monthlyClosings: MonthlyClosingRecord[] = [
    {
      id: 1,
      month: prevMonthKey,
      school_year: '2026-2027',
      total_revenue: 290,
      total_expenses: 60,
      total_teacher_payouts: 84,
      net_profit: 146,
      closed_at: `${currentMonthKey}-01T18:00:00Z`,
      closed_by: 1,
      notes: 'Clôture validée par la direction.',
      is_closed: true,
    },
  ];

  schedules: ScheduleItemRecord[] = [
    {
      id: 1,
      day: 'Lundi',
      time: '16:00 - 18:00',
      subject: 'Français',
      teacher: 'Mme. Amira Ben Salem',
      level: '9ème Année de Base',
      room: 'Salle 3',
      group_name: '9ème Année A',
    },
    {
      id: 2,
      day: 'Mardi',
      time: '17:00 - 19:00',
      subject: 'Mathématiques',
      teacher: 'Professeur Mohamed',
      level: '4ème Année Bac Mathématiques',
      room: 'Salle 1',
      group_name: 'Bac Math A',
    },
    {
      id: 3,
      day: 'Mercredi',
      time: '15:00 - 17:00',
      subject: 'Sciences Physiques',
      teacher: 'Professeur Mohamed',
      level: '4ème Année Bac Sciences Expérimentales',
      room: 'Salle 2',
      group_name: 'Bac Sc Exp B',
    },
    {
      id: 4,
      day: 'Jeudi',
      time: '16:00 - 18:00',
      subject: 'Français',
      teacher: 'Mme. Amira Ben Salem',
      level: '9ème Année de Base',
      room: 'Salle 3',
      group_name: '9ème Année A',
    },
    {
      id: 5,
      day: 'Samedi',
      time: '09:00 - 11:00',
      subject: 'Mathématiques',
      teacher: 'Professeur Mohamed',
      level: '4ème Année Bac Mathématiques',
      room: 'Salle 1',
      group_name: 'Bac Math A',
    },
    {
      id: 6,
      day: 'Dimanche',
      time: '10:00 - 12:00',
      subject: 'SVT',
      teacher: 'M. Karim Trabelsi',
      level: '4ème Année Bac Sciences Expérimentales',
      room: 'Salle 2',
      group_name: 'Bac Sc Exp B',
    },
  ];

  schools: SchoolRecord[] = [
    { id: 1, name: 'École Primaire Habib Bourguiba', address: 'Avenue Habib Bourguiba', city: 'Tunis' },
    { id: 2, name: 'École Primaire El Menzah 5', address: 'Rue de la Liberté', city: 'Ariana' },
  ];

  schoolClasses: SchoolClassRecord[] = [
    { id: 1, school_id: 1, level: 5, section: 'A' },
    { id: 2, school_id: 2, level: 6, section: 'B' },
  ];

  schoolCalendarImports: SchoolCalendarImportRecord[] = [
    {
      id: 1,
      school_class_id: 1,
      file_name: 'Emploi_5eme_A_Bourguiba.png',
      file_type: 'image/png',
      buffer: Buffer.from(SAMPLE_PNG_BASE64, 'base64'),
      created_at: new Date().toISOString(),
    },
  ];

  activityLogs: ActivityLogRecord[] = [
    {
      id: 1,
      type: 'payment',
      action: 'created',
      message: 'Paiement de 80 TND pour Sarra Jaziri (reçu)',
      user_id: 2,
      user_name: 'Secrétaire',
      user_role: 'secretary',
      created_at: new Date().toISOString(),
      timestamp: Math.floor(Date.now() / 1000) - 300,
    },
    {
      id: 2,
      type: 'attendance',
      action: 'recorded',
      message: 'Appel de présence validé (Bac Sc Exp B, Sciences Physiques : 2 élèves pointés)',
      user_id: 3,
      user_name: 'Professeur Mohamed',
      user_role: 'teacher',
      created_at: new Date().toISOString(),
      timestamp: Math.floor(Date.now() / 1000) - 1800,
    },
    {
      id: 3,
      type: 'student',
      action: 'created',
      message: 'Nouvel élève inscrit : Aziz Khelifi (9ème Année de Base)',
      user_id: 1,
      user_name: 'Directeur',
      user_role: 'director',
      created_at: new Date().toISOString(),
      timestamp: Math.floor(Date.now() / 1000) - 3600,
    },
  ];
}

export const store = new Store();

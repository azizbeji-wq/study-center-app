import {
  DEFAULT_DAYCARE_MONTHLY_FEE,
  DEFAULT_PERSONNEL_PERMISSIONS,
  DEFAULT_SESSION_PRICE,
  DEFAULT_TEACHER_PERMISSIONS,
  EnrollmentRecord,
  SESSIONS_PER_MONTH,
  store,
  StudentRecord,
  TeacherRecord,
  UserRecord,
} from './store';

export function round2(n: number): number {
  return Math.round((n + Number.EPSILON) * 100) / 100;
}

export function slugify(text: string): string {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export function getEffectivePermissions(user: UserRecord): string[] {
  if (user.role === 'director') {
    return [
      'students',
      'attendance',
      'daycare',
      'teachers',
      'schedule',
      'schoolcalendar',
      'payments',
      'reports',
      'can_delete',
      'teacher_portal',
    ];
  }
  if (user.role === 'teacher') {
    return Array.isArray(user.permissions) ? user.permissions : [...DEFAULT_TEACHER_PERMISSIONS];
  }
  return Array.isArray(user.permissions) ? user.permissions : [...DEFAULT_PERSONNEL_PERMISSIONS];
}

export function hasPermission(user: UserRecord | undefined, perm: string): boolean {
  if (!user) return false;
  if (user.role === 'director') return true;
  return getEffectivePermissions(user).includes(perm);
}

export function resolveTeacherForUser(user: UserRecord | undefined, explicitTeacherId?: number | null): TeacherRecord | null {
  if (!user) return null;
  if (explicitTeacherId && ['director', 'secretary'].includes(user.role)) {
    const found = store.teachers.find((t) => t.id === explicitTeacherId);
    if (found) return found;
  }

  let teacher = store.teachers.find((t) => t.user_id === user.id);
  if (teacher) return teacher;

  if (user.email) {
    teacher = store.teachers.find((t) => t.email.toLowerCase() === user.email.toLowerCase());
    if (teacher) {
      if (!teacher.user_id) teacher.user_id = user.id;
      return teacher;
    }
  }

  if (user.name) {
    const lower = user.name.toLowerCase();
    teacher = store.teachers.find((t) => t.name.toLowerCase().includes(lower));
    if (teacher) {
      if (!teacher.user_id) teacher.user_id = user.id;
      return teacher;
    }
  }

  if (['director', 'secretary'].includes(user.role)) {
    const created: TeacherRecord = {
      id: store.nextId('teacher'),
      user_id: user.id,
      name: user.name,
      email: user.email || `staff_${user.id}@studycenter.tn`,
      phone: user.phone || '20 000 000',
      subject: user.role === 'director' ? 'Direction & Enseignement' : 'Personnel & Enseignement',
      weekly_lessons: 4,
      salary_per_lesson: 25,
      fixed_student_rate: 15,
      center_fixed_per_student: 20,
      remuneration_type: 'percentage',
      rate_percentage: 70,
      payout_status: 'pending',
      payout_date: null,
      payout_month: null,
      payout_amount: null,
      payout_by_user_id: null,
      payout_by_user_name: null,
    };
    store.teachers.push(created);
    return created;
  }

  return null;
}

export function splitSubjects(subject: string | null | undefined): string[] {
  const seen = new Set<string>();
  const out: string[] = [];
  for (const part of String(subject || '').split(/[,;/،]+/)) {
    const trimmed = part.trim();
    if (trimmed && !seen.has(trimmed)) {
      seen.add(trimmed);
      out.push(trimmed);
    }
  }
  return out;
}

export function recordActivity(
  user: UserRecord | undefined,
  type: string,
  action: string,
  message: string,
  metadata?: any
): void {
  const entry = {
    id: store.nextId('activityLog'),
    type,
    action,
    message,
    user_id: user?.id ?? null,
    user_name: user?.name ?? 'Directeur',
    user_role: user?.role ?? 'director',
    metadata,
    created_at: new Date().toISOString(),
    timestamp: Math.floor(Date.now() / 1000),
  };
  store.activityLogs.unshift(entry);
  if (store.activityLogs.length > 100) {
    store.activityLogs.length = 100;
  }
}

export function enrollmentSessionPrice(enr: EnrollmentRecord): [number, 'session' | 'monthly' | 'default'] {
  if (enr.price_per_session > 0) return [enr.price_per_session, 'session'];
  if (enr.price_per_month > 0) return [round2(enr.price_per_month / (enr.sessions_per_month || SESSIONS_PER_MONTH)), 'monthly'];
  return [DEFAULT_SESSION_PRICE, 'default'];
}

export function averageSessionPrice(enrollments: EnrollmentRecord[]): [number, 'session' | 'monthly' | 'default'] {
  if (!enrollments.length) return [DEFAULT_SESSION_PRICE, 'default'];
  let sum = 0;
  let source: 'session' | 'monthly' | 'default' = 'default';
  for (const e of enrollments) {
    const [p, s] = enrollmentSessionPrice(e);
    sum += p;
    if (s !== 'default') source = s;
  }
  return [round2(sum / enrollments.length), source];
}

export function formatTeacher(t: TeacherRecord, currentUser?: UserRecord): Record<string, any> {
  const teacherEnrollments = store.enrollments.filter((e) => e.teacher_id === t.id);
  const studentIds = new Set<number>();
  for (const e of teacherEnrollments) studentIds.add(e.student_id);
  for (const a of store.attendances) {
    if (a.teacher_id === t.id) studentIds.add(a.student_id);
  }
  const studentCount = studentIds.size;

  let monthlyGross = teacherEnrollments.reduce((acc, e) => acc + (Number(e.price_per_month) || 0), 0);
  if (monthlyGross === 0 && studentCount > 0) {
    monthlyGross = studentCount * ((t.salary_per_lesson || 35) * SESSIONS_PER_MONTH);
  }
  monthlyGross = round2(monthlyGross);

  const remType = t.remuneration_type || 'percentage';
  const ratePct = t.rate_percentage ?? 70;
  const salaryPerLesson = t.salary_per_lesson ?? 25;
  const fixedStudentRate = t.fixed_student_rate ?? 15;
  const centerFixedPerStudent = t.center_fixed_per_student ?? 20;
  const weeklyLessons = t.weekly_lessons || 4;
  const monthlyLessons = Math.max(1, weeklyLessons * SESSIONS_PER_MONTH);

  let teacherShare = 0;
  let centerShare = 0;
  let perSessionEst = 0;

  if (remType === 'percentage') {
    teacherShare = round2((monthlyGross * ratePct) / 100);
    centerShare = round2(Math.max(0, monthlyGross - teacherShare));
    perSessionEst = round2(teacherShare / monthlyLessons);
  } else if (remType === 'fixed_center') {
    const totalCenterTarget = round2(studentCount * centerFixedPerStudent);
    teacherShare = round2(Math.max(0, monthlyGross - totalCenterTarget));
    centerShare = round2(Math.min(monthlyGross, totalCenterTarget));
    perSessionEst = round2(teacherShare / monthlyLessons);
  } else {
    teacherShare = round2(studentCount * fixedStudentRate);
    centerShare = round2(Math.max(0, monthlyGross - teacherShare));
    perSessionEst = round2(teacherShare / monthlyLessons);
  }

  const linkedUser = t.user_id ? store.users.find((u) => u.id === t.user_id) : undefined;

  const row: Record<string, any> = {
    id: t.id,
    user_id: t.user_id,
    user_role: linkedUser?.role ?? null,
    name: t.name,
    subject: t.subject,
    subjects: splitSubjects(t.subject),
    phone: t.phone,
    email: t.email,
    studentCount,
    lesson_rate: salaryPerLesson,
    weeklyLessons,
    monthlyLessons,
    remuneration_type: remType,
    rate_percentage: ratePct,
    salary_per_lesson: salaryPerLesson,
    fixed_student_rate: fixedStudentRate,
    center_fixed_per_student: centerFixedPerStudent,
    monthly_gross_revenue: monthlyGross,
    teacher_share: teacherShare,
    center_share: centerShare,
    per_session_share: perSessionEst,
    payout_status: t.payout_status || 'pending',
    payout_date: t.payout_date,
    payout_month: t.payout_month,
    payout_amount: t.payout_amount ?? teacherShare,
    payout_by_user_name: t.payout_by_user_name || (t.payout_status === 'paid' ? 'Directeur' : null),
  };

  if (currentUser && currentUser.role !== 'director') {
    const isOwnProfile =
      currentUser.id === t.user_id ||
      currentUser.email.toLowerCase() === t.email.toLowerCase();
    if (!isOwnProfile) {
      delete row.remuneration_type;
      delete row.rate_percentage;
      delete row.salary_per_lesson;
      delete row.fixed_student_rate;
      delete row.center_fixed_per_student;
      delete row.monthly_gross_revenue;
      delete row.teacher_share;
      delete row.center_share;
      delete row.per_session_share;
      delete row.payout_status;
      delete row.payout_date;
      delete row.payout_month;
      delete row.payout_amount;
      delete row.payout_by_user_name;
    }
  }

  return row;
}

export function formatStudent(s: StudentRecord) {
  const enrollments = store.enrollments.filter((e) => e.student_id === s.id);
  const attendances = store.attendances.filter((a) => a.student_id === s.id);

  const totalSessions = attendances.length;
  const presentCount = attendances.filter((a) => a.status === 'present').length;
  const absentCount = attendances.filter((a) => a.status === 'absent').length;
  const excusedCount = attendances.filter((a) => a.status === 'excused').length;

  let monthlySubjectTotal = 0;
  for (const enr of enrollments) {
    const teacher = enr.teacher_id ? store.teachers.find((t) => t.id === enr.teacher_id) : null;
    const monthPrice = enr.price_per_month > 0 ? enr.price_per_month : (teacher?.salary_per_lesson || 35) * 4;
    monthlySubjectTotal += monthPrice;
  }

  let sessionSubjectTotal = 0;
  let usedDefaultPrice = false;
  for (const enr of enrollments) {
    const [sessionPrice, source] = enrollmentSessionPrice(enr);
    const attendedCount = attendances.filter(
      (a) => a.subject_name === enr.subject_name && a.status === 'present'
    ).length;
    if (attendedCount > 0) {
      sessionSubjectTotal += attendedCount * sessionPrice;
      if (source === 'default') usedDefaultPrice = true;
    }
  }

  let grossFee = s.base_fee > 0 ? s.base_fee : monthlySubjectTotal > 0 ? monthlySubjectTotal : sessionSubjectTotal;
  if (grossFee <= 0 && monthlySubjectTotal > 0) grossFee = monthlySubjectTotal;

  let discountVal = 0;
  if (s.is_remise && s.remise > 0) {
    discountVal = s.discount_type === 'percentage' ? (grossFee * s.remise) / 100 : s.remise;
  }
  const netPayable = Math.max(0, grossFee - discountVal);
  const isDefaultPrice = usedDefaultPrice && s.base_fee <= 0 && monthlySubjectTotal <= 0;

  return {
    id: s.id,
    name: s.name,
    level: s.level,
    group_name: s.group_name,
    phone: s.phone,
    parent_phone: s.parent_phone,
    status: s.status,
    is_remise: Boolean(s.is_remise),
    remise: Number(s.remise || 0),
    discount_type: s.discount_type || 'fixed',
    base_fee: Number(s.base_fee || 0),
    calculated_fee: {
      gross: round2(grossFee),
      discount: round2(discountVal),
      netPayable: round2(netPayable),
      center_absorbed_discount: round2(discountVal),
      isDefaultPrice,
    },
    attendance_stats: {
      total: totalSessions,
      present: presentCount,
      absent: absentCount,
      excused: excusedCount,
    },
    subjects: enrollments.map((e) => {
      const teacher = e.teacher_id ? store.teachers.find((t) => t.id === e.teacher_id) : null;
      const sessions = e.sessions_per_month || 4;
      const sessionRate = e.price_per_session > 0 ? e.price_per_session : teacher?.salary_per_lesson || 35;
      const monthRate = e.price_per_month > 0 ? e.price_per_month : sessionRate * sessions;
      return {
        id: e.id,
        subject: e.subject_name,
        teacher: teacher ? teacher.name : 'N/A',
        teacher_id: e.teacher_id,
        group_name: e.group_name,
        sessions_per_month: sessions,
        price_per_month: monthRate,
        price_per_session: sessionRate,
      };
    }),
  };
}

export function studentDueStatus(unpaid: number): 'overdue' | 'pending' | 'ok' {
  if (unpaid > 7) return 'overdue';
  if (unpaid >= 4) return 'pending';
  return 'ok';
}

export function daycareDueStatus(unpaid: number): 'overdue' | 'pending' | 'ok' {
  if (unpaid > 2) return 'overdue';
  if (unpaid >= 1) return 'pending';
  return 'ok';
}

export function monthLabelFromDate(dateStr?: string | null): string {
  const months = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
  ];
  const d = dateStr ? new Date(dateStr) : new Date();
  const safe = isNaN(d.getTime()) ? new Date() : d;
  return `${months[safe.getMonth()]} ${safe.getFullYear()}`;
}

export function computeDues() {
  const teacherNames = new Map<number, string>(store.teachers.map((t) => [t.id, t.name]));

  // Paid sessions per student-teacher
  const paidSessions = new Map<string, number>();
  for (const p of store.payments) {
    if (p.student_id && p.teacher_id) {
      const k = `${p.student_id}-${p.teacher_id}`;
      paidSessions.set(k, (paidSessions.get(k) || 0) + (p.sessions_paid || 0));
    }
  }

  const studentRows: any[] = [];
  for (const student of store.students.filter((s) => s.status === 'active')) {
    const stuEnrollments = store.enrollments.filter((e) => e.student_id === student.id);
    const stuAttendances = store.attendances.filter(
      (a) => a.student_id === student.id && (a.status === 'present' || a.status === 'late')
    );

    let discountFactor = 1;
    if (student.is_remise && student.remise > 0) {
      if (student.discount_type === 'percentage') {
        discountFactor = Math.max(0, 1 - student.remise / 100);
      } else {
        let grossMonthly = stuEnrollments.reduce((acc, e) => acc + (e.price_per_month || 0), 0);
        if (grossMonthly <= 0) {
          grossMonthly = Math.max(1, stuEnrollments.length * 15 * 4);
        }
        discountFactor = Math.max(0, 1 - Math.min(grossMonthly, student.remise) / grossMonthly);
      }
    }

    const byTeacher = new Map<number, EnrollmentRecord[]>();
    for (const e of stuEnrollments) {
      if (!e.teacher_id) continue;
      if (!byTeacher.has(e.teacher_id)) byTeacher.set(e.teacher_id, []);
      byTeacher.get(e.teacher_id)!.push(e);
    }

    for (const [teacherId, enrs] of byTeacher.entries()) {
      const subjects = Array.from(new Set(enrs.map((e) => e.subject_name)));
      const attended = stuAttendances.filter((a) =>
        a.teacher_id ? a.teacher_id === teacherId : subjects.includes(a.subject_name)
      ).length;

      const paid = paidSessions.get(`${student.id}-${teacherId}`) || 0;
      const unpaid = Math.max(0, attended - paid);
      if (unpaid === 0) continue;

      const [price, priceSource] = averageSessionPrice(enrs);
      const unit = round2(price * discountFactor);

      studentRows.push({
        key: `s${student.id}-t${teacherId}`,
        studentId: student.id,
        studentName: student.name,
        groupName: student.group_name,
        parentPhone: student.parent_phone,
        phone: student.phone,
        teacherId,
        teacherName: teacherNames.get(teacherId) ?? 'N/A',
        subjects,
        sessionsAttended: attended,
        sessionsPaid: paid,
        sessionsUnpaid: unpaid,
        pricePerSession: price,
        priceSource,
        unitAmount: unit,
        amountDue: round2(unpaid * unit),
        status: studentDueStatus(unpaid),
      });
    }
  }

  // Daycare dues
  const paidMonths = new Map<number, number>();
  for (const p of store.payments) {
    if (p.daycare_child_id) {
      paidMonths.set(p.daycare_child_id, (paidMonths.get(p.daycare_child_id) || 0) + (p.months_paid || 0));
    }
  }

  const now = new Date();
  const daycareRows: any[] = [];
  for (const child of store.daycareChildren) {
    const start = child.start_date ? new Date(child.start_date) : new Date(child.created_at);
    const monthsDue = Math.max(
      1,
      (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth()) + 1
    );
    const paid = paidMonths.get(child.id) || 0;
    const unpaid = Math.max(0, monthsDue - paid);
    if (unpaid === 0) continue;

    let fee = child.monthly_fee || 0;
    let feeSource: 'set' | 'default' = 'set';
    if (fee <= 0 && DEFAULT_DAYCARE_MONTHLY_FEE > 0) {
      fee = DEFAULT_DAYCARE_MONTHLY_FEE;
      feeSource = 'default';
    }

    daycareRows.push({
      key: `d${child.id}`,
      daycareChildId: child.id,
      name: child.name,
      parentName: child.parent_name,
      phone: child.phone,
      monthlyFee: fee,
      feeSource,
      monthsDue,
      monthsPaid: paid,
      monthsUnpaid: unpaid,
      amountDue: round2(unpaid * fee),
      status: daycareDueStatus(unpaid),
    });
  }

  const order: Record<string, number> = { overdue: 0, pending: 1, ok: 2 };
  const sorter = (a: any, b: any) =>
    order[a.status] - order[b.status] || b.amountDue - a.amountDue;

  studentRows.sort(sorter);
  daycareRows.sort(sorter);

  const all = [...studentRows, ...daycareRows];

  return {
    thresholds: {
      studentPendingFrom: 4,
      studentOverdueAfter: 7,
      daycarePendingFrom: 1,
      daycareOverdueAfter: 2,
    },
    pricing: {
      sessionsPerMonth: SESSIONS_PER_MONTH,
      defaultSessionPrice: DEFAULT_SESSION_PRICE,
      defaultDaycareMonthlyFee: DEFAULT_DAYCARE_MONTHLY_FEE,
    },
    students: studentRows,
    daycare: daycareRows,
    totals: {
      pending: round2(all.filter((r) => r.status === 'pending').reduce((s, r) => s + r.amountDue, 0)),
      overdue: round2(all.filter((r) => r.status === 'overdue').reduce((s, r) => s + r.amountDue, 0)),
      pendingCount: all.filter((r) => r.status === 'pending').length,
      overdueCount: all.filter((r) => r.status === 'overdue').length,
    },
  };
}

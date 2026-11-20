import { Router } from 'express';
import multer from 'multer';
import {
  averageSessionPrice,
  computeDues,
  daycareDueStatus,
  monthLabelFromDate,
  recordActivity,
  round2,
  studentDueStatus,
} from '../helpers';
import { authRequired, AuthedRequest, requirePermission } from '../middleware';
import {
  DEFAULT_DAYCARE_MONTHLY_FEE,
  ExpenseRecord,
  MonthlyClosingRecord,
  PaymentRecord,
  SchoolCalendarImportRecord,
  SchoolClassRecord,
  SchoolRecord,
  store,
  TeacherRecord,
} from '../store';

export const financeAndSchoolsRouter = Router();

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 10 * 1024 * 1024 },
});

// ─── HELPERS ────────────────────────────────────────────────────────────────
function formatPayment(p: PaymentRecord) {
  const year = p.date ? p.date.slice(0, 4) : String(new Date().getFullYear());
  const creator = p.created_by ? store.users.find((u) => u.id === p.created_by) : null;
  const teacher = p.teacher_id ? store.teachers.find((t) => t.id === p.teacher_id) : null;

  return {
    id: p.id,
    receiptNumber: `REC-${year}-${String(p.id).padStart(5, '0')}`,
    studentName: p.student_name,
    month: p.month,
    amount: Number(p.amount),
    date: p.date,
    status: p.status,
    paymentMethod: p.payment_method || 'especes',
    createdBy: p.created_by,
    createdByName: creator?.name ?? 'Directeur Fondateur',
    createdByRole: creator?.role ?? 'director',
    type: p.daycare_child_id ? 'daycare' : 'student',
    studentId: p.student_id,
    daycareChildId: p.daycare_child_id,
    teacherId: p.teacher_id,
    teacherName: teacher?.name ?? null,
    sessionsPaid: p.sessions_paid || 0,
    monthsPaid: p.months_paid || 0,
  };
}

function calcTeacherShareForPayment(t: TeacherRecord, p: PaymentRecord): number {
  const remType = t.remuneration_type || 'percentage';
  const ratePct = t.rate_percentage ?? 70;
  const salaryPerLesson = t.salary_per_lesson ?? 35;
  const enr = p.student_id
    ? store.enrollments.find((e) => e.student_id === p.student_id && e.teacher_id === t.id)
    : undefined;
  const sessionPrice = enr && enr.price_per_session > 0 ? enr.price_per_session : salaryPerLesson;
  const baseGross = Math.max(Number(p.amount), round2((p.sessions_paid || 0) * sessionPrice));

  if (remType === 'percentage' || (ratePct > 0 && salaryPerLesson === 0 && remType !== 'fixed' && remType !== 'fixed_center')) {
    return round2((baseGross * ratePct) / 100);
  }
  if (remType === 'fixed_center') {
    const centerFixed = t.center_fixed_per_student ?? 20;
    const adminShare = round2(centerFixed * Math.max(1, Math.round((p.sessions_paid || 0) / 4)));
    return round2(Math.max(0, baseGross - adminShare));
  }
  const fixedRate = t.fixed_student_rate ?? salaryPerLesson;
  return round2(fixedRate * Math.max(1, Math.round((p.sessions_paid || 0) / 4)));
}

function csvEscape(v: any): string {
  const s = String(v ?? '').replace(/"/g, '""');
  return `"${s}"`;
}

// ─── PAYMENTS ───────────────────────────────────────────────────────────────
financeAndSchoolsRouter.get('/payment-dues', authRequired, (_req, res) => {
  res.json(computeDues());
});

financeAndSchoolsRouter.get('/payments/extract-teachers', authRequired, (req: AuthedRequest, res) => {
  if (req.user?.role !== 'director') {
    return res.status(403).json({ message: 'Accès réservé au directeur.' });
  }

  const rawIds = req.query.teacher_ids;
  let teachers = [...store.teachers];
  if (rawIds) {
    const ids = (Array.isArray(rawIds) ? rawIds : String(rawIds).split(','))
      .map((x) => Number(x))
      .filter((x) => x > 0);
    if (ids.length > 0) teachers = teachers.filter((t) => ids.includes(t.id));
  }
  teachers.sort((a, b) => a.name.localeCompare(b.name));

  const from = req.query.from ? String(req.query.from) : null;
  const to = req.query.to ? String(req.query.to) : null;

  let grandTotalCollected = 0;
  let grandTotalTeacherShare = 0;
  let grandTotalCenterMargin = 0;

  const data = teachers.map((t) => {
    const payments = store.payments
      .filter(
        (p) =>
          p.teacher_id === t.id &&
          p.status === 'paid' &&
          (!from || p.date >= from) &&
          (!to || p.date <= to)
      )
      .sort((a, b) => a.date.localeCompare(b.date));

    let totalCollected = 0;
    let totalTeacherShare = 0;
    let totalSessions = 0;

    const paymentDetails = payments.map((p) => {
      const amt = Number(p.amount);
      const tShare = calcTeacherShareForPayment(t, p);
      const cMargin = round2(Math.max(0, amt - tShare));
      totalCollected += amt;
      totalTeacherShare += tShare;
      totalSessions += p.sessions_paid || 0;

      return {
        id: p.id,
        date: p.date,
        month: p.month,
        student_id: p.student_id,
        student_name: p.student_name,
        sessions_paid: p.sessions_paid || 0,
        amount: amt,
        teacher_share: tShare,
        center_margin: cMargin,
      };
    });

    const totalCenterMargin = round2(Math.max(0, totalCollected - totalTeacherShare));
    grandTotalCollected += totalCollected;
    grandTotalTeacherShare += totalTeacherShare;
    grandTotalCenterMargin += totalCenterMargin;

    return {
      teacher: {
        id: t.id,
        name: t.name,
        subject: t.subject,
        phone: t.phone,
        email: t.email,
        remuneration_type: t.remuneration_type,
        rate_percentage: t.rate_percentage,
        salary_per_lesson: t.salary_per_lesson,
        fixed_student_rate: t.fixed_student_rate,
        center_fixed_per_student: t.center_fixed_per_student,
        weekly_lessons: t.weekly_lessons,
      },
      summary: {
        total_collected: round2(totalCollected),
        total_teacher_share: round2(totalTeacherShare),
        total_center_margin: totalCenterMargin,
        total_sessions_paid: totalSessions,
        payments_count: payments.length,
      },
      payments: paymentDetails,
    };
  });

  if (req.query.format === 'csv') {
    const filename = `Extrait_Paiements_Enseignants_${new Date().toISOString().slice(0, 10)}.csv`;
    const rows: string[] = [
      [
        'Enseignant', 'Matiere', 'Telephone', 'Formule',
        'Date', 'Mois', 'Eleve', 'Seances Payees', 'Montant Encaisse (TND)',
        'Part Professeur (TND)', 'Marge Centre (TND)',
      ].map(csvEscape).join(';'),
    ];
    for (const item of data) {
      const t = item.teacher;
      const formule =
        t.remuneration_type === 'percentage'
          ? `Formule C : ${t.rate_percentage}% du brut`
          : t.remuneration_type === 'fixed_center'
            ? `Formule A : Idara ${t.center_fixed_per_student} TND / élève`
            : `Formule B : ${t.fixed_student_rate} TND / élève`;

      if (item.payments.length === 0) {
        rows.push([t.name, t.subject, t.phone, formule, '-', '-', 'Aucun paiement', 0, 0, 0, 0].map(csvEscape).join(';'));
      } else {
        for (const p of item.payments) {
          rows.push(
            [
              t.name, t.subject, t.phone, formule,
              p.date, p.month, p.student_name, p.sessions_paid,
              p.amount.toFixed(2).replace('.', ','),
              p.teacher_share.toFixed(2).replace('.', ','),
              p.center_margin.toFixed(2).replace('.', ','),
            ].map(csvEscape).join(';')
          );
        }
      }
    }
    res.setHeader('Content-Type', 'text/csv; charset=UTF-8');
    res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
    return res.send('\uFEFF' + rows.join('\n'));
  }

  res.json({
    generated_at: new Date().toISOString(),
    period: { from, to },
    totals: {
      grand_total_collected: round2(grandTotalCollected),
      grand_total_teacher_share: round2(grandTotalTeacherShare),
      grand_total_center_margin: round2(grandTotalCenterMargin),
      teachers_count: data.length,
    },
    data,
  });
});

financeAndSchoolsRouter.get('/payments/extract-students', authRequired, (req: AuthedRequest, res) => {
  if (req.user?.role !== 'director') {
    return res.status(403).json({ message: 'Accès réservé au directeur.' });
  }

  const rawIds = req.query.student_ids;
  let students = [...store.students];
  if (rawIds) {
    const ids = (Array.isArray(rawIds) ? rawIds : String(rawIds).split(','))
      .map((x) => Number(x))
      .filter((x) => x > 0);
    if (ids.length > 0) students = students.filter((s) => ids.includes(s.id));
  }
  students.sort((a, b) => a.name.localeCompare(b.name));

  const from = req.query.from ? String(req.query.from) : null;
  const to = req.query.to ? String(req.query.to) : null;
  const dues = computeDues();

  let grandTotalPaid = 0;
  let grandTotalDue = 0;

  const data = students.map((s) => {
    const payments = store.payments
      .filter((p) => p.student_id === s.id && (!from || p.date >= from) && (!to || p.date <= to))
      .sort((a, b) => a.date.localeCompare(b.date));

    const totalPaid = round2(payments.filter((p) => p.status === 'paid').reduce((sum, p) => sum + Number(p.amount), 0));
    grandTotalPaid += totalPaid;

    const studentDues = dues.students
      .filter((d) => d.studentId === s.id)
      .map((d) => ({
        teacher_id: d.teacherId,
        teacher_name: d.teacherName,
        subject: d.subjects.join(', '),
        unpaid_sessions: d.sessionsUnpaid,
        unit_price: d.unitAmount,
        amount_due: d.amountDue,
        status: d.status,
      }));
    const totalDue = round2(studentDues.reduce((sum, d) => sum + d.amount_due, 0));
    grandTotalDue += totalDue;

    return {
      student: {
        id: s.id,
        name: s.name,
        level: s.level,
        group_name: s.group_name,
        phone: s.phone,
        parent_name: null,
        parent_phone: s.parent_phone,
        is_remise: Boolean(s.is_remise),
        remise: s.remise,
        discount_type: s.discount_type,
      },
      summary: {
        total_paid: totalPaid,
        total_due: totalDue,
        payments_count: payments.length,
        has_overdue: studentDues.some((d) => d.status === 'overdue'),
        has_pending: studentDues.some((d) => d.status === 'pending'),
      },
      payments: payments.map(formatPayment),
      dues: studentDues,
    };
  });

  if (req.query.format === 'csv') {
    const filename = `Extrait_Paiements_Eleves_${new Date().toISOString().slice(0, 10)}.csv`;
    const rows: string[] = [
      [
        'Élève', 'Niveau', 'Groupe', 'Téléphone',
        'Date Paiement', 'Mois', 'Enseignant / Type', 'Séances Payées',
        'Montant Payé (TND)', 'Statut Paiement', 'Encaissé Par', 'Reste Dû (TND)',
      ].map(csvEscape).join(';'),
    ];
    for (const item of data) {
      const s = item.student;
      const totalDueStr = item.summary.total_due.toFixed(2).replace('.', ',');
      if (item.payments.length === 0) {
        rows.push(
          [s.name, s.level || '-', s.group_name || '-', s.phone || '-', '-', '-', 'Aucun règlement', 0, 0, 'Sans historique', '-', totalDueStr]
            .map(csvEscape)
            .join(';')
        );
      } else {
        for (const p of item.payments) {
          const collectedBy = `${p.createdByName} (${p.createdByRole === 'secretary' ? 'Personnel' : 'Directeur'})`;
          rows.push(
            [
              s.name, s.level || '-', s.group_name || '-', s.phone || '-',
              p.date, p.month, p.teacherName || (p.type === 'daycare' ? 'Garderie' : 'Cours'),
              p.sessionsPaid || p.monthsPaid || 1,
              Number(p.amount).toFixed(2).replace('.', ','),
              p.status, collectedBy, totalDueStr,
            ].map(csvEscape).join(';')
          );
        }
      }
    }
    res.setHeader('Content-Type', 'text/csv; charset=UTF-8');
    res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
    return res.send('\uFEFF' + rows.join('\n'));
  }

  res.json({
    generated_at: new Date().toISOString(),
    period: { from, to },
    totals: {
      grand_total_paid: round2(grandTotalPaid),
      grand_total_due: round2(grandTotalDue),
      students_count: data.length,
    },
    data,
  });
});

financeAndSchoolsRouter.get('/payments', authRequired, (_req, res) => {
  const list = [...store.payments].sort((a, b) => b.id - a.id).map(formatPayment);
  res.json(list);
});

financeAndSchoolsRouter.post('/payments', authRequired, (req: AuthedRequest, res) => {
  const body = req.body || {};
  const studentId = body.studentId ? Number(body.studentId) : null;
  const daycareChildId = body.daycareChildId ? Number(body.daycareChildId) : null;
  const teacherId = body.teacherId ? Number(body.teacherId) : null;

  if (!studentId && !daycareChildId) {
    return res.status(422).json({ message: 'Élève ou enfant de garderie requis.' });
  }
  if (body.amount === undefined || Number(body.amount) < 0) {
    return res.status(422).json({ message: 'Montant valide requis.' });
  }

  let targetName = '';
  if (studentId) {
    const stu = store.students.find((s) => s.id === studentId);
    if (!stu) return res.status(404).json({ message: 'Élève introuvable.' });
    targetName = stu.name;
  } else if (daycareChildId) {
    const child = store.daycareChildren.find((c) => c.id === daycareChildId);
    if (!child) return res.status(404).json({ message: 'Enfant introuvable.' });
    targetName = child.name;
  }

  const date = body.date ? String(body.date).slice(0, 10) : new Date().toISOString().slice(0, 10);
  const method = body.paymentMethod || body.payment_method || 'especes';

  const payment: PaymentRecord = {
    id: store.nextId('payment'),
    student_id: studentId,
    teacher_id: studentId ? teacherId : null,
    daycare_child_id: daycareChildId,
    student_name: targetName,
    month: body.month || monthLabelFromDate(date),
    amount: round2(Number(body.amount)),
    date,
    status: ['paid', 'pending', 'overdue'].includes(body.status) ? body.status : 'paid',
    payment_method: method,
    sessions_paid: studentId ? Number(body.sessionsPaid || 0) : 0,
    months_paid: daycareChildId ? Number(body.monthsPaid || 0) : 0,
    created_by: req.user?.id ?? 1,
    created_at: new Date().toISOString(),
  };

  store.payments.push(payment);

  store.paymentAuditLogs.unshift({
    id: store.nextId('auditLog'),
    payment_id: payment.id,
    user_id: req.user?.id ?? null,
    user_name: req.user?.name ?? 'Directeur / Personnel',
    user_role: req.user?.role ?? 'director',
    action: 'created',
    amount: payment.amount,
    student_name: payment.student_name,
    payment_method: method,
    details: {
      month: payment.month,
      sessions: payment.sessions_paid,
      months: payment.months_paid,
      status: payment.status,
    },
    ip_address: req.ip || '127.0.0.1',
    created_at: new Date().toISOString(),
  });

  recordActivity(
    req.user,
    'payment',
    'created',
    `Paiement de ${payment.amount} TND pour ${payment.student_name} (${payment.status === 'paid' ? 'reçu' : 'en attente'})`
  );

  res.status(201).json(formatPayment(payment));
});

financeAndSchoolsRouter.get('/payments/:id', authRequired, (req, res) => {
  const payment = store.payments.find((p) => p.id === Number(req.params.id));
  if (!payment) return res.status(404).json({ message: 'Paiement introuvable.' });
  res.json(formatPayment(payment));
});

financeAndSchoolsRouter.put('/payments/:id', authRequired, (req: AuthedRequest, res) => {
  const payment = store.payments.find((p) => p.id === Number(req.params.id));
  if (!payment) return res.status(404).json({ message: 'Paiement introuvable.' });

  const oldStatus = payment.status;
  const body = req.body || {};

  if (body.studentName !== undefined) payment.student_name = String(body.studentName);
  if (body.month !== undefined) payment.month = String(body.month);
  if (body.amount !== undefined) payment.amount = round2(Number(body.amount));
  if (body.date !== undefined) payment.date = String(body.date).slice(0, 10);
  if (['paid', 'pending', 'overdue'].includes(body.status)) payment.status = body.status;
  if (body.sessionsPaid !== undefined) payment.sessions_paid = Number(body.sessionsPaid);
  if (body.monthsPaid !== undefined) payment.months_paid = Number(body.monthsPaid);
  if (body.paymentMethod !== undefined || body.payment_method !== undefined) {
    payment.payment_method = String(body.paymentMethod ?? body.payment_method);
  }

  const action = oldStatus !== 'paid' && payment.status === 'paid' ? 'marked_paid' : 'updated';

  store.paymentAuditLogs.unshift({
    id: store.nextId('auditLog'),
    payment_id: payment.id,
    user_id: req.user?.id ?? null,
    user_name: req.user?.name ?? 'Directeur / Personnel',
    user_role: req.user?.role ?? 'director',
    action,
    amount: payment.amount,
    student_name: payment.student_name,
    payment_method: payment.payment_method,
    details: body,
    ip_address: req.ip || '127.0.0.1',
    created_at: new Date().toISOString(),
  });

  recordActivity(
    req.user,
    'payment',
    action,
    action === 'marked_paid'
      ? `Paiement de ${payment.amount} TND validé pour ${payment.student_name} (encaissé)`
      : `Modification du paiement #${payment.id} pour ${payment.student_name}`
  );

  res.json(formatPayment(payment));
});

financeAndSchoolsRouter.delete('/payments/:id', authRequired, requirePermission('can_delete'), (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const idx = store.payments.findIndex((p) => p.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Paiement introuvable.' });

  const payment = store.payments[idx];
  store.paymentAuditLogs.unshift({
    id: store.nextId('auditLog'),
    payment_id: payment.id,
    user_id: req.user?.id ?? null,
    user_name: req.user?.name ?? 'Directeur',
    user_role: req.user?.role ?? 'director',
    action: 'deleted',
    amount: payment.amount,
    student_name: payment.student_name,
    payment_method: payment.payment_method,
    details: { month: payment.month, date: payment.date, status: payment.status },
    ip_address: req.ip || '127.0.0.1',
    created_at: new Date().toISOString(),
  });

  recordActivity(req.user, 'payment', 'deleted', `Suppression du paiement de ${payment.amount} TND pour ${payment.student_name}`);
  store.payments.splice(idx, 1);
  res.json({ message: 'Paiement supprimé avec succès' });
});

// ─── ACCOUNTING (Permission: reports) ───────────────────────────────────────
const accountingAuth = [authRequired, requirePermission('reports')] as const;

function monthShortLabel(year: number, monthIndex0: number): string {
  const shortMonths = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
  return `${shortMonths[monthIndex0]} ${year}`;
}

function formatExpense(e: ExpenseRecord) {
  const creator = e.created_by ? store.users.find((u) => u.id === e.created_by) : null;
  return {
    ...e,
    creator: creator ? { id: creator.id, name: creator.name, role: creator.role } : null,
  };
}

financeAndSchoolsRouter.get('/accounting/summary', ...accountingAuth, (req, res) => {
  const now = new Date();
  const period = String(req.query.period || '12months');
  const selectedSchoolYear = String(req.query.school_year || `${now.getFullYear()}-${now.getFullYear() + 1}`);
  const selectedMonth = String(req.query.month || now.toISOString().slice(0, 7));
  const journalDate = String(req.query.journal_date || now.toISOString().slice(0, 10));

  let startYear = now.getFullYear();
  let startMonth = now.getMonth() - 11;
  let endYear = now.getFullYear();
  let endMonth = now.getMonth();

  if (period === 'school_year') {
    const parts = selectedSchoolYear.split('-');
    startYear = Number(parts[0]) || now.getFullYear();
    startMonth = 8; // Sept
    endYear = Number(parts[1]) || startYear + 1;
    endMonth = 5; // June
  } else if (period === 'current_month') {
    const [y, m] = selectedMonth.split('-').map(Number);
    startYear = y || now.getFullYear();
    startMonth = (m || now.getMonth() + 1) - 1;
    endYear = startYear;
    endMonth = startMonth;
  }

  const startD = new Date(startYear, startMonth, 1);
  const endD = new Date(endYear, endMonth + 1, 0);
  const startStr = `${startD.getFullYear()}-${String(startD.getMonth() + 1).padStart(2, '0')}-${String(startD.getDate()).padStart(2, '0')}`;
  const endStr = `${endD.getFullYear()}-${String(endD.getMonth() + 1).padStart(2, '0')}-${String(endD.getDate()).padStart(2, '0')}`;

  const periodPayments = store.payments.filter(
    (p) => p.status === 'paid' && p.date >= startStr && p.date <= endStr
  );
  const periodExpenses = store.expenses.filter((e) => e.date >= startStr && e.date <= endStr);

  // Months trend
  const monthsTrend: any[] = [];
  const cursor = new Date(startD.getFullYear(), startD.getMonth(), 1);
  const endCursor = new Date(endD.getFullYear(), endD.getMonth(), 1);
  while (cursor <= endCursor) {
    const mKey = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}`;
    const mPayments = periodPayments.filter((p) => p.date.startsWith(mKey));
    const mExpenses = periodExpenses.filter((e) => e.date.startsWith(mKey));
    const studentRev = mPayments.filter((p) => !p.daycare_child_id).reduce((s, p) => s + Number(p.amount), 0);
    const daycareRev = mPayments.filter((p) => p.daycare_child_id).reduce((s, p) => s + Number(p.amount), 0);
    const totalRev = studentRev + daycareRev;
    const expTotal = mExpenses.reduce((s, e) => s + Number(e.amount), 0);
    let tPayouts = 0;
    for (const mp of mPayments.filter((p) => !p.daycare_child_id && p.teacher_id)) {
      const t = store.teachers.find((x) => x.id === mp.teacher_id);
      if (t) tPayouts += calcTeacherShareForPayment(t, mp);
    }
    monthsTrend.push({
      month: mKey,
      label: monthShortLabel(cursor.getFullYear(), cursor.getMonth()),
      students: round2(studentRev),
      daycare: round2(daycareRev),
      totalRevenue: round2(totalRev),
      expenses: round2(expTotal),
      teacherPayouts: round2(tPayouts),
      netProfit: round2(totalRev - expTotal - tPayouts),
    });
    cursor.setMonth(cursor.getMonth() + 1);
  }

  // By Teacher
  const byTeacher = store.teachers.map((t) => {
    const tPayments = periodPayments.filter((p) => p.teacher_id === t.id);
    const collected = tPayments.reduce((s, p) => s + Number(p.amount), 0);
    const sessions = tPayments.reduce((s, p) => s + Number(p.sessions_paid || 0), 0);
    const teacherShare = round2(tPayments.reduce((s, tp) => s + calcTeacherShareForPayment(t, tp), 0));
    const isPaid = t.payout_status === 'paid';
    const paidAmount = isPaid ? Number(t.payout_amount ?? teacherShare) : 0;
    const remaining = round2(Math.max(0, teacherShare - paidAmount));

    return {
      id: t.id,
      name: t.name,
      subject: t.subject,
      phone: t.phone,
      remunerationType: t.remuneration_type,
      ratePercentage: t.rate_percentage,
      salaryPerLesson: t.salary_per_lesson,
      fixedStudentRate: t.fixed_student_rate,
      centerFixedPerStudent: t.center_fixed_per_student,
      sessionsCount: sessions,
      revenueCollected: round2(collected),
      teacherShare,
      centerMargin: round2(Math.max(0, collected - teacherShare)),
      paidAmount: round2(paidAmount),
      remainingToPay: remaining,
      payoutStatus: isPaid ? 'paid' : 'pending',
      payoutDate: t.payout_date,
    };
  });

  // By Subject
  const subjMap = new Map<string, number>();
  for (const p of periodPayments.filter((p) => !p.daycare_child_id)) {
    const t = p.teacher_id ? store.teachers.find((x) => x.id === p.teacher_id) : null;
    const subj = t?.subject || 'Général';
    subjMap.set(subj, (subjMap.get(subj) || 0) + Number(p.amount));
  }
  const totalSubjRev = Math.max(1, Array.from(subjMap.values()).reduce((a, b) => a + b, 0));
  const bySubject = Array.from(subjMap.entries()).map(([subject, amount]) => ({
    subject,
    amount: round2(amount),
    percentage: round2((amount / totalSubjRev) * 100),
  }));

  // By Level
  const lvlMap = new Map<string, number>();
  for (const p of periodPayments) {
    let lvl = 'Autres';
    if (p.daycare_child_id) lvl = 'Garderie';
    else if (p.student_id) {
      const stu = store.students.find((s) => s.id === p.student_id);
      lvl = stu?.level || 'Non spécifié';
    }
    lvlMap.set(lvl, (lvlMap.get(lvl) || 0) + Number(p.amount));
  }
  const totalLvlRev = Math.max(1, Array.from(lvlMap.values()).reduce((a, b) => a + b, 0));
  const byLevel = Array.from(lvlMap.entries()).map(([level, amount]) => ({
    level,
    amount: round2(amount),
    percentage: round2((amount / totalLvlRev) * 100),
  }));

  // Expenses Donut
  const expenseCategories: Record<string, string> = {
    loyer: 'Loyer',
    électricité: 'Électricité / Eau',
    fournitures: 'Fournitures & Copies',
    entretien: 'Entretien & Nettoyage',
    publicité: 'Publicité & Marketing',
    internet_tel: 'Internet & Télécoms',
    salaires: 'Salaires & Primes Annexes',
    autres: 'Autres Dépenses',
  };
  const totalExpAmount = periodExpenses.reduce((s, e) => s + Number(e.amount), 0);
  const expenseDonut = Object.entries(expenseCategories).map(([category, label]) => {
    const items = periodExpenses.filter((e) => e.category === category);
    const amount = items.reduce((s, e) => s + Number(e.amount), 0);
    return {
      category,
      label,
      amount: round2(amount),
      count: items.length,
      percentage: totalExpAmount > 0 ? round2((amount / totalExpAmount) * 100) : 0,
    };
  });

  // Top Debtors (real dues + WhatsApp links)
  const dues = computeDues();
  const topDebtors = [
    ...dues.students.map((d) => {
      const rawPhone = d.parentPhone || d.phone || '';
      let cleanPhone = rawPhone.replace(/[^0-9]/g, '');
      if (cleanPhone.length === 8) cleanPhone = `216${cleanPhone}`;
      const msg = `Bonjour, le Centre d'études vous informe qu'un règlement de ${d.amountDue} DT (${d.sessionsUnpaid} séances de ${d.subjects[0] || 'Cours'}) est en attente pour l'élève ${d.studentName}. Merci de régulariser la situation auprès de l'administration. Cordialement.`;
      return {
        key: d.key,
        id: d.studentId,
        name: d.studentName,
        type: 'student',
        category: `Élève - ${d.subjects[0] || 'Cours'}`,
        parentName: null,
        phone: rawPhone,
        cleanPhone,
        unpaidUnits: `${d.sessionsUnpaid} séances`,
        amountDue: d.amountDue,
        teacherName: d.teacherName,
        status: studentDueStatus(d.sessionsUnpaid),
        whatsappUrl: cleanPhone ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}` : null,
      };
    }),
    ...dues.daycare.map((d) => {
      const rawPhone = d.phone || '';
      let cleanPhone = rawPhone.replace(/[^0-9]/g, '');
      if (cleanPhone.length === 8) cleanPhone = `216${cleanPhone}`;
      const msg = `Bonjour ${d.parentName}, le Centre d'études vous informe que les frais de garderie (${d.monthsUnpaid} mois - ${d.amountDue} DT) pour ${d.name} sont en attente. Merci de régulariser la situation auprès de l'administration. Cordialement.`;
      return {
        key: d.key,
        id: d.daycareChildId,
        name: d.name,
        type: 'daycare',
        category: 'Garderie',
        parentName: d.parentName,
        phone: rawPhone,
        cleanPhone,
        unpaidUnits: `${d.monthsUnpaid} mois`,
        amountDue: d.amountDue,
        teacherName: 'Garderie',
        status: daycareDueStatus(d.monthsUnpaid),
        whatsappUrl: cleanPhone ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}` : null,
      };
    }),
  ]
    .sort((a, b) => b.amountDue - a.amountDue)
    .slice(0, 15);

  const totalStudentsDue = round2(dues.students.reduce((s, d) => s + d.amountDue, 0));
  const totalDaycareDue = round2(dues.daycare.reduce((s, d) => s + d.amountDue, 0));
  const grandTotalUnpaid = round2(totalStudentsDue + totalDaycareDue);

  const grandTotalRevenue = round2(periodPayments.reduce((s, p) => s + Number(p.amount), 0));
  const grandTotalExpenses = round2(totalExpAmount);
  const grandTotalTeacherPayouts = round2(byTeacher.reduce((s, t) => s + t.teacherShare, 0));
  const netProfit = round2(grandTotalRevenue - grandTotalExpenses - grandTotalTeacherPayouts);

  const recDenom = grandTotalRevenue + grandTotalUnpaid;
  const recoveryRate = recDenom > 0 ? Math.round((grandTotalRevenue / recDenom) * 1000) / 10 : 100;

  const monthlyDiscounts = round2(
    store.students
      .filter((s) => s.status === 'active' && s.is_remise && s.remise > 0)
      .reduce((sum, s) => sum + Number(s.remise), 0)
  );

  const activePayingStudentIds = new Set(periodPayments.filter((p) => p.student_id).map((p) => p.student_id)).size;
  const studentRevTotal = periodPayments.filter((p) => !p.daycare_child_id).reduce((s, p) => s + Number(p.amount), 0);
  const averageRevenuePerStudent = activePayingStudentIds > 0 ? round2(studentRevTotal / activePayingStudentIds) : 0;

  let projectedNextMonthRevenue = 0;
  for (const s of store.students.filter((x) => x.status === 'active')) {
    const enrs = store.enrollments.filter((e) => e.student_id === s.id);
    let gross = enrs.reduce((sum, e) => {
      const [price] = averageSessionPrice([e]);
      return sum + (e.sessions_per_month || 4) * price;
    }, 0);
    if (s.is_remise && s.remise > 0) {
      gross = s.discount_type === 'percentage' ? gross * (1 - s.remise / 100) : Math.max(0, gross - s.remise);
    }
    projectedNextMonthRevenue += gross;
  }
  for (const c of store.daycareChildren) {
    projectedNextMonthRevenue += c.monthly_fee > 0 ? c.monthly_fee : DEFAULT_DAYCARE_MONTHLY_FEE;
  }

  // Daily Cash Journal
  const jPayments = store.payments.filter((p) => p.status === 'paid' && p.date === journalDate);
  const jExpenses = store.expenses.filter((e) => e.date === journalDate);
  const methods = ['especes', 'cheque', 'virement', 'carte', 'autre'];
  const inflowsByMethod: Record<string, number> = {};
  const outflowsByMethod: Record<string, number> = {};
  for (const m of methods) {
    inflowsByMethod[m] = round2(jPayments.filter((p) => p.payment_method === m).reduce((s, p) => s + p.amount, 0));
    outflowsByMethod[m] = round2(jExpenses.filter((e) => e.payment_method === m).reduce((s, e) => s + e.amount, 0));
  }
  const totalInflows = round2(jPayments.reduce((s, p) => s + p.amount, 0));
  const totalOutflows = round2(jExpenses.reduce((s, e) => s + e.amount, 0));

  const dailyCashJournal = {
    date: journalDate,
    totalInflows,
    totalOutflows,
    netTotal: round2(totalInflows - totalOutflows),
    netCashOnly: round2(inflowsByMethod.especes - outflowsByMethod.especes),
    inflowsByMethod,
    outflowsByMethod,
    inflowsCount: jPayments.length,
    outflowsCount: jExpenses.length,
    recentInflows: jPayments.slice(0, 10).map((p) => ({
      id: p.id,
      receiptNumber: `REC-${p.date.slice(0, 4)}-${String(p.id).padStart(5, '0')}`,
      studentName: p.student_name,
      amount: p.amount,
      method: p.payment_method,
    })),
    recentOutflows: jExpenses.slice(0, 10).map((e) => ({
      id: e.id,
      title: e.title,
      category: e.category,
      amount: e.amount,
      method: e.payment_method,
    })),
  };

  const closing = store.monthlyClosings.find((c) => c.month === selectedMonth);

  res.json({
    period,
    selectedMonth,
    schoolYear: selectedSchoolYear,
    range: { start: startStr, end: endStr },
    kpis: {
      totalRevenue: grandTotalRevenue,
      totalExpenses: grandTotalExpenses,
      totalTeacherPayouts: grandTotalTeacherPayouts,
      netProfit,
      recoveryRate,
      totalUnpaid: grandTotalUnpaid,
      unpaidStudents: totalStudentsDue,
      unpaidDaycare: totalDaycareDue,
      monthlyDiscounts,
      payingStudentsCount: activePayingStudentIds,
      averageRevenuePerStudent,
      projectedNextMonthRevenue: round2(projectedNextMonthRevenue),
    },
    monthsTrend,
    byTeacher,
    bySubject,
    byLevel,
    expenseDonut,
    topDebtors,
    dailyCashJournal,
    monthlyClosing: closing
      ? {
          isClosed: closing.is_closed,
          closedAt: closing.closed_at,
          notes: closing.notes,
          netProfit: closing.net_profit,
        }
      : {
          isClosed: false,
          closedAt: null,
          notes: null,
          netProfit: null,
        },
  });
});

financeAndSchoolsRouter.get('/accounting/expenses', ...accountingAuth, (req, res) => {
  let list = [...store.expenses];
  if (req.query.category) list = list.filter((e) => e.category === String(req.query.category));
  if (req.query.payment_method) list = list.filter((e) => e.payment_method === String(req.query.payment_method));
  if (req.query.from) list = list.filter((e) => e.date >= String(req.query.from));
  if (req.query.to) list = list.filter((e) => e.date <= String(req.query.to));
  if (req.query.is_recurring === 'true' || req.query.is_recurring === '1') {
    list = list.filter((e) => e.is_recurring);
  }
  if (req.query.search) {
    const s = String(req.query.search).toLowerCase();
    list = list.filter(
      (e) =>
        e.title.toLowerCase().includes(s) ||
        (e.receipt_reference || '').toLowerCase().includes(s) ||
        (e.notes || '').toLowerCase().includes(s)
    );
  }

  list.sort((a, b) => b.date.localeCompare(a.date) || b.id - a.id);
  const totalAmount = round2(list.reduce((sum, e) => sum + Number(e.amount), 0));

  res.json({
    totalAmount,
    count: list.length,
    data: list.map(formatExpense),
  });
});

financeAndSchoolsRouter.post('/accounting/expenses', ...accountingAuth, (req: AuthedRequest, res) => {
  const body = req.body || {};
  if (!body.title || !body.category || !body.amount || !body.date) {
    return res.status(422).json({ message: 'Libellé, catégorie, montant et date sont obligatoires.' });
  }

  const exp: ExpenseRecord = {
    id: store.nextId('expense'),
    title: String(body.title).trim(),
    category: String(body.category),
    amount: round2(Number(body.amount)),
    date: String(body.date).slice(0, 10),
    payment_method: body.payment_method || 'especes',
    is_recurring: Boolean(body.is_recurring),
    recurring_frequency: body.recurring_frequency === 'annual' ? 'annual' : 'monthly',
    receipt_reference: body.receipt_reference ? String(body.receipt_reference) : null,
    notes: body.notes ? String(body.notes) : null,
    created_by: req.user?.id ?? 1,
    created_at: new Date().toISOString(),
  };

  store.expenses.push(exp);
  recordActivity(req.user, 'expense', 'created', `Dépense enregistrée : ${exp.title} (${exp.amount} TND)`);
  res.status(201).json(formatExpense(exp));
});

financeAndSchoolsRouter.put('/accounting/expenses/:id', ...accountingAuth, (req: AuthedRequest, res) => {
  const exp = store.expenses.find((e) => e.id === Number(req.params.id));
  if (!exp) return res.status(404).json({ message: 'Dépense introuvable.' });

  const body = req.body || {};
  if (body.title !== undefined) exp.title = String(body.title).trim();
  if (body.category !== undefined) exp.category = String(body.category);
  if (body.amount !== undefined) exp.amount = round2(Number(body.amount));
  if (body.date !== undefined) exp.date = String(body.date).slice(0, 10);
  if (body.payment_method !== undefined) exp.payment_method = String(body.payment_method);
  if (body.is_recurring !== undefined) exp.is_recurring = Boolean(body.is_recurring);
  if (body.recurring_frequency !== undefined) exp.recurring_frequency = body.recurring_frequency === 'annual' ? 'annual' : 'monthly';
  if (body.receipt_reference !== undefined) exp.receipt_reference = body.receipt_reference ? String(body.receipt_reference) : null;
  if (body.notes !== undefined) exp.notes = body.notes ? String(body.notes) : null;

  recordActivity(req.user, 'expense', 'updated', `Modification de la dépense : ${exp.title}`);
  res.json(formatExpense(exp));
});

financeAndSchoolsRouter.delete('/accounting/expenses/:id', ...accountingAuth, (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const idx = store.expenses.findIndex((e) => e.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Dépense introuvable.' });

  const { title, amount } = store.expenses[idx];
  store.expenses.splice(idx, 1);
  recordActivity(req.user, 'expense', 'deleted', `Suppression de la dépense : ${title} (${amount} TND)`);
  res.json({ message: 'Dépense supprimée avec succès.' });
});

financeAndSchoolsRouter.post('/accounting/expenses/generate-recurring', ...accountingAuth, (req: AuthedRequest, res) => {
  const targetMonth = String(req.body?.month || new Date().toISOString().slice(0, 7));
  const targetDate = `${targetMonth}-01`;

  const templates = new Map<string, ExpenseRecord>();
  for (const e of store.expenses) {
    if (e.is_recurring) {
      const key = `${e.title}-${e.category}`;
      if (!templates.has(key)) templates.set(key, e);
    }
  }

  const generated: ExpenseRecord[] = [];
  for (const tpl of templates.values()) {
    const exists = store.expenses.some(
      (e) => e.title === tpl.title && e.category === tpl.category && e.date.startsWith(targetMonth)
    );
    if (!exists) {
      const exp: ExpenseRecord = {
        id: store.nextId('expense'),
        title: tpl.title,
        category: tpl.category,
        amount: tpl.amount,
        date: targetDate,
        payment_method: tpl.payment_method || 'virement',
        is_recurring: true,
        recurring_frequency: tpl.recurring_frequency || 'monthly',
        receipt_reference: `AUTO-${targetMonth}`,
        notes: 'Génération automatique récurrente mensuelle',
        created_by: req.user?.id ?? 1,
        created_at: new Date().toISOString(),
      };
      store.expenses.push(exp);
      generated.push(exp);
    }
  }

  res.json({
    message: `${generated.length} dépense(s) récurrente(s) générée(s) pour ${targetMonth}`,
    generated,
  });
});

financeAndSchoolsRouter.get('/accounting/audit-logs', ...accountingAuth, (req, res) => {
  let list = [...store.paymentAuditLogs];
  if (req.query.action) list = list.filter((l) => l.action === String(req.query.action));
  if (req.query.search) {
    const s = String(req.query.search).toLowerCase();
    list = list.filter(
      (l) =>
        l.user_name.toLowerCase().includes(s) ||
        l.student_name.toLowerCase().includes(s) ||
        l.action.toLowerCase().includes(s)
    );
  }
  res.json(list.slice(0, 150));
});

financeAndSchoolsRouter.get('/accounting/closings', ...accountingAuth, (_req, res) => {
  const list = [...store.monthlyClosings]
    .sort((a, b) => b.month.localeCompare(a.month))
    .map((c) => {
      const closer = c.closed_by ? store.users.find((u) => u.id === c.closed_by) : null;
      return {
        ...c,
        closed_by_user: closer ? { id: closer.id, name: closer.name } : null,
      };
    });
  res.json(list);
});

financeAndSchoolsRouter.post('/accounting/close-month', ...accountingAuth, (req: AuthedRequest, res) => {
  const { month, school_year, notes } = req.body || {};
  if (!month || !/^\d{4}-\d{2}$/.test(String(month))) {
    return res.status(422).json({ message: 'Format de mois invalide (YYYY-MM).' });
  }

  const mPayments = store.payments.filter((p) => p.status === 'paid' && p.date.startsWith(month));
  const mExpenses = store.expenses.filter((e) => e.date.startsWith(month));

  const totalRevenue = round2(mPayments.reduce((s, p) => s + Number(p.amount), 0));
  const totalExpenses = round2(mExpenses.reduce((s, e) => s + Number(e.amount), 0));

  let totalTeacherPayouts = 0;
  for (const p of mPayments.filter((x) => !x.daycare_child_id && x.teacher_id)) {
    const t = store.teachers.find((x) => x.id === p.teacher_id);
    if (t) totalTeacherPayouts += calcTeacherShareForPayment(t, p);
  }
  totalTeacherPayouts = round2(totalTeacherPayouts);
  const netProfit = round2(totalRevenue - totalExpenses - totalTeacherPayouts);

  let closing = store.monthlyClosings.find((c) => c.month === month);
  if (closing) {
    closing.school_year = school_year || closing.school_year;
    closing.total_revenue = totalRevenue;
    closing.total_expenses = totalExpenses;
    closing.total_teacher_payouts = totalTeacherPayouts;
    closing.net_profit = netProfit;
    closing.closed_at = new Date().toISOString();
    closing.closed_by = req.user?.id ?? 1;
    closing.notes = notes || 'Clôture mensuelle effectuée par la direction.';
    closing.is_closed = true;
  } else {
    closing = {
      id: store.nextId('closing'),
      month,
      school_year: school_year || '2026-2027',
      total_revenue: totalRevenue,
      total_expenses: totalExpenses,
      total_teacher_payouts: totalTeacherPayouts,
      net_profit: netProfit,
      closed_at: new Date().toISOString(),
      closed_by: req.user?.id ?? 1,
      notes: notes || 'Clôture mensuelle effectuée par la direction.',
      is_closed: true,
    } as MonthlyClosingRecord;
    store.monthlyClosings.push(closing);
  }

  recordActivity(req.user, 'accounting', 'closed', `Clôture comptable du mois ${month} (Bénéfice Net : ${netProfit} TND)`);
  res.json({ message: `Mois ${month} clôturé avec succès.`, closing });
});

financeAndSchoolsRouter.post('/accounting/reopen-month', ...accountingAuth, (req: AuthedRequest, res) => {
  const { month } = req.body || {};
  const closing = store.monthlyClosings.find((c) => c.month === String(month));
  if (!closing) return res.status(404).json({ message: 'Clôture introuvable pour ce mois.' });

  closing.is_closed = false;
  closing.notes = `${closing.notes ? closing.notes + '\n' : ''}Réouvert le ${new Date().toISOString()}`;
  recordActivity(req.user, 'accounting', 'reopened', `Réouverture comptable du mois ${month}`);
  res.json({ message: `Le mois ${month} a été réouvert.`, closing });
});

financeAndSchoolsRouter.get('/accounting/teachers/:id/payslip', ...accountingAuth, (req, res) => {
  const teacher = store.teachers.find((t) => t.id === Number(req.params.id));
  if (!teacher) return res.status(404).json({ message: 'Enseignant introuvable.' });

  const month = String(req.query.month || new Date().toISOString().slice(0, 7));
  const payments = store.payments.filter(
    (p) => p.teacher_id === teacher.id && p.status === 'paid' && p.date.startsWith(month)
  );

  let totalCollected = 0;
  let totalSessions = 0;

  const items = payments.map((p) => {
    const amt = Number(p.amount);
    const sessions = p.sessions_paid || 0;
    const tShare = calcTeacherShareForPayment(teacher, p);
    totalCollected += amt;
    totalSessions += sessions;
    return {
      paymentId: p.id,
      receiptNumber: `REC-${p.date.slice(0, 4)}-${String(p.id).padStart(5, '0')}`,
      studentName: p.student_name,
      date: p.date,
      sessions,
      amountPaid: amt,
      teacherShare: tShare,
    };
  });

  const grossDue = round2(items.reduce((s, i) => s + i.teacherShare, 0));
  const isPaid = teacher.payout_status === 'paid';
  const advancesPaid = isPaid ? Number(teacher.payout_amount ?? grossDue) : 0;
  const netPayable = round2(Math.max(0, grossDue - advancesPaid));

  res.json({
    payslipNumber: `FDP-${month.replace('-', '')}-${String(teacher.id).padStart(4, '0')}`,
    generatedAt: new Date().toISOString(),
    month,
    monthLabel: monthLabelFromDate(`${month}-01`),
    teacher: {
      id: teacher.id,
      name: teacher.name,
      subject: teacher.subject,
      phone: teacher.phone,
      email: teacher.email,
      remunerationType: teacher.remuneration_type,
      ratePercentage: teacher.rate_percentage,
      salaryPerLesson: teacher.salary_per_lesson,
      fixedStudentRate: teacher.fixed_student_rate,
      centerFixedPerStudent: teacher.center_fixed_per_student,
      payoutStatus: isPaid ? 'paid' : 'pending',
      payoutDate: teacher.payout_date,
    },
    summary: {
      totalCollected: round2(totalCollected),
      totalSessions,
      grossDue,
      advancesPaid: round2(advancesPaid),
      netPayable,
    },
    items,
  });
});

// ─── SCHOOLS & SCHOOL CALENDAR ──────────────────────────────────────────────
financeAndSchoolsRouter.get('/schools', authRequired, (_req, res) => {
  const list = [...store.schools]
    .sort((a, b) => a.name.localeCompare(b.name))
    .map((s) => ({
      ...s,
      classes_count: store.schoolClasses.filter((c) => c.school_id === s.id).length,
    }));
  res.json(list);
});

financeAndSchoolsRouter.get('/schools/:id/classes', authRequired, (req, res) => {
  const schoolId = Number(req.params.id);
  const classes = store.schoolClasses
    .filter((c) => c.school_id === schoolId)
    .sort((a, b) => a.level - b.level || a.section.localeCompare(b.section));
  res.json(classes);
});

financeAndSchoolsRouter.post('/schools', authRequired, (req, res) => {
  const { name, address, city } = req.body || {};
  if (!name || !String(name).trim()) {
    return res.status(422).json({ message: "Le nom de l'école est obligatoire." });
  }
  const cleanName = String(name).trim();
  if (store.schools.some((s) => s.name.toLowerCase() === cleanName.toLowerCase())) {
    return res.status(422).json({ message: 'Cette école existe déjà.' });
  }
  const school: SchoolRecord = {
    id: store.nextId('school'),
    name: cleanName,
    address: address ? String(address).trim() : null,
    city: city ? String(city).trim() : null,
  };
  store.schools.push(school);
  res.status(201).json(school);
});

financeAndSchoolsRouter.put('/schools/:id', authRequired, (req, res) => {
  const id = Number(req.params.id);
  const school = store.schools.find((s) => s.id === id);
  if (!school) return res.status(404).json({ message: 'École introuvable.' });

  const { name, address, city } = req.body || {};
  if (!name || !String(name).trim()) {
    return res.status(422).json({ message: "Le nom de l'école est obligatoire." });
  }
  const cleanName = String(name).trim();
  if (store.schools.some((s) => s.id !== id && s.name.toLowerCase() === cleanName.toLowerCase())) {
    return res.status(422).json({ message: 'Ce nom d’école est déjà utilisé.' });
  }
  school.name = cleanName;
  school.address = address !== undefined ? (address ? String(address).trim() : null) : school.address;
  school.city = city !== undefined ? (city ? String(city).trim() : null) : school.city;
  res.json(school);
});

financeAndSchoolsRouter.delete('/schools/:id', authRequired, (req, res) => {
  const id = Number(req.params.id);
  const idx = store.schools.findIndex((s) => s.id === id);
  if (idx === -1) return res.status(404).json({ message: 'École introuvable.' });

  const classIds = store.schoolClasses.filter((c) => c.school_id === id).map((c) => c.id);
  store.schoolCalendarImports = store.schoolCalendarImports.filter((i) => !classIds.includes(i.school_class_id));
  store.schoolClasses = store.schoolClasses.filter((c) => c.school_id !== id);
  store.schools.splice(idx, 1);

  res.json({ message: 'École supprimée avec succès' });
});

function resolveSchoolClassId(schoolId: number, level: number, section: string): number {
  const existing = store.schoolClasses.find(
    (c) => c.school_id === schoolId && c.level === level && c.section.toLowerCase() === section.toLowerCase()
  );
  if (existing) return existing.id;

  const created: SchoolClassRecord = {
    id: store.nextId('schoolClass'),
    school_id: schoolId,
    level,
    section,
  };
  store.schoolClasses.push(created);
  return created.id;
}

financeAndSchoolsRouter.get('/school-calendar-imports', authRequired, (req, res) => {
  let rows = store.schoolCalendarImports
    .map((imp) => {
      const cls = store.schoolClasses.find((c) => c.id === imp.school_class_id);
      const school = cls ? store.schools.find((s) => s.id === cls.school_id) : null;
      if (!cls || !school) return null;
      return {
        id: imp.id,
        file_name: imp.file_name,
        file_type: imp.file_type,
        created_at: imp.created_at,
        school_id: school.id,
        school_name: school.name,
        level: cls.level,
        section: cls.section,
      };
    })
    .filter((x): x is NonNullable<typeof x> => x !== null);

  if (req.query.school_id) rows = rows.filter((r) => r.school_id === Number(req.query.school_id));
  if (req.query.level) rows = rows.filter((r) => r.level === Number(req.query.level));
  if (req.query.section) rows = rows.filter((r) => r.section === String(req.query.section));

  rows.sort((a, b) => b.created_at.localeCompare(a.created_at));
  res.json(rows);
});

financeAndSchoolsRouter.post('/school-calendar-imports', authRequired, upload.single('file'), (req, res) => {
  const schoolId = Number(req.body?.school_id);
  const level = Number(req.body?.level);
  const section = String(req.body?.section || '').trim();
  const file = req.file;

  if (!schoolId || !store.schools.some((s) => s.id === schoolId)) {
    return res.status(422).json({ message: 'École invalide.' });
  }
  if (!level || level < 1 || level > 6 || !section) {
    return res.status(422).json({ message: 'Niveau (1-6) et section obligatoires.' });
  }
  if (!file) {
    return res.status(422).json({ message: 'Le fichier (PDF, JPG, PNG) est obligatoire.' });
  }

  const classId = resolveSchoolClassId(schoolId, level, section);
  const record: SchoolCalendarImportRecord = {
    id: store.nextId('calendarImport'),
    school_class_id: classId,
    file_name: file.originalname,
    file_type: file.mimetype || 'application/octet-stream',
    buffer: file.buffer,
    created_at: new Date().toISOString(),
  };
  store.schoolCalendarImports.push(record);
  res.status(201).json({ id: record.id });
});

financeAndSchoolsRouter.get('/school-calendar-imports/:id/file', authRequired, (req, res) => {
  const record = store.schoolCalendarImports.find((i) => i.id === Number(req.params.id));
  if (!record) return res.status(404).json({ message: 'Fichier introuvable.' });

  res.setHeader('Content-Type', record.file_type || 'application/octet-stream');
  res.setHeader('Content-Disposition', `inline; filename="${record.file_name}"`);
  res.send(record.buffer);
});

financeAndSchoolsRouter.post('/school-calendar-imports/:id', authRequired, upload.single('file'), (req, res) => {
  const record = store.schoolCalendarImports.find((i) => i.id === Number(req.params.id));
  if (!record) return res.status(404).json({ message: 'Emploi du temps introuvable.' });

  const schoolId = Number(req.body?.school_id);
  const level = Number(req.body?.level);
  const section = String(req.body?.section || '').trim();
  if (!schoolId || !store.schools.some((s) => s.id === schoolId)) {
    return res.status(422).json({ message: 'École invalide.' });
  }
  if (!level || level < 1 || level > 6 || !section) {
    return res.status(422).json({ message: 'Niveau et section obligatoires.' });
  }

  record.school_class_id = resolveSchoolClassId(schoolId, level, section);
  if (req.file) {
    record.file_name = req.file.originalname;
    record.file_type = req.file.mimetype || 'application/octet-stream';
    record.buffer = req.file.buffer;
  }

  res.json({ id: record.id });
});

financeAndSchoolsRouter.delete('/school-calendar-imports/:id', authRequired, (req, res) => {
  const id = Number(req.params.id);
  const idx = store.schoolCalendarImports.findIndex((i) => i.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Emploi du temps introuvable.' });

  store.schoolCalendarImports.splice(idx, 1);
  res.json({ message: 'Emploi du temps supprimé avec succès' });
});

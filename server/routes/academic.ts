import { Router } from 'express';
import {
  formatStudent,
  formatTeacher,
  hasPermission,
  recordActivity,
  resolveTeacherForUser,
  round2,
  slugify,
  splitSubjects,
} from '../helpers';
import { authRequired, AuthedRequest, requirePermission, requireRole } from '../middleware';
import {
  AttendanceRecord,
  DaycareChildRecord,
  EnrollmentRecord,
  GroupRecord,
  ScheduleItemRecord,
  store,
  StudentRecord,
  SubjectRecord,
  TeacherRecord,
} from '../store';

export const academicRouter = Router();

// ─── Helper: resolve Group by numeric ID or decoded name ────────────────────
function resolveGroup(idOrName: string): GroupRecord | undefined {
  const decoded = decodeURIComponent(idOrName);
  if (/^\d+$/.test(decoded)) {
    const byId = store.groups.find((g) => g.id === Number(decoded));
    if (byId) return byId;
  }
  return store.groups.find((g) => g.name.toLowerCase() === decoded.toLowerCase());
}

function formatGroup(g: GroupRecord) {
  const subj = g.subject_id
    ? store.subjects.find((s) => s.id === g.subject_id) || null
    : g.subject
      ? store.subjects.find((s) => s.name.toLowerCase() === g.subject!.toLowerCase()) || null
      : null;
  const teacher = g.teacher_id ? store.teachers.find((t) => t.id === g.teacher_id) || null : null;
  const students = store.students.filter((s) => g.student_ids.includes(s.id));

  return {
    id: g.id,
    name: g.name,
    level: g.level,
    subject: g.subject || subj?.name || null,
    subject_id: g.subject_id ?? subj?.id ?? null,
    teacher_id: g.teacher_id,
    user_id: g.user_id,
    max_capacity: g.max_capacity,
    notes: g.notes,
    students_count: students.length,
    subject_obj: subj,
    teacher: teacher ? { id: teacher.id, name: teacher.name, subject: teacher.subject } : null,
    students: students.map((s) => ({
      id: s.id,
      name: s.name,
      level: s.level,
      group_name: s.group_name,
      phone: s.phone,
      parent_phone: s.parent_phone,
      status: s.status,
    })),
  };
}

function normalizeTeacherSubjects(body: any): string {
  const rawList: string[] = Array.isArray(body.subjects)
    ? body.subjects
    : splitSubjects(body.subject);
  const clean: string[] = [];
  for (const s of rawList) {
    const trimmed = String(s).trim();
    if (trimmed && !clean.includes(trimmed)) clean.push(trimmed);
  }
  return clean.join(', ');
}

function parseTimeRange(time?: string | null): [number | null, number | null] {
  if (!time) return [null, null];
  const m = time.match(/(\d{1,2})\s*[:hH]\s*(\d{2})\s*(?:-|–|—|à)\s*(\d{1,2})\s*[:hH]\s*(\d{2})/u);
  if (!m) return [null, null];
  const start = Number(m[1]) * 60 + Number(m[2]);
  const end = Number(m[3]) * 60 + Number(m[4]);
  return end > start ? [start, end] : [null, null];
}

function findScheduleConflict(
  candidate: Partial<ScheduleItemRecord>,
  ignoreId?: number
): { type: 'room' | 'group'; message: string } | null {
  const [start, end] = parseTimeRange(candidate.time);
  if (start === null || end === null || !candidate.day) return null;

  const same = (a?: string | null, b?: string | null) => {
    const sa = String(a || '').trim().toLowerCase();
    const sb = String(b || '').trim().toLowerCase();
    return sa !== '' && sa === sb;
  };

  let groupConflict: { type: 'group'; message: string } | null = null;

  for (const other of store.schedules) {
    if (ignoreId && other.id === ignoreId) continue;
    if (!same(other.day, candidate.day)) continue;
    const [s, e] = parseTimeRange(other.time);
    if (s === null || e === null || s >= end || e <= start) continue;

    if (same(candidate.room, other.room)) {
      return {
        type: 'room',
        message: `Conflit : la salle ${other.room} est déjà occupée le ${other.day} de ${other.time} (${other.subject}, ${other.teacher}). Choisissez une autre salle.`,
      };
    }

    if (!groupConflict && same(candidate.group_name, other.group_name)) {
      groupConflict = {
        type: 'group',
        message: `Attention : le groupe ${other.group_name} a déjà un cours le ${other.day} de ${other.time} (${other.subject}, ${other.teacher}).`,
      };
    }
  }

  return groupConflict;
}

// ─── TEACHERS ───────────────────────────────────────────────────────────────
academicRouter.get('/teachers', authRequired, (req: AuthedRequest, res) => {
  res.json(store.teachers.map((t) => formatTeacher(t, req.user)));
});

academicRouter.post('/teachers', authRequired, (req: AuthedRequest, res) => {
  const body = req.body || {};
  const subjectStr = normalizeTeacherSubjects(body);
  if (!body.name || !subjectStr || !body.phone || !body.email) {
    return res.status(422).json({ message: 'Nom, matière(s), téléphone et email sont obligatoires.' });
  }

  const isDirector = req.user?.role === 'director';
  const emailLower = String(body.email).trim().toLowerCase();
  const matchingUser = store.users.find(
    (u) => u.email.toLowerCase() === emailLower || u.name.toLowerCase() === String(body.name).trim().toLowerCase()
  );

  const teacher: TeacherRecord = {
    id: store.nextId('teacher'),
    user_id: matchingUser?.id ?? null,
    name: String(body.name).trim(),
    subject: subjectStr,
    phone: String(body.phone).trim(),
    email: String(body.email).trim(),
    weekly_lessons: Number(body.weeklyLessons ?? body.weekly_lessons ?? 4) || 4,
    remuneration_type: isDirector && ['percentage', 'fixed', 'fixed_center'].includes(body.remuneration_type)
      ? body.remuneration_type
      : 'percentage',
    rate_percentage: isDirector && body.rate_percentage != null ? Number(body.rate_percentage) : 70,
    salary_per_lesson: isDirector && body.salary_per_lesson != null ? Number(body.salary_per_lesson) : 25,
    fixed_student_rate: isDirector && body.fixed_student_rate != null ? Number(body.fixed_student_rate) : 15,
    center_fixed_per_student:
      isDirector && body.center_fixed_per_student != null ? Number(body.center_fixed_per_student) : 20,
    payout_status: 'pending',
    payout_date: null,
    payout_month: null,
    payout_amount: null,
    payout_by_user_id: null,
    payout_by_user_name: null,
  };

  store.teachers.push(teacher);
  recordActivity(req.user, 'teacher', 'created', `Nouvel enseignant ajouté : ${teacher.name} (${teacher.subject})`);

  res.status(201).json(formatTeacher(teacher, req.user));
});

academicRouter.get('/teachers/:id', authRequired, (req: AuthedRequest, res) => {
  const teacher = store.teachers.find((t) => t.id === Number(req.params.id));
  if (!teacher) return res.status(404).json({ message: 'Enseignant introuvable.' });
  res.json(formatTeacher(teacher, req.user));
});

academicRouter.put('/teachers/:id', authRequired, (req: AuthedRequest, res) => {
  const teacher = store.teachers.find((t) => t.id === Number(req.params.id));
  if (!teacher) return res.status(404).json({ message: 'Enseignant introuvable.' });

  const body = req.body || {};
  if (body.name !== undefined) teacher.name = String(body.name).trim();
  if (body.subjects !== undefined || body.subject !== undefined) {
    const subj = normalizeTeacherSubjects(body);
    if (subj) teacher.subject = subj;
  }
  if (body.phone !== undefined) teacher.phone = String(body.phone).trim();
  if (body.email !== undefined) teacher.email = String(body.email).trim();
  if (body.weeklyLessons !== undefined || body.weekly_lessons !== undefined) {
    teacher.weekly_lessons = Number(body.weeklyLessons ?? body.weekly_lessons) || teacher.weekly_lessons;
  }

  if (req.user?.role === 'director') {
    if (['percentage', 'fixed', 'fixed_center'].includes(body.remuneration_type)) {
      teacher.remuneration_type = body.remuneration_type;
    }
    if (body.rate_percentage !== undefined) {
      teacher.rate_percentage = body.rate_percentage === null ? 70 : Number(body.rate_percentage);
    }
    if (body.salary_per_lesson !== undefined) {
      teacher.salary_per_lesson = body.salary_per_lesson === null ? 25 : Number(body.salary_per_lesson);
    }
    if (body.fixed_student_rate !== undefined) {
      teacher.fixed_student_rate = body.fixed_student_rate === null ? 15 : Number(body.fixed_student_rate);
    }
    if (body.center_fixed_per_student !== undefined) {
      teacher.center_fixed_per_student =
        body.center_fixed_per_student === null ? 20 : Number(body.center_fixed_per_student);
    }
  }

  if (!teacher.user_id) {
    const matchingUser = store.users.find(
      (u) =>
        u.email.toLowerCase() === teacher.email.toLowerCase() ||
        u.name.toLowerCase() === teacher.name.toLowerCase()
    );
    if (matchingUser) teacher.user_id = matchingUser.id;
  }

  res.json(formatTeacher(teacher, req.user));
});

academicRouter.delete('/teachers/:id', authRequired, requirePermission('can_delete'), (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const idx = store.teachers.findIndex((t) => t.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Enseignant introuvable.' });

  const name = store.teachers[idx].name;
  store.teachers.splice(idx, 1);
  recordActivity(req.user, 'teacher', 'deleted', `Suppression de l'enseignant : ${name}`);
  res.json({ message: 'Enseignant supprimé avec succès' });
});

academicRouter.post('/teachers/:id/toggle-payout', authRequired, requireRole('director'), (req: AuthedRequest, res) => {
  const teacher = store.teachers.find((t) => t.id === Number(req.params.id));
  if (!teacher) return res.status(404).json({ message: 'Enseignant introuvable.' });

  const body = req.body || {};
  const newStatus: 'paid' | 'pending' =
    body.status === 'paid' || body.status === 'pending'
      ? body.status
      : teacher.payout_status === 'paid'
        ? 'pending'
        : 'paid';

  teacher.payout_status = newStatus;
  if (newStatus === 'paid') {
    const now = new Date();
    teacher.payout_date = body.date || now.toISOString().slice(0, 10);
    teacher.payout_month = body.month || teacher.payout_month || now.toISOString().slice(0, 7);
    if (body.amount !== undefined && body.amount !== null) {
      teacher.payout_amount = Number(body.amount);
    }
    teacher.payout_by_user_id = req.user?.id ?? 1;
    teacher.payout_by_user_name = req.user?.name ?? 'Directeur Fondateur';
  } else {
    teacher.payout_date = null;
    teacher.payout_by_user_id = null;
    teacher.payout_by_user_name = null;
  }

  recordActivity(
    req.user,
    'teacher',
    'payout',
    newStatus === 'paid'
      ? `Rémunération de ${teacher.name} marquée payée (${teacher.payout_amount ?? 0} TND remis)`
      : `Rémunération de ${teacher.name} remise en attente`
  );

  res.json({
    message:
      newStatus === 'paid'
        ? "Rémunération marquée comme payée (l'enseignant a pris son argent)."
        : 'Rémunération remise en attente.',
    teacher: formatTeacher(teacher, req.user),
  });
});

// ─── STUDENTS ───────────────────────────────────────────────────────────────
function syncStudentGroupMembership(student: StudentRecord, grpName: string | null | undefined, teacherId?: number | null) {
  if (!grpName) return;
  const trimmed = grpName.trim();
  if (!trimmed) return;
  let grp = store.groups.find((g) => g.name.toLowerCase() === trimmed.toLowerCase());
  if (!grp) {
    grp = {
      id: store.nextId('group'),
      name: trimmed,
      level: student.level,
      subject: null,
      subject_id: null,
      teacher_id: teacherId ?? null,
      user_id: null,
      max_capacity: 25,
      notes: null,
      student_ids: [],
    };
    store.groups.push(grp);
  }
  if (!grp.student_ids.includes(student.id)) {
    grp.student_ids.push(student.id);
  }
}

academicRouter.get('/students', authRequired, (_req, res) => {
  res.json(store.students.map((s) => formatStudent(s)));
});

academicRouter.post('/students', authRequired, (req: AuthedRequest, res) => {
  const body = req.body || {};
  if (!body.name || !body.level || !body.phone) {
    return res.status(422).json({ message: 'Nom, niveau et téléphone sont obligatoires.' });
  }

  const student: StudentRecord = {
    id: store.nextId('student'),
    name: String(body.name).trim(),
    level: String(body.level).trim(),
    group_name: body.group_name ? String(body.group_name).trim() : null,
    phone: String(body.phone).trim(),
    parent_phone: body.parent_phone ? String(body.parent_phone).trim() : null,
    status: body.status === 'inactive' ? 'inactive' : 'active',
    is_remise: Boolean(body.is_remise),
    remise: Number(body.remise || 0),
    discount_type: body.discount_type === 'percentage' ? 'percentage' : 'fixed',
    base_fee: Number(body.base_fee || 0),
    created_at: new Date().toISOString(),
  };

  store.students.push(student);

  if (Array.isArray(body.subjects)) {
    for (const sub of body.subjects) {
      const teacher = sub.teacher_id ? store.teachers.find((t) => t.id === Number(sub.teacher_id)) : null;
      const lessonRate = teacher?.salary_per_lesson ?? 35;
      const sessionsPerMonth = Number(sub.sessions_per_month) > 0 ? Number(sub.sessions_per_month) : 4;
      const pricePerSession = Number(sub.price_per_session) > 0 ? Number(sub.price_per_session) : lessonRate;
      const pricePerMonth =
        Number(sub.price_per_month) > 0 ? Number(sub.price_per_month) : pricePerSession * sessionsPerMonth;
      const grpName = sub.group_name ?? student.group_name;

      const enr: EnrollmentRecord = {
        id: store.nextId('enrollment'),
        student_id: student.id,
        subject_name: String(sub.subject || sub.subject_name || 'Matière').trim(),
        teacher_id: sub.teacher_id ? Number(sub.teacher_id) : null,
        group_name: grpName || null,
        sessions_per_month: sessionsPerMonth,
        price_per_month: pricePerMonth,
        price_per_session: pricePerSession,
      };
      store.enrollments.push(enr);
      syncStudentGroupMembership(student, grpName, enr.teacher_id);
    }
  }

  syncStudentGroupMembership(student, student.group_name);
  recordActivity(req.user, 'student', 'created', `Nouvel élève inscrit : ${student.name} (${student.level})`);

  res.status(201).json(formatStudent(student));
});

academicRouter.get('/students/:id', authRequired, (req, res) => {
  const student = store.students.find((s) => s.id === Number(req.params.id));
  if (!student) return res.status(404).json({ message: 'Élève introuvable.' });
  res.json(formatStudent(student));
});

academicRouter.put('/students/:id', authRequired, (req: AuthedRequest, res) => {
  const student = store.students.find((s) => s.id === Number(req.params.id));
  if (!student) return res.status(404).json({ message: 'Élève introuvable.' });

  const body = req.body || {};
  if (body.name !== undefined) student.name = String(body.name).trim();
  if (body.level !== undefined) student.level = String(body.level).trim();
  if (body.group_name !== undefined) student.group_name = body.group_name ? String(body.group_name).trim() : null;
  if (body.phone !== undefined) student.phone = String(body.phone).trim();
  if (body.parent_phone !== undefined) student.parent_phone = body.parent_phone ? String(body.parent_phone).trim() : null;
  if (body.status === 'active' || body.status === 'inactive') student.status = body.status;
  if (body.is_remise !== undefined) student.is_remise = Boolean(body.is_remise);
  if (body.remise !== undefined) student.remise = Number(body.remise || 0);
  if (body.discount_type === 'percentage' || body.discount_type === 'fixed') student.discount_type = body.discount_type;
  if (body.base_fee !== undefined) student.base_fee = Number(body.base_fee || 0);

  if (Array.isArray(body.subjects)) {
    store.enrollments = store.enrollments.filter((e) => e.student_id !== student.id);
    for (const sub of body.subjects) {
      const teacher = sub.teacher_id ? store.teachers.find((t) => t.id === Number(sub.teacher_id)) : null;
      const lessonRate = teacher?.salary_per_lesson ?? 35;
      const sessionsPerMonth = Number(sub.sessions_per_month) > 0 ? Number(sub.sessions_per_month) : 4;
      const pricePerSession = Number(sub.price_per_session) > 0 ? Number(sub.price_per_session) : lessonRate;
      const pricePerMonth =
        Number(sub.price_per_month) > 0 ? Number(sub.price_per_month) : pricePerSession * sessionsPerMonth;
      const grpName = sub.group_name ?? student.group_name;

      const enr: EnrollmentRecord = {
        id: store.nextId('enrollment'),
        student_id: student.id,
        subject_name: String(sub.subject || sub.subject_name || 'Matière').trim(),
        teacher_id: sub.teacher_id ? Number(sub.teacher_id) : null,
        group_name: grpName || null,
        sessions_per_month: sessionsPerMonth,
        price_per_month: pricePerMonth,
        price_per_session: pricePerSession,
      };
      store.enrollments.push(enr);
      syncStudentGroupMembership(student, grpName, enr.teacher_id);
    }
  }

  syncStudentGroupMembership(student, student.group_name);
  res.json(formatStudent(student));
});

academicRouter.delete('/students/:id', authRequired, requirePermission('can_delete'), (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const idx = store.students.findIndex((s) => s.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Élève introuvable.' });

  const name = store.students[idx].name;
  store.students.splice(idx, 1);
  store.enrollments = store.enrollments.filter((e) => e.student_id !== id);
  for (const grp of store.groups) {
    grp.student_ids = grp.student_ids.filter((sid) => sid !== id);
  }
  recordActivity(req.user, 'student', 'deleted', `Suppression de l'élève : ${name}`);
  res.json({ message: 'Élève supprimé avec succès' });
});

// ─── SUBJECTS ───────────────────────────────────────────────────────────────
function formatSubject(s: SubjectRecord) {
  const teachersCount = store.teachers.filter((t) =>
    splitSubjects(t.subject).some((sub) => sub.toLowerCase() === s.name.toLowerCase())
  ).length;
  const enrollmentsCount = store.enrollments.filter(
    (e) => e.subject_name.toLowerCase() === s.name.toLowerCase()
  ).length;
  return {
    id: s.id,
    name: s.name,
    slug: s.slug,
    color: s.color,
    is_active: Boolean(s.is_active),
    teachers_count: teachersCount,
    enrollments_count: enrollmentsCount,
  };
}

academicRouter.get('/subjects', authRequired, (_req, res) => {
  const list = [...store.subjects].sort((a, b) => a.name.localeCompare(b.name)).map(formatSubject);
  res.json(list);
});

academicRouter.get('/subjects/:id', authRequired, (req, res) => {
  const subj = store.subjects.find((s) => s.id === Number(req.params.id));
  if (!subj) return res.status(404).json({ message: 'Matière introuvable.' });
  res.json(formatSubject(subj));
});

academicRouter.post('/subjects', authRequired, requireRole('director'), (req, res) => {
  const { name, color, is_active } = req.body || {};
  if (!name || !String(name).trim()) {
    return res.status(422).json({ message: 'Le nom de la matière est obligatoire.' });
  }
  const cleanName = String(name).trim();
  const slug = slugify(cleanName);
  if (store.subjects.some((s) => s.slug === slug)) {
    return res.status(422).json({ message: `Une matière avec un nom similaire existe déjà (slug : ${slug}).` });
  }
  const subj: SubjectRecord = {
    id: store.nextId('subject'),
    name: cleanName,
    slug,
    color: typeof color === 'string' && /^#[0-9A-Fa-f]{6}$/.test(color) ? color : '#6366f1',
    is_active: is_active === undefined ? true : Boolean(is_active),
  };
  store.subjects.push(subj);
  res.status(201).json(formatSubject(subj));
});

academicRouter.put('/subjects/:id', authRequired, requireRole('director'), (req, res) => {
  const subj = store.subjects.find((s) => s.id === Number(req.params.id));
  if (!subj) return res.status(404).json({ message: 'Matière introuvable.' });

  const { name, color, is_active } = req.body || {};
  if (name !== undefined && String(name).trim()) {
    const cleanName = String(name).trim();
    const newSlug = slugify(cleanName);
    if (newSlug !== subj.slug && store.subjects.some((s) => s.slug === newSlug)) {
      return res.status(422).json({ message: `Une matière avec un nom similaire existe déjà (slug : ${newSlug}).` });
    }
    subj.name = cleanName;
    subj.slug = newSlug;
  }
  if (typeof color === 'string' && /^#[0-9A-Fa-f]{6}$/.test(color)) subj.color = color;
  if (is_active !== undefined) subj.is_active = Boolean(is_active);

  res.json(formatSubject(subj));
});

academicRouter.delete('/subjects/:id', authRequired, requirePermission('can_delete'), (req: AuthedRequest, res) => {
  if (req.user?.role !== 'director') {
    return res.status(403).json({ message: 'Accès réservé au directeur.' });
  }
  const id = Number(req.params.id);
  const idx = store.subjects.findIndex((s) => s.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Matière introuvable.' });

  const subj = store.subjects[idx];
  const enrollmentsCount = store.enrollments.filter(
    (e) => e.subject_name.toLowerCase() === subj.name.toLowerCase()
  ).length;
  if (enrollmentsCount > 0) {
    return res.status(422).json({
      message: `Impossible de supprimer : ${enrollmentsCount} inscription(s) sont liées à cette matière.`,
    });
  }

  store.subjects.splice(idx, 1);
  res.json({ message: 'Matière supprimée avec succès.' });
});

// ─── GROUPS ─────────────────────────────────────────────────────────────────
academicRouter.get('/groups', authRequired, (req: AuthedRequest, res) => {
  let groups = [...store.groups];
  if (req.user?.role === 'teacher') {
    const teacher = resolveTeacherForUser(req.user);
    groups = groups.filter(
      (g) => g.user_id === req.user!.id || (teacher && g.teacher_id === teacher.id)
    );
  }
  groups.sort((a, b) => a.name.localeCompare(b.name));
  res.json(groups.map(formatGroup));
});

academicRouter.post('/groups', authRequired, (req: AuthedRequest, res) => {
  const body = req.body || {};
  if (!body.name || !String(body.name).trim()) {
    return res.status(422).json({ message: 'Le nom du groupe est obligatoire.' });
  }

  const user = req.user!;
  let teacherId: number | null = null;
  if (body.teacher_id) {
    const t =
      store.teachers.find((x) => x.id === Number(body.teacher_id)) ||
      store.teachers.find((x) => x.user_id === Number(body.teacher_id));
    teacherId = t ? t.id : Number(body.teacher_id);
  } else if (user.role === 'teacher') {
    const linked = resolveTeacherForUser(user);
    teacherId = linked?.id ?? null;
  }

  let subjectId: number | null = body.subject_id ? Number(body.subject_id) : null;
  let subjectName: string | null = body.subject ? String(body.subject).trim() : null;
  if (subjectName && !subjectId) {
    const matched = store.subjects.find((s) => s.name.toLowerCase() === subjectName!.toLowerCase());
    if (matched) subjectId = matched.id;
  } else if (subjectId && !subjectName) {
    const matched = store.subjects.find((s) => s.id === subjectId);
    if (matched) subjectName = matched.name;
  }

  const grp: GroupRecord = {
    id: store.nextId('group'),
    name: String(body.name).trim(),
    level: body.level ? String(body.level).trim() : null,
    subject: subjectName,
    subject_id: subjectId,
    teacher_id: teacherId,
    user_id: user.id,
    max_capacity: Number(body.max_capacity) > 0 ? Number(body.max_capacity) : 25,
    notes: body.notes ? String(body.notes) : null,
    student_ids: [],
  };
  store.groups.push(grp);
  recordActivity(user, 'group', 'group_created', `Création du groupe ${grp.name}`);

  const formatted = formatGroup(grp);
  res.status(201).json({
    message: `Le groupe ${grp.name} a été créé avec succès.`,
    group: formatted,
    id: grp.id,
    name: grp.name,
    level: grp.level,
    subject: formatted.subject,
    teacher_id: grp.teacher_id,
  });
});

academicRouter.get('/groups/:id', authRequired, (req, res) => {
  const grp = resolveGroup(String(req.params.id));
  if (!grp) return res.status(404).json({ message: 'Groupe introuvable.' });
  res.json(formatGroup(grp));
});

academicRouter.put('/groups/:id', authRequired, (req: AuthedRequest, res) => {
  const grp = resolveGroup(String(req.params.id));
  if (!grp) return res.status(404).json({ message: 'Groupe introuvable.' });

  const body = req.body || {};
  const oldName = grp.name;

  if (body.name !== undefined && String(body.name).trim()) grp.name = String(body.name).trim();
  if (body.level !== undefined) grp.level = body.level ? String(body.level).trim() : null;
  if (body.subject !== undefined) grp.subject = body.subject ? String(body.subject).trim() : null;
  if (body.subject_id !== undefined) grp.subject_id = body.subject_id ? Number(body.subject_id) : null;
  if (grp.subject && !grp.subject_id) {
    const m = store.subjects.find((s) => s.name.toLowerCase() === grp.subject!.toLowerCase());
    if (m) grp.subject_id = m.id;
  }
  if (body.max_capacity !== undefined) grp.max_capacity = Number(body.max_capacity) || grp.max_capacity;
  if (body.notes !== undefined) grp.notes = body.notes ? String(body.notes) : null;

  if (grp.name !== oldName) {
    for (const sc of store.schedules) {
      if (sc.group_name === oldName) sc.group_name = grp.name;
    }
    for (const st of store.students) {
      if (st.group_name === oldName) st.group_name = grp.name;
    }
  }

  recordActivity(req.user, 'group', 'group_updated', `Modification du groupe ${grp.name}`);

  res.json({
    message: `Le groupe ${grp.name} a été mis à jour avec succès.`,
    group: formatGroup(grp),
  });
});

academicRouter.delete('/groups/:id', authRequired, (req: AuthedRequest, res) => {
  const grp = resolveGroup(String(req.params.id));
  if (!grp) return res.status(404).json({ message: 'Groupe introuvable.' });

  const name = grp.name;
  for (const sc of store.schedules) {
    if (sc.group_name === name) sc.group_name = null;
  }
  store.groups = store.groups.filter((g) => g.id !== grp.id);
  recordActivity(req.user, 'group', 'group_deleted', `Suppression du groupe ${name}`);

  res.json({ message: `Le groupe ${name} a été supprimé avec succès.` });
});

academicRouter.post('/groups/:group/students', authRequired, (req: AuthedRequest, res) => {
  const grp = resolveGroup(String(req.params.group));
  if (!grp) return res.status(404).json({ message: 'Groupe introuvable.' });

  const studentId = Number(req.body?.student_id);
  if (!studentId) return res.status(422).json({ message: "L'identifiant de l'élève est obligatoire." });

  const student = store.students.find((s) => s.id === studentId);
  if (!student) return res.status(422).json({ message: "Cet élève n'existe pas dans la base de données." });

  if (grp.student_ids.includes(studentId)) {
    return res.status(409).json({
      status: 'warning',
      message: `L'élève ${student.name} est déjà inscrit dans le groupe ${grp.name}.`,
      group: { id: grp.id, name: grp.name },
      student_id: studentId,
    });
  }

  grp.student_ids.push(studentId);
  student.group_name = grp.name;
  recordActivity(req.user, 'group', 'student_attached', `Élève ${student.name} rattaché au groupe ${grp.name}`);

  res.json({
    status: 'success',
    message: `L'élève ${student.name} a été rattaché avec succès au groupe ${grp.name}.`,
    group: { id: grp.id, name: grp.name, students_count: grp.student_ids.length },
    student: { id: student.id, name: student.name, level: student.level, phone: student.phone },
  });
});

academicRouter.delete('/groups/:group/students/:student', authRequired, (req, res) => {
  const grp = resolveGroup(String(req.params.group));
  if (!grp) return res.status(404).json({ message: 'Groupe introuvable.' });

  const studentId = Number(req.params.student);
  const student = store.students.find((s) => s.id === studentId);
  if (!student) return res.status(404).json({ message: 'Élève introuvable.' });

  const isAttached = grp.student_ids.includes(studentId);
  if (!isAttached) {
    if (student.group_name === grp.name) {
      student.group_name = null;
      return res.json({ message: `L'élève ${student.name} a été retiré du groupe ${grp.name}.` });
    }
    return res.status(404).json({ message: `L'élève ${student.name} n'appartient pas au groupe ${grp.name}.` });
  }

  grp.student_ids = grp.student_ids.filter((id) => id !== studentId);
  if (student.group_name === grp.name) student.group_name = null;

  res.json({ message: `L'élève ${student.name} a été retiré du groupe ${grp.name}.` });
});

// ─── SCHEDULES ──────────────────────────────────────────────────────────────
academicRouter.get('/schedules', authRequired, (_req, res) => {
  res.json(store.schedules);
});

academicRouter.post('/schedules', authRequired, (req: AuthedRequest, res) => {
  const body = req.body || {};
  if (!body.day || !body.time || !body.subject || !body.level || !body.room) {
    return res.status(422).json({ message: 'Jour, horaire, matière, niveau et salle sont obligatoires.' });
  }

  const force = Boolean(body.force);
  const candidate: Partial<ScheduleItemRecord> = {
    day: String(body.day).trim(),
    time: String(body.time).trim(),
    subject: String(body.subject).trim(),
    teacher: body.teacher ? String(body.teacher).trim() : req.user?.name || 'Enseignant',
    level: String(body.level).trim(),
    room: String(body.room).trim(),
    group_name: body.group_name ? String(body.group_name).trim() : null,
  };

  const conflict = findScheduleConflict(candidate);
  if (conflict) {
    if (conflict.type === 'room') return res.status(422).json({ message: conflict.message });
    if (!force) return res.status(409).json({ message: conflict.message });
  }

  const item: ScheduleItemRecord = {
    id: store.nextId('schedule'),
    day: candidate.day!,
    time: candidate.time!,
    subject: candidate.subject!,
    teacher: candidate.teacher!,
    level: candidate.level!,
    room: candidate.room!,
    group_name: candidate.group_name ?? null,
  };
  store.schedules.push(item);

  recordActivity(
    req.user,
    'schedule',
    'created',
    `Cours planifié : ${item.subject} par ${item.teacher} (${item.day} de ${item.time}, Salle ${item.room})`
  );

  res.status(201).json(item);
});

academicRouter.get('/schedules/:id', authRequired, (req, res) => {
  const item = store.schedules.find((s) => s.id === Number(req.params.id));
  if (!item) return res.status(404).json({ message: 'Créneau introuvable.' });
  res.json(item);
});

academicRouter.put('/schedules/:id', authRequired, (req: AuthedRequest, res) => {
  const item = store.schedules.find((s) => s.id === Number(req.params.id));
  if (!item) return res.status(404).json({ message: 'Créneau introuvable.' });

  const body = req.body || {};
  const force = Boolean(body.force);
  const candidate: ScheduleItemRecord = {
    ...item,
    ...(body.day !== undefined ? { day: String(body.day).trim() } : {}),
    ...(body.time !== undefined ? { time: String(body.time).trim() } : {}),
    ...(body.subject !== undefined ? { subject: String(body.subject).trim() } : {}),
    ...(body.teacher !== undefined ? { teacher: String(body.teacher).trim() } : {}),
    ...(body.level !== undefined ? { level: String(body.level).trim() } : {}),
    ...(body.room !== undefined ? { room: String(body.room).trim() } : {}),
    ...(body.group_name !== undefined ? { group_name: body.group_name ? String(body.group_name).trim() : null } : {}),
  };

  const conflict = findScheduleConflict(candidate, item.id);
  if (conflict) {
    if (conflict.type === 'room') return res.status(422).json({ message: conflict.message });
    if (!force) return res.status(409).json({ message: conflict.message });
  }

  Object.assign(item, candidate);
  recordActivity(req.user, 'schedule', 'updated', `Cours modifié : ${item.subject} (${item.day} ${item.time})`);
  res.json(item);
});

academicRouter.delete('/schedules/:id', authRequired, (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const idx = store.schedules.findIndex((s) => s.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Créneau introuvable.' });

  const { subject, day } = store.schedules[idx];
  store.schedules.splice(idx, 1);
  recordActivity(req.user, 'schedule', 'deleted', `Suppression du cours ${subject} (${day})`);
  res.json({ message: 'Cours supprimé avec succès' });
});

// ─── ATTENDANCES ────────────────────────────────────────────────────────────
function formatAttendance(a: AttendanceRecord) {
  const student = store.students.find((s) => s.id === a.student_id) || null;
  const teacher = a.teacher_id ? store.teachers.find((t) => t.id === a.teacher_id) || null : null;
  return {
    ...a,
    student: student ? { id: student.id, name: student.name, level: student.level, group_name: student.group_name } : null,
    teacher: teacher ? { id: teacher.id, name: teacher.name, subject: teacher.subject } : null,
  };
}

academicRouter.get('/attendances', authRequired, (req, res) => {
  let list = [...store.attendances];
  if (req.query.student_id) list = list.filter((a) => a.student_id === Number(req.query.student_id));
  if (req.query.teacher_id) list = list.filter((a) => a.teacher_id === Number(req.query.teacher_id));
  if (req.query.group_name) list = list.filter((a) => a.group_name === String(req.query.group_name));
  if (req.query.subject_name) list = list.filter((a) => a.subject_name === String(req.query.subject_name));
  if (req.query.session_date) list = list.filter((a) => a.session_date === String(req.query.session_date));
  list.sort((a, b) => b.session_date.localeCompare(a.session_date) || b.id - a.id);
  res.json(list.map(formatAttendance));
});

academicRouter.post('/attendances', authRequired, (req, res) => {
  const body = req.body || {};
  if (!body.student_id || !body.subject_name || !body.session_date || !body.status) {
    return res.status(422).json({ message: 'Élève, matière, date et statut sont obligatoires.' });
  }

  let att = store.attendances.find(
    (a) =>
      a.student_id === Number(body.student_id) &&
      a.subject_name === String(body.subject_name) &&
      a.session_date === String(body.session_date)
  );

  if (att) {
    att.teacher_id = body.teacher_id ? Number(body.teacher_id) : att.teacher_id;
    att.group_name = body.group_name ?? att.group_name;
    att.status = body.status;
    att.notes = body.notes ?? null;
  } else {
    att = {
      id: store.nextId('attendance'),
      student_id: Number(body.student_id),
      teacher_id: body.teacher_id ? Number(body.teacher_id) : null,
      group_name: body.group_name ?? null,
      subject_name: String(body.subject_name),
      session_date: String(body.session_date),
      status: body.status,
      notes: body.notes ?? null,
      created_at: new Date().toISOString(),
    };
    store.attendances.push(att);
  }

  res.status(201).json(formatAttendance(att));
});

academicRouter.post('/attendances/batch', authRequired, (req: AuthedRequest, res) => {
  const { session_date, subject_name, teacher_id, group_name, records } = req.body || {};
  if (!session_date || !subject_name || !Array.isArray(records)) {
    return res.status(422).json({ message: 'Date, matière et liste des élèves sont obligatoires.' });
  }

  let count = 0;
  for (const r of records) {
    const sid = Number(r.student_id);
    if (!sid) continue;
    const existing = store.attendances.find(
      (a) => a.student_id === sid && a.subject_name === subject_name && a.session_date === session_date
    );
    if (existing) {
      existing.teacher_id = teacher_id ? Number(teacher_id) : existing.teacher_id;
      existing.group_name = group_name ?? existing.group_name;
      existing.status = r.status;
      existing.notes = r.notes ?? null;
    } else {
      store.attendances.push({
        id: store.nextId('attendance'),
        student_id: sid,
        teacher_id: teacher_id ? Number(teacher_id) : null,
        group_name: group_name ?? null,
        subject_name: String(subject_name),
        session_date: String(session_date),
        status: r.status,
        notes: r.notes ?? null,
        created_at: new Date().toISOString(),
      });
    }
    count++;
  }

  recordActivity(
    req.user,
    'attendance',
    'recorded',
    `Appel de présence validé pour ${session_date} (${group_name || 'N/A'}, ${subject_name} : ${count} élèves pointés)`
  );

  res.json({ message: 'Présences enregistrées avec succès', count });
});

academicRouter.get('/attendances/summary/:studentId', authRequired, (req, res) => {
  const student = store.students.find((s) => s.id === Number(req.params.studentId));
  if (!student) return res.status(404).json({ message: 'Élève introuvable.' });

  const formatted = formatStudent(student);
  const attendances = store.attendances
    .filter((a) => a.student_id === student.id)
    .sort((a, b) => b.session_date.localeCompare(a.session_date));

  res.json({
    student: {
      id: student.id,
      name: student.name,
      level: student.level,
      group_name: student.group_name,
      is_remise: Boolean(student.is_remise),
      remise: Number(student.remise || 0),
      discount_type: student.discount_type || 'fixed',
    },
    attendance_count: {
      total: formatted.attendance_stats.total,
      present: formatted.attendance_stats.present,
      absent: formatted.attendance_stats.absent,
    },
    calculation: {
      gross_fee: round2(formatted.calculated_fee.gross),
      discount_amount: round2(formatted.calculated_fee.discount),
      amount_to_pay: round2(formatted.calculated_fee.netPayable),
      is_default_price: formatted.calculated_fee.isDefaultPrice,
    },
    attendances,
  });
});

academicRouter.delete('/attendances/:id', authRequired, requirePermission('can_delete'), (req, res) => {
  const id = Number(req.params.id);
  const idx = store.attendances.findIndex((a) => a.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Présence introuvable.' });
  store.attendances.splice(idx, 1);
  res.json({ message: 'Présence supprimée' });
});

// ─── DAYCARE ────────────────────────────────────────────────────────────────
function formatDaycare(c: DaycareChildRecord) {
  return {
    id: c.id,
    name: c.name,
    age: c.age,
    parentName: c.parent_name,
    phone: c.phone,
    schedule: c.schedule,
    status: c.status,
    monthlyFee: Number(c.monthly_fee || 0),
    startDate: c.start_date ? String(c.start_date).slice(0, 10) : null,
  };
}

academicRouter.get('/daycare', authRequired, (_req, res) => {
  res.json(store.daycareChildren.map(formatDaycare));
});

academicRouter.post('/daycare', authRequired, (req: AuthedRequest, res) => {
  const body = req.body || {};
  if (!body.name || body.age === undefined || !body.parentName || !body.phone || !body.schedule) {
    return res.status(422).json({ message: 'Tous les champs obligatoires doivent être renseignés.' });
  }

  const child: DaycareChildRecord = {
    id: store.nextId('daycare'),
    name: String(body.name).trim(),
    age: Number(body.age),
    parent_name: String(body.parentName).trim(),
    phone: String(body.phone).trim(),
    schedule: String(body.schedule).trim(),
    status: body.status === 'absent' ? 'absent' : 'present',
    monthly_fee: Number(body.monthlyFee ?? 0),
    start_date: body.startDate ? String(body.startDate).slice(0, 10) : new Date().toISOString().slice(0, 10),
    created_at: new Date().toISOString(),
  };
  store.daycareChildren.push(child);

  recordActivity(req.user, 'daycare', 'created', `Inscription en garderie : ${child.name} (${child.age} ans)`);
  res.status(201).json(formatDaycare(child));
});

academicRouter.get('/daycare/:id', authRequired, (req, res) => {
  const child = store.daycareChildren.find((c) => c.id === Number(req.params.id));
  if (!child) return res.status(404).json({ message: 'Enfant introuvable.' });
  res.json(formatDaycare(child));
});

academicRouter.put('/daycare/:id', authRequired, (req: AuthedRequest, res) => {
  const child = store.daycareChildren.find((c) => c.id === Number(req.params.id));
  if (!child) return res.status(404).json({ message: 'Enfant introuvable.' });

  const body = req.body || {};
  if (body.name !== undefined) child.name = String(body.name).trim();
  if (body.age !== undefined) child.age = Number(body.age);
  if (body.parentName !== undefined) child.parent_name = String(body.parentName).trim();
  if (body.phone !== undefined) child.phone = String(body.phone).trim();
  if (body.schedule !== undefined) child.schedule = String(body.schedule).trim();
  if (body.status === 'present' || body.status === 'absent') child.status = body.status;
  if (body.monthlyFee !== undefined) child.monthly_fee = body.monthlyFee === null ? 0 : Number(body.monthlyFee);
  if (body.startDate !== undefined && body.startDate) child.start_date = String(body.startDate).slice(0, 10);

  recordActivity(req.user, 'daycare', 'updated', `Mise à jour enfant de garderie : ${child.name}`);
  res.json(formatDaycare(child));
});

academicRouter.delete('/daycare/:id', authRequired, requirePermission('can_delete'), (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const idx = store.daycareChildren.findIndex((c) => c.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Enfant introuvable.' });

  const name = store.daycareChildren[idx].name;
  store.daycareChildren.splice(idx, 1);
  recordActivity(req.user, 'daycare', 'deleted', `Suppression de l'enfant en garderie : ${name}`);
  res.json({ message: 'Enfant de garde supprimé avec succès' });
});

// ─── TEACHER PORTAL (/api/teacher/*) ────────────────────────────────────────
const teacherPortalAuth = [authRequired, requireRole('teacher', 'director', 'secretary')] as const;

academicRouter.get('/teacher/profile', ...teacherPortalAuth, (req: AuthedRequest, res) => {
  const user = req.user!;
  const explicitId = req.query.teacher_id ? Number(req.query.teacher_id) : null;
  const teacher = resolveTeacherForUser(user, explicitId);

  if (!teacher) {
    return res.json({
      user: { id: user.id, name: user.name, email: user.email, role: user.role },
      teacher: null,
      is_linked: false,
      message: 'Aucune fiche enseignant liée à ce compte utilisateur.',
    });
  }

  const formatted = formatTeacher(teacher, user);
  const subjectsList = splitSubjects(teacher.subject);
  const groupNames = new Set(
    store.enrollments
      .filter((e) => e.teacher_id === teacher.id && e.group_name)
      .map((e) => e.group_name!)
  );
  const scheduledCount = store.schedules.filter((s) =>
    s.teacher.toLowerCase().includes(teacher.name.toLowerCase())
  ).length;

  formatted.subjects_list = subjectsList;
  formatted.groups_count = groupNames.size;
  formatted.scheduled_lessons_count = scheduledCount;

  res.json({
    user: { id: user.id, name: user.name, email: user.email, role: user.role },
    teacher: formatted,
    is_linked: true,
  });
});

academicRouter.get('/teacher/schedule', ...teacherPortalAuth, (req: AuthedRequest, res) => {
  const explicitId = req.query.teacher_id ? Number(req.query.teacher_id) : null;
  const teacher = resolveTeacherForUser(req.user, explicitId);
  if (!teacher) return res.json([]);

  const tName = teacher.name.trim().toLowerCase();
  let items = store.schedules.filter((s) => s.teacher.toLowerCase().includes(tName));
  if (items.length === 0 && teacher.subject) {
    const subjs = splitSubjects(teacher.subject).map((s) => s.toLowerCase());
    items = store.schedules.filter((s) => subjs.includes(s.subject.toLowerCase()));
  }

  const dayOrder: Record<string, number> = {
    Lundi: 1,
    Mardi: 2,
    Mercredi: 3,
    Jeudi: 4,
    Vendredi: 5,
    Samedi: 6,
    Dimanche: 7,
  };
  items.sort((a, b) => (dayOrder[a.day] ?? 99) - (dayOrder[b.day] ?? 99) || a.time.localeCompare(b.time));
  res.json(items);
});

academicRouter.get('/teacher/groups', ...teacherPortalAuth, (req: AuthedRequest, res) => {
  const explicitId = req.query.teacher_id ? Number(req.query.teacher_id) : null;
  const teacher = resolveTeacherForUser(req.user, explicitId);
  if (!teacher) return res.json([]);

  const user = req.user!;
  const enrollmentGroups = store.enrollments.filter((e) => e.teacher_id === teacher.id && e.group_name);
  const scheduleGroups = store.schedules.filter(
    (s) => s.teacher.toLowerCase().includes(teacher.name.toLowerCase()) && s.group_name
  );
  const directGroups = store.groups.filter((g) => g.user_id === user.id || g.teacher_id === teacher.id);

  const groupNames = Array.from(
    new Set([
      ...enrollmentGroups.map((e) => e.group_name!),
      ...scheduleGroups.map((s) => s.group_name!),
      ...directGroups.map((g) => g.name),
    ])
  );

  const result = groupNames.map((gName) => {
    let matched = store.groups.find((g) => g.name.toLowerCase() === gName.toLowerCase());
    if (!matched) {
      matched = {
        id: store.nextId('group'),
        name: gName,
        level: 'Secondaire',
        subject: null,
        subject_id: null,
        teacher_id: teacher.id,
        user_id: user.id,
        max_capacity: 25,
        notes: null,
        student_ids: [],
      };
      store.groups.push(matched);
    }

    const enrolledIds = enrollmentGroups.filter((e) => e.group_name === gName).map((e) => e.student_id);
    const fallbackIds =
      enrolledIds.length === 0 ? store.students.filter((s) => s.group_name === gName).map((s) => s.id) : [];
    const studentIds = Array.from(new Set([...enrolledIds, ...fallbackIds, ...matched.student_ids]));

    const directSubj =
      matched.subject ||
      (matched.subject_id ? store.subjects.find((s) => s.id === matched!.subject_id)?.name : null);

    const subjects = Array.from(
      new Set(
        [
          ...enrollmentGroups.filter((e) => e.group_name === gName).map((e) => e.subject_name),
          ...scheduleGroups.filter((s) => s.group_name === gName).map((s) => s.subject),
          ...(directSubj ? [directSubj] : []),
        ].filter(Boolean)
      )
    );

    let levels = Array.from(
      new Set(
        [
          ...scheduleGroups.filter((s) => s.group_name === gName).map((s) => s.level),
          ...(matched.level ? [matched.level] : []),
        ].filter(Boolean)
      )
    );
    if (levels.length === 0 && studentIds.length > 0) {
      levels = Array.from(
        new Set(store.students.filter((s) => studentIds.includes(s.id)).map((s) => s.level).filter(Boolean))
      );
    }

    const slots = scheduleGroups
      .filter((s) => s.group_name === gName)
      .map((s) => ({ day: s.day, time: s.time, room: s.room, subject: s.subject }));

    return {
      id: matched.id,
      group_name: gName,
      student_count: studentIds.length,
      subjects: subjects.length > 0 ? subjects : [splitSubjects(teacher.subject)[0] || 'Cours'],
      levels,
      level: levels.join(', ') || 'Non spécifié',
      schedule_slots: slots,
    };
  });

  result.sort((a, b) => a.group_name.localeCompare(b.group_name));
  res.json(result);
});

academicRouter.get('/teacher/groups/:group/students', ...teacherPortalAuth, (req: AuthedRequest, res) => {
  const explicitId = req.query.teacher_id ? Number(req.query.teacher_id) : null;
  const teacher = resolveTeacherForUser(req.user, explicitId);
  const decodedGroup = decodeURIComponent(String(req.params.group));

  const directGroup = resolveGroup(String(req.params.group));
  const groupNameToMatch = directGroup ? directGroup.name : decodedGroup;

  const enrolledIds = teacher
    ? store.enrollments
        .filter((e) => e.teacher_id === teacher.id && e.group_name === groupNameToMatch)
        .map((e) => e.student_id)
    : [];
  const directStudentIds = directGroup ? directGroup.student_ids : [];
  const groupNameStudentIds = store.students
    .filter((s) => s.group_name === groupNameToMatch)
    .map((s) => s.id);

  const allIds = new Set([...enrolledIds, ...directStudentIds, ...groupNameStudentIds]);
  if (allIds.size === 0) return res.json([]);

  const dateParam = String(req.query.session_date || new Date().toISOString().slice(0, 10));
  const subjectParam = req.query.subject_name ? String(req.query.subject_name) : null;

  const students = store.students
    .filter((s) => allIds.has(s.id))
    .sort((a, b) => a.name.localeCompare(b.name))
    .map((s) => {
      const attList = store.attendances.filter(
        (a) => a.student_id === s.id && (!teacher || a.teacher_id === teacher.id)
      );
      const total = attList.length;
      const presents = attList.filter((a) => a.status === 'present').length;
      const absents = attList.filter((a) => a.status === 'absent').length;

      const existing = store.attendances.find(
        (a) =>
          a.student_id === s.id &&
          a.session_date === dateParam &&
          (!subjectParam || a.subject_name === subjectParam)
      );

      return {
        id: s.id,
        name: s.name,
        level: s.level,
        group_name: s.group_name,
        phone: s.phone,
        parent_phone: s.parent_phone,
        stats: {
          total_sessions: total,
          present_count: presents,
          absent_count: absents,
          attendance_rate: total > 0 ? Math.round((presents / total) * 100) : 100,
        },
        current_status: existing?.status ?? 'present',
        current_notes: existing?.notes ?? '',
      };
    });

  res.json(students);
});

academicRouter.post('/teacher/attendance/batch', ...teacherPortalAuth, (req: AuthedRequest, res) => {
  const teacher = resolveTeacherForUser(req.user);
  const { session_date, subject_name, group_name, records } = req.body || {};
  if (!session_date || !subject_name || !Array.isArray(records) || records.length === 0) {
    return res.status(422).json({ message: 'Date, matière et liste des élèves sont obligatoires.' });
  }

  let count = 0;
  for (const r of records) {
    const sid = Number(r.student_id);
    if (!sid) continue;
    const existing = store.attendances.find(
      (a) => a.student_id === sid && a.subject_name === subject_name && a.session_date === session_date
    );
    if (existing) {
      existing.teacher_id = teacher?.id ?? existing.teacher_id;
      existing.group_name = group_name ?? existing.group_name;
      existing.status = r.status;
      existing.notes = r.notes ?? null;
    } else {
      store.attendances.push({
        id: store.nextId('attendance'),
        student_id: sid,
        teacher_id: teacher?.id ?? null,
        group_name: group_name ?? null,
        subject_name: String(subject_name),
        session_date: String(session_date),
        status: r.status,
        notes: r.notes ?? null,
        created_at: new Date().toISOString(),
      });
    }
    count++;
  }

  const teacherName = teacher ? teacher.name : req.user!.name;
  recordActivity(
    req.user,
    'attendance',
    'recorded',
    `Appel validé par l'enseignant ${teacherName} pour le ${session_date} (${group_name || ''}, ${subject_name} : ${count} élèves pointés)`
  );

  res.json({
    message: 'Présences enregistrées avec succès.',
    session_date,
    subject_name,
    group_name: group_name ?? null,
    count,
  });
});

academicRouter.get('/teacher/attendances', ...teacherPortalAuth, (req: AuthedRequest, res) => {
  const teacher = resolveTeacherForUser(req.user);
  let list = [...store.attendances];
  if (teacher) list = list.filter((a) => a.teacher_id === teacher.id);
  if (req.query.session_date) list = list.filter((a) => a.session_date === String(req.query.session_date));
  if (req.query.group_name) list = list.filter((a) => a.group_name === String(req.query.group_name));
  if (req.query.subject_name) list = list.filter((a) => a.subject_name === String(req.query.subject_name));

  list.sort((a, b) => b.session_date.localeCompare(a.session_date) || b.id - a.id);
  res.json(list.slice(0, 100).map(formatAttendance));
});

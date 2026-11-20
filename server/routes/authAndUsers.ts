import bcrypt from 'bcryptjs';
import crypto from 'crypto';
import { Router } from 'express';
import {
  formatTeacher,
  getEffectivePermissions,
  hasPermission,
  recordActivity,
  resolveTeacherForUser,
  round2,
} from '../helpers';
import { authRequired, AuthedRequest, requireRole } from '../middleware';
import {
  DEFAULT_PERSONNEL_PERMISSIONS,
  DEFAULT_TEACHER_PERMISSIONS,
  store,
  UserRecord,
  VALID_GUIDE_KEYS,
} from '../store';

export const authAndUsersRouter = Router();

authAndUsersRouter.get('/health', (_req, res) => {
  res.json({ status: 'ok', app: 'Study Center API' });
});

authAndUsersRouter.post('/login', (req, res) => {
  const { role, email, password } = req.body || {};

  let user: UserRecord | undefined;

  if (role && ['director', 'secretary', 'teacher'].includes(role)) {
    const targetEmail =
      role === 'director'
        ? 'director@studycenter.com'
        : role === 'teacher'
          ? 'teacher@studycenter.com'
          : 'secretary@studycenter.com';
    user = store.users.find((u) => u.email.toLowerCase() === targetEmail);
  } else if (email && password) {
    const found = store.users.find((u) => u.email.toLowerCase() === String(email).trim().toLowerCase());
    if (found && bcrypt.compareSync(String(password), found.passwordHash)) {
      user = found;
    }
  }

  if (!user) {
    return res.status(401).json({ message: 'Adresse email ou mot de passe incorrect.' });
  }

  if (!user.is_active) {
    return res.status(403).json({
      message: 'Ce compte a été désactivé par la direction. Veuillez contacter le Directeur.',
    });
  }

  const token = `${user.id}|${crypto.randomBytes(24).toString('hex')}`;
  store.tokens.set(token, {
    token,
    userId: user.id,
    createdAt: Date.now(),
    expiresAt: Date.now() + 2 * 60 * 60 * 1000,
  });

  const teacher = store.teachers.find((t) => t.user_id === user!.id || t.email === user!.email);
  const roleLabel =
    user.role === 'director' ? 'Directeur' : user.role === 'teacher' ? 'Enseignant' : 'Personnel';
  recordActivity(user, 'account', 'login', `Connexion de ${user.name} (${roleLabel})`);

  return res.json({
    status: 'success',
    user: {
      id: user.id,
      name: user.name,
      role: user.role,
      email: user.email,
      permissions: getEffectivePermissions(user),
      teacher_id: teacher?.id ?? null,
    },
    token,
    expires_in: 7200,
    login_time: new Date().toISOString(),
  });
});

authAndUsersRouter.post('/logout', authRequired, (req: AuthedRequest, res) => {
  if (req.token) {
    store.tokens.delete(req.token);
  }
  res.json({ message: 'Déconnecté avec succès' });
});

// Profile
authAndUsersRouter.get('/profile', authRequired, (req: AuthedRequest, res) => {
  const user = req.user!;
  res.json({
    id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone,
    role: user.role,
    permissions: getEffectivePermissions(user),
    is_active: user.is_active,
    created_at: user.created_at,
  });
});

authAndUsersRouter.put('/profile', authRequired, (req: AuthedRequest, res) => {
  const user = req.user!;
  const { name, email, phone } = req.body || {};
  if (!name || !email) {
    return res.status(422).json({ message: 'Le nom et l’adresse email sont obligatoires.' });
  }
  const emailLower = String(email).trim().toLowerCase();
  const duplicate = store.users.find((u) => u.id !== user.id && u.email.toLowerCase() === emailLower);
  if (duplicate) {
    return res.status(422).json({ message: 'Cette adresse email est déjà utilisée par un autre compte.' });
  }

  user.name = String(name).trim();
  user.email = emailLower;
  if (phone !== undefined) user.phone = phone ? String(phone).trim() : null;

  res.json({
    message: 'Profil mis à jour avec succès.',
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      role: user.role,
    },
  });
});

authAndUsersRouter.put('/profile/password', authRequired, (req: AuthedRequest, res) => {
  const user = req.user!;
  const { current_password, new_password, new_password_confirmation } = req.body || {};

  if (!current_password || !new_password || String(new_password).length < 6) {
    return res.status(422).json({ message: 'Le nouveau mot de passe doit contenir au moins 6 caractères.' });
  }
  if (new_password !== new_password_confirmation) {
    return res.status(422).json({ message: 'La confirmation du mot de passe ne correspond pas.' });
  }
  if (!bcrypt.compareSync(String(current_password), user.passwordHash)) {
    return res.status(422).json({
      message: "L'ancien mot de passe est incorrect.",
      errors: { current_password: ["L'ancien mot de passe fourni ne correspond pas à votre mot de passe actuel."] },
    });
  }

  user.passwordHash = bcrypt.hashSync(String(new_password), 8);
  res.json({ message: 'Votre mot de passe a été modifié avec succès.' });
});

// Users management (Director only)
authAndUsersRouter.get('/users', authRequired, requireRole('director'), (_req, res) => {
  const list = [...store.users]
    .sort((a, b) => {
      if (a.role === 'director' && b.role !== 'director') return -1;
      if (a.role !== 'director' && b.role === 'director') return 1;
      return a.name.localeCompare(b.name);
    })
    .map((u) => ({
      id: u.id,
      name: u.name,
      email: u.email,
      phone: u.phone,
      role: u.role,
      permissions: getEffectivePermissions(u),
      is_active: Boolean(u.is_active),
      is_founder: u.id === 1,
      created_at: u.created_at,
    }));
  res.json(list);
});

authAndUsersRouter.post('/users', authRequired, requireRole('director'), (req: AuthedRequest, res) => {
  const { name, email, phone, password, role, permissions } = req.body || {};
  if (!name || !email || !password || !role) {
    return res.status(422).json({ message: 'Tous les champs obligatoires doivent être renseignés.' });
  }
  if (!['secretary', 'director', 'teacher'].includes(role)) {
    return res.status(422).json({ message: 'Rôle invalide.' });
  }
  if (String(password).length < 6) {
    return res.status(422).json({ message: 'Le mot de passe doit contenir au moins 6 caractères.' });
  }
  const emailLower = String(email).trim().toLowerCase();
  if (store.users.some((u) => u.email.toLowerCase() === emailLower)) {
    return res.status(422).json({ message: 'Cette adresse email est déjà enregistrée.' });
  }

  const perms =
    role === 'director'
      ? null
      : role === 'teacher'
        ? Array.isArray(permissions)
          ? permissions
          : [...DEFAULT_TEACHER_PERMISSIONS]
        : Array.isArray(permissions)
          ? permissions
          : [...DEFAULT_PERSONNEL_PERMISSIONS];

  const user: UserRecord = {
    id: store.nextId('user'),
    name: String(name).trim(),
    email: emailLower,
    phone: phone ? String(phone).trim() : null,
    passwordHash: bcrypt.hashSync(String(password), 8),
    role,
    permissions: perms,
    is_active: true,
    created_at: new Date().toISOString().slice(0, 16).replace('T', ' '),
  };
  store.users.push(user);

  recordActivity(
    req.user,
    'account',
    'created',
    `Création du compte ${user.role === 'secretary' ? 'personnel' : 'directeur'} : ${user.name} (${user.email})`
  );

  res.status(201).json({
    message: `Compte ${user.role === 'secretary' ? 'personnel' : 'directeur'} créé avec succès.`,
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      role: user.role,
      permissions: getEffectivePermissions(user),
      is_active: true,
      is_founder: false,
      created_at: user.created_at,
    },
  });
});

authAndUsersRouter.put('/users/:id', authRequired, requireRole('director'), (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const user = store.users.find((u) => u.id === id);
  if (!user) return res.status(404).json({ message: 'Utilisateur introuvable.' });

  const { name, email, phone, role, permissions, is_active } = req.body || {};

  if (user.id === 1 && role && role !== 'director') {
    return res.status(422).json({ message: 'Le compte Directeur Fondateur ne peut pas changer de rôle.' });
  }
  if (user.id === 1 && is_active === false) {
    return res.status(422).json({ message: 'Le compte Directeur Fondateur ne peut pas être désactivé.' });
  }
  if (email) {
    const emailLower = String(email).trim().toLowerCase();
    if (store.users.some((u) => u.id !== id && u.email.toLowerCase() === emailLower)) {
      return res.status(422).json({ message: 'Cet email est déjà pris.' });
    }
    user.email = emailLower;
  }

  if (name) user.name = String(name).trim();
  if (phone !== undefined) user.phone = phone ? String(phone).trim() : null;
  if (role && ['secretary', 'director', 'teacher'].includes(role)) user.role = role;
  if (permissions !== undefined) {
    user.permissions = user.role === 'director' ? null : Array.isArray(permissions) ? permissions : user.permissions;
  }
  if (typeof is_active === 'boolean') user.is_active = is_active;

  recordActivity(req.user, 'account', 'updated', `Mise à jour du profil/compte de ${user.name}`);

  res.json({
    message: 'Compte mis à jour avec succès.',
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      role: user.role,
      permissions: getEffectivePermissions(user),
      is_active: Boolean(user.is_active),
      is_founder: user.id === 1,
    },
  });
});

authAndUsersRouter.post('/users/:id/reset-password', authRequired, requireRole('director'), (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  const user = store.users.find((u) => u.id === id);
  if (!user) return res.status(404).json({ message: 'Utilisateur introuvable.' });

  const { password } = req.body || {};
  if (!password || String(password).length < 6) {
    return res.status(422).json({ message: 'Le mot de passe doit contenir au moins 6 caractères.' });
  }
  user.passwordHash = bcrypt.hashSync(String(password), 8);
  recordActivity(req.user, 'account', 'password_reset', `Réinitialisation du mot de passe de ${user.name}`);

  res.json({ message: `Le mot de passe de ${user.name} a été réinitialisé avec succès.` });
});

authAndUsersRouter.delete('/users/:id', authRequired, requireRole('director'), (req: AuthedRequest, res) => {
  const id = Number(req.params.id);
  if (id === req.user!.id) {
    return res.status(403).json({
      message: 'Sécurité : Vous ne pouvez pas supprimer votre propre compte actuellement connecté.',
    });
  }
  if (id === 1) {
    return res.status(403).json({
      message: 'Sécurité : Le compte Directeur Maître Fondateur est sanctuarisé et ne peut pas être supprimé.',
    });
  }

  const idx = store.users.findIndex((u) => u.id === id);
  if (idx === -1) return res.status(404).json({ message: 'Utilisateur introuvable.' });
  const name = store.users[idx].name;
  store.users.splice(idx, 1);

  recordActivity(req.user, 'account', 'deleted', `Suppression du compte utilisateur ${name}`);
  res.json({ message: `Le compte de ${name} a été supprimé avec succès.` });
});

// Guide Progress
authAndUsersRouter.get('/guide/progress', authRequired, (req: AuthedRequest, res) => {
  const user = req.user!;
  const keys = Array.from(store.guideProgress.get(user.id) || []);

  const startingChecks =
    user.role === 'director' || user.role === 'secretary'
      ? {
          has_subjects: store.subjects.length > 0,
          has_teachers: store.teachers.length > 0,
          has_pricing: store.enrollments.some((e) => e.price_per_month > 0 || e.price_per_session > 0),
          has_students: store.students.length > 0,
          has_attendances: store.attendances.length > 0,
          has_payments: store.payments.some((p) => p.status === 'paid'),
          has_expenses: user.role === 'director' ? store.expenses.length > 0 : false,
        }
      : {
          has_attendances: store.attendances.length > 0,
        };

  res.json({
    completed_keys: keys,
    starting_checks: startingChecks,
  });
});

authAndUsersRouter.post('/guide/progress/:key', authRequired, (req: AuthedRequest, res) => {
  const key = String(req.params.key || '');
  if (!VALID_GUIDE_KEYS.includes(key)) {
    return res.status(422).json({ error: 'Clé inconnue.' });
  }
  const set = store.guideProgress.get(req.user!.id) || new Set<string>();
  set.add(key);
  store.guideProgress.set(req.user!.id, set);
  res.json({ status: 'ok', key });
});

authAndUsersRouter.delete('/guide/progress/:key', authRequired, (req: AuthedRequest, res) => {
  const key = String(req.params.key || '');
  if (!VALID_GUIDE_KEYS.includes(key)) {
    return res.status(422).json({ error: 'Clé inconnue.' });
  }
  const set = store.guideProgress.get(req.user!.id);
  if (set) set.delete(key);
  res.json({ status: 'ok', key });
});

// Global Search
authAndUsersRouter.get('/search', authRequired, (req: AuthedRequest, res) => {
  const q = String(req.query.q || '').trim();
  if (q.length < 2) {
    return res.status(422).json({ message: 'Le terme de recherche doit comporter au moins 2 caractères.' });
  }

  const term = q.toLowerCase();
  const user = req.user!;
  const canViewPayments = hasPermission(user, 'payments');
  const canViewStudents = hasPermission(user, 'students');
  const canViewTeachers = hasPermission(user, 'teachers');

  if (user.role === 'teacher') {
    const teacher = resolveTeacherForUser(user);
    const allowedIds = new Set<number>();
    if (teacher) {
      for (const e of store.enrollments) {
        if (e.teacher_id === teacher.id) allowedIds.add(e.student_id);
      }
      for (const g of store.groups) {
        if (g.teacher_id === teacher.id || g.user_id === user.id) {
          for (const sid of g.student_ids) allowedIds.add(sid);
        }
      }
    }
    const matchingStudents = store.students
      .filter((s) => {
        if (!allowedIds.has(s.id)) return false;
        const gNames = store.groups.filter((g) => g.student_ids.includes(s.id)).map((g) => g.name);
        return (
          s.name.toLowerCase().includes(term) ||
          s.level.toLowerCase().includes(term) ||
          (s.group_name || '').toLowerCase().includes(term) ||
          gNames.some((gn) => gn.toLowerCase().includes(term))
        );
      })
      .slice(0, 10)
      .map((s) => {
        const gNames = store.groups.filter((g) => g.student_ids.includes(s.id)).map((g) => g.name);
        return {
          id: s.id,
          name: s.name,
          level: s.level,
          group_name: s.group_name || gNames[0] || null,
          groups: gNames,
          phone: s.phone,
          parent_phone: s.parent_phone,
          status: s.status,
          can_view: true,
          can_pay: false,
          can_statement: false,
        };
      });

    return res.json({ students: matchingStudents, teachers: [] });
  }

  const matchingStudents = canViewStudents
    ? store.students
        .filter((s) => {
          const gNames = store.groups.filter((g) => g.student_ids.includes(s.id)).map((g) => g.name);
          return (
            s.name.toLowerCase().includes(term) ||
            s.level.toLowerCase().includes(term) ||
            (s.group_name || '').toLowerCase().includes(term) ||
            gNames.some((gn) => gn.toLowerCase().includes(term))
          );
        })
        .slice(0, 10)
        .map((s) => {
          const gNames = store.groups.filter((g) => g.student_ids.includes(s.id)).map((g) => g.name);
          return {
            id: s.id,
            name: s.name,
            level: s.level,
            group_name: s.group_name || gNames[0] || null,
            groups: gNames,
            phone: s.phone,
            parent_phone: s.parent_phone,
            status: s.status,
            can_view: true,
            can_pay: canViewPayments,
            can_statement: canViewPayments,
          };
        })
    : [];

  const matchingTeachers = canViewTeachers
    ? store.teachers
        .filter(
          (t) =>
            t.name.toLowerCase().includes(term) ||
            t.subject.toLowerCase().includes(term) ||
            t.phone.toLowerCase().includes(term)
        )
        .slice(0, 10)
        .map((t) => ({
          id: t.id,
          name: t.name,
          subject: t.subject,
          phone: t.phone,
          email: t.email,
        }))
    : [];

  res.json({ students: matchingStudents, teachers: matchingTeachers });
});

// Dashboard stats
authAndUsersRouter.get('/stats', authRequired, (req: AuthedRequest, res) => {
  const isDirector = req.user?.role === 'director';
  const totalStudents = store.students.length;
  const activeStudents = store.students.filter((s) => s.status === 'active').length;
  const totalTeachers = store.teachers.length;
  const totalRevenue = round2(
    store.payments.filter((p) => p.status === 'paid').reduce((sum, p) => sum + Number(p.amount), 0)
  );
  const pendingPayments = store.payments.filter((p) => p.status === 'pending').length;
  const weeklyLessons = store.teachers.reduce((sum, t) => sum + (t.weekly_lessons || 0), 0);

  const topTeachers = store.teachers.map((t) => {
    const formatted = formatTeacher(t, req.user);
    const item: Record<string, any> = {
      id: t.id,
      name: t.name,
      subject: t.subject,
      students: formatted.studentCount,
      lessons: t.weekly_lessons || 4,
    };
    if (isDirector) {
      item.monthly_gross_revenue = formatted.monthly_gross_revenue;
      item.teacher_share = formatted.teacher_share;
      item.center_share = formatted.center_share;
      item.remuneration_type = formatted.remuneration_type;
      item.rate_percentage = formatted.rate_percentage;
      item.salary_per_lesson = formatted.salary_per_lesson;
    }
    return item;
  });

  const recentActivity = store.activityLogs.slice(0, 8).map((l) => ({
    id: `act_${l.id}`,
    type: l.type,
    action: l.action,
    message: l.message,
    user_name: l.user_name,
    user_role: l.user_role,
    time: 'Récemment',
    timestamp: l.timestamp,
  }));

  res.json({
    stats: {
      totalStudents,
      activeStudents,
      totalTeachers,
      totalRevenue,
      pendingPayments,
      weeklyLessons,
    },
    topTeachers,
    recentActivity,
  });
});

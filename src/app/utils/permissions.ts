// src/app/utils/permissions.ts

export interface PermissionItem {
  key: string;
  label: string;
  description: string;
  category: string;
  badge?: string;
  isSensitive?: boolean;
}

export interface PermissionCategory {
  name: string;
  items: PermissionItem[];
}

export const PERMISSION_CATEGORIES: PermissionCategory[] = [
  {
    name: 'Pédagogie & Élèves',
    items: [
      {
        key: 'students',
        label: 'Gestion des Élèves',
        description: 'Consulter, inscrire et modifier les fiches élèves',
        category: 'Pédagogie & Élèves',
      },
      {
        key: 'attendance',
        label: 'Présences & Groupes',
        description: 'Feuille d\'appel, pointage des séances et suivi des groupes',
        category: 'Pédagogie & Élèves',
      },
      {
        key: 'daycare',
        label: 'Élèves de garde (Garderie)',
        description: 'Pointage et inscription des enfants à la garderie',
        category: 'Pédagogie & Élèves',
      },
    ],
  },
  {
    name: 'Corps Enseignant & Planning',
    items: [
      {
        key: 'teachers',
        label: 'Enseignants & Matières',
        description: 'Gestion du corps professoral et matières dispensées',
        category: 'Corps Enseignant & Planning',
      },
      {
        key: 'schedule',
        label: 'Emploi du Temps',
        description: 'Planning des séances, répartition des salles et horaires',
        category: 'Corps Enseignant & Planning',
      },
      {
        key: 'schoolcalendar',
        label: 'Calendrier Scolaire',
        description: 'Calendrier des vacances et import des emplois du temps',
        category: 'Corps Enseignant & Planning',
      },
      {
        key: 'teacher_portal',
        label: 'Espace Enseignant (Double Rôle)',
        description: 'Permet à ce membre du personnel d\'enseigner des matières et gérer ses groupes/planning',
        category: 'Corps Enseignant & Planning',
        badge: 'Double rôle',
      },
    ],
  },
  {
    name: 'Finances & Comptabilité',
    items: [
      {
        key: 'payments',
        label: 'Encaissements & Reçus',
        description: 'Encaisser les mensualités, imprimer reçus et extraits',
        category: 'Finances & Comptabilité',
      },
      {
        key: 'reports',
        label: 'Comptabilité & Bilan Financier',
        description: 'Accès au journal de caisse, dépenses et clôture mensuelle',
        category: 'Finances & Comptabilité',
        badge: 'Sensible',
      },
    ],
  },
  {
    name: 'Sécurité & Droits Avancés',
    items: [
      {
        key: 'can_delete',
        label: 'Autorisation de Suppression',
        description: 'Droit de supprimer définitivement élèves, paiements ou fiches',
        category: 'Sécurité & Droits Avancés',
        isSensitive: true,
      },
    ],
  },
];

export const PRESET_PROFILES = [
  {
    id: 'all',
    title: 'Accès Intégral',
    badge: 'Tout coché',
    description: 'Accès à toutes les fonctionnalités et modules',
    permissions: [
      'students', 'attendance', 'daycare', 'teachers',
      'schedule', 'schoolcalendar', 'payments', 'reports', 'can_delete'
    ],
  },
  {
    id: 'standard_personnel',
    title: 'Personnel Standard',
    badge: 'Recommandé',
    description: 'Accueil, inscriptions, présences, garderie & encaissements',
    permissions: [
      'students', 'attendance', 'daycare', 'teachers',
      'schedule', 'schoolcalendar', 'payments'
    ],
  },
  {
    id: 'cashier',
    title: 'Finances / Caissier',
    badge: 'Finances',
    description: 'Paiements, encaissements, extraits et comptabilité',
    permissions: [
      'payments', 'reports', 'students'
    ],
  },
  {
    id: 'pedagogical',
    title: 'Superviseur Pédagogique',
    badge: 'Pédagogie',
    description: 'Élèves, présences, enseignants, plannings et calendrier',
    permissions: [
      'students', 'attendance', 'daycare', 'teachers',
      'schedule', 'schoolcalendar'
    ],
  },
];

export function hasUserPermission(user: any, permissionKey: string): boolean {
  if (!user) return false;
  if (user.role === 'director') return true;
  if (user.role === 'teacher') {
    const teacherPerms = Array.isArray(user.permissions) ? user.permissions : ['teacher_portal', 'schedule', 'attendance'];
    return teacherPerms.includes(permissionKey);
  }
  if (Array.isArray(user.permissions)) {
    return user.permissions.includes(permissionKey);
  }
  // Par défaut pour le personnel existant
  const defaults = [
    'dashboard', 'students', 'attendance', 'daycare',
    'teachers', 'schedule', 'payments', 'schoolcalendar'
  ];
  return defaults.includes(permissionKey);
}

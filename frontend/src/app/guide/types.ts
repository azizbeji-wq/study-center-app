// src/app/guide/types.ts
// Types du système de guide intégré

export type UserRole = 'director' | 'secretary' | 'teacher';

export type TabKey =
  | 'dashboard'
  | 'students'
  | 'attendance'
  | 'payments'
  | 'schedule'
  | 'teachers'
  | 'reports'
  | 'daycare'
  | 'schoolcalendar'
  | 'today'
  | 'groups';

/** Une étape d'une fiche "Sur cette page" */
export interface GuideStep {
  title: string;
  description: string;
}

/** Une fiche "Sur cette page" liée à un onglet */
export interface GuidePage {
  tab: TabKey;
  title: string;
  steps: GuideStep[];
}

/** Une étape de visite guidée (highlight d'un élément) */
export interface TourStep {
  /** Sélecteur data-tour="xxx" ou sélecteur CSS sur l'élément cible */
  target: string;
  title: string;
  text: string;
}

/** Une visite guidée pour un onglet donné */
export interface TourConfig {
  tab: TabKey;
  steps: TourStep[];
}

/** Un élément de la checklist "Pour démarrer" */
export interface ChecklistItem {
  key: string;
  label: string;
  description: string;
  /** Clé de route/onglet vers lequel orienter l'utilisateur */
  targetTab?: TabKey;
}

/** Contenu du guide pour un rôle donné */
export interface GuideContent {
  role: UserRole;
  checklist: ChecklistItem[];
  pages: GuidePage[];
  tours: TourConfig[];
}

/** Clés valides de progression */
export const VALID_PROGRESS_KEYS = [
  // Checklist directeur
  'checklist.subjects',
  'checklist.teachers',
  'checklist.pricing',
  'checklist.first_student',
  'checklist.first_attendance',
  // Visites guidées
  'tour.students',
  'tour.attendance',
  'tour.payments',
  'tour.schedule',
  'tour.teachers',
  'tour.today',
  'tour.groups',
  // Bandeaux "nouveau ici"
  'banner.students',
  'banner.attendance',
  'banner.payments',
  'banner.schedule',
  'banner.teachers',
  'banner.today',
  'banner.groups',
] as const;

export type ProgressKey = typeof VALID_PROGRESS_KEYS[number];

/** État de progression chargé de l'API */
export interface ProgressState {
  completedKeys: Set<string>;
  loading: boolean;
  error: boolean;
}


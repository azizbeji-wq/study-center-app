// Utilitaires partagés : un enseignant peut enseigner plusieurs matières.
// Les matières sont écrites dans le champ « matière » de l'enseignant,
// séparées par des virgules (ex : « Mathématiques, Physique »).

export interface TeacherWithSubjects {
  subject?: string | null;
  subjects?: string[];
}

/** Minuscules, sans accents, espaces normalisés (pour comparer deux noms de matières). */
export function normalizeText(text: string | null | undefined): string {
  return (text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}

/** « Mathématiques, Physique » devient ['Mathématiques', 'Physique']. */
export function parseSubjects(subject: string | null | undefined): string[] {
  return (subject || '')
    .split(/[,;/،]+/)
    .map(s => s.trim())
    .filter(Boolean);
}

/** Liste des matières d'un enseignant (utilise `subjects` si le serveur l'envoie). */
export function teacherSubjectList(teacher: TeacherWithSubjects): string[] {
  return teacher.subjects && teacher.subjects.length > 0
    ? teacher.subjects
    : parseSubjects(teacher.subject);
}

/** Dictionnaire des synonymes et abréviations des matières en Tunisie. */
const SUBJECT_ALIASES: string[][] = [
  ['math', 'maths', 'mathematique', 'mathematiques', 'رياضيات'],
  ['physique', 'sciences physiques', 'physique-chimie', 'chimie', 'فيزياء'],
  ['svt', 'sciences', 'science', 'sciences de la vie et de la terre', 'علوم', 'علوم طبيعية'],
  ['arabe', 'arab', 'langue arabe', 'عربية', 'لغة عربية'],
  ['francais', 'french', 'langue francaise', 'فرنسية'],
  ['anglais', 'english', 'langue anglaise', 'انقليزية', 'إنجليزية'],
  ['philosophie', 'philo', 'فلسفة'],
  ['informatique', 'info', 'tic', 'إعلامية'],
  ['economie', 'eco', 'إقتصاد'],
  ['gestion', 'تصرف'],
  ['histoire', 'histoire-geo', 'histoire et geographie', 'تاريخ'],
  ['geographie', 'geo', 'جغرافيا'],
  ['technique', 'technologie', 'تقنية']
];

function areSubjectsEquivalent(subA: string, subB: string): boolean {
  const normA = normalizeText(subA);
  const normB = normalizeText(subB);
  if (!normA || !normB) return false;

  // Égalité directe ou inclusion
  if (normA === normB || normA.includes(normB) || normB.includes(normA)) {
    return true;
  }

  // Vérification des alias
  for (const group of SUBJECT_ALIASES) {
    const inGroupA = group.some(alias => normA.includes(alias) || alias.includes(normA));
    const inGroupB = group.some(alias => normB.includes(alias) || alias.includes(normB));
    if (inGroupA && inGroupB) {
      return true;
    }
  }

  return false;
}

/** Vrai si l'enseignant donne cette matière (« Sciences Physiques » correspond à « Physique »). */
export function teacherTeachesSubject(teacher: TeacherWithSubjects, subjectName: string): boolean {
  if (!subjectName || !normalizeText(subjectName)) return true;

  const list = teacherSubjectList(teacher);
  return list.some(s => areSubjectsEquivalent(s, subjectName));
}

/**
 * Enseignants d'une matière.
 * Retourne UNIQUEMENT les enseignants qui enseignent cette matière.
 * Si aucun enseignant n'est déclaré pour cette matière, retourne une liste vide ([]).
 */
export function filterTeachersBySubject<T extends TeacherWithSubjects>(
  teachers: T[],
  subjectName: string
): { list: T[]; matched: boolean } {
  if (!normalizeText(subjectName)) {
    return { list: [], matched: false };
  }
  const list = teachers.filter(t => teacherTeachesSubject(t, subjectName));
  return { list, matched: list.length > 0 };
}

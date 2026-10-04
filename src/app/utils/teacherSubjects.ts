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

/** Vrai si l'enseignant donne cette matière (« Sciences Physiques » correspond à « Physique »). */
export function teacherTeachesSubject(teacher: TeacherWithSubjects, subjectName: string): boolean {
  const wanted = normalizeText(subjectName);
  if (!wanted) return true;

  return teacherSubjectList(teacher)
    .map(normalizeText)
    .filter(Boolean)
    .some(s => s === wanted || s.includes(wanted) || wanted.includes(s));
}

/**
 * Enseignants d'une matière.
 * Si aucun enseignant n'est déclaré pour cette matière, retourne tous les enseignants
 * avec matched = false (pour ne pas bloquer l'utilisateur).
 */
export function filterTeachersBySubject<T extends TeacherWithSubjects>(
  teachers: T[],
  subjectName: string
): { list: T[]; matched: boolean } {
  if (!normalizeText(subjectName)) {
    return { list: teachers, matched: true };
  }
  const list = teachers.filter(t => teacherTeachesSubject(t, subjectName));
  return list.length > 0 ? { list, matched: true } : { list: teachers, matched: false };
}

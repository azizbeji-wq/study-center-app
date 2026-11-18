import { describe, it, expect } from 'vitest';

/**
 * Tests des fonctions pures de useTeachers.ts :
 * getInitial, formatDate, getGrossRevenue, getTeacherShare, getCenterShare
 */

type Teacher = {
  id: number;
  name: string;
  subject?: string;
  subjects?: string[];
  studentCount?: number;
  weeklyLessons?: number;
  payout_status?: string;
  remuneration_type?: string;
  rate_percentage?: number;
  teacher_share?: number;
  center_share?: number;
  monthly_gross_revenue?: number;
  fixed_student_rate?: number;
  center_fixed_per_student?: number;
  is_active?: boolean;
};

// ─── Fonctions copiées pures ──────────────────────────────────────────────────

function getInitial(name: string): string {
  const clean = name.replace(/^prof\.?\s*/i, '').trim();
  return (clean.charAt(0) || name.charAt(0) || 'P').toUpperCase();
}

function formatDate(d: string | null | undefined): string {
  if (!d) return '-';
  try {
    const parts = d.split('-');
    if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return d;
  } catch {
    return d;
  }
}

function getGrossRevenue(teacher: Teacher): number {
  if (teacher.monthly_gross_revenue !== undefined) return Number(teacher.monthly_gross_revenue);
  const count = Number(teacher.studentCount) || 0;
  return count * 60;
}

function getTeacherShare(teacher: Teacher): number {
  if (teacher.teacher_share !== undefined) return Number(teacher.teacher_share);
  const gross = getGrossRevenue(teacher);
  const count = Number(teacher.studentCount) || 0;
  if (teacher.remuneration_type === 'fixed_center') {
    const centerFixed = Number(teacher.center_fixed_per_student) || 20;
    return Math.max(0, gross - (count * centerFixed));
  }
  const isPct = teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0);
  if (isPct) {
    const pct = Number(teacher.rate_percentage) || 70;
    return Math.round((gross * pct) / 100);
  }
  const fixedRate = Number(teacher.fixed_student_rate) || 15;
  return Math.round(fixedRate * count);
}

function getCenterShare(teacher: Teacher): number {
  if (teacher.center_share !== undefined) return Number(teacher.center_share);
  const gross = getGrossRevenue(teacher);
  const tShare = getTeacherShare(teacher);
  return Math.max(0, gross - tShare);
}

// ─── Tests : getInitial ──────────────────────────────────────────────────────

describe('getInitial', () => {
  it('retourne la première lettre majuscule du nom', () => {
    expect(getInitial('Alice Martin')).toBe('A');
    expect(getInitial('Dupont')).toBe('D');
  });

  it('supprime le préfixe "Prof." avant de prendre l\'initiale', () => {
    expect(getInitial('Prof. Ahmed')).toBe('A');
    expect(getInitial('prof. Ben')).toBe('B');
    expect(getInitial('Prof Carla')).toBe('C');
  });

  it('retourne "P" si le nom est vide', () => {
    expect(getInitial('')).toBe('P');
  });

  it('fonctionne avec un seul caractère', () => {
    expect(getInitial('Z')).toBe('Z');
  });
});

// ─── Tests : formatDate ──────────────────────────────────────────────────────

describe('formatDate', () => {
  it('convertit le format ISO YYYY-MM-DD en DD/MM/YYYY', () => {
    expect(formatDate('2025-03-15')).toBe('15/03/2025');
    expect(formatDate('2024-01-01')).toBe('01/01/2024');
  });

  it('retourne "-" pour une valeur nulle ou undefined', () => {
    expect(formatDate(null)).toBe('-');
    expect(formatDate(undefined)).toBe('-');
    expect(formatDate('')).toBe('-');
  });

  it('retourne la valeur brute si le format n\'est pas YYYY-MM-DD', () => {
    expect(formatDate('15/03/2025')).toBe('15/03/2025'); // déjà en DD/MM/YYYY
  });
});

// ─── Tests : getGrossRevenue ─────────────────────────────────────────────────

describe('getGrossRevenue', () => {
  it('retourne monthly_gross_revenue si défini', () => {
    const t: Teacher = { id: 1, name: 'Test', monthly_gross_revenue: 500 };
    expect(getGrossRevenue(t)).toBe(500);
  });

  it('calcule studentCount * 60 si monthly_gross_revenue absent', () => {
    const t: Teacher = { id: 2, name: 'Test', studentCount: 10 };
    expect(getGrossRevenue(t)).toBe(600);
  });

  it('retourne 0 si studentCount absent', () => {
    const t: Teacher = { id: 3, name: 'Test' };
    expect(getGrossRevenue(t)).toBe(0);
  });
});

// ─── Tests : getTeacherShare ─────────────────────────────────────────────────

describe('getTeacherShare', () => {
  it('retourne teacher_share directement si défini', () => {
    const t: Teacher = { id: 1, name: 'Test', teacher_share: 350 };
    expect(getTeacherShare(t)).toBe(350);
  });

  it('calcule la part en mode pourcentage (70%)', () => {
    const t: Teacher = { id: 2, name: 'Test', studentCount: 10, remuneration_type: 'percentage', rate_percentage: 70 };
    // gross = 600, 70% = 420
    expect(getTeacherShare(t)).toBe(420);
  });

  it('calcule la part en mode fixed_center (gross - nb_étudiants * center_fixed)', () => {
    const t: Teacher = { id: 3, name: 'Test', studentCount: 10, remuneration_type: 'fixed_center', center_fixed_per_student: 20 };
    // gross = 600, 600 - (10 * 20) = 400
    expect(getTeacherShare(t)).toBe(400);
  });

  it('calcule via fixed_student_rate quand pas de pourcentage ni fixed_center', () => {
    const t: Teacher = { id: 4, name: 'Test', studentCount: 8, fixed_student_rate: 15 };
    // 15 * 8 = 120
    expect(getTeacherShare(t)).toBe(120);
  });

  it('ne peut pas être négatif en mode fixed_center', () => {
    const t: Teacher = { id: 5, name: 'Test', studentCount: 2, remuneration_type: 'fixed_center', center_fixed_per_student: 500 };
    // gross = 120, 120 - 1000 = négatif → clamp à 0
    expect(getTeacherShare(t)).toBe(0);
  });
});

// ─── Tests : getCenterShare ──────────────────────────────────────────────────

describe('getCenterShare', () => {
  it('retourne center_share directement si défini', () => {
    const t: Teacher = { id: 1, name: 'Test', center_share: 200 };
    expect(getCenterShare(t)).toBe(200);
  });

  it('calcule gross - teacherShare', () => {
    const t: Teacher = { id: 2, name: 'Test', studentCount: 10, remuneration_type: 'percentage', rate_percentage: 70 };
    // gross = 600, teacher = 420, center = 180
    expect(getCenterShare(t)).toBe(180);
  });

  it('ne peut pas être négatif', () => {
    const t: Teacher = { id: 3, name: 'Test', studentCount: 5, teacher_share: 1000, monthly_gross_revenue: 300 };
    // gross = 300, teacher = 1000, center = max(0, -700) = 0
    expect(getCenterShare(t)).toBe(0);
  });
});

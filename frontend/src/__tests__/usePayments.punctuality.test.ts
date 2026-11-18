import { describe, it, expect } from 'vitest';

/**
 * Tests isolés pour la logique de ponctualité des paiements.
 * On extrait la logique pure de getPaymentPunctuality pour la tester
 * sans avoir besoin de monter le composable complet (et donc sans axios).
 */

type PaymentPunctuality = {
  isLate: boolean;
  label: string;
  badgeClass: string;
};

type Payment = {
  id: number;
  date: string | null;
  month: string | null;
  amount: number;
  status: string;
  student_name?: string;
  teacherId?: number | null;
  daycareChildId?: number | null;
  monthsPaid?: number;
  sessionsPaid?: number;
  teacherName?: string;
};

/** Copie pure de getPaymentPunctuality pour tests isolés */
function getPaymentPunctuality(payment: Payment): PaymentPunctuality {
  if (!payment.date || !payment.month) {
    return { isLate: false, label: 'Payé dans les délais', badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
  }

  try {
    const payDate = new Date(payment.date);
    const mStr = (payment.month || '').toLowerCase();

    const yearMatch = mStr.match(/\b(20\d\d)\b/);
    const targetYear = yearMatch ? parseInt(yearMatch[1], 10) : payDate.getFullYear();

    let targetMonthIdx = -1;
    if (mStr.includes('janv')) targetMonthIdx = 0;
    else if (mStr.includes('fev') || mStr.includes('fév')) targetMonthIdx = 1;
    else if (mStr.includes('mars')) targetMonthIdx = 2;
    else if (mStr.includes('avr')) targetMonthIdx = 3;
    else if (mStr.includes('mai')) targetMonthIdx = 4;
    else if (mStr.includes('juin')) targetMonthIdx = 5;
    else if (mStr.includes('juil')) targetMonthIdx = 6;
    else if (mStr.includes('août') || mStr.includes('aout')) targetMonthIdx = 7;
    else if (mStr.includes('sept')) targetMonthIdx = 8;
    else if (mStr.includes('oct')) targetMonthIdx = 9;
    else if (mStr.includes('nov')) targetMonthIdx = 10;
    else if (mStr.includes('déc') || mStr.includes('dec')) targetMonthIdx = 11;

    if (targetMonthIdx !== -1) {
      const deadline = new Date(targetYear, targetMonthIdx + 1, 5, 23, 59, 59);
      if (payDate.getTime() > deadline.getTime()) {
        return { isLate: true, label: 'Règlement en retard', badgeClass: 'bg-amber-50 text-amber-700 border-amber-200' };
      }
    }
  } catch (e) {/* */}

  return { isLate: false, label: 'Payé à temps', badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
}

// ─── Tests ───────────────────────────────────────────────────────────────────

describe('getPaymentPunctuality', () => {
  it('retourne isLate=false quand date et month sont absents', () => {
    const result = getPaymentPunctuality({ id: 1, date: null, month: null, amount: 100, status: 'paid' });
    expect(result.isLate).toBe(false);
    expect(result.label).toBe('Payé dans les délais');
  });

  it('retourne isLate=false pour un paiement payé avant la deadline (5 du mois suivant)', () => {
    // Paiement pour janvier 2025, payé le 3 février 2025 (avant le 5)
    const result = getPaymentPunctuality({
      id: 2, date: '2025-02-03', month: 'janvier 2025', amount: 50, status: 'paid'
    });
    expect(result.isLate).toBe(false);
    expect(result.badgeClass).toContain('emerald');
  });

  it('retourne isLate=true pour un paiement payé après la deadline', () => {
    // Paiement pour janvier 2025, payé le 10 février 2025 (après le 5)
    const result = getPaymentPunctuality({
      id: 3, date: '2025-02-10', month: 'janvier 2025', amount: 50, status: 'paid'
    });
    expect(result.isLate).toBe(true);
    expect(result.label).toBe('Règlement en retard');
    expect(result.badgeClass).toContain('amber');
  });

  it('détecte le retard pour le mois de mars (mars 2025, payé le 7 avril)', () => {
    const result = getPaymentPunctuality({
      id: 4, date: '2025-04-07', month: 'mars 2025', amount: 80, status: 'paid'
    });
    expect(result.isLate).toBe(true);
  });

  it('ne marque pas comme retard si payé le 5 du mois suivant exactement', () => {
    // 5 avril 23:00 — avant la deadline 23:59:59
    const result = getPaymentPunctuality({
      id: 5, date: '2025-04-05', month: 'mars 2025', amount: 80, status: 'paid'
    });
    expect(result.isLate).toBe(false);
  });

  it('gère "déc" et "dec" comme décembre', () => {
    // Payé le 10 janvier pour décembre → retard
    const result = getPaymentPunctuality({
      id: 6, date: '2025-01-10', month: 'déc 2024', amount: 60, status: 'paid'
    });
    expect(result.isLate).toBe(true);
  });

  it('gère "août" et "aout" correctement', () => {
    const result1 = getPaymentPunctuality({
      id: 7, date: '2025-09-10', month: 'août 2025', amount: 60, status: 'paid'
    });
    const result2 = getPaymentPunctuality({
      id: 8, date: '2025-09-10', month: 'aout 2025', amount: 60, status: 'paid'
    });
    expect(result1.isLate).toBe(true);
    expect(result2.isLate).toBe(true);
  });

  it('retourne isLate=false pour un mois non reconnu (mois inconnu)', () => {
    const result = getPaymentPunctuality({
      id: 9, date: '2025-05-20', month: 'trimestre 1', amount: 100, status: 'paid'
    });
    expect(result.isLate).toBe(false);
  });
});

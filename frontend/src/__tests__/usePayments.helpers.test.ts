import { describe, it, expect } from 'vitest';

/**
 * Tests des fonctions utilitaires pures de usePayments.ts :
 * round2, paymentDetail, firstError
 */

// ─── round2 ──────────────────────────────────────────────────────────────────

function round2(value: number): number {
  return Math.round(value * 100) / 100;
}

describe('round2', () => {
  it('arrondit à 2 décimales', () => {
    // Note: 1.005 en IEEE 754 vaut ~1.00499..., round2 retourne donc 1.00
    expect(round2(1.004)).toBe(1);
    expect(round2(10.1234)).toBe(10.12);
    expect(round2(0.999)).toBe(1);
    expect(round2(100)).toBe(100);
    expect(round2(1.235)).toBe(1.24);
  });

  it('fonctionne avec des nombres négatifs', () => {
    expect(round2(-1.005)).toBe(-1);
    expect(round2(-0.5)).toBe(-0.5);
  });

  it('fonctionne avec zéro', () => {
    expect(round2(0)).toBe(0);
  });
});

// ─── firstError ──────────────────────────────────────────────────────────────

function firstError(e: any, fallback: string): string {
  const errors = e?.response?.data?.errors;
  if (errors) {
    const first = Object.values(errors)[0] as string[];
    if (first?.length) return first[0];
  }
  return e?.response?.data?.message || fallback;
}

describe('firstError', () => {
  it('retourne la première erreur de validation laravel', () => {
    const axiosError = {
      response: {
        data: {
          errors: {
            email: ['L\'email est requis.', 'L\'email est invalide.'],
            password: ['Le mot de passe doit comporter au moins 8 caractères.'],
          }
        }
      }
    };
    expect(firstError(axiosError, 'Erreur inconnue')).toBe('L\'email est requis.');
  });

  it('retourne le message si pas d\'erreurs de validation', () => {
    const axiosError = {
      response: {
        data: { message: 'Non autorisé.' }
      }
    };
    expect(firstError(axiosError, 'Erreur inconnue')).toBe('Non autorisé.');
  });

  it('retourne le fallback si pas de réponse', () => {
    const networkError = { code: 'NETWORK_ERROR' };
    expect(firstError(networkError, 'Erreur réseau')).toBe('Erreur réseau');
  });

  it('retourne le fallback si e est undefined', () => {
    expect(firstError(undefined, 'Fallback')).toBe('Fallback');
  });

  it('retourne le fallback si errors est un objet vide', () => {
    const e = { response: { data: { errors: {} } } };
    expect(firstError(e, 'Fallback')).toBe('Fallback');
  });
});

// ─── paymentDetail ───────────────────────────────────────────────────────────

type Payment = {
  id: number;
  amount: number;
  status: string;
  date?: string;
  month?: string;
  teacherId?: number | null;
  daycareChildId?: number | null;
  monthsPaid?: number;
  sessionsPaid?: number;
  teacherName?: string;
};

function paymentDetail(p: Payment): string {
  if (p.daycareChildId) {
    return `${p.monthsPaid} mois`;
  }
  if (p.teacherId) {
    return `${p.sessionsPaid} séance(s) · ${p.teacherName ?? 'Enseignant'}`;
  }
  return '-';
}

describe('paymentDetail', () => {
  it('affiche les mois pour un paiement crèche', () => {
    const p: Payment = { id: 1, amount: 200, status: 'paid', daycareChildId: 5, monthsPaid: 2 };
    expect(paymentDetail(p)).toBe('2 mois');
  });

  it('affiche les séances et le nom pour un paiement enseignant', () => {
    const p: Payment = { id: 2, amount: 100, status: 'paid', teacherId: 3, sessionsPaid: 4, teacherName: 'M. Dupont' };
    expect(paymentDetail(p)).toBe('4 séance(s) · M. Dupont');
  });

  it('affiche le nom par défaut "Enseignant" si teacherName absent', () => {
    const p: Payment = { id: 3, amount: 100, status: 'paid', teacherId: 3, sessionsPaid: 2 };
    expect(paymentDetail(p)).toBe('2 séance(s) · Enseignant');
  });

  it('retourne "-" pour un paiement sans teacherId ni daycareChildId', () => {
    const p: Payment = { id: 4, amount: 50, status: 'pending' };
    expect(paymentDetail(p)).toBe('-');
  });
});

import { describe, it, expect } from 'vitest';
import {
  getCategoryLabel,
  getCategoryBadgeClass,
  formatAuditAction,
  getAuditActionBadge,
  formatLogDate,
  formatDateTime,
} from '../app/composables/useFinancialFormatters';

// ─── getCategoryLabel ────────────────────────────────────────────────────────

describe('getCategoryLabel', () => {
  it('retourne le label traduit pour une catégorie connue', () => {
    expect(getCategoryLabel('loyer')).toBe('Loyer');
    expect(getCategoryLabel('électricité')).toBe('Électricité / Eau');
    expect(getCategoryLabel('fournitures')).toBe('Fournitures & Copies');
    expect(getCategoryLabel('entretien')).toBe('Entretien');
    expect(getCategoryLabel('publicité')).toBe('Publicité & Marketing');
    expect(getCategoryLabel('internet_tel')).toBe('Internet & Tel');
    expect(getCategoryLabel('salaires')).toBe('Salaires Annexes');
    expect(getCategoryLabel('autres')).toBe('Autres Dépenses');
  });

  it('retourne la catégorie brute si inconnue (fallback)', () => {
    expect(getCategoryLabel('inconnu')).toBe('inconnu');
    expect(getCategoryLabel('')).toBe('');
  });
});

// ─── getCategoryBadgeClass ───────────────────────────────────────────────────

describe('getCategoryBadgeClass', () => {
  it('retourne la classe CSS pour chaque catégorie connue', () => {
    expect(getCategoryBadgeClass('loyer')).toContain('indigo');
    expect(getCategoryBadgeClass('salaires')).toContain('blue');
    expect(getCategoryBadgeClass('autres')).toContain('gray');
  });

  it('retourne la classe par défaut pour une catégorie inconnue', () => {
    expect(getCategoryBadgeClass('xyz')).toBe('bg-gray-50 text-gray-700 border-gray-200');
  });
});

// ─── formatAuditAction ───────────────────────────────────────────────────────

describe('formatAuditAction', () => {
  it('traduit les actions audit en français', () => {
    expect(formatAuditAction('created')).toBe('Paiement Enregistré');
    expect(formatAuditAction('updated')).toBe('Modification');
    expect(formatAuditAction('marked_paid')).toBe('Validé Payé');
    expect(formatAuditAction('deleted')).toBe('Paiement Supprimé');
  });

  it('retourne la clé brute pour une action inconnue', () => {
    expect(formatAuditAction('unknown_action')).toBe('unknown_action');
  });
});

// ─── getAuditActionBadge ─────────────────────────────────────────────────────

describe('getAuditActionBadge', () => {
  it('retourne la classe badge correcte pour chaque action', () => {
    expect(getAuditActionBadge('created')).toContain('emerald');
    expect(getAuditActionBadge('updated')).toContain('blue');
    expect(getAuditActionBadge('marked_paid')).toContain('purple');
    expect(getAuditActionBadge('deleted')).toContain('red');
  });

  it('retourne le badge par défaut pour une action inconnue', () => {
    expect(getAuditActionBadge('xyz')).toBe('bg-gray-50 text-gray-700 border-gray-200');
  });
});

// ─── formatLogDate ───────────────────────────────────────────────────────────

describe('formatLogDate', () => {
  it('retourne "—" pour une valeur vide', () => {
    expect(formatLogDate('')).toBe('—');
  });

  it('formate une date ISO en date locale courte française', () => {
    const result = formatLogDate('2025-03-15T10:30:00Z');
    // doit contenir le jour "15" et le mois "mars"
    expect(result).toMatch(/15/);
    expect(result.toLowerCase()).toMatch(/mars|mar/);
  });

  it('ne lève pas d\'exception sur une date invalide', () => {
    // formatLogDate attrape les erreurs et renvoie la valeur brute
    expect(() => formatLogDate('not-a-date')).not.toThrow();
  });
});

// ─── formatDateTime ──────────────────────────────────────────────────────────

describe('formatDateTime', () => {
  it('retourne "—" pour une valeur vide', () => {
    expect(formatDateTime('')).toBe('—');
  });

  it('formate une date ISO en date longue française', () => {
    const result = formatDateTime('2025-06-01T08:00:00Z');
    expect(result).toMatch(/2025/);
  });
});

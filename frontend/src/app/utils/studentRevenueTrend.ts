import type { Payment } from '../types/payments';
import type { StudentRevenueChartPoint } from './renderStudentRevenueRechart';

const SHORT_FRENCH_MONTHS = [
  'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
  'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc',
];

const FRENCH_MONTH_PATTERNS: Array<{ index: number; pattern: RegExp }> = [
  { index: 0, pattern: /janv/i },
  { index: 1, pattern: /f[eé]v/i },
  { index: 2, pattern: /mars/i },
  { index: 3, pattern: /avr/i },
  { index: 4, pattern: /mai/i },
  { index: 5, pattern: /juin/i },
  { index: 6, pattern: /juil/i },
  { index: 7, pattern: /ao[uû]t/i },
  { index: 8, pattern: /sept/i },
  { index: 9, pattern: /oct/i },
  { index: 10, pattern: /nov/i },
  { index: 11, pattern: /d[eé]c/i },
];

function extractMonthKey(payment: Payment): string | null {
  const rawDate = (payment.date || '').trim();
  const isoMatch = rawDate.match(/^(\d{4})-(\d{2})/);
  if (isoMatch) {
    return `${isoMatch[1]}-${isoMatch[2]}`;
  }

  const label = (payment.month || '').trim();
  if (label) {
    const yearMatch = label.match(/\b(20\d{2})\b/);
    const year = yearMatch ? Number(yearMatch[1]) : new Date().getFullYear();
    for (const entry of FRENCH_MONTH_PATTERNS) {
      if (entry.pattern.test(label)) {
        return `${year}-${String(entry.index + 1).padStart(2, '0')}`;
      }
    }
  }

  return null;
}

export function buildMonthlyStudentRevenueSeries(
  payments: Payment[],
  monthsCount: 6 | 12 = 6
): StudentRevenueChartPoint[] {
  const now = new Date();
  const buckets = new Map<string, { students: number; paymentCount: number; label: string }>();
  const orderedKeys: string[] = [];

  for (let offset = monthsCount - 1; offset >= 0; offset -= 1) {
    const d = new Date(now.getFullYear(), now.getMonth() - offset, 1);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const label = `${SHORT_FRENCH_MONTHS[d.getMonth()]} ${d.getFullYear()}`;
    buckets.set(key, { students: 0, paymentCount: 0, label });
    orderedKeys.push(key);
  }

  for (const p of payments) {
    if (p.status !== 'paid') continue;
    const isStudentPayment = p.type === 'student' || (!p.daycareChildId && Boolean(p.studentId));
    if (!isStudentPayment) continue;

    const key = extractMonthKey(p);
    if (!key) continue;

    const bucket = buckets.get(key);
    if (!bucket) continue;

    bucket.students = Math.round((bucket.students + Number(p.amount || 0)) * 100) / 100;
    bucket.paymentCount += 1;
  }

  return orderedKeys.map((key) => {
    const b = buckets.get(key)!;
    return {
      month: key,
      label: b.label,
      students: b.students,
      paymentCount: b.paymentCount,
    };
  });
}

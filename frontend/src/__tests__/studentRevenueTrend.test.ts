import { describe, it, expect } from 'vitest';
import { buildMonthlyStudentRevenueSeries } from '../app/utils/studentRevenueTrend';
import type { Payment } from '../app/types/payments';

function monthKeyOffset(offset: number): string {
  const now = new Date();
  const d = new Date(now.getFullYear(), now.getMonth() + offset, 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

describe('buildMonthlyStudentRevenueSeries', () => {
  it('aggregates paid student payments by month while excluding daycare and unpaid payments', () => {
    const currentKey = monthKeyOffset(0);
    const prevKey = monthKeyOffset(-1);

    const payments: Payment[] = [
      {
        id: 1,
        studentName: 'Yassine',
        month: 'Octobre',
        amount: 120,
        date: `${prevKey}-10`,
        status: 'paid',
        type: 'student',
        studentId: 1,
        daycareChildId: null,
        teacherId: 1,
        teacherName: 'Prof. Mohamed',
        sessionsPaid: 4,
        monthsPaid: 0,
      },
      {
        id: 2,
        studentName: 'Sarra',
        month: 'Novembre',
        amount: 80,
        date: `${currentKey}-04`,
        status: 'paid',
        type: 'student',
        studentId: 4,
        daycareChildId: null,
        teacherId: 2,
        teacherName: 'Mme. Amira',
        sessionsPaid: 4,
        monthsPaid: 0,
      },
      {
        id: 3,
        studentName: 'Aziz',
        month: 'Novembre',
        amount: 100,
        date: `${currentKey}-11`,
        status: 'paid',
        type: 'student',
        studentId: 5,
        daycareChildId: null,
        teacherId: 1,
        teacherName: 'Prof. Mohamed',
        sessionsPaid: 4,
        monthsPaid: 0,
      },
      {
        id: 4,
        studentName: 'Adam (Garderie)',
        month: 'Novembre',
        amount: 180,
        date: `${currentKey}-05`,
        status: 'paid',
        type: 'daycare',
        studentId: null,
        daycareChildId: 1,
        teacherId: null,
        teacherName: null,
        sessionsPaid: 0,
        monthsPaid: 1,
      },
      {
        id: 5,
        studentName: 'Impayé',
        month: 'Novembre',
        amount: 90,
        date: `${currentKey}-08`,
        status: 'pending',
        type: 'student',
        studentId: 2,
        daycareChildId: null,
        teacherId: 1,
        teacherName: 'Prof. Mohamed',
        sessionsPaid: 3,
        monthsPaid: 0,
      },
    ];

    const series = buildMonthlyStudentRevenueSeries(payments, 6);
    expect(series).toHaveLength(6);

    const prevPoint = series.find((p) => p.month === prevKey);
    const currentPoint = series.find((p) => p.month === currentKey);

    expect(prevPoint?.students).toBe(120);
    expect(prevPoint?.paymentCount).toBe(1);
    expect(currentPoint?.students).toBe(180);
    expect(currentPoint?.paymentCount).toBe(2);
  });
});

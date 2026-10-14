import { ref, computed } from 'vue';
import api from '../utils/api';
import type {
  Payment,
  Dues,
  StudentLite,
  ChildLite,
  PaymentPunctuality
} from '../types/payments';

export function usePayments() {
  const payments = ref<Payment[]>([]);
  const students = ref<StudentLite[]>([]);
  const children = ref<ChildLite[]>([]);
  const dues = ref<Dues>({
    thresholds: { studentPendingFrom: 4, studentOverdueAfter: 7, daycarePendingFrom: 1, daycareOverdueAfter: 2 },
    pricing: { sessionsPerMonth: 4, defaultSessionPrice: 15, defaultDaycareMonthlyFee: 0 },
    students: [],
    daycare: [],
    totals: { pending: 0, overdue: 0, pendingCount: 0, overdueCount: 0 }
  });

  const loading = ref(false);
  const loadError = ref('');

  function firstError(e: any, fallback: string): string {
    const errors = e?.response?.data?.errors;
    if (errors) {
      const first = Object.values(errors)[0] as string[];
      if (first?.length) return first[0];
    }
    return e?.response?.data?.message || fallback;
  }

  function round2(value: number): number {
    return Math.round(value * 100) / 100;
  }

  async function loadPayments() {
    try {
      const res = await api.get('/api/payments');
      payments.value = res.data || [];
    } catch (e) {
      console.error('Erreur chargement paiements:', e);
      payments.value = [];
    }
  }

  async function loadDues() {
    try {
      const res = await api.get('/api/payment-dues');
      dues.value = res.data;
      loadError.value = '';
    } catch (e) {
      console.error('Erreur chargement soldes:', e);
      loadError.value = firstError(
        e,
        'Impossible de calculer les soldes. Vérifiez que la migration a bien été exécutée.'
      );
    }
  }

  async function loadData() {
    loading.value = true;
    try {
      const [, , sRes, cRes] = await Promise.all([
        loadPayments(),
        loadDues(),
        api.get('/api/students').catch(() => ({ data: [] })),
        api.get('/api/daycare').catch(() => ({ data: [] }))
      ]);
      students.value = sRes.data || [];
      children.value = cRes.data || [];
    } finally {
      loading.value = false;
    }
  }

  const totalPaid = computed(() =>
    payments.value
      .filter(p => p.status === 'paid')
      .reduce((sum, p) => sum + Number(p.amount), 0)
  );

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
    } catch (e) {}

    return { isLate: false, label: 'Payé à temps', badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
  }

  function paymentDetail(p: Payment): string {
    if (p.daycareChildId) {
      return `${p.monthsPaid} mois`;
    }
    if (p.teacherId) {
      return `${p.sessionsPaid} séance(s) · ${p.teacherName ?? 'Enseignant'}`;
    }
    return '-';
  }

  function unitAmountFor(studentId: number | null, teacherId: number | null): number {
    if (!studentId || !teacherId) return 0;

    const due = dues.value.students.find(r => r.studentId === studentId && r.teacherId === teacherId);
    if (due) return due.unitAmount;

    const s = students.value.find(st => st.id === studentId);
    if (!s) return 0;
    const p = dues.value.pricing;
    const prices: number[] = [];
    for (const x of s.subjects || []) {
      if (x.teacher_id !== teacherId) continue;
      const perSession = Number(x.price_per_session ?? 0);
      const perMonth = Number(x.price_per_month ?? 0);
      if (perSession > 0) prices.push(perSession);
      else if (perMonth > 0) prices.push(round2(perMonth / p.sessionsPerMonth));
    }
    const price = prices.length > 0
      ? round2(prices.reduce((a, b) => a + b, 0) / prices.length)
      : p.defaultSessionPrice;
    const factor =
      s.is_remise && s.discount_type === 'percentage' && Number(s.remise) > 0
        ? Math.max(0, 1 - Number(s.remise) / 100)
        : 1;
    return round2(price * factor);
  }

  function childFeeFor(childId: number | null): number {
    if (!childId) return 0;
    const c = children.value.find(ch => ch.id === childId);
    const fee = Number(c?.monthlyFee ?? 0);
    return fee > 0 ? fee : dues.value.pricing.defaultDaycareMonthlyFee;
  }

  async function markAsPaid(payment: Payment) {
    const todayStr = new Date().toISOString().split('T')[0];
    const oldStatus = payment.status;
    const oldDate = payment.date;
    payment.status = 'paid';
    payment.date = todayStr;
    try {
      await api.put(`/api/payments/${payment.id}`, { status: 'paid', date: todayStr });
    } catch (e) {
      payment.status = oldStatus;
      payment.date = oldDate;
      console.error('Erreur validation paiement:', e);
      alert('Impossible de marquer ce paiement comme payé.');
    }
  }

  async function deletePayment(id: number) {
    if (!confirm('Voulez-vous vraiment supprimer cet enregistrement de paiement ?')) return;
    try {
      await api.delete(`/api/payments/${id}`);
      payments.value = payments.value.filter(p => p.id !== id);
      await loadDues();
    } catch (e) {
      console.error('Erreur suppression paiement:', e);
      alert('Impossible de supprimer ce paiement.');
    }
  }

  return {
    payments,
    students,
    children,
    dues,
    loading,
    loadError,
    totalPaid,
    firstError,
    round2,
    loadPayments,
    loadDues,
    loadData,
    getPaymentPunctuality,
    paymentDetail,
    unitAmountFor,
    childFeeFor,
    markAsPaid,
    deletePayment
  };
}

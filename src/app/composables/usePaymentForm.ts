import { ref, computed } from 'vue';
import type {
  Payment,
  Dues,
  StudentLite,
  ChildLite,
  Kind,
  PaymentFormState
} from '../types/payments';
import { usePayments } from './usePayments';

export function usePaymentForm(
  props: {
    students: StudentLite[];
    children: ChildLite[];
    dues: Dues;
  }
) {
  const { round2 } = usePayments();
  const today = () => new Date().toISOString().split('T')[0];

  function emptyForm(): PaymentFormState {
    return {
      kind: 'student',
      studentId: null,
      teacherId: null,
      daycareChildId: null,
      sessionsPaid: 1,
      monthsPaid: 1,
      amount: 0,
      date: today(),
      month: '',
      status: 'paid',
      studentName: ''
    };
  }

  const form = ref<PaymentFormState>(emptyForm());

  const teacherOptions = computed(() => {
    const s = props.students.find(st => st.id === form.value.studentId);
    if (!s) return [] as Array<{ id: number; name: string }>;
    const seen = new Map<number, string>();
    for (const sub of s.subjects || []) {
      if (sub.teacher_id && !seen.has(sub.teacher_id)) {
        seen.set(sub.teacher_id, sub.teacher || `Enseignant ${sub.teacher_id}`);
      }
    }
    return Array.from(seen.entries()).map(([id, name]) => ({ id, name }));
  });

  const currentStudentDue = computed(
    () =>
      props.dues.students.find(
        r => r.studentId === form.value.studentId && r.teacherId === form.value.teacherId
      ) ?? null
  );

  const currentChildDue = computed(
    () => props.dues.daycare.find(r => r.daycareChildId === form.value.daycareChildId) ?? null
  );

  const currentUnitAmount = computed(() => {
    if (!form.value.studentId || !form.value.teacherId) return 0;
    const due = props.dues.students.find(r => r.studentId === form.value.studentId && r.teacherId === form.value.teacherId);
    if (due) return due.unitAmount;

    const s = props.students.find(st => st.id === form.value.studentId);
    if (!s) return 0;
    const p = props.dues.pricing;
    const prices: number[] = [];
    for (const x of s.subjects || []) {
      if (x.teacher_id !== form.value.teacherId) continue;
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
  });

  const currentChildFee = computed(() => {
    if (!form.value.daycareChildId) return 0;
    const c = props.children.find(ch => ch.id === form.value.daycareChildId);
    const fee = Number(c?.monthlyFee ?? 0);
    return fee > 0 ? fee : props.dues.pricing.defaultDaycareMonthlyFee;
  });

  function recomputeAmount() {
    if (form.value.kind === 'student') {
      const unit = currentUnitAmount.value;
      form.value.amount = unit > 0 ? round2(Number(form.value.sessionsPaid || 0) * unit) : 0;
    } else {
      const fee = currentChildFee.value;
      form.value.amount = fee > 0 ? round2(Number(form.value.monthsPaid || 0) * fee) : 0;
    }
  }

  function onStudentChange() {
    form.value.teacherId = teacherOptions.value[0]?.id ?? null;
    onTeacherChange();
  }

  function onTeacherChange() {
    const due = currentStudentDue.value;
    form.value.sessionsPaid = due ? due.sessionsUnpaid : 1;
    recomputeAmount();
  }

  function onChildChange() {
    const due = currentChildDue.value;
    form.value.monthsPaid = due ? due.monthsUnpaid : 1;
    recomputeAmount();
  }

  function setKind(kind: Kind) {
    form.value = { ...emptyForm(), kind };
  }

  function initFromPayment(payment: Payment) {
    form.value = {
      ...emptyForm(),
      kind: payment.daycareChildId ? 'daycare' : 'student',
      studentName: payment.studentName,
      sessionsPaid: payment.sessionsPaid,
      monthsPaid: payment.monthsPaid,
      amount: Number(payment.amount),
      date: payment.date || '',
      month: payment.month,
      status: payment.status
    };
  }

  function initFromPrefill(prefill: { kind?: Kind; studentId?: number; teacherId?: number; daycareChildId?: number } = {}) {
    form.value = { ...emptyForm(), kind: prefill.kind ?? 'student' };
    if (form.value.kind === 'student' && prefill.studentId) {
      form.value.studentId = prefill.studentId;
      form.value.teacherId = prefill.teacherId ?? teacherOptions.value[0]?.id ?? null;
      onTeacherChange();
    } else if (form.value.kind === 'daycare' && prefill.daycareChildId) {
      form.value.daycareChildId = prefill.daycareChildId;
      onChildChange();
    }
  }

  return {
    form,
    teacherOptions,
    currentStudentDue,
    currentChildDue,
    currentUnitAmount,
    currentChildFee,
    recomputeAmount,
    onStudentChange,
    onTeacherChange,
    onChildChange,
    setKind,
    initFromPayment,
    initFromPrefill
  };
}

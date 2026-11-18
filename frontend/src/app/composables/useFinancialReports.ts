import { ref, computed } from 'vue';
import api from '../utils/api';
import type {
  AccountingSubTab,
  AccountingPeriod,
  ExpenseItem,
  AccountingAuditLog
} from '../types/financial';

import {
  categoryColors,
  getCategoryLabel,
  getCategoryBadgeClass,
  formatAuditAction,
  getAuditActionBadge,
  formatLogDate,
  formatDateTime
} from './useFinancialFormatters';

export {
  categoryColors,
  getCategoryLabel,
  getCategoryBadgeClass,
  formatAuditAction,
  getAuditActionBadge,
  formatLogDate,
  formatDateTime
};

export function useFinancialReports() {
  const currentUser = ref<{ role?: string; name?: string } | null>(null);
  try {
    const saved = localStorage.getItem('user');
    if (saved) currentUser.value = JSON.parse(saved);
  } catch {}

  const isDirector = computed(() => currentUser.value?.role === 'director');

  const activeSubTab = ref<AccountingSubTab>('overview');
  const selectedPeriod = ref<AccountingPeriod>('12months');
  const selectedSchoolYear = ref('2025-2026');
  const selectedMonth = ref(new Date().toISOString().substring(0, 7));
  const journalDate = ref(new Date().toISOString().substring(0, 10));

  const isLoading = ref(false);
  const summaryData = ref<any>(null);
  const expensesList = ref<ExpenseItem[]>([]);
  const auditLogs = ref<AccountingAuditLog[]>([]);
  const paymentsList = ref<any[]>([]);

  const kpis = computed(() => summaryData.value?.kpis || {});

  async function fetchPayments() {
    try {
      const res = await api.get('/api/payments');
      paymentsList.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
    } catch (e) {
      console.error('Erreur chargement paiements pour exports:', e);
    }
  }

  async function fetchSummary() {
    if (!isDirector.value) return;
    isLoading.value = true;
    try {
      const params: Record<string, string> = {
        period: selectedPeriod.value,
        school_year: selectedSchoolYear.value,
        month: selectedMonth.value,
        journal_date: journalDate.value,
      };

      const res = await api.get('/api/accounting/summary', { params });
      summaryData.value = res.data;
    } catch (e) {
      console.error('Erreur chargement synthèse comptable:', e);
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchExpenses() {
    try {
      const res = await api.get('/api/accounting/expenses');
      expensesList.value = res.data?.data || [];
    } catch (e) {
      console.error('Erreur chargement dépenses:', e);
    }
  }

  async function fetchAuditLogs() {
    try {
      const res = await api.get('/api/accounting/audit-logs');
      auditLogs.value = res.data || [];
    } catch (e) {
      console.error('Erreur chargement audit logs:', e);
    }
  }

  function changePeriod(mode: AccountingPeriod) {
    selectedPeriod.value = mode;
    fetchSummary();
  }

  async function saveExpense(expenseData: any, editingId: number | null) {
    if (editingId) {
      await api.put(`/api/accounting/expenses/${editingId}`, expenseData);
    } else {
      await api.post('/api/accounting/expenses', expenseData);
    }
    await Promise.all([fetchExpenses(), fetchSummary()]);
  }

  async function deleteExpense(id: number) {
    if (!confirm('Confirmez-vous la suppression de cette dépense ?')) return;
    try {
      await api.delete(`/api/accounting/expenses/${id}`);
      await Promise.all([fetchExpenses(), fetchSummary()]);
    } catch (e: any) {
      alert('Erreur suppression dépense: ' + (e.response?.data?.message || e.message));
    }
  }

  async function generateRecurringExpenses() {
    try {
      const res = await api.post('/api/accounting/expenses/generate-recurring', {
        month: selectedMonth.value,
      });
      alert(res.data?.message || 'Dépenses récurrentes traitées.');
      await Promise.all([fetchExpenses(), fetchSummary()]);
    } catch (e: any) {
      alert('Erreur génération récurrentes: ' + (e.response?.data?.message || e.message));
    }
  }

  async function toggleTeacherPayoutStatus(teacher: any) {
    const isPaid = teacher.payoutStatus === 'paid' || teacher.payoutStatus === 'taken';
    const newStatus = isPaid ? 'pending' : 'paid';
    const label = newStatus === 'paid' ? 'marquer comme RÉGLÉ' : 'remettre en ATTENTE';
    if (!confirm(`Souhaitez-vous ${label} la part de ${teacher.name} (${teacher.teacherShare} DT) ?`)) return;

    try {
      await api.post(`/api/teachers/${teacher.id}/toggle-payout`, {
        status: newStatus,
        amount: teacher.teacherShare,
        date: new Date().toISOString().substring(0, 10),
        month: selectedMonth.value,
      });
      await fetchSummary();
    } catch (e: any) {
      alert('Erreur mise à jour versement: ' + (e.response?.data?.message || e.message));
    }
  }

  async function closeCurrentMonth() {
    if (!confirm(`Confirmez-vous la clôture comptable du mois ${selectedMonth.value} ? Cette opération fige le bilan mensuel.`)) return;

    try {
      const res = await api.post('/api/accounting/close-month', {
        month: selectedMonth.value,
        school_year: selectedSchoolYear.value,
      });
      alert(res.data?.message || 'Mois clôturé avec succès.');
      await fetchSummary();
    } catch (e: any) {
      alert('Erreur clôture: ' + (e.response?.data?.message || e.message));
    }
  }

  async function reopenCurrentMonth() {
    if (!confirm(`Voulez-vous réouvrir le mois ${selectedMonth.value} pour régularisation ?`)) return;

    try {
      const res = await api.post('/api/accounting/reopen-month', {
        month: selectedMonth.value,
      });
      alert(res.data?.message || 'Mois réouvert.');
      await fetchSummary();
    } catch (e: any) {
      alert('Erreur réouverture: ' + (e.response?.data?.message || e.message));
    }
  }

  function exportCsv(type = 'all') {
    let rows: string[][] = [];
    let filename = `comptabilite-${selectedMonth.value}.csv`;

    if (type === 'expenses') {
      filename = `depenses-${selectedMonth.value}.csv`;
      rows.push(['Date', 'Titre', 'Categorie', 'Mode Paiement', 'Reference', 'Recurrent', 'Montant (DT)', 'Notes']);
      expensesList.value.forEach(e => {
        rows.push([
          e.date || '',
          `"${(e.title || '').replace(/"/g, '""')}"`,
          e.category || '',
          e.payment_method || '',
          e.receipt_reference || '',
          e.is_recurring ? 'Oui' : 'Non',
          String(e.amount || 0),
          `"${(e.notes || '').replace(/"/g, '""')}"`,
        ]);
      });
    } else if (type === 'teachers') {
      filename = `honoraires-enseignants-${selectedMonth.value}.csv`;
      rows.push(['Enseignant', 'Matière', 'Formule', 'Séances', 'CA Encaissé (DT)', 'Part Dûe (DT)', 'Déjà Versé (DT)', 'Reste à Payer (DT)', 'Statut']);
      (summaryData.value?.byTeacher || []).forEach((t: any) => {
        rows.push([
          `"${t.name}"`,
          t.subject || '',
          t.remunerationType === 'percentage'
            ? `Formule C (${t.ratePercentage || 70}%)`
            : t.remunerationType === 'fixed_center'
              ? `Formule A (Idara ${t.centerFixedPerStudent || 20} DT/él)`
              : `Formule B (${t.fixedStudentRate || 15} DT/él)`,
          String(t.sessionsCount),
          String(t.revenueCollected),
          String(t.teacherShare),
          String(t.paidAmount),
          String(t.remainingToPay),
          t.payoutStatus,
        ]);
      });
    } else {
      rows.push(['Mois', 'Recettes Élèves (DT)', 'Recettes Garderie (DT)', 'Recettes Totales (DT)', 'Dépenses (DT)', 'Parts Enseignants (DT)', 'Bénéfice Net (DT)']);
      (summaryData.value?.monthsTrend || []).forEach((m: any) => {
        rows.push([
          m.month,
          String(m.students),
          String(m.daycare),
          String(m.totalRevenue),
          String(m.expenses),
          String(m.teacherPayouts),
          String(m.netProfit),
        ]);
      });
    }

    const csvContent = '\uFEFF' + rows.map(r => r.join(';')).join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  const expenseCategoriesSummary = computed(() => {
    return (summaryData.value?.expenseDonut || []).map((cat: any) => ({
      ...cat,
      color: categoryColors[cat.category] || '#9CA3AF',
    }));
  });

  const expenseDonutSlices = computed(() => {
    const cats = expenseCategoriesSummary.value;
    const circ = 2 * Math.PI * 38;
    let currentOffset = 0;

    return cats.map((cat: any) => {
      const strokeLength = ((cat.percentage || 0) / 100) * circ;
      const strokeOffset = -currentOffset;
      currentOffset += strokeLength;

      return {
        ...cat,
        strokeLength,
        strokeOffset,
      };
    });
  });

  const chartPoints = computed(() => {
    const trend = summaryData.value?.monthsTrend || [];
    if (trend.length === 0) return [];

    const maxVal = Math.max(...trend.map((t: any) => Math.max(t.totalRevenue, t.expenses, 1000)), 5000);
    const minX = 60;
    const maxX = 560;
    const baseY = 240;
    const topY = 40;
    const step = trend.length > 1 ? (maxX - minX) / (trend.length - 1) : 0;

    return trend.map((t: any, idx: number) => {
      const x = minX + idx * step;
      const revenueY = baseY - (t.totalRevenue / maxVal) * (baseY - topY);
      const expenseY = baseY - (t.expenses / maxVal) * (baseY - topY);
      const profitY = baseY - (Math.max(0, t.netProfit) / maxVal) * (baseY - topY);

      return {
        x,
        label: t.label,
        revenue: t.totalRevenue,
        expenses: t.expenses,
        students: t.students,
        daycare: t.daycare,
        teacherPayouts: t.teacherPayouts,
        netProfit: t.netProfit,
        revenueY,
        expenseY,
        profitY,
      };
    });
  });

  const profitLinePath = computed(() => {
    const pts = chartPoints.value;
    if (pts.length === 0) return '';
    return pts.reduce((acc: string, p: any, idx: number) => {
      return idx === 0 ? `M ${p.x} ${p.profitY}` : `${acc} L ${p.x} ${p.profitY}`;
    }, '');
  });

  const totalTeachersGross = computed(() => {
    return (summaryData.value?.byTeacher || []).reduce((acc: number, t: any) => acc + (t.revenueCollected || 0), 0);
  });

  const totalCenterTeacherMargin = computed(() => {
    return (summaryData.value?.byTeacher || []).reduce((acc: number, t: any) => acc + (t.centerMargin || 0), 0);
  });

  return {
    currentUser,
    isDirector,
    activeSubTab,
    selectedPeriod,
    selectedSchoolYear,
    selectedMonth,
    journalDate,
    isLoading,
    summaryData,
    expensesList,
    auditLogs,
    paymentsList,
    kpis,
    changePeriod,
    fetchSummary,
    fetchExpenses,
    fetchAuditLogs,
    fetchPayments,
    saveExpense,
    deleteExpense,
    generateRecurringExpenses,
    toggleTeacherPayoutStatus,
    closeCurrentMonth,
    reopenCurrentMonth,
    exportCsv,
    getCategoryLabel,
    getCategoryBadgeClass,
    formatAuditAction,
    getAuditActionBadge,
    formatLogDate,
    formatDateTime,
    expenseCategoriesSummary,
    expenseDonutSlices,
    chartPoints,
    profitLinePath,
    totalTeachersGross,
    totalCenterTeacherMargin
  };
}

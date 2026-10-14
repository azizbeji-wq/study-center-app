<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Lock } from 'lucide-vue-next';
import api from '../utils/api';
import type { ExpenseItem } from '../types/financial';
import { useFinancialReports } from '../composables/useFinancialReports';

// Subcomponents
import FinancialHeader from './financial-reports/FinancialHeader.vue';
import FinancialOverviewTab from './financial-reports/FinancialOverviewTab.vue';
import FinancialExpensesTab from './financial-reports/FinancialExpensesTab.vue';
import FinancialTeachersTab from './financial-reports/FinancialTeachersTab.vue';
import FinancialDuesTab from './financial-reports/FinancialDuesTab.vue';
import FinancialCashAuditTab from './financial-reports/FinancialCashAuditTab.vue';
import FinancialClosingTab from './financial-reports/FinancialClosingTab.vue';

// Modals
import FinancialExpenseModal from './financial-reports/FinancialExpenseModal.vue';
import FinancialPayslipModal from './financial-reports/FinancialPayslipModal.vue';
import FinancialReportPrintModal from './financial-reports/FinancialReportPrintModal.vue';
import FinancialExpertPdfModal from './financial-reports/FinancialExpertPdfModal.vue';

const {
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
  expenseCategoriesSummary,
  expenseDonutSlices,
  chartPoints,
  profitLinePath,
  totalTeachersGross,
  totalCenterTeacherMargin,
} = useFinancialReports();

// ─── Modal Dépense ────────────────────────────────────────────────────────────
const showExpenseModal = ref(false);
const editingExpenseId = ref<number | null>(null);
const selectedExpenseForEdit = ref<ExpenseItem | null>(null);
const isSubmittingExpense = ref(false);

function openExpenseModal(expenseToEdit?: ExpenseItem) {
  if (expenseToEdit) {
    editingExpenseId.value = expenseToEdit.id;
    selectedExpenseForEdit.value = expenseToEdit;
  } else {
    editingExpenseId.value = null;
    selectedExpenseForEdit.value = null;
  }
  showExpenseModal.value = true;
}

async function handleSaveExpense(formData: any) {
  isSubmittingExpense.value = true;
  try {
    await saveExpense(formData, editingExpenseId.value);
    showExpenseModal.value = false;
  } catch (e: any) {
    alert('Erreur enregistrement dépense: ' + (e.response?.data?.message || e.message));
  } finally {
    isSubmittingExpense.value = false;
  }
}

// ─── Modal Fiche de Paie ──────────────────────────────────────────────────────
const showPayslipModal = ref(false);
const activePayslip = ref<any>(null);

async function openPayslip(teacherId: number) {
  try {
    const res = await api.get(`/api/accounting/teachers/${teacherId}/payslip`, {
      params: { month: selectedMonth.value },
    });
    activePayslip.value = res.data;
    showPayslipModal.value = true;
  } catch (e: any) {
    alert('Erreur génération fiche de paie: ' + (e.response?.data?.message || e.message));
  }
}

// ─── Modal Bilan Imprimable ───────────────────────────────────────────────────
const showReportPrintModal = ref(false);

// ─── Modal Expert-Comptable PDF ───────────────────────────────────────────────
const showExpertPdfModal = ref(false);
const expertPdfType = ref<'receipts' | 'expenses' | 'teachers'>('receipts');

function openExpertPdfModal(type: 'receipts' | 'expenses' | 'teachers') {
  expertPdfType.value = type;
  showExpertPdfModal.value = true;
  if (type === 'receipts' && paymentsList.value.length === 0) {
    fetchPayments();
  }
}

onMounted(async () => {
  if (isDirector.value) {
    await Promise.all([
      fetchSummary(),
      fetchExpenses(),
      fetchAuditLogs(),
      fetchPayments(),
    ]);
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- Avertissement non-directeur si accès direct -->
    <div v-if="!isDirector" class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
      <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-3">
        <Lock class="w-6 h-6" />
      </div>
      <h3 class="text-lg font-bold text-amber-900">Espace Réservé à la Direction</h3>
      <p class="text-sm text-amber-700 mt-1 max-w-md mx-auto">
        Le module de comptabilité générale, bilans de marge et fiches de paie est réservé au Directeur du centre.
      </p>
    </div>

    <template v-else>
      <!-- En-tête Principal & Sélecteur de Période & Navigation Métier -->
      <FinancialHeader
        v-model:activeSubTab="activeSubTab"
        v-model:selectedSchoolYear="selectedSchoolYear"
        v-model:selectedMonth="selectedMonth"
        :selectedPeriod="selectedPeriod"
        :summaryData="summaryData"
        @changePeriod="changePeriod"
        @periodChanged="fetchSummary"
        @openExpenseModal="openExpenseModal"
        @exportCsv="() => exportCsv('all')"
        @triggerPrint="showReportPrintModal = true"
      />

      <!-- État de Chargement -->
      <div v-if="isLoading" class="bg-white border border-gray-200 rounded-2xl p-12 text-center">
        <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
        <p class="text-sm text-gray-600 font-medium">Calcul des soldes et synthèse comptable en cours...</p>
      </div>

      <!-- Corps des Onglets Métiers -->
      <div v-else class="space-y-6">
        <!-- Onglet 1: Vue d'Ensemble & KPIs -->
        <FinancialOverviewTab
          v-if="activeSubTab === 'overview'"
          :kpis="kpis"
          :chartPoints="chartPoints"
          :profitLinePath="profitLinePath"
          :expenseCategoriesSummary="expenseCategoriesSummary"
          :expenseDonutSlices="expenseDonutSlices"
        />

        <!-- Onglet 2: Dépenses Réelles -->
        <FinancialExpensesTab
          v-else-if="activeSubTab === 'expenses'"
          :expenses="expensesList"
          :kpis="kpis"
          :expenseCategoriesSummary="expenseCategoriesSummary"
          :expenseDonutSlices="expenseDonutSlices"
          @openModal="openExpenseModal"
          @deleteExpense="deleteExpense"
          @generateRecurring="generateRecurringExpenses"
        />

        <!-- Onglet 3: Honoraires & Fiches de Paie Enseignants -->
        <FinancialTeachersTab
          v-else-if="activeSubTab === 'teachers'"
          :summaryData="summaryData"
          :kpis="kpis"
          :totalTeachersGross="totalTeachersGross"
          :totalCenterTeacherMargin="totalCenterTeacherMargin"
          @openPayslip="openPayslip"
          @togglePayout="toggleTeacherPayoutStatus"
        />

        <!-- Onglet 4: Soldes Dus & Relances WhatsApp -->
        <FinancialDuesTab
          v-else-if="activeSubTab === 'dues'"
          :summaryData="summaryData"
          :kpis="kpis"
        />

        <!-- Onglet 5: Journal de Caisse & Audit Logs -->
        <FinancialCashAuditTab
          v-else-if="activeSubTab === 'cash_audit'"
          v-model:journalDate="journalDate"
          :summaryData="summaryData"
          :auditLogs="auditLogs"
          @fetchSummary="fetchSummary"
          @fetchAuditLogs="fetchAuditLogs"
        />

        <!-- Onglet 6: Clôture Mensuelle & Exports Officiels -->
        <FinancialClosingTab
          v-else-if="activeSubTab === 'closing'"
          :summaryData="summaryData"
          :kpis="kpis"
          :selectedMonth="selectedMonth"
          @closeMonth="closeCurrentMonth"
          @reopenMonth="reopenCurrentMonth"
          @openExpertPdf="openExpertPdfModal"
          @exportCsv="exportCsv"
        />
      </div>
    </template>

    <!-- Modals -->
    <FinancialExpenseModal
      :isOpen="showExpenseModal"
      :editingExpenseId="editingExpenseId"
      :initialExpense="selectedExpenseForEdit"
      :isSubmitting="isSubmittingExpense"
      @close="showExpenseModal = false"
      @save="handleSaveExpense"
    />

    <FinancialPayslipModal
      :isOpen="showPayslipModal"
      :payslip="activePayslip"
      @close="showPayslipModal = false"
    />

    <FinancialReportPrintModal
      :isOpen="showReportPrintModal"
      :summaryData="summaryData"
      :kpis="kpis"
      :selectedMonth="selectedMonth"
      :selectedPeriod="selectedPeriod"
      :selectedSchoolYear="selectedSchoolYear"
      @close="showReportPrintModal = false"
    />

    <FinancialExpertPdfModal
      :isOpen="showExpertPdfModal"
      :expertPdfType="expertPdfType"
      :selectedPeriod="selectedPeriod"
      :selectedSchoolYear="selectedSchoolYear"
      :selectedMonth="selectedMonth"
      :summaryData="summaryData"
      :kpis="kpis"
      :paymentsList="paymentsList"
      :expensesList="expensesList"
      :totalTeachersGross="totalTeachersGross"
      @close="showExpertPdfModal = false"
    />
  </div>
</template>

<style scoped>
@media print {
  /* Cache tous les éléments de l'interface sauf le document actif */
  body * {
    visibility: hidden;
  }
  .no-print, .no-print * {
    display: none !important;
  }

  /* Si Fiche de paie ouverte */
  #payslip-printable, #payslip-printable * {
    visibility: visible;
  }
  #payslip-printable {
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    border: none !important;
    box-shadow: none !important;
  }

  /* Si Bilan Financier ouvert */
  #financial-report-printable, #financial-report-printable * {
    visibility: visible;
  }
  #financial-report-printable {
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    border: none !important;
    box-shadow: none !important;
  }

  /* Si Document Expert-Comptable ouvert (Grand Livre, Dépenses, Honoraires) */
  #expert-report-printable, #expert-report-printable * {
    visibility: visible;
  }
  #expert-report-printable {
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    border: none !important;
    box-shadow: none !important;
  }
}
</style>

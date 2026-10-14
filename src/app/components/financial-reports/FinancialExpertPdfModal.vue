<script setup lang="ts">
import { computed } from 'vue';
import { FileText, Printer, X } from 'lucide-vue-next';
import type { ExpenseItem } from '../../types/financial';
import { printIsolatedElement } from '../../utils/printHelper';
import FinancialExpertReceiptsDoc from './FinancialExpertReceiptsDoc.vue';
import FinancialExpertExpensesDoc from './FinancialExpertExpensesDoc.vue';
import FinancialExpertTeachersDoc from './FinancialExpertTeachersDoc.vue';

const props = defineProps<{
  isOpen: boolean;
  expertPdfType: 'receipts' | 'expenses' | 'teachers';
  selectedPeriod: string;
  selectedSchoolYear: string;
  selectedMonth: string;
  summaryData: any;
  kpis: any;
  paymentsList: any[];
  expensesList: ExpenseItem[];
  totalTeachersGross: number;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const selectedPeriodLabel = computed(() => {
  if (props.selectedPeriod === 'school_year') return `Année Scolaire ${props.selectedSchoolYear}`;
  if (props.selectedPeriod === 'current_month') return `Mois ${props.selectedMonth}`;
  return '12 derniers mois';
});

const expertPdfModalTitle = computed(() => {
  if (props.expertPdfType === 'receipts') return 'Grand Livre des Recettes (Export PDF & Impression A4)';
  if (props.expertPdfType === 'expenses') return 'Journal Général des Dépenses (Export PDF & Impression A4)';
  return 'États des Honoraires Enseignants (Export PDF & Impression A4)';
});

const expertPdfDocRef = computed(() => {
  const m = props.selectedMonth || 'ANNUEL';
  if (props.expertPdfType === 'receipts') return `GL-RECETTES-${m}`;
  if (props.expertPdfType === 'expenses') return `JD-DEPENSES-${m}`;
  return `HONORAIRES-${m}`;
});

function handlePrint() {
  const titles = {
    receipts: `Grand Livre des Recettes - ${props.selectedMonth || 'Période'}`,
    expenses: `Journal des Dépenses - ${props.selectedMonth || 'Période'}`,
    teachers: `États des Honoraires Enseignants - ${props.selectedMonth || 'Période'}`,
  };
  printIsolatedElement('expert-report-printable', titles[props.expertPdfType]);
}

const filteredPaymentsForExport = computed(() => {
  return props.paymentsList.filter(p => {
    if (props.selectedPeriod === 'current_month') {
      const pMonth = p.month || (p.date ? p.date.substring(0, 7) : '');
      return pMonth === props.selectedMonth;
    }
    if (props.summaryData?.range?.start && props.summaryData?.range?.end && p.date) {
      return p.date >= props.summaryData.range.start && p.date <= props.summaryData.range.end;
    }
    return true;
  });
});

const totalFilteredPaymentsAmount = computed(() => {
  return filteredPaymentsForExport.value.reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const totalFilteredPaymentsStudents = computed(() => {
  return filteredPaymentsForExport.value
    .filter(p => !p.daycare_child_id && !p.is_garderie)
    .reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const totalFilteredPaymentsDaycare = computed(() => {
  return filteredPaymentsForExport.value
    .filter(p => p.daycare_child_id || p.is_garderie || (p.student_name || '').toLowerCase().includes('garde'))
    .reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const totalCashPaymentsAmount = computed(() => {
  return filteredPaymentsForExport.value
    .filter(p => (p.payment_method || 'especes').toLowerCase() === 'especes')
    .reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const recurringExpensesAmount = computed(() => {
  return props.expensesList
    .filter(e => e.is_recurring)
    .reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const suppliesExpensesAmount = computed(() => {
  return props.expensesList
    .filter(e => e.category === 'fournitures' || e.category === 'entretien')
    .reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const cashExpensesAmount = computed(() => {
  return props.expensesList
    .filter(e => (e.payment_method || 'especes').toLowerCase() === 'especes')
    .reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const totalFilteredExpenses = computed(() => {
  return props.expensesList.reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const totalTeachersPaid = computed(() => {
  return (props.summaryData?.byTeacher || []).reduce((sum: number, t: any) => sum + Number(t.paidAmount || 0), 0);
});

const totalTeachersRemaining = computed(() => {
  return (props.summaryData?.byTeacher || []).reduce((sum: number, t: any) => sum + Number(t.remainingToPay || 0), 0);
});
</script>

<template>
  <div
    v-if="isOpen"
    @click.self="emit('close')"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs p-3 sm:p-6 flex justify-center items-start"
  >
    <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-5xl my-2 sm:my-6 overflow-hidden flex flex-col max-h-[94vh] animate-in fade-in zoom-in-95">
      <!-- Actions barre (Sticky Top) -->
      <div class="p-4 bg-gray-900 text-white flex items-center justify-between no-print print:hidden shrink-0 shadow-sm z-20">
        <div class="flex items-center gap-2">
          <FileText class="w-4 h-4 text-indigo-400" />
          <span class="text-xs sm:text-sm font-bold">{{ expertPdfModalTitle }}</span>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="handlePrint"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>Imprimer / PDF (A4)</span>
          </button>
          <button
            @click="emit('close')"
            class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition-colors cursor-pointer"
            title="Fermer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Corps Déroulant Imprimable -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-gray-50/50 print:p-0 print:bg-white print:overflow-visible">
        <div id="expert-report-printable" class="p-6 sm:p-8 space-y-6 text-gray-900 bg-white rounded-xl border border-gray-200 print:border-none print:shadow-none print:p-0">
          <!-- En-tête officiel Centre d'études -->
          <div class="avoid-break flex items-start justify-between border-b-2 border-gray-900 pb-5">
            <div>
              <h1 class="text-xl font-black tracking-tight text-gray-900">CENTRE D'ÉTUDES & SOUTIEN SCOLAIRE</h1>
              <p class="text-xs text-gray-500 mt-0.5">Direction Financière & Comptabilité Générale — États d'Expertise</p>
              <p class="text-xs text-gray-500">Tunis, Tunisie • Document Officiel Conforme</p>
            </div>
            <div class="text-right">
              <span class="text-xs font-mono px-2.5 py-1 bg-gray-100 rounded-lg font-bold border border-gray-300">
                {{ expertPdfDocRef }}
              </span>
              <p class="text-xs text-gray-500 mt-2">Édité le: {{ new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) }}</p>
              <p class="text-xs text-gray-500">Période: <span class="font-bold text-gray-900">{{ selectedPeriodLabel }}</span></p>
            </div>
          </div>

          <!-- DOCUMENT 1 : GRAND LIVRE DES RECETTES -->
          <FinancialExpertReceiptsDoc
            v-if="expertPdfType === 'receipts'"
            :paymentsList="filteredPaymentsForExport"
            :totalAmount="totalFilteredPaymentsAmount"
            :totalStudents="totalFilteredPaymentsStudents"
            :totalDaycare="totalFilteredPaymentsDaycare"
            :totalCash="totalCashPaymentsAmount"
          />

          <!-- DOCUMENT 2 : JOURNAL DES DÉPENSES -->
          <FinancialExpertExpensesDoc
            v-else-if="expertPdfType === 'expenses'"
            :expensesList="expensesList"
            :totalExpenses="totalFilteredExpenses"
            :recurringAmount="recurringExpensesAmount"
            :suppliesAmount="suppliesExpensesAmount"
            :cashAmount="cashExpensesAmount"
          />

          <!-- DOCUMENT 3 : ÉTATS DES HONORAIRES PROFESSEURS -->
          <FinancialExpertTeachersDoc
            v-else-if="expertPdfType === 'teachers'"
            :teachersList="summaryData?.byTeacher || []"
            :kpis="kpis"
            :totalGross="totalTeachersGross"
            :totalPaid="totalTeachersPaid"
            :totalRemaining="totalTeachersRemaining"
          />

          <!-- Visa et Certification pour l'Expert-Comptable -->
          <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t-2 border-gray-900 text-xs">
            <div>
              <p class="font-bold text-gray-900">Visa & Rapprochement Comptable :</p>
              <p class="text-[10px] text-gray-500 mt-1">Conforme aux livres auxiliaires, reçus de paiement et fiches d'émargement.</p>
              <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
            </div>
            <div class="text-right">
              <p class="font-bold text-gray-900">Cachet & Signature de la Direction :</p>
              <p class="text-[10px] text-gray-500 mt-1">Document certifié pour audit et déclaration comptable.</p>
              <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

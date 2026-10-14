<script setup lang="ts">
import { computed } from 'vue';
import {
  CheckCircle2, Plus, Download, Printer, BarChart3, TrendingDown,
  Users, AlertCircle, ShieldAlert, FileSpreadsheet
} from 'lucide-vue-next';
import type { AccountingSubTab, AccountingPeriod } from '../../types/financial';

const props = defineProps<{
  activeSubTab: AccountingSubTab;
  selectedPeriod: AccountingPeriod;
  selectedSchoolYear: string;
  selectedMonth: string;
  summaryData?: any;
}>();

defineEmits<{
  (e: 'update:activeSubTab', tab: AccountingSubTab): void;
  (e: 'changePeriod', mode: AccountingPeriod): void;
  (e: 'update:selectedSchoolYear', val: string): void;
  (e: 'update:selectedMonth', val: string): void;
  (e: 'periodChanged'): void;
  (e: 'openExpenseModal'): void;
  (e: 'exportCsv'): void;
  (e: 'triggerPrint'): void;
}>();

const accountingTabs = computed(() => [
  { id: 'overview' as AccountingSubTab, label: "Vue d'Ensemble", icon: BarChart3 },
  { id: 'expenses' as AccountingSubTab, label: "Dépenses Réelles", icon: TrendingDown, badge: props.summaryData?.kpis?.totalExpenses ? `${Math.round(props.summaryData.kpis.totalExpenses)} DT` : undefined },
  { id: 'teachers' as AccountingSubTab, label: "Honoraires Enseignants", icon: Users, badge: props.summaryData?.byTeacher?.length ? `${props.summaryData.byTeacher.length}` : undefined },
  { id: 'dues' as AccountingSubTab, label: "Reste à Encaisser", icon: AlertCircle, badge: props.summaryData?.topDebtors?.length ? `${props.summaryData.topDebtors.length}` : undefined },
  { id: 'cash_audit' as AccountingSubTab, label: "Journal de Caisse & Audit", icon: ShieldAlert },
  { id: 'closing' as AccountingSubTab, label: "Clôture & Exports", icon: FileSpreadsheet, badge: props.summaryData?.monthlyClosing?.isClosed ? 'Figé' : undefined },
]);
</script>

<template>
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h2 class="text-2xl font-bold text-gray-900">Comptabilité & Pilotage Financier</h2>
          <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
            Direction
          </span>
          <span v-if="summaryData?.monthlyClosing?.isClosed" class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
            <CheckCircle2 class="w-3 h-3" /> Mois Clôturé
          </span>
        </div>
        <p class="text-sm text-gray-500 mt-0.5">
          Revenus réels, dépenses réelles, rémunérations des enseignants et clôtures certifiées
        </p>
      </div>

      <!-- Contrôles Période & Actions Rapides -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Sélecteur Mode Période -->
        <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-medium">
          <button
            @click="$emit('changePeriod', '12months')"
            :class="['px-3 py-1.5 rounded-lg transition-all cursor-pointer', selectedPeriod === '12months' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900']"
          >
            12 Mois
          </button>
          <button
            @click="$emit('changePeriod', 'school_year')"
            :class="['px-3 py-1.5 rounded-lg transition-all cursor-pointer', selectedPeriod === 'school_year' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900']"
          >
            Année Scolaire
          </button>
          <button
            @click="$emit('changePeriod', 'current_month')"
            :class="['px-3 py-1.5 rounded-lg transition-all cursor-pointer', selectedPeriod === 'current_month' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900']"
          >
            Mois Précis
          </button>
        </div>

        <!-- Sélecteur Année scolaire -->
        <select
          v-if="selectedPeriod === 'school_year'"
          :value="selectedSchoolYear"
          @change="$emit('update:selectedSchoolYear', ($event.target as HTMLSelectElement).value); $emit('periodChanged')"
          class="text-xs bg-white border border-gray-200 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="2025-2026">Sept 2025 – Juin 2026</option>
          <option value="2026-2027">Sept 2026 – Juin 2027</option>
        </select>

        <!-- Sélecteur Mois -->
        <input
          v-if="selectedPeriod === 'current_month'"
          :value="selectedMonth"
          type="month"
          @change="$emit('update:selectedMonth', ($event.target as HTMLInputElement).value); $emit('periodChanged')"
          class="text-xs bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />

        <!-- Boutons d'Action -->
        <button
          @click="$emit('openExpenseModal')"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-medium transition-colors shadow-xs cursor-pointer"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Dépense</span>
        </button>

        <button
          @click="$emit('exportCsv')"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-medium transition-colors shadow-xs cursor-pointer"
          title="Exporter pour un comptable"
        >
          <Download class="w-3.5 h-3.5" />
          <span>Export CSV</span>
        </button>

        <button
          @click="$emit('triggerPrint')"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-medium transition-colors shadow-xs cursor-pointer"
          title="Imprimer ou enregistrer en PDF"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>PDF / Print</span>
        </button>
      </div>
    </div>

    <!-- Navigation par Onglets Métiers -->
    <div class="flex items-center gap-1 mt-5 pt-3 border-t border-gray-100 overflow-x-auto text-xs">
      <button
        v-for="tab in accountingTabs"
        :key="tab.id"
        @click="$emit('update:activeSubTab', tab.id)"
        :class="[
          'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl font-medium whitespace-nowrap transition-all cursor-pointer',
          activeSubTab === tab.id
            ? 'bg-indigo-600 text-white shadow-xs'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
        ]"
      >
        <component :is="tab.icon" class="w-4 h-4" />
        <span>{{ tab.label }}</span>
        <span
          v-if="tab.badge"
          :class="[
            'text-[10px] px-1.5 py-0.2 rounded-full font-bold',
            activeSubTab === tab.id ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700'
          ]"
        >
          {{ tab.badge }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ExpenseItem } from '../../types/financial';
import { getCategoryLabel, getCategoryBadgeClass } from '../../composables/useFinancialFormatters';

defineProps<{
  expensesList: ExpenseItem[];
  totalExpenses: number;
  recurringAmount: number;
  suppliesAmount: number;
  cashAmount: number;
}>();
</script>

<template>
  <div class="space-y-6">
    <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
      <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">JOURNAL GÉNÉRAL DES DÉPENSES & CHARGES D'EXPLOITATION</h2>
      <p class="text-xs text-gray-600 mt-0.5">
        Livre comptable exhaustif des factures, loyers, salaires annexes et achats d'exploitation
      </p>
    </div>

    <!-- KPIs Dépenses -->
    <div class="avoid-break grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
      <div class="p-3 bg-red-50/70 rounded-xl border border-red-200">
        <span class="text-red-700 uppercase text-[10px] tracking-wider font-semibold">Total Dépenses</span>
        <p class="text-lg font-black text-red-600 mt-0.5">{{ totalExpenses.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-red-600 mt-0.5">{{ expensesList.length }} écritures</p>
      </div>
      <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
        <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Charges Récurrentes</span>
        <p class="text-lg font-black text-indigo-900 mt-0.5">{{ recurringAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-indigo-600 mt-0.5">Loyer, abonnements...</p>
      </div>
      <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200">
        <span class="text-amber-700 uppercase text-[10px] tracking-wider font-semibold">Fournitures & Entretien</span>
        <p class="text-lg font-black text-amber-900 mt-0.5">{{ suppliesAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-amber-600 mt-0.5">Consommables</p>
      </div>
      <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
        <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Règlements Espèces</span>
        <p class="text-lg font-black text-gray-900 mt-0.5">{{ cashAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-gray-500 mt-0.5">Sorties de caisse</p>
      </div>
    </div>

    <!-- Tableau Dépenses -->
    <div class="avoid-break">
      <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
        <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
          <tr>
            <th class="py-2.5 px-3">Date</th>
            <th class="py-2.5 px-3">Catégorie</th>
            <th class="py-2.5 px-3">Libellé / Titre de dépense</th>
            <th class="py-2.5 px-3">Réf / Justificatif</th>
            <th class="py-2.5 px-3">Mode</th>
            <th class="py-2.5 px-3 text-center">Récurrent</th>
            <th class="py-2.5 px-3 text-right font-bold">Montant (DT)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="e in expensesList" :key="e.id" class="avoid-break hover:bg-gray-50/50">
            <td class="py-2 px-3 text-gray-700">{{ e.date || '—' }}</td>
            <td class="py-2 px-3">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="getCategoryBadgeClass(e.category)">
                {{ getCategoryLabel(e.category) }}
              </span>
            </td>
            <td class="py-2 px-3 font-bold text-gray-900">{{ e.title }}</td>
            <td class="py-2 px-3 font-mono text-[11px] text-gray-500">{{ e.receipt_reference || '—' }}</td>
            <td class="py-2 px-3 text-gray-600 capitalize">{{ e.payment_method || 'especes' }}</td>
            <td class="py-2 px-3 text-center">
              <span v-if="e.is_recurring" class="text-indigo-600 font-bold">Oui</span>
              <span v-else class="text-gray-400">Non</span>
            </td>
            <td class="py-2 px-3 text-right font-black text-red-600">{{ Number(e.amount || 0).toFixed(2) }} DT</td>
          </tr>
          <tr v-if="expensesList.length === 0">
            <td colspan="7" class="py-8 text-center text-gray-400">Aucune dépense enregistrée sur cette période</td>
          </tr>
        </tbody>
        <tfoot v-if="expensesList.length > 0" class="bg-gray-100 font-bold border-t-2 border-gray-300">
          <tr>
            <td colspan="6" class="py-2.5 px-3 text-right uppercase text-[11px] text-gray-700">Total Général des Dépenses :</td>
            <td class="py-2.5 px-3 text-right font-black text-red-600 text-sm">{{ totalExpenses.toFixed(2) }} DT</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

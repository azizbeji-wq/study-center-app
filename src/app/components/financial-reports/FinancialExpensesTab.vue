<script setup lang="ts">
import { ref, computed } from 'vue';
import { Zap, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import type { ExpenseItem } from '../../types/financial';
import { getCategoryLabel, getCategoryBadgeClass } from '../../composables/useFinancialFormatters';

const props = defineProps<{
  expenses: ExpenseItem[];
  kpis: any;
  expenseCategoriesSummary: any[];
  expenseDonutSlices: any[];
}>();

const emit = defineEmits<{
  (e: 'openModal', expense?: ExpenseItem): void;
  (e: 'deleteExpense', id: number): void;
  (e: 'generateRecurring'): void;
}>();

const expenseSearch = ref('');
const expenseCategoryFilter = ref('');
const expensePaymentMethodFilter = ref('');

const filteredExpenses = computed(() => {
  return props.expenses.filter(exp => {
    if (expenseCategoryFilter.value && exp.category !== expenseCategoryFilter.value) return false;
    if (expensePaymentMethodFilter.value && exp.payment_method !== expensePaymentMethodFilter.value) return false;
    if (expenseSearch.value) {
      const q = expenseSearch.value.toLowerCase();
      const matchTitle = exp.title?.toLowerCase().includes(q);
      const matchRef = exp.receipt_reference?.toLowerCase().includes(q);
      const matchNotes = exp.notes?.toLowerCase().includes(q);
      if (!matchTitle && !matchRef && !matchNotes) return false;
    }
    return true;
  });
});

const totalFilteredExpenses = computed(() => {
  return filteredExpenses.value.reduce((sum, e) => sum + Number(e.amount || 0), 0);
});
</script>

<template>
  <div class="space-y-6">
    <!-- Graphique en Anneau & Statistiques Dépenses -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Anneau SVG Dynamique -->
      <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs flex flex-col items-center justify-center">
        <h3 class="text-sm font-bold text-gray-900 mb-4 self-start">Ventilation par Catégorie</h3>
        
        <div class="relative w-44 h-44 my-2">
          <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
            <circle
              v-for="(slice, idx) in expenseDonutSlices"
              :key="idx"
              cx="50"
              cy="50"
              r="38"
              fill="transparent"
              :stroke="slice.color"
              stroke-width="14"
              :stroke-dasharray="`${slice.strokeLength} ${2 * Math.PI * 38}`"
              :stroke-dashoffset="slice.strokeOffset"
              class="transition-all duration-500 hover:opacity-85"
            >
              <title>{{ slice.label }} : {{ slice.amount }} DT ({{ slice.percentage }}%)</title>
            </circle>
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <span class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Total</span>
            <span class="text-base font-black text-gray-900">{{ (kpis?.totalExpenses || 0).toLocaleString('fr-FR') }} DT</span>
          </div>
        </div>
      </div>

      <!-- Légende détaillée des 8 catégories -->
      <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs lg:col-span-2 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900">Catégories de Dépenses</h3>
            <button
              @click="emit('generateRecurring')"
              class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
              title="Génère automatiquement les dépenses récurrentes (loyer, internet...) pour ce mois"
            >
              <Zap class="w-3.5 h-3.5" />
              <span>Générer Récurrentes (Loyer, etc.)</span>
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div
              v-for="item in expenseCategoriesSummary"
              :key="item.category"
              class="p-2.5 rounded-xl border border-gray-100 bg-gray-50/50 flex items-center justify-between"
            >
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: item.color }"></span>
                <div>
                  <p class="font-medium text-gray-900">{{ item.label }}</p>
                  <p class="text-[10px] text-gray-500">{{ item.count }} écriture(s)</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold text-gray-900">{{ item.amount.toLocaleString('fr-FR') }} DT</p>
                <p class="text-[10px] text-gray-500">{{ item.percentage }}%</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Table Complète des Dépenses Réelles avec CRUD -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
      <!-- Barre d'outils et filtres -->
      <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
          <input
            v-model="expenseSearch"
            type="text"
            placeholder="Rechercher une facture ou dépense..."
            class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />

          <select
            v-model="expenseCategoryFilter"
            class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Toutes catégories</option>
            <option value="loyer">Loyer</option>
            <option value="électricité">Électricité / Eau</option>
            <option value="fournitures">Fournitures & Copies</option>
            <option value="entretien">Entretien</option>
            <option value="publicité">Publicité & Marketing</option>
            <option value="internet_tel">Internet & Tel</option>
            <option value="salaires">Salaires Annexes</option>
            <option value="autres">Autres</option>
          </select>

          <select
            v-model="expensePaymentMethodFilter"
            class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Tous règlements</option>
            <option value="especes">Espèces</option>
            <option value="cheque">Chèque</option>
            <option value="virement">Virement</option>
            <option value="carte">Carte bancaire</option>
          </select>
        </div>

        <button
          @click="emit('openModal')"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-xs cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>+ Nouvelle Dépense</span>
        </button>
      </div>

      <!-- Tableau des dépenses -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
          <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200">
            <tr>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Libellé / Titre</th>
              <th class="py-3 px-3">Catégorie</th>
              <th class="py-3 px-3">Mode Règlement</th>
              <th class="py-3 px-3">Justificatif / Ref</th>
              <th class="py-3 px-3">Enregistrée par</th>
              <th class="py-3 px-3 text-center">Récurrence</th>
              <th class="py-3 px-4 text-right">Montant</th>
              <th class="py-3 px-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="exp in filteredExpenses" :key="exp.id" class="hover:bg-gray-50/80 transition-colors">
              <td class="py-3 px-4 text-gray-600 whitespace-nowrap">
                {{ exp.date }}
              </td>
              <td class="py-3 px-4 font-semibold text-gray-900">
                {{ exp.title }}
                <span v-if="exp.notes" class="block text-[11px] text-gray-400 font-normal truncate max-w-xs">{{ exp.notes }}</span>
              </td>
              <td class="py-3 px-3">
                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border', getCategoryBadgeClass(exp.category)]">
                  {{ getCategoryLabel(exp.category) }}
                </span>
              </td>
              <td class="py-3 px-3 text-gray-600">
                <span class="capitalize">{{ exp.payment_method || 'Espèces' }}</span>
              </td>
              <td class="py-3 px-3 font-mono text-gray-500">
                {{ exp.receipt_reference || '—' }}
              </td>
              <td class="py-3 px-3 whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                  :class="exp.creator?.role === 'secretary' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'"
                >
                  <span>{{ exp.creator?.name || 'Directeur Fondateur' }}</span>
                  <span class="text-[9px] font-medium opacity-75">({{ exp.creator?.role === 'secretary' ? 'Personnel' : 'Directeur' }})</span>
                </span>
              </td>
              <td class="py-3 px-3 text-center">
                <span v-if="exp.is_recurring" class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-[10px] font-semibold border border-blue-200">
                  Mensuel
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="py-3 px-4 text-right font-black text-gray-900 text-sm">
                {{ Number(exp.amount).toLocaleString('fr-FR') }} DT
              </td>
              <td class="py-3 px-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <button
                    @click="emit('openModal', exp)"
                    class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer"
                    title="Modifier"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="emit('deleteExpense', exp.id)"
                    class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                    title="Supprimer"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredExpenses.length === 0">
              <td colspan="9" class="py-10 text-center text-gray-400">
                Aucune dépense trouvée pour les critères sélectionnés.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="filteredExpenses.length > 0" class="bg-gray-50 font-bold border-t border-gray-200">
            <tr>
              <td colspan="7" class="py-3 px-4 text-right uppercase text-[11px] text-gray-600 tracking-wider">Total Dépenses Filtrées</td>
              <td class="py-3 px-4 text-right text-red-600 text-sm font-black">{{ totalFilteredExpenses.toLocaleString('fr-FR') }} DT</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

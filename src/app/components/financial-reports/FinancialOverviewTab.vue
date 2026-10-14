<script setup lang="ts">
import {
  TrendingUp, TrendingDown, DollarSign, Users, CheckCircle2,
  Calendar, Zap
} from 'lucide-vue-next';
import { getCategoryLabel } from '../../composables/useFinancialFormatters';

defineProps<{
  kpis: any;
  chartPoints: any[];
  profitLinePath: string;
  expenseCategoriesSummary: any[];
  expenseDonutSlices: any[];
}>();
</script>

<template>
  <div class="space-y-6">
    <!-- 6 Cartes KPIs Réels -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
      <!-- Bénéfice Net Réel -->
      <div class="bg-gradient-to-br from-emerald-500 to-teal-700 text-white rounded-2xl p-4 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between text-emerald-100 mb-1">
          <span class="text-xs font-semibold uppercase tracking-wider">Bénéfice Net</span>
          <TrendingUp class="w-4 h-4" />
        </div>
        <p class="text-2xl font-black">{{ (kpis.netProfit || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal">DT</span></p>
        <p class="text-[11px] text-emerald-100 mt-1">
          Encaissements − Dépenses − Parts Profs
        </p>
      </div>

      <!-- Recettes Encaissées -->
      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between text-gray-500 mb-1">
          <span class="text-xs font-semibold uppercase tracking-wider">Recettes Totales</span>
          <div class="w-7 h-7 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
            <DollarSign class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-black text-gray-900">{{ (kpis.totalRevenue || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
        <p class="text-[11px] text-gray-500 mt-1">
          Élèves & Garderie encaissés
        </p>
      </div>

      <!-- Dépenses Réelles -->
      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between text-gray-500 mb-1">
          <span class="text-xs font-semibold uppercase tracking-wider">Dépenses Réelles</span>
          <div class="w-7 h-7 bg-red-50 text-red-600 rounded-lg flex items-center justify-center">
            <TrendingDown class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-black text-red-600">{{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
        <p class="text-[11px] text-gray-500 mt-1">
          Loyer, STEG, Fournitures...
        </p>
      </div>

      <!-- Parts Enseignants Dues -->
      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between text-gray-500 mb-1">
          <span class="text-xs font-semibold uppercase tracking-wider">Parts Enseignants</span>
          <div class="w-7 h-7 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
            <Users class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-black text-purple-700">{{ (kpis.totalTeacherPayouts || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
        <p class="text-[11px] text-gray-500 mt-1">
          Honoraires calculés sur séances
        </p>
      </div>

      <!-- Taux de Recouvrement -->
      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between text-gray-500 mb-1">
          <span class="text-xs font-semibold uppercase tracking-wider">Recouvrement</span>
          <div class="w-7 h-7 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
            <CheckCircle2 class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-black text-blue-700">{{ kpis.recoveryRate || 100 }}%</p>
        <p class="text-[11px] text-amber-600 font-medium mt-1">
          Reste dû : {{ (kpis.totalUnpaid || 0).toLocaleString('fr-FR') }} DT
        </p>
      </div>

      <!-- Recettes Prévues (Mois Prochain) -->
      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between text-gray-500 mb-1">
          <span class="text-xs font-semibold uppercase tracking-wider">Prévisionnel</span>
          <div class="w-7 h-7 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
            <Calendar class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-black text-gray-900">{{ (kpis.projectedNextMonthRevenue || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
        <p class="text-[11px] text-gray-500 mt-1">
          ARPU: {{ kpis.averageRevenuePerStudent || 0 }} DT / élève
        </p>
      </div>
    </div>

    <!-- Graphique Évolution Mensuelle Réelle -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
          <h3 class="text-lg font-bold text-gray-900">Évolution Mensuelle Réelle</h3>
          <p class="text-xs text-gray-500 mt-0.5">Recettes encaissées vs Dépenses vs Marge nette mensuelle</p>
        </div>

        <!-- Légende Graphique -->
        <div class="flex items-center gap-4 text-xs font-medium">
          <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
            <span class="text-gray-700">Recettes Encaissées</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            <span class="text-gray-700">Dépenses Totales</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
            <span class="text-gray-700">Bénéfice Net</span>
          </div>
        </div>
      </div>

      <!-- Graphique SVG Interactif -->
      <div class="w-full overflow-x-auto">
        <svg class="w-full min-w-[600px] h-72" viewBox="0 0 620 280">
          <!-- Grille Horizontale -->
          <line x1="40" y1="40" x2="600" y2="40" stroke="#f3f4f6" stroke-width="1" stroke-dasharray="4,4" />
          <line x1="40" y1="90" x2="600" y2="90" stroke="#f3f4f6" stroke-width="1" stroke-dasharray="4,4" />
          <line x1="40" y1="140" x2="600" y2="140" stroke="#f3f4f6" stroke-width="1" stroke-dasharray="4,4" />
          <line x1="40" y1="190" x2="600" y2="190" stroke="#f3f4f6" stroke-width="1" stroke-dasharray="4,4" />
          <line x1="40" y1="240" x2="600" y2="240" stroke="#e5e7eb" stroke-width="1.5" />

          <!-- Barres Doubles (Recettes vs Dépenses) -->
          <g v-for="(p, idx) in chartPoints" :key="'bar-' + idx">
            <!-- Barre Recette (Indigo) -->
            <rect
              :x="p.x - 14"
              :y="p.revenueY"
              width="12"
              :height="Math.max(2, 240 - p.revenueY)"
              rx="3"
              fill="#4f46e5"
              class="transition-all duration-300 hover:opacity-80 cursor-pointer"
            >
              <title>{{ p.label }}: {{ p.revenue }} DT Recettes (Élèves: {{ p.students }} DT, Garderie: {{ p.daycare }} DT)</title>
            </rect>

            <!-- Barre Dépense (Rouge) -->
            <rect
              :x="p.x + 2"
              :y="p.expenseY"
              width="12"
              :height="Math.max(2, 240 - p.expenseY)"
              rx="3"
              fill="#ef4444"
              class="transition-all duration-300 hover:opacity-80 cursor-pointer"
            >
              <title>{{ p.label }}: {{ p.expenses }} DT Dépenses réelles</title>
            </rect>

            <!-- Label Mois -->
            <text :x="p.x" y="260" font-size="11" fill="#6b7280" text-anchor="middle" font-weight="500">
              {{ p.label }}
            </text>
          </g>

          <!-- Ligne de Tendance Bénéfice Net (Vert Émeraude) -->
          <path
            v-if="profitLinePath"
            :d="profitLinePath"
            fill="none"
            stroke="#10b981"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
          />

          <!-- Points Bénéfice Net -->
          <g v-for="(p, idx) in chartPoints" :key="'point-' + idx">
            <circle
              :cx="p.x"
              :cy="p.profitY"
              r="4.5"
              fill="#10b981"
              stroke="#ffffff"
              stroke-width="2"
              class="cursor-pointer"
            >
              <title>{{ p.label }}: Bénéfice net = {{ p.netProfit }} DT</title>
            </circle>
          </g>
        </svg>
      </div>
    </div>

    <!-- 2 Colonnes : Répartition des Dépenses & Synthèse de Marge -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Répartition des Dépenses Réelles (Donut SVG) -->
      <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-base font-bold text-gray-900">Répartition des Dépenses</h3>
              <p class="text-xs text-gray-500 mt-0.5">Par poste de coût réel enregistré</p>
            </div>
            <span class="text-xs font-bold text-gray-900 bg-gray-100 px-2.5 py-1 rounded-lg">
              Total: {{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }} DT
            </span>
          </div>

          <div v-if="expenseCategoriesSummary.length === 0" class="py-12 text-center text-xs text-gray-400">
            Aucune dépense enregistrée pour la période sélectionnée.
          </div>

          <div v-else class="flex flex-col sm:flex-row items-center gap-6 mt-2">
            <!-- Donut SVG -->
            <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
              <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                <circle cx="50" cy="50" r="38" fill="transparent" stroke="#f3f4f6" stroke-width="14" />
                <circle
                  v-for="(slice, idx) in expenseDonutSlices"
                  :key="idx"
                  cx="50"
                  cy="50"
                  r="38"
                  fill="transparent"
                  :stroke="slice.color"
                  stroke-width="14"
                  :stroke-dasharray="`${slice.strokeLength} 238.76`"
                  :stroke-dashoffset="slice.strokeOffset"
                  class="transition-all duration-500"
                />
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                <span class="text-xs text-gray-400 font-medium uppercase">Dépenses</span>
                <span class="text-base font-black text-gray-900 leading-tight">{{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }}</span>
                <span class="text-[10px] text-gray-400">DT</span>
              </div>
            </div>

            <!-- Liste des Postes -->
            <div class="flex-1 w-full space-y-2">
              <div
                v-for="cat in expenseCategoriesSummary"
                :key="cat.category"
                class="flex items-center justify-between text-xs py-1 px-2 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: cat.color }"></span>
                  <span class="font-medium text-gray-700 capitalize truncate">{{ getCategoryLabel(cat.category) }}</span>
                </div>
                <div class="text-right shrink-0">
                  <span class="font-bold text-gray-900">{{ Number(cat.total || 0).toLocaleString('fr-FR') }} DT</span>
                  <span class="text-[10px] text-gray-500 ml-1.5">({{ cat.percentage }}%)</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <p class="text-[11px] text-gray-500 mt-4 pt-3 border-t border-gray-100 flex items-center gap-1.5">
          <Zap class="w-3.5 h-3.5 text-amber-500" />
          <span>Conseil : les charges fixes (loyer + électricité) représentent {{ kpis.fixedCostsRate || 0 }}% des dépenses.</span>
        </p>
      </div>

      <!-- Rapprochement Comptable & Synthèse de Marge -->
      <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-base font-bold text-gray-900">Rapprochement & Cascade de Marge</h3>
              <p class="text-xs text-gray-500 mt-0.5">Du C.A. brut encaissé au résultat net distribuable</p>
            </div>
            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
              Marge Nette : {{ kpis.netMarginRate || 0 }}%
            </span>
          </div>

          <div class="space-y-3">
            <!-- Ligne 1: Chiffre d'Affaires Encaissé -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
              <div>
                <p class="text-xs font-semibold text-gray-900">Chiffre d'Affaires Encaissé</p>
                <p class="text-[11px] text-gray-500">Paiements élèves ({{ (kpis.studentsRevenue || 0).toLocaleString('fr-FR') }} DT) + Garderie ({{ (kpis.daycareRevenue || 0).toLocaleString('fr-FR') }} DT)</p>
              </div>
              <p class="text-sm font-black text-gray-900">+{{ (kpis.totalRevenue || 0).toLocaleString('fr-FR') }} DT</p>
            </div>

            <!-- Ligne 2: Moins Rémunération des Enseignants -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-purple-50/70 border border-purple-100">
              <div>
                <p class="text-xs font-semibold text-purple-900">− Parts des Enseignants (Honoraires)</p>
                <p class="text-[11px] text-purple-600">Calculées au prorata des séances ou forfaits</p>
              </div>
              <p class="text-sm font-black text-purple-700">−{{ (kpis.totalTeacherPayouts || 0).toLocaleString('fr-FR') }} DT</p>
            </div>

            <!-- Ligne 3: Marge Brute Centre -->
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-indigo-50 border border-indigo-100 font-bold text-indigo-950 text-xs">
              <span>= Marge Brute du Centre</span>
              <span class="text-sm font-black text-indigo-700">{{ ((kpis.totalRevenue || 0) - (kpis.totalTeacherPayouts || 0)).toLocaleString('fr-FR') }} DT</span>
            </div>

            <!-- Ligne 4: Moins Dépenses Réelles d'Exploitation -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-red-50/70 border border-red-100">
              <div>
                <p class="text-xs font-semibold text-red-900">− Dépenses d'Exploitation Réelles</p>
                <p class="text-[11px] text-red-600">Loyer, électricité, fournitures, entretien, salaires annexes...</p>
              </div>
              <p class="text-sm font-black text-red-600">−{{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }} DT</p>
            </div>

            <!-- Ligne 5: Résultat Net Final -->
            <div class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-600 text-white shadow-xs">
              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-100">Résultat Net / Bénéfice Disponible</p>
                <p class="text-[11px] text-emerald-200">Trésorerie nette générée sur la période</p>
              </div>
              <p class="text-lg font-black">{{ (kpis.netProfit || 0).toLocaleString('fr-FR') }} DT</p>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span>Taux de marge brute : <strong>{{ kpis.grossMarginRate || 0 }}%</strong></span>
          <span>Bénéfice / jour : <strong>{{ Math.round((kpis.netProfit || 0) / 30) }} DT</strong></span>
        </div>
      </div>
    </div>
  </div>
</template>
